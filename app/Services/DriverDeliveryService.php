<?php

namespace App\Services;

use App\Models\DeliveryRun;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverDeliveryService
{
    public function list(Driver $driver, Request $request): array
    {
        $statuses = [
            DeliveryRun::STATUS_DRAFT,
            DeliveryRun::STATUS_ASSIGNED,
            DeliveryRun::STATUS_OUT_FOR_DELIVERY,
            DeliveryRun::STATUS_PARTIALLY_DELIVERED,
            DeliveryRun::STATUS_COMPLETED,
            DeliveryRun::STATUS_CANCELLED,
        ];
        $sortable = ['id', 'run_number', 'status', 'assigned_at', 'dispatched_at', 'completed_at', 'created_at', 'updated_at'];

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($statuses)],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', Rule::in($sortable)],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = DeliveryRun::query()
            ->where('assigned_driver_id', $driver->id)
            ->with([
                'warehouse:id,name,code,address,latitude,longitude,contact_phone',
                'stops:id,delivery_run_id,recipient_name,recipient_phone,status,town,landmark,gh_post_address,verification_code_sent_at,verification_code_expires_at,verification_attempts,max_attempts,arrived_at,delivered_at,failure_reason',
                'items:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,expected_quantity,delivered_quantity,status',
                'items.shipmentItem:id,shipment_id,description,tracking_code',
                'items.shipmentItem.shipment:id,shipment_number',
            ]);

        if (!empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('run_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('stops', fn ($sq) => $sq
                        ->where('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_phone', 'like', "%{$search}%")
                    );
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
            ->map(fn (DeliveryRun $run) => $this->transformRun($run))
            ->values()
            ->all();

        $count = count($rows);
        $hasMore = ($offset + $count) < $total;

        return [
            'success' => true,
            'message' => 'Deliveries retrieved successfully.',
            'data' => [
                'deliveries' => $rows,
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

    public function show(Driver $driver, DeliveryRun $run): array
    {
        if ((int) $run->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Delivery run not found.', 'status' => 404];
        }

        $run->load([
            'warehouse:id,name,code,address,latitude,longitude,contact_phone',
            'stops:id,delivery_run_id,recipient_name,recipient_phone,status,region_id,district_id,town,latitude,longitude,gh_post_address,landmark,verification_code_sent_at,verification_code_expires_at,verification_attempts,max_attempts,arrived_at,delivered_at,delivery_latitude,delivery_longitude,failure_reason,failure_notes',
            'stops.region:id,name',
            'stops.district:id,name',
            'items:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,expected_quantity,delivered_quantity,status,notes,delivered_at',
            'items.shipmentItem:id,shipment_id,description,tracking_code',
            'items.shipmentItem.shipment:id,shipment_number',
        ]);

        return [
            'success' => true,
            'message' => 'Delivery run retrieved successfully.',
            'data' => ['delivery' => $this->transformRun($run)],
            'status' => 200,
        ];
    }

    private function transformRun(DeliveryRun $run): array
    {
        return [
            'id' => $run->id,
            'run_number' => $run->run_number,
            'status' => $run->status,
            'warehouse' => $run->warehouse ? [
                'id' => $run->warehouse->id,
                'name' => $run->warehouse->name,
                'code' => $run->warehouse->code,
                'address' => $run->warehouse->address,
                'latitude' => $run->warehouse->latitude,
                'longitude' => $run->warehouse->longitude,
                'contact_phone' => $run->warehouse->contact_phone,
            ] : null,
            'timeline' => [
                'assigned' => ['at' => $run->assigned_at],
                'out_for_delivery' => ['at' => $run->dispatched_at],
                'completed' => ['at' => $run->completed_at],
            ],
            'stops' => $run->stops->map(function ($stop) use ($run) {
                $items = $run->items->where('delivery_run_stop_id', $stop->id)->values();
                return [
                    'id' => $stop->id,
                    'recipient_name' => $stop->recipient_name,
                    'recipient_phone' => $stop->recipient_phone,
                    'status' => $stop->status,
                    'location' => [
                        'region' => $stop->region?->name,
                        'district' => $stop->district?->name,
                        'town' => $stop->town,
                        'latitude' => $stop->latitude,
                        'longitude' => $stop->longitude,
                        'gh_post_address' => $stop->gh_post_address,
                        'landmark' => $stop->landmark,
                    ],
                    'verification' => [
                        'code_sent_at' => $stop->verification_code_sent_at,
                        'code_expires_at' => $stop->verification_code_expires_at,
                        'attempts' => (int) $stop->verification_attempts,
                        'max_attempts' => (int) $stop->max_attempts,
                    ],
                    'timeline' => [
                        'arrived' => ['at' => $stop->arrived_at],
                        'delivered' => ['at' => $stop->delivered_at],
                    ],
                    'failure_reason' => $stop->failure_reason,
                    'failure_notes' => $stop->failure_notes,
                    'items' => $items->map(function ($item) {
                        return [
                            'shipment_item_id' => $item->shipment_item_id,
                            'shipment_number' => $item->shipmentItem?->shipment?->shipment_number,
                            'description' => $item->shipmentItem?->description,
                            'tracking_code' => $item->shipmentItem?->tracking_code,
                            'expected_quantity' => (int) $item->expected_quantity,
                            'delivered_quantity' => (int) $item->delivered_quantity,
                            'status' => $item->status,
                            'notes' => $item->notes,
                            'delivered_at' => $item->delivered_at,
                        ];
                    })->values(),
                ];
            })->values(),
            'notes' => $run->notes,
            'created_at' => $run->created_at,
            'updated_at' => $run->updated_at,
        ];
    }
}

