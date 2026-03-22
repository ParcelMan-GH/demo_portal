<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentCollection extends Model
{
    public const STATUS_READY = 'ready';
    public const STATUS_COLLECTED = 'collected';

    protected $fillable = [
        'shipment_id',
        'warehouse_id',
        'status',
        'collected_by_name',
        'collected_by_phone',
        'collected_by_id_type',
        'collected_by_id_number',
        'ready_at',
        'collected_at',
        'handed_over_by_user_id',
        'notes',
        'signature_path',
    ];

    protected $casts = [
        'ready_at' => 'datetime',
        'collected_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by_user_id');
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isCollected(): bool
    {
        return $this->status === self::STATUS_COLLECTED;
    }
}
