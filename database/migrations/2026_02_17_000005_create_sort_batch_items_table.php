<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sort_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sort_batch_id')->constrained('sort_batches')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->foreignId('warehouse_receipt_item_id')->constrained('warehouse_receipt_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity_allocated')->default(1);
            $table->unsignedBigInteger('added_by_user_id')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index('shipment_item_id');
            $table->index('warehouse_receipt_item_id');
            $table->index('removed_at');
            $table->index('added_by_user_id');
            $table->unique(['sort_batch_id', 'shipment_item_id'], 'sort_batch_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_batch_items');
    }
};

