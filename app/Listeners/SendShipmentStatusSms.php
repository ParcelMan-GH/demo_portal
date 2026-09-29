<?php

namespace App\Listeners;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Listeners\Concerns\SendsShipmentSms;
use App\Models\Shipment;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

/**
 * Hooks 2-4 — customer/vendor texts on shipment status transitions:
 *
 *   picked_up / in_transit  -> CUSTOMER
 *   out_for_delivery        -> CUSTOMER (names the assigned rider)
 *   delivered               -> BOTH the vendor and the customer
 *
 * ShipmentStatusChanged only fires when the status actually changes, and the
 * trait additionally de-duplicates against sms_logs, so a replayed event cannot
 * text anyone twice.
 */
class SendShipmentStatusSms
{
    use SendsShipmentSms;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(ShipmentStatusChanged $event): void
    {
        try {
            $shipment = $event->shipment->loadMissing('vendor');
            $shipmentNumber = $shipment->shipment_number ?? 'N/A';

            switch ($event->newStatus) {
                case ShipmentStatus::PICKED_UP->value:
                case ShipmentStatus::IN_TRANSIT->value:
                    $vendorName = $shipment->vendor?->business_name ?: ($shipment->vendor?->name ?: 'your vendor');
                    $this->textCustomers(
                        $shipment,
                        "Your package #{$shipmentNumber} from {$vendorName} has been picked up by Parcelman and is on its way!"
                    );
                    break;

                case ShipmentStatus::OUT_FOR_DELIVERY->value:
                    $assignment = $this->latestActivePickupAssignment($shipment);
                    if (! $assignment) {
                        // No rider to name, so the message cannot be rendered.
                        return;
                    }
                    $riderName = $assignment->driver?->name ?? 'your rider';
                    $riderPhone = $assignment->driver?->phone ?? 'N/A';
                    $this->textCustomers(
                        $shipment,
                        "Your parcel #{$shipmentNumber} is out for delivery with rider {$riderName} ({$riderPhone})."
                    );
                    break;

                case ShipmentStatus::DELIVERED->value:
                    $message = "Parcel #{$shipmentNumber} has been successfully delivered. Thank you for using Parcelman Express!";
                    $this->textCustomers($shipment, $message);
                    $this->sendShipmentSms($shipment, $this->shipmentVendorPhone($shipment), $message);
                    break;
            }
        } catch (\Throwable $e) {
            Log::warning('Shipment status SMS listener failed', [
                'event' => 'ShipmentStatusChanged',
                'status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function textCustomers(Shipment $shipment, string $message): void
    {
        foreach ($this->shipmentCustomerPhones($shipment) as $phone) {
            $this->sendShipmentSms($shipment, $phone, $message);
        }
    }
}
