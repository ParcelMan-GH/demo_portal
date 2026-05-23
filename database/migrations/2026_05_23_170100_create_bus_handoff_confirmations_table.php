<?php

use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_handoff_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_run_id')->constrained('delivery_runs')->cascadeOnDelete();
            $table->foreignId('delivery_run_stop_id')->constrained('delivery_run_stops')->cascadeOnDelete();
            $table->foreignId('delivery_run_item_id')->unique()->constrained('delivery_run_items')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->foreignId('handoff_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status', 40)->default('pending');
            $table->string('source', 40)->nullable();
            $table->string('target_type', 20)->nullable();
            $table->string('target_name')->nullable();
            $table->string('target_phone', 40)->nullable();
            $table->string('confirmation_code_hash')->nullable();
            $table->timestamp('confirmation_code_sent_at')->nullable();
            $table->timestamp('confirmation_code_expires_at')->nullable();
            $table->timestamp('confirmation_code_verified_at')->nullable();
            $table->unsignedInteger('confirmation_attempts')->default(0);
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->timestamp('public_link_sent_at')->nullable();
            $table->foreignId('reason_id')->nullable()->constrained('bus_handoff_reasons')->nullOnDelete();
            $table->text('issue_notes')->nullable();
            $table->text('confirmation_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('confirmed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('public_confirmed_at')->nullable();
            $table->timestamp('public_reported_at')->nullable();
            $table->timestamps();

            $table->index(['handoff_driver_id', 'status']);
            $table->index(['delivery_run_stop_id', 'status']);
            $table->index(['shipment_item_id', 'status']);
            $table->index(['target_phone', 'status']);
        });

        $this->backfillExistingConfirmations();
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_handoff_confirmations');
    }

    private function backfillExistingConfirmations(): void
    {
        $items = DeliveryRunItem::query()
            ->with(['run:id,assigned_driver_id', 'stop:id,delivery_method,status,confirmed_by_admin_id,confirmed_at,confirmation_notes'])
            ->whereHas('stop', fn ($query) => $query->where('delivery_method', DeliveryRunStop::METHOD_BUS_HANDOFF))
            ->whereIn('status', [
                DeliveryRunItem::STATUS_HANDED_OFF,
                DeliveryRunItem::STATUS_DELIVERED,
                DeliveryRunItem::STATUS_FAILED,
            ])
            ->get();

        foreach ($items as $item) {
            $stop = $item->stop;
            if (!$stop || !$item->shipment_item_id) {
                continue;
            }

            $status = match ($item->status) {
                DeliveryRunItem::STATUS_DELIVERED => 'admin_confirmed',
                DeliveryRunItem::STATUS_FAILED => 'failed',
                default => 'pending',
            };

            DB::table('bus_handoff_confirmations')->updateOrInsert(
                ['delivery_run_item_id' => $item->id],
                [
                    'delivery_run_id' => $item->delivery_run_id,
                    'delivery_run_stop_id' => $item->delivery_run_stop_id,
                    'shipment_item_id' => $item->shipment_item_id,
                    'handoff_driver_id' => $item->run?->assigned_driver_id,
                    'status' => $status,
                    'source' => $status === 'admin_confirmed' ? 'admin' : null,
                    'confirmed_at' => $item->delivered_at ?? $stop->confirmed_at,
                    'confirmed_by_admin_id' => $stop->confirmed_by_admin_id,
                    'confirmation_notes' => $stop->confirmation_notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
};
