@extends('admin.layouts.app')

@php
    $isWarehouseScope = ($roleScope ?? ($role->is_warehouse_role ? 'warehouse' : 'system')) === 'warehouse';
@endphp

@section('title', ($isWarehouseScope ? 'Edit Warehouse Role - ' : 'Edit Role - ') . $role->name)

@section('breadcrumb-parent', 'Roles & Permissions')
@section('breadcrumb-current', ($isWarehouseScope ? 'Edit Warehouse Role - ' : 'Edit ') . $role->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.roles.show', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Role Details
        </a>
    </div>

    <div class="max-w-3xl">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-900">Edit Role</h1>
                <p class="text-sm text-gray-600">Update role information and permissions for {{ $role->name }}</p>
                @if($role->is_system_role)
                    <div class="mt-2 px-3 py-2 bg-purple-50 border border-purple-200 rounded-md">
                        <p class="text-sm text-purple-700">
                            <strong>Note:</strong> This is a system role. Some restrictions may apply.
                        </p>
                    </div>
                @endif
            </div>

            <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="scope" value="{{ $isWarehouseScope ? 'warehouse' : 'system' }}">

                <!-- Role Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Role Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                        placeholder="e.g., Sales Manager"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                        placeholder="Brief description of this role's responsibilities"
                    >{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            >
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="is_active"
                                value="0"
                                {{ !old('is_active', $role->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            >
                            <span class="ml-2 text-sm text-gray-700">Inactive</span>
                        </label>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Permissions <span class="text-red-500">*</span>
                    </label>

                    @if($permissions->isEmpty())
                        <p class="text-sm text-gray-500 italic">No permissions available. Please seed permissions first.</p>
                    @else
                        <div class="space-y-4" x-data="{ openModules: [] }">
                            @foreach($permissions as $module => $modulePermissions)
                                <div class="border border-gray-200 rounded-md">
                                    <!-- Module Header -->
                                    <button
                                        type="button"
                                        @click="openModules.includes('{{ $module }}') ? openModules = openModules.filter(m => m !== '{{ $module }}') : openModules.push('{{ $module }}')"
                                        class="w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 flex items-center justify-between text-left"
                                    >
                                        <span class="text-sm font-medium text-gray-900 capitalize">
                                            {{ str_replace('_', ' ', $module) }} ({{ $modulePermissions->count() }})
                                        </span>
                                        <svg
                                            class="w-5 h-5 text-gray-500 transition-transform"
                                            :class="openModules.includes('{{ $module }}') ? 'transform rotate-180' : ''"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <!-- Module Permissions -->
                                    <div
                                        x-show="openModules.includes('{{ $module }}')"
                                        x-cloak
                                        class="px-4 py-3 space-y-2 bg-white"
                                    >
                                        @foreach($modulePermissions as $permission)
                                            <label class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->id }}"
                                                    {{
                                                        (is_array(old('permissions')) && in_array($permission->id, old('permissions')))
                                                        || (empty(old('permissions')) && in_array($permission->id, $rolePermissions))
                                                        ? 'checked'
                                                        : ''
                                                    }}
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                >
                                                <span class="ml-2 text-sm text-gray-700">
                                                    {{ $permission->label() }}
                                                    @if($permission->description)
                                                        <span class="text-gray-500">({{ $permission->description }})</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('permissions')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.roles.show', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Update Role
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
