<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\Shipment\AddItemRequest;
use App\Http\Requests\Api\Vendor\Shipment\UpdateItemRequest;
use App\Http\Requests\Api\Vendor\Shipment\UploadImageRequest;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use App\Services\ShipmentItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorShipmentItemController extends Controller
{
    public function __construct(
        private ShipmentItemService $shipmentItemService
    ) {}

    /**
     * Add an item to a shipment.
     */
    public function store(AddItemRequest $request, Shipment $shipment): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentItemService->addItem($shipment, $request->validated(), $request);

        $statusCode = $result['success'] ? 201 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Update a shipment item.
     */
    public function update(UpdateItemRequest $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        // Check item belongs to shipment
        if ($item->shipment_id !== $shipment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentItemService->updateItem($item, $request->validated(), $request);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Remove a shipment item.
     */
    public function destroy(Request $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        // Check item belongs to shipment
        if ($item->shipment_id !== $shipment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentItemService->removeItem($item, $request);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Upload an image for a shipment item.
     */
    public function uploadImage(UploadImageRequest $request, Shipment $shipment, ShipmentItem $item): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        // Check item belongs to shipment
        if ($item->shipment_id !== $shipment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentItemService->uploadImages($item, $request->file('images'), $request);

        $statusCode = $result['success'] ? 201 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Delete an image from a shipment item.
     */
    public function deleteImage(Request $request, Shipment $shipment, ShipmentItem $item, ShipmentItemImage $image): JsonResponse
    {
        // Check ownership
        if ($shipment->vendor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
                'data' => null,
            ], 404);
        }

        // Check item belongs to shipment
        if ($item->shipment_id !== $shipment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
                'data' => null,
            ], 404);
        }

        // Check image belongs to item
        if ($image->shipment_item_id !== $item->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
                'data' => null,
            ], 404);
        }

        $result = $this->shipmentItemService->deleteImage($image, $request);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }
}
