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
    $createdUsers = $admin->createdUsers;
    $initialTab = request()->has('logs_page') ? 'activity' : request('tab', 'permissions');
    if (!in_array($initialTab, ['permissions', 'created', 'activity'], true)) {
        $initialTab = 'permissions';
    }
@endphp

<div
    class="space-y-6"
    x-data="{
        activeTab: @js($initialTab),
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            if (tab !== 'activity') {
                url.searchParams.delete('logs_page');
            }
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

                            <!-- Created Users -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $createdUsers->count() }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Created Users</p>
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
        <div class="border-b border-slate-200 px-4 py-3 bg-slate-50/80">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <button
                    type="button"
                    @click="setTab('permissions')"
                    class="group inline-flex w-full items-center justify-between rounded-xl border px-3.5 py-2.5 text-left transition-all"
                    :class="activeTab === 'permissions' ? 'border-violet-200 bg-violet-50/90 text-violet-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                >
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                              :class="activeTab === 'permissions' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-500'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </span>
                        <span class="text-xs font-semibold">Permissions</span>
                    </span>
                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                          :class="activeTab === 'permissions' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600'">{{ $allPermissions->count() }}</span>
                </button>

                <button
                    type="button"
                    @click="setTab('created')"
                    class="group inline-flex w-full items-center justify-between rounded-xl border px-3.5 py-2.5 text-left transition-all"
                    :class="activeTab === 'created' ? 'border-emerald-200 bg-emerald-50/90 text-emerald-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                >
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                              :class="activeTab === 'created' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </span>
                        <span class="text-xs font-semibold">Created Users</span>
                    </span>
                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                          :class="activeTab === 'created' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ $createdUsers->count() }}</span>
                </button>

                <button
                    type="button"
                    @click="setTab('activity')"
                    class="group inline-flex w-full items-center justify-between rounded-xl border px-3.5 py-2.5 text-left transition-all"
                    :class="activeTab === 'activity' ? 'border-indigo-200 bg-indigo-50/90 text-indigo-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                >
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                              :class="activeTab === 'activity' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 3v6c0 5-3.5 9.5-8 11-4.5-1.5-8-6-8-11V6l8-3z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 12.5l1.75 1.75L14.5 11"/>
                            </svg>
                        </span>
                        <span class="text-xs font-semibold">User Activity Logs</span>
                    </span>
                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                          :class="activeTab === 'activity' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'">{{ $auditLogs->total() }}</span>
                </button>
            </div>
        </div>

        <!-- Permissions Panel -->
        <div x-show="activeTab === 'permissions'" x-cloak x-transition.opacity.duration.150ms>
            <div class="border-b border-slate-200 px-6 py-4 bg-gradient-to-r from-slate-50 to-slate-100/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/10 to-violet-600/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Permissions</h2>
                        <p class="text-xs text-slate-500">{{ $allPermissions->count() }} permissions across {{ $groupedPermissions->count() }} modules</p>
                    </div>
                </div>
            </div>
            @if($groupedPermissions->isNotEmpty())
                <div class="p-6">
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($groupedPermissions as $module => $permissions)
                            <div class="rounded-xl border border-slate-200/70 bg-slate-50/50 p-4 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-6 h-6 rounded-md bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center flex-shrink-0">
                                        <span class="text-[9px] font-bold text-white uppercase">{{ strtoupper(substr(str_replace('_', '', $module), 0, 2)) }}</span>
                                    </div>
                                    <p class="text-xs font-bold capitalize text-slate-700">{{ str_replace('_', ' ', $module) }}</p>
                                    <span class="ml-auto inline-flex items-center rounded-full bg-white px-1.5 py-0.5 text-[9px] font-bold text-slate-500 ring-1 ring-slate-200">{{ $permissions->count() }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($permissions as $permission)
                                        <span class="rounded-md bg-white px-2 py-1 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200/80 shadow-sm">
                                            {{ $permission->action }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-slate-500">No permissions assigned to this user role yet.</p>
                </div>
            @endif
        </div>

        <!-- Created Users Panel -->
        <div x-show="activeTab === 'created'" x-cloak x-transition.opacity.duration.150ms>
            <div class="border-b border-slate-200 px-6 py-4 bg-gradient-to-r from-slate-50 to-slate-100/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/10 to-emerald-600/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Created Users</h2>
                        <p class="text-xs text-slate-500">{{ $createdUsers->count() }} {{ Str::plural('user', $createdUsers->count()) }} created by this account</p>
                    </div>
                </div>
            </div>
            @if($createdUsers->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach($createdUsers as $createdUser)
                        <div class="flex flex-col gap-3 px-6 py-3.5 sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50/70 transition-colors">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-600 ring-1 ring-slate-200/50">
                                    {{ strtoupper(substr($createdUser->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.admins.show', $createdUser) }}"
                                       class="block truncate text-sm font-semibold text-slate-900 hover:text-blue-600 transition-colors">
                                        {{ $createdUser->name }}
                                    </a>
                                    <p class="truncate text-xs text-slate-500">{{ $createdUser->email }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                @foreach($createdUser->roles as $role)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-blue-200/50">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $createdUser->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $createdUser->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $createdUser->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-slate-500">No users have been created by this account yet.</p>
                </div>
            @endif
        </div>

        <!-- Activity Logs Panel -->
        <div x-show="activeTab === 'activity'" x-cloak x-transition.opacity.duration.150ms>
        <div class="border-b border-slate-200 px-6 py-4 bg-gradient-to-r from-slate-50 to-slate-100/50">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500/10 to-indigo-600/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 3v6c0 5-3.5 9.5-8 11-4.5-1.5-8-6-8-11V6l8-3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 12.5l1.75 1.75L14.5 11"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">User Activity Logs</h2>
                        <p class="text-xs text-slate-500">{{ number_format($auditLogs->total()) }} recorded {{ Str::plural('action', $auditLogs->total()) }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.settings.index', ['tab' => 'admin-audit-logs', 'user_id' => $admin->id]) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    Open Full Logs
                </a>
            </div>
        </div>

        @if($auditLogs->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Request</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($auditLogs as $log)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 text-xs text-slate-700 whitespace-nowrap">
                                    {{ optional($log->created_at)->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold
                                        {{ $log->scope === 'warehouse' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700' }}">
                                        {{ $log->scope === 'warehouse' ? 'Warehouse' : 'System' }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 ml-1">
                                        {{ Str::of($log->action_type)->replace('_', ' ')->title() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    <div class="font-semibold">{{ $log->action }}</div>
                                    <div class="text-slate-500">{{ $log->description ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    <div>{{ $log->method ?: '-' }} {{ $log->route_name ?: '' }}</div>
                                    <div class="text-slate-500">IP: {{ $log->ip_address ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700 whitespace-nowrap">
                                    <div class="font-semibold">HTTP {{ $log->status_code ?: '-' }}</div>
                                    <div class="text-slate-500">{{ $log->duration_ms ?? '-' }} ms</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $auditLogs->links() }}
            </div>
        @else
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-slate-500">No activity logs found for this user yet.</p>
            </div>
        @endif
        </div>
    </div>
</div>
@endsection
