<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseSortingService
{
    public function __construct(private RecipientPaymentService $recipientPaymentService) {}

    public function eligibleItemsQuery(Warehouse $warehouse): Builder
    {
        return WarehouseReceiptItem::query()
            ->with([
                'receipt:id,warehouse_id,status,pickup_assignment_id',
                'receipt.pickupAssignment:id,shipment_id',
                'shipmentItem:id,shipment_id,description,quantity,tracking_code,status,delivery_method,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark',
                'shipmentItem.shipment:id,vendor_id,shipment_number,destination_mode,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark',
                'shipmentItem.shipment.vendor:id,name',
                'shipmentItem.shipment.deliveryRegion:id,name',
                'shipmentItem.shipment.deliveryDistrict:id,name',
                'shipmentItem.deliveryRegion:id,name',
                'shipmentItem.deliveryDistrict:id,name',
            ])
            ->whereHas('receipt', function (Builder $query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('status', 'finalized');
            })
            ->where('received_quantity', '>', 0)
            ->whereHas('shipmentItem', function (Builder $query) {
                $query->whereIn('status', $this->sortableWarehouseItemStatuses());

                if (Schema::hasTable('delivery_run_items') && Schema::hasTable('delivery_runs')) {
                    $query->whereDoesntHave('deliveryRunItems', function (Builder $deliveryQuery) {
                        $deliveryQuery->whereHas('run', function (Builder $runQuery) {
                            $runQuery->where('status', '!=', DeliveryRun::STATUS_CANCELLED);
                        });
                    });
                }

                if (Schema::hasTable('transport_manifest_items') && Schema::hasTable('transport_manifests')) {
                    $query->whereDoesntHave('transportManifestItems', function (Builder $manifestItemQuery) {
                        $manifestItemQuery->whereHas('manifest', function (Builder $manifestQuery) {
                            $manifestQuery->whereNotIn('status', [
                                TransportManifest::STATUS_RECEIVED,
                                TransportManifest::STATUS_CANCELLED,
                            ]);
                        });
                    });
                }
            })
            ->whereDoesntHave('sortBatchItems', function (Builder $query) {
                $query->whereNull('removed_at');
            });
    }

    public function batchesQuery(Warehouse $warehouse): Builder
    {
        return SortBatch::query()
            ->with([
                'destinationWarehouse:id,name,code',
                'activeItems:id,sort_batch_id,shipment_item_id,warehouse_receipt_item_id,quantity_allocated,added_at',
                'activeItems.shipmentItem:id,description,tracking_code',
            ])
            ->where('origin_warehouse_id', $warehouse->id);
    }

    public function createBatch(
        Warehouse $originWarehouse,
        ?Warehouse $destinationWarehouse,
        User $user,
        string $dispatchMode = SortBatch::DISPATCH_TRANSFER,
        ?string $notes = null
    ): array {
        if (!in_array($dispatchMode, [SortBatch::DISPATCH_TRANSFER, SortBatch::DISPATCH_LOCAL_DELIVERY], true)) {
            return [
                'success' => false,
                'message' => 'Invalid dispatch mode.',
            ];
        }

        if ($dispatchMode === SortBatch::DISPATCH_TRANSFER) {
            if (!$destinationWarehouse) {
                return [
                    'success' => false,
                    'message' => 'Destination warehouse is required for transfer batches.',
                ];
            }

            if ((int) $destinationWarehouse->id === (int) $originWarehouse->id) {
                return [
                    'success' => false,
                    'message' => 'Use local delivery mode when destination is the same warehouse.',
                ];
            }

            if (!$destinationWarehouse->is_active) {
                return [
                    'success' => false,
                    'message' => 'Destination warehouse is inactive.',
                ];
            }
        }

        if ($dispatchMode === SortBatch::DISPATCH_LOCAL_DELIVERY) {
            $destinationWarehouse = null;
        }

        $batch = null;
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts && !$batch) {
            $attempt++;
            $batchNumber = $this->generateBatchNumber($originWarehouse, $dispatchMode, $destinationWarehouse);

            try {
                $batch = SortBatch::query()->create([
                    'batch_number' => $batchNumber,
                    'origin_warehouse_id' => $originWarehouse->id,
                    'destination_warehouse_id' => $destinationWarehouse?->id,
                    'dispatch_mode' => $dispatchMode,
                    'status' => SortBatch::STATUS_OPEN,
                    'created_by_user_id' => $user->id,
                    'notes' => $notes,
                ]);
            } catch (QueryException $exception) {
                if (!$this->isDuplicateBatchNumberError($exception) || $attempt >= $maxAttempts) {
                    return [
                        'success' => false,
                        'message' => 'Unable to create sort batch right now. Please try again.',
                    ];
                }
            }
        }

        if (!$batch) {
            return [
                'success' => false,
                'message' => 'Unable to create sort batch right now. Please try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Sort batch created successfully.',
            'data' => ['batch' => $batch->fresh('destinationWarehouse')],
        ];
    }

    /**
     * @param iterable<int, array{warehouse_receipt_item_id:int, destination_warehouse_id:int}> $routes
     */
    public function autoRouteReceiptItemsToDestinationBatches(iterable $routes, Warehouse $warehouse, User $user): array
    {
        $routes = collect($routes)
            ->map(fn (array $route) => [
                'warehouse_receipt_item_id' => (int) ($route['warehouse_receipt_item_id'] ?? 0),
                'destination_warehouse_id' => (int) ($route['destination_warehouse_id'] ?? 0),
            ])
            ->filter(fn (array $route) => $route['warehouse_receipt_item_id'] > 0)
            ->filter(fn (array $route) => $route['destination_warehouse_id'] > 0)
            ->filter(fn (array $route) => $route['destination_warehouse_id'] !== (int) $warehouse->id)
            ->groupBy('destination_warehouse_id');

        if ($routes->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No transfer routing selected.',
                'data' => ['batches' => []],
            ];
        }

        $results = [];

        foreach ($routes as $destinationWarehouseId => $groupedRoutes) {
            $destinationWarehouse = Warehouse::query()
                ->whereKey((int) $destinationWarehouseId)
                ->where('is_active', true)
                ->first();

            if (!$destinationWarehouse) {
                $results[] = [
                    'success' => false,
                    'message' => 'Destination warehouse is inactive or missing.',
                    'destination_warehouse_id' => (int) $destinationWarehouseId,
                ];
                continue;
            }

            $batch = SortBatch::query()
                ->where('origin_warehouse_id', $warehouse->id)
                ->where('destination_warehouse_id', $destinationWarehouse->id)
                ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
                ->where('status', SortBatch::STATUS_OPEN)
                ->orderByDesc('id')
                ->first();

            if (!$batch) {
                $created = $this->createBatch(
                    originWarehouse: $warehouse,
                    destinationWarehouse: $destinationWarehouse,
                    user: $user,
                    dispatchMode: SortBatch::DISPATCH_TRANSFER,
                    notes: 'Auto-created transfer batch from receiving.'
                );

                if (!$created['success']) {
                    $results[] = [
                        'success' => false,
                        'message' => $created['message'],
                        'destination_warehouse_id' => $destinationWarehouse->id,
                    ];
                    continue;
                }

                $batch = $created['data']['batch'];
            }

            $added = $this->addItems(
                batch: $batch,
                warehouse: $warehouse,
                user: $user,
                warehouseReceiptItemIds: $groupedRoutes->pluck('warehouse_receipt_item_id')->all()
            );

            $results[] = [
                'success' => $added['success'],
                'message' => $added['message'],
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'destination_warehouse_id' => $destinationWarehouse->id,
                'destination_warehouse_name' => $destinationWarehouse->name,
                'item_count' => $groupedRoutes->count(),
            ];
        }

        return [
            'success' => collect($results)->every(fn (array $result) => $result['success']),
            'message' => 'Transfer routing processed.',
            'data' => ['batches' => $results],
        ];
    }

    /**
     * @param array<int, int|string> $warehouseReceiptItemIds
     */
    public function addItems(SortBatch $batch, Warehouse $warehouse, User $user, array $warehouseReceiptItemIds): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return [
                'success' => false,
                'message' => 'Cannot modify a batch from another warehouse.',
            ];
        }

        if (!$batch->isOpen()) {
            return [
                'success' => false,
                'message' => 'Batch is sealed and cannot be modified.',
            ];
        }

        $ids = collect($warehouseReceiptItemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Select at least one item to add.',
            ];
        }

        return DB::transaction(function () use ($batch, $warehouse, $user, $ids) {
            $items = WarehouseReceiptItem::query()
                ->with(['receipt', 'shipmentItem.shipment'])
                ->whereIn('id', $ids->all())
                ->lockForUpdate()
                ->get();

            foreach ($items as $receiptItem) {
                if ((int) $receiptItem->receipt->warehouse_id !== (int) $warehouse->id || $receiptItem->receipt->status !== 'finalized') {
                    return [
                        'success' => false,
                        'message' => 'One or more selected items are not eligible for sorting.',
                    ];
                }

                if ((int) $receiptItem->received_quantity <= 0) {
                    return [
                        'success' => false,
                        'message' => "Item #{$receiptItem->shipment_item_id} has no receivable quantity for sorting.",
                    ];
                }

                $shipmentItem = ShipmentItem::query()
                    ->with('shipment')
                    ->whereKey($receiptItem->shipment_item_id)
                    ->lockForUpdate()
                    ->first();

                if (!$shipmentItem) {
                    return [
                        'success' => false,
                        'message' => 'One or more selected shipment items could not be found.',
                    ];
                }

                $sortability = $this->sortableWarehouseItemState($shipmentItem);
                if (!$sortability['eligible']) {
                    return [
                        'success' => false,
                        'message' => "Item #{$shipmentItem->id} cannot be added to this batch. {$sortability['reason']}",
                    ];
                }

                $alreadyInActiveBatch = SortBatchItem::query()
                    ->where('shipment_item_id', $shipmentItem->id)
                    ->whereNull('removed_at')
                    ->whereHas('sortBatch', fn (Builder $q) => $q->where('origin_warehouse_id', $warehouse->id))
                    ->exists();

                if ($alreadyInActiveBatch) {
                    return [
                        'success' => false,
                        'message' => "Item #{$shipmentItem->id} is already in an active sort batch.",
                    ];
                }

                $sortBatchItem = SortBatchItem::query()
                    ->where('sort_batch_id', $batch->id)
                    ->where('shipment_item_id', $shipmentItem->id)
                    ->first();

                if ($sortBatchItem) {
                    $sortBatchItem->update([
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'quantity_allocated' => (int) $receiptItem->received_quantity,
                        'added_by_user_id' => $user->id,
                        'added_at' => now(),
                        'removed_at' => null,
                    ]);
                } else {
                    $sortBatchItem = SortBatchItem::query()->create([
                        'sort_batch_id' => $batch->id,
                        'shipment_item_id' => $shipmentItem->id,
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'quantity_allocated' => (int) $receiptItem->received_quantity,
                        'added_by_user_id' => $user->id,
                        'added_at' => now(),
                    ]);
                }

                $shipmentItem->update(['status' => ItemStatus::SORTED]);
                $itemTrackingNote = $batch->isLocalDeliveryMode()
                    ? 'Item added to local delivery batch ' . $batch->batch_number . '.'
                    : 'Item added to transfer batch ' . $batch->batch_number . '.';

                ShipmentItemTracking::create([
                    'shipment_item_id' => $shipmentItem->id,
                    'status' => ItemStatus::SORTED->value,
                    'location' => $warehouse->name,
                    'notes' => $itemTrackingNote,
                    'meta' => [
                        'sort_batch_id' => $batch->id,
                        'sort_batch_number' => $batch->batch_number,
                        'dispatch_mode' => $batch->dispatch_mode,
                        'destination_warehouse_id' => $batch->destination_warehouse_id,
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => now(),
                ]);

                $this->recipientPaymentService->ensureTaskForSortBatchItem($sortBatchItem);
                $this->syncShipmentSortedStatus($shipmentItem->shipment);

                unset($sortBatchItem);
            }

            return [
                'success' => true,
                'message' => 'Items added to sort batch.',
                'data' => ['batch' => $batch->fresh('activeItems.shipmentItem')],
            ];
        });
    }

    public function removeItem(SortBatch $batch, ShipmentItem $shipmentItem, Warehouse $warehouse, User $user): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return [
                'success' => false,
                'message' => 'Cannot modify a batch from another warehouse.',
            ];
        }

        if (!$batch->isOpen()) {
            return [
                'success' => false,
                'message' => 'Batch is sealed and cannot be modified.',
            ];
        }

        return DB::transaction(function () use ($batch, $shipmentItem, $warehouse, $user) {
            $sortBatchItem = SortBatchItem::query()
                ->where('sort_batch_id', $batch->id)
                ->where('shipment_item_id', $shipmentItem->id)
                ->whereNull('removed_at')
                ->lockForUpdate()
                ->first();

            if (!$sortBatchItem) {
                return [
                    'success' => false,
                    'message' => 'Item is not active in this sort batch.',
                ];
            }

            $sortBatchItem->update(['removed_at' => now()]);
            $this->recipientPaymentService->cancelTaskForSortBatchItem($sortBatchItem);

            $stillInAnyOpenBatch = SortBatchItem::query()
                ->where('shipment_item_id', $shipmentItem->id)
                ->whereNull('removed_at')
                ->whereHas('sortBatch', fn (Builder $q) => $q->where('status', SortBatch::STATUS_OPEN))
                ->exists();

            if (!$stillInAnyOpenBatch) {
                $shipmentItem->update(['status' => ItemStatus::AT_WAREHOUSE]);

                ShipmentItemTracking::create([
                    'shipment_item_id' => $shipmentItem->id,
                    'status' => ItemStatus::AT_WAREHOUSE->value,
                    'location' => $warehouse->name,
                    'notes' => 'Item removed from sort batch ' . $batch->batch_number . '.',
                    'meta' => [
                        'sort_batch_id' => $batch->id,
                        'sort_batch_number' => $batch->batch_number,
                        'removed' => true,
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => now(),
                ]);

                $this->syncShipmentSortedStatus($shipmentItem->shipment);
            }

            return [
                'success' => true,
                'message' => 'Item removed from sort batch.',
                'data' => ['batch' => $batch->fresh('activeItems.shipmentItem')],
            ];
        });
    }

    public function sealBatch(SortBatch $batch, Warehouse $warehouse, User $user): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return [
                'success' => false,
                'message' => 'Cannot seal a batch from another warehouse.',
            ];
        }

        if (!$batch->isOpen()) {
            return [
                'success' => false,
                'message' => 'Batch is already sealed.',
            ];
        }

        $activeCount = $batch->activeItems()->count();
        if ($activeCount < 1) {
            return [
                'success' => false,
                'message' => 'Cannot seal an empty sort batch.',
            ];
        }

        return DB::transaction(function () use ($batch, $user) {
            $activeItems = $batch->activeItems()
                ->with('shipmentItem')
                ->lockForUpdate()
                ->get();

            $missingDeliveryPrices = collect();

            foreach ($activeItems as $sortBatchItem) {
                if (! $sortBatchItem->shipmentItem || ! $this->isSortableWarehouseItem($sortBatchItem->shipmentItem, true)) {
                    return [
                        'success' => false,
                        'message' => 'This batch contains a package that is no longer idle at the warehouse. Remove it before sealing.',
                    ];
                }

                $paymentTask = $this->recipientPaymentService->ensureTaskForSortBatchItem($sortBatchItem);
                $deliveryFee = $paymentTask?->shipmentCharge
                    ?: $this->recipientPaymentService->deliveryFeeChargeForItem($sortBatchItem->shipmentItem);

                if ($paymentTask?->negotiated_amount === null && $deliveryFee?->amount === null) {
                    $missingDeliveryPrices->push($sortBatchItem->shipmentItem->tracking_code ?: 'Item #' . $sortBatchItem->shipmentItem->id);
                }
            }

            if ($missingDeliveryPrices->isNotEmpty()) {
                $sample = $missingDeliveryPrices->take(3)->implode(', ');
                $extra = $missingDeliveryPrices->count() > 3 ? ' +' . ($missingDeliveryPrices->count() - 3) . ' more' : '';

                return [
                    'success' => false,
                    'message' => 'Set delivery prices for all packages before sealing. Missing: ' . $sample . $extra . '.',
                ];
            }

            $batch->update([
                'status' => SortBatch::STATUS_SEALED,
                'sealed_by_user_id' => $user->id,
                'sealed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Sort batch sealed successfully.',
                'data' => ['batch' => $batch->fresh('activeItems')],
            ];
        });
    }

    public function reopenBatch(SortBatch $batch, Warehouse $warehouse, User $user): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return [
                'success' => false,
                'message' => 'Cannot reopen a batch from another warehouse.',
            ];
        }

        if ($batch->isOpen()) {
            return [
                'success' => false,
                'message' => 'Batch is already open.',
            ];
        }

        if ($message = $this->reopenLockReason($batch)) {
            return [
                'success' => false,
                'message' => $message,
            ];
        }

        $batch->update([
            'status' => SortBatch::STATUS_OPEN,
            'sealed_by_user_id' => null,
            'sealed_at' => null,
        ]);

        return [
            'success' => true,
            'message' => 'Sort batch reopened successfully.',
            'data' => ['batch' => $batch->fresh('activeItems')],
        ];
    }

    /**
     * @return array{deletable: bool, reason: ?string}
     */
    public function deleteState(SortBatch $batch): array
    {
        if ($this->batchHasTransportManifest($batch)) {
            return [
                'deletable' => false,
                'reason' => 'This batch already has a transport manifest.',
            ];
        }

        if ($this->batchHasDeliveryRun($batch)) {
            return [
                'deletable' => false,
                'reason' => 'This batch already has a delivery run.',
            ];
        }

        return [
            'deletable' => true,
            'reason' => null,
        ];
    }

    public function deleteBatch(SortBatch $batch, Warehouse $warehouse, User $user): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return [
                'success' => false,
                'message' => 'Cannot delete a batch from another warehouse.',
            ];
        }

        $deleteState = $this->deleteState($batch);
        if (!$deleteState['deletable']) {
            return [
                'success' => false,
                'message' => $deleteState['reason'] ?? 'This batch cannot be deleted.',
            ];
        }

        return DB::transaction(function () use ($batch, $warehouse, $user) {
            $batch = SortBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $deleteState = $this->deleteState($batch);
            if (!$deleteState['deletable']) {
                return [
                    'success' => false,
                    'message' => $deleteState['reason'] ?? 'This batch cannot be deleted.',
                ];
            }

            $activeItems = $batch->activeItems()
                ->with('shipmentItem.shipment')
                ->lockForUpdate()
                ->get();

            foreach ($activeItems as $sortBatchItem) {
                $shipmentItem = $sortBatchItem->shipmentItem;

                $sortBatchItem->update(['removed_at' => now()]);

                if (!$shipmentItem) {
                    continue;
                }

                $currentStatus = $shipmentItem->status?->value ?? $shipmentItem->getRawOriginal('status');
                if ($currentStatus === ItemStatus::SORTED->value) {
                    $shipmentItem->update(['status' => ItemStatus::AT_WAREHOUSE]);
                }

                ShipmentItemTracking::create([
                    'shipment_item_id' => $shipmentItem->id,
                    'status' => $shipmentItem->fresh()->status?->value ?? ItemStatus::AT_WAREHOUSE->value,
                    'location' => $warehouse->name,
                    'notes' => 'Item removed because sort batch ' . $batch->batch_number . ' was deleted.',
                    'meta' => [
                        'sort_batch_id' => $batch->id,
                        'sort_batch_number' => $batch->batch_number,
                        'deleted_batch' => true,
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => now(),
                ]);

                $this->syncShipmentSortedStatus($shipmentItem->shipment);
            }

            $batchNumber = $batch->batch_number;
            $batch->delete();

            return [
                'success' => true,
                'message' => "Sort batch {$batchNumber} deleted.",
            ];
        });
    }

    private function batchHasTransportManifest(SortBatch $batch): bool
    {
        return Schema::hasTable('transport_manifests')
            && $batch->transportManifest()->exists();
    }

    private function batchHasDeliveryRun(SortBatch $batch): bool
    {
        return Schema::hasTable('delivery_runs')
            && $batch->deliveryRun()->exists();
    }

    private function reopenLockReason(SortBatch $batch): ?string
    {
        $batch->loadMissing(['transportManifest:id,sort_batch_id,status', 'deliveryRun:id,sort_batch_id,status']);

        if ($batch->transportManifest?->status === TransportManifest::STATUS_RECEIVED) {
            return 'This batch cannot be reopened because its transport manifest has been completed.';
        }

        if ($batch->deliveryRun?->status === DeliveryRun::STATUS_COMPLETED) {
            return 'This batch cannot be reopened because its delivery run has been completed.';
        }

        return null;
    }

    /**
     * @return array{eligible: bool, reason: ?string}
     */
    public function sortableWarehouseItemState(ShipmentItem $shipmentItem, bool $allowSorted = false): array
    {
        $status = $shipmentItem->status?->value ?? $shipmentItem->getRawOriginal('status');
        $allowedStatuses = $this->sortableWarehouseItemStatuses();

        if ($allowSorted) {
            $allowedStatuses[] = ItemStatus::SORTED->value;
        }

        if (!in_array($status, $allowedStatuses, true)) {
            return [
                'eligible' => false,
                'reason' => 'Status is ' . $this->formatItemStatus($status) . '.',
            ];
        }

        $hasActiveDeliveryRun = Schema::hasTable('delivery_run_items') && Schema::hasTable('delivery_runs')
            ? $shipmentItem->deliveryRunItems()
                ->whereHas('run', function (Builder $query) {
                    $query->where('status', '!=', DeliveryRun::STATUS_CANCELLED);
                })
                ->exists()
            : false;

        if ($hasActiveDeliveryRun) {
            return [
                'eligible' => false,
                'reason' => 'Already linked to a delivery run.',
            ];
        }

        $hasActiveManifest = Schema::hasTable('transport_manifest_items') && Schema::hasTable('transport_manifests')
            ? $shipmentItem->transportManifestItems()
                ->whereHas('manifest', function (Builder $query) {
                    $query->whereNotIn('status', [
                        TransportManifest::STATUS_RECEIVED,
                        TransportManifest::STATUS_CANCELLED,
                    ]);
                })
                ->exists()
            : false;

        if ($hasActiveManifest) {
            return [
                'eligible' => false,
                'reason' => 'Already linked to an active transport manifest.',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    private function isSortableWarehouseItem(ShipmentItem $shipmentItem, bool $allowSorted = false): bool
    {
        return $this->sortableWarehouseItemState($shipmentItem, $allowSorted)['eligible'];
    }

    /**
     * @return array<int, string>
     */
    private function sortableWarehouseItemStatuses(): array
    {
        return [
            ItemStatus::AT_WAREHOUSE->value,
            ItemStatus::AT_DESTINATION->value,
        ];
    }

    private function formatItemStatus(mixed $status): string
    {
        if ($status instanceof ItemStatus) {
            return $status->label();
        }

        $status = (string) $status;

        if ($status === '') {
            return 'unknown';
        }

        return ItemStatus::tryFrom($status)?->label() ?? ucfirst(str_replace('_', ' ', $status));
    }

    private function generateBatchNumber(
        Warehouse $originWarehouse,
        string $dispatchMode,
        ?Warehouse $destinationWarehouse
    ): string
    {
        $year = now()->format('Y');
        $originCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($originWarehouse->code ?: $originWarehouse->id)));

        if ($dispatchMode === SortBatch::DISPATCH_LOCAL_DELIVERY) {
            $prefix = "LB-{$year}-{$originCode}-LOCAL-";
        } else {
            $destinationCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($destinationWarehouse?->code ?: $destinationWarehouse?->id)));
            $prefix = "SB-{$year}-{$originCode}-{$destinationCode}-";
        }

        $lastBatch = SortBatch::query()
            ->where('batch_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $next = 1;
        if ($lastBatch) {
            $parts = explode('-', $lastBatch->batch_number);
            $last = (int) end($parts);
            $next = $last + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function syncShipmentSortedStatus(?Shipment $shipment): void
    {
        if (!$shipment) {
            return;
        }

        $allSorted = !$shipment->items()
            ->whereNotIn('status', [
                ItemStatus::SORTED->value,
                ItemStatus::IN_TRANSIT->value,
                ItemStatus::AT_DESTINATION->value,
                ItemStatus::OUT_FOR_DELIVERY->value,
                ItemStatus::DELIVERED->value,
            ])
            ->exists();

        if ($allSorted) {
            $shipment->update(['status' => ShipmentStatus::SORTED]);
            return;
        }

        if ($shipment->status === ShipmentStatus::SORTED) {
            $shipment->update(['status' => ShipmentStatus::AT_WAREHOUSE]);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mapEligibleItems(Collection $rows): Collection
    {
        return $rows->map(function (WarehouseReceiptItem $receiptItem) {
            $shipmentItem = $receiptItem->shipmentItem;
            $shipment = $shipmentItem?->shipment;

            $isPerItem = $shipment?->isPerItemDestination() ?? false;

            $recipientName = $isPerItem
                ? ($shipmentItem?->delivery_recipient_name)
                : ($shipment?->delivery_recipient_name);

            $recipientPhone = $isPerItem
                ? ($shipmentItem?->delivery_recipient_phone)
                : ($shipment?->delivery_recipient_phone);

            $region = $isPerItem
                ? ($shipmentItem?->deliveryRegion?->name)
                : ($shipment?->deliveryRegion?->name);

            $district = $isPerItem
                ? ($shipmentItem?->deliveryDistrict?->name)
                : ($shipment?->deliveryDistrict?->name);

            $town = $isPerItem
                ? ($shipmentItem?->delivery_town)
                : ($shipment?->delivery_town);

            $landmark = $isPerItem
                ? ($shipmentItem?->delivery_landmark)
                : ($shipment?->delivery_landmark);

            return [
                'warehouse_receipt_item_id' => $receiptItem->id,
                'shipment_item_id' => $receiptItem->shipment_item_id,
                'shipment_number' => $shipment?->shipment_number,
                'vendor_name' => $shipment?->vendor?->name,
                'item_description' => $shipmentItem?->description,
                'tracking_code' => $shipmentItem?->tracking_code,
                'delivery_method' => $shipmentItem?->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                'delivery_method_label' => $shipmentItem?->delivery_method === ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF
                    ? 'Bus Courier'
                    : 'Direct Delivery',
                'fulfillment_type' => $shipmentItem?->fulfillment_type?->value ?? $shipment?->fulfillment_type?->value ?? 'warehouse',
                'received_quantity' => (int) $receiptItem->received_quantity,
                'damaged_quantity' => (int) $receiptItem->damaged_quantity,
                'discrepancy_type' => $receiptItem->discrepancy_type,
                'destination' => [
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $recipientPhone,
                    'region' => $region,
                    'district' => $district,
                    'town' => $town,
                    'landmark' => $landmark,
                ],
            ];
        })->values();
    }

    private function isDuplicateBatchNumberError(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'duplicate')
            && str_contains($message, 'batch_number');
    }
}
