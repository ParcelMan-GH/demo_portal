<?php

namespace App\Services\Agent;

use App\Models\AgentCallLog;
use App\Models\ShipmentItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The 72-hour SLA and the 10-unconfirmed cap: one rule, one place.
 *
 * ## What expires, and why this shape
 *
 * A parcel that has reached a destination hub has a pickup code, and the agent's
 * commission for it does not unlock until they confirm that code with the
 * customer. That gate has a deadlock in it: an item nobody can confirm blocks the
 * agent's whole day indefinitely, so the agent is paid nothing for work they did
 * do. This service resolves the deadlock — after 72 hours the item stops blocking,
 * and the commission it was holding is forfeited permanently.
 *
 * ## The anchor is hub arrival, not dispatch
 *
 * The clock runs from `arrived_at_hub_at`. That is not a preference: `pickup_code`
 * is minted in the same `update()` as `arrived_at_hub_at` (HubController hub
 * intake), so hub arrival is by construction the first moment a code exists to be
 * collected. Anchoring on dispatch would start a clock on parcels that have no code
 * yet, and the rule would forfeit money for failing to confirm something that
 * could not be confirmed. `AgentCallConfirmationService` refuses to require
 * confirmation before the code exists for exactly that reason; this service
 * inherits the same boundary by filtering through `logRequiresConfirmation`.
 *
 * ## Exemptions still win
 *
 * Nothing here invents its own idea of "unconfirmed". A candidate item must
 * genuinely require confirmation by the existing rule — so a cancelled recipient,
 * a returned parcel, and an unreachable customer the agent kept calling are all
 * spared, on the same terms as the gate that would otherwise block the payout.
 * This is deliberate: a second, looser definition of "unconfirmed" living in a
 * sweeper is how an agent ends up forfeited for a parcel that was returned to the
 * vendor through no fault of theirs.
 *
 * ## Forfeiture never touches the tier maths of other parcels
 *
 * Commission is a band function of the day's *cumulative* collection, not a sum of
 * per-item amounts (see AgentCommissionController::lineItems). Removing an expired
 * parcel's contribution from the running total would re-band every later call and
 * silently change what the agent earned for parcels that are not expired — money
 * taken from one parcel as a side effect of another's forfeiture. That is
 * indefensible, so expiry marks the item and nothing else. Reporting layers read
 * the flag; the band arithmetic is left alone.
 *
 * ## Nothing is applied retroactively unless asked
 *
 * `sweep()` only considers items that cross the threshold after deploy.
 * `--backfill` lifts that. On the live database 13 parcels were already past 72
 * hours at the time this was written, so a retroactive pass is a decision about
 * real earnings and is left to a human to invoke.
 */
class AgentCommissionExpirationService
{
    /** Hours after hub arrival before an unconfirmed parcel forfeits. */
    public const SLA_HOURS = 72;

    /** Unconfirmed-past-window parcels at which the cap rule fires. */
    public const UNCONFIRMED_CAP = 10;

    public const REASON_SLA = 'sla_72h';
    public const REASON_CAP = 'cap_10';

    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_ADMIN = 'admin';

    public function __construct(
        private readonly AgentCallConfirmationService $confirmation,
    ) {}

    /**
     * When a parcel that arrived at `$arrivedAt` forfeits.
     */
    public function expiresAt(Carbon|string|null $arrivedAt): ?Carbon
    {
        if (blank($arrivedAt)) {
            return null;
        }

        return Carbon::parse($arrivedAt)->addHours(self::SLA_HOURS);
    }

    /**
     * Hours left before forfeiture. Negative once past — callers decide how to
     * present that rather than getting a clamped zero that hides the overrun.
     */
    public function hoursRemaining(ShipmentItem $item, ?Carbon $now = null): ?float
    {
        $expiresAt = $this->expiresAt($item->arrived_at_hub_at);

        if (! $expiresAt) {
            return null;
        }

        return ($now ?? Carbon::now())->diffInMinutes($expiresAt, false) / 60;
    }

    /**
     * Items whose window has closed and which still require confirmation.
     *
     * `$backfill` false (the default) additionally requires that the window closed
     * *after* the cutoff given, so a first run does not reach back through history
     * and forfeit parcels nobody was ever told about.
     *
     * @return Collection<int, ShipmentItem>
     */
    public function expirableItems(
        ?int $agentId = null,
        bool $backfill = false,
        ?Carbon $now = null,
        int $limit = 1000,
    ): Collection {
        $now ??= Carbon::now();
        $threshold = $now->copy()->subHours(self::SLA_HOURS);

        $query = ShipmentItem::query()
            ->whereNotNull('agent_id')
            ->whereNotNull('pickup_code')
            ->whereNotNull('arrived_at_hub_at')
            ->where('arrived_at_hub_at', '<=', $threshold)
            ->whereNull('commission_expired_at')
            // A parcel already released to the customer is settled, whatever the
            // agent did or did not confirm on their side — the hub verified the
            // same code at the desk.
            ->whereNull('released_at');

        if ($agentId !== null) {
            $query->where('agent_id', $agentId);
        }

        if (! $backfill) {
            /*
             * Without this the first run after deploy would sweep up every parcel
             * that has ever been late, in one go, and forfeit all of it before
             * anyone could see it happening. Restricting to windows that closed
             * after the sweep was first deployed means the rule starts applying to
             * parcels as they age out, which is what "an SLA" means.
             */
            $query->where('arrived_at_hub_at', '>=', $this->deployedAt($now));
        }

        $items = $query->with(['agentCallLogs' => function ($q) use ($agentId) {
            if ($agentId !== null) {
                $q->where('agent_id', $agentId);
            }
            $q->orderByDesc('id');
        }])->orderBy('arrived_at_hub_at')->limit($limit)->get();

        // The gate's own judgement, applied item by item. Kept out of SQL because
        // the exemption rule counts attempts across days and calls
        // AgentCallConfirmationService, which is the point of reusing it.
        return $items
            ->filter(fn (ShipmentItem $item) => $this->requiresConfirmation($item))
            ->values();
    }

    /**
     * Whether this item is still genuinely owed a confirmation.
     *
     * Delegates to the confirmation service so the sweeper and the payout gate can
     * never disagree about which parcels are outstanding.
     */
    public function requiresConfirmation(ShipmentItem $item): bool
    {
        $agentId = (int) ($item->agent_id ?? 0);

        if ($agentId === 0) {
            return false;
        }

        if ($item->commission_expired_at !== null || $item->commission_expiry_reversed_at !== null) {
            // Already dealt with once. A reversal is final for the sweeper: an
            // admin who reinstated a parcel outranks the timer, otherwise
            // reversing and re-expiring would be a loop.
            return false;
        }

        if ($this->confirmation->isItemConfirmed((int) $item->id, $agentId)) {
            return false;
        }

        // Released to the recipient at the hub: the code was verified at the desk,
        // so the agent's obligation is discharged.
        if ($this->confirmation->isSettledAtHub($item)) {
            return false;
        }

        $log = $item->agentCallLogs->first()
            ?? AgentCallLog::query()
                ->where('shipment_item_id', $item->id)
                ->where('agent_id', $agentId)
                ->orderByDesc('id')
                ->first();

        if (! $log) {
            return false;
        }

        return $this->confirmation->logRequiresConfirmation($log);
    }

    /**
     * The agent's live position against the cap, for the app and the dashboard.
     *
     * `unconfirmed` counts parcels still owed a confirmation whatever their age —
     * that is the number the agent can act on. `at_cap` is the escalation signal.
     *
     * @return array{unconfirmed: int, cap: int, at_cap: bool, expired: int, forgiven: int}
     */
    public function stateForAgent(int $agentId): array
    {
        $items = ShipmentItem::query()
            ->where('agent_id', $agentId)
            ->whereNotNull('pickup_code')
            ->whereNotNull('arrived_at_hub_at')
            ->whereNull('released_at')
            ->with(['agentCallLogs' => fn ($q) => $q->where('agent_id', $agentId)->orderByDesc('id')])
            ->get();

        $unconfirmed = 0;
        $expired = 0;
        $forgiven = 0;

        foreach ($items as $item) {
            if ($item->commission_expired_at !== null && $item->commission_expiry_reversed_at === null) {
                $expired++;

                continue;
            }

            if ($item->commission_expiry_reversed_at !== null) {
                $forgiven++;
            }

            if ($this->requiresConfirmation($item)) {
                $unconfirmed++;
            }
        }

        return [
            'unconfirmed' => $unconfirmed,
            'cap' => self::UNCONFIRMED_CAP,
            'at_cap' => $unconfirmed >= self::UNCONFIRMED_CAP,
            'expired' => $expired,
            'forgiven' => $forgiven,
        ];
    }

    /**
     * Run the rule.
     *
     * Two passes, in order: the per-parcel SLA first, then the cap over whatever
     * the SLA left. On the current rules the two overlap heavily — a parcel past
     * 72 hours is exactly what the cap counts — so the cap mostly acts as a
     * backstop and an escalation signal rather than a second forfeiture wave. It
     * is implemented separately anyway so the recorded reason says which rule
     * actually caught an item, which is not reconstructable afterwards.
     *
     * @return array{sla_expired: int, cap_expired: int, agents_at_cap: array<int,int>, examined: int, dry_run: bool}
     */
    public function sweep(
        bool $backfill = false,
        ?int $agentId = null,
        bool $dryRun = false,
        ?Carbon $now = null,
    ): array {
        $now ??= Carbon::now();

        $slaExpired = 0;
        $capExpired = 0;
        $agentsAtCap = [];
        $examined = 0;

        // ---- Pass 1: the 72-hour SLA -------------------------------------
        foreach ($this->expirableItems($agentId, $backfill, $now) as $item) {
            $examined++;

            if ($dryRun) {
                $slaExpired++;

                continue;
            }

            if ($this->expireItem($item, self::REASON_SLA, self::SOURCE_SYSTEM, null, $now)) {
                $slaExpired++;
            }
        }

        // ---- Pass 2: the cap ---------------------------------------------
        $candidates = $this->expirableItems($agentId, $backfill, $now);

        $byAgent = $candidates->groupBy(fn (ShipmentItem $item) => (int) $item->agent_id);

        foreach ($byAgent as $ownerId => $items) {
            $outstanding = $items->filter(fn (ShipmentItem $i) => $this->requiresConfirmation($i));

            if ($outstanding->count() < self::UNCONFIRMED_CAP) {
                continue;
            }

            $agentsAtCap[$ownerId] = $outstanding->count();

            foreach ($outstanding as $item) {
                $examined++;

                if ($dryRun) {
                    $capExpired++;

                    continue;
                }

                $reason = sprintf(
                    'Agent %d reached %d unconfirmed parcels past the %d-hour window.',
                    $ownerId,
                    $outstanding->count(),
                    self::SLA_HOURS
                );

                if ($this->expireItem($item, self::REASON_CAP, self::SOURCE_SYSTEM, $reason, $now)) {
                    $capExpired++;
                }
            }
        }

        return [
            'sla_expired' => $slaExpired,
            'cap_expired' => $capExpired,
            'agents_at_cap' => $agentsAtCap,
            'examined' => $examined,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Forfeit one parcel's commission. Idempotent.
     *
     * Locked and re-checked inside the transaction because the sweeper and an
     * admin reversal can otherwise interleave: the admin reinstates a parcel
     * while a sweep that already read the row is mid-flight, and the forfeiture
     * lands on top of the reversal. `lockForUpdate` plus a fresh read of both
     * flags is what makes the last writer's intent win rather than a stale one.
     */
    public function expireItem(
        ShipmentItem $item,
        string $reason,
        string $source = self::SOURCE_SYSTEM,
        ?string $note = null,
        ?Carbon $now = null,
    ): bool {
        $now ??= Carbon::now();

        return DB::transaction(function () use ($item, $reason, $source, $note, $now) {
            $fresh = ShipmentItem::query()->whereKey($item->getKey())->lockForUpdate()->first();

            if (! $fresh) {
                return false;
            }

            if ($fresh->commission_expired_at !== null) {
                // Already forfeited — not an error, and not a second write. The
                // first reason recorded is the one that fired and stays.
                return false;
            }

            if ($fresh->commission_expiry_reversed_at !== null) {
                // An admin reinstated it. The timer does not get to overrule that
                // by firing again on the next sweep.
                return false;
            }

            $fresh->forceFill([
                'commission_expired_at' => $now,
                'commission_expiry_reason' => $reason,
                'commission_expiry_source' => $source,
                'commission_expiry_note' => $note,
            ])->save();

            Log::info('Agent commission forfeited', [
                'shipment_item_id' => $fresh->id,
                'agent_id' => $fresh->agent_id,
                'reason' => $reason,
                'source' => $source,
                'arrived_at_hub_at' => optional($fresh->arrived_at_hub_at)->toIso8601String(),
                'pickup_code_confirmed' => $this->confirmation->isItemConfirmed((int) $fresh->id, (int) $fresh->agent_id),
            ]);

            $item->setRawAttributes($fresh->getAttributes(), true);

            return true;
        });
    }

    /**
     * Reinstate a forfeited parcel. Admin-only action; the runtime caller enforces
     * that, this records it.
     *
     * Reversal is deliberately not a delete. The forfeiture happened, someone
     * overruled it, and both facts stay legible in the row.
     */
    public function reverseExpiry(ShipmentItem $item, int $adminId, ?string $note = null): bool
    {
        return DB::transaction(function () use ($item, $adminId, $note) {
            $fresh = ShipmentItem::query()->whereKey($item->getKey())->lockForUpdate()->first();

            if (! $fresh || $fresh->commission_expired_at === null || $fresh->commission_expiry_reversed_at !== null) {
                return false;
            }

            $fresh->forceFill([
                'commission_expiry_reversed_at' => Carbon::now(),
                'commission_expiry_note' => trim(sprintf(
                    '%s Reversed by admin #%d%s',
                    (string) $fresh->commission_expiry_note,
                    $adminId,
                    $note ? ': '.$note : ''
                )),
            ])->save();

            Log::info('Agent commission expiry reversed by admin', [
                'shipment_item_id' => $fresh->id,
                'agent_id' => $fresh->agent_id,
                'admin_id' => $adminId,
            ]);

            return true;
        });
    }

    /**
     * Whether an item's commission is forfeited right now.
     */
    public function isForfeited(ShipmentItem $item): bool
    {
        return $item->commission_expired_at !== null && $item->commission_expiry_reversed_at === null;
    }

    /**
     * When the sweep first ran, used to bound a non-backfill pass.
     *
     * The earliest expiry ever recorded is the honest answer: it is the moment the
     * rule started biting. Before any expiry exists, `now - SLA` is used so the
     * first run picks up parcels that cross the line from here on rather than
     * everything in history.
     */
    private function deployedAt(Carbon $now): Carbon
    {
        $earliest = ShipmentItem::query()->whereNotNull('commission_expired_at')->min('commission_expired_at');

        return $earliest ? Carbon::parse($earliest) : $now->copy()->subHours(self::SLA_HOURS);
    }
}
