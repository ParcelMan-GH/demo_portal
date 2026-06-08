<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sort_batches', function (Blueprint $table) {
            if (Schema::hasColumn('sort_batches', 'destination_warehouse_id')) {
                $table->dropForeign(['destination_warehouse_id']);
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE sort_batches MODIFY destination_warehouse_id BIGINT UNSIGNED NULL');
        }

        Schema::table('sort_batches', function (Blueprint $table) {
            $table->foreign('destination_warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->nullOnDelete();

            $table->string('dispatch_mode')
                ->default('transfer')
                ->after('destination_warehouse_id');

            $table->index(['origin_warehouse_id', 'dispatch_mode', 'status'], 'sort_batches_origin_dispatch_status_idx');
        });
    }

    public function down(): void
    {
        DB::table('sort_batches')
            ->whereNull('destination_warehouse_id')
            ->update(['destination_warehouse_id' => DB::raw('origin_warehouse_id')]);

        Schema::table('sort_batches', function (Blueprint $table) {
            $table->dropIndex('sort_batches_origin_dispatch_status_idx');
            $table->dropColumn('dispatch_mode');
            $table->dropForeign(['destination_warehouse_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE sort_batches MODIFY destination_warehouse_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('sort_batches', function (Blueprint $table) {
            $table->foreign('destination_warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->cascadeOnDelete();
        });
    }
};
