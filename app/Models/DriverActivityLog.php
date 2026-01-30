<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'driver_id',
        'action',
        'description',
        'ip_address',
        'device_type',
        'device_name',
        'os_version',
        'app_version',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The driver this log belongs to.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
