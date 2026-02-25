<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\TransportManifest;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverTransportService
{
    public function __construct(
        private WarehouseTransportService $warehouseTransportService
    ) {
    }

    public function list(Driver $driver, Request $request): array
    {
        $sortable = ['id', 'manifest_number', 'status', 'assigned_at', 'dispatched_at', 'arrived_at', 'created_at', 'updated_at'];
        $statuses = [
            TransportManifest::STATUS_DRAFT,
            TransportManifest::STATUS_ASSIGNED,
            TransportManifest::STATUS_LOADING,
            TransportManifest::STATUS_IN_TRANSIT,
            TransportManifest::STATUS_ARRIVED,
            TransportManifest::STATUS_RECEIVED,
            TransportManifest::STATUS_CANCELLED,
        ];

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($statuses)],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', Rule::in($sortable)],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = TransportManifest::query()
            ->where('assigned_driver_id', $driver->id)
            ->with([
                'originWarehouse:id,name,code,address,latitude,longitude,contact_phone',
                'destinationWarehouse:id,name,code,address,latitude,longitude,contact_phone',
                'items.shipmentItem.shipment:id,shipment_number',
                'items.shipmentItem:id,shipment_id,description,tracking_code',
            ]);

        if (!empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('manifest_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('items.shipmentItem.shipment', fn ($sq) => $sq->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $limit = (int) ($validated['limit'] ?? 15);
        $offset = (int) ($validated['offset'] ?? 0);

        $total = (clone $query)->count();
        $rows = $query
            ->orderBy($sortBy, $sortOrder)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (TransportManifest $manifest) => $this->transformManifest($manifest))
            ->values()
            ->all();

        $count = count($rows);
        $hasMore = ($offset + $count) < $total;

        return [
            'success' => true,
            'message' => 'Transports retrieved successfully.',
            'data' => [
                'transports' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => $hasMore,
                    'next_offset' => $hasMore ? $offset + $limit : null,
                    'current_page' => (int) floor($offset / $limit) + 1,
                    'last_page' => max((int) ceil($total / $limit), 1),
                    'per_page' => $limit,
                ],
            ],
        ];
    }

    public function show(Driver $driver, TransportManifest $manifest): array
    {
        if ((int) $manifest->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Transport not found.', 'status' => 404];
        }

        $manifest->load([
            'originWarehouse:id,name,code,address,latitude,longitude,contact_phone',
            'destinationWarehouse:id,name,code,address,latitude,longitude,contact_phone',
            'items.shipmentItem.shipment:id,shipment_number',
            'items.shipmentItem:id,shipment_id,description,tracking_code',
        ]);

        return [
            'success' => true,
            'message' => 'Transport retrieved successfully.',
            'data' => ['transport' => $this->transformManifest($manifest)],
            'status' => 200,
        ];
    }

    private function transformManifest(TransportManifest $manifest): array
    {
        return [
            'id' => $manifest->id,
            'manifest_number' => $manifest->manifest_number,
            'status' => $manifest->status,
            'origin_warehouse' => $manifest->originWarehouse ? [
                'id' => $manifest->originWarehouse->id,
                'name' => $manifest->originWarehouse->name,
                'code' => $manifest->originWarehouse->code,
                'address' => $manifest->originWarehouse->address,
                'latitude' => $manifest->originWarehouse->latitude,
                'longitude' => $manifest->originWarehouse->longitude,
                'contact_phone' => $manifest->originWarehouse->contact_phone,
            ] : null,
            'destination_warehouse' => $manifest->destinationWarehouse ? [
                'id' => $manifest->destinationWarehouse->id,
                'name' => $manifest->destinationWarehouse->name,
                'code' => $manifest->destinationWarehouse->code,
                'address' => $manifest->destinationWarehouse->address,
                'latitude' => $manifest->destinationWarehouse->latitude,
                'longitude' => $manifest->destinationWarehouse->longitude,
                'contact_phone' => $manifest->destinationWarehouse->contact_phone,
            ] : null,
            'timeline' => [
                'assigned' => ['at' => $manifest->assigned_at],
                'dispatched' => ['at' => $manifest->dispatched_at],
                'arrived' => ['at' => $manifest->arrived_at],
                'received' => ['at' => $manifest->received_at],
            ],
            'items' => $manifest->items->map(function ($line) {
                return [
                    'shipment_item_id' => $line->shipment_item_id,
                    'shipment_number' => $line->shipmentItem?->shipment?->shipment_number,
                    'description' => $line->shipmentItem?->description,
                    'tracking_code' => $line->shipmentItem?->tracking_code,
                    'expected_quantity' => (int) $line->expected_quantity,
                    'loaded_quantity' => (int) $line->loaded_quantity,
                    'received_quantity' => (int) $line->received_quantity,
                    'line_status' => $line->line_status,
                    'scan_out_count' => (int) $line->scan_out_count,
                    'scan_in_count' => (int) $line->scan_in_count,
                    'loaded_at' => $line->loaded_at,
                    'received_at' => $line->received_at,
                    'notes' => $line->notes,
                ];
            })->values(),
            'notes' => $manifest->notes,
            'created_at' => $manifest->created_at,
            'updated_at' => $manifest->updated_at,
        ];
    }
}

