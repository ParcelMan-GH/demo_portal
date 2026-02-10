<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Exports\ShipmentsExport;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentController extends Controller
{
    public function index()
    {
        $this->authorizePermission('shipments.view');

        return view('admin.shipments.index', [
            'statuses' => ShipmentStatus::toArray(),
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizePermission('shipments.view');

        $query = Shipment::with(['vendor', 'region', 'district']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['shipment_number', 'recipient_name', 'status', 'created_at', 'submitted_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min($request->get('per_page', 50), 100);
        $shipments = $query->paginate($perPage);

        return response()->json([
            'data' => $shipments->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'vendor_name' => $shipment->vendor?->name,
                    'vendor_business' => $shipment->vendor?->business_name,
                    'recipient_name' => $shipment->recipient_name,
                    'recipient_phone' => $shipment->recipient_phone,
                    'region' => $shipment->region?->name,
                    'district' => $shipment->district?->name,
                    'items_count' => $shipment->items()->count(),
                    'status' => $shipment->status->value,
                    'status_label' => $shipment->status->label(),
                    'submitted_at' => $shipment->submitted_at?->format('Y-m-d H:i:s'),
                    'created_at' => $shipment->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'from' => $shipments->firstItem() ?? 0,
                'to' => $shipments->lastItem() ?? 0,
                'total' => $shipments->total(),
                'last_page' => $shipments->lastPage(),
            ],
        ]);
    }

    public function showPage(Shipment $shipment)
    {
        $this->authorizePermission('shipments.view');

        $shipment->load(['vendor', 'region', 'district', 'invoice', 'pickupAssignment.driver']);

        $itemsCount = $shipment->items()->count();
        $canManage = Auth::guard('admin')->user()->hasPermission('shipments.edit');

        return view('admin.shipments.show', [
            'shipment' => $shipment,
            'itemsCount' => $itemsCount,
            'canManage' => $canManage,
            'statuses' => ShipmentStatus::toArray(),
            'invoiceStatuses' => InvoiceStatus::toArray(),
            'assignmentStatuses' => PickupAssignmentStatus::toArray(),
        ]);
    }

    public function items(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.view');

        $items = $shipment->items()->with('images')->get();

        return response()->json([
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'status' => $item->status->value ?? $item->status,
                    'status_label' => method_exists($item->status, 'label') ? $item->status->label() : $item->status,
                    'tracking_code' => $item->tracking_code,
                    'images' => $item->images->map(function ($img) {
                        return $img->getSignedUrl();
                    }),
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    public function tracking(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.view');

        $timeline = [];

        $timeline[] = [
            'status' => 'created',
            'label' => 'Shipment Created',
            'timestamp' => $shipment->created_at->format('Y-m-d H:i:s'),
        ];

        if ($shipment->submitted_at) {
            $timeline[] = [
                'status' => 'submitted',
                'label' => 'Submitted for Processing',
                'timestamp' => $shipment->submitted_at->format('Y-m-d H:i:s'),
            ];
        }

        if ($invoice = $shipment->invoice) {
            if ($invoice->sent_at) {
                $timeline[] = [
                    'status' => 'invoice_sent',
                    'label' => 'Invoice Sent',
                    'timestamp' => $invoice->sent_at->format('Y-m-d H:i:s'),
                ];
            }
            if ($invoice->accepted_at) {
                $timeline[] = [
                    'status' => 'invoice_accepted',
                    'label' => 'Invoice Accepted',
                    'timestamp' => $invoice->accepted_at->format('Y-m-d H:i:s'),
                ];
            }
            if ($invoice->rejected_at) {
                $timeline[] = [
                    'status' => 'invoice_rejected',
                    'label' => 'Invoice Rejected',
                    'timestamp' => $invoice->rejected_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        if ($assignment = $shipment->pickupAssignment) {
            if ($assignment->assigned_at) {
                $timeline[] = [
                    'status' => 'pickup_assigned',
                    'label' => 'Driver Assigned: ' . ($assignment->driver?->name ?? 'Unknown'),
                    'timestamp' => $assignment->assigned_at->format('Y-m-d H:i:s'),
                ];
            }
            if ($assignment->en_route_at) {
                $timeline[] = [
                    'status' => 'en_route',
                    'label' => 'Driver En Route',
                    'timestamp' => $assignment->en_route_at->format('Y-m-d H:i:s'),
                ];
            }
            if ($assignment->arrived_at) {
                $timeline[] = [
                    'status' => 'arrived',
                    'label' => 'Driver Arrived',
                    'timestamp' => $assignment->arrived_at->format('Y-m-d H:i:s'),
                ];
            }
            if ($assignment->picked_up_at) {
                $timeline[] = [
                    'status' => 'picked_up',
                    'label' => 'Items Picked Up',
                    'timestamp' => $assignment->picked_up_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        return response()->json(['data' => $timeline]);
    }

    public function export(Request $request)
    {
        $this->authorizePermission('shipments.view');

        $query = Shipment::with(['vendor', 'region', 'district']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%");
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

        $shipments = $query->orderBy('created_at', 'desc')->get();

        $rows = $shipments->map(function ($s) {
            return [
                'Shipment #' => $s->shipment_number,
                'Vendor' => $s->vendor?->name,
                'Recipient' => $s->recipient_name,
                'Phone' => $s->recipient_phone,
                'Region' => $s->region?->name,
                'District' => $s->district?->name,
                'Items' => $s->items()->count(),
                'Status' => $s->status->label(),
                'Submitted At' => $s->submitted_at?->format('Y-m-d H:i:s'),
                'Created At' => $s->created_at->format('Y-m-d H:i:s'),
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new ShipmentsExport($rows), 'shipments_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.shipments.export-pdf', [
                'rows' => $rows,
                'generatedAt' => now()->format('F d, Y H:i:s'),
            ]);
            return $pdf->download('shipments_' . date('Y-m-d_His') . '.pdf');
        }

        return response()->json(['data' => $rows]);
    }

    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
