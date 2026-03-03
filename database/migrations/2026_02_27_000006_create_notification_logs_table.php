<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type');       // 'shipment_status', 'invoice_sent', 'driver_assigned', etc.
            $table->string('channel');    // push, email, sms
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('status')->default('pending'); // sent, failed, pending
            $table->text('error')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
