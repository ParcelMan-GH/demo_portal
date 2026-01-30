<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\Shipment\CreateShipmentRequest;
use App\Http\Requests\Api\Vendor\Shipment\UpdateShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $filters = [
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'created_at'),
            'order_dir' => $request->input('order_dir', 'desc'),
            'per_page' => $request->input('per_page', 15),
        ];

        $result = $this->shipmentService->list($vendor, $filters, $request);

        return response()->json($result);
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
