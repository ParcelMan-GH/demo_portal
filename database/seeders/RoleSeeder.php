<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::query()->pluck('id')->all();

        $roles = [
            [
                'slug' => 'administrator',
                'name' => 'Administrator',
                'description' => 'Administrative role. HQ warehouses can administer the system; other warehouses remain scoped to their own warehouse and granted capabilities.',
                'assignable' => false,
                'permissions' => $allPermissions,
            ],
            [
                'slug' => 'operations_manager',
                'name' => 'Operations Manager',
                'description' => 'Manages receiving, sorting, manifests, deliveries, and operational staff.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
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
                    'warehouse.contacts.manage',
                    'drivers.view',
                    'drivers.assign',
                    'reports.view',
                    'reports.export',
                ]),
            ],
            [
                'slug' => 'warehouse-manager',
                'name' => 'Warehouse Manager',
                'description' => 'Manages local warehouse operations and users. In an HQ warehouse, access can extend system-wide through warehouse HQ privileges.',
                'assignable' => false,
                'permissions' => $this->permissionIds([
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
                    'warehouse.contacts.manage',
                    'warehouse.charges.view',
                    'warehouse.charges.manage',
                    'warehouse.recipient_payments.view',
                    'warehouse.recipient_payments.process',
                    'warehouse.recipient_payments.assign',
                    'warehouse.recipient_payments.reconcile',
                    'warehouse.recipient_payments.override',
                    'warehouse.recipient_payments.manage_wallets',
                    'drivers.view',
                    'drivers.assign',
                    'reports.view',
                    'reports.export',
                ]),
            ],
            [
                'slug' => 'accounts_officer',
                'name' => 'Accounts Officer',
                'description' => 'Manages shipment charges, recipient payments, vendor payouts, and finance reports where the warehouse is allowed.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'dashboard.view',
                    'warehouse.dashboard.view',
                    'warehouse.charges.view',
                    'warehouse.charges.manage',
                    'warehouse.recipient_payments.view',
                    'warehouse.recipient_payments.process',
                    'warehouse.recipient_payments.assign',
                    'warehouse.recipient_payments.reconcile',
                    'warehouse.recipient_payments.override',
                    'warehouse.recipient_payments.manage_wallets',
                    'charges.view',
                    'charges.manage',
                    'vendors.view',
                    'vendors.manage',
                    'reports.view',
                    'reports.export',
                ]),
            ],
            [
                'slug' => 'warehouse_receiver',
                'name' => 'Warehouse Receiver',
                'description' => 'Receives packages into warehouse inventory.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.receiving.manage',
                    'warehouse.items.scan',
                    'warehouse.charges.view',
                    'warehouse.charges.manage',
                ]),
            ],
            [
                'slug' => 'warehouse_sorter',
                'name' => 'Warehouse Sorter',
                'description' => 'Sorts packages and prepares manifests.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.sorting.manage',
                    'warehouse.manifest.manage',
                    'warehouse.items.scan',
                ]),
            ],
            [
                'slug' => 'warehouse_dispatcher',
                'name' => 'Warehouse Dispatcher',
                'description' => 'Assigns transport and delivery drivers.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.manifest.manage',
                    'warehouse.transport.assign',
                    'warehouse.delivery.assign',
                    'warehouse.delivery.code.reset',
                    'warehouse.items.scan',
                    'drivers.view',
                    'drivers.assign',
                ]),
            ],
            [
                'slug' => 'warehouse_contact_agent',
                'name' => 'Contact Agent',
                'description' => 'Calls recipients to confirm delivery preference.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.contacts.manage',
                ]),
            ],
            [
                'slug' => 'recipient-payment-agent',
                'name' => 'Recipient Payment Agent',
                'description' => 'Records assigned recipient delivery-fee payments.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.recipient_payments.view',
                    'warehouse.recipient_payments.process',
                ]),
            ],
            [
                'slug' => 'recipient_payment_supervisor',
                'name' => 'Recipient Payment Supervisor',
                'description' => 'Assigns recipient payment work and reviews wallet reconciliation.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.recipient_payments.view',
                    'warehouse.recipient_payments.process',
                    'warehouse.recipient_payments.assign',
                    'warehouse.recipient_payments.reconcile',
                    'warehouse.recipient_payments.override',
                    'warehouse.recipient_payments.manage_wallets',
                ]),
            ],
        ];

        foreach ($roles as $definition) {
            $role = Role::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system_role' => true,
                    'is_warehouse_role' => true,
                    'is_assignable_by_warehouse_manager' => $definition['assignable'],
                    'is_active' => true,
                ]
            );

            $role->permissions()->sync($definition['permissions']);
        }

        $this->command->info('Successfully seeded warehouse-first roles.');
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int>
     */
    private function permissionIds(array $names): array
    {
        return Permission::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->all();
    }
}
