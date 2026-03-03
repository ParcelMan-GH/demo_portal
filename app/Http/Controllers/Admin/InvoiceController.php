<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Create an invoice for a shipment (optionally send immediately).
     */
    public function store(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('invoices.create');

        $validated = $request->validate([
            'pickup_fee' => ['required', 'numeric', 'min:0'],
            'transport_fee' => ['required', 'numeric', 'min:0'],
            'handling_fee' => ['required', 'numeric', 'min:0'],
            'other_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $admin = Auth::guard('admin')->user();
        $sendNow = (bool) ($validated['send_now'] ?? false);

        $result = $this->invoiceService->create($shipment, $validated, $admin, $sendNow);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Show invoice details with shipment relationship.
     */
    public function show(Invoice $invoice)
    {
        $this->authorizePermission('invoices.view');

        $invoice->load('shipment');

        return response()->json([
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update a pending invoice.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizePermission('invoices.edit');

        if ($invoice->status !== InvoiceStatus::PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending invoices can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'pickup_fee' => ['required', 'numeric', 'min:0'],
            'transport_fee' => ['required', 'numeric', 'min:0'],
            'handling_fee' => ['required', 'numeric', 'min:0'],
            'other_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully.',
            'data' => ['invoice' => $invoice->fresh()],
        ]);
    }

    /**
     * Send a pending invoice to the vendor.
     */
    public function send(Invoice $invoice)
    {
        $this->authorizePermission('invoices.create');

        $result = $this->invoiceService->send($invoice);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        $this->authorizePermission('invoices.delete');

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string'],
        ]);

        $result = $this->invoiceService->cancel($invoice, $validated['cancel_reason'] ?? null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Admin accepts an invoice on behalf of the vendor (Phase 3 override).
     * Requires invoices.edit permission and the invoice to be in 'sent' status.
     */
    public function adminAccept(Request $request, Invoice $invoice)
    {
        $this->authorizePermission('invoices.edit');

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $admin = Auth::guard('admin')->user();
        $result = $this->invoiceService->adminAcceptOnBehalfOfVendor(
            $invoice,
            $admin,
            $validated['admin_notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Check if current admin has permission.
     */
    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
