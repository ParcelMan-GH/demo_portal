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
use App\Models\TransportManifestAssignment;
use App\Models\TransportContainer;
use App\Models\TransportContainerItem;
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

            $this->ensureDefaultContainer($manifest->fresh('items'), $user);

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

    public function assignDriver(TransportManifest $manifest, Driver $driver, Warehouse $warehouse, ?User $user = null): array
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

        if (!$driver->hasCapability(Driver::CAPABILITY_TRANSPORT)) {
            return ['success' => false, 'message' => 'Driver is not configured for transport assignments.'];
        }

        return DB::transaction(function () use ($manifest, $driver, $user) {
            $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);
            $previousDriverId = $lockedManifest->assigned_driver_id;
            $now = now();

            // Close previous assignment log if driver is changing
            if ($previousDriverId && (int) $previousDriverId !== (int) $driver->id) {
                TransportManifestAssignment::query()
                    ->where('transport_manifest_id', $lockedManifest->id)
                    ->where('driver_id', $previousDriverId)
                    ->whereNull('unassigned_at')
                    ->update([
                        'unassigned_at' => $now,
                        'unassigned_by_user_id' => $user?->id,
                        'unassign_reason' => 'Reassigned to another driver',
                    ]);

                Driver::query()->whereKey($previousDriverId)->update(['status' => 'available']);
            }

            $lockedManifest->update([
                'assigned_driver_id' => $driver->id,
                'assigned_at' => $now,
                'status' => TransportManifest::STATUS_ASSIGNED,
            ]);

            // Create assignment log entry
            TransportManifestAssignment::query()->create([
                'transport_manifest_id' => $lockedManifest->id,
                'driver_id' => $driver->id,
                'assigned_by_user_id' => $user?->id,
                'assigned_at' => $now,
            ]);

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

    public function unassignDriver(TransportManifest $manifest, Warehouse $warehouse, User $user, ?string $reason = null): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_DRAFT], true)) {
            return ['success' => false, 'message' => 'Driver can only be unassigned from draft or assigned manifests.'];
        }

        if (!$manifest->assigned_driver_id) {
            return ['success' => false, 'message' => 'No driver is currently assigned.'];
        }

        return DB::transaction(function () use ($manifest, $user, $reason) {
            $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);
            $previousDriverId = $lockedManifest->assigned_driver_id;
            $now = now();

            // Close assignment log
            TransportManifestAssignment::query()
                ->where('transport_manifest_id', $lockedManifest->id)
                ->where('driver_id', $previousDriverId)
                ->whereNull('unassigned_at')
                ->update([
                    'unassigned_at' => $now,
                    'unassigned_by_user_id' => $user->id,
                    'unassign_reason' => $reason ?: 'Driver unassigned',
                ]);

            $lockedManifest->update([
                'assigned_driver_id' => null,
                'assigned_at' => null,
                'status' => TransportManifest::STATUS_DRAFT,
            ]);

            if ($previousDriverId) {
                Driver::query()->whereKey($previousDriverId)->update(['status' => 'available']);
            }

            return [
                'success' => true,
                'message' => 'Driver unassigned successfully.',
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

        $this->ensureDefaultContainer($manifest->fresh('items'));

        return [
            'success' => true,
            'message' => 'Loading started.',
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

        return DB::transaction(function () use ($manifest, $driver, $trackingCode) {
            $manifest = TransportManifest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            if ($manifest->status === TransportManifest::STATUS_ASSIGNED) {
                $manifest->update(['status' => TransportManifest::STATUS_LOADING]);
            }

            $this->ensureDefaultContainer($manifest);

            $container = TransportContainer::query()
                ->where('transport_manifest_id', $manifest->id)
                ->where('container_code', $trackingCode)
                ->with('items.manifestItem')
                ->lockForUpdate()
                ->first();

            if ($container) {
                return $this->markContainerLoaded($container, $driver);
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

            $containerItem = TransportContainerItem::query()
                ->where('transport_manifest_item_id', $line->id)
                ->with('container.items.manifestItem')
                ->first();

            if ($containerItem?->container) {
                $allContainerItemsLoaded = $containerItem->container->items->every(function (TransportContainerItem $item) {
                    $manifestLine = $item->manifestItem;

                    return $manifestLine
                        && (int) $manifestLine->loaded_quantity >= (int) $manifestLine->expected_quantity;
                });

                if ($allContainerItemsLoaded) {
                    $containerItem->container->update([
                        'status' => TransportContainer::STATUS_LOADED,
                        'loaded_at' => $containerItem->container->loaded_at ?? now(),
                        'loaded_by_driver_id' => $driver->id,
                    ]);

                    $containerItem->container->items()->update(['status' => TransportContainerItem::STATUS_LOADED]);
                }
            }

            return [
                'success' => true,
                'message' => 'Item loaded successfully.',
            ];
        });
    }

    public function adminMarkItemLoaded(TransportManifest $manifest, TransportManifestItem $line, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot load another warehouse manifest.'];
        }

        if ((int) $line->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Item not found on this manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Manifest is not in loading state.'];
        }

        return DB::transaction(function () use ($manifest, $line, $user) {
            $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);

            if ($lockedManifest->status === TransportManifest::STATUS_ASSIGNED) {
                $lockedManifest->update(['status' => TransportManifest::STATUS_LOADING]);
            }

            $lockedLine = TransportManifestItem::query()
                ->where('transport_manifest_id', $lockedManifest->id)
                ->lockForUpdate()
                ->findOrFail($line->id);

            if ((int) $lockedLine->loaded_quantity >= (int) $lockedLine->expected_quantity && $lockedLine->line_status === TransportManifestItem::LINE_LOADED) {
                return [
                    'success' => true,
                    'message' => 'Manifest item is already loaded.',
                ];
            }

            $lockedLine->update([
                'scan_out_count' => max((int) $lockedLine->scan_out_count, 1),
                'loaded_quantity' => (int) $lockedLine->expected_quantity,
                'loaded_at' => $lockedLine->loaded_at ?? now(),
                'line_status' => TransportManifestItem::LINE_LOADED,
                'notes' => trim(implode("\n", array_filter([
                    $lockedLine->notes,
                    'Marked loaded by admin ' . $user->name . '.',
                ]))),
            ]);

            $this->syncLineContainerLoadState($lockedLine);

            return [
                'success' => true,
                'message' => 'Manifest item marked as loaded.',
            ];
        });
    }

    public function adminMarkAllItemsLoaded(TransportManifest $manifest, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot load another warehouse manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Manifest is not in loading state.'];
        }

        return DB::transaction(function () use ($manifest, $user) {
            $lockedManifest = TransportManifest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            if ($lockedManifest->status === TransportManifest::STATUS_ASSIGNED) {
                $lockedManifest->update(['status' => TransportManifest::STATUS_LOADING]);
            }

            $updated = 0;
            foreach ($lockedManifest->items as $line) {
                if ((int) $line->loaded_quantity >= (int) $line->expected_quantity && $line->line_status === TransportManifestItem::LINE_LOADED) {
                    continue;
                }

                $line->update([
                    'scan_out_count' => max((int) $line->scan_out_count, 1),
                    'loaded_quantity' => (int) $line->expected_quantity,
                    'loaded_at' => $line->loaded_at ?? now(),
                    'line_status' => TransportManifestItem::LINE_LOADED,
                    'notes' => trim(implode("\n", array_filter([
                        $line->notes,
                        'Marked loaded by admin ' . $user->name . '.',
                    ]))),
                ]);
                $this->syncLineContainerLoadState($line);
                $updated++;
            }

            return [
                'success' => true,
                'message' => $updated === 1
                    ? '1 manifest item marked as loaded.'
                    : "{$updated} manifest items marked as loaded.",
                'data' => ['updated_count' => $updated],
            ];
        });
    }

    public function createContainer(TransportManifest $manifest, Warehouse $warehouse, User $user, string $containerType = 'box', ?string $notes = null): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if (!in_array($manifest->status, [
            TransportManifest::STATUS_DRAFT,
            TransportManifest::STATUS_ASSIGNED,
            TransportManifest::STATUS_LOADING,
        ], true)) {
            return ['success' => false, 'message' => 'Containers can only be created before transport departure.'];
        }

        return DB::transaction(function () use ($manifest, $user, $containerType, $notes) {
            $lockedManifest = TransportManifest::query()
                ->with('containers')
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $nextSequence = ((int) $lockedManifest->containers->max('sequence_number')) + 1;

            $container = TransportContainer::query()->create([
                'transport_manifest_id' => $lockedManifest->id,
                'container_code' => $this->generateContainerCode($lockedManifest, $nextSequence),
                'container_type' => $containerType ?: 'box',
                'sequence_number' => $nextSequence,
                'status' => TransportContainer::STATUS_OPEN,
                'expected_package_count' => 0,
                'sealed_by_user_id' => $user->id,
                'notes' => $notes,
            ]);

            return [
                'success' => true,
                'message' => 'Transport container created.',
                'data' => ['container' => $container],
            ];
        });
    }

    public function moveItemToContainer(
        TransportManifest $manifest,
        TransportManifestItem $line,
        TransportContainer $container,
        Warehouse $warehouse,
        User $user
    ): array {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if ((int) $line->transport_manifest_id !== (int) $manifest->id || (int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Container or item does not belong to this manifest.'];
        }

        if (!in_array($manifest->status, [
            TransportManifest::STATUS_DRAFT,
            TransportManifest::STATUS_ASSIGNED,
            TransportManifest::STATUS_LOADING,
        ], true)) {
            return ['success' => false, 'message' => 'Items can only be moved before transport departure.'];
        }

        if (in_array($container->status, [
            TransportContainer::STATUS_LOADED,
            TransportContainer::STATUS_IN_TRANSIT,
            TransportContainer::STATUS_RECEIVED,
            TransportContainer::STATUS_RECONCILED,
        ], true)) {
            return ['success' => false, 'message' => 'Cannot move items into a loaded or received container.'];
        }

        return DB::transaction(function () use ($line, $container, $user) {
            $lockedLine = TransportManifestItem::query()
                ->with('shipmentItem')
                ->lockForUpdate()
                ->findOrFail($line->id);

            $lockedContainer = TransportContainer::query()
                ->lockForUpdate()
                ->findOrFail($container->id);

            TransportContainerItem::query()
                ->where('transport_manifest_item_id', $lockedLine->id)
                ->delete();

            TransportContainerItem::query()->create([
                'transport_container_id' => $lockedContainer->id,
                'transport_manifest_item_id' => $lockedLine->id,
                'shipment_item_id' => $lockedLine->shipment_item_id,
                'label_barcode' => $lockedLine->shipmentItem?->tracking_code,
                'expected_quantity' => (int) $lockedLine->expected_quantity,
                'status' => TransportContainerItem::STATUS_PACKED,
            ]);

            TransportContainer::query()
                ->where('transport_manifest_id', $lockedContainer->transport_manifest_id)
                ->get()
                ->each(function (TransportContainer $manifestContainer) {
                    $manifestContainer->update([
                        'expected_package_count' => $manifestContainer->items()->count(),
                    ]);
                });

            $lockedContainer->update([
                'status' => TransportContainer::STATUS_SEALED,
                'sealed_at' => $lockedContainer->sealed_at ?? now(),
                'sealed_by_user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Manifest item moved to container.',
            ];
        });
    }

    public function adminMarkContainerLoaded(TransportManifest $manifest, TransportContainer $container, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot load another warehouse manifest.'];
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Container not found on this manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Manifest is not in loading state.'];
        }

        return DB::transaction(function () use ($manifest, $container, $user) {
            $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);
            if ($lockedManifest->status === TransportManifest::STATUS_ASSIGNED) {
                $lockedManifest->update(['status' => TransportManifest::STATUS_LOADING]);
            }

            $lockedContainer = TransportContainer::query()
                ->with('items.manifestItem')
                ->lockForUpdate()
                ->findOrFail($container->id);

            if ($lockedContainer->items->isEmpty()) {
                return ['success' => false, 'message' => 'Cannot load an empty container.'];
            }

            $now = now();
            foreach ($lockedContainer->items as $containerItem) {
                $line = $containerItem->manifestItem;
                if (!$line) {
                    continue;
                }

                $line->update([
                    'scan_out_count' => max((int) $line->scan_out_count, 1),
                    'loaded_quantity' => (int) $line->expected_quantity,
                    'loaded_at' => $line->loaded_at ?? $now,
                    'line_status' => TransportManifestItem::LINE_LOADED,
                    'notes' => trim(implode("\n", array_filter([
                        $line->notes,
                        'Container ' . $lockedContainer->container_code . ' marked loaded by admin ' . $user->name . '.',
                    ]))),
                ]);

                $containerItem->update(['status' => TransportContainerItem::STATUS_LOADED]);
            }

            $lockedContainer->update([
                'status' => TransportContainer::STATUS_LOADED,
                'loaded_at' => $lockedContainer->loaded_at ?? $now,
            ]);

            return [
                'success' => true,
                'message' => 'Transport container marked as loaded.',
            ];
        });
    }

    public function deleteContainer(TransportManifest $manifest, TransportContainer $container, Warehouse $warehouse): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Container not found on this manifest.'];
        }

        if (!in_array($manifest->status, [
            TransportManifest::STATUS_DRAFT,
            TransportManifest::STATUS_ASSIGNED,
            TransportManifest::STATUS_LOADING,
        ], true)) {
            return ['success' => false, 'message' => 'Containers can only be deleted before transport departure.'];
        }

        return DB::transaction(function () use ($manifest, $container) {
            $lockedManifest = TransportManifest::query()
                ->withCount('containers')
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            if ((int) $lockedManifest->containers_count <= 1) {
                return ['success' => false, 'message' => 'At least one container must remain on the manifest.'];
            }

            $lockedContainer = TransportContainer::query()
                ->withCount('items')
                ->lockForUpdate()
                ->findOrFail($container->id);

            if ((int) $lockedContainer->items_count > 0) {
                return ['success' => false, 'message' => 'Move all items to another container before deleting this one.'];
            }

            $lockedContainer->delete();

            return [
                'success' => true,
                'message' => 'Transport container deleted.',
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

        $this->ensureDefaultContainer($manifest->fresh('items'));

        $notLoadedCount = $manifest->containers()
            ->whereHas('items')
            ->whereNotIn('status', [
                TransportContainer::STATUS_LOADED,
                TransportContainer::STATUS_IN_TRANSIT,
                TransportContainer::STATUS_RECEIVED,
                TransportContainer::STATUS_RECONCILED,
            ])
            ->count();
        if ($notLoadedCount > 0) {
            return ['success' => false, 'message' => 'All transport containers must be loaded before departure.'];
        }

        $manifest->update([
            'status' => TransportManifest::STATUS_IN_TRANSIT,
            'dispatched_at' => $manifest->dispatched_at ?? now(),
        ]);

        $manifest->containers()
            ->where('status', TransportContainer::STATUS_LOADED)
            ->update(['status' => TransportContainer::STATUS_IN_TRANSIT]);

        return [
            'success' => true,
            'message' => 'Manifest departed successfully.',
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
        ];
    }

    public function ensureDefaultContainer(TransportManifest $manifest, ?User $user = null): TransportContainer
    {
        $existing = $manifest->containers()->orderBy('sequence_number')->first();
        if ($existing) {
            return $existing;
        }

        $manifest->loadMissing('items.shipmentItem');

        $container = TransportContainer::query()->create([
            'transport_manifest_id' => $manifest->id,
            'container_code' => $this->generateContainerCode($manifest, 1),
            'container_type' => 'loose',
            'sequence_number' => 1,
            'status' => TransportContainer::STATUS_SEALED,
            'expected_package_count' => $manifest->items->count(),
            'sealed_at' => now(),
            'sealed_by_user_id' => $user?->id ?? $manifest->created_by_user_id,
            'notes' => 'Default loose-items container.',
        ]);

        foreach ($manifest->items as $line) {
            TransportContainerItem::query()->create([
                'transport_container_id' => $container->id,
                'transport_manifest_item_id' => $line->id,
                'shipment_item_id' => $line->shipment_item_id,
                'label_barcode' => $line->shipmentItem?->tracking_code,
                'expected_quantity' => (int) $line->expected_quantity,
                'status' => TransportContainerItem::STATUS_PACKED,
            ]);
        }

        return $container->fresh('items.manifestItem');
    }

    private function markContainerLoaded(TransportContainer $container, Driver $driver): array
    {
        if ($container->status === TransportContainer::STATUS_LOADED) {
            return [
                'success' => true,
                'message' => 'Container is already loaded.',
            ];
        }

        $now = now();
        foreach ($container->items as $containerItem) {
            $line = $containerItem->manifestItem;
            if (!$line) {
                continue;
            }

            $line->update([
                'scan_out_count' => max((int) $line->scan_out_count, 1),
                'loaded_quantity' => (int) $line->expected_quantity,
                'loaded_at' => $line->loaded_at ?? $now,
                'line_status' => TransportManifestItem::LINE_LOADED,
            ]);

            $containerItem->update(['status' => TransportContainerItem::STATUS_LOADED]);
        }

        $container->update([
            'status' => TransportContainer::STATUS_LOADED,
            'loaded_at' => $container->loaded_at ?? $now,
            'loaded_by_driver_id' => $driver->id,
        ]);

        return [
            'success' => true,
            'message' => 'Container loaded successfully.',
        ];
    }

    private function syncLineContainerLoadState(TransportManifestItem $line): void
    {
        $containerItem = TransportContainerItem::query()
            ->where('transport_manifest_item_id', $line->id)
            ->with('container.items.manifestItem')
            ->first();

        if (!$containerItem?->container) {
            return;
        }

        $containerItem->update(['status' => TransportContainerItem::STATUS_LOADED]);

        $allLoaded = $containerItem->container->items->every(function (TransportContainerItem $item) {
            $manifestLine = $item->manifestItem;

            return $manifestLine
                && (int) $manifestLine->loaded_quantity >= (int) $manifestLine->expected_quantity;
        });

        if ($allLoaded) {
            $containerItem->container->update([
                'status' => TransportContainer::STATUS_LOADED,
                'loaded_at' => $containerItem->container->loaded_at ?? now(),
            ]);
            $containerItem->container->items()->update(['status' => TransportContainerItem::STATUS_LOADED]);
        }
    }

    private function generateContainerCode(TransportManifest $manifest, int $sequence): string
    {
        return preg_replace('/[^A-Z0-9-]/', '', strtoupper($manifest->manifest_number)) . '-C' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
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
