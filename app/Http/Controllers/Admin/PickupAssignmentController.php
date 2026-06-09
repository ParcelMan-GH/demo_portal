<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PickupAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PickupAssignmentService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PickupAssignmentController extends Controller
{
    public function __construct(
        private PickupAssignmentService $pickupAssignmentService
    ) {}

    /**
     * Show the pickup assignments listing page.
     */
    public function index()
    {
        $this->authorizePermission('shipments.view');

        return view('admin.pickups.index', [
            'statuses' => PickupAssignmentStatus::toArray(),
        ]);
    }

    /**
     * Get paginated pickup assignment data.
     */
    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.view');

        $query = PickupAssignment::with(['shipment.vendor', 'driver', 'targetWarehouse', 'assignedBy']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('shipment', fn ($sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipment.vendor', fn ($sq) => $sq->where('business_name', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('targetWarehouse', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Date range on assigned_at
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('assigned_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('assigned_at', '<=', $dateTo);
        }

        // Sorting
        $sortBy = $request->get('sort', 'assigned_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['status', 'assigned_at', 'completed_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('assigned_at', 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $assignments = $query->paginate($perPage);

        return response()->json([
            'data' => $assignments->map(function (PickupAssignment $a) {
                return [
                    'id' => $a->id,
                    'shipment_id' => $a->shipment_id,
                    'shipment_number' => $a->shipment?->shipment_number,
                    'vendor_name' => $a->shipment?->vendor?->business_name ?? '-',
                    'driver_id' => $a->driver_id,
                    'driver_name' => $a->driver?->name ?? '-',
                    'driver_phone' => $a->driver?->phone ?? '-',
                    'target_warehouse' => $a->targetWarehouse?->name ?? '-',
                    'target_warehouse_id' => $a->target_warehouse_id,
                    'status' => $a->status instanceof PickupAssignmentStatus ? $a->status->value : $a->status,
                    'status_label' => $a->status instanceof PickupAssignmentStatus ? $a->status->label() : ucfirst(str_replace('_', ' ', $a->status ?? '')),
                    'assigned_by' => $a->assignedBy?->name ?? '-',
                    'assigned_at' => $a->assigned_at?->format('Y-m-d H:i:s'),
                    'en_route_at' => $a->en_route_at?->format('Y-m-d H:i:s'),
                    'arrived_at' => $a->arrived_at?->format('Y-m-d H:i:s'),
                    'picked_up_at' => $a->picked_up_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $a->completed_at?->format('Y-m-d H:i:s'),
                    'received_at' => $a->received_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'from' => $assignments->firstItem() ?? 0,
                'to' => $assignments->lastItem() ?? 0,
                'total' => $assignments->total(),
                'last_page' => $assignments->lastPage(),
            ],
        ]);
    }

    /**
     * Get available riders, optionally filtered by vehicle type.
     */
    public function availableDrivers(Request $request)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = $request->validate([
            'vehicle_type' => ['nullable', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'assignment_type' => ['nullable', Rule::in(Driver::CAPABILITIES)],
        ]);

        $drivers = $this->pickupAssignmentService->getAvailableDrivers(
            $validated['vehicle_type'] ?? null,
            $validated['assignment_type'] ?? Driver::CAPABILITY_PICKUP
        );

        return response()->json([
            'data' => $drivers,
        ]);
    }

    /**
     * Get active warehouses for pickup destination.
     */
    public function availableWarehouses()
    {
        $this->authorizePermission('shipments.assign_driver');

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'data' => $warehouses,
        ]);
    }

    /**
     * Assign a rider to a shipment.
     */
    public function assign(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'target_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);

        // Validate shipment readiness
        $errors = [];
        if (blank($shipment->pickup_contact_name)) $errors[] = 'Pickup contact name is required.';
        if (blank($shipment->pickup_contact_phone)) $errors[] = 'Pickup contact phone is required.';
        if (blank($shipment->pickup_town) && blank($shipment->pickup_region_id)) $errors[] = 'Pickup location is required.';
        if ($shipment->items()->count() === 0) $errors[] = 'At least one package is required.';

        // Direct delivery requires delivery details
        if ($shipment->fulfillment_type?->value === 'direct') {
            if ($shipment->destination_mode->value === 'single') {
                if (blank($shipment->delivery_recipient_name)) $errors[] = 'Delivery recipient name is required for direct delivery.';
                if (blank($shipment->delivery_recipient_phone)) $errors[] = 'Delivery recipient phone is required for direct delivery.';
                if (blank($shipment->delivery_town) && blank($shipment->delivery_region_id)) $errors[] = 'Delivery location is required for direct delivery.';
            } else {
                $shipment->items->each(function ($item, $i) use (&$errors) {
                    if ($item->fulfillment_type?->value === 'direct') {
                        if (blank($item->delivery_recipient_name)) $errors[] = 'Package ' . ($i + 1) . ': recipient name required for direct delivery.';
                        if (blank($item->delivery_recipient_phone)) $errors[] = 'Package ' . ($i + 1) . ': recipient phone required for direct delivery.';
                        if (blank($item->delivery_town) && blank($item->delivery_region_id)) $errors[] = 'Package ' . ($i + 1) . ': delivery location required for direct delivery.';
                    }
                });
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => $errors[0],
                'errors' => $errors,
            ], 422);
        }

        $driver = Driver::findOrFail($validated['driver_id']);
        $admin = Auth::guard('admin')->user();

        $result = $this->pickupAssignmentService->assign(
            $shipment,
            $driver,
            $admin,
            $validated['notes'] ?? null,
            (int) $validated['target_warehouse_id']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Update the rider and/or target warehouse on an existing ASSIGNED pickup.
     */
    public function update(Request $request, PickupAssignment $pickupAssignment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = $request->validate([
            'driver_id'           => ['nullable', 'exists:drivers,id'],
            'target_warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $result = $this->pickupAssignmentService->updateAssignment(
            assignment:       $pickupAssignment,
            newDriverId:      isset($validated['driver_id']) ? (int) $validated['driver_id'] : null,
            newWarehouseId:   isset($validated['target_warehouse_id']) ? (int) $validated['target_warehouse_id'] : null,
            pushService:      app(PushNotificationService::class)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Cancel a pickup assignment.
     */
    public function cancel(PickupAssignment $pickupAssignment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = request()->validate([
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $result = $this->pickupAssignmentService->cancel($pickupAssignment, $validated['cancellation_reason']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Mark pickup as received at warehouse.
     */
    public function receive(Request $request, PickupAssignment $pickupAssignment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = $request->validate([
            'received_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'receive_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $admin = Auth::guard('admin')->user();

        $result = $this->pickupAssignmentService->receiveAtWarehouse(
            assignment: $pickupAssignment,
            receivedByUserId: $admin?->id,
            receivedWarehouseId: isset($validated['received_warehouse_id']) ? (int) $validated['received_warehouse_id'] : null,
            receiveNotes: $validated['receive_notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Check if current admin has permission.
     */
    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
