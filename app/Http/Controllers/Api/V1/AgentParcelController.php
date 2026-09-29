<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\AgentCallLog;
use App\Models\AgentDailyQuota;
use App\Models\CommissionTier;
use App\Models\OutgoingBatchAssignmentEvent;
use App\Models\ShipmentItem;
use App\Services\OutgoingBatchAutoAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentParcelController extends Controller
{
    /**
     * Handle scan and claim for agent parcels
     */
    public function scanClaim(Request $request)
    {
        $request->validate([
            'barcode' => 'nullable|string',
            'tracking_code' => 'nullable|string',
        ]);

        $code = $request->input('barcode') ?? $request->input('tracking_code');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode or tracking code is required.',
            ], 422);
        }

        $agent = $request->user();

        // Resolves any prefix scheme, so a label printed before the move to
        // PM- still scans.
        $parcel = CodeResolver::resolveShipmentItem($code);

        if (!$parcel) {
            return response()->json([
                'success' => false,
                'message' => 'Parcel not found in system.',
            ], 404);
        }

        // Assign to agent using valid Enum case
        $parcel->update([
            'agent_id' => $agent->id,
            'status' => ItemStatus::PICKED_UP,
            'claimed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parcel claimed successfully.',
            'data' => $parcel,
        ]);
    }

    /**
     * Get agent queue list
     */
    public function getQueue(Request $request)
    {
        $agent = $request->user();

        /*
         * The Call Queue holds the agent's claimed parcels that are still
         * *waiting* for a call — and only those.
         *
         * `status = picked_up` on its own kept every parcelled call in the queue
         * forever: a call outcome other than "confirmed" changes no status, so
         * once an agent logged a call the parcel still matched and could never
         * leave the list. "Still to call" and "currently picked up" are two
         * different questions, so the filter is now two conditions — still
         * picked up, *and* no call log recorded.
         *
         * The exclusion is deliberately a query, not a new status. The brief only
         * names a target status for a confirmed call (`pending`); inventing one
         * for rescheduled / unreachable / cancelled would either overload
         * `pending` or add a state nothing else in the system understands, and
         * the queue would still have to look up the logs to decide. Asking the
         * logs directly travels with the data. `whereDoesntHave` compiles to a
         * single NOT EXISTS, so this stays one query — no N+1.
         */
        $parcels = ShipmentItem::where('agent_id', $agent->id)
            ->where('status', ItemStatus::PICKED_UP)
            /*
             * Scoped to THIS agent. Unscoped, the question was "has this parcel
             * ever been called by anybody?", so a parcel that a previous agent
             * called in an earlier cycle was hidden from the agent who has just
             * claimed it — the queue looked empty while the Home screen counted
             * the parcel as claimed. The queue means "parcels I still have to
             * ring", so only this agent's own calls should take one out of it.
             */
            ->whereDoesntHave('agentCallLogs', fn ($query) => $query->where('agent_id', $agent->id))
            ->latest()
            ->get();

        /*
         * Rows are shaped for the agent app, which reads `recipient_name`,
         * `recipient_phone` and `town`.
         *
         * This returned the raw shipment_items records, whose columns are named
         * `delivery_recipient_name`, `delivery_recipient_phone` and
         * `delivery_town` — so every claimed parcel rendered as "Unknown
         * Recipient / No phone provided / No address specified" while the data
         * sat right there in the payload under other names.
         *
         * The original columns are kept alongside the aliases so nothing that
         * already reads this payload loses a field. `status` is deliberately
         * left as the raw slug: the app both filters on it (`picked_up` /
         * `pending` / `rescheduled`) and renders it, so prettifying it here
         * would empty the tabs.
         */
        return response()->json([
            'success' => true,
            'data' => $parcels->map(fn (ShipmentItem $parcel) => array_merge(
                $parcel->toArray(),
                [
                    'id' => $parcel->id,
                    'tracking_code' => $parcel->tracking_code,
                    'recipient_name' => $parcel->delivery_recipient_name,
                    'recipient_phone' => $parcel->delivery_recipient_phone,
                    'town' => $parcel->delivery_town,
                    'address' => $parcel->delivery_gh_post_address,
                    'items_count' => (int) ($parcel->quantity ?? 1),
                    'total_fee' => (float) ($parcel->delivery_fee ?? 0),
                    'claimed_at' => optional($parcel->updated_at)->toIso8601String(),
                ]
            ))->values(),
        ]);
    }

    /**
     * Agent Overview Data
     */
    public function overview(Request $request)
    {
        $agent = $request->user();

        $claimedToday = ShipmentItem::where('agent_id', $agent->id)
            ->whereDate('claimed_at', today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'claimed_today' => $claimedToday,
                /*
                 * Same exclusion as getQueue(). Counting raw PICKED_UP here made
                 * the Home tile disagree with the Call Queue on the very next
                 * screen — a parcel that had already been called was still
                 * counted as waiting for one. This is the number the agent
                 * decides what to do next from, so it has to match the list.
                 */
                // Same scoping as getQueue(), or this count would disagree with
                // the list one screen away.
                'pending_calls' => ShipmentItem::where('agent_id', $agent->id)
                    ->where('status', ItemStatus::PICKED_UP)
                    ->whereDoesntHave('agentCallLogs', fn ($query) => $query->where('agent_id', $agent->id))
                    ->count(),
                'rescheduled' => AgentCallLog::where('agent_id', $agent->id)
                    ->where('outcome', AgentCallLog::OUTCOME_RESCHEDULED)
                    ->whereDate('created_at', today())
                    ->count(),

                /*
                 * The four tiles the Home screen actually renders.
                 *
                 * It reads `today_commission`, `customers_called`, `amount_paid`
                 * and `unable_to_reach`, and this endpoint returned none of them.
                 * The keys above are named differently — `claimed_today`,
                 * `pending_calls`, `rescheduled` — so nothing matched and every
                 * tile fell back to its zero default. The dashboard was not
                 * showing a wrong number, it was showing no number at all.
                 *
                 * Each is computed from this agent's own rows, so one agent can
                 * never see another's figures.
                 */
                'amount_paid' => (float) AgentCallLog::where('agent_id', $agent->id)
                    ->where('outcome', AgentCallLog::OUTCOME_CONFIRMED)
                    ->sum('amount_paid'),

                /*
                 * Every call this agent has logged, not just today's: the brief
                 * asks for the total of what they have collected, so this reads
                 * the whole history rather than a window. (The Home screen has a
                 * Today/Week/Month picker that the API does not read yet — see the
                 * note in the report.)
                 */
                'customers_called' => AgentCallLog::where('agent_id', $agent->id)->count(),

                'unable_to_reach' => AgentCallLog::where('agent_id', $agent->id)
                    ->where('outcome', AgentCallLog::OUTCOME_UNREACHABLE)
                    ->count(),

                /*
                 * Today's commission, resolved the way the ledger and the
                 * earnings endpoint resolve it. The stored column is not trusted
                 * alone because a band can change after the row was written.
                 */
                /*
                 * Today's cycle if there is one, otherwise the most recent cycle
                 * that has NOT been paid out yet.
                 *
                 * Scoping this strictly to `today()` was why the header read
                 * GH₵0.00 the morning after a collection. An agent who brought in
                 * 3500 yesterday has earned 120 and is still owed it — the row is
                 * right there with payout_status `locked` — but a calendar-day
                 * lookup found no row for today and reported nothing. To the agent
                 * that is indistinguishable from the commission not working.
                 *
                 * Stopping at the newest UNPAID cycle keeps the number honest:
                 * once a cycle is paid it drops out and the figure reflects the
                 * cycle being worked now. It is deliberately not "the newest row
                 * whenever", which would keep showing a month-old payout as if it
                 * were current.
                 */
                'today_commission' => (float) (CommissionTier::findTierForAmount(
                    (float) (AgentDailyQuota::where('user_id', $agent->id)
                        ->where(function ($query) {
                            $query->whereDate('tracking_date', today())
                                ->orWhere(fn ($q) => $q->where('payout_status', '!=', 'paid'));
                        })
                        ->orderByDesc('tracking_date')
                        ->value('collected_amount') ?? 0.0)
                )?->payout_amount ?? 0.0),

                /*
                 * Which cycle that figure belongs to, so the app can say so
                 * rather than presenting a figure with no date attached.
                 */
                'commission_cycle_date' => AgentDailyQuota::where('user_id', $agent->id)
                    ->where(function ($query) {
                        $query->whereDate('tracking_date', today())
                            ->orWhere(fn ($q) => $q->where('payout_status', '!=', 'paid'));
                    })
                    ->orderByDesc('tracking_date')
                    ->value('tracking_date'),
            ]
        ]);
    }

    /**
     * Record the outcome of a call the agent made about a parcel.
     *
     * A "confirmed" outcome (the spec's "Confirmed Payment") is the trigger for
     * auto-batching: the parcel joins the open outgoing batch for its
     * destination, or gets a brand new batch when none is open yet. It also
     * returns the parcel to `pending` so it leaves the call queue.
     *
     * A parcel can only ever carry one call log: a second attempt is refused with
     * a 409 (see below), and the queue is filtered on the same fact.
     */
    public function logCall(Request $request, OutgoingBatchAutoAssignmentService $autoBatching)
    {
        $request->validate([
            'parcel_id' => ['required'],
            'outcome' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Kept loose on purpose: the agent types this on a decimal keypad,
            // and a mistyped amount must never cost us the call outcome (and
            // with it the batch assignment). Normalised below.
            'amount_paid' => ['nullable', 'string', 'max:40'],
            'rescheduled_date' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif,pdf', 'max:10240'],
        ]);

        $outcome = AgentCallLog::normalizeOutcome($request->input('outcome'));

        if (! $outcome) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown call outcome. Expected one of: '.implode(', ', AgentCallLog::OUTCOMES).'.',
            ], 422);
        }

        $agent = $request->user();
        $parcel = $this->resolveParcel($request->input('parcel_id'));

        if (! $parcel) {
            return response()->json([
                'success' => false,
                'message' => 'Parcel not found in system.',
            ], 404);
        }

        // An agent only ever works their own queue. An unclaimed parcel is
        // adopted here so logging a call is never a dead end.
        if ($parcel->agent_id && (int) $parcel->agent_id !== (int) $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This parcel is assigned to another agent.',
            ], 403);
        }

        /*
         * Everything that has to agree with itself runs in one transaction, and
         * it begins by locking the parcel's row.
         *
         * The lock is what makes "one call per parcel" hold when two taps race:
         * the second request blocks here until the first commits, then sees the
         * log the first wrote and is refused below. A plain existence check with
         * no lock lets both requests read "no call yet" and both insert.
         *
         * In the same transaction a confirmed outcome also moves the parcel from
         * `picked_up` to `pending`, before the auto-batching claims it for its
         * destination. Atomic on purpose: if batching throws, the status change
         * must not survive on its own. (Batching opens its own transaction; under
         * a surrounding one it becomes a savepoint, so it still shares our fate.)
         */
        $result = DB::transaction(function () use ($request, $agent, $parcel, $outcome, $autoBatching) {
            $locked = ShipmentItem::query()
                ->whereKey($parcel->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return ['state' => 'missing'];
            }

            /*
             * One logged call per parcel. Read *under the lock* so a concurrent
             * insert cannot slip past it: the racer is either before us (we see
             * its log) or behind us (it waits for our lock and then sees ours).
             */
            $existingLog = AgentCallLog::query()
                ->where('shipment_item_id', $locked->getKey())
                ->latest('id')
                ->first();

            if ($existingLog) {
                return ['state' => 'duplicate', 'existing' => $existingLog];
            }

            if (! $locked->agent_id) {
                $locked->update([
                    'agent_id' => $agent->id,
                    'claimed_at' => $locked->claimed_at ?? now(),
                ]);
            }

            $proofPath = $request->hasFile('payment_proof')
                ? $request->file('payment_proof')->store('agent-call-proofs/'.$locked->id, 'public')
                : null;

            $log = AgentCallLog::create([
                'shipment_item_id' => $locked->id,
                'agent_id' => $agent->id,
                'outcome' => $outcome,
                'notes' => $request->input('notes'),
                'amount_paid' => $this->parseAmount($request->input('amount_paid')),
                'payment_proof_path' => $proofPath,
                'rescheduled_for' => $request->input('rescheduled_date'),
            ]);

            $batching = null;

            if ($outcome === AgentCallLog::OUTCOME_CONFIRMED) {
                /*
                 * Confirmed payment returns the parcel to `pending` for handling
                 * (the brief's target status). Auto-batching runs straight after
                 * and may advance it again to `ready_for_hub_transfer` when it
                 * finds a destination — which is why the response reports the
                 * status we *end* on, not the one set here.
                 */
                $locked->update(['status' => ItemStatus::PENDING]);

                /*
                 * Credit the calling agent's daily commission ledger.
                 *
                 * This is the piece the API path never had. Logging a call wrote
                 * the log and moved the parcel, but nothing ever touched
                 * `agent_daily_quotas`, so an agent who worked entirely through
                 * the app earned nothing, ever — no matter how many payments
                 * they confirmed. The dashboard has always credited on approval
                 * (and, until now, credited a hardcoded user id); here it is
                 * keyed on the authenticated agent, so the money lands on the
                 * person who actually made the call.
                 *
                 * Only the confirmed (paid) outcome credits anything:
                 * rescheduled / unreachable / cancelled are not money. The
                 * amount is whatever `parseAmount()` managed to read; an
                 * unreadable amount counts as 0, never a failed call.
                 *
                 * The row is created on the first confirmed call of the day via
                 * the unique (user_id, tracking_date) pair, then re-read under a
                 * row lock: without the lock two confirmed calls for the same
                 * agent committing at the same instant could both read the same
                 * `collected_amount` and one credit would be lost. `assigned_tasks`
                 * is deliberately left alone — nothing in this API path assigns a
                 * quota of tasks, and inventing one would make `hasClearedList()`
                 * report against a target that was never set.
                 *
                 * `earned_commission` is recomputed from the same `CommissionTier`
                 * lookup the admin ledger uses and *stored*, so the row is
                 * accurate on its own rather than left at its 0.00 default.
                 */
                $quota = AgentDailyQuota::firstOrCreate([
                    'user_id' => $agent->id,
                    'tracking_date' => today()->toDateString(),
                ]);

                $quota = AgentDailyQuota::query()
                    ->whereKey($quota->getKey())
                    ->lockForUpdate()
                    ->first() ?? $quota;

                $collected = (float) $quota->collected_amount + (float) ($log->amount_paid ?? 0);
                $tier = CommissionTier::findTierForAmount($collected);

                $quota->update([
                    'completed_tasks' => $quota->completed_tasks + 1,
                    'collected_amount' => $collected,
                    'earned_commission' => $tier?->payout_amount ?? 0.00,
                ]);

                $assignment = $autoBatching->assignForDestination(
                    $locked->fresh(),
                    OutgoingBatchAssignmentEvent::SOURCE_AGENT_CALL,
                    (int) $agent->id
                );

                $batching = [
                    'result' => $assignment['result'],
                    'batch_id' => $assignment['batch']?->id,
                    'batch_number' => $assignment['batch']?->batch_number,
                    'batch_created' => $assignment['created'],
                    'message' => $assignment['message'],
                ];
            }

            // Re-read once so `new_status` is what the app should show, including
            // whatever the batching just changed it to.
            $locked->refresh();

            return [
                'state' => 'logged',
                'log' => $log,
                'batching' => $batching,
                'new_status' => $locked->status instanceof ItemStatus
                    ? $locked->status->value
                    : (string) $locked->status,
            ];
        });

        if ($result['state'] === 'missing') {
            return response()->json([
                'success' => false,
                'message' => 'Parcel not found in system.',
            ], 404);
        }

        if ($result['state'] === 'duplicate') {
            $existing = $result['existing'];

            /*
             * 409 Conflict rather than 422: the request is well formed and the
             * data is valid — it simply collides with a call that already exists
             * for this parcel. The existing outcome and time are returned so the
             * app can explain *why* the Call button is now unavailable instead of
             * just failing.
             */
            return response()->json([
                'success' => false,
                'message' => 'A call has already been logged for this parcel.',
                'data' => [
                    'existing_call_log' => [
                        'id' => $existing->getKey(),
                        'outcome' => $existing->outcome,
                        'created_at' => optional($existing->created_at)->toIso8601String(),
                    ],
                ],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $this->outcomeMessage($outcome, $result['batching']),
            'data' => [
                'call_log' => $result['log'],
                'batching' => $result['batching'],
                'new_status' => $result['new_status'],
            ],
        ]);
    }

    /**
     * The call outcomes this agent has logged, newest first.
     */
    public function callHistory(Request $request)
    {
        $agent = $request->user();

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        /*
         * The column list is explicit, so a field not named here is simply never
         * sent — it is not a missing column on the model.
         *
         * It named the recipient and the phone but *no location at all*, which is
         * why the Call Log card had nothing to put under a "Location" label: the
         * app was not failing to read the field, it was never given one. The
         * location columns and the region/district relations are now selected, and
         * each row carries a ready-made `delivery_location_label` so the app does
         * not have to re-implement the same fallback logic the SMS uses.
         */
        $logs = AgentCallLog::query()
            ->where('agent_id', $agent->id)
            ->with(['shipmentItem:id,tracking_code,status,description,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_landmark,delivery_gh_post_address,delivery_region_id,delivery_district_id,outgoing_batch_id'])
            // Eager-loaded, not lazy: the label joins region and district names,
            // and a page of 25 call logs would otherwise fire up to 50 extra
            // queries to build them.
            ->with(['shipmentItem.deliveryRegion:id,name', 'shipmentItem.deliveryDistrict:id,name'])
            ->latest('id')
            ->paginate($perPage);

        $rows = collect($logs->items())->map(function (AgentCallLog $log) {
            $item = $log->shipmentItem;

            if ($item) {
                // Set as an attribute so it serialises with the row the app
                // already reads, rather than in a parallel structure.
                $item->setAttribute('delivery_location_label', $item->deliveryLocationLabel());
            }

            return $log;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'call_logs' => $rows,
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'has_more' => $logs->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * The authenticated agent's earnings summary and recent commission activity.
     *
     * This endpoint previously did not exist at all. `routes/api.php` pointed
     * GET /api/v1/agent/earnings at this method, which was never written, so the
     * request 500'd, the app swallowed the error and fell back to its literal
     * defaults — the Earnings screen showed "GH₵ 0.00" no matter how much the
     * agent had collected.
     *
     * It reads the same `agent_daily_quotas` ledger the admin uses and derives
     * each day's commission with the same `CommissionTier::findTierForAmount()`
     * lookup as Admin\CommissionLedgerController — deliberately *not* from the
     * stored `earned_commission` column — so the agent app and the admin ledger
     * can never disagree. The column is still kept accurate by logCall() for
     * audits and direct queries; this endpoint simply treats the tier lookup as
     * the source of truth, exactly as the ledger view does.
     *
     * The screen renders these values *directly as strings* (`{balance}`,
     * `{totalEarned}`), so they are returned display-ready ("GH₵ 12.00") — a
     * bare number would render with no currency symbol. The activity rows match
     * the screen's `ActivityItem` shape: id / title / code / date / amount /
     * isPayout, where `code` and `date` are joined as "<code> • <date>".
     *
     * Balance semantics: a day's commission is *available* only once its quota
     * has been unlocked for payout. The schema default is `is_unlocked = false`
     * / `payout_status = 'locked'`; only the admin override flips them to
     * true / 'unlocked'. `total_earned` counts every day's commission, unlocked
     * or not, while `available_balance` counts only the unlocked days. Nothing
     * in the schema records a "paid" state, so unlocked is read as payable.
     */
    public function earnings(Request $request)
    {
        $agent = $request->user();

        /*
         * One row per agent per day, so this is bounded by tenure rather than by
         * calls. Newest first, which makes the activity list a simple prefix of
         * the same collection and lets the totals be computed in one pass.
         */
        $quotas = AgentDailyQuota::query()
            ->where('user_id', $agent->id)
            ->orderByDesc('tracking_date')
            ->orderByDesc('id')
            ->get();

        $totalEarned = 0.0;
        $availableBalance = 0.0;
        $earnedByQuota = [];

        foreach ($quotas as $quota) {
            // Same derivation the admin ledger uses, so both screens agree.
            $tier = CommissionTier::findTierForAmount((float) $quota->collected_amount);
            $earned = (float) ($tier?->payout_amount ?? 0.00);

            $earnedByQuota[$quota->getKey()] = $earned;
            $totalEarned += $earned;

            if ($quota->is_unlocked || $quota->payout_status === 'unlocked') {
                $availableBalance += $earned;
            }
        }

        /*
         * Bounded on purpose: the screen only ever renders a scroll list, and an
         * unbounded history would grow without limit. Fifty recent days is more
         * than the list can show and keeps the payload small. The totals above
         * are all-time and are not limited by this slice.
         */
        $activities = $quotas->take(50)->map(function (AgentDailyQuota $quota) use ($earnedByQuota) {
            $calls = (int) $quota->completed_tasks;

            return [
                'id' => (string) $quota->getKey(),
                'title' => 'Commission earned',
                'code' => $calls.' '.($calls === 1 ? 'call' : 'calls'),
                'date' => optional($quota->tracking_date)->format('M j, Y'),
                'amount' => $this->formatMoney($earnedByQuota[$quota->getKey()] ?? 0.00),
                'isPayout' => false,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'available_balance' => $this->formatMoney($availableBalance),
                'total_earned' => $this->formatMoney($totalEarned),
                'activities' => $activities,
            ],
        ]);
    }

    /**
     * Format an amount the way the agent app's own fallback does ("GH₵ 0.00"),
     * so a populated response and an empty one are rendered identically.
     */
    protected function formatMoney(float $amount): string
    {
        return 'GH₵ '.number_format($amount, 2);
    }

    /**
     * Read the amount the agent typed as best we can.
     *
     * A decimal keypad can hand us "150.", a locale separator such as "150,50",
     * or a stutter like "1.5.0". The amount is bookkeeping; the outcome and the
     * batch assignment are the point, so an unreadable value is stored as null
     * rather than rejecting the whole call.
     */
    protected function parseAmount($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.,]/', '', (string) $value) ?? '';
        $clean = str_replace(',', '.', $clean);

        if (substr_count($clean, '.') > 1) {
            $parts = explode('.', $clean);
            $clean = array_shift($parts).'.'.implode('', $parts);
        }

        if ($clean === '' || $clean === '.' || ! is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    /**
     * Accept either a numeric id or a tracking code, which is what the app
     * happens to have to hand.
     */
    protected function resolveParcel($reference): ?ShipmentItem
    {
        // Accepts a numeric id or a tracking code, under any prefix scheme, so a
        // label printed before the move to PM- still claims.
        return CodeResolver::resolveShipmentItem(is_scalar($reference) ? (string) $reference : null);
    }

    /**
     * A message the agent can act on.
     *
     * @param  array<string, mixed>|null  $batching
     */
    protected function outcomeMessage(string $outcome, ?array $batching): string
    {
        if ($outcome !== AgentCallLog::OUTCOME_CONFIRMED) {
            return 'Call outcome recorded.';
        }

        if (! $batching) {
            return 'Payment confirmed.';
        }

        return match ($batching['result']) {
            OutgoingBatchAutoAssignmentService::RESULT_BATCH_CREATED,
            OutgoingBatchAutoAssignmentService::RESULT_BATCH_ATTACHED => 'Payment confirmed. '.$batching['message'],
            OutgoingBatchAutoAssignmentService::RESULT_ALREADY_BATCHED => 'Payment confirmed. '.$batching['message'],
            default => 'Payment confirmed, but the parcel could not be batched: '.$batching['message'],
        };
    }
}
