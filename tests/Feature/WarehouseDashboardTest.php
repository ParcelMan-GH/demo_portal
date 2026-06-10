<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\DeliveryRun;
use App\Models\Driver;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(LogAdminAuditActivity::class);
});

function makeWarehouseDashboardUser(Warehouse $warehouse, array $permissionNames): User
{
    $user = User::factory()->create([
        'name' => 'Warehouse Dashboard Admin',
        'email' => 'warehouse-dashboard-admin@example.test',
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::create([
        'name' => 'Warehouse Dashboard Role',
        'slug' => 'warehouse-dashboard-role-' . $user->id,
        'description' => 'Dashboard test role',
        'is_system_role' => false,
        'is_warehouse_role' => false,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissionNames as $permissionName) {
        [$module, $action] = array_pad(explode('.', $permissionName, 2), 2, 'view');

        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            [
                'module' => $module,
                'action' => $action,
                'description' => $permissionName,
            ],
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);
    $user->flushPermissionCache();

    return $user->fresh();
}

test('warehouse dashboard renders delivery run activity with assigned driver', function (): void {
    $warehouse = Warehouse::create([
        'name' => 'Dashboard HQ',
        'code' => 'WH-DASH',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $user = makeWarehouseDashboardUser($warehouse, ['warehouse.delivery.assign']);

    $driver = Driver::create([
        'name' => 'Kojo Delivery',
        'email' => 'kojo-dashboard@example.test',
        'phone' => '+233240900001',
        'password' => Hash::make('password'),
        'vehicle_type' => 'motorcycle',
        'vehicle_number' => 'PM-100',
        'status' => 'busy',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_DELIVERY],
    ]);

    $sortBatch = SortBatch::create([
        'batch_number' => 'SB-DASH-001',
        'origin_warehouse_id' => $warehouse->id,
        'destination_warehouse_id' => $warehouse->id,
        'dispatch_mode' => SortBatch::DISPATCH_LOCAL_DELIVERY,
        'status' => SortBatch::STATUS_SEALED,
        'created_by_user_id' => $user->id,
        'sealed_by_user_id' => $user->id,
        'sealed_at' => now(),
    ]);

    DeliveryRun::create([
        'run_number' => 'DR-DASH-001',
        'sort_batch_id' => $sortBatch->id,
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $driver->id,
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
        'assigned_at' => now(),
        'dispatched_at' => now(),
        'created_by_user_id' => $user->id,
    ]);

    $this->actingAs($user, 'admin')
        ->get(route('warehouse.dashboard'))
        ->assertOk()
        ->assertSeeText('Delivery run')
        ->assertSeeText('DR-DASH-001')
        ->assertSeeText('Kojo Delivery');
});
