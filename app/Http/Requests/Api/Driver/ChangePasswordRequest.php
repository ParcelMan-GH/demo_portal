<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:new_password'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $aliases = [];

        if (! $this->exists('new_password') && $this->exists('password')) {
            $aliases['new_password'] = $this->input('password');
        }

        if (! $this->exists('confirm_password') && $this->exists('password_confirmation')) {
            $aliases['confirm_password'] = $this->input('password_confirmation');
        }

        $this->merge($aliases);
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least 8 characters.',
            'confirm_password.required' => 'Password confirmation is required.',
            'confirm_password.same' => 'Password confirmation does not match.',
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
