<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentDailyQuota;
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

        // 3. Update the Agent's Commission Ledger (Hardcoded User ID 1)
        $quota = AgentDailyQuota::where('user_id', 1)
            ->whereDate('tracking_date', today())
            ->first();

        if ($quota) {
            $quota->increment('completed_tasks');
            $quota->increment('collected_amount', $task->amount ?? 0);
        }

        return back()->with('success', 'Payment approved! '.$assignment['message']);
    }
}
