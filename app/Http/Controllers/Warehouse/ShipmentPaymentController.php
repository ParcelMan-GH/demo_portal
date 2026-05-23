<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PickupAssignment;
use App\Models\ShipmentPayment;
use App\Services\ChargesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentPaymentController extends Controller
{
    /**
     * List all payments for the shipment linked to a pickup assignment.
     */
    public function data(PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizeAny(['warehouse.receiving.manage']);

        $shipment = $pickupAssignment->shipment;
        if (!$shipment) {
            return response()->json(['payments' => [], 'summary' => ['total_due' => 0, 'total_paid' => 0, 'balance_due' => 0]]);
        }

        $payments = $shipment->payments()
            ->with(['recordedBy:id,name'])
            ->latest()
            ->get();

        $chargeSummary = app(ChargesService::class)->summariseShipment($shipment);
        $totalDue = (float) ($chargeSummary['revenue_total'] ?? 0);

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
                'total_due'      => $totalDue,
                'total_paid'     => (float) $totalPaid,
                'balance_due'    => max(0, $totalDue - (float) $totalPaid),
            ],
        ]);
    }

    /**
     * Record a new payment for the shipment.
     */
    public function store(Request $request, PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $shipment     = $pickupAssignment->shipment;

        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'string', 'in:cash,bank_transfer,mobile_money,cheque'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'payment_date'     => ['required', 'date'],
        ]);

        $admin   = Auth::guard('admin')->user();
        $payment = ShipmentPayment::create([
            'shipment_id'          => $shipment->id,
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
     * Download a payment receipt as PDF.
     */
    public function download(ShipmentPayment $payment)
    {
        $this->authorizeAny(['warehouse.receiving.manage']);

        $payment->load(['shipment.vendor', 'recordedBy']);

        $logoPath   = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('pdf.payment-receipt', [
            'payment'    => $payment,
            'logoBase64' => $logoBase64,
        ]);
        $pdf->setPaper([0, 0, 226.77, 800], 'portrait');
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download('receipt-PMR-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }

    /**
     * Render a payment receipt as printable HTML.
     */
    public function print(ShipmentPayment $payment)
    {
        $this->authorizeAny(['warehouse.receiving.manage']);

        $payment->load(['shipment.vendor', 'recordedBy']);

        $logoPath   = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        return view('pdf.payment-receipt', [
            'payment'    => $payment,
            'logoBase64' => $logoBase64,
            'autoPrint'  => true,
        ]);
    }

    /**
     * Void a payment. HQ only.
     */
    public function destroy(ShipmentPayment $payment): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin->isHqUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Only HQ administrators can void payments.',
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment voided successfully.',
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function authorizeAny(array $permissions): void
    {
        $user = Auth::guard('admin')->user();
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return;
            }
        }
        abort(403, 'Unauthorized action.');
    }
}
