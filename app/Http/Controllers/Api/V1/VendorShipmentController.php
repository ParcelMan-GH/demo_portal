<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\Shipment\CreateShipmentRequest;
use App\Http\Requests\Api\Vendor\Shipment\UpdateShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorShipmentController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService
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
        $result = $this->shipmentService->create($vendor, $request->validated(), $request);

        $statusCode = $result['success'] ? 201 : 400;
        return response()->json($result, $statusCode);
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

        $result = $this->shipmentService->update($shipment, $request->validated(), $request);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
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
