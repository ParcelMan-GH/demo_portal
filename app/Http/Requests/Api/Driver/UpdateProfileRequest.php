<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Aligned with the `drivers` table — see DriverProfileService::updateProfile.
            // `email` is added because the profile screen collects it and login
            // accepts it; the rest were already correct.
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'vehicle_type' => ['sometimes', 'required', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'vehicle_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'make_model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'license_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            // A label, not a number: "3,500 kg" would fail a numeric rule.
            'max_capacity' => ['sometimes', 'nullable', 'string', 'max:50'],
            'base_location' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'phone.required' => 'Phone is required.',
            'vehicle_type.in' => 'Vehicle type must be: motorcycle, car, van, or truck.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
        ], 422));
    }
}
