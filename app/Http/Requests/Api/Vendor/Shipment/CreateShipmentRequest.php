<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use App\Enums\ShipmentDestinationMode;
use App\Http\Requests\Api\Vendor\Shipment\Concerns\NormalizesRequestedVehicles;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateShipmentRequest extends FormRequest
{
    use NormalizesRequestedVehicles;

    protected function prepareForValidation(): void
    {
        // Accepts `requested_vehicles` or the legacy `pickup_vehicles`, a JSON
        // string or an array, and rows keyed by `type` slug or `vehicle_type_id`.
        $this->normalizeRequestedVehicles();
    }

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
            /*
             * At least one vehicle is required — a pickup with none on it cannot
             * be dispatched. Enforced here as well as in the app so the rule holds
             * for any client, not just the current build.
             */
            'requested_vehicles' => ['required', 'array', 'min:1'],
            'requested_vehicles.*.vehicle_type_id' => [
                'required',
                'integer',
                Rule::exists('pickup_vehicle_types', 'id')->where('is_active', true),
            ],
            'requested_vehicles.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],

            // Legacy alias. prepareForValidation() has already normalised it into
            // the canonical key; this permissive rule exists so the value survives
            // `validated()` and reaches ShipmentService, which reads this key.
            'pickup_vehicles' => ['sometimes', 'array'],

            // Inline items — at least one item with at least one uploaded or reused image
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
            'items.*.images' => ['nullable', 'array'],
            'items.*.images.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'items.*.reused_image_ids' => ['nullable', 'array'],
            'items.*.reused_image_ids.*' => ['integer', 'exists:shipment_item_images,id'],
            'items.*.reused_image_phones' => ['nullable', 'array'],
            'items.*.reused_image_phones.*' => ['nullable', 'string', 'max:20'],
            'items.*.phones' => ['nullable', 'array'],
            'items.*.phones.*' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate total images across all items (max 500)
            $totalImages = 0;
            $fileItems = $this->file('items', []);
            $inputItems = $this->input('items', []);
            foreach ($inputItems as $index => $itemInput) {
                $uploadedCount = isset($fileItems[$index]['images']) && is_array($fileItems[$index]['images'])
                    ? count($fileItems[$index]['images'])
                    : 0;
                $reusedCount = isset($itemInput['reused_image_ids']) && is_array($itemInput['reused_image_ids'])
                    ? count(array_filter($itemInput['reused_image_ids']))
                    : 0;

                if ($uploadedCount + $reusedCount < 1) {
                    $validator->errors()->add("items.{$index}.images", 'Each item must have at least one image.');
                }

                $totalImages += $uploadedCount + $reusedCount;
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
            'items.*.images.*.max' => 'Each image must be under 2MB.',
            'items.*.images.*.mimes' => 'Images must be JPEG, PNG, or WebP.',
            'requested_vehicles.required' => 'Select at least one pickup vehicle.',
            'requested_vehicles.min' => 'Select at least one pickup vehicle.',
            /*
             * A `type` slug that does not resolve is normalised to a null id, so
             * it surfaces on this rule. The default message ("the
             * vehicle_type_id field is required") is misleading when the client
             * sent a slug, so say what actually happened.
             */
            'requested_vehicles.*.vehicle_type_id.required' => 'One of the selected pickup vehicles is no longer available. Please choose it again.',
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
