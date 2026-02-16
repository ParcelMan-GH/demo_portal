@extends('admin.layouts.app')

@php
    $isWarehouseScope = ($roleScope ?? ($role->is_warehouse_role ? 'warehouse' : 'system')) === 'warehouse';
    $rolesIndexRoute = $isWarehouseScope ? route('admin.roles.warehouse.index') : route('admin.roles.index');
@endphp

@section('title', $role->name)

@section('breadcrumb-parent', 'Roles & Permissions')
@section('breadcrumb-current', $role->name)

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ $rolesIndexRoute }}" class="text-blue-600 hover:text-blue-800 flex items-center">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ $isWarehouseScope ? 'Back to Warehouse Roles' : 'Back to System Roles' }}
            </a>
        </div>
        <div class="flex items-center space-x-4">
            @hasPermission('roles.edit')
                <a href="{{ route('admin.roles.edit', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Edit Role
                </a>
            @endhasPermission

            @hasPermission('roles.delete')
                @if(!$role->is_system_role)
                    <form action="{{ route('admin.roles.destroy', ['role' => $role, 'scope' => $isWarehouseScope ? 'warehouse' : 'system']) }}"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this role?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                            Delete Role
                        </button>
                    </form>
                @endif
            @endhasPermission
        </div>
    </div>

    <!-- Role Information -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $role->name }}</h1>
                <div class="flex items-center space-x-2">
                    @if($role->is_system_role)
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                            System Role
                        </span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                            Custom Role
                        </span>
                    @endif

                    @if($role->is_active)
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            Active
                        </span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $role->description ?? 'No description provided' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Slug</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $role->slug }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Users</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $role->users->count() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Permissions</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $role->permissions->count() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $role->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $role->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Assigned Users -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Assigned Users ({{ $role->users->count() }})</h2>
        </div>

        @if($role->users->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="mt-2">No users have been assigned this role</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
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
                        @foreach($role->users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->is_active)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @hasPermission('users.view')
                                        <a href="{{ route('admin.admins.show', $user) }}"
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                    @endhasPermission
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Permissions -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Permissions ({{ $role->permissions->count() }})</h2>
        </div>

        @if($role->permissions->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="mt-2">No permissions have been assigned to this role</p>
            </div>
        @else
            <div class="px-6 py-4">
                @php
                    $groupedPermissions = $role->permissions->groupBy('module');
                @endphp

                <div class="space-y-4">
                    @foreach($groupedPermissions as $module => $modulePermissions)
                        <div class="border border-gray-200 rounded-md">
                            <div class="px-4 py-3 bg-gray-50">
                                <h3 class="text-sm font-medium text-gray-900 capitalize">
                                    {{ str_replace('_', ' ', $module) }} ({{ $modulePermissions->count() }})
                                </h3>
                            </div>
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($modulePermissions as $permission)
                                        <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                                            {{ $permission->action }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
