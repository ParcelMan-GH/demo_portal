<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The vendor app used to mute "Van" and "Truck" with a hardcoded frontend
 * blocklist (UNAVAILABLE_VEHICLE_NAMES). Availability is now driven entirely by
 * `pickup_vehicle_types.is_active`, so this migration records the state the app
 * was already enforcing: these two start locked, and an admin unlocks them from
 * the Vehicle Fleet panel when they are ready to accept those loads.
 *
 * Only rows that are currently active are touched, so re-running is a no-op and
 * a deliberate admin choice is never reversed by a later deployment.
 */
return new class extends Migration
{
    private const LOCKED_BY_DEFAULT = ['van', 'truck'];

    public function up(): void
    {
        DB::table('pickup_vehicle_types')
            ->whereIn('slug', self::LOCKED_BY_DEFAULT)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('pickup_vehicle_types')
            ->whereIn('slug', self::LOCKED_BY_DEFAULT)
            ->where('is_active', false)
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
