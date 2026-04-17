<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageContactAttempt extends Model
{
    public $timestamps = false;

    public const OUTCOME_ANSWERED = 'answered';
    public const OUTCOME_NO_ANSWER = 'no_answer';
    public const OUTCOME_BUSY = 'busy';
    public const OUTCOME_WRONG_NUMBER = 'wrong_number';
    public const OUTCOME_VOICEMAIL = 'voicemail';

    protected $fillable = [
        'contact_task_id',
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
        return $this->belongsTo(PackageContactTask::class, 'contact_task_id');
    }

    public function attemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attempted_by_user_id');
    }
}
