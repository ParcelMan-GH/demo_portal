<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\OutgoingBatch;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Services\BackOfficeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTransportManifestController extends Controller
{
    public function __construct(
        private readonly BackOfficeAccess $access,
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
        ]);

        $batch = OutgoingBatch::create([
            'batch_number'         => 'BATCH-' . strtoupper(Str::random(6)),
            'delivery_region_id'   => $validated['delivery_region_id'],
            'delivery_district_id' => $validated['delivery_district_id'],
            'status'               => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Batch {$batch->batch_number} created successfully!",
            'data'    => $batch,
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