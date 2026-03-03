<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PickupAssignmentService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PickupAssignmentController extends Controller
{
    public function __construct(
        private PickupAssignmentService $pickupAssignmentService
    ) {}

    /**
     * Get available drivers, optionally filtered by vehicle type.
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
     * Assign a driver to a shipment.
     */
    public function assign(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'target_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);

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
     * Update the driver and/or target warehouse on an existing ASSIGNED pickup.
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
