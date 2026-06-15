<?php

use App\Enums\ItemStatus;
use App\Models\DeliveryDelayEvent;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function warehousePackageLedgerCreateAdmin(Warehouse $warehouse): User
{
    $admin = User::factory()->create([
        'name' => 'Warehouse Package Admin',
        'email' => 'warehouse-package-admin@example.test',
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::create([
        'name' => 'Warehouse Package Role',
        'slug' => 'warehouse-package-role-' . $admin->id,
        'description' => 'Warehouse package endpoint regression role',
        'is_system_role' => false,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    $permission = Permission::firstOrCreate(
        ['name' => 'warehouse.items.scan'],
        [
            'module' => 'warehouse',
            'action' => 'items.scan',
            'description' => 'Scan warehouse packages',
        ],
    );

    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $admin->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $admin->id,
    ]);
    $admin->flushPermissionCache();

    return $admin->fresh();
}

function warehousePackageLedgerCreatePackageWithDriverEta(string $deliveryMethod = ShipmentItem::DELIVERY_METHOD_DIRECT): array
{
    $warehouse = Warehouse::create([
        'name' => 'Accra Main',
        'code' => 'ACC',
        'address' => 'Accra',
        'is_active' => true,
    ]);

    $admin = warehousePackageLedgerCreateAdmin($warehouse);

    $driver = Driver::create([
        'name' => 'Yaw Delivery',
        'email' => 'yaw-delivery@example.test',
        'phone' => '+233240000001',
        'password' => Hash::make('password'),
        'vehicle_type' => 'motorbike',
        'status' => 'busy',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_DELIVERY],
    ]);

    $vendor = Vendor::create([
        'name' => 'Nana Shop',
        'phone' => '+233240000002',
        'email' => 'vendor-ledger@example.test',
        'pin_hash' => Hash::make('1234'),
        'is_phone_verified' => true,
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-LEDGER',
        'status' => 'out_for_delivery',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
    ]);

    $shipmentItem = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Ledger package',
        'quantity' => 1,
        'delivery_recipient_name' => 'Ama Recipient',
        'delivery_recipient_phone' => '+233240000003',
        'delivery_town' => 'Kasoa',
        'delivery_method' => $deliveryMethod,
        'status' => ItemStatus::OUT_FOR_DELIVERY->value,
        'tracking_code' => 'TRKLEDGER001',
    ]);

    $receipt = WarehouseReceipt::create([
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'status' => WarehouseReceipt::STATUS_FINALIZED,
        'finalized_at' => now(),
    ]);

    $receiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $shipmentItem->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
        'condition_status' => 'ok',
        'received_at' => now(),
    ]);

    $run = DeliveryRun::create([
        'run_number' => 'DR-LEDGER-001',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $driver->id,
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
        'assigned_at' => now(),
        'dispatched_at' => now(),
        'created_by_user_id' => $admin->id,
    ]);

    $stop = DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Ama Recipient',
        'recipient_phone' => '+233240000003',
        'town' => 'Kasoa',
        'total_packages' => 1,
        'status' => 'pending',
        'delivery_method' => $deliveryMethod,
    ]);

    $expectedDeliveryAt = now()->addHours(2)->seconds(0);
    $runItem = DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => $shipmentItem->id,
        'expected_quantity' => 1,
        'delivered_quantity' => 0,
        'status' => DeliveryRunItem::STATUS_PENDING,
        'expected_delivery_at' => $expectedDeliveryAt,
        'expected_delivery_set_at' => now()->seconds(0),
        'expected_delivery_set_by_driver_id' => $driver->id,
    ]);

    DeliveryDelayEvent::create([
        'delivery_run_item_id' => $runItem->id,
        'delivery_run_stop_id' => $stop->id,
        'delivery_run_id' => $run->id,
        'shipment_item_id' => $shipmentItem->id,
        'reason_label' => 'Traffic delay',
        'source' => DeliveryDelayEvent::SOURCE_RIDER_ETA,
        'actor_driver_id' => $driver->id,
        'old_expected_delivery_at' => now()->addHour(),
        'new_expected_delivery_at' => $expectedDeliveryAt,
    ]);

    return compact('admin', 'driver', 'receiptItem');
}

test('warehouse packages data loads delivery run ETA relations without crashing', function () {
    ['admin' => $admin, 'driver' => $driver] = warehousePackageLedgerCreatePackageWithDriverEta();

    $this->actingAs($admin, 'admin')
        ->getJson(route('warehouse.packages.data'))
        ->assertOk()
        ->assertJsonPath('data.0.tracking_code', 'TRKLEDGER001')
        ->assertJsonPath('data.0.delivery_run.driver', $driver->name)
        ->assertJsonPath('data.0.eta.set_by', $driver->name);
});

test('bus station packages data uses the same ledger relation path without crashing', function () {
    ['admin' => $admin, 'driver' => $driver] = warehousePackageLedgerCreatePackageWithDriverEta(ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF);

    $this->actingAs($admin, 'admin')
        ->getJson(route('warehouse.bus-station-packages.data'))
        ->assertOk()
        ->assertJsonPath('data.0.tracking_code', 'TRKLEDGER001')
        ->assertJsonPath('data.0.delivery_run.driver', $driver->name)
        ->assertJsonPath('data.0.eta.set_by', $driver->name);
});
