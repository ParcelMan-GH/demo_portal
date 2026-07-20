<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderAssignmentEvent extends Model
{
    public const EVENT_ASSIGNED = 'assigned';

    public const EVENT_REASSIGNED = 'reassigned';

    public const EVENT_UNASSIGNED = 'unassigned';

    protected $fillable = [
        'job_type', 'job_id', 'event_type', 'previous_driver_id', 'driver_id',
        'performed_by_user_id', 'reason',
    ];

    public function previousDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'previous_driver_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
