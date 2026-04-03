<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_receipt_item_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_receipt_item_id')->constrained('warehouse_receipt_items')->cascadeOnDelete();
            $table->string('barcode_value')->unique();
            $table->unsignedInteger('label_index')->nullable();
            $table->unsignedInteger('labels_total')->default(1);
            $table->string('label_type', 20)->default('sealed'); // 'sealed' or 'unit'
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamps();

            $table->index('warehouse_receipt_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipt_item_labels');
    }
};
