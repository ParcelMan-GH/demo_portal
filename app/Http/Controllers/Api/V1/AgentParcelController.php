<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\AgentCallLog;
use App\Models\OutgoingBatchAssignmentEvent;
use App\Models\ShipmentItem;
use App\Services\OutgoingBatchAutoAssignmentService;
use Illuminate\Http\Request;

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

        // Query using tracking_code instead of barcode
        $parcel = ShipmentItem::where('tracking_code', $code)->first();

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

        $parcels = ShipmentItem::where('agent_id', $agent->id)
            ->where('status', ItemStatus::PICKED_UP)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parcels,
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
                'pending_calls' => ShipmentItem::where('agent_id', $agent->id)->where('status', ItemStatus::PICKED_UP)->count(),
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
     * destination, or gets a brand new batch when none is open yet.
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

        if (! $parcel->agent_id) {
            $parcel->update([
                'agent_id' => $agent->id,
                'claimed_at' => $parcel->claimed_at ?? now(),
            ]);
        }

        $proofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('agent-call-proofs/'.$parcel->id, 'public')
            : null;

        $log = AgentCallLog::create([
            'shipment_item_id' => $parcel->id,
            'agent_id' => $agent->id,
            'outcome' => $outcome,
            'notes' => $request->input('notes'),
            'amount_paid' => $this->parseAmount($request->input('amount_paid')),
            'payment_proof_path' => $proofPath,
            'rescheduled_for' => $request->input('rescheduled_date'),
        ]);

        $batching = null;

        if ($outcome === AgentCallLog::OUTCOME_CONFIRMED) {
            $assignment = $autoBatching->assignForDestination(
                $parcel->fresh(),
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

        return response()->json([
            'success' => true,
            'message' => $this->outcomeMessage($outcome, $batching),
            'data' => [
                'call_log' => $log,
                'batching' => $batching,
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

        $logs = AgentCallLog::query()
            ->where('agent_id', $agent->id)
            ->with(['shipmentItem:id,tracking_code,status,description,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,outgoing_batch_id'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'call_logs' => $logs->items(),
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
        if ($reference === null || $reference === '') {
            return null;
        }

        if (is_numeric($reference)) {
            $byId = ShipmentItem::query()->whereKey((int) $reference)->first();

            if ($byId) {
                return $byId;
            }
        }

        return ShipmentItem::query()->where('tracking_code', (string) $reference)->first();
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
