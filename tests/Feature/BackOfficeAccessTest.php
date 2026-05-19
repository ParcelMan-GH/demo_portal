<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCapability;
use App\Services\BackOfficeAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\LogAdminAuditActivity::class);

    Schema::dropIfExists('warehouse_capabilities');
    Schema::dropIfExists('user_roles');
    Schema::dropIfExists('role_permissions');
    Schema::dropIfExists('permissions');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('users');
    Schema::dropIfExists('warehouses');

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

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone', 20)->nullable()->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->timestamp('last_login_at')->nullable();
        $table->timestamp('last_permission_cache_at')->nullable();
        $table->foreignId('created_by_user_id')->nullable();
        $table->foreignId('warehouse_id')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->boolean('is_system_role')->default(false);
        $table->boolean('is_warehouse_role')->default(false);
        $table->boolean('is_assignable_by_warehouse_manager')->default(false);
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
        $table->timestamps();
    });

    Schema::create('role_permissions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('role_id');
        $table->foreignId('permission_id');
        $table->timestamps();
    });

    Schema::create('user_roles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('role_id');
        $table->timestamp('assigned_at')->nullable();
        $table->foreignId('assigned_by')->nullable();
    });

    Schema::create('warehouse_capabilities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('warehouse_id');
        $table->string('module');
        $table->string('scope')->default('own');
        $table->json('allowed_warehouse_ids')->nullable();
        $table->foreignId('granted_by_user_id')->nullable();
        $table->timestamps();
    });
});

function makeBackOfficeUser(Warehouse $warehouse, array $permissions = []): User
{
    $role = Role::create([
        'name' => 'Test Role ' . uniqid(),
        'slug' => 'test-role-' . uniqid(),
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissions as $permission) {
        $model = Permission::create([
            'module' => str($permission)->before('.')->toString(),
            'action' => str($permission)->after('.')->toString(),
            'name' => $permission,
            'description' => $permission,
        ]);

        $role->permissions()->attach($model);
    }

    $user = User::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $user->roles()->attach($role, ['assigned_at' => now()]);

    return $user;
}

test('hq warehouse user can use every module through back office access', function () {
    $warehouse = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-001',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $user = makeBackOfficeUser($warehouse);

    expect(app(BackOfficeAccess::class)->canUseModule($user, 'vendors'))->toBeTrue();
});

test('non hq warehouse needs a capability grant for admin modules', function () {
    $warehouse = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-001',
        'is_active' => true,
        'is_hq' => false,
        'can_administer_system' => false,
    ]);

    $user = makeBackOfficeUser($warehouse, ['vendors.view']);
    $access = app(BackOfficeAccess::class);

    expect($access->canUseModule($user, 'vendors'))->toBeFalse()
        ->and($access->canUsePermission($user, 'vendors.view'))->toBeFalse();

    WarehouseCapability::create([
        'warehouse_id' => $warehouse->id,
        'module' => 'vendors',
        'scope' => WarehouseCapability::SCOPE_OWN,
    ]);

    expect($access->canUseModule($user->fresh(), 'vendors'))->toBeTrue()
        ->and($access->canUsePermission($user->fresh(), 'vendors.view'))->toBeTrue();
});

test('local warehouse operations remain available without explicit capability grants', function () {
    $warehouse = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-002',
        'is_active' => true,
        'is_hq' => false,
        'can_administer_system' => false,
    ]);

    $user = makeBackOfficeUser($warehouse, ['warehouse.receiving.manage']);

    expect(app(BackOfficeAccess::class)->canUsePermission($user, 'warehouse.receiving.manage'))->toBeTrue();
});

test('hq user can select all warehouses or one warehouse context', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-CTX',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-CTX',
        'is_active' => true,
    ]);

    $user = makeBackOfficeUser($hq);

    $this->actingAs($user, 'admin')
        ->post(route('admin.context.warehouse.update'), ['warehouse_id' => $branch->id])
        ->assertRedirect();

    expect(session('backoffice.selected_warehouse_id'))->toBe($branch->id);

    $this->actingAs($user, 'admin')
        ->post(route('admin.context.warehouse.update'), ['warehouse_id' => 'all'])
        ->assertRedirect();

    expect(session()->has('backoffice.selected_warehouse_id'))->toBeFalse();
});

test('branch user cannot select another warehouse context', function () {
    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-OWN',
        'is_active' => true,
    ]);

    $other = Warehouse::create([
        'name' => 'Other',
        'code' => 'BR-OTHER',
        'is_active' => true,
    ]);

    $user = makeBackOfficeUser($branch, ['dashboard.view']);

    $this->actingAs($user, 'admin')
        ->withHeader('referer', url('/admin'))
        ->post(route('admin.context.warehouse.update'), ['warehouse_id' => $other->id])
        ->assertForbidden();
});

test('hq user can grant module capabilities to a warehouse', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-GRANT',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-GRANT',
        'is_active' => true,
    ]);

    $user = makeBackOfficeUser($hq);

    $this->actingAs($user, 'admin')
        ->put(route('admin.warehouses.capabilities.update', $branch), [
            'capabilities' => [
                [
                    'module' => 'vendors',
                    'scope' => WarehouseCapability::SCOPE_GLOBAL,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('capabilities.vendors.scope', WarehouseCapability::SCOPE_GLOBAL);

    expect($branch->capabilities()->where('module', 'vendors')->exists())->toBeTrue();
});

test('branch user cannot grant warehouse capabilities', function () {
    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-NOGRANT',
        'is_active' => true,
    ]);

    $other = Warehouse::create([
        'name' => 'Other',
        'code' => 'BR-NOGRANT-2',
        'is_active' => true,
    ]);

    $user = makeBackOfficeUser($branch, ['warehouses.update']);

    $this->actingAs($user, 'admin')
        ->put(route('admin.warehouses.capabilities.update', $other), [
            'capabilities' => [
                ['module' => 'vendors', 'scope' => WarehouseCapability::SCOPE_GLOBAL],
            ],
        ])
        ->assertForbidden();
});

test('warehouse operation route names now resolve under the back office prefix', function () {
    expect(route('warehouse.walkin.create', absolute: false))->toBe('/admin/operations/walkin')
        ->and(route('warehouse.dashboard', absolute: false))->toBe('/admin/operations');
});

test('old warehouse URL family redirects to unified operation routes during migration', function () {
    $this->get('/warehouse/walkin')
        ->assertRedirect('/admin/operations/walkin')
        ->assertStatus(308);
});
