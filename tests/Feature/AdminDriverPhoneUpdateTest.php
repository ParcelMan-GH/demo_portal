<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\Driver;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

function createDriverAdminUserForPhoneUpdateTest(): User
{
    $warehouse = Warehouse::create([
        'name' => 'HQ Warehouse',
        'code' => 'WH-HQ',
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

    $permission = Permission::create([
        'module' => 'drivers',
        'action' => 'edit',
        'name' => 'drivers.edit',
        'description' => 'Edit existing riders',
        'sort_order' => 62,
        'is_active' => true,
    ]);

    $role->permissions()->attach($permission->id);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    $user->flushPermissionCache();

    return $user;
}

function buildDriverPhoneUpdateTestSchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
        'drivers',
        'role_permissions',
        'permissions',
        'user_roles',
        'roles',
        'users',
        'warehouses',
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
        $table->boolean('is_active')->default(true);
        $table->boolean('is_hq')->default(false);
        $table->boolean('can_administer_system')->default(false);
        $table->timestamps();
        $table->softDeletes();
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
        $table->string('module');
        $table->string('action');
        $table->string('name')->unique();
        $table->text('description')->nullable();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
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
}

function createDriverRecordForPhoneUpdateTest(array $overrides = []): Driver
{
    return Driver::create(array_merge([
        'name' => 'John Rider',
        'email' => 'driver@example.test',
        'phone' => '+233244111111',
        'password' => Hash::make('password123'),
        'vehicle_type' => 'van',
        'vehicle_number' => 'GR-1234-20',
        'license_number' => 'DL123457',
        'status' => 'offline',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_PICKUP],
    ], $overrides));
}

beforeEach(function () {
    buildDriverPhoneUpdateTestSchema();
    $this->withoutMiddleware(LogAdminAuditActivity::class);
    $this->actingAs(createDriverAdminUserForPhoneUpdateTest(), 'admin');
});

test('admin can update a driver phone number from the driver management endpoint', function () {
    $driver = createDriverRecordForPhoneUpdateTest();

    $response = $this->putJson(route('admin.drivers.update', $driver), [
        'name' => 'John Rider',
        'email' => 'driver@example.test',
        'phone' => '0541234567',
        'vehicle_type' => 'van',
        'vehicle_number' => 'GR-1234-20',
        'license_number' => 'DL123457',
        'task_capabilities' => [Driver::CAPABILITY_PICKUP, Driver::CAPABILITY_DELIVERY],
        'is_active' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('driver.phone', '+233541234567');

    expect($driver->fresh()->phone)->toBe('+233541234567')
        ->and($driver->fresh()->getCapabilities())->toEqualCanonicalizing([
            Driver::CAPABILITY_PICKUP,
            Driver::CAPABILITY_DELIVERY,
        ]);
});
