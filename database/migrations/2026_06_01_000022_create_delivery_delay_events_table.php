<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_delay_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_run_item_id')->constrained('delivery_run_items')->cascadeOnDelete();
            $table->foreignId('delivery_run_stop_id')->nullable()->constrained('delivery_run_stops')->nullOnDelete();
            $table->foreignId('delivery_run_id')->nullable()->constrained('delivery_runs')->nullOnDelete();
            $table->foreignId('shipment_item_id')->nullable()->constrained('shipment_items')->nullOnDelete();
            $table->foreignId('delivery_delay_reason_id')->nullable()->constrained('delivery_delay_reasons')->nullOnDelete();
            $table->string('reason_label', 120)->nullable();
            $table->timestamp('old_expected_delivery_at')->nullable();
            $table->timestamp('new_expected_delivery_at')->nullable();
            $table->string('source', 40);
            $table->foreignId('actor_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('recipient_sms_sent')->default(false);
            $table->boolean('vendor_notification_sent')->default(false);
            $table->boolean('vendor_sms_sent')->default(false);
            $table->text('message_preview')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['delivery_run_item_id', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_delay_events');
    }
};
