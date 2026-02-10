<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_assignment_id')->constrained('pickup_assignments')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->nullable()->constrained('shipment_items')->nullOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->integer('size')->nullable();
            $table->string('type')->default('item'); // item, receipt, condition, other
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_photos');
    }
};
