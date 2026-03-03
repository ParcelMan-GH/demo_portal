<?php

namespace App\Listeners;

use App\Events\InvoiceSent;
use App\Services\PushNotificationService;

class SendVendorInvoiceNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(InvoiceSent $event): void
    {
        $invoice = $event->invoice;
        $invoice->loadMissing('shipment.vendor');
        $vendor = $invoice->shipment?->vendor;

        if (!$vendor || !$vendor->fcm_token) {
            return;
        }

        $this->pushService->sendToVendor(
            vendor: $vendor,
            title: 'Invoice Ready — ' . ($invoice->shipment->shipment_number ?? ''),
            body: 'An invoice of ' . number_format((float) $invoice->total_amount, 2) . ' ' . ($invoice->currency ?? 'GHS') . ' has been generated. Please review and respond.',
            data: [
                'invoice_id'      => (string) $invoice->id,
                'invoice_number'  => $invoice->invoice_number,
                'shipment_number' => $invoice->shipment?->shipment_number,
            ],
            type: 'invoice_sent'
        );
    }
}
