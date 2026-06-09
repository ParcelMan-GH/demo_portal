<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard (1)
            ['module' => 'dashboard', 'action' => 'view', 'name' => 'dashboard.view', 'description' => 'View dashboard', 'sort_order' => 10],

            // Users (5)
            ['module' => 'users', 'action' => 'view', 'name' => 'users.view', 'description' => 'View users list', 'sort_order' => 20],
            ['module' => 'users', 'action' => 'create', 'name' => 'users.create', 'description' => 'Create new users', 'sort_order' => 21],
            ['module' => 'users', 'action' => 'edit', 'name' => 'users.edit', 'description' => 'Edit existing users', 'sort_order' => 22],
            ['module' => 'users', 'action' => 'delete', 'name' => 'users.delete', 'description' => 'Delete users', 'sort_order' => 23],
            ['module' => 'users', 'action' => 'activate', 'name' => 'users.activate', 'description' => 'Activate/deactivate users', 'sort_order' => 24],

            // Roles (5)
            ['module' => 'roles', 'action' => 'view', 'name' => 'roles.view', 'description' => 'View roles list', 'sort_order' => 30],
            ['module' => 'roles', 'action' => 'create', 'name' => 'roles.create', 'description' => 'Create new roles', 'sort_order' => 31],
            ['module' => 'roles', 'action' => 'edit', 'name' => 'roles.edit', 'description' => 'Edit existing roles', 'sort_order' => 32],
            ['module' => 'roles', 'action' => 'delete', 'name' => 'roles.delete', 'description' => 'Delete roles', 'sort_order' => 33],
            ['module' => 'roles', 'action' => 'assign', 'name' => 'roles.assign', 'description' => 'Assign roles to users', 'sort_order' => 34],

            // Orders (6)
            ['module' => 'shipments', 'action' => 'view', 'name' => 'shipments.view', 'description' => 'View orders', 'sort_order' => 40],
            ['module' => 'shipments', 'action' => 'create', 'name' => 'shipments.create', 'description' => 'Create orders', 'sort_order' => 41],
            ['module' => 'shipments', 'action' => 'edit', 'name' => 'shipments.edit', 'description' => 'Edit orders', 'sort_order' => 42],
            ['module' => 'shipments', 'action' => 'delete', 'name' => 'shipments.delete', 'description' => 'Delete orders', 'sort_order' => 43],
            ['module' => 'shipments', 'action' => 'assign_driver', 'name' => 'shipments.assign_driver', 'description' => 'Assign riders to orders', 'sort_order' => 44],
            ['module' => 'shipments', 'action' => 'update_status', 'name' => 'shipments.update_status', 'description' => 'Update order status', 'sort_order' => 45],

            // Vendors (5)
            ['module' => 'vendors', 'action' => 'view', 'name' => 'vendors.view', 'description' => 'View vendors list', 'sort_order' => 50],
            ['module' => 'vendors', 'action' => 'create', 'name' => 'vendors.create', 'description' => 'Create new vendors', 'sort_order' => 51],
            ['module' => 'vendors', 'action' => 'edit', 'name' => 'vendors.edit', 'description' => 'Edit existing vendors', 'sort_order' => 52],
            ['module' => 'vendors', 'action' => 'delete', 'name' => 'vendors.delete', 'description' => 'Delete vendors', 'sort_order' => 53],
            ['module' => 'vendors', 'action' => 'activate', 'name' => 'vendors.activate', 'description' => 'Activate/deactivate vendors', 'sort_order' => 54],
            ['module' => 'vendors', 'action' => 'manage', 'name' => 'vendors.manage', 'description' => 'Manage vendor payouts and finance actions', 'sort_order' => 55],

            // Drivers (5)
            ['module' => 'drivers', 'action' => 'view', 'name' => 'drivers.view', 'description' => 'View riders list', 'sort_order' => 60],
            ['module' => 'drivers', 'action' => 'create', 'name' => 'drivers.create', 'description' => 'Create new riders', 'sort_order' => 61],
            ['module' => 'drivers', 'action' => 'edit', 'name' => 'drivers.edit', 'description' => 'Edit existing riders', 'sort_order' => 62],
            ['module' => 'drivers', 'action' => 'delete', 'name' => 'drivers.delete', 'description' => 'Delete riders', 'sort_order' => 63],
            ['module' => 'drivers', 'action' => 'assign', 'name' => 'drivers.assign', 'description' => 'Assign riders to tasks', 'sort_order' => 64],

            // Charges ledger (admin-side: pickup/delivery/station/handling fees per shipment)
            ['module' => 'charges', 'action' => 'view', 'name' => 'charges.view', 'description' => 'View shipment charges ledger', 'sort_order' => 75],
            ['module' => 'charges', 'action' => 'manage', 'name' => 'charges.manage', 'description' => 'Add / edit / mark paid / waive shipment charges', 'sort_order' => 76],

            // Warehouses (4)
            ['module' => 'warehouses', 'action' => 'view', 'name' => 'warehouses.view', 'description' => 'View warehouses', 'sort_order' => 110],
            ['module' => 'warehouses', 'action' => 'create', 'name' => 'warehouses.create', 'description' => 'Create warehouses', 'sort_order' => 111],
            ['module' => 'warehouses', 'action' => 'edit', 'name' => 'warehouses.edit', 'description' => 'Edit warehouses', 'sort_order' => 112],
            ['module' => 'warehouses', 'action' => 'delete', 'name' => 'warehouses.delete', 'description' => 'Delete warehouses', 'sort_order' => 113],

            // Warehouse Operations (13)
            ['module' => 'warehouse', 'action' => 'dashboard_view', 'name' => 'warehouse.dashboard.view', 'description' => 'View warehouse dashboard', 'sort_order' => 120],
            ['module' => 'warehouse', 'action' => 'users_view', 'name' => 'warehouse.users.view', 'description' => 'View warehouse users', 'sort_order' => 121],
            ['module' => 'warehouse', 'action' => 'users_create', 'name' => 'warehouse.users.create', 'description' => 'Create warehouse users', 'sort_order' => 122],
            ['module' => 'warehouse', 'action' => 'users_edit', 'name' => 'warehouse.users.edit', 'description' => 'Edit warehouse users', 'sort_order' => 123],
            ['module' => 'warehouse', 'action' => 'users_deactivate', 'name' => 'warehouse.users.deactivate', 'description' => 'Deactivate warehouse users', 'sort_order' => 124],
            ['module' => 'warehouse', 'action' => 'users_assign_roles', 'name' => 'warehouse.users.assign_roles', 'description' => 'Assign roles to warehouse users', 'sort_order' => 125],
            ['module' => 'warehouse', 'action' => 'users_impersonate', 'name' => 'warehouse.users.impersonate', 'description' => 'Login as warehouse users for support and testing', 'sort_order' => 1251],
            ['module' => 'warehouse', 'action' => 'receiving_manage', 'name' => 'warehouse.receiving.manage', 'description' => 'Manage warehouse receiving', 'sort_order' => 126],
            ['module' => 'warehouse', 'action' => 'receiving_approve_discrepancy', 'name' => 'warehouse.receiving.approve_discrepancy', 'description' => 'Approve warehouse receiving discrepancies', 'sort_order' => 1261],
            ['module' => 'warehouse', 'action' => 'sorting_manage', 'name' => 'warehouse.sorting.manage', 'description' => 'Manage warehouse sorting', 'sort_order' => 127],
            ['module' => 'warehouse', 'action' => 'sorting_reopen', 'name' => 'warehouse.sorting.reopen', 'description' => 'Reopen sealed sort batches', 'sort_order' => 1271],
            ['module' => 'warehouse', 'action' => 'manifest_manage', 'name' => 'warehouse.manifest.manage', 'description' => 'Manage transport manifests', 'sort_order' => 128],
            ['module' => 'warehouse', 'action' => 'transport_assign', 'name' => 'warehouse.transport.assign', 'description' => 'Assign transport riders', 'sort_order' => 129],
            ['module' => 'warehouse', 'action' => 'delivery_assign', 'name' => 'warehouse.delivery.assign', 'description' => 'Assign delivery riders', 'sort_order' => 130],
            ['module' => 'warehouse', 'action' => 'delivery_code_reset', 'name' => 'warehouse.delivery.code.reset', 'description' => 'Regenerate delivery verification codes', 'sort_order' => 1301],
            ['module' => 'warehouse', 'action' => 'items_scan', 'name' => 'warehouse.items.scan', 'description' => 'Scan warehouse items in/out', 'sort_order' => 131],
            ['module' => 'warehouse', 'action' => 'contacts_manage', 'name' => 'warehouse.contacts.manage', 'description' => 'Manage package contact queue and log calls', 'sort_order' => 136],
            ['module' => 'warehouse', 'action' => 'charges_view', 'name' => 'warehouse.charges.view', 'description' => 'View shipment charges at this warehouse', 'sort_order' => 137],
            ['module' => 'warehouse', 'action' => 'charges_manage', 'name' => 'warehouse.charges.manage', 'description' => 'Add / edit / mark paid / waive charges on shipments at this warehouse', 'sort_order' => 138],
            ['module' => 'warehouse', 'action' => 'recipient_payments_view', 'name' => 'warehouse.recipient_payments.view', 'description' => 'View recipient payment queues in the warehouse portal', 'sort_order' => 1390],
            ['module' => 'warehouse', 'action' => 'recipient_payments_process', 'name' => 'warehouse.recipient_payments.process', 'description' => 'Process assigned recipient payments in the warehouse portal', 'sort_order' => 1391],
            ['module' => 'warehouse', 'action' => 'recipient_payments_assign', 'name' => 'warehouse.recipient_payments.assign', 'description' => 'Assign recipient payment tasks in the warehouse portal', 'sort_order' => 1392],
            ['module' => 'warehouse', 'action' => 'recipient_payments_reconcile', 'name' => 'warehouse.recipient_payments.reconcile', 'description' => 'Review warehouse recipient payment sessions and reconciliation', 'sort_order' => 1393],
            ['module' => 'warehouse', 'action' => 'recipient_payments_override', 'name' => 'warehouse.recipient_payments.override', 'description' => 'Override recipient payment dispatch blocks in the warehouse portal', 'sort_order' => 1394],
            ['module' => 'warehouse', 'action' => 'recipient_payments_manage_wallets', 'name' => 'warehouse.recipient_payments.manage_wallets', 'description' => 'Manage approved recipient payment wallets in the warehouse portal', 'sort_order' => 1395],

            // Recipient Payments
            ['module' => 'recipient_payments', 'action' => 'view', 'name' => 'recipient_payments.view', 'description' => 'View recipient payment queues', 'sort_order' => 140],
            ['module' => 'recipient_payments', 'action' => 'process', 'name' => 'recipient_payments.process', 'description' => 'Process assigned recipient payments', 'sort_order' => 141],
            ['module' => 'recipient_payments', 'action' => 'assign', 'name' => 'recipient_payments.assign', 'description' => 'Assign recipient payment tasks', 'sort_order' => 142],
            ['module' => 'recipient_payments', 'action' => 'reconcile', 'name' => 'recipient_payments.reconcile', 'description' => 'Review recipient payment sessions and reconciliation', 'sort_order' => 143],
            ['module' => 'recipient_payments', 'action' => 'override', 'name' => 'recipient_payments.override', 'description' => 'Override recipient payment dispatch blocks', 'sort_order' => 144],
            ['module' => 'recipient_payments', 'action' => 'manage_wallets', 'name' => 'recipient_payments.manage_wallets', 'description' => 'Manage approved recipient payment wallets', 'sort_order' => 145],

            // Reports (2)
            ['module' => 'reports', 'action' => 'view', 'name' => 'reports.view', 'description' => 'View reports', 'sort_order' => 80],
            ['module' => 'reports', 'action' => 'export', 'name' => 'reports.export', 'description' => 'Export reports', 'sort_order' => 81],

            // Settings (2)
            ['module' => 'settings', 'action' => 'view', 'name' => 'settings.view', 'description' => 'View settings', 'sort_order' => 90],
            ['module' => 'settings', 'action' => 'edit', 'name' => 'settings.edit', 'description' => 'Edit settings', 'sort_order' => 91],

            // Platform Settings (5)
            ['module' => 'platform_settings', 'action' => 'view', 'name' => 'platform_settings.view', 'description' => 'View platform settings', 'sort_order' => 100],
            ['module' => 'platform_settings', 'action' => 'create', 'name' => 'platform_settings.create', 'description' => 'Create platform settings', 'sort_order' => 101],
            ['module' => 'platform_settings', 'action' => 'edit', 'name' => 'platform_settings.edit', 'description' => 'Edit platform settings', 'sort_order' => 102],
            ['module' => 'platform_settings', 'action' => 'delete', 'name' => 'platform_settings.delete', 'description' => 'Delete platform settings', 'sort_order' => 103],
            ['module' => 'platform_settings', 'action' => 'manage', 'name' => 'platform_settings.manage', 'description' => 'Manage all platform settings', 'sort_order' => 104],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✓ Successfully seeded ' . count($permissions) . ' permissions.');
    }
}
