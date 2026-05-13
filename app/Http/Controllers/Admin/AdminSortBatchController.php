<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\Warehouse;
use App\Services\Warehouse\RecipientPaymentService;
use App\Services\Warehouse\WarehouseDeliveryService;
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

class AdminSortBatchController extends Controller
{
    public function __construct(
        private WarehouseSortingService $sortingService,
        private WarehouseDeliveryService $deliveryService,
        private WarehouseTransportService $transportService,
        private RecipientPaymentService $recipientPaymentService,
    ) {
    }

    public function index(): View
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);

        $dispatchModes = [
            ['value' => SortBatch::DISPATCH_TRANSFER,       'label' => 'Transfer'],
            ['value' => SortBatch::DISPATCH_LOCAL_DELIVERY, 'label' => 'Local Delivery'],
        ];

        $statuses = [
            ['value' => SortBatch::STATUS_OPEN,   'label' => 'Open'],
            ['value' => SortBatch::STATUS_SEALED, 'label' => 'Sealed'],
        ];

        return view('admin.sort-batches.index', compact('warehouses', 'dispatchModes', 'statuses'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.sort-batches.create', compact('warehouses'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = SortBatch::with([
            'originWarehouse',
            'destinationWarehouse',
            'createdBy',
            'transportManifest',
            'deliveryRun',
        ])->withCount('activeItems');

        if ($search = $request->get('search')) {
            $query->where('batch_number', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dispatchMode = $request->get('dispatch_mode')) {
            $query->where('dispatch_mode', $dispatchMode);
        }

        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        $sortBy        = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts  = ['batch_number', 'status', 'dispatch_mode', 'sealed_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $batches = $query->paginate($perPage);

        return response()->json([
            'data' => $batches->map(function (SortBatch $batch) {
                return [
                    'id'                     => $batch->id,
                    'batch_number'           => $batch->batch_number,
                    'status'                 => $batch->status,
                    'dispatch_mode'          => $batch->dispatch_mode,
                    'dispatch_mode_label'    => $batch->dispatch_mode === SortBatch::DISPATCH_TRANSFER
                        ? 'Transfer'
                        : 'Local Delivery',
                    'origin_warehouse'       => $batch->originWarehouse
                        ? ['id' => $batch->originWarehouse->id, 'name' => $batch->originWarehouse->name, 'code' => $batch->originWarehouse->code]
                        : null,
                    'destination_warehouse'  => $batch->destinationWarehouse
                        ? ['id' => $batch->destinationWarehouse->id, 'name' => $batch->destinationWarehouse->name, 'code' => $batch->destinationWarehouse->code]
                        : null,
                    'items_count'            => $batch->active_items_count,
                    'created_by_name'        => $batch->createdBy?->name,
                    'sealed_at'              => $batch->sealed_at?->format('Y-m-d H:i:s'),
                    'created_at'             => $batch->created_at->format('Y-m-d H:i:s'),
                    'has_manifest'           => $batch->transportManifest !== null,
                    'manifest_number'        => $batch->transportManifest?->manifest_number,
                    'manifest_status'        => $batch->transportManifest?->status,
                    'manifest_id'            => $batch->transportManifest?->id,
                    'has_delivery_run'       => $batch->deliveryRun !== null,
                    'run_number'             => $batch->deliveryRun?->run_number,
                    'run_status'             => $batch->deliveryRun?->status,
                    'run_id'                 => $batch->deliveryRun?->id,
                    'can_delete'             => $this->sortingService->deleteState($batch)['deletable'],
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

    public function export(Request $request)
    {
        $query = SortBatch::with(['originWarehouse', 'destinationWarehouse', 'createdBy'])
            ->withCount('activeItems');

        if ($search = $request->get('search')) {
            $query->where('batch_number', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dispatchMode = $request->get('dispatch_mode')) {
            $query->where('dispatch_mode', $dispatchMode);
        }

        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        $batches = $query->orderBy('created_at', 'desc')->get();

        $rows = $batches->map(fn(SortBatch $b) => [
            'Batch #'       => $b->batch_number,
            'From'          => $b->originWarehouse?->name ?? '—',
            'To'            => $b->destinationWarehouse?->name ?? '—',
            'Mode'          => $b->dispatch_mode === SortBatch::DISPATCH_TRANSFER ? 'Transfer' : 'Local Delivery',
            'Items'         => $b->active_items_count,
            'Status'        => ucfirst($b->status),
            'Created By'    => $b->createdBy?->name ?? '—',
            'Sealed At'     => $b->sealed_at?->format('Y-m-d H:i:s') ?? '—',
            'Created At'    => $b->created_at->format('Y-m-d H:i:s'),
        ])->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'sort_batches_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'sort_batches_' . date('Y-m-d_His') . '.pdf', 'Sort Batches');
        }

        return response()->json(['data' => $rows]);
    }

    public function show(Request $request, SortBatch $batch): View
    {
        $batch->load([
            'originWarehouse',
            'destinationWarehouse',
            'createdBy',
            'sealedBy',
            'transportManifest.assignedDriver',
            'deliveryRun.assignedDriver',
        ]);

        $batch->loadCount('activeItems');

        $deleteState = $this->sortingService->deleteState($batch);
        $recipientPaymentSummary = $this->recipientPaymentService->summaryForBatch($batch);
        $initialEligibleItems = collect();
        $initialEligibleMeta = [
            'total' => 0,
            'per_page' => 25,
            'current_page' => 1,
            'last_page' => 1,
            'from' => 0,
            'to' => 0,
        ];

        if ($batch->status === SortBatch::STATUS_OPEN) {
            $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
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

        return view('admin.sort-batches.show', compact('batch', 'deleteState', 'recipientPaymentSummary', 'initialEligibleItems', 'initialEligibleMeta'));
    }

    public function itemsData(Request $request, SortBatch $batch): JsonResponse
    {
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

        $query = $batch->activeItems()
            ->with($relations);

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

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page    = max((int) $request->get('page', 1), 1);
        $total   = $query->count();
        $items   = $query->latest('id')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $items->map(function ($item, $index) use ($batch, $page, $perPage) {
                $si = $item->shipmentItem;
                if (!$si) {
                    $sortability = ['eligible' => false, 'reason' => 'Package record is missing.'];
                } elseif ($batch->isOpen()) {
                    $sortability = $this->sortingService->sortableWarehouseItemState($si, true);
                } else {
                    $sortability = ['eligible' => true, 'reason' => null];
                }

                return [
                    'row_number'               => (($page - 1) * $perPage) + $index + 1,
                    'id'                       => $item->id,
                    'shipment_item_id'         => $item->shipment_item_id,
                    'warehouse_receipt_item_id' => $item->warehouse_receipt_item_id,
                    'shipment_id'              => $si?->shipment?->id,
                    'shipment_number'          => $si?->shipment?->shipment_number,
                    'vendor_name'              => $si?->shipment?->vendor?->name,
                    'tracking_code'            => $si?->tracking_code,
                    'description'              => $si?->description,
                    'quantity'                 => $item->quantity_allocated ?? $si?->quantity,
                    'delivery_recipient_name'  => $si?->delivery_recipient_name,
                    'delivery_recipient_phone' => $si?->delivery_recipient_phone,
                    'delivery_town'            => $si?->delivery_town,
                    'added_by'                 => $item->addedBy?->name,
                    'added_at'                 => $item->added_at?->format('d M Y, H:i'),
                    'is_sortable'              => $sortability['eligible'],
                    'sort_block_reason'        => $sortability['reason'],
                    'recipient_payment'        => $this->mapRecipientPaymentForBatchItem($item),
                ];
            }),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max((int) ceil($total / $perPage), 1),
                'from'         => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_warehouse_id'      => ['required', 'integer', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'dispatch_mode'            => ['required', 'in:transfer,local_delivery'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
        ]);

        $originWarehouse      = Warehouse::findOrFail((int) $validated['origin_warehouse_id']);
        $destinationWarehouse = !empty($validated['destination_warehouse_id'])
            ? Warehouse::findOrFail((int) $validated['destination_warehouse_id'])
            : null;

        $user   = Auth::guard('admin')->user();
        $result = $this->sortingService->createBatch(
            originWarehouse:      $originWarehouse,
            destinationWarehouse: $destinationWarehouse,
            user:                 $user,
            dispatchMode:         (string) $validated['dispatch_mode'],
            notes:                $validated['notes'] ?? null,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function eligibleItemsData(Request $request, SortBatch $batch): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $query     = $this->sortingService->eligibleItemsQuery($warehouse);

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

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $page    = max((int) $request->input('page', 1), 1);
        $total   = $query->count();
        $rows    = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $this->sortingService->mapEligibleItems($rows),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage) ?: 1,
                'from'         => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    public function addItems(Request $request, SortBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_receipt_item_ids'   => ['required', 'array', 'min:1'],
            'warehouse_receipt_item_ids.*' => ['integer', 'exists:warehouse_receipt_items,id'],
        ]);

        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->sortingService->addItems(
            batch:                   $batch,
            warehouse:               $warehouse,
            user:                    $user,
            warehouseReceiptItemIds: $validated['warehouse_receipt_item_ids'],
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function removeItem(SortBatch $batch, ShipmentItem $shipmentItem): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->sortingService->removeItem(
            batch:        $batch,
            shipmentItem: $shipmentItem,
            warehouse:    $warehouse,
            user:         $user,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function seal(SortBatch $batch): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->sortingService->sealBatch(
            batch:     $batch,
            warehouse: $warehouse,
            user:      $user,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function reopen(SortBatch $batch): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->sortingService->reopenBatch(
            batch:     $batch,
            warehouse: $warehouse,
            user:      $user,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function destroy(SortBatch $batch): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->sortingService->deleteBatch(
            batch:     $batch,
            warehouse: $warehouse,
            user:      $user,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function createDeliveryRun(SortBatch $batch): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->deliveryService->createRun(
            batch:     $batch,
            warehouse: $warehouse,
            user:      $user,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function createTransportManifest(SortBatch $batch): JsonResponse
    {
        $warehouse = Warehouse::findOrFail((int) $batch->origin_warehouse_id);
        $user      = Auth::guard('admin')->user();

        $result = $this->transportService->createManifest(
            batch:     $batch,
            warehouse: $warehouse,
            user:      $user,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
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
