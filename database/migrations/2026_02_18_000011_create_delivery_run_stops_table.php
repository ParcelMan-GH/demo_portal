<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_run_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_run_id')->constrained('delivery_runs')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('town')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('gh_post_address')->nullable();
            $table->string('landmark')->nullable();
            $table->string('status')->default('pending'); // pending|arrived|delivered|failed
            $table->string('verification_code_hash')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->timestamp('verification_code_sent_at')->nullable();
            $table->unsignedInteger('verification_attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->string('proof_photo_path')->nullable();
            $table->unsignedBigInteger('proof_photo_size')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('failure_notes')->nullable();
            $table->timestamps();

            $table->index(['delivery_run_id', 'status']);
            $table->index(['recipient_phone', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_run_stops');
    }
};

