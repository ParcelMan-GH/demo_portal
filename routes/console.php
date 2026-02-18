<?php

use App\Models\PickupAssignment;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('warehouse:receipts-backfill {--dry-run : Preview only} {--assignment_id=* : Limit to specific pickup assignment IDs}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $assignmentIds = collect((array) $this->option('assignment_id'))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    $query = PickupAssignment::query()
        ->with([
            'shipment:id',
            'shipment.items:id,shipment_id,quantity,tracking_code',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
        ])
        ->whereNotNull('received_at')
        ->where(function ($builder) {
            $builder->whereNotNull('received_warehouse_id')
                ->orWhereNotNull('target_warehouse_id');
        });

    if (!empty($assignmentIds)) {
        $query->whereIn('id', $assignmentIds);
    }

    $stats = [
        'scanned' => 0,
        'created' => 0,
        'existing' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    $resolveDiscrepancyType = function (int $expected, int $received): string {
        if ($expected === $received) {
            return 'none';
        }

        return $received < $expected ? 'missing' : 'excess';
    };

    $query->orderBy('id')->chunkById(100, function ($assignments) use (&$stats, $dryRun, $resolveDiscrepancyType) {
        foreach ($assignments as $assignment) {
            $stats['scanned']++;

            $warehouseId = $assignment->received_warehouse_id ?: $assignment->target_warehouse_id;
            if (!$warehouseId || !$assignment->shipment) {
                $stats['skipped']++;
                $this->warn("Skipping assignment #{$assignment->id}: missing warehouse or shipment.");
                continue;
            }

            $alreadyExists = WarehouseReceipt::query()
                ->where('pickup_assignment_id', $assignment->id)
                ->exists();

            if ($alreadyExists) {
                $stats['existing']++;
                continue;
            }

            if ($dryRun) {
                $stats['created']++;
                continue;
            }

            try {
                DB::transaction(function () use ($assignment, $warehouseId, $resolveDiscrepancyType, &$stats) {
                    $hasDiscrepancy = false;

                    $receipt = WarehouseReceipt::query()->create([
                        'pickup_assignment_id' => $assignment->id,
                        'warehouse_id' => $warehouseId,
                        'status' => WarehouseReceipt::STATUS_FINALIZED,
                        'started_by_user_id' => $assignment->received_by_user_id,
                        'finalized_by_user_id' => $assignment->received_by_user_id,
                        'approved_by_user_id' => null,
                        'approval_reason' => null,
                        'notes' => $assignment->receive_notes,
                        'started_at' => $assignment->arrived_warehouse_at ?: $assignment->received_at,
                        'finalized_at' => $assignment->received_at,
                        'created_at' => $assignment->received_at,
                        'updated_at' => $assignment->received_at,
                    ]);

                    $confirmations = $assignment->itemConfirmations->keyBy('shipment_item_id');

                    foreach ($assignment->shipment->items as $item) {
                        $confirmation = $confirmations->get($item->id);
                        $expected = max(0, (int) ($confirmation?->expected_quantity ?? $item->quantity));
                        $received = max(0, (int) ($confirmation?->confirmed_quantity ?? $expected));
                        $discrepancyType = $resolveDiscrepancyType($expected, $received);
                        $conditionStatus = $discrepancyType === 'none' ? 'ok' : 'partial';
                        $hasDiscrepancy = $hasDiscrepancy || $discrepancyType !== 'none';

                        WarehouseReceiptItem::query()->create([
                            'warehouse_receipt_id' => $receipt->id,
                            'shipment_item_id' => $item->id,
                            'expected_quantity' => $expected,
                            'received_quantity' => $received,
                            'damaged_quantity' => 0,
                            'discrepancy_type' => $discrepancyType,
                            'condition_status' => $conditionStatus,
                            'notes' => $confirmation?->notes,
                            'received_by_user_id' => $assignment->received_by_user_id,
                            'received_at' => $assignment->received_at,
                            'barcode_value' => $item->tracking_code,
                            'barcode_format' => 'code128',
                            'barcode_printed_at' => null,
                            'barcode_print_count' => 0,
                            'created_at' => $assignment->received_at,
                            'updated_at' => $assignment->received_at,
                        ]);
                    }

                    if ($hasDiscrepancy) {
                        $receipt->update([
                            'status' => WarehouseReceipt::STATUS_DISCREPANCY_OPEN,
                        ]);
                    }

                    $stats['created']++;
                });
            } catch (\Throwable $exception) {
                $stats['failed']++;
                $this->error("Failed assignment #{$assignment->id}: {$exception->getMessage()}");
            }
        }
    });

    $this->newLine();
    $this->line('Backfill summary');
    $this->table(['Metric', 'Count'], [
        ['Scanned assignments', $stats['scanned']],
        [$dryRun ? 'Would create receipts' : 'Created receipts', $stats['created']],
        ['Already existing receipts', $stats['existing']],
        ['Skipped assignments', $stats['skipped']],
        ['Failed assignments', $stats['failed']],
    ]);
})->purpose('Backfill warehouse receipts for historical received pickup assignments');

Artisan::command('warehouse:dispatch-backfill {--dry-run : Preview only} {--batch_id=* : Limit to specific sort batch IDs}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $batchIds = collect((array) $this->option('batch_id'))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    /** @var WarehouseTransportService $transportService */
    $transportService = app(WarehouseTransportService::class);
    /** @var WarehouseDeliveryService $deliveryService */
    $deliveryService = app(WarehouseDeliveryService::class);

    $query = SortBatch::query()
        ->with([
            'originWarehouse:id,name,code',
            'destinationWarehouse:id,name,code',
            'createdBy:id,name,warehouse_id,is_active',
            'sealedBy:id,name,warehouse_id,is_active',
        ])
        ->where('status', SortBatch::STATUS_SEALED)
        ->orderBy('id');

    if (!empty($batchIds)) {
        $query->whereIn('id', $batchIds);
    }

    $stats = [
        'scanned' => 0,
        'transport_created' => 0,
        'delivery_created' => 0,
        'existing' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    $query->chunkById(100, function ($batches) use (&$stats, $dryRun, $transportService, $deliveryService) {
        foreach ($batches as $batch) {
            $stats['scanned']++;

            $isTransfer = $batch->dispatch_mode === SortBatch::DISPATCH_TRANSFER;
            $isLocalDelivery = $batch->dispatch_mode === SortBatch::DISPATCH_LOCAL_DELIVERY;

            if (!$isTransfer && !$isLocalDelivery) {
                $stats['skipped']++;
                $this->warn("Skipping batch #{$batch->id}: unsupported dispatch mode '{$batch->dispatch_mode}'.");
                continue;
            }

            if ($isTransfer && $batch->transportManifest()->exists()) {
                $stats['existing']++;
                continue;
            }

            if ($isLocalDelivery && $batch->deliveryRun()->exists()) {
                $stats['existing']++;
                continue;
            }

            $originWarehouse = $batch->originWarehouse;
            if (!$originWarehouse) {
                $stats['skipped']++;
                $this->warn("Skipping batch #{$batch->id}: origin warehouse not found.");
                continue;
            }

            $actor = $batch->createdBy ?: $batch->sealedBy;
            if (!$actor) {
                $actor = User::query()
                    ->where('warehouse_id', $originWarehouse->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();
            }

            if (!$actor) {
                $stats['skipped']++;
                $this->warn("Skipping batch #{$batch->id}: no active warehouse user available as actor.");
                continue;
            }

            if ($dryRun) {
                if ($isTransfer) {
                    $stats['transport_created']++;
                } else {
                    $stats['delivery_created']++;
                }
                continue;
            }

            try {
                $result = $isTransfer
                    ? $transportService->createManifest($batch, $originWarehouse, $actor)
                    : $deliveryService->createRun($batch, $originWarehouse, $actor);

                if (($result['success'] ?? false) !== true) {
                    $stats['failed']++;
                    $this->error("Failed batch #{$batch->id}: " . ($result['message'] ?? 'Unknown error'));
                    continue;
                }

                if ($isTransfer) {
                    $stats['transport_created']++;
                } else {
                    $stats['delivery_created']++;
                }
            } catch (\Throwable $exception) {
                $stats['failed']++;
                $this->error("Failed batch #{$batch->id}: {$exception->getMessage()}");
            }
        }
    });

    $transportLabel = $dryRun ? 'Would create transport manifests' : 'Created transport manifests';
    $deliveryLabel = $dryRun ? 'Would create delivery runs' : 'Created delivery runs';

    $this->newLine();
    $this->line('Dispatch backfill summary');
    $this->table(['Metric', 'Count'], [
        ['Scanned sort batches', $stats['scanned']],
        [$transportLabel, $stats['transport_created']],
        [$deliveryLabel, $stats['delivery_created']],
        ['Already existing manifests/runs', $stats['existing']],
        ['Skipped batches', $stats['skipped']],
        ['Failed batches', $stats['failed']],
    ]);
})->purpose('Backfill transport manifests and delivery runs for sealed sort batches');
