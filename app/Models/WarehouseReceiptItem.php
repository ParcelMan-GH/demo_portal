<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_receipt_id',
        'shipment_item_id',
        'expected_quantity',
        'received_quantity',
        'damaged_quantity',
        'discrepancy_type',
        'condition_status',
        'notes',
        'received_by_user_id',
        'received_at',
        'barcode_value',
        'barcode_format',
        'barcode_printed_at',
        'barcode_print_count',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'barcode_print_count' => 'integer',
        'received_at' => 'datetime',
        'barcode_printed_at' => 'datetime',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceipt::class, 'warehouse_receipt_id');
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WarehouseReceiptItemPhoto::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(WarehouseReceiptItemLabel::class);
    }

    public function sortBatchItems(): HasMany
    {
        return $this->hasMany(SortBatchItem::class);
    }

    public function activeSortBatchItem(): HasMany
    {
        return $this->hasMany(SortBatchItem::class)->whereNull('removed_at');
    }
}

