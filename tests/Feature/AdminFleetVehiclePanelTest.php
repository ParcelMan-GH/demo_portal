<?php

use App\Models\Permission;
use App\Models\PickupVehicleType;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function afvpWarehouse(): Warehouse
{
    return Warehouse::query()->create([
        'name' => 'Fleet Panel Warehouse',
        'code' => 'WH-FLEET',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
}

function afvpAdmin(Warehouse $warehouse, array $permissions = ['settings.view']): User
{
    $admin = User::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'Fleet Panel Role '.$admin->id,
        'slug' => 'fleet-panel-role-'.$admin->id,
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissions as $permissionName) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $permissionName],
            [
                'module' => str($permissionName)->before('.')->toString(),
                'action' => str($permissionName)->after('.')->toString(),
                'description' => $permissionName,
            ],
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $admin->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $admin->id,
    ]);

    $admin->flushPermissionCache();

    return $admin->fresh();
}

it('renders the vehicle fleet panel listing every type', function () {
    $admin = afvpAdmin(afvpWarehouse());

    // The grid itself is rendered client-side by Alpine from the injected
    // data, so the vehicle names live in the markup rather than in the text.
    $this->actingAs($admin, 'admin')
        ->get(route('admin.fleet.vehicles.index'))
        ->assertOk()
        ->assertSeeText('Vehicle Fleet')
        ->assertSee('Motorbike')
        ->assertSee('Van')
        ->assertSee('Truck');
});

it('reflects locked and available counts for the fleet', function () {
    $admin = afvpAdmin(afvpWarehouse());

    // Van and Truck ship locked, so two of the four seeded types are unavailable.
    $this->actingAs($admin, 'admin')
        ->get(route('admin.fleet.vehicles.index'))
        ->assertOk()
        ->assertSeeText('2 available')
        ->assertSeeText('2 locked');
});

it('unlocks a vehicle type through the fleet panel toggle', function () {
    // Toggling availability is an edit, so it needs settings.edit as well.
    $admin = afvpAdmin(afvpWarehouse(), ['settings.view', 'settings.edit']);
    $van = PickupVehicleType::query()->where('slug', 'van')->firstOrFail();

    expect($van->is_active)->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->patchJson(route('admin.settings.pickup-vehicles.toggle', $van))
        ->assertOk()
        ->assertJsonPath('vehicle_type.is_active', true);

    expect($van->fresh()->is_active)->toBeTrue();
});

it('keeps the fleet panel closed to an admin without the settings permission', function () {
    $admin = afvpAdmin(afvpWarehouse(), []);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.fleet.vehicles.index'))
        ->assertForbidden();
});
