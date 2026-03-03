<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\TransportManifest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverUnassignedFromTransport
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TransportManifest $manifest,
        public readonly Driver $driver,
    ) {}
}
