<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\Warehouse;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseSortingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SortingController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseSortingService $sortingService
    ) {
    }

    public function index(): View
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.sorting.index', [
            'warehouse' => $warehouse,
            'destinationWarehouses' => $this->portalService->destinationWarehouses()
                ->where('id', '!=', $warehouse->id)
                ->values(),
            'canReopenBatches' => (bool) Auth::guard('admin')->user()?->hasPermission('warehouse.sorting.reopen'),
        ]);
    }

    public function itemsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->sortingService->eligibleItemsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('shipmentItem.shipment', fn (Builder $shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', fn (Builder $itemQuery) => $itemQuery
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('tracking_code', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_town', 'like', "%{$search}%")
                    );
            });
        }

        if ($discrepancyType = $request->input('discrepancy_type')) {
            $query->where('discrepancy_type', $discrepancyType);
        }

        $sortBy = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['created_at', 'received_at', 'received_quantity'], true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, fn ($rows) => $this->sortingService->mapEligibleItems($rows));
    }

    public function batchesData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->sortingService->batchesQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('destinationWarehouse', fn (Builder $warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $query->latest('id');

        return $this->paginate($query, $request, function ($rows) {
            return $rows->map(function (SortBatch $batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'dispatch_mode' => $batch->dispatch_mode,
                    'dispatch_mode_label' => $batch->dispatch_mode === SortBatch::DISPATCH_LOCAL_DELIVERY
                        ? 'Local Delivery'
                        : 'Transfer',
                    'status' => $batch->status,
                    'destination_warehouse' => [
                        'id' => $batch->destinationWarehouse?->id,
                        'name' => $batch->destinationWarehouse?->name,
                        'code' => $batch->destinationWarehouse?->code,
                    ],
                    'created_at' => optional($batch->created_at)?->toIso8601String(),
                    'sealed_at' => optional($batch->sealed_at)?->toIso8601String(),
                    'items_count' => $batch->activeItems->count(),
                    'items' => $batch->activeItems->map(function ($row) {
                        return [
                            'sort_batch_item_id' => $row->id,
                            'shipment_item_id' => $row->shipment_item_id,
                            'warehouse_receipt_item_id' => $row->warehouse_receipt_item_id,
                            'description' => $row->shipmentItem?->description,
                            'tracking_code' => $row->shipmentItem?->tracking_code,
                            'quantity_allocated' => (int) $row->quantity_allocated,
                        ];
                    })->values(),
                ];
            })->values();
        });
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'dispatch_mode' => ['required', 'in:transfer,local_delivery'],
            'destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $destinationWarehouse = !empty($validated['destination_warehouse_id'])
            ? Warehouse::query()->findOrFail((int) $validated['destination_warehouse_id'])
            : null;

        $result = $this->sortingService->createBatch(
            originWarehouse: $warehouse,
            destinationWarehouse: $destinationWarehouse,
            user: $user,
            dispatchMode: (string) $validated['dispatch_mode'],
            notes: $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function addItems(Request $request, SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'warehouse_receipt_item_ids' => ['required', 'array', 'min:1'],
            'warehouse_receipt_item_ids.*' => ['integer', 'exists:warehouse_receipt_items,id'],
        ]);

        $result = $this->sortingService->addItems(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: $user,
            warehouseReceiptItemIds: $validated['warehouse_receipt_item_ids']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function removeItem(SortBatch $sortBatch, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $result = $this->sortingService->removeItem(
            batch: $sortBatch,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function seal(SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $result = $this->sortingService->sealBatch(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function reopen(SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.reopen');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $result = $this->sortingService->reopenBatch(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function paginate(Builder $query, Request $request, callable $mapper): JsonResponse
    {
        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        return response()->json([
            'data' => $mapper($rows),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
