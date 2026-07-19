<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\TransportContainer;
use App\Models\TransportLoadingException;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\TransportManifestReceiptLabelScan;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\Warehouse\BarcodeService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseTransportReceivingService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransportManifestController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseTransportService $transportService,
        private WarehouseTransportReceivingService $receivingService,
        private BarcodeService $barcodeService
    ) {
    }

    public function outboundIndex(): View
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.manifests.transport.index', [
            'warehouse' => $warehouse,
            'transportDrivers' => Driver::query()
                ->where('is_active', true)
                ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']),
            'transferBatches' => SortBatch::query()
                ->with('destinationWarehouse:id,name,code')
                ->where('origin_warehouse_id', $warehouse->id)
                ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
                ->where('status', SortBatch::STATUS_SEALED)
                ->whereDoesntHave('transportManifest')
                ->orderByDesc('id')
                ->get(['id', 'batch_number', 'destination_warehouse_id'])
                ->map(fn (SortBatch $batch) => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'destination' => $batch->destinationWarehouse?->name,
                ]),
            'destinationWarehouses' => Warehouse::query()
                ->where('is_active', true)
                ->whereKeyNot($warehouse->id)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Warehouse $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                ]),
        ]);
    }

    public function outboundShow(TransportManifest $manifest): View
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if ($manifest->items()->exists()) {
            $this->transportService->ensureDefaultContainer($manifest->fresh('items'));
        }

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'receivedBy',
            'items.shipmentItem.shipment.vendor',
            'items.shipmentItem.warehouseReceiptItems.labels',
            'items.labelScans:id,transport_manifest_item_id,barcode_value,scanned_at',
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

        $transportDrivers = Driver::query()
            ->where('is_active', true)
            ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

        $availableSortBatches = SortBatch::query()
            ->with([
                'destinationWarehouse:id,name,code',
                'activeItems.shipmentItem.shipment.vendor:id,name',
            ])
            ->withCount('activeItems')
            ->where('origin_warehouse_id', $warehouse->id)
            ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
            ->where('status', SortBatch::STATUS_SEALED)
            ->where(function (Builder $query) use ($manifest) {
                $query->whereDoesntHave('transportManifest')
                    ->orWhereHas('transportManifest', fn (Builder $manifestQuery) => $manifestQuery->whereKey($manifest->id));
            })
            ->orderByDesc('sealed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SortBatch $batch) => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'destination' => $batch->destinationWarehouse?->name,
                'destination_code' => $batch->destinationWarehouse?->code,
                'items_count' => (int) $batch->active_items_count,
                'items' => $batch->activeItems->map(fn ($batchItem) => [
                    'id' => $batchItem->id,
                    'shipment_item_id' => $batchItem->shipment_item_id,
                    'shipment_number' => $batchItem->shipmentItem?->shipment?->shipment_number,
                    'tracking_code' => $batchItem->shipmentItem?->tracking_code,
                    'description' => $batchItem->shipmentItem?->description,
                    'vendor_name' => $batchItem->shipmentItem?->shipment?->vendor?->name,
                    'recipient_name' => $batchItem->shipmentItem?->delivery_recipient_name,
                    'recipient_phone' => $batchItem->shipmentItem?->delivery_recipient_phone,
                    'quantity' => (int) $batchItem->quantity_allocated,
                ])->values(),
            ])->values();

        $containerItems = collect();

        $itemsData = $manifest->items->map(function ($line) use ($containerItems) {
            $shipmentItem = $line->shipmentItem;
            $shipment = $shipmentItem?->shipment;
            $container = $containerItems->get($line->id);
            return [
                'id' => $line->id,
                'description' => $shipmentItem?->description ?? '-',
                'tracking_code' => $shipmentItem?->tracking_code ?? '',
                'shipment_item_id' => $line->shipment_item_id,
                'shipment_number' => $shipment?->shipment_number ?? '-',
                'vendor_name' => $shipment?->vendor?->name ?? '',
                'container_id' => $container?->id,
                'container_code' => $container?->container_code,
                'expected_quantity' => (int) $line->expected_quantity,
                'loaded_quantity' => (int) $line->loaded_quantity,
                'received_quantity' => (int) $line->received_quantity,
                'line_status' => $line->line_status ?? 'pending',
                'notes' => $line->notes ?: '',
                'loaded_at' => $line->loaded_at?->format('M d, H:i'),
                'received_at' => $line->received_at?->format('M d, H:i'),
            ];
        })->values()->toArray();

        $manifestConfig = [
            'assign_driver_endpoint' => route('warehouse.manifests.transport.assign-driver', ['manifest' => $manifest]),
            'dispatch_endpoint' => route('warehouse.manifests.transport.dispatch', ['manifest' => $manifest]),
            'undo_dispatch_endpoint' => route('warehouse.manifests.transport.undo-dispatch', ['manifest' => $manifest]),
            'print_waybill_endpoint' => route('warehouse.manifests.transport.print-waybill', ['manifest' => $manifest]),
            'unassign_driver_endpoint' => route('warehouse.manifests.transport.unassign-driver', ['manifest' => $manifest]),
            'mark_all_loaded_endpoint' => route('warehouse.manifests.transport.mark-all-loaded', ['manifest' => $manifest]),
            'mark_all_not_loaded_endpoint' => route('warehouse.manifests.transport.mark-all-not-loaded', ['manifest' => $manifest]),
            'mark_arrived_endpoint' => route('warehouse.manifests.transport.mark-arrived', ['manifest' => $manifest]),
            'undo_arrival_endpoint' => route('warehouse.manifests.transport.undo-arrival', ['manifest' => $manifest]),
            'mark_item_loaded_endpoint_template' => route('warehouse.manifests.transport.items.mark-loaded', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'mark_item_not_loaded_endpoint_template' => route('warehouse.manifests.transport.items.mark-not-loaded', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'create_container_endpoint' => route('warehouse.manifests.transport.containers.store', ['manifest' => $manifest]),
            'container_items_endpoint_template' => route('warehouse.manifests.transport.containers.items-data', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'attach_sort_batch_container_endpoint_template' => route('warehouse.manifests.transport.containers.attach-sort-batch', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'update_container_notes_endpoint_template' => route('warehouse.manifests.transport.containers.notes', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'mark_container_loaded_endpoint_template' => route('warehouse.manifests.transport.containers.mark-loaded', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'mark_container_not_loaded_endpoint_template' => route('warehouse.manifests.transport.containers.mark-not-loaded', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'delete_container_endpoint_template' => route('warehouse.manifests.transport.containers.destroy', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'print_container_label_endpoint_template' => route('warehouse.manifests.transport.containers.print-label', ['manifest' => $manifest, 'container' => '__CONTAINER__']),
            'move_item_container_endpoint_template' => route('warehouse.manifests.transport.items.move-container', ['manifest' => $manifest, 'item' => '__ITEM__']),
            'approve_scan_issue_endpoint_template' => route('warehouse.manifests.transport.scan-issues.approve', ['manifest' => $manifest, 'exception' => '__ISSUE__']),
            'reject_scan_issue_endpoint_template' => route('warehouse.manifests.transport.scan-issues.reject', ['manifest' => $manifest, 'exception' => '__ISSUE__']),
            'delete_endpoint' => route('warehouse.manifests.transport.destroy', ['manifest' => $manifest]),
            'index_url' => route('warehouse.manifests.transport.index'),
        ];

        $assignmentHistory = $manifest->assignments()
            ->with(['driver:id,name,phone,vehicle_type,vehicle_number', 'assignedBy:id,name', 'unassignedBy:id,name'])
            ->orderByDesc('id')
            ->get();
        $statusLabel = $this->outboundStatusLabel($manifest->status);
        $deleteState = $this->transportService->deleteState($manifest);
        $transferTimeline = $this->transportService->manifestTimeline($manifest);
        $manifestIndexUrl = route('warehouse.manifests.transport.index');
        $manifestBackLabel = 'Back to Outgoing Transfers';
        $sortBatchUrl = $manifest->sortBatch ? route('warehouse.sorting.show', $manifest->sortBatch) : null;

        return view('warehouse.manifests.transport.show', [
            'warehouse' => $warehouse,
            'manifest' => $manifest,
            'transportDrivers' => $transportDrivers,
            'manifestConfig' => $manifestConfig,
            'itemsData' => $itemsData,
            'assignmentHistory' => $assignmentHistory,
            'statusLabel' => $statusLabel,
            'deleteState' => $deleteState,
            'transferTimeline' => $transferTimeline,
            'manifestIndexUrl' => $manifestIndexUrl,
            'manifestBackLabel' => $manifestBackLabel,
            'sortBatchUrl' => $sortBatchUrl,
            'manifestResourceLabel' => 'Outgoing Transfer',
            'manifestCompletedLabel' => 'Completed',
            'hideManifestOrigin' => true,
            'availableSortBatches' => $availableSortBatches,
            'allowSortBatchAttachment' => true,
        ]);
    }

    public function outboundData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->transportService->outboundQuery($warehouse);
        $summary = $this->outboundSummary(clone $query);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('manifest_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('assignedDriver', fn (Builder $driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sortBatch', fn (Builder $batchQuery) => $batchQuery->where('batch_number', 'like', "%{$search}%"))
                    ->orWhereHas('destinationWarehouse', fn (Builder $warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items.shipmentItem.shipment', fn (Builder $shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $this->applyOutboundStatusFilter($query, (string) $status);
        }

        if ($destinationId = $request->input('destination_warehouse_id')) {
            $query->where('destination_warehouse_id', (int) $destinationId);
        }

        if ($driverId = $request->input('driver_id')) {
            $query->where('assigned_driver_id', (int) $driverId);
        }

        if ($assignedState = $request->input('assigned_state')) {
            if ($assignedState === 'assigned') {
                $query->whereNotNull('assigned_driver_id');
            } elseif ($assignedState === 'unassigned') {
                $query->whereNull('assigned_driver_id');
            }
        }

        $dateColumn = match ((string) $request->input('date_type', 'created_at')) {
            'assigned_at' => 'assigned_at',
            'dispatched_at' => 'dispatched_at',
            'arrived_at' => 'arrived_at',
            'completed_at' => 'received_at',
            default => 'created_at',
        };

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate($dateColumn, '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate($dateColumn, '<=', $dateTo);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['manifest_number', 'status', 'assigned_at', 'dispatched_at', 'arrived_at', 'received_at', 'created_at', 'items_count', 'sort_batch_id', 'destination_warehouse_id'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, function (TransportManifest $manifest) {
            $expectedQuantity = (int) $manifest->items->sum('expected_quantity');
            $loadedQuantity = (int) $manifest->items->sum('loaded_quantity');

            return [
                'id' => $manifest->id,
                'manifest_number' => $manifest->manifest_number,
                'status' => $manifest->status,
                'status_label' => $this->outboundStatusLabel($manifest->status),
                'destination_warehouse' => $manifest->destinationWarehouse?->name,
                'batch_number' => $manifest->sortBatch?->batch_number,
                'batch_url' => $manifest->sortBatch ? route('warehouse.sorting.show', $manifest->sortBatch) : null,
                'driver_name' => $manifest->assignedDriver?->name,
                'driver_phone' => $manifest->assignedDriver?->phone,
                'items_count' => (int) ($manifest->items_count ?? $manifest->items->count()),
                'loaded_count' => $loadedQuantity,
                'expected_count' => $expectedQuantity,
                'loaded_display' => $loadedQuantity . ' / ' . $expectedQuantity,
                'created_by' => $manifest->createdBy?->name,
                'created_at' => optional($manifest->created_at)?->format('Y-m-d H:i:s'),
                'assigned_at' => optional($manifest->assigned_at)?->format('Y-m-d H:i:s'),
                'dispatched_at' => optional($manifest->dispatched_at)?->format('Y-m-d H:i:s'),
                'arrived_at' => optional($manifest->arrived_at)?->format('Y-m-d H:i:s'),
                'completed_at' => optional($manifest->received_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.manifests.transport.show', $manifest),
            ];
        }, ['summary' => $summary]);
    }

    public function create(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'sort_batch_id' => ['nullable', 'integer', 'exists:sort_batches,id'],
        ]);

        if (!empty($validated['sort_batch_id'])) {
            $batch = SortBatch::query()->with('destinationWarehouse')->findOrFail((int) $validated['sort_batch_id']);
            $result = $this->transportService->createManifest($batch, $warehouse, Auth::guard('admin')->user());
        } else {
            $result = $this->transportService->createDraftManifest($warehouse, Auth::guard('admin')->user());
        }

        if ($result['success'] && isset($result['data']['manifest'])) {
            $result['data']['redirect_url'] = route('warehouse.manifests.transport.show', $result['data']['manifest']);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function assignDriver(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $driver = Driver::query()->findOrFail((int) $validated['driver_id']);
        $result = $this->transportService->assignDriver($manifest, $driver, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function unassignDriver(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->transportService->unassignDriver(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function dispatch(TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->dispatch($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function undoDispatch(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
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
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->adminMarkItemLoaded($manifest, $item, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markItemNotLoaded(TransportManifest $manifest, TransportManifestItem $item): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->adminMarkItemNotLoaded($manifest, $item, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markAllLoaded(TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->adminMarkAllItemsLoaded($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markAllNotLoaded(TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->adminMarkAllItemsNotLoaded($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markArrived(TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->adminMarkArrived($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function undoArrival(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
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

    public function createContainer(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'container_type' => ['nullable', 'string', 'max:40'],
            'sort_batch_id' => ['nullable', 'integer', 'exists:sort_batches,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sortBatch = !empty($validated['sort_batch_id'])
            ? SortBatch::query()->findOrFail((int) $validated['sort_batch_id'])
            : null;

        $result = $this->transportService->createContainer(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['container_type'] ?? 'box',
            $validated['notes'] ?? null,
            $sortBatch
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function attachSortBatchToContainer(Request $request, TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
        ]);

        $sortBatch = SortBatch::query()->findOrFail((int) $validated['sort_batch_id']);
        $result = $this->transportService->attachSortBatchToExistingContainer(
            $manifest,
            $container,
            $sortBatch,
            $warehouse,
            Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function containerItemsData(Request $request, TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if ((int) $container->transport_manifest_id !== (int) $manifest->id) {
            abort(404);
        }

        return $this->containerItemsResponse($request, $container);
    }

    public function updateContainerNotes(Request $request, TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->transportService->updateContainerNotes(
            $manifest,
            $container,
            $warehouse,
            $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function moveItemToContainer(Request $request, TransportManifest $manifest, TransportManifestItem $item): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'container_id' => ['required', 'integer', 'exists:transport_containers,id'],
        ]);

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
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
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
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
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
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $result = $this->transportService->deleteContainer($manifest, $container, $warehouse);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function printContainerLabel(TransportManifest $manifest, TransportContainer $container): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

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
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

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

    public function approveScanIssue(Request $request, TransportManifest $manifest, TransportLoadingException $exception): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

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
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

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

    public function destroy(TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->deleteManifest($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function incomingIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.manifests.incoming.index', [
            'warehouse' => $warehouse,
            'originWarehouses' => Warehouse::query()
                ->where('is_active', true)
                ->whereKeyNot($warehouse->id)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Warehouse $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                ]),
            'transportDrivers' => Driver::query()
                ->where('is_active', true)
                ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
        ]);
    }

    public function incomingData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->transportService->incomingQuery($warehouse);
        $summary = $this->incomingSummary(clone $query);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('manifest_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('originWarehouse', fn (Builder $originQuery) => $originQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedDriver', fn (Builder $driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $this->applyIncomingStatusFilter($query, (string) $status);
        }

        if ($originId = $request->input('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', (int) $originId);
        }

        if ($driverId = $request->input('driver_id')) {
            $query->where('assigned_driver_id', (int) $driverId);
        }

        $dateColumn = match ((string) $request->input('date_type', 'created_at')) {
            'assigned_at' => 'assigned_at',
            'dispatched_at' => 'dispatched_at',
            'arrived_at' => 'arrived_at',
            'received_at' => 'received_at',
            default => 'created_at',
        };

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate($dateColumn, '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate($dateColumn, '<=', $dateTo);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['manifest_number', 'status', 'origin_warehouse_id', 'assigned_at', 'dispatched_at', 'arrived_at', 'received_at', 'created_at', 'items_count'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, function (TransportManifest $manifest) {
            $expectedQuantity = (int) $manifest->items->sum('expected_quantity');
            $receivedQuantity = (int) $manifest->items->sum('received_quantity');

            return [
                'id' => $manifest->id,
                'manifest_number' => $manifest->manifest_number,
                'status' => $manifest->status,
                'status_label' => $this->incomingStatusLabel($manifest),
                'origin_warehouse' => $manifest->originWarehouse?->name,
                'origin_warehouse_code' => $manifest->originWarehouse?->code,
                'destination_warehouse' => $manifest->destinationWarehouse?->name,
                'driver_name' => $manifest->assignedDriver?->name,
                'driver_phone' => $manifest->assignedDriver?->phone,
                'items_count' => (int) ($manifest->items_count ?? $manifest->items->count()),
                'received_count' => $receivedQuantity,
                'expected_count' => $expectedQuantity,
                'received_display' => $receivedQuantity . ' / ' . $expectedQuantity,
                'created_at' => optional($manifest->created_at)?->format('Y-m-d H:i:s'),
                'assigned_at' => optional($manifest->assigned_at)?->format('Y-m-d H:i:s'),
                'dispatched_at' => optional($manifest->dispatched_at)?->format('Y-m-d H:i:s'),
                'arrived_at' => optional($manifest->arrived_at)?->format('Y-m-d H:i:s'),
                'received_at' => optional($manifest->received_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.manifests.incoming.show', $manifest),
            ];
        }, ['summary' => $summary]);
    }

    public function incomingShow(TransportManifest $manifest): View
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'items.shipmentItem.shipment.vendor',
            'items.shipmentItem.shipment.pickupAssignment.photos',
            'items.shipmentItem.images',
            'items.shipmentItem.warehouseReceiptItems.labels',
            'items.shipmentItem.warehouseReceiptItems.photos',
            'items.labelScans:id,transport_manifest_item_id,barcode_value,scanned_at',
            'containers.items.manifestItem.shipmentItem.shipment.vendor',
            'containers.items.manifestItem.shipmentItem.warehouseReceiptItems.labels',
            'containers.items.manifestItem.labelScans:id,transport_manifest_item_id,barcode_value,scanned_at',
        ]);

        $config = [
            'scan_receive_endpoint' => route('warehouse.manifests.incoming.items.scan', ['manifest' => $manifest, 'shipmentItem' => '__ITEM__']),
            'finalize_endpoint' => route('warehouse.manifests.incoming.finalize', ['manifest' => $manifest]),
            'index_url' => route('warehouse.manifests.incoming.index'),
        ];

        return view('warehouse.manifests.incoming.show', [
            'warehouse' => $warehouse,
            'manifest' => $manifest,
            'manifestConfig' => $config,
        ]);
    }

    public function scanIncomingPackage(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:120'],
        ]);

        $barcode = trim($validated['barcode']);
        $container = TransportContainer::query()
            ->with('manifest.originWarehouse', 'manifest.destinationWarehouse')
            ->where('container_code', $barcode)
            ->whereHas('manifest', function (Builder $query) use ($warehouse) {
                $query->where('destination_warehouse_id', $warehouse->id)
                    ->where('status', '!=', TransportManifest::STATUS_CANCELLED);
            })
            ->first();

        if ($container && $container->manifest) {
            return response()->json([
                'success' => true,
                'message' => 'Container found. Opening the incoming manifest.',
                'data' => [
                    'type' => 'container',
                    'container' => [
                        'id' => $container->id,
                        'container_code' => $container->container_code,
                        'manifest_id' => $container->manifest->id,
                        'manifest_number' => $container->manifest->manifest_number,
                        'manifest_status' => $container->manifest->status,
                        'redirect_url' => route('warehouse.manifests.incoming.show', $container->manifest)
                            . '?container=' . $container->id,
                    ],
                ],
            ]);
        }

        $label = WarehouseReceiptItemLabel::query()
            ->with('receiptItem:id,shipment_item_id')
            ->where('barcode_value', $barcode)
            ->first();

        $shipmentItemId = $label?->receiptItem?->shipment_item_id;
        if (!$shipmentItemId) {
            $shipmentItemId = ShipmentItem::query()
                ->where('tracking_code', $barcode)
                ->value('id');
        }

        if (!$shipmentItemId) {
            return response()->json([
                'success' => false,
                'message' => 'Package label was not found.',
            ], 404);
        }

        $line = TransportManifestItem::query()
            ->where('shipment_item_id', $shipmentItemId)
            ->whereHas('manifest', function (Builder $query) use ($warehouse) {
                $query->where('destination_warehouse_id', $warehouse->id)
                    ->where('status', '!=', TransportManifest::STATUS_CANCELLED);
            })
            ->with([
                'manifest.originWarehouse',
                'manifest.destinationWarehouse',
                'manifest.assignedDriver',
                'shipmentItem.shipment.vendor',
                'shipmentItem.shipment.pickupAssignment.photos',
                'shipmentItem.images',
                'shipmentItem.warehouseReceiptItems.labels',
                'shipmentItem.warehouseReceiptItems.photos',
                'labelScans:id,transport_manifest_item_id,barcode_value,scanned_at',
                'containerItems.container',
            ])
            ->latest('id')
            ->first();

        if (!$line || !$line->manifest) {
            return response()->json([
                'success' => false,
                'message' => 'This package is not part of an incoming manifest for this warehouse.',
            ], 404);
        }

        $payload = $this->incomingReceiveLinePayload($line);
        if ($label) {
            $alreadyReceived = TransportManifestReceiptLabelScan::query()
                ->where('transport_manifest_id', $line->transport_manifest_id)
                ->where('warehouse_receipt_item_label_id', $label->id)
                ->exists();

            if ($alreadyReceived) {
                return response()->json([
                    'success' => false,
                    'message' => 'This label has already been received.',
                ], 422);
            }

            $receivedLabelCount = TransportManifestReceiptLabelScan::query()
                ->where('transport_manifest_item_id', $line->id)
                ->count();
            $payload['scan_mode'] = 'label';
            $payload['scanned_label_barcode'] = $label->barcode_value;
            $payload['received_quantity'] = min(
                max((int) $line->expected_quantity, 1),
                max((int) $line->received_quantity, $receivedLabelCount) + 1
            );
            $payload['line_status'] = 'received';
            $payload['notes'] = '';
        } else {
            $payload['scan_mode'] = 'line';
            $payload['scanned_label_barcode'] = null;
            $payload['received_quantity'] = (int) $line->expected_quantity;
            $payload['line_status'] = 'received';
            $payload['notes'] = '';
        }

        return response()->json([
            'success' => true,
            'message' => $line->manifest->status === TransportManifest::STATUS_ARRIVED
                ? 'Package found. Confirm the receipt details.'
                : 'Package found, but the manifest has not arrived yet.',
            'data' => [
                'type' => 'package',
                'item' => $payload,
            ],
        ]);
    }

    public function scanIncomingItem(Request $request, TransportManifest $manifest, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'received_quantity' => ['required', 'integer', 'min:0'],
            'line_status' => ['nullable', 'in:pending,loaded,received,short,excess,damaged'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'scanned_label_barcode' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $this->receivingService->scanReceive(
            manifest: $manifest,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['received_quantity'],
            lineStatus: $validated['line_status'] ?? null,
            notes: $validated['notes'] ?? null,
            scannedLabelBarcode: $validated['scanned_label_barcode'] ?? null,
            description: $validated['description'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function incomingReceiveLinePayload(TransportManifestItem $line): array
    {
        $shipmentItem = $line->shipmentItem;
        $shipment = $shipmentItem?->shipment;
        $manifest = $line->manifest;
        $containerItem = $line->containerItems?->first();
        $container = $containerItem?->container;
        $labels = $shipmentItem?->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->labels)
            ->filter()
            ->unique('id')
            ->values() ?? collect();
        $physicalPackageCount = max($labels->count(), 1);
        $loadedPackageCount = $line->labelScans?->count() ?: ((int) $line->loaded_quantity > 0 ? $physicalPackageCount : 0);

        $vendorPhotos = $shipmentItem?->images
            ?->map(fn ($photo) => $this->incomingReceivePhotoPayload($photo, 'Vendor'))
            ->values() ?? collect();
        $driverPhotos = $shipment?->pickupAssignment?->photos
            ?->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $shipmentItem?->id)
            ->map(fn ($photo) => $this->incomingReceivePhotoPayload($photo, 'Rider'))
            ->values() ?? collect();
        $receiptPhotos = $shipmentItem?->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->photos)
            ->map(fn ($photo) => $this->incomingReceivePhotoPayload($photo, 'Receipt'))
            ->values() ?? collect();
        $primaryPhotos = $vendorPhotos->isNotEmpty()
            ? $vendorPhotos
            : ($driverPhotos->isNotEmpty() ? $driverPhotos : $receiptPhotos);

        return [
            'manifest_id' => $manifest?->id,
            'manifest_number' => $manifest?->manifest_number,
            'manifest_status' => $manifest?->status,
            'manifest_status_label' => $manifest ? $this->incomingStatusLabel($manifest) : null,
            'manifest_url' => $manifest ? route('warehouse.manifests.incoming.show', $manifest) : null,
            'receive_endpoint' => $manifest && $shipmentItem
                ? route('warehouse.manifests.incoming.items.scan', ['manifest' => $manifest, 'shipmentItem' => $shipmentItem])
                : null,
            'shipment_item_id' => $line->shipment_item_id,
            'shipment_number' => $shipment?->shipment_number,
            'description' => $shipmentItem?->description,
            'tracking_code' => $shipmentItem?->tracking_code,
            'recipient_name' => $shipmentItem?->delivery_recipient_name ?: $shipment?->delivery_recipient_name,
            'recipient_phone' => $shipmentItem?->delivery_recipient_phone ?: $shipment?->delivery_recipient_phone,
            'container_id' => $container?->id,
            'container_code' => $container?->container_code ?: 'Unassigned',
            'container_url' => ($container && $manifest)
                ? route('warehouse.manifests.incoming.show', $manifest) . '?container=' . $container->id
                : null,
            'container_type' => $container?->container_type ? str($container->container_type)->replace('_', ' ')->title()->toString() : 'No container',
            'container_sequence' => $container?->sequence_number,
            'container_status' => $container?->status,
            'container_item_status' => $containerItem?->status,
            'physical_package_count' => $physicalPackageCount,
            'loaded_package_count' => min($loadedPackageCount, $physicalPackageCount),
            'labels' => $labels->map(fn ($label) => [
                'id' => $label->id,
                'barcode_value' => $label->barcode_value,
                'label_index' => $label->label_index,
                'labels_total' => $label->labels_total,
                'label_type' => $label->label_type,
            ])->values(),
            'expected_quantity' => (int) $line->expected_quantity,
            'loaded_quantity' => (int) $line->loaded_quantity,
            'received_quantity' => (int) $line->received_quantity,
            'line_status' => $line->line_status ?: 'pending',
            'vendor_name' => $shipment?->vendor?->name,
            'photos' => [
                'primary' => $primaryPhotos->values(),
                'primary_source' => $vendorPhotos->isNotEmpty() ? 'Vendor' : ($driverPhotos->isNotEmpty() ? 'Rider' : 'Receipt'),
                'vendor' => $vendorPhotos,
                'driver' => $driverPhotos,
                'receipt' => $receiptPhotos,
                'total' => $vendorPhotos->count() + $driverPhotos->count() + $receiptPhotos->count(),
            ],
            'notes' => $line->notes,
            'loaded_at' => optional($line->loaded_at)?->format('Y-m-d H:i:s'),
            'received_at' => optional($line->received_at)?->format('Y-m-d H:i:s'),
        ];
    }

    private function incomingReceivePhotoPayload($photo, string $source): array
    {
        $storageService = app(\App\Services\StorageService::class);
        $url = $storageService->getUrl($photo->path);

        return [
            'id' => $photo->id,
            'url' => $url,
            'name' => $photo->original_name ?: $source . ' photo',
            'source' => $source,
        ];
    }

    public function finalizeIncoming(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            abort(404);
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

        if ($result['success'] && \App\Models\PlatformSetting::getValue('contact_queue.auto_queue_on_transport_receive', false)) {
            try {
                $itemIds = $manifest->items()->pluck('shipment_item_id')->toArray();
                app(\App\Services\Warehouse\PackageContactService::class)->createTasksForWarehouseItems($warehouse, $itemIds);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-queue on transport receive failed: ' . $e->getMessage());
            }
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function applyOutboundStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'ready' => $query->where('status', TransportManifest::STATUS_DRAFT),
            'on_the_road' => $query->where('status', TransportManifest::STATUS_IN_TRANSIT),
            'completed' => $query->where('status', TransportManifest::STATUS_RECEIVED),
            'needs_driver' => $query
                ->whereNull('assigned_driver_id')
                ->whereNotIn('status', [TransportManifest::STATUS_RECEIVED, TransportManifest::STATUS_CANCELLED]),
            default => $query->where('status', $status),
        };
    }

    private function outboundStatusLabel(?string $status): string
    {
        return match ($status) {
            TransportManifest::STATUS_DRAFT => 'Ready',
            TransportManifest::STATUS_ASSIGNED => 'Assigned',
            TransportManifest::STATUS_LOADING => 'Loading',
            TransportManifest::STATUS_IN_TRANSIT => 'On the Road',
            TransportManifest::STATUS_ARRIVED => 'Arrived',
            TransportManifest::STATUS_RECEIVED => 'Completed',
            TransportManifest::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    }

    private function outboundSummary(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'ready' => (clone $query)->where('status', TransportManifest::STATUS_DRAFT)->count(),
            'assigned' => (clone $query)->where('status', TransportManifest::STATUS_ASSIGNED)->count(),
            'loading' => (clone $query)->where('status', TransportManifest::STATUS_LOADING)->count(),
            'on_the_road' => (clone $query)->where('status', TransportManifest::STATUS_IN_TRANSIT)->count(),
            'arrived' => (clone $query)->where('status', TransportManifest::STATUS_ARRIVED)->count(),
            'completed' => (clone $query)->where('status', TransportManifest::STATUS_RECEIVED)->count(),
            'needs_driver' => (clone $query)
                ->whereNull('assigned_driver_id')
                ->whereNotIn('status', [TransportManifest::STATUS_RECEIVED, TransportManifest::STATUS_CANCELLED])
                ->count(),
        ];
    }

    private function applyIncomingStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'receiving' => $query
                ->whereHas('warehouseReceipt', fn (Builder $receiptQuery) => $receiptQuery->whereNotNull('started_at'))
                ->whereNull('received_at'),
            'pending_receipt' => $query
                ->where('status', TransportManifest::STATUS_ARRIVED)
                ->whereDoesntHave('warehouseReceipt', fn (Builder $receiptQuery) => $receiptQuery->whereNotNull('started_at')),
            default => $query->where('status', $status),
        };
    }

    private function incomingStatusLabel(TransportManifest $manifest): string
    {
        if (
            $manifest->status === TransportManifest::STATUS_ARRIVED
            && !$manifest->received_at
            && $manifest->warehouseReceipt?->started_at
        ) {
            return 'Receiving';
        }

        return match ($manifest->status) {
            TransportManifest::STATUS_IN_TRANSIT => 'In Transit',
            TransportManifest::STATUS_ARRIVED => 'Arrived',
            TransportManifest::STATUS_RECEIVED => 'Received',
            TransportManifest::STATUS_ASSIGNED => 'Assigned',
            TransportManifest::STATUS_LOADING => 'Loading',
            TransportManifest::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $manifest->status)),
        };
    }

    private function incomingSummary(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'in_transit' => (clone $query)->where('status', TransportManifest::STATUS_IN_TRANSIT)->count(),
            'arrived' => (clone $query)->where('status', TransportManifest::STATUS_ARRIVED)->count(),
            'receiving' => (clone $query)
                ->whereHas('warehouseReceipt', fn (Builder $receiptQuery) => $receiptQuery->whereNotNull('started_at'))
                ->whereNull('received_at')
                ->count(),
            'received' => (clone $query)->where('status', TransportManifest::STATUS_RECEIVED)->count(),
            'pending_receipt' => (clone $query)
                ->where('status', TransportManifest::STATUS_ARRIVED)
                ->whereDoesntHave('warehouseReceipt', fn (Builder $receiptQuery) => $receiptQuery->whereNotNull('started_at'))
                ->count(),
        ];
    }

    private function paginate(Builder $query, Request $request, callable $mapper, array $extra = []): JsonResponse
    {
        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get()->map($mapper)->values();

        return response()->json(array_merge([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ], $extra));
    }
}
