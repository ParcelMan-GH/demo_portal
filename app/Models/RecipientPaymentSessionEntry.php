<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientPaymentSessionEntry extends Model
{
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'recipient_payment_session_id',
        'recipient_payment_task_id',
        'recipient_payment_group_id',
        'shipment_charge_id',
        'entry_type',
        'amount',
        'currency',
        'reference',
        'receipt_path',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RecipientPaymentSession::class, 'recipient_payment_session_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(RecipientPaymentTask::class, 'recipient_payment_task_id');
    }

    public function paymentGroup(): BelongsTo
    {
        return $this->belongsTo(RecipientPaymentGroup::class, 'recipient_payment_group_id');
    }

    public function shipmentCharge(): BelongsTo
    {
        return $this->belongsTo(ShipmentCharge::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
