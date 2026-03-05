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
        // 1. Add soft deletes to vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Change vendor_activity_logs FK from nullOnDelete to restrict
        Schema::table('vendor_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('restrict');
        });

        // 3. Change shipments FK from cascade to restrict
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert shipments FK back to cascade
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('cascade');
        });

        // Revert vendor_activity_logs FK back to nullOnDelete
        Schema::table('vendor_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->nullOnDelete();
        });

        // Remove soft deletes from vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
