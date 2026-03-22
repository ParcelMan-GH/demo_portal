<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseReceipt extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_DISCREPANCY_OPEN = 'discrepancy_open';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'pickup_assignment_id',
        'shipment_id',
        'transport_manifest_id',
        'warehouse_id',
        'status',
        'started_by_user_id',
        'finalized_by_user_id',
        'approved_by_user_id',
        'approval_reason',
        'notes',
        'started_at',
        'finalized_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function pickupAssignment(): BelongsTo
    {
        return $this->belongsTo(PickupAssignment::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function transportManifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseReceiptItem::class);
    }

    public function hasDiscrepancies(): bool
    {
        return $this->items()->where('discrepancy_type', '!=', 'none')->exists();
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }
}

