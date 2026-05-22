<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCapability;
use App\Services\BackOfficeAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\LogAdminAuditActivity::class);

    Schema::dropIfExists('warehouse_capabilities');
    Schema::dropIfExists('admin_audit_logs');
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
        $table->string('email')->nullable()->unique();
        $table->string('phone', 20)->nullable()->unique();
        $table->string('photo_path')->nullable();
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

    Schema::create('admin_audit_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('warehouse_id')->nullable();
        $table->string('scope', 20)->nullable();
        $table->string('action_type', 40);
        $table->string('action', 180);
        $table->string('description', 255)->nullable();
        $table->string('method', 12)->nullable();
        $table->string('route_name', 180)->nullable();
        $table->string('url', 2048)->nullable();
        $table->unsignedSmallInteger('status_code')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->unsignedInteger('duration_ms')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('created_at')->useCurrent();
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
        $model = Permission::firstOrCreate(
            ['name' => $permission],
            [
                'module' => str($permission)->before('.')->toString(),
                'action' => str($permission)->after('.')->toString(),
                'description' => $permission,
            ]
        );

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

test('hq users page lists users across warehouses and filters by warehouse', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-USERS',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-USERS',
        'is_active' => true,
    ]);

    $hqUser = makeBackOfficeUser($hq, ['warehouse.users.view']);
    $branchUser = makeBackOfficeUser($branch, ['warehouse.users.view']);

    $this->actingAs($hqUser, 'admin')
        ->getJson(route('warehouse.users.data'))
        ->assertOk()
        ->assertJsonFragment(['id' => $hqUser->id])
        ->assertJsonFragment(['id' => $branchUser->id]);

    $response = $this->actingAs($hqUser, 'admin')
        ->getJson(route('warehouse.users.data', ['warehouse_id' => $branch->id]))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$branchUser->id]);
});

test('branch users page remains scoped to its own warehouse', function () {
    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-SCOPED',
        'is_active' => true,
    ]);

    $other = Warehouse::create([
        'name' => 'Other',
        'code' => 'BR-SCOPED-2',
        'is_active' => true,
    ]);

    $branchUser = makeBackOfficeUser($branch, ['warehouse.users.view']);
    $otherUser = makeBackOfficeUser($other, ['warehouse.users.view']);

    $this->actingAs($branchUser, 'admin')
        ->getJson(route('warehouse.users.data', ['warehouse_id' => $other->id]))
        ->assertOk()
        ->assertJsonMissing(['id' => $otherUser->id])
        ->assertJsonFragment(['id' => $branchUser->id]);
});

test('hq user can create a phone-only user for a selected warehouse', function () {
    Storage::fake('public');

    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-CREATE-USER',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-CREATE-USER',
        'is_active' => true,
    ]);

    $actor = makeBackOfficeUser($hq, ['warehouse.users.create']);
    $role = Role::create([
        'name' => 'Assignable User',
        'slug' => 'assignable-user',
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => true,
        'is_active' => true,
    ]);

    $this->actingAs($actor, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('warehouse.users.store'), [
            'name' => 'Phone Only User',
            'phone' => '0241234501',
            'email' => null,
            'warehouse_id' => $branch->id,
            'role_id' => $role->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile_photo' => UploadedFile::fake()->image('user.jpg', 320, 320),
        ])
        ->assertOk();

    $created = User::where('phone', '+233241234501')->first();

    expect($created)->not->toBeNull()
        ->and($created->email)->toBeNull()
        ->and($created->warehouse_id)->toBe($branch->id)
        ->and($created->photo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($created->photo_path);
});

test('branch user creation stays scoped to own warehouse even if warehouse id is submitted', function () {
    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-LOCAL-CREATE',
        'is_active' => true,
    ]);

    $other = Warehouse::create([
        'name' => 'Other',
        'code' => 'BR-LOCAL-CREATE-2',
        'is_active' => true,
    ]);

    $actor = makeBackOfficeUser($branch, ['warehouse.users.create']);
    $role = Role::create([
        'name' => 'Local Assignable User',
        'slug' => 'local-assignable-user',
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => true,
        'is_active' => true,
    ]);

    $this->actingAs($actor, 'admin')
        ->postJson(route('warehouse.users.store'), [
            'name' => 'Local User',
            'phone' => '0241234502',
            'email' => null,
            'warehouse_id' => $other->id,
            'role_id' => $role->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertOk();

    $created = User::where('phone', '+233241234502')->first();

    expect($created)->not->toBeNull()
        ->and($created->warehouse_id)->toBe($branch->id);
});

test('user phone validation requires an exact valid 10 digit Ghana number', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-PHONE-VALIDATION',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $actor = makeBackOfficeUser($hq, ['warehouse.users.create']);
    $role = Role::create([
        'name' => 'Phone Validation Role',
        'slug' => 'phone-validation-role',
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => true,
        'is_active' => true,
    ]);

    $payload = [
        'name' => 'Invalid Phone User',
        'email' => null,
        'warehouse_id' => $hq->id,
        'role_id' => $role->id,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($actor, 'admin')
        ->postJson(route('warehouse.users.store'), $payload + ['phone' => '024123450'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');

    $this->actingAs($actor, 'admin')
        ->postJson(route('warehouse.users.store'), $payload + ['phone' => '0301234501'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');
});

test('hq user can move another user to a different warehouse while keeping email optional', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-MOVE-USER',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-MOVE-USER',
        'is_active' => true,
    ]);

    $other = Warehouse::create([
        'name' => 'Other',
        'code' => 'BR-MOVE-USER-2',
        'is_active' => true,
    ]);

    $actor = makeBackOfficeUser($hq, ['warehouse.users.edit', 'warehouse.users.assign_roles']);
    $target = makeBackOfficeUser($branch);
    $role = Role::create([
        'name' => 'Moved User Role',
        'slug' => 'moved-user-role',
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => true,
        'is_active' => true,
    ]);

    $this->actingAs($actor, 'admin')
        ->putJson(route('warehouse.users.update', $target), [
            'name' => 'Moved User',
            'phone' => '0241234503',
            'email' => null,
            'warehouse_id' => $other->id,
            'role_id' => $role->id,
            'is_active' => true,
        ])
        ->assertOk();

    expect($target->fresh()->warehouse_id)->toBe($other->id)
        ->and($target->fresh()->email)->toBeNull()
        ->and($target->fresh()->roles()->first()->id)->toBe($role->id);
});

test('hq user with impersonation permission can login as another active user and return', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ-IMP',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-IMP',
        'is_active' => true,
    ]);

    $hqUser = makeBackOfficeUser($hq, ['warehouse.users.view', 'warehouse.users.impersonate', 'warehouse.dashboard.view']);
    $target = makeBackOfficeUser($branch, ['warehouse.dashboard.view']);

    $this->actingAs($hqUser, 'admin')
        ->postJson(route('warehouse.users.impersonate', $target))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertAuthenticatedAs($target, 'admin');
    expect(session('impersonation.impersonator_id'))->toBe($hqUser->id);

    $this->post(route('admin.impersonation.stop'))
        ->assertRedirect();

    $this->assertAuthenticatedAs($hqUser, 'admin');
    expect(session()->has('impersonation.impersonator_id'))->toBeFalse();
});

test('non hq users cannot impersonate even with the permission', function () {
    $branch = Warehouse::create([
        'name' => 'Branch',
        'code' => 'BR-NOIMP',
        'is_active' => true,
    ]);

    $actor = makeBackOfficeUser($branch, ['warehouse.users.view', 'warehouse.users.impersonate']);
    $target = makeBackOfficeUser($branch, ['warehouse.dashboard.view']);

    $this->actingAs($actor, 'admin')
        ->postJson(route('warehouse.users.impersonate', $target))
        ->assertForbidden();
});
