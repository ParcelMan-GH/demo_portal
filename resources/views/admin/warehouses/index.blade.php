@extends('admin.layouts.app')

@section('title', 'Warehouse Management')
@section('breadcrumb-parent', 'Management')
@section('breadcrumb-current', 'Warehouses')

@section('content')

@php
    $warehousesConfig = [
        'endpoint' => route('admin.warehouses.data'),
        'exportEndpoint' => route('admin.warehouses.export'),
        'storeEndpoint' => route('admin.warehouses.store'),
        'regionsEndpoint' => route('admin.warehouses.regions'),
        'districtsEndpointTemplate' => route('admin.warehouses.districts', ['region' => '__REGION__']),
        'showEndpointTemplate' => route('admin.warehouses.show.json', ['warehouse' => '__ID__']),
    ];
@endphp

<div class="space-y-5" x-data="warehousesTable" data-warehouses-config='@json($warehousesConfig)'>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <button type="button" @@click="clearStatusFilter()" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 21h18M4 10h16v11H4V10Zm-.5-3L12 3l8.5 4"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Total</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.total || 0"></p>
            </div>
        </button>
        <button type="button" @@click="setStatusFilter('active')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Active</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="activeCount()"></p>
            </div>
        </button>
        <button type="button" @@click="setStatusFilter('inactive')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v3.75m0 3.75h.008M3.75 19.5h16.5L12 4.5 3.75 19.5Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Inactive</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="inactiveCount()"></p>
            </div>
        </button>
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Staff</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="staffCount()"></p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 21h18M4 10h16v11H4V10Zm-.5-3L12 3l8.5 4M8 14v3m4-3v3m4-3v3"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Warehouses</h2>
                        <p class="truncate text-sm text-slate-500">Manage HQ warehouse locations, capacity, contacts, and staff access.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4"/></svg>
                        Roles
                    </a>
                    @if(Auth::guard('admin')->user()->hasPermission('warehouses.create'))
                    <button type="button" @@click="openAddModal()" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Warehouse
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search name, code, phone..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                            View
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="col.label"></span>
                                    <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2Z"/></svg>
                            Export
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <button type="button" @@click="exportData('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <div class="my-1 border-t border-slate-100"></div>
                            <button type="button" @@click="printData(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="statusFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Region</label>
                        <select x-model="regionFilter" @@change="onFilterRegionChange()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All regions</option>
                            <template x-for="region in regions" :key="region.id"><option :value="region.id" x-text="region.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">District</label>
                        <select x-model="districtFilter" :disabled="!regionFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">All districts</option>
                            <template x-for="district in filterDistricts" :key="district.id"><option :value="district.id" x-text="district.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                        <input type="text" x-ref="createdRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Capacity (m³)</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="capacityMin" placeholder="Min" class="min-w-0 border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <input type="number" min="0" x-model="capacityMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assigned Users</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="usersMin" placeholder="Min" class="min-w-0 border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <input type="number" min="0" x-model="usersMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
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

            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-[1180px] w-full table-fixed divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.name" @@click="sort('name')" class="w-[20%] cursor-pointer px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Warehouse</th>
                            <th x-show="visibleColumns.code" @@click="sort('code')" class="w-[10%] cursor-pointer px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Code</th>
                            <th x-show="visibleColumns.region" class="w-[14%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Region</th>
                            <th x-show="visibleColumns.district" class="w-[14%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">District</th>
                            <th x-show="visibleColumns.contact_phone" class="w-[14%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Contact</th>
                            <th x-show="visibleColumns.capacity" class="w-[10%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Capacity</th>
                            <th x-show="visibleColumns.users_count" @@click="sort('users_count')" class="w-[8%] cursor-pointer px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Users</th>
                            <th x-show="visibleColumns.is_active" class="w-[9%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th x-show="visibleColumns.actions" class="w-[11%] px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="warehouses.length === 0 && !loading">
                            <tr><td :colspan="visibleColumnCount()" class="px-4 py-12 text-center text-sm font-semibold text-slate-400">No warehouses found</td></tr>
                        </template>
                        <template x-for="warehouse in warehouses" :key="warehouse.id">
                            <tr class="transition hover:bg-orange-50/20">
                                <td x-show="visibleColumns.name" class="px-4 py-3 align-top">
                                    <a :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id" class="font-extrabold text-slate-900 hover:text-orange-700" x-text="warehouse.name"></a>
                                    <p class="mt-1 line-clamp-1 text-[11px] font-medium text-slate-500" x-text="warehouse.address || 'No address set'"></p>
                                </td>
                                <td x-show="visibleColumns.code" class="px-4 py-3 align-top font-mono text-xs font-bold text-slate-600" x-text="warehouse.code || '-'"></td>
                                <td x-show="visibleColumns.region" class="px-4 py-3 align-top text-xs font-semibold text-slate-700" x-text="warehouse.region || '-'"></td>
                                <td x-show="visibleColumns.district" class="px-4 py-3 align-top text-xs font-semibold text-slate-700" x-text="warehouse.district || '-'"></td>
                                <td x-show="visibleColumns.contact_phone" class="px-4 py-3 align-top">
                                    <p class="text-xs font-semibold text-slate-700" x-text="warehouse.contact_phone || '-'"></p>
                                    <p class="mt-1 truncate text-[11px] text-slate-400" x-text="warehouse.contact_email || ''"></p>
                                </td>
                                <td x-show="visibleColumns.capacity" class="px-4 py-3 align-top text-xs font-semibold text-slate-700" x-text="warehouse.capacity ? warehouse.capacity + ' m³' : '-'"></td>
                                <td x-show="visibleColumns.users_count" class="px-4 py-3 text-center align-top text-xs font-extrabold text-slate-900" x-text="warehouse.users_count ?? 0"></td>
                                <td x-show="visibleColumns.is_active" class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold" :class="warehouse.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" x-text="warehouse.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td x-show="visibleColumns.actions" class="px-4 py-3 text-right align-top">
                                    <div class="inline-flex items-center gap-1">
                                        <a :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id" class="rounded-lg p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600" title="View"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg></a>
                                        <template x-if="warehouse.can_manage"><button type="button" @@click="openEditModal(warehouse)" class="rounded-lg p-2 text-slate-400 transition hover:bg-emerald-50 hover:text-emerald-600" title="Edit"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg></button></template>
                                        <template x-if="warehouse.can_manage"><button type="button" @@click="toggleWarehouseStatus(warehouse)" class="rounded-lg p-2 text-slate-400 transition" :class="warehouse.is_active ? 'hover:bg-amber-50 hover:text-amber-600' : 'hover:bg-emerald-50 hover:text-emerald-600'" :title="warehouse.is_active ? 'Deactivate' : 'Activate'"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg></button></template>
                                        <template x-if="warehouse.can_manage"><button type="button" @@click="openDeleteModal(warehouse)" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="Delete"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79 18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/></svg></button></template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="warehouses.length === 0 && !loading">
                    <div class="px-5 py-12 text-center text-sm font-semibold text-slate-400">No warehouses found</div>
                </template>
                <template x-for="warehouse in warehouses" :key="warehouse.id">
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id" class="text-base font-extrabold text-slate-900" x-text="warehouse.name"></a>
                                <p class="mt-1 font-mono text-xs font-bold text-slate-500" x-text="warehouse.code || '-'"></p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" :class="warehouse.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" x-text="warehouse.is_active ? 'Active' : 'Inactive'"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Region</p><p class="mt-1 font-bold text-slate-700" x-text="warehouse.region || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">District</p><p class="mt-1 font-bold text-slate-700" x-text="warehouse.district || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Users</p><p class="mt-1 font-bold text-slate-700" x-text="warehouse.users_count ?? 0"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Capacity</p><p class="mt-1 font-bold text-slate-700" x-text="warehouse.capacity ? warehouse.capacity + ' m³' : '-'"></p></div>
                        </div>
                        <div class="mt-4 flex items-center justify-end gap-2">
                            <a :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">View</a>
                            <template x-if="warehouse.can_manage"><button type="button" @@click="openEditModal(warehouse)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">Edit</button></template>
                        </div>
                    </article>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm font-semibold text-slate-600">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span></p>
                    <div class="flex flex-wrap items-center gap-3">
                        <select x-model="perPage" @@change="meta.current_page = 1; loadData()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-sm font-bold text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></span>
                        <button type="button" @@click="previousPage()" :disabled="meta.current_page === 1" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 disabled:opacity-40">Prev</button>
                        <button type="button" @@click="nextPage()" :disabled="meta.current_page === meta.last_page" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @keydown.escape.window="closeModal()"
    >
        <!-- Backdrop -->
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @click="closeModal()"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-3xl bg-white backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50"
            >
                <!-- Header with Gradient -->
                <div class="relative bg-white px-6 py-5 border-b border-slate-200/70">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <!-- Icon Badge -->
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-orange-600 flex items-center justify-center shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                </svg>
                            </div>
                            <!-- Title & Description -->
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" x-text="modalMode === 'add' ? 'Add New Warehouse' : (modalMode === 'edit' ? 'Edit Warehouse' : 'View Warehouse')"></h3>
                                <p class="text-sm text-slate-500 mt-1" x-text="modalMode === 'add' ? 'Create a new warehouse location' : (modalMode === 'edit' ? 'Update warehouse information and settings' : 'View warehouse details')"></p>
                            </div>
                        </div>
                        <!-- Close Button -->
                        <button @click="closeModal()" class="flex-shrink-0 rounded-xl p-2 text-slate-400 hover:bg-white hover:text-slate-700 transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <form @submit.prevent="saveWarehouse()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Warehouse Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.name"
                                        :disabled="modalMode === 'view'"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="Main Warehouse"
                                        required
                                    >
                                </div>
                                <template x-if="errors.name">
                                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="errors.name[0]"></span>
                                    </p>
                                </template>
                            </div>

                            <!-- Code -->
                            <div class="grid grid-cols-1 gap-5">
                                <!-- Code -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Code <span class="text-slate-400 text-xs font-normal">(Auto-generated if empty)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="text"
                                            x-model="form.code"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="WH-001"
                                        >
                                    </div>
                                    <template x-if="errors.code">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.code[0]"></span>
                                        </p>
                                    </template>
                                </div>

                            </div>

                            <!-- Address -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Address <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-0 pl-3 flex items-start pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <textarea
                                        x-model="form.address"
                                        :disabled="modalMode === 'view'"
                                        rows="2"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500 resize-none"
                                        placeholder="Full address of warehouse location"
                                    ></textarea>
                                </div>
                                <template x-if="errors.address">
                                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="errors.address[0]"></span>
                                    </p>
                                </template>
                            </div>

                            <!-- Region & District Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Region -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Region <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <select
                                            x-model="form.region_id"
                                            :disabled="modalMode === 'view'"
                                            @@change="onRegionChange()"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 transition-all disabled:bg-slate-50 disabled:text-slate-500 appearance-none cursor-pointer"
                                        >
                                            <option value="">Select region</option>
                                            <template x-for="region in regions" :key="region.id">
                                                <option :value="region.id" x-text="region.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <template x-if="errors.region_id">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.region_id[0]"></span>
                                        </p>
                                    </template>
                                </div>

                                <!-- District -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        District <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <select
                                            x-model="form.district_id"
                                            :disabled="modalMode === 'view' || !form.region_id || loadingDistricts"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 transition-all disabled:bg-slate-50 disabled:text-slate-500 appearance-none cursor-pointer"
                                        >
                                            <option value="">Select district</option>
                                            <template x-for="district in districts" :key="district.id">
                                                <option :value="district.id" x-text="district.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <p x-show="loadingDistricts" class="mt-1 text-xs text-slate-400">Loading districts...</p>
                                    <template x-if="errors.district_id">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.district_id[0]"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                            <!-- Contact Phone & Email Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Contact Phone -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Contact Phone <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="text"
                                            x-model="form.contact_phone"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="+233 24 123 4567"
                                        >
                                    </div>
                                    <template x-if="errors.contact_phone">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.contact_phone[0]"></span>
                                        </p>
                                    </template>
                                </div>

                                <!-- Contact Email -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Contact Email <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="email"
                                            x-model="form.contact_email"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="warehouse@example.com"
                                        >
                                    </div>
                                    <template x-if="errors.contact_email">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.contact_email[0]"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                            <!-- Capacity -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Capacity (m&sup3;) <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="number"
                                        x-model="form.capacity"
                                        :disabled="modalMode === 'view'"
                                        min="0"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="500"
                                    >
                                </div>
                                <template x-if="errors.capacity">
                                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="errors.capacity[0]"></span>
                                    </p>
                                </template>
                            </div>

                            <!-- Status Toggle -->
                            <div x-show="modalMode !== 'view'" class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Warehouse Status</h4>
                                            <p class="text-xs text-slate-500" x-text="form.is_active ? 'Warehouse is operational' : 'Warehouse is inactive'"></p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @@click="form.is_active = !form.is_active"
                                        :class="form.is_active ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-slate-300'"
                                        class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-sm"
                                    >
                                        <span
                                            :class="form.is_active ? 'translate-x-7' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                                        ></span>
                                    </button>
                                </div>
                            </div>

                            <!-- View mode additional info -->
                            <template x-if="modalMode === 'view'">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Status Card -->
                                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-4 border border-emerald-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-xs font-bold text-slate-700">Warehouse Status</span>
                                        </div>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold shadow-sm"
                                            :class="form.is_active ? 'bg-emerald-500 text-white' : 'bg-slate-400 text-white'"
                                            x-text="form.is_active ? 'Active' : 'Inactive'"
                                        ></span>
                                    </div>

                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between gap-3 border-t border-slate-200/50 bg-gradient-to-r from-slate-50/50 to-slate-100/30 px-6 py-5 rounded-b-2xl">
                            <p class="text-xs text-slate-500">
                                <span class="text-rose-500">*</span> Required fields
                            </p>
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @@click="closeModal()"
                                    class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all shadow-sm"
                                >
                                    <span x-text="modalMode === 'view' ? 'Close' : 'Cancel'"></span>
                                </button>
                                <button
                                    x-show="modalMode !== 'view'"
                                    type="submit"
                                    :disabled="saving"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-orange-600/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                                >
                                    <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="saving ? 'Saving...' : (modalMode === 'add' ? 'Create Warehouse' : 'Save Changes')"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
        x-show="$store.warehousesDelete.show"
        x-cloak
        class="fixed inset-0 z-[110] overflow-y-auto"
        @keydown.escape.window="$store.warehousesDelete.show = false"
    >
        <!-- Backdrop -->
        <div x-show="$store.warehousesDelete.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-600/60 backdrop-blur-[2px]"
             @click="$store.warehousesDelete.show = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="$store.warehousesDelete.show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-lg bg-white rounded-2xl shadow-[0_18px_45px_rgba(15,23,42,0.28)] border border-slate-200/80 overflow-hidden"
            >
                <div class="px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Delete Warehouse</h3>
                            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">
                                Are you sure you want to delete
                                <span class="font-semibold text-slate-800" x-text="$store.warehousesDelete.warehouse?.name"></span>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-5">
                    <button
                        type="button"
                        @click="$store.warehousesDelete.show = false"
                        class="text-sm font-medium text-slate-600 hover:text-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="$store.warehousesDelete.onConfirm && $store.warehousesDelete.onConfirm()"
                        :disabled="$store.warehousesDelete.deleting"
                        class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-full shadow-lg shadow-rose-600/25 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="$store.warehousesDelete.deleting">Deleting...</span>
                        <span x-show="!$store.warehousesDelete.deleting">Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
