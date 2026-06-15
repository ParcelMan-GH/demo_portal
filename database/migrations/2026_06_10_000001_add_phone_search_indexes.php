<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Prefix-matchable phone indexes for the admin global search
// (vendor/driver phones are already unique-indexed).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->index('recipient_phone', 'shipments_recipient_phone_idx');
            $table->index('delivery_recipient_phone', 'shipments_delivery_recipient_phone_idx');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->index('delivery_recipient_phone', 'si_delivery_recipient_phone_idx');
        });

        Schema::table('recipient_payment_tasks', function (Blueprint $table) {
            $table->index('recipient_phone', 'rpt_recipient_phone_idx');
        });
    }

    public function down(): void
    {
        Schema::table('recipient_payment_tasks', function (Blueprint $table) {
            $table->dropIndex('rpt_recipient_phone_idx');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropIndex('si_delivery_recipient_phone_idx');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_delivery_recipient_phone_idx');
            $table->dropIndex('shipments_recipient_phone_idx');
        });
    }
};
