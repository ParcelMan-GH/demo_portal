<?php
namespace App\Observers;
use App\Events\ShipmentStatusChanged;
use App\Models\Shipment;

class ShipmentObserver
{
    public function updating(Shipment $shipment): void
    {
        if ($shipment->isDirty('status')) {
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
