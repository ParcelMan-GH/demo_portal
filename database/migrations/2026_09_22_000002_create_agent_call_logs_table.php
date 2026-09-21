<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the outcome of a call an agent made about a parcel, as sent by the
 * agent mobile app (POST /api/v1/agent/calls/log).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_item_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('outcome', 30);
            $table->text('notes')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('rescheduled_for')->nullable();
            $table->timestamps();

            $table->foreign('shipment_item_id')->references('id')->on('shipment_items')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['shipment_item_id', 'outcome']);
            $table->index(['agent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_call_logs');
    }
};
