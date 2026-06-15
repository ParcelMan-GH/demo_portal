<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageContactAttempt;
use App\Models\PackageContactTask;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Warehouse\PackageContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminContactQueueController extends Controller
{
    public function __construct(
        private PackageContactService $contactService,
    ) {}

    public function index(): View
    {
        $this->authorizePermission('shipments.view');

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $workers = User::whereNotNull('warehouse_id')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_warehouse_role', true))
            ->orderBy('name')
            ->get(['id', 'name', 'warehouse_id']);

        $stats = [
            'total' => PackageContactTask::count(),
            'unassigned' => PackageContactTask::where('status', PackageContactTask::STATUS_PENDING)->count(),
            'assigned' => PackageContactTask::where('status', PackageContactTask::STATUS_ASSIGNED)->count(),
            'in_progress' => PackageContactTask::where('status', PackageContactTask::STATUS_IN_PROGRESS)->count(),
            'resolved' => PackageContactTask::where('status', PackageContactTask::STATUS_RESOLVED)->count(),
            'callbacks_due' => PackageContactTask::where('outcome', PackageContactTask::OUTCOME_CALLBACK)
                ->where('callback_at', '<=', now())->count(),
            'resolved_today' => PackageContactTask::where('status', PackageContactTask::STATUS_RESOLVED)
                ->whereDate('resolved_at', today())->count(),
        ];

        return view('admin.contacts.index', compact('warehouses', 'workers', 'stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.view');

        $query = PackageContactTask::with($this->taskRelations());

        if ($status = $request->get('status')) {
            if ($status === 'callbacks_due') {
                $query->where('outcome', PackageContactTask::OUTCOME_CALLBACK)->where('callback_at', '<=', now());
            } else {
                $query->where('status', $status);
            }
        }

        if ($warehouseId = $request->get('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($workerId = $request->get('worker_id')) {
            $query->where('assigned_to_user_id', $workerId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_town', 'like', "%{$search}%")
                  ->orWhereHas('shipment', fn ($sq) => $sq->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        $total = $query->count();
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $tasks = $query->latest('created_at')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $tasks->map(function (PackageContactTask $task) {
                $task = $this->contactService->syncWithPackageState($task);
                $task->loadMissing($this->taskRelations());

                return $this->transformTask($task);
            })->values(),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total),
            ],
            'stats' => [
                'total' => PackageContactTask::count(),
                'unassigned' => PackageContactTask::where('status', PackageContactTask::STATUS_PENDING)->count(),
                'assigned' => PackageContactTask::where('status', PackageContactTask::STATUS_ASSIGNED)->count(),
                'in_progress' => PackageContactTask::where('status', PackageContactTask::STATUS_IN_PROGRESS)->count(),
                'resolved' => PackageContactTask::where('status', PackageContactTask::STATUS_RESOLVED)->count(),
                'callbacks_due' => PackageContactTask::where('outcome', PackageContactTask::OUTCOME_CALLBACK)
                    ->where('callback_at', '<=', now())->count(),
                'resolved_today' => PackageContactTask::where('status', PackageContactTask::STATUS_RESOLVED)
                    ->whereDate('resolved_at', today())->count(),
            ],
        ]);
    }

    public function assign(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id', 'required_without:worker_id'],
            'worker_id' => ['nullable', 'exists:users,id', 'required_without:user_id'],
        ]);

        $worker = User::findOrFail((int) ($validated['user_id'] ?? $validated['worker_id']));
        $this->contactService->assignToWorker($task, $worker);

        $task->loadMissing($this->taskRelations());

        return response()->json([
            'success' => true,
            'message' => "Assigned to {$worker->name}.",
            'task' => $this->transformTask($task->fresh($this->taskRelations())),
        ]);
    }

    public function autoAssign(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.edit');
        $validated = $request->validate(['warehouse_id' => ['required', 'exists:warehouses,id']]);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $count = $this->contactService->autoAssignRoundRobin($warehouse);
        if ($count === 0) return response()->json(['success' => false, 'message' => 'No pending tasks or eligible workers.']);
        return response()->json(['success' => true, 'message' => "{$count} tasks auto-assigned."]);
    }

    public function logCall(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('shipments.edit');
        $validated = $request->validate([
            'call_outcome' => ['required', 'in:answered,no_answer,busy,wrong_number,voicemail'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $admin = Auth::guard('admin')->user();
        $this->contactService->logAttempt($task, $admin, $validated['call_outcome'], $validated['notes']);
        return response()->json(['success' => true, 'message' => 'Call logged.', 'attempts_count' => $task->fresh()->attempts_count]);
    }

    public function resolve(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('shipments.edit');
        $validated = $request->validate([
            'outcome' => ['required', 'in:deliver,self_pickup,unreachable,wrong_number,callback'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'callback_at' => ['nullable', 'required_if:outcome,callback', 'date', 'after:now'],
            'confirmation_code' => ['nullable', 'string', 'max:10'],
        ]);
        $callbackAt = !empty($validated['callback_at']) ? new \DateTime($validated['callback_at']) : null;
        $admin = Auth::guard('admin')->user();

        $result = $this->contactService->resolveTask(
            $task,
            $validated['outcome'],
            $validated['notes'] ?? null,
            $callbackAt,
            $validated['confirmation_code'] ?? null,
            $admin,
        );

        if (!($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json(['success' => true, 'message' => 'Task resolved.']);
    }

    public function sendCode(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('shipments.edit');
        $result = $this->contactService->sendConfirmationCode($task);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function attempts(PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('shipments.view');
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

    public function workerStats(): JsonResponse
    {
        $this->authorizePermission('shipments.view');
        $workers = User::whereNotNull('warehouse_id')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_warehouse_role', true))
            ->get()
            ->filter(fn ($u) => $u->hasPermission('warehouse.contacts.manage'));

        $stats = $workers->map(function ($worker) {
            $warehouse = $worker->warehouse;
            if (!$warehouse) return null;
            return array_merge(
                ['id' => $worker->id, 'name' => $worker->name, 'warehouse' => $warehouse->name],
                $this->contactService->getWorkerStats($worker, $warehouse),
            );
        })->filter()->values();

        return response()->json(['data' => $stats]);
    }

    public function addToQueue(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'shipment_item_ids' => ['required', 'array', 'min:1'],
            'shipment_item_ids.*' => ['integer', 'exists:shipment_items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $count = $this->contactService->createTasksForWarehouseItems($warehouse, $validated['shipment_item_ids']);

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'No items added — they may already be in the queue or have no recipient phone.']);
        }

        return response()->json(['success' => true, 'message' => "{$count} item(s) added to contact queue."]);
    }

    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()?->hasPermission($permission)) {
            abort(403, 'Unauthorized.');
        }
    }

    private function transformTask(PackageContactTask $task): array
    {
        $isCallbackDue = $task->outcome === PackageContactTask::OUTCOME_CALLBACK
            && $task->callback_at?->lte(now());
        $deliveryMarker = $this->contactService->deliveryMarkerFor($task);

        return [
            'id' => $task->id,
            'shipment_number' => $task->shipment?->shipment_number,
            'tracking_code' => $task->shipmentItem?->tracking_code,
            'tracking_number' => $task->shipmentItem?->tracking_code,
            'description' => $task->shipmentItem?->description,
            'recipient_name' => $task->recipient_name,
            'recipient_phone' => $task->recipient_phone,
            'delivery_town' => $task->delivery_town,
            'recipient_town' => $task->delivery_town,
            'town' => $task->delivery_town,
            'warehouse_name' => $task->warehouse?->name,
            'status' => $task->status,
            'outcome' => $task->outcome,
            'assigned_to' => $task->assignedTo?->name,
            'assigned_to_name' => $task->assignedTo?->name,
            'assigned_to_id' => $task->assigned_to_user_id,
            'assigned_at' => $task->assigned_at?->format('M d, H:i'),
            'resolved_by' => $task->resolvedBy?->name,
            'resolved_by_id' => $task->resolved_by_user_id,
            'callback_at' => $task->callback_at?->format('M d, H:i'),
            'attempts_count' => $task->attempts_count,
            'resolved_at' => $task->resolved_at?->format('M d, H:i'),
            'delivered_by' => $deliveryMarker,
            'delivered_by_type' => $deliveryMarker['type'] ?? null,
            'delivered_by_name' => $deliveryMarker['name'] ?? null,
            'delivered_by_at' => $deliveryMarker['at'] ?? null,
            'notes' => $task->notes,
            'created_at' => $task->created_at?->format('M d, H:i'),
            'is_callback_due' => (bool) $isCallbackDue,
        ];
    }

    private function taskRelations(): array
    {
        return [
            'assignedTo:id,name',
            'resolvedBy:id,name',
            'shipment:id,shipment_number',
            'warehouse:id,name',
            'shipmentItem:id,shipment_id,tracking_code,description,status',
            'shipmentItem.deliveryRunItems:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,status,delivered_at',
            'shipmentItem.deliveryRunItems.run:id,run_number,assigned_driver_id',
            'shipmentItem.deliveryRunItems.run.assignedDriver:id,name,phone',
            'shipmentItem.deliveryRunItems.stop:id,delivery_run_id,status,delivery_method,delivered_at,confirmed_at,confirmed_by_admin_id,bus_station_name',
            'shipmentItem.deliveryRunItems.stop.confirmedBy:id,name',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation:id,delivery_run_item_id,status,source,target_type,target_name,target_phone,confirmed_at,confirmed_by_driver_id,confirmed_by_admin_id,public_confirmed_at',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByDriver:id,name,phone',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByAdmin:id,name',
        ];
    }

}
