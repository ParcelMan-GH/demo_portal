<?php

namespace App\Events;

use App\Models\Shipment;
use App\Models\ShipmentCollection;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentCollected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ShipmentCollection $collection,
        public readonly User $handedOverBy,
    ) {}
}
