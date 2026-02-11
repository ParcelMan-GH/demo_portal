<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use App\Enums\ShipmentDestinationMode;
use App\Models\PlatformSetting;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);

        return [
            'description' => ['sometimes', 'string', 'max:500'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'images' => ['sometimes', 'array', 'min:1', 'max:'.$maxImages],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image_ids' => ['sometimes', 'array', 'min:1'],
            'remove_image_ids.*' => ['required', 'integer', 'distinct', 'exists:shipment_item_images,id'],

            'delivery_recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'delivery_recipient_phone_confirm' => ['required_with:delivery_recipient_phone', 'same:delivery_recipient_phone'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_gh_post_address' => ['nullable', 'string', 'max:50'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $shipment = $this->route('shipment');
            if (!$shipment instanceof Shipment) {
                return;
            }

            if ($shipment->destination_mode === ShipmentDestinationMode::PER_ITEM) {
                if (blank($this->resolvedValue('delivery_recipient_name'))) {
                    $validator->errors()->add('delivery_recipient_name', 'Delivery recipient name is required for per_item destination mode.');
                }

                if (blank($this->resolvedValue('delivery_recipient_phone'))) {
                    $validator->errors()->add('delivery_recipient_phone', 'Delivery recipient phone is required for per_item destination mode.');
                }

                $this->validateDeliveryLocationWithFallback($validator);
            } else {
                if ($this->hasAnyValues([
                    'delivery_recipient_name',
                    'delivery_recipient_phone',
                    'delivery_region_id',
                    'delivery_district_id',
                    'delivery_town',
                    'delivery_latitude',
                    'delivery_longitude',
                    'delivery_gh_post_address',
                    'delivery_landmark',
                    'delivery_instructions',
                ])) {
                    $validator->errors()->add('delivery', 'Item-level delivery fields are not allowed when shipment destination mode is single.');
                }
            }

            $item = $this->item();
            if (!$item || !$this->filled('remove_image_ids')) {
                return;
            }

            $removeIds = collect($this->input('remove_image_ids', []))
                ->filter(fn($value) => $value !== null && $value !== '')
                ->map(fn($value) => (int) $value)
                ->unique()
                ->values();

            if ($removeIds->isEmpty()) {
                return;
            }

            $countBelongingToItem = $item->images()
                ->whereIn('id', $removeIds->all())
                ->count();

            if ($countBelongingToItem !== $removeIds->count()) {
                $validator->errors()->add('remove_image_ids', 'One or more selected images do not belong to this item.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'description.max' => 'Item description cannot exceed 500 characters.',
            'quantity.min' => 'Quantity must be at least 1.',
            'images.array' => 'Images must be provided as an array.',
            'images.min' => 'At least one image file is required when images are provided.',
            'images.max' => 'Too many images provided. Reduce image count and try again.',
            'images.*.required' => 'Each image file is required.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a JPEG, PNG, or WebP file.',
            'images.*.max' => 'Each image must not exceed 5MB.',
            'remove_image_ids.array' => 'remove_image_ids must be an array.',
            'remove_image_ids.min' => 'Select at least one image to remove.',
            'remove_image_ids.*.distinct' => 'Duplicate image IDs are not allowed in remove_image_ids.',
            'delivery_recipient_phone_confirm.required_with' => 'Please confirm the delivery recipient phone number.',
            'delivery_recipient_phone_confirm.same' => 'Delivery phone numbers do not match.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    private function item(): ?ShipmentItem
    {
        $item = $this->route('item');

        return $item instanceof ShipmentItem ? $item : null;
    }

    private function resolvedValue(string $key): mixed
    {
        if ($this->has($key)) {
            return $this->input($key);
        }

        $item = $this->item();
        if (!$item) {
            return null;
        }

        return $item->{$key};
    }

    private function hasAnyValues(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

    private function validateDeliveryLocationWithFallback(Validator $validator): void
    {
        $regionValue = $this->resolvedValue('delivery_region_id');
        $districtValue = $this->resolvedValue('delivery_district_id');
        $latValue = $this->resolvedValue('delivery_latitude');
        $lngValue = $this->resolvedValue('delivery_longitude');
        $ghPostValue = $this->resolvedValue('delivery_gh_post_address');

        $hasDropdown = filled($regionValue) && filled($districtValue);
        $hasCoordinates = filled($latValue) && filled($lngValue);
        $hasGhPost = filled($ghPostValue);

        if (!$hasDropdown && !$hasCoordinates && !$hasGhPost) {
            $validator->errors()->add('delivery_location', 'Delivery location is required: dropdown (region + district), coordinates (latitude + longitude), or Ghana Post address.');
        }

        if ($this->filled('delivery_region_id') && !$this->filled('delivery_district_id') && blank($districtValue)) {
            $validator->errors()->add('delivery_district_id', 'District is required when region is selected.');
        }

        if ($this->filled('delivery_latitude') && !$this->filled('delivery_longitude') && blank($lngValue)) {
            $validator->errors()->add('delivery_longitude', 'Longitude is required when latitude is provided.');
        }

        if ($this->filled('delivery_longitude') && !$this->filled('delivery_latitude') && blank($latValue)) {
            $validator->errors()->add('delivery_latitude', 'Latitude is required when longitude is provided.');
        }
    }
}
