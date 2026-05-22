<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'warehouse.users.impersonate'],
            [
                'module' => 'warehouse',
                'action' => 'users_impersonate',
                'description' => 'Login as warehouse users for support and testing',
                'sort_order' => 1251,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')->where('name', 'warehouse.users.impersonate')->value('id');
        $administratorId = DB::table('roles')->where('slug', 'administrator')->value('id');

        if ($permissionId && $administratorId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $administratorId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('user_roles')
                ->where('role_id', $administratorId)
                ->pluck('user_id')
                ->each(function ($userId) {
                    Cache::forget("user.{$userId}.permissions");
                    Cache::forget("user.{$userId}.permission_names");
                });
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'warehouse.users.impersonate')->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
