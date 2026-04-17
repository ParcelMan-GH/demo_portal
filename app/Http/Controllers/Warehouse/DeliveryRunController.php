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
use App\Models\OtpCode;
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
                'verification_code' => $this->getStopOtpCode($stop),
                'attempts' => (int) $stop->verification_attempts,
                'max_attempts' => (int) $stop->max_attempts,
                'arrived_at' => $stop->arrived_at?->format('M d, Y H:i'),
                'delivered_at' => $stop->delivered_at?->format('M d, Y H:i'),
                'failure_reason' => $stop->failure_reason,
                'failure_notes' => $stop->failure_notes,
                'delivery_notes' => $stop->delivery_notes,
                'has_proof_photo' => !empty($stop->proof_photo_path),
                'total_packages' => (int) $stop->total_packages,
                'verification_skipped' => (bool) $stop->verification_skipped,
                'verification_skip_reason' => $stop->verification_skip_reason,
                'verification_skipped_at' => $stop->verification_skipped_at?->format('M d, Y H:i'),
                'delivery_method' => $stop->delivery_method ?? 'direct',
                'handoff' => $stop->delivery_method === 'bus_handoff' ? [
                    'courier_name' => $stop->handoff_courier_name,
                    'courier_phone' => $stop->handoff_courier_phone,
                    'vehicle_number' => $stop->handoff_vehicle_number,
                    'handed_off_at' => $stop->handoff_at?->format('M d, Y H:i'),
                ] : null,
                'confirmed_at' => $stop->confirmed_at?->format('M d, Y H:i'),
                'confirmation_notes' => $stop->confirmation_notes,
                'items_count' => $stop->items->count(),
                'items' => $stop->items->map(fn($item) => [
                    'id' => $item->id,
                    'description' => $item->shipmentItem?->description ?? '-',
                    'shipment_number' => $item->shipmentItem?->shipment?->shipment_number ?? '-',
                    'fulfillment_type' => $item->shipmentItem?->fulfillment_type?->value ?? $item->shipmentItem?->shipment?->fulfillment_type?->value ?? 'warehouse',
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
                'fulfillment_type' => $item->shipmentItem?->fulfillment_type?->value ?? $item->shipmentItem?->shipment?->fulfillment_type?->value ?? 'warehouse',
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

    public function updateStopDeliveryMethod(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('warehouse.delivery.assign');

        $validated = $request->validate([
            'delivery_method' => ['required', 'string', 'in:direct,bus_handoff'],
        ]);

        if ($stop->delivery_run_id !== $run->id) {
            return response()->json(['success' => false, 'message' => 'Stop not found.'], 404);
        }

        if (in_array($stop->status, ['delivered', 'handed_off'])) {
            return response()->json(['success' => false, 'message' => 'Cannot change delivery method for completed stops.'], 400);
        }

        $stop->update(['delivery_method' => $validated['delivery_method']]);

        return response()->json(['success' => true, 'message' => 'Delivery method updated.', 'delivery_method' => $stop->delivery_method]);
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

        if ($stop->status !== DeliveryRunStop::STATUS_HANDED_OFF) {
            return response()->json(['success' => false, 'message' => 'This stop has not been handed off yet.'], 400);
        }

        $admin = Auth::guard('admin')->user();
        $now = now();

        if ($validated['action'] === 'delivered') {
            $stop->update([
                'status' => DeliveryRunStop::STATUS_DELIVERED,
                'confirmed_by_admin_id' => $admin->id,
                'confirmed_at' => $now,
                'confirmation_notes' => $validated['notes'],
            ]);

            $runItems = \App\Models\DeliveryRunItem::where('delivery_run_stop_id', $stop->id)->with('shipmentItem.shipment')->get();
            foreach ($runItems as $runItem) {
                if ($runItem->shipmentItem) {
                    $runItem->shipmentItem->update(['status' => \App\Enums\ItemStatus::DELIVERED]);

                    \App\Models\ShipmentItemTracking::create([
                        'shipment_item_id' => $runItem->shipmentItem->id,
                        'status' => 'delivered',
                        'location' => $stop->town ?: $stop->landmark,
                        'notes' => 'Delivery confirmed by admin via phone call. ' . ($validated['notes'] ?? ''),
                        'meta' => ['confirmed_by' => $admin->name, 'confirmed_at' => $now->toIso8601String()],
                        'created_at' => $now,
                    ]);

                    if ($runItem->shipmentItem->shipment) {
                        $allDelivered = $runItem->shipmentItem->shipment->items()
                            ->where('status', '!=', \App\Enums\ItemStatus::DELIVERED->value)
                            ->doesntExist();
                        if ($allDelivered) {
                            $runItem->shipmentItem->shipment->update(['status' => \App\Enums\ShipmentStatus::DELIVERED]);
                        }
                    }
                }
            }

            app(\App\Services\VendorCommissionService::class)->createEarningsForStop($stop);

            return response()->json(['success' => true, 'message' => 'Delivery confirmed. Recipient verified receipt via phone call.']);
        }

        $stop->update([
            'status' => DeliveryRunStop::STATUS_FAILED,
            'confirmed_by_admin_id' => $admin->id,
            'confirmed_at' => $now,
            'confirmation_notes' => $validated['notes'],
            'failure_reason' => 'not_received_by_recipient',
            'failure_notes' => 'Admin confirmed via phone call that recipient did not receive the package. ' . ($validated['notes'] ?? ''),
        ]);

        $runItems = \App\Models\DeliveryRunItem::where('delivery_run_stop_id', $stop->id)->with('shipmentItem')->get();
        foreach ($runItems as $runItem) {
            $runItem->update(['status' => \App\Models\DeliveryRunItem::STATUS_FAILED]);
            if ($runItem->shipmentItem) {
                $runItem->shipmentItem->update(['status' => \App\Enums\ItemStatus::AT_DESTINATION]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Stop marked as failed. Recipient did not receive the package.']);
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
                  ->orWhere('handoff_vehicle_number', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $stops = $query->with(['run', 'region', 'district'])
            ->latest('handoff_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $now = now();

        return response()->json([
            'data' => $stops->map(fn ($stop) => [
                'id' => $stop->id,
                'run_id' => $stop->delivery_run_id,
                'run_number' => $stop->run?->run_number,
                'recipient_name' => $stop->recipient_name,
                'recipient_phone' => $stop->recipient_phone,
                'town' => $stop->town,
                'region' => $stop->region?->name,
                'district' => $stop->district?->name,
                'courier_name' => $stop->handoff_courier_name,
                'courier_phone' => $stop->handoff_courier_phone,
                'vehicle_number' => $stop->handoff_vehicle_number,
                'handoff_at' => $stop->handoff_at?->format('M d, Y H:i'),
                'hours_since_handoff' => $stop->handoff_at ? round($stop->handoff_at->diffInHours($now), 1) : null,
                'needs_followup' => $stop->handoff_at && $stop->handoff_at->diffInHours($now) >= 24,
                'total_packages' => (int) $stop->total_packages,
            ])->values(),
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
}
