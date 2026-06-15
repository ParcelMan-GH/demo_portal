<?php

use App\Models\Driver;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\LabelCustodyEvent;
use App\Models\RiderPackageTransfer;
use App\Models\RiderTeamHandoverItem;
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
        'bus_handoff_confirmations',
        'delivery_delay_events',
        'delivery_run_items',
        'delivery_run_stops',
        'delivery_runs',
        'delivery_failure_reasons',
        'delivery_delay_reasons',
        'shipment_charges',
        'rider_package_transfers',
        'rider_team_handover_items',
        'label_custody_events',
        'warehouse_receipt_item_labels',
        'warehouse_receipt_items',
        'warehouse_receipts',
        'shipment_items',
        'shipments',
        'vendors',
        'warehouses',
        'platform_settings',
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
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('contact_phone')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('platform_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->text('description')->nullable();
        $table->boolean('is_encrypted')->default(false);
        $table->timestamps();
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
        $table->timestamp('completed_at')->nullable();
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
        $table->timestamp('verification_code_sent_at')->nullable();
        $table->timestamp('verification_code_expires_at')->nullable();
        $table->unsignedInteger('verification_attempts')->default(0);
        $table->unsignedInteger('max_attempts')->default(3);
        $table->boolean('verification_skipped')->default(false);
        $table->string('verification_skip_reason')->nullable();
        $table->timestamp('verification_skipped_at')->nullable();
        $table->timestamp('arrived_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->decimal('delivery_latitude', 10, 8)->nullable();
        $table->decimal('delivery_longitude', 11, 8)->nullable();
        $table->string('proof_photo_path')->nullable();
        $table->string('failure_reason')->nullable();
        $table->text('failure_notes')->nullable();
        $table->text('delivery_notes')->nullable();
        $table->string('handoff_courier_name')->nullable();
        $table->string('handoff_courier_phone')->nullable();
        $table->string('handoff_vehicle_number')->nullable();
        $table->string('bus_station_name')->nullable();
        $table->timestamp('handoff_at')->nullable();
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
        $table->timestamp('expected_delivery_at')->nullable();
        $table->timestamp('expected_delivery_set_at')->nullable();
        $table->unsignedBigInteger('expected_delivery_set_by_driver_id')->nullable();
        $table->unsignedBigInteger('expected_delivery_set_by_user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_failure_reasons', function (Blueprint $table) {
        $table->id();
        $table->string('label');
        $table->string('slug')->nullable();
        $table->string('type')->default('other');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('delivery_delay_reasons', function (Blueprint $table) {
        $table->id();
        $table->string('label');
        $table->string('slug')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('delivery_delay_events', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_item_id');
        $table->unsignedBigInteger('delivery_delay_reason_id')->nullable();
        $table->string('reason_label')->nullable();
        $table->string('source')->nullable();
        $table->unsignedBigInteger('actor_driver_id')->nullable();
        $table->unsignedBigInteger('actor_user_id')->nullable();
        $table->timestamp('old_expected_delivery_at')->nullable();
        $table->timestamp('new_expected_delivery_at')->nullable();
        $table->timestamps();
    });

    Schema::create('bus_handoff_confirmations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_item_id')->unique();
        $table->string('status')->default('pending');
        $table->string('source')->nullable();
        $table->string('target_type')->nullable();
        $table->string('target_phone')->nullable();
        $table->timestamp('confirmation_code_sent_at')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->unsignedBigInteger('reason_id')->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_charges', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id')->nullable();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->string('charge_type');
        $table->string('payer_type');
        $table->string('direction');
        $table->string('due_stage');
        $table->decimal('amount', 12, 2)->default(0);
        $table->string('currency')->default('GHS');
        $table->string('status')->default('draft');
        $table->timestamp('paid_at')->nullable();
        $table->string('payment_method')->nullable();
        $table->text('notes')->nullable();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('rider_package_transfers', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_item_id');
        $table->unsignedBigInteger('from_driver_id')->nullable();
        $table->unsignedBigInteger('to_driver_id')->nullable();
        $table->string('status')->default(RiderPackageTransfer::STATUS_PENDING);
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('responded_at')->nullable();
        $table->timestamps();
    });

    Schema::create('rider_team_handover_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('rider_team_handover_id')->nullable();
        $table->unsignedBigInteger('warehouse_receipt_item_label_id');
        $table->unsignedBigInteger('allocated_to_driver_id')->nullable();
        $table->string('status')->default(RiderTeamHandoverItem::STATUS_ASSIGNED_TO_LEADER);
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('leader_received_at')->nullable();
        $table->timestamp('allocated_at')->nullable();
        $table->timestamp('member_claimed_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('returned_at')->nullable();
        $table->text('notes')->nullable();
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

test('starting deliveries can immediately load the delivery detail response', function () {
    $warehouse = Warehouse::create([
        'name' => 'Accra Main Office',
        'code' => 'AMO',
        'is_active' => true,
    ]);
    $vendor = driverPackageRouteIntentCreateVendor();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00995',
        'status' => 'at_warehouse',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Immediate detail package',
        'quantity' => 1,
        'delivery_recipient_name' => 'Esi Boateng',
        'delivery_recipient_phone' => '+233541112225',
        'delivery_town' => 'Tema',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRKDETAIL123',
    ]);

    $receipt = WarehouseReceipt::create();
    $receiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
    ]);

    $label = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $receiptItem->id,
        'barcode_value' => 'TRKDETAIL123-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'unit',
    ]);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $label->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
    ]);

    $startResponse = $this->postJson('/api/v1/driver/start-deliveries', [
        'warehouse_id' => $warehouse->id,
        'barcodes' => ['TRKDETAIL123-001'],
    ]);

    $startResponse->assertOk()
        ->assertJsonPath('success', true);

    $runId = $startResponse->json('data.delivery_run_id');
    $runItem = DeliveryRunItem::query()
        ->where('delivery_run_id', $runId)
        ->firstOrFail();

    $expectedDeliveryAt = now()->addHours(2)->seconds(0);
    $runItem->forceFill([
        'expected_delivery_at' => $expectedDeliveryAt,
        'expected_delivery_set_at' => now()->seconds(0),
        'expected_delivery_set_by_driver_id' => $this->driver->id,
    ])->save();

    \App\Models\DeliveryDelayEvent::create([
        'delivery_run_item_id' => $runItem->id,
        'reason_label' => 'Traffic delay',
        'source' => \App\Models\DeliveryDelayEvent::SOURCE_RIDER_ETA,
        'actor_driver_id' => $this->driver->id,
        'old_expected_delivery_at' => now()->addHour(),
        'new_expected_delivery_at' => now()->addHours(2),
    ]);

    $this->getJson('/api/v1/driver/deliveries')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.deliveries.0.id', $runId);

    $this->getJson("/api/v1/driver/deliveries/{$runId}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.delivery.id', $runId)
        ->assertJsonPath('data.delivery.stops.0.items.0.tracking_code', 'TRKDETAIL123')
        ->assertJsonPath('data.delivery.stops.0.items.0.eta.set_by', $this->driver->name)
        ->assertJsonPath('data.delivery.stops.0.items.0.eta.expected_delivery_at_iso', $expectedDeliveryAt->toIso8601String());
});

test('starting deliveries blocks packages with pending rider transfers', function () {
    $warehouse = Warehouse::create([
        'name' => 'Accra Main Office',
        'code' => 'AMO',
        'is_active' => true,
    ]);
    $vendor = driverPackageRouteIntentCreateVendor();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00994',
        'status' => 'at_warehouse',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Ready package',
        'quantity' => 1,
        'delivery_recipient_name' => 'Yaw',
        'delivery_recipient_phone' => '+233205531645',
        'delivery_town' => 'Kasoa',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRKPENDING123',
    ]);

    $receipt = WarehouseReceipt::create();
    $receiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
    ]);

    $label = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $receiptItem->id,
        'barcode_value' => 'TRKPENDING123-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'unit',
    ]);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $label->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
    ]);

    RiderPackageTransfer::create([
        'shipment_item_id' => $item->id,
        'from_driver_id' => $this->driver->id,
        'status' => RiderPackageTransfer::STATUS_PENDING,
        'requested_at' => now(),
    ]);

    $this->postJson('/api/v1/driver/start-deliveries', [
        'warehouse_id' => $warehouse->id,
    ])->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Some packages have pending rider transfer requests and cannot be started for delivery.')
        ->assertJsonPath('pending_transfer_packages.0', 'TRKPENDING123');
});

test('starting deliveries does not reuse delivered packages still held in custody', function () {
    $warehouse = Warehouse::create([
        'name' => 'Accra Main Office',
        'code' => 'AMO',
        'is_active' => true,
    ]);
    $vendor = driverPackageRouteIntentCreateVendor();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00993',
        'status' => 'at_warehouse',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
    ]);

    $oldItem = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Old Package',
        'quantity' => 1,
        'delivery_recipient_name' => 'Ama',
        'delivery_recipient_phone' => '+233541112223',
        'delivery_town' => 'Madina',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => 'out_for_delivery',
        'tracking_code' => 'TRKOLD1234',
    ]);

    $newItem = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'New Package',
        'quantity' => 1,
        'delivery_recipient_name' => 'Kojo',
        'delivery_recipient_phone' => '+233541112224',
        'delivery_town' => 'Osu',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRKNEW1234',
    ]);

    $receipt = WarehouseReceipt::create();
    $oldReceiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $oldItem->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
    ]);
    $newReceiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $newItem->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
    ]);

    $oldLabel = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $oldReceiptItem->id,
        'barcode_value' => 'TRKOLD1234-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'sealed',
    ]);
    $newLabel = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $newReceiptItem->id,
        'barcode_value' => 'TRKNEW1234-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'sealed',
    ]);

    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $oldLabel->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
    ]);
    LabelCustodyEvent::create([
        'warehouse_receipt_item_label_id' => $newLabel->id,
        'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
        'driver_id' => $this->driver->id,
    ]);

    $oldRun = \App\Models\DeliveryRun::create([
        'run_number' => 'DR-2026-AMO-0001',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $this->driver->id,
        'status' => \App\Models\DeliveryRun::STATUS_COMPLETED,
        'dispatched_at' => now(),
        'completed_at' => now(),
    ]);
    $oldStop = DeliveryRunStop::create([
        'delivery_run_id' => $oldRun->id,
        'recipient_name' => 'Ama',
        'recipient_phone' => '+233541112223',
        'town' => 'Madina',
        'total_packages' => 1,
        'status' => DeliveryRunStop::STATUS_DELIVERED,
        'delivery_method' => DeliveryRunStop::METHOD_DIRECT,
    ]);
    DeliveryRunItem::create([
        'delivery_run_id' => $oldRun->id,
        'delivery_run_stop_id' => $oldStop->id,
        'shipment_item_id' => $oldItem->id,
        'expected_quantity' => 1,
        'delivered_quantity' => 1,
        'status' => DeliveryRunItem::STATUS_DELIVERED,
        'delivered_at' => now(),
    ]);

    $packagesResponse = $this->getJson('/api/v1/driver/my-packages');
    $packagesResponse->assertOk();

    $packages = collect($packagesResponse->json('data.packages'))->keyBy('barcode');
    expect($packages->get('TRKOLD1234-001')['in_delivery_run'])->toBeTrue()
        ->and($packages->get('TRKNEW1234-001')['in_delivery_run'])->toBeFalse();

    $this->postJson('/api/v1/driver/start-deliveries', [
        'warehouse_id' => $warehouse->id,
        'barcodes' => ['TRKOLD1234-001', 'TRKNEW1234-001'],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.packages_count', 1)
        ->assertJsonPath('data.unique_items_count', 1);

    $newRunItems = DeliveryRunItem::query()
        ->where('delivery_run_id', '!=', $oldRun->id)
        ->get();

    expect($newRunItems)->toHaveCount(1)
        ->and((int) $newRunItems->first()->shipment_item_id)->toBe($newItem->id);
});
