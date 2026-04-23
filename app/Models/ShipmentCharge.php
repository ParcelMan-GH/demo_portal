<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentCharge extends Model
{
    use SoftDeletes;

    // Charge types
    public const TYPE_PICKUP_FEE   = 'pickup_fee';
    public const TYPE_DELIVERY_FEE = 'delivery_fee';
    public const TYPE_STATION_FEE  = 'station_fee';
    public const TYPE_HANDLING_FEE = 'handling_fee';
    public const TYPE_OTHER        = 'other';

    public const TYPES = [
        self::TYPE_PICKUP_FEE,
        self::TYPE_DELIVERY_FEE,
        self::TYPE_STATION_FEE,
        self::TYPE_HANDLING_FEE,
        self::TYPE_OTHER,
    ];

    // Who pays
    public const PAYER_VENDOR    = 'vendor';
    public const PAYER_RECIPIENT = 'recipient';
    public const PAYER_PARCELMAN = 'parcelman';

    public const PAYERS = [self::PAYER_VENDOR, self::PAYER_RECIPIENT, self::PAYER_PARCELMAN];

    // Direction — derived from payer
    public const DIRECTION_REVENUE = 'revenue';
    public const DIRECTION_EXPENSE = 'expense';

    // Due stages — which milestone this charge is tied to
    public const STAGE_AT_PICKUP        = 'at_pickup';
    public const STAGE_AT_RECEIVING     = 'at_receiving';
    public const STAGE_BEFORE_DELIVERY  = 'before_delivery';
    public const STAGE_AT_DELIVERY      = 'at_delivery';
    public const STAGE_AT_HANDOFF       = 'at_handoff';

    public const DUE_STAGES = [
        self::STAGE_AT_PICKUP,
        self::STAGE_AT_RECEIVING,
        self::STAGE_BEFORE_DELIVERY,
        self::STAGE_AT_DELIVERY,
        self::STAGE_AT_HANDOFF,
    ];

    // Status lifecycle
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_WAIVED    = 'waived';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_WAIVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'shipment_id',
        'shipment_item_id',
        'charge_type',
        'payer_type',
        'direction',
        'due_stage',
        'amount',
        'currency',
        'status',
        'paid_at',
        'payment_method',
        'payment_reference',
        'recorded_by_admin_id',
        'recorded_by_driver_id',
        'delivery_run_stop_id',
        'pickup_assignment_id',
        'notes',
        'waive_reason',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Payer → direction mapping. Parcelman paying out = expense; everyone else pays us = revenue.
     */
    public static function directionFor(string $payerType): string
    {
        return $payerType === self::PAYER_PARCELMAN
            ? self::DIRECTION_EXPENSE
            : self::DIRECTION_REVENUE;
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function recordedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_admin_id');
    }

    public function recordedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'recorded_by_driver_id');
    }

    public function pickupAssignment(): BelongsTo
    {
        return $this->belongsTo(PickupAssignment::class);
    }

    public function deliveryRunStop(): BelongsTo
    {
        return $this->belongsTo(DeliveryRunStop::class);
    }

    public function isRevenue(): bool
    {
        return $this->direction === self::DIRECTION_REVENUE;
    }

    public function isExpense(): bool
    {
        return $this->direction === self::DIRECTION_EXPENSE;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOutstanding(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING], true);
    }
}
