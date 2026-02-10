<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Shipment;
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
     * Assign a driver to a shipment.
     */
    public function assign(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);
        $admin = Auth::guard('admin')->user();

        $result = $this->pickupAssignmentService->assign($shipment, $driver, $admin);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Cancel a pickup assignment.
     */
    public function cancel(PickupAssignment $pickupAssignment)
    {
        $this->authorizePermission('shipments.assign_driver');

        $reason = request('cancellation_reason');

        $result = $this->pickupAssignmentService->cancel($pickupAssignment, $reason);

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
