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
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhereIn('status', ['available', 'offline']);
                })
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
                'stops' => $run->stops->map(function ($stop) {
                    return [
                        'id' => $stop->id,
                        'recipient_name' => $stop->recipient_name,
                        'recipient_phone' => $stop->recipient_phone,
                        'status' => $stop->status,
                        'code_sent_at' => optional($stop->verification_code_sent_at)?->format('Y-m-d H:i:s'),
                        'attempts' => (int) $stop->verification_attempts,
                        'max_attempts' => (int) $stop->max_attempts,
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

    public function resendCode(DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.code.reset');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->deliveryService->resendStopCode($run, $stop, $warehouse);

        return response()->json($result, $result['success'] ? 200 : 422);
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
