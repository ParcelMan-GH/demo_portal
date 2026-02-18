<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin Role - All Permissions
        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'is_system_role' => true,
                'is_warehouse_role' => false,
                'is_assignable_by_warehouse_manager' => false,
                'is_active' => true,
            ]
        );

        // Assign all permissions to Super Admin
        $allPermissions = Permission::all()->pluck('id');
        $superAdmin->permissions()->sync($allPermissions);

        // 2. Operations Manager Role - Operations-focused permissions
        $operationsManager = Role::updateOrCreate(
            ['slug' => 'operations_manager'],
            [
                'name' => 'Operations Manager',
                'description' => 'Manage shipments, drivers, vendors, and view reports',
                'is_system_role' => true,
                'is_warehouse_role' => false,
                'is_assignable_by_warehouse_manager' => false,
                'is_active' => true,
            ]
        );

        $operationsPermissions = Permission::whereIn('name', [
            // Dashboard
            'dashboard.view',

            // Shipments
            'shipments.view',
            'shipments.create',
            'shipments.edit',
            'shipments.delete',
            'shipments.assign_driver',
            'shipments.update_status',

            // Drivers
            'drivers.view',
            'drivers.create',
            'drivers.edit',
            'drivers.delete',
            'drivers.assign',

            // Vendors
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',
            'vendors.activate',

            // Reports
            'reports.view',
            'reports.export',
        ])->pluck('id');
        $operationsManager->permissions()->sync($operationsPermissions);

        // 3. Accountant Role
        $accountant = Role::updateOrCreate(
            ['slug' => 'accountant'],
            [
                'name' => 'Accountant',
                'description' => 'Manage invoices, view reports and settings',
                'is_system_role' => true,
                'is_warehouse_role' => false,
                'is_assignable_by_warehouse_manager' => false,
                'is_active' => true,
            ]
        );

        $accountantPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'reports.view',
            'reports.export',
            'settings.view',
            'platform_settings.view',
        ])->pluck('id');
        $accountant->permissions()->sync($accountantPermissions);

        // 4. Warehouse Manager
        $warehouseManager = Role::updateOrCreate(
            ['slug' => 'warehouse_manager'],
            [
                'name' => 'Warehouse Manager',
                'description' => 'Manages warehouse operations and warehouse users',
                'is_system_role' => true,
                'is_warehouse_role' => true,
                'is_assignable_by_warehouse_manager' => false,
                'is_active' => true,
            ]
        );

        $warehouseManagerPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'warehouse.dashboard.view',
            'warehouse.users.view',
            'warehouse.users.create',
            'warehouse.users.edit',
            'warehouse.users.deactivate',
            'warehouse.users.assign_roles',
            'warehouse.receiving.manage',
            'warehouse.receiving.approve_discrepancy',
            'warehouse.sorting.manage',
            'warehouse.sorting.reopen',
            'warehouse.manifest.manage',
            'warehouse.transport.assign',
            'warehouse.delivery.assign',
            'warehouse.delivery.code.reset',
            'warehouse.items.scan',
            'drivers.view',
            'drivers.assign',
            'warehouses.view',
        ])->pluck('id');
        $warehouseManager->permissions()->sync($warehouseManagerPermissions);

        // 5. Warehouse Receiver
        $warehouseReceiver = Role::updateOrCreate(
            ['slug' => 'warehouse_receiver'],
            [
                'name' => 'Warehouse Receiver',
                'description' => 'Receives items into warehouse inventory',
                'is_system_role' => true,
                'is_warehouse_role' => true,
                'is_assignable_by_warehouse_manager' => true,
                'is_active' => true,
            ]
        );

        $warehouseReceiverPermissions = Permission::whereIn('name', [
            'warehouse.dashboard.view',
            'warehouse.receiving.manage',
            'warehouse.items.scan',
        ])->pluck('id');
        $warehouseReceiver->permissions()->sync($warehouseReceiverPermissions);

        // 6. Warehouse Sorter
        $warehouseSorter = Role::updateOrCreate(
            ['slug' => 'warehouse_sorter'],
            [
                'name' => 'Warehouse Sorter',
                'description' => 'Sorts items and prepares manifests',
                'is_system_role' => true,
                'is_warehouse_role' => true,
                'is_assignable_by_warehouse_manager' => true,
                'is_active' => true,
            ]
        );

        $warehouseSorterPermissions = Permission::whereIn('name', [
            'warehouse.dashboard.view',
            'warehouse.sorting.manage',
            'warehouse.manifest.manage',
            'warehouse.items.scan',
        ])->pluck('id');
        $warehouseSorter->permissions()->sync($warehouseSorterPermissions);

        // 7. Warehouse Dispatcher
        $warehouseDispatcher = Role::updateOrCreate(
            ['slug' => 'warehouse_dispatcher'],
            [
                'name' => 'Warehouse Dispatcher',
                'description' => 'Assigns transport and delivery drivers',
                'is_system_role' => true,
                'is_warehouse_role' => true,
                'is_assignable_by_warehouse_manager' => true,
                'is_active' => true,
            ]
        );

        $warehouseDispatcherPermissions = Permission::whereIn('name', [
            'warehouse.dashboard.view',
            'warehouse.manifest.manage',
            'warehouse.transport.assign',
            'warehouse.delivery.assign',
            'warehouse.delivery.code.reset',
            'warehouse.items.scan',
            'drivers.view',
            'drivers.assign',
        ])->pluck('id');
        $warehouseDispatcher->permissions()->sync($warehouseDispatcherPermissions);

        $this->command->info('Successfully seeded 7 system roles:');
        $this->command->info('  - Super Administrator (' . $superAdmin->permissions->count() . ' permissions)');
        $this->command->info('  - Operations Manager (' . $operationsManager->permissions->count() . ' permissions)');
        $this->command->info('  - Accountant (' . $accountant->permissions->count() . ' permissions)');
        $this->command->info('  - Warehouse Manager (' . $warehouseManager->permissions->count() . ' permissions)');
        $this->command->info('  - Warehouse Receiver (' . $warehouseReceiver->permissions->count() . ' permissions)');
        $this->command->info('  - Warehouse Sorter (' . $warehouseSorter->permissions->count() . ' permissions)');
        $this->command->info('  - Warehouse Dispatcher (' . $warehouseDispatcher->permissions->count() . ' permissions)');
    }
}
