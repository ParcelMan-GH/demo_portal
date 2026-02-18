<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_manifest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_manifest_id')->constrained('transport_manifests')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->unsignedInteger('expected_quantity')->default(0);
            $table->unsignedInteger('loaded_quantity')->default(0);
            $table->unsignedInteger('received_quantity')->default(0);
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedInteger('scan_out_count')->default(0);
            $table->unsignedInteger('scan_in_count')->default(0);
            $table->string('line_status')->default('pending'); // pending|loaded|received|short|excess|damaged
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['transport_manifest_id', 'shipment_item_id'], 'transport_manifest_item_unique');
            $table->index(['shipment_item_id', 'line_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_manifest_items');
    }
};

