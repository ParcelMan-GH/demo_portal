<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'base_location',
        'status',
        'is_active',
        'task_capabilities',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'task_capabilities' => 'array',
        'last_login_at' => 'datetime',
    ];

    /**
     * Activity logs for this driver.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(DriverActivityLog::class);
    }

    /**
     * Get all pickup assignments for this driver.
     */
    public function pickupAssignments(): HasMany
    {
        return $this->hasMany(PickupAssignment::class);
    }

    /**
     * Get the active pickup assignment for this driver.
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(PickupAssignment::class)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latestOfMany();
    }
}
