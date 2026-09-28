<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give the warehouses coordinates.
 *
 * `warehouses.latitude` and `longitude` have always existed and have always been
 * NULL, so the driver app had no real positions to work with. It fell back to
 * hardcoded Accra and Kumasi constants, which meant every batch was drawn and
 * measured as Accra -> Kumasi — including the ones whose origin and destination
 * are the same warehouse.
 *
 * These are OpenStreetMap coordinates for each warehouse's stated location, not
 * surveyed positions for the buildings themselves. The `address` field on these
 * rows is just an area name ("Circle", "Kumasi", "Tema"), so a district-level
 * point is the most that can honestly be derived from what is stored. They are
 * good enough to put a pin in the right city and to stop an intra-city trip
 * being reported as 250 km; they are not good enough for turn-by-turn routing.
 * Replace them with surveyed coordinates when the sites are confirmed.
 *
 * Only rows that are still NULL are touched, so a coordinate entered by hand
 * later is never overwritten by re-running migrations.
 */
return new class extends Migration
{
    /**
     * Keyed by warehouse code, which is stable; the name is a fallback for rows
     * that predate the codes.
     *
     * Source: OpenStreetMap (ODbL), September 2026.
     *   WH-001 Kwame Nkrumah Circle, Accra   5.5697257, -0.2157297
     *   WH-002 Kumasi                        6.6985605, -1.6233086
     *   WH-003 Tema                          5.6596409, -0.0096771
     */
    private const COORDINATES = [
        'WH-001' => ['latitude' => 5.5697257, 'longitude' => -0.2157297],
        'WH-002' => ['latitude' => 6.6985605, 'longitude' => -1.6233086],
        'WH-003' => ['latitude' => 5.6596409, 'longitude' => -0.0096771],
    ];

    private const BY_NAME = [
        'Accra Main' => 'WH-001',
        'Kumasi Center' => 'WH-002',
        'Tema Warehouse' => 'WH-003',
    ];

    public function up(): void
    {
        $warehouses = DB::table('warehouses')->get(['id', 'name', 'code', 'latitude', 'longitude']);

        foreach ($warehouses as $warehouse) {
            $code = $warehouse->code ?: (self::BY_NAME[$warehouse->name] ?? null);

            if (! $code || ! isset(self::COORDINATES[$code])) {
                continue;
            }

            // Never overwrite a coordinate that is already set.
            if ($warehouse->latitude !== null && $warehouse->longitude !== null) {
                continue;
            }

            DB::table('warehouses')->where('id', $warehouse->id)->update(self::COORDINATES[$code]);
        }
    }

    public function down(): void
    {
        // Deliberately not reverted: nulling the coordinates would put the app
        // back to drawing an invented route, and a coordinate is not data the
        // rollback of this migration should destroy.
    }
};
