<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PickupAssignmentStatus;
use App\Helpers\CodeResolver;
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

        $query = PickupAssignment::with([
            'shipment.vendor',
            // Coverage is per shipment, and one shipment can now carry several
            // assignment rows, so both the requested vehicles and the sibling
            // assignments come along to keep the derived numbers off an N+1.
            'shipment.pickupVehicleRequests.vehicleType',
            'shipment.pickupAssignments',
            'driver',
            'targetWarehouse',
            'assignedBy',
            'pickupVehicleType',
        ]);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('shipment', fn ($sq) => $sq->where('shipment_number', 'like', CodeResolver::likeTerm($search)))
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

        // Derived coverage is computed once per shipment and reused for every
        // row of that shipment, so a shipment with several assignments does not
        // recompute (or re-query) the same numbers.
        $coverageByShipment = [];

        return response()->json([
            'data' => $assignments->map(function (PickupAssignment $a) use (&$coverageByShipment) {
                $coverage = [];
                if ($a->shipment) {
                    if (! isset($coverageByShipment[$a->shipment->id])) {
                        $status = $a->shipment->pickupCoverageStatus();
                        $coverageByShipment[$a->shipment->id] = [
                            'coverage_status' => $status->value,
                            'coverage_label' => $status->label(),
                            'required_slots' => $a->shipment->pickupRequiredSlotCount(),
                            'assigned_slots' => $a->shipment->pickupAssignedSlotCount(),
                            'slot_breakdown' => $a->shipment->pickupSlotBreakdown(),
                        ];
                    }
                    $coverage = $coverageByShipment[$a->shipment->id];
                }

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
                    // Slot position of this row, plus the shipment's derived
                    // coverage (see $coverageByShipment above).
                    'pickup_vehicle_type_id' => $a->pickup_vehicle_type_id,
                    'vehicle_type_name' => $a->pickupVehicleType?->name,
                    'slot_label' => $a->slot_number
                        ? 'Slot '.$a->slot_number.' - '.($a->pickupVehicleType?->name ?? 'Vehicle')
                        : null,
                    ...$coverage,
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
            /*
             * The pickup vehicle types the parcel asked for. Sending the ids lets
             * the list be narrowed to riders who can actually serve that pickups,
             * instead of offering a car driver for a motorbike run. Ids rather than
             * slugs because the translation to a driver's own vehicle type lives on
             * the service, not in the browser.
             */
            'pickup_vehicle_type_ids' => ['nullable', 'array'],
            'pickup_vehicle_type_ids.*' => ['integer', 'exists:pickup_vehicle_types,id'],
        ]);

        $vehicleTypes = null;

        if (! empty($validated['vehicle_type'])) {
            $vehicleTypes = [$validated['vehicle_type']];
        } elseif (! empty($validated['pickup_vehicle_type_ids'])) {
            $vehicleTypes = $this->pickupAssignmentService
                ->driverVehicleTypesForPickupTypes($validated['pickup_vehicle_type_ids']);

            // A requested type with no driver equivalent must not filter the list
            // down to nobody; fall back to the unfiltered list instead.
            if ($vehicleTypes === []) {
                $vehicleTypes = null;
            }
        }

        $drivers = $this->pickupAssignmentService->getAvailableDrivers(
            $vehicleTypes,
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
            /*
             * `driver_id` on its own is what the edit screen and older callers
             * post. The shipment page's picker is multi-select and also posts
             * `driver_ids[]` — and this endpoint used to read only `driver_id`,
             * so choosing two riders assigned one and reported success, leaving a
             * shipment that asked for two riders never covered.
             */
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id', 'required_without:driver_ids'],
            'driver_ids' => ['nullable', 'array', 'required_without:driver_id'],
            'driver_ids.*' => ['integer', 'exists:drivers,id'],
            'target_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'confirm_busy_assignment' => ['sometimes', 'boolean'],
            // Optional: name the requested vehicle type to claim a slot for it.
            // Omitted, the riders fill the shipment's open slots in request order.
            'pickup_vehicle_type_id' => ['nullable', 'integer', 'exists:pickup_vehicle_types,id'],
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

        // Every selected rider, de-duplicated. `driver_id` is folded in so a
        // caller posting both does not have to worry about which one wins.
        $driverIds = collect($validated['driver_ids'] ?? [])
            ->when(
                filled($validated['driver_id'] ?? null),
                fn ($ids) => $ids->push($validated['driver_id']),
            )
            ->all();

        $admin = Auth::guard('admin')->user();

        $result = $this->pickupAssignmentService->assignMany(
            shipment: $shipment,
            driverIds: $driverIds,
            admin: $admin,
            notes: $validated['notes'] ?? null,
            targetWarehouseId: (int) $validated['target_warehouse_id'],
            confirmBusyAssignment: (bool) ($validated['confirm_busy_assignment'] ?? false),
            pickupVehicleTypeId: isset($validated['pickup_vehicle_type_id']) ? (int) $validated['pickup_vehicle_type_id'] : null,
        );

        return response()->json($result, $this->assignmentResponseStatus($result));
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
            'confirm_busy_assignment' => ['sometimes', 'boolean'],
            'reassignment_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->pickupAssignmentService->updateAssignment(
            assignment:       $pickupAssignment,
            newDriverId:      isset($validated['driver_id']) ? (int) $validated['driver_id'] : null,
            newWarehouseId:   isset($validated['target_warehouse_id']) ? (int) $validated['target_warehouse_id'] : null,
            pushService:      app(PushNotificationService::class),
            actor:            Auth::guard('admin')->user(),
            confirmBusyAssignment: (bool) ($validated['confirm_busy_assignment'] ?? false),
            reassignmentReason: $validated['reassignment_reason'] ?? null,
        );

        return response()->json($result, $this->assignmentResponseStatus($result));
    }

    private function assignmentResponseStatus(array $result): int
    {
        if ($result['success'] ?? false) return 200;

        return ($result['code'] ?? null) === 'rider_busy' ? 409 : 422;
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

        $result = $this->pickupAssignmentService->cancel(
            $pickupAssignment,
            $validated['cancellation_reason'],
            Auth::guard('admin')->user(),
        );

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
