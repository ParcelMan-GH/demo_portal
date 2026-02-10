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
     * Create and immediately send an invoice for a shipment.
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
        ]);

        $admin = Auth::guard('admin')->user();

        $result = $this->invoiceService->create($shipment, $validated, $admin);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        // Send the invoice immediately after creation
        $invoice = $result['data']['invoice'];
        $sendResult = $this->invoiceService->send($invoice);

        return response()->json($sendResult, $sendResult['success'] ? 200 : 422);
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
    public function cancel(Invoice $invoice)
    {
        $this->authorizePermission('invoices.delete');

        $result = $this->invoiceService->cancel($invoice);

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
