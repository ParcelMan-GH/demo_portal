<?php

use App\Enums\ItemStatus;
use App\Models\DeliveryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function sortBatchDeleteWarehouse(string $code = 'WH-DEL'): Warehouse
{
    return Warehouse::query()->create([
        'name' => 'Delete Test Warehouse ' . $code,
        'code' => $code,
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
}

function sortBatchDeleteAdmin(Warehouse $warehouse, array $permissions = ['warehouse.sorting.manage']): User
{
    $admin = User::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'Sort Batch Delete Role ' . $admin->id,
        'slug' => 'sort-batch-delete-role-' . $admin->id,
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissions as $permissionName) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $permissionName],
            [
                'module' => str($permissionName)->before('.')->toString(),
                'action' => str($permissionName)->after('.')->toString(),
                'description' => $permissionName,
            ],
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $admin->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $admin->id,
    ]);
    $admin->flushPermissionCache();

    return $admin->fresh();
}

function sortBatchDeleteBatch(Warehouse $origin, ?Warehouse $destination = null, array $overrides = []): SortBatch
{
    $destination ??= Warehouse::query()->create([
        'name' => 'Delete Destination Warehouse ' . uniqid(),
        'code' => 'DST-' . uniqid(),
        'is_active' => true,
    ]);

    return SortBatch::query()->create(array_merge([
        'batch_number' => 'SB-DELETE-' . uniqid(),
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $destination->id,
        'dispatch_mode' => SortBatch::DISPATCH_TRANSFER,
        'status' => SortBatch::STATUS_OPEN,
    ], $overrides));
}

function sortBatchDeleteAttachPackage(SortBatch $batch, Warehouse $warehouse): array
{
    $vendor = Vendor::query()->forceCreate([
        'name' => 'Delete Test Vendor',
        'phone' => '+23324' . random_int(1000000, 9999999),
        'is_active' => true,
    ]);

    $shipment = Shipment::query()->create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-DELETE-' . uniqid(),
        'status' => 'at_warehouse',
        'recipient_name' => 'Ama Recipient',
        'recipient_phone' => '+233240000000',
        'destination_mode' => 'single',
    ]);

    $shipmentItem = ShipmentItem::query()->create([
        'shipment_id' => $shipment->id,
        'description' => 'Delete test package',
        'quantity' => 1,
        'status' => ItemStatus::SORTED->value,
        'tracking_code' => 'TRKDEL' . random_int(10000, 99999),
    ]);

    $receipt = WarehouseReceipt::query()->create([
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'status' => WarehouseReceipt::STATUS_FINALIZED,
        'finalized_at' => now(),
    ]);

    $receiptItem = WarehouseReceiptItem::query()->create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $shipmentItem->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
        'received_at' => now(),
    ]);

    $batchItem = SortBatchItem::query()->create([
        'sort_batch_id' => $batch->id,
        'shipment_item_id' => $shipmentItem->id,
        'warehouse_receipt_item_id' => $receiptItem->id,
        'quantity_allocated' => 1,
        'added_at' => now(),
    ]);

    return compact('shipmentItem', 'batchItem');
}

test('admin delete returns friendly json when sort batch is already gone', function () {
    $warehouse = sortBatchDeleteWarehouse('WH-ADMIN-MISSING');
    $admin = sortBatchDeleteAdmin($warehouse);

    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.sort-batches.destroy', ['batch' => 999999]))
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'sort_batch_not_found')
        ->assertJsonPath('message', 'This sort batch was already deleted or is no longer available. The list has been refreshed.')
        ->assertJsonMissing(['message' => 'No query results for model [App\\Models\\SortBatch] 999999']);
});

test('warehouse delete returns friendly json when sort batch is already gone', function () {
    $warehouse = sortBatchDeleteWarehouse('WH-WAREHOUSE-MISSING');
    $admin = sortBatchDeleteAdmin($warehouse);

    $this->actingAs($admin, 'admin')
        ->deleteJson(route('warehouse.sorting.destroy', ['sortBatch' => 999999]))
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'sort_batch_not_found')
        ->assertJsonPath('message', 'This sort batch was already deleted or is no longer available. The list has been refreshed.')
        ->assertJsonMissing(['message' => 'No query results for model [App\\Models\\SortBatch] 999999']);
});

test('deletable sort batch is removed and active package items are returned to warehouse inventory', function () {
    $warehouse = sortBatchDeleteWarehouse('WH-DELETE-OK');
    $admin = sortBatchDeleteAdmin($warehouse);
    $batch = sortBatchDeleteBatch($warehouse);
    ['shipmentItem' => $shipmentItem, 'batchItem' => $batchItem] = sortBatchDeleteAttachPackage($batch, $warehouse);

    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.sort-batches.destroy', ['batch' => $batch->id]))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(SortBatch::query()->find($batch->id))->toBeNull()
        ->and(SortBatchItem::query()->find($batchItem->id))->toBeNull()
        ->and($shipmentItem->fresh()->status->value)->toBe(ItemStatus::AT_WAREHOUSE->value);
});

test('sort batch linked to a transport manifest cannot be deleted', function () {
    $warehouse = sortBatchDeleteWarehouse('WH-MANIFEST-LOCK');
    $destination = sortBatchDeleteWarehouse('WH-MANIFEST-DEST');
    $admin = sortBatchDeleteAdmin($warehouse);
    $batch = sortBatchDeleteBatch($warehouse, $destination);

    TransportManifest::query()->create([
        'manifest_number' => 'TM-DELETE-' . uniqid(),
        'sort_batch_id' => $batch->id,
        'origin_warehouse_id' => $warehouse->id,
        'destination_warehouse_id' => $destination->id,
        'status' => TransportManifest::STATUS_DRAFT,
    ]);

    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.sort-batches.destroy', ['batch' => $batch->id]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'This batch already has a transport manifest.');

    expect(SortBatch::query()->find($batch->id))->not->toBeNull();
});

test('sort batch linked to a delivery run cannot be deleted', function () {
    $warehouse = sortBatchDeleteWarehouse('WH-RUN-LOCK');
    $admin = sortBatchDeleteAdmin($warehouse);
    $batch = sortBatchDeleteBatch($warehouse);

    DeliveryRun::query()->create([
        'run_number' => 'DR-DELETE-' . uniqid(),
        'sort_batch_id' => $batch->id,
        'warehouse_id' => $warehouse->id,
        'status' => DeliveryRun::STATUS_DRAFT,
    ]);

    $this->actingAs($admin, 'admin')
        ->deleteJson(route('warehouse.sorting.destroy', ['sortBatch' => $batch->id]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'This batch already has a delivery run.');

    expect(SortBatch::query()->find($batch->id))->not->toBeNull();
});

test('sort batch list page handles stale delete responses', function () {
    $view = file_get_contents(resource_path('views/admin/sort-batches/index.blade.php'));

    expect($view)->toContain('sort_batch_not_found')
        ->and($view)->toContain('Unable to refresh sort batches.');
});
