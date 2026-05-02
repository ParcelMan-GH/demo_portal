<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Role;
use App\Models\User;
use App\Services\VendorCommissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

function adminDeliveryRunHandoffBuildSchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
        'delivery_run_items',
        'delivery_run_stops',
        'delivery_runs',
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
        $table->unsignedInteger('total_packages')->default(1);
        $table->string('status')->default(DeliveryRunStop::STATUS_PENDING);
        $table->string('delivery_method')->default(DeliveryRunStop::METHOD_DIRECT);
        $table->unsignedBigInteger('confirmed_by_admin_id')->nullable();
        $table->timestamp('confirmed_at')->nullable();
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
}

function adminDeliveryRunHandoffCreateAdmin(): User
{
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.test',
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);

    $role = Role::create([
        'name' => 'Super Admin',
        'slug' => 'super_admin',
        'description' => 'Delivery handoff test role',
        'is_system_role' => true,
        'is_active' => true,
    ]);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    return $user;
}

beforeEach(function () {
    adminDeliveryRunHandoffBuildSchema();
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
        'status' => DeliveryRunItem::STATUS_DELIVERED,
    ]);

    $item = DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
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
