<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\AgentCallLog;
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
            ->whereDoesntHave('agentCallLogs')
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
                'pending_calls' => ShipmentItem::where('agent_id', $agent->id)
                    ->where('status', ItemStatus::PICKED_UP)
                    ->whereDoesntHave('agentCallLogs')
                    ->count(),
                'rescheduled' => AgentCallLog::where('agent_id', $agent->id)
                    ->where('outcome', AgentCallLog::OUTCOME_RESCHEDULED)
                    ->whereDate('created_at', today())
                    ->count(),
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
