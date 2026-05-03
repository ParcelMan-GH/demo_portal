<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransportManifest extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_LOADING = 'loading';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'manifest_number',
        'sort_batch_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'assigned_driver_id',
        'status',
        'assigned_at',
        'dispatched_at',
        'arrived_at',
        'received_at',
        'created_by_user_id',
        'received_by_user_id',
        'notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'arrived_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function sortBatch(): BelongsTo
    {
        return $this->belongsTo(SortBatch::class);
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransportManifestItem::class);
    }

    public function containers(): HasMany
    {
        return $this->hasMany(TransportContainer::class);
    }

    public function warehouseReceipt(): HasOne
    {
        return $this->hasOne(WarehouseReceipt::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TransportManifestAssignment::class);
    }

    public function loadingExceptions(): HasMany
    {
        return $this->hasMany(TransportLoadingException::class);
    }

    public function labelScans(): HasMany
    {
        return $this->hasMany(TransportManifestLabelScan::class);
    }
}
