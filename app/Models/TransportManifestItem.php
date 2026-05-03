<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportManifestItem extends Model
{
    use HasFactory;

    public const LINE_PENDING = 'pending';
    public const LINE_LOADED = 'loaded';
    public const LINE_RECEIVED = 'received';
    public const LINE_SHORT = 'short';
    public const LINE_EXCESS = 'excess';
    public const LINE_DAMAGED = 'damaged';

    protected $fillable = [
        'transport_manifest_id',
        'shipment_item_id',
        'expected_quantity',
        'loaded_quantity',
        'received_quantity',
        'loaded_at',
        'received_at',
        'scan_out_count',
        'scan_in_count',
        'line_status',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'loaded_quantity' => 'integer',
        'received_quantity' => 'integer',
        'scan_out_count' => 'integer',
        'scan_in_count' => 'integer',
        'loaded_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class, 'transport_manifest_id');
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function containerItems(): HasMany
    {
        return $this->hasMany(TransportContainerItem::class, 'transport_manifest_item_id');
    }

    public function loadingExceptions(): HasMany
    {
        return $this->hasMany(TransportLoadingException::class, 'transport_manifest_item_id');
    }

    public function labelScans(): HasMany
    {
        return $this->hasMany(TransportManifestLabelScan::class, 'transport_manifest_item_id');
    }
}
