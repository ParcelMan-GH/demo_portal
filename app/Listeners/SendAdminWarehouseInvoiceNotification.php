<?php

namespace App\Listeners;

use App\Events\WarehouseInvoiceCancelled;
use App\Events\WarehouseInvoiceCreated;
use App\Services\PushNotificationService;

class SendAdminWarehouseInvoiceNotification
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(WarehouseInvoiceCreated|WarehouseInvoiceCancelled $event): void
    {
        $invoice = $event->invoice;
        $shipment = $invoice->shipment;
        $invoiceNumber = $invoice->invoice_number ?? "Invoice #{$invoice->id}";
        $shipmentNumber = $shipment?->shipment_number ?? "Shipment #{$invoice->shipment_id}";
        $actorName = $event->createdBy->name ?? ($event->cancelledBy->name ?? 'Warehouse user');

        if ($event instanceof WarehouseInvoiceCreated) {
            $actorName = $event->createdBy->name;
            $title = 'Invoice Created by Warehouse';
            $body = "{$actorName} created {$invoiceNumber} for shipment {$shipmentNumber}.";
            $type = 'warehouse_invoice_created';
        } else {
            $actorName = $event->cancelledBy->name;
            $title = 'Invoice Cancelled by Warehouse';
            $body = "{$actorName} cancelled {$invoiceNumber} for shipment {$shipmentNumber}.";
            $type = 'warehouse_invoice_cancelled';
        }

        $this->pushService->sendToSuperAdmins(
            title: $title,
            body: $body,
            data: [
                'invoice_id'      => (string) $invoice->id,
                'invoice_number'  => $invoiceNumber,
                'shipment_number' => $shipmentNumber,
                'actor'           => $actorName,
                'url'             => '/admin/invoices',
            ],
            type: $type
        );
    }
}
