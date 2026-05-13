<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentWallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'name',
        'provider',
        'phone_number',
        'account_owner',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'payment_wallet_user')->withTimestamps();
    }

    public function recipientPaymentTasks(): HasMany
    {
        return $this->hasMany(RecipientPaymentTask::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RecipientPaymentSession::class);
    }
}
