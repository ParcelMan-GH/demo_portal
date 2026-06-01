<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderPackageTransfer extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shipment_item_id',
        'from_driver_id',
        'to_driver_id',
        'status',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function fromDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'from_driver_id');
    }

    public function toDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'to_driver_id');
    }
}
