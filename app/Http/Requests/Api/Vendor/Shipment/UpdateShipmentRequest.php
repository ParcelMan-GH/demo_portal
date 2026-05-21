<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use App\Enums\ShipmentDestinationMode;
use App\Models\Shipment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('pickup_vehicles'))) {
            $decoded = json_decode($this->input('pickup_vehicles'), true);
            if (is_array($decoded)) {
                $this->merge(['pickup_vehicles' => $decoded]);
            }
        }
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

        $shipment = $this->route('shipment');
        $isSubmitted = $shipment instanceof Shipment && $shipment->status->value === 'submitted';

        // Submitted shipments: vendors can only edit these fields + photos
        if ($isSubmitted) {
            return [
                'destination_mode' => ['sometimes', 'string', Rule::in($modes)],
                'pickup_town' => ['sometimes', 'nullable', 'string', 'max:255'],
                'sender_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'vendor_declared_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'pickup_vehicles' => ['sometimes', 'array'],
                'pickup_vehicles.*.vehicle_type_id' => [
                    'required_with:pickup_vehicles',
                    'integer',
                    Rule::exists('pickup_vehicle_types', 'id')->where('is_active', true),
                ],
                'pickup_vehicles.*.quantity' => ['required_with:pickup_vehicles', 'integer', 'min:1', 'max:99'],
                'new_photos' => ['sometimes', 'array'],
                'new_photos.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
                'new_photos_phones' => ['nullable', 'array'],
                'new_photos_phones.*' => ['nullable', 'string', 'max:20'],
                'remove_photo_ids' => ['sometimes', 'array'],
                'remove_photo_ids.*' => ['integer'],
            ];
        }

        // Draft shipments: full editing
        return [
            'destination_mode' => ['sometimes', 'string', Rule::in($modes)],
            'delivery_preference' => ['sometimes', 'string', Rule::in(['deliver', 'self_pickup'])],
            'fulfillment_type' => ['sometimes', 'string', Rule::in(['warehouse', 'self_pickup', 'direct'])],

            'pickup_contact_name' => ['sometimes', 'string', 'max:255'],
            'pickup_contact_phone' => ['sometimes', 'string', 'max:20'],
            'pickup_contact_phone_confirm' => ['nullable', 'same:pickup_contact_phone'],
            'pickup_region_id' => ['nullable', 'exists:regions,id'],
            'pickup_district_id' => ['nullable', 'exists:districts,id'],
            'pickup_town' => ['nullable', 'string', 'max:255'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_gh_post_address' => ['nullable', 'string', 'max:50'],
            'pickup_landmark' => ['nullable', 'string', 'max:255'],
            'pickup_instructions' => ['nullable', 'string', 'max:1000'],

            'delivery_recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'delivery_recipient_phone_confirm' => ['nullable', 'same:delivery_recipient_phone'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_gh_post_address' => ['nullable', 'string', 'max:50'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],

            'sender_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'vendor_declared_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'pickup_vehicles' => ['sometimes', 'array'],
            'pickup_vehicles.*.vehicle_type_id' => [
                'required_with:pickup_vehicles',
                'integer',
                Rule::exists('pickup_vehicle_types', 'id')->where('is_active', true),
            ],
            'pickup_vehicles.*.quantity' => ['required_with:pickup_vehicles', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_mode.in' => 'Destination mode must be single or per_item.',
            'pickup_contact_phone_confirm.same' => 'Pickup phone numbers do not match.',
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
}
