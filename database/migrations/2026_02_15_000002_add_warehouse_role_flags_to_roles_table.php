<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_warehouse_role')
                ->default(false)
                ->after('is_system_role');

            $table->boolean('is_assignable_by_warehouse_manager')
                ->default(false)
                ->after('is_warehouse_role');

            $table->index('is_warehouse_role');
            $table->index('is_assignable_by_warehouse_manager');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['is_warehouse_role']);
            $table->dropIndex(['is_assignable_by_warehouse_manager']);
            $table->dropColumn(['is_warehouse_role', 'is_assignable_by_warehouse_manager']);
        });
    }
};
