<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyAccountant = DB::table('roles')->where('slug', 'accountant')->first();
        $accountsOfficer = DB::table('roles')->where('slug', 'accounts_officer')->first();

        if ($legacyAccountant && $accountsOfficer) {
            DB::table('user_roles')
                ->where('role_id', $legacyAccountant->id)
                ->update(['role_id' => $accountsOfficer->id]);

            $permissionIds = DB::table('role_permissions')
                ->where('role_id', $legacyAccountant->id)
                ->pluck('permission_id')
                ->all();

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $accountsOfficer->id,
                    'permission_id' => $permissionId,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('roles')->where('id', $legacyAccountant->id)->delete();
            return;
        }

        if ($legacyAccountant && !$accountsOfficer) {
            DB::table('roles')
                ->where('id', $legacyAccountant->id)
                ->update([
                    'slug' => 'accounts_officer',
                    'name' => 'Accounts Officer',
                    'description' => 'Manages invoices, shipment charges, recipient payments, vendor payouts, and finance reports where the warehouse is allowed.',
                    'is_warehouse_role' => true,
                    'is_assignable_by_warehouse_manager' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('slug', 'accounts_officer')
            ->update([
                'slug' => 'accountant',
                'name' => 'Accountant',
                'is_warehouse_role' => false,
                'is_assignable_by_warehouse_manager' => false,
                'updated_at' => now(),
            ]);
    }
};
