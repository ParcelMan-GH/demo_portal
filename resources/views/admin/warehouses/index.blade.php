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

<div class="space-y-6" x-data="warehousesTable" data-warehouses-config='@json($warehousesConfig)'>
    <!-- Warehouses Datatable -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Warehouses</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Manage warehouse locations and capacity</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Total Warehouses'">
                </span>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Search + Type + Status + Date Range -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search warehouses..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Type Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-56">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="typeFilterName || 'All types'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="typeFilter = ''; typeFilterName = 'All types'; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="typeFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="typeFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All types</span>
                            </button>
                            @foreach($types as $type)
                            <button
                                type="button"
                                @@click="typeFilter = '{{ $type['value'] }}'; typeFilterName = '{{ $type['label'] }}'; loadData(); open = false"
                                class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="typeFilter === '{{ $type['value'] }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="typeFilter === '{{ $type['value'] }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $type['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-56">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="statusFilterName || 'All statuses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="statusFilter = ''; statusFilterName = 'All statuses'; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="statusFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All statuses</span>
                            </button>
                            <button
                                type="button"
                                @@click="statusFilter = 'active'; statusFilterName = 'Active'; loadData(); open = false"
                                class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === 'active' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="statusFilter === 'active'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Active</span>
                            </button>
                            <button
                                type="button"
                                @@click="statusFilter = 'inactive'; statusFilterName = 'Inactive'; loadData(); open = false"
                                class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === 'inactive' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="statusFilter === 'inactive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Inactive</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-56">
                            <input
                                type="text"
                                x-ref="createdRange"
                                placeholder="Created date range"
                                class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 cursor-pointer"
                                readonly
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Right Controls -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <!-- Customize Columns -->
                    <div x-data="{ open: false }" class="relative">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)"
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
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <button type="button" @@click="exportData('excel'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Excel
                            </button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                PDF
                            </button>
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                CSV
                            </button>
                            <div class="border-t border-slate-200/50 my-1"></div>
                            <button type="button" @@click="printData(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Print
                            </button>
                        </div>
                    </div>

                    @if(Auth::guard('admin')->user()->hasPermission('warehouses.create'))
                    <button
                        type="button"
                        @@click="openAddModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Warehouse
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="px-6 py-4">
            <div class="rounded-xl border border-slate-200/50 relative">
            <!-- Loading overlay -->
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>

            <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th x-show="visibleColumns.name" @@click="sort('name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                NAME
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.code" @@click="sort('code')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                CODE
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'code' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.type" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            TYPE
                        </th>
                        <th x-show="visibleColumns.region" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            REGION
                        </th>
                        <th x-show="visibleColumns.district" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            DISTRICT
                        </th>
                        <th x-show="visibleColumns.contact_phone" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            CONTACT PHONE
                        </th>
                        <th x-show="visibleColumns.capacity" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            CAPACITY
                        </th>
                        <th x-show="visibleColumns.status" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            STATUS
                        </th>
                        <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                CREATED AT
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            ACTIONS
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-transparent divide-y divide-slate-100/50">
                    <template x-if="warehouses.length === 0 && !loading">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">
                                No warehouses found
                            </td>
                        </tr>
                    </template>

                    <template x-for="warehouse in warehouses" :key="warehouse.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.name" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="warehouse.name"></td>
                            <td x-show="visibleColumns.code" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600 font-mono" x-text="warehouse.code || '-'"></td>
                            <td x-show="visibleColumns.type" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="{
                                        'bg-blue-100 text-blue-700': warehouse.type === 'origin',
                                        'bg-violet-100 text-violet-700': warehouse.type === 'destination',
                                        'bg-emerald-100 text-emerald-700': warehouse.type === 'both'
                                    }"
                                    x-text="warehouse.type ? warehouse.type.charAt(0).toUpperCase() + warehouse.type.slice(1) : '-'"
                                ></span>
                            </td>
                            <td x-show="visibleColumns.region" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="warehouse.region || '-'"></td>
                            <td x-show="visibleColumns.district" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="warehouse.district || '-'"></td>
                            <td x-show="visibleColumns.contact_phone" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="warehouse.contact_phone || '-'"></td>
                            <td x-show="visibleColumns.capacity" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600 text-center" x-text="warehouse.capacity || '-'"></td>
                            <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="warehouse.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                    x-text="warehouse.is_active ? 'Active' : 'Inactive'"
                                ></span>
                            </td>
                            <td x-show="visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(warehouse.created_at)"></td>
                            <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                <div class="inline-flex items-center gap-1">
                                    <!-- View Button -->
                                    <a
                                        :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="View warehouse">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <!-- Edit Button -->
                                    <template x-if="warehouse.can_manage">
                                        <button
                                            @@click="openEditModal(warehouse)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                            title="Edit warehouse">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                    </template>

                                    <!-- Toggle Status Button -->
                                    <template x-if="warehouse.can_manage">
                                        <button
                                            @@click="toggleWarehouseStatus(warehouse)"
                                            class="p-1.5 rounded-lg transition-colors"
                                            :class="warehouse.is_active ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-teal-600 hover:bg-teal-50'"
                                            :title="warehouse.is_active ? 'Deactivate warehouse' : 'Activate warehouse'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    </template>

                                    <!-- Delete Button -->
                                    <template x-if="warehouse.can_manage">
                                        <button
                                            @@click="openDeleteModal(warehouse)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Delete warehouse">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>

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
                class="relative w-full max-w-lg bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50"
            >
                <!-- Header with Gradient -->
                <div class="relative bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-5 border-b border-slate-200/50 rounded-t-2xl">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <!-- Icon Badge -->
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center shadow-lg shadow-teal-900/20">
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
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

                            <!-- Code & Type Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
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
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
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

                                <!-- Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Type <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        </div>
                                        <select
                                            x-model="form.type"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 transition-all disabled:bg-slate-50 disabled:text-slate-500 appearance-none cursor-pointer"
                                            required
                                        >
                                            <option value="">Select type</option>
                                            <option value="origin">Origin</option>
                                            <option value="destination">Destination</option>
                                            <option value="both">Both</option>
                                        </select>
                                    </div>
                                    <template x-if="errors.type">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.type[0]"></span>
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500 resize-none"
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
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 transition-all disabled:bg-slate-50 disabled:text-slate-500 appearance-none cursor-pointer"
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
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 transition-all disabled:bg-slate-50 disabled:text-slate-500 appearance-none cursor-pointer"
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
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
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
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
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
                                    Capacity <span class="text-slate-400 text-xs font-normal">(Optional)</span>
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
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
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-slate-950 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-slate-900/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
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
