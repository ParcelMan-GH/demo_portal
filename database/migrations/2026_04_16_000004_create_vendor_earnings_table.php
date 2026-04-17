<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_earnings')) {
            Schema::create('vendor_earnings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('shipment_id');
                $table->unsignedBigInteger('shipment_item_id');
                $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('status', 20)->default('approved');
                $table->unsignedBigInteger('payout_id')->nullable();
                $table->timestamps();

                $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
                $table->foreign('shipment_item_id')->references('id')->on('shipment_items')->cascadeOnDelete();
                $table->foreign('delivery_run_stop_id')->references('id')->on('delivery_run_stops')->nullOnDelete();
                $table->foreign('payout_id')->references('id')->on('vendor_payouts')->nullOnDelete();

                $table->index(['vendor_id', 'status']);
                $table->index('shipment_id');
                $table->unique('shipment_item_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_earnings');
    }
};
