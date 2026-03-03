<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentPaymentController extends Controller
{
    /**
     * List all payments for a shipment (JSON, for the Payments tab).
     */
    public function data(Shipment $shipment): JsonResponse
    {
        $payments = $shipment->payments()
            ->with(['recordedBy:id,name', 'invoice:id,invoice_number'])
            ->latest()
            ->get();

        $totalInvoiced = $shipment->invoices()
            ->where('status', 'accepted')
            ->sum('total_amount');

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
                'invoice_number'   => $p->invoice?->invoice_number,
                'created_at'       => $p->created_at?->format('Y-m-d H:i'),
            ]),
            'summary' => [
                'total_invoiced' => (float) $totalInvoiced,
                'total_paid'     => (float) $totalPaid,
                'balance_due'    => max(0, (float) $totalInvoiced - (float) $totalPaid),
            ],
        ]);
    }

    /**
     * Record a new payment for a shipment.
     */
    public function store(Request $request, Shipment $shipment): JsonResponse
    {
        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'string', 'in:cash,bank_transfer,mobile_money,cheque'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'payment_date'     => ['required', 'date'],
            'invoice_id'       => ['nullable', 'integer', 'exists:invoices,id'],
        ]);

        $admin = Auth::guard('admin')->user();

        $payment = ShipmentPayment::create([
            'shipment_id'        => $shipment->id,
            'invoice_id'         => $validated['invoice_id'] ?? null,
            'amount'             => $validated['amount'],
            'payment_method'     => $validated['payment_method'],
            'reference_number'   => $validated['reference_number'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'recorded_by_admin_id' => $admin->id,
            'payment_date'       => $validated['payment_date'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data'    => ['payment' => $payment->load('recordedBy:id,name', 'invoice:id,invoice_number')],
        ]);
    }

    /**
     * Void (delete) a payment. Superadmin only.
     */
    public function destroy(ShipmentPayment $payment): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admins can void payments.',
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment voided successfully.',
        ]);
    }
}
