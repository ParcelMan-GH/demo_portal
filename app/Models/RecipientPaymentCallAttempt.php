<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientPaymentCallAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'recipient_payment_task_id',
        'attempted_by_user_id',
        'outcome',
        'notes',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(RecipientPaymentTask::class, 'recipient_payment_task_id');
    }

    public function attemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attempted_by_user_id');
    }
}
