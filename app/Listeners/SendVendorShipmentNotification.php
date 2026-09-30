<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Services\PushNotificationService;

class SendVendorShipmentNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment;
        $vendor = $shipment->vendor;

        /*
         * Only a missing vendor stops this now.
         *
         * The `!$vendor->fcm_token` arm used to be here too, and it was the
         * reason a vendor with the app installed saw nothing happen: no push
         * token meant no push *and* — because the service also returned early —
         * no inbox row either. The token check belongs in the service, where it
         * decides between pushing and merely recording; this listener's job is
         * only to decide whether there is anything worth saying.
         */
        if (!$vendor) {
            return;
        }

        [$title, $body] = $this->buildMessage($event->newStatus, $shipment->shipment_number);

        if (!$title) {
            return;
        }

        $this->pushService->sendToVendor(
            vendor: $vendor,
            title: $title,
            body: $body,
            data: [
                'shipment_id'     => (string) $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'status'          => $event->newStatus,
            ],
            type: 'shipment_status'
        );
    }

    /**
     * The vendor-facing wording for each shipment status.
     *
     * Every value of {@see \App\Enums\ShipmentStatus} that represents real
     * progress now has a line. `submitted`, `processing`, `handed_to_courier`
     * and `rejected` previously fell through to `default => [null, null]`, so
     * those transitions produced no notification at all — the vendor's shipment
     * could be rejected and the app would stay silent about it.
     *
     * `draft` is deliberately absent: that is the vendor's own unpublished work,
     * not news. Statuses that live on `ShipmentItem` rather than `Shipment`
     * (`arrived_at_hub`, `dispatched_to_bus`, `ready_for_hub_transfer`) never
     * reach this listener, because `ShipmentStatusChanged` only fires for the
     * shipment's own status; the hub leg has its own SMS paths.
     */
    private function buildMessage(string $status, string $shipmentNumber): array
    {
        return match ($status) {
            'submitted'         => ["Shipment Submitted — {$shipmentNumber}", 'We have received your shipment and it is awaiting processing.'],
            'processing'        => ["Shipment Processing — {$shipmentNumber}", 'Your shipment is being processed.'],
            'pickup_assigned'   => ["Rider Assigned — {$shipmentNumber}", 'A rider has been assigned to pick up your parcel.'],
            'picked_up'         => ["Parcel Picked Up — {$shipmentNumber}", 'Your parcel has been collected by the rider.'],
            'at_warehouse'      => ["Parcel at Warehouse — {$shipmentNumber}", 'Your parcel has arrived at the warehouse.'],
            'sorted'            => ["Parcel Ready for Delivery — {$shipmentNumber}", 'Your parcel has been sorted and is ready for delivery.'],
            'in_transit'        => ["Parcel in Transit — {$shipmentNumber}", 'Your parcel is in transit to the destination.'],
            'at_destination'    => ["Arrived at Destination — {$shipmentNumber}", 'Your parcel has arrived at the destination hub.'],
            'out_for_delivery'  => ["Out for Delivery — {$shipmentNumber}", 'Your parcel is out for delivery.'],
            'handed_to_courier' => ["Handed to Courier — {$shipmentNumber}", 'Your parcel has been handed to the courier for final delivery.'],
            'delivered'         => ["Parcel Delivered — {$shipmentNumber}", 'Your parcel has been successfully delivered.'],
            'cancelled'         => ["Parcel Cancelled — {$shipmentNumber}", 'Your parcel has been cancelled.'],
            'rejected'          => ["Shipment Rejected — {$shipmentNumber}", 'Your shipment was rejected. Please check the details and contact support.'],
            default             => [null, null],
        };
    }
}
