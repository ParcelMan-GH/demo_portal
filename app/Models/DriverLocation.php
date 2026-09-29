<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One position report from a rider's phone.
 *
 * `recorded_at` is when the phone took the fix, not when the row was written. A
 * ping that sat in a dead spot arrives late and out of order, and the trail has
 * to read in the order the rider actually moved.
 */
class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id',
        'user_id',
        'transport_manifest_id',
        'delivery_run_id',
        'latitude',
        'longitude',
        'accuracy',
        'heading',
        'speed',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'heading' => 'float',
        'speed' => 'float',
        'recorded_at' => 'datetime',
    ];

    /**
     * How long a fix is still worth drawing.
     *
     * Past this, the dashboard says the rider's position is unknown rather than
     * drawing a dot that is hours old and calling it live.
     */
    public const FRESH_FOR_HOURS = 12;

    /** How long a fix counts as current, rather than merely last known. */
    public const LIVE_FOR_MINUTES = 30;

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transportManifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class);
    }

    public function deliveryRun(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class);
    }

    public function isLive(): bool
    {
        return $this->recorded_at !== null
            && $this->recorded_at->gte(now()->subMinutes(self::LIVE_FOR_MINUTES));
    }

    /**
     * The newest fix for each of the given drivers, keyed by driver id.
     *
     * Newest by `recorded_at` rather than by row id: a ping that waited in a dead
     * spot is written late, so the highest id is not always the most recent
     * position. Reading the window and reducing in PHP keeps this correct without
     * a correlated subquery, and the window keeps it cheap — a rider whose last
     * fix is older than the window has no useful position to draw anyway.
     *
     * @param  array<int, int>  $driverIds
     * @return Collection<int, self>
     */
    public static function latestForDrivers(array $driverIds): Collection
    {
        $driverIds = array_values(array_filter(array_map('intval', $driverIds)));

        if ($driverIds === []) {
            return collect();
        }

        return self::query()
            ->whereIn('driver_id', $driverIds)
            ->where('recorded_at', '>=', now()->subHours(self::FRESH_FOR_HOURS))
            // `recorded_at` is stored to the second, and a phone that had no
            // signal sends its backlog in one burst — so several fixes routinely
            // share a timestamp. Without the id tie-break "newest" is whichever
            // row the engine happened to return first, which showed an admin a
            // position the rider had already left.
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get()
            ->unique('driver_id')
            ->keyBy('driver_id');
    }
}
