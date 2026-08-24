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
        Schema::create('outgoing_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique(); 
            
            // Tracking both for the Hub-and-Spoke model
            $table->unsignedBigInteger('delivery_region_id'); 
            $table->unsignedBigInteger('delivery_district_id'); 
            
            $table->string('status')->default('open'); // open, in_transit, at_warehouse, dispatched
            $table->unsignedBigInteger('transport_driver_id')->nullable(); // The van driver moving it to the region
            
            $table->timestamps();
        });

        // Add the batch ID to your existing shipment_items table
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->unsignedBigInteger('outgoing_batch_id')->nullable()->after('delivery_district_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn('outgoing_batch_id');
        });
        
        Schema::dropIfExists('outgoing_batches');
    }
};