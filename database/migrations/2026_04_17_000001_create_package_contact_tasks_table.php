<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('package_contact_tasks')) {
            Schema::create('package_contact_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_item_id');
                $table->unsignedBigInteger('shipment_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('assigned_to_user_id')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('recipient_name', 255)->nullable();
                $table->string('recipient_phone', 20)->nullable();
                $table->string('delivery_town', 255)->nullable();
                $table->string('outcome', 30)->nullable();
                $table->timestamp('callback_at')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('attempts_count')->default(0);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->foreign('shipment_item_id')->references('id')->on('shipment_items')->cascadeOnDelete();
                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
                $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();

                $table->unique('shipment_item_id');
                $table->index(['warehouse_id', 'status']);
                $table->index(['assigned_to_user_id', 'status']);
                $table->index('callback_at');
            });
        }

        if (!Schema::hasTable('package_contact_attempts')) {
            Schema::create('package_contact_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contact_task_id');
                $table->unsignedBigInteger('attempted_by_user_id');
                $table->string('outcome', 30);
                $table->text('notes')->nullable();
                $table->timestamp('attempted_at');

                $table->foreign('contact_task_id')->references('id')->on('package_contact_tasks')->cascadeOnDelete();
                $table->foreign('attempted_by_user_id')->references('id')->on('users')->cascadeOnDelete();

                $table->index('contact_task_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_contact_attempts');
        Schema::dropIfExists('package_contact_tasks');
    }
};
