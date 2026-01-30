<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $currentAdmin = Auth::guard('admin')->user();
        $targetAdmin = $this->route('admin');

        return $currentAdmin && $currentAdmin->canManage($targetAdmin);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $adminId = $this->route('admin')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('admins')->ignore($adminId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::enum(AdminRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $currentAdmin = Auth::guard('admin')->user();
            $targetAdmin = $this->route('admin');

            // Cannot change your own role
            if ($this->has('role') && $currentAdmin->id === $targetAdmin->id) {
                $validator->errors()->add('role', 'You cannot change your own role.');
            }

            // Cannot deactivate yourself
            if ($this->has('is_active') && !$this->boolean('is_active') && $currentAdmin->id === $targetAdmin->id) {
                $validator->errors()->add('is_active', 'You cannot deactivate yourself.');
            }

            // Warehouse managers can only assign staff role
            if ($currentAdmin->isWarehouseManager() && $this->has('role')) {
                if ($this->input('role') !== AdminRole::WAREHOUSE_STAFF->value) {
                    $validator->errors()->add('role', 'You can only assign the warehouse staff role.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.enum' => 'Invalid role selected.',
        ];
    }
}
