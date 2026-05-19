@extends('admin.layouts.app')

@php
    $backUrl = route('admin.roles.index');
    $currentAdmin = Auth::guard('admin')->user();
    $canEditRole = $currentAdmin?->isHqUser() && $currentAdmin?->hasPermission('roles.edit');

    $initialTab = request('tab', 'permissions');
    if (!in_array($initialTab, ['permissions', 'users'], true)) {
        $initialTab = 'permissions';
    }

    $permissions = $role->permissions
        ->sortBy(fn ($permission) => ($permission->sort_order ?? 9999) . '-' . ($permission->module ?? 'general') . '-' . ($permission->action ?? $permission->name))
        ->values();

    $localPermissions = $permissions->filter(fn ($permission) => $permission->module === 'warehouse')->values();
    $adminPermissions = $permissions->reject(fn ($permission) => $permission->module === 'warehouse')->values();
    $permissionsCount = $permissions->count();

    $permissionSections = collect([
        [
            'title' => 'Local Operations',
            'subtitle' => 'Permissions used inside the current warehouse workspace.',
            'count' => $localPermissions->count(),
            'groups' => $localPermissions->groupBy(fn ($permission) => $permission->displayModule()),
        ],
        [
            'title' => 'Admin Modules',
            'subtitle' => 'Capabilities that only work when the warehouse is HQ or has the module grant.',
            'count' => $adminPermissions->count(),
            'groups' => $adminPermissions->groupBy(fn ($permission) => $permission->displayModule()),
        ],
    ])->filter(fn ($section) => $section['count'] > 0)->values();

    $assignedUsers = $role->users
        ->sortByDesc('created_at')
        ->values()
        ->map(function ($user) use ($role, $currentAdmin) {
            $isSameWarehouse = $currentAdmin?->warehouse_id && (int) $currentAdmin->warehouse_id === (int) $user->warehouse_id;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_name' => $role->name,
                'warehouse_name' => $user->warehouse?->name ?: '-',
                'is_active' => (bool) $user->is_active,
                'status_label' => $user->is_active ? 'Active' : 'Inactive',
                'created_at_raw' => optional($user->created_at)->toISOString(),
                'created_at' => optional($user->created_at)->format('M d, Y, h:i A') ?: '-',
                'last_login_at_raw' => optional($user->last_login_at)->toISOString(),
                'last_login_at' => optional($user->last_login_at)->format('M d, Y, h:i A') ?: 'Never',
                'view_url' => $isSameWarehouse
                    ? route('warehouse.users.show', $user)
                    : ($user->warehouse_id ? route('admin.warehouses.show', ['warehouse' => $user->warehouse_id, 'tab' => 'users']) : '#'),
            ];
        });

    $assignmentLabel = $role->is_assignable_by_warehouse_manager ? 'Assignable' : 'HQ controlled';
    $definitionLabel = $role->is_system_role ? 'Default template' : 'Custom template';

    $roleShowConfig = [
        'initialTab' => $initialTab,
        'users' => $assignedUsers,
    ];
@endphp

@section('title', 'Role - ' . $role->name)
@section('breadcrumb-parent', 'Roles')
@section('breadcrumb-current', $role->name)

@section('content')
<div class="space-y-6" x-data="roleShowPage" data-role-show-config='@json($roleShowConfig)'>
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ $backUrl }}" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back</span>
                </a>

                <div class="ml-auto flex w-auto max-w-[calc(100%-5.75rem)] flex-wrap items-center justify-end gap-2 sm:max-w-none">
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black {{ $role->is_active ? 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/30' : 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30' }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $role->is_active ? 'bg-emerald-300' : 'bg-slate-300' }}"></span>
                        {{ $role->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($canEditRole)
                        <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Edit
                        </a>
                    @endif
                </div>
            </div>

            <div class="relative mt-5 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[640px] lg:shrink">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-2xl font-black text-white shadow-lg shadow-orange-950/25">
                            {{ strtoupper(substr($role->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Role Template</p>
                            <h1 class="mt-1 max-w-4xl break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl xl:text-2xl 2xl:text-3xl">{{ $role->name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                <span>{{ $role->slug }}</span>
                                <span class="text-slate-600">/</span>
                                <span>{{ $assignmentLabel }}</span>
                                <span class="text-slate-600">/</span>
                                <span>{{ $definitionLabel }}</span>
                                <span class="text-slate-600">/</span>
                                <span>Created {{ $role->created_at?->format('d M Y, h:i A') ?: '-' }}</span>
                            </div>
                            @if($role->description)
                                <p class="mt-3 max-w-2xl text-sm font-semibold leading-6 text-slate-400">{{ $role->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-3 lg:ml-auto lg:w-[620px] lg:shrink-0 2xl:w-[680px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m0-4a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ number_format($assignedUsers->count()) }} Users</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">Assigned to role</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ number_format($permissionsCount) }} Permissions</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">Total access rules</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ $role->updated_at?->format('d M Y') ?: '-' }}</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">Last updated</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.roles.show', ['role' => $role, 'tab' => 'permissions']) }}"
               @@click.prevent="setTab('permissions')"
               class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
               :class="activeTab === 'permissions' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Permissions
            </a>
            <a href="{{ route('admin.roles.show', ['role' => $role, 'tab' => 'users']) }}"
               @@click.prevent="setTab('users')"
               class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
               :class="activeTab === 'users' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m0-4a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                </svg>
                Assigned Users
            </a>
        </div>
    </section>

    <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div x-show="activeTab === 'permissions'" x-cloak>
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-black text-slate-900">Permissions</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Access rules attached to this role.</p>
            </div>

            @if($permissionSections->isNotEmpty())
                <div class="space-y-5 p-5">
                    @foreach($permissionSections as $section)
                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50/50">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-950">{{ $section['title'] }}</h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $section['subtitle'] }}</p>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-black text-orange-700">
                                    {{ number_format($section['count']) }} permissions
                                </span>
                            </div>

                            <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($section['groups'] as $module => $modulePermissions)
                                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <div class="mb-3 flex items-center gap-3 border-b border-slate-100 pb-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="truncate text-sm font-black text-slate-950">{{ $module }}</h4>
                                                <p class="text-xs font-bold text-slate-400">{{ $modulePermissions->count() }} rules</p>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            @foreach($modulePermissions as $permission)
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                    <p class="text-sm font-black text-slate-900">{{ $permission->displayLabel() }}</p>
                                                    @if($permission->displayDescription())
                                                        <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $permission->displayDescription() }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                        <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-500">No permissions assigned to this role yet.</p>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'users'" x-cloak>
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-black text-slate-900">Assigned Users</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Users currently carrying this role.</p>
            </div>

            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="block w-full xl:max-w-md">
                        <span class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</span>
                        <span class="relative block">
                            <svg class="pointer-events-none absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>
                            <input type="text" x-model="search" @@input.debounce.300ms="meta.current_page = 1; applyFilters()" placeholder="Search users..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @@click="showFilters = !showFilters"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                        </svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    <div class="flex gap-2">
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                    <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                    </svg>
                                    View
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none;">
                                    <template x-for="col in columns" :key="col.key">
                                        <button type="button" @@click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                            <span x-text="col.label"></span>
                                            <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                    <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                                    </svg>
                                    Export
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none;">
                                    <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">CSV</button>
                                    <button type="button" @@click="exportData('excel'); open = false" class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">Excel</button>
                                    <button type="button" @@click="exportData('pdf'); open = false" class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">PDF</button>
                                    <button type="button" @@click="printData(); open = false" class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">Print</button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                            <select x-model="statusFilter" @@change="statusFilterName = $event.target.selectedOptions[0].text; meta.current_page = 1; applyFilters()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                        <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                        <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2" x-show="statusFilter !== '' || search">
                    <span x-show="search" class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        Search: <span x-text="search"></span>
                        <button type="button" @@click="search = ''; meta.current_page = 1; applyFilters()" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                    <span x-show="statusFilter !== ''" class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        Status: <span x-text="statusFilterName"></span>
                        <button type="button" @@click="setStatusFilter('', 'All statuses')" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1040px] w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th x-show="visibleColumns.name" @@click="sort('name')" class="cursor-pointer whitespace-nowrap px-5 py-4 text-left text-xs font-black uppercase tracking-widest text-slate-500">
                                <span class="inline-flex items-center gap-1">Name</span>
                            </th>
                            <th x-show="visibleColumns.role" @@click="sort('role_name')" class="cursor-pointer whitespace-nowrap px-5 py-4 text-left text-xs font-black uppercase tracking-widest text-slate-500">Role</th>
                            <th x-show="visibleColumns.email" @@click="sort('email')" class="cursor-pointer whitespace-nowrap px-5 py-4 text-left text-xs font-black uppercase tracking-widest text-slate-500">Email</th>
                            <th x-show="visibleColumns.warehouse" @@click="sort('warehouse_name')" class="cursor-pointer whitespace-nowrap px-5 py-4 text-left text-xs font-black uppercase tracking-widest text-slate-500">Warehouse</th>
                            <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="cursor-pointer whitespace-nowrap px-5 py-4 text-left text-xs font-black uppercase tracking-widest text-slate-500">Created At</th>
                            <th x-show="visibleColumns.last_login_at" @@click="sort('last_login_at')" class="cursor-pointer whitespace-nowrap px-5 py-4 text-left text-xs font-black uppercase tracking-widest text-slate-500">Last Login</th>
                            <th x-show="visibleColumns.actions" class="whitespace-nowrap px-5 py-4 text-right text-xs font-black uppercase tracking-widest text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="users.length === 0">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-5 py-14 text-center text-sm font-bold text-slate-500">No assigned users found</td>
                            </tr>
                        </template>

                        <template x-for="user in users" :key="user.id">
                            <tr class="transition hover:bg-slate-50/80">
                                <td x-show="visibleColumns.name" class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-sm font-black text-orange-700" x-text="(user.name || '?').charAt(0).toUpperCase()"></span>
                                        <div class="min-w-0">
                                            <p class="truncate font-black text-slate-950" x-text="user.name"></p>
                                            <p class="text-xs font-bold text-slate-500" x-text="user.status_label"></p>
                                        </div>
                                    </div>
                                </td>
                                <td x-show="visibleColumns.role" class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex max-w-none items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-black text-orange-700" x-text="user.role_name"></span>
                                </td>
                                <td x-show="visibleColumns.email" class="whitespace-nowrap px-5 py-4 font-semibold text-slate-600" x-text="user.email"></td>
                                <td x-show="visibleColumns.warehouse" class="whitespace-nowrap px-5 py-4 font-semibold text-slate-600" x-text="user.warehouse_name"></td>
                                <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-5 py-4 font-semibold text-slate-600" x-text="user.created_at"></td>
                                <td x-show="visibleColumns.last_login_at" class="whitespace-nowrap px-5 py-4 font-semibold text-slate-600" x-text="user.last_login_at"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-5 py-4 text-right">
                                    <a :href="user.view_url" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-xs font-black text-orange-700 transition hover:bg-orange-100">
                                        View
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm font-bold text-slate-600">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span></p>

                    <div class="flex flex-wrap items-center justify-end gap-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-700">Rows</span>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex h-11 min-w-[76px] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-black text-slate-700">
                                    <span x-text="perPage"></span>
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-2 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-xl" style="display:none;">
                                    <button type="button" @@click="setPerPage(10); open = false" class="w-full rounded-lg px-2 py-2 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">10</button>
                                    <button type="button" @@click="setPerPage(25); open = false" class="w-full rounded-lg px-2 py-2 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">25</button>
                                    <button type="button" @@click="setPerPage(50); open = false" class="w-full rounded-lg px-2 py-2 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">50</button>
                                    <button type="button" @@click="setPerPage(100); open = false" class="w-full rounded-lg px-2 py-2 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">100</button>
                                </div>
                            </div>
                        </div>

                        <div class="text-sm font-black text-slate-700">Page <span x-text="meta.current_page"></span> / <span x-text="meta.last_page"></span></div>

                        <div class="flex gap-2">
                            <button type="button" @@click="previousPage()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-45 cursor-not-allowed' : 'hover:bg-white'" class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" @@click="nextPage()" :disabled="meta.current_page === meta.last_page" :class="meta.current_page === meta.last_page ? 'opacity-45 cursor-not-allowed' : 'hover:bg-white'" class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
