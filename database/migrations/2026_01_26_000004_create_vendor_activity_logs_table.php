<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // login, logout, register, verify_phone, forgot_pin, reset_pin
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_type')->nullable(); // android, ios
            $table->string('device_name')->nullable(); // Samsung Galaxy S21
            $table->string('os_version')->nullable(); // Android 13
            $table->string('app_version')->nullable(); // 1.0.0
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_activity_logs');
    }
};
