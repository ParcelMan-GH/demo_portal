<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sort_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('origin_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('status')->default('open'); // open|sealed
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('sealed_by_user_id')->nullable();
            $table->timestamp('sealed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['origin_warehouse_id', 'status']);
            $table->index(['destination_warehouse_id', 'status']);
            $table->index('created_by_user_id');
            $table->index('sealed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_batches');
    }
};

