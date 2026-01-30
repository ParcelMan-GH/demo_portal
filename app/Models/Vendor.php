<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'business_name',
        'phone',
        'email',
        'pin_hash',
        'is_phone_verified',
        'is_active',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'is_phone_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Set the PIN (hashes it automatically).
     */
    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
    }

    /**
     * Verify the PIN.
     */
    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

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
