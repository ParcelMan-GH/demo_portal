<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgentCallLog;
use App\Models\AgentDailyQuota;
use App\Models\CommissionTier;
use App\Models\ShipmentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The agent's commission history, and the breakdown behind any one cycle.
 *
 * The agent app previously had only a single figure and a flat list of days:
 * there was no way to ask "why is this day worth GH₵120?" and get an answer.
 * This endpoint answers exactly that, from the same two things the rest of the
 * system uses — the `agent_daily_quotas` ledger and the `commission_tiers`
 * table — so the app, the admin ledger and the home header can never disagree.
 *
 * Both endpoints derive the money with `CommissionTier::findTierForAmount()`
 * rather than reading the stored `earned_commission` column, for the same
 * reason `AgentParcelController::earnings()` does: the tier table is the source
 * of truth, and reading the cached column here would let a stale row show a
 * number the ledger would not agree with.
 */
class AgentCommissionController extends Controller
{
    /**
     * Every daily cycle this agent has, newest first, plus the running totals.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user();

        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        /*
         * One row per agent per day, so this is bounded by tenure rather than by
         * calls. Paged because a long-serving agent's history grows without
         * limit, but the summary below is computed over *all* cycles, not the
         * page — otherwise "total earned" would shrink as the agent paged.
         */
        $all = AgentDailyQuota::query()
            ->where('user_id', $agent->id)
            ->orderByDesc('tracking_date')
            ->orderByDesc('id')
            ->get();

        $totalEarned = 0.0;
        $availableBalance = 0.0;
        $lifetimeCollected = 0.0;
        $paidCycles = 0;

        foreach ($all as $quota) {
            $earned = (float) ($this->tierFor($quota)?->payout_amount ?? 0.00);

            $totalEarned += $earned;
            $lifetimeCollected += (float) $quota->collected_amount;

            if ($quota->is_unlocked || $quota->payout_status === 'unlocked') {
                $availableBalance += $earned;
            }

            if ($quota->payout_status === 'paid') {
                $paidCycles++;
            }
        }

        // Resolved once, then used to flag the row the Home header is showing —
        // so the history screen marks that cycle rather than assuming "today".
        $currentCycleId = $this->currentCycleFor($agent->id)?->getKey();

        $rows = $all->take($perPage)
            ->map(function (AgentDailyQuota $q) use ($currentCycleId) {
                return $this->cycleSummary($q) + [
                    'is_current' => $currentCycleId !== null
                        && (int) $currentCycleId === (int) $q->getKey(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_earned' => $this->money($totalEarned),
                    'total_earned_value' => round($totalEarned, 2),
                    'available_balance' => $this->money($availableBalance),
                    'available_balance_value' => round($availableBalance, 2),
                    'lifetime_collected' => $this->money($lifetimeCollected),
                    'lifetime_collected_value' => round($lifetimeCollected, 2),
                    'cycles' => $all->count(),
                    'paid_cycles' => $paidCycles,
                    // The unpaid cycles are what the agent is still owed, so the
                    // screen can say so rather than leaving the two numbers
                    // unexplained.
                    'unpaid_cycles' => $all->count() - $paidCycles,
                ],
                // What the header is currently showing, so the history screen can
                // mark the cycle it belongs to instead of guessing "today".
                'current_cycle_id' => $currentCycleId,
                'commissions' => $rows,
                'pagination' => [
                    'per_page' => $perPage,
                    'total' => $all->count(),
                    'has_more' => $all->count() > $perPage,
                ],
            ],
        ]);
    }

    /**
     * One cycle in full: its band, its totals, and every call that built it.
     */
    public function show(Request $request, $commission): JsonResponse
    {
        $agent = $request->user();

        /*
         * Looked up by hand and scoped to the caller rather than relying on
         * route-model binding. An agent asking for another agent's cycle must get
         * a 404 — not a 403 that confirms the row exists, and never the row.
         */
        $quota = AgentDailyQuota::query()
            ->where('user_id', $agent->id)
            ->whereKey($commission)
            ->first();

        if (! $quota) {
            return response()->json([
                'success' => false,
                'message' => 'Commission cycle not found.',
            ], 404);
        }

        $tier = $this->tierFor($quota);
        $earned = (float) ($tier?->payout_amount ?? 0.00);

        return response()->json([
            'success' => true,
            'data' => [
                'commission' => $this->cycleSummary($quota) + [
                    'band_label' => $this->bandLabel($tier),
                    'rate_label' => $this->money($earned),
                    'next_band' => $this->nextBand($quota),
                ],
                'line_items' => $this->lineItems($agent->id, $quota, $earned),
                'totals' => [
                    'collected' => $this->money((float) $quota->collected_amount),
                    'earned' => $this->money($earned),
                    'calls' => (int) $quota->completed_tasks,
                ],
            ],
        ]);
    }

    /**
     * The per-call breakdown of one cycle.
     *
     * Commission here is banded, not per-call: one payment of 3500 earns 120, and
     * a second payment that tips the total into the next band earns only the
     * *difference*. Reporting a flat per-call figure would be a lie — the number
     * would not add up to the cycle total. So each row carries the running
     * collected total after that call, the band it landed in, and the marginal
     * amount that call actually added. Those marginals sum to the cycle's
     * payout, which is what makes the screen's arithmetic checkable by hand.
     */
    private function lineItems(int $agentId, AgentDailyQuota $quota, float $cycleEarned): array
    {
        $logs = AgentCallLog::query()
            ->where('agent_id', $agentId)
            // The quota is stamped with `today()` when the first confirmed call
            // of the day lands, and `created_at` is written in the same timezone,
            // so the calendar day lines up.
            ->whereDate('created_at', $quota->tracking_date->toDateString())
            ->where('outcome', AgentCallLog::OUTCOME_CONFIRMED)
            ->with([
                'shipmentItem:id,tracking_code,status,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_landmark,delivery_gh_post_address,delivery_region_id,delivery_district_id',
                'shipmentItem.deliveryRegion:id,name',
                'shipmentItem.deliveryDistrict:id,name',
            ])
            ->orderBy('id')
            ->get();

        $running = 0.0;
        $previousPayout = 0.0;
        $rows = [];

        foreach ($logs as $log) {
            $running += (float) ($log->amount_paid ?? 0);
            $tier = CommissionTier::findTierForAmount($running);
            $payout = (float) ($tier?->payout_amount ?? 0.00);

            // The slice of commission this particular call produced. Can be 0
            // (still inside the same band) or the whole band jump — both are
            // true, and saying so is the point of the screen.
            $marginal = $payout - $previousPayout;
            $previousPayout = $payout;

            $item = $log->shipmentItem;

            if ($item) {
                $item->setAttribute('delivery_location_label', $item->deliveryLocationLabel());
            }

            $rows[] = [
                'id' => $log->getKey(),
                'parcel_id' => $log->shipment_item_id,
                'tracking_code' => $item?->tracking_code,
                'recipient_name' => $item?->delivery_recipient_name,
                'recipient_phone' => $item?->delivery_recipient_phone,
                'location' => $item?->delivery_location_label,
                'shipment_item' => $item,
                // The only thing in the system that credits commission. Kept as
                // a machine value plus a label so the app never has to hardcode
                // the wording.
                'action' => 'customer_call_confirmation',
                'action_label' => 'Customer Call Confirmation',
                'notes' => $log->notes,
                'occurred_at' => optional($log->created_at)->toIso8601String(),
                'amount_collected' => $this->money((float) ($log->amount_paid ?? 0)),
                'amount_collected_value' => round((float) ($log->amount_paid ?? 0), 2),
                'running_total' => $this->money($running),
                'running_total_value' => round($running, 2),
                'band_min' => $tier?->min_collection !== null ? (float) $tier->min_collection : null,
                'band_max' => $tier?->max_collection !== null ? (float) $tier->max_collection : null,
                'base_rate' => $this->money($payout),
                'base_rate_value' => round($payout, 2),
                'marginal_earned' => $this->money($marginal),
                'marginal_earned_value' => round($marginal, 2),
            ];
        }

        /*
         * A cycle can carry `completed_tasks` with no matching confirmed log —
         * a quota credited by the admin dashboard, or calls logged before the
         * API path existed. Rather than let the rows silently not add up to the
         * total, the discrepancy is reported so the screen can account for it.
         */
        $loggedCollected = array_sum(array_column($rows, 'amount_collected_value'));

        return [
            'rows' => $rows,
            'count' => count($rows),
            'logged_collected' => $this->money($loggedCollected),
            'logged_collected_value' => round($loggedCollected, 2),
            'matches_cycle' => abs($loggedCollected - (float) $quota->collected_amount) < 0.01,
            'cycle_collected' => $this->money((float) $quota->collected_amount),
            'cycle_collected_value' => round((float) $quota->collected_amount, 2),
            'cycle_earned' => $this->money($cycleEarned),
            'cycle_earned_value' => round($cycleEarned, 2),
        ];
    }

    /**
     * The shape both endpoints return for one cycle.
     */
    private function cycleSummary(AgentDailyQuota $quota): array
    {
        $tier = $this->tierFor($quota);
        $earned = (float) ($tier?->payout_amount ?? 0.00);

        return [
            'id' => $quota->getKey(),
            'tracking_date' => optional($quota->tracking_date)->toDateString(),
            'date_label' => optional($quota->tracking_date)->format('D, M j, Y'),
            'collected_amount' => $this->money((float) $quota->collected_amount),
            'collected_amount_value' => round((float) $quota->collected_amount, 2),
            'calls' => (int) $quota->completed_tasks,
            'band_min' => $tier?->min_collection !== null ? (float) $tier->min_collection : null,
            'band_max' => $tier?->max_collection !== null ? (float) $tier->max_collection : null,
            'band_label' => $this->bandLabel($tier),
            'earned_amount' => $this->money($earned),
            'earned_amount_value' => round($earned, 2),
            'payout_status' => $quota->payout_status,
            'is_unlocked' => (bool) $quota->is_unlocked,
            'is_paid' => $quota->payout_status === 'paid',
            'is_current' => false,
        ];
    }

    /** The cycle the Home header is showing, mirroring `overview()`. */
    private function currentCycleFor(int $agentId): ?AgentDailyQuota
    {
        return AgentDailyQuota::query()
            ->where('user_id', $agentId)
            ->where(function ($query) {
                $query->whereDate('tracking_date', today())
                    ->orWhere(fn ($q) => $q->where('payout_status', '!=', 'paid'));
            })
            ->orderByDesc('tracking_date')
            ->first();
    }

    private function tierFor(AgentDailyQuota $quota): ?CommissionTier
    {
        return CommissionTier::findTierForAmount((float) $quota->collected_amount);
    }

    /** "GH₵2,100 – GH₵2,999" or "GH₵4,001 and above". */
    private function bandLabel(?CommissionTier $tier): ?string
    {
        if (! $tier) {
            return null;
        }

        $min = $this->money((float) $tier->min_collection);

        return $tier->max_collection === null
            ? $min.' and above'
            : $min.' – '.$this->money((float) $tier->max_collection);
    }

    /**
     * How far the agent is from the next band, or null when already on the top
     * band. Lets the screen say "GH₵1 more for the next rate" instead of just
     * showing a static band.
     */
    private function nextBand(AgentDailyQuota $quota): ?array
    {
        $collected = (float) $quota->collected_amount;

        $next = CommissionTier::active()
            ->where('min_collection', '>', $collected)
            ->orderBy('min_collection')
            ->first();

        if (! $next) {
            return null;
        }

        $gap = (float) $next->min_collection - $collected;

        return [
            'band_label' => $this->bandLabel($next),
            'rate' => $this->money((float) $next->payout_amount),
            'collected_needed' => $this->money($gap),
            'collected_needed_value' => round($gap, 2),
        ];
    }

    /**
     * Format an amount the way the agent app already renders money ("GH₵ 12.00"),
     * so a populated value and the app's own fallback look identical.
     */
    private function money(float $amount): string
    {
        return 'GH₵ '.number_format($amount, 2);
    }
}
