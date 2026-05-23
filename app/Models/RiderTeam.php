<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiderTeam extends Model
{
    protected $fillable = [
        'name',
        'warehouse_id',
        'zone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(RiderTeamMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('is_active', true)->whereNull('removed_at');
    }

    public function leaders(): HasMany
    {
        return $this->activeMemberships()->where('role', RiderTeamMembership::ROLE_LEADER);
    }

    public function members(): HasMany
    {
        return $this->activeMemberships()->where('role', RiderTeamMembership::ROLE_MEMBER);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(RiderTeamHandover::class);
    }
}
