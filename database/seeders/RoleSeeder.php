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
                'slug' => 'admin',
                'name' => 'Admin',
                'description' => 'Administrative role with HQ access across system configuration, role management, master settings, and global operations.',
                'assignable' => false,
                'permissions' => $allPermissions,
            ],
            [
                'slug' => 'warehouse_supervisor',
                'name' => 'Warehouse Supervisor',
                'description' => 'Manages local hub inventory, approves batch dispatches/offloads, handles discrepancies, and oversees local operational staff.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'dashboard.view',
                    'warehouse.dashboard.view',
                    'warehouse.users.view',
                    'warehouse.users.create',
                    'warehouse.users.edit',
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
                    'warehouse.recipient_payments.reconcile',
                    'drivers.view',
                    'drivers.assign',
                    'reports.view',
                    'reports.export',
                ]),
            ],
            [
                'slug' => 'contact_agent',
                'name' => 'Contact Agent',
                'description' => 'Handles branch office counter intake, customer parcel booking, recipient delivery preferences, and counter pick-ups.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.contacts.manage',
                    'warehouse.items.scan',
                    'warehouse.receiving.manage',
                    'warehouse.recipient_payments.view',
                    'warehouse.recipient_payments.process',
                ]),
            ],
            [
                'slug' => 'external_bus_handoff_agent',
                'name' => 'External Bus Handoff Agent',
                'description' => 'Operates at commercial transit station terminals (e.g. STC/VIP), managing bus cargo loading, manifests, and waybill handoffs.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.manifest.manage',
                    'warehouse.transport.assign',
                    'warehouse.items.scan',
                    'drivers.view',
                    'drivers.assign',
                ]),
            ],
            [
                'slug' => 'external_hub_agent',
                'name' => 'External Hub Agent',
                'description' => 'Manages partner hub operations, regional parcel intake, sorting, and forwarding packages to local delivery teams.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.receiving.manage',
                    'warehouse.sorting.manage',
                    'warehouse.manifest.manage',
                    'warehouse.items.scan',
                ]),
            ],
            [
                'slug' => 'transporter',
                'name' => 'Transporter',
                'description' => 'Intercity long-haul transport driver executing multi-hub batch movements and warehouse offload scanning.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.items.scan',
                    'warehouse.transport.assign',
                    'drivers.view',
                ]),
            ],
            [
                'slug' => 'rider',
                'name' => 'Rider',
                'description' => 'Local last-mile doorstep delivery driver handling customer OTP verification and cash collection.',
                'assignable' => true,
                'permissions' => $this->permissionIds([
                    'warehouse.dashboard.view',
                    'warehouse.items.scan',
                    'warehouse.delivery.assign',
                    'warehouse.delivery.code.reset',
                    'drivers.view',
                ]),
            ],
        ];

        $allowedSlugs = array_column($roles, 'slug');

        // Delete all legacy/obsolete roles not matching our 7 roles
        $obsoleteRoles = Role::query()->whereNotIn('slug', $allowedSlugs)->get();
        foreach ($obsoleteRoles as $oldRole) {
            if (method_exists($oldRole, 'permissions')) {
                $oldRole->permissions()->detach();
            }
            if (method_exists($oldRole, 'users')) {
                $oldRole->users()->detach();
            }
            $oldRole->delete();
        }

        // Sync exact 7 allowed roles
        foreach ($roles as $definition) {
            $role = Role::query()->where('slug', $definition['slug'])->first() ?? new Role();

            $role->fill([
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'description' => $definition['description'],
                'is_system_role' => true,
                'is_warehouse_role' => true,
                'is_assignable_by_warehouse_manager' => $definition['assignable'],
                'is_active' => true,
            ]);
            $role->save();

            $role->permissions()->sync($definition['permissions']);
        }

        $this->command->info('✓ Deleted obsolete roles and seeded exact 7 Parcelman operational roles.');
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