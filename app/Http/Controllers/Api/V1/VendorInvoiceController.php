<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $allowedStatuses = array_map(
            fn(InvoiceStatus $status) => $status->value,
            InvoiceStatus::cases()
        );

        $sortableFields = [
            'id',
            'invoice_number',
            'status',
            'cancelled_at',
            'pickup_fee',
            'transport_fee',
            'handling_fee',
            'other_fee',
            'total_amount',
            'sent_at',
            'accepted_at',
            'rejected_at',
            'created_at',
            'updated_at',
        ];

        $normalizedStatuses = $this->normalizeStatusesInput($request->input('status'));
        if (empty($normalizedStatuses) && $request->filled('statuses')) {
            $normalizedStatuses = $this->normalizeStatusesInput($request->input('statuses'));
        }
        if (!empty($normalizedStatuses)) {
            $request->merge(['status' => $normalizedStatuses]);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($allowedStatuses)],
            'is_active' => ['nullable', 'boolean'],
            'shipment_id' => ['nullable', 'integer'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'sort_by' => ['nullable', 'string', Rule::in($sortableFields)],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $query = Invoice::whereHas('shipment', fn($q) => $q->where('vendor_id', $vendor->id))
            ->where('status', '!=', InvoiceStatus::PENDING->value);

        if (!empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (array_key_exists('is_active', $validated)) {
            if ((bool) $validated['is_active']) {
                $query->whereIn('status', InvoiceStatus::activeValues());
            } else {
                $query->whereNotIn('status', InvoiceStatus::activeValues());
            }
        }

        if (!empty($validated['shipment_id'])) {
            $query->where('shipment_id', $validated['shipment_id']);
        }

        if (!empty($validated['invoice_number'])) {
            $query->where('invoice_number', $validated['invoice_number']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('rejection_reason', 'like', "%{$search}%")
                    ->orWhere('cancel_reason', 'like', "%{$search}%")
                    ->orWhereHas('shipment', fn($shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        if (!empty($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        $limit = (int) ($validated['limit'] ?? 15);
        $offset = (int) ($validated['offset'] ?? 0);
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';

        $total = (clone $query)->count();

        $invoices = $query
            ->with('shipment')
            ->orderBy($sortBy, $sortOrder)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = $invoices
            ->map(fn(Invoice $invoice) => $this->transformInvoiceForList($invoice))
            ->toArray();

        $count = count($rows);
        $hasMore = ($offset + $count) < $total;
        $nextOffset = $hasMore ? ($offset + $limit) : null;
        $currentPage = (int) floor($offset / $limit) + 1;
        $lastPage = max((int) ceil($total / $limit), 1);

        return response()->json([
            'success' => true,
            'message' => 'Invoices retrieved successfully.',
            'data' => [
                'invoices' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => $hasMore,
                    'next_offset' => $nextOffset,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $limit,
                ],
            ],
        ]);
    }

    /**
     * Show a single invoice with shipment details.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->shipment->vendor_id !== $request->user()->id || $invoice->status === InvoiceStatus::PENDING) {
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
                'invoice' => $this->transformInvoiceForDetail($invoice),
            ],
        ]);
    }

    /**
     * Show invoice by filters (shipment_id and/or invoice_id).
     */
    public function showByFilter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id' => ['nullable', 'integer'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        if (empty($validated['shipment_id']) && empty($validated['invoice_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either shipment_id or invoice_id is required.',
                'errors' => [
                    'shipment_id' => ['Provide shipment_id or invoice_id.'],
                    'invoice_id' => ['Provide shipment_id or invoice_id.'],
                ],
            ], 422);
        }

        $vendorId = $request->user()->id;
        $invoice = null;

        if (!empty($validated['shipment_id']) && empty($validated['invoice_id'])) {
            $shipment = Shipment::query()
                ->where('id', $validated['shipment_id'])
                ->where('vendor_id', $vendorId)
                ->with(['invoice', 'invoices'])
                ->first();

            if ($shipment) {
                $invoice = $shipment->invoice
                    ?: $shipment->invoices->sortByDesc('id')->first();

                if ($invoice && $invoice->status === InvoiceStatus::PENDING) {
                    $invoice = $shipment->invoices
                        ->filter(fn($historyInvoice) => $historyInvoice->status !== InvoiceStatus::PENDING)
                        ->sortByDesc('id')
                        ->first();
                }
            }
        } else {
            $query = Invoice::query()
                ->with('shipment')
                ->whereHas('shipment', fn($q) => $q->where('vendor_id', $vendorId))
                ->where('status', '!=', InvoiceStatus::PENDING->value);

            if (!empty($validated['invoice_id'])) {
                $query->whereKey($validated['invoice_id']);
            }

            if (!empty($validated['shipment_id'])) {
                $query->where('shipment_id', $validated['shipment_id']);
            }

            $invoice = $query->latest('id')->first();
        }

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice retrieved successfully.',
            'data' => [
                'invoice' => $this->transformInvoiceForDetail($invoice),
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

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $acceptedInvoice = $invoice->fresh(['shipment']);

        return response()->json([
            'success' => true,
            'message' => 'Invoice accepted successfully.',
            'data' => [
                'invoice' => $this->transformInvoiceForDetail($acceptedInvoice),
            ],
        ]);
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

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $rejectedInvoice = $invoice->fresh(['shipment']);

        return response()->json([
            'success' => true,
            'message' => 'Invoice rejected successfully.',
            'data' => [
                'invoice' => $this->transformInvoiceForDetail($rejectedInvoice),
            ],
        ]);
    }

    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(Request $request, Invoice $invoice)
    {
        if ($invoice->shipment->vendor_id !== $request->user()->id || $invoice->status === InvoiceStatus::PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
            ], 404);
        }

        $invoice->load('shipment.vendor');

        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $data = [
            'invoice' => $invoice,
            'shipment' => $invoice->shipment,
            'vendor' => $invoice->shipment->vendor,
            'logoBase64' => $logoBase64,
        ];

        $pdf = Pdf::loadView('pdf.vendor-invoice', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isRemoteEnabled', true);

        $filename = ($invoice->invoice_number ?: 'invoice') . '.pdf';

        return $pdf->download($filename);
    }

    private function transformInvoiceForList(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'shipment_id' => $invoice->shipment_id,
            'shipment_number' => $invoice->shipment->shipment_number,
            'status' => $invoice->status->value,
            'pickup_fee' => (float) $invoice->pickup_fee,
            'transport_fee' => (float) $invoice->transport_fee,
            'handling_fee' => (float) $invoice->handling_fee,
            'other_fee' => (float) $invoice->other_fee,
            'total_amount' => (float) $invoice->total_amount,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'vendor_notes' => $invoice->vendor_notes,
            'rejection_reason' => $invoice->rejection_reason,
            'cancel_reason' => $invoice->cancel_reason,
            'is_active' => $this->isInvoiceActive($invoice),
            'sent_at' => $invoice->sent_at,
            'accepted_at' => $invoice->accepted_at,
            'rejected_at' => $invoice->rejected_at,
            'cancelled_at' => $invoice->cancelled_at,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ];
    }

    private function transformInvoiceForDetail(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'shipment_id' => $invoice->shipment_id,
            'shipment_number' => $invoice->shipment->shipment_number,
            'status' => $invoice->status->value,
            'pickup_fee' => (float) $invoice->pickup_fee,
            'transport_fee' => (float) $invoice->transport_fee,
            'handling_fee' => (float) $invoice->handling_fee,
            'other_fee' => (float) $invoice->other_fee,
            'total_amount' => (float) $invoice->total_amount,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'vendor_notes' => $invoice->vendor_notes,
            'rejection_reason' => $invoice->rejection_reason,
            'cancel_reason' => $invoice->cancel_reason,
            'is_active' => $this->isInvoiceActive($invoice),
            'sent_at' => $invoice->sent_at,
            'accepted_at' => $invoice->accepted_at,
            'rejected_at' => $invoice->rejected_at,
            'cancelled_at' => $invoice->cancelled_at,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
            'shipment' => [
                'id' => $invoice->shipment->id,
                'shipment_number' => $invoice->shipment->shipment_number,
                'status' => $invoice->shipment->status->value,
                'recipient_name' => $invoice->shipment->recipient_name,
                'recipient_phone' => $invoice->shipment->recipient_phone,
                'town' => $invoice->shipment->town,
                'landmark' => $invoice->shipment->landmark,
                'created_at' => $invoice->shipment->created_at,
            ],
        ];
    }

    private function isInvoiceActive(Invoice $invoice): bool
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status->value
            : (string) $invoice->status;

        return in_array($status, InvoiceStatus::activeValues(), true);
    }

    private function normalizeStatusesInput(mixed $rawStatuses): array
    {
        if (is_null($rawStatuses)) {
            return [];
        }

        if (is_string($rawStatuses)) {
            $rawStatuses = explode(',', $rawStatuses);
        }

        if (!is_array($rawStatuses)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn($value) => trim((string) $value),
            $rawStatuses
        ), fn($value) => $value !== ''));
    }
}
