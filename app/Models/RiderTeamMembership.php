<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderTeamMembership extends Model
{
    public const ROLE_LEADER = 'leader';
    public const ROLE_MEMBER = 'member';

    protected $fillable = [
        'rider_team_id',
        'driver_id',
        'role',
        'is_active',
        'added_by_type',
        'added_by_id',
        'joined_at',
        'removed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(RiderTeam::class, 'rider_team_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
