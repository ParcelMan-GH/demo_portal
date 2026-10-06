<?php

namespace App\Listeners;

use App\Enums\ItemStatus;
use App\Events\ShipmentItemStatusChanged;
use App\Listeners\Concerns\SendsShipmentSms;
use App\Services\PushNotificationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

/**
 * Tells the vendor about the hub leg: both an inbox row and a text.
 *
 * Before this, the parcel-level statuses produced neither. `ShipmentStatusChanged`
 * fires only for the shipment's own status, and nothing in the hub path updates
 * that — `HubController` writes `shipment_items.status` and no more. So a vendor
 * shipping to a destination hub was silent from intake to collection.
 *
 * ## Both channels, one listener
 *
 * The requirement is that a vendor gets an in-app alert *and* an SMS for each
 * transition, so they are done together rather than split across two listeners
 * where one can be registered and the other forgotten. `sendToVendor` writes the
 * inbox row even when the vendor has no FCM token — the token decides whether to
 * also push, not whether the event is worth recording — so an offline vendor still
 * finds the history waiting in the app.
 *
 * ## The overlap with the shipment-level SMS, stated plainly
 *
 * `SendShipmentStatusSms` already texts the vendor about shipment-level
 * transitions. Two of the item statuses here share a name with a shipment status
 * (`out_for_delivery`, `delivered`). When the parent shipment's status has already
 * reached the same value, the shipment listener is the one telling the vendor, and
 * this listener skips the SMS to avoid texting them twice about one event. In the
 * hub-collection path the parent status is never updated at all, which is the case
 * this listener exists for — so there it always sends.
 */
class SendVendorItemStatusNotification
{
    use SendsShipmentSms;

    public function __construct(
        private readonly PushNotificationService $pushService,
        SmsService $smsService,
    ) {
        $this->smsService = $smsService;
    }

    public function handle(ShipmentItemStatusChanged $event): void
    {
        try {
            $item = $event->item->loadMissing('shipment.vendor');
            $shipment = $item->shipment;
            $vendor = $shipment?->vendor;

            if (! $vendor) {
                return;
            }

            $message = $this->buildMessage($event->newStatus, $shipment->shipment_number, $item->tracking_code);

            if ($message === null) {
                // A status with nothing worth saying — an internal step, or one the
                // vendor cannot act on. Returning here is deliberate: inventing
                // filler for every enum value is how a vendor learns to ignore the
                // notifications entirely.
                return;
            }

            [$title, $body] = $message;

            $this->pushService->sendToVendor(
                vendor: $vendor,
                title: $title,
                body: $body,
                data: [
                    'shipment_id' => (string) $shipment->id,
                    'shipment_number' => (string) $shipment->shipment_number,
                    'shipment_item_id' => (string) $item->id,
                    'tracking_code' => (string) $item->tracking_code,
                    'status' => $event->newStatus,
                    'scope' => 'item',
                ],
                type: 'shipment_item_status'
            );

            // Suppressed only where the shipment-level listener is also speaking.
            if ($this->shipmentListenerCovers($event->newStatus, $shipment?->status)) {
                return;
            }

            $this->sendShipmentSms($shipment, $this->shipmentVendorPhone($shipment), $body);
        } catch (\Throwable $e) {
            // A notification must never break the scan or the release that caused
            // it. The parcel has moved; the message is best-effort.
            Log::warning('Vendor item-status notification failed', [
                'event' => 'ShipmentItemStatusChanged',
                'shipment_item_id' => $event->item->id ?? null,
                'status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Whether `SendShipmentStatusSms` owns the text for this transition.
     *
     * True only for the two statuses the two events share a name with, and only
     * when the parent shipment really has reached that value — which is what makes
     * the shipment listener fire.
     */
    private function shipmentListenerCovers(string $newStatus, mixed $shipmentStatus): bool
    {
        if (! in_array($newStatus, [ItemStatus::OUT_FOR_DELIVERY->value, ItemStatus::DELIVERED->value], true)) {
            return false;
        }

        $parent = $shipmentStatus instanceof \BackedEnum ? $shipmentStatus->value : (string) $shipmentStatus;

        return $parent === $newStatus;
    }

    /**
     * The vendor-facing wording, or null when there is nothing to say.
     *
     * @return array{0: string, 1: string}|null
     */
    private function buildMessage(string $status, ?string $shipmentNumber, ?string $trackingCode): ?array
    {
        $number = $shipmentNumber ?: 'N/A';
        $parcel = $trackingCode ? " (parcel {$trackingCode})" : '';

        return match ($status) {
            ItemStatus::READY_FOR_HUB_TRANSFER->value => [
                "Parcel Ready for Transfer — {$number}",
                "Your parcel{$parcel} is ready to be transferred to the destination hub.",
            ],
            ItemStatus::ARRIVED_AT_HUB->value => [
                "Arrived at Hub — {$number}",
                "Your parcel{$parcel} has arrived at the destination hub and is ready for collection.",
            ],
            ItemStatus::DISPATCHED_TO_BUS->value => [
                "Dispatched — {$number}",
                "Your parcel{$parcel} has been dispatched and is on its way to the destination hub.",
            ],
            ItemStatus::IN_TRANSIT->value => [
                "Parcel in Transit — {$number}",
                "Your parcel{$parcel} is in transit.",
            ],
            ItemStatus::AT_DESTINATION->value => [
                "At Destination — {$number}",
                "Your parcel{$parcel} has reached its destination.",
            ],
            ItemStatus::OUT_FOR_DELIVERY->value => [
                "Out for Delivery — {$number}",
                "Your parcel{$parcel} is out for delivery.",
            ],
            ItemStatus::HANDED_TO_COURIER->value => [
                "Handed to Courier — {$number}",
                "Your parcel{$parcel} has been handed to the courier.",
            ],
            ItemStatus::DELIVERED->value => [
                "Parcel Delivered — {$number}",
                "Your parcel{$parcel} has been delivered. Thank you for using Parcelman Express!",
            ],
            ItemStatus::RETURNED->value => [
                "Parcel Returned — {$number}",
                "Your parcel{$parcel} has been returned. Please check the details and contact support.",
            ],
            default => null,
        };
    }
}
