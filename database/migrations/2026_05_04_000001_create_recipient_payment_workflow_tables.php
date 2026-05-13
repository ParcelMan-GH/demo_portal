<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('name');
            $table->string('provider', 40);
            $table->string('phone_number', 40);
            $table->string('account_owner')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'is_active']);
            $table->unique(['provider', 'phone_number']);
        });

        Schema::create('payment_wallet_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_wallet_id')->constrained('payment_wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['payment_wallet_id', 'user_id']);
        });

        Schema::create('recipient_payment_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('shipment_charge_id')->nullable()->constrained('shipment_charges')->nullOnDelete();
            $table->foreignId('sort_batch_id')->nullable()->constrained('sort_batches')->nullOnDelete();
            $table->foreignId('sort_batch_item_id')->nullable()->constrained('sort_batch_items')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('payment_group', 32);
            $table->string('status', 24)->default('pending');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 40)->nullable();
            $table->string('delivery_town')->nullable();
            $table->decimal('negotiated_amount', 10, 2)->nullable();
            $table->string('currency', 10)->default('GHS');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payment_wallet_id')->nullable()->constrained('payment_wallets')->nullOnDelete();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('override_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('override_at')->nullable();
            $table->text('override_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['shipment_item_id', 'sort_batch_id']);
            $table->index(['warehouse_id', 'payment_group', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index(['sort_batch_id', 'status']);
        });

        Schema::create('recipient_payment_call_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_payment_task_id');
            $table->unsignedBigInteger('attempted_by_user_id');
            $table->string('outcome', 32);
            $table->text('notes')->nullable();
            $table->timestamp('attempted_at');

            $table->foreign('recipient_payment_task_id', 'rp_call_task_fk')->references('id')->on('recipient_payment_tasks')->cascadeOnDelete();
            $table->foreign('attempted_by_user_id', 'rp_call_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['recipient_payment_task_id', 'attempted_at'], 'rp_call_task_attempted_idx');
        });

        Schema::create('recipient_payment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('payment_wallet_id')->constrained('payment_wallets')->cascadeOnDelete();
            $table->decimal('opening_balance', 12, 2);
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->decimal('expected_closing_balance', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->string('status', 24)->default('open');
            $table->timestamp('started_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['payment_wallet_id', 'status']);
        });

        Schema::create('recipient_payment_session_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_payment_session_id');
            $table->unsignedBigInteger('recipient_payment_task_id')->nullable();
            $table->unsignedBigInteger('shipment_charge_id')->nullable();
            $table->string('entry_type', 24);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('GHS');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->timestamps();

            $table->foreign('recipient_payment_session_id', 'rp_entry_session_fk')->references('id')->on('recipient_payment_sessions')->cascadeOnDelete();
            $table->foreign('recipient_payment_task_id', 'rp_entry_task_fk')->references('id')->on('recipient_payment_tasks')->nullOnDelete();
            $table->foreign('shipment_charge_id', 'rp_entry_charge_fk')->references('id')->on('shipment_charges')->nullOnDelete();
            $table->foreign('recorded_by_user_id', 'rp_entry_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['recipient_payment_session_id', 'entry_type'], 'rp_entry_session_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipient_payment_session_entries');
        Schema::dropIfExists('recipient_payment_sessions');
        Schema::dropIfExists('recipient_payment_call_attempts');
        Schema::dropIfExists('recipient_payment_tasks');
        Schema::dropIfExists('payment_wallet_user');
        Schema::dropIfExists('payment_wallets');
    }
};
