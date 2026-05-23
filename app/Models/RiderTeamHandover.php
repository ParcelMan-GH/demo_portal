<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiderTeamHandover extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PARTIALLY_DISTRIBUTED = 'partially_distributed';
    public const STATUS_DISTRIBUTED = 'distributed';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_RECALLED = 'recalled';

    protected $fillable = [
        'handover_number',
        'warehouse_id',
        'rider_team_id',
        'leader_driver_id',
        'created_by_user_id',
        'created_by_driver_id',
        'status',
        'assigned_count',
        'received_count',
        'distributed_count',
        'claimed_count',
        'delivered_count',
        'failed_count',
        'assigned_at',
        'received_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'assigned_count' => 'integer',
        'received_count' => 'integer',
        'distributed_count' => 'integer',
        'claimed_count' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'assigned_at' => 'datetime',
        'received_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(RiderTeam::class, 'rider_team_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'leader_driver_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'created_by_driver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RiderTeamHandoverItem::class);
    }
}
