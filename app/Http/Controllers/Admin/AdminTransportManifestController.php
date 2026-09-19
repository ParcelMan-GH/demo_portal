<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BatchDestinationType;
use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\OutgoingBatch;
use App\Models\ShipmentItem;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\Warehouse;
use App\Services\BackOfficeAccess;
use App\Services\OutgoingBatchPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminTransportManifestController extends Controller
{
    public function __construct(
        private readonly BackOfficeAccess $access,
        private readonly OutgoingBatchPackageService $packageService,
    ) {}

    // ==========================================
    // OUTGOING BATCHES / TRANSFERS
    // ==========================================

    public function index(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouses = $this->access->warehousesFor($user, 'warehouse');
        $drivers = Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
        $transportDrivers = $drivers;

        $transferBatches = OutgoingBatch::with(['shipmentItems'])
            ->withCount('shipmentItems')
            ->orderByDesc('id')
            ->get();

        $statuses = [
            ['value' => 'open', 'label' => 'Open'],
            ['value' => 'in_transit', 'label' => 'In Transit'],
            ['value' => 'dispatched', 'label' => 'Dispatched'],
        ];

        return view('admin.transport-manifests.index', compact(
            'warehouses', 
            'drivers', 
            'transportDrivers',
            'statuses', 
            'transferBatches'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_region_id'   => ['required', 'integer'],
            'delivery_district_id' => ['required', 'integer'],
            'destination_type'     => ['nullable', 'string', 'in:' . implode(',', array_column(BatchDestinationType::toArray(), 'value'))],
        ]);

        $batch = OutgoingBatch::create([
            'batch_number'         => 'BATCH-' . strtoupper(Str::random(6)),
            'delivery_region_id'   => $validated['delivery_region_id'],
            'delivery_district_id' => $validated['delivery_district_id'],
            'destination_type'     => $validated['destination_type'] ?? null,
            'status'               => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Batch {$batch->batch_number} created successfully!",
            'data'    => $batch,
        ]);
    }

    /**
     * Detail view for a single batch: its metadata plus every package in it.
     *
     * The route for this has existed for a while but the method was never
     * written, so clicking a batch in the list returned a 500. It now backs the
     * "click a batch to open it" flow.
     */
    public function show(OutgoingBatch $manifest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'batch'    => $this->packageService->summary($manifest),
                'packages' => $this->packageService->assignedItems($manifest),
            ],
        ]);
    }

    /**
     * Packages that can be added to this batch, with per-package eligibility so
     * the picker can disable what the batch will not accept.
     */
    public function availablePackages(Request $request, OutgoingBatch $batch): JsonResponse
    {
        $search = (string) $request->get('search', '');
        $limit  = (int) $request->get('limit', 100);

        return response()->json([
            'success' => true,
            'data'    => [
                'batch'      => $this->packageService->summary($batch),
                'candidates' => $this->packageService->candidates($batch, $search, $limit),
            ],
        ]);
    }

    /**
     * Add packages to a batch.
     *
     * The commerce rule is enforced here as well as in the picker — the client
     * check is a convenience, this one is the guarantee.
     */
    public function addPackages(Request $request, OutgoingBatch $batch): JsonResponse
    {
        // The messages are supplied so an empty selection reads the same whether
        // it is caught here or by the service below, instead of surfacing
        // Laravel's raw "The package ids field is required."
        $validated = $request->validate([
            'package_ids'   => ['required', 'array', 'min:1'],
            'package_ids.*' => ['integer'],
        ], [
            'package_ids.required' => OutgoingBatchPackageService::ERROR_NONE_SELECTED,
            'package_ids.min'      => OutgoingBatchPackageService::ERROR_NONE_SELECTED,
        ]);

        $result = $this->packageService->addItems($batch, $validated['package_ids']);

        $batch->refresh();

        return response()->json([
            'success' => true,
            'message' => $result['added'] === 1
                ? '1 package added to the batch.'
                : "{$result['added']} packages added to the batch.",
            'data'    => [
                'batch'    => $this->packageService->summary($batch),
                'packages' => $result['items'],
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = OutgoingBatch::with(['shipmentItems'])->withCount('shipmentItems');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhere('delivery_region_id', 'like', "%{$search}%")
                  ->orWhere('delivery_district_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFilter = $request->get('date_filter')) {
            if ($dateFilter === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $page    = max((int) $request->get('page', 1), 1);
        $offset  = ($page - 1) * $perPage;

        $total   = $query->count();
        $batches = $query->orderBy('created_at', 'desc')->skip($offset)->take($perPage)->get();

        $data = $batches->map(function (OutgoingBatch $batch) {
            $itemsCount = $batch->shipment_items_count ?? $batch->shipmentItems->count();

            return [
                'id'                    => $batch->id,
                'manifest_number'       => $batch->batch_number,
                'batch_number'          => $batch->batch_number,
                'status'                => $batch->status,
                'status_label'          => ucfirst(str_replace('_', ' ', $batch->status)),
                'destination_type'      => $batch->destination_type,
                'destination_type_label' => $batch->destinationTypeLabel(),
                'accepts_only_commerce' => $batch->acceptsOnlyCommerce(),
                'destination_warehouse' => "Region #{$batch->delivery_region_id} / District #{$batch->delivery_district_id}",
                'driver_name'           => null,
                'driver_phone'          => null,
                'items_count'           => $itemsCount,
                'created_at'            => $batch->created_at->format('Y-m-d H:i:s'),
                'dispatched_at'         => $batch->status === 'dispatched' ? $batch->updated_at->format('Y-m-d H:i:s') : '—',
            ];
        });

        $cardCounts = [];
        if ($regionIdsParam = $request->get('region_ids')) {
            $regionIds = explode(',', $regionIdsParam);
            foreach ($regionIds as $rId) {
                $cardCounts[$rId] = OutgoingBatch::where('delivery_region_id', $rId)
                    ->withCount('shipmentItems')
                    ->get()
                    ->sum('shipment_items_count');
            }
        }

        return response()->json([
            'data'       => $data,
            'cardCounts' => $cardCounts,
            'meta'       => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage) ?: 1,
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function dispatchBatch(Request $request, OutgoingBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer'],
        ]);

        $batch->update([
            'status'              => 'dispatched',
            'transport_driver_id' => $validated['driver_id'] ?? null,
        ]);

        $batch->shipmentItems()->update([
            'status' => \App\Enums\ItemStatus::IN_TRANSIT->value ?? 'in_transit',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Batch {$batch->batch_number} closed and dispatched to transport.",
        ]);
    }

    /**
     * Close and dispatch an outgoing batch to transport.
     *
     * The Outgoing Batches screen posts here when the operator confirms
     * "Close & Dispatch". The batch is closed (status dispatched), an optional
     * transporter is stored, and every package on the batch moves to
     * "In Transit" so receiving hubs see it as travelling.
     */
    public function dispatch(Request $request, OutgoingBatch $manifest): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer'],
        ]);

        $alreadyDispatched = in_array($manifest->status, ['dispatched', 'received'], true);

        if (! empty($validated['driver_id'])) {
            $manifest->transport_driver_id = $validated['driver_id'];
        }

        if (! $alreadyDispatched) {
            $manifest->status = 'dispatched';
        }

        $manifest->save();

        if (! $alreadyDispatched) {
            $manifest->shipmentItems()->update([
                'status' => ItemStatus::IN_TRANSIT->value,
            ]);
        }

        // The mobile app dispatches and loads through transport manifests, so make
        // sure this batch has one the transporter can scan.
        try {
            $transportManifest = $this->bridgeBatchToTransportManifest($manifest);
        } catch (\Throwable $e) {
            logger()->error('Could not bridge outgoing batch to a transport manifest', [
                'batch' => $manifest->batch_number,
                'error' => $e->getMessage(),
            ]);

            $transportManifest = null;
        }

        $message = $alreadyDispatched
            ? "Batch {$manifest->batch_number} was already dispatched."
            : "Batch {$manifest->batch_number} closed and dispatched to transport.";

        $message .= $transportManifest
            ? " Transport manifest {$transportManifest->manifest_number} is ready to scan in the app."
            : ' No destination hub warehouse matched this region, so the app cannot show it yet.';

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => $message,
        ]);
    }

    /**
     * Create (once) the transport manifest the mobile app uses for this batch.
     */
    protected function bridgeBatchToTransportManifest(OutgoingBatch $batch): ?TransportManifest
    {
        $existing = TransportManifest::query()
            ->where('manifest_number', $batch->batch_number)
            ->first();

        if ($existing) {
            return $existing;
        }

        $user = Auth::guard('admin')->user();
        $origin = $this->resolveOriginWarehouse($user);
        $destination = $this->resolveDestinationWarehouse($batch);

        if (! $origin || ! $destination) {
            return null;
        }

        $driverId = $batch->transport_driver_id ?: null;
        $now = now();

        $columns = Schema::getColumnListing('transport_manifests');

        $attributes = array_filter([
            'manifest_number' => $batch->batch_number,
            'origin_warehouse_id' => $origin->id,
            'destination_warehouse_id' => $destination->id,
            'assigned_driver_id' => $driverId,
            'assigned_at' => $driverId ? $now : null,
            'status' => $driverId ? TransportManifest::STATUS_IN_TRANSIT : TransportManifest::STATUS_DRAFT,
            'dispatched_at' => $driverId ? $now : null,
            'created_by_user_id' => $user?->id,
            'notes' => 'Created automatically from outgoing batch '.$batch->batch_number.'.',
        ], fn ($value) => $value !== null);

        $attributes = array_intersect_key($attributes, array_flip($columns));

        $manifest = TransportManifest::query()->create($attributes);

        $items = ShipmentItem::query()
            ->where('outgoing_batch_id', $batch->id)
            ->get();

        foreach ($items as $item) {
            $quantity = max(1, (int) $item->quantity);

            TransportManifestItem::query()->create([
                'transport_manifest_id' => $manifest->id,
                'shipment_item_id' => $item->id,
                'expected_quantity' => $quantity,
                'loaded_quantity' => $quantity,
                'line_status' => TransportManifestItem::LINE_LOADED,
                'loaded_at' => $now,
            ]);
        }

        logger()->info('Bridged outgoing batch to transport manifest', [
            'batch' => $batch->batch_number,
            'manifest_id' => $manifest->id,
            'items' => $items->count(),
        ]);

        return $manifest->fresh();
    }

    protected function resolveOriginWarehouse(?object $user): ?Warehouse
    {
        if ($user?->warehouse_id) {
            $warehouse = Warehouse::query()->find($user->warehouse_id);

            if ($warehouse) {
                return $warehouse;
            }
        }

        return $this->access->warehousesFor($user, 'warehouse')->first();
    }

    protected function resolveDestinationWarehouse(OutgoingBatch $batch): ?Warehouse
    {
        $base = Warehouse::query()
            ->where('is_active', true)
            ->where('region_id', $batch->delivery_region_id);

        // Older databases do not have every warehouse column, so filter only when present.
        if (Schema::hasColumn('warehouses', 'type')) {
            $base->whereIn('type', ['destination', 'both']);
        }

        if (Schema::hasColumn('warehouses', 'district_id') && $batch->delivery_district_id) {
            $sameDistrict = (clone $base)->where('district_id', $batch->delivery_district_id)->orderBy('id')->first();

            if ($sameDistrict) {
                return $sameDistrict;
            }
        }

        return (clone $base)->orderBy('id')->first();
    }

    // ==========================================
    // INCOMING BATCHES / TRANSFERS
    // ==========================================

    public function incomingIndex(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouses = $this->access->warehousesFor($user, 'warehouse');
        $originWarehouses = Warehouse::all(['id', 'name']);

        return view('warehouse.manifests.incoming.index', [
            'warehouse'        => $warehouses->first(),
            'originWarehouses' => $originWarehouses,
            'layoutName'       => 'warehouse.layouts.app',
            'pageTitle'        => 'Incoming Transfers',
            'dataEndpoint'     => route('admin.transport-manifests.incoming.data'),
            'receiveEndpoint'  => route('admin.transport-manifests.receive', ['batch' => '__BATCH__']),
        ]);
    }

    public function incomingData(Request $request): JsonResponse
    {
        $query = OutgoingBatch::with(['shipmentItems'])
            ->withCount('shipmentItems')
            ->whereIn('status', ['dispatched', 'in_transit', 'received']);

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhere('delivery_region_id', 'like', "%{$search}%")
                  ->orWhere('delivery_district_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFilter = $request->get('date_filter')) {
            if ($dateFilter === 'today') {
                $query->whereDate('updated_at', today());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $page    = max((int) $request->get('page', 1), 1);
        $offset  = ($page - 1) * $perPage;

        $total   = $query->count();
        $batches = $query->orderBy('updated_at', 'desc')->skip($offset)->take($perPage)->get();

        $data = $batches->map(function (OutgoingBatch $batch) {
            $itemsCount = $batch->shipment_items_count ?? $batch->shipmentItems->count();

            return [
                'id'              => $batch->id,
                'batch_number'    => $batch->batch_number,
                'manifest_number' => $batch->batch_number,
                'status'          => $batch->status,
                'status_label'    => ucfirst(str_replace('_', ' ', $batch->status)),
                'origin_context'  => "Region #{$batch->delivery_region_id} / District #{$batch->delivery_district_id}",
                'items_count'     => $itemsCount,
                'dispatched_at'   => $batch->updated_at->format('Y-m-d H:i:s'),
                'can_receive'     => in_array($batch->status, ['dispatched', 'in_transit']),
                'view_url'        => '#',
            ];
        });

        $cardCounts = [];
        if ($regionIdsParam = $request->get('region_ids')) {
            $regionIds = explode(',', $regionIdsParam);
            foreach ($regionIds as $rId) {
                $cardCounts[$rId] = OutgoingBatch::where('delivery_region_id', $rId)
                    ->whereIn('status', ['dispatched', 'in_transit'])
                    ->withCount('shipmentItems')
                    ->get()
                    ->sum('shipment_items_count');
            }
        }

        return response()->json([
            'data'       => $data,
            'cardCounts' => $cardCounts,
            'meta'       => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage) ?: 1,
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function scanIncomingPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $item = ShipmentItem::where('tracking_code', $validated['code'])->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Package tracking code not found.'
            ], 404);
        }

        $item->update([
            'status' => \App\Enums\ItemStatus::AT_WAREHOUSE->value ?? 'at_warehouse'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Package {$item->tracking_code} received successfully into inventory!",
        ]);
    }

    public function receiveBatch(Request $request, OutgoingBatch $batch): JsonResponse
    {
        $batch->update(['status' => 'received']);

        $batch->shipmentItems()->update([
            'status' => \App\Enums\ItemStatus::AT_WAREHOUSE->value ?? 'at_warehouse'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Batch {$batch->batch_number} unsealed and received into hub inventory!",
        ]);
    }
}