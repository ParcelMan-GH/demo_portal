<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number')->unique();
            $table->foreignId('sort_batch_id')->constrained('sort_batches')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status')->default('draft'); // draft|assigned|out_for_delivery|partially_delivered|completed|cancelled
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('sort_batch_id');
            $table->index(['warehouse_id', 'status']);
            $table->index(['assigned_driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_runs');
    }
};

