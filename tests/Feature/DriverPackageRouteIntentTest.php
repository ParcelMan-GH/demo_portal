<?php

use App\Models\Driver;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\LabelCustodyEvent;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

function driverPackageRouteIntentBuildSchema(): void
{
    foreach ([
        'delivery_run_items',
        'delivery_run_stops',
        'delivery_runs',
        'label_custody_events',
        'warehouse_receipt_item_labels',
        'warehouse_receipt_items',
        'warehouse_receipts',
        'shipment_items',
        'shipments',
        'vendors',
        'warehouses',
        'drivers',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->unique();
        $table->string('password');
        $table->string('vehicle_type')->default('van');
        $table->string('vehicle_number')->nullable();
        $table->string('license_number')->nullable();
        $table->string('base_location')->nullable();
        $table->string('status')->default('available');
        $table->boolean('is_active')->default(true);
        $table->json('task_capabilities')->nullable();
        $table->timestamp('last_login_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->nullable();
        $table->string('address')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('business_name')->nullable();
        $table->string('phone')->unique();
        $table->string('email')->nullable();
        $table->string('pin_hash');
        $table->boolean('is_phone_verified')->default(false);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('vendor_id');
        $table->string('shipment_number')->unique();
        $table->string('status')->default('draft');
        $table->string('source')->default('vendor_app');
        $table->string('fulfillment_type')->nullable();
        $table->string('destination_mode')->default('single');
        $table->string('pickup_contact_phone')->nullable();
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id');
        $table->string('description')->nullable();
        $table->unsignedInteger('quantity')->default(1);
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->string('delivery_method')->nullable();
        $table->string('status')->default('pending');
        $table->string('tracking_code')->nullable();
        $table->timestamps();
    });

    Schema::create('warehouse_receipts', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('warehouse_receipt_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('warehouse_receipt_id');
        $table->unsignedBigInteger('shipment_item_id');
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('received_quantity')->default(1);
        $table->unsignedInteger('damaged_quantity')->default(0);
        $table->string('discrepancy_type')->default('none');
        $table->string('condition_status')->default('ok');
        $table->text('notes')->nullable();
        $table->unsignedBigInteger('received_by_user_id')->nullable();
        $table->timestamp('received_at')->nullable();
        $table->string('barcode_value')->nullable();
        $table->string('barcode_format')->default('code128');
        $table->timestamp('barcode_printed_at')->nullable();
        $table->unsignedInteger('barcode_print_count')->default(0);
        $table->timestamps();
    });

    Schema::create('warehouse_receipt_item_labels', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('warehouse_receipt_item_id');
        $table->string('barcode_value')->unique();
        $table->unsignedInteger('label_index')->nullable();
        $table->unsignedInteger('labels_total')->default(1);
        $table->string('label_type')->default('sealed');
        $table->timestamp('printed_at')->nullable();
        $table->unsignedInteger('print_count')->default(0);
        $table->timestamps();
    });

    Schema::create('label_custody_events', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('warehouse_receipt_item_label_id');
        $table->string('event_type', 20);
        $table->unsignedBigInteger('driver_id')->nullable();
        $table->unsignedBigInteger('scanned_by_user_id')->nullable();
        $table->string('location_note')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_runs', function (Blueprint $table) {
        $table->id();
        $table->string('run_number')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->string('status')->default('draft');
        $table->unsignedBigInteger('assigned_driver_id')->nullable();
        $table->timestamp('dispatched_at')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_stops', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id');
        $table->string('recipient_name')->nullable();
        $table->string('recipient_phone')->nullable();
        $table->unsignedBigInteger('region_id')->nullable();
        $table->unsignedBigInteger('district_id')->nullable();
        $table->string('town')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('gh_post_address')->nullable();
        $table->string('landmark')->nullable();
        $table->unsignedInteger('total_packages')->default(0);
        $table->string('status')->default('pending');
        $table->string('delivery_method')->default('direct');
        $table->timestamps();
    });

    Schema::create('delivery_run_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id');
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->unsignedBigInteger('shipment_item_id');
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('delivered_quantity')->default(0);
        $table->string('status')->default('pending');
        $table->text('notes')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
    });
}

function driverPackageRouteIntentCreateDriver(): Driver
{
    return Driver::create([
        'name' => 'Driver Mensah',
        'email' => 'driver.route.test@example.com',
        'phone' => '+233201111111',
        'password' => Hash::make('password123'),
        'vehicle_type' => 'van',
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_DELIVERY, Driver::CAPABILITY_BUS_HANDOFF],
    ]);
}

function driverPackageRouteIntentCreateVendor(): Vendor
{
    $vendor = new Vendor([
        'name' => 'Vendor Test',
        'phone' => '+233241234567',
        'email' => 'vendor.route.test@example.com',
        'is_active' => true,
    ]);

    $vendor->pin_hash = Hash::make('1234');
    $vendor->is_phone_verified = true;
    $vendor->save();

    return $vendor;
}

beforeEach(function () {
    driverPackageRouteIntentBuildSchema();

    $driver = driverPackageRouteIntentCreateDriver();
    Sanctum::actingAs($driver);

    $this->driver = $driver;
});

test('driver my packages exposes route intent for bus handoff packages', function () {
    $vendor = driverPackageRouteIntentCreateVendor();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00991',
        'status' => 'out_for_delivery',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
    ]);

    $busItem = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Laptop',
        'quantity' => 1,
        'delivery_recipient_name' => 'Ama Mensah',
        'delivery_recipient_phone' => '+233541112223',
        'delivery_town' => 'Madina',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
        'status' => 'out_for_delivery',
        'tracking_code' => 'TRKBUS1234',
    ]);

    $directItem = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Bag',
        'quantity' => 1,
        'delivery_recipient_name' => 'Mark Asante',
        'delivery_recipient_phone' => '+233201234567',
        'delivery_town' => 'Accra',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => 'out_for_delivery',
        'tracking_code' => 'TRKDIR1234',
    ]);

    $receipt = WarehouseReceipt::create();

    $busReceiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $busItem->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
    ]);

    $directReceiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $directItem->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
    ]);

    $busLabel = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $busReceiptItem->id,
        'barcode_value' => 'TRKBUS1234-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'sealed',
    ]);

    $directLabel = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $directReceiptItem->id,
        'barcode_value' => 'TRKDIR1234-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'sealed',
    ]);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $busLabel->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $directLabel->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/driver/my-packages');

    $response->assertOk()->assertJsonPath('success', true);

    $packages = collect($response->json('data.packages'))->keyBy('barcode');
    $busPackage = $packages->get('TRKBUS1234-001');
    $directPackage = $packages->get('TRKDIR1234-001');

    expect($busPackage)->not->toBeNull()
        ->and($busPackage['delivery_method'])->toBe('bus_handoff')
        ->and($busPackage['route_label'])->toBe('Bus Station')
        ->and($busPackage['recipient_name'])->toBe('Ama Mensah')
        ->and($busPackage['delivery_town'])->toBe('Madina')
        ->and($directPackage)->not->toBeNull()
        ->and($directPackage['delivery_method'])->toBe('direct')
        ->and($directPackage['route_label'])->toBe('Mark Asante');
});

test('starting deliveries uses claimed label count instead of full package quantity', function () {
    $warehouse = Warehouse::create([
        'name' => 'Accra Main Office',
        'code' => 'AMO',
        'is_active' => true,
    ]);
    $vendor = driverPackageRouteIntentCreateVendor();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00992',
        'status' => 'at_warehouse',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => '32 Inches Samsung TV',
        'quantity' => 2,
        'delivery_recipient_name' => 'George',
        'delivery_recipient_phone' => '+233205531644',
        'delivery_town' => 'Lapaz',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRKTV12345',
    ]);

    $receipt = WarehouseReceipt::create();
    $receiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 2,
        'received_quantity' => 2,
    ]);

    $firstLabel = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $receiptItem->id,
        'barcode_value' => 'TRKTV12345-001',
        'label_index' => 1,
        'labels_total' => 2,
        'label_type' => 'unit',
    ]);
    $secondLabel = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $receiptItem->id,
        'barcode_value' => 'TRKTV12345-002',
        'label_index' => 2,
        'labels_total' => 2,
        'label_type' => 'unit',
    ]);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $firstLabel->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
    ]);

    $this->postJson('/api/v1/driver/start-deliveries', [
        'warehouse_id' => $warehouse->id,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.packages_count', 1)
        ->assertJsonPath('data.unique_items_count', 1);

    $firstRunItem = DeliveryRunItem::query()->first();
    $firstStop = DeliveryRunStop::query()->first();

    expect($firstRunItem)->not->toBeNull()
        ->and((int) $firstRunItem->expected_quantity)->toBe(1)
        ->and((int) $firstStop->total_packages)->toBe(1);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $secondLabel->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
    ]);

    $this->postJson('/api/v1/driver/start-deliveries', [
        'warehouse_id' => $warehouse->id,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.packages_count', 1)
        ->assertJsonPath('data.unique_items_count', 1);

    $runItems = DeliveryRunItem::query()->orderBy('id')->get();

    expect($runItems)->toHaveCount(2)
        ->and((int) $runItems[0]->expected_quantity)->toBe(1)
        ->and((int) $runItems[1]->expected_quantity)->toBe(1);
});
