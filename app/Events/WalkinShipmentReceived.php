<?php

namespace App\Events;

use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalkinShipmentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly Warehouse $warehouse,
        public readonly User $receivedBy,
    ) {}
}
