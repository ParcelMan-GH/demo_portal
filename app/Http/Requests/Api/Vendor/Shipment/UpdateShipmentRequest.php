<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use App\Enums\ShipmentDestinationMode;
use App\Http\Requests\Api\Vendor\Shipment\Concerns\NormalizesRequestedVehicles;
use App\Models\Shipment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
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

        $shipment = $this->route('shipment');
        $isSubmitted = $shipment instanceof Shipment && $shipment->status->value === 'submitted';

        // Submitted shipments: vendors can only edit these fields + photos
        if ($isSubmitted) {
            return [
                'destination_mode' => ['sometimes', 'string', Rule::in($modes)],
                'pickup_town' => ['sometimes', 'nullable', 'string', 'max:255'],
                'sender_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'vendor_declared_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
                /*
                 * `sometimes`, not `required`: a partial update — a photo-only
                 * save, say — must not be forced to resend the vehicle list. But
                 * when the key IS present it must carry at least one, so an
                 * explicitly cleared selection cannot slip through.
                 */
                'requested_vehicles' => ['sometimes', 'array', 'min:1'],
                'requested_vehicles.*.vehicle_type_id' => [
                    'required',
                    'integer',
                    Rule::exists('pickup_vehicle_types', 'id')->where('is_active', true),
                ],
                'requested_vehicles.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
                // Legacy alias, normalised above; kept so it reaches the service.
                'pickup_vehicles' => ['sometimes', 'array'],
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
            // See the submitted branch above for why this is `sometimes`.
            'requested_vehicles' => ['sometimes', 'array', 'min:1'],
            'requested_vehicles.*.vehicle_type_id' => [
                'required',
                'integer',
                Rule::exists('pickup_vehicle_types', 'id')->where('is_active', true),
            ],
            'requested_vehicles.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            // Legacy alias, normalised above; kept so it reaches the service.
            'pickup_vehicles' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_mode.in' => 'Destination mode must be single or per_item.',
            'pickup_contact_phone_confirm.same' => 'Pickup phone numbers do not match.',
            'delivery_recipient_phone_confirm.same' => 'Delivery phone numbers do not match.',
            'requested_vehicles.min' => 'Select at least one pickup vehicle.',
            // See CreateShipmentRequest: an unresolved `type` slug lands here.
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
