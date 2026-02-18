<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_run_id')->constrained('delivery_runs')->cascadeOnDelete();
            $table->foreignId('delivery_run_stop_id')->constrained('delivery_run_stops')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->unsignedInteger('expected_quantity')->default(0);
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->string('status')->default('pending'); // pending|delivered|failed|partial
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['delivery_run_id', 'shipment_item_id'], 'delivery_run_item_unique');
            $table->index(['delivery_run_stop_id', 'status']);
            $table->index(['shipment_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_run_items');
    }
};

