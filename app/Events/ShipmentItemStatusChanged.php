<?php

namespace App\Events;

use App\Models\ShipmentItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A parcel's own status changed — the hub leg.
 *
 * ## Why this exists
 *
 * `ShipmentStatusChanged` fires from `ShipmentObserver`, i.e. only when the
 * *shipment's* status changes. But the statuses that describe the part of the
 * journey the vendor most wants to hear about — arrived at hub, dispatched to bus,
 * out for delivery, delivered — live on `ShipmentItem`, and `HubController` writes
 * them without ever touching the parent shipment's status.
 *
 * The practical result was that a parcel could be checked in at a hub, put on a
 * bus, released to a rider, and collected by the customer, and the vendor was told
 * nothing at any of those steps — no notification row, no SMS. The vendor app's
 * only signal that anything had happened was the customer calling to say thanks.
 *
 * So the item leg gets its own event. It is emitted by `ShipmentItemObserver`
 * rather than by each controller, so every writer is covered — hub intake, bus
 * dispatch, hub release, rider scans, and any admin path — instead of the three
 * places someone remembered to add a `notify()` call.
 *
 * ## After commit
 *
 * Same reason as `ShipmentStatusChanged`: firing inside the caller's transaction
 * meant a rolled-back check-in had already texted the vendor about a parcel that
 * was not there. A scan that fails must not announce itself.
 */
class ShipmentItemStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ShipmentItem $item,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
