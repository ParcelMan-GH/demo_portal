<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_assignment_events', function (Blueprint $table) {
            $table->id();
            $table->string('job_type', 32);
            $table->unsignedBigInteger('job_id');
            $table->string('event_type', 32);
            $table->foreignId('previous_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index(['job_type', 'job_id', 'created_at'], 'rider_assignment_job_history');
            $table->index(['driver_id', 'created_at']);
            $table->index(['previous_driver_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_assignment_events');
    }
};
