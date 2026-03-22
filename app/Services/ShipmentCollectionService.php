<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Events\ShipmentCollected;
use App\Events\ShipmentReadyForCollection;
use App\Models\Shipment;
use App\Models\ShipmentCollection;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use Illuminate\Support\Facades\DB;

class ShipmentCollectionService
{
    /**
     * Mark a self-pickup shipment as ready for collection.
     * Called after warehouse receipt is finalized for self-pickup shipments.
     * Auto-creates a sort batch (sealed) and a collection record.
     */
    public function markReadyForCollection(Shipment $shipment, Warehouse $warehouse): ShipmentCollection
    {
        return DB::transaction(function () use ($shipment, $warehouse) {
            $now = now();

            // Auto-create and seal sort batch
            $this->autoCreateSortBatch($shipment, $warehouse);

            // Transition to SORTED
            $shipment->update(['status' => ShipmentStatus::SORTED]);
            $shipment->items()->update(['status' => ItemStatus::SORTED]);

            // Create collection record
            $collection = ShipmentCollection::create([
                'shipment_id'  => $shipment->id,
                'warehouse_id' => $warehouse->id,
                'status'       => ShipmentCollection::STATUS_READY,
                'ready_at'     => $now,
            ]);

            // Tracking
            foreach ($shipment->items as $item) {
                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status'           => 'ready_for_collection',
                    'location'         => $warehouse->name,
                    'notes'            => 'Ready for recipient collection at ' . $warehouse->name,
                    'created_at'       => $now,
                ]);
            }

            event(new ShipmentReadyForCollection($shipment, $collection, $warehouse));

            return $collection;
        });
    }

    /**
     * Record handover of a self-pickup shipment to the recipient.
     */
    public function recordHandover(Shipment $shipment, User $handedOverBy, array $data): ShipmentCollection
    {
        return DB::transaction(function () use ($shipment, $handedOverBy, $data) {
            $collection = $shipment->collection;
            $now = now();

            $collection->update([
                'status'                 => ShipmentCollection::STATUS_COLLECTED,
                'collected_by_name'      => $data['collected_by_name'],
                'collected_by_phone'     => $data['collected_by_phone'] ?? null,
                'collected_by_id_type'   => $data['collected_by_id_type'] ?? null,
                'collected_by_id_number' => $data['collected_by_id_number'] ?? null,
                'collected_at'           => $now,
                'handed_over_by_user_id' => $handedOverBy->id,
                'notes'                  => $data['notes'] ?? null,
            ]);

            // Mark shipment as delivered
            $shipment->update(['status' => ShipmentStatus::DELIVERED]);
            $shipment->items()->update(['status' => ItemStatus::DELIVERED]);

            // Tracking
            foreach ($shipment->items as $item) {
                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status'           => 'delivered',
                    'location'         => $collection->warehouse?->name,
                    'notes'            => "Collected by {$data['collected_by_name']} at warehouse.",
                    'created_by'       => "user:{$handedOverBy->id}",
                    'created_at'       => $now,
                ]);
            }

            event(new ShipmentCollected($shipment, $collection->fresh(), $handedOverBy));

            return $collection->fresh();
        });
    }

    private function autoCreateSortBatch(Shipment $shipment, Warehouse $warehouse): SortBatch
    {
        $now = now();
        $year = $now->format('Y');
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($warehouse->code ?: $warehouse->id)));
        $prefix = "LB-{$year}-{$code}-COLLECT-";

        $last = SortBatch::where('batch_number', 'like', $prefix . '%')->latest('id')->first();
        $next = $last ? ((int) last(explode('-', $last->batch_number))) + 1 : 1;
        $batchNumber = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        $batch = SortBatch::create([
            'batch_number'             => $batchNumber,
            'origin_warehouse_id'      => $warehouse->id,
            'destination_warehouse_id' => $warehouse->id,
            'dispatch_mode'            => SortBatch::DISPATCH_LOCAL_DELIVERY,
            'status'                   => SortBatch::STATUS_SEALED,
            'sealed_at'                => $now,
            'notes'                    => 'Auto-created: self-pickup collection.',
        ]);

        // Find warehouse receipt items for this shipment
        foreach ($shipment->items as $item) {
            $receiptItem = WarehouseReceiptItem::where('shipment_item_id', $item->id)
                ->whereHas('receipt', fn ($q) => $q->where('warehouse_id', $warehouse->id))
                ->latest('id')
                ->first();

            SortBatchItem::create([
                'sort_batch_id'            => $batch->id,
                'shipment_item_id'         => $item->id,
                'warehouse_receipt_item_id' => $receiptItem?->id,
                'quantity_allocated'        => $item->quantity,
                'added_at'                 => $now,
            ]);
        }

        return $batch;
    }
}
