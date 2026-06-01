@extends('admin.layouts.app')

@section('title', 'Riders & Drivers')
@section('breadcrumb-parent', 'Management')
@section('breadcrumb-current', 'Riders & Drivers')

@section('content')

@php
    $driversConfig = [
        'endpoint' => route('admin.drivers.data'),
        'exportEndpoint' => route('admin.drivers.export'),
        'storeEndpoint' => route('admin.drivers.store'),
        'baseEndpoint' => route('admin.drivers.index'),
        'csrfToken' => csrf_token(),
    ];
@endphp

<div class="space-y-5" x-data="driversTable" data-drivers-config='@json($driversConfig)'>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <button type="button" @@click="clearAccountStatusFilter()" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h7.5m-7.5 0H3.375A1.125 1.125 0 0 1 2.25 17.625V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V14.25m-19.5 0V6.375c0-.621.504-1.125 1.125-1.125h11.25c.621 0 1.125.504 1.125 1.125v7.875m-12.375 0h18"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Total</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.total || 0"></p>
            </div>
        </button>
        <button type="button" @@click="setAccountStatusFilter('active', 'Active')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Active Accounts</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="activeCount()"></p>
            </div>
        </button>
        <button type="button" @@click="setAccountStatusFilter('inactive', 'Inactive')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v3.75m0 3.75h.008v.008H12V16.5Zm-8.25 3h16.5a1.5 1.5 0 0 0 1.296-2.256L13.296 3.006a1.5 1.5 0 0 0-2.592 0L2.454 17.244A1.5 1.5 0 0 0 3.75 19.5Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Inactive Accounts</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="inactiveCount()"></p>
            </div>
        </button>
        <button type="button" @@click="setAvailabilityFilter('available', 'Available')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h7.5m-7.5 0H3.375A1.125 1.125 0 0 1 2.25 17.625V7.5h13.5v11.25m0 0h1.5m-1.5 0a1.5 1.5 0 0 1 3 0m-3 0a1.5 1.5 0 0 0 3 0m0 0h1.125c.621 0 1.125-.504 1.125-1.125V13.5h-4.5V9h2.25l3.375 4.5"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Available Now</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="availableCount()"></p>
            </div>
        </button>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h7.5m-7.5 0H3.375A1.125 1.125 0 0 1 2.25 17.625V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V14.25m-19.5 0V6.375c0-.621.504-1.125 1.125-1.125h11.25c.621 0 1.125.504 1.125 1.125v7.875m-12.375 0h18"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Riders & Drivers</h2>
                            <p class="truncate text-sm text-slate-500">Manage rider accounts, vehicle details, capabilities, and assignment access.</p>
                        </div>
                    </div>
                </div>
                @if(Auth::guard('admin')->user()->hasPermission('drivers.create'))
                <div class="flex lg:justify-end">
                    <button type="button" @@click="openAddModal()" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Rider/Driver
                    </button>
                </div>
                @endif
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search name, email, or phone..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
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
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2z"/></svg>
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
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Account Status</label>
                        <select x-model="accountStatusFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All account statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Availability</label>
                        <select x-model="availabilityFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All availability</option>
                            <option value="available">Available</option>
                            <option value="busy">Busy</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Capability</label>
                        <select x-model="capabilityFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All capabilities</option>
                            <option value="pickup">Pickup</option>
                            <option value="transport">Transport</option>
                            <option value="delivery">Delivery</option>
                            <option value="bus_handoff">Bus Handoff</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Range</label>
                        <input type="text" x-ref="createdRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
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
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

            <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-[1180px] w-full table-fixed divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th x-show="visibleColumns.name" @@click="sort('name')" class="w-[17%] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1">
                                Name
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.email" @@click="sort('email')" class="w-[21%] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1">
                                Email
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'email' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.phone" @@click="sort('phone')" class="w-[13%] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1">
                                Phone
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'phone' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.capabilities" class="w-[16%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Capabilities
                        </th>
                        <th x-show="visibleColumns.vehicle_type" class="w-[12%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Vehicle
                        </th>
                        <th x-show="visibleColumns.status" class="w-[8%] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Availability
                        </th>
                        <th x-show="visibleColumns.is_active" class="w-[8%] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Account
                        </th>
                        <th x-show="visibleColumns.assignments" class="w-[7%] px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Pickups
                        </th>
                        <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="w-[13%] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1">
                                Created At
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-transparent divide-y divide-slate-100/50">
                    <template x-if="drivers.length === 0 && !loading">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h7.5"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No riders or drivers match the current filters</p>
                                    <button type="button" @@click="clearFilter('all')" class="text-xs font-semibold text-orange-600 hover:underline">Clear filters</button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="driver in drivers" :key="driver.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.name" class="px-4 py-3 align-top">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-orange-50 text-sm font-black text-orange-700 ring-1 ring-orange-100">
                                        <template x-if="driver.photo_url">
                                            <img :src="driver.photo_url" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!driver.photo_url">
                                            <span x-text="driver.avatar || (driver.name || 'R').charAt(0).toUpperCase()"></span>
                                        </template>
                                    </div>
                                    <a :href="'{{ route('admin.drivers.index') }}/' + driver.id" class="block min-w-0 break-words font-bold leading-5 text-slate-900 hover:text-orange-700 hover:underline" x-text="driver.name"></a>
                                </div>
                            </td>
                            <td x-show="visibleColumns.email" class="break-all px-4 py-3 align-top text-slate-600" x-text="driver.email"></td>
                            <td x-show="visibleColumns.phone" class="whitespace-nowrap px-4 py-3 align-top text-slate-600" x-text="driver.phone"></td>
                            <td x-show="visibleColumns.capabilities" class="px-4 py-3 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="capability in capabilityList(driver)" :key="capability.value">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-600" x-text="capability.label"></span>
                                    </template>
                                </div>
                            </td>
                            <td x-show="visibleColumns.vehicle_type" class="px-4 py-3 align-top">
                                <p class="font-semibold capitalize text-slate-700" x-text="driver.vehicle_type || '-'"></p>
                                <p class="mt-0.5 text-[10px] text-slate-400" x-text="driver.vehicle_number || 'No plate'"></p>
                            </td>
                            <td x-show="visibleColumns.status" class="whitespace-nowrap px-4 py-3 align-top text-center">
                                <span
                                    class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="driverStatusBadgeClass(driver)"
                                    x-text="driverStatusLabel(driver)"
                                ></span>
                            </td>
                            <td x-show="visibleColumns.is_active" class="whitespace-nowrap px-4 py-3 align-top text-center">
                                <span
                                    class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="activeBadgeClass(driver)"
                                    x-text="driver.is_active ? 'Active' : 'Inactive'"
                                ></span>
                            </td>
                            <td x-show="visibleColumns.assignments" class="whitespace-nowrap px-3 py-3 align-top text-center">
                                <span class="font-bold text-slate-900" x-text="driver.assignments_count || 0"></span>
                            </td>
                            <td x-show="visibleColumns.created_at" class="px-4 py-3 align-top text-slate-600" x-text="formatDateTime(driver.created_at)"></td>
                            <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 align-top text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a
                                        :href="'{{ route('admin.drivers.index') }}/' + driver.id"
                                        class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                        View
                                    </a>
                                    <template x-if="driver.can_manage">
                                        <button
                                            @@click="openEditModal(driver)"
                                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                            Edit
                                        </button>
                                    </template>
                                    <template x-if="driver.can_manage">
                                        <button
                                            @@click="toggleDriverStatus(driver)"
                                            class="rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold"
                                            :class="driver.is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100'"
                                            x-text="driver.is_active ? 'Disable' : 'Enable'">
                                        </button>
                                    </template>
                                    <template x-if="driver.can_manage">
                                        <button
                                            @@click="openDeleteModal(driver)"
                                            class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">
                                            Delete
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
            </div>

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
                                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display:none">
                                    <button type="button" @@click="setPerPage(10); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 10 ? 'bg-slate-100' : ''">10</button>
                                    <button type="button" @@click="setPerPage(25); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 25 ? 'bg-slate-100' : ''">25</button>
                                    <button type="button" @@click="setPerPage(50); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 50 ? 'bg-slate-100' : ''">50</button>
                                    <button type="button" @@click="setPerPage(100); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 100 ? 'bg-slate-100' : ''">100</button>
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

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="drivers.length === 0 && !loading">
                    <div class="px-4 py-12 text-center text-sm text-slate-400">No riders or drivers match the current filters.</div>
                </template>
                <template x-for="driver in drivers" :key="driver.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-orange-50 text-sm font-black text-orange-700 ring-1 ring-orange-100">
                                    <template x-if="driver.photo_url">
                                        <img :src="driver.photo_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!driver.photo_url">
                                        <span x-text="driver.avatar || (driver.name || 'R').charAt(0).toUpperCase()"></span>
                                    </template>
                                </div>
                                <a :href="'{{ route('admin.drivers.index') }}/' + driver.id" class="min-w-0 break-words text-sm font-extrabold text-slate-900 hover:text-orange-700" x-text="driver.name"></a>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="activeBadgeClass(driver)" x-text="driver.is_active ? 'Active' : 'Inactive'"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Phone</p><p class="font-bold text-slate-800" x-text="driver.phone || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Pickups</p><p class="font-bold text-slate-800" x-text="driver.assignments_count || 0"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Vehicle</p><p class="font-bold capitalize text-slate-800" x-text="driver.vehicle_type || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Availability</p><p class="font-bold text-slate-800" x-text="driverStatusLabel(driver)"></p></div>
                            <div class="col-span-2"><p class="font-black uppercase tracking-wide text-slate-400">Email</p><p class="break-words font-bold text-slate-800" x-text="driver.email || '-'"></p></div>
                            <div class="col-span-2">
                                <p class="font-black uppercase tracking-wide text-slate-400">Capabilities</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <template x-for="capability in capabilityList(driver)" :key="capability.value">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-600" x-text="capability.label"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a :href="'{{ route('admin.drivers.index') }}/' + driver.id" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">View</a>
                            <template x-if="driver.can_manage">
                                <button @@click="openEditModal(driver)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">Edit</button>
                            </template>
                            <template x-if="driver.can_manage">
                                <button @@click="toggleDriverStatus(driver)" class="rounded-lg border px-3 py-2 text-xs font-bold" :class="driver.is_active ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-orange-200 bg-orange-50 text-orange-700'" x-text="driver.is_active ? 'Disable' : 'Enable'"></button>
                            </template>
                            <template x-if="driver.can_manage">
                                <button @@click="openDeleteModal(driver)" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">Delete</button>
                            </template>
                        </div>
                    </div>
                </template>
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
                class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl"
            >
                <!-- Header -->
                <div class="relative border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <!-- Icon Badge -->
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <!-- Title & Description -->
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" x-text="modalMode === 'add' ? 'Add Rider/Driver' : (modalMode === 'edit' ? 'Edit Rider/Driver' : 'View Rider/Driver')"></h3>
                                <p class="text-sm text-slate-500 mt-1" x-text="modalMode === 'add' ? 'Create a rider or driver account with contact details' : (modalMode === 'edit' ? 'Update rider/driver information and settings' : 'View rider/driver account details')"></p>
                            </div>
                        </div>
                        <!-- Close Button -->
                        <button @click="closeModal()" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <form @submit.prevent="saveDriver()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                            <!-- Photo -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Driver Photo</label>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-orange-50 text-xl font-black text-orange-700 ring-1 ring-orange-100">
                                        <template x-if="form.photo_preview_url">
                                            <img :src="form.photo_preview_url" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!form.photo_preview_url">
                                            <span x-text="(form.name || 'R').trim().charAt(0).toUpperCase() || 'R'"></span>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input x-ref="driverPhotoInput"
                                               type="file"
                                               accept="image/*"
                                               :disabled="modalMode === 'view'"
                                               @@change="handleDriverPhoto($event)"
                                               class="block w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-orange-50 file:px-4 file:py-2 file:text-sm file:font-black file:text-orange-700 hover:file:bg-orange-100 focus:border-orange-400 focus:outline-none focus:ring-4 focus:ring-orange-100 disabled:bg-slate-50 disabled:text-slate-500">
                                        <template x-if="errors.profile_photo">
                                            <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                <span x-text="errors.profile_photo[0]"></span>
                                            </p>
                                        </template>
                                        <button type="button"
                                                x-show="form.profile_photo && modalMode !== 'view'"
                                                x-cloak
                                                @@click="clearSelectedPhoto()"
                                                class="mt-2 text-xs font-black text-slate-500 hover:text-slate-800">
                                            Clear selected photo
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.name"
                                        :disabled="modalMode === 'view'"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="John Doe"
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

                            <!-- Phone & Email Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Phone <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="text"
                                            x-model="form.phone"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="+233 24 123 4567"
                                            required
                                        >
                                    </div>
                                    <template x-if="errors.phone">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.phone[0]"></span>
                                        </p>
                                    </template>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Email <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="email"
                                            x-model="form.email"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="driver@example.com"
                                        >
                                    </div>
                                    <template x-if="errors.email">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.email[0]"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                            <!-- Vehicle Info Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- Vehicle Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Vehicle Type
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h8m-8 5h4m4.5 2H18l-2-7H8L6 14h.5m11 0a2.5 2.5 0 11-5 0m5 0a2.5 2.5 0 10-5 0m-4.5 0H6m0 0a2.5 2.5 0 11-5 0m5 0a2.5 2.5 0 10-5 0"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="text"
                                            x-model="form.vehicle_type"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="Motorcycle"
                                        >
                                    </div>
                                    <template x-if="errors.vehicle_type">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.vehicle_type[0]"></span>
                                        </p>
                                    </template>
                                </div>

                                <!-- Vehicle Number -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Vehicle Number
                                    </label>
                                    <input
                                        type="text"
                                        x-model="form.vehicle_number"
                                        :disabled="modalMode === 'view'"
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="GR-1234-21"
                                    >
                                    <template x-if="errors.vehicle_number">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.vehicle_number[0]"></span>
                                        </p>
                                    </template>
                                </div>

                                <!-- License Number -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        License Number
                                    </label>
                                    <input
                                        type="text"
                                        x-model="form.license_number"
                                        :disabled="modalMode === 'view'"
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="DL-123456"
                                    >
                                    <template x-if="errors.license_number">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.license_number[0]"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                            <!-- Task Capabilities -->
                            <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800">Assignment Capabilities</h4>
                                        <p class="text-xs text-slate-500">Select what this person is allowed to handle.</p>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600" x-text="(form.task_capabilities || []).length + ' selected'"></span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <input type="checkbox"
                                               value="pickup"
                                               x-model="form.task_capabilities"
                                               :disabled="modalMode === 'view'"
                                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        <span class="text-sm font-medium text-slate-700">Pickup</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <input type="checkbox"
                                               value="transport"
                                               x-model="form.task_capabilities"
                                               :disabled="modalMode === 'view'"
                                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        <span class="text-sm font-medium text-slate-700">Transport</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <input type="checkbox"
                                               value="delivery"
                                               x-model="form.task_capabilities"
                                               :disabled="modalMode === 'view'"
                                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        <span class="text-sm font-medium text-slate-700">Delivery</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50/40 px-3 py-2">
                                        <input type="checkbox"
                                               value="bus_handoff"
                                               x-model="form.task_capabilities"
                                               :disabled="modalMode === 'view'"
                                               class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                        <span class="text-sm font-medium text-violet-700">Bus Handoff</span>
                                    </label>
                                </div>

                                <template x-if="errors.task_capabilities">
                                    <p class="mt-2 text-xs text-rose-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="errors.task_capabilities[0]"></span>
                                    </p>
                                </template>
                            </div>

                            <!-- Password -->
                            <div x-show="modalMode !== 'view'" x-cloak x-data="{ showPassword: false, showPasswordConfirmation: false, changeDriverPassword: false }" x-effect="changeDriverPassword = modalMode === 'add'">
                                <div x-show="modalMode === 'edit'" x-cloak class="mb-4">
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-700">
                                        <input type="checkbox" x-model="changeDriverPassword" @@change="if (!changeDriverPassword) { form.password = ''; form.password_confirmation = ''; errors.password = null; errors.password_confirmation = null; }" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        Change password
                                    </label>
                                </div>

                                <div x-show="modalMode === 'add' || changeDriverPassword" x-cloak class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            <span x-text="modalMode === 'add' ? 'Password' : 'New Password'"></span>
                                            <span x-show="modalMode === 'add'" class="text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                :type="showPassword ? 'text' : 'password'"
                                                x-model="form.password"
                                                :disabled="modalMode === 'view'"
                                                class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 pr-12 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:bg-slate-50 disabled:text-slate-500"
                                                :placeholder="modalMode === 'edit' ? 'Leave blank to keep current' : 'Minimum 8 characters'"
                                                :required="modalMode === 'add'"
                                            >
                                            <button type="button" @@click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-4 text-slate-500 transition hover:text-slate-800" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                                                <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.829M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.732 0 3.36-.44 4.772-1.212M9.88 9.88L6.228 6.228m7.892 7.892l3.652 3.652"/></svg>
                                            </button>
                                        </div>
                                        <template x-if="errors.password">
                                            <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                                <span x-text="errors.password[0]"></span>
                                            </p>
                                        </template>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Confirm Password <span x-show="modalMode === 'add'" class="text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                                x-model="form.password_confirmation"
                                                :disabled="modalMode === 'view'"
                                                class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 pr-12 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:bg-slate-50 disabled:text-slate-500"
                                                placeholder="Re-enter password"
                                                :required="modalMode === 'add'"
                                            >
                                            <button type="button" @@click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 px-4 text-slate-500 transition hover:text-slate-800" :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'">
                                                <svg x-show="!showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="showPasswordConfirmation" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.829M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.732 0 3.36-.44 4.772-1.212M9.88 9.88L6.228 6.228m7.892 7.892l3.652 3.652"/></svg>
                                            </button>
                                        </div>
                                        <template x-if="errors.password_confirmation">
                                            <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                                <span x-text="errors.password_confirmation[0]"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Toggle -->
                            <div x-show="modalMode !== 'view'" class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 flex items-center justify-center shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Account Status</h4>
                                            <p class="text-xs text-slate-500" x-text="form.is_active ? 'Can accept assignments' : 'Account access disabled'"></p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @@click="form.is_active = !form.is_active"
                                        :class="form.is_active ? 'bg-orange-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-orange-100 focus:ring-offset-2 shadow-sm"
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
                                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-xs font-bold text-slate-700">Account Status</span>
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
                        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5">
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
                                    <span x-text="saving ? 'Saving...' : (modalMode === 'add' ? 'Create Rider/Driver' : 'Save Changes')"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <!-- Delete Confirmation Modal -->
    <div
        x-show="$store.driversDelete.show"
        x-cloak
        class="fixed inset-0 z-[110] overflow-y-auto"
        @keydown.escape.window="$store.driversDelete.show = false"
    >
        <!-- Backdrop -->
        <div x-show="$store.driversDelete.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @click="$store.driversDelete.show = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="$store.driversDelete.show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl"
            >
                <div class="px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Delete Rider/Driver</h3>
                            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">
                                Are you sure you want to delete
                                <span class="font-semibold text-slate-800" x-text="$store.driversDelete.driver?.name"></span>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5">
                    <button
                        type="button"
                        @click="$store.driversDelete.show = false"
                        class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="$store.driversDelete.onConfirm && $store.driversDelete.onConfirm()"
                        :disabled="$store.driversDelete.deleting"
                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-rose-600/20 transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-show="$store.driversDelete.deleting">Deleting...</span>
                        <span x-show="!$store.driversDelete.deleting">Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
