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
use App\Models\TransportLoadingException;
use App\Models\TransportManifestItem;
use App\Models\TransportManifestLabelScan;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseTransportService
{
    public function __construct(private RecipientPaymentService $recipientPaymentService) {}

    public function outboundQuery(Warehouse $warehouse): Builder
    {
        return TransportManifest::query()
            ->withCount('items')
            ->with([
                'destinationWarehouse:id,name,code',
                'assignedDriver:id,name,phone,vehicle_type,vehicle_number',
                'sortBatch:id,batch_number,status',
                'createdBy:id,name',
                'items:id,transport_manifest_id,shipment_item_id,expected_quantity,loaded_quantity,received_quantity,line_status',
            ])
            ->where('origin_warehouse_id', $warehouse->id);
    }

    public function incomingQuery(Warehouse $warehouse): Builder
    {
        return TransportManifest::query()
            ->withCount('items')
            ->with([
                'originWarehouse:id,name,code',
                'destinationWarehouse:id,name,code',
                'assignedDriver:id,name,phone,vehicle_type,vehicle_number',
                'warehouseReceipt:id,transport_manifest_id,status,started_at,finalized_at',
                'items:id,transport_manifest_id,shipment_item_id,expected_quantity,loaded_quantity,received_quantity,line_status',
            ])
            ->where('destination_warehouse_id', $warehouse->id);
    }

    public function manifestTimeline(TransportManifest $manifest): array
    {
        $manifest->loadMissing([
            'createdBy:id,name',
            'receivedBy:id,name',
            'assignedDriver:id,name,phone',
            'items:id,transport_manifest_id,shipment_item_id,expected_quantity,loaded_quantity,loaded_at,received_at,line_status',
            'assignments.driver:id,name,phone',
            'assignments.assignedBy:id,name',
            'assignments.unassignedBy:id,name',
            'warehouseReceipt.startedBy:id,name',
            'warehouseReceipt.finalizedBy:id,name',
        ]);

        $events = collect();
        $addEvent = function (string $type, string $label, mixed $at, ?string $actor = null, ?string $detail = null, string $tone = 'slate') use (&$events): void {
            if (!$at) {
                return;
            }

            $events->push([
                'type' => $type,
                'label' => $label,
                'at' => $at,
                'at_label' => $at->format('d M Y, h:i A'),
                'actor' => $actor,
                'detail' => $detail,
                'tone' => $tone,
            ]);
        };

        $addEvent(
            'created',
            'Created',
            $manifest->created_at,
            $manifest->createdBy?->name,
            'Outgoing transfer created.',
            'slate'
        );

        $manifest->assignments
            ->sortBy('assigned_at')
            ->each(function ($assignment) use ($addEvent) {
                $driver = trim(implode(' / ', array_filter([
                    $assignment->driver?->name,
                    $assignment->driver?->phone,
                ])));

                $addEvent(
                    'driver_assigned',
                    'Driver Assigned',
                    $assignment->assigned_at,
                    $assignment->assignedBy?->name,
                    $driver !== '' ? $driver : null,
                    'blue'
                );

                $addEvent(
                    'driver_unassigned',
                    'Driver Unassigned',
                    $assignment->unassigned_at,
                    $assignment->unassignedBy?->name,
                    $assignment->unassign_reason,
                    'amber'
                );
            });

        $loadedLines = $manifest->items->filter(fn (TransportManifestItem $line) => (bool) $line->loaded_at);
        if ($loadedLines->isNotEmpty()) {
            $loadedCount = $loadedLines->count();
            $expectedCount = $manifest->items->count();
            $loadedQty = (int) $manifest->items->sum('loaded_quantity');

            $addEvent(
                'loading_started',
                'Loading Started',
                $loadedLines->min('loaded_at'),
                null,
                "{$loadedCount} of {$expectedCount} lines loaded.",
                'purple'
            );

            $allLoaded = $expectedCount > 0 && $manifest->items->every(
                fn (TransportManifestItem $line) => (int) $line->loaded_quantity >= (int) $line->expected_quantity
                    && $line->line_status === TransportManifestItem::LINE_LOADED
            );

            if ($allLoaded) {
                $addEvent(
                    'all_loaded',
                    'All Loaded',
                    $loadedLines->max('loaded_at'),
                    null,
                    "{$loadedQty} packages loaded.",
                    'emerald'
                );
            }
        }

        $trackingEvents = $this->manifestTrackingEvents($manifest);
        $eventActors = $this->trackingEventActors($trackingEvents);

        $addEvent(
            'dispatched',
            'Dispatched',
            $manifest->dispatched_at,
            $eventActors['dispatched'] ?? null,
            'Transfer left the origin warehouse.',
            'amber'
        );

        $trackingEvents
            ->filter(fn (ShipmentItemTracking $tracking) => data_get($tracking->meta, 'event') === 'dispatch_reversed')
            ->groupBy(fn (ShipmentItemTracking $tracking) => $tracking->created_at?->timestamp . '|' . ($tracking->created_by ?? ''))
            ->each(function (Collection $group) use ($addEvent, $eventActors) {
                $tracking = $group->first();
                $addEvent(
                    'dispatch_reversed',
                    'Dispatch Reversed',
                    $tracking->created_at,
                    $eventActors['dispatch_reversed:' . $tracking->created_at?->timestamp] ?? $eventActors['dispatch_reversed'] ?? null,
                    'Transfer returned to origin workflow.',
                    'amber'
                );
            });

        $addEvent(
            'arrived',
            'Arrived',
            $manifest->arrived_at,
            $eventActors['admin_marked_arrived'] ?? null,
            'Transfer reached the destination warehouse.',
            'cyan'
        );

        $trackingEvents
            ->filter(fn (ShipmentItemTracking $tracking) => data_get($tracking->meta, 'event') === 'arrival_reversed')
            ->groupBy(fn (ShipmentItemTracking $tracking) => $tracking->created_at?->timestamp . '|' . ($tracking->created_by ?? ''))
            ->each(function (Collection $group) use ($addEvent, $eventActors) {
                $tracking = $group->first();
                $addEvent(
                    'arrival_reversed',
                    'Arrival Reversed',
                    $tracking->created_at,
                    $eventActors['arrival_reversed:' . $tracking->created_at?->timestamp] ?? $eventActors['arrival_reversed'] ?? null,
                    'Transfer moved back to in transit.',
                    'cyan'
                );
            });

        $receipt = $manifest->warehouseReceipt;
        if ($receipt) {
            $addEvent(
                'receiving_started',
                'Receiving Started',
                $receipt->started_at,
                $receipt->startedBy?->name,
                'Destination warehouse started receiving.',
                'emerald'
            );

            $addEvent(
                'received',
                'Received',
                $manifest->received_at ?? $receipt->finalized_at,
                $manifest->receivedBy?->name ?? $receipt->finalizedBy?->name,
                'Destination warehouse completed receiving.',
                'emerald'
            );
        }

        return $events
            ->filter(fn (array $event) => (bool) $event['at'])
            ->sortBy(fn (array $event) => $event['at']->timestamp)
            ->values()
            ->map(function (array $event) {
                unset($event['at']);

                return $event;
            })
            ->all();
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

    public function createDraftManifest(Warehouse $warehouse, User $user): array
    {
        return DB::transaction(function () use ($warehouse, $user) {
            $manifest = TransportManifest::query()->create([
                'manifest_number' => $this->generateManifestNumber($warehouse, null),
                'origin_warehouse_id' => $warehouse->id,
                'status' => TransportManifest::STATUS_DRAFT,
                'created_by_user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Outgoing transfer created successfully.',
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

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Only assigned or loaded manifests can be dispatched.'];
        }

        if (!$manifest->assigned_driver_id) {
            return ['success' => false, 'message' => 'Assign a driver before dispatching.'];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user) {
            $lockedManifest = TransportManifest::query()
                ->with(['items.shipmentItem.shipment'])
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $hasUnloadedItems = $lockedManifest->items->contains(function (TransportManifestItem $line) {
                return (int) $line->loaded_quantity < (int) $line->expected_quantity
                    || $line->line_status !== TransportManifestItem::LINE_LOADED;
            });

            if ($hasUnloadedItems) {
                return ['success' => false, 'message' => 'Mark all manifest items as loaded before dispatching.'];
            }

            $now = now();
            $lockedManifest->update([
                'status' => TransportManifest::STATUS_IN_TRANSIT,
                'dispatched_at' => $now,
            ]);

            $lockedManifest->containers()
                ->where('status', TransportContainer::STATUS_LOADED)
                ->update(['status' => TransportContainer::STATUS_IN_TRANSIT]);

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

    public function undoDispatch(TransportManifest $manifest, Warehouse $warehouse, User $user, ?string $reason = null): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot undo dispatch for another warehouse manifest.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_IN_TRANSIT || !$manifest->dispatched_at) {
            return ['success' => false, 'message' => 'Only dispatched transfers can be reverted.'];
        }

        if ($manifest->arrived_at || $manifest->received_at || $this->manifestReceivingHasStarted($manifest)) {
            return ['success' => false, 'message' => 'Receiving has already started. Undo receiving before reverting dispatch.'];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user, $reason) {
            $lockedManifest = TransportManifest::query()
                ->with(['items.shipmentItem.shipment', 'warehouseReceipt'])
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            if ($lockedManifest->status !== TransportManifest::STATUS_IN_TRANSIT || !$lockedManifest->dispatched_at) {
                return ['success' => false, 'message' => 'Only dispatched transfers can be reverted.'];
            }

            if ($lockedManifest->arrived_at || $lockedManifest->received_at || $this->manifestReceivingHasStarted($lockedManifest)) {
                return ['success' => false, 'message' => 'Receiving has already started. Undo receiving before reverting dispatch.'];
            }

            $now = now();
            $lockedManifest->update([
                'status' => TransportManifest::STATUS_ASSIGNED,
                'dispatched_at' => null,
            ]);

            $lockedManifest->containers()
                ->where('status', TransportContainer::STATUS_IN_TRANSIT)
                ->update(['status' => TransportContainer::STATUS_LOADED]);

            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();
            $noteReason = filled($reason) ? ' Reason: ' . trim((string) $reason) : '';

            foreach ($lockedManifest->items as $line) {
                $item = $line->shipmentItem;
                if (!$item) {
                    continue;
                }

                if ($this->statusValue($item->status) === ItemStatus::IN_TRANSIT->value) {
                    $item->update(['status' => ItemStatus::AT_WAREHOUSE]);
                }

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::AT_WAREHOUSE->value,
                    'location' => $warehouse->name,
                    'notes' => 'Dispatch reversed for transport manifest ' . $lockedManifest->manifest_number . '.' . $noteReason,
                    'meta' => [
                        'transport_manifest_id' => $lockedManifest->id,
                        'transport_manifest_number' => $lockedManifest->manifest_number,
                        'event' => 'dispatch_reversed',
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => $now,
                ]);

                if ($item->shipment) {
                    $shipments->push($item->shipment);
                }
            }

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentWarehouseStatus($shipment);
            });

            return [
                'success' => true,
                'message' => 'Dispatch reverted. The transfer is back at this warehouse and can be reassigned or dispatched later.',
                'data' => ['manifest' => $lockedManifest->fresh(['items.shipmentItem', 'assignedDriver'])],
            ];
        });
    }

    public function undoArrival(TransportManifest $manifest, Warehouse $warehouse, User $user, ?string $reason = null): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot undo arrival for another warehouse manifest.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_ARRIVED || !$manifest->arrived_at) {
            return ['success' => false, 'message' => 'Only arrived transfers can be reverted.'];
        }

        if ($manifest->received_at || $this->manifestReceivingHasStarted($manifest)) {
            return ['success' => false, 'message' => 'Receiving has already started. This arrival cannot be reverted.'];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user, $reason) {
            $lockedManifest = TransportManifest::query()
                ->with(['items.shipmentItem', 'warehouseReceipt'])
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            if ($lockedManifest->status !== TransportManifest::STATUS_ARRIVED || !$lockedManifest->arrived_at) {
                return ['success' => false, 'message' => 'Only arrived transfers can be reverted.'];
            }

            if ($lockedManifest->received_at || $this->manifestReceivingHasStarted($lockedManifest)) {
                return ['success' => false, 'message' => 'Receiving has already started. This arrival cannot be reverted.'];
            }

            $now = now();
            $noteReason = filled($reason) ? ' Reason: ' . trim((string) $reason) : '';

            $lockedManifest->update([
                'status' => TransportManifest::STATUS_IN_TRANSIT,
                'arrived_at' => null,
            ]);

            foreach ($lockedManifest->items as $line) {
                $item = $line->shipmentItem;
                if (!$item) {
                    continue;
                }

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::IN_TRANSIT->value,
                    'location' => $lockedManifest->originWarehouse?->name ?? $warehouse->name,
                    'notes' => 'Arrival reversed for transport manifest ' . $lockedManifest->manifest_number . '.' . $noteReason,
                    'meta' => [
                        'transport_manifest_id' => $lockedManifest->id,
                        'transport_manifest_number' => $lockedManifest->manifest_number,
                        'event' => 'arrival_reversed',
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => $now,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Arrival reverted. The transfer is back in transit.',
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
                if ($this->isLooseTransportContainer($container)) {
                    return [
                        'success' => false,
                        'message' => 'Loose items must be loaded by scanning each package label.',
                    ];
                }

                return $this->markContainerLoaded($container, $driver);
            }

            $label = WarehouseReceiptItemLabel::query()
                ->with('receiptItem:id,shipment_item_id')
                ->where('barcode_value', $trackingCode)
                ->lockForUpdate()
                ->first();

            $baseCodeLine = TransportManifestItem::query()
                ->where('transport_manifest_id', $manifest->id)
                ->whereHas('shipmentItem', function (Builder $query) use ($trackingCode) {
                    $query->where('tracking_code', $trackingCode);
                })
                ->lockForUpdate()
                ->first();

            if (!$label) {
                if ($baseCodeLine) {
                    return [
                        'success' => false,
                        'message' => 'Scan the printed package label barcode, not the package tracking code.',
                    ];
                }

                return ['success' => false, 'message' => 'Label code not found in this manifest.'];
            }

            $shipmentItemId = $label->receiptItem?->shipment_item_id;
            if (!$shipmentItemId) {
                return ['success' => false, 'message' => 'Label is not linked to a package item.'];
            }

            $line = TransportManifestItem::query()
                ->where('transport_manifest_id', $manifest->id)
                ->where('shipment_item_id', $shipmentItemId)
                ->lockForUpdate()
                ->first();

            if (!$line) {
                return ['success' => false, 'message' => 'Label code not found in this manifest.'];
            }

            $containerItem = TransportContainerItem::query()
                ->where('transport_manifest_item_id', $line->id)
                ->with('container.items.manifestItem')
                ->first();

            if ($containerItem?->container && !$this->isLooseTransportContainer($containerItem->container)) {
                return [
                    'success' => false,
                    'message' => 'This package is packed in ' . $containerItem->container->container_code . '. Scan the load group label instead.',
                ];
            }

            $existingScan = TransportManifestLabelScan::query()
                ->where('transport_manifest_id', $manifest->id)
                ->where('warehouse_receipt_item_label_id', $label->id)
                ->first();

            if (!$existingScan) {
                TransportManifestLabelScan::query()->create([
                    'transport_manifest_id' => $manifest->id,
                    'transport_manifest_item_id' => $line->id,
                    'warehouse_receipt_item_label_id' => $label->id,
                    'driver_id' => $driver->id,
                    'barcode_value' => $label->barcode_value,
                    'scanned_at' => now(),
                ]);
            }

            $scannedCount = TransportManifestLabelScan::query()
                ->where('transport_manifest_item_id', $line->id)
                ->count();
            $printedLabelCount = WarehouseReceiptItemLabel::query()
                ->whereHas('receiptItem', function (Builder $query) use ($line) {
                    $query->where('shipment_item_id', $line->shipment_item_id);
                })
                ->count();
            $expectedScanCount = max($printedLabelCount, 1);
            $expectedQuantity = max((int) $line->expected_quantity, 1);
            $isFullyLoaded = $scannedCount >= $expectedScanCount;
            $loadedCount = $isFullyLoaded
                ? $expectedQuantity
                : ($expectedScanCount === $expectedQuantity ? min($scannedCount, $expectedQuantity) : 0);

            $line->update([
                'scan_out_count' => $scannedCount,
                'loaded_quantity' => $loadedCount,
                'loaded_at' => $isFullyLoaded ? ($line->loaded_at ?? now()) : null,
                'line_status' => $isFullyLoaded ? TransportManifestItem::LINE_LOADED : TransportManifestItem::LINE_PENDING,
            ]);

            if ($containerItem?->container) {
                if ($isFullyLoaded) {
                    $this->syncLineContainerLoadState($line);

                    if ($containerItem->container->fresh()?->status === TransportContainer::STATUS_LOADED) {
                        $containerItem->container->update([
                            'loaded_by_driver_id' => $driver->id,
                        ]);
                    }
                } else {
                    $containerItem->update(['status' => TransportContainerItem::STATUS_PACKED]);
                    $containerItem->container->update([
                        'status' => TransportContainer::STATUS_SEALED,
                        'loaded_at' => null,
                        'loaded_by_driver_id' => null,
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => $isFullyLoaded
                    ? 'Package labels loaded successfully.'
                    : "Label loaded. {$scannedCount}/{$expectedScanCount} labels scanned.",
            ];
        });
    }

    public function driverReportScanIssue(TransportManifest $manifest, Driver $driver, array $data, UploadedFile $proofPhoto): array
    {
        if ((int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Manifest not found.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Manifest is not in loading state.'];
        }

        return DB::transaction(function () use ($manifest, $driver, $data, $proofPhoto) {
            $lockedManifest = TransportManifest::query()
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            if ($lockedManifest->status === TransportManifest::STATUS_ASSIGNED) {
                $lockedManifest->update(['status' => TransportManifest::STATUS_LOADING]);
            }

            $targetType = $data['target_type'];
            $container = null;
            $line = null;

            if ($targetType === 'container') {
                $container = TransportContainer::query()
                    ->where('transport_manifest_id', $lockedManifest->id)
                    ->with('items.manifestItem')
                    ->lockForUpdate()
                    ->whereKey((int) $data['container_id'])
                    ->first();

                if (!$container) {
                    return ['success' => false, 'message' => 'Load group not found on this manifest.'];
                }

                if ($container->items->isEmpty()) {
                    return ['success' => false, 'message' => 'Cannot report loading for an empty load group.'];
                }
            } else {
                $line = TransportManifestItem::query()
                    ->where('transport_manifest_id', $lockedManifest->id)
                    ->with('containerItems.container')
                    ->lockForUpdate()
                    ->whereKey((int) $data['manifest_item_id'])
                    ->first();

                if (!$line) {
                    return ['success' => false, 'message' => 'Manifest item not found on this transport.'];
                }

                $packedContainer = $line->containerItems
                    ->map(fn (TransportContainerItem $item) => $item->container)
                    ->filter()
                    ->first();

                if ($packedContainer && !$this->isLooseTransportContainer($packedContainer)) {
                    return [
                        'success' => false,
                        'message' => 'This package is packed in ' . $packedContainer->container_code . '. Report the scan issue on the load group instead.',
                    ];
                }
            }

            $autoAccept = (bool) PlatformSetting::getValue('transport.scan_issue_auto_accept', false);
            $path = $proofPhoto->store('transport-loading-exceptions/' . $lockedManifest->id, 'public');
            $now = now();

            $exception = TransportLoadingException::query()->create([
                'transport_manifest_id' => $lockedManifest->id,
                'transport_container_id' => $container?->id,
                'transport_manifest_item_id' => $line?->id,
                'driver_id' => $driver->id,
                'reason' => $data['reason'],
                'note' => $data['note'] ?? null,
                'proof_photo_path' => $path,
                'status' => $autoAccept ? TransportLoadingException::STATUS_ACCEPTED : TransportLoadingException::STATUS_PENDING,
                'auto_accepted' => $autoAccept,
                'reviewed_at' => $autoAccept ? $now : null,
                'admin_note' => $autoAccept ? 'Auto-accepted by platform setting.' : null,
            ]);

            if ($autoAccept) {
                if ($container) {
                    $this->markContainerLoadedFromException($container, $driver, $exception);
                } elseif ($line) {
                    $this->markLineLoadedFromException($line, $driver, $exception);
                }
            }

            return [
                'success' => true,
                'message' => $autoAccept
                    ? 'Scan issue accepted and load marked.'
                    : 'Scan issue submitted for admin review.',
                'data' => [
                    'scan_issue' => $exception->fresh(['container', 'manifestItem.shipmentItem', 'driver']),
                    'auto_accepted' => $autoAccept,
                ],
            ];
        });
    }

    public function adminReviewLoadingException(
        TransportManifest $manifest,
        TransportLoadingException $exception,
        Warehouse $warehouse,
        User $user,
        bool $accept,
        ?string $adminNote = null
    ): array {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot review another warehouse manifest.'];
        }

        if ((int) $exception->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Scan issue not found on this manifest.'];
        }

        return DB::transaction(function () use ($manifest, $exception, $user, $accept, $adminNote) {
            $lockedException = TransportLoadingException::query()
                ->with(['container.items.manifestItem', 'manifestItem', 'driver'])
                ->lockForUpdate()
                ->findOrFail($exception->id);

            if ($lockedException->status !== TransportLoadingException::STATUS_PENDING) {
                return ['success' => false, 'message' => 'This scan issue has already been reviewed.'];
            }

            if ($accept) {
                $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);
                if ($lockedManifest->status === TransportManifest::STATUS_ASSIGNED) {
                    $lockedManifest->update(['status' => TransportManifest::STATUS_LOADING]);
                }

                if ($lockedException->container) {
                    $this->markContainerLoadedFromException($lockedException->container, $lockedException->driver, $lockedException);
                } elseif ($lockedException->manifestItem) {
                    $this->markLineLoadedFromException($lockedException->manifestItem, $lockedException->driver, $lockedException);
                }
            }

            $lockedException->update([
                'status' => $accept ? TransportLoadingException::STATUS_ACCEPTED : TransportLoadingException::STATUS_REJECTED,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
            ]);

            return [
                'success' => true,
                'message' => $accept ? 'Scan issue accepted and load marked.' : 'Scan issue rejected.',
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

    public function adminMarkItemNotLoaded(TransportManifest $manifest, TransportManifestItem $line, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if ((int) $line->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Item not found on this manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Items can only be marked not loaded before departure.'];
        }

        if ((int) $line->received_quantity > 0 || $line->received_at) {
            return ['success' => false, 'message' => 'Cannot undo loading after this item has been received.'];
        }

        return DB::transaction(function () use ($manifest, $line, $user) {
            TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);

            $lockedLine = TransportManifestItem::query()
                ->where('transport_manifest_id', $manifest->id)
                ->lockForUpdate()
                ->findOrFail($line->id);

            if ((int) $lockedLine->received_quantity > 0 || $lockedLine->received_at) {
                return ['success' => false, 'message' => 'Cannot undo loading after this item has been received.'];
            }

            TransportManifestLabelScan::query()
                ->where('transport_manifest_item_id', $lockedLine->id)
                ->delete();

            $lockedLine->update([
                'scan_out_count' => 0,
                'loaded_quantity' => 0,
                'loaded_at' => null,
                'line_status' => TransportManifestItem::LINE_PENDING,
                'notes' => trim(implode("\n", array_filter([
                    $lockedLine->notes,
                    'Marked not loaded by admin ' . $user->name . '.',
                ]))),
            ]);

            $this->syncLineContainerUnloadState($lockedLine);

            return [
                'success' => true,
                'message' => 'Manifest item marked as not loaded.',
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

    public function adminMarkAllItemsNotLoaded(TransportManifest $manifest, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Items can only be marked not loaded before departure.'];
        }

        return DB::transaction(function () use ($manifest, $user) {
            $lockedManifest = TransportManifest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $updated = 0;
            foreach ($lockedManifest->items as $line) {
                if ((int) $line->received_quantity > 0 || $line->received_at) {
                    continue;
                }

                if (
                    (int) $line->loaded_quantity === 0
                    && (int) $line->scan_out_count === 0
                    && !$line->loaded_at
                    && $line->line_status === TransportManifestItem::LINE_PENDING
                ) {
                    continue;
                }

                TransportManifestLabelScan::query()
                    ->where('transport_manifest_item_id', $line->id)
                    ->delete();

                $line->update([
                    'scan_out_count' => 0,
                    'loaded_quantity' => 0,
                    'loaded_at' => null,
                    'line_status' => TransportManifestItem::LINE_PENDING,
                    'notes' => trim(implode("\n", array_filter([
                        $line->notes,
                        'Marked not loaded by admin ' . $user->name . '.',
                    ]))),
                ]);

                $this->syncLineContainerUnloadState($line);
                $updated++;
            }

            $allPending = $lockedManifest->items()->where(function (Builder $query) {
                $query->where('loaded_quantity', '>', 0)
                    ->orWhere('scan_out_count', '>', 0)
                    ->orWhereNotNull('loaded_at')
                    ->orWhere('line_status', '!=', TransportManifestItem::LINE_PENDING);
            })->doesntExist();

            if ($allPending) {
                $lockedManifest->update([
                    'status' => TransportManifest::STATUS_ASSIGNED,
                    'dispatched_at' => null,
                ]);
            }

            return [
                'success' => true,
                'message' => $updated === 1
                    ? '1 manifest item marked as not loaded.'
                    : "{$updated} manifest items marked as not loaded.",
                'data' => ['updated_count' => $updated],
            ];
        });
    }

    public function adminMarkArrived(TransportManifest $manifest, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot update another warehouse manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_LOADING, TransportManifest::STATUS_IN_TRANSIT], true)) {
            return ['success' => false, 'message' => 'Only loaded or in-transit manifests can be marked arrived.'];
        }

        if (!$manifest->dispatched_at) {
            return ['success' => false, 'message' => 'Dispatch the transfer before marking it arrived.'];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user) {
            $lockedManifest = TransportManifest::query()
                ->with(['items.shipmentItem.shipment'])
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $notLoadedCount = $lockedManifest->items->filter(function (TransportManifestItem $line) {
                return (int) $line->loaded_quantity < (int) $line->expected_quantity
                    || $line->line_status !== TransportManifestItem::LINE_LOADED;
            })->count();

            if ($notLoadedCount > 0) {
                return ['success' => false, 'message' => 'Mark all manifest items as loaded before marking arrival.'];
            }

            $now = now();
            $lockedManifest->update([
                'status' => TransportManifest::STATUS_ARRIVED,
                'arrived_at' => $now,
            ]);

            $lockedManifest->containers()
                ->whereIn('status', [TransportContainer::STATUS_LOADED, TransportContainer::STATUS_SEALED])
                ->update(['status' => TransportContainer::STATUS_IN_TRANSIT]);

            foreach ($lockedManifest->items as $line) {
                $item = $line->shipmentItem;
                if (!$item) {
                    continue;
                }

                $item->update(['status' => ItemStatus::IN_TRANSIT]);

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::IN_TRANSIT->value,
                    'location' => $lockedManifest->destinationWarehouse?->name ?? $warehouse->name,
                    'notes' => 'Transport manifest ' . $lockedManifest->manifest_number . ' marked arrived by admin ' . $user->name . '.',
                    'meta' => [
                        'transport_manifest_id' => $lockedManifest->id,
                        'transport_manifest_number' => $lockedManifest->manifest_number,
                        'event' => 'admin_marked_arrived',
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => $now,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Transport manifest marked as arrived.',
                'data' => ['manifest' => $lockedManifest->fresh(['items.shipmentItem', 'assignedDriver'])],
            ];
        });
    }

    public function createContainer(TransportManifest $manifest, Warehouse $warehouse, User $user, string $containerType = 'box', ?string $notes = null, ?SortBatch $sortBatch = null): array
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

        try {
            return DB::transaction(function () use ($manifest, $warehouse, $user, $containerType, $notes, $sortBatch) {
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

            if ($sortBatch) {
                $attachResult = $this->attachSortBatchToContainer($lockedManifest, $container, $sortBatch, $warehouse, $user);
                if (!$attachResult['success']) {
                    throw new \RuntimeException($attachResult['message'] ?? 'Unable to attach sort batch.');
                }

                return [
                    'success' => true,
                    'message' => 'Container created and sort batch loaded.',
                    'data' => ['container' => $container->fresh('items.manifestItem.shipmentItem')],
                ];
            }

            return [
                'success' => true,
                'message' => 'Transport container created.',
                'data' => ['container' => $container],
            ];
            });
        } catch (\RuntimeException $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    public function attachSortBatchToExistingContainer(
        TransportManifest $manifest,
        TransportContainer $container,
        SortBatch $sortBatch,
        Warehouse $warehouse,
        User $user
    ): array {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Container not found on this outgoing transfer.'];
        }

        if (!in_array($manifest->status, [
            TransportManifest::STATUS_DRAFT,
            TransportManifest::STATUS_ASSIGNED,
            TransportManifest::STATUS_LOADING,
        ], true)) {
            return ['success' => false, 'message' => 'Sort batches can only be attached before transport departure.'];
        }

        return DB::transaction(function () use ($manifest, $container, $sortBatch, $warehouse, $user) {
            $lockedManifest = TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);
            $lockedContainer = TransportContainer::query()
                ->where('transport_manifest_id', $lockedManifest->id)
                ->lockForUpdate()
                ->findOrFail($container->id);

            return $this->attachSortBatchToContainer($lockedManifest, $lockedContainer, $sortBatch, $warehouse, $user);
        });
    }

    public function updateContainerNotes(TransportManifest $manifest, TransportContainer $container, Warehouse $warehouse, ?string $notes): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Container not found on this outgoing transfer.'];
        }

        if (!in_array($manifest->status, [
            TransportManifest::STATUS_DRAFT,
            TransportManifest::STATUS_ASSIGNED,
            TransportManifest::STATUS_LOADING,
        ], true)) {
            return ['success' => false, 'message' => 'Container notes can only be edited before transport departure.'];
        }

        $container->update([
            'notes' => filled($notes) ? trim((string) $notes) : null,
        ]);

        return [
            'success' => true,
            'message' => 'Container notes updated.',
            'data' => ['container' => $container->fresh()],
        ];
    }

    private function attachSortBatchToContainer(
        TransportManifest $manifest,
        TransportContainer $container,
        SortBatch $sortBatch,
        Warehouse $warehouse,
        User $user
    ): array {
        if ((int) $sortBatch->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot attach another warehouse sort batch.'];
        }

        if ($sortBatch->dispatch_mode !== SortBatch::DISPATCH_TRANSFER) {
            return ['success' => false, 'message' => 'Only transfer sort batches can be loaded into outgoing transfers.'];
        }

        if ($sortBatch->status !== SortBatch::STATUS_SEALED) {
            return ['success' => false, 'message' => 'Sort batch must be sealed before it can be loaded.'];
        }

        if (!$sortBatch->destination_warehouse_id) {
            return ['success' => false, 'message' => 'Sort batch needs a destination warehouse.'];
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Container not found on this outgoing transfer.'];
        }

        if ($container->items()->exists()) {
            return ['success' => false, 'message' => 'Attach a sort batch only to an empty container.'];
        }

        $hasManifestItems = $manifest->items()->exists();
        $hasContainerItems = TransportContainerItem::query()
            ->whereHas('container', fn (Builder $query) => $query->where('transport_manifest_id', $manifest->id))
            ->exists();

        if ($hasManifestItems || $hasContainerItems) {
            return ['success' => false, 'message' => 'This outgoing transfer already has packages. Rearrange the existing packages instead of attaching another sort batch.'];
        }

        $existingManifest = $sortBatch->transportManifest()->first();
        if ($existingManifest && (int) $existingManifest->id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'This sort batch is already attached to another outgoing transfer.'];
        }

        $sortBatch = SortBatch::query()
            ->with(['activeItems.shipmentItem', 'destinationWarehouse'])
            ->lockForUpdate()
            ->findOrFail($sortBatch->id);

        $activeItems = $sortBatch->activeItems;
        if ($activeItems->isEmpty()) {
            return ['success' => false, 'message' => 'Cannot load an empty sort batch.'];
        }

        if (
            $manifest->destination_warehouse_id
            && (int) $manifest->destination_warehouse_id !== (int) $sortBatch->destination_warehouse_id
        ) {
            return ['success' => false, 'message' => 'This outgoing transfer already points to another destination warehouse.'];
        }

        $manifestUpdates = [
            'sort_batch_id' => $sortBatch->id,
            'destination_warehouse_id' => $sortBatch->destination_warehouse_id,
        ];

        $renumberedManifest = str_contains((string) $manifest->manifest_number, '-DRAFT-');

        if ($renumberedManifest) {
            $manifestUpdates['manifest_number'] = $this->generateManifestNumber($warehouse, $sortBatch->destinationWarehouse);
        }

        $manifest->update($manifestUpdates);
        $manifest->refresh();

        if ($renumberedManifest && str_contains((string) $container->container_code, '-DRAFT-')) {
            $container->update([
                'container_code' => $this->generateContainerCode($manifest, (int) $container->sequence_number),
            ]);
        }

        foreach ($activeItems as $batchItem) {
            $line = TransportManifestItem::query()->create([
                'transport_manifest_id' => $manifest->id,
                'shipment_item_id' => $batchItem->shipment_item_id,
                'expected_quantity' => (int) $batchItem->quantity_allocated,
                'line_status' => TransportManifestItem::LINE_PENDING,
            ]);

            TransportContainerItem::query()->create([
                'transport_container_id' => $container->id,
                'transport_manifest_item_id' => $line->id,
                'shipment_item_id' => $line->shipment_item_id,
                'label_barcode' => $batchItem->shipmentItem?->tracking_code,
                'expected_quantity' => (int) $line->expected_quantity,
                'status' => TransportContainerItem::STATUS_PACKED,
            ]);
        }

        $container->update([
            'status' => TransportContainer::STATUS_SEALED,
            'expected_package_count' => $activeItems->count(),
            'sealed_at' => $container->sealed_at ?? now(),
            'sealed_by_user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'message' => 'Sort batch loaded into container.',
            'data' => [
                'container' => $container->fresh('items.manifestItem.shipmentItem'),
                'manifest' => $manifest->fresh(['sortBatch', 'destinationWarehouse', 'items']),
            ],
        ];
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

    public function adminMarkContainerNotLoaded(TransportManifest $manifest, TransportContainer $container, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot modify another warehouse manifest.'];
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return ['success' => false, 'message' => 'Load group not found on this manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING], true)) {
            return ['success' => false, 'message' => 'Load groups can only be marked not loaded before departure.'];
        }

        return DB::transaction(function () use ($manifest, $container, $user) {
            TransportManifest::query()->lockForUpdate()->findOrFail($manifest->id);

            $lockedContainer = TransportContainer::query()
                ->with('items.manifestItem')
                ->where('transport_manifest_id', $manifest->id)
                ->lockForUpdate()
                ->findOrFail($container->id);

            foreach ($lockedContainer->items as $containerItem) {
                $line = $containerItem->manifestItem;
                if (!$line) {
                    continue;
                }

                if ((int) $line->received_quantity > 0 || $line->received_at) {
                    return ['success' => false, 'message' => 'Cannot undo loading because one or more items have already been received.'];
                }
            }

            foreach ($lockedContainer->items as $containerItem) {
                $line = $containerItem->manifestItem;
                if (!$line) {
                    continue;
                }

                TransportManifestLabelScan::query()
                    ->where('transport_manifest_item_id', $line->id)
                    ->delete();

                $line->update([
                    'scan_out_count' => 0,
                    'loaded_quantity' => 0,
                    'loaded_at' => null,
                    'line_status' => TransportManifestItem::LINE_PENDING,
                    'notes' => trim(implode("\n", array_filter([
                        $line->notes,
                        'Load group ' . $lockedContainer->container_code . ' marked not loaded by admin ' . $user->name . '.',
                    ]))),
                ]);

                $containerItem->update(['status' => TransportContainerItem::STATUS_PACKED]);
            }

            $lockedContainer->update([
                'status' => TransportContainer::STATUS_SEALED,
                'loaded_at' => null,
                'loaded_by_driver_id' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Load group marked as not loaded.',
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

    /**
     * @return array{deletable: bool, reason: ?string}
     */
    public function deleteState(TransportManifest $manifest): array
    {
        if ($manifest->status !== TransportManifest::STATUS_DRAFT) {
            return [
                'deletable' => false,
                'reason' => 'Only draft manifests can be deleted.',
            ];
        }

        if ($manifest->assigned_driver_id || $manifest->assigned_at) {
            return [
                'deletable' => false,
                'reason' => 'Unassign the driver before deleting this manifest.',
            ];
        }

        if ($manifest->dispatched_at || $manifest->arrived_at || $manifest->received_at) {
            return [
                'deletable' => false,
                'reason' => 'This manifest has transport activity and cannot be deleted.',
            ];
        }

        if ($manifest->warehouseReceipt()->exists()) {
            return [
                'deletable' => false,
                'reason' => 'This manifest already has an incoming warehouse receipt.',
            ];
        }

        if ($manifest->loadingExceptions()->exists()) {
            return [
                'deletable' => false,
                'reason' => 'This manifest has driver loading exceptions and cannot be deleted.',
            ];
        }

        if ($manifest->labelScans()->exists()) {
            return [
                'deletable' => false,
                'reason' => 'This manifest has driver label scans and cannot be deleted.',
            ];
        }

        $hasLoadedLine = $manifest->items()
            ->where(function (Builder $query) {
                $query->where('loaded_quantity', '>', 0)
                    ->orWhere('received_quantity', '>', 0)
                    ->orWhere('scan_out_count', '>', 0)
                    ->orWhere('scan_in_count', '>', 0)
                    ->orWhereNotNull('loaded_at')
                    ->orWhereNotNull('received_at')
                    ->orWhere('line_status', '!=', TransportManifestItem::LINE_PENDING);
            })
            ->exists();

        if ($hasLoadedLine) {
            return [
                'deletable' => false,
                'reason' => 'This manifest has loaded or received items and cannot be deleted.',
            ];
        }

        $hasMovedContainer = $manifest->containers()
            ->where(function (Builder $query) {
                $query->whereIn('status', [
                    TransportContainer::STATUS_LOADED,
                    TransportContainer::STATUS_IN_TRANSIT,
                    TransportContainer::STATUS_RECEIVED,
                    TransportContainer::STATUS_RECONCILED,
                    TransportContainer::STATUS_DAMAGED,
                    TransportContainer::STATUS_MISSING,
                ])
                    ->orWhereNotNull('loaded_at')
                    ->orWhereNotNull('received_at');
            })
            ->exists();

        if ($hasMovedContainer) {
            return [
                'deletable' => false,
                'reason' => 'This manifest has loaded or received containers and cannot be deleted.',
            ];
        }

        $hasChangedContainerItem = TransportContainerItem::query()
            ->whereHas('container', fn (Builder $query) => $query->where('transport_manifest_id', $manifest->id))
            ->where('status', '!=', TransportContainerItem::STATUS_PACKED)
            ->exists();

        if ($hasChangedContainerItem) {
            return [
                'deletable' => false,
                'reason' => 'This manifest has container items that have already moved.',
            ];
        }

        return [
            'deletable' => true,
            'reason' => null,
        ];
    }

    public function deleteManifest(TransportManifest $manifest, Warehouse $warehouse, User $user): array
    {
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot delete another warehouse manifest.'];
        }

        $deleteState = $this->deleteState($manifest);
        if (!$deleteState['deletable']) {
            return [
                'success' => false,
                'message' => $deleteState['reason'] ?? 'This manifest cannot be deleted.',
            ];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user) {
            $lockedManifest = TransportManifest::query()
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $deleteState = $this->deleteState($lockedManifest);
            if (!$deleteState['deletable']) {
                return [
                    'success' => false,
                    'message' => $deleteState['reason'] ?? 'This manifest cannot be deleted.',
                ];
            }

            $manifestNumber = $lockedManifest->manifest_number;
            $items = $lockedManifest->items()->with('shipmentItem.shipment')->get();

            foreach ($items as $item) {
                $shipmentItem = $item->shipmentItem;
                if (!$shipmentItem) {
                    continue;
                }

                ShipmentItemTracking::create([
                    'shipment_item_id' => $shipmentItem->id,
                    'status' => $shipmentItem->status?->value ?? $shipmentItem->getRawOriginal('status'),
                    'location' => $warehouse->name,
                    'notes' => 'Transport manifest ' . $manifestNumber . ' was deleted before loading.',
                    'meta' => [
                        'transport_manifest_id' => $lockedManifest->id,
                        'transport_manifest_number' => $manifestNumber,
                        'deleted_manifest' => true,
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => now(),
                ]);
            }

            $lockedManifest->delete();

            return [
                'success' => true,
                'message' => "Transport manifest {$manifestNumber} deleted.",
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

    private function markContainerLoadedFromException(TransportContainer $container, ?Driver $driver, TransportLoadingException $exception): void
    {
        $container->loadMissing('items.manifestItem');
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
                'notes' => trim(implode("\n", array_filter([
                    $line->notes,
                    'Loaded from scan issue #' . $exception->id . '.',
                ]))),
            ]);

            $containerItem->update(['status' => TransportContainerItem::STATUS_LOADED]);
        }

        $container->update([
            'status' => TransportContainer::STATUS_LOADED,
            'loaded_at' => $container->loaded_at ?? $now,
            'loaded_by_driver_id' => $driver?->id ?? $container->loaded_by_driver_id,
        ]);
    }

    private function markLineLoadedFromException(TransportManifestItem $line, ?Driver $driver, TransportLoadingException $exception): void
    {
        $line->update([
            'scan_out_count' => max((int) $line->scan_out_count, 1),
            'loaded_quantity' => (int) $line->expected_quantity,
            'loaded_at' => $line->loaded_at ?? now(),
            'line_status' => TransportManifestItem::LINE_LOADED,
            'notes' => trim(implode("\n", array_filter([
                $line->notes,
                'Loaded from scan issue #' . $exception->id . '.',
            ]))),
        ]);

        $this->syncLineContainerLoadState($line);

        $containerItem = TransportContainerItem::query()
            ->where('transport_manifest_item_id', $line->id)
            ->with('container')
            ->first();

        if ($containerItem?->container && $containerItem->container->status === TransportContainer::STATUS_LOADED) {
            $containerItem->container->update([
                'loaded_by_driver_id' => $driver?->id ?? $containerItem->container->loaded_by_driver_id,
            ]);
        }
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

    private function syncLineContainerUnloadState(TransportManifestItem $line): void
    {
        $containerItem = TransportContainerItem::query()
            ->where('transport_manifest_item_id', $line->id)
            ->with('container')
            ->first();

        if (!$containerItem?->container) {
            return;
        }

        $containerItem->update(['status' => TransportContainerItem::STATUS_PACKED]);

        $containerItem->container->update([
            'status' => TransportContainer::STATUS_SEALED,
            'loaded_at' => null,
            'loaded_by_driver_id' => null,
        ]);
    }

    private function isLooseTransportContainer(TransportContainer $container): bool
    {
        return strtolower((string) $container->container_type) === 'loose';
    }

    private function generateContainerCode(TransportManifest $manifest, int $sequence): string
    {
        return preg_replace('/[^A-Z0-9-]/', '', strtoupper($manifest->manifest_number)) . '-C' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    private function generateManifestNumber(Warehouse $origin, ?Warehouse $destination): string
    {
        $year = now()->format('Y');
        $originCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($origin->code ?: $origin->id)));
        $destinationCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) (($destination?->code) ?: ($destination?->id) ?: 'DRAFT')));
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

    private function syncShipmentWarehouseStatus(Shipment $shipment): void
    {
        $hasTransitOrBeyond = $shipment->items()
            ->whereIn('status', [
                ItemStatus::IN_TRANSIT->value,
                ItemStatus::AT_DESTINATION->value,
                ItemStatus::OUT_FOR_DELIVERY->value,
                ItemStatus::HANDED_TO_COURIER->value,
                ItemStatus::DELIVERED->value,
                ItemStatus::RETURNED->value,
            ])
            ->exists();

        if (!$hasTransitOrBeyond && $shipment->status === ShipmentStatus::IN_TRANSIT) {
            $shipment->update(['status' => ShipmentStatus::AT_WAREHOUSE]);
        }
    }

    private function manifestReceivingHasStarted(TransportManifest $manifest): bool
    {
        $receipt = $manifest->relationLoaded('warehouseReceipt')
            ? $manifest->warehouseReceipt
            : $manifest->warehouseReceipt()->first();

        if (!$receipt) {
            return false;
        }

        if (
            $receipt->status !== WarehouseReceipt::STATUS_DRAFT
            || $receipt->started_at
            || $receipt->finalized_at
        ) {
            return true;
        }

        return $receipt->items()
            ->where(function (Builder $query) {
                $query->where('received_quantity', '>', 0)
                    ->orWhere('damaged_quantity', '>', 0)
                    ->orWhereNotNull('received_at');
            })
            ->exists();
    }

    private function manifestTrackingEvents(TransportManifest $manifest): Collection
    {
        $shipmentItemIds = $manifest->items
            ->pluck('shipment_item_id')
            ->filter()
            ->values();

        if ($shipmentItemIds->isEmpty()) {
            return collect();
        }

        return ShipmentItemTracking::query()
            ->whereIn('shipment_item_id', $shipmentItemIds)
            ->where(function (Builder $query) use ($manifest) {
                $query->where('meta->transport_manifest_id', $manifest->id)
                    ->orWhere('meta->transport_manifest_id', (string) $manifest->id);
            })
            ->whereIn('meta->event', [
                'dispatched',
                'admin_marked_arrived',
                'dispatch_reversed',
                'arrival_reversed',
            ])
            ->orderBy('created_at')
            ->get();
    }

    private function trackingEventActors(Collection $trackingEvents): array
    {
        $userIds = $trackingEvents
            ->map(fn (ShipmentItemTracking $tracking) => $this->userIdFromCreatedBy($tracking->created_by))
            ->filter()
            ->unique()
            ->values();

        $users = $userIds->isEmpty()
            ? collect()
            : User::query()->whereKey($userIds)->pluck('name', 'id');

        $actors = [];

        foreach ($trackingEvents as $tracking) {
            $event = data_get($tracking->meta, 'event');
            if (!$event) {
                continue;
            }

            $userId = $this->userIdFromCreatedBy($tracking->created_by);
            $actor = $userId ? $users->get($userId) : null;
            if (!$actor) {
                continue;
            }

            $actors[$event] ??= $actor;
            $actors[$event . ':' . $tracking->created_at?->timestamp] ??= $actor;
        }

        return $actors;
    }

    private function userIdFromCreatedBy(?string $createdBy): ?int
    {
        if (!$createdBy || !str_starts_with($createdBy, 'user:')) {
            return null;
        }

        return (int) str($createdBy)->after('user:')->toString() ?: null;
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status !== null ? (string) $status : null);
    }
}
