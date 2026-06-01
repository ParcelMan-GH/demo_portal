<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_package_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('to_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status', 24)->default('pending');
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('responded_at')->nullable()->index();
            $table->timestamps();

            $table->index(['shipment_item_id', 'status']);
            $table->index(['from_driver_id', 'status']);
            $table->index(['to_driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_package_transfers');
    }
};
