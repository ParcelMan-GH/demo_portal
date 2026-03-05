<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminInvoiceListController extends Controller
{
    public function index(): View
    {
        $statuses = InvoiceStatus::toArray();

        return view('admin.invoices.index', compact('statuses'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = Invoice::with(['shipment.vendor']);

        // General search: invoice number or shipment number
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('shipment', function ($sq) use ($search) {
                      $sq->where('shipment_number', 'like', "%{$search}%");
                  });
            });
        }

        // Vendor search
        if ($vendorSearch = $request->get('vendor_search')) {
            $query->whereHas('shipment.vendor', function ($vq) use ($vendorSearch) {
                $vq->where('name', 'like', "%{$vendorSearch}%")
                   ->orWhere('business_name', 'like', "%{$vendorSearch}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sortBy        = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts  = ['invoice_number', 'status', 'total_amount', 'sent_at', 'accepted_at', 'rejected_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage  = min((int) $request->get('per_page', 50), 100);
        $invoices = $query->paginate($perPage);

        return response()->json([
            'data' => $invoices->map(function (Invoice $invoice) {
                $shipment = $invoice->shipment;
                $vendor   = $shipment?->vendor;

                return [
                    'id'                   => $invoice->id,
                    'invoice_number'       => $invoice->invoice_number,
                    'status'               => $invoice->status->value,
                    'status_label'         => $invoice->status->label(),
                    'shipment_id'          => $shipment?->id,
                    'shipment_number'      => $shipment?->shipment_number,
                    'shipment_url'         => $shipment
                        ? route('admin.shipments.show', $shipment->id)
                        : null,
                    'vendor_name'          => $vendor?->name,
                    'vendor_business_name' => $vendor?->business_name,
                    'total_amount'         => (float) ($invoice->total_amount ?? 0),
                    'currency'             => $invoice->currency,
                    'sent_at'              => $invoice->sent_at?->format('Y-m-d H:i:s'),
                    'accepted_at'          => $invoice->accepted_at?->format('Y-m-d H:i:s'),
                    'rejected_at'          => $invoice->rejected_at?->format('Y-m-d H:i:s'),
                    'cancelled_at'         => $invoice->cancelled_at?->format('Y-m-d H:i:s'),
                    'created_at'           => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'from'         => $invoices->firstItem() ?? 0,
                'to'           => $invoices->lastItem() ?? 0,
                'total'        => $invoices->total(),
                'last_page'    => $invoices->lastPage(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('invoices.view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Invoice::with(['shipment.vendor']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('shipment', function ($sq) use ($search) {
                      $sq->where('shipment_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        $rows = $invoices->map(function (Invoice $invoice) {
            $shipment = $invoice->shipment;
            $vendor   = $shipment?->vendor;

            return [
                'Invoice #'    => $invoice->invoice_number,
                'Shipment #'   => $shipment?->shipment_number ?? '—',
                'Vendor'       => $vendor?->name ?? '—',
                'Business'     => $vendor?->business_name ?? '—',
                'Amount'       => $invoice->currency . ' ' . number_format((float) $invoice->total_amount, 2),
                'Status'       => $invoice->status->label(),
                'Sent At'      => $invoice->sent_at?->format('Y-m-d H:i:s') ?? '—',
                'Accepted At'  => $invoice->accepted_at?->format('Y-m-d H:i:s') ?? '—',
                'Rejected At'  => $invoice->rejected_at?->format('Y-m-d H:i:s') ?? '—',
                'Created At'   => $invoice->created_at->format('Y-m-d H:i:s'),
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            $filename = 'invoices_' . date('Y-m-d_His') . '.xlsx';
            return Excel::download(new DriversExport($rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = 'invoices_' . date('Y-m-d_His') . '.pdf';
            return GenericPdfExporter::download($rows, $filename, 'Invoices List');
        }

        return response()->json(['data' => $rows]);
    }
}
