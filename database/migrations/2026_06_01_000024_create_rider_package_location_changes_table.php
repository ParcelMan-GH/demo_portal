<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_package_location_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->json('old_location')->nullable();
            $table->json('new_location');
            $table->string('proof_photo_path');
            $table->unsignedBigInteger('proof_photo_size')->nullable();
            $table->timestamp('changed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['shipment_item_id', 'changed_at']);
            $table->index(['driver_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_package_location_changes');
    }
};
