<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WarehouseReceiptItemLabel extends Model
{
    protected $fillable = [
        'warehouse_receipt_item_id',
        'barcode_value',
        'label_index',
        'labels_total',
        'label_type',
        'printed_at',
        'print_count',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function receiptItem(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptItem::class, 'warehouse_receipt_item_id');
    }

    public function custodyEvents(): HasMany
    {
        return $this->hasMany(LabelCustodyEvent::class, 'warehouse_receipt_item_label_id');
    }

    public function latestCustody(): HasOne
    {
        return $this->hasOne(LabelCustodyEvent::class, 'warehouse_receipt_item_label_id')->latestOfMany();
    }

    public function transportScans(): HasMany
    {
        return $this->hasMany(TransportManifestLabelScan::class, 'warehouse_receipt_item_label_id');
    }

    /**
     * Get the current driver holding this label (if claimed and not released/delivered/returned).
     */
    public function currentDriverId(): ?int
    {
        $latest = $this->latestCustody;
        if ($latest && $latest->event_type === LabelCustodyEvent::TYPE_CLAIMED) {
            return $latest->driver_id;
        }
        return null;
    }
}
