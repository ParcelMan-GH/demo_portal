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
     * A dispatched batch can no longer accept packages.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}