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
            $table->foreignId('transport_manifest_id')->constrained('transport_manifests')->cascadeOnDelete();
            $table->foreignId('transport_manifest_item_id')->constrained('transport_manifest_items')->cascadeOnDelete();
            $table->foreignId('warehouse_receipt_item_label_id')->constrained('warehouse_receipt_item_labels')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('barcode_value');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['transport_manifest_id', 'warehouse_receipt_item_label_id'], 'tm_label_scan_unique');
            $table->index(['transport_manifest_item_id', 'scanned_at'], 'tm_label_scan_item_idx');
            $table->index(['driver_id', 'scanned_at'], 'tm_label_scan_driver_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_manifest_label_scans');
    }
};
