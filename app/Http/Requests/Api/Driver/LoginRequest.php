<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Accept `identifier` (preferred) OR legacy `email` field from older clients.
        if (!$this->filled('identifier') && $this->filled('email')) {
            $this->merge(['identifier' => $this->input('email')]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string'],
            'fcm_token'  => ['nullable', 'string', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Email or phone is required.',
            'password.required' => 'Password is required.',
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
