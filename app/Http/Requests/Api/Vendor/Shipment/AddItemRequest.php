<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use App\Enums\ShipmentDestinationMode;
use App\Models\PlatformSetting;
use App\Models\Shipment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxImages = (int) PlatformSetting::getValue('shipment.max_images_per_item', 5);

        return [
            'description' => ['required', 'string', 'max:500'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'images' => ['sometimes', 'array', 'min:1', 'max:'.$maxImages],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_recipient_phone_confirm' => ['nullable', 'string', 'same:delivery_recipient_phone'],
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
                if (!$this->filled('delivery_recipient_name')) {
                    $validator->errors()->add('delivery_recipient_name', 'Delivery recipient name is required for per_item destination mode.');
                }

                if (!$this->filled('delivery_recipient_phone')) {
                    $validator->errors()->add('delivery_recipient_phone', 'Delivery recipient phone is required for per_item destination mode.');
                }

                if (!$this->filled('delivery_recipient_phone_confirm')) {
                    $validator->errors()->add('delivery_recipient_phone_confirm', 'Please confirm the delivery recipient phone number.');
                }

                $this->validateDeliveryLocation($validator);
                return;
            }

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
        });
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Item description is required.',
            'description.max' => 'Item description cannot exceed 500 characters.',
            'quantity.min' => 'Quantity must be at least 1.',
            'images.array' => 'Images must be provided as an array.',
            'images.min' => 'At least one image file is required when images are provided.',
            'images.max' => 'Too many images provided. Reduce image count and try again.',
            'images.*.required' => 'Each image file is required.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a JPEG, PNG, or WebP file.',
            'images.*.max' => 'Each image must not exceed 5MB.',
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

    private function hasAnyValues(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

    private function validateDeliveryLocation(Validator $validator): void
    {
        $hasDropdown = $this->filled('delivery_region_id') && $this->filled('delivery_district_id');
        $hasCoordinates = $this->filled('delivery_latitude') && $this->filled('delivery_longitude');
        $hasGhPost = $this->filled('delivery_gh_post_address');

        if (!$hasDropdown && !$hasCoordinates && !$hasGhPost) {
            $validator->errors()->add('delivery_location', 'Delivery location is required: dropdown (region + district), coordinates (latitude + longitude), or Ghana Post address.');
        }

        if ($this->filled('delivery_region_id') && !$this->filled('delivery_district_id')) {
            $validator->errors()->add('delivery_district_id', 'District is required when region is selected.');
        }

        if ($this->filled('delivery_latitude') && !$this->filled('delivery_longitude')) {
            $validator->errors()->add('delivery_longitude', 'Longitude is required when latitude is provided.');
        }

        if ($this->filled('delivery_longitude') && !$this->filled('delivery_latitude')) {
            $validator->errors()->add('delivery_latitude', 'Latitude is required when longitude is provided.');
        }
    }
}
