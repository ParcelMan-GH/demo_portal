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

    $assignmentLabel = $role->is_assignable_by_warehouse_manager ? 'Warehouse Assignable' : 'HQ Controlled';
    $definitionLabel = $role->is_system_role ? 'Default Template' : 'Custom Template';

    $roleShowConfig = [
        'initialTab' => $initialTab,
        'users' => $assignedUsers,
    ];
@endphp

@section('title', 'Role - ' . $role->name)
@section('breadcrumb-parent', 'Roles')
@section('breadcrumb-current', $role->name)

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="roleShowPage" data-role-show-config='@json($roleShowConfig)'>
    
    <!-- Page Header (Positioned Above Cards) -->
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <a href="{{ $backUrl }}" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors shadow-sm">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $role->name }}</h1>
                    <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider {{ $role->is_active ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' : 'bg-slate-200 text-slate-700 ring-1 ring-slate-300' }}">
                        {{ $role->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-bold text-slate-500">
                    <span class="text-orange-600">{{ $role->slug }}</span>
                    <span class="text-slate-300">•</span>
                    <span>{{ $assignmentLabel }}</span>
                    <span class="text-slate-300">•</span>
                    <span>{{ $definitionLabel }}</span>
                </div>
                @if($role->description)
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-500">{{ $role->description }}</p>
                @endif
            </div>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 sm:justify-end shrink-0">
            @if($canEditRole)
                <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-orange-200 bg-orange-50 px-4 text-sm font-bold text-orange-700 transition hover:bg-orange-100 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Edit Role
                </a>
                
                <button type="button" @click="showToggleModal = true" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border px-4 text-sm font-bold transition shadow-sm"
                    :class="{{ $role->is_active ? 'true' : 'false' }} ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'">
                    {{ $role->is_active ? 'Deactivate' : 'Activate' }}
                </button>

                <button type="button" @click="showDeleteModal = true" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-bold text-rose-700 transition hover:bg-rose-100 shadow-sm">
                    Delete
                </button>
            @endif
        </div>
    </div>

    <!-- Minimal Cream Metric Cards Grid -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <!-- Assigned Users Card -->
        <div class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-5 text-left shadow-sm min-h-[104px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Assigned Users</span>
                <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m0-4a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                </svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ number_format($assignedUsers->count()) }}</p>
        </div>

        <!-- Permissions Card -->
        <div class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-5 text-left shadow-sm min-h-[104px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Total Permissions</span>
                <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ number_format($permissionsCount) }}</p>
        </div>

        <!-- Last Updated Card -->
        <div class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-5 text-left shadow-sm min-h-[104px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Last Updated</span>
                <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900 truncate">{{ $role->updated_at?->format('d M Y') ?: '-' }}</p>
        </div>
    </div>

    <!-- Main Workspace Container -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-lg shadow-slate-300/30">
        
        <!-- Tab Navigation Bar -->
        <div class="border-b border-slate-100 bg-slate-50/70 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click.prevent="setTab('permissions')"
                    class="inline-flex w-auto items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition"
                    :class="activeTab === 'permissions' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-200/50 bg-white border border-slate-200/80'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Role Permissions
                </button>
                
                <button type="button" @click.prevent="setTab('users')"
                    class="inline-flex w-auto items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition"
                    :class="activeTab === 'users' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-200/50 bg-white border border-slate-200/80'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m0-4a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                    </svg>
                    Assigned Users
                </button>
            </div>
        </div>

        <!-- Permissions Tab Content -->
        <div x-show="activeTab === 'permissions'" x-cloak class="bg-slate-50/40 p-5 sm:p-8 min-h-[500px]">
            @if($permissionSections->isNotEmpty())
                <div class="space-y-6">
                    @foreach($permissionSections as $section)
                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <!-- Section Header -->
                            <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-black text-slate-900">{{ $section['title'] }}</h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $section['subtitle'] }}</p>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full border border-orange-200 bg-orange-50 px-3.5 py-1.5 text-xs font-black text-orange-700">
                                    {{ number_format($section['count']) }} permissions
                                </span>
                            </div>

                            <!-- Modules Grid -->
                            <div class="grid gap-5 p-6 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($section['groups'] as $module => $modulePermissions)
                                    <article class="rounded-2xl border border-slate-200 bg-slate-50/30 p-5 shadow-sm">
                                        <div class="mb-4 flex items-center gap-3 border-b border-slate-100 pb-4">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="truncate text-sm font-black text-slate-950">{{ $module }}</h4>
                                                <p class="text-[11px] font-bold text-slate-500 mt-0.5">{{ $modulePermissions->count() }} rules</p>
                                            </div>
                                        </div>

                                        <div class="space-y-2.5">
                                            @foreach($modulePermissions as $permission)
                                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-orange-200">
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
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">No Permissions Found</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">There are no permissions currently assigned to this role.</p>
                </div>
            @endif
        </div>

        <!-- Assigned Users Tab Content -->
        <div x-show="activeTab === 'users'" x-cloak class="min-h-[500px] flex flex-col">
            
            <!-- Users Search & Control Bar -->
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 xl:flex-row xl:items-end xl:justify-between bg-white">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="block w-full xl:max-w-md">
                        <span class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search Users</span>
                        <span class="relative block">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>
                            <input type="text" x-model="search" @input.debounce.300ms="meta.current_page = 1; applyFilters()" placeholder="Search assigned users..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        </span>
                    </label>
                </div>
            </div>

            <!-- Users Table -->
            <div class="relative flex-1 overflow-x-auto bg-white">
                <table class="w-full min-w-[1040px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500">Warehouse</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500">Last Login</th>
                            <th class="px-6 py-4 text-right text-xs font-extrabold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-if="users.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-sm font-semibold text-slate-500">No users found assigned to this role.</td>
                            </tr>
                        </template>

                        <template x-for="user in users" :key="user.id">
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-sm font-black text-orange-700" x-text="(user.name || '?').charAt(0).toUpperCase()"></span>
                                        <p class="truncate font-black text-slate-900" x-text="user.name"></p>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-600" x-text="user.email || '-'"></td>
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-600" x-text="user.warehouse_name"></td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider" 
                                          :class="user.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" 
                                          x-text="user.status_label"></span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-600" x-text="user.last_login_at"></td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <a :href="user.view_url" class="inline-flex items-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-bold text-orange-700 transition hover:bg-orange-100">
                                        View Profile
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Users Pagination Bar -->
            <div class="border-t border-slate-100 bg-slate-50/70 px-6 py-4 mt-auto">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs font-bold text-slate-600">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> users</p>
                    
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="previousPage()" :disabled="meta.current_page === 1" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page"></span> / <span x-text="meta.last_page"></span></div>
                        <button type="button" @click="nextPage()" :disabled="meta.current_page === meta.last_page" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection