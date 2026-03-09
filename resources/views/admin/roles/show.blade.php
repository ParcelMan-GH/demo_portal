@extends('admin.layouts.app')

@php
    $isWarehouseScope = ($roleScope ?? ($role->is_warehouse_role ? 'warehouse' : 'system')) === 'warehouse';
    $scope = $isWarehouseScope ? 'warehouse' : 'system';
    $backUrl = $isWarehouseScope ? route('admin.roles.warehouse.index') : route('admin.roles.index');
    $currentAdmin = Auth::guard('admin')->user();
    $canEditRole = $currentAdmin?->hasPermission('roles.edit');

    $initialTab = request('tab', 'permissions');
    if (!in_array($initialTab, ['permissions', 'users'], true)) {
        $initialTab = 'permissions';
    }

    $scopedPermissions = $role->permissions
        ->when($isWarehouseScope, fn ($collection) => $collection->where('module', 'warehouse'))
        ->when(!$isWarehouseScope, fn ($collection) => $collection->whereNotIn('module', ['warehouse', 'warehouse_roles']));

    $groupedPermissions = $scopedPermissions
        ->sortBy(fn ($permission) => ($permission->module ?? 'general') . '-' . ($permission->action ?? $permission->name))
        ->groupBy(fn ($permission) => $permission->module ?? 'general');

    $assignedUsers = $role->users
        ->sortByDesc('created_at')
        ->values()
        ->map(function ($user) use ($role) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_name' => $role->name,
                'is_active' => (bool) $user->is_active,
                'status_label' => $user->is_active ? 'Active' : 'Inactive',
                'created_at_raw' => optional($user->created_at)->toISOString(),
                'created_at' => optional($user->created_at)->format('M d, Y, h:i A') ?: '-',
                'last_login_at_raw' => optional($user->last_login_at)->toISOString(),
                'last_login_at' => optional($user->last_login_at)->format('M d, Y, h:i A') ?: 'Never',
                'view_url' => route('admin.admins.show', $user),
            ];
        });

    $roleShowConfig = [
        'initialTab' => $initialTab,
        'users' => $assignedUsers,
    ];
@endphp

@section('title', ($isWarehouseScope ? 'Warehouse Role - ' : 'System Role - ') . $role->name)
@section('breadcrumb-parent', 'Roles & Permissions')
@section('breadcrumb-current', $role->name)

@section('content')
<div class="space-y-6" x-data="roleShowPage" data-role-show-config='@json($roleShowConfig)'>
    <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 shadow-2xl shadow-slate-900/25">
        <div class="relative">
            <div class="pointer-events-none absolute inset-0 opacity-10">
                <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="role-show-grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#role-show-grid)"/>
                </svg>
            </div>

            <div class="relative px-6 py-6 lg:px-8">
                <div class="mb-6 flex items-center justify-between gap-3">
                    <a href="{{ $backUrl }}" class="group inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>{{ $isWarehouseScope ? 'Back to Warehouse Roles' : 'Back to System Roles' }}</span>
                    </a>

                    @if($canEditRole)
                        <a href="{{ route('admin.roles.edit', ['role' => $role, 'scope' => $scope]) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold text-white shadow-sm backdrop-blur-sm transition-all hover:bg-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Role
                        </a>
                    @endif
                </div>

                <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                    <div class="flex items-start gap-4">
                        <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-blue-600 text-white shadow-xl shadow-indigo-500/25 ring-4 ring-white/10">
                            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m-6-8h6m4 11H5a2 2 0 01-2-2V7a2 2 0 012-2h9l5 5v7a2 2 0 01-2 2z"/>
                            </svg>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <h1 class="truncate text-2xl font-bold text-white">{{ $role->name }}</h1>
                            <p class="text-xs text-slate-300">{{ $role->description ?: 'No description provided for this role.' }}</p>
                            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold {{ $role->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-blue-500/20 px-2 py-0.5 font-semibold text-blue-300">
                                    {{ $isWarehouseScope ? 'Warehouse Role' : 'System Role' }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-500/20 px-2 py-0.5 font-semibold text-slate-300">
                                    {{ $role->created_at?->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 lg:ml-auto lg:flex-nowrap">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 backdrop-blur-sm">
                            <p class="text-lg font-bold text-white leading-none">{{ $assignedUsers->count() }}</p>
                            <p class="mt-1 text-[10px] font-medium text-slate-400">Assigned Users</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 backdrop-blur-sm">
                            <p class="text-lg font-bold text-white leading-none">{{ $scopedPermissions->count() }}</p>
                            <p class="mt-1 text-[10px] font-medium text-slate-400">Permissions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm flex min-h-[560px]">

        {{-- Side Nav --}}
        <div class="w-48 flex-shrink-0 bg-slate-50/80 border-r border-slate-200 py-4 px-3 flex flex-col gap-1">

            <button type="button" @@click="setTab('permissions')"
                    class="flex items-center gap-2.5 w-full px-3 py-2.5 text-sm font-medium transition-all text-left rounded-xl"
                    :class="activeTab === 'permissions' ? 'bg-white text-slate-900 shadow-sm border border-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'">
                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg transition-colors"
                     :class="activeTab === 'permissions' ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-200/60 text-slate-400'">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="truncate">Permissions</span>
                <span class="ml-auto inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[10px] font-bold transition-colors"
                      :class="activeTab === 'permissions' ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-200/60 text-slate-400'">{{ $scopedPermissions->count() }}</span>
            </button>

            <button type="button" @@click="setTab('users')"
                    class="flex items-center gap-2.5 w-full px-3 py-2.5 text-sm font-medium transition-all text-left rounded-xl"
                    :class="activeTab === 'users' ? 'bg-white text-slate-900 shadow-sm border border-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'">
                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg transition-colors"
                     :class="activeTab === 'users' ? 'bg-sky-100 text-sky-600' : 'bg-slate-200/60 text-slate-400'">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="truncate">Assigned Users</span>
                <span class="ml-auto inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[10px] font-bold transition-colors"
                      :class="activeTab === 'users' ? 'bg-sky-100 text-sky-600' : 'bg-slate-200/60 text-slate-400'">{{ $assignedUsers->count() }}</span>
            </button>

        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
        <div x-show="activeTab === 'permissions'" x-cloak x-transition.opacity.duration.150ms>
            @if($groupedPermissions->isNotEmpty())
                <div class="p-6">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($groupedPermissions as $module => $permissions)
                            @php
                                $moduleLabel = Str::of($module)->replace('_', ' ')->title()->toString();
                            @endphp
                            <div class="group rounded-xl border border-slate-200 bg-white p-4 transition-all duration-200 hover:border-slate-300 hover:shadow-md hover:shadow-slate-200/40">
                                <div class="mb-3 flex items-center gap-2.5 border-b border-slate-100 pb-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-slate-500/15 to-slate-700/10 text-slate-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-xs font-bold tracking-wide text-slate-800">{{ $moduleLabel }}</h3>
                                    <span class="ml-auto inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-slate-100 px-1.5 text-[10px] font-bold text-slate-500">{{ $permissions->count() }}</span>
                                </div>

                                <div class="space-y-1.5">
                                    @foreach($permissions as $permission)
                                        <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 ring-1 ring-slate-200/60" title="{{ $permission->name }}">
                                            <svg class="h-3 w-3 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                            </svg>
                                            <span class="text-[11px] font-semibold text-slate-700">{{ $permission->description ?: Str::of($permission->action)->replace('_', ' ')->title()->toString() }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                        <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-500">No permissions assigned to this role yet.</p>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'users'" x-cloak x-transition.opacity.duration.150ms>
            <div class="p-6 pb-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="search" @@input.debounce.300ms="meta.current_page = 1; applyFilters()" placeholder="Search users..." class="w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 py-2 pr-10 text-sm text-slate-900 placeholder-slate-400 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <svg class="absolute right-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div x-data="{ open: false }" class="relative w-full sm:w-56">
                            <button type="button" @@click="open = !open" class="inline-flex w-full items-center justify-between rounded-xl border border-slate-200/70 bg-white/70 px-3 py-2 text-sm font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90">
                                <span x-text="statusFilterName"></span>
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display:none;">
                                <button type="button" @@click="setStatusFilter('', 'All statuses'); open = false" class="w-full rounded-full px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-white/70" :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">All statuses</button>
                                <button type="button" @@click="setStatusFilter('1', 'Active'); open = false" class="mt-2 w-full rounded-full px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-white/70" :class="statusFilter === '1' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Active</button>
                                <button type="button" @@click="setStatusFilter('0', 'Inactive'); open = false" class="mt-2 w-full rounded-full px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-white/70" :class="statusFilter === '0' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Inactive</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur-sm transition-colors hover:bg-white/90">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display:none;">
                                <template x-for="col in columns" :key="col.key">
                                    <button type="button" @@click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur-sm transition-colors hover:bg-white/90">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display:none;">
                                <button type="button" @@click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-white/70">CSV</button>
                                <button type="button" @@click="exportData('excel'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-white/70">Excel</button>
                                <button type="button" @@click="exportData('pdf'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-white/70">PDF</button>
                                <button type="button" @@click="printData(); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-white/70">Print</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4">
                <div class="relative overflow-hidden rounded-xl border border-slate-200/50">
                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th x-show="visibleColumns.name" @@click="sort('name')" class="cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center">
                                        NAME
                                        <svg class="ml-1 h-2.5 w-2.5" :class="sortBy === 'name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.role" @@click="sort('role_name')" class="cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center">
                                        ROLE
                                        <svg class="ml-1 h-2.5 w-2.5" :class="sortBy === 'role_name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.email" @@click="sort('email')" class="cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center">
                                        EMAIL
                                        <svg class="ml-1 h-2.5 w-2.5" :class="sortBy === 'email' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center">
                                        CREATED AT
                                        <svg class="ml-1 h-2.5 w-2.5" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.last_login_at" @@click="sort('last_login_at')" class="cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center">
                                        LAST LOGIN
                                        <svg class="ml-1 h-2.5 w-2.5" :class="sortBy === 'last_login_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 bg-transparent">
                            <template x-if="users.length === 0">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-xs text-gray-500">No assigned users found</td>
                                </tr>
                            </template>

                            <template x-for="user in users" :key="user.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td x-show="visibleColumns.name" class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold text-slate-900" x-text="user.name"></td>
                                    <td x-show="visibleColumns.role" class="px-4 py-2.5 text-xs text-slate-600">
                                        <span class="inline-flex items-center rounded-full border border-slate-200/50 bg-white/60 px-2 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm backdrop-blur-sm" x-text="user.role_name"></span>
                                    </td>
                                    <td x-show="visibleColumns.email" class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-600" x-text="user.email"></td>
                                    <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-600" x-text="user.created_at"></td>
                                    <td x-show="visibleColumns.last_login_at" class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-600" x-text="user.last_login_at"></td>
                                    <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-2.5 text-center text-xs font-medium">
                                        <a :href="user.view_url" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="border-t border-slate-200/50 bg-slate-50/30 px-4 py-2.5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> users</div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex min-w-[60px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90">
                                            <span x-text="perPage"></span>
                                            <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200/70 bg-white/95 p-1 shadow-lg backdrop-blur-xl" style="display:none;">
                                            <button type="button" @@click="setPerPage(10); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70">10</button>
                                            <button type="button" @@click="setPerPage(25); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70">25</button>
                                            <button type="button" @@click="setPerPage(50); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70">50</button>
                                            <button type="button" @@click="setPerPage(100); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70">100</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs font-medium text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></div>

                                <div class="flex space-x-1">
                                    <button @@click="firstPage()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button @@click="previousPage()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button @@click="nextPage()" :disabled="meta.current_page === meta.last_page" :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <button @@click="lastPage()" :disabled="meta.current_page === meta.last_page" :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- end flex-1 content --}}
    </div>
</div>
@endsection
