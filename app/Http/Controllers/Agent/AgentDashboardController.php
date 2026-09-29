<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentDailyQuota;
use App\Models\CommissionTier;
use App\Models\OutgoingBatchAssignmentEvent;
use App\Models\RecipientPaymentTask;
use App\Services\OutgoingBatchAutoAssignmentService;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    public function __construct(
        private readonly OutgoingBatchAutoAssignmentService $autoBatching
    ) {
    }

    public function index()
    {
        // 1. Fetch pending tasks assigned to the Admin (Hardcoded User ID 1)
        $tasks = RecipientPaymentTask::with('shipmentItem')
            ->where('assigned_to_user_id', 1) 
            ->whereIn('status', ['pending', 'failed', 'unreachable'])
            ->get();

        return view('agent.dashboard', [
            'pageTitle' => 'My Call Queue',
            'tasks'     => $tasks,
        ]);
    }

    public function approvePayment(Request $request, RecipientPaymentTask $task)
    {
        // Security check: ensure the task actually belongs to this agent (User ID 1)
        if ($task->assigned_to_user_id !== 1) {
            abort(403, 'Unauthorized access.');
        }

        $parcel = $task->shipmentItem;

        if (! $parcel) {
            return back()->with('error', 'This task has no parcel attached, so it cannot be batched.');
        }

        // 1. THE AUTO-BATCHING MAGIC
        // Shared with the agent app's "confirmed payment" call outcome so both
        // entry points behave identically.
        $assignment = $this->autoBatching->assignForDestination(
            $parcel,
            OutgoingBatchAssignmentEvent::SOURCE_AGENT_DASHBOARD,
            auth('admin')->id() ?? auth()->id()
        );

        // A parcel with no destination, or one that has already finished its
        // journey, is a genuine problem the agent has to fix by hand.
        if (in_array($assignment['result'], [
            OutgoingBatchAutoAssignmentService::RESULT_MISSING_DESTINATION,
            OutgoingBatchAutoAssignmentService::RESULT_NOT_ELIGIBLE,
        ], true)) {
            return back()->with('error', 'Cannot auto-batch! '.$assignment['message']);
        }

        // 2. Update the Task status
        $task->update(['status' => 'payment_approved']);

        /*
         * 3. Update the Commission Ledger for the agent who did the work.
         *
         * This used to write to user_id = 1 unconditionally, so every agent's
         * approved payment was booked against user 1's ledger. The money belongs
         * to the agent who handled the parcel: the parcel's own agent when it
         * has one, otherwise the user this task was assigned to. It is
         * deliberately *not* the authenticated user — this page is admin-guarded,
         * so that is normally an admin, and crediting them would repeat the same
         * bug against a different ledger.
         *
         * The row is created on first approval of the day (`firstOrCreate` on the
         * unique (user_id, tracking_date) pair) rather than skipped when absent:
         * the old `if ($quota)` guard silently dropped the credit whenever the
         * day's row had not been pre-seeded. It is then re-read under a row lock
         * so two simultaneous approvals for the same agent cannot both read the
         * same `collected_amount` and lose one credit.
         *
         * `earned_commission` is recomputed from the same CommissionTier lookup
         * the ledger view and the agent API use, and stored, so the row stays
         * accurate instead of sitting at its 0.00 default.
         */
        $agentId = $parcel->agent_id ?? $task->assigned_to_user_id;

        $quota = AgentDailyQuota::firstOrCreate([
            'user_id' => $agentId,
            'tracking_date' => today()->toDateString(),
        ]);

        $quota = AgentDailyQuota::query()
            ->whereKey($quota->getKey())
            ->lockForUpdate()
            ->first() ?? $quota;

        $collected = (float) $quota->collected_amount + (float) ($task->amount ?? 0);
        $tier = CommissionTier::findTierForAmount($collected);

        $quota->update([
            'completed_tasks' => $quota->completed_tasks + 1,
            'collected_amount' => $collected,
            'earned_commission' => $tier?->payout_amount ?? 0.00,
        ]);

        return back()->with('success', 'Payment approved! '.$assignment['message']);
    }
}
