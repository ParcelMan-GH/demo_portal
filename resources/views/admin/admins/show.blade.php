@extends('admin.layouts.app')

@section('title', 'View Admin - ' . $admin->name)

@section('breadcrumb-parent', 'User Management')
@section('breadcrumb-current', $admin->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.admins.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to User Management
        </a>
    </div>

    <div class="max-w-3xl">
        <div class="bg-white rounded-lg shadow">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center">
                    <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-2xl font-semibold">
                        {{ substr($admin->name, 0, 1) }}
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-semibold text-gray-900">{{ $admin->name }}</h1>
                        <p class="text-gray-600">{{ $admin->email }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @if(Auth::guard('admin')->user()->canManage($admin))
                        <a href="{{ route('admin.admins.edit', $admin) }}"
                           class="inline-flex items-center px-3 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </a>

                        @if($admin->id !== Auth::guard('admin')->id())
                            <form action="{{ route('admin.admins.toggle-active', $admin) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Are you sure you want to {{ $admin->is_active ? 'deactivate' : 'activate' }} this admin?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-2 {{ $admin->is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' }} text-white rounded-md transition-colors">
                                    @if($admin->is_active)
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Deactivate
                                    @else
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Activate
                                    @endif
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Details -->
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Roles -->
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 mb-2">Assigned Roles</dt>
                        <dd class="mt-1">
                            @if($admin->roles->isEmpty())
                                <span class="text-sm text-gray-500 italic">No roles assigned</span>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($admin->roles as $role)
                                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </dd>
                    </div>

                    <!-- Status -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($admin->is_active)
                                <span class="inline-flex px-2 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </dd>
                    </div>

                    <!-- Total Permissions -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Permissions</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $admin->getAllPermissions()->count() }}
                        </dd>
                    </div>

                    <!-- Created By -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created By</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($admin->creator)
                                <a href="{{ route('admin.admins.show', $admin->creator) }}" class="text-blue-600 hover:underline">
                                    {{ $admin->creator->name }}
                                </a>
                            @else
                                <span class="text-gray-500">System</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Created At -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $admin->created_at->format('M d, Y H:i') }}
                            <span class="text-gray-500">({{ $admin->created_at->diffForHumans() }})</span>
                        </dd>
                    </div>

                    <!-- Last Login -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($admin->last_login_at)
                                {{ $admin->last_login_at->format('M d, Y H:i') }}
                                <span class="text-gray-500">({{ $admin->last_login_at->diffForHumans() }})</span>
                            @else
                                <span class="text-gray-500">Never logged in</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Updated At -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $admin->updated_at->format('M d, Y H:i') }}
                            <span class="text-gray-500">({{ $admin->updated_at->diffForHumans() }})</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Permissions -->
            @if($admin->roles->count() > 0)
                <div class="px-6 py-4 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">All Permissions ({{ $admin->getAllPermissions()->count() }})</h3>
                    @php
                        $groupedPermissions = $admin->getAllPermissions()->groupBy('module');
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($groupedPermissions as $module => $permissions)
                            <div class="border border-gray-200 rounded-md p-3">
                                <h4 class="text-sm font-medium text-gray-900 capitalize mb-2">
                                    {{ str_replace('_', ' ', $module) }}
                                </h4>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($permissions as $permission)
                                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">
                                            {{ $permission->action }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Created Users (if any) -->
            @if($admin->createdUsers->count() > 0)
                <div class="px-6 py-4 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Created Users ({{ $admin->createdUsers->count() }})</h3>
                    <div class="space-y-2">
                        @foreach($admin->createdUsers as $createdUser)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-sm font-semibold">
                                        {{ substr($createdUser->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <a href="{{ route('admin.admins.show', $createdUser) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                            {{ $createdUser->name }}
                                        </a>
                                        <p class="text-xs text-gray-500">{{ $createdUser->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @foreach($createdUser->roles as $role)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                    @if($createdUser->is_active)
                                        <span class="inline-flex w-2 h-2 rounded-full bg-green-500"></span>
                                    @else
                                        <span class="inline-flex w-2 h-2 rounded-full bg-red-500"></span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
