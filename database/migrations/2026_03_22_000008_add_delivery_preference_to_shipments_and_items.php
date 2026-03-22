<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Shipments
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'delivery_preference')) {
                $table->string('delivery_preference', 20)->nullable()->after('fulfillment_type');
            }
        });

        // Backfill: map existing fulfillment_type to delivery_preference
        DB::table('shipments')->whereNull('delivery_preference')->update([
            'delivery_preference' => DB::raw("CASE WHEN fulfillment_type = 'self_pickup' THEN 'self_pickup' ELSE 'deliver' END"),
        ]);

        // Shipment Items
        Schema::table('shipment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_items', 'delivery_preference')) {
                $table->string('delivery_preference', 20)->nullable()->after('fulfillment_type');
            }
        });

        // Backfill items
        DB::table('shipment_items')->whereNotNull('fulfillment_type')->whereNull('delivery_preference')->update([
            'delivery_preference' => DB::raw("CASE WHEN fulfillment_type = 'self_pickup' THEN 'self_pickup' ELSE 'deliver' END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('delivery_preference');
        });
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn('delivery_preference');
        });
    }
};
