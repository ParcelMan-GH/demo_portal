<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AgentDailyQuota;
use App\Models\RecipientPaymentTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentAllocationController extends Controller
{
    public function index(): View
    {
        // 1. Fetch Call Agents and their workload for TODAY
        $agents = User::query()
            ->where('is_active', true)
            // Optional: Filter by role if you have one, e.g., ->where('role', 'call_agent')
            ->get()
            ->map(function ($agent) {
                $quota = AgentDailyQuota::where('user_id', $agent->id)
                    ->where('tracking_date', today())
                    ->first();
                
                return [
                    'id'              => $agent->id,
                    'name'            => $agent->name, // <-- Fixed! No extra spaces.
                    'assigned_today'  => $quota ? $quota->assigned_tasks : 0,
                    'completed_today' => $quota ? $quota->completed_tasks : 0,
                    'backlog'         => $quota ? ($quota->assigned_tasks - $quota->completed_tasks) : 0,
                ];
            })->values();

        // 2. Fetch Unassigned Tasks (Pending payments, failed deliveries, etc.)
        $unassignedTasks = RecipientPaymentTask::query()
            ->whereNull('assigned_to_user_id')
            ->whereIn('status', ['pending', 'failed', 'unreachable'])
            ->latest('id')
            ->get()
            ->map(function ($task) {
                return [
                    'id'          => $task->id,
                    'package'     => $task->shipmentItem->description ?? 'Unknown Package',
                    'customer'    => $task->shipmentItem->delivery_recipient_name ?? 'Unknown',
                    'phone'       => $task->shipmentItem->delivery_recipient_phone ?? '-',
                    'amount_due'  => $task->amount ?? 0,
                    'status'      => $task->status,
                ];
            })->values();

        return view('admin.agents.allocation', [
            'pageTitle'       => 'Smart Call Allocation',
            'pageSubtitle'    => 'Distribute follow-ups and monitor agent daily quotas.',
            'agents'          => $agents,
            'unassignedTasks' => $unassignedTasks,
            'assignEndpoint'  => route('admin.agents.allocation.assign'), 
        ]);
    }

    public function assignTasks(Request $request)
    {
        $validated = $request->validate([
            'agent_id'   => 'required|exists:users,id',
            'task_ids'   => 'required|array',
            'task_ids.*' => 'exists:recipient_payment_tasks,id'
        ]);

        $agentId = $validated['agent_id'];
        $taskIds = $validated['task_ids'];
        $taskCount = count($taskIds);

        // 1. Assign the actual tasks to the agent
        RecipientPaymentTask::whereIn('id', $taskIds)->update([
            'assigned_to_user_id' => $agentId,
            'updated_at'          => now(),
        ]);

        // 2. Update or Create the Agent's Daily Quota Ledger
        $quota = AgentDailyQuota::firstOrCreate(
            [
                'user_id'       => $agentId,
                'tracking_date' => today(),
            ],
            [
                'assigned_tasks'   => 0,
                'completed_tasks'  => 0,
                'collected_amount' => 0.00,
            ]
        );

        // Increment their daily target load
        $quota->increment('assigned_tasks', $taskCount);

        return response()->json([
            'success' => true, 
            'message' => "Successfully assigned {$taskCount} tasks!"
        ]);
    }
}