<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receipt_items', function (Blueprint $table) {
            $table->index('barcode_value', 'wri_barcode_value_idx');
        });

        Schema::table('shipment_payments', function (Blueprint $table) {
            $table->index('reference_number', 'sp_reference_number_idx');
        });

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->index('payment_reference', 'sc_payment_reference_idx');
        });

        Schema::table('recipient_payment_tasks', function (Blueprint $table) {
            $table->index('payment_reference', 'rpt_payment_reference_idx');
        });

        Schema::table('vendor_payouts', function (Blueprint $table) {
            $table->index('payment_reference', 'vp_payment_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_payouts', function (Blueprint $table) {
            $table->dropIndex('vp_payment_reference_idx');
        });

        Schema::table('recipient_payment_tasks', function (Blueprint $table) {
            $table->dropIndex('rpt_payment_reference_idx');
        });

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->dropIndex('sc_payment_reference_idx');
        });

        Schema::table('shipment_payments', function (Blueprint $table) {
            $table->dropIndex('sp_reference_number_idx');
        });

        Schema::table('warehouse_receipt_items', function (Blueprint $table) {
            $table->dropIndex('wri_barcode_value_idx');
        });
    }
};
