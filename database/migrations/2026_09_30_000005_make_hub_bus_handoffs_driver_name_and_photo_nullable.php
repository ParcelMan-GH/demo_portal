<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The batch dispatch form was reduced to four fields and no longer collects a
 * driver name or a handover photo, so those columns have to accept a handoff
 * without them.
 *
 * This only widens the columns. Every existing row has both a driver name and a
 * proof photo, and is left exactly as it was — the change is what lets a new
 * handoff be recorded when the agent did not supply them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_bus_handoffs', function (Blueprint $table) {
            $table->string('driver_name', 120)->nullable()->change();
            $table->string('proof_photo_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberately no-op. Restoring NOT NULL would fail on any row created
        // without a driver name or photo, and widening the columns is harmless
        // to leave in place.
    }
};
