<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorInvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * List vendor's invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $query = Invoice::whereHas('shipment', fn($q) => $q->where('vendor_id', $vendor->id));

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $invoices = $query->with('shipment')
            ->latest()
            ->paginate($request->input('per_page', 15));

        $invoices->getCollection()->transform(fn(Invoice $invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'shipment_number' => $invoice->shipment->shipment_number,
            'status' => [
                'value' => $invoice->status->value,
                'label' => $invoice->status->label(),
            ],
            'pickup_fee' => $invoice->pickup_fee,
            'transport_fee' => $invoice->transport_fee,
            'handling_fee' => $invoice->handling_fee,
            'other_fee' => $invoice->other_fee,
            'total_amount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'sent_at' => $invoice->sent_at,
            'accepted_at' => $invoice->accepted_at,
            'rejected_at' => $invoice->rejected_at,
            'created_at' => $invoice->created_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoices retrieved successfully.',
            'data' => $invoices,
        ]);
    }

    /**
     * Show a single invoice with shipment details.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
                'data' => null,
            ], 404);
        }

        $invoice->load('shipment');

        return response()->json([
            'success' => true,
            'message' => 'Invoice retrieved successfully.',
            'data' => [
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => [
                        'value' => $invoice->status->value,
                        'label' => $invoice->status->label(),
                    ],
                    'pickup_fee' => $invoice->pickup_fee,
                    'transport_fee' => $invoice->transport_fee,
                    'handling_fee' => $invoice->handling_fee,
                    'other_fee' => $invoice->other_fee,
                    'total_amount' => $invoice->total_amount,
                    'currency' => $invoice->currency,
                    'notes' => $invoice->notes,
                    'vendor_notes' => $invoice->vendor_notes,
                    'rejection_reason' => $invoice->rejection_reason,
                    'sent_at' => $invoice->sent_at,
                    'accepted_at' => $invoice->accepted_at,
                    'rejected_at' => $invoice->rejected_at,
                    'created_at' => $invoice->created_at,
                    'shipment' => [
                        'id' => $invoice->shipment->id,
                        'shipment_number' => $invoice->shipment->shipment_number,
                        'status' => [
                            'value' => $invoice->shipment->status->value,
                            'label' => $invoice->shipment->status->label(),
                        ],
                        'recipient_name' => $invoice->shipment->recipient_name,
                        'recipient_phone' => $invoice->shipment->recipient_phone,
                        'town' => $invoice->shipment->town,
                        'landmark' => $invoice->shipment->landmark,
                        'created_at' => $invoice->shipment->created_at,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Accept an invoice.
     */
    public function accept(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'vendor_notes' => ['nullable', 'string'],
        ]);

        $result = $this->invoiceService->accept($invoice, $validated['vendor_notes'] ?? null);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Reject an invoice.
     */
    public function reject(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $result = $this->invoiceService->reject($invoice, $validated['rejection_reason']);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }
}
