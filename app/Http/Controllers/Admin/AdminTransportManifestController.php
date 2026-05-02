<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\SortBatch;
use App\Models\TransportContainer;
use App\Models\TransportLoadingException;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\Warehouse;
use App\Services\Warehouse\BarcodeService;
use App\Services\Warehouse\WarehouseTransportService;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminTransportManifestController extends Controller
{
    public function __construct(
        private WarehouseTransportService $transportService,
        private BarcodeService $barcodeService
    )
    {
    }

    /**
     * Display the transport manifests index page.
     */
    public function index(): View
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);
        $drivers    = Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
        $transferBatches = SortBatch::query()
            ->with(['originWarehouse:id,name,code', 'destinationWarehouse:id,name,code'])
            ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
            ->where('status', SortBatch::STATUS_SEALED)
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
        $this->transportService->ensureDefaultContainer($manifest->fresh('items'));

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'receivedBy',
            'items.shipmentItem.shipment',
            'containers.items.manifestItem.shipmentItem.shipment',
            'loadingExceptions.driver:id,name,phone',
            'loadingExceptions.container:id,container_code,container_type',
            'loadingExceptions.manifestItem.shipmentItem:id,description,tracking_code',
            'loadingExceptions.reviewedBy:id,name',
            'warehouseReceipt.startedBy',
            'warehouseReceipt.finalizedBy',
        ]);

        $statusLabel = $this->formatStatusLabel($manifest->status);

        $transportDrivers = Driver::where('is_active', true)
            ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

        $manifestConfig = [
            'assign_driver_endpoint'   => route('admin.transport-manifests.assign-driver', $manifest),
            'unassign_driver_endpoint' => route('admin.transport-manifests.unassign-driver', $manifest),
            'dispatch_endpoint'        => route('admin.transport-manifests.dispatch', $manifest),
            'mark_all_loaded_endpoint' => route('admin.transport-manifests.mark-all-loaded', $manifest),
            'mark_item_loaded_endpoint_template' => route('admin.transport-manifests.items.mark-loaded', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'create_container_endpoint' => route('admin.transport-manifests.containers.store', $manifest),
            'mark_container_loaded_endpoint_template' => route('admin.transport-manifests.containers.mark-loaded', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'delete_container_endpoint_template' => route('admin.transport-manifests.containers.destroy', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'print_container_label_endpoint_template' => route('admin.transport-manifests.containers.print-label', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'move_item_container_endpoint_template' => route('admin.transport-manifests.items.move-container', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'approve_scan_issue_endpoint_template' => route('admin.transport-manifests.scan-issues.approve', ['manifest' => $manifest, 'exception' => '__ISSUE__']),
            'reject_scan_issue_endpoint_template' => route('admin.transport-manifests.scan-issues.reject', ['manifest' => $manifest, 'exception' => '__ISSUE__']),
        ];

        return view('admin.transport-manifests.show', compact('manifest', 'statusLabel', 'transportDrivers', 'manifestConfig'));
    }

    /**
     * Assign a driver to the transport manifest.
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
     * Unassign the driver from the transport manifest.
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

    public function deleteContainer(TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $warehouse = $manifest->originWarehouse;
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Manifest has no origin warehouse.'], 422);
        }

        $result = $this->transportService->deleteContainer($manifest, $container, $warehouse);

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
        $barcodeSvg = $this->barcodeService->renderCode128Svg($container->container_code, 70, 2, 10, true);
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

    /**
     * Export transport manifests data (JSON / Excel / PDF).
     */
    public function export(Request $request)
    {
        $query = TransportManifest::with(['originWarehouse', 'destinationWarehouse', 'assignedDriver'])
            ->withCount('items');

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
            'Driver Phone'  => $m->assignedDriver?->phone ?? '—',
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
}
