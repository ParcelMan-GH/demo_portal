<?php

use App\Events\DeliveryRunStopStatusChanged;
use App\Events\ShipmentStatusChanged;
use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\VendorCommissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

function adminDeliveryRunHandoffBuildSchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
        'delivery_run_items',
        'bus_handoff_confirmations',
        'delivery_run_stops',
        'delivery_runs',
        'shipment_item_tracking',
        'shipment_items',
        'shipments',
        'vendors',
        'role_permissions',
        'permissions',
        'user_roles',
        'roles',
        'warehouses',
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

    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->text('address')->nullable();
        $table->unsignedBigInteger('region_id')->nullable();
        $table->unsignedBigInteger('district_id')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('contact_phone')->nullable();
        $table->string('contact_email')->nullable();
        $table->integer('capacity')->default(0);
        $table->boolean('is_active')->default(true);
        $table->boolean('is_hq')->default(false);
        $table->boolean('can_administer_system')->default(false);
        $table->softDeletes();
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

    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('module');
        $table->string('action');
        $table->text('description')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('role_permissions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
        $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
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
        $table->string('phone')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('vendor_id')->nullable();
        $table->string('shipment_number')->nullable();
        $table->string('status')->default('submitted');
        $table->string('destination_mode')->default('single');
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->timestamp('submitted_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id');
        $table->string('description')->nullable();
        $table->string('tracking_code')->nullable();
        $table->unsignedInteger('quantity')->default(1);
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->string('status')->default('handed_to_courier');
        $table->timestamps();
    });

    Schema::create('shipment_item_tracking', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_item_id');
        $table->string('status');
        $table->string('location')->nullable();
        $table->text('notes')->nullable();
        $table->json('meta')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('delivery_runs', function (Blueprint $table) {
        $table->id();
        $table->string('run_number')->nullable();
        $table->unsignedBigInteger('sort_batch_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->unsignedBigInteger('assigned_driver_id')->nullable();
        $table->string('status')->default(DeliveryRun::STATUS_DRAFT);
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('dispatched_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_stops', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->string('recipient_name')->nullable();
        $table->string('recipient_phone')->nullable();
        $table->string('town')->nullable();
        $table->string('landmark')->nullable();
        $table->string('bus_station_name')->nullable();
        $table->string('handoff_courier_name')->nullable();
        $table->string('handoff_courier_phone')->nullable();
        $table->string('handoff_vehicle_number')->nullable();
        $table->timestamp('handoff_at')->nullable();
        $table->string('proof_photo_path')->nullable();
        $table->unsignedInteger('total_packages')->default(1);
        $table->string('status')->default(DeliveryRunStop::STATUS_PENDING);
        $table->string('delivery_method')->default(DeliveryRunStop::METHOD_DIRECT);
        $table->unsignedBigInteger('confirmed_by_admin_id')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->text('confirmation_notes')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('delivered_quantity')->default(0);
        $table->string('status')->default(DeliveryRunItem::STATUS_PENDING);
        $table->text('notes')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
    });

    Schema::create('bus_handoff_confirmations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id');
        $table->unsignedBigInteger('delivery_run_stop_id');
        $table->unsignedBigInteger('delivery_run_item_id')->unique();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->unsignedBigInteger('handoff_driver_id')->nullable();
        $table->string('status', 40)->default('pending');
        $table->string('source', 40)->nullable();
        $table->string('target_type', 20)->nullable();
        $table->string('target_name')->nullable();
        $table->string('target_phone', 40)->nullable();
        $table->string('confirmation_code_hash')->nullable();
        $table->timestamp('confirmation_code_sent_at')->nullable();
        $table->timestamp('confirmation_code_expires_at')->nullable();
        $table->timestamp('confirmation_code_verified_at')->nullable();
        $table->unsignedInteger('confirmation_attempts')->default(0);
        $table->string('public_token_hash', 64)->nullable()->unique();
        $table->timestamp('public_token_expires_at')->nullable();
        $table->timestamp('public_link_sent_at')->nullable();
        $table->unsignedBigInteger('reason_id')->nullable();
        $table->string('reason_label')->nullable();
        $table->string('reason_type')->nullable();
        $table->text('issue_notes')->nullable();
        $table->text('confirmation_notes')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->unsignedBigInteger('confirmed_by_driver_id')->nullable();
        $table->unsignedBigInteger('confirmed_by_admin_id')->nullable();
        $table->timestamp('public_confirmed_at')->nullable();
        $table->timestamp('public_reported_at')->nullable();
        $table->timestamps();
    });
}

function adminDeliveryRunHandoffCreateAdmin(): User
{
    $warehouse = Warehouse::create([
        'name' => 'HQ Warehouse',
        'code' => 'HQ',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.test',
        'password' => Hash::make('password'),
        'is_active' => true,
        'warehouse_id' => $warehouse->id,
    ]);

    $role = Role::create([
        'name' => 'Super Admin',
        'slug' => 'super_admin',
        'description' => 'Delivery handoff test role',
        'is_system_role' => true,
        'is_active' => true,
    ]);

    $permission = Permission::create([
        'name' => 'shipments.edit',
        'module' => 'shipments',
        'action' => 'edit',
        'description' => 'Edit shipments and delivery handoff status',
    ]);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    $role->permissions()->attach($permission->id);
    $user->flushPermissionCache();

    return $user->fresh();
}

function adminDeliveryRunHandoffCreateShipmentItem(string $trackingCode): int
{
    $now = now();
    $vendorId = DB::table('vendors')->insertGetId([
        'name' => 'Test Vendor',
        'business_name' => 'Test Vendor Ltd',
        'phone' => '+233200000000',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $shipmentId = DB::table('shipments')->insertGetId([
        'vendor_id' => $vendorId,
        'shipment_number' => 'PCM-2026-'.$trackingCode,
        'status' => 'handed_to_courier',
        'destination_mode' => 'single',
        'delivery_recipient_name' => 'Recipient',
        'delivery_recipient_phone' => '+233244000000',
        'delivery_town' => 'Lapaz',
        'submitted_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return DB::table('shipment_items')->insertGetId([
        'shipment_id' => $shipmentId,
        'description' => 'Package '.$trackingCode,
        'tracking_code' => 'TRK-'.$trackingCode,
        'quantity' => 1,
        'delivery_recipient_name' => 'Recipient',
        'delivery_recipient_phone' => '+233244000000',
        'delivery_town' => 'Lapaz',
        'status' => 'handed_to_courier',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

beforeEach(function () {
    adminDeliveryRunHandoffBuildSchema();
    Event::fake([DeliveryRunStopStatusChanged::class, ShipmentStatusChanged::class]);
    $this->withoutMiddleware(LogAdminAuditActivity::class);
    $this->actingAs(adminDeliveryRunHandoffCreateAdmin(), 'admin');
});

test('admin handoff confirmation completes the run when the last stop is resolved', function () {
    $this->mock(VendorCommissionService::class, function ($mock) {
        $mock->shouldReceive('createEarningsForStop')->once()->andReturn(0);
    });

    $run = DeliveryRun::create([
        'run_number' => 'DRN-2026-00015',
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
    ]);

    $stop = DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Bus Station Handoff',
        'status' => DeliveryRunStop::STATUS_HANDED_OFF,
        'delivery_method' => DeliveryRunStop::METHOD_BUS_HANDOFF,
        'total_packages' => 2,
    ]);

    DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => adminDeliveryRunHandoffCreateShipmentItem('00015A'),
        'status' => DeliveryRunItem::STATUS_DELIVERED,
    ]);

    $item = DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => adminDeliveryRunHandoffCreateShipmentItem('00015B'),
        'status' => DeliveryRunItem::STATUS_HANDED_OFF,
    ]);

    $response = $this->postJson(route('admin.delivery-runs.stops.items.confirm-handoff', [$run, $stop, $item]), [
        'action' => 'delivered',
        'notes' => 'Recipient confirmed by phone.',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('all_resolved', true)
        ->assertJsonPath('run_status', DeliveryRun::STATUS_COMPLETED);

    expect($stop->fresh()->status)->toBe(DeliveryRunStop::STATUS_DELIVERED)
        ->and($run->fresh()->status)->toBe(DeliveryRun::STATUS_COMPLETED)
        ->and($run->fresh()->completed_at)->not->toBeNull();
});

test('admin handoff confirmation partially delivers the run when other stops remain open', function () {
    $this->mock(VendorCommissionService::class, function ($mock) {
        $mock->shouldReceive('createEarningsForStop')->once()->andReturn(0);
    });

    $run = DeliveryRun::create([
        'run_number' => 'DRN-2026-00016',
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
    ]);

    $resolvedStop = DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Bus Station Handoff',
        'status' => DeliveryRunStop::STATUS_HANDED_OFF,
        'delivery_method' => DeliveryRunStop::METHOD_BUS_HANDOFF,
    ]);

    DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Pending Recipient',
        'status' => DeliveryRunStop::STATUS_PENDING,
    ]);

    $item = DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $resolvedStop->id,
        'shipment_item_id' => adminDeliveryRunHandoffCreateShipmentItem('00016A'),
        'status' => DeliveryRunItem::STATUS_HANDED_OFF,
    ]);

    $response = $this->postJson(route('admin.delivery-runs.stops.items.confirm-handoff', [$run, $resolvedStop, $item]), [
        'action' => 'delivered',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('all_resolved', true)
        ->assertJsonPath('run_status', DeliveryRun::STATUS_PARTIALLY_DELIVERED);

    expect($resolvedStop->fresh()->status)->toBe(DeliveryRunStop::STATUS_DELIVERED)
        ->and($run->fresh()->status)->toBe(DeliveryRun::STATUS_PARTIALLY_DELIVERED)
        ->and($run->fresh()->completed_at)->toBeNull();
});
