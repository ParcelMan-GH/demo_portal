<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\SortBatch;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseSortingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeliveryRunController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseDeliveryService $deliveryService,
        private WarehouseSortingService $sortingService
    ) {
    }

    public function index(): View
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        return view('warehouse.deliveries.runs.index', [
            'warehouse' => $warehouse,
            'canResetCodes' => (bool) $user?->hasPermission('warehouse.delivery.code.reset'),
            'deliveryDrivers' => Driver::query()
                ->where('is_active', true)
                ->whereJsonContains('task_capabilities', Driver::CAPABILITY_DELIVERY)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']),
            'localDeliveryBatches' => SortBatch::query()
                ->where('origin_warehouse_id', $warehouse->id)
                ->where('dispatch_mode', SortBatch::DISPATCH_LOCAL_DELIVERY)
                ->where('status', SortBatch::STATUS_SEALED)
                ->whereDoesntHave('deliveryRun')
                ->orderByDesc('id')
                ->get(['id', 'batch_number']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->deliveryService->runsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('run_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('assignedDriver', fn (Builder $driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('stops', fn (Builder $stopQuery) => $stopQuery
                        ->where('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_phone', 'like', "%{$search}%")
                    );
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['run_number', 'status', 'assigned_at', 'dispatched_at', 'completed_at', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, function (DeliveryRun $run) {
            return [
                'id' => $run->id,
                'run_number' => $run->run_number,
                'status' => $run->status,
                'driver_name' => $run->assignedDriver?->name,
                'driver_phone' => $run->assignedDriver?->phone,
                'stops_count' => $run->stops->count(),
                'items_count' => $run->items->count(),
                'assigned_at' => optional($run->assigned_at)?->format('Y-m-d H:i:s'),
                'dispatched_at' => optional($run->dispatched_at)?->format('Y-m-d H:i:s'),
                'completed_at' => optional($run->completed_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.deliveries.runs.show', $run->id),
                'stops' => $run->stops->map(function ($stop) {
                    return [
                        'id' => $stop->id,
                        'recipient_name' => $stop->recipient_name,
                        'recipient_phone' => $stop->recipient_phone,
                        'status' => $stop->status,
                        'total_packages' => (int) $stop->total_packages,
                        'code_sent_at' => optional($stop->verification_code_sent_at)?->format('Y-m-d H:i:s'),
                        'attempts' => (int) $stop->verification_attempts,
                        'max_attempts' => (int) $stop->max_attempts,
                        'verification_skipped' => (bool) $stop->verification_skipped,
                    ];
                })->values(),
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
        ]);

        $batch = SortBatch::query()->findOrFail((int) $validated['sort_batch_id']);
        $result = $this->deliveryService->createRun($batch, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function eligibleItems(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $query = $this->sortingService->eligibleItemsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('shipmentItem.shipment', fn (Builder $q) => $q->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', fn (Builder $q) => $q
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('tracking_code', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_town', 'like', "%{$search}%")
                    );
            });
        }

        $query->latest('id');

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 200);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        return response()->json([
            'data' => $this->sortingService->mapEligibleItems($rows),
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

    public function storeFromItems(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'warehouse_receipt_item_ids' => ['required', 'array', 'min:1'],
            'warehouse_receipt_item_ids.*' => ['integer', 'exists:warehouse_receipt_items,id'],
        ]);

        $result = $this->deliveryService->createRunFromItems(
            warehouse: $warehouse,
            user: $user,
            warehouseReceiptItemIds: $validated['warehouse_receipt_item_ids'],
            sortingService: $this->sortingService,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function assignDriver(Request $request, DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $driver = Driver::query()->findOrFail((int) $validated['driver_id']);
        $result = $this->deliveryService->assignDriver($run, $driver, $warehouse);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function dispatch(DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->deliveryService->dispatch($run, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function show(DeliveryRun $run): View
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $run->load([
            'warehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'stops.region',
            'stops.district',
            'stops.items.shipmentItem.shipment',
            'items.shipmentItem.shipment',
            'items.stop',
        ]);

        $deliveryDrivers = Driver::query()
            ->where('is_active', true)
            ->whereJsonContains('task_capabilities', Driver::CAPABILITY_DELIVERY)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

        $stopsData = $run->stops->map(function ($stop) {
            return [
                'id' => $stop->id,
                'recipient_name' => $stop->recipient_name,
                'recipient_phone' => $stop->recipient_phone,
                'status' => $stop->status,
                'region_name' => $stop->region?->name ?? '',
                'district_name' => $stop->district?->name ?? '',
                'town' => $stop->town ?? '',
                'gh_post_address' => $stop->gh_post_address ?? '',
                'landmark' => $stop->landmark ?? '',
                'code_sent_at' => $stop->verification_code_sent_at?->format('M d, H:i'),
                'attempts' => (int) $stop->verification_attempts,
                'max_attempts' => (int) $stop->max_attempts,
                'arrived_at' => $stop->arrived_at?->format('M d, Y H:i'),
                'delivered_at' => $stop->delivered_at?->format('M d, Y H:i'),
                'failure_reason' => $stop->failure_reason,
                'failure_notes' => $stop->failure_notes,
                'has_proof_photo' => !empty($stop->proof_photo_path),
                'total_packages' => (int) $stop->total_packages,
                'verification_skipped' => (bool) $stop->verification_skipped,
                'verification_skip_reason' => $stop->verification_skip_reason,
                'verification_skipped_at' => $stop->verification_skipped_at?->format('M d, Y H:i'),
                'items_count' => $stop->items->count(),
                'items' => $stop->items->map(fn($item) => [
                    'id' => $item->id,
                    'description' => $item->shipmentItem?->description ?? '-',
                    'shipment_number' => $item->shipmentItem?->shipment?->shipment_number ?? '-',
                    'expected_quantity' => (int) $item->expected_quantity,
                    'delivered_quantity' => (int) $item->delivered_quantity,
                    'status' => $item->status,
                    'notes' => $item->notes ?? '',
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        $itemsData = $run->items->map(function ($item) {
            return [
                'id' => $item->id,
                'description' => $item->shipmentItem?->description ?? '-',
                'tracking_code' => $item->shipmentItem?->tracking_code ?? '',
                'shipment_number' => $item->shipmentItem?->shipment?->shipment_number ?? '-',
                'stop_recipient' => $item->stop?->recipient_name ?? '-',
                'expected_quantity' => (int) $item->expected_quantity,
                'delivered_quantity' => (int) $item->delivered_quantity,
                'status' => $item->status,
                'notes' => $item->notes ?? '',
                'delivered_at' => $item->delivered_at?->format('M d, H:i'),
            ];
        })->values()->toArray();

        $user = Auth::guard('admin')->user();
        $runConfig = [
            'assign_driver_endpoint' => route('warehouse.deliveries.runs.assign-driver', ['run' => $run]),
            'dispatch_endpoint' => route('warehouse.deliveries.runs.dispatch', ['run' => $run]),
            'resend_code_endpoint' => route('warehouse.deliveries.runs.stops.resend-code', ['run' => $run, 'stop' => '__STOP__']),
            'can_reset_codes' => (bool) $user?->hasPermission('warehouse.delivery.code.reset'),
        ];

        return view('warehouse.deliveries.runs.show', [
            'run' => $run,
            'deliveryDrivers' => $deliveryDrivers,
            'stopsData' => $stopsData,
            'itemsData' => $itemsData,
            'runConfig' => $runConfig,
        ]);
    }

    public function resendCode(DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.code.reset');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->deliveryService->resendStopCode($run, $stop, $warehouse);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function updateStopPackages(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'Run not found.'], 404);
        }

        if ((int) $stop->delivery_run_id !== (int) $run->id) {
            return response()->json(['success' => false, 'message' => 'Stop not found.'], 404);
        }

        if (in_array($stop->status, [DeliveryRunStop::STATUS_DELIVERED, 'failed'], true)) {
            return response()->json(['success' => false, 'message' => 'Cannot update packages for a completed stop.'], 422);
        }

        $validated = $request->validate([
            'total_packages' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $stop->update(['total_packages' => (int) $validated['total_packages']]);

        return response()->json([
            'success' => true,
            'message' => 'Package count updated.',
            'data' => ['total_packages' => (int) $stop->total_packages],
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function paginate(Builder $query, Request $request, callable $mapper): JsonResponse
    {
        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get()->map($mapper)->values();

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
}
