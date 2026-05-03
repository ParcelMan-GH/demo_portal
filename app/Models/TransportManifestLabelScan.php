<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportManifestLabelScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_manifest_id',
        'transport_manifest_item_id',
        'warehouse_receipt_item_label_id',
        'driver_id',
        'barcode_value',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class, 'transport_manifest_id');
    }

    public function manifestItem(): BelongsTo
    {
        return $this->belongsTo(TransportManifestItem::class, 'transport_manifest_item_id');
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptItemLabel::class, 'warehouse_receipt_item_label_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
