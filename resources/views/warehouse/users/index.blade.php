@extends('warehouse.layouts.app')

@section('title', 'Warehouse Users')
@section('breadcrumb-parent', 'Warehouse')
@section('breadcrumb-current', 'Users')
@section('page-title', 'Warehouse Users')

@section('content')
<div class="space-y-6">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30"
         x-data="usersTable"
         data-endpoint="{{ route('warehouse.users.data') }}"
         data-export-endpoint="{{ route('warehouse.users.export') }}"
         data-store-endpoint="{{ route('warehouse.users.store') }}"
         data-csrf-token="{{ csrf_token() }}">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Warehouse Users</h2>
                        <p class="truncate text-sm text-slate-500">Manage staff access, roles, and account status for {{ $warehouse->name ?? 'this warehouse' }}.</p>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700" x-text="meta.total + ' users'"></span>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <!-- Search + Role + Date Range -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 max-w-sm">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                        >
                        <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div x-data="{ open: false }" class="relative w-full sm:w-56">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Role</label>
                        <button
                            type="button"
                            @@click="open = !open"
                            class="inline-flex w-full items-center justify-between rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition hover:bg-slate-50"
                        >
                            <span x-text="roleFilterName || 'All roles'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute right-0 z-50 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="roleFilter = ''; roleFilterName = 'All roles'; loadData(); open = false"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                :class="roleFilter === '' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="roleFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All roles</span>
                            </button>
                            @foreach($roles as $role)
                                <button
                                    type="button"
                                    data-role-name="{{ $role->name }}"
                                    @@click="roleFilter = {{ $role->id }}; roleFilterName = $el.dataset.roleName; loadData(); open = false"
                                    class="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    :class="roleFilter === '{{ $role->id }}' ? 'bg-orange-50 text-orange-700' : ''"
                                >
                                    <svg x-show="roleFilter === '{{ $role->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>{{ $role->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="w-full sm:w-44">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="statusFilter" @@change="statusFilterName = $event.target.selectedOptions[0].text; loadData()"
                                class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-56">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                            <input
                                type="text"
                                x-ref="createdRange"
                                placeholder="Created date range"
                                class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                readonly
                            >
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Right Controls -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <!-- Customize Columns -->
                    <div x-data="{ open: false }" class="relative">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                             style="display: none;">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)"
                                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
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
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                             style="display: none;">
                            <button type="button" @@click="exportData('excel'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Excel
                            </button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                PDF
                            </button>
                            <button type="button" @@click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                <svg class="w-4 h-4 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                CSV
                            </button>
                            <div class="border-t border-slate-200/50 my-1"></div>
                            <button type="button" @@click="printData(); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Print
                            </button>
                        </div>
                    </div>

                    @if($canCreateUsers)
                    <button @@click="openCreateModal()"
                       class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add User
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th x-show="visibleColumns.name" @@click="sort('name')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            <div class="flex items-center">
                                NAME
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.role" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            ROLE
                        </th>
                        <th x-show="visibleColumns.email" @@click="sort('email')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            <div class="flex items-center">
                                EMAIL
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'email' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.phone" @@click="sort('phone')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            <div class="flex items-center">
                                PHONE
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'phone' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.status" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            STATUS
                        </th>
                        <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            <div class="flex items-center">
                                CREATED AT
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.last_login_at" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            LAST LOGIN
                        </th>
                        <th x-show="visibleColumns.actions" class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                            ACTIONS
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <template x-if="users.length === 0 && !loading">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                No users found
                            </td>
                        </tr>
                    </template>

                    <template x-for="user in users" :key="user.id">
                        <tr class="transition hover:bg-orange-50/30">
                            <td x-show="visibleColumns.name" class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-sm font-black text-orange-700 ring-1 ring-orange-100" x-text="user.avatar"></div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900" x-text="user.name"></p>
                                        <p class="text-xs font-semibold text-slate-500" x-text="user.creator ? 'Created by ' + user.creator : ''"></p>
                                    </div>
                                </div>
                            </td>
                            <td x-show="visibleColumns.role" class="px-5 py-4 text-sm text-slate-600">
                                <template x-if="user.roles && user.roles.length">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="role in user.roles" :key="`role-${user.id}-${role.id}`">
                                            <span class="inline-flex items-center rounded-full border border-orange-100 bg-orange-50 px-2.5 py-1 text-[11px] font-bold text-orange-700"
                                                  x-text="role.name"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!user.roles || !user.roles.length">
                                    <span>-</span>
                                </template>
                            </td>
                            <td x-show="visibleColumns.email" class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="user.email"></td>
                            <td x-show="visibleColumns.phone" class="whitespace-nowrap px-5 py-4 font-mono text-sm font-bold text-slate-600" x-text="user.phone || '-'"></td>
                            <td x-show="visibleColumns.status" class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="user.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="user.created_at"></td>
                            <td x-show="visibleColumns.last_login_at" class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="user.last_login_at || '-'"></td>
                            <td x-show="visibleColumns.actions" class="whitespace-nowrap px-5 py-4 text-right text-xs font-medium">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <a :href="user.view_url"
                                       class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                        View
                                    </a>
                                    <button x-show="user.can_manage"
                                            @@click="openEditModal(user)"
                                            class="inline-flex items-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-bold text-orange-800 hover:bg-orange-100">
                                        Edit
                                    </button>
                                    <button x-show="user.can_manage && !user.is_self"
                                            @@click="toggleUserStatus(user)"
                                            class="inline-flex items-center rounded-lg border px-3 py-1.5 text-xs font-bold"
                                            :class="user.is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                                            x-text="user.is_active ? 'Deactivate' : 'Activate'">
                                    </button>
                                    <button x-show="user.can_delete && !user.is_self"
                                            @@click="openDeleteModal(user)"
                                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                                        Delete
                                    </button>
                                </div>
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
                        <span x-text="meta.from"></span>
                        to
                        <span x-text="meta.to"></span>
                        of
                        <span x-text="meta.total"></span>
                        results
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-600">Rows per page</span>
                            <div x-data="{ open: false }" class="relative">
                                <button
                                    type="button"
                                    @@click="open = !open"
                                    class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"
                                >
                                    <span x-text="perPage"></span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div
                                    x-show="open"
                                    @@click.away="open = false"
                                    x-transition
                                    class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                    style="display: none;"
                                >
                                    <button type="button" @@click="perPage = 10; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                    <button type="button" @@click="perPage = 25; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                    <button type="button" @@click="perPage = 50; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                    <button type="button" @@click="perPage = 100; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                </div>
                            </div>
                        </div>

                        <div class="text-xs font-medium text-slate-600">
                            Page
                            <span x-text="meta.current_page"></span>
                            of
                            <span x-text="meta.last_page"></span>
                        </div>

                        <div class="flex space-x-1">
                            <button
                                @@click="firstPage()"
                                :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                </svg>
                            </button>

                            <button
                                @@click="previousPage()"
                                :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>

                            <button
                                @@click="nextPage()"
                                :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                            <button
                                @@click="lastPage()"
                                :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
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

        @include('warehouse.users.partials.user-modal')

        <!-- Delete Confirmation Modal -->
        <template x-teleport="body">
        <div x-show="showDeleteModal"
             x-cloak
             class="fixed inset-0 z-[110] overflow-y-auto"
             @@keydown.escape.window="closeDeleteModal()">
            <div x-show="showDeleteModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                 @@click="closeDeleteModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showDeleteModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @@click.stop
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
                                @@click="closeDeleteModal()"
                                :disabled="deleting"
                                class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-60 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                        <button type="button"
                                @@click="deleteUser()"
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
