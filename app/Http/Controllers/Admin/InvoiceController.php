<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\ShipmentPayment;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
            'transport_fee' => ['nullable', 'numeric', 'min:0'],
            'handling_fee' => ['nullable', 'numeric', 'min:0'],
            'other_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $validated['transport_fee'] = $validated['transport_fee'] ?? 0;
        $validated['handling_fee'] = $validated['handling_fee'] ?? 0;
        $validated['other_fee'] = $validated['other_fee'] ?? 0;

        $admin = Auth::guard('admin')->user();
        $sendNow = (bool) ($validated['send_now'] ?? false);

        $result = $this->invoiceService->create($shipment, $validated, $admin, $sendNow);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Show invoice detail page.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorizePermission('invoices.view');

        $invoice->load(['shipment.vendor', 'creator']);

        $canManage = Auth::guard('admin')->user()->hasPermission('invoices.edit');

        return view('admin.invoices.show', compact('invoice', 'canManage'));
    }

    /**
     * Download invoice as PDF.
     */
    public function download(Invoice $invoice)
    {
        $this->authorizePermission('invoices.view');

        $invoice->load('shipment.vendor');

        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('pdf.vendor-invoice', [
            'invoice'    => $invoice,
            'shipment'   => $invoice->shipment,
            'vendor'     => $invoice->shipment->vendor,
            'logoBase64' => $logoBase64,
        ]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download(($invoice->invoice_number ?: 'invoice') . '.pdf');
    }

    /**
     * Get payments linked to this invoice.
     */
    public function paymentsData(Invoice $invoice): JsonResponse
    {
        $this->authorizePermission('invoices.view');

        $payments = ShipmentPayment::where('invoice_id', $invoice->id)
            ->with(['recordedBy:id,name'])
            ->latest('payment_date')
            ->get();

        $totalPaid = $payments->sum('amount');

        return response()->json([
            'payments' => $payments->map(fn(ShipmentPayment $p) => [
                'id'               => $p->id,
                'amount'           => (float) $p->amount,
                'formatted_amount' => $p->formattedAmount(),
                'payment_method'   => $p->payment_method,
                'method_label'     => $p->methodLabel(),
                'reference_number' => $p->reference_number,
                'notes'            => $p->notes,
                'payment_date'     => $p->payment_date?->format('Y-m-d H:i'),
                'recorded_by'      => $p->recordedBy?->name,
                'created_at'       => $p->created_at?->format('Y-m-d H:i'),
            ]),
            'summary' => [
                'total_invoiced' => (float) $invoice->total_amount,
                'total_paid'     => (float) $totalPaid,
                'balance_due'    => max(0, (float) $invoice->total_amount - (float) $totalPaid),
            ],
        ]);
    }

    /**
     * Record a payment against this invoice.
     */
    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizePermission('invoices.edit');

        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'string', 'in:cash,bank_transfer,mobile_money,cheque'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'payment_date'     => ['required', 'date'],
        ]);

        $admin = Auth::guard('admin')->user();

        $payment = ShipmentPayment::create([
            'shipment_id'          => $invoice->shipment_id,
            'invoice_id'           => $invoice->id,
            'amount'               => $validated['amount'],
            'payment_method'       => $validated['payment_method'],
            'reference_number'     => $validated['reference_number'] ?? null,
            'notes'                => $validated['notes'] ?? null,
            'recorded_by_admin_id' => $admin->id,
            'payment_date'         => $validated['payment_date'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data'    => ['payment' => $payment->load('recordedBy:id,name')],
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
