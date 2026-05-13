<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipientPaymentSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'payment_wallet_id',
        'opening_balance',
        'closing_balance',
        'expected_closing_balance',
        'variance',
        'status',
        'started_at',
        'closed_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'variance' => 'decimal:2',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function paymentWallet(): BelongsTo
    {
        return $this->belongsTo(PaymentWallet::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RecipientPaymentSessionEntry::class);
    }
}
