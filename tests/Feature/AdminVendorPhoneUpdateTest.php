<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function buildVendorPhoneUpdateTestSchema(): void
{
    Schema::disableForeignKeyConstraints();
    foreach ([
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

    Schema::create('user_roles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
        $table->timestamp('assigned_at')->nullable();
        $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
    });

    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('module');
        $table->string('action');
        $table->string('name')->unique();
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

    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('business_name')->nullable();
        $table->string('phone')->unique();
        $table->string('email')->nullable()->unique();
        $table->boolean('is_active')->default(true);
        $table->string('fcm_token')->nullable();
        $table->decimal('commission_rate_override', 10, 2)->nullable();
        $table->string('payout_momo_network', 40)->nullable();
        $table->string('payout_account_name')->nullable();
        $table->string('payout_account_number', 20)->nullable();
        $table->timestamp('payout_account_updated_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

function createVendorAdminUserForPhoneUpdateTest(): User
{
    $warehouse = Warehouse::create([
        'name' => 'Vendor Phone HQ',
        'code' => 'VP-HQ',
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

    $permission = Permission::create([
        'module' => 'vendors',
        'action' => 'edit',
        'name' => 'vendors.edit',
        'description' => 'Edit vendors',
    ]);

    $role->permissions()->attach($permission->id);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);
    $user->flushPermissionCache();

    return $user->fresh();
}

function createVendorPhoneUpdateRecord(array $overrides = []): Vendor
{
    return Vendor::create(array_merge([
        'name' => 'Test Vendor',
        'business_name' => 'Test Business',
        'phone' => '+233244111111',
        'email' => 'vendor@example.test',
        'is_active' => true,
    ], $overrides));
}

beforeEach(function () {
    buildVendorPhoneUpdateTestSchema();
    $this->withoutMiddleware(LogAdminAuditActivity::class);
    $this->actingAs(createVendorAdminUserForPhoneUpdateTest(), 'admin');
});

test('admin can update a vendor phone number and it is normalized', function () {
    $vendor = createVendorPhoneUpdateRecord();

    $response = $this->putJson(route('admin.vendors.update', $vendor), [
        'name' => 'Test Vendor',
        'business_name' => 'Test Business',
        'email' => 'vendor@example.test',
        'phone' => '0541234567',
        'is_active' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('vendor.phone', '+233541234567');

    expect($vendor->fresh()->phone)->toBe('+233541234567');
});

test('vendor phone update rejects numbers already stored in equivalent local or international format', function () {
    createVendorPhoneUpdateRecord([
        'name' => 'Existing Vendor',
        'phone' => '+233201234567',
        'email' => 'existing@example.test',
    ]);

    $vendor = createVendorPhoneUpdateRecord([
        'name' => 'Other Vendor',
        'phone' => '+233244111111',
        'email' => 'other@example.test',
    ]);

    $response = $this->putJson(route('admin.vendors.update', $vendor), [
        'name' => 'Other Vendor',
        'business_name' => 'Other Business',
        'email' => 'other@example.test',
        'phone' => '0201234567',
        'is_active' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['phone']);

    expect($vendor->fresh()->phone)->toBe('+233244111111');
});
