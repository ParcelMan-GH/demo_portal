<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderTeamHandoverItem extends Model
{
    public const STATUS_ASSIGNED_TO_LEADER = 'assigned_to_leader';
    public const STATUS_LEADER_RECEIVED = 'leader_received';
    public const STATUS_ALLOCATED_TO_MEMBER = 'allocated_to_member';
    public const STATUS_MEMBER_CLAIMED = 'member_claimed';
    public const STATUS_IN_DELIVERY = 'in_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_RECALLED = 'recalled';

    protected $fillable = [
        'rider_team_handover_id',
        'warehouse_receipt_item_label_id',
        'allocated_to_driver_id',
        'status',
        'assigned_at',
        'leader_received_at',
        'allocated_at',
        'member_claimed_at',
        'delivered_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'leader_received_at' => 'datetime',
        'allocated_at' => 'datetime',
        'member_claimed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function handover(): BelongsTo
    {
        return $this->belongsTo(RiderTeamHandover::class, 'rider_team_handover_id');
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptItemLabel::class, 'warehouse_receipt_item_label_id');
    }

    public function allocatedTo(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'allocated_to_driver_id');
    }
}
