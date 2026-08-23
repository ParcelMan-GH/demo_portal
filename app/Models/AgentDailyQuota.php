<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDailyQuota extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tracking_date'   => 'date',
        'is_kumasi_agent' => 'boolean',
        'is_unlocked'     => 'boolean',
        'overridden_at'   => 'datetime',
    ];

    // The agent who owns this quota
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // The admin who authorized an early payout
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by_id');
    }

    // Helper to check if they cleared their list naturally
    public function hasClearedList(): bool
    {
        return $this->assigned_tasks > 0 && $this->completed_tasks >= $this->assigned_tasks;
    }
}