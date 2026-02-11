<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_item_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->unsignedInteger('expected_quantity');
            $table->unsignedInteger('confirmed_quantity');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['pickup_assignment_id', 'shipment_item_id'], 'pickup_item_confirm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_item_confirmations');
    }
};
