<?php
namespace App\Observers;
use App\Events\ShipmentStatusChanged;
use App\Models\Shipment;

class ShipmentObserver
{
    /**
     * `updated`, not `updating`.
     *
     * Firing while the write was still pending meant the event described a change
     * that had not happened yet: a failed write, or a transaction that later
     * rolled back, had already texted and emailed the customer about a status the
     * database never kept. `updated` runs only once the row is really written, and
     * `getOriginal('status')` still holds the previous value here because
     * `syncOriginal()` runs after the event.
     */
    public function updated(Shipment $shipment): void
    {
        // `wasChanged`, not `isDirty`: the observer now runs after the write.
        if ($shipment->wasChanged('status')) {
            $oldStatus = $shipment->getOriginal('status');
            $newStatus = $shipment->status;

            // Normalize to string value
            $oldStatusValue = $oldStatus instanceof \BackedEnum ? $oldStatus->value : (string) $oldStatus;
            $newStatusValue = $newStatus instanceof \BackedEnum ? $newStatus->value : (string) $newStatus;

            if ($oldStatusValue !== $newStatusValue) {
                event(new ShipmentStatusChanged($shipment, $oldStatusValue, $newStatusValue));
            }
        }
    }
}
