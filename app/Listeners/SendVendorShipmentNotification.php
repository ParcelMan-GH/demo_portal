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
            'pickup_assigned'   => ["Driver Assigned — {$shipmentNumber}", 'A driver has been assigned to pick up your shipment.'],
            'picked_up'         => ["Shipment Picked Up — {$shipmentNumber}", 'Your shipment has been collected by the driver.'],
            'at_warehouse'      => ["Shipment at Warehouse — {$shipmentNumber}", 'Your shipment has arrived at the warehouse.'],
            'sorted'            => ["Shipment Sorted — {$shipmentNumber}", 'Your shipment has been sorted and is ready for dispatch.'],
            'in_transit'        => ["In Transit — {$shipmentNumber}", 'Your shipment is in transit to the destination.'],
            'out_for_delivery'  => ["Out for Delivery — {$shipmentNumber}", 'Your shipment is out for delivery.'],
            'at_destination'    => ["Arrived at Destination — {$shipmentNumber}", 'Your shipment has arrived at the destination hub.'],
            'delivered'         => ["Delivered — {$shipmentNumber}", 'Your shipment has been successfully delivered.'],
            'cancelled'         => ["Shipment Cancelled — {$shipmentNumber}", 'Your shipment has been cancelled.'],
            default             => [null, null],
        };
    }
}
