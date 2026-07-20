<?php

namespace App\Services;

use App\Models\RiderAssignmentEvent;
use App\Models\User;

class RiderAssignmentAuditService
{
    public function record(
        string $jobType,
        int $jobId,
        string $eventType,
        ?int $previousDriverId,
        ?int $driverId,
        ?User $actor = null,
        ?string $reason = null,
    ): RiderAssignmentEvent {
        return RiderAssignmentEvent::query()->create([
            'job_type' => $jobType,
            'job_id' => $jobId,
            'event_type' => $eventType,
            'previous_driver_id' => $previousDriverId,
            'driver_id' => $driverId,
            'performed_by_user_id' => $actor?->id,
            'reason' => $reason ?: null,
        ]);
    }
}
