<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipment_collections')) {
            Schema::create('shipment_collections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_id')->unique();
                $table->unsignedBigInteger('warehouse_id');
                $table->string('status')->default('ready'); // ready, collected
                $table->string('collected_by_name', 255)->nullable();
                $table->string('collected_by_phone', 20)->nullable();
                $table->string('collected_by_id_type', 50)->nullable();
                $table->string('collected_by_id_number', 100)->nullable();
                $table->dateTime('ready_at')->nullable();
                $table->dateTime('collected_at')->nullable();
                $table->unsignedBigInteger('handed_over_by_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('signature_path', 500)->nullable();
                $table->timestamps();

                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
                $table->foreign('handed_over_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['warehouse_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_collections');
    }
};
