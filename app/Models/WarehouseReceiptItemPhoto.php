<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseReceiptItemPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_receipt_item_id',
        'path',
        'original_name',
        'size',
        'photo_type',
        'created_by_user_id',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function receiptItem(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptItem::class, 'warehouse_receipt_item_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

