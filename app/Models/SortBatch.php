<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SortBatch extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_SEALED = 'sealed';

    protected $fillable = [
        'batch_number',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'status',
        'created_by_user_id',
        'sealed_by_user_id',
        'sealed_at',
        'notes',
    ];

    protected $casts = [
        'sealed_at' => 'datetime',
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

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}

