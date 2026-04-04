<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelCustodyEvent extends Model
{
    protected $fillable = [
        'warehouse_receipt_item_label_id',
        'event_type',
        'driver_id',
        'scanned_by_user_id',
        'location_note',
        'latitude',
        'longitude',
        'notes',
    ];

    const TYPE_CLAIMED = 'claimed';
    const TYPE_RELEASED = 'released';
    const TYPE_TRANSFERRED = 'transferred';
    const TYPE_DELIVERED = 'delivered';
    const TYPE_RETURNED = 'returned';

    public function label(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptItemLabel::class, 'warehouse_receipt_item_label_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'scanned_by_user_id');
    }
}
