<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupItemConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_assignment_id',
        'shipment_item_id',
        'expected_quantity',
        'confirmed_quantity',
        'notes',
        'confirmed_at',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'confirmed_quantity' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function pickupAssignment(): BelongsTo
    {
        return $this->belongsTo(PickupAssignment::class);
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }
}
