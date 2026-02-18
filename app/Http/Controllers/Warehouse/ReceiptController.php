<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\PickupAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\ShipmentItem;
use App\Models\Shipment;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceipt;
use App\Services\StorageService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseReceivingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseReceivingService $warehouseReceivingService,
        private StorageService $storageService
    )
    {
    }

    public function pendingIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.receipts.pending', [
            'warehouse' => $warehouse,
            'statuses' => $this->pendingStatuses(),
        ]);
    }

    public function pendingShow(PickupAssignment $pickupAssignment): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if (is_null($pickupAssignment->picked_up_at) || (string) ($pickupAssignment->status?->value ?? $pickupAssignment->status) === 'cancelled') {
            abort(404);
        }

        $pickupAssignment->load([
            'shipment.vendor:id,name,business_name',
            'shipment.pickupRegion:id,name',
            'shipment.pickupDistrict:id,name',
            'shipment.deliveryRegion:id,name',
            'shipment.deliveryDistrict:id,name',
            'shipment.items.images',
            'shipment.items.deliveryRegion:id,name',
            'shipment.items.deliveryDistrict:id,name',
            'driver:id,name,phone',
            'targetWarehouse:id,name,code',
            'photos:id,pickup_assignment_id,shipment_item_id,path,type,created_at',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
            'warehouseReceipt.items.photos',
        ]);

        $receipt = $this->portalService->receiptForAssignment($pickupAssignment, $warehouse);

        $receiptConfig = [
            'save_item_url' => route('warehouse.receipts.pending.items.save', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'print_label_url' => route('warehouse.receipts.pending.items.print-label', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'finalize_url' => route('warehouse.receipts.pending.finalize', ['pickupAssignment' => $pickupAssignment]),
            'receipt' => $receipt ? [
                'id' => $receipt->id,
                'status' => $receipt->status,
                'status_label' => $this->receiptStatusLabel($receipt->status),
                'has_discrepancies' => $receipt->items->contains(fn ($item) => $item->discrepancy_type !== 'none'),
            ] : null,
            'can_approve_discrepancy' => Auth::guard('admin')->user()->hasPermission('warehouse.receiving.approve_discrepancy'),
            'items' => $pickupAssignment->shipment?->items?->map(function (ShipmentItem $item) use ($pickupAssignment, $receipt) {
                $receiptItem = $receipt?->items?->firstWhere('shipment_item_id', $item->id);
                $driverConfirmation = $pickupAssignment->itemConfirmations->firstWhere('shipment_item_id', $item->id);
                $driverPhotos = $pickupAssignment->photos
                    ->where('shipment_item_id', $item->id)
                    ->values()
                    ->map(fn ($photo) => [
                        'id' => $photo->id,
                        'type' => $photo->type,
                        'url' => $this->storageService->getUrl($photo->path),
                        'created_at' => optional($photo->created_at)?->toIso8601String(),
                    ])->values();

                $vendorPhotos = $item->images
                    ->map(fn ($image) => $image->getSignedUrl())
                    ->values();

                $vendorQuantity = (int) $item->quantity;
                $driverQuantity = $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null;
                $expectedQuantity = $driverQuantity ?? $vendorQuantity;
                $expectedSource = $driverQuantity !== null ? 'driver' : 'vendor_fallback';

                return [
                    'shipment_item_id' => $item->id,
                    'description' => $item->description,
                    'tracking_code' => $item->tracking_code,
                    'vendor_quantity' => $vendorQuantity,
                    'driver_confirmed_quantity' => $driverQuantity,
                    'driver_qty_matches_vendor' => $driverQuantity !== null
                        ? $driverQuantity === $vendorQuantity
                        : null,
                    'vendor_photos' => $vendorPhotos,
                    'driver_photos' => $driverPhotos,
                    'expected_quantity' => $expectedQuantity,
                    'expected_source' => $expectedSource,
                    'received_quantity' => (int) ($receiptItem?->received_quantity ?? 0),
                    'damaged_quantity' => (int) ($receiptItem?->damaged_quantity ?? 0),
                    'discrepancy_type' => $receiptItem?->discrepancy_type ?? 'none',
                    'condition_status' => $receiptItem?->condition_status ?? 'ok',
                    'notes' => $receiptItem?->notes,
                    'barcode_value' => $receiptItem?->barcode_value,
                    'barcode_print_count' => (int) ($receiptItem?->barcode_print_count ?? 0),
                    'photos' => $receiptItem
                        ? $this->warehouseReceivingService->serializeReceiptItem($receiptItem)['photos']
                        : [],
                ];
            })->values() ?? [],
        ];

        return view('warehouse.receipts.show', [
            'warehouse' => $warehouse,
            'assignment' => $pickupAssignment,
            'statusLabel' => $this->statusLabel($pickupAssignment->status?->value ?? (string) $pickupAssignment->status),
            'receipt' => $receipt,
            'receiptConfig' => $receiptConfig,
        ]);
    }

    public function pendingData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->portalService->pendingReceiptsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('shipment', fn (Builder $sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->input('sort', 'assigned_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['shipment_number', 'driver_name', 'assigned_at', 'arrived_warehouse_at', 'status', 'created_at'];
        if ($sortBy === 'shipment_number') {
            $query->orderBy(
                Shipment::select('shipment_number')
                    ->whereColumn('shipments.id', 'pickup_assignments.shipment_id')
                    ->limit(1),
                $sortDirection
            );
        } elseif ($sortBy === 'driver_name') {
            $query->orderBy(
                Driver::select('name')
                    ->whereColumn('drivers.id', 'pickup_assignments.driver_id')
                    ->limit(1),
                $sortDirection
            );
        } elseif (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('assigned_at');
        }

        return $this->paginatedResponse($query, $request, function (PickupAssignment $assignment) {
            $status = $assignment->status?->value ?? (string) $assignment->status;

            return [
                'id' => $assignment->id,
                'shipment_number' => $assignment->shipment?->shipment_number,
                'driver_name' => $assignment->driver?->name,
                'driver_phone' => $assignment->driver?->phone,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'assigned_at' => optional($assignment->assigned_at)?->format('Y-m-d H:i:s'),
                'arrived_warehouse_at' => optional($assignment->arrived_warehouse_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.receipts.pending.show', $assignment),
            ];
        });
    }

    public function savePendingItem(Request $request, PickupAssignment $pickupAssignment, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:0'],
            'damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'condition_status' => ['nullable', 'in:ok,damaged,partial'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:12288'],
            'remove_photo_ids' => ['nullable', 'array'],
            'remove_photo_ids.*' => ['integer', 'exists:warehouse_receipt_item_photos,id'],
        ]);

        $result = $this->warehouseReceivingService->upsertReceiptItem(
            assignment: $pickupAssignment,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['received_quantity'],
            damagedQuantity: (int) ($validated['damaged_quantity'] ?? 0),
            conditionStatus: $validated['condition_status'] ?? null,
            notes: $validated['notes'] ?? null,
            photos: $request->file('photos', []),
            removePhotoIds: $validated['remove_photo_ids'] ?? []
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function printPendingItemLabel(PickupAssignment $pickupAssignment, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $result = $this->warehouseReceivingService->printItemLabel(
            assignment: $pickupAssignment,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function finalizePendingReceipt(Request $request, PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
            'approval_reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $result = $this->warehouseReceivingService->finalizeReceipt(
            assignment: $pickupAssignment,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            notes: $validated['notes'] ?? null,
            approvalReason: $validated['approval_reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function receivedPickupsIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.pickups.received', [
            'warehouse' => $warehouse,
            'statuses' => $this->allPickupStatuses(),
        ]);
    }

    public function receivedPickupsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->portalService->receivedPickupsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('shipment', fn (Builder $sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->input('sort', 'received_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['assigned_at', 'received_at', 'arrived_warehouse_at', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('received_at');
        }

        return $this->paginatedResponse($query, $request, function (PickupAssignment $assignment) {
            $status = $assignment->status?->value ?? (string) $assignment->status;

            return [
                'id' => $assignment->id,
                'shipment_number' => $assignment->shipment?->shipment_number,
                'driver_name' => $assignment->driver?->name,
                'driver_phone' => $assignment->driver?->phone,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'assigned_at' => optional($assignment->assigned_at)?->format('Y-m-d H:i:s'),
                'arrived_warehouse_at' => optional($assignment->arrived_warehouse_at)?->format('Y-m-d H:i:s'),
                'received_at' => optional($assignment->received_at)?->format('Y-m-d H:i:s'),
                'received_warehouse' => $assignment->receivedWarehouse?->name,
                'receive_notes' => $assignment->receive_notes,
            ];
        });
    }

    public function receivedItemsIndex(): View
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.items.received', [
            'warehouse' => $warehouse,
        ]);
    }

    public function receivedItemsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->portalService->receivedItemsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('shipmentItem.shipment', fn (Builder $sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', fn (Builder $iq) => $iq->where('description', 'like', "%{$search}%"))
                    ->orWhereHas('receipt.pickupAssignment.driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        $sortBy = $request->input('sort', 'received_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['received_at', 'received_quantity', 'expected_quantity', 'damaged_quantity', 'discrepancy_type', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('received_at');
        }

        return $this->paginatedResponse($query, $request, function (WarehouseReceiptItem $item) {
            return [
                'id' => $item->id,
                'warehouse_receipt_id' => $item->warehouse_receipt_id,
                'pickup_assignment_id' => $item->receipt?->pickup_assignment_id,
                'shipment_number' => $item->shipmentItem?->shipment?->shipment_number,
                'item_description' => $item->shipmentItem?->description,
                'expected_quantity' => (int) $item->expected_quantity,
                'received_quantity' => (int) $item->received_quantity,
                'damaged_quantity' => (int) $item->damaged_quantity,
                'discrepancy_type' => $item->discrepancy_type,
                'driver_name' => $item->receipt?->pickupAssignment?->driver?->name,
                'received_at' => optional($item->received_at)?->format('Y-m-d H:i:s'),
                'notes' => $item->notes,
            ];
        });
    }

    private function paginatedResponse(Builder $query, Request $request, callable $mapper): JsonResponse
    {
        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $rows = $query->skip($offset)->take($perPage)->get()->map($mapper)->values();

        return response()->json([
            'data' => $rows,
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

    private function pendingStatuses(): array
    {
        return array_values(array_map(function (PickupAssignmentStatus $status) {
            return [
                'value' => $status->value,
                'label' => $this->statusLabel($status->value),
            ];
        }, array_filter(PickupAssignmentStatus::cases(), fn (PickupAssignmentStatus $status) => $status !== PickupAssignmentStatus::CANCELLED)));
    }

    private function allPickupStatuses(): array
    {
        return array_map(fn (PickupAssignmentStatus $status) => [
            'value' => $status->value,
            'label' => $this->statusLabel($status->value),
        ], PickupAssignmentStatus::cases());
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            PickupAssignmentStatus::ASSIGNED->value => 'Assigned',
            PickupAssignmentStatus::EN_ROUTE->value => 'En Route',
            PickupAssignmentStatus::ARRIVED->value => 'Arrived',
            PickupAssignmentStatus::PICKING_UP->value => 'Picking Up',
            PickupAssignmentStatus::COMPLETED->value => 'Pickup Completed',
            PickupAssignmentStatus::CANCELLED->value => 'Cancelled',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }

    private function receiptStatusLabel(?string $status): string
    {
        return match ($status) {
            WarehouseReceipt::STATUS_DRAFT => 'Draft',
            WarehouseReceipt::STATUS_DISCREPANCY_OPEN => 'Discrepancy Open',
            WarehouseReceipt::STATUS_FINALIZED => 'Finalized',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }
}
