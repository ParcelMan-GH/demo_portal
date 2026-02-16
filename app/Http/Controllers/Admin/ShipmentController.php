<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Exports\ShipmentsExport;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Support\GenericPdfExporter;
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

        $query = Shipment::with(['vendor', 'deliveryRegion', 'deliveryDistrict'])->withCount('items');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_phone', 'like', "%{$search}%");
                    })
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
        $allowedSorts = ['shipment_number', 'destination_mode', 'delivery_recipient_name', 'status', 'created_at', 'submitted_at', 'items_count'];

        if ($sortBy === 'recipient_name') {
            $sortBy = 'delivery_recipient_name';
        }

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min($request->get('per_page', 50), 100);
        $shipments = $query->paginate($perPage);

        return response()->json([
            'data' => $shipments->map(function ($shipment) {
                $summary = $this->buildDestinationSummary($shipment);
                $location = $this->buildDeliveryLocationSummary($shipment);

                return [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'vendor_name' => $shipment->vendor?->name,
                    'vendor_business' => $shipment->vendor?->business_name,
                    'destination_mode' => $shipment->destination_mode?->value,
                    'destination_mode_label' => $shipment->destination_mode?->label() ?? 'Single Destination',
                    'destination_summary_title' => $summary['title'],
                    'destination_summary_subtitle' => $summary['subtitle'],
                    'delivery_location_title' => $location['title'],
                    'delivery_location_subtitle' => $location['subtitle'],
                    'items_count' => $shipment->items_count,
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

        $shipment->load([
            'vendor',
            'region',
            'district',
            'pickupRegion',
            'pickupDistrict',
            'deliveryRegion',
            'deliveryDistrict',
            'invoice',
            'invoices',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
            'pickupAssignment.receivedWarehouse',
            'pickupAssignments.driver',
            'pickupAssignments.assignedBy',
            'pickupAssignments.targetWarehouse',
            'pickupAssignments.receivedWarehouse',
        ]);

        $itemsCount = $shipment->items()->count();
        $canManage = Auth::guard('admin')->user()->hasPermission('shipments.edit');
        $currentAssignment = $shipment->pickupAssignment;
        if ($currentAssignment?->status === PickupAssignmentStatus::CANCELLED) {
            $currentAssignment = null;
        }

        $currentInvoice = $shipment->invoice;
        if (!$currentInvoice) {
            $currentInvoice = $shipment->invoices
                ->first(fn ($invoice) => $invoice->status->isActive())
                ?? $shipment->invoices->first();
        }

        $invoiceHistory = $shipment->invoices
            ->sortByDesc('id')
            ->values()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status->value,
                    'status_label' => $invoice->status->label(),
                    'is_active' => $invoice->status->isActive(),
                    'pickup_fee' => (float) ($invoice->pickup_fee ?? 0),
                    'transport_fee' => (float) ($invoice->transport_fee ?? 0),
                    'handling_fee' => (float) ($invoice->handling_fee ?? 0),
                    'other_fee' => (float) ($invoice->other_fee ?? 0),
                    'total_amount' => (float) ($invoice->total_amount ?? 0),
                    'currency' => $invoice->currency,
                    'notes' => $invoice->notes,
                    'vendor_notes' => $invoice->vendor_notes,
                    'rejection_reason' => $invoice->rejection_reason,
                    'cancel_reason' => $invoice->cancel_reason,
                    'sent_at' => $invoice->sent_at,
                    'accepted_at' => $invoice->accepted_at,
                    'rejected_at' => $invoice->rejected_at,
                    'cancelled_at' => $invoice->cancelled_at,
                    'created_at' => $invoice->created_at,
                    'updated_at' => $invoice->updated_at,
                ];
            })
            ->toArray();

        $assignmentHistory = $shipment->pickupAssignments
            ->sortByDesc('id')
            ->values()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'status' => $assignment->status->value,
                    'status_label' => $assignment->status->label(),
                    'driver_id' => $assignment->driver_id,
                    'driver_name' => $assignment->driver?->name,
                    'driver_phone' => $assignment->driver?->phone,
                    'assigned_by' => $assignment->assignedBy?->name,
                    'target_warehouse_id' => $assignment->target_warehouse_id,
                    'target_warehouse_name' => $assignment->targetWarehouse?->name,
                    'target_warehouse_code' => $assignment->targetWarehouse?->code,
                    'assigned_at' => $assignment->assigned_at,
                    'en_route_at' => $assignment->en_route_at,
                    'arrived_at' => $assignment->arrived_at,
                    'picked_up_at' => $assignment->picked_up_at,
                    'completed_at' => $assignment->completed_at,
                    'arrived_warehouse_at' => $assignment->arrived_warehouse_at,
                    'received_warehouse_id' => $assignment->received_warehouse_id,
                    'received_warehouse_name' => $assignment->receivedWarehouse?->name,
                    'received_warehouse_code' => $assignment->receivedWarehouse?->code,
                    'received_by_user_id' => $assignment->received_by_user_id,
                    'received_at' => $assignment->received_at,
                    'receive_notes' => $assignment->receive_notes,
                    'cancelled_at' => $assignment->cancelled_at,
                    'cancellation_reason' => $assignment->cancellation_reason,
                    'notes' => $assignment->notes,
                    'created_at' => $assignment->created_at,
                ];
            })
            ->toArray();

        return view('admin.shipments.show', [
            'shipment' => $shipment,
            'currentInvoice' => $currentInvoice,
            'currentAssignment' => $currentAssignment,
            'itemsCount' => $itemsCount,
            'canManage' => $canManage,
            'invoiceHistory' => $invoiceHistory,
            'assignmentHistory' => $assignmentHistory,
            'statuses' => ShipmentStatus::toArray(),
            'invoiceStatuses' => InvoiceStatus::toArray(),
            'assignmentStatuses' => PickupAssignmentStatus::toArray(),
        ]);
    }

    public function items(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.view');

        $items = $shipment->items()->with(['images', 'deliveryRegion', 'deliveryDistrict'])->get();

        return response()->json([
            'data' => $items->map(function ($item) {
                $deliveryLocationTitle = null;
                $deliveryLocationSubtitle = null;

                if ($item->delivery_region_id && $item->delivery_district_id) {
                    $deliveryLocationTitle = $item->deliveryRegion?->name;
                    $deliveryLocationSubtitle = trim(collect([
                        $item->deliveryDistrict?->name,
                        $item->delivery_town,
                    ])->filter()->implode(', '));
                } elseif ($item->delivery_latitude && $item->delivery_longitude) {
                    $deliveryLocationTitle = 'GPS Coordinates';
                    $deliveryLocationSubtitle = $item->delivery_latitude . ', ' . $item->delivery_longitude;
                } elseif ($item->delivery_gh_post_address) {
                    $deliveryLocationTitle = 'Ghana Post';
                    $deliveryLocationSubtitle = $item->delivery_gh_post_address;
                }

                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'status' => $item->status->value ?? $item->status,
                    'status_label' => method_exists($item->status, 'label') ? $item->status->label() : $item->status,
                    'tracking_code' => $item->tracking_code,
                    'delivery_recipient_name' => $item->delivery_recipient_name,
                    'delivery_recipient_phone' => $item->delivery_recipient_phone,
                    'delivery_location_title' => $deliveryLocationTitle,
                    'delivery_location_subtitle' => $deliveryLocationSubtitle,
                    'images' => $item->images->map(function ($img) {
                        return $img->getSignedUrl();
                    }),
                    'images_count' => $item->images->count(),
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    public function tracking(Request $request, Shipment $shipment)
    {
        $this->authorizePermission('shipments.view');

        $timeline = [];

        $createdAt = $shipment->created_at->format('Y-m-d H:i:s');
        $timeline[] = [
            'status' => 'created',
            'label' => 'Shipment Created',
            'status_label' => 'Shipment Created',
            'timestamp' => $createdAt,
            'created_at' => $createdAt,
        ];

        if ($shipment->submitted_at) {
            $submittedAt = $shipment->submitted_at->format('Y-m-d H:i:s');
            $timeline[] = [
                'status' => 'submitted',
                'label' => 'Submitted for Processing',
                'status_label' => 'Submitted for Processing',
                'timestamp' => $submittedAt,
                'created_at' => $submittedAt,
            ];
        }

        $shipment->loadMissing('invoices');
        foreach ($shipment->invoices as $invoice) {
            if ($invoice->sent_at) {
                $sentAt = $invoice->sent_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'invoice_sent',
                    'label' => "Invoice Sent ({$invoice->invoice_number})",
                    'status_label' => 'Invoice Sent',
                    'timestamp' => $sentAt,
                    'created_at' => $sentAt,
                ];
            }
            if ($invoice->accepted_at) {
                $acceptedAt = $invoice->accepted_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'invoice_accepted',
                    'label' => "Invoice Accepted ({$invoice->invoice_number})",
                    'status_label' => 'Invoice Accepted',
                    'timestamp' => $acceptedAt,
                    'created_at' => $acceptedAt,
                ];
            }
            if ($invoice->rejected_at) {
                $rejectedAt = $invoice->rejected_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'invoice_rejected',
                    'label' => "Invoice Rejected ({$invoice->invoice_number})",
                    'status_label' => 'Invoice Rejected',
                    'timestamp' => $rejectedAt,
                    'created_at' => $rejectedAt,
                ];
            }
            if ($invoice->cancelled_at) {
                $cancelledAt = $invoice->cancelled_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'invoice_cancelled',
                    'label' => "Invoice Cancelled ({$invoice->invoice_number})",
                    'status_label' => 'Invoice Cancelled',
                    'timestamp' => $cancelledAt,
                    'created_at' => $cancelledAt,
                ];
            }
        }

        $shipment->loadMissing([
            'pickupAssignments.driver',
            'pickupAssignments.targetWarehouse',
            'pickupAssignments.receivedWarehouse',
        ]);

        foreach ($shipment->pickupAssignments->sortBy('id') as $assignment) {
            if ($assignment->assigned_at) {
                $assignedAt = $assignment->assigned_at->format('Y-m-d H:i:s');
                $label = 'Driver Assigned: ' . ($assignment->driver?->name ?? 'Unknown');
                $timeline[] = [
                    'status' => 'pickup_assigned',
                    'label' => $label,
                    'status_label' => $label,
                    'timestamp' => $assignedAt,
                    'created_at' => $assignedAt,
                ];
            }
            if ($assignment->en_route_at) {
                $enRouteAt = $assignment->en_route_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'en_route',
                    'label' => 'Driver En Route',
                    'status_label' => 'Driver En Route',
                    'timestamp' => $enRouteAt,
                    'created_at' => $enRouteAt,
                ];
            }
            if ($assignment->arrived_at) {
                $arrivedAt = $assignment->arrived_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'arrived',
                    'label' => 'Driver Arrived',
                    'status_label' => 'Driver Arrived',
                    'timestamp' => $arrivedAt,
                    'created_at' => $arrivedAt,
                ];
            }
            if ($assignment->picked_up_at) {
                $pickedUpAt = $assignment->picked_up_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'picked_up',
                    'label' => 'Items Picked Up',
                    'status_label' => 'Items Picked Up',
                    'timestamp' => $pickedUpAt,
                    'created_at' => $pickedUpAt,
                ];
            }

            if ($assignment->arrived_warehouse_at) {
                $arrivedWarehouseAt = $assignment->arrived_warehouse_at->format('Y-m-d H:i:s');
                $location = $assignment->targetWarehouse?->name
                    ?? $assignment->receivedWarehouse?->name;

                $timeline[] = [
                    'status' => 'arrived_warehouse',
                    'label' => 'Arrived at Warehouse',
                    'status_label' => 'Arrived at Warehouse',
                    'timestamp' => $arrivedWarehouseAt,
                    'created_at' => $arrivedWarehouseAt,
                    'location' => $location,
                ];
            }

            if ($assignment->received_at) {
                $receivedAt = $assignment->received_at->format('Y-m-d H:i:s');
                $location = $assignment->receivedWarehouse?->name
                    ?? $assignment->targetWarehouse?->name;

                $timeline[] = [
                    'status' => 'at_warehouse',
                    'label' => 'Received at Warehouse',
                    'status_label' => 'Received at Warehouse',
                    'timestamp' => $receivedAt,
                    'created_at' => $receivedAt,
                    'location' => $location,
                    'description' => $assignment->receive_notes
                        ? 'Notes: ' . $assignment->receive_notes
                        : null,
                ];
            }
        }

        usort($timeline, fn($a, $b) => strcmp((string) $a['created_at'], (string) $b['created_at']));

        return response()->json(['data' => $timeline]);
    }

    public function export(Request $request)
    {
        $this->authorizePermission('shipments.view');

        $query = Shipment::with(['vendor', 'deliveryRegion', 'deliveryDistrict'])->withCount('items');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('delivery_recipient_name', 'like', "%{$search}%");
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

        $shipments = $query->orderBy('created_at', 'desc')->get();

        $rows = $shipments->map(function ($s) {
            $summary = $this->buildDestinationSummary($s);
            $location = $this->buildDeliveryLocationSummary($s);

            return [
                'Shipment #' => $s->shipment_number,
                'Vendor' => $s->vendor?->name,
                'Destination Mode' => $s->destination_mode?->label() ?? 'Single Destination',
                'Destination Summary' => trim($summary['title'].' - '.$summary['subtitle'], ' -'),
                'Delivery Location' => trim($location['title'].' - '.$location['subtitle'], ' -'),
                'Items' => $s->items_count,
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
            $filename = 'shipments_' . date('Y-m-d_His') . '.pdf';

            return GenericPdfExporter::download($rows, $filename, 'Shipments List');
        }

        return response()->json(['data' => $rows]);
    }

    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function buildDestinationSummary(Shipment $shipment): array
    {
        if ($shipment->isPerItemDestination()) {
            return [
                'title' => 'Per-item recipients',
                'subtitle' => $shipment->items_count . ' item(s)',
            ];
        }

        return [
            'title' => $shipment->delivery_recipient_name ?: '-',
            'subtitle' => $shipment->delivery_recipient_phone ?: '-',
        ];
    }

    private function buildDeliveryLocationSummary(Shipment $shipment): array
    {
        if ($shipment->isPerItemDestination()) {
            return [
                'title' => 'Per-item destinations',
                'subtitle' => 'Defined on each item',
            ];
        }

        if ($shipment->delivery_region_id && $shipment->delivery_district_id) {
            $title = $shipment->deliveryRegion?->name ?: 'Dropdown location';
            $subtitleParts = array_filter([
                $shipment->deliveryDistrict?->name,
                $shipment->delivery_town,
            ]);

            return [
                'title' => $title,
                'subtitle' => !empty($subtitleParts) ? implode(', ', $subtitleParts) : '-',
            ];
        }

        if ($shipment->delivery_latitude && $shipment->delivery_longitude) {
            return [
                'title' => 'GPS Coordinates',
                'subtitle' => $shipment->delivery_latitude . ', ' . $shipment->delivery_longitude,
            ];
        }

        if ($shipment->delivery_gh_post_address) {
            return [
                'title' => 'Ghana Post',
                'subtitle' => $shipment->delivery_gh_post_address,
            ];
        }

        return [
            'title' => '-',
            'subtitle' => '-',
        ];
    }
}
