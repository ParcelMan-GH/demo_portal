<?php

namespace App\Models;

use App\Enums\BatchDestinationType;
use App\Helpers\CodeResolver;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingBatch extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    /** Handed to a driver or bus and on its way. */
    public const STATUS_DISPATCHED = 'dispatched';

    /** Confirmed in at the destination end. */
    public const STATUS_RECEIVED = 'received';

    /**
     * The transporter has handed the batch over and the hub has checked it in.
     *
     * The value is `arrived` rather than `received_at_hub` on purpose: it is what
     * production rows already carry, and it is deliberately *not* in
     * CLOSED_STATUSES — a batch sitting at the hub is precisely the one that
     * still has to go on a bus, so it must stay in the dispatchable list.
     * `received` would retire it from that list the moment it was unpacked.
     */
    public const STATUS_ARRIVED_AT_HUB = 'arrived';

    /**
     * Statuses that mean the batch has already left and can take no more work.
     *
     * Deliberately a deny-list. The first version of this checked for `'open'`,
     * which meant any status the code did not anticipate — an older row, a
     * status written by another module — silently blocked every package with
     * "no longer open". A batch is now assumed workable unless it is known to
     * have gone.
     */
    public const CLOSED_STATUSES = ['dispatched', 'received', 'in_transit'];

    /**
     * A batch number that is not already taken, e.g. `PM-BATCH-IQKWKJ`.
     *
     * The `BATCH` marker is kept after the new `PM-` prefix because a batch has
     * to stay tellable apart from a parcel tracking code at a glance — both are
     * scanned by the same hands, and `PM-BATCH-…` says which is which.
     *
     * Short random bodies keep the code readable off a label; the longer
     * fallback is only reached if five six-character draws all collided. The
     * uniqueness check matters: one of the two callers used to generate a batch
     * number inline with no check at all.
     */
    public static function generateBatchNumber(): string
    {
        $prefix = rtrim(
            (string) PlatformSetting::getValue('shipment.batch_prefix', CodeResolver::PREFIX),
            '-'
        );

        foreach ([6, 12] as $length) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = $prefix.'-'.CodeResolver::BATCH_MARKER.'-'.strtoupper(Str::random($length));

                if (! static::query()->where('batch_number', $candidate)->exists()) {
                    return $candidate;
                }
            }
        }

        // Practically unreachable; a timestamp still beats failing the dispatch.
        return $prefix.'-'.CodeResolver::BATCH_MARKER.'-'.strtoupper((string) time());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_number',
        'delivery_region_id',
        'delivery_district_id',
        'destination_warehouse_id',
        'destination_type',
        'status',
        'transport_driver_id',
    ];

    /**
     * Get the shipment items attached to this batch.
     */
    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * The hub this batch is going to, when it is going to one.
     */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * The hub that serves a destination, from its region and district.
     *
     * One definition, used both when a batch is first formed and when the
     * transport manifest for it is raised, so the batch and its manifest can
     * never name different destinations.
     *
     * Prefers a hub in the same district, then any active hub in the region, and
     * then — when the region has no hub of its own — the network's default
     * routing hub.
     *
     * That last step deliberately reverses the previous rule, which returned null
     * and called it the honest answer. It was honest, and it left 14 of Ghana's 16
     * regions unable to dispatch anything: only Greater Accra and Ashanti have
     * warehouses, so a batch for anywhere else had no destination at all and the
     * transport manifest had nothing to name. A batch that reaches the HQ can be
     * sorted onward; a batch that reaches nowhere cannot move.
     *
     * The distinction is still real rather than pretended: the region's own hub
     * always wins, and the fallback is only reached when there is none. A caller
     * that needs to know which happened can compare the resolved warehouse's
     * region against the batch's.
     */
    public static function resolveDestinationWarehouseId(?int $regionId, ?int $districtId): ?int
    {
        if (empty($regionId)) {
            return null;
        }

        $base = Warehouse::query()
            ->where('is_active', true)
            ->where('region_id', $regionId);

        // Some deployments record what a warehouse is for. Only consider the ones
        // that can receive; guarded because the column is not everywhere, and
        // filtering on a missing column would turn batch creation into an error.
        if (Schema::hasColumn('warehouses', 'type')) {
            $base->whereIn('type', ['destination', 'both']);
        }

        if ($districtId) {
            $sameDistrict = (clone $base)->where('district_id', $districtId)->orderBy('id')->first();

            if ($sameDistrict) {
                return (int) $sameDistrict->id;
            }
        }

        $inRegion = (clone $base)->orderBy('id')->first();

        if ($inRegion) {
            return (int) $inRegion->id;
        }

        /*
         * No hub in this region: fall back to the default routing hub — the HQ
         * where there is one, otherwise the lowest-numbered active warehouse.
         *
         * Chosen by `is_hq` rather than by id so the choice survives a reseed; on
         * this data it resolves to Accra Main (id 1), the network's root hub.
         */
        $default = Warehouse::query()
            ->where('is_active', true)
            ->when(
                Schema::hasColumn('warehouses', 'type'),
                fn ($query) => $query->whereIn('type', ['destination', 'both'])
            )
            ->orderByDesc('is_hq')
            ->orderBy('id')
            ->first();

        return $default ? (int) $default->id : null;
    }

    /**
     * The batch's destination type.
     *
     * Resolved through the enum rather than an enum cast: the column is
     * genuinely optional, and a cast would have to pick a fallback case, which
     * would silently turn "no destination type set" into "commerce".
     */
    public function destinationType(): ?BatchDestinationType
    {
        return BatchDestinationType::fromValue($this->destination_type);
    }

    public function destinationTypeLabel(): string
    {
        return $this->destinationType()?->label() ?? 'Standard';
    }

    /**
     * True when this batch may only carry commerce packages.
     */
    public function acceptsOnlyCommerce(): bool
    {
        return (bool) $this->destinationType()?->acceptsOnlyCommerce();
    }

    /**
     * Whether the batch can still accept packages.
     */
    public function isOpen(): bool
    {
        return ! in_array(strtolower(trim((string) $this->status)), self::CLOSED_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return ucfirst(str_replace('_', ' ', (string) ($this->status ?: self::STATUS_OPEN)));
    }
}