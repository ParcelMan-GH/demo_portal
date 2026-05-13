<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recipient_payment_groups')) {
            Schema::create('recipient_payment_groups', function (Blueprint $table) {
                $table->id();
                $table->string('group_key', 80);
                $table->string('payment_group', 32);
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('primary_task_id')->nullable()->constrained('recipient_payment_tasks')->nullOnDelete();
                $table->foreignId('shipment_charge_id')->nullable()->constrained('shipment_charges')->nullOnDelete();
                $table->foreignId('payment_wallet_id')->nullable()->constrained('payment_wallets')->nullOnDelete();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_phone', 40)->nullable();
                $table->string('delivery_town')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 10)->default('GHS');
                $table->string('status', 24)->default('pending');
                $table->string('payment_reference', 100)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'payment_group', 'status']);
                $table->index(['assigned_to_user_id', 'status']);
                $table->index(['group_key', 'status']);
            });
        }

        if (!Schema::hasColumn('recipient_payment_tasks', 'recipient_payment_group_id')) {
            Schema::table('recipient_payment_tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('recipient_payment_group_id')->nullable()->after('id');
                $table->foreign('recipient_payment_group_id', 'rp_task_group_fk')
                    ->references('id')
                    ->on('recipient_payment_groups')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('recipient_payment_session_entries', 'recipient_payment_group_id')) {
            Schema::table('recipient_payment_session_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('recipient_payment_group_id')->nullable()->after('recipient_payment_task_id');
                $table->foreign('recipient_payment_group_id', 'rp_entry_group_fk')
                    ->references('id')
                    ->on('recipient_payment_groups')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recipient_payment_session_entries', 'recipient_payment_group_id')) {
            Schema::table('recipient_payment_session_entries', function (Blueprint $table) {
                $table->dropForeign('rp_entry_group_fk');
                $table->dropColumn('recipient_payment_group_id');
            });
        }

        if (Schema::hasColumn('recipient_payment_tasks', 'recipient_payment_group_id')) {
            Schema::table('recipient_payment_tasks', function (Blueprint $table) {
                $table->dropForeign('rp_task_group_fk');
                $table->dropColumn('recipient_payment_group_id');
            });
        }

        Schema::dropIfExists('recipient_payment_groups');
    }
};
