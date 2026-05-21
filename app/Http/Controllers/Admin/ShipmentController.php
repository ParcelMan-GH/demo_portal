<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Enums\FulfillmentType;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Exports\ShipmentsExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LabelCustodyEvent;
use App\Models\PickupItemConfirmation;
use App\Models\PickupPhoto;
use App\Models\Region;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Services\StorageService;
use App\Services\WalkinShipmentService;
use App\Services\Warehouse\WarehouseReceivingService;
use App\Services\BackOfficeAccess;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentController extends Controller
{
    public function index()
    {
        $this->authorizePermission('shipments.view');

        return view('admin.shipments.index', [
            'statuses' => ShipmentStatus::toArray(),
            'pickupStatuses' => PickupAssignmentStatus::toArray(),
            'sources' => array_map(
                fn (ShipmentSource $source) => ['value' => $source->value, 'label' => $source->label()],
                ShipmentSource::cases()
            ),
            'destinationModes' => ShipmentDestinationMode::toArray(),
            'fulfillmentTypes' => array_map(
                fn (FulfillmentType $type) => ['value' => $type->value, 'label' => $type->label()],
                FulfillmentType::cases()
            ),
            'deliveryPreferences' => [
                ['value' => 'deliver', 'label' => 'Deliver to Recipient'],
                ['value' => 'self_pickup', 'label' => 'Pickup at Warehouse'],
            ],
            'regions' => Region::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'drivers' => Driver::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']),
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizePermission('shipments.view');

        $query = Shipment::with([
            'vendor',
            'deliveryRegion',
            'deliveryDistrict',
            'pickupRegion',
            'pickupDistrict',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
        ])->withCount('items');

        $this->applyShipmentIndexFilters($query, $request, includeSummaryState: false);
        $summary = $this->buildShipmentIndexSummary(clone $query);
        $this->applyShipmentSummaryStateFilter($query, $request->get('summary_state'));

        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['shipment_number', 'destination_mode', 'delivery_recipient_name', 'pickup_contact_name', 'status', 'source', 'created_at', 'submitted_at', 'vendor_declared_quantity', 'items_count'];

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
                $assignment = $shipment->pickupAssignment;
                $pickupLocation = collect([
                    $shipment->pickup_town,
                    $shipment->pickupDistrict?->name,
                    $shipment->pickupRegion?->name,
                ])->filter()->implode(', ');

                return [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'view_url' => route('admin.orders.show', $shipment),
                    'vendor_name' => $shipment->vendor?->name,
                    'vendor_business' => $shipment->vendor?->business_name,
                    'vendor_phone' => $shipment->vendor?->phone,
                    'pickup_contact_name' => $shipment->pickup_contact_name,
                    'pickup_contact_phone' => $shipment->pickup_contact_phone,
                    'pickup_location' => $pickupLocation,
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
                    'pickup_assignment_id' => $assignment?->id,
                    'pickup_status' => $assignment?->status?->value ?? null,
                    'pickup_status_label' => $assignment?->status?->label() ?? 'Pending',
                    'pickup_driver_name' => $assignment?->driver?->name,
                    'pickup_driver_phone' => $assignment?->driver?->phone,
                    'target_warehouse_id' => $assignment?->target_warehouse_id,
                    'target_warehouse_name' => $assignment?->targetWarehouse?->name,
                    'target_warehouse_code' => $assignment?->targetWarehouse?->code,
                    'assigned_at' => $assignment?->assigned_at?->format('Y-m-d H:i:s'),
                    'picked_up_at' => $assignment?->picked_up_at?->format('Y-m-d H:i:s'),
                    'arrived_warehouse_at' => $assignment?->arrived_warehouse_at?->format('Y-m-d H:i:s'),
                    'received_at' => $assignment?->received_at?->format('Y-m-d H:i:s'),
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
            'summary' => $summary,
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
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
            'pickupAssignment.receivedWarehouse',
            'pickupAssignment.warehouseReceipt.items',
            'pickupAssignments.driver',
            'pickupAssignments.assignedBy',
            'pickupAssignments.targetWarehouse',
            'pickupAssignments.receivedWarehouse',
        ]);

        $itemsCount = $shipment->items()->count();
        $currentAdmin = Auth::guard('admin')->user();
        $access = app(BackOfficeAccess::class);
        $canManage = $currentAdmin
            && ($access->isHq($currentAdmin) || $access->canUsePermission($currentAdmin, 'shipments.edit'));
        $currentAssignment = $shipment->pickupAssignment;
        if ($currentAssignment?->status === PickupAssignmentStatus::CANCELLED) {
            $currentAssignment = null;
        }

        $vendorDeclaredQuantity = (int) ($shipment->vendor_declared_quantity ?? $shipment->items()->sum('quantity'));
        $driverPickedQuantity = $currentAssignment && ! is_null($currentAssignment->driver_picked_quantity)
            ? (int) $currentAssignment->driver_picked_quantity
            : null;
        $warehouseReceivedQuantity = $currentAssignment?->warehouseReceipt?->items
            ? (int) $currentAssignment->warehouseReceipt->items->sum('received_quantity')
            : null;
        $comparisonQuantity = $warehouseReceivedQuantity ?? $driverPickedQuantity;
        $quantityDifference = is_null($comparisonQuantity)
            ? null
            : (int) $comparisonQuantity - $vendorDeclaredQuantity;

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
            'currentAssignment' => $currentAssignment,
            'itemsCount' => $itemsCount,
            'canManage' => $canManage,
            'assignmentHistory' => $assignmentHistory,
            'quantitySummary' => [
                'vendor_declared' => $vendorDeclaredQuantity,
                'driver_picked' => $driverPickedQuantity,
                'warehouse_received' => $warehouseReceivedQuantity,
                'difference' => $quantityDifference,
            ],
            'statuses' => ShipmentStatus::toArray(),
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
            'label' => 'Order Created',
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

        $query = Shipment::with([
            'vendor',
            'deliveryRegion',
            'deliveryDistrict',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
        ])->withCount('items');

        $this->applyShipmentIndexFilters($query, $request);

        $shipments = $query->orderBy('created_at', 'desc')->get();

        $rows = $shipments->map(function ($s) {
            $summary = $this->buildDestinationSummary($s);
            $location = $this->buildDeliveryLocationSummary($s);
            $assignment = $s->pickupAssignment;

            return [
                'Order #' => $s->shipment_number,
                'Vendor' => $s->vendor?->name,
                'Pickup Contact' => trim(($s->pickup_contact_name ?: '-').' / '.($s->pickup_contact_phone ?: '-'), ' /'),
                'Pickup Driver' => $assignment?->driver?->name,
                'Target Warehouse' => $assignment?->targetWarehouse?->name,
                'Destination Mode' => $s->destination_mode?->label() ?? 'Single Destination',
                'Destination Summary' => trim($summary['title'].' - '.$summary['subtitle'], ' -'),
                'Delivery Location' => trim($location['title'].' - '.$location['subtitle'], ' -'),
                'Items' => $s->items_count,
                'Status' => $s->status->label(),
                'Pickup Status' => $assignment?->status?->label() ?? 'Pending',
                'Submitted At' => $s->submitted_at?->format('Y-m-d H:i:s'),
                'Created At' => $s->created_at->format('Y-m-d H:i:s'),
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new ShipmentsExport($rows), 'orders_'.date('Y-m-d_His').'.xlsx');
        }

        if ($format === 'pdf') {
            $filename = 'orders_'.date('Y-m-d_His').'.pdf';

            return GenericPdfExporter::download($rows, $filename, 'Orders List');
        }

        return response()->json(['data' => $rows]);
    }

    private function applyShipmentIndexFilters($query, Request $request, bool $includeSummaryState = true): void
    {
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    ->orWhere('pickup_contact_name', 'like', "%{$search}%")
                    ->orWhere('pickup_contact_phone', 'like', "%{$search}%")
                    ->orWhere('pickup_town', 'like', "%{$search}%")
                    ->orWhere('delivery_town', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('description', 'like', "%{$search}%")
                            ->orWhere('tracking_code', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                            ->orWhere('delivery_town', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('pickupAssignment.driver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('vehicle_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('pickupAssignment.targetWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        foreach ([
            'source' => 'source',
            'destination_mode' => 'destination_mode',
            'delivery_preference' => 'delivery_preference',
            'fulfillment_type' => 'fulfillment_type',
            'pickup_region_id' => 'pickup_region_id',
            'delivery_region_id' => 'delivery_region_id',
        ] as $requestKey => $column) {
            if ($value = $request->get($requestKey)) {
                $query->where($column, $value);
            }
        }

        if ($pickupStatus = $request->get('pickup_status')) {
            $query->whereHas('pickupAssignment', fn ($q) => $q->where('status', $pickupStatus));
        }

        if ($driverId = $request->get('driver_id')) {
            $query->whereHas('pickupAssignment', fn ($q) => $q->where('driver_id', $driverId));
        }

        if ($warehouseId = $request->get('target_warehouse_id')) {
            $query->whereHas('pickupAssignment', fn ($q) => $q->where('target_warehouse_id', $warehouseId));
        }

        if ($assignmentState = $request->get('assignment_state')) {
            match ($assignmentState) {
                'needs_assignment' => $query->whereDoesntHave('pickupAssignment'),
                'assigned' => $query->whereHas('pickupAssignment'),
                'active' => $query->whereHas('pickupAssignment', fn ($q) => $q->whereNotIn('status', [PickupAssignmentStatus::CANCELLED->value, PickupAssignmentStatus::COMPLETED->value])),
                'cancelled' => $query->whereHas('pickupAssignment', fn ($q) => $q->where('status', PickupAssignmentStatus::CANCELLED->value)),
                'warehouse_received' => $query->whereHas('pickupAssignment', fn ($q) => $q->whereNotNull('received_at')),
                default => null,
            };
        }

        $this->applyDateRangeFilter($query, $request, 'created_at', 'created_from', 'created_to', 'date_from', 'date_to');
        $this->applyDateRangeFilter($query, $request, 'submitted_at', 'submitted_from', 'submitted_to');

        foreach ([
            'assigned' => 'assigned_at',
            'picked' => 'picked_up_at',
            'warehouse_arrival' => 'arrived_warehouse_at',
            'received' => 'received_at',
        ] as $prefix => $column) {
            $from = $request->get($prefix.'_from');
            $to = $request->get($prefix.'_to');
            if ($from || $to) {
                $query->whereHas('pickupAssignment', function ($q) use ($column, $from, $to) {
                    if ($from) {
                        $q->whereDate($column, '>=', $from);
                    }
                    if ($to) {
                        $q->whereDate($column, '<=', $to);
                    }
                });
            }
        }

        if ($itemsMin = $request->integer('items_min')) {
            $query->has('items', '>=', $itemsMin);
        }
        if ($itemsMax = $request->integer('items_max')) {
            $query->has('items', '<=', $itemsMax);
        }

        if ($vendorQtyMin = $request->integer('vendor_qty_min')) {
            $query->where('vendor_declared_quantity', '>=', $vendorQtyMin);
        }
        if ($vendorQtyMax = $request->integer('vendor_qty_max')) {
            $query->where('vendor_declared_quantity', '<=', $vendorQtyMax);
        }

        if ($driverQtyMin = $request->integer('driver_qty_min')) {
            $query->whereHas('pickupAssignment', fn ($q) => $q->where('driver_picked_quantity', '>=', $driverQtyMin));
        }
        if ($driverQtyMax = $request->integer('driver_qty_max')) {
            $query->whereHas('pickupAssignment', fn ($q) => $q->where('driver_picked_quantity', '<=', $driverQtyMax));
        }

        if ($quantityState = $request->get('quantity_state')) {
            $receivedQtySql = "(select coalesce(sum(wri.received_quantity), 0)
                from pickup_assignments pa
                left join warehouse_receipts wr on wr.pickup_assignment_id = pa.id
                left join warehouse_receipt_items wri on wri.warehouse_receipt_id = wr.id
                where pa.shipment_id = shipments.id)";

            match ($quantityState) {
                'missing_vendor_total' => $query->whereNull('vendor_declared_quantity'),
                'missing_driver_total' => $query->whereHas('pickupAssignment', fn ($q) => $q->whereNull('driver_picked_quantity')),
                'receipt_missing' => $query->whereDoesntHave('pickupAssignment.warehouseReceipt'),
                'matched' => $query->whereRaw($receivedQtySql.' = coalesce(shipments.vendor_declared_quantity, 0)'),
                'shortage' => $query->whereRaw($receivedQtySql.' < coalesce(shipments.vendor_declared_quantity, 0)'),
                'excess' => $query->whereRaw($receivedQtySql.' > coalesce(shipments.vendor_declared_quantity, 0)'),
                'has_discrepancy' => $query->whereHas('pickupAssignment.warehouseReceipt.items', function ($q) {
                    $q->where('discrepancy_type', '!=', 'none')
                        ->orWhereColumn('received_quantity', '!=', 'expected_quantity')
                        ->orWhere('damaged_quantity', '>', 0);
                }),
                default => null,
            };
        }

        if ($includeSummaryState) {
            $this->applyShipmentSummaryStateFilter($query, $request->get('summary_state'));
        }
    }

    private function buildShipmentIndexSummary($query): array
    {
        return [
            'total' => (clone $query)->count(),
            'needs_driver' => tap(clone $query, fn ($q) => $this->applyShipmentSummaryStateFilter($q, 'needs_driver'))->count(),
            'assigned_pickup' => tap(clone $query, fn ($q) => $this->applyShipmentSummaryStateFilter($q, 'assigned_pickup'))->count(),
            'picked_up' => tap(clone $query, fn ($q) => $this->applyShipmentSummaryStateFilter($q, 'picked_up'))->count(),
            'received_warehouse' => tap(clone $query, fn ($q) => $this->applyShipmentSummaryStateFilter($q, 'received_warehouse'))->count(),
            'discrepancies' => tap(clone $query, fn ($q) => $this->applyShipmentSummaryStateFilter($q, 'discrepancies'))->count(),
        ];
    }

    private function applyShipmentSummaryStateFilter($query, ?string $state): void
    {
        if (!$state || $state === 'total') {
            return;
        }

        $activePickupStatuses = [
            PickupAssignmentStatus::ASSIGNED->value,
            PickupAssignmentStatus::EN_ROUTE->value,
            PickupAssignmentStatus::ARRIVED->value,
            PickupAssignmentStatus::PICKING_UP->value,
        ];

        $receivedQtySql = "(select coalesce(sum(wri.received_quantity), 0)
            from pickup_assignments pa
            left join warehouse_receipts wr on wr.pickup_assignment_id = pa.id
            left join warehouse_receipt_items wri on wri.warehouse_receipt_id = wr.id
            where pa.shipment_id = shipments.id)";

        match ($state) {
            'needs_driver' => $query
                ->whereNotIn('status', [ShipmentStatus::DRAFT->value, ShipmentStatus::CANCELLED->value])
                ->where(function ($q) {
                    $q->whereDoesntHave('pickupAssignment')
                        ->orWhereHas('pickupAssignment', fn ($assignment) => $assignment->where('status', PickupAssignmentStatus::CANCELLED->value));
                }),
            'assigned_pickup' => $query->whereHas('pickupAssignment', fn ($q) => $q
                ->whereIn('status', $activePickupStatuses)
                ->whereNull('picked_up_at')
                ->whereNull('received_at')),
            'picked_up' => $query->whereHas('pickupAssignment', fn ($q) => $q
                ->whereNull('received_at')
                ->where(function ($assignment) {
                    $assignment
                        ->whereNotNull('picked_up_at')
                        ->orWhere('status', PickupAssignmentStatus::COMPLETED->value);
                })),
            'received_warehouse' => $query->whereHas('pickupAssignment', fn ($q) => $q->whereNotNull('received_at')),
            'discrepancies' => $query->where(function ($q) use ($receivedQtySql) {
                $q->whereHas('pickupAssignment', fn ($assignment) => $assignment
                    ->whereNotNull('driver_picked_quantity')
                    ->whereColumn('driver_picked_quantity', '!=', 'shipments.vendor_declared_quantity'))
                    ->orWhereHas('pickupAssignment.warehouseReceipt.items', function ($items) {
                        $items->where('discrepancy_type', '!=', 'none')
                            ->orWhereColumn('received_quantity', '!=', 'expected_quantity')
                            ->orWhere('damaged_quantity', '>', 0);
                    })
                    ->orWhere(function ($received) use ($receivedQtySql) {
                        $received->whereHas('pickupAssignment.warehouseReceipt')
                            ->whereRaw($receivedQtySql.' != coalesce(shipments.vendor_declared_quantity, 0)');
                    })
                    ->orWhere(function ($received) use ($receivedQtySql) {
                        $received->whereHas('pickupAssignment', fn ($assignment) => $assignment->whereNotNull('driver_picked_quantity'))
                            ->whereHas('pickupAssignment.warehouseReceipt')
                            ->whereRaw($receivedQtySql.' != coalesce((select pa.driver_picked_quantity from pickup_assignments pa where pa.shipment_id = shipments.id order by pa.id desc limit 1), 0)');
                    });
            }),
            default => null,
        };
    }

    private function applyDateRangeFilter($query, Request $request, string $column, string $fromKey, string $toKey, ?string $legacyFromKey = null, ?string $legacyToKey = null): void
    {
        $from = $request->get($fromKey) ?: ($legacyFromKey ? $request->get($legacyFromKey) : null);
        $to = $request->get($toKey) ?: ($legacyToKey ? $request->get($legacyToKey) : null);

        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }
    }

    public function create()
    {
        $this->authorizePermission('shipments.create');

        return redirect()->route('warehouse.walkin.create');
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
            'message' => 'Walk-in order created successfully.',
            'redirect' => route('admin.orders.show', $result['shipment']->id),
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
        $user = Auth::guard('admin')->user();
        $access = app(BackOfficeAccess::class);

        if (! $user || (! $access->isHq($user) && ! $access->canUsePermission($user, $permission))) {
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

        // Legacy route — unified workspace now lives at /admin/orders/{id}
        // with the Packages tab pre-selected.
        return redirect()->route('admin.orders.show', [
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
            'saveUrl' => route('admin.orders.update', $shipment),
            'addPackageUrl' => route('admin.orders.packages.add', $shipment),
            'updatePackageUrlTemplate' => route('admin.orders.packages.update', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'deletePackageUrlTemplate' => route('admin.orders.packages.delete', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'splitPackageUrlTemplate' => route('admin.orders.packages.split', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'uploadPhotosUrlTemplate' => route('admin.orders.packages.photos.upload', ['shipment' => $shipment->id, 'item' => '__PKG__']),
            'movePhotoUrl' => route('admin.orders.packages.photos.move', $shipment),
            'deletePhotoUrlTemplate' => route('admin.orders.packages.photos.delete', ['image' => '__IMG__']),
            'townsSearchUrl' => route('admin.locations.towns.data'),
            'assignDriverEndpoint' => route('admin.assignments.assign', $shipment),
            'updateAssignmentEndpointTemplate' => $shipment->pickupAssignment
                ? route('admin.assignments.update', ['pickupAssignment' => $shipment->pickupAssignment->id])
                : null,
            'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
            'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
            'canEditShipmentFields' => true,
            'duplicateUrl' => route('admin.orders.duplicate', $shipment),
            'autoGroupByPhoneUrl' => route('admin.orders.auto-group-by-phone', $shipment),
            'showUrl' => route('admin.orders.show', $shipment),
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
        $oldMode = $shipment->destination_mode?->value ?? ShipmentDestinationMode::SINGLE->value;

        if ($newMode && $newMode !== $oldMode) {
            if ($newMode === 'per_item') {
                $this->seedPackageDeliveryFromShipment($shipment);
                $validated = array_merge($validated, $this->emptyShipmentDeliveryAttributes());
            } else {
                $validated = array_merge($this->shipmentDeliverySeedFromItems($shipment), $validated);
                $shipment->items()->update($this->emptyItemDeliveryAttributes());
            }
        }

        $shipment->update($validated);
        $shipment->load(['pickupRegion', 'pickupDistrict', 'deliveryRegion', 'deliveryDistrict']);

        return response()->json([
            'success' => true,
            'message' => 'Order updated.',
            'data' => [
                'destination_mode' => $shipment->destination_mode->value,
                'delivery' => $this->serializeShipmentDelivery($shipment),
                'pickup' => [
                    'contact_name' => $shipment->pickup_contact_name,
                    'contact_phone' => $shipment->pickup_contact_phone,
                    'region_id' => $shipment->pickup_region_id,
                    'region_name' => $shipment->pickupRegion?->name,
                    'district_id' => $shipment->pickup_district_id,
                    'district_name' => $shipment->pickupDistrict?->name,
                    'town' => $shipment->pickup_town,
                    'landmark' => $shipment->pickup_landmark,
                    'instructions' => $shipment->pickup_instructions,
                ],
            ],
        ]);
    }

    public function addPackage(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $shipment = $this->reloadReceivingShipment($shipment);
        $assignment = $shipment->pickupAssignment;
        $pickupComplete = $this->isPickupCompleteForReceiving($shipment, $assignment);
        $canReceive = $this->canReceiveInAdminWorkspace($assignment);

        if ($pickupComplete && ! $this->canAddPackageDuringReceiving($assignment, $shipment)) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingAddPackageLockReason($assignment, $shipment) ?? 'Packages can no longer be added for this shipment.',
            ], 422);
        }

        if ($pickupComplete && ! $assignment?->targetWarehouse) {
            return response()->json([
                'success' => false,
                'message' => 'No target warehouse found for this pickup.',
            ], 422);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:500'],
            'quantity' => ['required', 'integer', 'min:1'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
            'delivery_fee_mode' => ['nullable', 'in:none,collect,paid'],
            'delivery_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'delivery_fee_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_fee_payment_method' => ['nullable', 'string', 'max:32'],
            'delivery_fee_payment_reference' => ['nullable', 'string', 'max:100'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:12288'],
        ]);

        $item = $shipment->items()->create([
            'description' => $validated['description'] ?? null,
            'quantity' => $validated['quantity'] ?? 1,
            'status' => $this->initialStatusForAdminCreatedPackage($shipment, $assignment),
        ]);

        $this->applyReceivingPackageDetails($shipment, $item, $validated, true);
        $this->syncReceivingPackageDeliveryFee($shipment, $item, $validated);

        if ($canReceive) {
            if (! $pickupComplete) {
                $assignment = $this->completePickupForAdminReceiving(
                    $shipment,
                    $assignment,
                    Auth::guard('admin')->user(),
                    'Pickup auto-completed during warehouse receiving because the driver could not confirm pickup from mobile.'
                );
                $shipment = $this->reloadReceivingShipment($shipment->fresh());
                $item = $shipment->items->firstWhere('id', $item->id) ?? $item->fresh();
            }

            $receivingResult = app(WarehouseReceivingService::class)->upsertReceiptItem(
                assignment: $assignment,
                shipmentItem: $item,
                warehouse: $assignment->targetWarehouse,
                user: Auth::guard('admin')->user(),
                receivedQuantity: (int) $validated['quantity'],
                damagedQuantity: 0,
                conditionStatus: 'ok',
                notes: null,
                photos: $request->file('photos', []),
                removePhotoIds: []
            );

            if (($receivingResult['success'] ?? false) !== true) {
                return response()->json($receivingResult, 422);
            }
        }

        if ($assignment && $this->isPickupCompleteForReceiving($shipment, $assignment)) {
            $this->ensurePickupConfirmationForAdminCreatedPackage($assignment, $item);
        }

        $shipment = $this->reloadReceivingShipment($shipment);
        $item = $shipment->items->firstWhere('id', $item->id) ?? $item->fresh(['images', 'deliveryRegion', 'deliveryDistrict']);
        $assignment = $shipment->pickupAssignment;

        return response()->json([
            'success' => true,
            'message' => 'Package added.',
            'data' => [
                'package' => $this->serializeEditorPackage($item),
                'receiving_package' => $canReceive
                    ? $this->serializeReceivingPackage($shipment, $item, $assignment)
                    : null,
                'can_receive' => $this->canReceiveInAdminWorkspace($assignment),
                'assignment_id' => $assignment?->id,
                'can_auto_group' => $canReceive
                    ? $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment)
                    : null,
                'auto_group_lock_reason' => $canReceive
                    ? $this->receivingAutoGroupLockReason($assignment, $shipment)
                    : null,
            ],
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

        $shipment = $this->reloadReceivingShipment($shipment);
        $assignment = $shipment->pickupAssignment;
        $pickupComplete = $this->isPickupCompleteForReceiving($shipment, $assignment);
        $receiptItem = $this->findReceiptItemForShipmentItem($assignment, $item);

        if (! $this->canRemovePackageDuringReceiving($assignment, $receiptItem, $item, $shipment)) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingRemoveLockReason($assignment, $receiptItem, $item, $shipment) ?? 'This package can no longer be removed.',
            ], 422);
        }

        // Delete associated images from storage
        $storageService = app(StorageService::class);

        $deletedItemId = $item->id;

        DB::transaction(function () use ($item, $receiptItem, $storageService) {
            ShipmentCharge::query()
                ->where('shipment_item_id', $item->id)
                ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED])
                ->update([
                    'status' => ShipmentCharge::STATUS_CANCELLED,
                    'paid_at' => null,
                    'payment_method' => null,
                    'payment_reference' => null,
                ]);

            if ($receiptItem) {
                $receiptItem->loadMissing('photos');
                foreach ($receiptItem->photos as $photo) {
                    $storageService->delete($photo->path);
                    $photo->delete();
                }

                $receiptItem->delete();
            }

            $item->loadMissing('images');
            foreach ($item->images as $image) {
                $storageService->delete($image->path);
                $image->delete();
            }

            $item->delete();
        });

        $shipment = $this->reloadReceivingShipment($shipment->fresh());
        if ($pickupComplete) {
            $this->syncDestinationModeFromVendorPhotoPhones($shipment);
            $shipment = $this->reloadReceivingShipment($shipment->fresh());
        }
        $assignment = $shipment->pickupAssignment;

        return response()->json([
            'success' => true,
            'message' => 'Package deleted.',
            'data' => [
                'deleted_package_id' => $deletedItemId,
                'destination_mode' => $shipment->destination_mode->value,
                'delivery' => $this->serializeShipmentDelivery($shipment),
                'receiving_packages' => $shipment->items
                    ->map(fn (ShipmentItem $candidate) => $this->serializeReceivingPackage($shipment, $candidate, $assignment))
                    ->values(),
                'can_receive' => $this->canReceiveInAdminWorkspace($assignment),
                'assignment_id' => $assignment?->id,
                'can_auto_group' => $pickupComplete
                    ? $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment)
                    : null,
                'auto_group_lock_reason' => $pickupComplete
                    ? $this->receivingAutoGroupLockReason($assignment, $shipment)
                    : null,
            ],
        ]);
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
        $pickupComplete = $this->isPickupCompleteForReceiving($shipment, $assignment);
        $splitAllowed = ! $pickupComplete
            || $this->canSplitPackageDuringReceiving($assignment, $receiptItem, $shipment);

        if (! $splitAllowed) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingSplitLockReason($assignment, $receiptItem, $shipment) ?? 'Package can no longer be split.',
            ], 422);
        }

        $newItemStatus = $item->status?->value ?? $item->getRawOriginal('status') ?? ItemStatus::PENDING->value;
        if ($pickupComplete && $newItemStatus === ItemStatus::PENDING->value) {
            $newItemStatus = $this->initialStatusForAdminCreatedPackage($shipment, $assignment);
        }

        $newItem = $shipment->items()->create([
            'description' => $item->description,
            'quantity' => 1,
            'status' => $newItemStatus,
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

        if ($pickupComplete) {
            $this->ensurePickupConfirmationForAdminCreatedPackage($assignment, $newItem);
            $this->syncDestinationModeFromVendorPhotoPhones($shipment);
            $shipment = $this->reloadReceivingShipment($shipment->fresh());
            $assignment = $shipment->pickupAssignment;
        }

        $sourceItem = $shipment->items->firstWhere('id', $item->id) ?? $item->fresh(['images', 'deliveryRegion', 'deliveryDistrict']);
        $newItem = $shipment->items->firstWhere('id', $newItem->id) ?? $newItem->fresh(['images', 'deliveryRegion', 'deliveryDistrict']);

        return response()->json([
            'success' => true,
            'message' => "{$movedCount} photo(s) moved to new package.",
            'data' => [
                'package' => $this->serializeEditorPackage($newItem),
                'source_package' => $this->serializeEditorPackage($sourceItem),
                'destination_mode' => $shipment->destination_mode->value,
                'delivery' => $this->serializeShipmentDelivery($shipment),
                'receiving_package' => $this->serializeReceivingPackage($shipment, $newItem, $assignment),
                'source_receiving_package' => $this->serializeReceivingPackage($shipment, $sourceItem, $assignment),
                'receiving_packages' => $shipment->items
                    ->map(fn (ShipmentItem $candidate) => $this->serializeReceivingPackage($shipment, $candidate, $assignment))
                    ->values(),
                'can_receive' => $this->canReceiveInAdminWorkspace($assignment),
                'assignment_id' => $assignment?->id,
                'can_auto_group' => $pickupComplete
                    ? $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment)
                    : null,
                'auto_group_lock_reason' => $pickupComplete
                    ? $this->receivingAutoGroupLockReason($assignment, $shipment)
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
            'message' => 'Order duplicated as draft.',
            'data' => [
                'shipment_id' => $newShipment->id,
                'shipment_number' => $newShipment->shipment_number,
                'edit_url' => route('admin.orders.edit', $newShipment),
            ],
        ]);
    }

    // ─── RECEIVING (Back Office) ───────────────────────────────────────────────

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

        $this->completePickupForAdminReceiving(
            $shipment,
            $assignment,
            Auth::guard('admin')->user(),
            'Pickup marked completed by admin.'
        );

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
            $shipment = $this->reloadReceivingShipment($shipment);
            $packages = $shipment->items
                ->map(fn (ShipmentItem $item) => $this->serializeReceivingPackage($shipment, $item))
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'destination_mode' => $shipment->destination_mode->value,
                    'delivery' => $this->serializeShipmentDelivery($shipment),
                    'packages' => $packages,
                    'can_receive' => false,
                    'receipt' => null,
                    'assignment_id' => null,
                    'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving(null, $shipment),
                    'auto_group_lock_reason' => $this->receivingAutoGroupLockReason(null, $shipment),
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
                'destination_mode' => $shipment->destination_mode->value,
                'delivery' => $this->serializeShipmentDelivery($shipment),
                'packages' => $packages,
                'can_receive' => $this->canReceiveInAdminWorkspace($assignment),
                'receipt' => $this->serializeReceivingReceipt($receipt),
                'assignment_id' => $assignment->id,
                'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment),
                'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($assignment, $shipment),
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
            'delivery_fee_mode' => ['nullable', 'in:none,collect,paid'],
            'delivery_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'delivery_fee_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_fee_payment_method' => ['nullable', 'string', 'max:32'],
            'delivery_fee_payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->applyReceivingPackageDetails($shipment, $item, $validated, false);
        $this->syncReceivingPackageDeliveryFee($shipment, $item, $validated);
        if ($assignment) {
            $this->markReceivingReceiptNeedsFinalization($assignment);
        }

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
        if (! $this->canReceiveInAdminWorkspace($assignment)) {
            return response()->json(['success' => false, 'message' => 'Assign a pickup driver and target warehouse before receiving packages.'], 422);
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
            'delivery_fee_mode' => ['nullable', 'in:none,collect,paid'],
            'delivery_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'delivery_fee_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_fee_payment_method' => ['nullable', 'string', 'max:32'],
            'delivery_fee_payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->applyReceivingPackageDetails($shipment, $item, $validated, true);
        $this->syncReceivingPackageDeliveryFee($shipment, $item, $validated);

        if (! $this->isPickupCompleteForReceiving($shipment, $assignment)) {
            $assignment = $this->completePickupForAdminReceiving(
                $shipment,
                $assignment,
                Auth::guard('admin')->user(),
                'Pickup auto-completed during warehouse receiving because the driver could not confirm pickup from mobile.'
            );
            $shipment = $this->reloadReceivingShipment($shipment->fresh());
            $item = $shipment->items->firstWhere('id', $item->id) ?? $item->fresh();
            $assignment = $shipment->pickupAssignment;
            $warehouse = $assignment->targetWarehouse;
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
            removePhotoIds: $validated['remove_photo_ids'] ?? [],
            allowAfterFinalization: (bool) $assignment->received_at
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
        $relations = [
            'vendor',
            'pickupRegion',
            'pickupDistrict',
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'items.charges',
            'charges',
            'deliveryRegion',
            'deliveryDistrict',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
            'pickupAssignment.warehouseReceipt.items.photos',
        ];

        if (Schema::hasTable('shipment_item_tracking')) {
            $relations[] = 'items.tracking';
        }

        if ($this->receivingCustodyTablesAvailable()) {
            $relations[] = 'pickupAssignment.warehouseReceipt.items.labels.latestCustody.driver:id,name,phone';
        }

        if ($this->receivingMovementTablesAvailable()) {
            $relations[] = 'items.sortBatchItems.sortBatch.originWarehouse';
            $relations[] = 'items.sortBatchItems.sortBatch.destinationWarehouse';
            $relations[] = 'items.sortBatchItems.sortBatch.transportManifest.assignedDriver';
            $relations[] = 'items.sortBatchItems.sortBatch.transportManifest.originWarehouse';
            $relations[] = 'items.sortBatchItems.sortBatch.transportManifest.destinationWarehouse';
            $relations[] = 'items.transportManifestItems.manifest.assignedDriver';
            $relations[] = 'items.transportManifestItems.manifest.originWarehouse';
            $relations[] = 'items.transportManifestItems.manifest.destinationWarehouse';
        }

        if ($this->receivingDeliveryTablesAvailable()) {
            $relations[] = 'items.deliveryRunItems.run.assignedDriver';
            $relations[] = 'items.deliveryRunItems.run.warehouse';
            $relations[] = 'items.deliveryRunItems.stop.region';
            $relations[] = 'items.deliveryRunItems.stop.district';
        }

        return $shipment->load($relations);
    }

    protected function findReceiptItemForShipmentItem(?\App\Models\PickupAssignment $assignment, ShipmentItem $item): ?WarehouseReceiptItem
    {
        $receiptRelations = ['warehouseReceipt.items.photos'];

        if ($this->receivingCustodyTablesAvailable()) {
            $receiptRelations[] = 'warehouseReceipt.items.labels.latestCustody.driver:id,name,phone';
        }

        if (! $assignment?->relationLoaded('warehouseReceipt')) {
            $assignment?->load($receiptRelations);
        } elseif ($assignment->warehouseReceipt && ! $assignment->warehouseReceipt->relationLoaded('items')) {
            $itemRelations = ['items.photos'];

            if ($this->receivingCustodyTablesAvailable()) {
                $itemRelations[] = 'items.labels.latestCustody.driver:id,name,phone';
            }

            $assignment->warehouseReceipt->load($itemRelations);
        }

        return $assignment?->warehouseReceipt?->items?->firstWhere('shipment_item_id', $item->id);
    }

    protected function receivingCustodyTablesAvailable(): bool
    {
        return Schema::hasTable('warehouse_receipt_item_labels')
            && Schema::hasTable('label_custody_events');
    }

    protected function receivingMovementTablesAvailable(): bool
    {
        return Schema::hasTable('sort_batch_items')
            && Schema::hasTable('sort_batches')
            && Schema::hasTable('transport_manifest_items')
            && Schema::hasTable('transport_manifests');
    }

    protected function receivingDeliveryTablesAvailable(): bool
    {
        return Schema::hasTable('delivery_run_items')
            && Schema::hasTable('delivery_run_stops')
            && Schema::hasTable('delivery_runs');
    }

    protected function isPickupCompleteForReceiving(Shipment $shipment, ?\App\Models\PickupAssignment $assignment): bool
    {
        if (! $assignment) {
            return false;
        }

        if ($assignment->picked_up_at || $assignment->completed_at || $assignment->received_at) {
            return true;
        }

        if (($assignment->status?->value ?? $assignment->getRawOriginal('status')) === PickupAssignmentStatus::COMPLETED->value) {
            return true;
        }

        $status = $shipment->status?->value ?? $shipment->getRawOriginal('status');

        return in_array($status, [
            ShipmentStatus::PICKED_UP->value,
            ShipmentStatus::AT_WAREHOUSE->value,
            ShipmentStatus::SORTED->value,
            ShipmentStatus::IN_TRANSIT->value,
            ShipmentStatus::AT_DESTINATION->value,
            ShipmentStatus::OUT_FOR_DELIVERY->value,
            ShipmentStatus::HANDED_TO_COURIER->value,
            ShipmentStatus::DELIVERED->value,
        ], true);
    }

    protected function canReceiveInAdminWorkspace(?\App\Models\PickupAssignment $assignment): bool
    {
        if (! $assignment) {
            return false;
        }

        $status = $assignment->status?->value ?? $assignment->getRawOriginal('status');
        if ($assignment->cancelled_at || $status === PickupAssignmentStatus::CANCELLED->value) {
            return false;
        }

        return (bool) $assignment->driver_id && (bool) $assignment->target_warehouse_id;
    }

    protected function completePickupForAdminReceiving(
        Shipment $shipment,
        \App\Models\PickupAssignment $assignment,
        ?User $user,
        string $note
    ): \App\Models\PickupAssignment {
        if ($this->isPickupCompleteForReceiving($shipment, $assignment)) {
            return $assignment->fresh(['targetWarehouse', 'warehouseReceipt.items']);
        }

        return DB::transaction(function () use ($shipment, $assignment, $user, $note) {
            $now = now();
            $assignment = \App\Models\PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $shipment = Shipment::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($shipment->id);

            if (! $this->isPickupCompleteForReceiving($shipment, $assignment)) {
                $updates = [
                    'status' => PickupAssignmentStatus::COMPLETED,
                    'completed_at' => $assignment->completed_at ?: $now,
                ];

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

                $pickupLocation = $shipment->pickup_town
                    ?: $shipment->pickup_gh_post_address
                    ?: null;

                foreach ($shipment->items as $shipmentItem) {
                    if (($shipmentItem->status?->value ?? $shipmentItem->getRawOriginal('status')) === ItemStatus::PENDING->value) {
                        $shipmentItem->update(['status' => ItemStatus::PICKED_UP]);
                    }

                    ShipmentItemTracking::create([
                        'shipment_item_id' => $shipmentItem->id,
                        'status' => ItemStatus::PICKED_UP->value,
                        'location' => $pickupLocation,
                        'notes' => $note,
                        'meta' => [
                            'pickup_assignment_id' => $assignment->id,
                            'auto_completed_for_receiving' => true,
                        ],
                        'created_by' => $user ? "user:{$user->id}" : null,
                        'created_at' => $now,
                    ]);
                }

                $shipment->update(['status' => ShipmentStatus::PICKED_UP]);
            }

            return $assignment->fresh(['targetWarehouse', 'warehouseReceipt.items']);
        });
    }

    protected function canAutoGroupByPhoneDuringReceiving(?\App\Models\PickupAssignment $assignment, ?Shipment $shipment = null): bool
    {
        if ($assignment?->received_at) {
            return false;
        }

        if ($assignment && ! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt.items');
        } elseif ($assignment?->warehouseReceipt && ! $assignment->warehouseReceipt->relationLoaded('items')) {
            $assignment->warehouseReceipt->load('items');
        }

        if ($assignment?->warehouseReceipt?->items?->isNotEmpty()) {
            return false;
        }

        return $this->shipmentNeedsPhoneAutoGrouping($shipment ?? $assignment?->shipment);
    }

    protected function receivingAutoGroupLockReason(?\App\Models\PickupAssignment $assignment, ?Shipment $shipment = null): ?string
    {
        if ($assignment?->received_at) {
            return 'Warehouse receiving has already been finalized for this shipment.';
        }

        if ($assignment && ! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt.items');
        } elseif ($assignment?->warehouseReceipt && ! $assignment->warehouseReceipt->relationLoaded('items')) {
            $assignment->warehouseReceipt->load('items');
        }

        if ($assignment?->warehouseReceipt?->items?->isNotEmpty()) {
            return 'Auto-group by phone is only available before warehouse receiving starts.';
        }

        if (! $this->shipmentNeedsPhoneAutoGrouping($shipment ?? $assignment?->shipment)) {
            return 'No phone grouping is needed for the current package photos.';
        }

        return null;
    }

    protected function shipmentNeedsPhoneAutoGrouping(?Shipment $shipment): bool
    {
        if (! $shipment) {
            return false;
        }

        $shipment->loadMissing(['items.images']);

        $photos = $shipment->items->flatMap(fn (ShipmentItem $item) => $item->images);
        if ($photos->isEmpty()) {
            return false;
        }

        $taggedPhotos = $photos->filter(fn (ShipmentItemImage $image) => ! empty($image->recipient_phone));
        if ($taggedPhotos->isEmpty()) {
            return false;
        }

        $groups = $photos->groupBy(fn (ShipmentItemImage $image) => $image->recipient_phone ?: '__untagged__');
        $sourceItemGroups = $photos->groupBy('shipment_item_id');

        $onePhoneGroupTouchesMultipleItems = $groups->contains(function ($images) {
            return collect($images)->pluck('shipment_item_id')->filter()->unique()->count() > 1;
        });

        if ($onePhoneGroupTouchesMultipleItems) {
            return true;
        }

        $oneItemContainsMultiplePhoneGroups = $sourceItemGroups->contains(function ($images) {
            return collect($images)
                ->map(fn (ShipmentItemImage $image) => $image->recipient_phone ?: '__untagged__')
                ->unique()
                ->count() > 1;
        });

        if ($oneItemContainsMultiplePhoneGroups) {
            return true;
        }

        $phones = $taggedPhotos
            ->map(fn (ShipmentItemImage $image) => $this->normalizePhotoPhone($image->recipient_phone))
            ->filter()
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return false;
        }

        $destinationMode = $shipment->destination_mode?->value ?? (string) $shipment->destination_mode;

        if ($phones->count() > 1) {
            if ($destinationMode !== ShipmentDestinationMode::PER_ITEM->value) {
                return true;
            }

            return $shipment->items->contains(function (ShipmentItem $item) {
                $phone = $item->images
                    ->map(fn (ShipmentItemImage $image) => $this->normalizePhotoPhone($image->recipient_phone))
                    ->filter()
                    ->unique()
                    ->values();

                return $phone->count() === 1 && $this->normalizePhotoPhone($item->delivery_recipient_phone) !== $phone->first();
            });
        }

        if ($destinationMode !== ShipmentDestinationMode::SINGLE->value) {
            return true;
        }

        return $this->normalizePhotoPhone($shipment->delivery_recipient_phone) !== $phones->first();
    }

    protected function canAddPackageDuringReceiving(?\App\Models\PickupAssignment $assignment, ?Shipment $shipment = null): bool
    {
        if (! $assignment || ! $this->isPickupCompleteForReceiving($shipment ?? $assignment->shipment, $assignment)) {
            return false;
        }

        if ($assignment->received_at) {
            return false;
        }

        if (! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt');
        }

        return ! $assignment->warehouseReceipt?->isFinalized();
    }

    protected function receivingAddPackageLockReason(?\App\Models\PickupAssignment $assignment, ?Shipment $shipment = null): ?string
    {
        if (! $assignment) {
            return 'No pickup assignment found for this shipment.';
        }

        if (! $this->isPickupCompleteForReceiving($shipment ?? $assignment->shipment, $assignment)) {
            return 'Packages can only be added from Receiving after pickup.';
        }

        if ($assignment->received_at) {
            return 'Warehouse receiving has already been finalized for this shipment.';
        }

        if (! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt');
        }

        if ($assignment->warehouseReceipt?->isFinalized()) {
            return 'Warehouse receiving has already been finalized for this shipment.';
        }

        return null;
    }

    protected function canSplitPackageDuringReceiving(?\App\Models\PickupAssignment $assignment, ?WarehouseReceiptItem $receiptItem, ?Shipment $shipment = null): bool
    {
        if (! $receiptItem) {
            return true;
        }

        if (! $assignment || ! $this->isPickupCompleteForReceiving($shipment ?? $assignment->shipment, $assignment)) {
            return false;
        }

        return (int) $receiptItem->received_quantity === 0
            && (int) $receiptItem->barcode_print_count === 0;
    }

    protected function receivingSplitLockReason(?\App\Models\PickupAssignment $assignment, ?WarehouseReceiptItem $receiptItem, ?Shipment $shipment = null): ?string
    {
        if (! $receiptItem) {
            return null;
        }

        if (! $assignment || ! $this->isPickupCompleteForReceiving($shipment ?? $assignment->shipment, $assignment)) {
            return 'This package can only be split from Receiving after pickup.';
        }

        if ((int) $receiptItem->barcode_print_count > 0) {
            return 'Labels have already been printed for this package.';
        }

        if ((int) $receiptItem->received_quantity > 0) {
            return 'This package has already been received at the warehouse.';
        }

        return null;
    }

    protected function canRemovePackageDuringReceiving(
        ?\App\Models\PickupAssignment $assignment,
        ?WarehouseReceiptItem $receiptItem,
        ShipmentItem $item,
        ?Shipment $shipment = null,
    ): bool {
        if (! $receiptItem) {
            return ! $this->packageHasPaidCharges($item);
        }

        if (! $assignment || ! $this->isPickupCompleteForReceiving($shipment ?? $assignment->shipment, $assignment)) {
            return false;
        }

        if ($assignment->received_at) {
            return false;
        }

        if (! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt');
        }

        if ($assignment->warehouseReceipt?->isFinalized()) {
            return false;
        }

        if ($this->packageHasPaidCharges($item)) {
            return false;
        }

        return ! $this->receiptItemHasActiveLabelCustody($receiptItem);
    }

    protected function receivingRemoveLockReason(
        ?\App\Models\PickupAssignment $assignment,
        ?WarehouseReceiptItem $receiptItem,
        ShipmentItem $item,
        ?Shipment $shipment = null,
    ): ?string {
        if (! $receiptItem) {
            return $this->packageHasPaidCharges($item)
                ? 'This package has paid charges attached. Cancel or refund the charge before removing it.'
                : null;
        }

        if (! $assignment || ! $this->isPickupCompleteForReceiving($shipment ?? $assignment->shipment, $assignment)) {
            return 'This package can only be removed from Receiving after pickup.';
        }

        if ($assignment->received_at) {
            return 'Warehouse receiving has already been finalized for this shipment.';
        }

        if (! $assignment->relationLoaded('warehouseReceipt')) {
            $assignment->load('warehouseReceipt');
        }

        if ($assignment->warehouseReceipt?->isFinalized()) {
            return 'Warehouse receiving has already been finalized for this shipment.';
        }

        if ($this->packageHasPaidCharges($item)) {
            return 'This package has paid charges attached. Cancel or refund the charge before removing it.';
        }

        if ($this->receiptItemHasActiveLabelCustody($receiptItem)) {
            return 'This package has labels already claimed by a driver or delivered.';
        }

        return null;
    }

    protected function receiptItemHasActiveLabelCustody(WarehouseReceiptItem $receiptItem): bool
    {
        if (! $this->receivingCustodyTablesAvailable()) {
            return false;
        }

        $receiptItem->loadMissing('labels.latestCustody');

        return $receiptItem->labels->contains(function ($label) {
            $eventType = $label->latestCustody?->event_type;

            return in_array($eventType, [
                LabelCustodyEvent::TYPE_CLAIMED,
                LabelCustodyEvent::TYPE_TRANSFERRED,
                LabelCustodyEvent::TYPE_DELIVERED,
            ], true);
        });
    }

    protected function packageHasPaidCharges(ShipmentItem $item): bool
    {
        return ShipmentCharge::query()
            ->where('shipment_item_id', $item->id)
            ->where('status', ShipmentCharge::STATUS_PAID)
            ->exists();
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
            'vendor',
            'pickupRegion',
            'pickupDistrict',
            'charges',
            'deliveryRegion',
            'deliveryDistrict',
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'items.charges',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
            'pickupAssignment.warehouseReceipt.items.photos',
        ]);

        if (Schema::hasTable('shipment_item_tracking')) {
            $shipment->loadMissing(['items.tracking']);
        }

        $itemRelations = ['images', 'deliveryRegion', 'deliveryDistrict', 'charges'];

        if (Schema::hasTable('shipment_item_tracking')) {
            $itemRelations[] = 'tracking';
        }

        if ($this->receivingMovementTablesAvailable()) {
            $itemRelations[] = 'sortBatchItems.sortBatch.originWarehouse';
            $itemRelations[] = 'sortBatchItems.sortBatch.destinationWarehouse';
            $itemRelations[] = 'sortBatchItems.sortBatch.transportManifest.assignedDriver';
            $itemRelations[] = 'sortBatchItems.sortBatch.transportManifest.originWarehouse';
            $itemRelations[] = 'sortBatchItems.sortBatch.transportManifest.destinationWarehouse';
            $itemRelations[] = 'transportManifestItems.manifest.assignedDriver';
            $itemRelations[] = 'transportManifestItems.manifest.originWarehouse';
            $itemRelations[] = 'transportManifestItems.manifest.destinationWarehouse';
        }

        if ($this->receivingDeliveryTablesAvailable()) {
            $itemRelations[] = 'deliveryRunItems.run.assignedDriver';
            $itemRelations[] = 'deliveryRunItems.run.warehouse';
            $itemRelations[] = 'deliveryRunItems.stop.region';
            $itemRelations[] = 'deliveryRunItems.stop.district';
        }

        $item->loadMissing($itemRelations);

        $assignment ??= $shipment->pickupAssignment;
        $receiptItem ??= $this->findReceiptItemForShipmentItem($assignment, $item);
        $this->ensureTrackingCodeForReceivingPackage($shipment, $item, $assignment, $receiptItem);

        $driverConfirmation = $assignment?->itemConfirmations?->firstWhere('shipment_item_id', $item->id);
        $vendorDeclaredQuantity = (int) (
            $shipment->vendor_declared_quantity
            ?? ($shipment->relationLoaded('items') ? $shipment->items->sum('quantity') : $shipment->items()->sum('quantity'))
        );
        $driverPickedQuantity = $assignment && !is_null($assignment->driver_picked_quantity)
            ? (int) $assignment->driver_picked_quantity
            : null;
        $warehouseReceivedQuantity = $assignment?->warehouseReceipt?->items
            ? (int) $assignment->warehouseReceipt->items->sum('received_quantity')
            : null;
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
            'item_status' => $item->status?->value ?? $item->getRawOriginal('status'),
            'vendor_quantity' => (int) $item->quantity,
            'vendor_declared_quantity' => $vendorDeclaredQuantity,
            'driver_picked_quantity' => $driverPickedQuantity,
            'warehouse_received_quantity' => $warehouseReceivedQuantity,
            'driver_confirmed_quantity' => $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null,
            'expected_quantity' => $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : (int) $item->quantity,
            'vendor_photos' => $vendorPhotos,
            'driver_photos' => $driverPhotos,
            'receipt_item_id' => $receiptItem?->id,
            'received_quantity' => (int) ($receiptItem?->received_quantity ?? 0),
            'damaged_quantity' => (int) ($receiptItem?->damaged_quantity ?? 0),
            'discrepancy_type' => $receiptItem?->discrepancy_type ?? 'none',
            'condition_status' => $receiptItem?->condition_status,
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
            'delivery_fee' => $this->serializeReceivingPackageDeliveryFee($shipment, $item),
            'custody' => $this->serializeReceivingPackageCustody($receiptItem),
            'details' => $this->serializeReceivingPackageDetails($shipment, $item, $assignment, $receiptItem, $driverConfirmation),
            'photos' => $receiptItem
                ? $receivingService->serializeReceiptItem($receiptItem)['photos']
                : [],
            'can_split' => $this->canSplitPackageDuringReceiving($assignment, $receiptItem, $shipment),
            'split_lock_reason' => $this->receivingSplitLockReason($assignment, $receiptItem, $shipment),
            'can_delete' => $this->canRemovePackageDuringReceiving($assignment, $receiptItem, $item, $shipment),
            'delete_lock_reason' => $this->receivingRemoveLockReason($assignment, $receiptItem, $item, $shipment),
        ];
    }

    protected function serializeReceivingPackageDetails(
        Shipment $shipment,
        ShipmentItem $item,
        ?\App\Models\PickupAssignment $assignment,
        ?WarehouseReceiptItem $receiptItem,
        ?PickupItemConfirmation $driverConfirmation,
    ): array {
        $storageService = app(StorageService::class);
        $deliveryRunItem = $this->receivingDeliveryTablesAvailable()
            ? $item->deliveryRunItems?->sortByDesc(fn ($candidate) => $candidate->updated_at?->getTimestamp() ?? $candidate->id)->first()
            : null;
        $deliveryStop = $deliveryRunItem?->stop;
        $deliveryRun = $deliveryRunItem?->run;
        $manifestItem = $this->receivingMovementTablesAvailable()
            ? $item->transportManifestItems?->sortByDesc(fn ($candidate) => $candidate->updated_at?->getTimestamp() ?? $candidate->id)->first()
            : null;
        $manifest = $manifestItem?->manifest;
        $sortBatchItem = $this->receivingMovementTablesAvailable()
            ? $item->sortBatchItems?->whereNull('removed_at')->sortByDesc(fn ($candidate) => $candidate->added_at?->getTimestamp() ?? $candidate->id)->first()
            : null;
        $sortBatch = $sortBatchItem?->sortBatch;

        return [
            'shipment' => [
                'number' => $shipment->shipment_number,
                'status' => $shipment->status?->value ?? $shipment->getRawOriginal('status'),
                'submitted_at' => $shipment->submitted_at?->toIso8601String(),
                'created_at' => $shipment->created_at?->toIso8601String(),
                'vendor_name' => $shipment->vendor?->business_name ?: $shipment->vendor?->name,
                'vendor_phone' => $shipment->vendor?->phone,
            ],
            'shipment_totals' => [
                'vendor_declared' => (int) (
                    $shipment->vendor_declared_quantity
                    ?? ($shipment->relationLoaded('items') ? $shipment->items->sum('quantity') : $shipment->items()->sum('quantity'))
                ),
                'driver_picked' => $assignment && !is_null($assignment->driver_picked_quantity)
                    ? (int) $assignment->driver_picked_quantity
                    : null,
                'warehouse_received' => $assignment?->warehouseReceipt?->items
                    ? (int) $assignment->warehouseReceipt->items->sum('received_quantity')
                    : null,
            ],
            'quantities' => [
                'vendor_submitted' => (int) $item->quantity,
                'driver_confirmed' => $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null,
                'expected' => $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : (int) $item->quantity,
                'received' => (int) ($receiptItem?->received_quantity ?? 0),
                'damaged' => (int) ($receiptItem?->damaged_quantity ?? 0),
                'remaining' => max(0, ($driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : (int) $item->quantity) - (int) ($receiptItem?->received_quantity ?? 0)),
            ],
            'pickup' => [
                'contact_name' => $shipment->pickup_contact_name,
                'contact_phone' => $shipment->pickup_contact_phone,
                'region' => $shipment->pickupRegion?->name,
                'district' => $shipment->pickupDistrict?->name,
                'town' => $shipment->pickup_town,
                'landmark' => $shipment->pickup_landmark,
                'instructions' => $shipment->pickup_instructions,
                'latitude' => $shipment->pickup_latitude,
                'longitude' => $shipment->pickup_longitude,
                'gh_post_address' => $shipment->pickup_gh_post_address,
                'driver_name' => $assignment?->driver?->name,
                'driver_phone' => $assignment?->driver?->phone,
                'assigned_at' => $assignment?->assigned_at?->toIso8601String(),
                'picked_up_at' => $assignment?->picked_up_at?->toIso8601String(),
                'completed_at' => $assignment?->completed_at?->toIso8601String(),
                'warehouse' => $assignment?->targetWarehouse?->name,
                'captured_latitude' => $assignment?->pickup_latitude,
                'captured_longitude' => $assignment?->pickup_longitude,
            ],
            'delivery' => [
                'method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                'recipient_name' => $item->delivery_recipient_name ?: $shipment->delivery_recipient_name,
                'recipient_phone' => $item->delivery_recipient_phone ?: $shipment->delivery_recipient_phone,
                'region' => $item->deliveryRegion?->name ?: $shipment->deliveryRegion?->name,
                'district' => $item->deliveryDistrict?->name ?: $shipment->deliveryDistrict?->name,
                'town' => $item->delivery_town ?: $shipment->delivery_town,
                'landmark' => $item->delivery_landmark ?: $shipment->delivery_landmark,
                'instructions' => $item->delivery_instructions ?: $shipment->delivery_instructions,
                'latitude' => $item->delivery_latitude ?: $shipment->delivery_latitude,
                'longitude' => $item->delivery_longitude ?: $shipment->delivery_longitude,
                'gh_post_address' => $item->delivery_gh_post_address ?: $shipment->delivery_gh_post_address,
            ],
            'delivery_proof' => [
                'run_number' => $deliveryRun?->run_number,
                'run_status' => $deliveryRun?->status,
                'driver_name' => $deliveryRun?->assignedDriver?->name,
                'driver_phone' => $deliveryRun?->assignedDriver?->phone,
                'expected_quantity' => $deliveryRunItem?->expected_quantity,
                'delivered_quantity' => $deliveryRunItem?->delivered_quantity,
                'status' => $deliveryRunItem?->status ?: $deliveryStop?->status,
                'delivered_at' => $deliveryRunItem?->delivered_at?->toIso8601String() ?: $deliveryStop?->delivered_at?->toIso8601String(),
                'arrived_at' => $deliveryStop?->arrived_at?->toIso8601String(),
                'latitude' => $deliveryStop?->delivery_latitude,
                'longitude' => $deliveryStop?->delivery_longitude,
                'proof_photo_url' => $deliveryStop?->proof_photo_path ? $storageService->getUrl($deliveryStop->proof_photo_path) : null,
                'failure_reason' => $deliveryStop?->failure_reason,
                'delivery_notes' => $deliveryStop?->delivery_notes,
            ],
            'bus_handoff' => [
                'station_name' => $deliveryStop?->bus_station_name,
                'courier_name' => $deliveryStop?->handoff_courier_name,
                'courier_phone' => $deliveryStop?->handoff_courier_phone,
                'vehicle_number' => $deliveryStop?->handoff_vehicle_number,
                'handoff_at' => $deliveryStop?->handoff_at?->toIso8601String(),
            ],
            'movement' => [
                'sort_batch_number' => $sortBatch?->batch_number,
                'sort_batch_status' => $sortBatch?->status,
                'sort_batch_added_at' => $sortBatchItem?->added_at?->toIso8601String(),
                'origin_warehouse' => $sortBatch?->originWarehouse?->name,
                'destination_warehouse' => $sortBatch?->destinationWarehouse?->name,
                'manifest_number' => $manifest?->manifest_number,
                'manifest_status' => $manifest?->status,
                'manifest_driver_name' => $manifest?->assignedDriver?->name,
                'manifest_driver_phone' => $manifest?->assignedDriver?->phone,
                'manifest_dispatched_at' => $manifest?->dispatched_at?->toIso8601String(),
                'manifest_arrived_at' => $manifest?->arrived_at?->toIso8601String(),
            ],
            'pickup_fee' => $this->serializeReceivingPickupFee($shipment),
            'charges' => $this->serializeReceivingPackageCharges($shipment, $item),
            'tracking_events' => $this->serializeReceivingPackageTrackingEvents($shipment, $item, $assignment, $receiptItem, $sortBatch, $manifest, $deliveryRun, $deliveryRunItem, $deliveryStop),
        ];
    }

    protected function serializeReceivingPackageCustody(?WarehouseReceiptItem $receiptItem): array
    {
        if (! $receiptItem || ! $this->receivingCustodyTablesAvailable()) {
            return [
                'total_labels' => 0,
                'claimed_labels' => 0,
                'delivered_labels' => 0,
                'warehouse_labels' => 0,
                'drivers' => [],
                'labels' => [],
            ];
        }

        $receiptItem->loadMissing('labels.latestCustody.driver:id,name,phone');

        $labels = $receiptItem->labels
            ->sortBy('label_index')
            ->values()
            ->map(function ($label) {
                $latest = $label->latestCustody;
                $isClaimed = $latest && $latest->event_type === LabelCustodyEvent::TYPE_CLAIMED;
                $isDelivered = $latest && $latest->event_type === LabelCustodyEvent::TYPE_DELIVERED;

                return [
                    'id' => $label->id,
                    'barcode' => $label->barcode_value,
                    'label_index' => (int) $label->label_index,
                    'labels_total' => (int) $label->labels_total,
                    'label_type' => $label->label_type,
                    'status' => $isDelivered ? 'delivered' : ($isClaimed ? 'claimed' : 'at_warehouse'),
                    'current_driver' => $isClaimed ? [
                        'id' => $latest->driver_id,
                        'name' => $latest->driver?->name,
                        'phone' => $latest->driver?->phone,
                    ] : null,
                    'claimed_at' => $isClaimed ? $latest->created_at?->format('M d, H:i') : null,
                ];
            });

        $drivers = $labels
            ->filter(fn ($label) => ! empty($label['current_driver']['id']))
            ->groupBy(fn ($label) => $label['current_driver']['id'])
            ->map(function ($driverLabels) {
                $first = $driverLabels->first()['current_driver'];

                return [
                    'driver_id' => $first['id'],
                    'name' => $first['name'] ?: 'Unknown driver',
                    'phone' => $first['phone'] ?: '',
                    'count' => $driverLabels->count(),
                    'barcodes' => $driverLabels->pluck('barcode')->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'total_labels' => $labels->count(),
            'claimed_labels' => $labels->where('status', 'claimed')->count(),
            'delivered_labels' => $labels->where('status', 'delivered')->count(),
            'warehouse_labels' => $labels->where('status', 'at_warehouse')->count(),
            'drivers' => $drivers,
            'labels' => $labels->all(),
        ];
    }

    protected function deliveryFieldKeys(): array
    {
        return [
            'delivery_recipient_name',
            'delivery_recipient_phone',
            'delivery_region_id',
            'delivery_district_id',
            'delivery_town',
            'delivery_latitude',
            'delivery_longitude',
            'delivery_gh_post_address',
            'delivery_landmark',
            'delivery_instructions',
            'delivery_preference',
            'fulfillment_type',
        ];
    }

    protected function coreDeliveryFieldKeys(): array
    {
        return [
            'delivery_recipient_name',
            'delivery_recipient_phone',
            'delivery_region_id',
            'delivery_district_id',
            'delivery_town',
            'delivery_latitude',
            'delivery_longitude',
            'delivery_gh_post_address',
            'delivery_landmark',
            'delivery_instructions',
        ];
    }

    protected function emptyShipmentDeliveryAttributes(): array
    {
        return array_fill_keys($this->deliveryFieldKeys(), null);
    }

    protected function emptyItemDeliveryAttributes(): array
    {
        return array_fill_keys($this->deliveryFieldKeys(), null);
    }

    protected function shipmentDeliveryAttributes(Shipment $shipment): array
    {
        return [
            'delivery_recipient_name' => $shipment->delivery_recipient_name,
            'delivery_recipient_phone' => $shipment->delivery_recipient_phone,
            'delivery_region_id' => $shipment->delivery_region_id,
            'delivery_district_id' => $shipment->delivery_district_id,
            'delivery_town' => $shipment->delivery_town,
            'delivery_latitude' => $shipment->delivery_latitude,
            'delivery_longitude' => $shipment->delivery_longitude,
            'delivery_gh_post_address' => $shipment->delivery_gh_post_address,
            'delivery_landmark' => $shipment->delivery_landmark,
            'delivery_instructions' => $shipment->delivery_instructions,
            'delivery_preference' => $shipment->delivery_preference,
            'fulfillment_type' => $shipment->fulfillment_type?->value ?? $shipment->getRawOriginal('fulfillment_type'),
        ];
    }

    protected function itemDeliveryAttributes(ShipmentItem $item): array
    {
        return [
            'delivery_recipient_name' => $item->delivery_recipient_name,
            'delivery_recipient_phone' => $item->delivery_recipient_phone,
            'delivery_region_id' => $item->delivery_region_id,
            'delivery_district_id' => $item->delivery_district_id,
            'delivery_town' => $item->delivery_town,
            'delivery_latitude' => $item->delivery_latitude,
            'delivery_longitude' => $item->delivery_longitude,
            'delivery_gh_post_address' => $item->delivery_gh_post_address,
            'delivery_landmark' => $item->delivery_landmark,
            'delivery_instructions' => $item->delivery_instructions,
            'delivery_preference' => $item->delivery_preference,
            'fulfillment_type' => $item->fulfillment_type?->value ?? $item->getRawOriginal('fulfillment_type'),
        ];
    }

    protected function hasCoreDeliveryDetails(Shipment|ShipmentItem $model): bool
    {
        foreach ($this->coreDeliveryFieldKeys() as $key) {
            if ($model->{$key} !== null && $model->{$key} !== '') {
                return true;
            }
        }

        return false;
    }

    protected function seedPackageDeliveryFromShipment(Shipment $shipment): void
    {
        if (! $this->hasCoreDeliveryDetails($shipment)) {
            return;
        }

        $attributes = $this->shipmentDeliveryAttributes($shipment);
        $shipment->items()->get()->each(function (ShipmentItem $item) use ($attributes) {
            $updates = [];

            foreach ($attributes as $key => $value) {
                if (($item->{$key} === null || $item->{$key} === '') && $value !== null && $value !== '') {
                    $updates[$key] = $value;
                }
            }

            if (! empty($updates)) {
                $item->update($updates);
            }
        });
    }

    protected function shipmentDeliverySeedFromItems(Shipment $shipment): array
    {
        $sourceItem = $shipment->items()->get()->first(fn (ShipmentItem $item) => $this->hasCoreDeliveryDetails($item));

        return $sourceItem ? $this->itemDeliveryAttributes($sourceItem) : [];
    }

    protected function serializeShipmentDelivery(Shipment $shipment): array
    {
        return [
            'recipient_name' => $shipment->delivery_recipient_name,
            'recipient_phone' => $shipment->delivery_recipient_phone,
            'region_id' => $shipment->delivery_region_id,
            'region_name' => $shipment->deliveryRegion?->name,
            'district_id' => $shipment->delivery_district_id,
            'district_name' => $shipment->deliveryDistrict?->name,
            'town' => $shipment->delivery_town,
            'landmark' => $shipment->delivery_landmark,
            'instructions' => $shipment->delivery_instructions,
            'delivery_preference' => $shipment->delivery_preference,
            'fulfillment_type' => $shipment->fulfillment_type?->value ?? $shipment->getRawOriginal('fulfillment_type'),
        ];
    }

    protected function normalizePhotoPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        return PhoneHelper::format($phone) ?: trim($phone);
    }

    protected function syncDestinationModeFromVendorPhotoPhones(Shipment $shipment): ?ShipmentDestinationMode
    {
        $shipment->loadMissing(['items.images', 'items.deliveryRegion', 'items.deliveryDistrict', 'deliveryRegion', 'deliveryDistrict']);

        $photos = $shipment->items->flatMap(fn (ShipmentItem $item) => $item->images);
        if ($photos->isEmpty()) {
            return null;
        }

        $phones = $photos
            ->map(fn (ShipmentItemImage $image) => $this->normalizePhotoPhone($image->recipient_phone))
            ->filter()
            ->values();

        if ($phones->count() !== $photos->count()) {
            return $this->syncDestinationModeFromPackageDeliveryDetails($shipment);
        }

        $uniquePhones = $phones->unique()->values();
        if ($uniquePhones->count() > 1) {
            if ($shipment->destination_mode !== ShipmentDestinationMode::PER_ITEM) {
                $this->seedPackageDeliveryFromShipment($shipment);
                $shipment->update(array_merge(
                    ['destination_mode' => ShipmentDestinationMode::PER_ITEM],
                    $this->emptyShipmentDeliveryAttributes()
                ));
            }

            $shipment->items->each(function (ShipmentItem $item) {
                $itemPhones = $item->images
                    ->map(fn (ShipmentItemImage $image) => $this->normalizePhotoPhone($image->recipient_phone))
                    ->filter()
                    ->unique()
                    ->values();

                if ($itemPhones->count() === 1) {
                    $item->update(['delivery_recipient_phone' => $itemPhones->first()]);
                }
            });

            return ShipmentDestinationMode::PER_ITEM;
        }

        if ($uniquePhones->count() === 1) {
            $seed = $shipment->destination_mode === ShipmentDestinationMode::PER_ITEM
                ? $this->shipmentDeliverySeedFromItems($shipment)
                : [];

            $shipment->update(array_merge($seed, [
                'destination_mode' => ShipmentDestinationMode::SINGLE,
                'delivery_recipient_phone' => $uniquePhones->first(),
            ]));

            return ShipmentDestinationMode::SINGLE;
        }

        return $this->syncDestinationModeFromPackageDeliveryDetails($shipment);
    }

    protected function syncDestinationModeFromPackageDeliveryDetails(Shipment $shipment): ?ShipmentDestinationMode
    {
        $items = $shipment->items()->get();
        if ($items->count() < 2) {
            return null;
        }

        $signatures = $items->map(function (ShipmentItem $item) {
            if (! $this->hasCoreDeliveryDetails($item)) {
                return null;
            }

            $phone = $this->normalizePhotoPhone($item->delivery_recipient_phone);
            if ($phone) {
                return 'phone:'.$phone;
            }

            $parts = [
                $item->delivery_recipient_name,
                $item->delivery_region_id,
                $item->delivery_district_id,
                $item->delivery_town,
                $item->delivery_latitude,
                $item->delivery_longitude,
                $item->delivery_gh_post_address,
                $item->delivery_landmark,
            ];

            $signature = collect($parts)
                ->map(fn ($part) => is_string($part) ? strtolower(trim($part)) : $part)
                ->filter(fn ($part) => $part !== null && $part !== '')
                ->implode('|');

            return $signature !== '' ? 'location:'.$signature : null;
        });

        if ($signatures->contains(null)) {
            return null;
        }

        if ($signatures->unique()->count() !== 1) {
            return null;
        }

        $seed = $this->shipmentDeliverySeedFromItems($shipment);
        if (empty($seed)) {
            return null;
        }

        $shipment->update(array_merge($seed, [
            'destination_mode' => ShipmentDestinationMode::SINGLE,
        ]));

        return ShipmentDestinationMode::SINGLE;
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

    protected function initialStatusForAdminCreatedPackage(Shipment $shipment, mixed $assignment): string
    {
        $shipmentStatus = $shipment->status?->value ?? $shipment->getRawOriginal('status');

        if ($assignment?->received_at || in_array($shipmentStatus, [
            ShipmentStatus::AT_WAREHOUSE->value,
            ShipmentStatus::SORTED->value,
            ShipmentStatus::IN_TRANSIT->value,
            ShipmentStatus::AT_DESTINATION->value,
            ShipmentStatus::OUT_FOR_DELIVERY->value,
            ShipmentStatus::HANDED_TO_COURIER->value,
            ShipmentStatus::DELIVERED->value,
        ], true)) {
            return ItemStatus::AT_WAREHOUSE->value;
        }

        if ($this->isPickupCompleteForReceiving($shipment, $assignment)) {
            return ItemStatus::PICKED_UP->value;
        }

        return ItemStatus::PENDING->value;
    }

    protected function ensureTrackingCodeForReceivingPackage(
        Shipment $shipment,
        ShipmentItem $item,
        mixed $assignment,
        ?WarehouseReceiptItem $receiptItem,
    ): void {
        $itemStatus = $item->status?->value ?? $item->getRawOriginal('status');
        $shouldHaveTracking = $receiptItem
            || $this->isPickupCompleteForReceiving($shipment, $assignment)
            || ($itemStatus && $itemStatus !== ItemStatus::PENDING->value);

        if ($shouldHaveTracking && empty($item->tracking_code)) {
            $item->update(['tracking_code' => ShipmentItem::generateTrackingCode()]);
            $item->refresh();
        }

        if ($receiptItem && empty($receiptItem->barcode_value) && filled($item->tracking_code)) {
            $receiptItem->update([
                'barcode_value' => $item->tracking_code,
                'barcode_format' => 'code128',
            ]);
            $receiptItem->refresh();
        }
    }

    protected function syncReceivingPackageDeliveryFee(Shipment $shipment, ShipmentItem $item, array $validated): void
    {
        if (! array_key_exists('delivery_fee_mode', $validated)) {
            return;
        }

        $mode = $validated['delivery_fee_mode'] ?: 'none';
        $amount = array_key_exists('delivery_fee_amount', $validated) && $validated['delivery_fee_amount'] !== null
            ? round((float) $validated['delivery_fee_amount'], 2)
            : 0.0;

        $outstanding = $this->deliveryFeeChargesForItem($shipment, $item)
            ->whereIn('status', [ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING]);

        if ($mode === 'none' || $amount <= 0) {
            $outstanding->each(fn (ShipmentCharge $charge) => $charge->update([
                'status' => ShipmentCharge::STATUS_CANCELLED,
            ]));

            return;
        }

        $payload = [
            'shipment_id' => $shipment->id,
            'shipment_item_id' => $item->id,
            'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
            'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
            'direction' => ShipmentCharge::DIRECTION_REVENUE,
            'due_stage' => ShipmentCharge::STAGE_AT_DELIVERY,
            'amount' => $amount,
            'currency' => 'GHS',
            'notes' => $validated['delivery_fee_notes'] ?? null,
            'recorded_by_admin_id' => Auth::guard('admin')->id(),
        ];

        if ($mode === 'paid') {
            $payload['status'] = ShipmentCharge::STATUS_PAID;
            $payload['payment_method'] = $validated['delivery_fee_payment_method'] ?? 'cash';
            $payload['payment_reference'] = $validated['delivery_fee_payment_reference'] ?? null;
        } else {
            $payload['status'] = ShipmentCharge::STATUS_PENDING;
            $payload['paid_at'] = null;
            $payload['payment_method'] = null;
            $payload['payment_reference'] = null;
        }

        $charge = $outstanding->first()
            ?: $this->deliveryFeeChargesForItem($shipment, $item)
                ->where('status', ShipmentCharge::STATUS_PAID)
                ->sortByDesc(fn (ShipmentCharge $candidate) => $candidate->paid_at?->getTimestamp() ?? 0)
                ->first();

        if ($charge && $charge->status === ShipmentCharge::STATUS_PAID && $mode !== 'paid') {
            return;
        }

        if ($charge) {
            if ($mode === 'paid' && ! $charge->paid_at) {
                $payload['paid_at'] = now();
            }
            $charge->update($payload);
            return;
        }

        if ($mode === 'paid') {
            $payload['paid_at'] = now();
        }

        ShipmentCharge::query()->create($payload);
    }

    protected function deliveryFeeChargesForItem(Shipment $shipment, ShipmentItem $item)
    {
        return ShipmentCharge::query()
            ->where('shipment_id', $shipment->id)
            ->where('shipment_item_id', $item->id)
            ->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)
            ->where('payer_type', ShipmentCharge::PAYER_RECIPIENT)
            ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED])
            ->get();
    }

    protected function serializeReceivingPickupFee(Shipment $shipment): array
    {
        $charges = ($shipment->relationLoaded('charges') ? $shipment->charges : $shipment->charges()->get())
            ->where('charge_type', ShipmentCharge::TYPE_PICKUP_FEE)
            ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED]);
        $latest = $charges->sortByDesc(fn (ShipmentCharge $charge) => $charge->updated_at?->getTimestamp() ?? 0)->first();
        $paidAmount = (float) $charges->where('status', ShipmentCharge::STATUS_PAID)->sum('amount');
        $outstandingAmount = (float) $charges
            ->whereIn('status', [ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING])
            ->sum('amount');

        return [
            'amount' => round($outstandingAmount > 0 ? $outstandingAmount : $paidAmount, 2),
            'currency' => $latest?->currency ?: 'GHS',
            'paid_amount' => round($paidAmount, 2),
            'outstanding_amount' => round($outstandingAmount, 2),
            'status' => $outstandingAmount > 0 ? 'due' : ($paidAmount > 0 ? 'paid' : 'none'),
            'paid_at' => $charges
                ->where('status', ShipmentCharge::STATUS_PAID)
                ->sortByDesc(fn (ShipmentCharge $charge) => $charge->paid_at?->getTimestamp() ?? 0)
                ->first()?->paid_at?->toIso8601String(),
        ];
    }

    protected function serializeReceivingPackageCharges(Shipment $shipment, ShipmentItem $item): array
    {
        $charges = ($shipment->relationLoaded('charges') ? $shipment->charges : $shipment->charges()->get())
            ->filter(fn (ShipmentCharge $charge) => $charge->shipment_item_id === null || (int) $charge->shipment_item_id === (int) $item->id)
            ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED])
            ->sortByDesc(fn (ShipmentCharge $charge) => $charge->updated_at?->getTimestamp() ?? 0)
            ->values();

        return $charges->map(fn (ShipmentCharge $charge) => [
            'id' => $charge->id,
            'type' => $charge->charge_type,
            'payer' => $charge->payer_type,
            'due_stage' => $charge->due_stage,
            'amount' => round((float) $charge->amount, 2),
            'currency' => $charge->currency ?: 'GHS',
            'status' => $charge->status,
            'paid_at' => $charge->paid_at?->toIso8601String(),
            'payment_method' => $charge->payment_method,
            'payment_reference' => $charge->payment_reference,
            'notes' => $charge->notes,
        ])->all();
    }

    protected function serializeReceivingPackageTrackingEvents(
        Shipment $shipment,
        ShipmentItem $item,
        ?\App\Models\PickupAssignment $assignment,
        ?WarehouseReceiptItem $receiptItem,
        mixed $sortBatch,
        mixed $manifest,
        mixed $deliveryRun,
        mixed $deliveryRunItem,
        mixed $deliveryStop,
    ): array {
        $events = collect();

        $events->push([
            'label' => 'Submitted by vendor',
            'status' => $shipment->status?->value ?? $shipment->getRawOriginal('status'),
            'location' => $shipment->pickup_town ?: $shipment->pickup_gh_post_address,
            'notes' => $shipment->vendor?->business_name ?: $shipment->vendor?->name,
            'created_at' => ($shipment->submitted_at ?: $shipment->created_at)?->toIso8601String(),
        ]);

        if ($assignment?->assigned_at) {
            $events->push([
                'label' => 'Pickup driver assigned',
                'status' => 'assigned',
                'location' => $assignment->targetWarehouse?->name,
                'notes' => trim(($assignment->driver?->name ?: '').' '.($assignment->driver?->phone ?: '')) ?: null,
                'created_at' => $assignment->assigned_at?->toIso8601String(),
            ]);
        }

        if ($assignment?->picked_up_at || $assignment?->completed_at) {
            $events->push([
                'label' => 'Picked up by driver',
                'status' => 'picked_up',
                'location' => $shipment->pickup_town ?: $shipment->pickup_gh_post_address,
                'notes' => trim(($assignment->driver?->name ?: '').' '.($assignment->driver?->phone ?: '')) ?: null,
                'created_at' => ($assignment->picked_up_at ?: $assignment->completed_at)?->toIso8601String(),
            ]);
        }

        if ($receiptItem?->created_at) {
            $events->push([
                'label' => 'Received at warehouse',
                'status' => 'received',
                'location' => $assignment?->targetWarehouse?->name,
                'notes' => $receiptItem->notes,
                'created_at' => $receiptItem->created_at?->toIso8601String(),
            ]);
        }

        if ($sortBatch?->sealed_at) {
            $events->push([
                'label' => 'Sorted into batch',
                'status' => $sortBatch->status,
                'location' => $sortBatch->destinationWarehouse?->name ?: $sortBatch->originWarehouse?->name,
                'notes' => $sortBatch->batch_number,
                'created_at' => $sortBatch->sealed_at?->toIso8601String(),
            ]);
        }

        if ($manifest?->dispatched_at || $manifest?->assigned_at) {
            $events->push([
                'label' => 'Transport manifest dispatched',
                'status' => $manifest->status,
                'location' => $manifest->destinationWarehouse?->name ?: $manifest->originWarehouse?->name,
                'notes' => $manifest->manifest_number,
                'created_at' => ($manifest->dispatched_at ?: $manifest->assigned_at)?->toIso8601String(),
            ]);
        }

        if ($deliveryRun?->dispatched_at || $deliveryRun?->assigned_at) {
            $events->push([
                'label' => 'Out for delivery',
                'status' => $deliveryRun->status,
                'location' => $deliveryRun->warehouse?->name,
                'notes' => trim(($deliveryRun->assignedDriver?->name ?: '').' '.($deliveryRun->assignedDriver?->phone ?: '')) ?: null,
                'created_at' => ($deliveryRun->dispatched_at ?: $deliveryRun->assigned_at)?->toIso8601String(),
            ]);
        }

        if ($deliveryStop?->handoff_at) {
            $events->push([
                'label' => 'Handed to bus courier',
                'status' => 'handed_off',
                'location' => $deliveryStop->bus_station_name ?: $deliveryStop->town,
                'notes' => trim(($deliveryStop->handoff_courier_name ?: '').' '.($deliveryStop->handoff_courier_phone ?: '').' '.($deliveryStop->handoff_vehicle_number ?: '')) ?: null,
                'created_at' => $deliveryStop->handoff_at?->toIso8601String(),
            ]);
        }

        if ($deliveryRunItem?->delivered_at || $deliveryStop?->delivered_at) {
            $events->push([
                'label' => 'Delivered',
                'status' => 'delivered',
                'location' => $deliveryStop?->town,
                'notes' => $deliveryStop?->delivery_notes,
                'created_at' => ($deliveryRunItem?->delivered_at ?: $deliveryStop?->delivered_at)?->toIso8601String(),
            ]);
        }

        if (Schema::hasTable('shipment_item_tracking')) {
            $item->tracking?->each(function (ShipmentItemTracking $tracking) use ($events) {
                $events->push([
                    'label' => str_replace('_', ' ', ucfirst((string) $tracking->status)),
                    'status' => $tracking->status,
                    'location' => $tracking->location,
                    'notes' => $tracking->notes,
                    'created_at' => $tracking->created_at?->toIso8601String(),
                ]);
            });
        }

        return $events
            ->filter(fn ($event) => ! empty($event['created_at']))
            ->unique(fn ($event) => implode('|', [$event['label'], $event['status'], $event['created_at']]))
            ->sortByDesc(fn ($event) => strtotime($event['created_at']) ?: 0)
            ->values()
            ->all();
    }

    protected function serializeReceivingPackageDeliveryFee(Shipment $shipment, ShipmentItem $item): array
    {
        $charges = $this->deliveryFeeChargesForItem($shipment, $item);

        if ($charges->isEmpty()) {
            return [
                'mode' => 'none',
                'status' => 'none',
                'amount' => null,
                'currency' => 'GHS',
                'paid_amount' => 0.0,
                'outstanding_amount' => 0.0,
                'notes' => null,
                'payment_method' => 'cash',
                'payment_reference' => null,
                'paid_at' => null,
            ];
        }

        $paidAmount = (float) $charges->where('status', ShipmentCharge::STATUS_PAID)->sum('amount');
        $outstandingAmount = (float) $charges
            ->whereIn('status', [ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING])
            ->sum('amount');
        $waivedAmount = (float) $charges->where('status', ShipmentCharge::STATUS_WAIVED)->sum('amount');
        $latest = $charges
            ->sortByDesc(fn (ShipmentCharge $charge) => $charge->updated_at?->getTimestamp() ?? 0)
            ->first();
        $latestPaid = $charges
            ->where('status', ShipmentCharge::STATUS_PAID)
            ->sortByDesc(fn (ShipmentCharge $charge) => $charge->paid_at?->getTimestamp() ?? 0)
            ->first();

        $status = match (true) {
            $outstandingAmount > 0 && $paidAmount > 0 => 'partially_paid',
            $outstandingAmount > 0 => 'collect',
            $paidAmount > 0 => 'paid',
            $waivedAmount > 0 => 'waived',
            default => 'none',
        };

        return [
            'mode' => $status === 'paid' ? 'paid' : ($status === 'collect' || $status === 'partially_paid' ? 'collect' : 'none'),
            'status' => $status,
            'amount' => round($outstandingAmount > 0 ? $outstandingAmount : $paidAmount, 2),
            'currency' => $latest?->currency ?: 'GHS',
            'paid_amount' => round($paidAmount, 2),
            'outstanding_amount' => round($outstandingAmount, 2),
            'notes' => $latest?->notes,
            'payment_method' => $latestPaid?->payment_method ?: 'cash',
            'payment_reference' => $latestPaid?->payment_reference,
            'paid_at' => $latestPaid?->paid_at?->toIso8601String(),
        ];
    }

    protected function buildReceivingWorkspaceResponseData(Shipment $shipment, ShipmentItem $item): array
    {
        $shipment = $this->reloadReceivingShipment($shipment);
        $assignment = $shipment->pickupAssignment;
        $serializedItem = $shipment->items->firstWhere('id', $item->id) ?? $item;

        $data = [
            'destination_mode' => $shipment->destination_mode->value,
            'delivery' => $this->serializeShipmentDelivery($shipment),
            'package' => $this->serializeReceivingPackage($shipment, $serializedItem, $assignment),
            'receipt' => $this->serializeReceivingReceipt($assignment?->warehouseReceipt),
            'can_receive' => $this->canReceiveInAdminWorkspace($assignment),
            'assignment_id' => $assignment?->id,
            'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment),
            'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($assignment, $shipment),
        ];

        if ($shipment->destination_mode === ShipmentDestinationMode::SINGLE) {
            $data['packages'] = $shipment->items
                ->map(fn (ShipmentItem $candidate) => $this->serializeReceivingPackage($shipment, $candidate, $assignment))
                ->values();
        }

        return $data;
    }

    protected function serializeReceivingReceipt(?WarehouseReceipt $receipt): ?array
    {
        if (! $receipt) {
            return null;
        }

        return [
            'id' => $receipt->id,
            'status' => $receipt->status,
            'finalized_at' => $receipt->finalized_at?->toIso8601String(),
        ];
    }

    protected function markReceivingReceiptNeedsFinalization(?\App\Models\PickupAssignment $assignment): void
    {
        $receipt = $assignment?->warehouseReceipt;
        if (! $receipt?->isFinalized()) {
            return;
        }

        $hasDiscrepancies = $receipt->items()
            ->where('discrepancy_type', '!=', 'none')
            ->exists();

        $receipt->update([
            'status' => $hasDiscrepancies
                ? WarehouseReceipt::STATUS_DISCREPANCY_OPEN
                : WarehouseReceipt::STATUS_DRAFT,
            'finalized_by_user_id' => null,
            'approved_by_user_id' => null,
            'approval_reason' => null,
            'finalized_at' => null,
        ]);
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

    protected function ensurePickupConfirmationForAdminCreatedPackage(
        ?\App\Models\PickupAssignment $assignment,
        ShipmentItem $item,
    ): void {
        if (! $assignment || ! $this->isPickupCompleteForReceiving($item->shipment, $assignment)) {
            return;
        }

        PickupItemConfirmation::query()->updateOrCreate([
            'pickup_assignment_id' => $assignment->id,
            'shipment_item_id' => $item->id,
        ], [
            'expected_quantity' => (int) $item->quantity,
            'confirmed_quantity' => (int) $item->quantity,
            'notes' => 'Package added by admin during warehouse receiving after pickup was completed.',
            'confirmed_at' => $assignment->picked_up_at
                ?: $assignment->completed_at
                ?: $assignment->received_at
                ?: now(),
        ]);
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

        if (! $this->isPickupCompleteForReceiving($shipment, $assignment)) {
            return response()->json(['success' => false, 'message' => 'Order has not been picked up yet.'], 422);
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

    protected function groupShipmentPackagesByPhoneForReceiving(Shipment $shipment): array
    {
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
        if (! $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment)) {
            return [
                'success' => false,
                'status' => 422,
                'message' => $this->receivingAutoGroupLockReason($assignment, $shipment) ?? 'Auto-group by phone is no longer available for this shipment.',
            ];
        }

        $allImages = $shipment->items->flatMap(fn ($item) => $item->images);

        if ($allImages->isEmpty()) {
            return ['success' => false, 'status' => 400, 'message' => 'No photos to group.'];
        }

        $taggedImages = $allImages->filter(fn ($img) => ! empty($img->recipient_phone));

        if ($taggedImages->isEmpty()) {
            return ['success' => false, 'status' => 400, 'message' => 'No photos have recipient phone tags. Tag photos with phone numbers first.'];
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
            $oldMode = $shipment->destination_mode ?? ShipmentDestinationMode::SINGLE;

            if ($newMode === ShipmentDestinationMode::PER_ITEM) {
                if ($oldMode === ShipmentDestinationMode::SINGLE) {
                    $this->seedPackageDeliveryFromShipment($shipment);
                }

                $shipmentUpdate = array_merge(
                    ['destination_mode' => $newMode],
                    $this->emptyShipmentDeliveryAttributes()
                );
            } else {
                $shipmentUpdate = array_merge($this->shipmentDeliverySeedFromItems($shipment), [
                    'destination_mode' => $newMode,
                    'delivery_recipient_phone' => collect($groupedItems)->pluck('phone')->filter()->first(),
                ]);

                $shipment->items()->update($this->emptyItemDeliveryAttributes());
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

            return [
                'success' => true,
                'status' => 200,
                'message' => count($groupedItems).' package(s) created. Destination mode set to '.$newMode->value.'.',
                'shipment' => $shipment,
                'assignment' => $shipment->pickupAssignment,
                'grouped_items' => $groupedItems,
                'destination_mode' => $newMode,
            ];
        });
    }

    public function autoGroupByPhone(Shipment $shipment): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $result = $this->groupShipmentPackagesByPhoneForReceiving($shipment);
        if (($result['success'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Auto-group by phone is no longer available for this shipment.',
            ], $result['status'] ?? 422);
        }

        /** @var Shipment $shipment */
        $shipment = $result['shipment'];
        $assignment = $result['assignment'];
        $newMode = $result['destination_mode'];

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'destination_mode' => $newMode->value,
                'delivery' => $this->serializeShipmentDelivery($shipment),
                'delivery_recipient_phone' => $shipment->delivery_recipient_phone,
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
                'receiving_packages' => $shipment->items
                    ->map(fn (ShipmentItem $item) => $this->serializeReceivingPackage($shipment, $item, $assignment))
                    ->values(),
                'can_receive' => $this->canReceiveInAdminWorkspace($assignment),
                'assignment_id' => $assignment?->id,
                'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($assignment, $shipment),
                'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($assignment, $shipment),
            ],
        ]);
    }
}
