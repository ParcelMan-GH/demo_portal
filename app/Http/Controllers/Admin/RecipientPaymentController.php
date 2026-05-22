<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PaymentWallet;
use App\Models\RecipientPaymentGroup;
use App\Models\RecipientPaymentSession;
use App\Models\RecipientPaymentSessionEntry;
use App\Models\RecipientPaymentTask;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\SortBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\Warehouse\RecipientPaymentService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\StorageService;
use App\Support\GenericPdfExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class RecipientPaymentController extends Controller
{
    public function __construct(
        private RecipientPaymentService $recipientPayments,
        private WarehousePortalService $portalService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $workers = $this->workersQuery($warehouse)->get(['id', 'name']);
        $wallets = $this->walletsQuery($warehouse)->get(['id', 'name', 'provider', 'phone_number', 'account_owner', 'warehouse_id', 'is_active']);
        $canAssign = $this->canRecipientPayment('assign');
        $canReconcile = $this->canRecipientPayment('reconcile');
        $canOverride = $this->canRecipientPayment('override');
        $canManageWallets = $this->canRecipientPayment('manage_wallets');
        $isAgentOnly = $this->isWarehouseRoute($request)
            && !($canAssign || $canReconcile || $canOverride || $canManageWallets);

        return view('shared.recipient-payments.index', [
            'layoutName' => $this->isWarehouseRoute($request) ? 'warehouse.layouts.app' : 'admin.layouts.app',
            'warehouse' => $warehouse,
            'warehouses' => $this->isWarehouseRoute($request) ? collect([$warehouse]) : Warehouse::orderBy('name')->get(['id', 'name', 'code']),
            'workers' => $workers,
            'wallets' => $wallets,
            'canAssign' => $canAssign,
            'canReconcile' => $canReconcile,
            'canOverride' => $canOverride,
            'canManageWallets' => $canManageWallets,
            'isAgentOnly' => $isAgentOnly,
            'currentUserId' => $this->user()?->id,
            'routePrefix' => $this->isWarehouseRoute($request) ? 'warehouse.recipient-payments' : 'admin.recipient-payments',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $this->ensureLocalDeliveryQueue($request, $warehouse);
        $query = $this->taskFilteredQuery($request, $warehouse);

        if ($request->boolean('group_by_recipient')) {
            return $this->groupedTasksResponse($request, $query);
        }

        $sortBy = (string) $request->input('sort', 'id');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['payment_group', 'recipient_name', 'recipient_phone', 'delivery_town', 'status', 'created_at', 'paid_at'], true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->orderBy('id', $direction);
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $tasks = $query->paginate($perPage);

        return response()->json([
            'data' => $tasks->map(fn (RecipientPaymentTask $task) => $this->serializeTask($task))->values(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'from' => $tasks->firstItem() ?? 0,
                'to' => $tasks->lastItem() ?? 0,
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage(),
            ],
        ]);
    }

    public function dataExport(Request $request)
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $this->ensureLocalDeliveryQueue($request, $warehouse);
        $tasks = $this->taskFilteredQuery($request, $warehouse)
            ->latest('id')
            ->get();

        $rows = $tasks->map(function (RecipientPaymentTask $task) {
            $data = $this->serializeTask($task);

            return [
                'Group' => $data['payment_group'] === 'local_delivery' ? 'Local Delivery' : 'Warehouse Transfer',
                'Batch' => $data['batch_number'] ?: 'No batch',
                'Shipment' => $data['shipment_number'] ?: '-',
                'Package' => $data['description'] ?: 'Package',
                'Tracking Code' => $data['tracking_code'] ?: '-',
                'Recipient' => $data['recipient_name'] ?: '-',
                'Phone' => $data['recipient_phone'] ?: '-',
                'Town' => $data['delivery_town'] ?: '-',
                'Delivery Method' => $data['delivery_method'] ?: '-',
                'Fee Amount' => $data['fee_amount'] === null ? '-' : 'GHS ' . number_format($data['fee_amount'], 2),
                'Fee Status' => $data['fee_status'] ?: '-',
                'Payment Status' => ucfirst(str_replace('_', ' ', $data['status'])),
                'Assigned To' => $data['assigned_to'] ?: 'Unassigned',
                'Wallet' => $data['wallet'] ?: '-',
                'Payment Reference' => $data['payment_reference'] ?: '-',
                'Paid At' => $data['paid_at'] ?: '-',
            ];
        })->values()->all();

        $format = (string) $request->input('format', 'json');
        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'recipient_payment_tasks_' . now()->format('Y-m-d_His') . '.xlsx');
        }
        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'recipient_payment_tasks_' . now()->format('Y-m-d_His') . '.pdf', 'Recipient Payment Tasks');
        }

        return response()->json(['data' => $rows]);
    }

    public function reports(Request $request): View
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $canAssign = $this->canRecipientPayment('assign');
        $canReconcile = $this->canRecipientPayment('reconcile');
        $canOverride = $this->canRecipientPayment('override');
        $canManageWallets = $this->canRecipientPayment('manage_wallets');
        $isAgentOnly = $this->isWarehouseRoute($request) && !($canAssign || $canReconcile || $canOverride || $canManageWallets);

        return view('shared.recipient-payments.reports', [
            'layoutName' => $this->isWarehouseRoute($request) ? 'warehouse.layouts.app' : 'admin.layouts.app',
            'warehouse' => $warehouse,
            'warehouses' => $this->isWarehouseRoute($request) ? collect([$warehouse]) : Warehouse::orderBy('name')->get(['id', 'name', 'code']),
            'workers' => $this->workersQuery($warehouse)->get(['id', 'name']),
            'wallets' => $isAgentOnly
                ? $this->reportWalletsForAgent($warehouse)
                : $this->walletsQuery($warehouse)->get(['id', 'name', 'provider', 'phone_number', 'account_owner', 'warehouse_id', 'is_active']),
            'isAgentOnly' => $isAgentOnly,
            'canReconcile' => $canReconcile,
            'canManageWallets' => $canManageWallets,
            'routePrefix' => $this->isWarehouseRoute($request) ? 'warehouse.recipient-payments' : 'admin.recipient-payments',
        ]);
    }

    public function reportsData(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $this->ensureLocalDeliveryQueue($request, $warehouse);

        $baseQuery = $this->reportFilteredQuery($request, $warehouse);
        $summaryTasks = (clone $baseQuery)->get();
        $summary = $this->reportSummary($summaryTasks);

        $sortBy = (string) $request->input('sort', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['payment_group', 'recipient_name', 'recipient_phone', 'delivery_town', 'status', 'created_at', 'paid_at', 'negotiated_amount'], true)) {
            $baseQuery->orderBy($sortBy, $direction);
        } else {
            $baseQuery->latest('id');
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $tasks = $baseQuery->paginate($perPage);

        return response()->json([
            'data' => $tasks->map(fn (RecipientPaymentTask $task) => $this->serializeReportTask($task))->values(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'from' => $tasks->firstItem() ?? 0,
                'to' => $tasks->lastItem() ?? 0,
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage(),
            ],
        ]);
    }

    public function reportsExport(Request $request)
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $this->ensureLocalDeliveryQueue($request, $warehouse);

        $rows = $this->reportFilteredQuery($request, $warehouse)
            ->latest('id')
            ->get()
            ->map(function (RecipientPaymentTask $task) {
                $data = $this->serializeReportTask($task);

                return [
                    'Date Recorded' => $data['paid_at'] ?: $data['created_at'],
                    'Agent' => $data['assigned_to'] ?: '-',
                    'Warehouse' => $data['warehouse'] ?: '-',
                    'Wallet' => $data['wallet'] ?: '-',
                    'Wallet Phone' => $data['wallet_phone'] ?: '-',
                    'Recipient' => $data['recipient_name'] ?: '-',
                    'Recipient Phone' => $data['recipient_phone'] ?: '-',
                    'Location' => $data['delivery_town'] ?: '-',
                    'Shipment' => $data['shipment_number'] ?: '-',
                    'Tracking Code' => $data['tracking_code'] ?: '-',
                    'Package' => $data['description'] ?: '-',
                    'Quantity' => $data['quantity'],
                    'Group' => $data['payment_group_label'],
                    'Delivery Method' => $data['delivery_method_label'],
                    'Delivery Fee' => $data['fee_amount'] === null ? '-' : 'GHS ' . number_format($data['fee_amount'], 2),
                    'Payment Status' => $data['payment_status_label'],
                    'Call Result' => $data['call_result_label'],
                    'Payment Reference' => $data['payment_reference'] ?: '-',
                    'Receipt Screenshot' => $data['has_receipt'] ? 'Yes' : 'No',
                    'Session' => $data['session'] ?: '-',
                    'Session Status' => $data['session_status'] ?: '-',
                    'Notes' => $data['notes'] ?: '-',
                ];
            })
            ->values()
            ->all();

        $format = (string) $request->input('format', 'json');
        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'recipient_payment_report_' . now()->format('Y-m-d_His') . '.xlsx');
        }
        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'recipient_payment_report_' . now()->format('Y-m-d_His') . '.pdf', 'Recipient Payment Report');
        }

        return response()->json(['data' => $rows]);
    }

    public function locationSearch(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $q = trim((string) $request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['locations' => []]);
        }

        $locations = Location::query()
            ->where('is_active', true)
            ->with(['district:id,name', 'region:id,name'])
            ->where(function (Builder $query) use ($q) {
                $query->where('name', 'like', $q . '%')
                    ->orWhere('name', 'like', '% ' . $q . '%');
            })
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$q . '%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'locations' => $locations->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'district' => ['id' => $location->district->id, 'name' => $location->district->name],
                'region' => ['id' => $location->region->id, 'name' => $location->region->name],
                'display' => "{$location->name}, {$location->district->name}, {$location->region->name}",
            ])->values(),
        ]);
    }

    public function wallets(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);

        if ($request->boolean('options')) {
            return response()->json([
                'data' => $this->walletsQuery($warehouse)
                    ->where('is_active', true)
                    ->with('assignedUsers:id,name')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (PaymentWallet $wallet) => $this->serializeWallet($wallet))
                    ->values(),
            ]);
        }

        [$dateFrom, $dateTo] = $this->walletStatsDateRange($request);
        $query = $this->walletsFilteredQuery($request, $warehouse);
        $sortBy = (string) $request->input('sort', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['name', 'provider', 'phone_number', 'account_owner', 'created_at'], true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $wallets = $query
            ->with('assignedUsers:id,name')
            ->withCount(['recipientPaymentTasks', 'sessions'])
            ->paginate($perPage);

        $stats = $this->walletPaymentStats($wallets->getCollection()->pluck('id'), $dateFrom, $dateTo);

        return response()->json([
            'data' => $wallets->map(fn (PaymentWallet $wallet) => $this->serializeWallet($wallet, $stats->get($wallet->id)))->values(),
            'meta' => [
                'current_page' => $wallets->currentPage(),
                'from' => $wallets->firstItem() ?? 0,
                'to' => $wallets->lastItem() ?? 0,
                'total' => $wallets->total(),
                'last_page' => $wallets->lastPage(),
            ],
        ]);
    }

    public function walletsExport(Request $request)
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        [$dateFrom, $dateTo] = $this->walletStatsDateRange($request);
        $wallets = $this->walletsFilteredQuery($request, $warehouse)
            ->with('assignedUsers:id,name')
            ->withCount(['recipientPaymentTasks', 'sessions'])
            ->orderBy('name')
            ->get();
        $stats = $this->walletPaymentStats($wallets->pluck('id'), $dateFrom, $dateTo);

        $rows = $wallets->map(function (PaymentWallet $wallet) use ($stats) {
            $data = $this->serializeWallet($wallet, $stats->get($wallet->id));

            return [
                'Wallet Name' => $data['name'],
                'Provider' => $data['provider'],
                'Phone' => $data['phone_number'],
                'Account Owner' => $data['account_owner'] ?: '-',
                'Warehouse' => $data['warehouse_name'],
                'Assigned Agents' => collect($data['assigned_users'])->pluck('name')->join(', ') ?: '-',
                'Recorded Amount' => 'GHS ' . number_format($data['recorded_amount'], 2),
                'Payment Count' => $data['payment_count'],
                'Last Payment' => $data['last_payment_at'] ?: '-',
                'History' => $data['history_label'],
                'Status' => $data['is_active'] ? 'Active' : 'Inactive',
                'Created At' => $data['created_at'],
            ];
        })->values()->all();

        $format = (string) $request->input('format', 'json');
        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'payment_wallets_' . now()->format('Y-m-d_His') . '.xlsx');
        }
        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'payment_wallets_' . now()->format('Y-m-d_His') . '.pdf', 'Payment Wallets');
        }

        return response()->json(['data' => $rows]);
    }

    public function storeWallet(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.manage_wallets');
        $validated = $this->validateWalletPayload($request);
        $warehouse = $this->warehouseForRequest($request)
            ?: (!empty($validated['warehouse_id']) ? Warehouse::query()->find((int) $validated['warehouse_id']) : null);

        $wallet = PaymentWallet::query()->create([
            'warehouse_id' => $warehouse?->id,
            'name' => $validated['name'],
            'provider' => $validated['provider'],
            'phone_number' => $validated['phone_number'],
            'account_owner' => $validated['account_owner'] ?? null,
            'is_active' => true,
        ]);
        $wallet->assignedUsers()->sync($validated['user_ids'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Payment wallet saved.',
            'wallet' => $this->serializeWallet($wallet->fresh(['assignedUsers'])),
        ]);
    }

    public function updateWallet(Request $request, PaymentWallet $wallet): JsonResponse
    {
        $this->authorizePermission('recipient_payments.manage_wallets');
        $this->assertWalletScope($request, $wallet);
        $validated = $this->validateWalletPayload($request, $wallet);
        $warehouse = $this->warehouseForRequest($request)
            ?: (!empty($validated['warehouse_id']) ? Warehouse::query()->find((int) $validated['warehouse_id']) : null);

        $wallet->update([
            'warehouse_id' => $warehouse?->id,
            'name' => $validated['name'],
            'provider' => $validated['provider'],
            'phone_number' => $validated['phone_number'],
            'account_owner' => $validated['account_owner'] ?? null,
        ]);
        $wallet->assignedUsers()->sync($validated['user_ids'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Payment wallet updated.',
            'wallet' => $this->serializeWallet($wallet->fresh(['assignedUsers'])),
        ]);
    }

    public function deleteWallet(Request $request, PaymentWallet $wallet): JsonResponse
    {
        $this->authorizePermission('recipient_payments.manage_wallets');
        $this->assertWalletScope($request, $wallet);

        if ($this->walletHasHistory($wallet)) {
            return response()->json([
                'success' => false,
                'message' => 'This wallet has payment history. Deactivate it instead of deleting it.',
            ], 422);
        }

        $wallet->assignedUsers()->detach();
        $wallet->forceDelete();

        return response()->json(['success' => true, 'message' => 'Payment wallet deleted.']);
    }

    public function updateWalletStatus(Request $request, PaymentWallet $wallet): JsonResponse
    {
        $this->authorizePermission('recipient_payments.manage_wallets');
        $this->assertWalletScope($request, $wallet);
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $wallet->update(['is_active' => (bool) $validated['is_active']]);

        return response()->json([
            'success' => true,
            'message' => $wallet->is_active ? 'Payment wallet activated.' : 'Payment wallet deactivated.',
            'wallet' => $this->serializeWallet($wallet->fresh(['assignedUsers'])),
        ]);
    }

    public function assign(Request $request, RecipientPaymentTask $task): JsonResponse
    {
        $this->authorizePermission('recipient_payments.assign');
        $validated = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $worker = User::query()->findOrFail((int) $validated['user_id']);
        $this->recipientPayments->assignTasks([$task->id], $worker);

        return response()->json(['success' => true, 'message' => "Assigned to {$worker->name}."]);
    }

    public function release(Request $request, RecipientPaymentTask $task): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $result = $this->recipientPayments->releaseTaskForUser($task, $this->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.assign');
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:recipient_payment_tasks,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);
        $worker = User::query()->findOrFail((int) $validated['user_id']);
        $count = $this->recipientPayments->assignTasks($validated['task_ids'], $worker);

        return response()->json(['success' => true, 'message' => "{$count} task(s) assigned to {$worker->name}."]);
    }

    public function logCall(Request $request, RecipientPaymentTask $task): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate([
            'outcome' => ['required', 'string', 'in:answered,no_answer,busy,wrong_number,callback,payment_promised'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->recipientPayments->logCall($task, $this->user(), $validated['outcome'], $validated['notes'] ?? null);

        return response()->json(['success' => true, 'message' => 'Call attempt logged.']);
    }

    public function setFee(Request $request, RecipientPaymentTask $task): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $result = $this->recipientPayments->setFee($task, (float) $validated['amount'], $this->user(), $validated['notes'] ?? null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markPaid(Request $request, RecipientPaymentTask $task): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate([
            'payment_wallet_id' => ['required', 'exists:payment_wallets,id'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $wallet = PaymentWallet::query()->findOrFail((int) $validated['payment_wallet_id']);
        $result = $this->recipientPayments->markPaid(
            $task,
            $wallet,
            $this->user(),
            $validated['payment_reference'] ?? null,
            $validated['notes'] ?? null,
            !$this->canRecipientPayment('manage_wallets')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function logGroupCall(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:recipient_payment_tasks,id'],
            'outcome' => ['required', 'string', 'in:answered,no_answer,busy,wrong_number,callback,payment_promised'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tasks = $this->scopedGroupTasks($request, $validated['task_ids']);
        foreach ($tasks as $task) {
            $this->recipientPayments->logCall($task, $this->user(), $validated['outcome'], $validated['notes'] ?? null);
        }

        return response()->json([
            'success' => true,
            'message' => "Call logged for {$tasks->count()} package(s).",
        ]);
    }

    public function updateGroupDetails(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:recipient_payment_tasks,id'],
            'recipient_phone' => ['required', 'string', 'max:40'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
        ]);

        $tasks = $this->scopedGroupTasks($request, $validated['task_ids']);
        $result = $this->recipientPayments->updateRecipientDetails(
            $tasks,
            $this->user(),
            $validated['recipient_phone'],
            $validated['delivery_town'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function markGroupPaid(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:recipient_payment_tasks,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'recipient_phone' => ['nullable', 'string', 'max:40'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'payment_wallet_id' => ['required', 'exists:payment_wallets,id'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'outcome' => ['required', 'string', 'in:answered,no_answer,busy,wrong_number,callback,payment_promised'],
            'payment_receipt' => ['nullable', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tasks = $this->scopedGroupTasks($request, $validated['task_ids']);
        $receiptPath = $request->file('payment_receipt')?->store('recipient-payment-receipts', 'public');

        if (!empty($validated['recipient_phone']) || array_key_exists('delivery_town', $validated)) {
            $this->recipientPayments->updateRecipientDetails(
                $tasks,
                $this->user(),
                $validated['recipient_phone'] ?? null,
                $validated['delivery_town'] ?? null
            );
            $tasks = $this->scopedGroupTasks($request, $validated['task_ids']);
        }
        $wallet = PaymentWallet::query()->findOrFail((int) $validated['payment_wallet_id']);
        $result = $this->recipientPayments->markRecipientGroupPaid(
            $tasks,
            (float) $validated['amount'],
            $wallet,
            $this->user(),
            $validated['payment_reference'] ?? null,
            $validated['notes'] ?? null,
            !$this->canRecipientPayment('manage_wallets'),
            $receiptPath
        );

        if ($result['success'] ?? false) {
            foreach ($tasks as $task) {
                $this->recipientPayments->logCall($task, $this->user(), $validated['outcome'], $validated['notes'] ?? null);
            }
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function override(Request $request, RecipientPaymentTask $task): JsonResponse
    {
        $this->authorizePermission('recipient_payments.override');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $result = $this->recipientPayments->override($task, $this->user(), $validated['reason']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $validated = $request->validate(['code' => ['required', 'string', 'max:120']]);
        $code = trim($validated['code']);

        $label = WarehouseReceiptItemLabel::query()
            ->with('receiptItem.receipt')
            ->where('barcode_value', $code)
            ->first();
        $shipmentItemId = $label?->receiptItem?->shipment_item_id;
        if (!$shipmentItemId) {
            $shipmentItemId = ShipmentItem::query()->where('tracking_code', $code)->value('id');
        }
        if (!$shipmentItemId) {
            return response()->json(['success' => false, 'message' => 'No package found for this label or tracking code.'], 404);
        }

        $batchItem = SortBatchItem::query()
            ->with('sortBatch')
            ->where('shipment_item_id', $shipmentItemId)
            ->whereNull('removed_at')
            ->latest('id')
            ->first();
        if (!$batchItem) {
            $shipmentItem = ShipmentItem::query()->with('shipment')->find($shipmentItemId);
            $warehouse = $label?->receiptItem?->receipt?->warehouse
                ?: $this->warehouseForRequest($request)
                ?: $this->latestFinalizedReceiptWarehouseForItem((int) $shipmentItemId);

            if (!$shipmentItem || !$warehouse) {
                return response()->json(['success' => false, 'message' => 'This package is not in an active warehouse receipt.'], 422);
            }

            $task = $this->recipientPayments->ensureLocalDeliveryTaskForShipmentItem($shipmentItem, $warehouse);
            if (!$task) {
                return response()->json(['success' => false, 'message' => 'This package is not eligible for local recipient payment processing.'], 422);
            }

            $claim = $this->recipientPayments->claimTaskForUser($task, $this->user());

            return response()->json([
                'success' => $claim['success'],
                'conflict' => $claim['conflict'] ?? false,
                'message' => $claim['message'],
                'task' => isset($claim['task']) ? $this->serializeTask($claim['task']) : $this->serializeTask($task),
            ], $claim['success'] ? 200 : 409);
        }

        $task = $this->recipientPayments->ensureTaskForSortBatchItem($batchItem);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'This package has no recipient phone and cannot be queued.'], 422);
        }

        $claim = $this->recipientPayments->claimTaskForUser($task, $this->user());

        return response()->json([
            'success' => $claim['success'],
            'conflict' => $claim['conflict'] ?? false,
            'message' => $claim['message'],
            'task' => isset($claim['task']) ? $this->serializeTask($claim['task']) : $this->serializeTask($task),
        ], $claim['success'] ? 200 : 409);
    }

    public function sessions(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $query = $this->sessionsFilteredQuery($request, $warehouse);
        $sortBy = (string) $request->input('sort', 'started_at');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['started_at', 'closed_at', 'opening_balance', 'closing_balance', 'expected_closing_balance', 'variance', 'status'], true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest('id');
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $sessions = $query->paginate($perPage);

        return response()->json([
            'data' => $sessions->map(fn (RecipientPaymentSession $session) => $this->serializeSession($session))->values(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'from' => $sessions->firstItem() ?? 0,
                'to' => $sessions->lastItem() ?? 0,
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
        ]);
    }

    public function sessionsExport(Request $request)
    {
        $this->authorizePermission('recipient_payments.view');
        $warehouse = $this->warehouseForRequest($request);
        $sessions = $this->sessionsFilteredQuery($request, $warehouse)
            ->orderByDesc('started_at')
            ->get();

        $rows = $sessions->map(function (RecipientPaymentSession $session) {
            $data = $this->serializeSession($session);

            return [
                'Agent' => $data['agent'] ?: '-',
                'Wallet' => $data['wallet'] ?: '-',
                'Provider' => $data['wallet_provider'] ?: '-',
                'Phone' => $data['wallet_phone'] ?: '-',
                'Warehouse' => $data['warehouse'] ?: '-',
                'Opening Balance' => 'GHS ' . number_format($data['opening_balance'], 2),
                'Closing Balance' => $data['closing_balance'] === null ? '-' : 'GHS ' . number_format($data['closing_balance'], 2),
                'Expected Closing' => $data['expected_closing_balance'] === null ? '-' : 'GHS ' . number_format($data['expected_closing_balance'], 2),
                'Variance' => $data['variance'] === null ? '-' : 'GHS ' . number_format($data['variance'], 2),
                'Status' => ucfirst(str_replace('_', ' ', $data['status'])),
                'Started At' => $data['started_at'] ?: '-',
                'Closed At' => $data['closed_at'] ?: '-',
            ];
        })->values()->all();

        $format = (string) $request->input('format', 'json');
        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'recipient_payment_sessions_' . now()->format('Y-m-d_His') . '.xlsx');
        }
        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'recipient_payment_sessions_' . now()->format('Y-m-d_His') . '.pdf', 'Recipient Payment Sessions');
        }

        return response()->json(['data' => $rows]);
    }

    public function startSession(Request $request): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        $warehouse = $this->warehouseForRequest($request) ?: Warehouse::query()->findOrFail((int) $request->input('warehouse_id'));
        $validated = $request->validate([
            'payment_wallet_id' => ['required', 'exists:payment_wallets,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $wallet = PaymentWallet::query()->findOrFail((int) $validated['payment_wallet_id']);
        if (!$wallet->assignedUsers()->whereKey($this->user()->id)->exists() && !$this->canRecipientPayment('manage_wallets')) {
            return response()->json(['success' => false, 'message' => 'You can only start sessions for wallets assigned to you.'], 422);
        }
        $result = $this->recipientPayments->startSession($this->user(), $warehouse, $wallet, (float) $validated['opening_balance'], $validated['notes'] ?? null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function closeSession(Request $request, RecipientPaymentSession $session): JsonResponse
    {
        $this->authorizePermission('recipient_payments.process');
        if ($session->user_id !== $this->user()?->id && !$this->canRecipientPayment('reconcile')) {
            return response()->json(['success' => false, 'message' => 'You cannot close another agent session.'], 403);
        }
        $validated = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $result = $this->recipientPayments->closeSession($session, $this->user(), (float) $validated['closing_balance'], $validated['notes'] ?? null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function serializeTask(RecipientPaymentTask $task): array
    {
        $task->loadMissing([
            'assignedTo:id,name',
            'paymentWallet:id,name,provider,phone_number',
            'shipmentItem.images',
            'shipmentItem.warehouseReceiptItems.photos',
            'shipmentItem.shipment.vendor',
            'shipmentItem.shipment.pickupAssignment.photos',
            'shipmentCharge',
            'paymentGroupRecord',
            'sessionEntries',
            'sortBatch',
            'callAttempts',
        ]);

        $item = $task->shipmentItem;
        $charge = $task->shipmentCharge;
        $photos = $this->packagePhotosForTask($task);
        $latestCall = $task->callAttempts->first();
        $status = $this->effectivePaymentStatus($task);
        $receipt = $this->paymentReceiptForTask($task);

        return [
            'id' => $task->id,
            'payment_group' => $task->payment_group,
            'status' => $status,
            'sort_batch_id' => $task->sort_batch_id,
            'batch_number' => $task->sortBatch?->batch_number,
            'shipment_item_id' => $task->shipment_item_id,
            'shipment_number' => $item?->shipment?->shipment_number,
            'tracking_code' => $item?->tracking_code,
            'description' => $item?->description,
            'quantity' => (int) ($item?->quantity ?? 1),
            'delivery_method' => $item?->delivery_method,
            'recipient_name' => $task->recipient_name,
            'recipient_phone' => $task->recipient_phone,
            'delivery_town' => $task->delivery_town,
            'original_recipient_name' => $item?->delivery_recipient_name ?: $item?->shipment?->delivery_recipient_name,
            'original_recipient_phone' => $item?->delivery_recipient_phone ?: $item?->shipment?->delivery_recipient_phone,
            'original_delivery_town' => $item?->delivery_town ?: $item?->shipment?->delivery_town,
            'vendor_photos' => $photos['photos'],
            'photo_source' => $photos['source'],
            'photo_source_label' => $photos['source_label'],
            'fee_amount' => $charge ? (float) $charge->amount : ($task->negotiated_amount !== null ? (float) $task->negotiated_amount : null),
            'fee_status' => $status === RecipientPaymentTask::STATUS_PAID ? ShipmentCharge::STATUS_PAID : $charge?->status,
            'currency' => $task->currency,
            'assigned_to' => $task->assignedTo?->name,
            'assigned_to_user_id' => $task->assigned_to_user_id,
            'wallet' => $task->paymentWallet?->name,
            'payment_wallet_id' => $task->payment_wallet_id,
            'payment_reference' => $task->payment_reference,
            'payment_receipt_url' => $receipt['url'],
            'payment_receipt_name' => $receipt['name'],
            'payment_receipt_path' => $receipt['path'],
            'paid_at' => $task->paid_at?->format('Y-m-d H:i:s'),
            'call_result' => $latestCall?->outcome,
            'call_result_label' => $latestCall ? $this->callOutcomeLabel($latestCall->outcome) : 'Not called',
            'last_call_at' => $latestCall?->attempted_at?->format('Y-m-d H:i:s'),
            'notes' => $task->notes,
            'can_release' => (int) $task->assigned_to_user_id === (int) $this->user()?->id
                && !in_array($status, [RecipientPaymentTask::STATUS_PAID, RecipientPaymentTask::STATUS_WAIVED, RecipientPaymentTask::STATUS_OVERRIDDEN], true),
            'override_reason' => $task->override_reason,
        ];
    }

    private function paymentReceiptForTask(RecipientPaymentTask $task): array
    {
        $task->loadMissing(['paymentGroupRecord', 'sessionEntries']);

        $path = $task->paymentGroupRecord?->receipt_path;
        if (!$path) {
            $path = $task->sessionEntries
                ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
                ->sortByDesc('created_at')
                ->first()?->receipt_path;
        }

        if (!$path) {
            return ['path' => null, 'url' => null, 'name' => null];
        }

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'name' => basename($path),
        ];
    }

    private function effectivePaymentStatus(RecipientPaymentTask $task): string
    {
        $task->loadMissing(['shipmentCharge', 'paymentGroupRecord', 'sessionEntries']);

        if ($task->status === RecipientPaymentTask::STATUS_OVERRIDDEN) {
            return RecipientPaymentTask::STATUS_OVERRIDDEN;
        }

        if ($task->paymentGroupRecord?->status === RecipientPaymentGroup::STATUS_PAID) {
            return RecipientPaymentTask::STATUS_PAID;
        }

        if ($task->status === RecipientPaymentTask::STATUS_PAID || $task->paid_at) {
            return RecipientPaymentTask::STATUS_PAID;
        }

        if ($task->shipmentCharge?->status === ShipmentCharge::STATUS_PAID) {
            return RecipientPaymentTask::STATUS_PAID;
        }

        $hasPaymentEntry = $task->sessionEntries
            ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
            ->isNotEmpty();
        if ($hasPaymentEntry) {
            return RecipientPaymentTask::STATUS_PAID;
        }

        return $task->status;
    }

    private function packagePhotosForTask(RecipientPaymentTask $task): array
    {
        $item = $task->shipmentItem;

        if (!$item) {
            return ['source' => null, 'source_label' => 'No photos', 'photos' => collect()];
        }

        $vendorPhotos = $item->images?->map(fn ($image) => $image->getSignedUrl())->values() ?? collect();
        if ($vendorPhotos->isNotEmpty()) {
            return ['source' => 'vendor', 'source_label' => 'Vendor photos', 'photos' => $vendorPhotos];
        }

        $pickupPhotos = $item->shipment?->pickupAssignment?->photos
            ?->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $item->id)
            ->values()
            ->map(fn ($photo) => $this->photoPayload($photo, 'Pickup driver photo'))
            ?? collect();

        if ($pickupPhotos->isNotEmpty()) {
            return ['source' => 'pickup_driver', 'source_label' => 'Pickup driver photos', 'photos' => $pickupPhotos];
        }

        $receiptPhotos = $item->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->photos ?? collect())
            ->values()
            ->map(fn ($photo) => $this->photoPayload($photo, 'Receipt photo'))
            ?? collect();

        if ($receiptPhotos->isNotEmpty()) {
            return ['source' => 'receipt', 'source_label' => 'Receipt photos', 'photos' => $receiptPhotos];
        }

        return ['source' => null, 'source_label' => 'No photos', 'photos' => collect()];
    }

    private function photoPayload($photo, string $fallbackName): array
    {
        $url = app(StorageService::class)->getUrl($photo->path);
        $size = (int) ($photo->size ?? 0);

        return [
            'id' => $photo->id,
            'url' => $url,
            'original_name' => $photo->original_name ?: $fallbackName,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    private function groupedTasksResponse(Request $request, Builder $query): JsonResponse
    {
        $sortBy = (string) $request->input('sort', 'recipient_name');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $page = max((int) $request->input('page', 1), 1);

        $tasks = $query
            ->orderBy('recipient_name')
            ->orderBy('recipient_phone')
            ->orderByDesc('id')
            ->get();

        $groups = $tasks
            ->groupBy(fn (RecipientPaymentTask $task) => $this->recipientGroupKey($task))
            ->map(fn (Collection $groupTasks) => $this->serializeTaskGroup($groupTasks))
            ->values();

        $groups = $groups->sortBy(function (array $group) use ($sortBy) {
            return match ($sortBy) {
                'status' => $group['status'],
                'payment_group' => $group['payment_group'],
                'recipient_phone' => $group['recipient_phone'],
                default => $group['recipient_name'],
            };
        }, SORT_REGULAR, $direction === 'desc')->values();

        $paginator = new LengthAwarePaginator(
            $groups->forPage($page, $perPage)->values(),
            $groups->count(),
            $perPage,
            $page
        );

        return response()->json([
            'data' => $paginator->getCollection()->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    private function serializeTaskGroup(Collection $tasks): array
    {
        $serialized = $tasks->map(fn (RecipientPaymentTask $task) => $this->serializeTask($task))->values();
        $first = $serialized->first();
        $pendingTasks = $serialized->reject(fn (array $task) => in_array($task['status'], [
            RecipientPaymentTask::STATUS_PAID,
            RecipientPaymentTask::STATUS_WAIVED,
            RecipientPaymentTask::STATUS_OVERRIDDEN,
        ], true));
        $statuses = $serialized->pluck('status')->unique()->values();
        $paymentGroups = $serialized->pluck('payment_group')->filter()->unique()->values();
        $status = $statuses->count() === 1
            ? (string) $statuses->first()
            : ($pendingTasks->isEmpty() ? RecipientPaymentTask::STATUS_PAID : RecipientPaymentTask::STATUS_IN_PROGRESS);
        $latestCall = $serialized
            ->filter(fn (array $task) => !empty($task['last_call_at']))
            ->sortByDesc('last_call_at')
            ->first();

        return [
            'id' => 'recipient-' . $this->recipientGroupKey($tasks->first()),
            'is_group' => true,
            'payment_group' => $paymentGroups->count() === 1 ? (string) $paymentGroups->first() : 'mixed',
            'status' => $status,
            'recipient_name' => $first['recipient_name'] ?? 'No recipient',
            'recipient_phone' => $first['recipient_phone'] ?? null,
            'delivery_town' => $first['delivery_town'] ?? null,
            'delivery_method' => $serialized->pluck('delivery_method')->filter()->unique()->count() === 1 ? $first['delivery_method'] : null,
            'batch_number' => $serialized->pluck('batch_number')->filter()->unique()->take(2)->join(', ') ?: 'No batch',
            'shipment_number' => $serialized->pluck('shipment_number')->filter()->unique()->take(2)->join(', '),
            'description' => $tasks->count() . ' package' . ($tasks->count() === 1 ? '' : 's') . ' for this recipient',
            'tracking_code' => $tasks->count() === 1 ? ($first['tracking_code'] ?? null) : null,
            'fee_amount' => (float) $serialized->sum(fn (array $task) => (float) ($task['fee_amount'] ?? 0)),
            'fee_status' => $pendingTasks->isEmpty() ? 'cleared' : $pendingTasks->count() . ' pending',
            'call_result' => $latestCall['call_result'] ?? null,
            'call_result_label' => $latestCall['call_result_label'] ?? 'Not called',
            'last_call_at' => $latestCall['last_call_at'] ?? null,
            'assigned_to' => $first['assigned_to'] ?? null,
            'assigned_to_user_id' => $first['assigned_to_user_id'] ?? null,
            'payment_wallet_id' => $first['payment_wallet_id'] ?? null,
            'payment_reference' => $first['payment_reference'] ?? null,
            'payment_receipt_url' => $serialized->pluck('payment_receipt_url')->filter()->first(),
            'payment_receipt_name' => $serialized->pluck('payment_receipt_name')->filter()->first(),
            'payment_receipt_path' => $serialized->pluck('payment_receipt_path')->filter()->first(),
            'can_release' => $serialized->contains(fn (array $task) => (bool) ($task['can_release'] ?? false)),
            'package_count' => $tasks->count(),
            'pending_count' => $pendingTasks->count(),
            'paid_count' => $serialized->where('status', RecipientPaymentTask::STATUS_PAID)->count(),
            'tasks' => $serialized,
        ];
    }

    private function callOutcomeLabel(?string $outcome): string
    {
        return match ($outcome) {
            'answered' => 'Answered',
            'no_answer' => 'No answer',
            'busy' => 'Busy',
            'wrong_number' => 'Wrong number',
            'callback' => 'Call back',
            'payment_promised' => 'Pay later',
            default => 'Not called',
        };
    }

    private function recipientGroupKey(RecipientPaymentTask $task): string
    {
        $phone = preg_replace('/\D+/', '', (string) $task->recipient_phone);
        return $phone !== '' ? $phone : 'task-' . $task->id;
    }

    private function scopedGroupTasks(Request $request, array $taskIds): Collection
    {
        $warehouse = $this->warehouseForRequest($request);
        $query = RecipientPaymentTask::query()
            ->with(['shipmentItem.shipment', 'shipmentCharge', 'paymentWallet'])
            ->whereIn('id', collect($taskIds)->map(fn ($id) => (int) $id)->unique()->values());

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        if (!$this->canRecipientPayment('reconcile')) {
            $query->where('assigned_to_user_id', $this->user()?->id);
        }

        $tasks = $query->get();
        if ($tasks->isEmpty()) {
            abort(403, 'No processable recipient payment tasks found.');
        }

        return $tasks;
    }

    private function serializeWallet(PaymentWallet $wallet, mixed $stats = null): array
    {
        $wallet->loadMissing(['assignedUsers:id,name', 'warehouse:id,name']);

        $tasksCount = (int) ($wallet->recipient_payment_tasks_count ?? $wallet->recipientPaymentTasks()->count());
        $sessionsCount = (int) ($wallet->sessions_count ?? $wallet->sessions()->count());
        $hasHistory = $tasksCount > 0 || $sessionsCount > 0;
        $recordedAmount = $stats ? (float) $stats->recorded_amount : 0.0;
        $paymentCount = $stats ? (int) $stats->payment_count : 0;

        return [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'provider' => $wallet->provider,
            'phone_number' => $wallet->phone_number,
            'account_owner' => $wallet->account_owner,
            'warehouse_id' => $wallet->warehouse_id,
            'warehouse_name' => $wallet->warehouse?->name ?: 'All warehouses',
            'is_active' => $wallet->is_active,
            'has_history' => $hasHistory,
            'can_delete' => !$hasHistory,
            'history_label' => $hasHistory ? "{$tasksCount} payment task(s), {$sessionsCount} session(s)" : 'Unused',
            'recorded_amount' => $recordedAmount,
            'payment_count' => $paymentCount,
            'last_payment_at' => $stats?->last_payment_at ? Carbon::parse($stats->last_payment_at)->format('Y-m-d H:i:s') : null,
            'created_at' => $wallet->created_at?->format('Y-m-d H:i:s'),
            'assigned_users' => $wallet->assignedUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ];
    }

    private function validateWalletPayload(Request $request, ?PaymentWallet $wallet = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'provider' => ['required', 'string', 'max:40', Rule::in(['MTN MoMo', 'Telecel Cash', 'AirtelTigo Cash'])],
            'phone_number' => [
                'required',
                'string',
                'max:40',
                Rule::unique('payment_wallets', 'phone_number')
                    ->where(fn ($query) => $query->where('provider', $request->input('provider')))
                    ->ignore($wallet?->id),
            ],
            'account_owner' => ['nullable', 'string', 'max:120'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    private function walletHasHistory(PaymentWallet $wallet): bool
    {
        return $wallet->recipientPaymentTasks()->exists() || $wallet->sessions()->exists();
    }

    private function assertWalletScope(Request $request, PaymentWallet $wallet): void
    {
        $warehouse = $this->warehouseForRequest($request);
        if ($warehouse && $wallet->warehouse_id !== null && (int) $wallet->warehouse_id !== (int) $warehouse->id) {
            abort(403, 'This wallet belongs to another warehouse.');
        }
    }

    private function warehouseForRequest(Request $request): ?Warehouse
    {
        if ($this->isWarehouseRoute($request)) {
            return $this->portalService->resolveWarehouse($this->user());
        }

        $warehouseId = $request->integer('warehouse_id');
        return $warehouseId ? Warehouse::query()->find($warehouseId) : null;
    }

    private function ensureLocalDeliveryQueue(Request $request, ?Warehouse $warehouse): void
    {
        $group = $request->input('group');
        if ($group === RecipientPaymentTask::GROUP_WAREHOUSE_TRANSFER) {
            return;
        }

        if ($warehouse) {
            $this->recipientPayments->ensureLocalDeliveryTasksForWarehouse($warehouse);
            return;
        }

        Warehouse::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(25)
            ->get(['id', 'name'])
            ->each(fn (Warehouse $activeWarehouse) => $this->recipientPayments->ensureLocalDeliveryTasksForWarehouse($activeWarehouse));
    }

    private function latestFinalizedReceiptWarehouseForItem(int $shipmentItemId): ?Warehouse
    {
        $receiptItem = WarehouseReceiptItem::query()
            ->with('receipt.warehouse')
            ->where('shipment_item_id', $shipmentItemId)
            ->where('received_quantity', '>', 0)
            ->whereHas('receipt', fn (Builder $query) => $query->where('status', WarehouseReceipt::STATUS_FINALIZED))
            ->latest('id')
            ->first();

        return $receiptItem?->receipt?->warehouse;
    }

    private function walletsFilteredQuery(Request $request, ?Warehouse $warehouse): Builder
    {
        $query = $this->walletsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('account_owner', 'like', "%{$search}%")
                    ->orWhereHas('assignedUsers', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($provider = trim((string) $request->input('provider'))) {
            $query->where('provider', $provider);
        }

        if ($status = trim((string) $request->input('status'))) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (!$warehouse && $request->filled('wallet_warehouse_id')) {
            $walletWarehouseId = (string) $request->input('wallet_warehouse_id');
            if ($walletWarehouseId === 'global') {
                $query->whereNull('warehouse_id');
            } elseif (ctype_digit($walletWarehouseId)) {
                $query->where('warehouse_id', (int) $walletWarehouseId);
            }
        }

        return $query;
    }

    private function walletStatsDateRange(Request $request): array
    {
        $timezone = config('app.timezone', 'UTC');
        $today = Carbon::now($timezone);
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'), $timezone)->startOfDay()
            : $today->copy()->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'), $timezone)->endOfDay()
            : $today->copy()->endOfDay();

        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom->timezone('UTC'), $dateTo->timezone('UTC')];
    }

    private function walletPaymentStats(Collection $walletIds, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        if ($walletIds->isEmpty()) {
            return collect();
        }

        return RecipientPaymentSessionEntry::query()
            ->selectRaw('recipient_payment_sessions.payment_wallet_id, COALESCE(SUM(recipient_payment_session_entries.amount), 0) as recorded_amount, COUNT(*) as payment_count, MAX(recipient_payment_session_entries.created_at) as last_payment_at')
            ->join('recipient_payment_sessions', 'recipient_payment_sessions.id', '=', 'recipient_payment_session_entries.recipient_payment_session_id')
            ->whereIn('recipient_payment_sessions.payment_wallet_id', $walletIds->values()->all())
            ->where('recipient_payment_session_entries.entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
            ->whereBetween('recipient_payment_session_entries.created_at', [$dateFrom, $dateTo])
            ->groupBy('recipient_payment_sessions.payment_wallet_id')
            ->get()
            ->keyBy('payment_wallet_id');
    }

    private function taskFilteredQuery(Request $request, ?Warehouse $warehouse): Builder
    {
        $query = $this->recipientPayments->queueQuery($warehouse);

        if ($group = $request->input('group')) {
            $query->where('payment_group', $group);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($request->boolean('assigned_to_me')) {
            $query->where('assigned_to_user_id', $this->user()?->id);
        } elseif ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', (int) $request->input('assigned_to_user_id'));
        }
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('delivery_town', 'like', "%{$search}%")
                    ->orWhereHas('shipmentItem', fn (Builder $itemQuery) => $itemQuery
                        ->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem.shipment', fn (Builder $shipmentQuery) => $shipmentQuery
                        ->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function reportFilteredQuery(Request $request, ?Warehouse $warehouse): Builder
    {
        $query = $this->recipientPayments->queueQuery($warehouse)
            ->with([
                'warehouse:id,name,code',
                'assignedTo:id,name',
                'paymentWallet:id,name,provider,phone_number',
                'callAttempts',
                'shipmentCharge',
                'sessionEntries.session:id,status,started_at,closed_at,opening_balance,closing_balance,variance',
            ]);

        [$createdFrom, $createdTo] = $this->reportDateRange($request, 'created');
        if ($createdFrom && $createdTo) {
            $query->whereBetween('created_at', [$createdFrom, $createdTo]);
        }

        [$paidFrom, $paidTo] = $this->reportDateRange($request, 'paid');
        if ($paidFrom && $paidTo) {
            $query->whereBetween('paid_at', [$paidFrom, $paidTo]);
        }

        [$callFrom, $callTo] = $this->reportDateRange($request, 'call');
        if ($callFrom && $callTo) {
            $query->whereHas('callAttempts', fn (Builder $callQuery) => $callQuery->whereBetween('attempted_at', [$callFrom, $callTo]));
        }

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        } elseif ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if (!$this->canRecipientPayment('reconcile')) {
            $query->where('assigned_to_user_id', $this->user()?->id);
        } elseif ($request->filled('agent_id')) {
            $query->where('assigned_to_user_id', (int) $request->input('agent_id'));
        }

        if ($group = $request->input('group')) {
            $query->where('payment_group', $group);
        }

        if ($request->filled('wallet_id')) {
            $query->where('payment_wallet_id', (int) $request->input('wallet_id'));
        }

        if ($request->filled('recipient_phone')) {
            $query->where('recipient_phone', 'like', '%' . trim((string) $request->input('recipient_phone')) . '%');
        }

        if ($request->filled('delivery_method')) {
            $method = (string) $request->input('delivery_method');
            $query->whereHas('shipmentItem', function (Builder $itemQuery) use ($method) {
                if ($method === 'direct_delivery') {
                    $itemQuery->where(function (Builder $methodQuery) {
                        $methodQuery->whereNull('delivery_method')
                            ->orWhere('delivery_method', 'direct_delivery')
                            ->orWhere('delivery_method', 'delivery');
                    });
                    return;
                }

                $itemQuery->where('delivery_method', $method);
            });
        }

        if ($request->filled('call_result')) {
            $callResult = (string) $request->input('call_result');
            if ($callResult === 'not_called') {
                $query->whereDoesntHave('callAttempts');
            } else {
                $query->whereHas('callAttempts', fn (Builder $callQuery) => $callQuery->where('outcome', $callResult));
            }
        }

        if ($request->filled('payment_status')) {
            match ((string) $request->input('payment_status')) {
                'paid' => $query->where('status', RecipientPaymentTask::STATUS_PAID),
                'waived' => $query->where('status', RecipientPaymentTask::STATUS_WAIVED),
                'overridden' => $query->where('status', RecipientPaymentTask::STATUS_OVERRIDDEN),
                'no_fee' => $query->whereNull('shipment_charge_id')->whereNull('negotiated_amount'),
                'due' => $query->whereNotIn('status', [
                    RecipientPaymentTask::STATUS_PAID,
                    RecipientPaymentTask::STATUS_WAIVED,
                    RecipientPaymentTask::STATUS_OVERRIDDEN,
                    RecipientPaymentTask::STATUS_CANCELLED,
                ]),
                default => null,
            };
        }

        if ($request->filled('amount_min')) {
            $query->where(function (Builder $amountQuery) use ($request) {
                $amountQuery->where('negotiated_amount', '>=', (float) $request->input('amount_min'))
                    ->orWhereHas('shipmentCharge', fn (Builder $chargeQuery) => $chargeQuery->where('amount', '>=', (float) $request->input('amount_min')));
            });
        }

        if ($request->filled('amount_max')) {
            $query->where(function (Builder $amountQuery) use ($request) {
                $amountQuery->where('negotiated_amount', '<=', (float) $request->input('amount_max'))
                    ->orWhereHas('shipmentCharge', fn (Builder $chargeQuery) => $chargeQuery->where('amount', '<=', (float) $request->input('amount_max')));
            });
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('delivery_town', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('paymentWallet', fn (Builder $walletQuery) => $walletQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"))
                    ->orWhereHas('assignedTo', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sortBatch', fn (Builder $batchQuery) => $batchQuery->where('batch_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', fn (Builder $itemQuery) => $itemQuery
                        ->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem.shipment', fn (Builder $shipmentQuery) => $shipmentQuery
                        ->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function reportDateRange(Request $request, string $prefix): array
    {
        $timezone = config('app.timezone', 'UTC');
        $fromKey = "{$prefix}_date_from";
        $toKey = "{$prefix}_date_to";

        if (!$request->filled($fromKey) && !$request->filled($toKey)) {
            return [null, null];
        }

        $dateFrom = $request->filled($fromKey)
            ? Carbon::parse($request->input($fromKey), $timezone)->startOfDay()
            : Carbon::parse($request->input($toKey), $timezone)->startOfDay();
        $dateTo = $request->filled($toKey)
            ? Carbon::parse($request->input($toKey), $timezone)->endOfDay()
            : Carbon::parse($request->input($fromKey), $timezone)->endOfDay();

        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom->timezone('UTC'), $dateTo->timezone('UTC')];
    }

    private function serializeReportTask(RecipientPaymentTask $task): array
    {
        $data = $this->serializeTask($task);
        $task->loadMissing([
            'warehouse:id,name,code',
            'paymentWallet:id,name,provider,phone_number',
            'sessionEntries.session:id,status,started_at,closed_at,opening_balance,closing_balance,variance',
        ]);
        $entry = $task->sessionEntries
            ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
            ->sortByDesc('created_at')
            ->first();
        $session = $entry?->session;

        return $data + [
            'warehouse' => $task->warehouse?->name,
            'warehouse_id' => $task->warehouse_id,
            'payment_group_label' => $task->payment_group === RecipientPaymentTask::GROUP_LOCAL_DELIVERY ? 'Local Delivery' : 'Warehouse Transfer',
            'delivery_method_label' => $this->deliveryMethodLabel($data['delivery_method'] ?? null),
            'payment_status_label' => $this->reportPaymentStatusLabel($task),
            'wallet_phone' => $task->paymentWallet?->phone_number,
            'receipt_path' => $data['payment_receipt_path'] ?: $entry?->receipt_path,
            'receipt_url' => $data['payment_receipt_url'] ?: ($entry?->receipt_path ? Storage::disk('public')->url($entry->receipt_path) : null),
            'has_receipt' => (bool) ($data['payment_receipt_path'] ?: $entry?->receipt_path),
            'session' => $session ? 'Session #' . $session->id : null,
            'session_status' => $session?->status,
            'session_started_at' => $session?->started_at?->format('Y-m-d H:i:s'),
            'session_closed_at' => $session?->closed_at?->format('Y-m-d H:i:s'),
            'session_variance' => $session?->variance !== null ? (float) $session->variance : null,
            'created_at' => $task->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $task->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function reportSummary(Collection $tasks): array
    {
        $statusFor = fn (RecipientPaymentTask $task) => $this->effectivePaymentStatus($task);

        return [
            'total_tasks' => $tasks->count(),
            'recipients' => $tasks->pluck('recipient_phone')->filter()->unique()->count(),
            'packages' => $tasks->count(),
            'paid' => $tasks->filter(fn (RecipientPaymentTask $task) => $statusFor($task) === RecipientPaymentTask::STATUS_PAID)->count(),
            'due' => $tasks->reject(fn (RecipientPaymentTask $task) => in_array($statusFor($task), [
                RecipientPaymentTask::STATUS_PAID,
                RecipientPaymentTask::STATUS_WAIVED,
                RecipientPaymentTask::STATUS_OVERRIDDEN,
                RecipientPaymentTask::STATUS_CANCELLED,
            ], true))->count(),
            'waived' => $tasks->filter(fn (RecipientPaymentTask $task) => $statusFor($task) === RecipientPaymentTask::STATUS_WAIVED)->count(),
            'overridden' => $tasks->filter(fn (RecipientPaymentTask $task) => $statusFor($task) === RecipientPaymentTask::STATUS_OVERRIDDEN)->count(),
            'total_delivery_fee' => (float) $tasks->sum(fn (RecipientPaymentTask $task) => (float) ($task->shipmentCharge?->amount ?? $task->negotiated_amount ?? 0)),
            'total_paid' => (float) $tasks->filter(fn (RecipientPaymentTask $task) => $statusFor($task) === RecipientPaymentTask::STATUS_PAID)->sum(fn (RecipientPaymentTask $task) => (float) ($task->shipmentCharge?->amount ?? $task->negotiated_amount ?? 0)),
        ];
    }

    private function reportPaymentStatusLabel(RecipientPaymentTask $task): string
    {
        return match ($this->effectivePaymentStatus($task)) {
            RecipientPaymentTask::STATUS_PAID => 'Paid',
            RecipientPaymentTask::STATUS_WAIVED => 'Waived',
            RecipientPaymentTask::STATUS_OVERRIDDEN => 'Override',
            default => ($task->shipment_charge_id || $task->negotiated_amount !== null ? 'Due' : 'No fee set'),
        };
    }

    private function deliveryMethodLabel(?string $method): string
    {
        return match ($method) {
            'bus_handoff' => 'Bus station handoff',
            'pickup' => 'Self pickup',
            default => 'Direct delivery',
        };
    }

    private function sessionsFilteredQuery(Request $request, ?Warehouse $warehouse): Builder
    {
        [$dateFrom, $dateTo] = $this->walletStatsDateRange($request);
        $includeOpen = $request->boolean('include_open');
        $query = RecipientPaymentSession::query()
            ->with(['user:id,name', 'paymentWallet:id,name,provider,phone_number', 'warehouse:id,name,code']);

        $query->where(function (Builder $query) use ($dateFrom, $dateTo, $includeOpen) {
            $query->whereBetween('started_at', [$dateFrom, $dateTo]);

            if ($includeOpen) {
                $query->orWhere('status', RecipientPaymentSession::STATUS_OPEN);
            }
        });

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        } elseif ($request->filled('session_warehouse_id')) {
            $sessionWarehouseId = (string) $request->input('session_warehouse_id');
            if (ctype_digit($sessionWarehouseId)) {
                $query->where('warehouse_id', (int) $sessionWarehouseId);
            }
        }

        if (!$this->canRecipientPayment('reconcile')) {
            $query->where('user_id', $this->user()->id);
        } elseif ($request->filled('agent_id')) {
            $query->where('user_id', (int) $request->input('agent_id'));
        }

        if ($request->filled('wallet_id')) {
            $query->where('payment_wallet_id', (int) $request->input('wallet_id'));
        }

        if ($status = trim((string) $request->input('status'))) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('paymentWallet', fn (Builder $walletQuery) => $walletQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('provider', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"))
                    ->orWhereHas('warehouse', fn (Builder $warehouseQuery) => $warehouseQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function serializeSession(RecipientPaymentSession $session): array
    {
        $expectedClosingBalance = $session->expected_closing_balance;

        if ($expectedClosingBalance === null && $session->status === RecipientPaymentSession::STATUS_OPEN) {
            $incoming = (float) $session->entries()
                ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
                ->sum('amount');
            $adjustments = (float) $session->entries()
                ->where('entry_type', RecipientPaymentSessionEntry::TYPE_ADJUSTMENT)
                ->sum('amount');
            $expectedClosingBalance = round((float) $session->opening_balance + $incoming - $adjustments, 2);
        }

        return [
            'id' => $session->id,
            'agent' => $session->user?->name,
            'agent_id' => $session->user_id,
            'wallet' => $session->paymentWallet?->name,
            'wallet_id' => $session->payment_wallet_id,
            'wallet_provider' => $session->paymentWallet?->provider,
            'wallet_phone' => $session->paymentWallet?->phone_number,
            'warehouse' => $session->warehouse?->name,
            'warehouse_id' => $session->warehouse_id,
            'opening_balance' => (float) $session->opening_balance,
            'closing_balance' => $session->closing_balance !== null ? (float) $session->closing_balance : null,
            'expected_closing_balance' => $expectedClosingBalance !== null ? (float) $expectedClosingBalance : null,
            'variance' => $session->variance !== null ? (float) $session->variance : null,
            'status' => $session->status,
            'started_at' => $session->started_at?->format('Y-m-d H:i:s'),
            'closed_at' => $session->closed_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function walletsQuery(?Warehouse $warehouse): Builder
    {
        return PaymentWallet::query()
            ->when($warehouse, fn (Builder $q) => $q->where(function (Builder $walletQuery) use ($warehouse) {
                $walletQuery->whereNull('warehouse_id')->orWhere('warehouse_id', $warehouse->id);
            }));
    }

    private function reportWalletsForAgent(?Warehouse $warehouse): Collection
    {
        $user = $this->user();
        if (!$user) {
            return collect();
        }

        return PaymentWallet::query()
            ->withTrashed()
            ->where(function (Builder $query) use ($user) {
                $query->whereHas('assignedUsers', fn (Builder $assignedQuery) => $assignedQuery->whereKey($user->id))
                    ->orWhereHas('sessions', fn (Builder $sessionQuery) => $sessionQuery->where('user_id', $user->id))
                    ->orWhereHas('sessions.entries', fn (Builder $entryQuery) => $entryQuery->where('recorded_by_user_id', $user->id));
            })
            ->when($warehouse, fn (Builder $query) => $query->where(function (Builder $walletQuery) use ($warehouse) {
                $walletQuery->whereNull('warehouse_id')
                    ->orWhere('warehouse_id', $warehouse->id)
                    ->orWhereHas('sessions', fn (Builder $sessionQuery) => $sessionQuery->where('warehouse_id', $warehouse->id));
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'provider', 'phone_number', 'account_owner', 'warehouse_id', 'is_active', 'deleted_at']);
    }

    private function workersQuery(?Warehouse $warehouse): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->when($warehouse, fn (Builder $q) => $q->where('warehouse_id', $warehouse->id))
            ->whereHas('roles.permissions', fn (Builder $q) => $q->where('name', $this->recipientPaymentPermission('process')))
            ->orderBy('name');
    }

    private function isWarehouseRoute(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'warehouse.');
    }

    private function user(): ?User
    {
        return Auth::guard('admin')->user();
    }

    private function authorizePermission(string $permission): void
    {
        if (str_starts_with($permission, 'recipient_payments.')) {
            $permission = $this->scopedRecipientPaymentPermission($permission);
        }

        if (!$this->user()?->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function canRecipientPayment(string $ability): bool
    {
        return $this->user()?->hasPermission($this->recipientPaymentPermission($ability)) ?? false;
    }

    private function recipientPaymentPermission(string $ability): string
    {
        return $this->isWarehouseRoute(request())
            ? "warehouse.recipient_payments.{$ability}"
            : "recipient_payments.{$ability}";
    }

    private function scopedRecipientPaymentPermission(string $permission): string
    {
        if (!$this->isWarehouseRoute(request())) {
            return $permission;
        }

        return 'warehouse.' . $permission;
    }
}
