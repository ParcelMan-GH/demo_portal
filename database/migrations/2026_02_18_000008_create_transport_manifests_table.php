<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->foreignId('sort_batch_id')->constrained('sort_batches')->cascadeOnDelete();
            $table->foreignId('origin_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status')->default('draft'); // draft|assigned|loading|in_transit|arrived|received|cancelled
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique('sort_batch_id');
            $table->index(['origin_warehouse_id', 'status']);
            $table->index(['destination_warehouse_id', 'status']);
            $table->index(['assigned_driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_manifests');
    }
};

