<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_assignment_id')->unique()->constrained('pickup_assignments')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft|discrepancy_open|finalized
            $table->unsignedBigInteger('started_by_user_id')->nullable();
            $table->unsignedBigInteger('finalized_by_user_id')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->text('approval_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index('started_by_user_id');
            $table->index('finalized_by_user_id');
            $table->index('approved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipts');
    }
};

