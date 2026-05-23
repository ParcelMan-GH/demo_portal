<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRunItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_HANDED_OFF = 'handed_off';

    protected $fillable = [
        'delivery_run_id',
        'delivery_run_stop_id',
        'shipment_item_id',
        'expected_quantity',
        'delivered_quantity',
        'status',
        'notes',
        'delivered_at',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'delivered_quantity' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class, 'delivery_run_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(DeliveryRunStop::class, 'delivery_run_stop_id');
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function busHandoffConfirmation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BusHandoffConfirmation::class, 'delivery_run_item_id');
    }
}
