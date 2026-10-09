<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\AgentCallLog;
use App\Models\AgentDailyQuota;
use App\Models\CommissionTier;
use App\Models\NotificationLog;
use App\Models\ShipmentItem;
use App\Services\Agent\AgentCallConfirmationService;
use App\Services\Agent\AgentCommissionExpirationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentParcelController extends Controller
{
    /**
     * Parcel states that are past the point of calling.
     *
     * A call agent rings the recipient to arrange delivery, so a parcel already
     * delivered or sent back has nothing left to arrange — and claiming it would
     * drag the item backwards to `picked_up`, corrupting a finished delivery.
     *
     * Deliberately a deny-list rather than an allow-list: every other state
     * legitimately precedes a call, and an allow-list would start refusing real
     * claims the moment a new pre-call state is introduced.
     *
     * @var array<int, ItemStatus>
     */
    private const UNCLAIMABLE_STATUSES = [
        ItemStatus::DELIVERED,
        ItemStatus::RETURNED,
    ];

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

        /*
         * Claiming is a read-then-write on shared state: two agents scanning the
         * same label would otherwise both pass every check below and the second
         * would silently take the parcel from the first. The row is locked and
         * the checks re-run inside the transaction, so only one claim can win.
         */
        return DB::transaction(function () use ($parcel, $agent) {
            $parcel = ShipmentItem::query()
                ->whereKey($parcel->id)
                ->lockForUpdate()
                ->firstOrFail();

            // A finished delivery must not be dragged back to `picked_up`.
            if (in_array($parcel->status, self::UNCLAIMABLE_STATUSES, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This parcel is already ' . $parcel->status->label() . ' and cannot be claimed.',
                ], 409);
            }

            // Someone else's parcel stays theirs. Taking it would move the
            // recipient's call off the agent who started it.
            if ($parcel->agent_id && (int) $parcel->agent_id !== (int) $agent->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This parcel is already claimed by another agent.',
                ], 409);
            }

            /*
             * Their own parcel, already claimed. Return without writing: this is
             * a re-scan or a retried request, and rewriting `claimed_at` would
             * count the parcel into today's quota a second time.
             */
            if ((int) $parcel->agent_id === (int) $agent->id) {
                return response()->json([
                    'success' => true,
                    'message' => 'Parcel already claimed.',
                    'data' => $parcel,
                ]);
            }

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
        });
    }

    /**
     * Get agent queue list
     */
    public function getQueue(Request $request)
    {
        $agent = $request->user();

        /*
         * The Call Queue holds the agent's claimed parcels that still have to be
         * rung — and only those.
         *
         * `status = picked_up` on its own kept every called parcel in the queue
         * forever: a call outcome other than "confirmed" changes no status, so
         * once an agent logged a call the parcel still matched and could never
         * leave the list. "Still to call" and "currently picked up" are two
         * different questions, so the filter is now two conditions — still
         * picked up, *and* no *final* call log on record.
         *
         * Only a locked outcome takes a parcel out. A retryable one
         * (rescheduled / unreachable) is provisional, and it used to remove the
         * parcel from the queue outright: an agent who marked a recipient
         * "rescheduled" could never ring them again, because the card was gone
         * and with it any way to log the second call. Those parcels now stay put
         * until something final is logged against them.
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
            ->whereDoesntHave('agentCallLogs', fn ($query) => $query
                ->where('agent_id', $agent->id)
                ->whereIn('outcome', AgentCallLog::LOCKED_OUTCOMES))
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
        /*
         * The queue screen's headline figures, counted server-side.
         *
         * Deliberately a top-level `summary`, not a key inside `data`: the app
         * reads `data` as the array of parcels (`data.queue || data.data`), so
         * turning `data` into an object would empty the list.
         *
         * These are counted here for two reasons. A list can only report what it
         * holds, and one of these numbers cannot be derived from it at all:
         * already-called parcels are excluded from this query, so a
         * "rescheduled" count taken from the list is structurally always 0 —
         * which is exactly what the screen has been showing. And scoping all
         * three to *this agent, today* keeps them consistent with each other and
         * with the working day the agent is actually being paid for.
         */
        $todayLogs = fn () => AgentCallLog::query()
            ->where('agent_id', $agent->id)
            ->whereDate('created_at', today());

        $summary = [
            // Parcels still waiting for a call: exactly this list.
            'pending_calls' => $parcels->count(),
            'rescheduled_today' => $todayLogs()
                ->where('outcome', AgentCallLog::OUTCOME_RESCHEDULED)
                ->count(),
            /*
             * Clients who actually paid today. Counted from a positive
             * `amount_paid` rather than the `confirmed` outcome, because the
             * money is the fact being reported — a confirmed log with no
             * readable amount moved nothing and must not inflate this.
             */
            'paid_today' => $todayLogs()
                ->whereNotNull('amount_paid')
                ->where('amount_paid', '>', 0)
                ->count(),
            'collected_today' => (float) $todayLogs()->sum('amount_paid'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
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
                    ->whereDoesntHave('agentCallLogs', fn ($query) => $query
                        ->where('agent_id', $agent->id)
                        ->whereIn('outcome', AgentCallLog::LOCKED_OUTCOMES))
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
                 * Whether the agent still has parcels to ring — the same three
                 * conditions `getQueue()` uses, so the lock and the queue can
                 * never disagree about whether work is outstanding. A locked
                 * dashboard next to an empty queue (or the reverse) would be
                 * worse than no lock at all.
                 *
                 * Counted here rather than derived in the app because the app
                 * only ever holds the page it fetched, and a task on page two
                 * would silently unlock the figure.
                 */
                'pending_tasks_count' => $pendingTasks = ShipmentItem::where('agent_id', $agent->id)
                    ->where('status', ItemStatus::PICKED_UP)
                    ->whereDoesntHave('agentCallLogs', fn ($query) => $query
                        ->where('agent_id', $agent->id)
                        ->whereIn('outcome', AgentCallLog::LOCKED_OUTCOMES))
                    ->count(),
                'has_remaining_tasks' => $pendingTasks > 0,

                /*
                 * How many clients have actually paid this agent — the same
                 * question `callHistory()`'s `paid` answers, so the two screens
                 * can never disagree. Counted from a positive `amount_paid`
                 * rather than the `confirmed` outcome because the money is the
                 * fact, and a confirmed log with an unreadable amount moved none.
                 *
                 * `amount_paid` above reports what was collected; this reports how
                 * many clients it came from, which is the number an agent
                 * comparing their day against a target actually wants.
                 */
                'paid_clients' => AgentCallLog::where('agent_id', $agent->id)
                    ->whereNotNull('amount_paid')
                    ->where('amount_paid', '>', 0)
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
     * A "confirmed" outcome (the spec's "Confirmed Payment") settles the parcel:
     * it returns it to `pending` so it leaves the call queue, and it credits the
     * calling agent's daily commission. It deliberately does *not* batch: a
     * confirmed call used to join the open outgoing batch (or open a new one) as
     * a side effect, which pushed the parcel onto an admin batch list from the
     * agent's call screen. Choosing a batch is the admin dashboard's decision,
     * so this path now only settles the parcel and pays the commission.
     *
     * Only a *locked* outcome (confirmed / cancelled) is final: a second attempt
     * against one is refused with a 409 (see below), and the queue is filtered on
     * the same fact. A retryable outcome (rescheduled / unreachable) leaves the
     * parcel callable, so a later call may record over it.
     */
    public function logCall(Request $request)
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
         * `picked_up` to `pending` and credits the commission. Atomic on purpose:
         * the status change and the money must not survive on their own.
         */
        $result = DB::transaction(function () use ($request, $agent, $parcel, $outcome) {
            $locked = ShipmentItem::query()
                ->whereKey($parcel->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return ['state' => 'missing'];
            }

            /*
             * The parcel's most recent call. Read *under the lock* so a
             * concurrent insert cannot slip past it: the racer is either before
             * us (we see its log) or behind us (it waits for our lock and then
             * sees ours).
             *
             * Only a locked outcome closes the parcel. A retryable one
             * (rescheduled / unreachable) was provisional, so a later call
             * records over it — as a new row, because everything that reads these
             * logs already takes the latest one, and the earlier attempt stays
             * on the record rather than being erased.
             */
            $existingLog = AgentCallLog::query()
                ->where('shipment_item_id', $locked->getKey())
                ->latest('id')
                ->first();

            if ($existingLog && AgentCallLog::isLockedOutcome($existingLog->outcome)) {
                return ['state' => 'duplicate', 'existing' => $existingLog];
            }

            $overwrotePrevious = $existingLog !== null;

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

                /*
                 * No batching from this path. A confirmed call used to join the
                 * open outgoing batch for its destination (or open a new one),
                 * which is what quietly pushed the parcel onto an admin batch
                 * list. Choosing a batch belongs to the admin dashboard, so a
                 * confirmed call now only settles the parcel and pays the
                 * commission — `$batching` stays null and is reported as such.
                 */
            }

            // Re-read once so `new_status` is what the app should show, including
            // whatever the batching just changed it to.
            $locked->refresh();

            return [
                'state' => 'logged',
                'log' => $log,
                'batching' => $batching,
                'overwrote_previous' => $overwrotePrevious,
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
             * data is valid — it simply collides with a call whose outcome is
             * final. Only a locked outcome (confirmed / cancelled) can reach
             * here; a retryable one was overwritten above. The existing outcome
             * and time are returned so the app can explain *why* the outcome is
             * now locked instead of just failing.
             */
            return response()->json([
                'success' => false,
                'message' => 'A call has already been logged for this parcel.',
                'data' => [
                    'overwrote_previous' => false,
                    'existing_call_log' => [
                        'id' => $existing->getKey(),
                        'outcome' => $existing->outcome,
                        'created_at' => optional($existing->created_at)->toIso8601String(),
                        'locked' => true,
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
                'overwrote_previous' => $result['overwrote_previous'],
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

        /*
         * The Call Log's summary cards, counted over the agent's WHOLE history
         * rather than over the page just returned.
         *
         * The screen derived these itself from whatever rows it had fetched, so
         * with a 25-per-page limit a 26th paid client was invisible and the
         * counts drifted lower the further back the agent looked. A headline
         * figure has to be an all-time total or it is simply wrong.
         *
         * `paid` is counted from a positive `amount_paid`, not from the
         * `confirmed` outcome: the money is the fact being shown, and a confirmed
         * log whose amount could not be read moved nothing. `successful` and
         * `failed` keep their existing meaning for the cards already on screen.
         */
        $totals = AgentCallLog::query()
            ->where('agent_id', $agent->id)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when outcome = ? then 1 else 0 end) as successful", [AgentCallLog::OUTCOME_CONFIRMED])
            ->selectRaw(
                'sum(case when outcome in (?, ?) then 1 else 0 end) as failed',
                [AgentCallLog::OUTCOME_UNREACHABLE, AgentCallLog::OUTCOME_CANCELLED]
            )
            ->selectRaw("sum(case when outcome = ? then 1 else 0 end) as rescheduled", [AgentCallLog::OUTCOME_RESCHEDULED])
            ->selectRaw('sum(case when amount_paid is not null and amount_paid > 0 then 1 else 0 end) as paid')
            ->selectRaw('sum(case when amount_paid is not null and amount_paid > 0 then amount_paid else 0 end) as collected')
            ->first();

        $summary = [
            'paid' => (int) ($totals->paid ?? 0),
            'successful' => (int) ($totals->successful ?? 0),
            'failed' => (int) ($totals->failed ?? 0),
            'rescheduled' => (int) ($totals->rescheduled ?? 0),
            'total' => (int) ($totals->total ?? 0),
            'collected' => (float) ($totals->collected ?? 0),
            'collected_label' => $this->formatMoney((float) ($totals->collected ?? 0)),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'call_logs' => $rows,
                'summary' => $summary,
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
    public function earnings(Request $request, AgentCallConfirmationService $confirmations)
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

                /*
                 * Same flag the overview carries, from the same conditions. The
                 * ledger must agree with Home about whether the agent has work
                 * outstanding: a locked dashboard beside an open ledger (or the
                 * reverse) reads as a bug rather than as a rule.
                 */
                'pending_tasks_count' => $ledgerPending = ShipmentItem::where('agent_id', $agent->id)
                    ->where('status', ItemStatus::PICKED_UP)
                    ->whereDoesntHave('agentCallLogs', fn ($query) => $query
                        ->where('agent_id', $agent->id)
                        ->whereIn('outcome', AgentCallLog::LOCKED_OUTCOMES))
                    ->count(),
                'has_remaining_tasks' => $ledgerPending > 0,

                /*
                 * Parcels still waiting on a pickup-code confirmation. One number
                 * rather than per-day detail, so the app can badge its "To Confirm"
                 * tab without this endpoint doing a per-quota lookup for each of the
                 * fifty days it renders.
                 *
                 * This is what gates the day's commission, so the screen needs it
                 * to explain why a day is still locked.
                 */
                'pending_confirmations' => $pendingConfirmations = $confirmations->countPendingForAgent((int) $agent->id),
                'has_pending_confirmations' => $pendingConfirmations > 0,

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

        /*
         * A confirmed call no longer batches, so there is never a batching
         * result to report. The parameter is kept so this signature — and the
         * response shape built around it — survive if batching is wired back in.
         */
        return 'Payment confirmed.';
    }

    /**
     * The parcels this agent still has to confirm with a pickup code.
     *
     * GET /api/v1/agent/calls/to-confirm — the list behind the app's "To Confirm"
     * screen. One row per parcel rather than per call, because the agent has one
     * code to enter however many times they rang.
     *
     * Everything that decides membership lives in AgentCallConfirmationService, so
     * this list and the admin ledger cannot disagree about what is outstanding.
     */
    public function callsToConfirm(Request $request, AgentCallConfirmationService $confirmations)
    {
        $agent = $request->user();
        $items = $confirmations->pendingPayloadsForAgent((int) $agent->id);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => count($items),
            ],
            // Also at the top level: the app's client returns the body, and older
            // builds read `calls` directly.
            'calls' => $items,
        ]);
    }

    /**
     * How this agent stands against the confirmation rules, for the dashboard.
     *
     * GET /api/v1/agent/calls/summary
     *
     * Feeds the "Unconfirmed parcels: X / 10" counter and the countdown on the
     * cards. `unconfirmed` is parcels still owed a confirmation whatever their age;
     * `at_cap` is the escalation signal once that reaches the cap. `expired` counts
     * forfeited parcels so the dashboard can say what the SLA has already cost,
     * rather than the number only ever going down with no explanation.
     *
     * Read from the expiry service rather than counted here, so the app, the
     * ledger and the sweeper cannot disagree about how many parcels are outstanding.
     */
    public function callsSummary(
        Request $request,
        AgentCommissionExpirationService $expiry,
        AgentCallConfirmationService $confirmations
    ) {
        $agent = $request->user();
        $agentId = (int) $agent->id;

        $state = $expiry->stateForAgent($agentId);

        // The countdown data lives on the confirm list itself, so the app can show
        // "Expires in 18h" per card without a second request. This endpoint only
        // carries the headline numbers.
        $pending = count($confirmations->pendingPayloadsForAgent($agentId));

        return response()->json([
            'success' => true,
            'data' => array_merge($state, [
                // Outstanding right now, which is what the badge shows. Equals
                // `unconfirmed` by construction — both come from the same rule —
                // but named for the screen so the app does not have to guess.
                'pending' => $pending,
                'sla_hours' => AgentCommissionExpirationService::SLA_HOURS,
                'cap' => AgentCommissionExpirationService::UNCONFIRMED_CAP,
            ]),
        ]);
    }

    /**
     * Confirm a call against the parcel's pickup code.
     *
     * POST /api/v1/agent/calls/{callLog}/confirm-code
     *
     * The code is the same one the recipient quotes to the hub agent, so this
     * compares it exactly as `HubController::release` does.
     */
    public function confirmCallCode(
        Request $request,
        AgentCallLog $callLog,
        AgentCallConfirmationService $confirmations
    ) {
        $agent = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            /*
             * Where the code came from. Optional so a build that predates this
             * field still works, but recorded when sent — the two sources are not
             * equally strong evidence (a hub agent always knows the code), and that
             * distinction cannot be recovered later if it is not captured now.
             */
            'source' => ['nullable', 'string', 'in:customer,hub_agent'],
        ]);

        /*
         * Only your own call. Without this an agent could confirm another agent's
         * parcel, which is precisely what the gate exists to prevent.
         */
        if ((int) $callLog->agent_id !== (int) $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This call belongs to another agent.',
            ], 403);
        }

        $result = $confirmations->verify($callLog, (string) $validated['code']);

        if (! $result['ok']) {
            // Burn an attempt only for a genuinely wrong code — see verify().
            if ($result['counts_as_attempt']) {
                $confirmations->recordFailedAttempt($callLog);
            }

            // Counted per parcel, not per call — the limit the agent is actually
            // held to (see AgentCallConfirmationService::totalCodeAttempts).
            $attempts = $confirmations->totalCodeAttempts(
                (int) $callLog->shipment_item_id,
                (int) $callLog->agent_id
            );

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'attempts_remaining' => max(0, AgentCallConfirmationService::MAX_CODE_ATTEMPTS - $attempts),
            ], 422);
        }

        $confirmed = $confirmations->confirm(
            $callLog,
            (string) ($validated['source'] ?? AgentCallLog::CODE_SOURCE_CUSTOMER),
            AgentCallLog::CONFIRMED_BY_AGENT,
            (int) $agent->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Pickup code confirmed.',
            'data' => [
                'call_log_id' => $confirmed->getKey(),
                'shipment_item_id' => $confirmed->shipment_item_id,
                'confirmed_at' => optional($confirmed->pickup_code_confirmed_at)->toIso8601String(),
                'source' => $confirmed->pickup_code_source,
                'confirmed_by' => $confirmed->pickup_code_confirmed_by,
            ],
        ]);
    }

    /**
     * The agent's own notification feed, plus their alert toggles.
     *
     * These three routes existed in `routes/api.php` but the methods did not, so
     * every call to the agent app's notifications screen answered 500. The shape
     * is dictated by the app, which reads `data.items` (falling back to `items`
     * and then `data`) for the list and a sibling `settings` for the switches —
     * `notifications` is included as well so an older build that reads that key
     * still finds its list.
     */
    public function notifications(Request $request)
    {
        $agent = $request->user();

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:100'],
            'is_read' => ['nullable', 'in:true,false,1,0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = NotificationLog::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $agent->id);

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (array_key_exists('is_read', $validated) && !is_null($validated['is_read'])) {
            filter_var($validated['is_read'], FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNotNull('read_at')
                : $query->whereNull('read_at');
        }

        // Counted before the page is sliced, so the badge can show a total that
        // is larger than the page the app is holding.
        $total = (clone $query)->count();
        $unread = (clone $query)->whereNull('read_at')->count();

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset((int) ($validated['offset'] ?? 0))
            ->limit((int) ($validated['limit'] ?? 50))
            ->get()
            ->map(fn (NotificationLog $notification) => [
                'id' => (string) $notification->id,
                'title' => (string) $notification->title,
                'message' => (string) $notification->body,
                'created_at' => optional($notification->created_at)->toIso8601String(),
                'read' => $notification->isRead(),
                'type' => $this->notificationBucket((string) $notification->type),
                'data' => $notification->data,
            ])
            ->values();

        $settings = $agent->notificationPreferences();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $total,
                'unread' => $unread,
            ],
            'notifications' => $items,
            'settings' => $settings,
        ]);
    }

    /**
     * Mark every unread notification for this agent as read.
     */
    public function markAllNotificationsRead(Request $request)
    {
        $agent = $request->user();

        $updated = NotificationLog::query()
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $agent->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $updated.' '.($updated === 1 ? 'notification' : 'notifications').' marked as read.',
            'data' => ['updated' => $updated],
        ]);
    }

    /**
     * Save the agent's alert toggles.
     *
     * Merged over what is already stored rather than replaced, because the app
     * sends one key per tap — replacing would silently reset the other two.
     */
    public function updateNotificationSettings(Request $request)
    {
        $agent = $request->user();

        $validated = $request->validate([
            'queue_alerts' => ['sometimes', 'boolean'],
            'payout_alerts' => ['sometimes', 'boolean'],
            'in_app_sound' => ['sometimes', 'boolean'],
        ]);

        $settings = array_merge($agent->notificationPreferences(), $validated);

        // forceFill: the model here is the authenticated user and may have been
        // resolved without this attribute loaded.
        $agent->forceFill(['notification_settings' => $settings])->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings saved.',
            'data' => ['settings' => $settings],
            'settings' => $settings,
        ]);
    }

    /**
     * Fold a stored notification type into the three buckets the app has icons for.
     *
     * The column holds specific types ("out_for_delivery", "commission_paid"),
     * but the screen only distinguishes what an agent acts on. Anything the app
     * has no icon for falls to `system` rather than rendering an empty badge.
     */
    protected function notificationBucket(string $type): string
    {
        $type = strtolower($type);

        if (str_contains($type, 'payout') || str_contains($type, 'commission') || str_contains($type, 'earning')) {
            return 'payout';
        }

        if (str_contains($type, 'queue') || str_contains($type, 'parcel') || str_contains($type, 'call') || str_contains($type, 'assigned')) {
            return 'queue';
        }

        return 'system';
    }
}
