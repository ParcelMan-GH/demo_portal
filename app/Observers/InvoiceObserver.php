<?php
namespace App\Observers;
use App\Events\InvoiceAcceptedByVendor;
use App\Events\InvoiceRejectedByVendor;
use App\Events\InvoiceSent;
use App\Models\Invoice;

class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if (!$invoice->wasChanged('status')) {
            return;
        }

        $newStatus = $invoice->status instanceof \BackedEnum
            ? $invoice->status->value
            : (string) $invoice->status;

        match ($newStatus) {
            'sent'     => event(new InvoiceSent($invoice)),
            'accepted' => event(new InvoiceAcceptedByVendor($invoice)),
            'rejected' => event(new InvoiceRejectedByVendor($invoice)),
            default    => null,
        };
    }
}
