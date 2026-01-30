<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
