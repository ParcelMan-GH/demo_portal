<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseSortingService
{
    public function eligibleItemsQuery(Warehouse $warehouse): Builder
    {
        return WarehouseReceiptItem::query()
            ->with([
                'receipt:id,warehouse_id,status,pickup_assignment_id',
                'receipt.pickupAssignment:id,shipment_id',
                'shipmentItem:id,shipment_id,description,quantity,tracking_code,status,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark',
                'shipmentItem.shipment:id,shipment_number,destination_mode,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark',
                'shipmentItem.shipment.deliveryRegion:id,name',
                'shipmentItem.shipment.deliveryDistrict:id,name',
                'shipmentItem.deliveryRegion:id,name',
                'shipmentItem.deliveryDistrict:id,name',
            ])
            ->whereHas('receipt', function (Builder $query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('status', 'finalized');
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
        Warehouse $destinationWarehouse,
        User $user,
        ?string $notes = null
    ): array {
        if (!$destinationWarehouse->is_active) {
            return [
                'success' => false,
                'message' => 'Destination warehouse is inactive.',
            ];
        }

        $batchNumber = $this->generateBatchNumber($originWarehouse, $destinationWarehouse);

        $batch = SortBatch::query()->create([
            'batch_number' => $batchNumber,
            'origin_warehouse_id' => $originWarehouse->id,
            'destination_warehouse_id' => $destinationWarehouse->id,
            'status' => SortBatch::STATUS_OPEN,
            'created_by_user_id' => $user->id,
            'notes' => $notes,
        ]);

        return [
            'success' => true,
            'message' => 'Sort batch created successfully.',
            'data' => ['batch' => $batch->fresh('destinationWarehouse')],
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

                $alreadyInActiveBatch = SortBatchItem::query()
                    ->where('shipment_item_id', $receiptItem->shipment_item_id)
                    ->whereNull('removed_at')
                    ->exists();

                if ($alreadyInActiveBatch) {
                    return [
                        'success' => false,
                        'message' => "Item #{$receiptItem->shipment_item_id} is already in an active sort batch.",
                    ];
                }

                $sortBatchItem = SortBatchItem::query()
                    ->where('sort_batch_id', $batch->id)
                    ->where('shipment_item_id', $receiptItem->shipment_item_id)
                    ->first();

                if ($sortBatchItem) {
                    $sortBatchItem->update([
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'quantity_allocated' => max(0, (int) $receiptItem->received_quantity),
                        'added_by_user_id' => $user->id,
                        'added_at' => now(),
                        'removed_at' => null,
                    ]);
                } else {
                    $sortBatchItem = SortBatchItem::query()->create([
                        'sort_batch_id' => $batch->id,
                        'shipment_item_id' => $receiptItem->shipment_item_id,
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'quantity_allocated' => max(0, (int) $receiptItem->received_quantity),
                        'added_by_user_id' => $user->id,
                        'added_at' => now(),
                    ]);
                }

                $shipmentItem = $receiptItem->shipmentItem;
                if ($shipmentItem) {
                    $shipmentItem->update(['status' => ItemStatus::SORTED]);
                    ShipmentItemTracking::create([
                        'shipment_item_id' => $shipmentItem->id,
                        'status' => ItemStatus::SORTED->value,
                        'location' => $warehouse->name,
                        'notes' => 'Item added to sort batch ' . $batch->batch_number . '.',
                        'meta' => [
                            'sort_batch_id' => $batch->id,
                            'sort_batch_number' => $batch->batch_number,
                            'destination_warehouse_id' => $batch->destination_warehouse_id,
                        ],
                        'created_by' => "user:{$user->id}",
                        'created_at' => now(),
                    ]);

                    $this->syncShipmentSortedStatus($shipmentItem->shipment);
                }

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
    }

    private function generateBatchNumber(Warehouse $originWarehouse, Warehouse $destinationWarehouse): string
    {
        $year = now()->format('Y');
        $originCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($originWarehouse->code ?: $originWarehouse->id)));
        $destinationCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($destinationWarehouse->code ?: $destinationWarehouse->id)));
        $prefix = "SB-{$year}-{$originCode}-{$destinationCode}-";

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
                'item_description' => $shipmentItem?->description,
                'tracking_code' => $shipmentItem?->tracking_code,
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
}
