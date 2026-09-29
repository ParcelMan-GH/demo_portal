<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Primary regional hubs for the four busiest non-Accra regions.
 *
 * Only Greater Accra and Ashanti had warehouses, so 14 of Ghana's 16 regions
 * resolved to the HQ fallback and a regional transfer lane could not exist.
 *
 * Regions are looked up **by name, never by id**. The ids are not what one would
 * expect — 6 is Volta and 7 is Northern, not the reverse — so a brief that says
 * "Tamale Hub (Region 6)" and "Ho Hub (Region 7)" has them swapped. Looking the
 * name up makes an off-by-one impossible.
 *
 * Coordinates are OpenStreetMap points for the city, matching the approach of
 * `2026_09_29_000003_backfill_warehouse_coordinates`: good enough to place a pin
 * in the right city and to stop a trip being measured against the old hardcoded
 * Accra/Kumasi fallback, not good enough for turn-by-turn routing. Nominal
 * district coverage is not recorded on the hub — `district_id` stays NULL, as it
 * does on every existing warehouse, which leaves the resolver matching on region.
 *
 * Idempotent: keyed on `code`, so re-running is a no-op and a hub edited by hand
 * afterwards is not clobbered.
 */
return new class extends Migration
{
    /**
     * code => [name, region name, address, latitude, longitude]
     *
     * Source: OpenStreetMap (ODbL), September 2026.
     */
    private const HUBS = [
        'WH-004' => ['Koforidua Hub', 'Eastern', 'Koforidua', 6.1003340, -0.2614572],
        'WH-005' => ['Tamale Hub', 'Northern', 'Tamale', 9.4051992, -0.8423986],
        'WH-006' => ['Takoradi Hub', 'Western', 'Sekondi-Takoradi', 4.9274560, -1.7490216],
        'WH-007' => ['Ho Hub', 'Volta', 'Ho', 6.6126598, 0.4688932],
    ];

    public function up(): void
    {
        $regionIds = DB::table('regions')->pluck('id', 'name');

        foreach (self::HUBS as $code => [$name, $regionName, $address, $latitude, $longitude]) {
            $regionId = $regionIds[$regionName] ?? null;

            // A region that does not exist on this install is skipped rather than
            // inserted with a null region, which would make the hub unusable.
            if (! $regionId) {
                continue;
            }

            if (DB::table('warehouses')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('warehouses')->insert([
                'name' => $name,
                'code' => $code,
                'address' => $address,
                'region_id' => $regionId,
                'district_id' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'contact_phone' => null,
                'contact_email' => null,
                'capacity' => null,
                'is_active' => true,
                'is_hq' => false,
                // Only the HQ administers the system; these are routing points.
                'can_administer_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        /*
         * Hard delete, and deliberately unbounded by soft deletes: these rows were
         * created by this migration and nothing else references them. Batches
         * already pointing at one keep the id — nulling that would recreate the
         * `NULL` destination this whole change exists to remove.
         */
        DB::table('warehouses')->whereIn('code', array_keys(self::HUBS))->delete();
    }
};
