<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Exports\ShipmentsExport;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\WalkinShipmentService;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
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

        // Unified eager-load covering all pipeline stages
        $shipment->loadMissing([
            'invoices',
            'pickupAssignments.driver',
            'pickupAssignments.targetWarehouse',
            'pickupAssignments.receivedWarehouse',
            'pickupAssignments.warehouseReceipt',
            'items.pickupConfirmations',
            'items.warehouseReceiptItems',
            'items.transportManifestItems',
            'items.sortBatchItems.sortBatch.originWarehouse',
            'items.sortBatchItems.sortBatch.destinationWarehouse',
            'items.sortBatchItems.sortBatch.transportManifest.assignedDriver',
            'items.sortBatchItems.sortBatch.transportManifest.originWarehouse',
            'items.sortBatchItems.sortBatch.transportManifest.destinationWarehouse',
            'items.sortBatchItems.sortBatch.deliveryRun.assignedDriver',
            'items.sortBatchItems.sortBatch.deliveryRun.warehouse',
            'items.deliveryRunItems.stop',
            'items.deliveryRunItems.run',
        ]);

        $timeline = [];

        // --- Shipment created ---
        $createdAt = $shipment->created_at->format('Y-m-d H:i:s');
        $timeline[] = [
            'status' => 'created',
            'label' => 'Shipment Created',
            'status_label' => 'Created',
            'timestamp' => $createdAt,
            'created_at' => $createdAt,
        ];

        // --- Submitted ---
        if ($shipment->submitted_at) {
            $submittedAt = $shipment->submitted_at->format('Y-m-d H:i:s');
            $timeline[] = [
                'status' => 'submitted',
                'label' => 'Submitted for Processing',
                'status_label' => 'Submitted',
                'timestamp' => $submittedAt,
                'created_at' => $submittedAt,
            ];
        }

        // --- Invoice lifecycle ---
        foreach ($shipment->invoices as $invoice) {
            if ($invoice->sent_at) {
                $ts = $invoice->sent_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'invoice_sent', 'label' => "Invoice Sent ({$invoice->invoice_number})", 'status_label' => 'Invoice Sent', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($invoice->accepted_at) {
                $ts = $invoice->accepted_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'invoice_accepted', 'label' => "Invoice Accepted ({$invoice->invoice_number})", 'status_label' => 'Invoice Accepted', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($invoice->rejected_at) {
                $ts = $invoice->rejected_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'invoice_rejected', 'label' => "Invoice Rejected ({$invoice->invoice_number})", 'status_label' => 'Invoice Rejected', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($invoice->cancelled_at) {
                $ts = $invoice->cancelled_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'invoice_cancelled', 'label' => "Invoice Cancelled ({$invoice->invoice_number})", 'status_label' => 'Invoice Cancelled', 'timestamp' => $ts, 'created_at' => $ts];
            }
        }

        // --- Pickup assignment lifecycle ---
        foreach ($shipment->pickupAssignments->sortBy('id') as $assignment) {
            if ($assignment->assigned_at) {
                $ts = $assignment->assigned_at->format('Y-m-d H:i:s');
                $label = 'Pickup Driver Assigned: ' . ($assignment->driver?->name ?? 'Unknown');
                $timeline[] = ['status' => 'pickup_assigned', 'label' => $label, 'status_label' => 'Driver Assigned', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($assignment->en_route_at) {
                $ts = $assignment->en_route_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'en_route', 'label' => 'Driver En Route to Vendor', 'status_label' => 'En Route', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($assignment->arrived_at) {
                $ts = $assignment->arrived_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'arrived', 'label' => 'Driver Arrived at Vendor', 'status_label' => 'Driver Arrived', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($assignment->picked_up_at) {
                $ts = $assignment->picked_up_at->format('Y-m-d H:i:s');
                $timeline[] = ['status' => 'picked_up', 'label' => 'Items Picked Up', 'status_label' => 'Picked Up', 'timestamp' => $ts, 'created_at' => $ts];
            }
            if ($assignment->arrived_warehouse_at) {
                $ts = $assignment->arrived_warehouse_at->format('Y-m-d H:i:s');
                $location = $assignment->targetWarehouse?->name ?? $assignment->receivedWarehouse?->name;
                $timeline[] = ['status' => 'arrived_warehouse', 'label' => 'Driver Arrived at Warehouse', 'status_label' => 'Arrived Warehouse', 'timestamp' => $ts, 'created_at' => $ts, 'location' => $location];
            }
            if ($assignment->received_at) {
                $ts = $assignment->received_at->format('Y-m-d H:i:s');
                $location = $assignment->receivedWarehouse?->name ?? $assignment->targetWarehouse?->name;
                $timeline[] = [
                    'status' => 'at_warehouse', 'label' => 'Received at Warehouse', 'status_label' => 'At Warehouse',
                    'timestamp' => $ts, 'created_at' => $ts, 'location' => $location,
                    'description' => $assignment->receive_notes ? 'Notes: ' . $assignment->receive_notes : null,
                ];
            }
        }

        // --- Sort batch, manifest, delivery run events ---
        $allSortBatches = $shipment->items
            ->flatMap(fn($item) => $item->sortBatchItems)
            ->filter(fn($sbi) => is_null($sbi->removed_at))
            ->map(fn($sbi) => $sbi->sortBatch)
            ->filter()
            ->unique('id');

        foreach ($allSortBatches as $batch) {
            if ($batch->sealed_at) {
                $ts = $batch->sealed_at->format('Y-m-d H:i:s');
                $modeLabel = $batch->dispatch_mode === 'transfer' ? 'Inter-warehouse Transfer' : 'Local Delivery';
                $timeline[] = [
                    'status' => 'sorted',
                    'label' => 'Sorted — Batch ' . $batch->batch_number,
                    'status_label' => 'Sorted',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => $batch->originWarehouse?->name,
                    'description' => 'Dispatch mode: ' . $modeLabel,
                    'meta' => ['batch_id' => $batch->id, 'batch_number' => $batch->batch_number],
                ];
            }
        }

        $allManifests = $allSortBatches->map(fn($b) => $b->transportManifest)->filter()->unique('id');
        foreach ($allManifests as $manifest) {
            if ($manifest->dispatched_at) {
                $ts = $manifest->dispatched_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'in_transit',
                    'label' => 'In Transit — Manifest ' . $manifest->manifest_number,
                    'status_label' => 'In Transit',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => ($manifest->originWarehouse?->name ?? '?') . ' → ' . ($manifest->destinationWarehouse?->name ?? '?'),
                    'description' => 'Driver: ' . ($manifest->assignedDriver?->name ?? 'Unknown'),
                    'meta' => ['manifest_id' => $manifest->id, 'manifest_number' => $manifest->manifest_number],
                ];
            }
            if ($manifest->arrived_at) {
                $ts = $manifest->arrived_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'at_destination',
                    'label' => 'Arrived at Destination — Manifest ' . $manifest->manifest_number,
                    'status_label' => 'At Destination',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => $manifest->destinationWarehouse?->name,
                    'meta' => ['manifest_id' => $manifest->id, 'manifest_number' => $manifest->manifest_number],
                ];
            }
            if ($manifest->received_at) {
                $ts = $manifest->received_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'received_at_destination',
                    'label' => 'Received at Destination Warehouse',
                    'status_label' => 'Received',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => $manifest->destinationWarehouse?->name,
                    'meta' => ['manifest_id' => $manifest->id, 'manifest_number' => $manifest->manifest_number],
                ];
            }
        }

        $allDeliveryRuns = $allSortBatches->map(fn($b) => $b->deliveryRun)->filter()->unique('id');
        foreach ($allDeliveryRuns as $run) {
            if ($run->dispatched_at) {
                $ts = $run->dispatched_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'out_for_delivery',
                    'label' => 'Out for Delivery — Run ' . $run->run_number,
                    'status_label' => 'Out for Delivery',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => $run->warehouse?->name,
                    'description' => 'Driver: ' . ($run->assignedDriver?->name ?? 'Unknown'),
                    'meta' => ['run_id' => $run->id, 'run_number' => $run->run_number],
                ];
            }
            if ($run->completed_at) {
                $ts = $run->completed_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'delivered',
                    'label' => 'Delivery Run Completed — Run ' . $run->run_number,
                    'status_label' => 'Delivered',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'meta' => ['run_id' => $run->id, 'run_number' => $run->run_number],
                ];
            }
        }

        usort($timeline, fn($a, $b) => strcmp((string) $a['created_at'], (string) $b['created_at']));

        // --- Per-item journey data ---
        $itemsData = $shipment->items->map(function ($item) {
            $activeSortBatchItem = $item->sortBatchItems->whereNull('removed_at')->first();
            $sortBatch = $activeSortBatchItem?->sortBatch;
            $manifest = $sortBatch?->transportManifest;
            $deliveryRun = $sortBatch?->deliveryRun;
            $deliveryRunItem = $item->deliveryRunItems->first();
            $stop = $deliveryRunItem?->stop;

            return [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'tracking_code' => $item->tracking_code,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),

                'sort_batch' => $sortBatch ? [
                    'id' => $sortBatch->id,
                    'batch_number' => $sortBatch->batch_number,
                    'status' => $sortBatch->status,
                    'dispatch_mode' => $sortBatch->dispatch_mode,
                    'dispatch_mode_label' => $sortBatch->dispatch_mode === 'transfer' ? 'Inter-warehouse Transfer' : 'Local Delivery',
                    'origin_warehouse' => $sortBatch->originWarehouse?->name,
                    'destination_warehouse' => $sortBatch->destinationWarehouse?->name,
                    'added_at' => $activeSortBatchItem?->added_at?->format('Y-m-d H:i:s'),
                    'sealed_at' => $sortBatch->sealed_at?->format('Y-m-d H:i:s'),
                    'quantity_allocated' => $activeSortBatchItem?->quantity_allocated,
                    'show_url' => route('admin.sort-batches.show', $sortBatch->id),
                ] : null,

                'transport_manifest' => $manifest ? [
                    'id' => $manifest->id,
                    'manifest_number' => $manifest->manifest_number,
                    'status' => $manifest->status,
                    'driver_name' => $manifest->assignedDriver?->name,
                    'origin_warehouse' => $manifest->originWarehouse?->name,
                    'destination_warehouse' => $manifest->destinationWarehouse?->name,
                    'assigned_at' => $manifest->assigned_at?->format('Y-m-d H:i:s'),
                    'dispatched_at' => $manifest->dispatched_at?->format('Y-m-d H:i:s'),
                    'arrived_at' => $manifest->arrived_at?->format('Y-m-d H:i:s'),
                    'received_at' => $manifest->received_at?->format('Y-m-d H:i:s'),
                    'show_url' => route('admin.transport-manifests.show', $manifest->id),
                ] : null,

                'delivery_run' => $deliveryRun ? [
                    'id' => $deliveryRun->id,
                    'run_number' => $deliveryRun->run_number,
                    'status' => $deliveryRun->status,
                    'driver_name' => $deliveryRun->assignedDriver?->name,
                    'warehouse' => $deliveryRun->warehouse?->name,
                    'assigned_at' => $deliveryRun->assigned_at?->format('Y-m-d H:i:s'),
                    'dispatched_at' => $deliveryRun->dispatched_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $deliveryRun->completed_at?->format('Y-m-d H:i:s'),
                    'show_url' => route('admin.delivery-runs.show', $deliveryRun->id),
                ] : null,

                'delivery_stop' => $stop ? [
                    'recipient_name' => $stop->recipient_name,
                    'recipient_phone' => $stop->recipient_phone,
                    'status' => $stop->status,
                    'arrived_at' => $stop->arrived_at?->format('Y-m-d H:i:s'),
                    'delivered_at' => $stop->delivered_at?->format('Y-m-d H:i:s'),
                    'failure_reason' => $stop->failure_reason,
                    'has_proof_photo' => !empty($stop->proof_photo_path),
                ] : null,

                'delivery_outcome' => $deliveryRunItem ? [
                    'status' => $deliveryRunItem->status,
                    'expected_quantity' => $deliveryRunItem->expected_quantity,
                    'delivered_quantity' => $deliveryRunItem->delivered_quantity,
                    'delivered_at' => $deliveryRunItem->delivered_at?->format('Y-m-d H:i:s'),
                    'notes' => $deliveryRunItem->notes,
                ] : null,

                'quantities' => [
                    'vendor_declared'      => $item->quantity,
                    'driver_expected'      => $item->pickupConfirmations->sum('expected_quantity') ?: null,
                    'driver_confirmed'     => $item->pickupConfirmations->sum('confirmed_quantity') ?: null,
                    'warehouse_expected'   => $item->warehouseReceiptItems->sum('expected_quantity') ?: null,
                    'warehouse_received'   => $item->warehouseReceiptItems->sum('received_quantity') ?: null,
                    'warehouse_damaged'    => $item->warehouseReceiptItems->sum('damaged_quantity') ?: null,
                    'allocated'            => $activeSortBatchItem?->quantity_allocated,
                    'manifest_expected'    => $item->transportManifestItems->sum('expected_quantity') ?: null,
                    'manifest_loaded'      => $item->transportManifestItems->sum('loaded_quantity') ?: null,
                    'manifest_received'    => $item->transportManifestItems->sum('received_quantity') ?: null,
                    'delivery_expected'    => $deliveryRunItem?->expected_quantity,
                    'delivery_actual'      => $deliveryRunItem?->delivered_quantity,
                ],
            ];
        })->values()->toArray();

        return response()->json([
            'data' => $timeline,
            'items' => $itemsData,
        ]);
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

    public function create()
    {
        $this->authorizePermission('shipments.create');

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.shipments.create', compact('warehouses'));
    }

    public function store(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $this->authorizePermission('shipments.create');

        $validated = $request->validate([
            'vendor_id'                          => 'required|exists:vendors,id',
            'warehouse_id'                       => 'required|exists:warehouses,id',
            'fulfillment_type'                   => 'nullable|in:warehouse,self_pickup,direct',
            'destination_mode'                   => 'required|in:single,per_item',
            'items'                              => 'required|array|min:1',
            'items.*.description'                => 'required|string|max:500',
            'items.*.quantity'                    => 'required|integer|min:1',
            // Per-item delivery
            'items.*.delivery.recipient_name'    => 'required_if:destination_mode,per_item|nullable|string|max:255',
            'items.*.delivery.recipient_phone'   => 'required_if:destination_mode,per_item|nullable|string|max:20',
            'items.*.delivery.region_id'         => 'required_if:destination_mode,per_item|nullable|integer',
            'items.*.delivery.district_id'       => 'required_if:destination_mode,per_item|nullable|integer',
            'items.*.delivery.town'              => 'nullable|string|max:255',
            'items.*.delivery.landmark'          => 'nullable|string|max:255',
            'items.*.delivery.instructions'      => 'nullable|string|max:1000',
            // Single delivery
            'delivery.recipient_name'            => 'required_if:destination_mode,single|nullable|string|max:255',
            'delivery.recipient_phone'           => 'required_if:destination_mode,single|nullable|string|max:20',
            'delivery.region_id'                 => 'required_if:destination_mode,single|nullable|integer',
            'delivery.district_id'               => 'required_if:destination_mode,single|nullable|integer',
            'delivery.town'                      => 'nullable|string|max:255',
            'delivery.landmark'                  => 'nullable|string|max:255',
            'delivery.instructions'              => 'nullable|string|max:1000',
        ]);

        $validated['source']             = 'admin_walkin';
        $validated['created_by_user_id'] = Auth::guard('admin')->id();

        $result = $service->createWalkinShipment($validated);

        return response()->json([
            'success'  => true,
            'message'  => 'Walk-in shipment created successfully.',
            'redirect' => route('admin.shipments.show', $result['shipment']->id),
        ]);
    }

    public function vendorLookup(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $this->authorizePermission('shipments.create');

        $request->validate(['phone' => 'required|string|min:9']);

        $vendor = $service->lookupVendor($request->get('phone'));

        return response()->json([
            'found'  => $vendor !== null,
            'vendor' => $vendor ? [
                'id'            => $vendor->id,
                'name'          => $vendor->name,
                'business_name' => $vendor->business_name,
                'phone'         => $vendor->phone,
                'email'         => $vendor->email,
                'is_active'     => $vendor->is_active,
            ] : null,
        ]);
    }

    public function vendorCreate(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $this->authorizePermission('shipments.create');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'phone'         => 'required|string|min:9|unique:vendors,phone',
            'email'         => 'nullable|email|unique:vendors,email',
        ]);

        $vendor = $service->createVendorInline($validated);

        return response()->json([
            'success' => true,
            'vendor'  => [
                'id'            => $vendor->id,
                'name'          => $vendor->name,
                'business_name' => $vendor->business_name,
                'phone'         => $vendor->phone,
                'email'         => $vendor->email,
                'is_active'     => $vendor->is_active,
            ],
        ]);
    }

    public function locationSearch(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.create');

        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['locations' => []]);
        }

        $locations = Location::where('is_active', true)
            ->with(['district:id,name', 'region:id,name'])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', $q . '%')
                      ->orWhere('name', 'like', '% ' . $q . '%');
            })
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$q . '%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'locations' => $locations->map(fn ($l) => [
                'id'       => $l->id,
                'name'     => $l->name,
                'district' => ['id' => $l->district->id, 'name' => $l->district->name],
                'region'   => ['id' => $l->region->id, 'name' => $l->region->name],
                'display'  => "{$l->name}, {$l->district->name}, {$l->region->name}",
            ]),
        ]);
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
