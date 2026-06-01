<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\DeliveryDelayReason;
use App\Models\Driver;
use App\Models\SortBatch;
use App\Services\DeliveryDelayService;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseSortingService;
use App\Services\BusHandoffConfirmationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\OtpCode;
use Illuminate\View\View;

class DeliveryRunController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseDeliveryService $deliveryService,
        private WarehouseSortingService $sortingService,
        private BusHandoffConfirmationService $busHandoffConfirmationService,
        private DeliveryDelayService $deliveryDelayService
    ) {
    }

    public function index(): View
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();
        $localDeliveryBatches = SortBatch::query()
            ->where('origin_warehouse_id', $warehouse->id)
            ->where('dispatch_mode', SortBatch::DISPATCH_LOCAL_DELIVERY)
            ->where('status', SortBatch::STATUS_SEALED)
            ->whereDoesntHave('deliveryRun')
            ->orderByDesc('id')
            ->get(['id', 'batch_number']);
        $runStatsQuery = DeliveryRun::query()->where('warehouse_id', $warehouse->id);

        return view('warehouse.deliveries.runs.index', [
            'warehouse' => $warehouse,
            'canResetCodes' => (bool) $user?->hasPermission('warehouse.delivery.code.reset'),
            'runStats' => [
                'total' => (clone $runStatsQuery)->count(),
                'active' => (clone $runStatsQuery)->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'completed' => (clone $runStatsQuery)->where('status', 'completed')->count(),
                'ready_batches' => $localDeliveryBatches->count(),
            ],
            'deliveryDrivers' => Driver::query()
                ->where('is_active', true)
                ->whereJsonContains('task_capabilities', Driver::CAPABILITY_DELIVERY)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']),
            'localDeliveryBatches' => $localDeliveryBatches,
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

        foreach ([
            'created' => 'created_at',
            'assigned' => 'assigned_at',
            'dispatched' => 'dispatched_at',
            'completed' => 'completed_at',
        ] as $prefix => $column) {
            if ($from = $request->input("{$prefix}_date_from")) {
                $query->whereDate($column, '>=', $from);
            }

            if ($to = $request->input("{$prefix}_date_to")) {
                $query->whereDate($column, '<=', $to);
            }
        }

        if ($driverId = $request->input('driver_id')) {
            $query->where('assigned_driver_id', $driverId);
        }

        if ($stopStatus = $request->input('stop_status')) {
            $query->whereHas('stops', fn (Builder $stopQuery) => $stopQuery->where('status', $stopStatus));
        }

        if ($verification = $request->input('verification')) {
            match ($verification) {
                'verified' => $query->whereHas('stops.verificationAttempts', fn (Builder $attemptQuery) => $attemptQuery->where('is_success', true)),
                'skipped' => $query->whereHas('stops', fn (Builder $stopQuery) => $stopQuery->where('verification_skipped', true)),
                'code_sent' => $query->whereHas('stops', fn (Builder $stopQuery) => $stopQuery->whereNotNull('verification_code_sent_at')),
                'no_code' => $query->whereHas('stops', fn (Builder $stopQuery) => $stopQuery->whereNull('verification_code_sent_at')->where('verification_skipped', false)),
                default => null,
            };
        }

        if (($minStops = $request->input('stops_min')) !== null && $minStops !== '') {
            $query->has('stops', '>=', max(0, (int) $minStops));
        }

        if (($maxStops = $request->input('stops_max')) !== null && $maxStops !== '') {
            $query->has('stops', '<=', max(0, (int) $maxStops));
        }

        if (($minItems = $request->input('items_min')) !== null && $minItems !== '') {
            $query->has('items', '>=', max(0, (int) $minItems));
        }

        if (($maxItems = $request->input('items_max')) !== null && $maxItems !== '') {
            $query->has('items', '<=', max(0, (int) $maxItems));
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
            'sort_batch_id' => ['nullable', 'integer', 'exists:sort_batches,id'],
        ]);

        if (!empty($validated['sort_batch_id'])) {
            $batch = SortBatch::query()->findOrFail((int) $validated['sort_batch_id']);
            $result = $this->deliveryService->createRun($batch, $warehouse, Auth::guard('admin')->user());
        } else {
            $result = $this->deliveryService->createDraftRun($warehouse, Auth::guard('admin')->user());
        }

        if ($result['success'] && isset($result['data']['run'])) {
            $result['data']['redirect_url'] = route('warehouse.deliveries.runs.show', $result['data']['run']);
        }

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

    public function eligibleItemsForRun(Request $request, DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'Run not found.'], 404);
        }

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

        $query->latest('id');

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
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

    public function addItems(Request $request, DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'warehouse_receipt_item_ids' => ['required', 'array', 'min:1'],
            'warehouse_receipt_item_ids.*' => ['integer', 'exists:warehouse_receipt_items,id'],
        ]);

        $result = $this->deliveryService->addItemsToDraftRun(
            run: $run,
            warehouse: $warehouse,
            user: $user,
            warehouseReceiptItemIds: $validated['warehouse_receipt_item_ids'],
            sortingService: $this->sortingService,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function attachSortBatch(Request $request, DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
        ]);

        $batch = SortBatch::query()->findOrFail((int) $validated['sort_batch_id']);
        $result = $this->deliveryService->attachSortBatchToDraftRun($run, $batch, $warehouse, $user);

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
            'stops.confirmedBy',
            'stops.items.shipmentItem.shipment',
            'stops.items.shipmentItem.warehouseReceiptItems.photos',
            'stops.items.expectedDeliverySetByDriver',
            'stops.items.expectedDeliverySetByUser',
            'stops.items.delayEvents.actorDriver',
            'stops.items.delayEvents.actorUser',
            'stops.items.delayEvents.reason',
            'stops.verificationAttempts',
            'items.shipmentItem.shipment',
            'items.stop',
            'items.expectedDeliverySetByDriver',
            'items.expectedDeliverySetByUser',
            'items.delayEvents.actorDriver',
            'items.delayEvents.actorUser',
            'items.delayEvents.reason',
        ]);

        $deliveryDrivers = Driver::query()
            ->where('is_active', true)
            ->whereJsonContains('task_capabilities', Driver::CAPABILITY_DELIVERY)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

        $user = Auth::guard('admin')->user();
        $statusLabel = $this->formatStatusLabel($run->status);
        $deliveryRunRoutes = [
            'indexUrl' => route('warehouse.deliveries.runs.index'),
            'sortBatchUrl' => $run->sortBatch ? route('warehouse.sorting.show', $run->sortBatch) : null,
            'assignDriverUrl' => route('warehouse.deliveries.runs.assign-driver', $run),
            'dispatchUrl' => route('warehouse.deliveries.runs.dispatch', $run),
            'eligibleItemsUrl' => route('warehouse.deliveries.runs.run-eligible-items', $run),
            'addItemsUrl' => route('warehouse.deliveries.runs.items.store', $run),
            'attachSortBatchUrl' => route('warehouse.deliveries.runs.attach-sort-batch', $run),
            'resendCodeUrlTemplate' => route('warehouse.deliveries.runs.stops.resend-code', ['run' => $run->id, 'stop' => '__STOP__']),
            'updateStopDeliveryMethodUrlTemplate' => route('warehouse.deliveries.runs.stops.update-delivery-method', ['run' => $run->id, 'stop' => '__STOP__']),
            'confirmHandoffStopUrlTemplate' => route('warehouse.deliveries.runs.stops.confirm-handoff', ['run' => $run->id, 'stop' => '__STOP__']),
            'confirmHandoffItemUrlTemplate' => route('warehouse.deliveries.runs.stops.items.confirm-handoff', ['run' => $run->id, 'stop' => '__STOP__', 'item' => '__ITEM__']),
            'delayNoticeItemUrlTemplate' => route('warehouse.deliveries.runs.items.delay-notice', ['run' => $run->id, 'item' => '__ITEM__']),
            'canResetCodes' => (bool) $user?->hasPermission('warehouse.delivery.code.reset'),
        ];

        $localDeliveryBatches = SortBatch::query()
            ->where('origin_warehouse_id', $warehouse->id)
            ->where('dispatch_mode', SortBatch::DISPATCH_LOCAL_DELIVERY)
            ->where('status', SortBatch::STATUS_SEALED)
            ->where(function (Builder $query) use ($run) {
                $query->whereDoesntHave('deliveryRun')
                    ->orWhereHas('deliveryRun', fn (Builder $runQuery) => $runQuery->whereKey($run->id));
            })
            ->withCount('activeItems')
            ->orderByDesc('sealed_at')
            ->orderByDesc('id')
            ->get(['id', 'batch_number', 'sealed_at'])
            ->map(fn (SortBatch $batch) => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'items_count' => (int) $batch->active_items_count,
                'sealed_at' => optional($batch->sealed_at)?->format('d M Y, h:i A'),
            ])
            ->values();

        return view('admin.delivery-runs.show', [
            'layoutName' => 'warehouse.layouts.app',
            'run' => $run,
            'statusLabel' => $statusLabel,
            'deliveryDrivers' => $deliveryDrivers,
            'deliveryRunRoutes' => $deliveryRunRoutes,
            'hideRunWarehouseMeta' => true,
            'localDeliveryBatches' => $localDeliveryBatches,
            'delayReasons' => $this->deliveryDelayService->activeReasons(),
            'deliveryDelayService' => $this->deliveryDelayService,
        ]);
    }

    public function sendItemDelayNotice(Request $request, DeliveryRun $run, DeliveryRunItem $item): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'Run not found.'], 404);
        }

        if ((int) $item->delivery_run_id !== (int) $run->id) {
            return response()->json(['success' => false, 'message' => 'Package is not part of this delivery run.'], 404);
        }

        $validated = $request->validate([
            'reason_id' => ['required', 'integer', 'exists:delivery_delay_reasons,id'],
            'revised_eta' => ['nullable', 'date', 'after:now'],
            'notify_recipient' => ['nullable', 'boolean'],
            'notify_vendor' => ['nullable', 'boolean'],
            'notify_vendor_sms' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $notifyRecipient = filter_var($validated['notify_recipient'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $notifyVendor = filter_var($validated['notify_vendor'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $notifyVendorSms = filter_var($validated['notify_vendor_sms'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$notifyRecipient && !$notifyVendor && !$notifyVendorSms) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one person to notify.',
            ], 422);
        }

        $reason = DeliveryDelayReason::query()
            ->where('is_active', true)
            ->find((int) $validated['reason_id']);

        if (!$reason) {
            return response()->json([
                'success' => false,
                'message' => 'Select an active delivery delay reason.',
            ], 422);
        }

        $item->loadMissing([
            'run.assignedDriver',
            'stop',
            'shipmentItem.shipment.vendor',
            'busHandoffConfirmation',
            'delayEvents.actorDriver',
            'delayEvents.actorUser',
            'delayEvents.reason',
        ]);

        $delay = $this->deliveryDelayService->snapshot($item);
        if (!($delay['can_notify'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Delay notices can only be sent for active delivery packages.',
            ], 422);
        }

        $this->deliveryDelayService->sendAdminNotice(
            item: $item,
            admin: Auth::guard('admin')->user(),
            reason: $reason,
            revisedEta: !empty($validated['revised_eta']) ? Carbon::parse($validated['revised_eta']) : null,
            notifyRecipient: $notifyRecipient,
            notifyVendor: $notifyVendor,
            notifyVendorSms: $notifyVendorSms,
            message: $validated['message'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $fresh = $item->fresh([
            'run.assignedDriver',
            'stop',
            'shipmentItem.shipment.vendor',
            'busHandoffConfirmation',
            'delayEvents.actorDriver',
            'delayEvents.actorUser',
            'delayEvents.reason',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delay notice recorded.',
            'data' => [
                'eta' => $this->deliveryDelayService->snapshot($fresh),
                'delay_history' => $this->deliveryDelayService->history($fresh),
            ],
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

    public function updateStopDeliveryMethod(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'delivery_method' => ['required', 'string', 'in:direct,bus_handoff'],
        ]);

        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'Run not found.'], 404);
        }

        if ($stop->delivery_run_id !== $run->id) {
            return response()->json(['success' => false, 'message' => 'Stop not found.'], 404);
        }

        if (in_array($stop->status, ['delivered', 'handed_off'])) {
            return response()->json(['success' => false, 'message' => 'Cannot change delivery method for completed stops.'], 400);
        }

        $stop->update(['delivery_method' => $validated['delivery_method']]);

        return response()->json(['success' => true, 'message' => 'Delivery method updated.', 'delivery_method' => $stop->delivery_method]);
    }

    public function confirmHandoffItem(Request $request, DeliveryRun $run, DeliveryRunStop $stop, DeliveryRunItem $item): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'action' => ['required', 'in:delivered,failed,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'Run not found.'], 404);
        }

        if ((int) $stop->delivery_run_id !== (int) $run->id || (int) $item->delivery_run_stop_id !== (int) $stop->id) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $admin = Auth::guard('admin')->user();
        $notes = $validated['notes'] ?? null;
        $result = $this->busHandoffConfirmationService->adminResolveItem($item, $admin, $validated['action'], $notes);

        $allResolved = DeliveryRunItem::query()
            ->where('delivery_run_stop_id', $stop->id)
            ->get()
            ->every(fn (DeliveryRunItem $runItem) => in_array($runItem->status, [DeliveryRunItem::STATUS_DELIVERED, DeliveryRunItem::STATUS_FAILED], true));

        $recipientName = $item->shipmentItem?->delivery_recipient_name ?? 'Package';

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? ($validated['action'] === 'delivered'
                ? "{$recipientName} confirmed as delivered."
                : "{$recipientName} marked as failed."),
            'all_resolved' => $allResolved,
            'run_status' => $run->fresh()->status,
        ]);
    }

    public function adminConfirmHandoff(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');

        $validated = $request->validate([
            'action' => ['required', 'in:delivered,failed'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($stop->delivery_run_id !== $run->id) {
            return response()->json(['success' => false, 'message' => 'Stop not found.'], 404);
        }

        if (! in_array($stop->status, [
            DeliveryRunStop::STATUS_HANDED_OFF,
            DeliveryRunStop::STATUS_DELIVERED,
            DeliveryRunStop::STATUS_FAILED,
        ], true)) {
            return response()->json(['success' => false, 'message' => 'This stop has not been handed off yet.'], 400);
        }

        $admin = Auth::guard('admin')->user();
        $notes = $validated['notes'] ?? null;
        $runItems = DeliveryRunItem::query()->where('delivery_run_stop_id', $stop->id)->get();

        foreach ($runItems as $runItem) {
            $this->busHandoffConfirmationService->adminResolveItem($runItem, $admin, $validated['action'], $notes);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['action'] === 'pending'
                ? 'Handoff returned to pending confirmation.'
                : ($validated['action'] === 'delivered' ? 'Delivery confirmed.' : 'Stop marked as failed.'),
        ]);
    }

    public function pendingConfirmations(): View
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.deliveries.pending-confirmations', compact('warehouse'));
    }

    public function pendingConfirmationsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $query = DeliveryRunStop::where('status', DeliveryRunStop::STATUS_HANDED_OFF)
            ->where('delivery_method', DeliveryRunStop::METHOD_BUS_HANDOFF)
            ->whereHas('run', fn ($q) => $q->where('warehouse_id', $warehouse->id));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('handoff_courier_name', 'like', "%{$search}%")
                  ->orWhere('handoff_vehicle_number', 'like', "%{$search}%")
                  ->orWhere('bus_station_name', 'like', "%{$search}%")
                  ->orWhereHas('items.shipmentItem', fn ($itemQuery) => $itemQuery
                      ->where('tracking_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%"));
            });
        }

        if ($confirmationStatus = $request->get('confirmation_status')) {
            $query->whereHas('busHandoffConfirmations', fn ($q) => $q->where('status', $confirmationStatus));
        }

        if ($confirmationSource = $request->get('confirmation_source')) {
            $query->whereHas('busHandoffConfirmations', fn ($q) => $q->where('source', $confirmationSource));
        }

        if ($followup = $request->get('followup')) {
            if ($followup === 'needs_followup') {
                $query->whereNotNull('handoff_at')->where('handoff_at', '<=', now()->subHours(24));
            } elseif ($followup === 'recent') {
                $query->where(function ($q) {
                    $q->whereNull('handoff_at')->orWhere('handoff_at', '>', now()->subHours(24));
                });
            }
        }

        if ($from = $request->get('handoff_date_from')) {
            $query->whereDate('handoff_at', '>=', $from);
        }

        if ($to = $request->get('handoff_date_to')) {
            $query->whereDate('handoff_at', '<=', $to);
        }

        if (($minPackages = $request->get('packages_min')) !== null && $minPackages !== '') {
            $query->where('total_packages', '>=', max(0, (int) $minPackages));
        }

        if (($maxPackages = $request->get('packages_max')) !== null && $maxPackages !== '') {
            $query->where('total_packages', '<=', max(0, (int) $maxPackages));
        }

        $summaryQuery = clone $query;
        $total = $query->count();
        $needsFollowup = (clone $summaryQuery)
            ->whereNotNull('handoff_at')
            ->where('handoff_at', '<=', now()->subHours(24))
            ->count();
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $stops = $query->with(['run', 'region', 'district', 'busHandoffConfirmations.reason'])
            ->latest('handoff_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $now = now();

        return response()->json([
            'data' => $stops->map(function ($stop) use ($now) {
                $confirmations = $stop->busHandoffConfirmations;

                return [
                'id' => $stop->id,
                'run_id' => $stop->delivery_run_id,
                'run_number' => $stop->run?->run_number,
                'run_url' => $stop->run ? route('warehouse.deliveries.runs.show', $stop->run) : null,
                'recipient_name' => $stop->recipient_name,
                'recipient_phone' => $stop->recipient_phone,
                'town' => $stop->town,
                'destination_town' => $stop->town,
                'region' => $stop->region?->name,
                'district' => $stop->district?->name,
                'destination_district' => $stop->district?->name,
                'courier_name' => $stop->handoff_courier_name,
                'courier_phone' => $stop->handoff_courier_phone,
                'vehicle_number' => $stop->handoff_vehicle_number,
                'handoff_at' => $stop->handoff_at?->format('d M Y, h:i A'),
                'handed_off_at' => $stop->handoff_at?->format('d M Y, h:i A'),
                'hours_since_handoff' => $stop->handoff_at ? round($stop->handoff_at->diffInHours($now), 1) : null,
                'hours_ago' => $stop->handoff_at ? round($stop->handoff_at->diffInHours($now), 1) : null,
                'needs_followup' => $stop->handoff_at && $stop->handoff_at->diffInHours($now) >= 24,
                'total_packages' => (int) $stop->total_packages,
                'packages_count' => (int) $stop->total_packages,
                'confirmation_summary' => [
                    'pending' => $confirmations->whereIn('status', ['pending', 'code_sent'])->count(),
                    'issues' => $confirmations->where('status', 'issue_reported')->count(),
                    'confirmed' => $confirmations->whereIn('status', ['confirmed', 'admin_confirmed'])->count(),
                    'failed' => $confirmations->where('status', 'failed')->count(),
                    'latest_status' => $confirmations->sortByDesc('updated_at')->first()?->status,
                    'latest_source' => $confirmations->sortByDesc('updated_at')->first()?->source,
                    'latest_reason' => $confirmations->sortByDesc('updated_at')->first()?->reason?->label,
                ],
            ];
            })->values(),
            'meta' => [
                'total' => $total,
                'needs_followup' => $needsFollowup,
                'pending_recent' => max($total - $needsFollowup, 0),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total),
            ],
        ]);
    }

    private function getStopOtpCode(DeliveryRunStop $stop): ?string
    {
        if ($stop->status === 'delivered' || !$stop->verification_code_sent_at) {
            return null;
        }

        return OtpCode::where('phone', (string) $stop->recipient_phone)
            ->where('purpose', 'delivery_verification')
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->value('code');
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            DeliveryRun::STATUS_DRAFT => 'Draft',
            DeliveryRun::STATUS_ASSIGNED => 'Assigned',
            DeliveryRun::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            DeliveryRun::STATUS_PARTIALLY_DELIVERED => 'Partially Delivered',
            DeliveryRun::STATUS_COMPLETED => 'Completed',
            DeliveryRun::STATUS_CANCELLED => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    private function refreshRunStatusAfterStopResolution(DeliveryRun $run): void
    {
        if ($run->status === DeliveryRun::STATUS_CANCELLED) {
            return;
        }

        $run->unsetRelation('stops');
        $run->load('stops');

        $totalStops = $run->stops->count();
        if ($totalStops === 0) {
            return;
        }

        $completedStops = $run->stops
            ->whereIn('status', [
                DeliveryRunStop::STATUS_DELIVERED,
                DeliveryRunStop::STATUS_FAILED,
            ])
            ->count();

        if ($completedStops === $totalStops) {
            $run->update([
                'status' => DeliveryRun::STATUS_COMPLETED,
                'completed_at' => $run->completed_at ?? now(),
            ]);

            return;
        }

        if ($completedStops > 0) {
            $run->update([
                'status' => DeliveryRun::STATUS_PARTIALLY_DELIVERED,
                'completed_at' => null,
            ]);

            return;
        }

        $run->update([
            'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
            'completed_at' => null,
        ]);
    }
}
