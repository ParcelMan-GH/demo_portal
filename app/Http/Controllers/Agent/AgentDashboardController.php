<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\RecipientPaymentTask;
use App\Models\OutgoingBatch;
use App\Models\AgentDailyQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentDashboardController extends Controller
{
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

        // --- NEW SAFETY CHECK ---
        // If the parcel is missing its destination, reject it gracefully instead of crashing!
        if (empty($parcel->delivery_region_id) || empty($parcel->delivery_district_id)) {
            return back()->with('error', 'Cannot auto-batch! This parcel is missing its Region or District routing information.');
        }

        // 1. THE AUTO-BATCHING MAGIC
        $batch = OutgoingBatch::firstOrCreate(
            [
                'delivery_region_id'   => $parcel->delivery_region_id,
                'delivery_district_id' => $parcel->delivery_district_id,
                'status'               => 'open',
            ],
            [
                'batch_number'         => 'BATCH-' . strtoupper(Str::random(6)),
            ]
        );

        // 2. Update the Parcel & Task statuses
        $parcel->update([
            'outgoing_batch_id' => $batch->id,
            'status'            => 'ready_for_hub_transfer'
        ]);

        $task->update(['status' => 'payment_approved']);

        // 3. Update the Agent's Commission Ledger (Hardcoded User ID 1)
        $quota = AgentDailyQuota::where('user_id', 1)
            ->whereDate('tracking_date', today())
            ->first();

        if ($quota) {
            $quota->increment('completed_tasks');
            $quota->increment('collected_amount', $task->amount ?? 0);
        }

        return back()->with('success', 'Payment approved! Parcel safely added to ' . $batch->batch_number);
    }
}