<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesActingDriver;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\DriverLocation;
use App\Models\TransportManifest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Where a rider is, as reported by the rider's phone.
 *
 * This is the only source of real positions in the system. Everything else the
 * dashboard has ever drawn came from `delivery_run_stops` coordinates, and every
 * one of those is NULL — which is why the admin's Live Tracking showed "No active
 * deliveries" while a transporter was mid-trip.
 *
 * The previous body wrote the position onto the user row alone:
 *
 *     $user->update(['current_latitude' => ..., 'last_location_at' => ...]);
 *
 * None of those three columns existed, and none were fillable, so the call threw
 * a mass-assignment error and stored nothing. It also recorded no history and no
 * trip, so even once it worked there would have been no way to show where a rider
 * had been, or which batch they were carrying.
 */
class TransporterLocationController extends Controller
{
    use ResolvesActingDriver;

    /**
     * A rider whose last fix is this recent and this close is standing still —
     * the phone is reporting the same place over and over, and a new history row
     * would only bloat the table.
     */
    private const DEDUPE_SECONDS = 20;

    private const DEDUPE_METRES = 20;

    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        // A rider is the usual caller, but a position reported by an account with
        // no `drivers` row is still recorded against the user rather than thrown
        // away — `actingDriver()` aborts 403 for such an account, and losing the
        // fix would be the worse outcome.
        $driver = null;

        try {
            $driver = $this->actingDriver($request);
        } catch (Throwable $e) {
            Log::info('Position reported by an account with no rider profile', [
                'user_id' => $user?->id,
                'reason' => $e->getMessage(),
            ]);
        }

        $recordedAt = isset($validated['recorded_at'])
            ? \Illuminate\Support\Carbon::parse($validated['recorded_at'])
            : now();

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $manifest = $driver ? $this->liveManifestFor($driver->id) : null;
        $run = $driver ? $this->liveRunFor($driver->id) : null;

        $stored = false;

        if (! $this->isDuplicate($driver?->id, $latitude, $longitude)) {
            DriverLocation::create([
                'driver_id' => $driver?->id,
                'user_id' => $user?->id,
                'transport_manifest_id' => $manifest?->id,
                'delivery_run_id' => $run?->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $validated['accuracy'] ?? null,
                'heading' => $validated['heading'] ?? null,
                'speed' => $validated['speed'] ?? null,
                'recorded_at' => $recordedAt,
            ]);

            $stored = true;
        }

        // The cheap read the dashboard and the app both use: one row per rider.
        if ($user) {
            $user->forceFill([
                'current_latitude' => $latitude,
                'current_longitude' => $longitude,
                'last_location_at' => $recordedAt,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => $stored ? 'Position recorded.' : 'Position unchanged.',
            'data' => [
                'recorded_at' => $recordedAt->toIso8601String(),
                'stored' => $stored,
                'driver_id' => $driver?->id,
                'transport' => $manifest?->manifest_number,
                'delivery_run' => $run?->run_number,
            ],
        ]);
    }

    /**
     * The batch this rider is carrying right now, if any.
     *
     * `assigned`, `loading` and `in_transit` are the trip; a draft nobody has
     * taken and a batch already landed are not where the rider is.
     */
    private function liveManifestFor(int $driverId): ?TransportManifest
    {
        return TransportManifest::query()
            ->where('assigned_driver_id', $driverId)
            ->whereIn('status', [
                TransportManifest::STATUS_ASSIGNED,
                TransportManifest::STATUS_LOADING,
                TransportManifest::STATUS_IN_TRANSIT,
            ])
            ->orderByDesc('id')
            ->first();
    }

    private function liveRunFor(int $driverId): ?DeliveryRun
    {
        return DeliveryRun::query()
            ->where('assigned_driver_id', $driverId)
            ->where('status', 'out_for_delivery')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Is this the same place we were told about moments ago?
     *
     * Compared on real distance rather than equality, because a stationary phone
     * still jitters by a few metres every fix.
     */
    private function isDuplicate(?int $driverId, float $latitude, float $longitude): bool
    {
        if (! $driverId) {
            return false;
        }

        $last = DriverLocation::query()
            ->where('driver_id', $driverId)
            ->orderByDesc('recorded_at')
            ->first();

        if (! $last || $last->recorded_at === null) {
            return false;
        }

        if ($last->recorded_at->gt(now()->subSeconds(self::DEDUPE_SECONDS))) {
            return $this->metresBetween($last->latitude, $last->longitude, $latitude, $longitude)
                < self::DEDUPE_METRES;
        }

        return false;
    }

    /** Flat-earth distance, near enough for a 20-metre question. */
    private function metresBetween(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }
}
