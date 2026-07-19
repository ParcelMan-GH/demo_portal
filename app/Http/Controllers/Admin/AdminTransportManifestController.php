<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PlatformSetting;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\TransportContainer;
use App\Models\TransportLoadingException;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\Warehouse;
use App\Services\BackOfficeAccess;
use App\Services\Warehouse\BarcodeService;
use App\Services\Warehouse\PackageContactService;
use App\Services\Warehouse\WarehouseTransportReceivingService;
use App\Services\Warehouse\WarehouseTransportService;
use App\Support\GenericPdfExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminTransportManifestController extends Controller
{
    public function __construct(
        private WarehouseTransportService $transportService,
        private WarehouseTransportReceivingService $receivingService,
        private BarcodeService $barcodeService,
        private readonly BackOfficeAccess $access,
    )
    {
    }

    /**
     * Display the transport manifests index page.
     */
    public function index(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouses = $this->access->warehousesFor($user, 'warehouse');
        $warehouseIds = $this->access->scopedWarehouseIdsFor($user, 'warehouse');
        $drivers    = Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
        $transferBatches = SortBatch::query()
            ->with(['originWarehouse:id,name,code', 'destinationWarehouse:id,name,code'])
            ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
            ->where('status', SortBatch::STATUS_SEALED)
            ->whereIn('origin_warehouse_id', $warehouseIds)
            ->whereDoesntHave('transportManifest')
            ->orderByDesc('id')
            ->get(['id', 'batch_number', 'origin_warehouse_id', 'destination_warehouse_id']);

        $statuses = [
            ['value' => TransportManifest::STATUS_DRAFT,       'label' => 'Draft'],
            ['value' => TransportManifest::STATUS_ASSIGNED,    'label' => 'Assigned'],
            ['value' => TransportManifest::STATUS_LOADING,     'label' => 'Loading'],
            ['value' => TransportManifest::STATUS_IN_TRANSIT,  'label' => 'In Transit'],
            ['value' => TransportManifest::STATUS_ARRIVED,     'label' => 'Arrived'],
            ['value' => TransportManifest::STATUS_RECEIVED,    'label' => 'Received'],
            ['value' => TransportManifest::STATUS_CANCELLED,   'label' => 'Cancelled'],
        ];

        return view('admin.transport-manifests.index', compact('warehouses', 'drivers', 'statuses', 'transferBatches'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
        ]);

        $batch = SortBatch::query()
            ->with(['originWarehouse', 'destinationWarehouse'])
            ->findOrFail((int) $validated['sort_batch_id']);

        $this->access->assertCanUseWarehouse(Auth::guard('admin')->user(), (int) $batch->origin_warehouse_id, 'warehouse');

        if (!$batch->originWarehouse) {
            return response()->json(['success' => false, 'message' => 'Selected batch has no origin warehouse.'], 422);
        }

        $result = $this->transportService->createManifest(
            $batch,
            $batch->originWarehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Return paginated transport manifests data as JSON (AJAX datatable endpoint).
     */
    public function data(Request $request): JsonResponse
    {
        $query = TransportManifest::with(['originWarehouse', 'destinationWarehouse', 'assignedDriver'])
            ->withCount('items');

        $warehouseIds = $this->access->scopedWarehouseIdsFor(Auth::guard('admin')->user(), 'warehouse');
        abort_if($warehouseIds === [], 403);
        $query->where(function (Builder $builder) use ($warehouseIds) {
            $builder->whereIn('origin_warehouse_id', $warehouseIds)
                ->orWhereIn('destination_warehouse_id', $warehouseIds);
        });

        // Search by manifest number
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('manifest_number', 'like', "%{$search}%")
                    ->orWhereHas('assignedDriver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('originWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('destinationWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by origin warehouse
        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        // Filter by destination warehouse
        if ($destinationWarehouseId = $request->get('destination_warehouse_id')) {
            $query->where('destination_warehouse_id', $destinationWarehouseId);
        }

        // Filter by date range (on created_at)
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sorting
        $sortBy        = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts  = [
            'manifest_number', 'status', 'created_at',
            'dispatched_at', 'arrived_at', 'received_at', 'items_count',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 25), 100);
        $page    = (int) $request->get('page', 1);
        $offset  = ($page - 1) * $perPage;

        $total     = $query->count();
        $manifests = $query->skip($offset)->take($perPage)->get();

        $data = $manifests->map(function (TransportManifest $manifest) {
            return [
                'id'                    => $manifest->id,
                'manifest_number'       => $manifest->manifest_number,
                'status'                => $manifest->status,
                'status_label'          => $this->formatStatusLabel($manifest->status),
                'origin_warehouse'      => $manifest->originWarehouse?->name,
                'origin_warehouse_code' => $manifest->originWarehouse?->code,
                'destination_warehouse'      => $manifest->destinationWarehouse?->name,
                'destination_warehouse_code' => $manifest->destinationWarehouse?->code,
                'driver_name'           => $manifest->assignedDriver?->name,
                'driver_phone'          => $manifest->assignedDriver?->phone,
                'items_count'           => $manifest->items_count,
                'dispatched_at'         => $manifest->dispatched_at?->format('Y-m-d H:i:s'),
                'arrived_at'            => $manifest->arrived_at?->format('Y-m-d H:i:s'),
                'received_at'           => $manifest->received_at?->format('Y-m-d H:i:s'),
                'created_at'            => $manifest->created_at->format('Y-m-d H:i:s'),
                'can_delete'            => $this->transportService->deleteState($manifest)['deletable'],
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage) ?: 1,
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Display the transport manifest detail page.
     */
    public function show(TransportManifest $manifest): View
    {
        $this->assertCanUseManifest($manifest);

        $this->transportService->ensureDefaultContainer($manifest->fresh('items'));

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'receivedBy',
            'items.shipmentItem.shipment',
            'containers' => fn ($query) => $query
                ->withCount('items')
                ->withSum('items', 'expected_quantity')
                ->orderBy('sequence_number'),
            'loadingExceptions.driver:id,name,phone',
            'loadingExceptions.container:id,container_code,container_type',
            'loadingExceptions.manifestItem.shipmentItem:id,description,tracking_code',
            'loadingExceptions.reviewedBy:id,name',
            'warehouseReceipt.startedBy',
            'warehouseReceipt.finalizedBy',
        ]);

        $statusLabel = $this->formatStatusLabel($manifest->status);
        $deleteState = $this->transportService->deleteState($manifest);

        $transportDrivers = Driver::where('is_active', true)
            ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

        $manifestConfig = [
            'assign_driver_endpoint'   => route('admin.transport-manifests.assign-driver', $manifest),
            'unassign_driver_endpoint' => route('admin.transport-manifests.unassign-driver', $manifest),
            'dispatch_endpoint'        => route('admin.transport-manifests.dispatch', $manifest),
            'undo_dispatch_endpoint'   => route('admin.transport-manifests.undo-dispatch', $manifest),
            'print_waybill_endpoint'   => route('admin.transport-manifests.print-waybill', $manifest),
            'mark_all_loaded_endpoint' => route('admin.transport-manifests.mark-all-loaded', $manifest),
            'mark_all_not_loaded_endpoint' => route('admin.transport-manifests.mark-all-not-loaded', $manifest),
            'mark_arrived_endpoint' => route('admin.transport-manifests.mark-arrived', $manifest),
            'undo_arrival_endpoint' => route('admin.transport-manifests.undo-arrival', $manifest),
            'mark_item_loaded_endpoint_template' => route('admin.transport-manifests.items.mark-loaded', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'mark_item_not_loaded_endpoint_template' => route('admin.transport-manifests.items.mark-not-loaded', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'create_container_endpoint' => route('admin.transport-manifests.containers.store', $manifest),
            'container_items_endpoint_template' => route('admin.transport-manifests.containers.items-data', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'update_container_notes_endpoint_template' => route('admin.transport-manifests.containers.notes', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'mark_container_loaded_endpoint_template' => route('admin.transport-manifests.containers.mark-loaded', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'mark_container_not_loaded_endpoint_template' => route('admin.transport-manifests.containers.mark-not-loaded', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'delete_container_endpoint_template' => route('admin.transport-manifests.containers.destroy', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'print_container_label_endpoint_template' => route('admin.transport-manifests.containers.print-label', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'move_item_container_endpoint_template' => route('admin.transport-manifests.items.move-container', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'approve_scan_issue_endpoint_template' => route('admin.transport-manifests.scan-issues.approve', ['manifest' => $manifest, 'exception' => '__ISSUE__']),
            'reject_scan_issue_endpoint_template' => route('admin.transport-manifests.scan-issues.reject', ['manifest' => $manifest, 'exception' => '__ISSUE__']),
            'delete_endpoint' => route('admin.transport-manifests.destroy', $manifest),
            'index_url' => route('admin.transport-manifests.index'),
        ];

        $manifestIndexUrl = route('admin.transport-manifests.index');
        $manifestBackLabel = 'Back to Transport Manifests';
        $sortBatchUrl = $manifest->sortBatch ? route('admin.sort-batches.show', $manifest->sortBatch) : null;
        $transferTimeline = $this->transportService->manifestTimeline($manifest);

        return view('admin.transport-manifests.show', compact('manifest', 'statusLabel', 'transportDrivers', 'manifestConfig', 'deleteState', 'manifestIndexUrl', 'manifestBackLabel', 'sortBatchUrl', 'transferTimeline'));
    }

    public function incomingIndex(): View
    {
        $warehouse = null;

        return view('warehouse.manifests.incoming.index', [
            'warehouse' => $warehouse,
            'layoutName' => 'admin.layouts.app',
            'pageTitle' => 'Incoming Transport Manifests',
            'dataEndpoint' => route('admin.transport-manifests.incoming.data'),
            'showDestinationWarehouse' => true,
        ]);
    }

    public function incomingData(Request $request): JsonResponse
    {
        $query = TransportManifest::query()
            ->with([
                'originWarehouse:id,name,code',
                'destinationWarehouse:id,name,code',
                'assignedDriver:id,name,phone',
                'items:id,transport_manifest_id',
            ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('manifest_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('originWarehouse', fn ($originQuery) => $originQuery->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('destinationWarehouse', fn ($destinationQuery) => $destinationQuery->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('assignedDriver', fn ($driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['manifest_number', 'status', 'assigned_at', 'arrived_at', 'received_at', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest('id');
        }

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $rows = $query->skip($offset)->take($perPage)->get()->map(function (TransportManifest $manifest) {
            return [
                'id' => $manifest->id,
                'manifest_number' => $manifest->manifest_number,
                'status' => $manifest->status,
                'status_label' => $this->formatStatusLabel($manifest->status),
                'origin_warehouse' => $manifest->originWarehouse?->name,
                'destination_warehouse' => $manifest->destinationWarehouse?->name,
                'driver_name' => $manifest->assignedDriver?->name,
                'items_count' => $manifest->items->count(),
                'arrived_at' => optional($manifest->arrived_at)?->format('Y-m-d H:i:s'),
                'received_at' => optional($manifest->received_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('admin.transport-manifests.incoming.show', $manifest),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function incomingShow(TransportManifest $manifest): View
    {
        $warehouse = $manifest->destinationWarehouse;
        if (!$warehouse) {
            abort(404);
        }

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'items.shipmentItem.shipment.vendor',
            'items.shipmentItem.warehouseReceiptItems.labels',
            'items.labelScans:id,transport_manifest_item_id,barcode_value,scanned_at',
            'containers.items.manifestItem.shipmentItem.shipment.vendor',
            'containers.items.manifestItem.shipmentItem.warehouseReceiptItems.labels',
            'containers.items.manifestItem.labelScans:id,transport_manifest_item_id,barcode_value,scanned_at',
        ]);

        $config = [
            'scan_receive_endpoint' => route('admin.transport-manifests.incoming.items.scan', ['manifest' => $manifest, 'shipmentItem' => '__ITEM__']),
            'finalize_endpoint' => route('admin.transport-manifests.incoming.finalize', ['manifest' => $manifest]),
        ];

        return view('warehouse.manifests.incoming.show', [
            'warehouse' => $warehouse,
            'manifest' => $manifest,
            'manifestConfig' => $config,
            'layoutName' => 'admin.layouts.app',
            'indexRoute' => route('admin.transport-manifests.incoming.index'),
            'backLabel' => 'Back to Incoming Manifests',
        ]);
    }

    public function scanIncomingItem(Request $request, TransportManifest $manifest, ShipmentItem $shipmentItem): JsonResponse
    {
        if ($manifest->status !== TransportManifest::STATUS_ARRIVED) {
            return response()->json([
                'success' => false,
                'message' => 'Manifest must be marked arrived before receiving items.',
            ], 422);
        }

        $warehouse = $manifest->destinationWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no destination warehouse.'], 422);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:0'],
            'line_status' => ['nullable', 'in:pending,loaded,received,short,excess,damaged'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->receivingService->scanReceive(
            manifest: $manifest,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['received_quantity'],
            lineStatus: $validated['line_status'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function finalizeIncoming(Request $request, TransportManifest $manifest): JsonResponse
    {
        if ($manifest->status !== TransportManifest::STATUS_ARRIVED) {
            return response()->json([
                'success' => false,
                'message' => 'Manifest must be marked arrived before finalizing receipt.',
            ], 422);
        }

        $warehouse = $manifest->destinationWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no destination warehouse.'], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $result = $this->receivingService->finalizeReceipt(
            manifest: $manifest,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            notes: $validated['notes'] ?? null
        );

        if ($result['success'] && PlatformSetting::getValue('contact_queue.auto_queue_on_transport_receive', false)) {
            try {
                $itemIds = $manifest->items()->pluck('shipment_item_id')->toArray();
                app(PackageContactService::class)->createTasksForWarehouseItems($warehouse, $itemIds);
            } catch (\Throwable $e) {
                Log::warning('Auto-queue on admin transport receive failed: ' . $e->getMessage());
            }
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Assign a rider to the transport manifest.
     */
    public function assignDriver(Request $request, TransportManifest $manifest): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        // For admin, use the manifest's own origin warehouse (no warehouse-scoped auth needed)
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $driver = Driver::findOrFail((int) $validated['driver_id']);
        $result = $this->transportService->assignDriver($manifest, $driver, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Unassign the rider from the transport manifest.
     */
    public function unassignDriver(Request $request, TransportManifest $manifest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->unassignDriver(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Dispatch the transport manifest.
     */
    public function dispatch(TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->dispatch($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function undoDispatch(Request $request, TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->transportService->undoDispatch(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markItemLoaded(TransportManifest $manifest, TransportManifestItem $item): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkItemLoaded(
            $manifest,
            $item,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markAllLoaded(TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkAllItemsLoaded(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markAllNotLoaded(TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkAllItemsNotLoaded(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markArrived(TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkArrived(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function undoArrival(Request $request, TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->transportService->undoArrival(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markItemNotLoaded(TransportManifest $manifest, TransportManifestItem $item): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkItemNotLoaded(
            $manifest,
            $item,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function createContainer(Request $request, TransportManifest $manifest): JsonResponse
    {
        $validated = $request->validate([
            'container_type' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->createContainer(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['container_type'] ?? 'box',
            $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function updateContainerNotes(Request $request, TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->updateContainerNotes(
            $manifest,
            $container,
            $warehouse,
            $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function containerItemsData(Request $request, TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            abort(404);
        }

        return $this->containerItemsResponse($request, $container);
    }

    public function moveItemToContainer(Request $request, TransportManifest $manifest, TransportManifestItem $item): JsonResponse
    {
        $validated = $request->validate([
            'container_id' => ['required', 'integer', 'exists:transport_containers,id'],
        ]);

        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $container = TransportContainer::query()->findOrFail((int) $validated['container_id']);
        $result = $this->transportService->moveItemToContainer(
            $manifest,
            $item,
            $container,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markContainerLoaded(TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkContainerLoaded(
            $manifest,
            $container,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markContainerNotLoaded(TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminMarkContainerNotLoaded(
            $manifest,
            $container,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function deleteContainer(TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->deleteContainer($manifest, $container, $warehouse);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function containerItemsResponse(Request $request, TransportContainer $container): JsonResponse
    {
        $query = $container->items()
            ->with([
                'manifestItem:id',
                'manifestItem.labelScans:id,transport_manifest_item_id,barcode_value',
                'shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone',
                'shipmentItem.shipment:id,shipment_number',
                'shipmentItem.warehouseReceiptItems.labels',
            ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('shipmentItem', function (Builder $itemQuery) use ($search) {
                    $itemQuery->where('description', 'like', "%{$search}%")
                        ->orWhere('tracking_code', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                        ->orWhereHas('shipment', fn (Builder $shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"));
                })->orWhereHas('manifestItem.labelScans', fn (Builder $labelQuery) => $labelQuery->where('barcode_value', 'like', "%{$search}%"));
            });
        }

        $perPage = min(max((int) $request->input('per_page', 50), 10), 100);
        $page = max((int) $request->input('page', 1), 1);
        $total = $query->count();
        $rows = $query->orderBy('id')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $rows->map(function ($containerItem) {
                $shipmentItem = $containerItem->shipmentItem;
                $trackingCode = (string) ($shipmentItem?->tracking_code ?? '');
                $labelCodes = $shipmentItem?->warehouseReceiptItems
                    ? $shipmentItem->warehouseReceiptItems
                        ->flatMap(fn ($receiptItem) => $receiptItem->labels)
                        ->sortBy(fn ($label) => [(int) ($label->label_index ?? 0), (int) $label->id])
                        ->pluck('barcode_value')
                        ->filter()
                        ->map(function ($labelCode) use ($trackingCode) {
                            $labelCode = (string) $labelCode;
                            return $trackingCode !== '' && str_starts_with($labelCode, $trackingCode . '-')
                                ? str($labelCode)->after($trackingCode . '-')->toString()
                                : $labelCode;
                        })
                        ->values()
                    : collect();

                return [
                    'id' => $containerItem->id,
                    'manifest_item_id' => $containerItem->transport_manifest_item_id,
                    'description' => $shipmentItem?->description ?? 'Package',
                    'tracking_code' => $trackingCode,
                    'shipment_number' => $shipmentItem?->shipment?->shipment_number,
                    'recipient_name' => $shipmentItem?->delivery_recipient_name,
                    'recipient_phone' => $shipmentItem?->delivery_recipient_phone,
                    'quantity' => (int) $containerItem->expected_quantity,
                    'labels_count' => $labelCodes->count(),
                    'labels_preview' => $labelCodes->take(3)->values(),
                ];
            })->values(),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total),
            ],
        ]);
    }

    public function destroy(TransportManifest $manifest): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $user = Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Admin user not found.'], 422);
        }

        $result = $this->transportService->deleteManifest($manifest, $warehouse, $user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function approveScanIssue(Request $request, TransportManifest $manifest, TransportLoadingException $exception): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminReviewLoadingException(
            $manifest,
            $exception,
            $warehouse,
            Auth::guard('admin')->user(),
            true,
            $validated['admin_note'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function rejectScanIssue(Request $request, TransportManifest $manifest, TransportLoadingException $exception): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->adminReviewLoadingException(
            $manifest,
            $exception,
            $warehouse,
            Auth::guard('admin')->user(),
            false,
            $validated['admin_note'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function printContainerLabel(TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            return response()->json(['success' => false, 'message' => 'Container not found on this manifest.'], 404);
        }

        $manifest->loadMissing(['originWarehouse', 'destinationWarehouse']);
        $container->loadMissing('items');
        $barcodeSvg = $this->barcodeService->renderCode128Svg($container->container_code, 210, 2, 10, false);
        $labelHtml = view('shared.transport-container-label', [
            'manifest' => $manifest,
            'container' => $container,
            'barcodeSvg' => $barcodeSvg,
        ])->render();

        return response()->json([
            'success' => true,
            'message' => 'Container label generated.',
            'data' => [
                'container_code' => $container->container_code,
                'label_html' => $labelHtml,
            ],
        ]);
    }

    public function printWaybill(TransportManifest $manifest): JsonResponse
    {
        $manifest->loadMissing([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'createdBy',
            'sortBatch',
            'items.shipmentItem.shipment',
            'containers' => fn ($query) => $query->orderBy('sequence_number'),
            'containers.items.shipmentItem.shipment',
            'containers.items.manifestItem',
        ]);

        if ($manifest->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Add packages before printing a waybill.'], 422);
        }

        $waybillHtml = view('shared.transport-waybill', [
            'manifest' => $manifest,
        ])->render();

        return response()->json([
            'success' => true,
            'message' => 'Waybill generated.',
            'data' => [
                'label_html' => $waybillHtml,
            ],
        ]);
    }

    /**
     * Export transport manifests data (JSON / Excel / PDF).
     */
    public function export(Request $request)
    {
        $query = TransportManifest::with(['originWarehouse', 'destinationWarehouse', 'assignedDriver'])
            ->withCount('items');

        $warehouseIds = $this->access->scopedWarehouseIdsFor(Auth::guard('admin')->user(), 'warehouse');
        abort_if($warehouseIds === [], 403);
        $query->where(function (Builder $builder) use ($warehouseIds) {
            $builder->whereIn('origin_warehouse_id', $warehouseIds)
                ->orWhereIn('destination_warehouse_id', $warehouseIds);
        });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('manifest_number', 'like', "%{$search}%")
                    ->orWhereHas('assignedDriver', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('originWarehouse', fn($wq) => $wq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('destinationWarehouse', fn($wq) => $wq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        if ($destinationWarehouseId = $request->get('destination_warehouse_id')) {
            $query->where('destination_warehouse_id', $destinationWarehouseId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $manifests = $query->orderBy('created_at', 'desc')->get();

        $rows = $manifests->map(fn(TransportManifest $m) => [
            'Manifest #'    => $m->manifest_number,
            'From'          => $m->originWarehouse?->name ?? '—',
            'To'            => $m->destinationWarehouse?->name ?? '—',
            'Driver'        => $m->assignedDriver?->name ?? '—',
            'Rider Phone'  => $m->assignedDriver?->phone ?? '—',
            'Items'         => $m->items_count,
            'Status'        => $this->formatStatusLabel($m->status),
            'Dispatched At' => $m->dispatched_at?->format('Y-m-d H:i:s') ?? '—',
            'Arrived At'    => $m->arrived_at?->format('Y-m-d H:i:s') ?? '—',
            'Received At'   => $m->received_at?->format('Y-m-d H:i:s') ?? '—',
            'Created At'    => $m->created_at->format('Y-m-d H:i:s'),
        ])->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'transport_manifests_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'transport_manifests_' . date('Y-m-d_His') . '.pdf', 'Transport Manifests');
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Convert a status string into a human-readable label.
     */
    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            TransportManifest::STATUS_DRAFT      => 'Draft',
            TransportManifest::STATUS_ASSIGNED   => 'Assigned',
            TransportManifest::STATUS_LOADING    => 'Loading',
            TransportManifest::STATUS_IN_TRANSIT => 'In Transit',
            TransportManifest::STATUS_ARRIVED    => 'Arrived',
            TransportManifest::STATUS_RECEIVED   => 'Received',
            TransportManifest::STATUS_CANCELLED  => 'Cancelled',
            default                              => ucwords(str_replace('_', ' ', $status)),
        };
    }

    private function assertCanUseManifest(TransportManifest $manifest): void
    {
        $warehouseIds = $this->access->warehouseIdsFor(Auth::guard('admin')->user(), 'warehouse');

        abort_unless(
            in_array((int) $manifest->origin_warehouse_id, $warehouseIds, true)
            || in_array((int) $manifest->destination_warehouse_id, $warehouseIds, true),
            403
        );
    }
}
