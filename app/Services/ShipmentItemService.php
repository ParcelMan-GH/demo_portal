<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ShipmentItemService
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private StorageService $storageService
    ) {}

    /**
     * Add an item to a shipment.
     */
    public function addItem(Shipment $shipment, array $data, Request $request): array
    {
        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot add items to a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        $item = $shipment->items()->create([
            'description' => $data['description'],
            'quantity' => $data['quantity'] ?? 1,
        ]);

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_added',
            description: "Added item to shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
            ]
        );

        return [
            'success' => true,
            'message' => 'Item added successfully.',
            'data' => [
                'item' => $this->transformItem($item),
            ],
        ];
    }

    /**
     * Update a shipment item.
     */
    public function updateItem(ShipmentItem $item, array $data, Request $request): array
    {
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot update items of a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        $item->update(array_filter([
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? null,
        ], fn($value) => $value !== null));

        $item->load('images');

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_updated',
            description: "Updated item in shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
            ]
        );

        return [
            'success' => true,
            'message' => 'Item updated successfully.',
            'data' => [
                'item' => $this->transformItem($item),
            ],
        ];
    }

    /**
     * Remove a shipment item.
     */
    public function removeItem(ShipmentItem $item, Request $request): array
    {
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot remove items from a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        // Images will be deleted via model events
        $item->delete();

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_removed',
            description: "Removed item from shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
            ]
        );

        return [
            'success' => true,
            'message' => 'Item removed successfully.',
            'data' => null,
        ];
    }

    /**
     * Upload an image for a shipment item.
     */
    public function uploadImage(ShipmentItem $item, UploadedFile $file, Request $request): array
    {
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot upload images to a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        // Check max images limit
        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);
        $currentCount = $item->images()->count();

        if ($currentCount >= $maxImages) {
            return [
                'success' => false,
                'message' => "Maximum {$maxImages} images allowed per item.",
                'data' => null,
            ];
        }

        // Upload to storage
        $path = "shipments/{$shipment->id}/items/{$item->id}";
        $uploadResult = $this->storageService->upload($file, $path);

        // Create image record
        $image = $item->images()->create([
            'path' => $uploadResult['path'],
            'original_name' => $uploadResult['original_name'],
            'size' => $uploadResult['size'],
            'sort_order' => $currentCount,
        ]);

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_image_uploaded',
            description: "Uploaded image for item in shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
                'image_id' => $image->id,
            ]
        );

        return [
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'data' => [
                'image' => $image->getSignedUrl(),
            ],
        ];
    }

    /**
     * Upload multiple images to a shipment item.
     */
    public function uploadImages(ShipmentItem $item, array $files, Request $request): array
    {
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot upload images to a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        // Check max images limit
        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);
        $currentCount = $item->images()->count();
        $uploadCount = count($files);

        if ($currentCount + $uploadCount > $maxImages) {
            $remaining = $maxImages - $currentCount;
            return [
                'success' => false,
                'message' => "Cannot upload {$uploadCount} images. Maximum {$maxImages} images allowed per item. You can upload {$remaining} more image(s).",
                'data' => null,
            ];
        }

        $uploadedImages = [];
        $path = "shipments/{$shipment->id}/items/{$item->id}";

        foreach ($files as $file) {
            // Upload to storage
            $uploadResult = $this->storageService->upload($file, $path);

            // Create image record
            $image = $item->images()->create([
                'path' => $uploadResult['path'],
                'original_name' => $uploadResult['original_name'],
                'size' => $uploadResult['size'],
                'sort_order' => $currentCount++,
            ]);

            $uploadedImages[] = $image->getSignedUrl();
        }

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_images_uploaded',
            description: "Uploaded {$uploadCount} image(s) for item in shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
                'images_count' => $uploadCount,
            ]
        );

        return [
            'success' => true,
            'message' => count($uploadedImages) === 1
                ? 'Image uploaded successfully.'
                : count($uploadedImages) . ' images uploaded successfully.',
            'data' => [
                'images' => $uploadedImages,
            ],
        ];
    }

    /**
     * Delete an image from a shipment item.
     */
    public function deleteImage(ShipmentItemImage $image, Request $request): array
    {
        $item = $image->item;
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot delete images from a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        // Delete will also remove from storage via model event
        $image->delete();

        // Log activity
        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_image_deleted',
            description: "Deleted image from item in shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
            ]
        );

        return [
            'success' => true,
            'message' => 'Image deleted successfully.',
            'data' => null,
        ];
    }

    /**
     * Transform a shipment item for API response.
     */
    private function transformItem(ShipmentItem $item): array
    {
        $item->load('images');

        return [
            'id' => $item->id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'status' => $item->status?->value ?? 'pending',
            'tracking_code' => $item->tracking_code,
            'images' => $item->images->map(fn($img) => $img->getSignedUrl()),
            'created_at' => $item->created_at->toIso8601String(),
            'updated_at' => $item->updated_at->toIso8601String(),
        ];
    }
}
