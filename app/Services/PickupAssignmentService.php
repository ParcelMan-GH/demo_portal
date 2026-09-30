<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\PickupPhoto;
use App\Models\PickupVehicleType;
use App\Models\RiderAssignmentEvent;
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

    /**
     * Assign a rider to a shipment.
     *
     * $pickupVehicleTypeId is optional and stays optional on purpose: when it is
     * null this behaves exactly as it always has — one more rider, no slot — so
     * every existing caller is untouched. When it is given, the assignment
     * claims the next free slot of that requested vehicle type, and is refused
     * once that type's requested slots are all taken.
     *
     * $allowOverflow lifts that refusal, for the one case where going past the
     * requested count is intentional rather than a mistake: an admin deliberately
     * sending more riders than the parcel asked for. The per-slot picker on the
     * pickups page keeps the cap, because a slot is one vehicle.
     */
    public function assign(
        Shipment $shipment,
        Driver $driver,
        ?User $admin = null,
        ?string $notes = null,
        ?int $targetWarehouseId = null,
        bool $confirmBusyAssignment = false,
        ?int $pickupVehicleTypeId = null,
        bool $allowOverflow = false,
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

        return DB::transaction(function () use ($shipment, $driver, $admin, $notes, $targetWarehouseId, $confirmBusyAssignment, $pickupVehicleTypeId) {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $lockedDriver = Driver::query()->lockForUpdate()->findOrFail($driver->id);

            // Removed the strict exists() check to allow multiple rider assignments
            if (! $lockedShipment->canBeAssigned()) {
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

            // Claim a slot only when the caller named a vehicle type. The
            // requested quantity of that type is the ceiling, and the slot number
            // is one past the highest already used on the shipment (1-based).
            $slotNumber = null;
            if ($pickupVehicleTypeId !== null) {
                $requested = (int) $lockedShipment->pickupVehicleRequests()
                    ->where('pickup_vehicle_type_id', $pickupVehicleTypeId)
                    ->sum('quantity');

                if ($requested === 0) {
                    return ['success' => false, 'message' => 'That vehicle type was not requested for this shipment.'];
                }

                $alreadyAssigned = PickupAssignment::query()
                    ->where('shipment_id', $lockedShipment->id)
                    ->where('pickup_vehicle_type_id', $pickupVehicleTypeId)
                    ->where('status', '!=', PickupAssignmentStatus::CANCELLED)
                    ->count();

                if (! $allowOverflow && $alreadyAssigned >= $requested) {
                    return ['success' => false, 'message' => 'All requested slots for that vehicle type are already assigned.'];
                }

                $slotNumber = (int) PickupAssignment::query()
                    ->where('shipment_id', $lockedShipment->id)
                    ->max('slot_number') + 1;
            }

            $coverageBefore = $lockedShipment->pickupCoverageStatus();

            $assignment = PickupAssignment::query()->create([
                'shipment_id' => $lockedShipment->id,
                'driver_id' => $lockedDriver->id,
                'pickup_vehicle_type_id' => $pickupVehicleTypeId,
                'slot_number' => $slotNumber,
                'target_warehouse_id' => $targetWarehouseId,
                'status' => PickupAssignmentStatus::ASSIGNED,
                'assigned_by' => $admin?->id,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            $lockedShipment->update(['status' => ShipmentStatus::PICKUP_ASSIGNED]);
            $this->assignmentAudit->record('pickup', $assignment->id, 'assigned', null, $lockedDriver->id, $admin);

            // Coverage is derived, so recompute it from the assignments now that
            // this one exists, and record the move on the same audit trail.
            $lockedShipment->load('pickupAssignments');
            $coverageAfter = $lockedShipment->pickupCoverageStatus();
            if ($coverageBefore !== $coverageAfter) {
                $this->assignmentAudit->record(
                    'pickup',
                    $assignment->id,
                    RiderAssignmentEvent::EVENT_COVERAGE_CHANGED,
                    null,
                    $lockedDriver->id,
                    $admin,
                    "Pickup coverage {$coverageBefore->value} → {$coverageAfter->value}",
                );
            }

            $this->workloads->syncStatus($lockedDriver);

            event(new \App\Events\DriverAssignedToPickup($assignment, $lockedDriver));

            return [
                'success' => true,
                'message' => 'Rider assigned successfully.',
                'data' => ['assignment' => $assignment->load('driver', 'targetWarehouse')],
            ];
        });
    }

    /**
     * Assign several riders to one shipment in a single transaction.
     *
     * The shipment page's picker is multi-select. This used to be handled by
     * creating one assignment for the first id and dropping the rest, so picking
     * two riders put one rider on the parcel and reported success — and a
     * shipment whose requester asked for two riders could never be covered.
     *
     * Two things this does that a loop in the controller could not:
     *
     *  1. Places each rider in a real slot when the shipment named its vehicles.
     *     An assignment with no vehicle type deliberately does not count towards
     *     coverage, so riders added unslotted left a two-slot shipment reading as
     *     unassigned however many riders it had.
     *  2. Is atomic. A busy rider found on the second id would otherwise leave the
     *     first already assigned, and the retry the UI then offers would assign
     *     that first rider twice.
     *
     * @param  array<int, int|string|null>  $driverIds
     */
    public function assignMany(
        Shipment $shipment,
        array $driverIds,
        ?User $admin = null,
        ?string $notes = null,
        ?int $targetWarehouseId = null,
        bool $confirmBusyAssignment = false,
        ?int $pickupVehicleTypeId = null,
    ): array {
        $driverIds = array_values(array_unique(array_filter(
            array_map('intval', $driverIds),
            fn (int $id) => $id > 0,
        )));

        if ($driverIds === []) {
            return ['success' => false, 'message' => 'Select at least one rider.'];
        }

        DB::beginTransaction();

        try {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            /*
             * Where each rider goes — one entry per rider being assigned.
             *
             * The requested quantity is a floor the parcel has to meet, not a cap on
             * how many riders may be sent. Refusing a third rider on a parcel that
             * asked for two was wrong: a heavy run often needs another pair of hands,
             * and the admin is the one looking at the load. Fewer than requested has
             * always been allowed and still is; the coverage readout says how short
             * it is.
             *
             * Riders beyond the requested count are still placed on a requested
             * vehicle rather than left unslotted, because an unslotted rider does not
             * count towards coverage and would show as a rider the parcel never asked
             * for.
             */
            ['slots' => $slots, 'overflow_from' => $overflowFrom] =
                $this->pickupSlotPlan($lockedShipment, count($driverIds), $pickupVehicleTypeId);

            /*
             * Check every rider before writing any of them, so the common refusal
             * (an inactive rider, or one who is already busy) comes back without
             * having touched the shipment.
             */
            $drivers = [];

            foreach ($driverIds as $driverId) {
                $driver = Driver::query()->find($driverId);

                if (! $driver) {
                    DB::rollBack();

                    return ['success' => false, 'message' => 'One of the selected riders no longer exists.'];
                }

                if (! $driver->is_active) {
                    DB::rollBack();

                    return ['success' => false, 'message' => "{$driver->name} is inactive."];
                }

                if (! $driver->hasCapability(Driver::CAPABILITY_PICKUP)) {
                    DB::rollBack();

                    return ['success' => false, 'message' => "{$driver->name} is not configured for pickup assignments."];
                }

                $busy = $this->workloads->busyConflict($driver, $confirmBusyAssignment);

                if ($busy) {
                    // A 409 the caller turns into an "assign anyway?" prompt. The
                    // batch is rolled back first: nothing has been written yet.
                    DB::rollBack();

                    return $busy;
                }

                $drivers[] = $driver;
            }

            $assignments = [];

            foreach ($drivers as $index => $driver) {
                $result = $this->assign(
                    shipment: $lockedShipment,
                    driver: $driver,
                    admin: $admin,
                    notes: $notes,
                    targetWarehouseId: $targetWarehouseId,
                    confirmBusyAssignment: $confirmBusyAssignment,
                    pickupVehicleTypeId: $slots[$index] ?? null,
                    // Past the requested count these are riders the admin chose to
                    // add, so assign()'s per-type cap must not turn them away.
                    allowOverflow: $index >= $overflowFrom,
                );

                if (! ($result['success'] ?? false)) {
                    // Should be unreachable after the checks above; a concurrent
                    // assignment racing us on the same driver or slot is the one
                    // way it happens, and then nothing is written at all.
                    DB::rollBack();

                    return $result;
                }

                $assignments[] = $result['data']['assignment'];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => count($assignments) === 1
                    ? 'Rider assigned successfully.'
                    : count($assignments) . ' riders assigned successfully.',
                'data' => [
                    'assignments' => $assignments,
                    // Kept because the shipment page reads a single `assignment`.
                    'assignment' => $assignments[0],
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * A vehicle type for every rider being assigned, in order.
     *
     * Open slots are filled first, in request order. Riders beyond that keep going
     * on the last requested vehicle, so assigning three riders to a parcel that
     * asked for two leaves all three counted against the pickup rather than the
     * third floating without a slot.
     *
     * All-null when the parcel named no vehicles at all: the legacy case, where
     * riders are added unslotted exactly as they always were.
     *
     * `overflow_from` is the index at which riders stop filling requested slots and
     * start being extras the admin chose to send. assign() has to know, because its
     * per-type cap must not refuse them.
     *
     * @return array{slots: array<int, int|null>, overflow_from: int}
     */
    private function pickupSlotPlan(Shipment $shipment, int $riderCount, ?int $onlyTypeId = null): array
    {
        if ($riderCount < 1) {
            return ['slots' => [], 'overflow_from' => 0];
        }

        $namedVehicles = $shipment->pickupVehicleRequests()->exists();

        if (! $namedVehicles) {
            return ['slots' => array_fill(0, $riderCount, null), 'overflow_from' => 0];
        }

        $open = $this->openPickupSlots($shipment, $onlyTypeId);

        // The vehicle to put anyone beyond the requested count on. An explicit type
        // wins; otherwise the last one the parcel named.
        $overflowType = $onlyTypeId ?? $this->lastRequestedVehicleTypeId($shipment);

        $slots = array_slice($open, 0, $riderCount);
        $overflowFrom = count($slots);

        while (count($slots) < $riderCount) {
            $slots[] = $overflowType;
        }

        return ['slots' => $slots, 'overflow_from' => $overflowFrom];
    }

    /**
     * The vehicle type on the parcel's last request, ignoring requests whose type
     * row has since been deleted.
     */
    private function lastRequestedVehicleTypeId(Shipment $shipment): ?int
    {
        $id = $shipment->pickupVehicleRequests()
            ->whereNotNull('pickup_vehicle_type_id')
            ->orderByDesc('id')
            ->value('pickup_vehicle_type_id');

        return $id ? (int) $id : null;
    }

    /**
     * The still-open pickup slots on a shipment, in request order.
     *
     * Each entry is the vehicle type id of a slot that is still free, so the first
     * N entries are where the first N riders go. Already-filled slots are consumed
     * from the front of each request, matching how pickupSlotBreakdown() counts.
     *
     * Empty when the shipment never named a vehicle: the legacy case, where riders
     * are added without a slot and the caller falls back to unslotted rows.
     *
     * @return array<int, int>
     */
    private function openPickupSlots(Shipment $shipment, ?int $onlyTypeId = null): array
    {
        $requests = $shipment->pickupVehicleRequests()->orderBy('id')->get();

        if ($requests->isEmpty()) {
            return [];
        }

        $taken = PickupAssignment::query()
            ->where('shipment_id', $shipment->id)
            ->where('status', '!=', PickupAssignmentStatus::CANCELLED)
            ->whereNotNull('pickup_vehicle_type_id')
            ->selectRaw('pickup_vehicle_type_id, count(*) as total')
            ->groupBy('pickup_vehicle_type_id')
            ->pluck('total', 'pickup_vehicle_type_id')
            ->toArray();

        $open = [];

        foreach ($requests as $request) {
            $typeId = $request->pickup_vehicle_type_id;

            // A request whose vehicle type row was deleted has no id to claim.
            if (! $typeId) {
                continue;
            }

            if ($onlyTypeId !== null && (int) $typeId !== $onlyTypeId) {
                continue;
            }

            for ($i = 0; $i < (int) $request->quantity; $i++) {
                if (($taken[$typeId] ?? 0) > 0) {
                    $taken[$typeId]--;

                    continue;
                }

                $open[] = (int) $typeId;
            }
        }

        return $open;
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

    /**
     * Which driver vehicle serves a requested pickup vehicle.
     *
     * The two vocabularies do not line up. A parcel asks for motorbike / aboboyaa /
     * van / truck, while a driver's own vehicle is motorcycle / car / van / truck.
     * The translation lives here so the picker can send the ids it already has and
     * the mapping stays in one place.
     *
     * Aboboyaa has no driver vehicle of its own. It maps to the motorcycle pool
     * deliberately: a tricycle load is dispatched to a rider, and leaving it
     * unmapped would list car, van and truck drivers for a tricycle pickup, which
     * is backwards. This is the one line to change if that policy differs.
     */
    private const PICKUP_VEHICLE_DRIVER_VEHICLES = [
        'motorbike' => 'motorcycle',
        'aboboyaa' => 'motorcycle',
        'van' => 'van',
        'truck' => 'truck',
    ];

    /**
     * Translate requested pickup vehicle type ids into driver vehicle types.
     *
     * Returns an empty array when nothing maps, which callers treat as "no
     * constraint" — a filter that matches nobody would leave an admin unable to
     * assign anyone at all.
     *
     * @param  array<int, int|string|null>  $pickupVehicleTypeIds
     * @return array<int, string>
     */
    public function driverVehicleTypesForPickupTypes(array $pickupVehicleTypeIds): array
    {
        $ids = collect($pickupVehicleTypeIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return PickupVehicleType::query()
            ->whereIn('id', $ids->all())
            ->pluck('slug')
            ->map(fn ($slug) => self::PICKUP_VEHICLE_DRIVER_VEHICLES[strtolower(trim((string) $slug))] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  string|array<int, string>|null  $vehicleType  One driver vehicle type,
     *                                                       or several to match any of.
     */
    public function getAvailableDrivers(string|array|null $vehicleType = null, ?string $assignmentType = null): array
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

            // Check if there are any other pending assignments for this shipment
            $hasPendingAssignments = PickupAssignment::query()
                ->where('shipment_id', $assignment->shipment_id)
                ->whereNotIn('status', [PickupAssignmentStatus::COMPLETED, PickupAssignmentStatus::CANCELLED])
                ->where('id', '!=', $assignment->id)
                ->exists();

            if (! $hasPendingAssignments) {
                $assignment->shipment->update(['status' => ShipmentStatus::PICKED_UP]);
            }
            
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

            // Check if there are other riders still expected at the warehouse
            $hasPendingWarehouseDeliveries = PickupAssignment::query()
                ->where('shipment_id', $lockedAssignment->shipment_id)
                ->whereNull('received_at')
                ->where('status', '!=', PickupAssignmentStatus::CANCELLED)
                ->where('id', '!=', $lockedAssignment->id)
                ->exists();

            if (! $hasPendingWarehouseDeliveries) {
                $lockedAssignment->shipment->update(['status' => ShipmentStatus::AT_WAREHOUSE]);
            }

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
            $assignment->loadMissing('shipment');

            // Coverage is derived from the live assignments, so snapshot it
            // before and after this cancellation and log any move.
            $coverageBefore = $assignment->shipment?->pickupCoverageStatus();

            $assignment->update([
                'status' => PickupAssignmentStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            
            // Note: If you cancel an assignment, you only revert the Shipment to SUBMITTED 
            // if there are NO OTHER active assignments left.
            $hasOtherActiveAssignments = PickupAssignment::query()
                ->where('shipment_id', $assignment->shipment_id)
                ->where('status', '!=', PickupAssignmentStatus::CANCELLED)
                ->where('id', '!=', $assignment->id)
                ->exists();

            if (! $hasOtherActiveAssignments) {
                $assignment->shipment->update(['status' => ShipmentStatus::SUBMITTED]);
            }

            $this->assignmentAudit->record('pickup', $assignment->id, 'unassigned', $driver?->id, null, $actor, $reason);

            if ($assignment->shipment) {
                $assignment->shipment->load('pickupAssignments');
                $coverageAfter = $assignment->shipment->pickupCoverageStatus();
                if ($coverageBefore !== $coverageAfter) {
                    $this->assignmentAudit->record(
                        'pickup',
                        $assignment->id,
                        RiderAssignmentEvent::EVENT_COVERAGE_CHANGED,
                        $driver?->id,
                        null,
                        $actor,
                        'Pickup coverage '.($coverageBefore?->value ?? 'unknown').' → '.$coverageAfter->value,
                    );
                }
            }

            if ($driver) {
                event(new \App\Events\DriverUnassignedFromPickup($assignment, $driver, $reason));
                $this->workloads->syncStatus($driver);
            }

            return ['success' => true, 'message' => 'Assignment cancelled.'];
        });
    }
}