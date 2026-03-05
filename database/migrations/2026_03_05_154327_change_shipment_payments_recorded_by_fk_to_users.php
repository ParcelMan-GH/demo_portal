<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by_admin_id']);
            $table->foreign('recorded_by_admin_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by_admin_id']);
            $table->foreign('recorded_by_admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });
    }
};
