<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PackageContactAttempt;
use App\Models\PackageContactTask;
use App\Models\ShipmentCollection;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Services\ShipmentCollectionService;
use App\Services\StorageService;
use App\Services\Warehouse\PackageContactService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactQueueController extends Controller
{
    public function __construct(
        private PackageContactService $contactService,
        private ShipmentCollectionService $collectionService,
        private WarehousePortalService $portalService,
    ) {}

    public function index(): View
    {
        $this->authorizePermission('warehouse.contacts.manage');
        $admin = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($admin);
        $stats = $this->contactService->getWarehouseStats($warehouse);

        $workers = User::where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_warehouse_role', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('warehouse.contacts.index', compact('warehouse', 'stats', 'workers'));
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');
        $admin = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($admin);

        $collectionItemIds = ShipmentCollection::where('warehouse_id', $warehouse->id)
            ->whereIn('status', [ShipmentCollection::STATUS_READY, ShipmentCollection::STATUS_COLLECTED])
            ->whereHas('shipment.items')
            ->with('shipment.items:id,shipment_id')
            ->get()
            ->flatMap(fn (ShipmentCollection $collection) => $collection->shipment?->items?->pluck('id') ?? collect())
            ->values()
            ->all();

        if ($collectionItemIds) {
            $this->contactService->createTasksForWarehouseItems($warehouse, $collectionItemIds);
        }

        $query = PackageContactTask::where('warehouse_id', $warehouse->id)
            ->with([
                'assignedTo:id,name',
                'resolvedBy:id,name',
                'shipmentItem:id,shipment_id,tracking_code,description,quantity,status',
                'shipmentItem.deliveryRunItems:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,status,delivered_at',
                'shipmentItem.deliveryRunItems.run:id,run_number,assigned_driver_id',
                'shipmentItem.deliveryRunItems.run.assignedDriver:id,name,phone',
                'shipmentItem.deliveryRunItems.stop:id,delivery_run_id,status,delivery_method,delivered_at,confirmed_at,confirmed_by_admin_id,bus_station_name',
                'shipmentItem.deliveryRunItems.stop.confirmedBy:id,name',
                'shipmentItem.deliveryRunItems.busHandoffConfirmation:id,delivery_run_item_id,status,source,target_type,target_name,target_phone,confirmed_at,confirmed_by_driver_id,confirmed_by_admin_id,public_confirmed_at',
                'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByDriver:id,name,phone',
                'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByAdmin:id,name',
                'shipment:id,shipment_number,fulfillment_type,delivery_recipient_name,delivery_recipient_phone',
                'shipment.collection:id,shipment_id,warehouse_id,status,ready_at,collected_at,collected_by_name,collected_by_phone,handed_over_by_user_id',
                'shipment.collection.handedOverBy:id,name',
                'shipment.items.images',
                'shipment.items.warehouseReceiptItems.photos',
                'shipment.pickupAssignment.photos',
            ]);

        if ($status = $request->get('status')) {
            if ($status === 'callbacks_due') {
                $query->where('outcome', PackageContactTask::OUTCOME_CALLBACK)
                    ->where('callback_at', '<=', now());
            } else {
                $query->where('status', $status);
            }
        }

        if ($worker = $request->get('worker_id')) {
            $query->where('assigned_to_user_id', $worker);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_town', 'like', "%{$search}%")
                  ->orWhereHas('shipmentItem', fn ($sq) => $sq->where('tracking_code', 'like', "%{$search}%"))
                  ->orWhereHas('shipment', fn ($sq) => $sq->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        $total = $query->count();
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $tasks = $query->latest('created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'data' => $tasks->map(function ($task) {
                $task = $this->contactService->syncWithPackageState($task);
                $task->loadMissing([
                    'assignedTo:id,name',
                    'resolvedBy:id,name',
                    'shipmentItem.deliveryRunItems.run.assignedDriver:id,name,phone',
                    'shipmentItem.deliveryRunItems.stop.confirmedBy:id,name',
                    'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByDriver:id,name,phone',
                    'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByAdmin:id,name',
                ]);
                $itemStatus = $task->shipmentItem?->status;
                $deliveryMarker = $this->contactService->deliveryMarkerFor($task);

                return [
                'id' => $task->id,
                'shipment_number' => $task->shipment?->shipment_number,
                'tracking_code' => $task->shipmentItem?->tracking_code,
                'description' => $task->shipmentItem?->description,
                'item_name' => $task->shipmentItem?->description,
                'recipient_name' => $task->recipient_name,
                'recipient_phone' => $task->recipient_phone,
                'delivery_town' => $task->delivery_town,
                'status' => $task->status,
                'outcome' => $task->outcome,
                'package_status' => $itemStatus?->value ?? $task->shipmentItem?->getRawOriginal('status'),
                'package_status_label' => ($itemStatus && method_exists($itemStatus, 'label')) ? $itemStatus->label() : str($task->shipmentItem?->getRawOriginal('status') ?? '')->headline()->toString(),
                'is_package_delivered' => $this->contactService->isPackageDelivered($task),
                'collection' => $this->collectionPayload($task),
                'can_handover' => $this->canHandOverCollection($task),
                'packages' => $task->shipment?->items?->map(fn (ShipmentItem $item) => $this->packagePayload($item, $task->shipment))?->values() ?? collect(),
                'assigned_to' => $task->assignedTo?->name,
                'assigned_to_id' => $task->assigned_to_user_id,
                'assigned_at' => $task->assigned_at?->format('M d, H:i'),
                'resolved_by' => $task->resolvedBy?->name,
                'resolved_by_id' => $task->resolved_by_user_id,
                'callback_at' => $task->callback_at?->format('M d, H:i'),
                'is_callback_due' => $task->outcome === PackageContactTask::OUTCOME_CALLBACK
                    && $task->callback_at?->lte(now()),
                'attempts_count' => $task->attempts_count,
                'resolved_at' => $task->resolved_at?->format('M d, H:i'),
                'delivered_by' => $deliveryMarker,
                'delivered_by_type' => $deliveryMarker['type'] ?? null,
                'delivered_by_name' => $deliveryMarker['name'] ?? null,
                'delivered_by_at' => $deliveryMarker['at'] ?? null,
                'notes' => $task->notes,
                'created_at' => $task->created_at?->format('M d, H:i'),
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
            'stats' => $this->contactService->getWarehouseStats($warehouse),
        ]);
    }

    public function assign(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $worker = User::findOrFail($validated['user_id']);
        $this->contactService->assignToWorker($task, $worker);

        return response()->json(['success' => true, 'message' => "Assigned to {$worker->name}."]);
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');

        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:package_contact_tasks,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $worker = User::findOrFail($validated['user_id']);
        $tasks = PackageContactTask::whereIn('id', $validated['task_ids'])->get();

        foreach ($tasks as $task) {
            $this->contactService->assignToWorker($task, $worker);
        }

        return response()->json(['success' => true, 'message' => count($validated['task_ids']) . " tasks assigned to {$worker->name}."]);
    }

    public function autoAssign(): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');
        $admin = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($admin);

        $eligibleWorkers = User::where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_warehouse_role', true))
            ->get()
            ->filter(fn ($user) => $user->hasPermission('warehouse.contacts.manage'))
            ->count();

        if ($eligibleWorkers === 0) {
            return response()->json([
                'success' => false,
                'reason' => 'no_workers',
                'message' => 'No eligible warehouse contact workers found. Add an active warehouse user with contact queue permission.',
            ]);
        }

        $pendingTasks = PackageContactTask::where('warehouse_id', $warehouse->id)
            ->where('status', PackageContactTask::STATUS_PENDING)
            ->whereNull('assigned_to_user_id')
            ->count();

        if ($pendingTasks === 0) {
            return response()->json([
                'success' => false,
                'reason' => 'no_pending_tasks',
                'message' => "There are no pending unassigned contact tasks for {$warehouse->name}.",
            ]);
        }

        $count = $this->contactService->autoAssignRoundRobin($warehouse);

        if ($count === 0) {
            return response()->json(['success' => false, 'reason' => 'not_assigned', 'message' => 'No tasks were assigned. Refresh and try again.']);
        }

        return response()->json(['success' => true, 'message' => "{$count} tasks auto-assigned to workers."]);
    }

    public function logCall(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');

        $validated = $request->validate([
            'call_outcome' => ['required', 'in:answered,no_answer,busy,wrong_number,voicemail'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $task = $this->contactService->syncWithPackageState($task);
        if ($this->contactService->isPackageDelivered($task)) {
            return response()->json([
                'success' => false,
                'message' => 'This package is already delivered, so the recipient call has been closed.',
            ], 422);
        }

        $admin = Auth::guard('admin')->user();
        $this->contactService->logAttempt($task, $admin, $validated['call_outcome'], $validated['notes']);

        return response()->json(['success' => true, 'message' => 'Call attempt logged.', 'attempts_count' => $task->fresh()->attempts_count]);
    }

    public function handover(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $task->loadMissing('shipment.collection');
        $shipment = $task->shipment;
        $collection = $shipment?->collection;

        if (!$shipment || !$collection || (int) $collection->warehouse_id !== (int) $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'This package is not ready for warehouse handover.'], 404);
        }

        if ($collection->isCollected()) {
            return response()->json(['success' => false, 'message' => 'This shipment has already been handed over.'], 400);
        }

        $validated = $request->validate([
            'collected_by_name'      => 'required|string|max:255',
            'collected_by_phone'     => 'required|string|max:20',
            'collected_by_id_type'   => 'nullable|string|max:50',
            'collected_by_id_number' => 'nullable|string|max:100',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $this->collectionService->recordHandover($shipment, $user, $validated);
        $this->contactService->syncWithPackageState($task->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Package handed over successfully.',
        ]);
    }

    public function resolve(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');

        $validated = $request->validate([
            'outcome' => ['required', 'in:deliver,self_pickup,unreachable,wrong_number,callback'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'callback_at' => ['nullable', 'required_if:outcome,callback', 'date', 'after:now'],
            'confirmation_code' => ['nullable', 'string', 'max:10'],
        ]);

        $callbackAt = !empty($validated['callback_at']) ? new \DateTime($validated['callback_at']) : null;

        $result = $this->contactService->resolveTask(
            $task,
            $validated['outcome'],
            $validated['notes'] ?? null,
            $callbackAt,
            $validated['confirmation_code'] ?? null,
            Auth::guard('admin')->user(),
        );

        if (!($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $labels = [
            'deliver' => 'Marked for delivery.',
            'self_pickup' => 'Marked for self-pickup. Fulfillment type updated.',
            'unreachable' => 'Marked as unreachable.',
            'wrong_number' => 'Marked as wrong number.',
            'callback' => 'Callback scheduled.',
        ];

        return response()->json(['success' => true, 'message' => $labels[$validated['outcome']] ?? 'Task resolved.']);
    }

    public function sendCode(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');
        $result = $this->contactService->sendConfirmationCode($task);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function attempts(PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');

        $attempts = $task->attempts()->with('attemptedBy:id,name')->get();

        return response()->json([
            'data' => $attempts->map(fn ($a) => [
                'id' => $a->id,
                'outcome' => $a->outcome,
                'notes' => $a->notes,
                'attempted_by' => $a->attemptedBy?->name,
                'attempted_at' => $a->attempted_at?->format('M d, Y H:i'),
            ])->values(),
        ]);
    }

    private function canHandOverCollection(PackageContactTask $task): bool
    {
        $collection = $task->shipment?->collection;

        return $collection
            && !$collection->isCollected()
            && !$this->contactService->isPackageDelivered($task);
    }

    private function collectionPayload(PackageContactTask $task): ?array
    {
        $collection = $task->shipment?->collection;
        if (!$collection) {
            return null;
        }

        return [
            'id' => $collection->id,
            'status' => $collection->status,
            'ready_at' => $collection->ready_at?->format('d M Y, h:i A'),
            'collected_at' => $collection->collected_at?->format('d M Y, h:i A'),
            'collected_by_name' => $collection->collected_by_name,
            'collected_by_phone' => $collection->collected_by_phone,
            'handed_over_by' => $collection->handedOverBy?->name,
        ];
    }

    private function packagePayload(ShipmentItem $item, $shipment = null): array
    {
        $vendorPhotos = $item->images
            ?->map(fn ($image) => $image->getSignedUrl() + ['source' => 'Vendor'])
            ->values() ?? collect();

        $pickupPhotos = $shipment?->pickupAssignment?->photos
            ?->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $item->id)
            ->map(fn ($photo) => $this->photoPayload($photo, 'Pickup photo', 'Pickup'))
            ->values() ?? collect();

        $receiptPhotos = $item->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->photos ?? collect())
            ->map(fn ($photo) => $this->photoPayload($photo, 'Receipt photo', 'Receipt'))
            ->values() ?? collect();

        $primaryPhotos = $vendorPhotos->isNotEmpty()
            ? $vendorPhotos
            : ($pickupPhotos->isNotEmpty() ? $pickupPhotos : $receiptPhotos);

        return [
            'id' => $item->id,
            'description' => $item->description,
            'tracking_code' => $item->tracking_code,
            'quantity' => (int) $item->quantity,
            'photos' => [
                'primary' => $primaryPhotos->values(),
                'primary_source' => $vendorPhotos->isNotEmpty() ? 'Vendor' : ($pickupPhotos->isNotEmpty() ? 'Pickup' : ($receiptPhotos->isNotEmpty() ? 'Receipt' : 'No photos')),
                'vendor' => $vendorPhotos,
                'pickup' => $pickupPhotos,
                'receipt' => $receiptPhotos,
                'total' => $vendorPhotos->count() + $pickupPhotos->count() + $receiptPhotos->count(),
            ],
        ];
    }

    private function photoPayload($photo, string $fallbackName, string $source): array
    {
        return [
            'id' => $photo->id,
            'url' => app(StorageService::class)->getUrl($photo->path),
            'original_name' => $photo->original_name ?: $fallbackName,
            'source' => $source,
        ];
    }

    public function workerStats(): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');
        $admin = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($admin);

        $workers = User::where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_warehouse_role', true))
            ->get()
            ->filter(fn ($u) => $u->hasPermission('warehouse.contacts.manage'));

        $stats = $workers->map(fn ($worker) => array_merge(
            ['id' => $worker->id, 'name' => $worker->name],
            $this->contactService->getWorkerStats($worker, $warehouse),
        ))->values();

        return response()->json(['data' => $stats]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
