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
                'is_active' => true,
            ]
        );

        // Assign specific permissions to Operations Manager
        $operationsPermissions = Permission::whereIn('name', [
            // Dashboard
            'dashboard.view',

            // Shipments (full access)
            'shipments.view',
            'shipments.create',
            'shipments.edit',
            'shipments.delete',
            'shipments.assign_driver',
            'shipments.update_status',

            // Drivers (full access)
            'drivers.view',
            'drivers.create',
            'drivers.edit',
            'drivers.delete',
            'drivers.assign',

            // Vendors (full access)
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',
            'vendors.activate',

            // Reports (view and export)
            'reports.view',
            'reports.export',
        ])->pluck('id');
        $operationsManager->permissions()->sync($operationsPermissions);

        // 3. Accountant Role - Finance-focused permissions
        $accountant = Role::updateOrCreate(
            ['slug' => 'accountant'],
            [
                'name' => 'Accountant',
                'description' => 'Manage invoices, view reports and settings',
                'is_system_role' => true,
                'is_active' => true,
            ]
        );

        // Assign specific permissions to Accountant
        $accountantPermissions = Permission::whereIn('name', [
            // Dashboard
            'dashboard.view',

            // Invoices (full access)
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',

            // Reports (view only)
            'reports.view',
            'reports.export',

            // Settings (view only)
            'settings.view',

            // Platform Settings (view only)
            'platform_settings.view',
        ])->pluck('id');
        $accountant->permissions()->sync($accountantPermissions);

        $this->command->info('✓ Successfully seeded 3 system roles:');
        $this->command->info('  - Super Administrator (' . $superAdmin->permissions->count() . ' permissions)');
        $this->command->info('  - Operations Manager (' . $operationsManager->permissions->count() . ' permissions)');
        $this->command->info('  - Accountant (' . $accountant->permissions->count() . ' permissions)');
    }
}
