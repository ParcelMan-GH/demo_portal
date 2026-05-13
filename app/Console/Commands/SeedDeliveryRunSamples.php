<?php

namespace App\Console\Commands;

use App\Enums\FulfillmentType;
use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\District;
use App\Models\Driver;
use App\Models\Region;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\Warehouse\RecipientPaymentService;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehouseSortingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SeedDeliveryRunSamples extends Command
{
    protected $signature = 'deliveries:seed-sample-runs
                            {--count=50}
                            {--warehouse-id=1}
                            {--driver-id=1}
                            {--vendor-id=1}';

    protected $description = 'Create sample delivery runs through receipt, sorting, sealing, and delivery-run creation.';

    private const MARKER = 'DEV-SAMPLE-DELIVERY-RUN';

    public function handle(
        WarehouseSortingService $sortingService,
        WarehouseDeliveryService $deliveryService,
        RecipientPaymentService $paymentService,
    ): int {
        $count = max(1, (int) $this->option('count'));
        $warehouse = Warehouse::query()->find((int) $this->option('warehouse-id'));
        $driver = Driver::query()->find((int) $this->option('driver-id'));
        $vendor = Vendor::query()->find((int) $this->option('vendor-id'));
        $user = User::query()->orderBy('id')->first();

        if (!$warehouse || !$driver || !$vendor || !$user) {
            $this->error('Missing warehouse, driver, vendor, or user. Check the command options.');
            return self::FAILURE;
        }

        if (!$driver->hasCapability(Driver::CAPABILITY_DELIVERY)) {
            $this->error("Driver #{$driver->id} is not delivery-capable.");
            return self::FAILURE;
        }

        $regions = Region::query()->with('districts')->get();
        if ($regions->isEmpty()) {
            $this->error('No regions/districts found.');
            return self::FAILURE;
        }

        $this->info("Creating {$count} delivery runs for {$warehouse->name} using {$driver->name}.");

        $createdRunIds = [];
        $statusCounts = [];
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 1; $i <= $count; $i++) {
            $targetStatus = $this->targetStatus($i);

            try {
                $run = DB::transaction(function () use (
                    $i,
                    $warehouse,
                    $driver,
                    $vendor,
                    $user,
                    $regions,
                    $sortingService,
                    $deliveryService,
                    $paymentService,
                    $targetStatus
                ) {
                    $sampleDate = now()->subDays(($i - 1) % 12)->subMinutes($i * 9);
                    $shipment = $this->createShipment($i, $warehouse, $vendor, $user, $regions, $sampleDate);
                    $receiptItemIds = $this->createReceiptPipeline($i, $shipment, $warehouse, $user, $regions, $sampleDate);

                    $batchResult = $sortingService->createBatch(
                        originWarehouse: $warehouse,
                        destinationWarehouse: null,
                        user: $user,
                        dispatchMode: SortBatch::DISPATCH_LOCAL_DELIVERY,
                        notes: self::MARKER . " sample batch {$i}",
                    );

                    $this->assertSuccess($batchResult, "batch create {$i}");
                    /** @var SortBatch $batch */
                    $batch = $batchResult['data']['batch'];

                    $addResult = $sortingService->addItems($batch, $warehouse, $user, $receiptItemIds);
                    $this->assertSuccess($addResult, "batch add items {$i}");

                    $batch = $batch->fresh('activeItems.shipmentItem');
                    $batch->activeItems->each(fn ($batchItem) => $paymentService->ensureTaskForSortBatchItem($batchItem));
                    $this->syncTasksPaid($batch->fresh('activeItems.shipmentItem'));

                    $sealResult = $sortingService->sealBatch($batch->fresh(), $warehouse, $user);
                    $this->assertSuccess($sealResult, "batch seal {$i}");

                    $runResult = $deliveryService->createRun($batch->fresh(), $warehouse, $user);
                    $this->assertSuccess($runResult, "delivery run create {$i}");

                    /** @var DeliveryRun $run */
                    $run = $runResult['data']['run']->fresh(['stops.items.shipmentItem.shipment', 'items.shipmentItem.shipment']);
                    $run->update(['notes' => self::MARKER . " sample run {$i}"]);
                    $this->refreshStopPackageCounts($run);
                    $this->applyRunState($run->fresh(['stops.items.shipmentItem.shipment', 'items.shipmentItem.shipment']), $targetStatus, $warehouse, $driver, $user, $sampleDate);

                    return $run->fresh(['stops', 'items']);
                });

                $createdRunIds[] = $run->id;
                $statusCounts[$run->status] = ($statusCounts[$run->status] ?? 0) + 1;
            } catch (\Throwable $exception) {
                $this->newLine();
                $this->error("Run {$i} failed: {$exception->getMessage()}");
                return self::FAILURE;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Created delivery run IDs: ' . implode(', ', $createdRunIds));
        $this->info('Status breakdown: ' . json_encode($statusCounts, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function createShipment(int $index, Warehouse $warehouse, Vendor $vendor, User $user, $regions, Carbon $sampleDate): Shipment
    {
        $destination = $this->destination($regions, $index);

        return Shipment::query()->create([
            'vendor_id' => $vendor->id,
            'status' => ShipmentStatus::AT_WAREHOUSE,
            'source' => ShipmentSource::WAREHOUSE_WALKIN,
            'fulfillment_type' => FulfillmentType::WAREHOUSE,
            'created_by_user_id' => $user->id,
            'destination_mode' => ShipmentDestinationMode::PER_ITEM,
            'pickup_contact_name' => $vendor->name,
            'pickup_contact_phone' => $vendor->phone,
            'pickup_town' => $warehouse->name,
            'delivery_recipient_name' => $destination['name'],
            'delivery_recipient_phone' => $destination['phone'],
            'delivery_region_id' => $destination['region_id'],
            'delivery_district_id' => $destination['district_id'],
            'delivery_town' => $destination['town'],
            'delivery_landmark' => $destination['landmark'],
            'delivery_preference' => 'deliver',
            'sender_notes' => self::MARKER . " shipment {$index}",
            'submitted_at' => $sampleDate,
            'created_at' => $sampleDate,
            'updated_at' => $sampleDate,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function createReceiptPipeline(int $index, Shipment $shipment, Warehouse $warehouse, User $user, $regions, Carbon $sampleDate): array
    {
        $receipt = WarehouseReceipt::query()->create([
            'shipment_id' => $shipment->id,
            'warehouse_id' => $warehouse->id,
            'status' => WarehouseReceipt::STATUS_FINALIZED,
            'started_by_user_id' => $user->id,
            'finalized_by_user_id' => $user->id,
            'notes' => self::MARKER . " receipt {$index}",
            'started_at' => $sampleDate->copy()->addMinutes(5),
            'finalized_at' => $sampleDate->copy()->addMinutes(15),
            'created_at' => $sampleDate->copy()->addMinutes(5),
            'updated_at' => $sampleDate->copy()->addMinutes(15),
        ]);

        $itemCount = ($index % 4) + 1;
        $receiptItemIds = [];

        for ($itemIndex = 1; $itemIndex <= $itemCount; $itemIndex++) {
            $quantity = (($index + $itemIndex) % 3) + 1;
            $destination = $this->destination($regions, $index + $itemIndex);

            $item = ShipmentItem::query()->create([
                'shipment_id' => $shipment->id,
                'description' => $this->description($index, $itemIndex),
                'quantity' => $quantity,
                'delivery_recipient_name' => $destination['name'],
                'delivery_recipient_phone' => $destination['phone'],
                'delivery_region_id' => $destination['region_id'],
                'delivery_district_id' => $destination['district_id'],
                'delivery_town' => $destination['town'],
                'delivery_landmark' => $destination['landmark'],
                'fulfillment_type' => FulfillmentType::WAREHOUSE,
                'delivery_preference' => 'deliver',
                'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
                'status' => ItemStatus::AT_WAREHOUSE,
                'created_at' => $sampleDate->copy()->addMinutes(10),
                'updated_at' => $sampleDate->copy()->addMinutes(10),
            ]);

            ShipmentItemTracking::query()->create([
                'shipment_item_id' => $item->id,
                'status' => ItemStatus::AT_WAREHOUSE->value,
                'location' => $warehouse->name,
                'notes' => self::MARKER . ' item received at warehouse.',
                'meta' => ['seed_sample' => true],
                'created_by' => "user:{$user->id}",
                'created_at' => $sampleDate->copy()->addMinutes(15),
            ]);

            $receiptItem = WarehouseReceiptItem::query()->create([
                'warehouse_receipt_id' => $receipt->id,
                'shipment_item_id' => $item->id,
                'expected_quantity' => $quantity,
                'received_quantity' => $quantity,
                'damaged_quantity' => 0,
                'discrepancy_type' => 'none',
                'condition_status' => 'ok',
                'notes' => self::MARKER . ' receipt item',
                'received_by_user_id' => $user->id,
                'received_at' => $sampleDate->copy()->addMinutes(15),
                'barcode_value' => $item->tracking_code,
                'barcode_format' => 'code128',
                'barcode_printed_at' => $sampleDate->copy()->addMinutes(16),
                'barcode_print_count' => 1,
                'created_at' => $sampleDate->copy()->addMinutes(15),
                'updated_at' => $sampleDate->copy()->addMinutes(16),
            ]);

            for ($labelIndex = 1; $labelIndex <= $quantity; $labelIndex++) {
                WarehouseReceiptItemLabel::query()->create([
                    'warehouse_receipt_item_id' => $receiptItem->id,
                    'barcode_value' => sprintf('%s-%03d', $item->tracking_code, $labelIndex),
                    'label_index' => $labelIndex,
                    'labels_total' => $quantity,
                    'label_type' => 'item',
                    'printed_at' => $sampleDate->copy()->addMinutes(16),
                    'print_count' => 1,
                    'created_at' => $sampleDate->copy()->addMinutes(16),
                    'updated_at' => $sampleDate->copy()->addMinutes(16),
                ]);
            }

            ShipmentCharge::query()->create([
                'shipment_id' => $shipment->id,
                'shipment_item_id' => $item->id,
                'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
                'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
                'direction' => ShipmentCharge::DIRECTION_REVENUE,
                'due_stage' => ShipmentCharge::STAGE_BEFORE_DELIVERY,
                'amount' => $this->deliveryFee($index, $itemIndex),
                'currency' => 'GHS',
                'status' => ShipmentCharge::STATUS_PAID,
                'paid_at' => $sampleDate->copy()->addMinutes(30),
                'payment_method' => 'momo',
                'payment_reference' => sprintf('SAMPLE-DR-%02d-%02d', $index, $itemIndex),
                'recorded_by_admin_id' => $user->id,
                'notes' => self::MARKER . ' paid recipient delivery fee',
                'created_at' => $sampleDate->copy()->addMinutes(25),
                'updated_at' => $sampleDate->copy()->addMinutes(30),
            ]);

            $receiptItemIds[] = $receiptItem->id;
        }

        return $receiptItemIds;
    }

    private function syncTasksPaid(SortBatch $batch): void
    {
        $batch->loadMissing('activeItems.shipmentItem.charges');

        foreach ($batch->activeItems as $batchItem) {
            $charge = $batchItem->shipmentItem?->charges()
                ->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)
                ->where('payer_type', ShipmentCharge::PAYER_RECIPIENT)
                ->latest('id')
                ->first();

            if (!$charge) {
                continue;
            }

            DB::table('recipient_payment_tasks')
                ->where('sort_batch_item_id', $batchItem->id)
                ->update([
                    'shipment_charge_id' => $charge->id,
                    'status' => 'paid',
                    'negotiated_amount' => $charge->amount,
                    'paid_at' => $charge->paid_at,
                    'payment_reference' => $charge->payment_reference,
                    'updated_at' => now(),
                ]);
        }
    }

    private function applyRunState(DeliveryRun $run, string $targetStatus, Warehouse $warehouse, Driver $driver, User $user, Carbon $sampleDate): void
    {
        if ($targetStatus === DeliveryRun::STATUS_DRAFT) {
            return;
        }

        $run->update([
            'assigned_driver_id' => $driver->id,
            'assigned_at' => $sampleDate->copy()->addHours(1),
            'status' => DeliveryRun::STATUS_ASSIGNED,
        ]);

        if ($targetStatus === DeliveryRun::STATUS_ASSIGNED) {
            return;
        }

        if ($targetStatus === DeliveryRun::STATUS_CANCELLED) {
            $run->update([
                'status' => DeliveryRun::STATUS_CANCELLED,
                'notes' => trim(($run->notes ? $run->notes . "\n" : '') . 'Sample cancelled before dispatch.'),
            ]);
            return;
        }

        $run->update([
            'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
            'dispatched_at' => $sampleDate->copy()->addHours(2),
        ]);

        foreach ($run->items as $runItem) {
            $item = $runItem->shipmentItem;
            if (!$item) {
                continue;
            }

            $item->update(['status' => ItemStatus::OUT_FOR_DELIVERY]);
            $item->shipment?->update(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);

            $this->track($item, ItemStatus::OUT_FOR_DELIVERY->value, $warehouse, $user, $run, $sampleDate->copy()->addHours(2));
        }

        if ($targetStatus === DeliveryRun::STATUS_OUT_FOR_DELIVERY) {
            $firstStop = $run->stops->first();
            if ($firstStop) {
                $firstStop->update([
                    'status' => DeliveryRunStop::STATUS_ARRIVED,
                    'arrived_at' => $sampleDate->copy()->addHours(3),
                    'verification_code_sent_at' => $sampleDate->copy()->addHours(3),
                    'verification_code_expires_at' => $sampleDate->copy()->addHours(3)->addMinutes(15),
                ]);
            }
            return;
        }

        $stopsToDeliver = $targetStatus === DeliveryRun::STATUS_COMPLETED
            ? $run->stops
            : $run->stops->take(max(1, (int) floor($run->stops->count() / 2)));

        foreach ($stopsToDeliver as $stop) {
            $stop->update([
                'status' => DeliveryRunStop::STATUS_DELIVERED,
                'arrived_at' => $sampleDate->copy()->addHours(3),
                'delivered_at' => $sampleDate->copy()->addHours(4),
                'delivery_latitude' => '5.' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'delivery_longitude' => '-0.' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'confirmation_notes' => self::MARKER . ' sample delivered stop.',
                'proof_photo_path' => $this->sampleStopProofPhotoPath(),
                'proof_photo_size' => 142_320,
            ]);

            foreach ($stop->items as $runItem) {
                $runItem->update([
                    'status' => DeliveryRunItem::STATUS_DELIVERED,
                    'delivered_quantity' => $runItem->expected_quantity,
                    'delivered_at' => $sampleDate->copy()->addHours(4),
                ]);

                $item = $runItem->shipmentItem;
                if ($item) {
                    $item->update(['status' => ItemStatus::DELIVERED]);
                    $this->track($item, ItemStatus::DELIVERED->value, $warehouse, $user, $run, $sampleDate->copy()->addHours(4));
                }
            }
        }

        if ($targetStatus === DeliveryRun::STATUS_COMPLETED) {
            $run->update([
                'status' => DeliveryRun::STATUS_COMPLETED,
                'completed_at' => $sampleDate->copy()->addHours(4),
            ]);

            $run->items->pluck('shipmentItem.shipment')->filter()->unique('id')->each(
                fn (Shipment $shipment) => $shipment->update(['status' => ShipmentStatus::DELIVERED])
            );
            return;
        }

        $run->update(['status' => DeliveryRun::STATUS_PARTIALLY_DELIVERED]);
    }

    private function refreshStopPackageCounts(DeliveryRun $run): void
    {
        $run->loadMissing('stops.items');

        foreach ($run->stops as $stop) {
            $stop->update([
                'total_packages' => $stop->items->sum(fn (DeliveryRunItem $item) => (int) $item->expected_quantity),
            ]);
        }
    }

    private function track(ShipmentItem $item, string $status, Warehouse $warehouse, User $user, DeliveryRun $run, Carbon $at): void
    {
        ShipmentItemTracking::query()->create([
            'shipment_item_id' => $item->id,
            'status' => $status,
            'location' => $warehouse->name,
            'notes' => self::MARKER . " {$status} for run {$run->run_number}.",
            'meta' => [
                'delivery_run_id' => $run->id,
                'delivery_run_number' => $run->run_number,
                'seed_sample' => true,
            ],
            'created_by' => "user:{$user->id}",
            'created_at' => $at,
        ]);
    }

    private function targetStatus(int $index): string
    {
        return match (true) {
            $index <= 8 => DeliveryRun::STATUS_DRAFT,
            $index <= 16 => DeliveryRun::STATUS_ASSIGNED,
            $index <= 26 => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
            $index <= 36 => DeliveryRun::STATUS_PARTIALLY_DELIVERED,
            $index <= 45 => DeliveryRun::STATUS_COMPLETED,
            default => DeliveryRun::STATUS_CANCELLED,
        };
    }

    private function destination($regions, int $seed): array
    {
        $region = $regions[($seed - 1) % $regions->count()];
        $districts = $region->districts->isNotEmpty() ? $region->districts : District::query()->where('region_id', $region->id)->get();
        $district = $districts->isNotEmpty() ? $districts[($seed - 1) % $districts->count()] : null;

            return [
            'name' => $this->names()[($seed - 1) % count($this->names())],
            'phone' => $this->testRecipientPhone($seed),
            'region_id' => $region->id,
            'district_id' => $district?->id,
            'town' => $this->towns()[($seed - 1) % count($this->towns())],
            'landmark' => $this->landmarks()[($seed - 1) % count($this->landmarks())],
        ];
    }

    private function description(int $runIndex, int $itemIndex): string
    {
        $items = ['Shoes', 'Cosmetics package', 'Phone accessories', 'Rice bag', 'Clothing bundle', 'Laptop bag', 'Kitchenware', 'Books', 'Watch box'];
        return $items[($runIndex + $itemIndex - 2) % count($items)];
    }

    private function deliveryFee(int $runIndex, int $itemIndex): float
    {
        $fees = [12.00, 15.00, 18.50, 20.00, 25.00, 30.00, 40.00, 50.00];
        return $fees[($runIndex + $itemIndex - 2) % count($fees)];
    }

    private function phoneSuffix(int $seed): string
    {
        $base = 200000000 + ($seed * 13791);
        return (string) $base;
    }

    private function sampleStopProofPhotoPath(): string
    {
        return 'recipient-payment-receipts/tKYjj2m56TCnXpCyitAqkVWPZUhjCrqn5epPTeD9.jpg';
    }

    private function testRecipientPhone(int $seed): string
    {
        $testPhones = [
            '0211111111',
            '0212222222',
        ];

        return $testPhones[($seed - 1) % count($testPhones)];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function assertSuccess(array $result, string $context): void
    {
        if (empty($result['success'])) {
            throw new \RuntimeException("{$context}: " . ($result['message'] ?? 'failed'));
        }
    }

    private function names(): array
    {
        return ['Yaw Nyarko', 'Akosua Mensah', 'George Boateng', 'Ama Owusu', 'Kofi Darko', 'Efua Appiah', 'Nana Sarpong', 'Esi Asante'];
    }

    private function towns(): array
    {
        return ['East Legon', 'Lapaz', 'Madina', 'Osu', 'Adenta', 'Dansoman', 'Spintex', 'Tema Community 5', 'Achimota'];
    }

    private function landmarks(): array
    {
        return ['Near the pharmacy', 'Blue gate', 'Opposite Shell', 'Second house after junction', 'Behind the market', 'Call on arrival'];
    }
}
