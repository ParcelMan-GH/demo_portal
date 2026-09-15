@extends('admin.layouts.app')

@php
    $scope = ($roleScope ?? 'warehouse') === 'system' ? 'system' : 'warehouse';
    $currentUser = Auth::guard('admin')->user();
    $canManageRoleDefinitions = $currentUser?->isHqUser() && $currentUser?->hasPermission('roles.create');
    $rolesTableConfig = [
        'endpoint' => route('admin.roles.data'),
        'exportEndpoint' => route('admin.roles.export'),
        'createUrl' => route('admin.roles.create'),
        'csrfToken' => csrf_token(),
        'scope' => $scope,
        'canCreate' => $canManageRoleDefinitions,
    ];
@endphp

@section('title', 'Roles')
@section('breadcrumb-parent', 'Team')
@section('breadcrumb-current', 'Roles')
@section('page-title', 'Roles')

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6"
     x-data="{
         selectedRole: null,
         selectRole(role) {
             this.selectedRole = role;
         },
         ...rolesTable()
     }"
     data-roles-config="{{ json_encode($rolesTableConfig) }}">
     
    <!-- Page Header (Positioned Above the Workspace) -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $scope === 'system' ? 'HQ Role Templates' : 'Warehouse Roles' }}
                </h1>
            </div>
            <p class="text-slate-500 text-sm font-semibold mt-1">
                HQ manages role templates; warehouses assign approved roles to their operational staff.
            </p>
        </div>
        
        <div class="flex items-center gap-4">
            <span class="inline-flex items-center rounded-full bg-white border border-slate-200/80 px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm" x-text="(meta.total || 0) + ' roles'"></span>
            
            @if($canManageRoleDefinitions)
            <a href="{{ route('admin.roles.create') }}" class="bg-[#E2762B] hover:bg-[#d1651d] text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Add Role
            </a>
            @endif
        </div>
    </div>

    <!-- Master-Detail Split Screen Layout -->
    <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-slate-100/60 p-6 shadow-lg shadow-slate-300/20 min-h-[760px] flex flex-col lg:flex-row gap-6 lg:items-start">
        
        <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

        <!-- LEFT SIDE: Roles Master List (33% width) -->
        <div class="w-full lg:w-4/12 shrink-0 flex flex-col rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden min-h-[720px]">
            
            <!-- Search & Filter Controls -->
            <div class="border-b border-slate-100 bg-slate-50/70 p-5 space-y-4">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                    </svg>
                    <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search role name, slug..." class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        Filter List
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-40 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" style="display: none;">
                            <button type="button" @click="exportData('excel'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Excel</button>
                            <button type="button" @click="exportData('pdf'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">CSV</button>
                        </div>
                    </div>
                </div>

                <!-- Filter Drawer -->
                <div x-show="showFilters" x-transition class="space-y-3 pt-3 border-t border-slate-200/60" style="display:none">
                    <select x-model="statusFilter" @change="applyFilters()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        <option value="">All Statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <select x-model="typeFilter" @change="applyFilters()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        <option value="">All Definitions</option>
                        <option value="system">Default Template</option>
                        <option value="custom">Custom Role</option>
                    </select>
                    <select x-model="assignableFilter" @change="applyFilters()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        <option value="">All Assignments</option>
                        <option value="1">Warehouse Assignable</option>
                        <option value="0">HQ Controlled</option>
                    </select>
                </div>
            </div>

            <!-- Scrollable List of Roles -->
            <div class="flex-1 divide-y divide-slate-100 overflow-y-auto max-h-[580px]">
                <template x-if="roles.length === 0 && !loading">
                    <div class="p-8 text-center text-sm font-semibold text-slate-500">
                        No roles match your search.
                    </div>
                </template>

                <template x-for="role in roles" :key="role.id">
                    <div @click="selectRole(role)"
                         class="cursor-pointer p-5 transition-all duration-150 hover:bg-orange-50/40"
                         :class="selectedRole?.id === role.id ? 'bg-orange-50/90 border-l-4 border-orange-600 shadow-inner' : ''">
                        <div class="flex items-center gap-4">
                            <div class="flex h-13 w-13 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-orange-100 text-lg font-black text-orange-700 ring-1 ring-orange-200">
                                <span x-text="role.name ? role.name.charAt(0).toUpperCase() : 'R'"></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-sm font-black text-slate-900" x-text="role.name"></p>
                                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider" 
                                          :class="role.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" 
                                          x-text="role.status_label"></span>
                                </div>

                                <p class="truncate text-xs font-semibold text-slate-500 mt-0.5" x-text="role.slug"></p>

                                <div class="mt-2.5 flex flex-wrap gap-1.5">
                                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="role.users_count + ' Users'"></span>
                                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="role.permissions_count + ' Permissions'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Left Pagination Controls -->
            <div class="mt-auto border-t border-slate-100 bg-slate-50/70 p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-extrabold text-slate-600">
                        Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Role Detail Pane (67% width) -->
        <div class="w-full lg:w-8/12 flex-1 flex flex-col rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden min-h-[720px]">
            
            <!-- Blank Empty State -->
            <div x-show="!selectedRole" class="flex flex-1 flex-col items-center justify-center p-12 text-center min-h-[680px]">
                <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 mb-5 shadow-sm">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Select a Role to View Details</h3>
                <p class="mt-2 text-sm font-semibold text-slate-500 max-w-md">Click on any role from the left list to inspect its configuration, assigned permissions, and active users.</p>
            </div>

            <!-- Selected Role Detail View -->
            <div x-show="selectedRole" class="flex flex-col h-full" style="display: none;">
                
                <!-- Light Header Banner -->
                <div class="relative overflow-hidden border-b border-slate-200/80 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/60 p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div class="flex items-center gap-6">
                            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-orange-600 text-4xl font-black text-white shadow-xl shadow-orange-600/20 ring-4 ring-white">
                                <span x-text="selectedRole?.name ? selectedRole.name.charAt(0).toUpperCase() : 'R'"></span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-3xl font-black tracking-tight text-slate-950" x-text="selectedRole?.name"></h2>
                                    <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider" 
                                          :class="selectedRole?.is_active ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' : 'bg-slate-200 text-slate-700 ring-1 ring-slate-300'" 
                                          x-text="selectedRole?.status_label"></span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-bold text-slate-700">
                                    <span x-text="selectedRole?.slug"></span>
                                    <span class="text-slate-300">•</span>
                                    <span x-text="selectedRole?.is_system_role ? 'Default Template' : 'Custom Template'"></span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold text-slate-500">
                                    <span class="text-orange-700 font-extrabold" x-text="selectedRole?.assignable_label"></span>
                                    <span>•</span>
                                    <span x-text="'Created: ' + (selectedRole?.created_at || '-')"></span>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="selectedRole = null" class="self-start rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition shadow-sm">
                            Deselect &times;
                        </button>
                    </div>
                </div>

                <!-- Management Actions Toolbar -->
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-white px-8 py-4">
                    <template x-if="selectedRole?.can_edit">
                        <a :href="selectedRole?.edit_url"
                           class="inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-xs font-black text-orange-800 hover:bg-orange-100 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit Role Config
                        </a>
                    </template>

                    <a :href="selectedRole?.view_url"
                       class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 transition shadow-sm">
                        Detailed Permissions View &rarr;
                    </a>

                    <template x-if="selectedRole?.can_delete">
                        <button type="button" @click="deleteRole(selectedRole)"
                                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-black text-rose-700 hover:bg-rose-100 transition ml-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete Role
                        </button>
                    </template>
                </div>

                <!-- Detail Body Area -->
                <div class="flex-1 p-8 overflow-y-auto space-y-6 bg-slate-50/40">
                    
                    <!-- Overview Metrics -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m0-4a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-slate-900" x-text="selectedRole?.users_count || 0"></p>
                                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Assigned Users</p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-slate-900" x-text="selectedRole?.permissions_count || 0"></p>
                                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Permissions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Role Configuration Notes -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Role Description</h4>
                        <div>
                            <p class="text-sm font-semibold text-slate-700 leading-relaxed" x-text="selectedRole?.description || 'No description provided for this role.'"></p>
                        </div>
                    </div>

                    <!-- Prompt to view full config -->
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <p class="text-sm font-semibold text-slate-500">To inspect the exact capabilities, menus, and actions permitted for <span class="font-bold text-slate-800" x-text="selectedRole?.name"></span>, open the Detailed Permissions View.</p>
                        <a :href="selectedRole?.view_url" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800">
                            Review Full Access List
                        </a>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection