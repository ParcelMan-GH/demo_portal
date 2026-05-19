<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('roles')->where('slug', 'super_admin')->first();
        $administrator = DB::table('roles')->where('slug', 'administrator')->first();

        if ($legacy && !$administrator) {
            DB::table('roles')
                ->where('id', $legacy->id)
                ->update([
                    'slug' => 'administrator',
                    'name' => 'Administrator',
                    'description' => 'Administrative role. HQ warehouses can administer the system; other warehouses remain scoped to their own warehouse and granted capabilities.',
                    'is_warehouse_role' => true,
                    'is_assignable_by_warehouse_manager' => false,
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($legacy && $administrator) {
            DB::table('user_roles')
                ->where('role_id', $legacy->id)
                ->update(['role_id' => $administrator->id]);

            $legacyPermissionIds = DB::table('role_permissions')
                ->where('role_id', $legacy->id)
                ->pluck('permission_id')
                ->all();

            foreach ($legacyPermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $administrator->id,
                    'permission_id' => $permissionId,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('roles')->where('id', $legacy->id)->delete();
        }
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('slug', 'administrator')
            ->update([
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'updated_at' => now(),
            ]);
    }
};
