@extends('warehouse.layouts.app')

@section('title', 'Users')
@section('breadcrumb-parent', 'Warehouse')
@section('breadcrumb-current', 'Users')
@section('page-title', 'Users & Staff Management')

@section('content')
<div class="space-y-6">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30"
         x-data="{
             selectedUser: null,
             selectUser(user) {
                 this.selectedUser = user;
             },
             ...usersTable()
         }"
         data-endpoint="{{ route('warehouse.users.data') }}"
         data-export-endpoint="{{ route('warehouse.users.export') }}"
         data-store-endpoint="{{ route('warehouse.users.store') }}"
         data-is-hq="{{ $isHqUser ? '1' : '0' }}"
         data-csrf-token="{{ csrf_token() }}">

        <!-- Top Header Bar -->
        <div class="border-b border-slate-200/60 px-8 py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-2xl font-black text-slate-900">Users & Staff Hub</h2>
                        <p class="truncate text-sm font-semibold text-slate-500">
                            @if($isHqUser)
                                Manage staff access, roles, and real-time activity across all warehouses.
                            @else
                                Manage staff access, roles, and real-time activity for {{ $warehouse->name ?? 'this warehouse' }}.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-700" x-text="meta.total + ' users'"></span>
                    
                    @if($canCreateUsers)
                    <button @click="openCreateModal()"
                       class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-md shadow-orange-600/20 transition hover:bg-orange-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Worker
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Master-Detail Split Layout -->
        <div class="relative min-h-[760px] bg-slate-100/60 p-6">
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

            <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                
                <!-- LEFT SIDE: Worker Master Roster (33% width) -->
                <div class="w-full lg:w-4/12 shrink-0 flex flex-col rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden min-h-[720px]">
                    
                    <!-- Roster Search & Filter Controls -->
                    <div class="border-b border-slate-100 bg-slate-50/70 p-5 space-y-4">
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                            </svg>
                            <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search worker name, email, phone..." class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                                Filter Roster
                            </button>

                            <!-- Export Dropdown -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50">
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

                        <!-- Filter Options -->
                        <div x-show="showFilters" x-transition class="space-y-3 pt-3 border-t border-slate-200/60" style="display:none">
                            <select x-model="roleFilter" @change="applyFilters()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800">
                                <option value="">All Roles</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <select x-model="statusFilter" @change="applyFilters()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Scrollable List of Worker Cards -->
                    <div class="flex-1 divide-y divide-slate-100 overflow-y-auto max-h-[580px]">
                        <template x-if="users.length === 0 && !loading">
                            <div class="p-8 text-center text-sm font-semibold text-slate-500">
                                No users found.
                            </div>
                        </template>

                        <template x-for="user in users" :key="user.id">
                            <div @click="selectUser(user)"
                                 class="cursor-pointer p-5 transition-all duration-150 hover:bg-orange-50/40"
                                 :class="selectedUser?.id === user.id ? 'bg-orange-50/90 border-l-4 border-orange-600 shadow-inner' : ''">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-13 w-13 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-orange-100 text-base font-black text-orange-700 ring-1 ring-orange-200">
                                        <template x-if="user.photo_url">
                                            <img :src="user.photo_url" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!user.photo_url">
                                            <span x-text="user.avatar"></span>
                                        </template>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-black text-slate-900" x-text="user.name"></p>
                                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider" 
                                                  :class="user.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" 
                                                  x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                        </div>

                                        <p class="truncate text-xs font-semibold text-slate-500 mt-1" x-text="user.email || user.phone || 'No contact details'"></p>

                                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                                            <template x-for="role in user.roles" :key="`role-${user.id}-${role.id}`">
                                                <span class="inline-flex items-center rounded-lg border border-orange-100 bg-orange-50 px-2.5 py-1 text-xs font-bold text-orange-700" x-text="role.name"></span>
                                            </template>
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
                                <button @click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: Worker Detail Pane (67% width) -->
                <div class="w-full lg:w-8/12 flex-1 flex flex-col rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden min-h-[720px]">
                    
                    <!-- Blank Empty State (When No User is Selected) -->
                    <div x-show="!selectedUser" class="flex flex-1 flex-col items-center justify-center p-12 text-center min-h-[680px]">
                        <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 mb-5 shadow-sm">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900">Select a Worker to View Details</h3>
                        <p class="mt-2 text-sm font-semibold text-slate-500 max-w-md">Click on any staff member from the left list to inspect their full profile, role assignments, contact details, and management options.</p>
                    </div>

                    <!-- Selected Worker Content Container -->
                    <div x-show="selectedUser" class="flex flex-col h-full" style="display: none;">
                        
                        <!-- Light Header Banner -->
                        <div class="relative overflow-hidden border-b border-slate-200/80 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/60 p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                                <div class="flex items-center gap-6">
                                    <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-orange-600 text-4xl font-black text-white shadow-xl shadow-orange-600/20 ring-4 ring-white">
                                        <template x-if="selectedUser?.photo_url">
                                            <img :src="selectedUser.photo_url" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!selectedUser?.photo_url">
                                            <span x-text="selectedUser?.avatar || 'U'"></span>
                                        </template>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-3">
                                            <h2 class="text-3xl font-black tracking-tight text-slate-950" x-text="selectedUser?.name"></h2>
                                            <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider" 
                                                  :class="selectedUser?.is_active ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' : 'bg-slate-200 text-slate-700 ring-1 ring-slate-300'" 
                                                  x-text="selectedUser?.is_active ? 'Active' : 'Inactive'"></span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-bold text-slate-700">
                                            <span x-text="selectedUser?.phone || 'No phone'"></span>
                                            <span class="text-slate-300">•</span>
                                            <span x-text="selectedUser?.email || 'No email'"></span>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-slate-950 font-black" x-text="selectedUser?.warehouse?.name || 'No warehouse assigned'"></span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold text-slate-500">
                                            <span class="text-orange-700 font-extrabold" x-text="selectedUser?.roles?.map(r => r.name).join(', ') || 'No Role'"></span>
                                            <span>•</span>
                                            <span x-text="'Last login: ' + (selectedUser?.last_login_at || 'Never')"></span>
                                            <span>•</span>
                                            <span x-text="'Created: ' + (selectedUser?.created_at || '-')"></span>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" @click="selectedUser = null" class="self-start rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition">
                                    Deselect &times;
                                </button>
                            </div>
                        </div>

                        <!-- Management Actions Toolbar -->
                        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-white px-8 py-4">
                            <button x-show="selectedUser?.can_manage"
                                    @click="openEditModal(selectedUser)"
                                    class="inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-xs font-black text-orange-800 hover:bg-orange-100 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit Profile
                            </button>

                            <button x-show="selectedUser?.can_manage && !selectedUser?.is_self"
                                    @click="toggleUserStatus(selectedUser)"
                                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-xs font-black transition"
                                    :class="selectedUser?.is_active ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
                                    x-text="selectedUser?.is_active ? 'Deactivate Account' : 'Activate Account'">
                            </button>

                            <button x-show="selectedUser?.can_impersonate"
                                    @click="openImpersonationModal(selectedUser)"
                                    class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-xs font-black text-sky-800 hover:bg-sky-100 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                Login as User
                            </button>

                            <a :href="selectedUser?.view_url"
                               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 transition">
                                Detailed Activity Page &rarr;
                            </a>

                            <button x-show="selectedUser?.can_delete && !selectedUser?.is_self"
                                    @click="openDeleteModal(selectedUser)"
                                    class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-black text-red-700 hover:bg-red-100 transition ml-auto">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </div>

                        <!-- Clean Detail Body -->
                        <div class="flex-1 p-8 overflow-y-auto space-y-6 bg-slate-50/40">
                            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Contact & Profile Information</h4>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-bold text-slate-500">Full Name</p>
                                        <p class="mt-1 text-base font-black text-slate-900" x-text="selectedUser?.name"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-500">Email Address</p>
                                        <p class="mt-1 text-base font-black text-slate-900" x-text="selectedUser?.email || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-500">Phone Number</p>
                                        <p class="mt-1 text-base font-black text-slate-900" x-text="selectedUser?.phone || '-'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-500">Account Creator</p>
                                        <p class="mt-1 text-base font-black text-slate-900" x-text="selectedUser?.creator || 'System'"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Warehouse & Role Permissions</h4>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-bold text-slate-500">Assigned System Roles</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <template x-for="role in selectedUser?.roles" :key="`detail-role-${role.id}`">
                                                <span class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-bold text-orange-800" x-text="role.name"></span>
                                            </template>
                                        </div>
                                    </div>

                                    @if($isHqUser)
                                    <div>
                                        <p class="text-xs font-bold text-slate-500">Assigned Branch / Warehouse</p>
                                        <p class="mt-1 text-base font-black text-slate-900" x-text="selectedUser?.warehouse?.name || 'Unassigned'"></p>
                                        <p class="font-mono text-xs font-bold text-slate-400" x-text="selectedUser?.warehouse?.code || ''"></p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @include('warehouse.users.partials.user-modal')

        <!-- Impersonation Confirmation Modal -->
        <template x-teleport="body">
        <div x-show="showImpersonationModal"
             x-cloak
             class="fixed inset-0 z-[110] overflow-y-auto"
             @keydown.escape.window="closeImpersonationModal()">
            <div x-show="showImpersonationModal"
                 x-transition.opacity
                 class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"
                 @click="closeImpersonationModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showImpersonationModal"
                     x-transition
                     @click.stop
                     class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start gap-4 border-b border-slate-100 p-6">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-xl font-black text-white shadow-lg shadow-orange-600/25">
                            <span x-text="impersonatingUser?.avatar || 'U'"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xl font-black text-slate-950">Login as user</h3>
                            <p class="mt-1 text-sm font-semibold leading-6 text-slate-500">You will temporarily act as this user. All actions remain logged.</p>
                        </div>
                        <button type="button" @click="closeImpersonationModal()" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-base font-black text-slate-900" x-text="impersonatingUser?.name || '-'"></p>
                            <p class="mt-1 text-sm font-semibold text-slate-600" x-text="[impersonatingUser?.email, impersonatingUser?.phone].filter(Boolean).join(' / ') || '-'"></p>
                            <p class="mt-2 text-xs font-black uppercase tracking-wide text-slate-400" x-text="impersonatingUser?.warehouse?.name || 'No warehouse'"></p>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                            Your current account will be saved so you can return from the banner after testing.
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" @click="closeImpersonationModal()" :disabled="impersonating" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 disabled:opacity-50">Cancel</button>
                        <button type="button" @click="startImpersonation()" :disabled="impersonating" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700 disabled:opacity-50">
                            <span x-show="!impersonating">Login as user</span>
                            <span x-show="impersonating">Switching...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </template>

        <!-- Delete Confirmation Modal -->
        <template x-teleport="body">
        <div x-show="showDeleteModal"
             x-cloak
             class="fixed inset-0 z-[110] overflow-y-auto"
             @keydown.escape.window="closeDeleteModal()">
            <div x-show="showDeleteModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                 @click="closeDeleteModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showDeleteModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop
                     class="relative w-full max-w-md rounded-2xl border border-slate-200/60 bg-white/95 p-6 shadow-2xl backdrop-blur-xl">
                    <div class="flex items-start gap-4">
                        <div class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-1.414-1.414A2 2 0 0015.536 3H8.464a2 2 0 00-1.414.586L5.636 5M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-slate-900">Delete user</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                This action will permanently remove this user account.
                            </p>
                            <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2">
                                <p class="text-xs font-medium text-slate-500">User</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-900" x-text="deletingUser?.name || '-'"></p>
                                <p class="text-xs text-slate-600" x-text="deletingUser?.email || ''"></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button"
                                @click="closeDeleteModal()"
                                :disabled="deleting"
                                class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-60 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                        <button type="button"
                                @click="deleteUser()"
                                :disabled="deleting"
                                class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed">
                            <span x-show="!deleting">Delete User</span>
                            <span x-show="deleting" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </template>
    </div>
</div>
@endsection