<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\ShipmentStatus;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;

class InvoiceService
{
    public function create(Shipment $shipment, array $fees, ?User $admin = null): array
    {
        if (!$shipment->canBeInvoiced()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be invoiced in its current status.',
            ];
        }

        $invoice = Invoice::create([
            'shipment_id' => $shipment->id,
            'pickup_fee' => $fees['pickup_fee'] ?? 0,
            'transport_fee' => $fees['transport_fee'] ?? 0,
            'handling_fee' => $fees['handling_fee'] ?? 0,
            'other_fee' => $fees['other_fee'] ?? 0,
            'notes' => $fees['notes'] ?? null,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $admin?->id,
        ]);

        return [
            'success' => true,
            'message' => 'Invoice created successfully.',
            'data' => ['invoice' => $invoice],
        ];
    }

    public function send(Invoice $invoice): array
    {
        if ($invoice->status !== InvoiceStatus::PENDING) {
            return [
                'success' => false,
                'message' => 'Only pending invoices can be sent.',
            ];
        }

        $invoice->update([
            'status' => InvoiceStatus::SENT,
            'sent_at' => now(),
        ]);

        $invoice->shipment->update(['status' => ShipmentStatus::INVOICE_SENT]);

        return [
            'success' => true,
            'message' => 'Invoice sent to vendor.',
            'data' => ['invoice' => $invoice->fresh()],
        ];
    }

    public function accept(Invoice $invoice, ?string $vendorNotes = null): array
    {
        if ($invoice->status !== InvoiceStatus::SENT) {
            return [
                'success' => false,
                'message' => 'Only sent invoices can be accepted.',
            ];
        }

        $invoice->update([
            'status' => InvoiceStatus::ACCEPTED,
            'accepted_at' => now(),
            'vendor_notes' => $vendorNotes,
        ]);

        $invoice->shipment->update(['status' => ShipmentStatus::INVOICE_ACCEPTED]);

        return [
            'success' => true,
            'message' => 'Invoice accepted.',
            'data' => ['invoice' => $invoice->fresh()],
        ];
    }

    public function reject(Invoice $invoice, string $reason): array
    {
        if ($invoice->status !== InvoiceStatus::SENT) {
            return [
                'success' => false,
                'message' => 'Only sent invoices can be rejected.',
            ];
        }

        $invoice->update([
            'status' => InvoiceStatus::REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Revert shipment to submitted so admin can create a new invoice
        $invoice->shipment->update(['status' => ShipmentStatus::SUBMITTED]);

        return [
            'success' => true,
            'message' => 'Invoice rejected.',
            'data' => ['invoice' => $invoice->fresh()],
        ];
    }

    public function cancel(Invoice $invoice): array
    {
        if (in_array($invoice->status, [InvoiceStatus::ACCEPTED, InvoiceStatus::CANCELLED])) {
            return [
                'success' => false,
                'message' => 'This invoice cannot be cancelled.',
            ];
        }

        $invoice->update(['status' => InvoiceStatus::CANCELLED]);

        // Revert shipment to submitted
        $invoice->shipment->update(['status' => ShipmentStatus::SUBMITTED]);

        return [
            'success' => true,
            'message' => 'Invoice cancelled.',
        ];
    }
}
