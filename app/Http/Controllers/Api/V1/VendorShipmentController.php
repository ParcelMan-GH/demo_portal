<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\Shipment\CreateShipmentRequest;
use App\Http\Requests\Api\Vendor\Shipment\UpdateShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentItemService;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorShipmentController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService,
        private ShipmentItemService $shipmentItemService
    ) {}

    /**
     * List vendor's shipments.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $allowedStatuses = array_map(
            fn(ShipmentStatus $status) => $status->value,
            ShipmentStatus::cases()
        );

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

        $normalizedStatuses = $this->normalizeStatusesInput($request->input('status'));
        if (empty($normalizedStatuses) && $request->filled('statuses')) {
            $normalizedStatuses = $this->normalizeStatusesInput($request->input('statuses'));
        }
        if (!empty($normalizedStatuses)) {
            $request->merge(['status' => $normalizedStatuses]);
        }

        $normalizedIncludes = $this->normalizeIncludesInput($request->input('include'));
        if (!empty($normalizedIncludes)) {
            $request->merge(['include' => $normalizedIncludes]);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($allowedStatuses)],
            'include' => ['nullable', 'array'],
            'include.*' => ['string', Rule::in(['pickup_details'])],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'sort_by' => ['nullable', 'string', Rule::in($sortableFields)],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            // Legacy aliases retained for backward compatibility.
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'order_by' => ['nullable', 'string', Rule::in($sortableFields)],
            'order_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $filters = [
            'status' => $validated['status'] ?? [],
            'include' => $validated['include'] ?? [],
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'search' => $validated['search'] ?? null,
            'limit' => (int) ($validated['limit'] ?? $validated['per_page'] ?? 15),
            'offset' => (int) ($validated['offset'] ?? 0),
            'sort_by' => $validated['sort_by'] ?? $validated['order_by'] ?? 'created_at',
            'sort_order' => $validated['sort_order'] ?? $validated['order_dir'] ?? 'desc',
        ];

        $result = $this->shipmentService->list($vendor, $filters, $request);

        return response()->json($result);
    }

    private function normalizeStatusesInput(mixed $rawStatuses): array
    {
        if (is_null($rawStatuses)) {
            return [];
        }

        if (is_string($rawStatuses)) {
            $rawStatuses = explode(',', $rawStatuses);
        }

        if (!is_array($rawStatuses)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn($value) => trim((string) $value),
            $rawStatuses
        ), fn($value) => $value !== ''));
    }

    private function normalizeIncludesInput(mixed $rawIncludes): array
    {
        if (is_null($rawIncludes) || $rawIncludes === '') {
            return [];
        }

        if (is_string($rawIncludes)) {
            $rawIncludes = explode(',', $rawIncludes);
        }

        if (!is_array($rawIncludes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn($value) => trim((string) $value),
            $rawIncludes
        ), fn($value) => $value !== ''));
    }

    /**
     * Create a new shipment.
     */
    public function store(CreateShipmentRequest $request): JsonResponse
    {
        $vendor = $request->user();
        $validated = $request->validated();

        // Extract items before creating shipment
        $itemsData = $validated['items'] ?? [];
        unset($validated['items']);

        // Create shipment
        $result = $this->shipmentService->create($vendor, $validated, $request);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $shipment = Shipment::find($result['data']['shipment']['id']);

        // Create inline items with images
        $failedImages = 0;
        foreach ($itemsData as $index => $itemData) {
            $images = $request->file("items.{$index}.images", []);
            $images = is_array($images) ? array_values(array_filter($images)) : [];
            $phones = $request->input("items.{$index}.phones", []);
            $phones = is_array($phones) ? $phones : [];

            $itemResult = $this->shipmentItemService->addItem(
                shipment: $shipment,
                data: $itemData,
                request: $request,
                images: $images,
                phones: $phones,
            );

            if (!($itemResult['success'] ?? false)) {
                $failedImages++;
            }
        }

        // Auto-submit the shipment
        $submitResult = $this->shipmentService->submit($shipment, $request);

        // Reload and return
        $shipment->refresh();
        $showResult = $this->shipmentService->show($shipment);

        return response()->json([
            'success' => true,
            'message' => $submitResult['success']
                ? 'Shipment submitted successfully.'
                : 'Shipment created but could not be auto-submitted: ' . ($submitResult['message'] ?? ''),
            'data' => $showResult['data'],
        ], 201);
    }

    /**
     * Show a shipment.
     */
    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentService->show($shipment);

        return response()->json($result);
    }

    /**
     * Update a shipment.
     */
    public function update(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        // Extract photo operations before passing to service
        $newPhotos = $request->file('new_photos', []);
        $newPhotosPhones = $request->input('new_photos_phones', []);
        $removePhotoIds = $validated['remove_photo_ids'] ?? [];
        unset($validated['new_photos'], $validated['new_photos_phones'], $validated['remove_photo_ids']);

        $result = $this->shipmentService->update($shipment, $validated, $request);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        // Handle photo operations on the first item (unprocessed = 1 item holds all photos)
        $item = $shipment->items()->first();
        if ($item) {
            // Remove photos
            if (!empty($removePhotoIds)) {
                $images = $item->images()->whereIn('id', $removePhotoIds)->get();
                $storageService = app(\App\Services\StorageService::class);
                foreach ($images as $image) {
                    if ($image->path) {
                        $storageService->delete($image->path);
                    }
                    $image->delete();
                }
            }

            // Upload new photos with optional phone tags
            $newPhotos = is_array($newPhotos) ? array_values(array_filter($newPhotos)) : [];
            $newPhotosPhones = is_array($newPhotosPhones) ? $newPhotosPhones : [];
            if (!empty($newPhotos)) {
                $this->shipmentItemService->uploadImages($item, $newPhotos, $request, $newPhotosPhones);
            }
        }

        // Reload and return
        $showResult = $this->shipmentService->show($shipment->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully.',
            'data' => $showResult['data'],
        ]);
    }

    /**
     * Delete a shipment.
     */
    public function destroy(Request $request, Shipment $shipment): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentService->delete($shipment, $request);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Submit a shipment for invoicing.
     */
    public function submit(Request $request, Shipment $shipment): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentService->submit($shipment, $request);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }
}
