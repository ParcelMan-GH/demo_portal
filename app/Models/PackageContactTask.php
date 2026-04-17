<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageContactTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    public const OUTCOME_DELIVER = 'deliver';
    public const OUTCOME_SELF_PICKUP = 'self_pickup';
    public const OUTCOME_UNREACHABLE = 'unreachable';
    public const OUTCOME_WRONG_NUMBER = 'wrong_number';
    public const OUTCOME_CALLBACK = 'callback';

    protected $fillable = [
        'shipment_item_id',
        'shipment_id',
        'warehouse_id',
        'assigned_to_user_id',
        'assigned_at',
        'status',
        'recipient_name',
        'recipient_phone',
        'delivery_town',
        'outcome',
        'callback_at',
        'notes',
        'attempts_count',
        'resolved_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'callback_at' => 'datetime',
        'resolved_at' => 'datetime',
        'attempts_count' => 'integer',
    ];

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PackageContactAttempt::class, 'contact_task_id')->orderByDesc('attempted_at');
    }
}
