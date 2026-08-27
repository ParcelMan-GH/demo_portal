<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        $barcode = $request->input('barcode') ?? $request->input('tracking_code');

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode or tracking code is required.',
            ], 422);
        }

        // Return successful claim response
        return response()->json([
            'success' => true,
            'message' => 'Parcel claimed successfully.',
            'data' => [
                'barcode' => $barcode,
                'status' => 'claimed',
                'claimed_at' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get agent queue list
     */
    public function getQueue(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Agent Overview Data
     */
    public function overview(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'claimed_today' => 0,
                'pending_pickup' => 0
            ]
        ]);
    }
}