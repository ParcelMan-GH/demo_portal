<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\TransportManifest;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTransportManifestController extends Controller
{
    /**
     * Display the transport manifests index page.
     */
    public function index(): View
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);
        $drivers    = Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);

        $statuses = [
            ['value' => TransportManifest::STATUS_DRAFT,       'label' => 'Draft'],
            ['value' => TransportManifest::STATUS_ASSIGNED,    'label' => 'Assigned'],
            ['value' => TransportManifest::STATUS_LOADING,     'label' => 'Loading'],
            ['value' => TransportManifest::STATUS_IN_TRANSIT,  'label' => 'In Transit'],
            ['value' => TransportManifest::STATUS_ARRIVED,     'label' => 'Arrived'],
            ['value' => TransportManifest::STATUS_RECEIVED,    'label' => 'Received'],
            ['value' => TransportManifest::STATUS_CANCELLED,   'label' => 'Cancelled'],
        ];

        return view('admin.transport-manifests.index', compact('warehouses', 'drivers', 'statuses'));
    }

    /**
     * Return paginated transport manifests data as JSON (AJAX datatable endpoint).
     */
    public function data(Request $request): JsonResponse
    {
        $query = TransportManifest::with(['originWarehouse', 'destinationWarehouse', 'assignedDriver'])
            ->withCount('items');

        // Search by manifest number
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('manifest_number', 'like', "%{$search}%")
                    ->orWhereHas('assignedDriver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('originWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('destinationWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by origin warehouse
        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        // Filter by destination warehouse
        if ($destinationWarehouseId = $request->get('destination_warehouse_id')) {
            $query->where('destination_warehouse_id', $destinationWarehouseId);
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
        $allowedSorts  = [
            'manifest_number', 'status', 'created_at',
            'dispatched_at', 'arrived_at', 'received_at', 'items_count',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 25), 100);
        $page    = (int) $request->get('page', 1);
        $offset  = ($page - 1) * $perPage;

        $total     = $query->count();
        $manifests = $query->skip($offset)->take($perPage)->get();

        $data = $manifests->map(function (TransportManifest $manifest) {
            return [
                'id'                    => $manifest->id,
                'manifest_number'       => $manifest->manifest_number,
                'status'                => $manifest->status,
                'status_label'          => $this->formatStatusLabel($manifest->status),
                'origin_warehouse'      => $manifest->originWarehouse?->name,
                'origin_warehouse_code' => $manifest->originWarehouse?->code,
                'destination_warehouse'      => $manifest->destinationWarehouse?->name,
                'destination_warehouse_code' => $manifest->destinationWarehouse?->code,
                'driver_name'           => $manifest->assignedDriver?->name,
                'driver_phone'          => $manifest->assignedDriver?->phone,
                'items_count'           => $manifest->items_count,
                'dispatched_at'         => $manifest->dispatched_at?->format('Y-m-d H:i:s'),
                'arrived_at'            => $manifest->arrived_at?->format('Y-m-d H:i:s'),
                'received_at'           => $manifest->received_at?->format('Y-m-d H:i:s'),
                'created_at'            => $manifest->created_at->format('Y-m-d H:i:s'),
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
     * Display the transport manifest detail page.
     */
    public function show(TransportManifest $manifest): View
    {
        $manifest->load([
            'originWarehouse',
            'destinationWarehouse',
            'assignedDriver',
            'sortBatch',
            'createdBy',
            'receivedBy',
            'items.shipmentItem.shipment',
            'warehouseReceipt.startedBy',
            'warehouseReceipt.finalizedBy',
        ]);

        $statusLabel = $this->formatStatusLabel($manifest->status);

        return view('admin.transport-manifests.show', compact('manifest', 'statusLabel'));
    }

    /**
     * Convert a status string into a human-readable label.
     */
    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            TransportManifest::STATUS_DRAFT      => 'Draft',
            TransportManifest::STATUS_ASSIGNED   => 'Assigned',
            TransportManifest::STATUS_LOADING    => 'Loading',
            TransportManifest::STATUS_IN_TRANSIT => 'In Transit',
            TransportManifest::STATUS_ARRIVED    => 'Arrived',
            TransportManifest::STATUS_RECEIVED   => 'Received',
            TransportManifest::STATUS_CANCELLED  => 'Cancelled',
            default                              => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
