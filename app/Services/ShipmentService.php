<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ShipmentService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    /**
     * List shipments for a vendor with filters.
     */
    public function list(Vendor $vendor, array $filters, Request $request): array
    {
        $query = $vendor->shipments()->with(['region', 'district', 'items']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Search by shipment number or recipient name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%");
            });
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        // Paginate
        $perPage = min($filters['per_page'] ?? 15, 100);
        $shipments = $query->paginate($perPage);

        return [
            'success' => true,
            'message' => 'Shipments retrieved successfully.',
            'data' => [
                'shipments' => $this->transformShipments($shipments),
                'pagination' => [
                    'current_page' => $shipments->currentPage(),
                    'last_page' => $shipments->lastPage(),
                    'per_page' => $shipments->perPage(),
                    'total' => $shipments->total(),
                ],
            ],
        ];
    }

    /**
     * Create a new draft shipment.
     */
    public function create(Vendor $vendor, array $data, Request $request): array
    {
        $shipment = $vendor->shipments()->create([
            'recipient_name' => $data['recipient_name'],
            'recipient_phone' => $data['recipient_phone'],
            'region_id' => $data['region_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'town' => $data['town'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'gh_post_address' => $data['gh_post_address'] ?? null,
            'delivery_instructions' => $data['delivery_instructions'] ?? null,
            'landmark' => $data['landmark'] ?? null,
            'status' => ShipmentStatus::DRAFT,
        ]);

        // Load relationships
        $shipment->load(['region', 'district', 'items']);

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $vendor,
            action: 'shipment_created',
            description: "Created shipment {$shipment->shipment_number}",
            request: $request,
            metadata: ['shipment_id' => $shipment->id]
        );

        return [
            'success' => true,
            'message' => 'Shipment created successfully.',
            'data' => [
                'shipment' => $this->transformShipment($shipment),
            ],
        ];
    }

    /**
     * Get a single shipment with items.
     */
    public function show(Shipment $shipment): array
    {
        $shipment->load(['region', 'district', 'items.images']);

        return [
            'success' => true,
            'message' => 'Shipment retrieved successfully.',
            'data' => [
                'shipment' => $this->transformShipment($shipment, true),
            ],
        ];
    }

    /**
     * Update a draft shipment.
     */
    public function update(Shipment $shipment, array $data, Request $request): array
    {
        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be edited in its current status.',
                'data' => null,
            ];
        }

        $shipment->update(array_filter([
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'region_id' => $data['region_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'town' => $data['town'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'gh_post_address' => $data['gh_post_address'] ?? null,
            'delivery_instructions' => $data['delivery_instructions'] ?? null,
            'landmark' => $data['landmark'] ?? null,
        ], fn($value) => $value !== null));

        $shipment->load(['region', 'district', 'items']);

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_updated',
            description: "Updated shipment {$shipment->shipment_number}",
            request: $request,
            metadata: ['shipment_id' => $shipment->id]
        );

        return [
            'success' => true,
            'message' => 'Shipment updated successfully.',
            'data' => [
                'shipment' => $this->transformShipment($shipment),
            ],
        ];
    }

    /**
     * Delete a draft shipment.
     */
    public function delete(Shipment $shipment, Request $request): array
    {
        if (!$shipment->canBeDeleted()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be deleted in its current status.',
                'data' => null,
            ];
        }

        $shipmentNumber = $shipment->shipment_number;
        $vendor = $shipment->vendor;

        $shipment->delete();

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $vendor,
            action: 'shipment_deleted',
            description: "Deleted shipment {$shipmentNumber}",
            request: $request
        );

        return [
            'success' => true,
            'message' => 'Shipment deleted successfully.',
            'data' => null,
        ];
    }

    /**
     * Submit a shipment for invoicing.
     */
    public function submit(Shipment $shipment, Request $request): array
    {
        if (!$shipment->canBeSubmitted()) {
            if ($shipment->items()->count() === 0) {
                return [
                    'success' => false,
                    'message' => 'Shipment must have at least one item before submitting.',
                    'data' => null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Shipment cannot be submitted in its current status.',
                'data' => null,
            ];
        }

        $shipment->update([
            'status' => ShipmentStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $shipment->load(['region', 'district', 'items']);

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_submitted',
            description: "Submitted shipment {$shipment->shipment_number} for invoicing",
            request: $request,
            metadata: ['shipment_id' => $shipment->id]
        );

        return [
            'success' => true,
            'message' => 'Shipment submitted for invoicing successfully.',
            'data' => [
                'shipment' => $this->transformShipment($shipment),
            ],
        ];
    }

    /**
     * Transform a single shipment for API response.
     */
    private function transformShipment(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
            'status' => $shipment->status->value,
            'recipient_name' => $shipment->recipient_name,
            'recipient_phone' => $shipment->recipient_phone,
            'location' => $shipment->formatted_location,
            'delivery_instructions' => $shipment->delivery_instructions,
            'items' => $shipment->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'status' => $item->status->value,
                    'tracking_code' => $item->tracking_code,
                    'images' => $item->images->map(fn($img) => $img->getSignedUrl()),
                    'created_at' => $item->created_at->toIso8601String(),
                ];
            }),
            'submitted_at' => $shipment->submitted_at?->toIso8601String(),
            'created_at' => $shipment->created_at->toIso8601String(),
            'updated_at' => $shipment->updated_at->toIso8601String(),
        ];
    }

    /**
     * Transform paginated shipments.
     */
    private function transformShipments(LengthAwarePaginator $shipments): array
    {
        return $shipments->getCollection()->map(
            fn($shipment) => $this->transformShipment($shipment)
        )->toArray();
    }
}
