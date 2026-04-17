<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VendorCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorEarningsController extends Controller
{
    public function __construct(
        private VendorCommissionService $commissionService
    ) {}

    /**
     * Get vendor earnings summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $vendor = $request->user();
        $summary = $this->commissionService->getVendorSummary($vendor);

        return response()->json([
            'success' => true,
            'message' => 'Earnings summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    /**
     * List vendor's earnings (paginated).
     */
    public function earnings(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:approved,paid'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);

        $query = $vendor->earnings()->with('shipment:id,shipment_number');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $total = (clone $query)->count();

        $rows = $query
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn($earning) => [
                'id' => $earning->id,
                'shipment_number' => $earning->shipment?->shipment_number,
                'amount' => (float) $earning->amount,
                'status' => $earning->status,
                'created_at' => $earning->created_at?->toIso8601String(),
            ])
            ->toArray();

        $count = count($rows);
        $hasMore = ($offset + $count) < $total;
        $nextOffset = $hasMore ? ($offset + $limit) : null;
        $currentPage = (int) floor($offset / $limit) + 1;
        $lastPage = max((int) ceil($total / $limit), 1);

        return response()->json([
            'success' => true,
            'message' => 'Earnings retrieved successfully.',
            'data' => [
                'earnings' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => $hasMore,
                    'next_offset' => $nextOffset,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $limit,
                ],
            ],
        ]);
    }

    /**
     * List vendor's payouts (paginated).
     */
    public function payouts(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:pending,sent,confirmed'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);

        $query = $vendor->payouts();

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $total = (clone $query)->count();

        $rows = $query
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn($payout) => [
                'id' => $payout->id,
                'amount' => (float) $payout->amount,
                'status' => $payout->status,
                'payment_method' => $payout->payment_method,
                'payment_reference' => $payout->payment_reference,
                'payment_phone' => $payout->payment_phone,
                'sent_at' => $payout->sent_at?->toIso8601String(),
                'confirmed_at' => $payout->confirmed_at?->toIso8601String(),
                'created_at' => $payout->created_at?->toIso8601String(),
            ])
            ->toArray();

        $count = count($rows);
        $hasMore = ($offset + $count) < $total;
        $nextOffset = $hasMore ? ($offset + $limit) : null;
        $currentPage = (int) floor($offset / $limit) + 1;
        $lastPage = max((int) ceil($total / $limit), 1);

        return response()->json([
            'success' => true,
            'message' => 'Payouts retrieved successfully.',
            'data' => [
                'payouts' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => $hasMore,
                    'next_offset' => $nextOffset,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $limit,
                ],
            ],
        ]);
    }
}
