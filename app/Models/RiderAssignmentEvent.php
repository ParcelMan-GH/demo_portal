<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderAssignmentEvent extends Model
{
    public const EVENT_ASSIGNED = 'assigned';

    public const EVENT_REASSIGNED = 'reassigned';

    public const EVENT_UNASSIGNED = 'unassigned';

    /**
     * A shipment's derived pickup coverage moved (e.g. unassigned → partially).
     * Recorded against the assignment whose change caused it, so coverage
     * history lives on the same audit trail as the assignment history.
     */
    public const EVENT_COVERAGE_CHANGED = 'coverage_changed';

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
