<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_receipt_id')->constrained('warehouse_receipts')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->unsignedInteger('expected_quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->string('discrepancy_type')->default('none'); // none|missing|excess|damaged|mixed
            $table->string('condition_status')->default('ok'); // ok|damaged|partial
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('barcode_value')->nullable();
            $table->string('barcode_format')->default('code128');
            $table->timestamp('barcode_printed_at')->nullable();
            $table->unsignedInteger('barcode_print_count')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_receipt_id', 'shipment_item_id'], 'warehouse_receipt_item_unique');
            $table->index(['discrepancy_type', 'condition_status']);
            $table->index('received_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipt_items');
    }
};

