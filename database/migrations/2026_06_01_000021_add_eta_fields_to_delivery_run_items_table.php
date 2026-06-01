<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_run_items', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_run_items', 'expected_delivery_at')) {
                $table->timestamp('expected_delivery_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('delivery_run_items', 'expected_delivery_set_at')) {
                $table->timestamp('expected_delivery_set_at')->nullable()->after('expected_delivery_at');
            }
            if (!Schema::hasColumn('delivery_run_items', 'expected_delivery_set_by_driver_id')) {
                $table->foreignId('expected_delivery_set_by_driver_id')->nullable()->after('expected_delivery_set_at')->constrained('drivers')->nullOnDelete();
            }
            if (!Schema::hasColumn('delivery_run_items', 'expected_delivery_set_by_user_id')) {
                $table->foreignId('expected_delivery_set_by_user_id')->nullable()->after('expected_delivery_set_by_driver_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_run_items', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_run_items', 'expected_delivery_set_by_user_id')) {
                $table->dropConstrainedForeignId('expected_delivery_set_by_user_id');
            }
            if (Schema::hasColumn('delivery_run_items', 'expected_delivery_set_by_driver_id')) {
                $table->dropConstrainedForeignId('expected_delivery_set_by_driver_id');
            }
            if (Schema::hasColumn('delivery_run_items', 'expected_delivery_set_at')) {
                $table->dropColumn('expected_delivery_set_at');
            }
            if (Schema::hasColumn('delivery_run_items', 'expected_delivery_at')) {
                $table->dropColumn('expected_delivery_at');
            }
        });
    }
};
