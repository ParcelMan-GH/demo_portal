<?php

namespace App\Http\Requests\Admin;

use App\Helpers\PhoneHelper;
use App\Models\Role;
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->filled('phone')) {
            $this->merge([
                'phone' => PhoneHelper::format((string) $this->input('phone')) ?? $this->input('phone'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $adminId = $this->route('admin')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($adminId)],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid((string) $value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, Rule::unique('users', 'phone')->ignore($adminId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'roles' => ['nullable', 'array', 'max:1'],
            'roles.*' => ['exists:roles,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
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

            // Cannot deactivate yourself
            if ($this->has('is_active') && !$this->boolean('is_active') && $currentAdmin->id === $targetAdmin->id) {
                $validator->errors()->add('is_active', 'You cannot deactivate yourself.');
            }

            // Warehouse-scoped users can only receive warehouse roles.
            $selectedRoleId = $this->selectedRoleId();
            if (!$selectedRoleId) {
                return;
            }

            $warehouseId = $this->input('warehouse_id', $targetAdmin->warehouse_id);

            if ($warehouseId) {
                $isWarehouseRole = Role::query()
                    ->whereKey($selectedRoleId)
                    ->warehouseRoles()
                    ->exists();

                if (!$isWarehouseRole) {
                    $validator->errors()->add('role_id', 'Warehouse users can only be assigned warehouse roles.');
                }
                return;
            }

            $isWarehouseRole = Role::query()
                ->whereKey($selectedRoleId)
                ->warehouseRoles()
                ->exists();

            if ($isWarehouseRole) {
                $validator->errors()->add('role_id', 'System users cannot be assigned warehouse roles.');
            }
        });
    }

    private function selectedRoleId(): ?int
    {
        if ($this->filled('role_id')) {
            return (int) $this->input('role_id');
        }

        $roles = array_values(array_filter((array) $this->input('roles')));
        if (empty($roles)) {
            return null;
        }

        return (int) $roles[0];
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
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'This phone number is already in use.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
