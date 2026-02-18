<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryVerificationAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_run_stop_id',
        'entered_code_masked',
        'is_success',
        'driver_id',
        'attempted_at',
        'ip_address',
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function stop(): BelongsTo
    {
        return $this->belongsTo(DeliveryRunStop::class, 'delivery_run_stop_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

