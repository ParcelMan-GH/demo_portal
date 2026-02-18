<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRun extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_PARTIALLY_DELIVERED = 'partially_delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'run_number',
        'sort_batch_id',
        'warehouse_id',
        'assigned_driver_id',
        'status',
        'assigned_at',
        'dispatched_at',
        'completed_at',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function sortBatch(): BelongsTo
    {
        return $this->belongsTo(SortBatch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(DeliveryRunStop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryRunItem::class);
    }
}

