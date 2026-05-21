<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PickupVehicleType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'capacity_hint',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $type) {
            if (blank($type->slug) && filled($type->name)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function shipmentRequests(): HasMany
    {
        return $this->hasMany(ShipmentPickupVehicleRequest::class);
    }
}
