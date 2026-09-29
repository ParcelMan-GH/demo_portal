<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give a pickup assignment its place in a multi-slot request: which of the
     * shipment's requested vehicle types it covers, and which slot of the
     * shipment it is (1-based).
     *
     * Both columns are nullable on purpose. Every row written before this
     * migration predates the idea of slots — it simply says "a rider is going
     * to pick this parcel up" — and has to keep working unslotted.
     */
    public function up(): void
    {
        Schema::table('pickup_assignments', function (Blueprint $table) {
            $table->foreignId('pickup_vehicle_type_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('pickup_vehicle_types')
                ->nullOnDelete();

            $table->unsignedSmallInteger('slot_number')
                ->nullable()
                ->after('pickup_vehicle_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_assignments', function (Blueprint $table) {
            // Drops the foreign key and the column in one step.
            $table->dropConstrainedForeignId('pickup_vehicle_type_id');
            $table->dropColumn('slot_number');
        });
    }
};
