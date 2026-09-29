<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a shipment's status actually changes.
 *
 * Deferred until the surrounding transaction commits, matching
 * DriverAssignedToPickup. Without this the listeners ran inside whatever
 * transaction the caller had open, so a transition that was rolled back had
 * already texted and emailed the customer about a status the database never
 * kept. A shipment that is not delivered must not announce that it was.
 */
class ShipmentStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
