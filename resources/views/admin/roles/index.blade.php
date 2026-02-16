@extends('admin.layouts.app')

@php
    $isWarehouseScope = ($roleScope ?? 'system') === 'warehouse';
    $pageTitle = $isWarehouseScope ? 'Warehouse Roles' : 'System Roles';
    $pageDescription = $isWarehouseScope
        ? 'Manage warehouse-specific roles and permissions'
        : 'Manage system roles and their permissions';
@endphp

@section('title', $pageTitle)

@section('breadcrumb-parent', 'Roles & Permissions')
@section('breadcrumb-current', $pageTitle)

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $pageTitle }}</h1>
            <p class="text-gray-600 mt-1">{{ $pageDescription }}</p>
        </div>
        @hasPermission('roles.create')
            <a href="{{ route('admin.roles.create', ['scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Create New Role
            </a>
        @endhasPermission
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Users
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Permissions
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($roles as $role)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $role->name }}</div>
                                    @if($role->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($role->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $role->users_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $role->permissions->count() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($role->is_system_role)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                    System
                                </span>
                            @elseif($role->is_warehouse_role)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800">
                                    Warehouse
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Custom
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($role->is_active)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('admin.roles.show', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
                               class="text-blue-600 hover:text-blue-900">View</a>

                            @hasPermission('roles.edit')
                                <a href="{{ route('admin.roles.edit', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            @endhasPermission

                            @hasPermission('roles.delete')
                                @if(!$role->is_system_role)
                                    <form action="{{ route('admin.roles.destroy', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            @endhasPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <p class="mt-2">No roles found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($roles->hasPages())
        <div class="mt-4">
            {{ $roles->appends(['scope' => $isWarehouseScope ? 'warehouse' : 'system'])->links() }}
        </div>
    @endif
@endsection
