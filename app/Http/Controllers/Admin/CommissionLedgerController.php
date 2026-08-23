<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentDailyQuota;
use App\Models\CommissionTier;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CommissionLedgerController extends Controller
{
    public function index(Request $request): View
    {
        // Default to today, but allow admin to look at previous days if needed
        $date = $request->get('date', today()->format('Y-m-d'));

        // Fetch all quotas for the selected date, including the agent details
        $ledgers = AgentDailyQuota::with(['agent', 'overriddenBy'])
            ->where('tracking_date', $date)
            ->get()
            ->map(function ($quota) {
                // Use our handy model function to find their correct payout bracket!
                $tier = CommissionTier::findTierForAmount($quota->collected_amount);
                $earned = $tier ? $tier->payout_amount : 0.00;

                return [
                    'id'               => $quota->id,
                    'agent_name'       => $quota->agent->name ?? 'Unknown Agent',
                    'assigned_tasks'   => $quota->assigned_tasks,
                    'completed_tasks'  => $quota->completed_tasks,
                    'collected_amount' => $quota->collected_amount,
                    'earned_commission'=> $earned,
                    'is_unlocked'      => $quota->is_unlocked,
                    'has_cleared_list' => $quota->hasClearedList(),
                    'override_reason'  => $quota->override_reason,
                    'overridden_by'    => $quota->overriddenBy->name ?? null,
                ];
            });

        return view('admin.agents.ledger', [
            'pageTitle'    => 'Commission & Payouts Ledger',
            'pageSubtitle' => 'Monitor financial targets, quotas, and manage overrides.',
            'ledgers'      => $ledgers,
            'currentDate'  => $date,
        ]);
    }

    public function override(Request $request, AgentDailyQuota $quota)
    {
        // 1. Validate the admin actually provided a reason
        $validated = $request->validate([
            'override_reason' => 'required|string|min:5|max:255',
        ]);

        // 2. Unlock the quota and stamp it with the Admin's ID for auditing
        $quota->update([
            'is_unlocked'      => true,
            'payout_status'    => 'unlocked',
            'override_reason'  => $validated['override_reason'],
            'overridden_by_id' => Auth::guard('admin')->id() ?? Auth::id(), // Fallback depending on your auth setup
            'overridden_at'    => now(),
        ]);

        return back()->with('success', 'Agent payout successfully unlocked!');
    }
}