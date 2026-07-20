<?php

namespace App\Listeners;

use App\Enums\ShipmentStatus;
use App\Events\DeliveryRunStopStatusChanged;
use App\Events\DriverAssignedToPickup;
use App\Events\ShipmentReadyForCollection;
use App\Events\ShipmentStatusChanged;
use App\Events\VendorRegistered;
use App\Models\DeliveryRunStop;
use App\Models\EmailTemplate;
use App\Models\RecipientPaymentTask;
use App\Services\EmailTemplateService;
use Illuminate\Support\Carbon;

class SendCustomerEmailTemplateNotification
{
    public function __construct(private EmailTemplateService $emails) {}

    public function handle(
        VendorRegistered|ShipmentStatusChanged|DriverAssignedToPickup|ShipmentReadyForCollection|DeliveryRunStopStatusChanged $event
    ): void {
        match (true) {
            $event instanceof VendorRegistered => $this->vendorWelcome($event),
            $event instanceof ShipmentStatusChanged => $this->shipmentStatus($event),
            $event instanceof DriverAssignedToPickup => $this->pickupAssigned($event),
            $event instanceof ShipmentReadyForCollection => $this->collectionReady($event),
            $event instanceof DeliveryRunStopStatusChanged => $this->deliveryStatus($event),
            default => null,
        };
    }

    public function paymentRequired(RecipientPaymentTask $task): void
    {
        $task->loadMissing('shipment.vendor', 'shipmentItem');
        $vendor = $task->shipment?->vendor;

        if (!$vendor) {
            return;
        }

        $this->emails->send(EmailTemplate::PAYMENT_REQUIRED, $vendor, $this->paymentVariables($task), [
            'notifiable' => $task,
        ]);
    }

    public function paymentReceived(RecipientPaymentTask $task): void
    {
        $task->loadMissing('shipment.vendor', 'shipmentItem');
        $vendor = $task->shipment?->vendor;

        if (!$vendor) {
            return;
        }

        $this->emails->send(EmailTemplate::PAYMENT_RECEIVED, $vendor, $this->paymentVariables($task), [
            'notifiable' => $task,
        ]);
    }

    private function vendorWelcome(VendorRegistered $event): void
    {
        $this->emails->send(EmailTemplate::VENDOR_WELCOME, $event->vendor, [
            'vendor_name' => $this->vendorName($event->vendor),
            'login_url' => url('/vendor/login'),
        ]);
    }

    private function shipmentStatus(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment->loadMissing('vendor', 'pickupAssignment.targetWarehouse');
        $vendor = $shipment->vendor;

        if (!$vendor) {
            return;
        }

        $templateKey = match ($event->newStatus) {
            ShipmentStatus::SUBMITTED->value => EmailTemplate::SHIPMENT_SUBMITTED,
            ShipmentStatus::AT_WAREHOUSE->value => EmailTemplate::PACKAGE_AT_WAREHOUSE,
            ShipmentStatus::OUT_FOR_DELIVERY->value => EmailTemplate::DELIVERY_OUT_FOR_DELIVERY,
            ShipmentStatus::DELIVERED->value => EmailTemplate::DELIVERY_COMPLETED,
            default => null,
        };

        if (!$templateKey) {
            return;
        }

        $this->emails->send($templateKey, $vendor, $this->shipmentVariables($shipment));
    }

    private function pickupAssigned(DriverAssignedToPickup $event): void
    {
        if (!$event->notifyCustomer) {
            return;
        }

        $assignment = $event->assignment->loadMissing(['shipment.vendor', 'targetWarehouse']);
        $shipment = $assignment->shipment;
        $vendor = $shipment?->vendor;

        if (!$shipment || !$vendor) {
            return;
        }

        $this->emails->send(EmailTemplate::PICKUP_ASSIGNED, $vendor, array_merge(
            $this->shipmentVariables($shipment),
            [
                'driver_name' => $event->driver->name,
                'driver_phone' => $event->driver->phone,
                'warehouse_name' => $assignment->targetWarehouse?->name ?: 'the warehouse',
            ]
        ), ['notifiable' => $assignment]);
    }

    private function collectionReady(ShipmentReadyForCollection $event): void
    {
        $shipment = $event->shipment->loadMissing('vendor');
        $vendor = $shipment->vendor;

        if (!$vendor) {
            return;
        }

        $this->emails->send(EmailTemplate::PACKAGE_READY_FOR_COLLECTION, $vendor, array_merge(
            $this->shipmentVariables($shipment),
            [
                'recipient_name' => $shipment->delivery_recipient_name ?: $this->vendorName($vendor),
                'warehouse_name' => $event->warehouse->name,
                'warehouse_address' => $event->warehouse->address ?: $event->warehouse->name,
            ]
        ), ['notifiable' => $event->collection]);
    }

    private function deliveryStatus(DeliveryRunStopStatusChanged $event): void
    {
        $stop = $event->stop->loadMissing(['run.assignedDriver', 'items.shipmentItem.shipment.vendor']);
        $templateKey = match ($event->newStatus) {
            DeliveryRunStop::STATUS_DELIVERED => EmailTemplate::DELIVERY_COMPLETED,
            default => null,
        };

        if (!$templateKey) {
            return;
        }

        $shipment = $stop->items->first()?->shipmentItem?->shipment;
        $vendor = $shipment?->vendor;

        if (!$vendor) {
            return;
        }

        $this->emails->send($templateKey, $vendor, array_merge(
            $this->shipmentVariables($shipment),
            [
                'recipient_name' => $stop->recipient_name ?: $shipment->delivery_recipient_name ?: $this->vendorName($vendor),
                'run_number' => $stop->run?->run_number,
                'driver_name' => $stop->run?->assignedDriver?->name,
                'driver_phone' => $stop->run?->assignedDriver?->phone,
                'delivered_at' => Carbon::parse($stop->delivered_at ?: now())->format('d M Y, h:i A'),
            ]
        ), ['notifiable' => $stop]);
    }

    private function shipmentVariables($shipment): array
    {
        $vendor = $shipment->vendor;

        return [
            'vendor_name' => $vendor ? $this->vendorName($vendor) : 'Vendor',
            'shipment_number' => $shipment->shipment_number,
            'warehouse_name' => $shipment->pickupAssignment?->targetWarehouse?->name ?: 'the warehouse',
        ];
    }

    private function paymentVariables(RecipientPaymentTask $task): array
    {
        $item = $task->shipmentItem;
        $shipment = $task->shipment;

        return [
            'vendor_name' => $shipment?->vendor ? $this->vendorName($shipment->vendor) : 'Vendor',
            'recipient_name' => $task->recipient_name ?: $item?->delivery_recipient_name ?: 'Recipient',
            'shipment_number' => $shipment?->shipment_number ?: 'Shipment',
            'tracking_code' => $item?->tracking_code ?: 'Package',
            'amount' => number_format((float) ($task->negotiated_amount ?? 0), 2),
            'currency' => $task->currency ?: 'GHS',
            'payment_reference' => $task->payment_reference ?: 'N/A',
        ];
    }

    private function vendorName($vendor): string
    {
        return $vendor->business_name ?: $vendor->name ?: 'Vendor';
    }
}
