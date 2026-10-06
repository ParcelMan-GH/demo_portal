<?php

namespace App\Listeners;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Listeners\Concerns\SendsShipmentSms;
use App\Models\Shipment;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

/**
 * Texts on shipment-level status transitions.
 *
 * Coverage, after the vendor-notification audit:
 *
 *   picked_up / in_transit  -> CUSTOMER and VENDOR
 *   at_destination          -> VENDOR   ("arrived at hub")
 *   sorted                  -> VENDOR
 *   out_for_delivery        -> CUSTOMER and VENDOR (both name the rider)
 *   delivered               -> CUSTOMER and VENDOR
 *   cancelled / rejected    -> VENDOR
 *
 * The vendor column used to be a single entry: `delivered`. A vendor therefore
 * heard nothing about their own parcel until the very end — not when it was
 * collected, not when it reached the destination, not when it went out. The audit
 * found the same hole on the parcel-level leg, which is what
 * {@see SendVendorItemStatusNotification} fixes; between them the two listeners
 * cover every status a vendor can act on.
 *
 * Customer texts are unchanged: this listener widening the vendor's coverage does
 * not mean texting the recipient more often.
 *
 * ShipmentStatusChanged only fires when the status actually changes, and the trait
 * de-duplicates against sms_logs, so a replayed event cannot text anyone twice.
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
                    // The vendor is the sender here, and "your parcel has been
                    // collected" is the first thing they want confirmed. Until now
                    // they only ever heard about the *last* leg — `delivered` — so a
                    // shipment could be collected, sorted and in transit with the
                    // vendor app showing nothing since submission.
                    $this->textVendor(
                        $shipment,
                        "Parcel #{$shipmentNumber} has been picked up and is on its way."
                    );
                    break;

                case ShipmentStatus::AT_DESTINATION->value:
                    // "Arrived at Hub" from the vendor's point of view.
                    $this->textVendor(
                        $shipment,
                        "Parcel #{$shipmentNumber} has arrived at the destination hub."
                    );
                    break;

                case ShipmentStatus::SORTED->value:
                    $this->textVendor(
                        $shipment,
                        "Parcel #{$shipmentNumber} has been sorted and is ready for delivery."
                    );
                    break;

                case ShipmentStatus::OUT_FOR_DELIVERY->value:
                    $assignment = $this->latestActivePickupAssignment($shipment);
                    if (! $assignment) {
                        /*
                         * No rider to name. The customer text cannot be rendered
                         * without one, but the vendor's does not depend on the rider
                         * at all — so the vendor is still told, and `break` rather
                         * than `return` so the rest of the listener is not skipped.
                         */
                        $this->textVendor(
                            $shipment,
                            "Parcel #{$shipmentNumber} is out for delivery."
                        );
                        break;
                    }
                    $riderName = $assignment->driver?->name ?? 'your rider';
                    $riderPhone = $assignment->driver?->phone ?? 'N/A';
                    $this->textCustomers(
                        $shipment,
                        "Your parcel #{$shipmentNumber} is out for delivery with rider {$riderName} ({$riderPhone})."
                    );
                    $this->textVendor(
                        $shipment,
                        "Parcel #{$shipmentNumber} is out for delivery with rider {$riderName} ({$riderPhone})."
                    );
                    break;

                case ShipmentStatus::DELIVERED->value:
                    $message = "Parcel #{$shipmentNumber} has been successfully delivered. Thank you for using Parcelman Express!";
                    $this->textCustomers($shipment, $message);
                    $this->sendShipmentSms($shipment, $this->shipmentVendorPhone($shipment), $message);
                    break;

                case ShipmentStatus::CANCELLED->value:
                case ShipmentStatus::REJECTED->value:
                    // The two statuses a vendor most needs to hear about and, with
                    // `submitted`/`processing`, the two that previously produced no
                    // text at all.
                    $this->textVendor(
                        $shipment,
                        "Parcel #{$shipmentNumber} was {$event->newStatus}. Please check the details and contact support."
                    );
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

    /**
     * Text the vendor who sent the parcel.
     *
     * One recipient, addressed through the same idempotency guard as everything
     * else in this trait: the pair (vendor phone, rendered message) is what
     * sms_logs de-duplicates on, so a replayed event cannot text them twice.
     */
    private function textVendor(Shipment $shipment, string $message): void
    {
        $this->sendShipmentSms($shipment, $this->shipmentVendorPhone($shipment), $message);
    }
}
