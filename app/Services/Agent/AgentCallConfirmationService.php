<?php

namespace App\Services\Agent;

use App\Enums\ItemStatus;
use App\Models\AgentCallLog;
use App\Models\AgentDailyQuota;
use App\Models\ShipmentItem;
use App\Services\Agent\AgentCommissionExpirationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The pickup-code confirmation rule, in one place.
 *
 * The recipient already quotes a `pickup_code` to the external hub agent, who
 * verifies it in `HubController::release` before handing the parcel over. The
 * contact agent must now confirm the same code, and a day's commission does not
 * unlock until they have.
 *
 * This exists as a service rather than as conditions scattered across the
 * controllers because the commission figure is already derived independently in
 * six places (three controllers call `CommissionTier::findTierForAmount()`
 * directly). Adding a seventh derivation of "is this day payable" would guarantee
 * that the agent app and the admin ledger eventually disagree about what someone
 * is owed, which is the one thing that must not happen to money.
 *
 * The rule:
 *
 *   1. A call is only *required* to be confirmed once the parcel has a pickup
 *      code — which is minted at hub intake. Before that there is no code in
 *      existence to collect, so nothing is owed.
 *   2. Exempt by design, because the agent cannot control them: a cancelled
 *      recipient, a parcel that came back, and an unreachable customer *after*
 *      the agent has demonstrably kept trying.
 *   3. Confirmation is per parcel, not per call. An agent who rang three times
 *      has three logs but one parcel, so it is confirmed when any of their logs
 *      for it is confirmed.
 */
class AgentCallConfirmationService
{
    /** Wrong codes accepted against one call before it needs an admin. */
    public const MAX_CODE_ATTEMPTS = 5;

    /** Unreachable attempts before a customer stops holding up the day. */
    public const EXEMPT_UNREACHABLE_ATTEMPTS = 3;

    /** ...and on how many separate days those attempts must fall. */
    public const EXEMPT_UNREACHABLE_DAYS = 2;

    /**
     * Calls still awaiting a code confirmation, newest first, one row per parcel.
     *
     * This is what the agent app's "To Confirm" list renders. Only the latest
     * call per parcel is returned: a parcel called three times would otherwise
     * appear three times, and the agent only has one code to enter.
     */
    public function pendingForAgent(int $agentId, int $limit = 100): Collection
    {
        $latestLogIds = AgentCallLog::query()
            ->where('agent_id', $agentId)
            ->whereNotNull('shipment_item_id')
            ->selectRaw('MAX(id) as id')
            ->groupBy('shipment_item_id')
            ->pluck('id');

        if ($latestLogIds->isEmpty()) {
            return collect();
        }

        return AgentCallLog::query()
            ->with(['shipmentItem.shipment'])
            ->whereIn('id', $latestLogIds)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (AgentCallLog $log) => $this->logRequiresConfirmation($log))
            ->take($limit)
            ->values();
    }

    /**
     * Whether this call is one the agent still has to confirm.
     */
    public function logRequiresConfirmation(AgentCallLog $log): bool
    {
        $item = $log->shipmentItem;

        if (! $item) {
            return false;
        }

        // No code exists until the parcel reaches a destination hub.
        if (blank($item->pickup_code)) {
            return false;
        }

        if ($this->isItemConfirmed((int) $item->id, (int) $log->agent_id)) {
            return false;
        }

        /*
         * The customer collected it at the hub. The desk verified the same code
         * before handing the parcel over, so the agent's confirmation has nothing
         * left to prove — and requiring it would leave a parcel the customer
         * already has sitting in the agent's queue, blocking the day it belongs to.
         *
         * This is the real-time half of the rule: hub release writes `released_at`
         * and DELIVERED in one update, so the next read of this list drops the
         * parcel with no separate sync job to fall out of step.
         */
        if ($this->isSettledAtHub($item)) {
            return false;
        }

        // Already forfeited under the 72-hour SLA. It has stopped blocking the day;
        // re-listing it would put a parcel the agent can no longer be paid for back
        // in front of them.
        if ($this->isForfeited($item)) {
            return false;
        }

        return ! $this->isExemptLog($log);
    }

    /**
     * Whether the parcel was collected at the hub by the recipient.
     *
     * Requires both `released_at` and DELIVERED, because a rider handover sets
     * `released_at` with OUT_FOR_DELIVERY and does *not* verify the pickup code.
     * Treating that as settled would let an agent's obligation evaporate the moment
     * the parcel left the counter on a rider's bike, which is exactly the window
     * where a customer call still matters.
     */
    public function isSettledAtHub(ShipmentItem $item): bool
    {
        if ($item->released_at === null) {
            return false;
        }

        $status = $item->status instanceof ItemStatus ? $item->status->value : (string) $item->status;

        return $status === ItemStatus::DELIVERED->value;
    }

    /**
     * Whether the parcel's commission has been forfeited and not reinstated.
     */
    public function isForfeited(ShipmentItem $item): bool
    {
        return $item->commission_expired_at !== null
            && $item->commission_expiry_reversed_at === null;
    }

    /**
     * A parcel counts as confirmed if any of this agent's logs for it is.
     */
    public function isItemConfirmed(int $shipmentItemId, int $agentId): bool
    {
        return AgentCallLog::query()
            ->where('shipment_item_id', $shipmentItemId)
            ->where('agent_id', $agentId)
            ->whereNotNull('pickup_code_confirmed_at')
            ->exists();
    }

    /**
     * Outcomes that legitimately cannot be confirmed.
     *
     * Kept narrow on purpose. Widening it is the easy way to make this gate
     * meaningless, so anything added here should be something the agent
     * genuinely has no control over.
     */
    public function isExemptLog(AgentCallLog $log): bool
    {
        if ($log->outcome === AgentCallLog::OUTCOME_CANCELLED) {
            return true;
        }

        if ($log->shipmentItem?->status === ItemStatus::RETURNED) {
            return true;
        }

        // Unreachable only counts once the agent has clearly kept trying. A single
        // no-answer must not excuse the follow-up, or "unreachable" becomes the
        // cheapest way to clear a list. The attempts are already in this table, so
        // counting them costs nothing extra to track.
        if ($log->outcome === AgentCallLog::OUTCOME_UNREACHABLE) {
            $attempts = $this->unreachableAttempts((int) $log->shipment_item_id, (int) $log->agent_id);

            return $attempts['attempts'] >= self::EXEMPT_UNREACHABLE_ATTEMPTS
                && $attempts['days'] >= self::EXEMPT_UNREACHABLE_DAYS;
        }

        return false;
    }

    /**
     * How many times this agent has logged the parcel unreachable, and on how
     * many separate days.
     *
     * @return array{attempts: int, days: int}
     */
    public function unreachableAttempts(int $shipmentItemId, int $agentId): array
    {
        $rows = AgentCallLog::query()
            ->where('shipment_item_id', $shipmentItemId)
            ->where('agent_id', $agentId)
            ->where('outcome', AgentCallLog::OUTCOME_UNREACHABLE)
            ->get(['created_at']);

        return [
            'attempts' => $rows->count(),
            'days' => $rows
                ->map(fn ($row) => optional($row->created_at)->toDateString())
                ->filter()
                ->unique()
                ->count(),
        ];
    }

    /**
     * The confirmation state of one agent-day, for both the agent app and the
     * admin ledger.
     *
     * @return array{required: int, confirmed: int, exempt: int, pending: int, can_unlock: bool, pending_calls: array<int, array<string, mixed>>}
     */
    /**
     * @param  bool  $includeCodes  Whether to include the pickup codes themselves.
     *   TRUE ONLY FOR ADMIN CALLERS. The agent must never receive the code in a
     *   payload — handing it to them would let them read it and type it straight
     *   back, which makes the whole gate decorative. The desk needs it precisely
     *   because the agent may not have it.
     */
    public function stateForQuota(AgentDailyQuota $quota, bool $includeCodes = false): array
    {
        $agentId = (int) $quota->user_id;
        $day = Carbon::parse($quota->tracking_date)->toDateString();

        $itemIds = AgentCallLog::query()
            ->where('agent_id', $agentId)
            ->whereDate('created_at', $day)
            ->whereNotNull('shipment_item_id')
            ->distinct()
            ->pluck('shipment_item_id');

        $empty = [
            'required' => 0,
            'confirmed' => 0,
            'confirmed_by_agent' => 0,
            'confirmed_by_admin' => 0,
            'exempt' => 0,
            'settled_at_hub' => 0,
            'forfeited' => 0,
            'pending' => 0,
            'can_unlock' => true,
            'pending_calls' => [],
        ];

        if ($itemIds->isEmpty()) {
            // Nothing was called that day, so there is nothing to confirm. An
            // agent who called nobody is a separate problem from this rule, and
            // blocking their unlock here would be the wrong lever.
            return $empty;
        }

        $items = ShipmentItem::query()->whereIn('id', $itemIds)->get()->keyBy('id');

        $required = 0;
        $confirmed = 0;
        $confirmedByAgent = 0;
        $confirmedByAdmin = 0;
        $exempt = 0;
        $settledAtHub = 0;
        $forfeited = 0;
        $pending = [];

        foreach ($itemIds as $itemId) {
            $item = $items->get($itemId);

            if (! $item) {
                continue;
            }

            // No code minted yet: not required. Nothing is owed for a parcel that
            // has not reached a destination hub.
            if (blank($item->pickup_code)) {
                continue;
            }

            /*
             * Collected at the hub by the recipient. The desk verified the code, so
             * this is discharged and must not hold up the day. Checked before the
             * confirmation lookup because it is the stronger fact: it does not
             * matter whether the agent ever entered the code themselves.
             */
            if ($this->isSettledAtHub($item)) {
                $settledAtHub++;

                continue;
            }

            /*
             * Forfeited under the 72-hour SLA. It has stopped blocking the day —
             * that is the whole point of the rule — and it is reported separately
             * so the shortfall is visible rather than silently shrinking `required`.
             */
            if ($this->isForfeited($item)) {
                $forfeited++;

                continue;
            }

            $confirmingLog = AgentCallLog::query()
                ->where('shipment_item_id', $itemId)
                ->where('agent_id', $agentId)
                ->whereNotNull('pickup_code_confirmed_at')
                ->orderByDesc('pickup_code_confirmed_at')
                ->first();

            if ($confirmingLog) {
                $required++;
                $confirmed++;

                // Split by who did it. The desk confirming everything on the
                // agents' behalf must be visible, or it looks identical to agents
                // doing the work themselves.
                if ($confirmingLog->wasConfirmedByAdmin()) {
                    $confirmedByAdmin++;
                } else {
                    $confirmedByAgent++;
                }

                continue;
            }

            $log = AgentCallLog::query()
                ->where('agent_id', $agentId)
                ->where('shipment_item_id', $itemId)
                ->orderByDesc('id')
                ->first();

            if ($log && $this->isExemptLog($log)) {
                $exempt++;

                continue;
            }

            $required++;
            $pending[] = $this->pendingPayload($log, $item, $includeCodes);
        }

        return [
            'required' => $required,
            'confirmed' => $confirmed,
            'confirmed_by_agent' => $confirmedByAgent,
            'confirmed_by_admin' => $confirmedByAdmin,
            'exempt' => $exempt,
            // Discharged because the recipient collected at the desk.
            'settled_at_hub' => $settledAtHub,
            // Forfeited under the SLA. Reported so the day is not just "unlockable"
            // with a quietly smaller total — the shortfall is the point.
            'forfeited' => $forfeited,
            'pending' => count($pending),
            /*
             * Forfeited parcels deliberately do not appear in `pending`, so a day
             * whose only outstanding parcels have timed out becomes unlockable.
             * Without that the SLA would resolve nothing: the deadlock it exists to
             * break is exactly this flag staying false forever.
             */
            'can_unlock' => $pending === [],
            'pending_calls' => $pending,
        ];
    }

    /**
     * Whether a day's commission may be released.
     *
     * Deliberately conservative: without an agent id or a date there is nothing to
     * assess, so it refuses rather than assuming the day is clear.
     */
    public function canUnlock(AgentDailyQuota $quota): bool
    {
        if (! $quota->user_id || blank($quota->tracking_date)) {
            return false;
        }

        return $this->stateForQuota($quota)['can_unlock'];
    }

    /**
     * Check a submitted code against the parcel's pickup code.
     *
     * `counts_as_attempt` is returned rather than inferred by the caller from the
     * message text: only a genuinely wrong code should burn one of the limited
     * attempts, while re-submitting an already-confirmed parcel or one whose code
     * does not exist yet must not.
     *
     * @return array{ok: bool, message: ?string, counts_as_attempt: bool}
     */
    public function verify(AgentCallLog $log, string $submitted): array
    {
        $item = $log->shipmentItem;

        if (! $item) {
            return ['ok' => false, 'message' => 'This call has no parcel attached.', 'counts_as_attempt' => false];
        }

        if (blank($item->pickup_code)) {
            return [
                'ok' => false,
                'message' => 'This parcel has no pickup code yet — it has not reached a destination hub.',
                'counts_as_attempt' => false,
            ];
        }

        if ($log->pickup_code_confirmed_at !== null) {
            return ['ok' => false, 'message' => 'This parcel has already been confirmed.', 'counts_as_attempt' => false];
        }

        if ($this->totalCodeAttempts((int) $item->id, (int) $log->agent_id) >= self::MAX_CODE_ATTEMPTS) {
            return [
                'ok' => false,
                'message' => 'Too many incorrect attempts on this parcel. An admin can confirm it for you.',
                'counts_as_attempt' => false,
            ];
        }

        // hash_equals: the same timing-safe comparison HubController::release uses
        // on the same column, so the hub and the agent can never disagree about
        // whether a code is valid.
        if (! hash_equals((string) $item->pickup_code, trim($submitted))) {
            return ['ok' => false, 'message' => 'That code does not match this parcel.', 'counts_as_attempt' => true];
        }

        return ['ok' => true, 'message' => null, 'counts_as_attempt' => false];
    }

    /**
     * How many parcels this agent still has to confirm.
     *
     * A single number so the app can badge its "To Confirm" tab without pulling
     * the whole list. Deliberately not derived per quota-day: the earnings screen
     * renders up to fifty days and asking `stateForQuota` for each would put a few
     * hundred queries behind one mobile request.
     */
    public function countPendingForAgent(int $agentId): int
    {
        return $this->pendingForAgent($agentId, PHP_INT_MAX)->count();
    }

    /**
     * The pending list in its wire shape, so the app and the quota state agree.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingPayloadsForAgent(int $agentId, int $limit = 100): array
    {
        return $this->pendingForAgent($agentId, $limit)
            ->map(fn (AgentCallLog $log) => $this->pendingPayload($log, $log->shipmentItem))
            ->values()
            ->all();
    }

    /**
     * Record a correct code against a call.
     *
     * `confirmed_before_release` is captured rather than assumed: it is the only
     * record of whether the parcel had actually been handed over at the moment of
     * confirmation, which is what tells us later whether this gate is proving
     * receipt or only that the parcel reached the hub.
     */
    public function confirm(
        AgentCallLog $log,
        string $source,
        string $confirmedBy,
        int $confirmedByUserId
    ): AgentCallLog {
        $log->forceFill([
            'pickup_code_confirmed_at' => now(),
            'pickup_code_source' => $source,
            'pickup_code_confirmed_by' => $confirmedBy,
            'pickup_code_confirmed_by_user_id' => $confirmedByUserId,
            'pickup_code_confirmed_before_release' => $log->shipmentItem?->released_at === null,
        ])->save();

        return $log;
    }

    /**
     * Count a wrong code, so a call cannot be guessed at indefinitely.
     */
    public function recordFailedAttempt(AgentCallLog $log): void
    {
        $log->increment('pickup_code_attempts');
    }

    /**
     * Wrong-code attempts across every log this agent holds on the parcel.
     *
     * Counted per parcel rather than per call deliberately. The counter lives on
     * the call log, so a limit read from a single log could be reset simply by
     * logging another call against the same parcel. That matters more here than it
     * looks: `pickup_code` is four digits, and this code now gates money — the
     * difference between a real limit and a brute-forceable one.
     */
    public function totalCodeAttempts(int $shipmentItemId, int $agentId): int
    {
        return (int) AgentCallLog::query()
            ->where('shipment_item_id', $shipmentItemId)
            ->where('agent_id', $agentId)
            ->sum('pickup_code_attempts');
    }

    /**
     * The call an admin would be confirming on an agent's behalf: the agent's
     * most recent log for the parcel.
     */
    public function latestLogForItem(int $shipmentItemId, int $agentId): ?AgentCallLog
    {
        return AgentCallLog::query()
            ->where('shipment_item_id', $shipmentItemId)
            ->where('agent_id', $agentId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingPayload(?AgentCallLog $log, ShipmentItem $item, bool $includeCode = false): array
    {
        return [
            'call_log_id' => $log?->getKey(),
            'shipment_item_id' => $item->getKey(),
            'tracking_code' => $item->tracking_code,
            // The recipient lives on the item as delivery_recipient_*, and is
            // surfaced under the names the agent app already reads — the same
            // mapping AgentParcelController::getQueue uses for the same screen.
            'recipient_name' => $item->delivery_recipient_name,
            'recipient_phone' => $item->delivery_recipient_phone,
            'status' => $item->status instanceof ItemStatus ? $item->status->value : (string) $item->status,
            'outcome' => $log?->outcome,
            'called_at' => optional($log?->created_at)->toIso8601String(),
            /*
             * Only ever present for an admin caller. This key is the difference
             * between a gate and a formality: an agent holding the code would simply
             * read it back, so it must not appear in the agent's payload at all —
             * not blanked, not nulled, absent.
             */
            'pickup_code' => $includeCode ? $item->pickup_code : null,
            /*
             * The parcel's attempts, not this call's — the limit the agent is
             * actually held to. One extra query per row, which is bounded by how
             * many parcels a single agent has outstanding.
             */
            'attempts' => $attempts = $this->totalCodeAttempts((int) $item->id, (int) ($log?->agent_id ?? 0)),
            'attempts_remaining' => max(0, self::MAX_CODE_ATTEMPTS - $attempts),

            /*
             * The countdown the agent app shows on the card ("Expires in 18h").
             *
             * Computed from `arrived_at_hub_at` because that is where the clock
             * starts — the code is minted in the same write, so hub arrival is the
             * first moment a confirmation was possible at all.
             *
             * The constant is read off the expiry service rather than copied, and
             * read as a class constant so it does not need the service injected:
             * injecting it would be circular, since the expiry service takes this
             * one as a dependency.
             */
            'expires_at' => $expiresAt = $item->arrived_at_hub_at
                ? \Illuminate\Support\Carbon::parse($item->arrived_at_hub_at)
                    ->addHours(AgentCommissionExpirationService::SLA_HOURS)
                    ->toIso8601String()
                : null,
            'hours_remaining' => $item->arrived_at_hub_at
                ? round(\Illuminate\Support\Carbon::now()
                    ->diffInMinutes(\Illuminate\Support\Carbon::parse($expiresAt), false) / 60, 1)
                : null,
        ];
    }
}
