<?php

namespace App\Models;

use App\Enums\BatchDestinationType;
use App\Helpers\CodeResolver;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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