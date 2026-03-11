<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportManifestAssignment extends Model
{
    protected $fillable = [
        'transport_manifest_id',
        'driver_id',
        'assigned_by_user_id',
        'assigned_at',
        'unassigned_at',
        'unassigned_by_user_id',
        'unassign_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class, 'transport_manifest_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function unassignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unassigned_by_user_id');
    }
}
