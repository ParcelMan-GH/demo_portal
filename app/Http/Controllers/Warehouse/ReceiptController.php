<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\PickupAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __construct(private WarehousePortalService $portalService)
    {
    }

    public function pendingIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.receipts.pending', [
            'warehouse' => $warehouse,
            'statuses' => PickupAssignmentStatus::toArray(),
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
        $allowedSorts = ['assigned_at', 'arrived_warehouse_at', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('assigned_at');
        }

        return $this->paginatedResponse($query, $request, function (PickupAssignment $assignment) {
            return [
                'id' => $assignment->id,
                'shipment_number' => $assignment->shipment?->shipment_number,
                'driver_name' => $assignment->driver?->name,
                'driver_phone' => $assignment->driver?->phone,
                'status' => $assignment->status?->value ?? (string) $assignment->status,
                'assigned_at' => optional($assignment->assigned_at)?->format('Y-m-d H:i:s'),
                'arrived_warehouse_at' => optional($assignment->arrived_warehouse_at)?->format('Y-m-d H:i:s'),
                'target_warehouse' => $assignment->targetWarehouse?->name,
            ];
        });
    }

    public function receivedPickupsIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.pickups.received', [
            'warehouse' => $warehouse,
            'statuses' => PickupAssignmentStatus::toArray(),
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
            return [
                'id' => $assignment->id,
                'shipment_number' => $assignment->shipment?->shipment_number,
                'driver_name' => $assignment->driver?->name,
                'driver_phone' => $assignment->driver?->phone,
                'status' => $assignment->status?->value ?? (string) $assignment->status,
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
                    ->orWhereHas('pickupAssignment.driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        $sortBy = $request->input('sort', 'confirmed_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['confirmed_at', 'confirmed_quantity', 'expected_quantity', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('confirmed_at');
        }

        return $this->paginatedResponse($query, $request, function (PickupItemConfirmation $item) {
            return [
                'id' => $item->id,
                'pickup_assignment_id' => $item->pickup_assignment_id,
                'shipment_number' => $item->shipmentItem?->shipment?->shipment_number,
                'item_description' => $item->shipmentItem?->description,
                'expected_quantity' => (int) $item->expected_quantity,
                'confirmed_quantity' => (int) $item->confirmed_quantity,
                'driver_name' => $item->pickupAssignment?->driver?->name,
                'confirmed_at' => optional($item->confirmed_at)?->format('Y-m-d H:i:s'),
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
}
