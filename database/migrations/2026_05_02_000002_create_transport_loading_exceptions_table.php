<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_loading_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_manifest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transport_container_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transport_manifest_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 80);
            $table->text('note')->nullable();
            $table->string('proof_photo_path');
            $table->string('status', 30)->default('pending');
            $table->boolean('auto_accepted')->default(false);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['transport_manifest_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['transport_container_id', 'status']);
            $table->index(['transport_manifest_item_id', 'status'], 'transport_loading_exception_item_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_loading_exceptions');
    }
};
