<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bus_stations')) {
            Schema::create('bus_stations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('shipment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_items', 'bus_station_id')) {
                $table->unsignedBigInteger('bus_station_id')->nullable()->after('delivery_instructions');
                $table->foreign('bus_station_id')->references('id')->on('bus_stations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropForeign(['bus_station_id']);
            $table->dropColumn('bus_station_id');
        });
        Schema::dropIfExists('bus_stations');
    }
};
