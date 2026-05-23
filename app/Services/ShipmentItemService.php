<?php

namespace App\Services;

use App\Enums\ShipmentDestinationMode;
use App\Helpers\PhoneHelper;
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

    public function addItem(Shipment $shipment, array $data, Request $request, ?array $images = null, array $phones = []): array
    {
        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot add items to a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        $itemPayload = [
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
        ];

        if ($shipment->destination_mode === ShipmentDestinationMode::PER_ITEM) {
            $itemPayload = array_merge($itemPayload, [
                'delivery_recipient_name' => $data['delivery_recipient_name'] ?? null,
                'delivery_recipient_phone' => !empty($data['delivery_recipient_phone'])
                    ? PhoneHelper::format($data['delivery_recipient_phone'])
                    : null,
                'delivery_region_id' => $data['delivery_region_id'] ?? null,
                'delivery_district_id' => $data['delivery_district_id'] ?? null,
                'delivery_town' => $data['delivery_town'] ?? null,
                'delivery_latitude' => $data['delivery_latitude'] ?? null,
                'delivery_longitude' => $data['delivery_longitude'] ?? null,
                'delivery_gh_post_address' => $data['delivery_gh_post_address'] ?? null,
                'delivery_landmark' => $data['delivery_landmark'] ?? null,
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'delivery_preference' => $data['delivery_preference'] ?? 'deliver',
                'fulfillment_type' => $data['fulfillment_type'] ?? null,
            ]);
        }

        $item = $shipment->items()->create($itemPayload);
        $uploadedImageCount = 0;

        $files = $images ?? $request->file('images', []);
        $files = array_values(array_filter(is_array($files) ? $files : []));

        if (!empty($files)) {
            $uploadResult = $this->uploadImages($item, $files, $request, $phones);

            if (!$uploadResult['success']) {
                $item->delete();
                return $uploadResult;
            }

            $uploadedImageCount = count($uploadResult['data']['images'] ?? []);
        }

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
            'message' => $uploadedImageCount > 0
                ? "Item added and {$uploadedImageCount} image(s) uploaded successfully."
                : 'Item added successfully.',
            'data' => [
                'item' => $this->transformItem($item),
            ],
        ];
    }

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

        $removeImageIds = collect($data['remove_image_ids'] ?? [])
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value) => (int) $value)
            ->unique()
            ->values();

        $imagesToRemove = $removeImageIds->isEmpty()
            ? collect()
            : $item->images()->whereIn('id', $removeImageIds->all())->get();

        $files = $request->file('images', []);
        $files = array_values(array_filter(is_array($files) ? $files : []));

        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);
        $currentImageCount = $item->images()->count();
        $finalImageCount = $currentImageCount - $imagesToRemove->count() + count($files);

        if ($finalImageCount > $maxImages) {
            $remaining = max(0, $maxImages - ($currentImageCount - $imagesToRemove->count()));
            return [
                'success' => false,
                'message' => "Cannot upload ".count($files)." images. Maximum {$maxImages} images allowed per item. You can upload {$remaining} more image(s) after removals.",
                'data' => null,
            ];
        }

        $updates = array_filter([
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? null,
        ], fn($value) => $value !== null);

        if ($shipment->destination_mode === ShipmentDestinationMode::PER_ITEM) {
            $perItemFields = [
                'delivery_recipient_name',
                'delivery_region_id',
                'delivery_district_id',
                'delivery_town',
                'delivery_latitude',
                'delivery_longitude',
                'delivery_gh_post_address',
                'delivery_landmark',
                'delivery_instructions',
            ];

            foreach ($perItemFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if (array_key_exists('delivery_recipient_phone', $data)) {
                $updates['delivery_recipient_phone'] = !empty($data['delivery_recipient_phone'])
                    ? PhoneHelper::format($data['delivery_recipient_phone'])
                    : null;
            }

            if (array_key_exists('delivery_preference', $data)) {
                $updates['delivery_preference'] = $data['delivery_preference'];
            }
            if (array_key_exists('fulfillment_type', $data)) {
                $updates['fulfillment_type'] = $data['fulfillment_type'];
            }
        } else {
            $updates = array_merge($updates, [
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
                'delivery_preference' => null,
                'fulfillment_type' => null,
            ]);
        }

        $item->update($updates);

        $removedImageCount = 0;
        if ($imagesToRemove->isNotEmpty()) {
            foreach ($imagesToRemove as $image) {
                $image->delete();
                $removedImageCount++;
            }
        }

        $uploadedImageCount = 0;
        if (!empty($files)) {
            $path = "shipments/{$shipment->id}/items/{$item->id}";
            $nextSortOrder = ((int) ($item->images()->max('sort_order') ?? -1)) + 1;

            foreach ($files as $file) {
                $uploadResult = $this->storageService->upload($file, $path);

                $item->images()->create([
                    'path' => $uploadResult['path'],
                    'original_name' => $uploadResult['original_name'],
                    'size' => $uploadResult['size'],
                    'sort_order' => $nextSortOrder++,
                ]);

                $uploadedImageCount++;
            }
        }

        $item->load(['images', 'deliveryRegion', 'deliveryDistrict']);

        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_updated',
            description: "Updated item in shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
                'images_removed' => $removedImageCount,
                'images_uploaded' => $uploadedImageCount,
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

        $item->delete();

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

        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);
        $currentCount = $item->images()->count();

        if ($currentCount >= $maxImages) {
            return [
                'success' => false,
                'message' => "Maximum {$maxImages} images allowed per item.",
                'data' => null,
            ];
        }

        $path = "shipments/{$shipment->id}/items/{$item->id}";
        $uploadResult = $this->storageService->upload($file, $path);

        $image = $item->images()->create([
            'path' => $uploadResult['path'],
            'original_name' => $uploadResult['original_name'],
            'size' => $uploadResult['size'],
            'sort_order' => $currentCount,
        ]);

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

    public function uploadImages(ShipmentItem $item, array $files, Request $request, array $phones = []): array
    {
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot upload images to a shipment that is not in draft status.',
                'data' => null,
            ];
        }

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

        foreach ($files as $index => $file) {
            $uploadResult = $this->storageService->upload($file, $path);

            $phone = !empty($phones[$index]) ? PhoneHelper::format($phones[$index]) : null;

            $image = $item->images()->create([
                'path' => $uploadResult['path'],
                'original_name' => $uploadResult['original_name'],
                'size' => $uploadResult['size'],
                'sort_order' => $currentCount++,
                'recipient_phone' => $phone,
            ]);

            $uploadedImages[] = $image->getSignedUrl();
        }

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

    public function copyImagesFromRejectedRequest(ShipmentItem $item, array $imageIds, array $phones, Request $request): array
    {
        $shipment = $item->shipment;

        if (!$shipment->canBeEdited()) {
            return [
                'success' => false,
                'message' => 'Cannot reuse images for a shipment that is not in draft status.',
                'data' => null,
            ];
        }

        $imageIds = collect($imageIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($imageIds->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No reused images supplied.',
                'data' => ['images' => []],
            ];
        }

        $sourceImages = ShipmentItemImage::query()
            ->whereIn('id', $imageIds->all())
            ->whereHas('item.shipment', function ($query) use ($shipment) {
                $query->where('vendor_id', $shipment->vendor_id)
                    ->where('status', \App\Enums\ShipmentStatus::REJECTED->value);
            })
            ->get()
            ->keyBy('id');

        if ($sourceImages->count() !== $imageIds->unique()->count()) {
            return [
                'success' => false,
                'message' => 'One or more selected photos cannot be reused.',
                'data' => null,
            ];
        }

        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);
        $currentCount = $item->images()->count();
        if ($currentCount + $imageIds->count() > $maxImages) {
            $remaining = max(0, $maxImages - $currentCount);
            return [
                'success' => false,
                'message' => "Cannot reuse {$imageIds->count()} images. Maximum {$maxImages} images allowed per item. You can add {$remaining} more image(s).",
                'data' => null,
            ];
        }

        $copied = [];
        foreach ($imageIds as $index => $sourceId) {
            $source = $sourceImages->get($sourceId);
            $phone = !empty($phones[$index])
                ? PhoneHelper::format($phones[$index])
                : $source->recipient_phone;

            $image = $item->images()->create([
                'path' => $source->path,
                'original_name' => $source->original_name,
                'size' => $source->size,
                'sort_order' => $currentCount++,
                'recipient_phone' => $phone,
            ]);

            $copied[] = $image->getSignedUrl();
        }

        $this->activityLogService->logVendor(
            vendor: $shipment->vendor,
            action: 'shipment_item_images_reused',
            description: "Reused {$imageIds->count()} image(s) for item in shipment {$shipment->shipment_number}",
            request: $request,
            metadata: [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
                'source_image_ids' => $imageIds->all(),
            ]
        );

        return [
            'success' => true,
            'message' => count($copied) === 1
                ? 'Image reused successfully.'
                : count($copied) . ' images reused successfully.',
            'data' => [
                'images' => $copied,
            ],
        ];
    }

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

        $image->delete();

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

    private function transformItem(ShipmentItem $item): array
    {
        $item->load(['images', 'deliveryRegion', 'deliveryDistrict', 'shipment']);

        return [
            'id' => $item->id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'status' => $item->status?->value ?? 'pending',
            'tracking_code' => $item->tracking_code,
            'delivery_preference' => $item->delivery_preference ?? 'deliver',
            'fulfillment_type' => $item->fulfillment_type?->value,
            'delivery' => $item->shipment && $item->shipment->destination_mode === ShipmentDestinationMode::PER_ITEM ? [
                'recipient_name' => $item->delivery_recipient_name,
                'recipient_phone' => $item->delivery_recipient_phone,
                'location' => $item->formatted_delivery_location,
                'instructions' => $item->delivery_instructions,
            ] : null,
            'images' => $item->images->map(fn($img) => $img->getSignedUrl()),
            'created_at' => $item->created_at->toIso8601String(),
            'updated_at' => $item->updated_at->toIso8601String(),
        ];
    }
}
