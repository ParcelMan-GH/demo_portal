<?php

use App\Models\Driver;
use App\Models\Permission;
use App\Models\PickupAssignment;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function receiptRouteStateAdmin(Warehouse $warehouse): User
{
    $admin = User::factory()->create([
        'name' => 'Receipt Route Admin',
        'email' => 'receipt-route-admin-'.uniqid().'@example.test',
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::create([
        'name' => 'Receipt Route Role '.$admin->id,
        'slug' => 'receipt-route-role-'.$admin->id,
        'description' => 'Receipt route state regression role',
        'is_system_role' => false,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach (['warehouse.receiving.manage', 'warehouse.receiving.approve_discrepancy'] as $name) {
        [$module, $action] = array_pad(explode('.', $name, 2), 2, 'view');
        $permission = Permission::firstOrCreate(
            ['name' => $name],
            ['module' => $module, 'action' => $action, 'description' => $name],
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

function receiptRouteStateAssignment(Warehouse $warehouse): PickupAssignment
{
    $vendor = Vendor::create([
        'name' => 'Receipt Route Vendor',
        'phone' => '+233240000100',
        'email' => 'receipt-route-vendor@example.test',
        'pin_hash' => Hash::make('1234'),
        'is_phone_verified' => true,
        'is_active' => true,
    ]);

    $driver = Driver::create([
        'name' => 'Receipt Route Rider',
        'email' => 'receipt-route-rider@example.test',
        'phone' => '+233240000101',
        'password' => Hash::make('password'),
        'vehicle_type' => 'motorbike',
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_PICKUP],
    ]);

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-ROUTE-'.uniqid(),
        'status' => 'out_for_delivery',
        'pickup_contact_name' => 'Kofi Sender',
        'pickup_contact_phone' => '+233240000102',
        'pickup_town' => 'Accra',
        'delivery_recipient_name' => 'Ama Receiver',
        'delivery_recipient_phone' => '+233240000103',
        'delivery_town' => 'Kasoa',
        'submitted_at' => now(),
    ]);

    $assignment = PickupAssignment::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => 'completed',
        'assigned_at' => now()->subHours(3),
        'picked_up_at' => now()->subHours(2),
        'completed_at' => now()->subHours(2),
        'arrived_warehouse_at' => now()->subHour(),
        'received_warehouse_id' => $warehouse->id,
        'received_at' => now()->subHour(),
        'driver_picked_quantity' => 1,
    ]);

    WarehouseReceipt::create([
        'pickup_assignment_id' => $assignment->id,
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'status' => WarehouseReceipt::STATUS_FINALIZED,
        'started_at' => now()->subHour(),
        'finalized_at' => now()->subMinutes(30),
    ]);

    return $assignment;
}

test('pending receipt detail redirects to received pickup detail after warehouse receipt is finalized', function () {
    $warehouse = Warehouse::create([
        'name' => 'Accra Main',
        'code' => 'ACC',
        'address' => 'Accra',
        'is_active' => true,
    ]);
    $admin = receiptRouteStateAdmin($warehouse);
    $assignment = receiptRouteStateAssignment($warehouse);

    $this->actingAs($admin, 'admin')
        ->get(route('warehouse.receipts.pending.show', $assignment))
        ->assertRedirect(route('warehouse.pickups.received.show', $assignment));
});

test('hq user can open received pickup detail for an accessible warehouse without selecting it first', function () {
    $hq = Warehouse::create([
        'name' => 'HQ',
        'code' => 'HQ',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
    $warehouse = Warehouse::create([
        'name' => 'Accra Main',
        'code' => 'ACC',
        'address' => 'Accra',
        'is_active' => true,
    ]);
    $admin = receiptRouteStateAdmin($hq);
    $assignment = receiptRouteStateAssignment($warehouse);

    $this->actingAs($admin, 'admin')
        ->get(route('warehouse.pickups.received.show', $assignment))
        ->assertOk()
        ->assertSee($assignment->shipment->shipment_number);
});
