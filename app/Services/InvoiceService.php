<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\ShipmentStatus;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function create(Shipment $shipment, array $fees, ?User $admin = null, bool $sendNow = false): array
    {
        return DB::transaction(function () use ($shipment, $fees, $admin, $sendNow) {
            $lockedShipment = Shipment::query()
                ->lockForUpdate()
                ->find($shipment->id);

            if (!$lockedShipment || $lockedShipment->status !== ShipmentStatus::SUBMITTED) {
                return [
                    'success' => false,
                    'message' => 'Shipment cannot be invoiced in its current status.',
                ];
            }

            if ($this->hasActiveInvoice($lockedShipment)) {
                return [
                    'success' => false,
                    'message' => 'Shipment already has an active invoice.',
                ];
            }

            $invoice = Invoice::create([
                'shipment_id' => $lockedShipment->id,
                'pickup_fee' => $fees['pickup_fee'] ?? 0,
                'transport_fee' => $fees['transport_fee'] ?? 0,
                'handling_fee' => $fees['handling_fee'] ?? 0,
                'other_fee' => $fees['other_fee'] ?? 0,
                'notes' => $fees['notes'] ?? null,
                'status' => InvoiceStatus::PENDING,
                'created_by' => $admin?->id,
            ]);

            $lockedShipment->current_invoice_id = $invoice->id;

            if ($sendNow) {
                $invoice->update([
                    'status' => InvoiceStatus::SENT,
                    'sent_at' => now(),
                ]);
                $lockedShipment->status = ShipmentStatus::INVOICE_SENT;
            }

            $lockedShipment->save();

            return [
                'success' => true,
                'message' => $sendNow
                    ? 'Invoice created and sent successfully.'
                    : 'Invoice created successfully.',
                'data' => ['invoice' => $invoice->fresh(['shipment'])],
            ];
        });
    }

    public function send(Invoice $invoice): array
    {
        return DB::transaction(function () use ($invoice) {
            $lockedInvoice = Invoice::query()
                ->with('shipment')
                ->lockForUpdate()
                ->find($invoice->id);

            if (!$lockedInvoice || $lockedInvoice->status !== InvoiceStatus::PENDING) {
                return [
                    'success' => false,
                    'message' => 'Only pending invoices can be sent.',
                ];
            }

            $lockedShipment = Shipment::query()
                ->lockForUpdate()
                ->find($lockedInvoice->shipment_id);

            if (!$lockedShipment) {
                return [
                    'success' => false,
                    'message' => 'Shipment not found for this invoice.',
                ];
            }

            if ($this->hasActiveInvoice($lockedShipment, $lockedInvoice->id)) {
                return [
                    'success' => false,
                    'message' => 'Another active invoice exists for this shipment.',
                ];
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::SENT,
                'sent_at' => now(),
            ]);

            $lockedShipment->update([
                'status' => ShipmentStatus::INVOICE_SENT,
                'current_invoice_id' => $lockedInvoice->id,
            ]);

            return [
                'success' => true,
                'message' => 'Invoice sent to vendor.',
                'data' => ['invoice' => $lockedInvoice->fresh(['shipment'])],
            ];
        });
    }

    public function accept(Invoice $invoice, ?string $vendorNotes = null): array
    {
        return DB::transaction(function () use ($invoice, $vendorNotes) {
            $lockedInvoice = Invoice::query()
                ->with('shipment')
                ->lockForUpdate()
                ->find($invoice->id);

            if (!$lockedInvoice || $lockedInvoice->status !== InvoiceStatus::SENT) {
                return [
                    'success' => false,
                    'message' => 'Only sent invoices can be accepted.',
                ];
            }

            $lockedShipment = Shipment::query()
                ->lockForUpdate()
                ->find($lockedInvoice->shipment_id);

            if (!$lockedShipment) {
                return [
                    'success' => false,
                    'message' => 'Shipment not found for this invoice.',
                ];
            }

            if ($this->hasActiveInvoice($lockedShipment, $lockedInvoice->id)) {
                return [
                    'success' => false,
                    'message' => 'Another active invoice exists for this shipment.',
                ];
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::ACCEPTED,
                'accepted_at' => now(),
                'vendor_notes' => $vendorNotes,
            ]);

            $lockedShipment->update([
                'status' => ShipmentStatus::INVOICE_ACCEPTED,
                'current_invoice_id' => $lockedInvoice->id,
            ]);

            return [
                'success' => true,
                'message' => 'Invoice accepted.',
                'data' => ['invoice' => $lockedInvoice->fresh(['shipment'])],
            ];
        });
    }

    public function reject(Invoice $invoice, string $reason): array
    {
        return DB::transaction(function () use ($invoice, $reason) {
            $lockedInvoice = Invoice::query()
                ->with('shipment')
                ->lockForUpdate()
                ->find($invoice->id);

            if (!$lockedInvoice || $lockedInvoice->status !== InvoiceStatus::SENT) {
                return [
                    'success' => false,
                    'message' => 'Only sent invoices can be rejected.',
                ];
            }

            $lockedShipment = Shipment::query()
                ->lockForUpdate()
                ->find($lockedInvoice->shipment_id);

            if (!$lockedShipment) {
                return [
                    'success' => false,
                    'message' => 'Shipment not found for this invoice.',
                ];
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $shipmentUpdates = ['status' => ShipmentStatus::SUBMITTED];
            if ((int) $lockedShipment->current_invoice_id === (int) $lockedInvoice->id) {
                $shipmentUpdates['current_invoice_id'] = null;
            }
            $lockedShipment->update($shipmentUpdates);

            return [
                'success' => true,
                'message' => 'Invoice rejected.',
                'data' => ['invoice' => $lockedInvoice->fresh(['shipment'])],
            ];
        });
    }

    public function cancel(Invoice $invoice, ?string $cancelReason = null): array
    {
        return DB::transaction(function () use ($invoice, $cancelReason) {
            $lockedInvoice = Invoice::query()
                ->with('shipment')
                ->lockForUpdate()
                ->find($invoice->id);

            if (!$lockedInvoice) {
                return [
                    'success' => false,
                    'message' => 'Invoice not found.',
                ];
            }

            if (in_array($lockedInvoice->status, [InvoiceStatus::REJECTED, InvoiceStatus::CANCELLED], true)) {
                return [
                    'success' => false,
                    'message' => 'This invoice is already inactive.',
                ];
            }

            $lockedShipment = Shipment::query()
                ->lockForUpdate()
                ->find($lockedInvoice->shipment_id);

            if (!$lockedShipment) {
                return [
                    'success' => false,
                    'message' => 'Shipment not found for this invoice.',
                ];
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::CANCELLED,
                'cancel_reason' => $cancelReason,
                'cancelled_at' => now(),
            ]);

            $shipmentUpdates = ['status' => ShipmentStatus::SUBMITTED];
            if ((int) $lockedShipment->current_invoice_id === (int) $lockedInvoice->id) {
                $shipmentUpdates['current_invoice_id'] = null;
            }
            $lockedShipment->update($shipmentUpdates);

            return [
                'success' => true,
                'message' => 'Invoice cancelled.',
                'data' => ['invoice' => $lockedInvoice->fresh(['shipment'])],
            ];
        });
    }

    private function hasActiveInvoice(Shipment $shipment, ?int $exceptInvoiceId = null): bool
    {
        $query = $shipment->invoices()
            ->whereIn('status', InvoiceStatus::activeValues());

        if ($exceptInvoiceId) {
            $query->where('id', '!=', $exceptInvoiceId);
        }

        return $query->exists();
    }
}
