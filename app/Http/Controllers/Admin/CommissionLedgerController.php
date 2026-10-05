<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCallLog;
use App\Models\AgentDailyQuota;
use App\Models\CommissionTier;
use App\Services\AdminAuditLogService;
use App\Services\Agent\AgentCallConfirmationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CommissionLedgerController extends Controller
{
    public function __construct(
        private AgentCallConfirmationService $confirmations,
        private AdminAuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        // Default to today, but allow admin to look at previous days if needed
        $date = $request->get('date', today()->format('Y-m-d'));

        // Fetch all quotas for the selected date, including the agent details
        $ledgers = AgentDailyQuota::with(['agent', 'overriddenBy'])
            ->where('tracking_date', $date)
            ->get()
            ->map(function ($quota) {
                // Derive the payout bracket exactly as the agent app does.
                $tier = CommissionTier::findTierForAmount($quota->collected_amount);
                $earned = $tier ? $tier->payout_amount : 0.00;

                /*
                 * Whether this day may be released, and what is holding it back.
                 *
                 * Computed by the same service the agent app reads, so this ledger
                 * and the agent's own screen cannot disagree about what is
                 * outstanding — the failure mode that matters here is telling an
                 * agent they are clear while the desk sees them blocked, or the
                 * reverse.
                 */
                $state = $this->confirmations->stateForQuota($quota);

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

                    // Pickup-code confirmation, added with this feature.
                    'codes_required'   => $state['required'],
                    'codes_confirmed'  => $state['confirmed'],
                    'codes_exempt'     => $state['exempt'],
                    'codes_pending'    => $state['pending'],
                    'can_unlock'       => $state['can_unlock'],
                    'awaiting'         => $state['pending_calls'],
                ];
            });

        return view('admin.agents.ledger', [
            'pageTitle'    => 'Commission & Payouts Ledger',
            'pageSubtitle' => 'Monitor financial targets, quotas, and manage overrides.',
            'ledgers'      => $ledgers,
            'currentDate'  => $date,
        ]);
    }

    /**
     * Confirm a parcel's pickup code on the agent's behalf.
     *
     * The agent is meant to collect the code from the recipient (or the hub agent)
     * themselves. This is the desk's fallback for when they cannot — the recipient
     * is unreachable, the agent is off shift, the line dropped. It is recorded as
     * `admin` rather than as the agent, so a desk that quietly confirms everything
     * for everybody stays visible rather than looking like diligent agents.
     */
    public function confirmCode(Request $request, AgentDailyQuota $quota)
    {
        $validated = $request->validate([
            'shipment_item_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:64'],
            'source' => ['nullable', 'string', 'in:customer,hub_agent'],
        ]);

        $agentId = (int) $quota->user_id;

        $log = $this->confirmations->latestLogForItem((int) $validated['shipment_item_id'], $agentId);

        if (! $log) {
            return back()->with('error', 'That parcel has no call logged by this agent, so there is nothing to confirm against.');
        }

        $result = $this->confirmations->verify($log, (string) $validated['code']);

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        $confirmed = $this->confirmations->confirm(
            $log,
            (string) ($validated['source'] ?? AgentCallLog::CODE_SOURCE_CUSTOMER),
            AgentCallLog::CONFIRMED_BY_ADMIN,
            (int) ($this->adminUser()?->id ?? 0),
        );

        $this->auditLog->logMoneyAdjustment(
            $this->adminUser(),
            'agent_commission.code_confirmed',
            sprintf(
                'Pickup code confirmed on agent #%d\'s behalf for parcel #%d (%s)',
                $agentId,
                $confirmed->shipment_item_id,
                $confirmed->shipmentItem?->tracking_code ?? 'no tracking code'
            ),
            [
                'agent_daily_quota_id' => $quota->id,
                'agent_user_id' => $agentId,
                'shipment_item_id' => $confirmed->shipment_item_id,
                'agent_call_log_id' => $confirmed->getKey(),
                'source' => $confirmed->pickup_code_source,
                'confirmed_by' => AgentCallLog::CONFIRMED_BY_ADMIN,
                'before_release' => $confirmed->pickup_code_confirmed_before_release,
            ]
        );

        return back()->with('success', 'Pickup code confirmed for the agent.');
    }

    /**
     * Release a day's commission by hand.
     *
     * This remains the escape hatch when parcels genuinely cannot be confirmed,
     * but it now records how many were still outstanding at the moment the desk
     * waved it through. Without that number an override and a clean release look
     * identical in the ledger afterwards, which is what makes a rubber stamp
     * invisible.
     */
    public function override(Request $request, AgentDailyQuota $quota)
    {
        // 1. Validate the admin actually provided a reason
        $validated = $request->validate([
            'override_reason' => 'required|string|min:5|max:255',
        ]);

        $state = $this->confirmations->stateForQuota($quota);

        // 2. Unlock the quota and stamp it with the Admin's ID for auditing
        $quota->update([
            'is_unlocked'      => true,
            'payout_status'    => 'unlocked',
            'override_reason'  => $validated['override_reason'],
            'overridden_by_id' => $this->adminUser()?->id,
            'overridden_at'    => now(),
        ]);

        $this->auditLog->logMoneyAdjustment(
            $this->adminUser(),
            'agent_commission.override_unlock',
            sprintf(
                'Agent #%d\'s commission for %s released by override with %d parcel(s) still unconfirmed',
                (int) $quota->user_id,
                optional($quota->tracking_date)->format('Y-m-d') ?? 'an unknown date',
                $state['pending']
            ),
            [
                'agent_daily_quota_id' => $quota->id,
                'agent_user_id' => (int) $quota->user_id,
                'tracking_date' => optional($quota->tracking_date)->toDateString(),
                'reason' => $validated['override_reason'],
                'codes_required' => $state['required'],
                'codes_confirmed' => $state['confirmed'],
                'codes_pending_at_override' => $state['pending'],
                'unconfirmed_items' => collect($state['pending_calls'])->pluck('shipment_item_id')->all(),
            ]
        );

        return back()->with('success', $state['pending'] > 0
            ? 'Agent payout unlocked, with '.$state['pending'].' parcel(s) still unconfirmed. This has been logged.'
            : 'Agent payout successfully unlocked!');
    }

    private function adminUser(): ?\App\Models\User
    {
        return Auth::guard('admin')->user() ?? Auth::user();
    }
}
