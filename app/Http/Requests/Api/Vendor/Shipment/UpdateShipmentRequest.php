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
            'destination_mode' => ['sometimes', 'string', Rule::in($modes)],
            'fulfillment_type' => ['sometimes', 'string', Rule::in(['warehouse', 'self_pickup', 'direct'])],

            'pickup_contact_name' => ['sometimes', 'string', 'max:255'],
            'pickup_contact_phone' => ['sometimes', 'string', 'max:20'],
            'pickup_contact_phone_confirm' => ['required_with:pickup_contact_phone', 'same:pickup_contact_phone'],
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
            $this->validateLocationGroupWithFallback(
                validator: $validator,
                regionKey: 'pickup_region_id',
                districtKey: 'pickup_district_id',
                latKey: 'pickup_latitude',
                lngKey: 'pickup_longitude',
                ghPostKey: 'pickup_gh_post_address',
                errorPrefix: 'pickup'
            );

            $resolvedMode = $this->resolvedValue('destination_mode');
            $mode = $resolvedMode instanceof ShipmentDestinationMode
                ? $resolvedMode->value
                : (string) $resolvedMode;

            if ($mode === ShipmentDestinationMode::SINGLE->value) {
                if (blank($this->resolvedValue('delivery_recipient_name'))) {
                    $validator->errors()->add('delivery_recipient_name', 'Delivery recipient name is required when destination mode is single.');
                }

                if (blank($this->resolvedValue('delivery_recipient_phone'))) {
                    $validator->errors()->add('delivery_recipient_phone', 'Delivery recipient phone is required when destination mode is single.');
                }

                $this->validateLocationGroupWithFallback(
                    validator: $validator,
                    regionKey: 'delivery_region_id',
                    districtKey: 'delivery_district_id',
                    latKey: 'delivery_latitude',
                    lngKey: 'delivery_longitude',
                    ghPostKey: 'delivery_gh_post_address',
                    errorPrefix: 'delivery'
                );
            }

            if ($mode === ShipmentDestinationMode::PER_ITEM->value) {
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
                    $validator->errors()->add('delivery', 'Do not provide shipment-level delivery fields when destination mode is per_item.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'destination_mode.in' => 'Destination mode must be single or per_item.',
            'pickup_contact_phone_confirm.required_with' => 'Please confirm the pickup contact phone number.',
            'pickup_contact_phone_confirm.same' => 'Pickup phone numbers do not match.',
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

    private function shipment(): ?Shipment
    {
        $shipment = $this->route('shipment');

        return $shipment instanceof Shipment ? $shipment : null;
    }

    private function resolvedValue(string $key): mixed
    {
        if ($this->has($key)) {
            return $this->input($key);
        }

        $shipment = $this->shipment();
        if (!$shipment) {
            return null;
        }

        return $shipment->{$key};
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

    private function validateLocationGroupWithFallback(
        Validator $validator,
        string $regionKey,
        string $districtKey,
        string $latKey,
        string $lngKey,
        string $ghPostKey,
        string $errorPrefix
    ): void {
        $regionValue = $this->resolvedValue($regionKey);
        $districtValue = $this->resolvedValue($districtKey);
        $latValue = $this->resolvedValue($latKey);
        $lngValue = $this->resolvedValue($lngKey);
        $ghPostValue = $this->resolvedValue($ghPostKey);

        $hasDropdown = filled($regionValue) && filled($districtValue);
        $hasCoordinates = filled($latValue) && filled($lngValue);
        $hasGhPost = filled($ghPostValue);

        if (!$hasDropdown && !$hasCoordinates && !$hasGhPost) {
            $validator->errors()->add($errorPrefix . '_location', ucfirst($errorPrefix) . ' location is required: dropdown (region + district), coordinates (latitude + longitude), or Ghana Post address.');
        }

        if ($this->filled($regionKey) && !$this->filled($districtKey) && blank($districtValue)) {
            $validator->errors()->add($districtKey, 'District is required when region is selected.');
        }

        if ($this->filled($latKey) && !$this->filled($lngKey) && blank($lngValue)) {
            $validator->errors()->add($lngKey, 'Longitude is required when latitude is provided.');
        }

        if ($this->filled($lngKey) && !$this->filled($latKey) && blank($latValue)) {
            $validator->errors()->add($latKey, 'Latitude is required when longitude is provided.');
        }
    }
}
