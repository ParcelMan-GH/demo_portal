<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderPackageLocationChange extends Model
{
    protected $fillable = [
        'shipment_item_id',
        'driver_id',
        'old_location',
        'new_location',
        'proof_photo_path',
        'proof_photo_size',
        'changed_at',
    ];

    protected $casts = [
        'old_location' => 'array',
        'new_location' => 'array',
        'changed_at' => 'datetime',
    ];

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
