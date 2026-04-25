<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\District;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Region;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function rwCreateAdminUser(): User
{
    $user = User::factory()->create([
        'is_active' => true,
        'warehouse_id' => null,
    ]);

    $role = Role::create([
        'name' => 'Super Admin',
        'slug' => 'super_admin',
        'description' => 'Receiving workspace test role',
        'is_system_role' => true,
        'is_active' => true,
    ]);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    return $user;
}

function rwBuildSchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
        'shipment_item_tracking',
        'shipment_charges',
        'warehouse_receipt_item_photos',
        'warehouse_receipt_items',
        'warehouse_receipts',
        'pickup_photos',
        'pickup_item_confirmations',
        'pickup_assignments',
        'shipment_item_images',
        'shipment_items',
        'shipments',
        'warehouses',
        'districts',
        'regions',
        'drivers',
        'vendors',
        'platform_settings',
        'user_roles',
        'roles',
        'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }
    Schema::enableForeignKeyConstraints();

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->timestamp('last_login_at')->nullable();
        $table->timestamp('last_permission_cache_at')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->string('remember_token')->nullable();
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->boolean('is_system_role')->default(false);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('user_roles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
        $table->timestamp('assigned_at')->nullable();
        $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
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

    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->unique();
        $table->string('password');
        $table->string('vehicle_type')->default('motorcycle');
        $table->string('vehicle_number')->nullable();
        $table->string('license_number')->nullable();
        $table->string('base_location')->nullable();
        $table->string('status')->default('offline');
        $table->boolean('is_active')->default(true);
        $table->json('task_capabilities')->nullable();
        $table->timestamp('last_login_at')->nullable();
        $table->string('remember_token')->nullable();
        $table->timestamps();
    });

    Schema::create('regions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('districts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
        $table->string('name');
        $table->string('code')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->text('address')->nullable();
        $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
        $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('contact_phone')->nullable();
        $table->string('contact_email')->nullable();
        $table->integer('capacity')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
        $table->string('shipment_number')->unique();
        $table->string('status')->default('draft');
        $table->string('source')->default('vendor_app');
        $table->string('fulfillment_type')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->string('destination_mode')->default('single');
        $table->unsignedBigInteger('current_invoice_id')->nullable();
        $table->string('pickup_contact_name')->nullable();
        $table->string('pickup_contact_phone')->nullable();
        $table->foreignId('pickup_region_id')->nullable()->constrained('regions')->nullOnDelete();
        $table->foreignId('pickup_district_id')->nullable()->constrained('districts')->nullOnDelete();
        $table->string('pickup_town')->nullable();
        $table->decimal('pickup_latitude', 10, 8)->nullable();
        $table->decimal('pickup_longitude', 11, 8)->nullable();
        $table->string('pickup_gh_post_address')->nullable();
        $table->string('pickup_landmark')->nullable();
        $table->text('pickup_instructions')->nullable();
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->foreignId('delivery_region_id')->nullable()->constrained('regions')->nullOnDelete();
        $table->foreignId('delivery_district_id')->nullable()->constrained('districts')->nullOnDelete();
        $table->string('delivery_town')->nullable();
        $table->decimal('delivery_latitude', 10, 8)->nullable();
        $table->decimal('delivery_longitude', 11, 8)->nullable();
        $table->string('delivery_gh_post_address')->nullable();
        $table->string('delivery_landmark')->nullable();
        $table->text('delivery_instructions')->nullable();
        $table->string('delivery_preference', 20)->nullable();
        $table->text('sender_notes')->nullable();
        $table->timestamp('submitted_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->text('cancellation_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
        $table->string('description')->nullable();
        $table->unsignedInteger('quantity')->default(1);
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->foreignId('delivery_region_id')->nullable()->constrained('regions')->nullOnDelete();
        $table->foreignId('delivery_district_id')->nullable()->constrained('districts')->nullOnDelete();
        $table->string('delivery_town')->nullable();
        $table->decimal('delivery_latitude', 10, 8)->nullable();
        $table->decimal('delivery_longitude', 11, 8)->nullable();
        $table->string('delivery_gh_post_address')->nullable();
        $table->string('delivery_landmark')->nullable();
        $table->text('delivery_instructions')->nullable();
        $table->string('fulfillment_type')->nullable();
        $table->string('delivery_method')->nullable();
        $table->string('delivery_preference', 20)->nullable();
        $table->string('status')->default('pending');
        $table->string('tracking_code')->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_item_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->string('path');
        $table->string('original_name')->nullable();
        $table->unsignedBigInteger('size')->default(0);
        $table->unsignedInteger('sort_order')->default(0);
        $table->string('recipient_phone')->nullable();
        $table->timestamps();
    });

    Schema::create('pickup_assignments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
        $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
        $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        $table->string('status')->default('assigned');
        $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('en_route_at')->nullable();
        $table->timestamp('arrived_at')->nullable();
        $table->timestamp('picked_up_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamp('arrived_warehouse_at')->nullable();
        $table->foreignId('received_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        $table->unsignedBigInteger('received_by_user_id')->nullable();
        $table->timestamp('received_at')->nullable();
        $table->text('receive_notes')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->text('cancellation_reason')->nullable();
        $table->decimal('pickup_latitude', 10, 8)->nullable();
        $table->decimal('pickup_longitude', 11, 8)->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('pickup_item_confirmations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('confirmed_quantity')->default(1);
        $table->text('notes')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('pickup_photos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->string('path');
        $table->string('original_name')->nullable();
        $table->unsignedBigInteger('size')->default(0);
        $table->string('type')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_charges', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
        $table->foreignId('shipment_item_id')->nullable()->constrained('shipment_items')->nullOnDelete();
        $table->string('charge_type')->nullable();
        $table->string('payer_type')->nullable();
        $table->string('direction')->nullable();
        $table->string('due_stage')->nullable();
        $table->decimal('amount', 10, 2)->default(0);
        $table->string('currency')->default('GHS');
        $table->string('status')->default('pending');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipment_item_tracking', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->string('status');
        $table->string('location')->nullable();
        $table->text('notes')->nullable();
        $table->json('meta')->nullable();
        $table->string('created_by')->nullable();
        $table->timestamp('created_at')->useCurrent();
    });

    Schema::create('warehouse_receipts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
        $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
        $table->unsignedBigInteger('transport_manifest_id')->nullable();
        $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
        $table->string('status')->default('draft');
        $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->text('approval_reason')->nullable();
        $table->text('notes')->nullable();
        $table->timestamp('started_at')->nullable();
        $table->timestamp('finalized_at')->nullable();
        $table->timestamps();
    });

    Schema::create('warehouse_receipt_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('warehouse_receipt_id')->constrained('warehouse_receipts')->cascadeOnDelete();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('received_quantity')->default(0);
        $table->unsignedInteger('damaged_quantity')->default(0);
        $table->string('discrepancy_type')->nullable();
        $table->string('condition_status')->nullable();
        $table->text('notes')->nullable();
        $table->unsignedBigInteger('received_by_user_id')->nullable();
        $table->timestamp('received_at')->nullable();
        $table->string('barcode_value')->nullable();
        $table->string('barcode_format')->nullable();
        $table->timestamp('barcode_printed_at')->nullable();
        $table->unsignedInteger('barcode_print_count')->default(0);
        $table->timestamps();
    });

    Schema::create('warehouse_receipt_item_photos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('warehouse_receipt_item_id')->constrained('warehouse_receipt_items')->cascadeOnDelete();
        $table->string('path');
        $table->string('original_name')->nullable();
        $table->unsignedBigInteger('size')->default(0);
        $table->string('photo_type')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->timestamps();
    });
}

function rwCreateVendor(): Vendor
{
    $vendor = new Vendor([
        'name' => 'Vendor '.Str::upper(Str::random(4)),
        'business_name' => 'Parcelman Tests',
        'phone' => '23324'.random_int(1000000, 9999999),
        'email' => Str::lower(Str::random(8)).'@example.test',
        'is_active' => true,
    ]);

    $vendor->pin_hash = Hash::make('1234');
    $vendor->is_phone_verified = true;
    $vendor->save();

    return $vendor;
}

function rwCreateDriver(): Driver
{
    return Driver::create([
        'name' => 'Driver '.Str::upper(Str::random(4)),
        'email' => Str::lower(Str::random(10)).'@example.test',
        'phone' => '23320'.random_int(1000000, 9999999),
        'password' => Hash::make('password123'),
        'vehicle_type' => 'van',
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_PICKUP],
    ]);
}

function rwCreateLocation(): array
{
    $region = Region::create([
        'name' => 'Region '.Str::upper(Str::random(4)),
        'code' => 'RG-'.Str::upper(Str::random(5)),
        'is_active' => true,
    ]);

    $district = District::create([
        'region_id' => $region->id,
        'name' => 'District '.Str::upper(Str::random(4)),
        'code' => 'DT-'.Str::upper(Str::random(5)),
        'is_active' => true,
    ]);

    return compact('region', 'district');
}

function rwCreateWarehouse(?Region $region = null, ?District $district = null): Warehouse
{
    return Warehouse::create([
        'name' => 'Warehouse '.Str::upper(Str::random(4)),
        'code' => 'WH-'.Str::upper(Str::random(5)),
        'region_id' => $region?->id,
        'district_id' => $district?->id,
        'is_active' => true,
    ]);
}

function rwCreateShipment(Vendor $vendor, array $overrides = []): Shipment
{
    return Shipment::create(array_merge([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'SHP-'.Str::upper(Str::random(10)),
        'status' => 'draft',
        'source' => 'vendor_app',
        'destination_mode' => 'single',
        'fulfillment_type' => 'warehouse',
        'delivery_preference' => 'deliver',
    ], $overrides));
}

function rwCreateShipmentItem(Shipment $shipment, array $overrides = []): ShipmentItem
{
    return ShipmentItem::create(array_merge([
        'shipment_id' => $shipment->id,
        'description' => 'Package '.Str::upper(Str::random(4)),
        'quantity' => 1,
        'status' => 'pending',
        'fulfillment_type' => 'warehouse',
        'delivery_preference' => 'deliver',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
    ], $overrides));
}

function rwCreateAssignment(Shipment $shipment, Driver $driver, Warehouse $warehouse, array $overrides = []): PickupAssignment
{
    return PickupAssignment::create(array_merge([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => 'assigned',
        'assigned_by' => auth('admin')->id(),
        'assigned_at' => now(),
    ], $overrides));
}

function rwAddVendorPhoto(ShipmentItem $item, string $name, int $sortOrder = 0): ShipmentItemImage
{
    return ShipmentItemImage::create([
        'shipment_item_id' => $item->id,
        'path' => "shipments/{$item->shipment_id}/items/{$item->id}/{$name}",
        'original_name' => $name,
        'size' => 2048,
        'sort_order' => $sortOrder,
    ]);
}

function rwCreateReceipt(PickupAssignment $assignment, Warehouse $warehouse): WarehouseReceipt
{
    return WarehouseReceipt::create([
        'pickup_assignment_id' => $assignment->id,
        'shipment_id' => $assignment->shipment_id,
        'warehouse_id' => $warehouse->id,
        'status' => WarehouseReceipt::STATUS_DRAFT,
        'started_by_user_id' => auth('admin')->id(),
        'started_at' => now(),
    ]);
}

function rwCreateReceiptItem(WarehouseReceipt $receipt, ShipmentItem $item, array $overrides = []): WarehouseReceiptItem
{
    return WarehouseReceiptItem::create(array_merge([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 1,
        'received_quantity' => 0,
        'damaged_quantity' => 0,
        'discrepancy_type' => 'none',
        'condition_status' => 'ok',
        'received_by_user_id' => auth('admin')->id(),
        'received_at' => now(),
        'barcode_value' => 'TRK'.Str::upper(Str::random(8)),
        'barcode_format' => 'code128',
        'barcode_print_count' => 0,
    ], $overrides));
}

beforeEach(function () {
    rwBuildSchema();
    $this->withoutMiddleware(LogAdminAuditActivity::class);
    $this->actingAs(rwCreateAdminUser(), 'admin');
});

test('pre-pickup package split still works from the packages workspace and clones delivery metadata', function () {
    $location = rwCreateLocation();
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'destination_mode' => 'per_item',
    ]);

    $item = rwCreateShipmentItem($shipment, [
        'description' => 'Blue suitcase',
        'quantity' => 2,
        'delivery_recipient_name' => 'Ama Mensah',
        'delivery_recipient_phone' => '+233241112223',
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
        'delivery_town' => 'Madina',
        'delivery_landmark' => 'Near Market',
        'delivery_instructions' => 'Call on arrival',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
    ]);

    $keptPhoto = rwAddVendorPhoto($item, 'front.jpg', 1);
    $movedPhoto = rwAddVendorPhoto($item, 'side.jpg', 2);

    $response = $this->postJson(route('admin.shipments.packages.split', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'photo_ids' => [$movedPhoto->id],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.package.description', 'Blue suitcase')
        ->assertJsonPath('data.package.delivery_recipient_name', 'Ama Mensah')
        ->assertJsonPath('data.package.delivery_region_id', $location['region']->id)
        ->assertJsonPath('data.package.delivery_district_id', $location['district']->id)
        ->assertJsonPath('data.package.delivery_town', 'Madina')
        ->assertJsonPath('data.package.delivery_method', ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF)
        ->assertJsonPath('data.receiving_package', null);

    $newItem = ShipmentItem::query()
        ->where('shipment_id', $shipment->id)
        ->whereKeyNot($item->id)
        ->firstOrFail();

    expect($newItem->description)->toBe('Blue suitcase')
        ->and($newItem->delivery_recipient_name)->toBe('Ama Mensah')
        ->and($newItem->delivery_recipient_phone)->toBe('+233241112223')
        ->and($newItem->delivery_region_id)->toBe($location['region']->id)
        ->and($newItem->delivery_district_id)->toBe($location['district']->id)
        ->and($newItem->delivery_town)->toBe('Madina')
        ->and($newItem->delivery_landmark)->toBe('Near Market')
        ->and($newItem->delivery_instructions)->toBe('Call on arrival')
        ->and($newItem->delivery_method)->toBe(ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF);

    expect($movedPhoto->fresh()->shipment_item_id)->toBe($newItem->id)
        ->and($keptPhoto->fresh()->shipment_item_id)->toBe($item->id);

    $this->assertDatabaseCount('shipment_items', 2);
});

test('post-pickup split works from receiving before receipt starts and returns receiving package payloads', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
        'destination_mode' => 'per_item',
    ]);

    $item = rwCreateShipmentItem($shipment, [
        'description' => 'Camera box',
        'delivery_recipient_name' => 'Kojo Driver',
        'delivery_recipient_phone' => '+233501112223',
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
        'delivery_town' => 'Kasoa',
        'delivery_landmark' => 'Main lorry station',
        'delivery_instructions' => 'Leave with office admin',
    ]);

    rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);

    $firstPhoto = rwAddVendorPhoto($item, 'one.jpg', 1);
    $secondPhoto = rwAddVendorPhoto($item, 'two.jpg', 2);

    $response = $this->postJson(route('admin.shipments.packages.split', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'photo_ids' => [$secondPhoto->id],
    ]);

    $newItem = ShipmentItem::query()
        ->where('shipment_id', $shipment->id)
        ->whereKeyNot($item->id)
        ->firstOrFail();

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.receiving_package.shipment_item_id', $newItem->id)
        ->assertJsonPath('data.receiving_package.delivery_town', 'Kasoa')
        ->assertJsonPath('data.receiving_package.can_split', true)
        ->assertJsonPath('data.receiving_package.vendor_photos.0.id', $secondPhoto->id)
        ->assertJsonPath('data.source_receiving_package.shipment_item_id', $item->id)
        ->assertJsonPath('data.source_receiving_package.vendor_photos.0.id', $firstPhoto->id)
        ->assertJsonPath('data.source_receiving_package.can_split', true);

    expect($newItem->description)->toBe('Camera box')
        ->and($newItem->delivery_recipient_name)->toBe('Kojo Driver')
        ->and($newItem->delivery_recipient_phone)->toBe('+233501112223')
        ->and($newItem->delivery_region_id)->toBe($location['region']->id)
        ->and($newItem->delivery_district_id)->toBe($location['district']->id)
        ->and($newItem->delivery_town)->toBe('Kasoa');

    expect($secondPhoto->fresh()->shipment_item_id)->toBe($newItem->id)
        ->and($firstPhoto->fresh()->shipment_item_id)->toBe($item->id);
});

test('post-pickup receiving split is blocked once warehouse intake has started', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
        'destination_mode' => 'per_item',
    ]);
    $item = rwCreateShipmentItem($shipment);
    $assignment = rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);
    $receipt = rwCreateReceipt($assignment, $warehouse);
    rwCreateReceiptItem($receipt, $item, [
        'received_quantity' => 1,
        'barcode_print_count' => 0,
    ]);
    $photo = rwAddVendorPhoto($item, 'blocked.jpg', 1);

    $response = $this->postJson(route('admin.shipments.packages.split', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'photo_ids' => [$photo->id],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'This package has already been received at the warehouse.');

    $this->assertDatabaseCount('shipment_items', 1);
    expect($photo->fresh()->shipment_item_id)->toBe($item->id);
});

test('post-pickup receiving split is blocked once labels have been printed', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
        'destination_mode' => 'per_item',
    ]);
    $item = rwCreateShipmentItem($shipment);
    $assignment = rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);
    $receipt = rwCreateReceipt($assignment, $warehouse);
    rwCreateReceiptItem($receipt, $item, [
        'received_quantity' => 0,
        'barcode_print_count' => 1,
    ]);
    $photo = rwAddVendorPhoto($item, 'printed.jpg', 1);

    $response = $this->postJson(route('admin.shipments.packages.split', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'photo_ids' => [$photo->id],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Labels have already been printed for this package.');

    $this->assertDatabaseCount('shipment_items', 1);
});

test('post-pickup auto-group by phone works from receiving before warehouse intake starts', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
        'destination_mode' => 'single',
    ]);

    $firstItem = rwCreateShipmentItem($shipment, [
        'description' => 'Mixed vendor photos',
    ]);
    $secondItem = rwCreateShipmentItem($shipment, [
        'description' => 'Phone B package',
    ]);

    rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);

    ShipmentItemImage::create([
        'shipment_item_id' => $firstItem->id,
        'path' => "shipments/{$shipment->id}/items/{$firstItem->id}/a.jpg",
        'original_name' => 'a.jpg',
        'size' => 2048,
        'sort_order' => 1,
        'recipient_phone' => '+233240000001',
    ]);

    ShipmentItemImage::create([
        'shipment_item_id' => $firstItem->id,
        'path' => "shipments/{$shipment->id}/items/{$firstItem->id}/b.jpg",
        'original_name' => 'b.jpg',
        'size' => 2048,
        'sort_order' => 2,
        'recipient_phone' => '+233240000002',
    ]);

    ShipmentItemImage::create([
        'shipment_item_id' => $secondItem->id,
        'path' => "shipments/{$shipment->id}/items/{$secondItem->id}/c.jpg",
        'original_name' => 'c.jpg',
        'size' => 2048,
        'sort_order' => 1,
        'recipient_phone' => '+233240000002',
    ]);

    $response = $this->postJson(route('admin.shipments.auto-group-by-phone', $shipment), []);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.destination_mode', 'per_item')
        ->assertJsonPath('data.can_auto_group', true);

    $receivingPackages = collect($response->json('data.receiving_packages'));

    expect($receivingPackages)->toHaveCount(2)
        ->and($receivingPackages->pluck('delivery_recipient_phone')->all())->toEqualCanonicalizing([
            '+233240000001',
            '+233240000002',
        ]);

    $phoneA = $receivingPackages->firstWhere('delivery_recipient_phone', '+233240000001');
    $phoneB = $receivingPackages->firstWhere('delivery_recipient_phone', '+233240000002');

    expect($phoneA['vendor_photos'])->toHaveCount(1)
        ->and($phoneA['vendor_photos'][0]['recipient_phone'])->toBe('+233240000001')
        ->and($phoneB['vendor_photos'])->toHaveCount(2)
        ->and(collect($phoneB['vendor_photos'])->pluck('recipient_phone')->unique()->values()->all())->toBe(['+233240000002']);

    $this->assertDatabaseCount('shipment_items', 2);
    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'destination_mode' => 'per_item',
    ]);
});

test('post-pickup auto-group by phone is blocked once warehouse intake has started', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
    ]);
    $item = rwCreateShipmentItem($shipment);
    $assignment = rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);
    $receipt = rwCreateReceipt($assignment, $warehouse);
    rwCreateReceiptItem($receipt, $item, [
        'received_quantity' => 0,
        'barcode_print_count' => 0,
    ]);
    rwAddVendorPhoto($item, 'tagged.jpg', 1)->update([
        'recipient_phone' => '+233240000009',
    ]);

    $response = $this->postJson(route('admin.shipments.auto-group-by-phone', $shipment), []);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Auto-group by phone is only available before warehouse receiving starts.');
});

test('receiving details save updates package details without creating a receipt item', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
        'destination_mode' => 'per_item',
    ]);
    $item = rwCreateShipmentItem($shipment, [
        'description' => 'Old package name',
    ]);

    rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);

    $response = $this->postJson(route('admin.shipments.receiving.details', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'description' => 'Updated package name',
        'delivery_recipient_name' => 'Serwaa',
        'delivery_recipient_phone' => '0241234567',
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
        'delivery_town' => 'Dansoman',
        'delivery_landmark' => 'Near Shell',
        'delivery_instructions' => 'Ring twice',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.package.description', 'Updated package name')
        ->assertJsonPath('data.package.delivery_recipient_name', 'Serwaa')
        ->assertJsonPath('data.package.delivery_recipient_phone', '+233241234567')
        ->assertJsonPath('data.package.delivery_town', 'Dansoman')
        ->assertJsonPath('data.package.received_quantity', 0);

    $this->assertDatabaseHas('shipment_items', [
        'id' => $item->id,
        'description' => 'Updated package name',
        'delivery_recipient_name' => 'Serwaa',
        'delivery_recipient_phone' => '+233241234567',
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
        'delivery_town' => 'Dansoman',
        'delivery_landmark' => 'Near Shell',
        'delivery_instructions' => 'Ring twice',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
    ]);

    $this->assertDatabaseCount('warehouse_receipts', 0);
    $this->assertDatabaseCount('warehouse_receipt_items', 0);
});

test('single-destination receiving details save syncs all receiving package cards from shipment delivery data', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
        'destination_mode' => 'single',
    ]);
    $firstItem = rwCreateShipmentItem($shipment, [
        'description' => 'First package',
        'delivery_town' => 'Old Town A',
    ]);
    rwCreateShipmentItem($shipment, [
        'description' => 'Second package',
        'delivery_town' => 'Old Town B',
    ]);

    rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'picked_up_at' => now(),
    ]);

    $response = $this->postJson(route('admin.shipments.receiving.details', [
        'shipment' => $shipment,
        'item' => $firstItem,
    ]), [
        'description' => 'First package updated',
        'delivery_recipient_name' => 'Akosua',
        'delivery_recipient_phone' => '0201112222',
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
        'delivery_town' => 'Tema Community 1',
        'delivery_landmark' => 'Near roundabout',
        'delivery_instructions' => 'Call vendor office first',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.package.description', 'First package updated')
        ->assertJsonPath('data.package.delivery_recipient_name', 'Akosua')
        ->assertJsonPath('data.package.delivery_recipient_phone', '+233201112222')
        ->assertJsonPath('data.package.delivery_town', 'Tema Community 1');

    $packages = collect($response->json('data.packages'));

    expect($packages)->toHaveCount(2)
        ->and($packages->pluck('delivery_recipient_name')->unique()->values()->all())->toBe(['Akosua'])
        ->and($packages->pluck('delivery_recipient_phone')->unique()->values()->all())->toBe(['+233201112222'])
        ->and($packages->pluck('delivery_town')->unique()->values()->all())->toBe(['Tema Community 1'])
        ->and($packages->pluck('delivery_region_id')->unique()->values()->all())->toBe([$location['region']->id])
        ->and($packages->pluck('delivery_district_id')->unique()->values()->all())->toBe([$location['district']->id]);

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'delivery_recipient_name' => 'Akosua',
        'delivery_recipient_phone' => '+233201112222',
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
        'delivery_town' => 'Tema Community 1',
        'delivery_landmark' => 'Near roundabout',
        'delivery_instructions' => 'Call vendor office first',
    ]);
});

test('receiving finalization accepts approval reason for discrepancy receipts', function () {
    $location = rwCreateLocation();
    $warehouse = rwCreateWarehouse($location['region'], $location['district']);
    $shipment = rwCreateShipment(rwCreateVendor(), [
        'status' => 'picked_up',
    ]);
    $item = rwCreateShipmentItem($shipment, [
        'description' => 'Damaged carton',
        'quantity' => 2,
    ]);
    $assignment = rwCreateAssignment($shipment, rwCreateDriver(), $warehouse, [
        'status' => 'completed',
        'picked_up_at' => now(),
    ]);
    $receipt = rwCreateReceipt($assignment, $warehouse);

    rwCreateReceiptItem($receipt, $item, [
        'expected_quantity' => 2,
        'received_quantity' => 1,
        'damaged_quantity' => 0,
        'discrepancy_type' => 'missing',
        'condition_status' => 'partial',
    ]);

    $response = $this->postJson(route('admin.shipments.receiving.finalize', $shipment), [
        'notes' => 'Finalized after warehouse review',
        'approval_reason' => 'Vendor confirmed one piece was not handed over at pickup.',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.receipt.status', WarehouseReceipt::STATUS_FINALIZED);

    $this->assertDatabaseHas('warehouse_receipts', [
        'id' => $receipt->id,
        'status' => WarehouseReceipt::STATUS_FINALIZED,
        'notes' => 'Finalized after warehouse review',
        'approval_reason' => 'Vendor confirmed one piece was not handed over at pickup.',
        'approved_by_user_id' => auth('admin')->id(),
    ]);
});
