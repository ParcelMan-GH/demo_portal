<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\District;
use App\Models\Driver;
use App\Models\Location;
use App\Models\PickupAssignment;
use App\Models\Region;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function createAdminUser(): User
{
    $warehouse = Warehouse::create([
        'name' => 'Town Search HQ',
        'code' => 'TS-HQ',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $user = User::factory()->create([
        'is_active' => true,
        'warehouse_id' => $warehouse->id,
    ]);

    $role = Role::create([
        'name' => 'Super Admin',
        'slug' => 'super_admin',
        'description' => 'System test role',
        'is_system_role' => true,
        'is_active' => true,
    ]);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    return $user;
}

function createVendorRecord(): Vendor
{
    $vendor = new Vendor([
        'name' => 'Vendor '.Str::upper(Str::random(4)),
        'business_name' => 'Parcel Test Vendor',
        'phone' => '23324'.random_int(1000000, 9999999),
        'email' => Str::lower(Str::random(8)).'@example.test',
        'is_active' => true,
    ]);

    $vendor->pin_hash = Hash::make('1234');
    $vendor->is_phone_verified = true;
    $vendor->save();

    return $vendor;
}

function createLocationSet(string $townName, string $regionName, string $regionCode, string $districtName, string $districtCode, bool $active = true): array
{
    $region = Region::create([
        'name' => $regionName,
        'code' => $regionCode,
        'is_active' => $active,
    ]);

    $district = District::create([
        'region_id' => $region->id,
        'name' => $districtName,
        'code' => $districtCode,
        'is_active' => $active,
    ]);

    $town = Location::create([
        'name' => $townName,
        'region_id' => $region->id,
        'district_id' => $district->id,
        'type' => 'town',
        'is_active' => $active,
    ]);

    return compact('region', 'district', 'town');
}

function createShipmentRecord(Vendor $vendor, array $overrides = []): Shipment
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

function createShipmentItemRecord(Shipment $shipment, array $overrides = []): ShipmentItem
{
    return ShipmentItem::create(array_merge([
        'shipment_id' => $shipment->id,
        'description' => 'Parcel '.Str::upper(Str::random(4)),
        'quantity' => 1,
        'status' => 'pending',
    ], $overrides));
}

function buildTestSchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
        'platform_settings',
        'warehouse_receipt_item_photos',
        'warehouse_receipt_items',
        'warehouse_receipts',
        'pickup_photos',
        'pickup_item_confirmations',
        'pickup_assignments',
        'shipment_item_images',
        'shipment_charges',
        'shipment_items',
        'shipments',
        'locations',
        'districts',
        'regions',
        'warehouses',
        'drivers',
        'vendors',
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

    Schema::create('platform_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->string('description')->nullable();
        $table->boolean('is_encrypted')->default(false);
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

    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
        $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
        $table->string('type')->default('town');
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
        $table->boolean('is_hq')->default(false);
        $table->boolean('can_administer_system')->default(false);
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
        $table->timestamp('paid_at')->nullable();
        $table->string('payment_method')->nullable();
        $table->string('payment_reference')->nullable();
        $table->foreignId('recorded_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('recorded_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->foreignId('pickup_assignment_id')->nullable()->constrained('pickup_assignments')->nullOnDelete();
        $table->text('notes')->nullable();
        $table->text('waive_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
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

    Schema::create('warehouse_receipts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
        $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
        $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
        $table->string('status')->default('draft');
        $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('received_at')->nullable();
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
        $table->string('condition_status')->default('ok');
        $table->string('discrepancy_type')->default('none');
        $table->text('notes')->nullable();
        $table->string('barcode_value')->nullable();
        $table->unsignedInteger('barcode_print_count')->default(0);
        $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('received_at')->nullable();
        $table->timestamps();
    });

    Schema::create('warehouse_receipt_item_photos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('warehouse_receipt_item_id')->constrained('warehouse_receipt_items')->cascadeOnDelete();
        $table->string('path');
        $table->string('original_name')->nullable();
        $table->unsignedBigInteger('size')->default(0);
        $table->timestamps();
    });
}

beforeEach(function () {
    buildTestSchema();
    $this->withoutMiddleware(LogAdminAuditActivity::class);
    $this->actingAs(createAdminUser(), 'admin');
});

test('towns data endpoint returns active towns with region and district context for duplicate names', function () {
    createLocationSet('Springfield', 'Northern Belt', 'NBL', 'Alpha District', 'ALP');
    createLocationSet('Springfield', 'Southern Belt', 'SBL', 'Beta District', 'BET');
    createLocationSet('Springfield', 'Dormant Region', 'DRM', 'Gamma District', 'GAM', false);

    $response = $this->getJson(route('admin.locations.towns.data', [
        'search' => 'Springfield',
        'active' => 1,
    ]));

    $response->assertOk()->assertJsonPath('success', true);

    $towns = collect($response->json('data.towns'));

    expect($towns)->toHaveCount(2)
        ->and($towns->pluck('region_name')->all())->toEqualCanonicalizing(['Northern Belt', 'Southern Belt'])
        ->and($towns->pluck('district_name')->all())->toEqualCanonicalizing(['Alpha District', 'Beta District']);
});

test('shipment update keeps free-text pickup and delivery towns unlinked until a saved town is selected', function () {
    $vendor = createVendorRecord();
    $pickupLocation = createLocationSet('Kasoa', 'Central', 'CTR', 'Awutu', 'AWT');
    $deliveryLocation = createLocationSet('Adenta', 'Greater Accra', 'GAR', 'La Nkwantanang', 'LAN');

    $shipment = createShipmentRecord($vendor, [
        'destination_mode' => 'single',
    ]);

    $this->putJson(route('admin.shipments.update', $shipment), [
        'pickup_town' => 'Raw Mobile Pickup',
        'pickup_region_id' => null,
        'pickup_district_id' => null,
        'delivery_town' => 'Raw Mobile Delivery',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ])->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'pickup_town' => 'Raw Mobile Pickup',
        'pickup_region_id' => null,
        'pickup_district_id' => null,
        'delivery_town' => 'Raw Mobile Delivery',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ]);

    $this->putJson(route('admin.shipments.update', $shipment), [
        'pickup_town' => $pickupLocation['town']->name,
        'pickup_region_id' => $pickupLocation['region']->id,
        'pickup_district_id' => $pickupLocation['district']->id,
        'delivery_town' => $deliveryLocation['town']->name,
        'delivery_region_id' => $deliveryLocation['region']->id,
        'delivery_district_id' => $deliveryLocation['district']->id,
    ])->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'pickup_town' => $pickupLocation['town']->name,
        'pickup_region_id' => $pickupLocation['region']->id,
        'pickup_district_id' => $pickupLocation['district']->id,
        'delivery_town' => $deliveryLocation['town']->name,
        'delivery_region_id' => $deliveryLocation['region']->id,
        'delivery_district_id' => $deliveryLocation['district']->id,
    ]);
});

test('package update keeps free-text delivery town unlinked until a saved town is selected', function () {
    $vendor = createVendorRecord();
    $location = createLocationSet('Tamale', 'Northern', 'NOR', 'Tamale Metro', 'TML');

    $shipment = createShipmentRecord($vendor, [
        'destination_mode' => 'per_item',
    ]);
    $item = createShipmentItemRecord($shipment);

    $this->putJson(route('admin.shipments.packages.update', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'delivery_town' => 'Free Text Package Town',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ])->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('shipment_items', [
        'id' => $item->id,
        'delivery_town' => 'Free Text Package Town',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ]);

    $this->putJson(route('admin.shipments.packages.update', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'delivery_town' => $location['town']->name,
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
    ])->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('shipment_items', [
        'id' => $item->id,
        'delivery_town' => $location['town']->name,
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
    ]);
});

test('receiving save keeps free-text towns unlinked and links selected towns for single-destination shipments', function () {
    $vendor = createVendorRecord();
    $location = createLocationSet('Koforidua', 'Eastern', 'EST', 'New Juaben', 'NJB');

    $shipment = createShipmentRecord($vendor, [
        'destination_mode' => 'single',
    ]);
    $item = createShipmentItemRecord($shipment);

    $driver = Driver::create([
        'name' => 'Driver One',
        'email' => 'driver-'.Str::lower(Str::random(6)).'@example.test',
        'phone' => '23320'.random_int(1000000, 9999999),
        'password' => Hash::make('password'),
        'vehicle_type' => 'motorcycle',
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => ['pickup'],
    ]);

    $warehouse = Warehouse::create([
        'name' => 'Main Warehouse',
        'code' => 'WH-'.Str::upper(Str::random(5)),
        'is_active' => true,
    ]);

    PickupAssignment::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => 'assigned',
        'assigned_by' => auth('admin')->id(),
        'assigned_at' => now(),
        'picked_up_at' => now(),
        'received_at' => now(),
    ]);

    $this->postJson(route('admin.shipments.receiving.save', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'received_quantity' => 0,
        'delivery_recipient_name' => 'Ama',
        'delivery_recipient_phone' => '0241234567',
        'delivery_town' => 'Receiving Free Text Town',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ])->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('shipment_items', [
        'id' => $item->id,
        'delivery_town' => 'Receiving Free Text Town',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ]);

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'delivery_town' => 'Receiving Free Text Town',
        'delivery_region_id' => null,
        'delivery_district_id' => null,
    ]);

    $this->postJson(route('admin.shipments.receiving.save', [
        'shipment' => $shipment,
        'item' => $item,
    ]), [
        'received_quantity' => 0,
        'delivery_recipient_name' => 'Ama',
        'delivery_recipient_phone' => '0241234567',
        'delivery_town' => $location['town']->name,
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
    ])->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('shipment_items', [
        'id' => $item->id,
        'delivery_town' => $location['town']->name,
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
    ]);

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'delivery_town' => $location['town']->name,
        'delivery_region_id' => $location['region']->id,
        'delivery_district_id' => $location['district']->id,
    ]);
});
