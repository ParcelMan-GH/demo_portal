<?php

namespace App\Models;

use App\Services\StorageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_assignment_id',
        'shipment_item_id',
        'path',
        'original_name',
        'size',
        'type',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'size' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($photo) {
            app(StorageService::class)->delete($photo->path);
        });
    }

    public function pickupAssignment(): BelongsTo
    {
        return $this->belongsTo(PickupAssignment::class);
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }
}
