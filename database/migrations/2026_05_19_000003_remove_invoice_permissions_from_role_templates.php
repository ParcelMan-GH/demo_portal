<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('module', 'invoices')
            ->orWhere('name', 'like', 'warehouse.invoices.%')
            ->pluck('id')
            ->all();

        if ($permissionIds === []) {
            return;
        }

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        // Invoice permissions were intentionally removed from the active
        // warehouse-first back-office role model.
    }
};
