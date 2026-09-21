<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for every automatic placement of a package into an outgoing
 * batch (agent confirmed payment, agent dashboard approval, and any future
 * source).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_batch_assignment_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_item_id');
            $table->unsignedBigInteger('outgoing_batch_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('source', 40);
            $table->string('event_type', 30);
            $table->unsignedBigInteger('delivery_region_id')->nullable();
            $table->unsignedBigInteger('delivery_district_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('shipment_item_id')->references('id')->on('shipment_items')->cascadeOnDelete();
            $table->foreign('outgoing_batch_id')->references('id')->on('outgoing_batches')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['outgoing_batch_id', 'event_type']);
            $table->index(['shipment_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_batch_assignment_events');
    }
};
