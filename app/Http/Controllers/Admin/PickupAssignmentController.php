<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PickupAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $vehicleType = $request->get('vehicle_type');

        $drivers = $this->pickupAssignmentService->getAvailableDrivers($vehicleType);

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
            ->whereIn('type', ['origin', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

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
