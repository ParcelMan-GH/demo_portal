<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseTransportService
{
    public function outboundQuery(Warehouse $warehouse): Builder
    {
        return TransportManifest::query()
            ->with([
                'originWarehouse:id,name,code',
                'destinationWarehouse:id,name,code',
                'assignedDriver:id,name,phone,vehicle_type,vehicle_number',
                'items:id,transport_manifest_id,shipment_item_id,expected_quantity,loaded_quantity,received_quantity,line_status',
            ])
            ->where('origin_warehouse_id', $warehouse->id);
    }

    public function incomingQuery(Warehouse $warehouse): Builder
    {
        return TransportManifest::query()
            ->with([
                'originWarehouse:id,name,code',
                'destinationWarehouse:id,name,code',
                'assignedDriver:id,name,phone,vehicle_type,vehicle_number',
                'items:id,transport_manifest_id,shipment_item_id,expected_quantity,loaded_quantity,received_quantity,line_status',
            ])
            ->where('destination_warehouse_id', $warehouse->id);
    }

    public function createManifest(SortBatch $batch, Warehouse $warehouse, User $user): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot create manifest for another warehouse batch.'];
        }

        if ($batch->dispatch_mode !== SortBatch::DISPATCH_TRANSFER) {
            return ['success' => false, 'message' => 'Only transfer sort batches can create transport manifests.'];
        }

        if ($batch->status !== SortBatch::STATUS_SEALED) {
            return ['success' => false, 'message' => 'Batch must be sealed before creating manifest.'];
        }

        if (!$batch->destination_warehouse_id) {
            return ['success' => false, 'message' => 'Destination warehouse is required for transfer manifest.'];
        }

        if ($batch->transportManifest()->exists()) {
            return ['success' => false, 'message' => 'Transport manifest already exists for this batch.'];
        }

        return DB::transaction(function () use ($batch, $warehouse, $user) {
            $batch = SortBatch::query()
                ->with(['activeItems.shipmentItem'])
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if ($batch->transportManifest()->exists()) {
                return ['success' => false, 'message' => 'Transport manifest already exists for this batch.'];
            }

            $activeItems = $batch->activeItems;
            if ($activeItems->isEmpty()) {
                return ['success' => false, 'message' => 'Cannot create manifest from an empty batch.'];
            }

            $manifest = TransportManifest::query()->create([
                'manifest_number' => $this->generateManifestNumber($warehouse, $batch->destinationWarehouse),
                'sort_batch_id' => $batch->id,
                'origin_warehouse_id' => $warehouse->id,
                'destination_warehouse_id' => $batch->destination_warehouse_id,
                'status' => TransportManifest::STATUS_DRAFT,
                'created_by_user_id' => $user->id,
            ]);

            foreach ($activeItems as $batchItem) {
                TransportManifestItem::query()->create([
                    'transport_manifest_id' => $manifest->id,
                    'shipment_item_id' => $batchItem->shipment_item_id,
                    'expected_quantity' => (int) $batchItem->quantity_allocated,
                    'line_status' => TransportManifestItem::LINE_PENDING,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Transport manifest created successfully.',
                'data' => [
                    'manifest' => $manifest->fresh([
                        'originWarehouse',
                        'destinationWarehouse',
                        'items.shipmentItem.shipment',
                    ]),
                ],
            ];
        });
    }

    public function assignDriver(TransportManifest $manifest, Driver $driver, Warehouse $warehouse): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot assign driver for another warehouse manifest.'];
        }

        if (in_array($manifest->status, [
            TransportManifest::STATUS_IN_TRANSIT,
            TransportManifest::STATUS_ARRIVED,
            TransportManifest::STATUS_RECEIVED,
            TransportManifest::STATUS_CANCELLED,
        ], true)) {
            return ['success' => false, 'message' => 'Manifest can no longer be assigned.'];
        }

        if (!$driver->is_active) {
            return ['success' => false, 'message' => 'Driver is inactive.'];
        }

        if ($driver->status === 'busy' && (int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Driver is currently busy.'];
        }

        if (!$driver->hasCapability(Driver::CAPABILITY_TRANSPORT)) {
            return ['success' => false, 'message' => 'Driver is not configured for transport assignments.'];
        }

        return DB::transaction(function () use ($manifest, $driver) {
            $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);
            $previousDriverId = $lockedManifest->assigned_driver_id;

            $lockedManifest->update([
                'assigned_driver_id' => $driver->id,
                'assigned_at' => now(),
                'status' => TransportManifest::STATUS_ASSIGNED,
            ]);

            if ($previousDriverId && (int) $previousDriverId !== (int) $driver->id) {
                Driver::query()->whereKey($previousDriverId)->update(['status' => 'available']);
            }

            $driver->update(['status' => 'busy']);

            return [
                'success' => true,
                'message' => 'Transport driver assigned successfully.',
                'data' => [
                    'manifest' => $lockedManifest->fresh([
                        'assignedDriver',
                        'originWarehouse',
                        'destinationWarehouse',
                    ]),
                ],
            ];
        });
    }

    public function dispatch(TransportManifest $manifest, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot dispatch another warehouse manifest.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_ASSIGNED) {
            return ['success' => false, 'message' => 'Only assigned manifests can be dispatched.'];
        }

        if (!$manifest->assigned_driver_id) {
            return ['success' => false, 'message' => 'Assign a driver before dispatching.'];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user) {
            $lockedManifest = TransportManifest::query()
                ->with(['items.shipmentItem.shipment'])
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $now = now();
            $lockedManifest->update([
                'status' => TransportManifest::STATUS_LOADING,
                'dispatched_at' => $now,
            ]);

            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();

            foreach ($lockedManifest->items as $line) {
                $item = $line->shipmentItem;
                if (!$item) {
                    continue;
                }

                $item->update(['status' => ItemStatus::IN_TRANSIT]);

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::IN_TRANSIT->value,
                    'location' => $warehouse->name,
                    'notes' => 'Item dispatched for transport manifest ' . $lockedManifest->manifest_number . '.',
                    'meta' => [
                        'transport_manifest_id' => $lockedManifest->id,
                        'transport_manifest_number' => $lockedManifest->manifest_number,
                        'event' => 'dispatched',
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => $now,
                ]);

                if ($item->shipment) {
                    $shipments->push($item->shipment);
                }
            }

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentInTransitStatus($shipment);
            });

            return [
                'success' => true,
                'message' => 'Transport manifest dispatched successfully.',
                'data' => ['manifest' => $lockedManifest->fresh(['items.shipmentItem', 'assignedDriver'])],
            ];
        });
    }

    public function driverStartLoading(TransportManifest $manifest, Driver $driver): array
    {
        if ((int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Manifest not found.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Manifest is not ready for loading.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_LOADING) {
            $manifest->update(['status' => TransportManifest::STATUS_LOADING]);
        }

        return [
            'success' => true,
            'message' => 'Loading started.',
            'data' => ['manifest' => $manifest->fresh(['items.shipmentItem', 'originWarehouse', 'destinationWarehouse'])],
        ];
    }

    public function driverScanLoad(TransportManifest $manifest, Driver $driver, string $trackingCode): array
    {
        if ((int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Manifest not found.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Manifest is not in loading state.'];
        }

        return DB::transaction(function () use ($manifest, $trackingCode) {
            $manifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);

            if ($manifest->status === TransportManifest::STATUS_ASSIGNED) {
                $manifest->update(['status' => TransportManifest::STATUS_LOADING]);
            }

            $line = TransportManifestItem::query()
                ->where('transport_manifest_id', $manifest->id)
                ->whereHas('shipmentItem', function (Builder $query) use ($trackingCode) {
                    $query->where('tracking_code', $trackingCode);
                })
                ->lockForUpdate()
                ->first();

            if (!$line) {
                return ['success' => false, 'message' => 'Tracking code not found in this manifest.'];
            }

            $line->update([
                'scan_out_count' => ((int) $line->scan_out_count) + 1,
                'loaded_quantity' => (int) $line->expected_quantity,
                'loaded_at' => now(),
                'line_status' => TransportManifestItem::LINE_LOADED,
            ]);

            return [
                'success' => true,
                'message' => 'Item loaded successfully.',
                'data' => [
                    'line' => $line->fresh('shipmentItem'),
                    'manifest' => $manifest->fresh(['items.shipmentItem']),
                ],
            ];
        });
    }

    public function driverDepart(TransportManifest $manifest, Driver $driver): array
    {
        if ((int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Manifest not found.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_LOADING) {
            return ['success' => false, 'message' => 'Manifest is not ready to depart.'];
        }

        $notLoadedCount = $manifest->items()->where('loaded_quantity', '<=', 0)->count();
        if ($notLoadedCount > 0) {
            return ['success' => false, 'message' => 'All manifest items must be scanned before departure.'];
        }

        $manifest->update([
            'status' => TransportManifest::STATUS_IN_TRANSIT,
            'dispatched_at' => $manifest->dispatched_at ?? now(),
        ]);

        return [
            'success' => true,
            'message' => 'Manifest departed successfully.',
            'data' => ['manifest' => $manifest->fresh(['items.shipmentItem'])],
        ];
    }

    public function driverArrive(TransportManifest $manifest, Driver $driver): array
    {
        if ((int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Manifest not found.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_IN_TRANSIT) {
            return ['success' => false, 'message' => 'Manifest is not in transit.'];
        }

        $manifest->update([
            'status' => TransportManifest::STATUS_ARRIVED,
            'arrived_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Arrival recorded successfully.',
            'data' => ['manifest' => $manifest->fresh(['items.shipmentItem', 'destinationWarehouse'])],
        ];
    }

    private function generateManifestNumber(Warehouse $origin, ?Warehouse $destination): string
    {
        $year = now()->format('Y');
        $originCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($origin->code ?: $origin->id)));
        $destinationCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) (($destination?->code) ?: ($destination?->id))));
        $prefix = "TM-{$year}-{$originCode}-{$destinationCode}-";

        $last = TransportManifest::query()
            ->where('manifest_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $next = 1;
        if ($last) {
            $parts = explode('-', $last->manifest_number);
            $next = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function syncShipmentInTransitStatus(Shipment $shipment): void
    {
        $allTransitOrBeyond = !$shipment->items()
            ->whereNotIn('status', [
                ItemStatus::IN_TRANSIT->value,
                ItemStatus::AT_DESTINATION->value,
                ItemStatus::OUT_FOR_DELIVERY->value,
                ItemStatus::DELIVERED->value,
                ItemStatus::RETURNED->value,
            ])
            ->exists();

        if ($allTransitOrBeyond && $shipment->status !== ShipmentStatus::IN_TRANSIT) {
            $shipment->update(['status' => ShipmentStatus::IN_TRANSIT]);
        }
    }
}
