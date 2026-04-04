<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_custody_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_receipt_item_label_id')->constrained('warehouse_receipt_item_labels')->cascadeOnDelete();
            $table->string('event_type', 20); // claimed, released, transferred, delivered, returned
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('scanned_by_user_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('location_note', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('warehouse_receipt_item_label_id', 'lce_label_id_index');
            $table->index('driver_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_custody_events');
    }
};
