<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_manifest_receipt_label_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transport_manifest_id');
            $table->unsignedBigInteger('transport_manifest_item_id');
            $table->unsignedBigInteger('warehouse_receipt_item_label_id');
            $table->unsignedBigInteger('scanned_by_user_id')->nullable();
            $table->string('barcode_value');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['transport_manifest_id', 'warehouse_receipt_item_label_id'], 'tm_receipt_label_unique');
            $table->index(['transport_manifest_item_id', 'scanned_at'], 'tm_receipt_label_item_idx');
            $table->index(['scanned_by_user_id', 'scanned_at'], 'tm_receipt_label_user_idx');

            $table->foreign('transport_manifest_id', 'tm_receipt_label_manifest_fk')
                ->references('id')
                ->on('transport_manifests')
                ->cascadeOnDelete();
            $table->foreign('transport_manifest_item_id', 'tm_receipt_label_item_fk')
                ->references('id')
                ->on('transport_manifest_items')
                ->cascadeOnDelete();
            $table->foreign('warehouse_receipt_item_label_id', 'tm_receipt_label_label_fk')
                ->references('id')
                ->on('warehouse_receipt_item_labels')
                ->cascadeOnDelete();
            $table->foreign('scanned_by_user_id', 'tm_receipt_label_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_manifest_receipt_label_scans');
    }
};
