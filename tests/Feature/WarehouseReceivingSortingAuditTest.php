<?php

use App\Models\SortBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseSortingService;

test('pending receipts query is scoped to picked up and not yet received assignments', function () {
    $warehouse = new Warehouse(['id' => 77]);
    $warehouse->id = 77;

    /** @var WarehousePortalService $service */
    $service = app(WarehousePortalService::class);
    $query = $service->pendingReceiptsQuery($warehouse);
    $sql = $query->toSql();

    expect($sql)->toContain('target_warehouse_id')
        ->and($sql)->toContain('picked_up_at')
        ->and($sql)->toContain('received_at')
        ->and($sql)->toContain('status');
});

test('received items query uses warehouse receipt items as source of truth', function () {
    $warehouse = new Warehouse(['id' => 88]);
    $warehouse->id = 88;

    /** @var WarehousePortalService $service */
    $service = app(WarehousePortalService::class);
    $sql = $service->receivedItemsQuery($warehouse)->toSql();

    expect($sql)->toContain('warehouse_receipt_items')
        ->and($sql)->toContain('exists');
});

test('sorting eligible items query excludes zero received quantity and active batched items', function () {
    $warehouse = new Warehouse(['id' => 99]);
    $warehouse->id = 99;

    /** @var WarehouseSortingService $service */
    $service = app(WarehouseSortingService::class);
    $sql = $service->eligibleItemsQuery($warehouse)->toSql();

    expect($sql)->toContain('received_quantity')
        ->and($sql)->toContain('not exists');
});

test('sorting create batch rejects invalid dispatch mode before persistence', function () {
    $originWarehouse = new Warehouse(['id' => 1, 'code' => 'WH1', 'is_active' => true]);
    $originWarehouse->id = 1;
    $user = new User(['id' => 1]);
    $user->id = 1;

    /** @var WarehouseSortingService $service */
    $service = app(WarehouseSortingService::class);
    $result = $service->createBatch(
        originWarehouse: $originWarehouse,
        destinationWarehouse: null,
        user: $user,
        dispatchMode: 'invalid_mode'
    );

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Invalid dispatch mode');
});

test('sorting add items requires at least one item id', function () {
    $warehouse = new Warehouse(['id' => 2]);
    $warehouse->id = 2;
    $batch = new SortBatch(['origin_warehouse_id' => 2, 'status' => SortBatch::STATUS_OPEN]);
    $batch->origin_warehouse_id = 2;
    $batch->status = SortBatch::STATUS_OPEN;
    $user = new User(['id' => 2]);
    $user->id = 2;

    /** @var WarehouseSortingService $service */
    $service = app(WarehouseSortingService::class);
    $result = $service->addItems(
        batch: $batch,
        warehouse: $warehouse,
        user: $user,
        warehouseReceiptItemIds: []
    );

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Select at least one item');
});
