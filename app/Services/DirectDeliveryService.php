<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Support\Facades\DB;

class DirectDeliveryService
{
    public function __construct(
        private DriverWorkloadService $workloads,
        private RiderAssignmentAuditService $assignmentAudit,
    ) {}

    /**
     * Auto-create virtual pipeline records when a direct-delivery pickup is confirmed.
     * Creates: WarehouseReceipt (finalized) → SortBatch (sealed) → DeliveryRun + Stop + Items.
     * Transitions shipment through AT_WAREHOUSE → SORTED → OUT_FOR_DELIVERY.
     *
     * Returns the auto-created delivery run data for the API response.
     */
    public function createVirtualPipeline(Shipment $shipment, int $driverId, int $warehouseId): array
    {
        return DB::transaction(function () use ($shipment, $driverId, $warehouseId) {
            $driver = Driver::query()->lockForUpdate()->findOrFail($driverId);
            $warehouse = Warehouse::findOrFail($warehouseId);
            $items = $shipment->items;
            $now = now();

            // 1. Virtual WarehouseReceipt (auto-finalized)
            $receipt = WarehouseReceipt::create([
                'shipment_id' => $shipment->id,
                'warehouse_id' => $warehouse->id,
                'status' => WarehouseReceipt::STATUS_FINALIZED,
                'started_by_user_id' => null,
                'finalized_by_user_id' => null,
                'notes' => 'Auto-created: direct delivery bypass.',
                'started_at' => $now,
                'finalized_at' => $now,
            ]);

            foreach ($items as $item) {
                WarehouseReceiptItem::create([
                    'warehouse_receipt_id' => $receipt->id,
                    'shipment_item_id' => $item->id,
                    'expected_quantity' => $item->quantity,
                    'received_quantity' => $item->quantity,
                    'damaged_quantity' => 0,
                    'discrepancy_type' => 'none',
                    'condition_status' => 'ok',
                    'barcode_value' => $item->tracking_code,
                    'barcode_format' => 'code128',
                    'received_at' => $now,
                ]);
            }

            // Transition shipment → AT_WAREHOUSE
            $shipment->update(['status' => ShipmentStatus::AT_WAREHOUSE]);
            $items->each(fn (ShipmentItem $i) => $i->update(['status' => ItemStatus::AT_WAREHOUSE]));

            // 2. Virtual SortBatch (auto-sealed)
            $batchNumber = $this->generateBatchNumber($warehouse);
            $batch = SortBatch::create([
                'batch_number' => $batchNumber,
                'origin_warehouse_id' => $warehouse->id,
                'destination_warehouse_id' => $warehouse->id,
                'dispatch_mode' => SortBatch::DISPATCH_LOCAL_DELIVERY,
                'status' => SortBatch::STATUS_SEALED,
                'sealed_at' => $now,
                'notes' => 'Auto-created: direct delivery.',
            ]);

            foreach ($items as $item) {
                $receiptItem = WarehouseReceiptItem::where('warehouse_receipt_id', $receipt->id)
                    ->where('shipment_item_id', $item->id)
                    ->first();

                SortBatchItem::create([
                    'sort_batch_id' => $batch->id,
                    'shipment_item_id' => $item->id,
                    'warehouse_receipt_item_id' => $receiptItem?->id,
                    'quantity_allocated' => $item->quantity,
                    'added_at' => $now,
                ]);
            }

            // Transition shipment → SORTED
            $shipment->update(['status' => ShipmentStatus::SORTED]);
            $items->each(fn (ShipmentItem $i) => $i->update(['status' => ItemStatus::SORTED]));

            // 3. DeliveryRun + Stops + Items (assigned to the same driver)
            $runNumber = $this->generateRunNumber($warehouse);
            $run = DeliveryRun::create([
                'run_number' => $runNumber,
                'sort_batch_id' => $batch->id,
                'warehouse_id' => $warehouse->id,
                'assigned_driver_id' => $driverId,
                'status' => 'out_for_delivery',
                'assigned_at' => $now,
                'dispatched_at' => $now,
                'notes' => 'Auto-created: direct delivery from pickup.',
            ]);

            // Group items by recipient to create stops
            $stops = $this->buildStops($shipment, $items);

            foreach ($stops as $stopGroup) {
                $stop = DeliveryRunStop::create(array_merge($stopGroup['stop_data'], [
                    'delivery_run_id' => $run->id,
                    'total_packages' => count($stopGroup['items']),
                    'status' => 'pending',
                ]));

                foreach ($stopGroup['items'] as $item) {
                    DeliveryRunItem::create([
                        'delivery_run_id' => $run->id,
                        'delivery_run_stop_id' => $stop->id,
                        'shipment_item_id' => $item->id,
                        'expected_quantity' => $item->quantity,
                        'delivered_quantity' => 0,
                        'status' => 'pending',
                    ]);
                }
            }

            // Transition shipment → OUT_FOR_DELIVERY
            $shipment->update(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);
            $items->each(fn (ShipmentItem $i) => $i->update(['status' => ItemStatus::OUT_FOR_DELIVERY]));

            // Tracking entries
            foreach ($items as $item) {
                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status' => 'out_for_delivery',
                    'location' => 'Direct delivery from pickup',
                    'notes' => "Auto-dispatched via direct delivery. Run: {$runNumber}",
                    'created_at' => $now,
                ]);
            }

            $this->assignmentAudit->record('delivery', $run->id, 'assigned', null, $driver->id);
            event(new \App\Events\DriverAssignedToDelivery($run, $driver));
            $this->workloads->syncStatus($driver);

            return [
                'delivery_run_id' => $run->id,
                'run_number' => $run->run_number,
            ];
        });
    }

    /**
     * Build stop groups. For single destination: one stop with all items.
     * For per-item: group items by recipient (name + phone) into separate stops.
     */
    private function buildStops(Shipment $shipment, $items): array
    {
        if ($shipment->isSingleDestination()) {
            return [[
                'stop_data' => [
                    'recipient_name' => $shipment->delivery_recipient_name,
                    'recipient_phone' => $shipment->delivery_recipient_phone,
                    'region_id' => $shipment->delivery_region_id,
                    'district_id' => $shipment->delivery_district_id,
                    'town' => $shipment->delivery_town,
                    'latitude' => $shipment->delivery_latitude,
                    'longitude' => $shipment->delivery_longitude,
                    'gh_post_address' => $shipment->delivery_gh_post_address,
                    'landmark' => $shipment->delivery_landmark,
                ],
                'items' => $items->all(),
            ]];
        }

        // Per-item: group by recipient name + phone
        $grouped = [];
        foreach ($items as $item) {
            $key = mb_strtolower(trim(($item->delivery_recipient_name ?? '').'|'.($item->delivery_recipient_phone ?? '')));
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'stop_data' => [
                        'recipient_name' => $item->delivery_recipient_name,
                        'recipient_phone' => $item->delivery_recipient_phone,
                        'region_id' => $item->delivery_region_id,
                        'district_id' => $item->delivery_district_id,
                        'town' => $item->delivery_town,
                        'latitude' => $item->delivery_latitude,
                        'longitude' => $item->delivery_longitude,
                        'gh_post_address' => $item->delivery_gh_post_address,
                        'landmark' => $item->delivery_landmark,
                    ],
                    'items' => [],
                ];
            }
            $grouped[$key]['items'][] = $item;
        }

        return array_values($grouped);
    }

    private function generateBatchNumber(Warehouse $warehouse): string
    {
        $year = now()->format('Y');
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($warehouse->code ?: $warehouse->id)));
        $prefix = "LB-{$year}-{$code}-DIRECT-";

        $last = SortBatch::where('batch_number', 'like', $prefix.'%')->latest('id')->first();
        $next = $last ? ((int) last(explode('-', $last->batch_number))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateRunNumber(Warehouse $warehouse): string
    {
        $year = now()->format('Y');
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($warehouse->code ?: $warehouse->id)));
        $prefix = "DR-{$year}-{$code}-";

        $last = DeliveryRun::where('run_number', 'like', $prefix.'%')->latest('id')->first();
        $next = $last ? ((int) last(explode('-', $last->run_number))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
