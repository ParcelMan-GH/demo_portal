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
<div class="space-y-6">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30"
         x-data="rolesTable"
         data-roles-config="{{ json_encode($rolesTableConfig) }}">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2Zm10-10V7a4 4 0 00-8 0v4"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Roles</h2>
                        <p class="truncate text-sm text-slate-500">HQ manages role templates; warehouses assign approved roles to their local users.</p>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700" x-text="meta.total + ' roles'"></span>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                        </svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search role, slug, permission..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                        </svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

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
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="col.label"></span>
                                    <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

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
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                            <button type="button" @@click="exportData('excel'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Excel</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">PDF</button>
                            <button type="button" @@click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">CSV</button>
                            <div class="my-1 border-t border-slate-200/50"></div>
                            <button type="button" @@click="printData(); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Print</button>
                        </div>
                    </div>

                    @if($canManageRoleDefinitions)
                        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Role
                        </a>
                    @endif
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="statusFilter" @@change="statusFilterName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Definition</label>
                        <select x-model="typeFilter" @@change="typeFilterName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All definitions</option>
                            <option value="system">Default role</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assignment</label>
                        <select x-model="assignableFilter" @@change="assignableFilterName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All assignment states</option>
                            <option value="1">Warehouse assignable</option>
                            <option value="0">Restricted assignment</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                        <input type="text" x-ref="createdRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Users Range</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="usersMin" placeholder="Min" class="w-full border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                            <input type="number" min="0" x-model="usersMax" placeholder="Max" class="w-full border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Permissions Range</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="permissionsMin" placeholder="Min" class="w-full border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                            <input type="number" min="0" x-model="permissionsMax" placeholder="Max" class="w-full border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2" x-show="activeFilterChips().length">
                <template x-for="chip in activeFilterChips()" :key="chip.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        <span x-text="chip.label"></span>
                        <button type="button" @@click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
            <div class="overflow-x-auto">
                <table class="min-w-[1120px] w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th x-show="visibleColumns.name" @@click="sort('name')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                <div class="flex items-center">Role<svg class="ml-1 h-2.5 w-2.5" :class="sortBy === 'name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                            </th>
                            <th x-show="visibleColumns.users_count" @@click="sort('users_count')" class="cursor-pointer px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Users</th>
                            <th x-show="visibleColumns.permissions_count" @@click="sort('permissions_count')" class="cursor-pointer px-5 py-3 text-center text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Permissions</th>
                            <th x-show="visibleColumns.assignable_label" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Assignment</th>
                            <th x-show="visibleColumns.type_label" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Definition</th>
                            <th x-show="visibleColumns.status_label" @@click="sort('is_active')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Status</th>
                            <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Created At</th>
                            <th x-show="visibleColumns.actions" class="px-5 py-3 text-right text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="roles.length === 0 && !loading">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">No roles found</td>
                            </tr>
                        </template>

                        <template x-for="role in roles" :key="role.id">
                            <tr class="transition hover:bg-orange-50/30">
                                <td x-show="visibleColumns.name" class="min-w-[300px] px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-sm font-black text-orange-700 ring-1 ring-orange-100" x-text="role.name ? role.name.charAt(0).toUpperCase() : 'R'"></div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900" x-text="role.name"></p>
                                            <p class="mt-0.5 max-w-[360px] text-xs font-semibold text-slate-500" x-text="role.description || role.slug"></p>
                                        </div>
                                    </div>
                                </td>
                                <td x-show="visibleColumns.users_count" class="whitespace-nowrap px-5 py-4 text-center text-sm font-bold text-slate-700" x-text="role.users_count"></td>
                                <td x-show="visibleColumns.permissions_count" class="whitespace-nowrap px-5 py-4 text-center text-sm font-bold text-slate-700" x-text="role.permissions_count"></td>
                                <td x-show="visibleColumns.assignable_label" class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="role.is_assignable_by_warehouse_manager ? 'bg-orange-50 text-orange-700 ring-1 ring-orange-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" x-text="role.assignable_label"></span>
                                </td>
                                <td x-show="visibleColumns.type_label" class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="role.is_system_role ? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'" x-text="role.is_system_role ? 'Default' : 'Custom'"></span>
                                </td>
                                <td x-show="visibleColumns.status_label" class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="role.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" x-text="role.status_label"></span>
                                </td>
                                <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="role.created_at"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-5 py-4 text-right text-xs font-medium">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a :href="role.view_url" class="inline-flex items-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-bold text-orange-800 hover:bg-orange-100">View</a>
                                        <a x-show="role.can_edit" :href="role.edit_url" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                                        <button x-show="role.can_delete" @@click="deleteRole(role)" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs font-semibold text-slate-600">
                            Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600">Rows</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @@click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700">
                                        <span x-text="perPage"></span>
                                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display: none;">
                                        <button type="button" @@click="perPage = 10; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 10 ? 'bg-slate-100' : ''">10</button>
                                        <button type="button" @@click="perPage = 25; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 25 ? 'bg-slate-100' : ''">25</button>
                                        <button type="button" @@click="perPage = 50; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 50 ? 'bg-slate-100' : ''">50</button>
                                        <button type="button" @@click="perPage = 100; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 100 ? 'bg-slate-100' : ''">100</button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1">
                                <button @@click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                                <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
