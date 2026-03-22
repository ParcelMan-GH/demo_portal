<?php

namespace App\Events;

use App\Models\Shipment;
use App\Models\ShipmentCollection;
use App\Models\Warehouse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentReadyForCollection
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ShipmentCollection $collection,
        public readonly Warehouse $warehouse,
    ) {}
}
