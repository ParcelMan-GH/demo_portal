<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportContainerItem extends Model
{
    use HasFactory;

    public const STATUS_PACKED = 'packed';
    public const STATUS_LOADED = 'loaded';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_MISSING = 'missing';
    public const STATUS_EXTRA = 'extra';
    public const STATUS_DAMAGED = 'damaged';

    protected $fillable = [
        'transport_container_id',
        'transport_manifest_item_id',
        'shipment_item_id',
        'label_barcode',
        'expected_quantity',
        'received_quantity',
        'status',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    public function container(): BelongsTo
    {
        return $this->belongsTo(TransportContainer::class, 'transport_container_id');
    }

    public function manifestItem(): BelongsTo
    {
        return $this->belongsTo(TransportManifestItem::class, 'transport_manifest_item_id');
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }
}
