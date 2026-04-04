<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\SortBatch;
use App\Models\Warehouse;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehouseSortingService;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminDeliveryRunController extends Controller
{
    /**
     * Display the delivery runs index page.
     */
    public function index(): View
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);
        $drivers = Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);

        $statuses = [
            ['value' => DeliveryRun::STATUS_DRAFT,               'label' => 'Draft'],
            ['value' => DeliveryRun::STATUS_ASSIGNED,            'label' => 'Assigned'],
            ['value' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,    'label' => 'Out for Delivery'],
            ['value' => DeliveryRun::STATUS_PARTIALLY_DELIVERED, 'label' => 'Partially Delivered'],
            ['value' => DeliveryRun::STATUS_COMPLETED,           'label' => 'Completed'],
            ['value' => DeliveryRun::STATUS_CANCELLED,           'label' => 'Cancelled'],
        ];

        return view('admin.delivery-runs.index', compact('warehouses', 'drivers', 'statuses'));
    }

    /**
     * Return paginated delivery runs data as JSON (AJAX datatable endpoint).
     */
    public function data(Request $request): JsonResponse
    {
        $query = DeliveryRun::with(['warehouse', 'assignedDriver'])
            ->withCount(['stops', 'items']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('run_number', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignedDriver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by warehouse
        if ($warehouseId = $request->get('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Filter by date range (on created_at)
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sorting
        $sortBy        = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts  = ['run_number', 'status', 'created_at', 'assigned_at', 'dispatched_at', 'completed_at', 'stops_count', 'items_count'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 25), 100);
        $page    = (int) $request->get('page', 1);
        $offset  = ($page - 1) * $perPage;

        $total = $query->count();
        $runs  = $query->skip($offset)->take($perPage)->get();

        $data = $runs->map(function (DeliveryRun $run) {
            return [
                'id'              => $run->id,
                'run_number'      => $run->run_number,
                'status'          => $run->status,
                'status_label'    => $this->formatStatusLabel($run->status),
                'warehouse_name'  => $run->warehouse?->name,
                'warehouse_code'  => $run->warehouse?->code,
                'driver_name'     => $run->assignedDriver?->name,
                'driver_phone'    => $run->assignedDriver?->phone,
                'stops_count'     => $run->stops_count,
                'items_count'     => $run->items_count,
                'assigned_at'     => $run->assigned_at?->format('Y-m-d H:i:s'),
                'dispatched_at'   => $run->dispatched_at?->format('Y-m-d H:i:s'),
                'completed_at'    => $run->completed_at?->format('Y-m-d H:i:s'),
                'created_at'      => $run->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage) ?: 1,
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Display the delivery run detail page.
     */
    public function show(DeliveryRun $run): View
    {
        $run->load([
            'warehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'stops.items.shipmentItem.shipment',
            'stops.verificationAttempts',
        ]);

        $statusLabel = $this->formatStatusLabel($run->status);

        return view('admin.delivery-runs.show', compact('run', 'statusLabel'));
    }

    /**
     * Export delivery runs data (JSON / Excel / PDF).
     */
    public function export(Request $request)
    {
        $query = DeliveryRun::with(['warehouse', 'assignedDriver'])
            ->withCount(['stops', 'items']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('run_number', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', fn($wq) => $wq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedDriver', fn($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($warehouseId = $request->get('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $runs = $query->orderBy('created_at', 'desc')->get();

        $rows = $runs->map(fn(DeliveryRun $run) => [
            'Run #'          => $run->run_number,
            'Warehouse'      => $run->warehouse?->name ?? '—',
            'Driver'         => $run->assignedDriver?->name ?? '—',
            'Driver Phone'   => $run->assignedDriver?->phone ?? '—',
            'Stops'          => $run->stops_count,
            'Items'          => $run->items_count,
            'Status'         => $this->formatStatusLabel($run->status),
            'Dispatched At'  => $run->dispatched_at?->format('Y-m-d H:i:s') ?? '—',
            'Completed At'   => $run->completed_at?->format('Y-m-d H:i:s') ?? '—',
            'Created At'     => $run->created_at->format('Y-m-d H:i:s'),
        ])->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'delivery_runs_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'delivery_runs_' . date('Y-m-d_His') . '.pdf', 'Delivery Runs');
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Convert a status string into a human-readable label.
     */
    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            DeliveryRun::STATUS_DRAFT               => 'Draft',
            DeliveryRun::STATUS_ASSIGNED            => 'Assigned',
            DeliveryRun::STATUS_OUT_FOR_DELIVERY    => 'Out for Delivery',
            DeliveryRun::STATUS_PARTIALLY_DELIVERED => 'Partially Delivered',
            DeliveryRun::STATUS_COMPLETED           => 'Completed',
            DeliveryRun::STATUS_CANCELLED           => 'Cancelled',
            default                                 => ucwords(str_replace('_', ' ', $status)),
        };
    }

    // ─── ACTIONS (same as warehouse portal) ──────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $batch = SortBatch::findOrFail((int) $validated['sort_batch_id']);
        $warehouse = Warehouse::findOrFail((int) $validated['warehouse_id']);
        $deliveryService = app(WarehouseDeliveryService::class);
        $result = $deliveryService->createRun($batch, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function storeFromItems(Request $request): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'warehouse_receipt_item_ids' => ['required', 'array', 'min:1'],
            'warehouse_receipt_item_ids.*' => ['integer'],
        ]);

        $warehouse = Warehouse::findOrFail((int) $validated['warehouse_id']);
        $deliveryService = app(WarehouseDeliveryService::class);
        $sortingService = app(WarehouseSortingService::class);
        $result = $deliveryService->createRunFromItems($warehouse, Auth::guard('admin')->user(), $validated['warehouse_receipt_item_ids'], $sortingService);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function assignDriver(Request $request, DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $run->update([
            'assigned_driver_id' => (int) $validated['driver_id'],
            'status' => DeliveryRun::STATUS_ASSIGNED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned to delivery run.',
            'data' => ['run' => $run->fresh()],
        ]);
    }

    public function dispatch(DeliveryRun $run): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $deliveryService = app(WarehouseDeliveryService::class);
        $admin = Auth::guard('admin')->user();
        $warehouse = $run->warehouse;

        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'No warehouse found for this run.'], 422);
        }

        $result = $deliveryService->dispatch($run, $warehouse, $admin);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function resendCode(DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $this->authorizePermission('shipments.edit');

        $deliveryService = app(WarehouseDeliveryService::class);
        $warehouse = $run->warehouse;

        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'No warehouse found.'], 422);
        }

        $result = $deliveryService->resendStopCode($run, $stop, $warehouse);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
