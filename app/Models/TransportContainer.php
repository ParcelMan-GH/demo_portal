<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportContainer extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_SEALED = 'sealed';
    public const STATUS_LOADED = 'loaded';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_MISSING = 'missing';

    protected $fillable = [
        'transport_manifest_id',
        'container_code',
        'container_type',
        'sequence_number',
        'status',
        'expected_package_count',
        'sealed_at',
        'sealed_by_user_id',
        'loaded_at',
        'loaded_by_driver_id',
        'received_at',
        'received_by_user_id',
        'notes',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'expected_package_count' => 'integer',
        'sealed_at' => 'datetime',
        'loaded_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class, 'transport_manifest_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransportContainerItem::class);
    }

    public function loadedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'loaded_by_driver_id');
    }

    public function loadingExceptions(): HasMany
    {
        return $this->hasMany(TransportLoadingException::class);
    }
}
