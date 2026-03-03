<?php

namespace App\Http\Requests\Api\Vendor;

use App\Helpers\PhoneHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid($value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }],
            'otp'       => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'fcm_token' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.size' => 'OTP must be exactly 6 digits.',
            'otp.regex' => 'OTP must contain only digits.',
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
