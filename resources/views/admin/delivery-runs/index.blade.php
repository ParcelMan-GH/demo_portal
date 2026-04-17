@extends('admin.layouts.app')

@section('title', 'Delivery Runs')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Delivery Runs')

@section('content')

@php
$drConfig = [
    'endpoint' => route('admin.delivery-runs.data'),
    'exportEndpoint' => route('admin.delivery-runs.export'),
    'storeUrl' => route('admin.delivery-runs.store'),
    'storeFromItemsUrl' => route('admin.delivery-runs.store-from-items'),
    'warehousesUrl' => route('admin.assignments.available-warehouses'),
];
@endphp
<div class="space-y-6" x-data="deliveryRunsTable" data-delivery-runs-config='@json($drConfig)'>
    <!-- Delivery Runs Datatable -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Delivery Runs</h2>
                        <p class="mt-0.5 text-sm text-slate-500">View and manage all delivery runs</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Total Runs'"></span>
                    <button @@click="openCreateModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-md active:scale-[0.98]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Create Run
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Search + Status + Warehouse + Date Range -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search runs..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-52">
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
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="statusFilter = ''; statusFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="statusFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All statuses</span>
                            </button>
                            @foreach($statuses as $status)
                            <button
                                type="button"
                                @@click="statusFilter = '{{ $status['value'] }}'; statusFilterName = '{{ $status['label'] }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '{{ $status['value'] }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="statusFilter === '{{ $status['value'] }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $status['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Warehouse Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-52">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="warehouseFilterName || 'All warehouses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-60 overflow-y-auto"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="warehouseFilter = ''; warehouseFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="warehouseFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="warehouseFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All warehouses</span>
                            </button>
                            @foreach($warehouses as $warehouse)
                            <button
                                type="button"
                                @@click="warehouseFilter = '{{ $warehouse->id }}'; warehouseFilterName = '{{ $warehouse->name }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="warehouseFilter === '{{ $warehouse->id }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="warehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $warehouse->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Date Range Picker -->
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-56">
                            <input
                                type="text"
                                x-ref="createdRange"
                                placeholder="Date range"
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
                                <th x-show="visibleColumns.run_number" @@click="sort('run_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        RUN #
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'run_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    WAREHOUSE
                                </th>
                                <th x-show="visibleColumns.driver" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    DRIVER
                                </th>
                                <th x-show="visibleColumns.stops_items" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    STOPS / ITEMS
                                </th>
                                <th x-show="visibleColumns.status" @@click="sort('status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        STATUS
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.dispatched_at" @@click="sort('dispatched_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        DISPATCHED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'dispatched_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.completed_at" @@click="sort('completed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        COMPLETED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <template x-if="runs.length === 0 && !loading">
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 text-xs">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            </svg>
                                            <span>No delivery runs found</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="run in runs" :key="run.id">
                                <tr class="hover:bg-slate-50/70">
                                    <!-- Run Number -->
                                    <td x-show="visibleColumns.run_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="run.run_number"></td>

                                    <!-- Warehouse -->
                                    <td x-show="visibleColumns.warehouse" class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-900" x-text="run.warehouse_name || '—'"></div>
                                        <div class="text-[10px] text-slate-500" x-text="run.warehouse_code || ''"></div>
                                    </td>

                                    <!-- Driver -->
                                    <td x-show="visibleColumns.driver" class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-900" x-text="run.driver_name || '—'"></div>
                                        <div class="text-[10px] text-slate-500" x-text="run.driver_phone || ''"></div>
                                    </td>

                                    <!-- Stops / Items -->
                                    <td x-show="visibleColumns.stops_items" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-50 text-[10px] font-semibold text-blue-700" x-text="run.stops_count" title="Stops"></span>
                                            <span class="text-slate-300">/</span>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700" x-text="run.items_count" title="Items"></span>
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-slate-100 text-slate-700':  run.status === 'draft',
                                                'bg-blue-100 text-blue-700':    run.status === 'assigned',
                                                'bg-amber-100 text-amber-700':  run.status === 'out_for_delivery',
                                                'bg-orange-100 text-orange-700': run.status === 'partially_delivered',
                                                'bg-emerald-100 text-emerald-700': run.status === 'completed',
                                                'bg-red-100 text-red-700':      run.status === 'cancelled'
                                            }"
                                            x-text="run.status_label"
                                        ></span>
                                    </td>

                                    <!-- Dispatched At -->
                                    <td x-show="visibleColumns.dispatched_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="run.dispatched_at ? formatDateTime(run.dispatched_at) : '—'"></td>

                                    <!-- Completed At -->
                                    <td x-show="visibleColumns.completed_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="run.completed_at ? formatDateTime(run.completed_at) : '—'"></td>

                                    <!-- Actions -->
                                    <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                        <div class="inline-flex items-center gap-1">
                                            <a
                                                :href="'{{ route('admin.delivery-runs.show', ['run' => '__ID__']) }}'.replace('__ID__', run.id)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="View delivery run"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
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

    {{-- Create Run Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @@click="showCreateModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @@click.stop>
            <h3 class="text-base font-bold text-slate-900 mb-4">Create Delivery Run</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warehouse *</label>
                    <select x-model="createForm.warehouse_id" @@change="loadSortBatches()" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        <option value="">Select Warehouse</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sort Batch (Sealed, Local Delivery) *</label>
                    <select x-model="createForm.sort_batch_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" :disabled="!createForm.warehouse_id">
                        <option value="">Select Sort Batch</option>
                        <template x-for="batch in sortBatches" :key="batch.id">
                            <option :value="batch.id" x-text="batch.batch_number + ' (' + batch.items_count + ' items)'"></option>
                        </template>
                    </select>
                    <p x-show="createForm.warehouse_id && sortBatches.length === 0" class="text-[10px] text-amber-600 mt-1">No sealed local delivery batches found for this warehouse.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <button @@click="showCreateModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">Cancel</button>
                <button @@click="submitCreateRun()" :disabled="createForm.submitting || !createForm.warehouse_id || !createForm.sort_batch_id"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">
                    <span x-show="!createForm.submitting">Create Run</span>
                    <span x-show="createForm.submitting">Creating...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function buildDeliveryRunsTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        storeUrl: config.storeUrl,
        storeFromItemsUrl: config.storeFromItemsUrl,
        warehousesUrl: config.warehousesUrl,
        runs: [],
        loading: false,
        showCreateModal: false,
        createForm: { warehouse_id: '', sort_batch_id: '', submitting: false },
        warehouses: [],
        sortBatches: [],
        search: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        warehouseFilter: '',
        warehouseFilterName: 'All warehouses',
        createdFrom: '',
        createdTo: '',
        dateRangePicker: null,
        sortBy: 'created_at',
        sortDirection: 'desc',
        perPage: 25,
        meta: {
            total: 0,
            from: 0,
            to: 0,
            current_page: 1,
            last_page: 1,
        },
        columns: [
            { key: 'run_number',    label: 'Run #' },
            { key: 'warehouse',     label: 'Warehouse' },
            { key: 'driver',        label: 'Driver' },
            { key: 'stops_items',   label: 'Stops / Items' },
            { key: 'status',        label: 'Status' },
            { key: 'dispatched_at', label: 'Dispatched At' },
            { key: 'completed_at',  label: 'Completed At' },
            { key: 'actions',       label: 'Actions' },
        ],
        visibleColumns: {
            run_number:    true,
            warehouse:     true,
            driver:        true,
            stops_items:   true,
            status:        true,
            dispatched_at: true,
            completed_at:  true,
            actions:       true,
        },

        init() {
            this.initDateRange();
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page:      this.meta.current_page,
                    per_page:  this.perPage,
                    sort:      this.sortBy,
                    direction: this.sortDirection,
                });
                if (this.search)          params.set('search',       this.search);
                if (this.statusFilter)    params.set('status',       this.statusFilter);
                if (this.warehouseFilter) params.set('warehouse_id', this.warehouseFilter);
                if (this.createdFrom)     params.set('date_from',    this.createdFrom);
                if (this.createdTo)       params.set('date_to',      this.createdTo);

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Network response was not ok');

                const json = await response.json();
                this.runs = json.data;
                this.meta = json.meta;
            } catch (error) {
                console.error('Failed to load delivery runs:', error);
                this.runs = [];
            } finally {
                this.loading = false;
            }
        },

        sort(field) {
            if (this.sortBy === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy        = field;
                this.sortDirection = 'asc';
            }
            this.meta.current_page = 1;
            this.loadData();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        initDateRange() {
            if (!this.$refs.createdRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                const $input = window.$(this.$refs.createdRange);
                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'right',
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    ranges: {
                        'Today':        [window.moment(), window.moment()],
                        'Yesterday':    [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days':  [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month':   [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month':   [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });
                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.createdFrom = picker.startDate.format('YYYY-MM-DD');
                    this.createdTo   = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.createdFrom} - ${this.createdTo}`);
                    this.meta.current_page = 1;
                    this.loadData();
                });
                $input.on('cancel.daterangepicker', () => {
                    this.createdFrom = '';
                    this.createdTo   = '';
                    $input.val('');
                    this.meta.current_page = 1;
                    this.loadData();
                });
                this.dateRangePicker = $input.data('daterangepicker');
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            const cssId = 'daterangepicker-css';
            if (!document.getElementById(cssId)) {
                const link = document.createElement('link');
                link.id = cssId; link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                document.head.appendChild(link);
            }
            const loadScript = (id, src) => new Promise(resolve => {
                if (document.getElementById(id)) return resolve();
                const s = document.createElement('script');
                s.id = id; s.src = src; s.onload = resolve;
                document.body.appendChild(s);
            });
            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    window.moment = window.moment || moment;
                    return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
                })
                .then(setupPicker);
        },

        firstPage() {
            if (this.meta.current_page === 1) return;
            this.meta.current_page = 1;
            this.loadData();
        },
        previousPage() {
            if (this.meta.current_page <= 1) return;
            this.meta.current_page--;
            this.loadData();
        },
        nextPage() {
            if (this.meta.current_page >= this.meta.last_page) return;
            this.meta.current_page++;
            this.loadData();
        },
        lastPage() {
            if (this.meta.current_page === this.meta.last_page) return;
            this.meta.current_page = this.meta.last_page;
            this.loadData();
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search)          params.append('search',       this.search);
                if (this.statusFilter)    params.append('status',       this.statusFilter);
                if (this.warehouseFilter) params.append('warehouse_id', this.warehouseFilter);
                if (this.createdFrom)     params.append('date_from',    this.createdFrom);
                if (this.createdTo)       params.append('date_to',      this.createdTo);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `${this.exportEndpoint}?${params}`;
                    return;
                }

                const response = await fetch(`${this.exportEndpoint}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Export failed');
                const result = await response.json();
                if (format === 'csv') this.downloadCSV(result.data);
            } catch (err) {
                console.error('Export failed:', err);
                alert('Export failed. Please try again.');
            }
        },

        async printData() {
            try {
                const params = new URLSearchParams();
                if (this.search)          params.append('search',       this.search);
                if (this.statusFilter)    params.append('status',       this.statusFilter);
                if (this.warehouseFilter) params.append('warehouse_id', this.warehouseFilter);
                if (this.createdFrom)     params.append('date_from',    this.createdFrom);
                if (this.createdTo)       params.append('date_to',      this.createdTo);

                const response = await fetch(`${this.exportEndpoint}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Failed to fetch data');
                const result = await response.json();
                this.openPrintWindow(result.data);
            } catch (err) {
                console.error('Print failed:', err);
                alert('Print failed. Please try again.');
            }
        },

        openPrintWindow(data) {
            if (!data.length) { alert('No data to print'); return; }
            const win = window.open('', '_blank');
            if (!win) { alert('Pop-up blocked. Please allow pop-ups to print.'); return; }
            const headers = Object.keys(data[0]);
            win.document.title = 'Delivery Runs Export';
            const style = win.document.createElement('style');
            style.textContent = 'body{font-family:sans-serif;padding:20px} h1{font-size:24px;margin-bottom:20px;color:#1e293b} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:12px} th{background:#f1f5f9;font-weight:600;color:#475569} tr:nth-child(even){background:#f8fafc}';
            win.document.head.appendChild(style);
            const h1 = win.document.createElement('h1');
            h1.textContent = 'Delivery Runs';
            win.document.body.appendChild(h1);
            const table = win.document.createElement('table');
            const thead = win.document.createElement('thead');
            const hrow = win.document.createElement('tr');
            headers.forEach(h => { const th = win.document.createElement('th'); th.textContent = h; hrow.appendChild(th); });
            thead.appendChild(hrow); table.appendChild(thead);
            const tbody = win.document.createElement('tbody');
            data.forEach(row => {
                const tr = win.document.createElement('tr');
                headers.forEach(h => { const td = win.document.createElement('td'); td.textContent = row[h] ?? '-'; tr.appendChild(td); });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody); win.document.body.appendChild(table);
            setTimeout(() => win.print(), 250);
        },

        downloadCSV(data) {
            if (!data.length) return;
            const headers = Object.keys(data[0]);
            const csv = [headers.join(','), ...data.map(row => headers.map(h => `"${String(row[h] ?? '').replace(/"/g, '""')}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'delivery_runs.csv';
            document.body.appendChild(a); a.click();
            document.body.removeChild(a); URL.revokeObjectURL(url);
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        },

        async openCreateModal() {
            this.showCreateModal = true;
            if (this.warehouses.length === 0) {
                try {
                    const r = await fetch(this.warehousesUrl, { headers: { 'Accept': 'application/json' } });
                    const j = await r.json();
                    this.warehouses = j.data || j || [];
                } catch (e) {}
            }
        },

        async loadSortBatches() {
            if (!this.createForm.warehouse_id) { this.sortBatches = []; return; }
            try {
                const r = await fetch('/admin/sort-batches-data?status=sealed&origin_warehouse_id=' + this.createForm.warehouse_id + '&per_page=100', { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                this.sortBatches = (j.data || []).filter(b => b.dispatch_mode === 'local_delivery');
            } catch (e) { this.sortBatches = []; }
        },

        async submitCreateRun() {
            if (!this.createForm.warehouse_id || !this.createForm.sort_batch_id) {
                window.showToast?.('Please select a warehouse and sort batch.', 'error');
                return;
            }
            this.createForm.submitting = true;
            try {
                const r = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ warehouse_id: this.createForm.warehouse_id, sort_batch_id: this.createForm.sort_batch_id }),
                });
                const j = await r.json();
                if (j.success) {
                    window.showToast?.(j.message || 'Delivery run created.', 'success');
                    this.showCreateModal = false;
                    this.createForm = { warehouse_id: '', sort_batch_id: '', submitting: false };
                    this.loadData();
                    if (j.data?.run_id) window.location.href = '/admin/delivery-runs/' + j.data.run_id;
                } else {
                    window.showToast?.(j.message || 'Failed to create run.', 'error');
                }
            } catch (e) { window.showToast?.('Error creating run.', 'error'); }
            this.createForm.submitting = false;
        },
    };
}

(function () {
    const container = document.querySelector('[data-delivery-runs-config]');
    if (!container) return;
    let config;
    try { config = JSON.parse(container.getAttribute('data-delivery-runs-config')); } catch (e) { return; }

    function register() {
        if (!window.Alpine) return;
        Alpine.data('deliveryRunsTable', () => buildDeliveryRunsTable(config));
    }

    if (window.Alpine) { register(); }
    else { document.addEventListener('alpine:init', register); }
})();
</script>
@endpush
