<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\TransportManifest;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseTransportReceivingService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransportManifestController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseTransportService $transportService,
        private WarehouseTransportReceivingService $receivingService
    ) {
    }

    public function outboundIndex(): View
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.manifests.transport.index', [
            'warehouse' => $warehouse,
            'transportDrivers' => Driver::query()
                ->where('is_active', true)
                ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']),
            'transferBatches' => SortBatch::query()
                ->where('origin_warehouse_id', $warehouse->id)
                ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
                ->where('status', SortBatch::STATUS_SEALED)
                ->whereDoesntHave('transportManifest')
                ->orderByDesc('id')
                ->get(['id', 'batch_number', 'destination_warehouse_id']),
        ]);
    }

    public function outboundShow(TransportManifest $manifest): View
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->origin_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'items.shipmentItem.shipment.vendor',
        ]);

        $transportDrivers = Driver::query()
            ->where('is_active', true)
            ->whereJsonContains('task_capabilities', Driver::CAPABILITY_TRANSPORT)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

        $itemsData = $manifest->items->map(function ($line) {
            $shipmentItem = $line->shipmentItem;
            $shipment = $shipmentItem?->shipment;
            return [
                'id' => $line->id,
                'description' => $shipmentItem?->description ?? '-',
                'tracking_code' => $shipmentItem?->tracking_code ?? '',
                'shipment_item_id' => $line->shipment_item_id,
                'shipment_number' => $shipment?->shipment_number ?? '-',
                'vendor_name' => $shipment?->vendor?->name ?? '',
                'expected_quantity' => (int) $line->expected_quantity,
                'loaded_quantity' => (int) $line->loaded_quantity,
                'received_quantity' => (int) $line->received_quantity,
                'line_status' => $line->line_status ?? 'pending',
                'notes' => $line->notes ?: '',
                'loaded_at' => $line->loaded_at?->format('M d, H:i'),
                'received_at' => $line->received_at?->format('M d, H:i'),
            ];
        })->values()->toArray();

        $manifestConfig = [
            'assign_driver_endpoint' => route('warehouse.manifests.transport.assign-driver', ['manifest' => $manifest]),
            'dispatch_endpoint' => route('warehouse.manifests.transport.dispatch', ['manifest' => $manifest]),
            'unassign_driver_endpoint' => route('warehouse.manifests.transport.unassign-driver', ['manifest' => $manifest]),
        ];

        $assignmentHistory = $manifest->assignments()
            ->with(['driver:id,name,phone,vehicle_type,vehicle_number', 'assignedBy:id,name', 'unassignedBy:id,name'])
            ->orderByDesc('id')
            ->get();

        return view('warehouse.manifests.transport.show', [
            'warehouse' => $warehouse,
            'manifest' => $manifest,
            'transportDrivers' => $transportDrivers,
            'manifestConfig' => $manifestConfig,
            'itemsData' => $itemsData,
            'assignmentHistory' => $assignmentHistory,
        ]);
    }

    public function outboundData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->transportService->outboundQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('manifest_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('assignedDriver', fn (Builder $driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items.shipmentItem.shipment', fn (Builder $shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['manifest_number', 'status', 'assigned_at', 'dispatched_at', 'arrived_at', 'received_at', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, function (TransportManifest $manifest) {
            return [
                'id' => $manifest->id,
                'manifest_number' => $manifest->manifest_number,
                'status' => $manifest->status,
                'origin_warehouse' => $manifest->originWarehouse?->name,
                'destination_warehouse' => $manifest->destinationWarehouse?->name,
                'driver_name' => $manifest->assignedDriver?->name,
                'driver_phone' => $manifest->assignedDriver?->phone,
                'items_count' => $manifest->items->count(),
                'assigned_at' => optional($manifest->assigned_at)?->format('Y-m-d H:i:s'),
                'dispatched_at' => optional($manifest->dispatched_at)?->format('Y-m-d H:i:s'),
                'arrived_at' => optional($manifest->arrived_at)?->format('Y-m-d H:i:s'),
                'received_at' => optional($manifest->received_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.manifests.transport.show', $manifest),
            ];
        });
    }

    public function create(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.manifest.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
        ]);

        $batch = SortBatch::query()->with('destinationWarehouse')->findOrFail((int) $validated['sort_batch_id']);
        $result = $this->transportService->createManifest($batch, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function assignDriver(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $driver = Driver::query()->findOrFail((int) $validated['driver_id']);
        $result = $this->transportService->assignDriver($manifest, $driver, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function unassignDriver(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->transportService->unassignDriver(
            $manifest,
            $warehouse,
            Auth::guard('admin')->user(),
            $validated['reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function dispatch(TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.transport.assign');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $result = $this->transportService->dispatch($manifest, $warehouse, Auth::guard('admin')->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function incomingIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.manifests.incoming.index', [
            'warehouse' => $warehouse,
        ]);
    }

    public function incomingData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->transportService->incomingQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('manifest_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('originWarehouse', fn (Builder $originQuery) => $originQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedDriver', fn (Builder $driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sort, ['manifest_number', 'status', 'assigned_at', 'arrived_at', 'received_at', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest('id');
        }

        return $this->paginate($query, $request, function (TransportManifest $manifest) {
            return [
                'id' => $manifest->id,
                'manifest_number' => $manifest->manifest_number,
                'status' => $manifest->status,
                'origin_warehouse' => $manifest->originWarehouse?->name,
                'driver_name' => $manifest->assignedDriver?->name,
                'items_count' => $manifest->items->count(),
                'arrived_at' => optional($manifest->arrived_at)?->format('Y-m-d H:i:s'),
                'received_at' => optional($manifest->received_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.manifests.incoming.show', $manifest),
            ];
        });
    }

    public function incomingShow(TransportManifest $manifest): View
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'items.shipmentItem.shipment.vendor',
        ]);

        $config = [
            'scan_receive_endpoint' => route('warehouse.manifests.incoming.items.scan', ['manifest' => $manifest, 'shipmentItem' => '__ITEM__']),
            'finalize_endpoint' => route('warehouse.manifests.incoming.finalize', ['manifest' => $manifest]),
        ];

        return view('warehouse.manifests.incoming.show', [
            'warehouse' => $warehouse,
            'manifest' => $manifest,
            'manifestConfig' => $config,
        ]);
    }

    public function scanIncomingItem(Request $request, TransportManifest $manifest, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:0'],
            'line_status' => ['nullable', 'in:pending,loaded,received,short,excess,damaged'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->receivingService->scanReceive(
            manifest: $manifest,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['received_quantity'],
            lineStatus: $validated['line_status'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function finalizeIncoming(Request $request, TransportManifest $manifest): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $result = $this->receivingService->finalizeReceipt(
            manifest: $manifest,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            notes: $validated['notes'] ?? null
        );

        if ($result['success'] && \App\Models\PlatformSetting::getValue('contact_queue.auto_queue_on_transport_receive', false)) {
            try {
                $itemIds = $manifest->items()->pluck('shipment_item_id')->toArray();
                app(\App\Services\Warehouse\PackageContactService::class)->createTasksForWarehouseItems($warehouse, $itemIds);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-queue on transport receive failed: ' . $e->getMessage());
            }
        }

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

