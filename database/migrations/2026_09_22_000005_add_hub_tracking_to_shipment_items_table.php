<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the fields a hub needs to hold and release parcels.
 *
 * A hub is a `warehouses` row, so `hub_id` records which hub currently holds a
 * package, the timestamps record the hub leg of its journey, `shelf_location` is
 * where it was racked, and `pickup_code` is the code quoted to hand it over.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_items', 'hub_id')) {
                $table->unsignedBigInteger('hub_id')->nullable()->after('outgoing_batch_id');
                $table->foreign('hub_id')->references('id')->on('warehouses')->nullOnDelete();
            }

            if (! Schema::hasColumn('shipment_items', 'arrived_at_hub_at')) {
                $table->timestamp('arrived_at_hub_at')->nullable()->after('hub_id');
            }

            if (! Schema::hasColumn('shipment_items', 'dispatched_to_bus_at')) {
                $table->timestamp('dispatched_to_bus_at')->nullable()->after('arrived_at_hub_at');
            }

            if (! Schema::hasColumn('shipment_items', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('dispatched_to_bus_at');
            }

            if (! Schema::hasColumn('shipment_items', 'shelf_location')) {
                $table->string('shelf_location', 60)->nullable()->after('released_at');
            }

            if (! Schema::hasColumn('shipment_items', 'pickup_code')) {
                $table->string('pickup_code', 12)->nullable()->after('shelf_location');
                $table->index('pickup_code');
            }

            $table->index(['hub_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_items', 'pickup_code')) {
                $table->dropIndex(['pickup_code']);
                $table->dropColumn('pickup_code');
            }

            if (Schema::hasColumn('shipment_items', 'shelf_location')) {
                $table->dropColumn('shelf_location');
            }

            if (Schema::hasColumn('shipment_items', 'released_at')) {
                $table->dropColumn('released_at');
            }

            if (Schema::hasColumn('shipment_items', 'dispatched_to_bus_at')) {
                $table->dropColumn('dispatched_to_bus_at');
            }

            if (Schema::hasColumn('shipment_items', 'arrived_at_hub_at')) {
                $table->dropColumn('arrived_at_hub_at');
            }

            if (Schema::hasColumn('shipment_items', 'hub_id')) {
                $table->dropForeign(['hub_id']);
                $table->dropIndex(['hub_id', 'status']);
                $table->dropColumn('hub_id');
            }
        });
    }
};
