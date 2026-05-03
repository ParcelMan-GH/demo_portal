<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_manifest_label_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transport_manifest_id');
            $table->unsignedBigInteger('transport_manifest_item_id');
            $table->unsignedBigInteger('warehouse_receipt_item_label_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('barcode_value');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['transport_manifest_id', 'warehouse_receipt_item_label_id'], 'tm_label_scan_unique');
            $table->index(['transport_manifest_item_id', 'scanned_at'], 'tm_label_scan_item_idx');
            $table->index(['driver_id', 'scanned_at'], 'tm_label_scan_driver_idx');

            $table->foreign('transport_manifest_id', 'tm_label_scan_manifest_fk')
                ->references('id')
                ->on('transport_manifests')
                ->cascadeOnDelete();
            $table->foreign('transport_manifest_item_id', 'tm_label_scan_item_fk')
                ->references('id')
                ->on('transport_manifest_items')
                ->cascadeOnDelete();
            $table->foreign('warehouse_receipt_item_label_id', 'tm_label_scan_label_fk')
                ->references('id')
                ->on('warehouse_receipt_item_labels')
                ->cascadeOnDelete();
            $table->foreign('driver_id', 'tm_label_scan_driver_fk')
                ->references('id')
                ->on('drivers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_manifest_label_scans');
    }
};
