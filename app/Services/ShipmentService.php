<?php

namespace App\Services;

use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentStatus;
use App\Models\Invoice;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\PickupPhoto;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ShipmentService
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private StorageService $storageService
    ) {}

    public function list(Vendor $vendor, array $filters, Request $request): array
    {
        $includes = is_array($filters['include'] ?? null) ? $filters['include'] : [];
        $includePickupDetails = in_array('pickup_details', $includes, true);

        $relations = [
            'deliveryRegion',
            'deliveryDistrict',
            'pickupRegion',
            'pickupDistrict',
            'items.images',
            'invoice',
            'invoices',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
            'pickupAssignment.receivedWarehouse',
        ];

        if ($includePickupDetails) {
            $relations[] = 'pickupAssignment.itemConfirmations';
            $relations[] = 'pickupAssignment.photos';
        }

        $query = $vendor->shipments()->with($relations);

        $statuses = $filters['status'] ?? [];
        if (is_string($statuses)) {
            $statuses = [$statuses];
        }
        if (!empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    ->orWhere('pickup_contact_name', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('description', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_phone', 'like', "%{$search}%");
                    });
            });
        }

        $sortableFields = [
            'id',
            'shipment_number',
            'status',
            'destination_mode',
            'delivery_recipient_name',
            'pickup_contact_name',
            'submitted_at',
            'created_at',
            'updated_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'created_at';
        if ($sortBy === 'recipient_name') {
            $sortBy = 'delivery_recipient_name';
        }
        if (!in_array($sortBy, $sortableFields, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc'));
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $limit = (int) ($filters['limit'] ?? $filters['per_page'] ?? 15);
        $limit = max(1, min($limit, 100));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        $total = (clone $query)->count();

        $shipments = $query
            ->orderBy($sortBy, $sortOrder)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = $this->transformShipments($shipments, [
            'include_pickup_details' => $includePickupDetails,
            'include_legacy_delivery_aliases' => false,
        ]);
        $count = count($rows);
        $hasMore = ($offset + $count) < $total;
        $nextOffset = $hasMore ? ($offset + $limit) : null;
        $currentPage = (int) floor($offset / $limit) + 1;
        $lastPage = max((int) ceil($total / $limit), 1);

        return [
            'success' => true,
            'message' => 'Shipments retrieved successfully.',
            'data' => [
                'shipments' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => $hasMore,
                    'next_offset' => $nextOffset,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $limit,
                ],
            ],
        ];
    }

    public function create(Vendor $vendor, array $data, Request $request): array
    {
        $payload = $this->buildShipmentPayload($data);
        $payload['status'] = ShipmentStatus::DRAFT;

        $shipment = $vendor->shipments()->create($payload);
        $shipment->load(['deliveryRegion', 'deliveryDistrict', 'pickupRegion', 'pickupDistrict', 'items']);

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

    public function show(Shipment $shipment): array
    {
        $shipment->load([
            'deliveryRegion',
            'deliveryDistrict',
            'pickupRegion',
            'pickupDistrict',
            'items.images',
            'items.deliveryRegion',
            'items.deliveryDistrict',
            'invoice',
            'invoices',
            'pickupAssignment.driver',
            'pickupAssignment.targetWarehouse',
            'pickupAssignment.receivedWarehouse',
            'pickupAssignment.itemConfirmations',
            'pickupAssignment.photos',
        ]);

        return [
            'success' => true,
            'message' => 'Shipment retrieved successfully.',
            'data' => [
                'shipment' => $this->transformShipment($shipment, [
                    'include_pickup_details' => true,
                ]),
            ],
        ];
    }

    public function update(Shipment $shipment, array $data, Request $request): array
    {
        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Shipment cannot be edited in its current status.',
                'data' => null,
            ];
        }

        $payload = $this->buildShipmentPayload($data, $shipment);
        $shipment->update($payload);
        $shipment->load(['deliveryRegion', 'deliveryDistrict', 'pickupRegion', 'pickupDistrict', 'items']);

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

        if (!$this->hasValidPickupLocation($shipment)) {
            return [
                'success' => false,
                'message' => 'Shipment pickup location is incomplete.',
                'data' => null,
            ];
        }

        if ($shipment->destination_mode === ShipmentDestinationMode::SINGLE) {
            if (!$this->hasValidSingleDelivery($shipment)) {
                return [
                    'success' => false,
                    'message' => 'Shipment delivery details are incomplete for single destination mode.',
                    'data' => null,
                ];
            }
        } else {
            $invalidItems = $shipment->items()
                ->where(function ($q) {
                    $q->whereNull('delivery_recipient_name')
                        ->orWhereNull('delivery_recipient_phone')
                        ->orWhere(function ($locationQ) {
                            $locationQ
                                ->where(function ($dropdownQ) {
                                    $dropdownQ->whereNull('delivery_region_id')
                                        ->orWhereNull('delivery_district_id');
                                })
                                ->where(function ($coordsQ) {
                                    $coordsQ->whereNull('delivery_latitude')
                                        ->orWhereNull('delivery_longitude');
                                })
                                ->whereNull('delivery_gh_post_address');
                        });
                })
                ->count();

            if ($invalidItems > 0) {
                return [
                    'success' => false,
                    'message' => 'All items must include recipient and delivery location details for per-item destination mode.',
                    'data' => null,
                ];
            }
        }

        $shipment->update([
            'status' => ShipmentStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $shipment->load(['deliveryRegion', 'deliveryDistrict', 'pickupRegion', 'pickupDistrict', 'items']);

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

    private function buildShipmentPayload(array $data, ?Shipment $shipment = null): array
    {
        $mode = $data['destination_mode']
            ?? ($shipment?->destination_mode?->value ?? ShipmentDestinationMode::SINGLE->value);

        $payload = [
            'destination_mode' => $mode,
            'fulfillment_type' => $data['fulfillment_type'] ?? $shipment?->fulfillment_type?->value ?? 'warehouse',
            'pickup_contact_name' => $data['pickup_contact_name'] ?? $shipment?->pickup_contact_name,
            'pickup_contact_phone' => $data['pickup_contact_phone'] ?? $shipment?->pickup_contact_phone,
            'pickup_region_id' => $data['pickup_region_id'] ?? $shipment?->pickup_region_id,
            'pickup_district_id' => $data['pickup_district_id'] ?? $shipment?->pickup_district_id,
            'pickup_town' => $data['pickup_town'] ?? $shipment?->pickup_town,
            'pickup_latitude' => $data['pickup_latitude'] ?? $shipment?->pickup_latitude,
            'pickup_longitude' => $data['pickup_longitude'] ?? $shipment?->pickup_longitude,
            'pickup_gh_post_address' => $data['pickup_gh_post_address'] ?? $shipment?->pickup_gh_post_address,
            'pickup_landmark' => $data['pickup_landmark'] ?? $shipment?->pickup_landmark,
            'pickup_instructions' => $data['pickup_instructions'] ?? $shipment?->pickup_instructions,
        ];

        if ($mode === ShipmentDestinationMode::SINGLE->value) {
            $payload = array_merge($payload, [
                'delivery_recipient_name' => $data['delivery_recipient_name'] ?? $shipment?->delivery_recipient_name,
                'delivery_recipient_phone' => $data['delivery_recipient_phone'] ?? $shipment?->delivery_recipient_phone,
                'delivery_region_id' => $data['delivery_region_id'] ?? $shipment?->delivery_region_id,
                'delivery_district_id' => $data['delivery_district_id'] ?? $shipment?->delivery_district_id,
                'delivery_town' => $data['delivery_town'] ?? $shipment?->delivery_town,
                'delivery_latitude' => $data['delivery_latitude'] ?? $shipment?->delivery_latitude,
                'delivery_longitude' => $data['delivery_longitude'] ?? $shipment?->delivery_longitude,
                'delivery_gh_post_address' => $data['delivery_gh_post_address'] ?? $shipment?->delivery_gh_post_address,
                'delivery_landmark' => $data['delivery_landmark'] ?? $shipment?->delivery_landmark,
                'delivery_instructions' => $data['delivery_instructions'] ?? $shipment?->delivery_instructions,
            ]);
        } else {
            $payload = array_merge($payload, [
                'delivery_recipient_name' => null,
                'delivery_recipient_phone' => null,
                'delivery_region_id' => null,
                'delivery_district_id' => null,
                'delivery_town' => null,
                'delivery_latitude' => null,
                'delivery_longitude' => null,
                'delivery_gh_post_address' => null,
                'delivery_landmark' => null,
                'delivery_instructions' => null,
            ]);
        }

        return $payload;
    }

    private function hasValidPickupLocation(Shipment $shipment): bool
    {
        $hasDropdown = filled($shipment->pickup_region_id) && filled($shipment->pickup_district_id);
        $hasCoordinates = filled($shipment->pickup_latitude) && filled($shipment->pickup_longitude);
        $hasGhPost = filled($shipment->pickup_gh_post_address);

        return $hasDropdown || $hasCoordinates || $hasGhPost;
    }

    private function hasValidSingleDelivery(Shipment $shipment): bool
    {
        if (blank($shipment->delivery_recipient_name) || blank($shipment->delivery_recipient_phone)) {
            return false;
        }

        $hasDropdown = filled($shipment->delivery_region_id) && filled($shipment->delivery_district_id);
        $hasCoordinates = filled($shipment->delivery_latitude) && filled($shipment->delivery_longitude);
        $hasGhPost = filled($shipment->delivery_gh_post_address);

        return $hasDropdown || $hasCoordinates || $hasGhPost;
    }

    private function transformShipment(Shipment $shipment, array $options = []): array
    {
        $includePickupDetails = (bool) ($options['include_pickup_details'] ?? false);
        $includeLegacyDeliveryAliases = (bool) ($options['include_legacy_delivery_aliases'] ?? false);
        $pickupAssignment = $shipment->relationLoaded('pickupAssignment')
            ? $shipment->pickupAssignment
            : null;

        $confirmationsByItemId = [];
        $pickupPhotosByItemId = [];

        if ($includePickupDetails && $pickupAssignment) {
            $confirmationsByItemId = $pickupAssignment->relationLoaded('itemConfirmations')
                ? $pickupAssignment->itemConfirmations->keyBy('shipment_item_id')->all()
                : [];

            $pickupPhotosByItemId = $pickupAssignment->relationLoaded('photos')
                ? $pickupAssignment->photos
                    ->filter(fn(PickupPhoto $photo) => !is_null($photo->shipment_item_id))
                    ->groupBy('shipment_item_id')
                    ->all()
                : [];
        }

        $items = $shipment->items->map(function (ShipmentItem $item) use ($shipment, $includePickupDetails, $confirmationsByItemId, $pickupPhotosByItemId) {
            $itemDelivery = $shipment->destination_mode === ShipmentDestinationMode::PER_ITEM
                ? [
                    'recipient_name' => $item->delivery_recipient_name,
                    'recipient_phone' => $item->delivery_recipient_phone,
                    'location' => $item->formatted_delivery_location,
                    'instructions' => $item->delivery_instructions,
                ]
                : null;

            $payload = [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'status' => $item->status->value,
                'tracking_code' => $item->tracking_code,
                'delivery' => $itemDelivery,
                'images' => $item->images->map(fn($img) => $img->getSignedUrl()),
                'created_at' => $item->created_at->toIso8601String(),
                'updated_at' => $item->updated_at->toIso8601String(),
            ];

            if ($includePickupDetails) {
                $pickupConfirmation = $this->transformPickupItemConfirmationForApi(
                    $confirmationsByItemId[$item->id] ?? null
                );

                if ($pickupConfirmation) {
                    $pickupConfirmation['photos'] = collect($pickupPhotosByItemId[$item->id] ?? [])
                        ->map(fn(PickupPhoto $photo) => $this->transformPickupPhotoForApi($photo))
                        ->values()
                        ->toArray();
                }

                $payload['pickup_confirmation'] = $pickupConfirmation;
            }

            return $payload;
        });

        $invoiceHistory = [];
        if ($shipment->relationLoaded('invoices')) {
            $invoiceHistory = $shipment->invoices
                ->map(fn(Invoice $invoice) => $this->transformInvoiceForApi($invoice, $shipment))
                ->values()
                ->toArray();
        } elseif ($shipment->relationLoaded('invoice') && $shipment->invoice) {
            // Fallback to keep invoice_history consistent when only current invoice is eager-loaded.
            $invoiceHistory = [$this->transformInvoiceForApi($shipment->invoice, $shipment)];
        }

        // Build collection info for self-pickup shipments
        $collectionData = null;
        if ($shipment->fulfillment_type?->isSelfPickup()) {
            $collection = $shipment->relationLoaded('collection') ? $shipment->collection : $shipment->collection;
            if ($collection) {
                $warehouse = $collection->relationLoaded('warehouse') ? $collection->warehouse : $collection->warehouse;
                $collectionData = [
                    'status' => $collection->status,
                    'warehouse_name' => $warehouse?->name,
                    'warehouse_address' => $warehouse?->address,
                    'warehouse_phone' => $warehouse?->contact_phone,
                    'ready_at' => $collection->ready_at?->toIso8601String(),
                    'collected_at' => $collection->collected_at?->toIso8601String(),
                    'collected_by_name' => $collection->collected_by_name,
                ];
            }
        }

        $payload = [
            'id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
            'status' => $shipment->status->value,
            'fulfillment_type' => $shipment->fulfillment_type?->value ?? 'warehouse',
            'destination_mode' => $shipment->destination_mode->value,
        ];

        if ($includeLegacyDeliveryAliases) {
            // Legacy compatibility aliases for older consumers.
            $payload = array_merge($payload, [
                'recipient_name' => $shipment->recipient_name,
                'recipient_phone' => $shipment->recipient_phone,
                'location' => $shipment->formatted_location,
                'delivery_instructions' => $shipment->delivery_instructions,
            ]);
        }

        return array_merge($payload, [
            'pickup' => [
                'contact_name' => $shipment->pickup_contact_name,
                'contact_phone' => $shipment->pickup_contact_phone,
                'location' => $shipment->formatted_pickup_location,
                'instructions' => $shipment->pickup_instructions,
            ],
            'delivery' => $shipment->destination_mode === ShipmentDestinationMode::SINGLE
                ? [
                    'recipient_name' => $shipment->delivery_recipient_name,
                    'recipient_phone' => $shipment->delivery_recipient_phone,
                    'location' => $shipment->formatted_location,
                    'instructions' => $shipment->delivery_instructions,
                ]
                : null,
            'items' => $items,

            'can_edit' => $shipment->canBeEdited(),
            'can_delete' => $shipment->canBeDeleted(),
            'can_submit' => $shipment->canBeSubmitted(),

            'submitted_at' => $shipment->submitted_at?->toIso8601String(),
            'created_at' => $shipment->created_at->toIso8601String(),
            'updated_at' => $shipment->updated_at->toIso8601String(),

            'invoice' => $shipment->relationLoaded('invoice') && $shipment->invoice
                ? $this->transformInvoiceForApi($shipment->invoice, $shipment)
                : null,
            'invoice_history' => $invoiceHistory,
            'collection' => $collectionData,
            'pickup_assignment' => $pickupAssignment ? array_merge([
                'id' => $pickupAssignment->id,
                'status' => $pickupAssignment->status->value,
                'status_label' => $pickupAssignment->status->label(),
                'driver_name' => $pickupAssignment->driver?->name,
                'driver_phone' => $pickupAssignment->driver?->phone,
                'driver' => $pickupAssignment->driver ? [
                    'id' => $pickupAssignment->driver->id,
                    'name' => $pickupAssignment->driver->name,
                    'phone' => $pickupAssignment->driver->phone,
                    'vehicle_type' => $pickupAssignment->driver->vehicle_type,
                    'vehicle_number' => $pickupAssignment->driver->vehicle_number,
                ] : null,
                'timeline' => $this->buildPickupTimelineForApi($pickupAssignment),
                'target_warehouse' => $pickupAssignment->targetWarehouse ? [
                    'id' => $pickupAssignment->targetWarehouse->id,
                    'name' => $pickupAssignment->targetWarehouse->name,
                    'code' => $pickupAssignment->targetWarehouse->code,
                ] : null,
                'notes' => $pickupAssignment->notes,
            ], $includePickupDetails ? [
                'include_details' => true,
            ] : []) : null,
        ]);
    }

    private function transformShipments(iterable $shipments, array $options = []): array
    {
        return collect($shipments)->map(
            fn($shipment) => $this->transformShipment($shipment, $options)
        )->values()->toArray();
    }

    private function transformPickupItemConfirmationForApi(?PickupItemConfirmation $confirmation): ?array
    {
        if (!$confirmation) {
            return null;
        }

        $expected = (int) $confirmation->expected_quantity;
        $confirmed = (int) $confirmation->confirmed_quantity;

        return [
            'expected_quantity' => $expected,
            'confirmed_quantity' => $confirmed,
            'missing_quantity' => max($expected - $confirmed, 0),
            'extra_quantity' => max($confirmed - $expected, 0),
            'is_exact_match' => $confirmed === $expected,
            'notes' => $confirmation->notes,
            'confirmed_at' => $confirmation->confirmed_at,
        ];
    }

    private function transformPickupPhotoForApi(PickupPhoto $photo): array
    {
        $size = (int) ($photo->size ?? 0);

        return [
            'id' => $photo->id,
            'url' => $this->storageService->getUrl($photo->path),
            'original_name' => $photo->original_name,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
            'created_at' => $photo->created_at,
        ];
    }

    private function buildPickupTimelineForApi(PickupAssignment $assignment): array
    {
        return [
            'assigned' => [
                'at' => $assignment->assigned_at,
            ],
            'en_route' => [
                'at' => $assignment->en_route_at,
            ],
            'arrived_pickup' => [
                'at' => $assignment->arrived_at,
                'latitude' => $assignment->pickup_latitude,
                'longitude' => $assignment->pickup_longitude,
            ],
            'picked_up' => [
                'at' => $assignment->picked_up_at,
            ],
            'arrived_warehouse' => [
                'at' => $assignment->arrived_warehouse_at,
                'warehouse' => $assignment->targetWarehouse ? [
                    'id' => $assignment->targetWarehouse->id,
                    'name' => $assignment->targetWarehouse->name,
                    'code' => $assignment->targetWarehouse->code,
                ] : null,
            ],
            'received' => [
                'at' => $assignment->received_at,
                'warehouse' => $assignment->receivedWarehouse ? [
                    'id' => $assignment->receivedWarehouse->id,
                    'name' => $assignment->receivedWarehouse->name,
                    'code' => $assignment->receivedWarehouse->code,
                ] : null,
                'received_by_user_id' => $assignment->received_by_user_id,
                'notes' => $assignment->receive_notes,
            ],
            'completed' => [
                'at' => $assignment->completed_at,
            ],
            'cancelled' => [
                'at' => $assignment->cancelled_at,
                'reason' => $assignment->cancellation_reason,
            ],
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, 2) . ' ' . $units[$power];
    }

    private function transformInvoiceForApi(Invoice $invoice, Shipment $shipment): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
            'status' => $invoice->status->value,
            'pickup_fee' => (float) $invoice->pickup_fee,
            'transport_fee' => (float) $invoice->transport_fee,
            'handling_fee' => (float) $invoice->handling_fee,
            'other_fee' => (float) $invoice->other_fee,
            'total_amount' => (float) $invoice->total_amount,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'vendor_notes' => $invoice->vendor_notes,
            'rejection_reason' => $invoice->rejection_reason,
            'cancel_reason' => $invoice->cancel_reason,
            'is_active' => $invoice->status->isActive(),
            'sent_at' => $invoice->sent_at,
            'accepted_at' => $invoice->accepted_at,
            'rejected_at' => $invoice->rejected_at,
            'cancelled_at' => $invoice->cancelled_at,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
        ];
    }
}
