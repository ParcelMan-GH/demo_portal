<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipientPaymentTask extends Model
{
    public const GROUP_LOCAL_DELIVERY = 'local_delivery';
    public const GROUP_WAREHOUSE_TRANSFER = 'warehouse_transfer';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PAID = 'paid';
    public const STATUS_WAIVED = 'waived';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_OVERRIDDEN = 'overridden';

    protected $fillable = [
        'recipient_payment_group_id',
        'shipment_item_id',
        'shipment_id',
        'shipment_charge_id',
        'sort_batch_id',
        'sort_batch_item_id',
        'warehouse_id',
        'assigned_to_user_id',
        'assigned_at',
        'payment_group',
        'status',
        'recipient_name',
        'recipient_phone',
        'delivery_town',
        'negotiated_amount',
        'currency',
        'paid_at',
        'payment_wallet_id',
        'payment_reference',
        'notes',
        'override_by_user_id',
        'override_at',
        'override_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'negotiated_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'override_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function paymentGroupRecord(): BelongsTo
    {
        return $this->belongsTo(RecipientPaymentGroup::class, 'recipient_payment_group_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function shipmentCharge(): BelongsTo
    {
        return $this->belongsTo(ShipmentCharge::class);
    }

    public function sortBatch(): BelongsTo
    {
        return $this->belongsTo(SortBatch::class);
    }

    public function sortBatchItem(): BelongsTo
    {
        return $this->belongsTo(SortBatchItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function paymentWallet(): BelongsTo
    {
        return $this->belongsTo(PaymentWallet::class);
    }

    public function callAttempts(): HasMany
    {
        return $this->hasMany(RecipientPaymentCallAttempt::class)->orderByDesc('attempted_at');
    }

    public function sessionEntries(): HasMany
    {
        return $this->hasMany(RecipientPaymentSessionEntry::class);
    }

    public function isCleared(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_WAIVED, self::STATUS_OVERRIDDEN], true);
    }
}
