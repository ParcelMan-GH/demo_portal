<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
