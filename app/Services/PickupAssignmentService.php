<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\PickupPhoto;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PickupAssignmentService
{
    public function __construct(
        private StorageService $storageService
    ) {}

    public function assign(
        Shipment $shipment,
        Driver $driver,
        ?User $admin = null,
        ?string $notes = null,
        ?int $targetWarehouseId = null
    ): array
    {
        if (!$shipment->canBeAssigned()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be assigned in its current status.',
            ];
        }

        if (!$driver->is_active) {
            return [
                'success' => false,
                'message' => 'Driver is inactive.',
            ];
        }

        if ($driver->status === 'busy') {
            return [
                'success' => false,
                'message' => 'Driver is currently busy.',
            ];
        }

        if (!$driver->hasCapability(Driver::CAPABILITY_PICKUP)) {
            return [
                'success' => false,
                'message' => 'Driver is not configured for pickup assignments.',
            ];
        }

        $assignment = PickupAssignment::create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'target_warehouse_id' => $targetWarehouseId,
            'status' => PickupAssignmentStatus::ASSIGNED,
            'assigned_by' => $admin?->id,
            'assigned_at' => now(),
            'notes' => $notes,
        ]);

        $shipment->update(['status' => ShipmentStatus::PICKUP_ASSIGNED]);
        $driver->update(['status' => 'busy']);

        event(new \App\Events\DriverAssignedToPickup($assignment, $driver));

        return [
            'success' => true,
            'message' => 'Driver assigned successfully.',
            'data' => ['assignment' => $assignment->load('driver', 'targetWarehouse')],
        ];
    }

    public function updateAssignment(
        PickupAssignment $assignment,
        ?int $newDriverId,
        ?int $newWarehouseId,
        PushNotificationService $pushService
    ): array
    {
        if ($assignment->status !== PickupAssignmentStatus::ASSIGNED) {
            return [
                'success' => false,
                'message' => 'Assignment can only be edited while in ASSIGNED status.',
            ];
        }

        $driverChanged    = $newDriverId !== null && (int) $newDriverId !== (int) $assignment->driver_id;
        $warehouseChanged = $newWarehouseId !== (int) $assignment->target_warehouse_id;

        if (!$driverChanged && !$warehouseChanged) {
            return [
                'success' => true,
                'message' => 'No changes were made.',
                'data'    => ['assignment' => $assignment->load(['driver', 'targetWarehouse', 'shipment'])],
            ];
        }

        // Capture old values before any mutation
        $assignment->loadMissing(['driver', 'targetWarehouse', 'shipment']);
        $oldDriver    = $assignment->driver;
        $oldWarehouse = $assignment->targetWarehouse;

        // --- Apply driver change ---
        if ($driverChanged) {
            $newDriver = Driver::find($newDriverId);

            if (!$newDriver) {
                return ['success' => false, 'message' => 'Driver not found.'];
            }
            if (!$newDriver->is_active) {
                return ['success' => false, 'message' => 'Selected driver is inactive.'];
            }
            if ($newDriver->status === 'busy') {
                return ['success' => false, 'message' => 'Selected driver is currently busy.'];
            }
            if (!$newDriver->hasCapability(Driver::CAPABILITY_PICKUP)) {
                return ['success' => false, 'message' => 'Selected driver is not configured for pickup assignments.'];
            }

            if ($oldDriver) {
                $oldDriver->update(['status' => 'available']);
            }
            $newDriver->update(['status' => 'busy']);
            $assignment->driver_id = $newDriver->id;
        }

        // --- Apply warehouse change ---
        $newWarehouse = null;
        if ($warehouseChanged) {
            if ($newWarehouseId) {
                $newWarehouse = Warehouse::find($newWarehouseId);
                if (!$newWarehouse) {
                    return ['success' => false, 'message' => 'Warehouse not found.'];
                }
            }
            $assignment->target_warehouse_id = $newWarehouseId ?: null;
        }

        $assignment->save();
        $assignment->load(['driver', 'targetWarehouse', 'shipment']);

        $shipmentNumber  = $assignment->shipment?->shipment_number ?? 'N/A';
        $currentDriver   = $assignment->driver;
        $currentWarehouse = $assignment->targetWarehouse;

        // --- Send smart notifications ---

        if ($driverChanged && !$warehouseChanged) {
            // Notify old driver: unassigned
            if ($oldDriver) {
                $pushService->sendToDriver(
                    driver: $oldDriver,
                    title: 'Pickup Assignment Removed',
                    body: "You have been unassigned from pickup for shipment {$shipmentNumber}.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'driver_unassigned'
                );
            }
            // Notify new driver: assigned
            if ($currentDriver) {
                $warehouseName = $currentWarehouse?->name;
                $body = $warehouseName
                    ? "Pick up shipment {$shipmentNumber} and deliver to {$warehouseName}."
                    : "You have been assigned to pick up shipment {$shipmentNumber}.";
                $pushService->sendToDriver(
                    driver: $currentDriver,
                    title: 'New Pickup Assignment',
                    body: $body,
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'driver_assigned'
                );
            }
            // Notify warehouse managers: driver changed
            if ($currentWarehouse) {
                $newDriverName = $currentDriver?->name ?? 'A driver';
                $pushService->sendToWarehouseManagers(
                    warehouse: $currentWarehouse,
                    title: 'Pickup Driver Changed',
                    body: "Driver changed — {$newDriverName} will now handle pickup for shipment {$shipmentNumber}.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'pickup_driver_changed'
                );
            }
        } elseif ($warehouseChanged && !$driverChanged) {
            // Notify driver: destination changed
            if ($currentDriver) {
                $newWarehouseName = $currentWarehouse?->name ?? 'a new warehouse';
                $pushService->sendToDriver(
                    driver: $currentDriver,
                    title: 'Drop-off Destination Changed',
                    body: "Your drop-off destination for shipment {$shipmentNumber} has been changed to {$newWarehouseName}.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'pickup_warehouse_changed'
                );
            }
            // Notify old warehouse managers: redirected
            if ($oldWarehouse && $oldWarehouse->id !== $assignment->target_warehouse_id) {
                $newWarehouseName = $currentWarehouse?->name ?? 'another warehouse';
                $pushService->sendToWarehouseManagers(
                    warehouse: $oldWarehouse,
                    title: 'Pickup Redirected',
                    body: "Pickup for shipment {$shipmentNumber} has been redirected to {$newWarehouseName}.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'pickup_redirected'
                );
            }
            // Notify new warehouse managers: incoming
            if ($currentWarehouse) {
                $driverName = $currentDriver?->name ?? 'A driver';
                $pushService->sendToWarehouseManagers(
                    warehouse: $currentWarehouse,
                    title: 'Incoming Pickup',
                    body: "Incoming pickup: {$driverName} will bring shipment {$shipmentNumber} to your warehouse.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'pickup_incoming'
                );
            }
        } else {
            // Both changed
            // Notify old driver: unassigned
            if ($oldDriver) {
                $pushService->sendToDriver(
                    driver: $oldDriver,
                    title: 'Pickup Assignment Removed',
                    body: "You have been unassigned from pickup for shipment {$shipmentNumber}.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'driver_unassigned'
                );
            }
            // Notify new driver: assigned to new warehouse
            if ($currentDriver) {
                $newWarehouseName = $currentWarehouse?->name;
                $body = $newWarehouseName
                    ? "Pick up shipment {$shipmentNumber} and deliver to {$newWarehouseName}."
                    : "You have been assigned to pick up shipment {$shipmentNumber}.";
                $pushService->sendToDriver(
                    driver: $currentDriver,
                    title: 'New Pickup Assignment',
                    body: $body,
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'driver_assigned'
                );
            }
            // Notify old warehouse managers: redirected
            if ($oldWarehouse && $oldWarehouse->id !== $assignment->target_warehouse_id) {
                $newWarehouseName = $currentWarehouse?->name ?? 'another warehouse';
                $pushService->sendToWarehouseManagers(
                    warehouse: $oldWarehouse,
                    title: 'Pickup Redirected',
                    body: "Pickup for shipment {$shipmentNumber} has been redirected to {$newWarehouseName}.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'pickup_redirected'
                );
            }
            // Notify new warehouse managers: incoming with new driver
            if ($currentWarehouse) {
                $newDriverName = $currentDriver?->name ?? 'A driver';
                $pushService->sendToWarehouseManagers(
                    warehouse: $currentWarehouse,
                    title: 'Incoming Pickup',
                    body: "Incoming pickup: {$newDriverName} will bring shipment {$shipmentNumber} to your warehouse.",
                    data: ['assignment_id' => (string) $assignment->id, 'shipment_number' => $shipmentNumber],
                    type: 'pickup_incoming'
                );
            }
        }

        return [
            'success' => true,
            'message' => 'Assignment updated successfully.',
            'data'    => ['assignment' => $assignment],
        ];
    }

    public function getAvailableDrivers(?string $vehicleType = null, ?string $assignmentType = null): array
    {
        $assignmentType = is_string($assignmentType) ? strtolower(trim($assignmentType)) : null;
        if (!in_array($assignmentType, Driver::CAPABILITIES, true)) {
            $assignmentType = Driver::CAPABILITY_PICKUP;
        }

        $query = Driver::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereIn('status', ['available', 'offline']);
            })
            ->where(function ($q) use ($assignmentType) {
                // Backward compatibility: null capabilities are treated as pickup-capable.
                if ($assignmentType === Driver::CAPABILITY_PICKUP) {
                    $q->whereNull('task_capabilities')
                        ->orWhereJsonContains('task_capabilities', $assignmentType);
                } else {
                    $q->whereJsonContains('task_capabilities', $assignmentType);
                }
            });

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

    public function confirmItem(
        PickupAssignment $assignment,
        ShipmentItem $item,
        int $confirmedQuantity,
        array $photos = [],
        ?string $notes = null,
        array $removePhotoIds = []
    ): array
    {
        if (!in_array($assignment->status, [PickupAssignmentStatus::ARRIVED, PickupAssignmentStatus::PICKING_UP])) {
            return [
                'success' => false,
                'message' => 'Driver must have arrived to confirm an item.',
            ];
        }

        if ($item->shipment_id !== $assignment->shipment_id) {
            return [
                'success' => false,
                'message' => 'This item does not belong to the selected pickup.',
            ];
        }

        if ($confirmedQuantity < 0) {
            return [
                'success' => false,
                'message' => 'Confirmed quantity must be zero or greater.',
            ];
        }

        foreach ($photos as $photo) {
            if (!($photo instanceof UploadedFile)) {
                return [
                    'success' => false,
                    'message' => 'Invalid item photo upload.',
                ];
            }
        }

        return DB::transaction(function () use ($assignment, $item, $confirmedQuantity, $photos, $notes, $removePhotoIds) {
            $existingConfirmation = PickupItemConfirmation::query()
                ->where('pickup_assignment_id', $assignment->id)
                ->where('shipment_item_id', $item->id)
                ->first();

            $existingPhotoCount = PickupPhoto::query()
                ->where('pickup_assignment_id', $assignment->id)
                ->where('shipment_item_id', $item->id)
                ->count();

            $normalizedRemoveIds = collect($removePhotoIds)
                ->map(fn($value) => (int) $value)
                ->filter(fn($value) => $value > 0)
                ->unique()
                ->values()
                ->all();

            $removablePhotoIds = empty($normalizedRemoveIds)
                ? []
                : PickupPhoto::query()
                    ->where('pickup_assignment_id', $assignment->id)
                    ->where('shipment_item_id', $item->id)
                    ->whereIn('id', $normalizedRemoveIds)
                    ->pluck('id')
                    ->all();

            $projectedPhotoCount = $existingPhotoCount - count($removablePhotoIds) + count($photos);
            if ($projectedPhotoCount < 1) {
                return [
                    'success' => false,
                    'message' => 'At least one item photo is required.',
                ];
            }

            $confirmedAt = now();

            // Transition to PICKING_UP once the first item is confirmed
            if ($assignment->status === PickupAssignmentStatus::ARRIVED) {
                $assignment->update(['status' => PickupAssignmentStatus::PICKING_UP]);
            }

            PickupItemConfirmation::query()->updateOrCreate([
                'pickup_assignment_id' => $assignment->id,
                'shipment_item_id' => $item->id,
            ], [
                'expected_quantity' => (int) $item->quantity,
                'confirmed_quantity' => $confirmedQuantity,
                'notes' => $notes,
                'confirmed_at' => $confirmedAt,
            ]);

            if (!empty($removablePhotoIds)) {
                PickupPhoto::query()
                    ->where('pickup_assignment_id', $assignment->id)
                    ->where('shipment_item_id', $item->id)
                    ->whereIn('id', $removablePhotoIds)
                    ->get()
                    ->each
                    ->delete();
            }

            foreach ($photos as $photo) {
                if (!$photo instanceof UploadedFile) {
                    continue;
                }

                $uploadResult = $this->storageService->upload(
                    $photo,
                    "pickups/{$assignment->id}/items/{$item->id}"
                );

                PickupPhoto::create([
                    'pickup_assignment_id' => $assignment->id,
                    'shipment_item_id' => $item->id,
                    'path' => $uploadResult['path'],
                    'original_name' => $uploadResult['original_name'],
                    'size' => $uploadResult['size'],
                    'type' => 'item',
                ]);
            }

            return [
                'success' => true,
                'message' => $existingConfirmation
                    ? 'Pickup item updated successfully.'
                    : 'Pickup item confirmed successfully.',
                'data' => ['assignment' => $assignment->fresh()],
            ];
        });
    }

    public function finalizePickup(
        PickupAssignment $assignment,
        ?float $lat = null,
        ?float $lng = null,
        ?string $notes = null
    ): array
    {
        if (!in_array($assignment->status, [PickupAssignmentStatus::ARRIVED, PickupAssignmentStatus::PICKING_UP], true)) {
            return [
                'success' => false,
                'message' => 'Driver must have arrived to finalize pickup.',
            ];
        }

        $shipmentItems = $assignment->shipment->items()->get()->keyBy('id');
        if ($shipmentItems->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No shipment items were found for this pickup.',
            ];
        }

        $confirmations = PickupItemConfirmation::query()
            ->where('pickup_assignment_id', $assignment->id)
            ->get()
            ->keyBy('shipment_item_id');

        foreach ($shipmentItems as $shipmentItemId => $item) {
            if (!$confirmations->has($shipmentItemId)) {
                return [
                    'success' => false,
                    'message' => 'All shipment items must be confirmed before finalizing pickup.',
                ];
            }

            $photoCount = PickupPhoto::query()
                ->where('pickup_assignment_id', $assignment->id)
                ->where('shipment_item_id', $shipmentItemId)
                ->count();

            if ($photoCount < 1) {
                return [
                    'success' => false,
                    'message' => 'Each confirmed item must include at least one photo before finalizing pickup.',
                ];
            }
        }

        return DB::transaction(function () use ($assignment, $shipmentItems, $confirmations, $lat, $lng, $notes) {
            $pickedUpAt = now();

            $payload = [
                'status' => PickupAssignmentStatus::COMPLETED,
                'picked_up_at' => $pickedUpAt,
                'completed_at' => $pickedUpAt,
            ];

            if (!is_null($notes)) {
                $payload['notes'] = $notes;
            }
            if (!is_null($lat)) {
                $payload['pickup_latitude'] = $lat;
            }
            if (!is_null($lng)) {
                $payload['pickup_longitude'] = $lng;
            }

            $assignment->update($payload);

            $pickupLocation = $assignment->shipment->pickup_town
                ?: $assignment->shipment->pickup_gh_post_address
                ?: (!is_null($assignment->pickup_latitude) && !is_null($assignment->pickup_longitude)
                    ? "{$assignment->pickup_latitude}, {$assignment->pickup_longitude}"
                    : null);

            $assignment->shipment->items()->each(function ($item) use (
                $pickedUpAt,
                $pickupLocation,
                $assignment,
                $confirmations
            ) {
                $item->update(['status' => ItemStatus::PICKED_UP]);

                $confirmation = $confirmations->get($item->id);
                $confirmed = (int) ($confirmation?->confirmed_quantity ?? 0);
                $expected = (int) $item->quantity;
                $baseNote = "Driver confirmed pickup quantity {$confirmed}/{$expected}.";
                $extraNote = $confirmation?->notes;

                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::PICKED_UP->value,
                    'location' => $pickupLocation,
                    'notes' => trim($baseNote . ' ' . ($extraNote ?? '')),
                    'created_by' => "driver:{$assignment->driver_id}",
                    'created_at' => $pickedUpAt,
                ]);
            });

            $assignment->shipment->update(['status' => ShipmentStatus::PICKED_UP]);

            return [
                'success' => true,
                'message' => 'Pickup finalized successfully.',
                'data' => ['assignment' => $assignment->fresh()],
            ];
        });
    }

    public function receiveAtWarehouse(
        PickupAssignment $assignment,
        int|string|null $receivedByUserId = null,
        ?int $receivedWarehouseId = null,
        ?string $receiveNotes = null,
        array $trackingMetaByItem = []
    ): array
    {
        if (in_array($assignment->status, [PickupAssignmentStatus::CANCELLED], true)) {
            return [
                'success' => false,
                'message' => 'Cancelled assignment cannot be received at warehouse.',
            ];
        }

        if (is_null($assignment->picked_up_at)) {
            return [
                'success' => false,
                'message' => 'Items must be picked up before warehouse receiving.',
            ];
        }

        if (!is_null($assignment->received_at)) {
            return [
                'success' => false,
                'message' => 'This pickup has already been received at warehouse.',
            ];
        }

        $warehouseId = $receivedWarehouseId ?? $assignment->target_warehouse_id;
        if (empty($warehouseId)) {
            return [
                'success' => false,
                'message' => 'Receiving warehouse is required.',
            ];
        }

        return DB::transaction(function () use ($assignment, $receivedByUserId, $warehouseId, $receiveNotes, $trackingMetaByItem) {
            $lockedAssignment = PickupAssignment::query()
                ->with(['shipment.items', 'driver'])
                ->lockForUpdate()
                ->find($assignment->id);

            if (!$lockedAssignment) {
                return [
                    'success' => false,
                    'message' => 'Pickup assignment not found.',
                ];
            }

            if (!is_null($lockedAssignment->received_at)) {
                return [
                    'success' => false,
                    'message' => 'This pickup has already been received at warehouse.',
                ];
            }

            $now = now();

            $lockedAssignment->update([
                'arrived_warehouse_at' => $lockedAssignment->arrived_warehouse_at ?? $now,
                'received_warehouse_id' => $warehouseId,
                'received_by_user_id' => $receivedByUserId,
                'received_at' => $now,
                'receive_notes' => $receiveNotes,
            ]);

            $lockedAssignment->shipment->update(['status' => ShipmentStatus::AT_WAREHOUSE]);

            $locationLabel = optional($lockedAssignment->receivedWarehouse)->name
                ?? optional($lockedAssignment->targetWarehouse)->name;

            $lockedAssignment->shipment->items->each(function ($item) use ($now, $locationLabel, $receivedByUserId, $trackingMetaByItem) {
                $item->update(['status' => ItemStatus::AT_WAREHOUSE]);

                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::AT_WAREHOUSE->value,
                    'location' => $locationLabel,
                    'notes' => 'Item received at warehouse.',
                    'meta' => $trackingMetaByItem[$item->id] ?? null,
                    'created_by' => $receivedByUserId ? "user:{$receivedByUserId}" : null,
                    'created_at' => $now,
                ]);
            });

            if ($lockedAssignment->driver) {
                $lockedAssignment->driver->update(['status' => 'available']);
            }

            return [
                'success' => true,
                'message' => 'Pickup received at warehouse successfully.',
                'data' => [
                    'assignment' => $lockedAssignment->fresh([
                        'driver',
                        'targetWarehouse',
                        'receivedWarehouse',
                    ]),
                ],
            ];
        });
    }

    public function cancel(PickupAssignment $assignment, ?string $reason = null): array
    {
        if (blank($reason)) {
            return [
                'success' => false,
                'message' => 'Cancellation reason is required.',
            ];
        }

        if (!is_null($assignment->picked_up_at) || !is_null($assignment->completed_at)) {
            return [
                'success' => false,
                'message' => 'You cannot unassign after items have been picked up.',
            ];
        }

        if (in_array($assignment->status, [PickupAssignmentStatus::COMPLETED, PickupAssignmentStatus::CANCELLED], true)) {
            return [
                'success' => false,
                'message' => 'This assignment cannot be cancelled.',
            ];
        }

        $driver = $assignment->driver;

        $assignment->update([
            'status' => PickupAssignmentStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Phase 3: If shipment had an accepted invoice, restore to INVOICE_ACCEPTED.
        // Otherwise (new flow: SUBMITTED → PICKUP_ASSIGNED), restore to SUBMITTED.
        $shipment = $assignment->shipment;
        $hasAcceptedInvoice = $shipment->invoices()
            ->where('status', \App\Enums\InvoiceStatus::ACCEPTED->value)
            ->exists();
        $revertStatus = $hasAcceptedInvoice
            ? ShipmentStatus::INVOICE_ACCEPTED
            : ShipmentStatus::SUBMITTED;

        $shipment->update(['status' => $revertStatus]);
        $assignment->driver->update(['status' => 'available']);

        // Fire unassignment event so driver receives a push notification
        if ($driver) {
            event(new \App\Events\DriverUnassignedFromPickup($assignment, $driver));
        }

        return [
            'success' => true,
            'message' => 'Assignment cancelled.',
        ];
    }
}
