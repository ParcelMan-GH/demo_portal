<?php

namespace App\Observers;

use App\Events\PickupAssignmentStatusChanged;
use App\Models\PickupAssignment;

class PickupAssignmentObserver
{
    public function updating(PickupAssignment $assignment): void
    {
        if ($assignment->isDirty('status')) {
            $oldStatus = $assignment->getOriginal('status');
            $newStatus = $assignment->status;

            // Convert enum to value if needed
            $oldValue = $oldStatus instanceof \BackedEnum ? $oldStatus->value : (string) $oldStatus;
            $newValue = $newStatus instanceof \BackedEnum ? $newStatus->value : (string) $newStatus;

            if ($oldValue !== $newValue) {
                event(new PickupAssignmentStatusChanged($assignment, $oldValue, $newValue));
            }
        }
    }
}
