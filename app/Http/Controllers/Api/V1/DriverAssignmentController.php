<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PickupAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\PickupAssignment;
use App\Models\PickupPhoto;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Services\DirectDeliveryService;
use App\Services\PickupAssignmentService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverAssignmentController extends Controller
{
    public function __construct(
        private PickupAssignmentService $pickupAssignmentService,
        private StorageService $storageService
    ) {}

    /**
     * List driver's pickups.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $allowedStatuses = array_map(
            fn(PickupAssignmentStatus $status) => $status->value,
            PickupAssignmentStatus::cases()
        );

        $sortableFields = [
            'id',
            'shipment_id',
            'status',
            'assigned_at',
            'en_route_at',
            'arrived_at',
            'picked_up_at',
            'completed_at',
            'cancelled_at',
            'created_at',
            'updated_at',
        ];

        $normalizedStatuses = $this->normalizeStatusesInput($request->input('status'));
        if (empty($normalizedStatuses) && $request->filled('statuses')) {
            $normalizedStatuses = $this->normalizeStatusesInput($request->input('statuses'));
        }
        if (!empty($normalizedStatuses)) {
            $request->merge(['status' => $normalizedStatuses]);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($allowedStatuses)],
            'shipment_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'sort_by' => ['nullable', 'string', Rule::in($sortableFields)],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            // Legacy aliases kept for compatibility.
            'order_by' => ['nullable', 'string', Rule::in($sortableFields)],
            'order_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $query = PickupAssignment::where('driver_id', $driver->id)
            ->with([
                'shipment.vendor',
                'shipment.pickupRegion',
                'shipment.pickupDistrict',
                'shipment.items.images',
                'targetWarehouse',
                'receivedWarehouse',
                'photos',
                'itemConfirmations',
            ]);

        if (!empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (!empty($validated['shipment_id'])) {
            $query->where('shipment_id', $validated['shipment_id']);
        }

        if (!empty($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('shipment', function ($sq) use ($search) {
                        $sq->where('shipment_number', 'like', "%{$search}%")
                            ->orWhere('pickup_contact_name', 'like', "%{$search}%")
                            ->orWhere('pickup_contact_phone', 'like', "%{$search}%")
                            ->orWhereHas('items', function ($iq) use ($search) {
                                $iq->where('description', 'like', "%{$search}%")
                                    ->orWhere('tracking_code', 'like', "%{$search}%");
                            })
                            ->orWhereHas('vendor', function ($vq) use ($search) {
                                $vq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $limit = (int) ($validated['limit'] ?? $validated['per_page'] ?? 15);
        $offset = (int) ($validated['offset'] ?? 0);
        $sortBy = $validated['sort_by'] ?? $validated['order_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? $validated['order_dir'] ?? 'desc';

        $total = (clone $query)->count();

        $assignments = $query
            ->orderBy($sortBy, $sortOrder)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $assignmentItems = $assignments
            ->map(fn(PickupAssignment $assignment) => $this->transformAssignmentForDriver($assignment))
            ->toArray();

        $count = count($assignmentItems);
        $hasMore = ($offset + $count) < $total;
        $nextOffset = $hasMore ? ($offset + $limit) : null;
        $currentPage = (int) floor($offset / $limit) + 1;
        $lastPage = max((int) ceil($total / $limit), 1);

        return response()->json([
            'success' => true,
            'message' => 'Pickups retrieved successfully.',
            'data' => [
                'pickups' => $assignmentItems,
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
        ]);
    }

    /**
     * Show a single pickup with detailed shipment data.
     */
    public function show(Request $request, PickupAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup not found.',
                'data' => null,
            ], 404);
        }

        $assignment->load([
            'shipment.vendor',
            'shipment.pickupRegion',
            'shipment.pickupDistrict',
            'shipment.items.images',
            'targetWarehouse',
            'receivedWarehouse',
            'photos',
            'itemConfirmations',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup retrieved successfully.',
            'data' => [
                'pickup' => $this->transformAssignmentForDriver($assignment),
            ],
        ]);
    }

    /**
     * Start en route to pickup location.
     */
    public function startEnRoute(Request $request, PickupAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->pickupAssignmentService->startEnRoute($assignment);
        if (($result['success'] ?? false) === true) {
            $freshAssignment = $assignment->fresh([
                'shipment.vendor',
                'shipment.pickupRegion',
                'shipment.pickupDistrict',
                'shipment.items.images',
                'targetWarehouse',
                'receivedWarehouse',
                'photos',
                'itemConfirmations',
            ]);

            if ($freshAssignment instanceof PickupAssignment) {
                $result['data']['assignment'] = $this->transformAssignmentForDriver($freshAssignment);
            }
        }

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Mark arrival at pickup location.
     */
    public function arrive(Request $request, PickupAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->pickupAssignmentService->arrive(
            $assignment,
            $validated['latitude'],
            $validated['longitude']
        );
        if (($result['success'] ?? false) === true) {
            $freshAssignment = $assignment->fresh([
                'shipment.vendor',
                'shipment.pickupRegion',
                'shipment.pickupDistrict',
                'shipment.items.images',
                'targetWarehouse',
                'receivedWarehouse',
                'photos',
                'itemConfirmations',
            ]);

            if ($freshAssignment instanceof PickupAssignment) {
                $result['data']['assignment'] = $this->transformAssignmentForDriver($freshAssignment);
            }
        }

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Confirm a pickup item with quantity and photos.
     */
    public function confirmPickupItem(Request $request, PickupAssignment $assignment, ShipmentItem $item): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'confirmed_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:10240'],
            'remove_photo_ids' => ['nullable', 'array'],
            'remove_photo_ids.*' => ['integer', 'distinct'],
        ]);

        $result = $this->pickupAssignmentService->confirmItem(
            $assignment,
            $item,
            (int) $validated['confirmed_quantity'],
            $validated['photos'] ?? [],
            $validated['notes'] ?? null,
            $validated['remove_photo_ids'] ?? []
        );
        if (($result['success'] ?? false) === true) {
            $freshAssignment = $assignment->fresh([
                'shipment.vendor',
                'shipment.pickupRegion',
                'shipment.pickupDistrict',
                'shipment.items.images',
                'targetWarehouse',
                'receivedWarehouse',
                'photos',
                'itemConfirmations',
            ]);

            if ($freshAssignment instanceof PickupAssignment) {
                $result['data']['assignment'] = $this->transformAssignmentForDriver($freshAssignment);
            }
        }

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Finalize pickup after all shipment items have been confirmed.
     */
    public function confirmPickup(Request $request, PickupAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->pickupAssignmentService->finalizePickup(
            $assignment,
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null,
            $validated['notes'] ?? null
        );
        if (($result['success'] ?? false) === true) {
            $freshAssignment = $assignment->fresh([
                'shipment.vendor',
                'shipment.pickupRegion',
                'shipment.pickupDistrict',
                'shipment.deliveryRegion',
                'shipment.deliveryDistrict',
                'shipment.items.images',
                'shipment.items.deliveryRegion',
                'shipment.items.deliveryDistrict',
                'targetWarehouse',
                'receivedWarehouse',
                'photos',
                'itemConfirmations',
            ]);

            if ($freshAssignment instanceof PickupAssignment) {
                $result['data']['assignment'] = $this->transformAssignmentForDriver($freshAssignment);

                // Direct delivery: auto-create virtual pipeline and return delivery info
                $shipment = $freshAssignment->shipment;
                if ($shipment?->fulfillment_type?->isDirect() && $freshAssignment->target_warehouse_id) {
                    $autoDelivery = app(DirectDeliveryService::class)->createVirtualPipeline(
                        $shipment,
                        $freshAssignment->driver_id,
                        $freshAssignment->target_warehouse_id
                    );
                    $result['data']['assignment']['auto_delivery'] = $autoDelivery;
                    $result['message'] = 'Pickup confirmed. Proceed to delivery.';
                }
            }
        }

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Normalize status filter input into an array of values.
     */
    private function normalizeStatusesInput(mixed $rawStatuses): array
    {
        if ($rawStatuses === null || $rawStatuses === '') {
            return [];
        }

        if (is_string($rawStatuses)) {
            $parts = preg_split('/[\s,]+/', $rawStatuses) ?: [];
            return array_values(array_filter(array_map('trim', $parts), fn($value) => $value !== ''));
        }

        if (!is_array($rawStatuses)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn($value) => is_scalar($value) ? trim((string) $value) : '',
            $rawStatuses
        ), fn(string $value) => $value !== ''));
    }

    private function transformAssignmentForDriver(PickupAssignment $assignment): array
    {
        $shipment = $assignment->shipment;
        $isDirect = $shipment?->fulfillment_type?->isDirect() ?? false;

        $directDelivery = null;
        if ($isDirect && $shipment) {
            $directDelivery = [
                'recipient_name' => $shipment->delivery_recipient_name,
                'recipient_phone' => $shipment->delivery_recipient_phone,
                'location' => [
                    'region' => $shipment->deliveryRegion?->name,
                    'district' => $shipment->deliveryDistrict?->name,
                    'town' => $shipment->delivery_town,
                    'latitude' => $shipment->delivery_latitude,
                    'longitude' => $shipment->delivery_longitude,
                    'gh_post_address' => $shipment->delivery_gh_post_address,
                    'landmark' => $shipment->delivery_landmark,
                ],
                'instructions' => $shipment->delivery_instructions,
            ];

            // For PER_ITEM mode, use first item's delivery if shipment-level is empty
            if (!$directDelivery['recipient_name'] && $shipment->isPerItemDestination() && $shipment->items->isNotEmpty()) {
                $firstItem = $shipment->items->first();
                $directDelivery = [
                    'recipient_name' => $firstItem->delivery_recipient_name,
                    'recipient_phone' => $firstItem->delivery_recipient_phone,
                    'location' => [
                        'region' => $firstItem->deliveryRegion?->name,
                        'district' => $firstItem->deliveryDistrict?->name,
                        'town' => $firstItem->delivery_town,
                        'latitude' => $firstItem->delivery_latitude,
                        'longitude' => $firstItem->delivery_longitude,
                        'gh_post_address' => $firstItem->delivery_gh_post_address,
                        'landmark' => $firstItem->delivery_landmark,
                    ],
                    'instructions' => $firstItem->delivery_instructions,
                ];
            }
        }

        return [
            'id' => $assignment->id,
            'shipment_id' => $assignment->shipment_id,
            'shipment_number' => $shipment?->shipment_number,
            'status' => $assignment->status->value,
            'is_direct_delivery' => $isDirect,
            'direct_delivery' => $directDelivery,
            'cancellation_reason' => $assignment->cancellation_reason,
            'notes' => $assignment->notes,
            'pickup_latitude' => $assignment->pickup_latitude,
            'pickup_longitude' => $assignment->pickup_longitude,
            'timeline' => $this->buildPickupTimeline($assignment),
            'target_warehouse' => $assignment->targetWarehouse ? [
                'id' => $assignment->targetWarehouse->id,
                'name' => $assignment->targetWarehouse->name,
                'code' => $assignment->targetWarehouse->code,
            ] : null,
            'shipment' => $shipment
                ? $this->transformShipmentForDriver($shipment, $assignment)
                : null,
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
        ];
    }

    private function transformShipmentForDriver(Shipment $shipment, ?PickupAssignment $assignment = null): array
    {
        $confirmationsByItemId = [];
        $pickupPhotosByItemId = [];

        if ($assignment) {
            $confirmationsByItemId = $assignment->itemConfirmations
                ->keyBy('shipment_item_id')
                ->all();

            $pickupPhotosByItemId = $assignment->photos
                ->filter(fn(PickupPhoto $photo) => !is_null($photo->shipment_item_id))
                ->groupBy('shipment_item_id')
                ->all();
        }

        return [
            'id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
            'status' => $shipment->status->value,
            'vendor_name' => $shipment->vendor?->name,
            'pickup' => [
                'contact_name' => $shipment->pickup_contact_name,
                'contact_phone' => $shipment->pickup_contact_phone,
                'location' => $this->transformPickupLocationForDriver($shipment),
                'instructions' => $shipment->pickup_instructions,
            ],
            'items' => $shipment->items->map(function (ShipmentItem $item) use ($confirmationsByItemId, $pickupPhotosByItemId) {
                $pickupConfirmation = $this->transformPickupItemConfirmationForDriver(
                    $confirmationsByItemId[$item->id] ?? null
                );

                $itemPickupPhotos = collect($pickupPhotosByItemId[$item->id] ?? [])
                    ->map(fn(PickupPhoto $photo) => $this->transformPickupPhotoForDriver($photo))
                    ->values()
                    ->toArray();

                if ($pickupConfirmation) {
                    $pickupConfirmation['photos'] = $itemPickupPhotos;
                }

                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'status' => $item->status->value,
                    'tracking_code' => $item->tracking_code,
                    'images' => $item->images->map(fn($image) => $image->getSignedUrl()),
                    'pickup_confirmation' => $pickupConfirmation,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->values()->toArray(),
            'submitted_at' => $shipment->submitted_at,
            'created_at' => $shipment->created_at,
            'updated_at' => $shipment->updated_at,
        ];
    }

    private function transformPickupLocationForDriver(Shipment $shipment): array
    {
        return [
            'region' => $shipment->pickupRegion?->name,
            'district' => $shipment->pickupDistrict?->name,
            'town' => $shipment->pickup_town,
            'latitude' => $shipment->pickup_latitude,
            'longitude' => $shipment->pickup_longitude,
            'gh_post_address' => $shipment->pickup_gh_post_address,
            'landmark' => $shipment->pickup_landmark,
        ];
    }

    private function transformPickupPhotoForDriver(PickupPhoto $photo): array
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

    private function transformPickupItemConfirmationForDriver(?\App\Models\PickupItemConfirmation $confirmation): ?array
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

    private function buildPickupTimeline(PickupAssignment $assignment): array
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
}
