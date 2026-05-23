@extends('admin.layouts.app')

@section('title', 'View Admin - ' . $admin->name)
@section('breadcrumb-parent', 'User Management')
@section('breadcrumb-current', $admin->name)

@section('content')
@php
    $currentAdmin = Auth::guard('admin')->user();
    $canManage = $currentAdmin->canManage($admin);
    $isSelf = $admin->id === $currentAdmin->id;
    $allPermissions = $admin->getAllPermissions();
    $groupedPermissions = $allPermissions->groupBy(fn ($permission) => $permission->module ?? 'general');
    $initialTab = request('tab', 'permissions');
    if (!in_array($initialTab, ['permissions', 'activity'], true)) {
        $initialTab = 'permissions';
    }
@endphp

<div
    class="space-y-6"
    x-data="{
        activeTab: @js($initialTab),
        auditLogsMeta: { total: 0 },
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        }
    }"
>

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <!-- Top Row: Back (left) + Actions (right) -->
                <div class="mb-6 flex items-center justify-between gap-3">
                    <a href="{{ route('admin.admins.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Users</span>
                    </a>

                    @if($canManage)
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('admin.admins.edit', $admin) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm shadow-sm hover:shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Edit User
                        </a>

                        @if(!$isSelf)
                            <form action="{{ route('admin.admins.toggle-active', $admin) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to {{ $admin->is_active ? 'deactivate' : 'activate' }} this user?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl border transition-all backdrop-blur-sm shadow-sm hover:shadow-md {{ $admin->is_active ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30' : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/30' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Main Row: Profile LEFT, Stats RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    <!-- LEFT: Profile Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <!-- Avatar -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                            </div>
                            <div class="absolute -bottom-1.5 -right-1.5 w-7 h-7 lg:w-8 lg:h-8 rounded-full {{ $admin->is_active ? 'bg-gradient-to-br from-emerald-400 to-emerald-600' : 'bg-gradient-to-br from-slate-400 to-slate-500' }} border-4 border-slate-900 flex items-center justify-center shadow-lg">
                                @if($admin->is_active)
                                    <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $admin->name }}</h1>
                                @if($isSelf)
                                    <p class="text-blue-300 text-xs mt-0.5 font-medium">This is you</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="truncate">{{ $admin->email }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.08 3.24a1 1 0 01-.27 1.05l-1.46 1.46a12.04 12.04 0 005.99 5.99l1.46-1.46a1 1 0 011.05-.27l3.24 1.08a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C10.37 21 3 13.63 3 5z"/>
                                    </svg>
                                    <span class="truncate">{{ $admin->phone ?: 'No phone set' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>Created by {{ $admin->creator?->name ?? 'System' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Updated {{ $admin->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $admin->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $admin->is_active ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                    {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @foreach($admin->roles as $role)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/20 text-blue-300">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $role->name }}
                                </span>
                                @endforeach
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $admin->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Stats -->
                    <div class="flex flex-col gap-3 lg:ml-auto lg:items-end">
                        <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap">
                            <!-- Roles -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $admin->roles->count() }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Roles</p>
                                </div>
                            </div>

                            <!-- Permissions -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $allPermissions->count() }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Permissions</p>
                                </div>
                            </div>

                            <!-- Last Login -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">
                                        @if($admin->last_login_at)
                                            {{ $admin->last_login_at->format('M d') }}
                                        @else
                                            &mdash;
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Last Login</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-2 bg-slate-50/80">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    type="button"
                    @click="setTab('permissions')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-sm font-semibold whitespace-nowrap transition-all duration-200"
                    :class="activeTab === 'permissions'
                        ? 'bg-slate-800 text-white shadow-sm'
                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100 hover:text-slate-800'"
                >
                    Permissions
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold"
                          :class="activeTab === 'permissions' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'">{{ $allPermissions->count() }}</span>
                </button>

                <button
                    type="button"
                    @click="setTab('activity')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-sm font-semibold whitespace-nowrap transition-all duration-200"
                    :class="activeTab === 'activity'
                        ? 'bg-slate-800 text-white shadow-sm'
                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100 hover:text-slate-800'"
                >
                    Activity Logs
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold"
                          :class="activeTab === 'activity' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'"
                          x-text="typeof auditLogsMeta !== 'undefined' ? auditLogsMeta.total : '...'">&hellip;</span>
                </button>
            </div>
        </div>

        <!-- Permissions Panel -->
        <div x-show="activeTab === 'permissions'" x-cloak x-transition.opacity.duration.150ms>
            @if($groupedPermissions->isNotEmpty())
                <div class="p-5">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $moduleColors = [
                                'users' => ['from-blue-500/15 to-blue-600/10', 'text-blue-600', 'border-blue-100'],
                                'roles' => ['from-violet-500/15 to-violet-600/10', 'text-violet-600', 'border-violet-100'],
                                'vendors' => ['from-amber-500/15 to-amber-600/10', 'text-amber-600', 'border-amber-100'],
                                'shipments' => ['from-cyan-500/15 to-cyan-600/10', 'text-cyan-600', 'border-cyan-100'],
                                'drivers' => ['from-emerald-500/15 to-emerald-600/10', 'text-emerald-600', 'border-emerald-100'],
                                'warehouses' => ['from-orange-500/15 to-orange-600/10', 'text-orange-600', 'border-orange-100'],
                                'warehouse' => ['from-orange-500/15 to-orange-600/10', 'text-orange-600', 'border-orange-100'],
                                'settings' => ['from-slate-500/15 to-slate-600/10', 'text-slate-600', 'border-slate-200'],
                                'platform_settings' => ['from-slate-500/15 to-slate-600/10', 'text-slate-600', 'border-slate-200'],
                                'dashboard' => ['from-indigo-500/15 to-indigo-600/10', 'text-indigo-600', 'border-indigo-100'],
                                'reports' => ['from-teal-500/15 to-teal-600/10', 'text-teal-600', 'border-teal-100'],
                            ];
                            $moduleLabels = [
                                'users' => 'Users',
                                'roles' => 'Roles',
                                'vendors' => 'Vendors',
                                'shipments' => 'Orders',
                                'drivers' => 'Drivers',
                                'warehouses' => 'Warehouses',
                                'warehouse' => 'Warehouse Ops',
                                'settings' => 'Settings',
                                'platform_settings' => 'Platform Settings',
                                'dashboard' => 'Dashboard',
                                'reports' => 'Reports',
                            ];
                            $moduleIcons = [
                                'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                                'roles' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                                'vendors' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                                'shipments' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                                'drivers' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                                'warehouses' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>',
                                'warehouse' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>',
                                'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'platform_settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>',
                                'reports' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                            ];

                            $actionColorMap = [
                                'view' => 'bg-sky-50 text-sky-700 ring-sky-200/60',
                                'create' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/60',
                                'edit' => 'bg-amber-50 text-amber-700 ring-amber-200/60',
                                'update' => 'bg-amber-50 text-amber-700 ring-amber-200/60',
                                'delete' => 'bg-rose-50 text-rose-700 ring-rose-200/60',
                                'deactivate' => 'bg-rose-50 text-rose-700 ring-rose-200/60',
                                'export' => 'bg-violet-50 text-violet-700 ring-violet-200/60',
                                'assign' => 'bg-indigo-50 text-indigo-700 ring-indigo-200/60',
                                'manage' => 'bg-purple-50 text-purple-700 ring-purple-200/60',
                                'activate' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/60',
                                'scan' => 'bg-teal-50 text-teal-700 ring-teal-200/60',
                            ];
                            $actionIconMap = [
                                'view' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
                                'create' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>',
                                'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                                'update' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                                'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
                                'deactivate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
                                'export' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                                'assign' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'manage' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'activate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                'scan' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>',
                            ];

                            // Keyword-based matching for compound actions like assign_driver, dashboard_view, etc.
                            $resolveActionStyle = function(string $action) use ($actionColorMap, $actionIconMap) {
                                $a = strtolower($action);
                                $keywords = ['delete', 'deactivate', 'create', 'edit', 'update', 'view', 'export', 'assign', 'manage', 'activate', 'scan'];
                                foreach ($keywords as $keyword) {
                                    if (str_contains($a, $keyword)) {
                                        return [
                                            'color' => $actionColorMap[$keyword],
                                            'icon' => $actionIconMap[$keyword],
                                        ];
                                    }
                                }
                                return [
                                    'color' => 'bg-slate-50 text-slate-600 ring-slate-200/60',
                                    'icon' => $actionIconMap['manage'],
                                ];
                            };
                        @endphp
                        @foreach($groupedPermissions as $module => $permissions)
                            @php
                                $key = strtolower(str_replace(' ', '_', $module));
                                $colors = $moduleColors[$key] ?? ['from-slate-500/15 to-slate-600/10', 'text-slate-600', 'border-slate-200'];
                                $icon = $moduleIcons[$key] ?? $moduleIcons['settings'];
                                $label = $moduleLabels[$key] ?? Str::of($module)->replace('_', ' ')->title()->toString();
                            @endphp
                            <div class="group rounded-xl border {{ $colors[2] }} bg-white p-4 transition-all duration-200 hover:shadow-md hover:shadow-slate-200/50 hover:border-slate-200">
                                <div class="flex items-center gap-2.5 mb-3 pb-3 border-b border-slate-100">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $colors[0] }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 {{ $colors[1] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icon !!}</svg>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800 tracking-wide">{{ $label }}</h3>
                                    <span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">{{ $permissions->count() }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach($permissions as $permission)
                                        @php
                                            $style = $resolveActionStyle($permission->action);
                                            $permLabel = $permission->description ?: Str::of($permission->action)->replace('_', ' ')->title()->toString();
                                        @endphp
                                        <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg {{ $style['color'] }} ring-1 transition-colors" title="{{ $permission->name }}">
                                            <svg class="w-3 h-3 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $style['icon'] !!}</svg>
                                            <span class="text-[11px] font-semibold">{{ $permLabel }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-500">No permissions assigned to this user role yet.</p>
                    <p class="text-xs text-slate-400 mt-1">Assign a role to grant permissions.</p>
                </div>
            @endif
        </div>

        <!-- Activity Logs Panel -->
        <div x-show="activeTab === 'activity'" x-cloak x-transition.opacity.duration.150ms
             x-data="auditLogsTable"
             x-init="$watch('meta', val => { auditLogsMeta = val })"
             data-endpoint="{{ route('admin.admins.audit-logs-data', $admin) }}"
             data-export-endpoint="{{ route('admin.settings.index', ['tab' => 'admin-audit-logs', 'user_id' => $admin->id]) }}">

            <!-- Table Controls -->
            <div class="p-6 pb-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <!-- Search + Date Range -->
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input
                                type="text"
                                x-model="search"
                                @input.debounce.500ms="meta.current_page = 1; loadData()"
                                placeholder="Search logs..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                            >
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div class="relative w-full sm:w-56">
                            <input
                                type="text"
                                x-ref="dateRange"
                                placeholder="Date range"
                                class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 cursor-pointer"
                                readonly
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Right Controls -->
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <!-- Customize Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                                 style="display: none;">
                                <template x-for="col in columns" :key="col.key">
                                    <button type="button" @click="toggleColumn(col.key)"
                                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                                 style="display: none;">
                                <button type="button" @click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @click="printData(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="px-6 py-4">
                <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
                    <!-- Loading overlay -->
                    <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>

                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th x-show="visibleColumns.created_at" @click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        DATE
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.type" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    TYPE
                                </th>
                                <th x-show="visibleColumns.action" @click="sort('action')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        ACTION
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'action' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.request" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    REQUEST
                                </th>
                                <th x-show="visibleColumns.result" @click="sort('status_code')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        RESULT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'status_code' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-if="logs.length === 0 && !loading">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">
                                        No activity logs found
                                    </td>
                                </tr>
                            </template>

                            <template x-for="log in logs" :key="log.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td x-show="visibleColumns.created_at" class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap" x-text="log.created_at"></td>
                                    <td x-show="visibleColumns.type" class="px-4 py-2.5">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold shadow-sm"
                                                  :class="log.scope === 'warehouse' ? 'border-emerald-200/50 bg-emerald-50/60 text-emerald-700' : 'border-indigo-200/50 bg-indigo-50/60 text-indigo-700'"
                                                  x-text="log.scope === 'warehouse' ? 'Warehouse' : 'System'"></span>
                                            <span class="inline-flex items-center rounded-full border border-slate-200/50 bg-white/60 px-2 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm"
                                                  x-text="log.action_type"></span>
                                        </div>
                                    </td>
                                    <td x-show="visibleColumns.action" class="px-4 py-2.5 text-xs text-slate-600">
                                        <div class="font-semibold text-slate-900" x-text="log.action"></div>
                                        <div class="text-slate-500" x-text="log.description"></div>
                                    </td>
                                    <td x-show="visibleColumns.request" class="px-4 py-2.5 text-xs text-slate-600">
                                        <div><span class="font-semibold text-slate-900" x-text="log.method"></span> <span class="text-slate-500 break-all" x-text="log.url"></span></div>
                                        <div class="text-slate-500" x-text="'IP: ' + log.ip_address"></div>
                                    </td>
                                    <td x-show="visibleColumns.result" class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap">
                                        <div class="font-semibold text-slate-900" x-text="'HTTP ' + log.status_code"></div>
                                        <div class="text-slate-500" x-text="log.duration_ms + ' ms'"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">
                                Showing
                                <span class="font-medium" x-text="meta.from"></span>
                                to
                                <span class="font-medium" x-text="meta.to"></span>
                                of
                                <span class="font-medium" x-text="meta.total"></span>
                                results
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"
                                        >
                                            <span x-text="perPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div
                                            x-show="open"
                                            @click.away="open = false"
                                            x-transition
                                            class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                            style="display: none;"
                                        >
                                            <button type="button" @click="perPage = 15; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 15 ? 'bg-slate-100/70' : ''">15</button>
                                            <button type="button" @click="perPage = 25; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @click="perPage = 50; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                            <button type="button" @click="perPage = 100; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs font-medium text-slate-600">
                                    Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span>
                                </div>

                                <div class="flex space-x-1">
                                    <button @click="firstPage()" :disabled="meta.current_page === 1"
                                            :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button @click="previousPage()" :disabled="meta.current_page === 1"
                                            :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button @click="nextPage()" :disabled="meta.current_page === meta.last_page"
                                            :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button @click="lastPage()" :disabled="meta.current_page === meta.last_page"
                                            :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
