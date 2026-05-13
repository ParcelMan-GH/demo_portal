<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SortBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort_batch_id',
        'shipment_item_id',
        'warehouse_receipt_item_id',
        'quantity_allocated',
        'added_by_user_id',
        'added_at',
        'removed_at',
    ];

    protected $casts = [
        'quantity_allocated' => 'integer',
        'added_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function sortBatch(): BelongsTo
    {
        return $this->belongsTo(SortBatch::class);
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function warehouseReceiptItem(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptItem::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    public function recipientPaymentTask(): HasOne
    {
        return $this->hasOne(RecipientPaymentTask::class);
    }
}
