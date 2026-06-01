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
        'resolved_by_user_id',
        'confirmation_code',
        'confirmation_code_sent_at',
        'confirmation_code_expires_at',
        'confirmation_code_verified_at',
        'confirmation_attempts',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'callback_at' => 'datetime',
        'resolved_at' => 'datetime',
        'attempts_count' => 'integer',
        'confirmation_code_sent_at' => 'datetime',
        'confirmation_code_expires_at' => 'datetime',
        'confirmation_code_verified_at' => 'datetime',
        'confirmation_attempts' => 'integer',
    ];

    /**
     * Outcomes that require confirming the recipient with an SMS code.
     */
    public const VERIFIED_OUTCOMES = [self::OUTCOME_DELIVER, self::OUTCOME_SELF_PICKUP];

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

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PackageContactAttempt::class, 'contact_task_id')->orderByDesc('attempted_at');
    }
}
