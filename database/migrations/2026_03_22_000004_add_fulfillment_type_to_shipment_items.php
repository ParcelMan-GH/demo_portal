<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_items', 'fulfillment_type')) {
                $table->string('fulfillment_type', 20)->nullable()->after('delivery_instructions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn('fulfillment_type');
        });
    }
};
