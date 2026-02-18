<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_verification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_run_stop_id')->constrained('delivery_run_stops')->cascadeOnDelete();
            $table->string('entered_code_masked')->nullable();
            $table->boolean('is_success')->default(false);
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('attempted_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['delivery_run_stop_id', 'attempted_at'], 'dva_stop_attempted_idx');
            $table->index(['driver_id', 'attempted_at'], 'dva_driver_attempted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_verification_attempts');
    }
};
