<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records a hub dispatching a parcel for doorstep delivery.
 *
 * A hub agent hands a parcel to a local rider rather than waiting for the
 * recipient to walk in. The agreed fee reuses `shipment_items.delivery_fee`,
 * which already exists; only the rider's own details and the moment of dispatch
 * are new, so they are added here rather than duplicating the fee column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_items', 'dispatch_rider_name')) {
                $table->string('dispatch_rider_name', 120)->nullable()->after('pickup_code');
            }

            if (! Schema::hasColumn('shipment_items', 'dispatch_rider_phone')) {
                $table->string('dispatch_rider_phone', 30)->nullable()->after('dispatch_rider_name');
            }

            if (! Schema::hasColumn('shipment_items', 'dispatched_for_delivery_at')) {
                $table->timestamp('dispatched_for_delivery_at')->nullable()->after('dispatch_rider_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_items', 'dispatched_for_delivery_at')) {
                $table->dropColumn('dispatched_for_delivery_at');
            }

            if (Schema::hasColumn('shipment_items', 'dispatch_rider_phone')) {
                $table->dropColumn('dispatch_rider_phone');
            }

            if (Schema::hasColumn('shipment_items', 'dispatch_rider_name')) {
                $table->dropColumn('dispatch_rider_name');
            }
        });
    }
};
