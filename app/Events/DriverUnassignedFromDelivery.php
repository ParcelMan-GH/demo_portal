<?php

namespace App\Events;

use App\Models\DeliveryRun;
use App\Models\Driver;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverUnassignedFromDelivery implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DeliveryRun $run,
        public readonly Driver $driver,
        public readonly ?string $reason = null,
    ) {}
}
