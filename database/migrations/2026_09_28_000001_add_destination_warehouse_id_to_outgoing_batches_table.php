<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which hub an outgoing batch is going to.
 *
 * A batch previously said where it was going only as a region and district, which
 * cannot answer "is this going to another hub?" — two hubs can share a region
 * (Accra Main and Tema Warehouse are both region 1), and a region may have no hub
 * at all. `sort_batches` has carried a `destination_warehouse_id` all along; this
 * gives outgoing batches the same, so both kinds of batch can be asked the same
 * question the same way.
 *
 * Nullable, and null means "no inter-hub transfer recorded" — the same meaning the
 * column already has on `sort_batches`, which is what lets a caller exclude rather
 * than guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('outgoing_batches', 'destination_warehouse_id')) {
                $table->unsignedBigInteger('destination_warehouse_id')->nullable()->after('delivery_district_id');
                $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_batches', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_batches', 'destination_warehouse_id')) {
                $table->dropForeign(['destination_warehouse_id']);
                $table->dropColumn('destination_warehouse_id');
            }
        });
    }
};
