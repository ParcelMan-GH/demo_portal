<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentPickupVehicleRequest extends Model
{
    protected $fillable = [
        'shipment_id',
        'pickup_vehicle_type_id',
        'vehicle_name_snapshot',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(PickupVehicleType::class, 'pickup_vehicle_type_id');
    }
}
