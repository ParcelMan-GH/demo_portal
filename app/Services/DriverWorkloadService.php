<?php

namespace App\Services;

use App\Enums\PickupAssignmentStatus;
use App\Models\DeliveryRun;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\TransportManifest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DriverWorkloadService
{
    /**
     * @return array{pickups:int,transports:int,deliveries:int,total:int,is_busy:bool}
     */
    public function summary(Driver|int $driver): array
    {
        $driverId = $driver instanceof Driver ? $driver->id : $driver;

        return $this->summaries([$driverId])[$driverId] ?? $this->emptySummary();
    }

    /**
     * @param  array<int, int|string>  $driverIds
     * @return array<int, array{pickups:int,transports:int,deliveries:int,total:int,is_busy:bool}>
     */
    public function summaries(array $driverIds): array
    {
        $ids = collect($driverIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $summaries = $ids->mapWithKeys(fn (int $id) => [$id => $this->emptySummary()])->all();

        $pickups = Schema::hasTable('pickup_assignments') ? PickupAssignment::query()
            ->selectRaw('driver_id, COUNT(*) AS aggregate')
            ->whereIn('driver_id', $ids)
            ->whereNotIn('status', [PickupAssignmentStatus::COMPLETED->value, PickupAssignmentStatus::CANCELLED->value])
            ->groupBy('driver_id')
            ->pluck('aggregate', 'driver_id') : collect();

        $transports = Schema::hasTable('transport_manifests') ? TransportManifest::query()
            ->selectRaw('assigned_driver_id, COUNT(*) AS aggregate')
            ->whereIn('assigned_driver_id', $ids)
            ->whereNotIn('status', [TransportManifest::STATUS_RECEIVED, TransportManifest::STATUS_CANCELLED])
            ->groupBy('assigned_driver_id')
            ->pluck('aggregate', 'assigned_driver_id') : collect();

        $deliveries = Schema::hasTable('delivery_runs') ? DeliveryRun::query()
            ->selectRaw('assigned_driver_id, COUNT(*) AS aggregate')
            ->whereIn('assigned_driver_id', $ids)
            ->whereIn('status', [
                DeliveryRun::STATUS_ASSIGNED,
                DeliveryRun::STATUS_OUT_FOR_DELIVERY,
                DeliveryRun::STATUS_PARTIALLY_DELIVERED,
            ])
            ->groupBy('assigned_driver_id')
            ->pluck('aggregate', 'assigned_driver_id') : collect();

        foreach ($ids as $id) {
            $summaries[$id]['pickups'] = (int) ($pickups[$id] ?? 0);
            $summaries[$id]['transports'] = (int) ($transports[$id] ?? 0);
            $summaries[$id]['deliveries'] = (int) ($deliveries[$id] ?? 0);
            $summaries[$id]['total'] = $summaries[$id]['pickups'] + $summaries[$id]['transports'] + $summaries[$id]['deliveries'];
            $summaries[$id]['is_busy'] = $summaries[$id]['total'] > 0;
        }

        return $summaries;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    /** @param array<int, int|string|null> $includeDriverIds */
    public function assignmentOptions(?string $capability = null, ?string $vehicleType = null, array $includeDriverIds = []): Collection
    {
        $capability = in_array($capability, Driver::CAPABILITIES, true)
            ? $capability
            : Driver::CAPABILITY_PICKUP;

        $query = Driver::query()->where('is_active', true)
            ->where(function ($builder) use ($capability) {
                if ($capability === Driver::CAPABILITY_PICKUP) {
                    $builder->whereNull('task_capabilities')
                        ->orWhereJsonContains('task_capabilities', $capability);
                } else {
                    $builder->whereJsonContains('task_capabilities', $capability);
                }
            });

        if ($vehicleType) {
            $query->where('vehicle_type', $vehicleType);
        }

        $drivers = $query->orderBy('name')->get([
            'id', 'name', 'phone', 'vehicle_type', 'vehicle_number', 'status',
        ]);

        $includedIds = collect($includeDriverIds)->filter()->map(fn ($id) => (int) $id)->unique();
        if ($includedIds->isNotEmpty()) {
            $included = Driver::query()
                ->whereIn('id', $includedIds)
                ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number', 'status']);
            $drivers = $drivers->concat($included)->unique('id')->sortBy('name')->values();
        }
        $summaries = $this->summaries($drivers->pluck('id')->all());

        return $drivers->map(function (Driver $driver) use ($summaries) {
            $workload = $summaries[$driver->id] ?? $this->emptySummary();

            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'vehicle_type' => $driver->vehicle_type,
                'vehicle_number' => $driver->vehicle_number,
                'status' => $driver->status,
                'is_busy' => $workload['is_busy'],
                'active_work_count' => $workload['total'],
                'active_work' => [
                    'pickups' => $workload['pickups'],
                    'transports' => $workload['transports'],
                    'deliveries' => $workload['deliveries'],
                ],
            ];
        });
    }

    public function syncStatus(Driver|int $driver): Driver
    {
        $model = $driver instanceof Driver ? $driver : Driver::query()->findOrFail($driver);
        $busy = $this->summary($model)['is_busy'];

        if ($busy && $model->status !== 'busy') {
            $model->forceFill(['status' => 'busy'])->saveQuietly();
        } elseif (! $busy && $model->status === 'busy') {
            $model->forceFill(['status' => 'available'])->saveQuietly();
        }

        return $model->refresh();
    }

    public function busyConflict(Driver $driver, bool $confirmed): ?array
    {
        $workload = $this->summary($driver);
        if (! $workload['is_busy'] || $confirmed) {
            return null;
        }

        return [
            'success' => false,
            'code' => 'rider_busy',
            'message' => 'This rider already has active work. Confirm to assign them anyway.',
            'data' => [
                'driver_id' => $driver->id,
                'active_work_count' => $workload['total'],
                'active_work' => [
                    'pickups' => $workload['pickups'],
                    'transports' => $workload['transports'],
                    'deliveries' => $workload['deliveries'],
                ],
            ],
        ];
    }

    /** @param array<int, int|string|null> $driverIds */
    public function syncMany(array $driverIds): void
    {
        Driver::query()
            ->whereIn('id', collect($driverIds)->filter()->unique())
            ->get()
            ->each(fn (Driver $driver) => $this->syncStatus($driver));
    }

    /** @return array{pickups:int,transports:int,deliveries:int,total:int,is_busy:bool} */
    private function emptySummary(): array
    {
        return ['pickups' => 0, 'transports' => 0, 'deliveries' => 0, 'total' => 0, 'is_busy' => false];
    }
}
