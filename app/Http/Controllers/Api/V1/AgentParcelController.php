<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShipmentItem;
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

        // Assign to agent
        $parcel->update([
            'agent_id' => $agent->id,
            'status' => 'claimed_by_agent',
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
            ->where('status', 'claimed_by_agent')
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
                'pending_calls' => ShipmentItem::where('agent_id', $agent->id)->where('status', 'claimed_by_agent')->count(),
                'rescheduled' => 0,
            ]
        ]);
    }
}