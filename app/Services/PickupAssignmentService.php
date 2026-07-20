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
        private StorageService $storageService,
        private DriverWorkloadService $workloads,
        private RiderAssignmentAuditService $assignmentAudit,
    ) {}

    public function assign(
        Shipment $shipment,
        Driver $driver,
        ?User $admin = null,
        ?string $notes = null,
        ?int $targetWarehouseId = null,
        bool $confirmBusyAssignment = false,
    ): array {
        if (! $shipment->canBeAssigned()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be assigned in its current status.',
            ];
        }

        if (! $driver->is_active) {
            return [
                'success' => false,
                'message' => 'Rider is inactive.',
            ];
        }

        if (! $driver->hasCapability(Driver::CAPABILITY_PICKUP)) {
            return [
                'success' => false,
                'message' => 'Rider is not configured for pickup assignments.',
            ];
        }

        return DB::transaction(function () use ($shipment, $driver, $admin, $notes, $targetWarehouseId, $confirmBusyAssignment) {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $lockedDriver = Driver::query()->lockForUpdate()->findOrFail($driver->id);

            if (! $lockedShipment->canBeAssigned() || $lockedShipment->pickupAssignment()->exists()) {
                return ['success' => false, 'message' => 'Shipment cannot be assigned in its current status.'];
            }

            if (! $lockedDriver->is_active) {
                return ['success' => false, 'message' => 'Rider is inactive.'];
            }

            if (! $lockedDriver->hasCapability(Driver::CAPABILITY_PICKUP)) {
                return ['success' => false, 'message' => 'Rider is not configured for pickup assignments.'];
            }

            $busy = $this->busyConflict($lockedDriver, $confirmBusyAssignment);
            if ($busy) {
                return $busy;
            }

            $assignment = PickupAssignment::query()->create([
                'shipment_id' => $lockedShipment->id,
                'driver_id' => $lockedDriver->id,
                'target_warehouse_id' => $targetWarehouseId,
                'status' => PickupAssignmentStatus::ASSIGNED,
                'assigned_by' => $admin?->id,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            $lockedShipment->update(['status' => ShipmentStatus::PICKUP_ASSIGNED]);
            $this->assignmentAudit->record('pickup', $assignment->id, 'assigned', null, $lockedDriver->id, $admin);
            $this->workloads->syncStatus($lockedDriver);

            event(new \App\Events\DriverAssignedToPickup($assignment, $lockedDriver));

            return [
                'success' => true,
                'message' => 'Rider assigned successfully.',
                'data' => ['assignment' => $assignment->load('driver', 'targetWarehouse')],
            ];
        });
    }

    public function updateAssignment(
        PickupAssignment $assignment,
        ?int $newDriverId,
        ?int $newWarehouseId,
        PushNotificationService $pushService,
        ?User $actor = null,
        bool $confirmBusyAssignment = false,
        ?string $reassignmentReason = null,
    ): array {
        return DB::transaction(function () use ($assignment, $newDriverId, $newWarehouseId, $pushService, $actor, $confirmBusyAssignment, $reassignmentReason) {
            $assignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($assignment->picked_up_at || $assignment->completed_at
                || in_array($assignment->status, [PickupAssignmentStatus::COMPLETED, PickupAssignmentStatus::CANCELLED], true)) {
                return ['success' => false, 'message' => 'Assignment can only be edited before pickup is confirmed.'];
            }

            $driverChanged = $newDriverId !== null && (int) $newDriverId !== (int) $assignment->driver_id;
            $warehouseChanged = $newWarehouseId !== null
                && (int) $newWarehouseId !== (int) $assignment->target_warehouse_id;
            if (! $driverChanged && ! $warehouseChanged) {
                return ['success' => true, 'message' => 'No changes were made.', 'data' => ['assignment' => $assignment->load(['driver', 'targetWarehouse', 'shipment'])]];
            }

            $assignment->loadMissing(['targetWarehouse', 'shipment']);
            $oldDriver = $assignment->driver;
            $oldWarehouse = $assignment->targetWarehouse;
            $newDriver = $oldDriver;

            if ($driverChanged) {
                $lockedDrivers = Driver::query()
                    ->whereIn('id', collect([$assignment->driver_id, $newDriverId])->filter()->unique())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                $oldDriver = $lockedDrivers->get((int) $assignment->driver_id) ?? $oldDriver;
                $newDriver = $lockedDrivers->get((int) $newDriverId);
                if (! $newDriver) {
                    return ['success' => false, 'message' => 'Rider not found.'];
                }
                if (! $newDriver->is_active) {
                    return ['success' => false, 'message' => 'Selected rider is inactive.'];
                }
                if (! $newDriver->hasCapability(Driver::CAPABILITY_PICKUP)) {
                    return ['success' => false, 'message' => 'Selected rider is not configured for pickup assignments.'];
                }
                if ($busy = $this->busyConflict($newDriver, $confirmBusyAssignment)) {
                    return $busy;
                }
                $assignment->driver_id = $newDriver->id;
            }

            if ($warehouseChanged) {
                if ($newWarehouseId && ! Warehouse::query()->whereKey($newWarehouseId)->exists()) {
                    return ['success' => false, 'message' => 'Warehouse not found.'];
                }
                $assignment->target_warehouse_id = $newWarehouseId ?: null;
            }

            $assignment->save();
            $assignment->load(['driver', 'targetWarehouse', 'shipment']);

            if ($driverChanged) {
                $this->assignmentAudit->record('pickup', $assignment->id, 'reassigned', $oldDriver?->id, $newDriver?->id, $actor, $reassignmentReason);
                if ($oldDriver) {
                    event(new \App\Events\DriverUnassignedFromPickup($assignment, $oldDriver, $reassignmentReason));
                }
                if ($newDriver) {
                    event(new \App\Events\DriverAssignedToPickup($assignment, $newDriver, $reassignmentReason, false));
                }
                $this->workloads->syncMany([$oldDriver?->id, $newDriver?->id]);
            }

            if ($warehouseChanged) {
                $assignmentId = $assignment->id;
                $shipmentNumber = $assignment->shipment?->shipment_number ?? 'N/A';
                $currentDriver = $assignment->driver;
                $currentWarehouse = $assignment->targetWarehouse;
                DB::afterCommit(function () use ($pushService, $assignmentId, $shipmentNumber, $currentDriver, $currentWarehouse, $oldWarehouse, $driverChanged) {
                    $data = ['pickup_id' => (string) $assignmentId, 'assignment_id' => (string) $assignmentId, 'shipment_number' => $shipmentNumber];
                    if ($currentDriver && ! $driverChanged) {
                        $name = $currentWarehouse?->name ?? 'a new warehouse';
                        $pushService->sendToDriver($currentDriver, 'Drop-off Destination Changed', "Your drop-off destination for parcel {$shipmentNumber} has been changed to {$name}.", $data, 'pickup_warehouse_changed');
                    }
                    if ($oldWarehouse && $oldWarehouse->id !== $currentWarehouse?->id) {
                        $pushService->sendToWarehouseManagers($oldWarehouse, 'Pickup Redirected', "Pickup for parcel {$shipmentNumber} has been redirected to ".($currentWarehouse?->name ?? 'another warehouse').'.', $data, 'pickup_redirected');
                    }
                    if ($currentWarehouse && ! $driverChanged) {
                        $driverName = $currentDriver?->name ?? 'A rider';
                        $pushService->sendToWarehouseManagers($currentWarehouse, 'Incoming Pickup', "{$driverName} will bring parcel {$shipmentNumber} to your warehouse.", $data, 'pickup_incoming');
                    }
                });
            }

            return ['success' => true, 'message' => 'Assignment updated successfully.', 'data' => ['assignment' => $assignment]];
        });
    }

    public function getAvailableDrivers(?string $vehicleType = null, ?string $assignmentType = null): array
    {
        $assignmentType = is_string($assignmentType) ? strtolower(trim($assignmentType)) : null;
        if (! in_array($assignmentType, Driver::CAPABILITIES, true)) {
            $assignmentType = Driver::CAPABILITY_PICKUP;
        }

        return $this->workloads->assignmentOptions($assignmentType, $vehicleType)->all();
    }

    private function busyConflict(Driver $driver, bool $confirmed): ?array
    {
        return $this->workloads->busyConflict($driver, $confirmed);
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
            'message' => 'Rider is now en route.',
            'data' => ['assignment' => $assignment->fresh()],
        ];
    }

    public function arrive(PickupAssignment $assignment, ?float $lat = null, ?float $lng = null): array
    {
        if ($assignment->status !== PickupAssignmentStatus::EN_ROUTE) {
            return [
                'success' => false,
                'message' => 'Rider must be en route to arrive.',
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
            'message' => 'Rider has arrived.',
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
    ): array {
        // Auto-advance through skipped statuses so driver can confirm directly
        $now = now();
        if ($assignment->status === PickupAssignmentStatus::ASSIGNED) {
            $assignment->update([
                'status' => PickupAssignmentStatus::EN_ROUTE,
                'en_route_at' => $now,
            ]);
        }
        if ($assignment->status === PickupAssignmentStatus::EN_ROUTE) {
            $assignment->update([
                'status' => PickupAssignmentStatus::ARRIVED,
                'arrived_at' => $now,
            ]);
        }
        if ($assignment->status === PickupAssignmentStatus::ARRIVED) {
            $assignment->update([
                'status' => PickupAssignmentStatus::PICKING_UP,
            ]);
        }

        if ($assignment->status !== PickupAssignmentStatus::PICKING_UP) {
            return [
                'success' => false,
                'message' => 'Cannot confirm items in current status.',
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
            if (! ($photo instanceof UploadedFile)) {
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
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
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

            if (! empty($removablePhotoIds)) {
                PickupPhoto::query()
                    ->where('pickup_assignment_id', $assignment->id)
                    ->where('shipment_item_id', $item->id)
                    ->whereIn('id', $removablePhotoIds)
                    ->get()
                    ->each
                    ->delete();
            }

            foreach ($photos as $photo) {
                if (! $photo instanceof UploadedFile) {
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
        int $driverPickedQuantity,
        ?float $lat = null,
        ?float $lng = null,
        ?string $notes = null
    ): array {
        if (! in_array($assignment->status, [PickupAssignmentStatus::ARRIVED, PickupAssignmentStatus::PICKING_UP], true)) {
            return [
                'success' => false,
                'message' => 'Rider must have arrived to finalize pickup.',
            ];
        }

        if ($driverPickedQuantity < 0) {
            return [
                'success' => false,
                'message' => 'Picked quantity must be zero or greater.',
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

        return DB::transaction(function () use ($assignment, $confirmations, $driverPickedQuantity, $lat, $lng, $notes) {
            $pickedUpAt = now();

            $payload = [
                'status' => PickupAssignmentStatus::COMPLETED,
                'driver_picked_quantity' => $driverPickedQuantity,
                'picked_up_at' => $pickedUpAt,
                'completed_at' => $pickedUpAt,
            ];

            if (! is_null($notes)) {
                $payload['notes'] = $notes;
            }
            if (! is_null($lat)) {
                $payload['pickup_latitude'] = $lat;
            }
            if (! is_null($lng)) {
                $payload['pickup_longitude'] = $lng;
            }

            $assignment->update($payload);

            $pickupLocation = $assignment->shipment->pickup_town
                ?: $assignment->shipment->pickup_gh_post_address
                ?: (! is_null($assignment->pickup_latitude) && ! is_null($assignment->pickup_longitude)
                    ? "{$assignment->pickup_latitude}, {$assignment->pickup_longitude}"
                    : null);

            $assignment->shipment->items()->each(function ($item) use (
                $pickedUpAt,
                $pickupLocation,
                $assignment,
                &$confirmations
            ) {
                $item->update(['status' => ItemStatus::PICKED_UP]);

                $confirmation = $confirmations->get($item->id);
                if (! $confirmation) {
                    $confirmation = PickupItemConfirmation::query()->create([
                        'pickup_assignment_id' => $assignment->id,
                        'shipment_item_id' => $item->id,
                        'expected_quantity' => (int) $item->quantity,
                        'confirmed_quantity' => (int) $item->quantity,
                        'notes' => 'Auto-confirmed when rider recorded shipment-level pickup quantity.',
                        'confirmed_at' => $pickedUpAt,
                    ]);
                    $confirmations->put($item->id, $confirmation);
                }
                $confirmed = (int) ($confirmation?->confirmed_quantity ?? 0);
                $expected = (int) $item->quantity;
                $baseNote = "Rider recorded shipment pickup total {$assignment->driver_picked_quantity}. Line reference {$confirmed}/{$expected}.";
                $extraNote = $confirmation?->notes;

                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::PICKED_UP->value,
                    'location' => $pickupLocation,
                    'notes' => trim($baseNote.' '.($extraNote ?? '')),
                    'created_by' => "driver:{$assignment->driver_id}",
                    'created_at' => $pickedUpAt,
                ]);
            });

            $assignment->shipment->update(['status' => ShipmentStatus::PICKED_UP]);
            $this->workloads->syncStatus($assignment->driver_id);

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
    ): array {
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

        if (! is_null($assignment->received_at)) {
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

            if (! $lockedAssignment) {
                return [
                    'success' => false,
                    'message' => 'Pickup assignment not found.',
                ];
            }

            if (! is_null($lockedAssignment->received_at)) {
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
                $this->workloads->syncStatus($lockedAssignment->driver);
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

    public function cancel(PickupAssignment $assignment, ?string $reason = null, ?User $actor = null): array
    {
        if (blank($reason)) {
            return [
                'success' => false,
                'message' => 'Cancellation reason is required.',
            ];
        }

        if (! is_null($assignment->picked_up_at) || ! is_null($assignment->completed_at)) {
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

        return DB::transaction(function () use ($assignment, $reason, $actor) {
            $assignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($assignment->picked_up_at || $assignment->completed_at
                || in_array($assignment->status, [PickupAssignmentStatus::COMPLETED, PickupAssignmentStatus::CANCELLED], true)) {
                return ['success' => false, 'message' => 'This assignment can no longer be cancelled.'];
            }

            $driver = $assignment->driver;
            $assignment->update([
                'status' => PickupAssignmentStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            $assignment->shipment->update(['status' => ShipmentStatus::SUBMITTED]);
            $this->assignmentAudit->record('pickup', $assignment->id, 'unassigned', $driver?->id, null, $actor, $reason);

            if ($driver) {
                event(new \App\Events\DriverUnassignedFromPickup($assignment, $driver, $reason));
                $this->workloads->syncStatus($driver);
            }

            return ['success' => true, 'message' => 'Assignment cancelled.'];
        });
    }
}
