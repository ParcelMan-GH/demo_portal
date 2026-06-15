<?php

use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Region;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

function buildDriverPickupFeeVisibilitySchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
        'shipment_charges',
        'shipment_item_tracking',
        'shipment_item_images',
        'pickup_photos',
        'pickup_item_confirmations',
        'pickup_assignments',
        'shipment_items',
        'shipments',
        'regions',
        'vendors',
        'drivers',
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

    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->unique();
        $table->string('password');
        $table->string('vehicle_type')->nullable();
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

    Schema::create('regions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
        $table->string('shipment_number')->unique();
        $table->string('status')->default('submitted');
        $table->string('source')->default('vendor_app');
        $table->string('fulfillment_type')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->string('destination_mode')->default('single');
        $table->string('pickup_contact_name')->nullable();
        $table->string('pickup_contact_phone')->nullable();
        $table->foreignId('pickup_region_id')->nullable()->constrained('regions')->nullOnDelete();
        $table->unsignedBigInteger('pickup_district_id')->nullable();
        $table->string('pickup_town')->nullable();
        $table->decimal('pickup_latitude', 10, 8)->nullable();
        $table->decimal('pickup_longitude', 11, 8)->nullable();
        $table->string('pickup_gh_post_address')->nullable();
        $table->string('pickup_landmark')->nullable();
        $table->text('pickup_instructions')->nullable();
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->unsignedBigInteger('delivery_region_id')->nullable();
        $table->unsignedBigInteger('delivery_district_id')->nullable();
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
        $table->string('status')->default('pending');
        $table->string('tracking_code')->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_item_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->string('path')->nullable();
        $table->string('original_name')->nullable();
        $table->unsignedBigInteger('size')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->string('recipient_phone')->nullable();
        $table->timestamps();
    });

    Schema::create('pickup_assignments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
        $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
        $table->unsignedBigInteger('target_warehouse_id')->nullable();
        $table->string('status')->default('assigned');
        $table->unsignedBigInteger('assigned_by')->nullable();
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('en_route_at')->nullable();
        $table->timestamp('arrived_at')->nullable();
        $table->timestamp('picked_up_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamp('arrived_warehouse_at')->nullable();
        $table->unsignedBigInteger('received_warehouse_id')->nullable();
        $table->unsignedBigInteger('received_by_user_id')->nullable();
        $table->timestamp('received_at')->nullable();
        $table->text('receive_notes')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->text('cancellation_reason')->nullable();
        $table->decimal('pickup_latitude', 10, 8)->nullable();
        $table->decimal('pickup_longitude', 11, 8)->nullable();
        $table->unsignedInteger('driver_picked_quantity')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_item_tracking', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->string('status');
        $table->string('location')->nullable();
        $table->text('notes')->nullable();
        $table->json('meta')->nullable();
        $table->string('created_by')->nullable();
        $table->timestamps();
    });

    Schema::create('pickup_item_confirmations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
        $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
        $table->unsignedInteger('expected_quantity');
        $table->unsignedInteger('confirmed_quantity');
        $table->text('notes')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('pickup_photos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
        $table->foreignId('shipment_item_id')->nullable()->constrained('shipment_items')->cascadeOnDelete();
        $table->string('path');
        $table->string('original_name')->nullable();
        $table->unsignedBigInteger('size')->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_charges', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->string('charge_type');
        $table->string('payer_type');
        $table->string('direction');
        $table->string('due_stage');
        $table->decimal('amount', 10, 2);
        $table->string('currency')->default('GHS');
        $table->string('status')->default('pending');
        $table->timestamp('paid_at')->nullable();
        $table->string('payment_method')->nullable();
        $table->string('payment_reference')->nullable();
        $table->unsignedBigInteger('recorded_by_admin_id')->nullable();
        $table->unsignedBigInteger('recorded_by_driver_id')->nullable();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->unsignedBigInteger('pickup_assignment_id')->nullable();
        $table->text('notes')->nullable();
        $table->text('waive_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

function createDriverForPickupFeeVisibilityTest(): Driver
{
    return Driver::create([
        'name' => 'Pickup Rider',
        'email' => 'pickup-rider@example.test',
        'phone' => '+233244000000',
        'password' => Hash::make('password123'),
        'status' => 'offline',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_PICKUP],
    ]);
}

function createVendorForPickupFeeVisibilityTest(): Vendor
{
    $vendor = new Vendor([
        'name' => 'Vendor Name',
        'business_name' => 'Vendor Biz',
        'phone' => '233241112222',
        'email' => 'vendor@example.test',
        'is_active' => true,
    ]);

    $vendor->pin_hash = Hash::make('1234');
    $vendor->is_phone_verified = true;
    $vendor->save();

    return $vendor;
}

beforeEach(function () {
    buildDriverPickupFeeVisibilitySchema();
});

test('driver pickup details include the latest active pickup fee status for collection', function () {
    $driver = createDriverForPickupFeeVisibilityTest();
    $vendor = createVendorForPickupFeeVisibilityTest();
    $region = Region::create([
        'name' => 'Greater Accra',
        'code' => 'GAR',
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00001',
        'status' => 'submitted',
        'source' => 'vendor_app',
        'destination_mode' => 'single',
        'pickup_contact_name' => 'Ama Vendor',
        'pickup_contact_phone' => '0241112222',
        'pickup_region_id' => $region->id,
        'pickup_town' => 'Madina',
    ]);

    ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Phone case',
        'quantity' => 2,
        'status' => 'pending',
    ]);

    $assignment = PickupAssignment::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);

    ShipmentCharge::create([
        'shipment_id' => $shipment->id,
        'pickup_assignment_id' => $assignment->id,
        'charge_type' => ShipmentCharge::TYPE_PICKUP_FEE,
        'payer_type' => ShipmentCharge::PAYER_VENDOR,
        'direction' => ShipmentCharge::DIRECTION_REVENUE,
        'due_stage' => ShipmentCharge::STAGE_AT_PICKUP,
        'amount' => 25.50,
        'currency' => 'GHS',
        'status' => ShipmentCharge::STATUS_PAID,
        'paid_at' => now()->subMinute(),
        'payment_method' => 'cash',
    ]);

    Sanctum::actingAs($driver);

    $response = $this->getJson("/api/v1/driver/pickups/{$assignment->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pickup.shipment.pickup_fee.amount', 25.5)
        ->assertJsonPath('data.pickup.shipment.pickup_fee.currency', 'GHS')
        ->assertJsonPath('data.pickup.shipment.pickup_fee.status', ShipmentCharge::STATUS_PAID)
        ->assertJsonPath('data.pickup.shipment.pickup_fee.is_paid', true);
});

test('driver pickup details include pending pickup fees from the shipment charges ledger', function () {
    $driver = createDriverForPickupFeeVisibilityTest();
    $vendor = createVendorForPickupFeeVisibilityTest();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00015',
        'status' => 'submitted',
        'source' => 'vendor_app',
        'destination_mode' => 'single',
        'pickup_contact_name' => 'Tony Mensa',
        'pickup_contact_phone' => '0542796510',
        'pickup_town' => 'Madina',
    ]);

    $assignment = PickupAssignment::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);

    ShipmentCharge::create([
        'shipment_id' => $shipment->id,
        'charge_type' => ShipmentCharge::TYPE_PICKUP_FEE,
        'payer_type' => ShipmentCharge::PAYER_VENDOR,
        'direction' => ShipmentCharge::DIRECTION_REVENUE,
        'due_stage' => ShipmentCharge::STAGE_AT_PICKUP,
        'amount' => 10.00,
        'currency' => 'GHS',
        'status' => ShipmentCharge::STATUS_PENDING,
    ]);

    Sanctum::actingAs($driver);

    $this->getJson("/api/v1/driver/pickups/{$assignment->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pickup.shipment.pickup_fee.amount', 10)
        ->assertJsonPath('data.pickup.shipment.pickup_fee.currency', 'GHS')
        ->assertJsonPath('data.pickup.shipment.pickup_fee.status', ShipmentCharge::STATUS_PENDING)
        ->assertJsonPath('data.pickup.shipment.pickup_fee.is_paid', false);
});

test('driver pickup arrival requires coordinates', function () {
    $driver = createDriverForPickupFeeVisibilityTest();
    $vendor = createVendorForPickupFeeVisibilityTest();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00022',
        'status' => 'submitted',
        'source' => 'vendor_app',
        'destination_mode' => 'single',
        'pickup_contact_name' => 'Ama Vendor',
        'pickup_contact_phone' => '0241112222',
        'pickup_town' => 'Madina',
    ]);

    $assignment = PickupAssignment::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'status' => 'en_route',
        'assigned_at' => now()->subMinutes(5),
        'en_route_at' => now()->subMinute(),
    ]);

    Sanctum::actingAs($driver);

    $this->postJson("/api/v1/driver/pickups/{$assignment->id}/arrive")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('driver pickup completion stores optional pickup coordinates and returns them in the timeline', function () {
    $driver = createDriverForPickupFeeVisibilityTest();
    $vendor = createVendorForPickupFeeVisibilityTest();

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-2026-00023',
        'status' => 'submitted',
        'source' => 'vendor_app',
        'destination_mode' => 'single',
        'pickup_contact_name' => 'Ama Vendor',
        'pickup_contact_phone' => '0241112222',
        'pickup_town' => 'Madina',
    ]);

    ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Phone case',
        'quantity' => 2,
        'status' => 'pending',
        'tracking_code' => 'TRK-TEST-00023',
    ]);

    $assignment = PickupAssignment::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'status' => 'arrived',
        'assigned_at' => now()->subMinutes(10),
        'en_route_at' => now()->subMinutes(6),
        'arrived_at' => now()->subMinute(),
    ]);

    Sanctum::actingAs($driver);

    $response = $this->postJson("/api/v1/driver/pickups/{$assignment->id}/confirm-pickup", [
        'driver_picked_quantity' => 2,
        'latitude' => 5.603717,
        'longitude' => -0.186964,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.assignment.pickup_latitude', '5.60371700')
        ->assertJsonPath('data.assignment.pickup_longitude', '-0.18696400')
        ->assertJsonPath('data.assignment.timeline.arrived_pickup.latitude', '5.60371700')
        ->assertJsonPath('data.assignment.timeline.arrived_pickup.longitude', '-0.18696400');

    $assignment->refresh();

    expect((string) $assignment->pickup_latitude)->toBe('5.60371700')
        ->and((string) $assignment->pickup_longitude)->toBe('-0.18696400');
});
