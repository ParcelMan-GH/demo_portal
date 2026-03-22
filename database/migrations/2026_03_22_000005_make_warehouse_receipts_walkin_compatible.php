<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            // Make pickup_assignment_id nullable for walk-in receipts
            $table->unsignedBigInteger('pickup_assignment_id')->nullable()->change();

            // Add shipment_id as alternative FK for walk-in receipts
            if (!Schema::hasColumn('warehouse_receipts', 'shipment_id')) {
                $table->unsignedBigInteger('shipment_id')->nullable()->after('pickup_assignment_id');
                $table->foreign('shipment_id')->references('id')->on('shipments')->nullOnDelete();
                $table->index('shipment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_receipts', 'shipment_id')) {
                $table->dropForeign(['shipment_id']);
                $table->dropIndex(['shipment_id']);
                $table->dropColumn('shipment_id');
            }
        });
    }
};
