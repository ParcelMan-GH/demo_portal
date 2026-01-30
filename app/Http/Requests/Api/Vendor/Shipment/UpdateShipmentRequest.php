<?php

namespace App\Http\Requests\Api\Vendor\Shipment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateShipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Recipient details (all optional on update)
            'recipient_name' => ['sometimes', 'string', 'max:255'],
            'recipient_phone' => ['sometimes', 'string', 'max:20'],
            'recipient_phone_confirm' => ['required_with:recipient_phone', 'same:recipient_phone'],

            // Location Option 1: Dropdown + Manual
            'region_id' => ['nullable', 'exists:regions,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'town' => ['nullable', 'string', 'max:255'],

            // Location Option 2: GPS Coordinates
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Location Option 3: Ghana Post
            'gh_post_address' => ['nullable', 'string', 'max:50'],

            // Additional
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'landmark' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // If region is provided, district must be too
            if ($this->filled('region_id') && !$this->filled('district_id')) {
                $validator->errors()->add('district_id', 'District is required when region is selected.');
            }

            // If latitude is provided, longitude must be too
            if ($this->filled('latitude') && !$this->filled('longitude')) {
                $validator->errors()->add('longitude', 'Longitude is required when latitude is provided.');
            }

            // If longitude is provided, latitude must be too
            if ($this->filled('longitude') && !$this->filled('latitude')) {
                $validator->errors()->add('latitude', 'Latitude is required when longitude is provided.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recipient_phone_confirm.required_with' => 'Please confirm the recipient phone number.',
            'recipient_phone_confirm.same' => 'Phone numbers do not match.',
            'region_id.exists' => 'Selected region is invalid.',
            'district_id.exists' => 'Selected district is invalid.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
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
