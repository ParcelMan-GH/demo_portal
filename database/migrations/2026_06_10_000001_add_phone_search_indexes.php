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
        $this->addIndexIfColumnExists('shipments', 'recipient_phone', 'shipments_recipient_phone_idx');
        $this->addIndexIfColumnExists('shipments', 'delivery_recipient_phone', 'shipments_delivery_recipient_phone_idx');
        $this->addIndexIfColumnExists('shipment_items', 'delivery_recipient_phone', 'si_delivery_recipient_phone_idx');
        $this->addIndexIfColumnExists('recipient_payment_tasks', 'recipient_phone', 'rpt_recipient_phone_idx');
    }

    public function down(): void
    {
        $this->dropIndexIfColumnExists('recipient_payment_tasks', 'recipient_phone', 'rpt_recipient_phone_idx');
        $this->dropIndexIfColumnExists('shipment_items', 'delivery_recipient_phone', 'si_delivery_recipient_phone_idx');
        $this->dropIndexIfColumnExists('shipments', 'delivery_recipient_phone', 'shipments_delivery_recipient_phone_idx');
        $this->dropIndexIfColumnExists('shipments', 'recipient_phone', 'shipments_recipient_phone_idx');
    }

    private function addIndexIfColumnExists(string $tableName, string $column, string $indexName): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $indexName) {
            $table->index($column, $indexName);
        });
    }

    private function dropIndexIfColumnExists(string $tableName, string $column, string $indexName): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
