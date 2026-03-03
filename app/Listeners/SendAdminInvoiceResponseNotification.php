<?php

namespace App\Listeners;

use App\Events\InvoiceAcceptedByVendor;
use App\Events\InvoiceRejectedByVendor;
use App\Services\PushNotificationService;

class SendAdminInvoiceResponseNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(InvoiceAcceptedByVendor|InvoiceRejectedByVendor $event): void
    {
        $invoice = $event->invoice;
        $shipment = $invoice->shipment;
        $vendorName = $shipment?->vendor?->name ?? 'Vendor';
        $invoiceNumber = $invoice->invoice_number ?? "Invoice #{$invoice->id}";

        $accepted = $event instanceof InvoiceAcceptedByVendor;

        $title = $accepted ? 'Vendor Accepted Invoice' : 'Vendor Rejected Invoice';
        $body = $accepted
            ? "{$vendorName} has accepted {$invoiceNumber}."
            : "{$vendorName} has rejected {$invoiceNumber}.";

        $this->pushService->sendToAllAdmins(
            title: $title,
            body: $body,
            data: [
                'invoice_id'     => (string) $invoice->id,
                'invoice_number' => $invoiceNumber,
                'vendor_name'    => $vendorName,
                'url'            => '/admin/invoices',
            ],
            type: 'invoice_response'
        );
    }
}
