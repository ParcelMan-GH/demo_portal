<?php

namespace App\Http\Requests\Api\Vendor\Shipment\Concerns;

use App\Models\PickupVehicleType;

/**
 * Normalises a pickup-vehicle selection onto one internal shape.
 *
 * Two things changed at the API surface and this keeps the rest of the stack
 * unaware of either:
 *
 *  1. The key is now `requested_vehicles`. `pickup_vehicles` still works, and
 *     must keep working: the mobile app ships through app-store builds, so
 *     versions already installed in the field will keep sending the old key for
 *     a long time. Renaming the API without accepting both would have been a
 *     silent failure — Laravel would drop the unknown field, the service's
 *     `array_key_exists('pickup_vehicles', …)` would be false, and every pickup
 *     from an older build would arrive with no vehicle at all and a 200.
 *
 *  2. A row may name the vehicle by `type` slug (`"motorbike"`) as well as by
 *     `vehicle_type_id`. Slugs are what the documented payload uses; ids are what
 *     the app reads back from the API.
 *
 * Everything is normalised to the internal `pickup_vehicles` array of
 * `{vehicle_type_id, quantity}`, which is what `ShipmentService` and the
 * `shipment_pickup_vehicle_requests` table already speak.
 *
 * A row naming a vehicle that does not resolve is passed through with a null id
 * rather than dropped, so validation reports it. Dropping it would let a typo
 * silently reduce the request to nothing.
 */
trait NormalizesRequestedVehicles
{
    /** The documented key, and the one clients should send. */
    public const VEHICLES_KEY = 'requested_vehicles';

    /** The original key, still accepted from app builds already in the field. */
    public const VEHICLES_KEY_LEGACY = 'pickup_vehicles';

    protected function normalizeRequestedVehicles(): void
    {
        $raw = $this->input(self::VEHICLES_KEY);

        // Only fall back to the legacy key when the canonical one is absent, so
        // a client that sends both is not silently overridden by the old field.
        if ($raw === null) {
            $raw = $this->input(self::VEHICLES_KEY_LEGACY);
        }

        // Neither key present: this is a partial update that is not touching the
        // vehicle selection, so leave the stored rows alone.
        if ($raw === null) {
            return;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            $raw = [];
        }

        $normalized = $this->mapVehicleRows($raw);

        $this->merge([
            self::VEHICLES_KEY => $normalized,
            // Required, not cosmetic: `$request->validated()` only returns keys
            // that appear in `rules()`, and `ShipmentService` reads the legacy
            // key. Without this merge the selection would vanish between
            // validation and the sync.
            self::VEHICLES_KEY_LEGACY => $normalized,
        ]);
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{vehicle_type_id: int|null, quantity: int|null}>
     */
    private function mapVehicleRows(array $rows): array
    {
        $slugs = [];
        foreach ($rows as $row) {
            if (is_array($row)
                && ! isset($row['vehicle_type_id'])
                && isset($row['type'])
                && is_string($row['type'])) {
                $slugs[] = $row['type'];
            }
        }

        // One query for every slug on the request rather than one per row.
        $idsBySlug = $slugs === []
            ? []
            : PickupVehicleType::query()
                ->whereIn('slug', array_unique($slugs))
                ->pluck('id', 'slug')
                ->all();

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $typeId = is_numeric($row['vehicle_type_id'] ?? null)
                ? (int) $row['vehicle_type_id']
                : null;

            if ($typeId === null && isset($row['type']) && is_string($row['type'])) {
                $typeId = $idsBySlug[$row['type']] ?? null;
            }

            $normalized[] = [
                'vehicle_type_id' => $typeId,
                'quantity' => is_numeric($row['quantity'] ?? null)
                    ? (int) $row['quantity']
                    : null,
            ];
        }

        return $normalized;
    }
}
