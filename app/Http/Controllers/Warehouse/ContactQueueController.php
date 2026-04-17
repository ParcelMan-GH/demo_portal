<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PackageContactAttempt;
use App\Models\PackageContactTask;
use App\Models\User;
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

        $query = PackageContactTask::where('warehouse_id', $warehouse->id)
            ->with(['assignedTo:id,name', 'shipmentItem:id,tracking_code,description', 'shipment:id,shipment_number']);

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
            'data' => $tasks->map(fn ($task) => [
                'id' => $task->id,
                'shipment_number' => $task->shipment?->shipment_number,
                'tracking_code' => $task->shipmentItem?->tracking_code,
                'description' => $task->shipmentItem?->description,
                'recipient_name' => $task->recipient_name,
                'recipient_phone' => $task->recipient_phone,
                'delivery_town' => $task->delivery_town,
                'status' => $task->status,
                'outcome' => $task->outcome,
                'assigned_to' => $task->assignedTo?->name,
                'assigned_to_id' => $task->assigned_to_user_id,
                'assigned_at' => $task->assigned_at?->format('M d, H:i'),
                'callback_at' => $task->callback_at?->format('M d, H:i'),
                'attempts_count' => $task->attempts_count,
                'resolved_at' => $task->resolved_at?->format('M d, H:i'),
                'notes' => $task->notes,
                'created_at' => $task->created_at?->format('M d, H:i'),
            ])->values(),
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

        $count = $this->contactService->autoAssignRoundRobin($warehouse);

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'No pending tasks to assign, or no eligible workers found.']);
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

        $admin = Auth::guard('admin')->user();
        $this->contactService->logAttempt($task, $admin, $validated['call_outcome'], $validated['notes']);

        return response()->json(['success' => true, 'message' => 'Call attempt logged.', 'attempts_count' => $task->fresh()->attempts_count]);
    }

    public function resolve(Request $request, PackageContactTask $task): JsonResponse
    {
        $this->authorizePermission('warehouse.contacts.manage');

        $validated = $request->validate([
            'outcome' => ['required', 'in:deliver,self_pickup,unreachable,wrong_number,callback'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'callback_at' => ['nullable', 'required_if:outcome,callback', 'date', 'after:now'],
        ]);

        $callbackAt = !empty($validated['callback_at']) ? new \DateTime($validated['callback_at']) : null;
        $this->contactService->resolveTask($task, $validated['outcome'], $validated['notes'], $callbackAt);

        $labels = [
            'deliver' => 'Marked for delivery.',
            'self_pickup' => 'Marked for self-pickup. Fulfillment type updated.',
            'unreachable' => 'Marked as unreachable.',
            'wrong_number' => 'Marked as wrong number.',
            'callback' => 'Callback scheduled.',
        ];

        return response()->json(['success' => true, 'message' => $labels[$validated['outcome']] ?? 'Task resolved.']);
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
}
