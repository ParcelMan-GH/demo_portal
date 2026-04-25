<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentStatus;
use App\Exports\ShipmentsExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PickupItemConfirmation;
use App\Models\PickupPhoto;
use App\Models\Region;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use App\Models\ShipmentItemTracking;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use App\Services\StorageService;
use App\Services\WalkinShipmentService;
use App\Services\Warehouse\WarehouseReceivingService;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                    'vendor_phone' => $shipment->vendor?->phone,
                    'sender_notes' => $shipment->sender_notes,
                    'destination_mode' => $shipment->destination_mode?->value,
                    'destination_mode_label' => $shipment->destination_mode?->label() ?? 'Single Destination',
                    'delivery_preference' => $shipment->delivery_preference ?? 'deliver',
                    'fulfillment_type' => $shipment->fulfillment_type?->value ?? null,
                    'fulfillment_type_label' => $shipment->fulfillment_type?->label() ?? null,
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
        $currentAdmin = Auth::guard('admin')->user();
        $canManage = $currentAdmin->hasPermission('shipments.edit');
        $canManageCharges = $currentAdmin->hasPermission('charges.manage');
        $currentAssignment = $shipment->pickupAssignment;
        if ($currentAssignment?->status === PickupAssignmentStatus::CANCELLED) {
            $currentAssignment = null;
        }

        $currentInvoice = $shipment->invoice;
        if (! $currentInvoice) {
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
            'canManageCharges' => $canManageCharges,
            'invoiceHistory' => $invoiceHistory,
            'assignmentHistory' => $assignmentHistory,
            'statuses' => ShipmentStatus::toArray(),
            'invoiceStatuses' => InvoiceStatus::toArray(),
            'assignmentStatuses' => PickupAssignmentStatus::toArray(),
            // Full editor config for the Packages tab (same payload the old
            // /edit page used to consume).
            'editConfig' => $canManage ? $this->buildEditConfig($shipment) : null,
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
                    $deliveryLocationSubtitle = $item->delivery_latitude.', '.$item->delivery_longitude;
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
                    'delivery_preference' => $item->delivery_preference ?? 'deliver',
                    'fulfillment_type' => $item->fulfillment_type?->value,
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
                $label = 'Pickup Driver Assigned: '.($assignment->driver?->name ?? 'Unknown');
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
                    'description' => $assignment->receive_notes ? 'Notes: '.$assignment->receive_notes : null,
                ];
            }
        }

        // --- Sort batch, manifest, delivery run events ---
        $allSortBatches = $shipment->items
            ->flatMap(fn ($item) => $item->sortBatchItems)
            ->filter(fn ($sbi) => is_null($sbi->removed_at))
            ->map(fn ($sbi) => $sbi->sortBatch)
            ->filter()
            ->unique('id');

        foreach ($allSortBatches as $batch) {
            if ($batch->sealed_at) {
                $ts = $batch->sealed_at->format('Y-m-d H:i:s');
                $modeLabel = $batch->dispatch_mode === 'transfer' ? 'Inter-warehouse Transfer' : 'Local Delivery';
                $timeline[] = [
                    'status' => 'sorted',
                    'label' => 'Sorted — Batch '.$batch->batch_number,
                    'status_label' => 'Sorted',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => $batch->originWarehouse?->name,
                    'description' => 'Dispatch mode: '.$modeLabel,
                    'meta' => ['batch_id' => $batch->id, 'batch_number' => $batch->batch_number],
                ];
            }
        }

        $allManifests = $allSortBatches->map(fn ($b) => $b->transportManifest)->filter()->unique('id');
        foreach ($allManifests as $manifest) {
            if ($manifest->dispatched_at) {
                $ts = $manifest->dispatched_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'in_transit',
                    'label' => 'In Transit — Manifest '.$manifest->manifest_number,
                    'status_label' => 'In Transit',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => ($manifest->originWarehouse?->name ?? '?').' → '.($manifest->destinationWarehouse?->name ?? '?'),
                    'description' => 'Driver: '.($manifest->assignedDriver?->name ?? 'Unknown'),
                    'meta' => ['manifest_id' => $manifest->id, 'manifest_number' => $manifest->manifest_number],
                ];
            }
            if ($manifest->arrived_at) {
                $ts = $manifest->arrived_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'at_destination',
                    'label' => 'Arrived at Destination — Manifest '.$manifest->manifest_number,
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

        $allDeliveryRuns = $allSortBatches->map(fn ($b) => $b->deliveryRun)->filter()->unique('id');
        foreach ($allDeliveryRuns as $run) {
            if ($run->dispatched_at) {
                $ts = $run->dispatched_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'out_for_delivery',
                    'label' => 'Out for Delivery — Run '.$run->run_number,
                    'status_label' => 'Out for Delivery',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'location' => $run->warehouse?->name,
                    'description' => 'Driver: '.($run->assignedDriver?->name ?? 'Unknown'),
                    'meta' => ['run_id' => $run->id, 'run_number' => $run->run_number],
                ];
            }
            if ($run->completed_at) {
                $ts = $run->completed_at->format('Y-m-d H:i:s');
                $timeline[] = [
                    'status' => 'delivered',
                    'label' => 'Delivery Run Completed — Run '.$run->run_number,
                    'status_label' => 'Delivered',
                    'timestamp' => $ts, 'created_at' => $ts,
                    'meta' => ['run_id' => $run->id, 'run_number' => $run->run_number],
                ];
            }
        }

        usort($timeline, fn ($a, $b) => strcmp((string) $a['created_at'], (string) $b['created_at']));

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
                    'has_proof_photo' => ! empty($stop->proof_photo_path),
                ] : null,

                'delivery_outcome' => $deliveryRunItem ? [
                    'status' => $deliveryRunItem->status,
                    'expected_quantity' => $deliveryRunItem->expected_quantity,
                    'delivered_quantity' => $deliveryRunItem->delivered_quantity,
                    'delivered_at' => $deliveryRunItem->delivered_at?->format('Y-m-d H:i:s'),
                    'notes' => $deliveryRunItem->notes,
                ] : null,

                'quantities' => [
                    'vendor_declared' => $item->quantity,
                    'driver_expected' => $item->pickupConfirmations->sum('expected_quantity') ?: null,
                    'driver_confirmed' => $item->pickupConfirmations->sum('confirmed_quantity') ?: null,
                    'warehouse_expected' => $item->warehouseReceiptItems->sum('expected_quantity') ?: null,
                    'warehouse_received' => $item->warehouseReceiptItems->sum('received_quantity') ?: null,
                    'warehouse_damaged' => $item->warehouseReceiptItems->sum('damaged_quantity') ?: null,
                    'allocated' => $activeSortBatchItem?->quantity_allocated,
                    'manifest_expected' => $item->transportManifestItems->sum('expected_quantity') ?: null,
                    'manifest_loaded' => $item->transportManifestItems->sum('loaded_quantity') ?: null,
                    'manifest_received' => $item->transportManifestItems->sum('received_quantity') ?: null,
                    'delivery_expected' => $deliveryRunItem?->expected_quantity,
                    'delivery_actual' => $deliveryRunItem?->delivered_quantity,
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
            return Excel::download(new ShipmentsExport($rows), 'shipments_'.date('Y-m-d_His').'.xlsx');
        }

        if ($format === 'pdf') {
            $filename = 'shipments_'.date('Y-m-d_His').'.pdf';

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
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'fulfillment_type' => 'nullable|in:warehouse,self_pickup,direct',
            'destination_mode' => 'required|in:single,per_item',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            // Per-item delivery
            'items.*.delivery.recipient_name' => 'required_if:destination_mode,per_item|nullable|string|max:255',
            'items.*.delivery.recipient_phone' => 'required_if:destination_mode,per_item|nullable|string|max:20',
            'items.*.delivery.region_id' => 'required_if:destination_mode,per_item|nullable|integer',
            'items.*.delivery.district_id' => 'required_if:destination_mode,per_item|nullable|integer',
            'items.*.delivery.town' => 'nullable|string|max:255',
            'items.*.delivery.landmark' => 'nullable|string|max:255',
            'items.*.delivery.instructions' => 'nullable|string|max:1000',
            // Single delivery
            'delivery.recipient_name' => 'required_if:destination_mode,single|nullable|string|max:255',
            'delivery.recipient_phone' => 'required_if:destination_mode,single|nullable|string|max:20',
            'delivery.region_id' => 'required_if:destination_mode,single|nullable|integer',
            'delivery.district_id' => 'required_if:destination_mode,single|nullable|integer',
            'delivery.town' => 'nullable|string|max:255',
            'delivery.landmark' => 'nullable|string|max:255',
            'delivery.instructions' => 'nullable|string|max:1000',
        ]);

        $validated['source'] = 'admin_walkin';
        $validated['created_by_user_id'] = Auth::guard('admin')->id();

        $result = $service->createWalkinShipment($validated);

        return response()->json([
            'success' => true,
            'message' => 'Walk-in shipment created successfully.',
            'redirect' => route('admin.shipments.show', $result['shipment']->id),
        ]);
    }

    public function vendorLookup(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $this->authorizePermission('shipments.create');

        $request->validate(['phone' => 'required|string|min:9']);

        $vendor = $service->lookupVendor($request->get('phone'));

        return response()->json([
            'found' => $vendor !== null,
            'vendor' => $vendor ? [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'business_name' => $vendor->business_name,
                'phone' => $vendor->phone,
                'email' => $vendor->email,
                'is_active' => $vendor->is_active,
            ] : null,
        ]);
    }

    public function vendorCreate(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $this->authorizePermission('shipments.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'phone' => 'required|string|min:9|unique:vendors,phone',
            'email' => 'nullable|email|unique:vendors,email',
        ]);

        $vendor = $service->createVendorInline($validated);

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'business_name' => $vendor->business_name,
                'phone' => $vendor->phone,
                'email' => $vendor->email,
                'is_active' => $vendor->is_active,
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
                $query->where('name', 'like', $q.'%')
                    ->orWhere('name', 'like', '% '.$q.'%');
            })
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$q.'%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'locations' => $locations->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'district' => ['id' => $l->district->id, 'name' => $l->district->name],
                'region' => ['id' => $l->region->id, 'name' => $l->region->name],
                'display' => "{$l->name}, {$l->district->name}, {$l->region->name}",
            ]),
        ]);
    }

    public function updateFulfillmentType(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'fulfillment_type' => 'required|in:warehouse,direct',
        ]);

        // Only allow change before pickup is completed
        $blockStatuses = ['picked_up', 'at_warehouse', 'sorted', 'in_transit', 'at_destination', 'out_for_delivery', 'delivered', 'cancelled'];
        if (in_array($shipment->status->value, $blockStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Fulfillment type cannot be changed after pickup is completed.',
            ], 400);
        }

        $shipment->update(['fulfillment_type' => $validated['fulfillment_type']]);

        return response()->json([
            'success' => true,
            'message' => 'Fulfillment type updated.',
            'fulfillment_type' => $shipment->fresh()->fulfillment_type->value,
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        if (! Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function buildDestinationSummary(Shipment $shipment): array
    {
        if ($shipment->isPerItemDestination()) {
            return [
                'title' => 'Per-item recipients',
                'subtitle' => $shipment->items_count.' item(s)',
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
                'subtitle' => ! empty($subtitleParts) ? implode(', ', $subtitleParts) : '-',
            ];
        }

        if ($shipment->delivery_latitude && $shipment->delivery_longitude) {
            return [
                'title' => 'GPS Coordinates',
                'subtitle' => $shipment->delivery_latitude.', '.$shipment->delivery_longitude,
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

    // ─── EDIT PAGE ─────────────────────────────────────────────────────────────

    public function editPage(Shipment $shipment)
    {
        $this->authorizePermission('shipments.edit');

        // Legacy route — unified workspace now lives at /admin/shipments/{id}
        // with the Packages tab pre-selected.
        return redirect()->route('admin.shipments.show', [
            'shipment' => $shipment->id,
            'tab' => 'packages',
        ]);
    }

    /**
     * Build the edit config payload consumed by the Packages editor partial.
     */
    protected function buildEditConfig(Shipment $shipment): array
    {
        $shipment->load([
            'vendor', 'items.images', 'items.deliveryRegion', 'items.deliveryDistrict',
            'pickupRegion', 'pickupDistrict', 'deliveryRegion', 'deliveryDistrict',
        ]);

        $regions = Region::active()->select('id', 'name', 'code')->orderBy('name')->get();

        return [
            'shipmentId' => $shipment->id,
            'saveUrl' => route('admin.shipments.update', $shipment),
            'addPackageUrl' => route('admin.shipments.packages.add', $shipment),
            'updatePackageUrlTemplate' => route('admin.shipments.packages.update', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'deletePackageUrlTemplate' => route('admin.shipments.packages.delete', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'splitPackageUrlTemplate' => route('admin.shipments.packages.split', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'uploadPhotosUrlTemplate' => route('admin.shipments.packages.photos.upload', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'movePhotoUrl' => route('admin.shipments.packages.photos.move', $shipment),
            'deletePhotoUrlTemplate' => route('admin.shipments.packages.photos.delete', ['image' => '__IMG__']),
            'townsSearchUrl' => route('admin.locations.towns.data'),
            'assignDriverEndpoint' => route('admin.assignments.assign', $shipment),
            'updateAssignmentEndpointTemplate' => $shipment->pickupAssignment
                ? route('admin.assignments.update', ['pickupAssignment' => $shipment->pickupAssignment->id])
                : null,
            'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
            'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
            'canEditShipmentFields' => true,
            'duplicateUrl' => route('admin.shipments.duplicate', $shipment),
            'autoGroupByPhoneUrl' => route('admin.shipments.auto-group-by-phone', $shipment),
            'showUrl' => route('admin.shipments.show', $shipment),
            'currentAssignment' => $shipment->pickupAssignment ? [
                'id' => $shipment->pickupAssignment->id,
                'status' => $shipment->pickupAssignment->status->value,
                'driver_id' => $shipment->pickupAssignment->driver_id,
                'driver_name' => $shipment->pickupAssignment->driver?->name,
                'driver_phone' => $shipment->pickupAssignment->driver?->phone,
                'target_warehouse_id' => $shipment->pickupAssignment->target_warehouse_id,
                'warehouse_name' => $shipment->pickupAssignment->targetWarehouse?->name,
                'picked_up_at' => $shipment->pickupAssignment->picked_up_at,
            ] : null,
            'regions' => $regions,
            'shipment' => [
                'id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'status' => $shipment->status->value,
                'destination_mode' => $shipment->destination_mode->value,
                'delivery_preference' => $shipment->delivery_preference ?? 'deliver',
                'fulfillment_type' => $shipment->fulfillment_type?->value,
                'sender_notes' => $shipment->sender_notes,
                'vendor_name' => $shipment->vendor?->name,
                'vendor_phone' => $shipment->vendor?->phone,
                'pickup' => [
                    'contact_name' => $shipment->pickup_contact_name,
                    'contact_phone' => $shipment->pickup_contact_phone,
                    'region_id' => $shipment->pickup_region_id,
                    'region_name' => $shipment->pickupRegion?->name,
                    'district_id' => $shipment->pickup_district_id,
                    'district_name' => $shipment->pickupDistrict?->name,
                    'town' => $shipment->pickup_town,
                    'latitude' => $shipment->pickup_latitude,
                    'longitude' => $shipment->pickup_longitude,
                    'gh_post_address' => $shipment->pickup_gh_post_address,
                    'landmark' => $shipment->pickup_landmark,
                    'instructions' => $shipment->pickup_instructions,
                ],
                'delivery' => $shipment->destination_mode === ShipmentDestinationMode::SINGLE ? [
                    'recipient_name' => $shipment->delivery_recipient_name,
                    'recipient_phone' => $shipment->delivery_recipient_phone,
                    'region_id' => $shipment->delivery_region_id,
                    'region_name' => $shipment->deliveryRegion?->name,
                    'district_id' => $shipment->delivery_district_id,
                    'district_name' => $shipment->deliveryDistrict?->name,
                    'town' => $shipment->delivery_town,
                    'latitude' => $shipment->delivery_latitude,
                    'longitude' => $shipment->delivery_longitude,
                    'gh_post_address' => $shipment->delivery_gh_post_address,
                    'landmark' => $shipment->delivery_landmark,
                    'instructions' => $shipment->delivery_instructions,
                ] : null,
                'packages' => $shipment->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'tracking_code' => $item->tracking_code,
                        'delivery_preference' => $item->delivery_preference ?? 'deliver',
                        'fulfillment_type' => $item->fulfillment_type?->value,
                        'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                        'delivery_recipient_name' => $item->delivery_recipient_name,
                        'delivery_recipient_phone' => $item->delivery_recipient_phone,
                        'delivery_region_id' => $item->delivery_region_id,
                        'delivery_region_name' => $item->deliveryRegion?->name,
                        'delivery_district_id' => $item->delivery_district_id,
                        'delivery_district_name' => $item->deliveryDistrict?->name,
                        'delivery_town' => $item->delivery_town,
                        'delivery_landmark' => $item->delivery_landmark,
                        'delivery_instructions' => $item->delivery_instructions,
                        'photos' => $item->images->map(fn ($img) => [
                            'id' => $img->id,
                            'url' => $img->getSignedUrl()['url'] ?? null,
                            'original_name' => $img->original_name,
                            'size_human' => $img->size_human,
                            'recipient_phone' => $img->recipient_phone,
                        ])->values(),
                    ];
                })->values(),
            ],
        ];

    }

    public function updateShipment(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'destination_mode' => ['nullable', 'string', 'in:single,per_item'],
            'pickup_contact_name' => ['nullable', 'string', 'max:255'],
            'pickup_contact_phone' => ['nullable', 'string', 'max:20'],
            'pickup_region_id' => ['nullable', 'exists:regions,id'],
            'pickup_district_id' => ['nullable', 'exists:districts,id'],
            'pickup_town' => ['nullable', 'string', 'max:255'],
            'pickup_landmark' => ['nullable', 'string', 'max:255'],
            'pickup_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_preference' => ['nullable', 'string', 'in:deliver,self_pickup'],
            'fulfillment_type' => ['nullable', 'string', 'in:warehouse,direct'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        // Handle destination_mode switch
        $newMode = $validated['destination_mode'] ?? null;
        $oldMode = $shipment->destination_mode->value;

        if ($newMode && $newMode !== $oldMode) {
            if ($newMode === 'per_item') {
                // Single → Per-item: clear shipment-level delivery fields
                $validated = array_merge($validated, [
                    'delivery_recipient_name' => null,
                    'delivery_recipient_phone' => null,
                    'delivery_region_id' => null,
                    'delivery_district_id' => null,
                    'delivery_town' => null,
                    'delivery_latitude' => null,
                    'delivery_longitude' => null,
                    'delivery_gh_post_address' => null,
                    'delivery_landmark' => null,
                    'delivery_instructions' => null,
                    'delivery_preference' => null,
                    'fulfillment_type' => null,
                ]);
            } else {
                // Per-item → Single: clear all package-level delivery fields
                $shipment->items()->update([
                    'delivery_recipient_name' => null,
                    'delivery_recipient_phone' => null,
                    'delivery_region_id' => null,
                    'delivery_district_id' => null,
                    'delivery_town' => null,
                    'delivery_latitude' => null,
                    'delivery_longitude' => null,
                    'delivery_gh_post_address' => null,
                    'delivery_landmark' => null,
                    'delivery_instructions' => null,
                    'delivery_preference' => null,
                    'fulfillment_type' => null,
                ]);
            }
        }

        $shipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated.',
            'data' => ['destination_mode' => $shipment->destination_mode->value],
        ]);
    }

    public function addPackage(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $item = $shipment->items()->create([
            'description' => $validated['description'] ?? null,
            'quantity' => $validated['quantity'] ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package added.',
            'data' => ['package' => ['id' => $item->id, 'description' => $item->description, 'quantity' => $item->quantity, 'photos' => []]],
        ]);
    }

    public function updatePackage(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'delivery_preference' => ['nullable', 'string', 'in:deliver,self_pickup'],
            'fulfillment_type' => ['nullable', 'string', 'in:warehouse,direct'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $item->update($validated);

        return response()->json(['success' => true, 'message' => 'Package updated.']);
    }

    public function deletePackage(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        // Delete associated images from storage
        $storageService = app(StorageService::class);
        foreach ($item->images as $image) {
            $storageService->delete($image->path);
            $image->delete();
        }

        $item->delete();

        return response()->json(['success' => true, 'message' => 'Package deleted.']);
    }

    public function splitPackage(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $validated = $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['required', 'integer'],
        ]);

        $shipment->load([
            'pickupAssignment.warehouseReceipt.items',
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'deliveryRegion',
            'deliveryDistrict',
        ]);

        $assignment = $shipment->pickupAssignment;
        $receiptItem = $this->findReceiptItemForShipmentItem($assignment, $item);
        $splitAllowed = is_null($assignment?->picked_up_at)
            || $this->canSplitPackageDuringReceiving($assignment, $receiptItem);

        if (! $splitAllowed) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingSplitLockReason($assignment, $receiptItem) ?? 'Package can no longer be split.',
            ], 422);
        }

        $newItem = $shipment->items()->create([
            'description' => $item->description,
            'quantity' => 1,
            'status' => $item->status?->value ?? $item->getRawOriginal('status') ?? 'pending',
            'delivery_preference' => $item->delivery_preference ?? 'deliver',
            'fulfillment_type' => $item->fulfillment_type?->value ?? $item->getRawOriginal('fulfillment_type'),
            'delivery_recipient_name' => $item->delivery_recipient_name,
            'delivery_recipient_phone' => $item->delivery_recipient_phone,
            'delivery_region_id' => $item->delivery_region_id,
            'delivery_district_id' => $item->delivery_district_id,
            'delivery_town' => $item->delivery_town,
            'delivery_landmark' => $item->delivery_landmark,
            'delivery_instructions' => $item->delivery_instructions,
            'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
        ]);

        $movedCount = ShipmentItemImage::whereIn('id', $validated['photo_ids'])
            ->where('shipment_item_id', $item->id)
            ->update(['shipment_item_id' => $newItem->id]);

        $shipment->load([
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
            'pickupAssignment.warehouseReceipt.items.photos',
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'deliveryRegion',
            'deliveryDistrict',
        ]);

        $sourceItem = $shipment->items->firstWhere('id', $item->id) ?? $item->fresh(['images', 'deliveryRegion', 'deliveryDistrict']);
        $newItem = $shipment->items->firstWhere('id', $newItem->id) ?? $newItem->fresh(['images', 'deliveryRegion', 'deliveryDistrict']);

        return response()->json([
            'success' => true,
            'message' => "{$movedCount} photo(s) moved to new package.",
            'data' => [
                'package' => $this->serializeEditorPackage($newItem),
                'source_package' => $this->serializeEditorPackage($sourceItem),
                'receiving_package' => ! is_null($assignment?->picked_up_at)
                    ? $this->serializeReceivingPackage($shipment, $newItem, $assignment)
                    : null,
                'source_receiving_package' => ! is_null($assignment?->picked_up_at)
                    ? $this->serializeReceivingPackage($shipment, $sourceItem, $assignment)
                    : null,
                'can_auto_group' => ! is_null($assignment?->picked_up_at)
                    ? $this->canAutoGroupByPhoneDuringReceiving($assignment)
                    : null,
                'auto_group_lock_reason' => ! is_null($assignment?->picked_up_at)
                    ? $this->receivingAutoGroupLockReason($assignment)
                    : null,
            ],
        ]);
    }

    public function uploadPhotos(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $storageService = app(StorageService::class);
        $uploaded = [];

        foreach ($request->file('photos', []) as $file) {
            $result = $storageService->uploadFile($file, "shipments/{$shipment->id}/items/{$item->id}");
            if ($result['success']) {
                $image = $item->images()->create([
                    'path' => $result['path'],
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
                $uploaded[] = [
                    'id' => $image->id,
                    'url' => $image->getSignedUrl()['url'] ?? null,
                    'original_name' => $image->original_name,
                    'size_human' => $image->size_human,
                    'recipient_phone' => $image->recipient_phone,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded).' photo(s) uploaded.',
            'data' => ['photos' => $uploaded],
        ]);
    }

    public function movePhoto(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'photo_id' => ['required', 'integer'],
            'target_package_id' => ['required', 'integer'],
        ]);

        $image = ShipmentItemImage::findOrFail($validated['photo_id']);
        $sourceItem = ShipmentItem::findOrFail($image->shipment_item_id);
        $targetItem = ShipmentItem::findOrFail($validated['target_package_id']);

        if ($sourceItem->shipment_id !== $shipment->id || $targetItem->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Invalid package.'], 404);
        }

        $image->update(['shipment_item_id' => $targetItem->id]);

        return response()->json(['success' => true, 'message' => 'Photo moved.']);
    }

    public function deletePhoto(Request $request, ShipmentItemImage $image): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $storageService = app(StorageService::class);
        $storageService->delete($image->path);
        $image->delete();

        return response()->json(['success' => true, 'message' => 'Photo deleted.']);
    }

    public function duplicate(Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $shipment->load([
            'items.images',
            'items.deliveryRegion:id,name',
            'items.deliveryDistrict:id,name',
            'deliveryRegion:id,name',
            'deliveryDistrict:id,name',
        ]);

        // Clone shipment
        $newShipment = $shipment->replicate([
            'shipment_number', 'status', 'submitted_at', 'cancelled_at', 'cancellation_reason',
            'current_invoice_id', 'created_at', 'updated_at', 'deleted_at',
        ]);
        $newShipment->status = ShipmentStatus::DRAFT;
        $newShipment->submitted_at = null;
        $newShipment->cancelled_at = null;
        $newShipment->cancellation_reason = null;
        $newShipment->current_invoice_id = null;
        $newShipment->save(); // shipment_number auto-generated via model boot

        // Clone packages and photo references
        foreach ($shipment->items as $item) {
            $newItem = $item->replicate([
                'tracking_code', 'status', 'created_at', 'updated_at',
            ]);
            $newItem->shipment_id = $newShipment->id;
            $newItem->tracking_code = null;
            $newItem->status = 'pending';
            $newItem->save();

            // Copy image records (point to same storage files — no re-upload)
            foreach ($item->images as $image) {
                $newItem->images()->create([
                    'path' => $image->path,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment duplicated as draft.',
            'data' => [
                'shipment_id' => $newShipment->id,
                'shipment_number' => $newShipment->shipment_number,
                'edit_url' => route('admin.shipments.edit', $newShipment),
            ],
        ]);
    }

    // ─── RECEIVING (Super Admin) ───────────────────────────────────────────────

    public function createRunFromClaims(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $driver = \App\Models\Driver::findOrFail($validated['driver_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $admin = Auth::guard('admin')->user();

        $deliveryService = app(\App\Services\Warehouse\WarehouseDeliveryService::class);
        $result = $deliveryService->createRunFromClaims($driver, $warehouse, $admin);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function custodyData(Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.view');

        $shipment->load(['items.shipment:id,shipment_number,delivery_recipient_name,delivery_town']);

        // Get all labels for this shipment's items via receipt items
        $assignment = $shipment->pickupAssignment;
        if (! $assignment) {
            return response()->json(['success' => true, 'data' => ['labels' => []]]);
        }

        $receipt = $assignment->warehouseReceipt;
        if (! $receipt) {
            return response()->json(['success' => true, 'data' => ['labels' => []]]);
        }

        $receipt->load([
            'items.labels',
            'items.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
        ]);

        $labels = [];
        foreach ($receipt->items as $receiptItem) {
            $item = $receiptItem->shipmentItem;
            foreach ($receiptItem->labels as $label) {
                $latestEvent = \App\Models\LabelCustodyEvent::where('warehouse_receipt_item_label_id', $label->id)
                    ->with('driver:id,name,phone')
                    ->latest('id')
                    ->first();
                $isClaimed = $latestEvent && $latestEvent->event_type === 'claimed';
                $isDelivered = $latestEvent && $latestEvent->event_type === 'delivered';

                $labels[] = [
                    'id' => $label->id,
                    'barcode' => $label->barcode_value,
                    'label_index' => $label->label_index,
                    'labels_total' => $label->labels_total,
                    'label_type' => $label->label_type,
                    'description' => $item?->description,
                    'tracking_code' => $item?->tracking_code,
                    'recipient_name' => $item?->delivery_recipient_name ?: $shipment->delivery_recipient_name,
                    'delivery_town' => $item?->delivery_town ?: $shipment->delivery_town,
                    'status' => $isDelivered ? 'delivered' : ($isClaimed ? 'claimed' : 'at_warehouse'),
                    'current_driver' => $isClaimed ? [
                        'id' => $latestEvent->driver_id,
                        'name' => $latestEvent->driver?->name,
                        'phone' => $latestEvent->driver?->phone,
                    ] : null,
                    'claimed_at' => $isClaimed ? $latestEvent->created_at->format('M d, H:i') : null,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => ['labels' => $labels]]);
    }

    public function adminCompletePickup(Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $assignment = $shipment->pickupAssignment;
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'No pickup assignment found.'], 422);
        }

        if ($assignment->status->value === 'completed') {
            return response()->json(['success' => false, 'message' => 'Pickup already completed.'], 422);
        }

        $now = now();
        $updates = ['status' => 'completed', 'completed_at' => $now];

        if (! $assignment->en_route_at) {
            $updates['en_route_at'] = $now;
        }
        if (! $assignment->arrived_at) {
            $updates['arrived_at'] = $now;
        }
        if (! $assignment->picked_up_at) {
            $updates['picked_up_at'] = $now;
        }

        $assignment->update($updates);

        // Update shipment status
        $shipment->update(['status' => 'picked_up']);

        return response()->json([
            'success' => true,
            'message' => 'Pickup marked as completed by admin.',
        ]);
    }

    public function receivingData(Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.view');

        $assignment = $shipment->pickupAssignment;
        if (! $assignment) {
            return response()->json([
                'success' => false,
                'message' => 'No pickup assignment found for this shipment.',
                'data' => [
                    'packages' => [],
                    'can_receive' => false,
                    'receipt' => null,
                    'can_auto_group' => false,
                    'auto_group_lock_reason' => 'No pickup assignment found for this shipment.',
                ],
            ]);
        }

        $shipment = $this->reloadReceivingShipment($shipment);
        $assignment = $shipment->pickupAssignment;
        $receipt = $assignment->warehouseReceipt;
        $packages = $shipment->items
            ->map(fn (ShipmentItem $item) => $this->serializeReceivingPackage($shipment, $item, $assignment))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $packages,
                'can_receive' => ! is_null($assignment->picked_up_at),
                'receipt' => $receipt ? [
                    'id' => $receipt->id,
                    'status' => $receipt->status,
                ] : null,
                'assignment_id' => $assignment->id,
                'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($assignment),
                'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($assignment),
            ],
        ]);
    }

    public function saveReceivingPackageDetails(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $assignment = $shipment->pickupAssignment;
        if (! $assignment || is_null($assignment->picked_up_at)) {
            return response()->json(['success' => false, 'message' => 'Shipment has not been picked up yet.'], 422);
        }

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
        ]);

        $this->applyReceivingPackageDetails($shipment, $item, $validated, false);

        return response()->json([
            'success' => true,
            'message' => 'Package details saved.',
            'data' => $this->buildReceivingWorkspaceResponseData($shipment, $item),
        ]);
    }

    public function receivePackage(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $assignment = $shipment->pickupAssignment;
        if (! $assignment || is_null($assignment->picked_up_at)) {
            return response()->json(['success' => false, 'message' => 'Shipment has not been picked up yet.'], 422);
        }

        $warehouse = $assignment->targetWarehouse;
        if (! $warehouse) {
            return response()->json(['success' => false, 'message' => 'No target warehouse found.'], 422);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:0'],
            'damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'condition_status' => ['nullable', 'in:ok,damaged,partial'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'description' => ['nullable', 'string', 'max:500'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:12288'],
            'remove_photo_ids' => ['nullable', 'array'],
            'remove_photo_ids.*' => ['integer'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
        ]);

        $this->applyReceivingPackageDetails($shipment, $item, $validated, true);

        // If already received at warehouse, skip receipt upsert — just save the delivery details
        if ($assignment->received_at) {
            return response()->json([
                'success' => true,
                'message' => 'Package details updated.',
                'data' => $this->buildReceivingWorkspaceResponseData($shipment, $item),
            ]);
        }

        $receivingService = app(WarehouseReceivingService::class);
        $result = $receivingService->upsertReceiptItem(
            assignment: $assignment,
            shipmentItem: $item,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['received_quantity'],
            damagedQuantity: (int) ($validated['damaged_quantity'] ?? 0),
            conditionStatus: $validated['condition_status'] ?? null,
            notes: $validated['notes'] ?? null,
            photos: $request->file('photos', []),
            removePhotoIds: $validated['remove_photo_ids'] ?? []
        );

        if (($result['success'] ?? false) === true) {
            $result['data'] = array_merge(
                $result['data'] ?? [],
                $this->buildReceivingWorkspaceResponseData($shipment, $item)
            );
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function printPackageLabel(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        if ($item->shipment_id !== $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $assignment = $shipment->pickupAssignment;
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'No pickup assignment found.'], 422);
        }

        $warehouse = $assignment->targetWarehouse;
        if (! $warehouse) {
            return response()->json(['success' => false, 'message' => 'No target warehouse found.'], 422);
        }

        $labelCount = max(1, min(500, (int) ($request->input('label_count', 1))));
        $labelType = $labelCount === 1 ? 'sealed' : 'unit';

        $receivingService = app(WarehouseReceivingService::class);
        $result = $receivingService->generateLabels(
            assignment: $assignment,
            shipmentItem: $item,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            labelCount: $labelCount,
            labelType: $labelType
        );

        if (($result['success'] ?? false) === true) {
            $result['data'] = array_merge(
                $result['data'] ?? [],
                $this->buildReceivingWorkspaceResponseData($shipment, $item)
            );
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    protected function serializeEditorPackage(ShipmentItem $item): array
    {
        $item->loadMissing(['images', 'deliveryRegion', 'deliveryDistrict']);

        return [
            'id' => $item->id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'tracking_code' => $item->tracking_code,
            'delivery_preference' => $item->delivery_preference ?? 'deliver',
            'fulfillment_type' => $item->fulfillment_type?->value ?? $item->getRawOriginal('fulfillment_type'),
            'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
            'delivery_recipient_name' => $item->delivery_recipient_name,
            'delivery_recipient_phone' => $item->delivery_recipient_phone,
            'delivery_region_id' => $item->delivery_region_id,
            'delivery_region_name' => $item->deliveryRegion?->name,
            'delivery_district_id' => $item->delivery_district_id,
            'delivery_district_name' => $item->deliveryDistrict?->name,
            'delivery_town' => $item->delivery_town,
            'delivery_landmark' => $item->delivery_landmark,
            'delivery_instructions' => $item->delivery_instructions,
            'photos' => $item->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->getSignedUrl()['url'] ?? null,
                'original_name' => $img->original_name,
                'size_human' => $img->size_human,
                'recipient_phone' => $img->recipient_phone,
            ])->values(),
        ];
    }

    protected function reloadReceivingShipment(Shipment $shipment): Shipment
    {
        return $shipment->load([
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'deliveryRegion',
            'deliveryDistrict',
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
            'pickupAssignment.warehouseReceipt.items.photos',
        ]);
    }

    protected function findReceiptItemForShipmentItem(?\App\Models\PickupAssignment $assignment, ShipmentItem $item): ?WarehouseReceiptItem
    {
        if (! $assignment?->relationLoaded('warehouseReceipt')) {
            $assignment?->load('warehouseReceipt.items.photos');
        } elseif ($assignment->warehouseReceipt && ! $assignment->warehouseReceipt->relationLoaded('items')) {
            $assignment->warehouseReceipt->load('items.photos');
        }

        return $assignment?->warehouseReceipt?->items?->firstWhere('shipment_item_id', $item->id);
    }

    protected function canAutoGroupByPhoneDuringReceiving(?\App\Models\PickupAssignment $assignment): bool
    {
        if (! $assignment?->picked_up_at) {
            return false;
        }

        if ($assignment->received_at) {
            return false;
        }

        if (! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt.items');
        } elseif ($assignment->warehouseReceipt && ! $assignment->warehouseReceipt->relationLoaded('items')) {
            $assignment->warehouseReceipt->load('items');
        }

        return ! $assignment->warehouseReceipt?->items?->isNotEmpty();
    }

    protected function receivingAutoGroupLockReason(?\App\Models\PickupAssignment $assignment): ?string
    {
        if (! $assignment) {
            return 'No pickup assignment found for this shipment.';
        }

        if (! $assignment->picked_up_at) {
            return 'This shipment can only be auto-grouped from Receiving after pickup.';
        }

        if ($assignment->received_at) {
            return 'Warehouse receiving has already been finalized for this shipment.';
        }

        if (! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt.items');
        } elseif ($assignment->warehouseReceipt && ! $assignment->warehouseReceipt->relationLoaded('items')) {
            $assignment->warehouseReceipt->load('items');
        }

        if ($assignment->warehouseReceipt?->items?->isNotEmpty()) {
            return 'Auto-group by phone is only available before warehouse receiving starts.';
        }

        return null;
    }

    protected function canSplitPackageDuringReceiving(?\App\Models\PickupAssignment $assignment, ?WarehouseReceiptItem $receiptItem): bool
    {
        if (! $assignment?->picked_up_at) {
            return false;
        }

        if (! $receiptItem) {
            return true;
        }

        return (int) $receiptItem->received_quantity === 0
            && (int) $receiptItem->barcode_print_count === 0;
    }

    protected function receivingSplitLockReason(?\App\Models\PickupAssignment $assignment, ?WarehouseReceiptItem $receiptItem): ?string
    {
        if (! $assignment?->picked_up_at) {
            return 'This package can only be split from Receiving after pickup.';
        }

        if (! $receiptItem) {
            return null;
        }

        if ((int) $receiptItem->barcode_print_count > 0) {
            return 'Labels have already been printed for this package.';
        }

        if ((int) $receiptItem->received_quantity > 0) {
            return 'This package has already been received at the warehouse.';
        }

        return null;
    }

    protected function serializeReceivingPackage(
        Shipment $shipment,
        ShipmentItem $item,
        ?\App\Models\PickupAssignment $assignment = null,
        ?WarehouseReceiptItem $receiptItem = null,
    ): array {
        $receivingService = app(WarehouseReceivingService::class);
        $storageService = app(StorageService::class);

        $shipment->loadMissing([
            'deliveryRegion',
            'deliveryDistrict',
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
            'pickupAssignment.warehouseReceipt.items.photos',
        ]);

        $item->loadMissing(['images', 'deliveryRegion', 'deliveryDistrict']);

        $assignment ??= $shipment->pickupAssignment;
        $receiptItem ??= $this->findReceiptItemForShipmentItem($assignment, $item);

        $driverConfirmation = $assignment?->itemConfirmations?->firstWhere('shipment_item_id', $item->id);
        $driverPhotos = $assignment
            ? $assignment->photos
                ->where('shipment_item_id', $item->id)
                ->values()
                ->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => $storageService->getUrl($photo->path),
                ])->values()
            : collect();

        $vendorPhotos = $item->images->map(fn ($img) => [
            'id' => $img->id,
            'url' => $img->getSignedUrl()['url'] ?? null,
            'original_name' => $img->original_name,
            'size_human' => $img->size_human,
            'recipient_phone' => $img->recipient_phone,
        ])->values();

        $usesShipmentDeliveryDetails = $shipment->destination_mode === ShipmentDestinationMode::SINGLE;
        $deliverySource = $usesShipmentDeliveryDetails ? $shipment : $item;

        return [
            'shipment_item_id' => $item->id,
            'description' => $item->description,
            'tracking_code' => $item->tracking_code,
            'vendor_quantity' => (int) $item->quantity,
            'driver_confirmed_quantity' => $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null,
            'expected_quantity' => $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : (int) $item->quantity,
            'vendor_photos' => $vendorPhotos,
            'driver_photos' => $driverPhotos,
            'received_quantity' => (int) ($receiptItem?->received_quantity ?? 0),
            'damaged_quantity' => (int) ($receiptItem?->damaged_quantity ?? 0),
            'condition_status' => $receiptItem?->condition_status ?? 'ok',
            'notes' => $receiptItem?->notes,
            'barcode_value' => $receiptItem?->barcode_value,
            'barcode_print_count' => (int) ($receiptItem?->barcode_print_count ?? 0),
            'delivery_recipient_name' => $deliverySource->delivery_recipient_name,
            'delivery_recipient_phone' => $deliverySource->delivery_recipient_phone,
            'delivery_region_id' => $deliverySource->delivery_region_id,
            'delivery_region_name' => $deliverySource->deliveryRegion?->name,
            'delivery_district_id' => $deliverySource->delivery_district_id,
            'delivery_district_name' => $deliverySource->deliveryDistrict?->name,
            'delivery_town' => $deliverySource->delivery_town,
            'delivery_landmark' => $deliverySource->delivery_landmark,
            'delivery_instructions' => $deliverySource->delivery_instructions,
            'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
            'photos' => $receiptItem
                ? $receivingService->serializeReceiptItem($receiptItem)['photos']
                : [],
            'can_split' => $this->canSplitPackageDuringReceiving($assignment, $receiptItem),
            'split_lock_reason' => $this->receivingSplitLockReason($assignment, $receiptItem),
        ];
    }

    protected function buildReceivingPackageItemUpdates(array $validated, bool $allowQuantity): array
    {
        $updates = [];

        if (array_key_exists('description', $validated)) {
            $updates['description'] = $validated['description'];
        }

        if ($allowQuantity && array_key_exists('quantity', $validated)) {
            $updates['quantity'] = $validated['quantity'];
        }

        if (array_key_exists('delivery_recipient_name', $validated)) {
            $updates['delivery_recipient_name'] = $validated['delivery_recipient_name'] ?? null;
        }

        if (array_key_exists('delivery_recipient_phone', $validated)) {
            $updates['delivery_recipient_phone'] = ! empty($validated['delivery_recipient_phone'])
                ? PhoneHelper::format($validated['delivery_recipient_phone'])
                : null;
        }

        if (array_key_exists('delivery_region_id', $validated)) {
            $updates['delivery_region_id'] = $validated['delivery_region_id'] ?? null;
        }

        if (array_key_exists('delivery_district_id', $validated)) {
            $updates['delivery_district_id'] = $validated['delivery_district_id'] ?? null;
        }

        if (array_key_exists('delivery_town', $validated)) {
            $updates['delivery_town'] = $validated['delivery_town'] ?? null;
        }

        if (array_key_exists('delivery_landmark', $validated)) {
            $updates['delivery_landmark'] = $validated['delivery_landmark'] ?? null;
        }

        if (array_key_exists('delivery_instructions', $validated)) {
            $updates['delivery_instructions'] = $validated['delivery_instructions'] ?? null;
        }

        if (array_key_exists('delivery_method', $validated) && $validated['delivery_method'] !== null) {
            $updates['delivery_method'] = $validated['delivery_method'];
        }

        return $updates;
    }

    protected function buildShipmentItemCloneAttributes(ShipmentItem $item, array $overrides = []): array
    {
        return array_merge([
            'description' => $item->description,
            'quantity' => 1,
            'status' => $item->status?->value ?? $item->getRawOriginal('status') ?? 'pending',
            'delivery_preference' => $item->delivery_preference ?? 'deliver',
            'fulfillment_type' => $item->fulfillment_type?->value ?? $item->getRawOriginal('fulfillment_type'),
            'delivery_recipient_name' => $item->delivery_recipient_name,
            'delivery_recipient_phone' => $item->delivery_recipient_phone,
            'delivery_region_id' => $item->delivery_region_id,
            'delivery_district_id' => $item->delivery_district_id,
            'delivery_town' => $item->delivery_town,
            'delivery_landmark' => $item->delivery_landmark,
            'delivery_instructions' => $item->delivery_instructions,
            'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
        ], $overrides);
    }

    protected function buildReceivingShipmentDeliveryUpdates(array $validated): array
    {
        $updates = [];

        foreach ([
            'delivery_recipient_name',
            'delivery_region_id',
            'delivery_district_id',
            'delivery_town',
            'delivery_landmark',
            'delivery_instructions',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $updates[$key] = $validated[$key] ?? null;
            }
        }

        if (array_key_exists('delivery_recipient_phone', $validated)) {
            $updates['delivery_recipient_phone'] = ! empty($validated['delivery_recipient_phone'])
                ? PhoneHelper::format($validated['delivery_recipient_phone'])
                : null;
        }

        return $updates;
    }

    protected function applyReceivingPackageDetails(
        Shipment $shipment,
        ShipmentItem $item,
        array $validated,
        bool $allowQuantity,
    ): void {
        $itemUpdates = $this->buildReceivingPackageItemUpdates($validated, $allowQuantity);
        if (! empty($itemUpdates)) {
            $item->update($itemUpdates);
        }

        if ($shipment->destination_mode === ShipmentDestinationMode::SINGLE) {
            $shipmentUpdates = $this->buildReceivingShipmentDeliveryUpdates($validated);
            if (! empty($shipmentUpdates)) {
                $shipment->update($shipmentUpdates);
            }
        }
    }

    protected function buildReceivingWorkspaceResponseData(Shipment $shipment, ShipmentItem $item): array
    {
        $shipment = $this->reloadReceivingShipment($shipment);
        $assignment = $shipment->pickupAssignment;
        $serializedItem = $shipment->items->firstWhere('id', $item->id) ?? $item;

        $data = [
            'package' => $this->serializeReceivingPackage($shipment, $serializedItem, $assignment),
            'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($assignment),
            'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($assignment),
        ];

        if ($shipment->destination_mode === ShipmentDestinationMode::SINGLE) {
            $data['packages'] = $shipment->items
                ->map(fn (ShipmentItem $candidate) => $this->serializeReceivingPackage($shipment, $candidate, $assignment))
                ->values();
        }

        return $data;
    }

    protected function mergePickupItemConfirmationsForAutoGroup(int $assignmentId, array $sourceItemIds, int $targetItemId): void
    {
        $sourceIds = collect($sourceItemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== $targetItemId)
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            return;
        }

        $targetConfirmation = PickupItemConfirmation::query()
            ->where('pickup_assignment_id', $assignmentId)
            ->where('shipment_item_id', $targetItemId)
            ->first();

        $sourceConfirmations = PickupItemConfirmation::query()
            ->where('pickup_assignment_id', $assignmentId)
            ->whereIn('shipment_item_id', $sourceIds->all())
            ->orderBy('id')
            ->get();

        foreach ($sourceConfirmations as $confirmation) {
            if (! $targetConfirmation) {
                $confirmation->update(['shipment_item_id' => $targetItemId]);
                $targetConfirmation = $confirmation->fresh();
                continue;
            }

            $confirmedAt = $targetConfirmation->confirmed_at ?? $confirmation->confirmed_at;
            if ($targetConfirmation->confirmed_at && $confirmation->confirmed_at && $confirmation->confirmed_at->gt($targetConfirmation->confirmed_at)) {
                $confirmedAt = $confirmation->confirmed_at;
            }

            $notes = collect([$targetConfirmation->notes, $confirmation->notes])
                ->filter()
                ->unique()
                ->implode("\n");

            $targetConfirmation->update([
                'expected_quantity' => (int) $targetConfirmation->expected_quantity + (int) $confirmation->expected_quantity,
                'confirmed_quantity' => (int) $targetConfirmation->confirmed_quantity + (int) $confirmation->confirmed_quantity,
                'notes' => $notes ?: null,
                'confirmed_at' => $confirmedAt,
            ]);

            $confirmation->delete();
        }
    }

    protected function reassignAutoGroupDependencies(
        Shipment $shipment,
        ?\App\Models\PickupAssignment $assignment,
        array $sourceItemIds,
        int $targetItemId,
    ): void {
        $sourceIds = collect($sourceItemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== $targetItemId)
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            return;
        }

        if ($assignment) {
            $this->mergePickupItemConfirmationsForAutoGroup($assignment->id, $sourceIds->all(), $targetItemId);

            PickupPhoto::query()
                ->where('pickup_assignment_id', $assignment->id)
                ->whereIn('shipment_item_id', $sourceIds->all())
                ->update(['shipment_item_id' => $targetItemId]);
        }

        ShipmentCharge::query()
            ->where('shipment_id', $shipment->id)
            ->whereIn('shipment_item_id', $sourceIds->all())
            ->update(['shipment_item_id' => $targetItemId]);

        ShipmentItemTracking::query()
            ->whereIn('shipment_item_id', $sourceIds->all())
            ->update(['shipment_item_id' => $targetItemId]);
    }

    public function finalizeReceiving(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $assignment = $shipment->pickupAssignment;
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'No pickup assignment found.'], 422);
        }

        if (is_null($assignment->picked_up_at)) {
            return response()->json(['success' => false, 'message' => 'Shipment has not been picked up yet.'], 422);
        }

        $warehouse = $assignment->targetWarehouse;
        if (! $warehouse) {
            return response()->json(['success' => false, 'message' => 'No target warehouse found.'], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
            'approval_reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $receivingService = app(WarehouseReceivingService::class);
        $result = $receivingService->finalizeReceipt(
            assignment: $assignment,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            notes: $validated['notes'] ?? null,
            approvalReason: $validated['approval_reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    // ─── AUTO-GROUP BY PHONE ──────────────────────────────────────────────────

    public function autoGroupByPhone(Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $shipment->load([
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'deliveryRegion',
            'deliveryDistrict',
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
            'pickupAssignment.warehouseReceipt.items',
        ]);

        $assignment = $shipment->pickupAssignment;
        if (! is_null($assignment?->picked_up_at) && ! $this->canAutoGroupByPhoneDuringReceiving($assignment)) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingAutoGroupLockReason($assignment) ?? 'Auto-group by phone is no longer available for this shipment.',
            ], 422);
        }

        $allImages = $shipment->items->flatMap(fn ($item) => $item->images);

        if ($allImages->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No photos to group.'], 400);
        }

        $taggedImages = $allImages->filter(fn ($img) => ! empty($img->recipient_phone));

        if ($taggedImages->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No photos have recipient phone tags. Tag photos with phone numbers first.'], 400);
        }

        $grouped = $allImages->groupBy(fn ($img) => $img->recipient_phone ?: '__untagged__');

        return DB::transaction(function () use ($shipment, $grouped) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $shipment->load([
                'items.images',
                'items.deliveryRegion',
                'items.deliveryDistrict',
                'deliveryRegion',
                'deliveryDistrict',
                'pickupAssignment.itemConfirmations',
                'pickupAssignment.photos',
                'pickupAssignment.warehouseReceipt.items',
            ]);

            $assignment = $shipment->pickupAssignment;
            $sourceItems = $shipment->items->keyBy('id');
            $usedItemIds = collect();
            $groupedItems = [];

            foreach ($grouped as $phone => $images) {
                $isUntagged = $phone === '__untagged__';
                $images = collect($images)->values();
                $sourceItemIds = $images->pluck('shipment_item_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $sourceItem = $sourceItems->get($sourceItemIds->first());
                if (! $sourceItem) {
                    continue;
                }

                $keeper = null;
                foreach ($sourceItemIds as $sourceItemId) {
                    if (! $usedItemIds->contains($sourceItemId)) {
                        $keeper = $sourceItems->get($sourceItemId);
                        break;
                    }
                }

                if (! $keeper) {
                    $keeper = $shipment->items()->create(
                        $this->buildShipmentItemCloneAttributes($sourceItem, [
                            'quantity' => max(1, $images->count()),
                            'delivery_recipient_phone' => $isUntagged ? null : $phone,
                        ])
                    );
                    $sourceItems->put($keeper->id, $keeper);
                } else {
                    $keeper->update([
                        'quantity' => max(1, $images->count()),
                        'delivery_recipient_phone' => $isUntagged ? null : $phone,
                    ]);
                }

                $usedItemIds->push((int) $keeper->id);

                ShipmentItemImage::query()
                    ->whereIn('id', $images->pluck('id')->all())
                    ->update(['shipment_item_id' => $keeper->id]);

                $this->reassignAutoGroupDependencies($shipment, $assignment, $sourceItemIds->all(), $keeper->id);

                $groupedItems[] = [
                    'id' => $keeper->id,
                    'phone' => $isUntagged ? null : $phone,
                    'photos_count' => $images->count(),
                ];
            }

            $keepItemIds = collect($groupedItems)->pluck('id')->unique()->values();
            $shipment->items()->whereNotIn('id', $keepItemIds)->get()->each(function ($item) {
                if ($item->images()->count() === 0) {
                    $item->delete();
                }
            });

            $multipleGroups = count($groupedItems) > 1;
            $newMode = $multipleGroups ? ShipmentDestinationMode::PER_ITEM : ShipmentDestinationMode::SINGLE;

            $shipmentUpdate = ['destination_mode' => $newMode];

            if ($newMode === ShipmentDestinationMode::SINGLE) {
                $shipmentUpdate['delivery_recipient_phone'] = collect($groupedItems)->pluck('phone')->filter()->first();
            } else {
                $shipmentUpdate['delivery_recipient_phone'] = $shipment->delivery_recipient_phone;
            }

            $shipment->update($shipmentUpdate);

            $shipment->load([
                'items.images',
                'items.deliveryRegion',
                'items.deliveryDistrict',
                'deliveryRegion',
                'deliveryDistrict',
                'pickupAssignment.itemConfirmations',
                'pickupAssignment.photos',
                'pickupAssignment.warehouseReceipt.items',
            ]);

            return response()->json([
                'success' => true,
                'message' => count($groupedItems).' package(s) created. Destination mode set to '.$newMode->value.'.',
                'data' => [
                    'destination_mode' => $newMode->value,
                    'delivery_recipient_phone' => $shipment->fresh()->delivery_recipient_phone,
                    'packages' => $shipment->items->map(fn ($item) => [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                        'delivery_recipient_phone' => $item->delivery_recipient_phone,
                        'delivery_recipient_name' => $item->delivery_recipient_name,
                        'photos' => $item->images->map(fn ($img) => [
                            'id' => $img->id,
                            'url' => app(\App\Services\StorageService::class)->getUrl($img->path),
                            'original_name' => $img->original_name,
                            'recipient_phone' => $img->recipient_phone,
                        ])->values(),
                    ])->values(),
                    'receiving_packages' => ! is_null($assignment?->picked_up_at)
                        ? $shipment->items->map(fn (ShipmentItem $item) => $this->serializeReceivingPackage($shipment, $item, $assignment))->values()
                        : null,
                    'can_auto_group' => ! is_null($assignment?->picked_up_at)
                        ? $this->canAutoGroupByPhoneDuringReceiving($assignment)
                        : null,
                    'auto_group_lock_reason' => ! is_null($assignment?->picked_up_at)
                        ? $this->receivingAutoGroupLockReason($assignment)
                        : null,
                ],
            ]);
        });
    }
}
