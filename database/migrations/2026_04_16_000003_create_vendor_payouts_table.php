<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_payouts')) {
            Schema::create('vendor_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->decimal('amount', 10, 2);
                $table->string('status', 20)->default('pending');
                $table->string('payment_method', 30)->default('momo');
                $table->string('payment_reference', 255)->nullable();
                $table->string('payment_phone', 20)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('processed_by_admin_id')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();

                $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
                $table->foreign('processed_by_admin_id')->references('id')->on('users')->nullOnDelete();

                $table->index(['vendor_id', 'status']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payouts');
    }
};
