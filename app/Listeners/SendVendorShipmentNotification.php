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

        if (!$vendor || !$vendor->fcm_token) {
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

    private function buildMessage(string $status, string $shipmentNumber): array
    {
        return match ($status) {
            'pickup_assigned'   => ["Rider Assigned — {$shipmentNumber}", 'A rider has been assigned to pick up your parcel.'],
            'picked_up'         => ["Parcel Picked Up — {$shipmentNumber}", 'Your parcel has been collected by the rider.'],
            'at_warehouse'      => ["Parcel at Warehouse — {$shipmentNumber}", 'Your parcel has arrived at the warehouse.'],
            'sorted'            => ["Parcel Sorted — {$shipmentNumber}", 'Your parcel has been sorted and is ready for dispatch.'],
            'in_transit'        => ["Parcel in Transit — {$shipmentNumber}", 'Your parcel is in transit to the destination.'],
            'out_for_delivery'  => ["Out for Delivery — {$shipmentNumber}", 'Your parcel is out for delivery.'],
            'at_destination'    => ["Arrived at Destination — {$shipmentNumber}", 'Your parcel has arrived at the destination hub.'],
            'delivered'         => ["Parcel Delivered — {$shipmentNumber}", 'Your parcel has been successfully delivered.'],
            'cancelled'         => ["Parcel Cancelled — {$shipmentNumber}", 'Your parcel has been cancelled.'],
            default             => [null, null],
        };
    }
}
