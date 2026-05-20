<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'vendor_declared_quantity')) {
                $table->unsignedInteger('vendor_declared_quantity')->nullable()->after('sender_notes');
            }
        });

        Schema::table('pickup_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_assignments', 'driver_picked_quantity')) {
                $table->unsignedInteger('driver_picked_quantity')->nullable()->after('pickup_longitude');
            }
        });

        DB::statement(<<<'SQL'
            UPDATE shipments s
            LEFT JOIN (
                SELECT shipment_id, SUM(quantity) AS total_quantity
                FROM shipment_items
                GROUP BY shipment_id
            ) si ON si.shipment_id = s.id
            SET s.vendor_declared_quantity = COALESCE(si.total_quantity, 0)
            WHERE s.vendor_declared_quantity IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE pickup_assignments pa
            LEFT JOIN (
                SELECT pickup_assignment_id, SUM(confirmed_quantity) AS total_quantity
                FROM pickup_item_confirmations
                GROUP BY pickup_assignment_id
            ) pic ON pic.pickup_assignment_id = pa.id
            SET pa.driver_picked_quantity = COALESCE(pic.total_quantity, 0)
            WHERE pa.driver_picked_quantity IS NULL
              AND pa.completed_at IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('pickup_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_assignments', 'driver_picked_quantity')) {
                $table->dropColumn('driver_picked_quantity');
            }
        });

        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'vendor_declared_quantity')) {
                $table->dropColumn('vendor_declared_quantity');
            }
        });
    }
};
