<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\PickupAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverAssignedToPickup
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PickupAssignment $assignment,
        public readonly Driver $driver,
    ) {}
}
