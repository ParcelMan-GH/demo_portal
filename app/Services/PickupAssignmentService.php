<?php

namespace App\Services;

use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\PickupPhoto;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class PickupAssignmentService
{
    public function __construct(
        private StorageService $storageService
    ) {}

    public function assign(Shipment $shipment, Driver $driver, ?User $admin = null): array
    {
        if (!$shipment->canBeAssigned()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be assigned in its current status.',
            ];
        }

        if ($driver->status !== 'available') {
            return [
                'success' => false,
                'message' => 'Driver is not available.',
            ];
        }

        $assignment = PickupAssignment::create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'status' => PickupAssignmentStatus::ASSIGNED,
            'assigned_by' => $admin?->id,
            'assigned_at' => now(),
        ]);

        $shipment->update(['status' => ShipmentStatus::PICKUP_ASSIGNED]);
        $driver->update(['status' => 'busy']);

        return [
            'success' => true,
            'message' => 'Driver assigned successfully.',
            'data' => ['assignment' => $assignment->load('driver')],
        ];
    }

    public function getAvailableDrivers(?string $vehicleType = null): array
    {
        $query = Driver::where('is_active', true)
            ->where('status', 'available');

        if ($vehicleType) {
            $query->where('vehicle_type', $vehicleType);
        }

        return $query->orderBy('name')->get()->toArray();
    }

    public function startEnRoute(PickupAssignment $assignment): array
    {
        if ($assignment->status !== PickupAssignmentStatus::ASSIGNED) {
            return [
                'success' => false,
                'message' => 'Assignment must be in assigned status.',
            ];
        }

        $assignment->update([
            'status' => PickupAssignmentStatus::EN_ROUTE,
            'en_route_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Driver is now en route.',
            'data' => ['assignment' => $assignment->fresh()],
        ];
    }

    public function arrive(PickupAssignment $assignment, ?float $lat = null, ?float $lng = null): array
    {
        if ($assignment->status !== PickupAssignmentStatus::EN_ROUTE) {
            return [
                'success' => false,
                'message' => 'Driver must be en route to arrive.',
            ];
        }

        $assignment->update([
            'status' => PickupAssignmentStatus::ARRIVED,
            'arrived_at' => now(),
            'pickup_latitude' => $lat,
            'pickup_longitude' => $lng,
        ]);

        return [
            'success' => true,
            'message' => 'Driver has arrived.',
            'data' => ['assignment' => $assignment->fresh()],
        ];
    }

    public function confirmPickup(PickupAssignment $assignment, array $photos = [], ?float $lat = null, ?float $lng = null, ?string $notes = null): array
    {
        if (!in_array($assignment->status, [PickupAssignmentStatus::ARRIVED, PickupAssignmentStatus::PICKING_UP])) {
            return [
                'success' => false,
                'message' => 'Driver must have arrived to confirm pickup.',
            ];
        }

        // Store photos
        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $path = $this->storageService->store(
                    $photo,
                    "pickups/{$assignment->id}"
                );

                PickupPhoto::create([
                    'pickup_assignment_id' => $assignment->id,
                    'path' => $path,
                    'original_name' => $photo->getClientOriginalName(),
                    'size' => $photo->getSize(),
                    'type' => 'item',
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);
            }
        }

        $assignment->update([
            'status' => PickupAssignmentStatus::COMPLETED,
            'picked_up_at' => now(),
            'completed_at' => now(),
            'notes' => $notes,
        ]);

        $assignment->shipment->update(['status' => ShipmentStatus::PICKED_UP]);
        $assignment->driver->update(['status' => 'available']);

        return [
            'success' => true,
            'message' => 'Pickup confirmed.',
            'data' => ['assignment' => $assignment->fresh()->load('photos')],
        ];
    }

    public function cancel(PickupAssignment $assignment, ?string $reason = null): array
    {
        if (in_array($assignment->status, [PickupAssignmentStatus::COMPLETED, PickupAssignmentStatus::CANCELLED])) {
            return [
                'success' => false,
                'message' => 'This assignment cannot be cancelled.',
            ];
        }

        $assignment->update([
            'status' => PickupAssignmentStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $assignment->shipment->update(['status' => ShipmentStatus::INVOICE_ACCEPTED]);
        $assignment->driver->update(['status' => 'available']);

        return [
            'success' => true,
            'message' => 'Assignment cancelled.',
        ];
    }
}
