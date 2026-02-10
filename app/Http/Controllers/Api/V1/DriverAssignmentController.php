<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PickupAssignment;
use App\Services\PickupAssignmentService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverAssignmentController extends Controller
{
    public function __construct(
        private PickupAssignmentService $pickupAssignmentService,
        private StorageService $storageService
    ) {}

    /**
     * List driver's assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $query = PickupAssignment::where('driver_id', $driver->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $assignments = $query->with(['shipment.vendor', 'shipment.region', 'shipment.district'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        $assignments->getCollection()->transform(fn(PickupAssignment $assignment) => [
            'id' => $assignment->id,
            'status' => [
                'value' => $assignment->status->value,
                'label' => $assignment->status->label(),
            ],
            'shipment' => [
                'shipment_number' => $assignment->shipment->shipment_number,
                'recipient_name' => $assignment->shipment->recipient_name,
                'recipient_phone' => $assignment->shipment->recipient_phone,
                'vendor_name' => $assignment->shipment->vendor?->name,
                'region' => $assignment->shipment->region?->name,
                'district' => $assignment->shipment->district?->name,
                'town' => $assignment->shipment->town,
                'landmark' => $assignment->shipment->landmark,
            ],
            'assigned_at' => $assignment->assigned_at,
            'en_route_at' => $assignment->en_route_at,
            'arrived_at' => $assignment->arrived_at,
            'picked_up_at' => $assignment->picked_up_at,
            'completed_at' => $assignment->completed_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignments retrieved successfully.',
            'data' => $assignments,
        ]);
    }

    /**
     * Show a single assignment with detailed shipment data.
     */
    public function show(Request $request, PickupAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
                'data' => null,
            ], 404);
        }

        $assignment->load(['shipment.vendor', 'shipment.items.images', 'photos']);

        return response()->json([
            'success' => true,
            'message' => 'Assignment retrieved successfully.',
            'data' => [
                'assignment' => [
                    'id' => $assignment->id,
                    'status' => [
                        'value' => $assignment->status->value,
                        'label' => $assignment->status->label(),
                    ],
                    'shipment' => [
                        'id' => $assignment->shipment->id,
                        'shipment_number' => $assignment->shipment->shipment_number,
                        'status' => [
                            'value' => $assignment->shipment->status->value,
                            'label' => $assignment->shipment->status->label(),
                        ],
                        'recipient_name' => $assignment->shipment->recipient_name,
                        'recipient_phone' => $assignment->shipment->recipient_phone,
                        'vendor_name' => $assignment->shipment->vendor?->name,
                        'region' => $assignment->shipment->region?->name,
                        'district' => $assignment->shipment->district?->name,
                        'town' => $assignment->shipment->town,
                        'landmark' => $assignment->shipment->landmark,
                        'delivery_instructions' => $assignment->shipment->delivery_instructions,
                        'items' => $assignment->shipment->items->map(fn($item) => [
                            'id' => $item->id,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'weight' => $item->weight,
                            'images' => $item->images->map(fn($image) => [
                                'id' => $image->id,
                                'path' => $image->path,
                                'original_name' => $image->original_name,
                            ]),
                        ]),
                    ],
                    'photos' => $assignment->photos->map(fn($photo) => [
                        'id' => $photo->id,
                        'path' => $photo->path,
                        'original_name' => $photo->original_name,
                        'type' => $photo->type,
                    ]),
                    'assigned_at' => $assignment->assigned_at,
                    'en_route_at' => $assignment->en_route_at,
                    'arrived_at' => $assignment->arrived_at,
                    'picked_up_at' => $assignment->picked_up_at,
                    'completed_at' => $assignment->completed_at,
                    'notes' => $assignment->notes,
                ],
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
                'message' => 'Assignment not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->pickupAssignmentService->startEnRoute($assignment);

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
                'message' => 'Assignment not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $result = $this->pickupAssignmentService->arrive(
            $assignment,
            $validated['latitude'],
            $validated['longitude']
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Confirm pickup with photos.
     */
    public function confirmPickup(Request $request, PickupAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['file', 'image', 'max:10240'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->pickupAssignmentService->confirmPickup(
            $assignment,
            $validated['photos'],
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null,
            $validated['notes'] ?? null
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }
}
