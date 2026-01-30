<?php

namespace App\Http\Requests\Api\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'new_pin' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
            'confirm_pin' => ['required', 'same:new_pin'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.size' => 'OTP must be exactly 6 digits.',
            'otp.regex' => 'OTP must contain only digits.',
            'new_pin.size' => 'New PIN must be exactly 4 digits.',
            'new_pin.regex' => 'New PIN must contain only digits.',
            'confirm_pin.same' => 'PIN confirmation does not match.',
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
