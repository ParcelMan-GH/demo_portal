<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipientPaymentGroup extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'group_key',
        'payment_group',
        'warehouse_id',
        'assigned_to_user_id',
        'primary_task_id',
        'shipment_charge_id',
        'payment_wallet_id',
        'recipient_name',
        'recipient_phone',
        'delivery_town',
        'amount',
        'currency',
        'status',
        'payment_reference',
        'receipt_path',
        'paid_at',
        'created_by_user_id',
        'paid_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(RecipientPaymentTask::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function primaryTask(): BelongsTo
    {
        return $this->belongsTo(RecipientPaymentTask::class, 'primary_task_id');
    }

    public function shipmentCharge(): BelongsTo
    {
        return $this->belongsTo(ShipmentCharge::class);
    }

    public function paymentWallet(): BelongsTo
    {
        return $this->belongsTo(PaymentWallet::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function sessionEntries(): HasMany
    {
        return $this->hasMany(RecipientPaymentSessionEntry::class);
    }
}
