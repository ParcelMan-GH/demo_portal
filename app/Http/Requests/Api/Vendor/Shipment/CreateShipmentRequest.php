<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use App\Enums\ShipmentDestinationMode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $modes = array_map(
            fn(ShipmentDestinationMode $mode) => $mode->value,
            ShipmentDestinationMode::cases()
        );

        return [
            'destination_mode' => ['required', 'string', Rule::in($modes)],
            'delivery_preference' => ['nullable', 'string', Rule::in(['deliver', 'self_pickup'])],
            'fulfillment_type' => ['nullable', 'string', Rule::in(['warehouse', 'self_pickup', 'direct'])],

            // Pickup — only name and phone required
            'pickup_contact_name' => ['required', 'string', 'max:255'],
            'pickup_contact_phone' => ['required', 'string', 'max:20'],
            'pickup_contact_phone_confirm' => ['nullable', 'string', 'same:pickup_contact_phone'],
            'pickup_region_id' => ['nullable', 'exists:regions,id'],
            'pickup_district_id' => ['nullable', 'exists:districts,id'],
            'pickup_town' => ['nullable', 'string', 'max:255'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_gh_post_address' => ['nullable', 'string', 'max:50'],
            'pickup_landmark' => ['nullable', 'string', 'max:255'],
            'pickup_instructions' => ['nullable', 'string', 'max:1000'],

            // Delivery — all optional
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

            // Sender notes
            'sender_notes' => ['nullable', 'string', 'max:2000'],
            'vendor_declared_quantity' => ['nullable', 'integer', 'min:1'],

            // Inline items — at least one item with at least one image
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.delivery_preference' => ['nullable', 'string', Rule::in(['deliver', 'self_pickup'])],
            'items.*.delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'items.*.delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'items.*.delivery_town' => ['nullable', 'string', 'max:255'],
            'items.*.delivery_region_id' => ['nullable', 'exists:regions,id'],
            'items.*.delivery_district_id' => ['nullable', 'exists:districts,id'],
            'items.*.delivery_landmark' => ['nullable', 'string', 'max:255'],
            'items.*.delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'items.*.images' => ['required', 'array', 'min:1'],
            'items.*.images.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'items.*.phones' => ['nullable', 'array'],
            'items.*.phones.*' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate total images across all items (max 500)
            $totalImages = 0;
            $items = $this->file('items', []);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['images']) && is_array($item['images'])) {
                        $totalImages += count($item['images']);
                    }
                }
            }
            if ($totalImages > 500) {
                $validator->errors()->add('items', 'Maximum 500 images allowed across all items. You uploaded ' . $totalImages . '.');
            }

            // Per-item mode: don't send shipment-level delivery fields
            $mode = $this->input('destination_mode');
            if ($mode === ShipmentDestinationMode::PER_ITEM->value) {
                $deliveryFields = [
                    'delivery_recipient_name', 'delivery_recipient_phone',
                    'delivery_region_id', 'delivery_district_id', 'delivery_town',
                    'delivery_latitude', 'delivery_longitude',
                    'delivery_gh_post_address', 'delivery_landmark', 'delivery_instructions',
                ];
                foreach ($deliveryFields as $field) {
                    if ($this->filled($field)) {
                        $validator->errors()->add('delivery', 'Do not provide shipment-level delivery fields when destination mode is per_item.');
                        break;
                    }
                }
            }

            // Pickup location: validate partial coordinate pairs
            if ($this->filled('pickup_region_id') && !$this->filled('pickup_district_id')) {
                $validator->errors()->add('pickup_district_id', 'District is required when region is selected.');
            }
            if ($this->filled('pickup_latitude') && !$this->filled('pickup_longitude')) {
                $validator->errors()->add('pickup_longitude', 'Longitude is required when latitude is provided.');
            }
            if ($this->filled('pickup_longitude') && !$this->filled('pickup_latitude')) {
                $validator->errors()->add('pickup_latitude', 'Latitude is required when longitude is provided.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'destination_mode.required' => 'Destination mode is required.',
            'destination_mode.in' => 'Destination mode must be single or per_item.',
            'pickup_contact_name.required' => 'Pickup contact name is required.',
            'pickup_contact_phone.required' => 'Pickup contact phone number is required.',
            'pickup_contact_phone_confirm.same' => 'Pickup phone numbers do not match.',
            'delivery_recipient_phone_confirm.same' => 'Delivery phone numbers do not match.',
            'items.required' => 'At least one item with images is required.',
            'items.min' => 'At least one item with images is required.',
            'items.*.images.required' => 'Each item must have at least one image.',
            'items.*.images.min' => 'Each item must have at least one image.',
            'items.*.images.*.max' => 'Each image must be under 2MB.',
            'items.*.images.*.mimes' => 'Images must be JPEG, PNG, or WebP.',
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
}
