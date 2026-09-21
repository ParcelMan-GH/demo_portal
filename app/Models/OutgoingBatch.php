<?php

namespace App\Models;

use App\Enums\BatchDestinationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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