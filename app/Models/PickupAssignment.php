<?php

namespace App\Models;

use App\Enums\PickupAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'driver_id',
        'status',
        'assigned_by',
        'assigned_at',
        'en_route_at',
        'arrived_at',
        'picked_up_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'pickup_latitude',
        'pickup_longitude',
        'notes',
    ];

    protected $casts = [
        'status' => PickupAssignmentStatus::class,
        'assigned_at' => 'datetime',
        'en_route_at' => 'datetime',
        'arrived_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PickupPhoto::class);
    }
}
