<?php

namespace App\Listeners;

use App\Events\DriverAssignedToPickup;
use App\Listeners\Concerns\SendsShipmentSms;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

/**
 * Hook 1 — a rider has been assigned to pick up a parcel. Texts the VENDOR the
 * rider's name and number.
 *
 * Fires on every DriverAssignedToPickup, including a reassignment, because the
 * vendor's useful information is "who is coming", which changes with the rider.
 */
class SendRiderAssignedSms
{
    use SendsShipmentSms;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(DriverAssignedToPickup $event): void
    {
        try {
            $assignment = $event->assignment->loadMissing('shipment.vendor');
            $shipment = $assignment->shipment;

            if (! $shipment) {
                return;
            }

            $driver = $event->driver;
            $driverName = $driver?->name ?? 'A rider';
            $driverPhone = $driver?->phone ?? 'N/A';
            // {TrackingNo} is the shipment identifier every existing notification
            // sends: shipment_number.
            $shipmentNumber = $shipment->shipment_number ?? 'N/A';

            $message = "Rider {$driverName} ({$driverPhone}) has been assigned to pick up Parcel #{$shipmentNumber}.";

            $this->sendShipmentSms($shipment, $this->shipmentVendorPhone($shipment), $message);
        } catch (\Throwable $e) {
            Log::warning('Rider-assigned SMS listener failed', [
                'event' => 'DriverAssignedToPickup',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
