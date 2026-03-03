<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'business_name',
        'phone',
        'email',
        'is_active',
        'fcm_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Activity logs for this vendor.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(VendorActivityLog::class);
    }

    /**
     * Shipments created by this vendor.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
