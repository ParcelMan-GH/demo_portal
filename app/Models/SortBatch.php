<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SortBatch extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_SEALED = 'sealed';
    public const DISPATCH_TRANSFER = 'transfer';
    public const DISPATCH_LOCAL_DELIVERY = 'local_delivery';

    protected $fillable = [
        'batch_number',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'dispatch_mode',
        'status',
        'created_by_user_id',
        'sealed_by_user_id',
        'sealed_at',
        'notes',
    ];

    protected $casts = [
        'sealed_at' => 'datetime',
    ];

    protected $attributes = [
        'dispatch_mode' => self::DISPATCH_TRANSFER,
        'status' => self::STATUS_OPEN,
    ];

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function sealedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sealed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SortBatchItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(SortBatchItem::class)->whereNull('removed_at');
    }

    public function transportManifest(): HasOne
    {
        return $this->hasOne(TransportManifest::class);
    }

    public function deliveryRun(): HasOne
    {
        return $this->hasOne(DeliveryRun::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isTransferMode(): bool
    {
        return $this->dispatch_mode === self::DISPATCH_TRANSFER;
    }

    public function isLocalDeliveryMode(): bool
    {
        return $this->dispatch_mode === self::DISPATCH_LOCAL_DELIVERY;
    }
}
