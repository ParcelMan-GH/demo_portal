<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Exports\DriversExport;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\Warehouse;
use App\Services\Warehouse\RecipientPaymentService;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseSortingService;
use App\Services\Warehouse\WarehouseTransportService;
use App\Support\GenericPdfExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SortingController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseSortingService $sortingService,
        private WarehouseDeliveryService $deliveryService,
        private WarehouseTransportService $transportService,
        private RecipientPaymentService $recipientPaymentService,
    ) {
    }

    public function index(): View
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $warehouses = $this->portalService->destinationWarehouses()->values();
        $originWarehouses = collect([$warehouse]);
        $dispatchModes = [
            ['value' => SortBatch::DISPATCH_TRANSFER,       'label' => 'Transfer'],
            ['value' => SortBatch::DISPATCH_LOCAL_DELIVERY, 'label' => 'Local Delivery'],
        ];
        $statuses = [
            ['value' => SortBatch::STATUS_OPEN,   'label' => 'Open'],
            ['value' => SortBatch::STATUS_SEALED, 'label' => 'Sealed'],
        ];
        $sortBatchConfig = [
            'dataUrl' => route('warehouse.sorting.batches.data'),
            'exportUrl' => route('warehouse.sorting.batches.export'),
            'storeUrl' => route('warehouse.sorting.batches.store'),
            'showUrlTemplate' => route('warehouse.sorting.show', ['sortBatch' => '__ID__']),
            'deleteUrlTemplate' => route('warehouse.sorting.destroy', ['sortBatch' => '__ID__']),
            'manifestShowUrlTemplate' => route('warehouse.manifests.transport.show', ['manifest' => '__ID__']),
            'deliveryRunShowUrlTemplate' => route('warehouse.deliveries.runs.show', ['run' => '__ID__']),
            'originWarehouseId' => (string) $warehouse->id,
            'warehouseColumnLabel' => 'Destination',
        ];

        return view('admin.sort-batches.index', [
            'layoutName' => 'warehouse.layouts.app',
            'warehouse' => $warehouse,
            'warehouses' => $warehouses,
            'originWarehouses' => $originWarehouses,
            'destinationWarehouses' => $this->portalService->destinationWarehouses()
                ->where('id', '!=', $warehouse->id)
                ->values(),
            'dispatchModes' => $dispatchModes,
            'statuses' => $statuses,
            'sortBatchConfig' => $sortBatchConfig,
        ]);
    }

    public function show(Request $request, SortBatch $sortBatch): View
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseBatch($sortBatch, $warehouse);

        $sortBatch->load([
            'originWarehouse',
            'destinationWarehouse',
            'createdBy',
            'sealedBy',
            'transportManifest.assignedDriver',
            'deliveryRun.assignedDriver',
        ]);
        $sortBatch->loadCount('activeItems');

        $deleteState = $this->sortingService->deleteState($sortBatch);
        $recipientPaymentSummary = $this->recipientPaymentService->summaryForBatch($sortBatch);
        $initialEligibleItems = collect();
        $initialEligibleMeta = [
            'total' => 0,
            'per_page' => 25,
            'current_page' => 1,
            'last_page' => 1,
            'from' => 0,
            'to' => 0,
        ];

        if ($sortBatch->status === SortBatch::STATUS_OPEN) {
            $eligibleQuery = $this->sortingService->eligibleItemsQuery($warehouse)->latest('id');
            $total = $eligibleQuery->count();
            $rows = $eligibleQuery->take(25)->get();
            $initialEligibleItems = $this->sortingService->mapEligibleItems($rows);
            $initialEligibleMeta = [
                'total' => $total,
                'per_page' => 25,
                'current_page' => 1,
                'last_page' => (int) ceil($total / 25) ?: 1,
                'from' => $total > 0 ? 1 : 0,
                'to' => min(25, $total),
            ];
        }
        $sortBatchShowConfig = [
            'indexUrl' => route('warehouse.sorting.index'),
            'itemsDataUrl' => route('warehouse.sorting.items-data', $sortBatch),
            'eligibleItemsUrl' => route('warehouse.sorting.eligible-items', $sortBatch),
            'addItemsUrl' => route('warehouse.sorting.batches.items.store', $sortBatch),
            'removeItemUrlTemplate' => route('warehouse.sorting.batches.items.destroy', ['sortBatch' => $sortBatch, 'shipmentItem' => '__ITEM__']),
            'sealUrl' => route('warehouse.sorting.batches.seal', $sortBatch),
            'reopenUrl' => route('warehouse.sorting.batches.reopen', $sortBatch),
            'deleteBatchUrl' => route('warehouse.sorting.destroy', $sortBatch),
            'createManifestUrl' => route('warehouse.sorting.create-transport-manifest', $sortBatch),
            'createRunUrl' => route('warehouse.sorting.create-delivery-run', $sortBatch),
            'shipmentShowUrlTemplate' => route('admin.shipments.show', '__ID__'),
            'packageShowUrlTemplate' => route('warehouse.packages.show', '__ID__'),
            'manifestShowUrlTemplate' => route('warehouse.manifests.transport.show', '__ID__'),
            'deliveryRunShowUrlTemplate' => route('warehouse.deliveries.runs.show', '__ID__'),
        ];

        return view('admin.sort-batches.show', [
            'layoutName' => 'warehouse.layouts.app',
            'batch' => $sortBatch,
            'deleteState' => $deleteState,
            'recipientPaymentSummary' => $recipientPaymentSummary,
            'sortBatchShowConfig' => $sortBatchShowConfig,
            'initialEligibleItems' => $initialEligibleItems,
            'initialEligibleMeta' => $initialEligibleMeta,
        ]);
    }

    public function batchItemsData(Request $request, SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseBatch($sortBatch, $warehouse);

        $relations = [
            'shipmentItem.shipment.vendor',
            'recipientPaymentTask.assignedTo',
            'recipientPaymentTask.shipmentCharge',
            'addedBy',
        ];

        if (Schema::hasTable('delivery_run_items') && Schema::hasTable('delivery_runs')) {
            $relations[] = 'shipmentItem.deliveryRunItems.run';
        }

        if (Schema::hasTable('transport_manifest_items') && Schema::hasTable('transport_manifests')) {
            $relations[] = 'shipmentItem.transportManifestItems.manifest';
        }

        $query = $sortBatch->activeItems()->with($relations);

        if ($search = $request->get('search')) {
            $query->whereHas('shipmentItem', function ($q) use ($search) {
                $q->where('tracking_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhereHas('shipment', function ($sq) use ($search) {
                        $sq->where('shipment_number', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        $page = max((int) $request->get('page', 1), 1);
        $total = $query->count();
        $items = $query->latest('id')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $items->map(function ($item, $index) use ($sortBatch, $page, $perPage) {
                $shipmentItem = $item->shipmentItem;
                if (!$shipmentItem) {
                    $sortability = ['eligible' => false, 'reason' => 'Package record is missing.'];
                } elseif ($sortBatch->isOpen()) {
                    $sortability = $this->sortingService->sortableWarehouseItemState($shipmentItem, true);
                } else {
                    $sortability = ['eligible' => true, 'reason' => null];
                }

                return [
                    'row_number' => (($page - 1) * $perPage) + $index + 1,
                    'id' => $item->id,
                    'shipment_item_id' => $item->shipment_item_id,
                    'warehouse_receipt_item_id' => $item->warehouse_receipt_item_id,
                    'shipment_id' => $shipmentItem?->shipment?->id,
                    'shipment_number' => $shipmentItem?->shipment?->shipment_number,
                    'vendor_name' => $shipmentItem?->shipment?->vendor?->name,
                    'tracking_code' => $shipmentItem?->tracking_code,
                    'description' => $shipmentItem?->description,
                    'quantity' => $item->quantity_allocated ?? $shipmentItem?->quantity,
                    'delivery_recipient_name' => $shipmentItem?->delivery_recipient_name,
                    'delivery_recipient_phone' => $shipmentItem?->delivery_recipient_phone,
                    'delivery_town' => $shipmentItem?->delivery_town,
                    'added_by' => $item->addedBy?->name,
                    'added_at' => $item->added_at?->format('d M Y, H:i'),
                    'is_sortable' => $sortability['eligible'],
                    'sort_block_reason' => $sortability['reason'],
                    'recipient_payment' => $this->mapRecipientPaymentForBatchItem($item),
                ];
            }),
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

    public function eligibleItemsData(Request $request, SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseBatch($sortBatch, $warehouse);

        $query = $this->sortingService->eligibleItemsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('shipmentItem.shipment', fn (Builder $q) => $q
                    ->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    ->orWhere('delivery_town', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn (Builder $vq) => $vq->where('name', 'like', "%{$search}%")))
                    ->orWhereHas('shipmentItem', fn (Builder $q) => $q
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('tracking_code', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                        ->orWhere('delivery_town', 'like', "%{$search}%")
                    );
            });
        }

        $deliveryMethod = trim((string) $request->input('delivery_method'));
        if (in_array($deliveryMethod, ShipmentItem::DELIVERY_METHODS, true)) {
            $query->whereHas('shipmentItem', function (Builder $q) use ($deliveryMethod) {
                if ($deliveryMethod === ShipmentItem::DELIVERY_METHOD_DIRECT) {
                    $q->where(function (Builder $methodQuery) {
                        $methodQuery->where('delivery_method', ShipmentItem::DELIVERY_METHOD_DIRECT)
                            ->orWhereNull('delivery_method');
                    });
                    return;
                }

                $q->where('delivery_method', $deliveryMethod);
            });
        }

        $query->latest('id');

        return $this->paginate($query, $request, fn ($rows) => $this->sortingService->mapEligibleItems($rows), 20);
    }

    public function itemsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->sortingService->eligibleItemsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('shipmentItem.shipment', fn (Builder $shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', fn (Builder $itemQuery) => $itemQuery
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('tracking_code', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_town', 'like', "%{$search}%")
                    );
            });
        }

        if ($discrepancyType = $request->input('discrepancy_type')) {
            $query->where('discrepancy_type', $discrepancyType);
        }

        $sortBy = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['created_at', 'received_at', 'received_quantity'], true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, fn ($rows) => $this->sortingService->mapEligibleItems($rows));
    }

    public function batchesData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = SortBatch::with([
            'originWarehouse',
            'destinationWarehouse',
            'createdBy',
            'transportManifest',
            'deliveryRun',
        ])->withCount('activeItems')
            ->where('origin_warehouse_id', $warehouse->id);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('destinationWarehouse', fn (Builder $warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dispatchMode = $request->input('dispatch_mode')) {
            $query->where('dispatch_mode', $dispatchMode);
        }

        $sortBy = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['batch_number', 'status', 'dispatch_mode', 'sealed_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest('id');
        }

        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $batches = $query->paginate($perPage);

        return response()->json([
            'data' => $batches->map(function (SortBatch $batch) {
                return [
                    'id'                    => $batch->id,
                    'batch_number'          => $batch->batch_number,
                    'status'                => $batch->status,
                    'dispatch_mode'         => $batch->dispatch_mode,
                    'dispatch_mode_label'   => $batch->dispatch_mode === SortBatch::DISPATCH_TRANSFER ? 'Transfer' : 'Local Delivery',
                    'origin_warehouse'      => $batch->originWarehouse
                        ? ['id' => $batch->originWarehouse->id, 'name' => $batch->originWarehouse->name, 'code' => $batch->originWarehouse->code]
                        : null,
                    'destination_warehouse' => $batch->destinationWarehouse
                        ? ['id' => $batch->destinationWarehouse->id, 'name' => $batch->destinationWarehouse->name, 'code' => $batch->destinationWarehouse->code]
                        : null,
                    'items_count'           => $batch->active_items_count,
                    'created_by_name'       => $batch->createdBy?->name,
                    'sealed_at'             => $batch->sealed_at?->format('Y-m-d H:i:s'),
                    'created_at'            => $batch->created_at?->format('Y-m-d H:i:s'),
                    'has_manifest'          => $batch->transportManifest !== null,
                    'manifest_number'       => $batch->transportManifest?->manifest_number,
                    'manifest_status'       => $batch->transportManifest?->status,
                    'manifest_id'           => $batch->transportManifest?->id,
                    'has_delivery_run'      => $batch->deliveryRun !== null,
                    'run_number'            => $batch->deliveryRun?->run_number,
                    'run_status'            => $batch->deliveryRun?->status,
                    'run_id'                => $batch->deliveryRun?->id,
                    'can_delete'            => $this->sortingService->deleteState($batch)['deletable'],
                ];
            }),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'from'         => $batches->firstItem() ?? 0,
                'to'           => $batches->lastItem() ?? 0,
                'total'        => $batches->total(),
                'last_page'    => $batches->lastPage(),
            ],
        ]);
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'dispatch_mode' => ['required', 'in:transfer,local_delivery'],
            'destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $destinationWarehouse = !empty($validated['destination_warehouse_id'])
            ? Warehouse::query()->findOrFail((int) $validated['destination_warehouse_id'])
            : null;

        $result = $this->sortingService->createBatch(
            originWarehouse: $warehouse,
            destinationWarehouse: $destinationWarehouse,
            user: $user,
            dispatchMode: (string) $validated['dispatch_mode'],
            notes: $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function addItems(Request $request, SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'warehouse_receipt_item_ids' => ['required', 'array', 'min:1'],
            'warehouse_receipt_item_ids.*' => ['integer', 'exists:warehouse_receipt_items,id'],
        ]);

        $result = $this->sortingService->addItems(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: $user,
            warehouseReceiptItemIds: $validated['warehouse_receipt_item_ids']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function removeItem(SortBatch $sortBatch, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $result = $this->sortingService->removeItem(
            batch: $sortBatch,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function seal(SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $result = $this->sortingService->sealBatch(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function reopen(SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.reopen');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $result = $this->sortingService->reopenBatch(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: $user
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function destroy(string $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $sortBatch = SortBatch::query()->find((int) $sortBatch);

        if (!$sortBatch) {
            return response()->json($this->missingSortBatchPayload(), 404);
        }

        $this->ensureWarehouseBatch($sortBatch, $warehouse);

        $result = $this->sortingService->deleteBatch(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function missingSortBatchPayload(): array
    {
        return [
            'success' => false,
            'code' => 'sort_batch_not_found',
            'message' => 'This sort batch was already deleted or is no longer available. The list has been refreshed.',
        ];
    }

    public function createTransportManifest(SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseBatch($sortBatch, $warehouse);

        $result = $this->transportService->createManifest(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function createDeliveryRun(SortBatch $sortBatch): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseBatch($sortBatch, $warehouse);

        $result = $this->deliveryService->createRun(
            batch: $sortBatch,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function export(Request $request)
    {
        $this->authorizePermission('warehouse.sorting.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = SortBatch::with(['originWarehouse', 'destinationWarehouse', 'createdBy'])
            ->withCount('activeItems')
            ->where('origin_warehouse_id', $warehouse->id);

        if ($search = $request->get('search')) {
            $query->where('batch_number', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dispatchMode = $request->get('dispatch_mode')) {
            $query->where('dispatch_mode', $dispatchMode);
        }

        $rows = $query->orderByDesc('created_at')->get()->map(fn (SortBatch $batch) => [
            'Batch #' => $batch->batch_number,
            'From' => $batch->originWarehouse?->name ?? '—',
            'To' => $batch->destinationWarehouse?->name ?? '—',
            'Mode' => $batch->dispatch_mode === SortBatch::DISPATCH_TRANSFER ? 'Transfer' : 'Local Delivery',
            'Items' => $batch->active_items_count,
            'Status' => ucfirst($batch->status),
            'Created By' => $batch->createdBy?->name ?? '—',
            'Sealed At' => $batch->sealed_at?->format('Y-m-d H:i:s') ?? '—',
            'Created At' => $batch->created_at?->format('Y-m-d H:i:s') ?? '—',
        ])->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'warehouse_sort_batches_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'warehouse_sort_batches_' . date('Y-m-d_His') . '.pdf', 'Warehouse Sort Batches');
        }

        return response()->json(['data' => $rows]);
    }

    private function paginate(Builder $query, Request $request, callable $mapper, int $defaultPerPage = 10): JsonResponse
    {
        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', $defaultPerPage), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        return response()->json([
            'data' => $mapper($rows),
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

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ensureWarehouseBatch(SortBatch $sortBatch, Warehouse $warehouse): void
    {
        if ((int) $sortBatch->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }
    }

    private function mapRecipientPaymentForBatchItem($batchItem): array
    {
        $task = $batchItem->recipientPaymentTask;
        $charge = $task?->shipmentCharge;

        if (!$task) {
            return [
                'status' => 'not_queued',
                'label' => 'Not queued',
                'amount' => null,
                'assigned_to' => null,
            ];
        }

        $status = (string) $task->status;
        $label = match ($status) {
            'paid' => 'Paid',
            'waived' => 'Waived',
            'overridden' => 'Override',
            'assigned' => 'Assigned',
            'in_progress' => 'In progress',
            default => $charge ? 'Pending payment' : 'No fee set',
        };

        return [
            'status' => $status,
            'label' => $label,
            'amount' => $charge ? (float) $charge->amount : ($task->negotiated_amount !== null ? (float) $task->negotiated_amount : null),
            'assigned_to' => $task->assignedTo?->name,
        ];
    }
}
