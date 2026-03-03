<?php

namespace App\Events;

use App\Models\PickupAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PickupAssignmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PickupAssignment $assignment,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
