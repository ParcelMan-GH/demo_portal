@extends('admin.layouts.app')

@section('title', 'Sort Batches')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Sort Batches')

@section('content')

<div class="space-y-6" x-data="sortBatchesTable">
    <!-- Sort Batches Datatable -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Sort Batches</h2>
                        <p class="mt-0.5 text-sm text-slate-500">View and manage warehouse sort batches</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Total Batches'"></span>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Search + Filters -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search batch number..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
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
                                <svg x-show="statusFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <svg x-show="statusFilter === '{{ $status['value'] }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $status['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dispatch Mode Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="dispatchModeFilterName || 'All modes'"></span>
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
                                @@click="dispatchModeFilter = ''; dispatchModeFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="dispatchModeFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="dispatchModeFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All modes</span>
                            </button>
                            @foreach($dispatchModes as $mode)
                            <button
                                type="button"
                                @@click="dispatchModeFilter = '{{ $mode['value'] }}'; dispatchModeFilterName = '{{ $mode['label'] }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="dispatchModeFilter === '{{ $mode['value'] }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="dispatchModeFilter === '{{ $mode['value'] }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $mode['label'] }}</span>
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
                            class="absolute left-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-64 overflow-y-auto"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="warehouseFilter = ''; warehouseFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="warehouseFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="warehouseFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <svg x-show="warehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $warehouse->name }}</span>
                                <span class="ml-auto text-[10px] text-slate-400">{{ $warehouse->code }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Right Controls: Create + Export + View -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <!-- Create Sort Batch -->
                    <button
                        type="button"
                        @@click="createBatchModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-white shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Batch
                    </button>
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
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                            <button type="button" @@click="exportData('excel'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Excel
                            </button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                PDF
                            </button>
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                CSV
                            </button>
                            <div class="border-t border-slate-200/50 my-1"></div>
                            <button type="button" @@click="printData(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                        </div>
                    </div>

                    <!-- View (column toggle) -->
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
                                <th x-show="visibleColumns.batch_number" @@click="sort('batch_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        BATCH #
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'batch_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    WAREHOUSE
                                </th>
                                <th x-show="visibleColumns.mode" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    MODE
                                </th>
                                <th x-show="visibleColumns.items" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    ITEMS
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
                                <th x-show="visibleColumns.sealed_at" @@click="sort('sealed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        SEALED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'sealed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.linked" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    LINKED
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
                            <template x-if="batches.length === 0 && !loading">
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 text-xs">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                            </svg>
                                            <span>No sort batches found</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="batch in batches" :key="batch.id">
                                <tr class="hover:bg-slate-50/70">
                                    <!-- Batch Number -->
                                    <td x-show="visibleColumns.batch_number" class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="text-xs font-semibold text-slate-900" x-text="batch.batch_number"></span>
                                    </td>

                                    <!-- Warehouse: origin → destination -->
                                    <td x-show="visibleColumns.warehouse" class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1 text-xs">
                                            <span class="font-medium text-slate-900" x-text="batch.origin_warehouse ? batch.origin_warehouse.name : '—'"></span>
                                            <template x-if="batch.destination_warehouse">
                                                <span class="text-slate-400">
                                                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </span>
                                            </template>
                                            <template x-if="batch.destination_warehouse">
                                                <span class="text-slate-600" x-text="batch.destination_warehouse.name"></span>
                                            </template>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            <span x-text="batch.origin_warehouse ? batch.origin_warehouse.code : ''"></span>
                                            <template x-if="batch.destination_warehouse">
                                                <span x-text="' → ' + batch.destination_warehouse.code"></span>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Dispatch Mode Badge -->
                                    <td x-show="visibleColumns.mode" class="px-4 py-2.5 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-blue-100 text-blue-700': batch.dispatch_mode === 'transfer',
                                                'bg-amber-100 text-amber-700': batch.dispatch_mode === 'local_delivery'
                                            }"
                                            x-text="batch.dispatch_mode_label"
                                        ></span>
                                    </td>

                                    <!-- Items Count -->
                                    <td x-show="visibleColumns.items" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700" x-text="batch.items_count"></span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': batch.status === 'open',
                                                'bg-slate-100 text-slate-700': batch.status === 'sealed'
                                            }"
                                            x-text="batch.status === 'open' ? 'Open' : 'Sealed'"
                                        ></span>
                                    </td>

                                    <!-- Sealed At -->
                                    <td x-show="visibleColumns.sealed_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="batch.sealed_at ? formatDateTime(batch.sealed_at) : '—'"></td>

                                    <!-- Linked: manifest or run -->
                                    <td x-show="visibleColumns.linked" class="px-4 py-2.5 whitespace-nowrap">
                                        <template x-if="batch.has_manifest">
                                            <a
                                                :href="'{{ route('admin.transport-manifests.show', ['manifest' => '__ID__']) }}'.replace('__ID__', batch.manifest_id)"
                                                class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span x-text="batch.manifest_number"></span>
                                            </a>
                                        </template>
                                        <template x-if="!batch.has_manifest && batch.has_delivery_run">
                                            <a
                                                :href="'{{ route('admin.delivery-runs.show', ['run' => '__ID__']) }}'.replace('__ID__', batch.run_id)"
                                                class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 hover:text-amber-800 hover:underline"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                                <span x-text="batch.run_number"></span>
                                            </a>
                                        </template>
                                        <template x-if="!batch.has_manifest && !batch.has_delivery_run">
                                            <span class="text-xs text-slate-400">—</span>
                                        </template>
                                    </td>

                                    <!-- Created At -->
                                    <td x-show="visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(batch.created_at)"></td>

                                    <!-- Actions -->
                                    <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                        <a
                                            :href="'{{ route('admin.sort-batches.show', ['batch' => '__ID__']) }}'.replace('__ID__', batch.id)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                                            title="View batch"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
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
</div>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Create Sort Batch                                                  --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div x-show="createBatchModalOpen"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @@keydown.escape.window="createBatchModalOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="createBatchModalOpen = false"></div>
    <div x-show="createBatchModalOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl shadow-slate-900/30 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">New Sort Batch</h3>
                    <p class="text-slate-300 text-xs mt-0.5">Configure warehouse and dispatch mode</p>
                </div>
            </div>
            <button type="button" @@click="createBatchModalOpen = false" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- Body -->
        <div class="px-6 py-5 space-y-4">
            <!-- Error -->
            <div x-show="createBatchError" x-cloak class="flex items-start gap-2 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3">
                <svg class="w-4 h-4 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-rose-700" x-text="createBatchError"></p>
            </div>
            <!-- Origin Warehouse -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Origin Warehouse <span class="text-rose-500">*</span></label>
                <select x-model="newBatch.origin_warehouse_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30 focus:border-slate-400 transition-colors">
                    <option value="">Select origin warehouse…</option>
                    @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->name }}{{ $wh->code ? ' (' . $wh->code . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Dispatch Mode -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Dispatch Mode <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="relative flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                           :class="newBatch.dispatch_mode === 'transfer' ? 'border-slate-700 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" value="transfer" x-model="newBatch.dispatch_mode" class="sr-only">
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                             :class="newBatch.dispatch_mode === 'transfer' ? 'border-slate-700' : 'border-slate-300'">
                            <div class="w-2 h-2 rounded-full bg-slate-700 transition-all" :class="newBatch.dispatch_mode === 'transfer' ? 'opacity-100' : 'opacity-0'"></div>
                        </div>
                        <span class="text-xs font-semibold" :class="newBatch.dispatch_mode === 'transfer' ? 'text-slate-800' : 'text-slate-500'">Transfer</span>
                    </label>
                    <label class="relative flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                           :class="newBatch.dispatch_mode === 'local_delivery' ? 'border-slate-700 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" value="local_delivery" x-model="newBatch.dispatch_mode" class="sr-only">
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                             :class="newBatch.dispatch_mode === 'local_delivery' ? 'border-slate-700' : 'border-slate-300'">
                            <div class="w-2 h-2 rounded-full bg-slate-700 transition-all" :class="newBatch.dispatch_mode === 'local_delivery' ? 'opacity-100' : 'opacity-0'"></div>
                        </div>
                        <span class="text-xs font-semibold" :class="newBatch.dispatch_mode === 'local_delivery' ? 'text-slate-800' : 'text-slate-500'">Local Delivery</span>
                    </label>
                </div>
            </div>
            <!-- Destination Warehouse (transfer only) -->
            <div x-show="newBatch.dispatch_mode === 'transfer'" x-transition>
                <label class="block text-xs font-bold text-slate-700 mb-2">Destination Warehouse <span class="text-rose-500">*</span></label>
                <select x-model="newBatch.destination_warehouse_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30 focus:border-slate-400 transition-colors">
                    <option value="">Select destination warehouse…</option>
                    @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->name }}{{ $wh->code ? ' (' . $wh->code . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Local delivery hint -->
            <div x-show="newBatch.dispatch_mode === 'local_delivery'" x-transition class="flex items-start gap-3 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3">
                <svg class="w-4 h-4 text-sky-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-sky-700">Items will be delivered directly from the selected warehouse to recipients.</p>
            </div>
            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea x-model="newBatch.notes" rows="2" placeholder="Optional notes about this batch…"
                          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30 focus:border-slate-400 transition-colors resize-none"></textarea>
            </div>
        </div>
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-3">
            <button type="button" @@click="createBatchModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Cancel</button>
            <button type="button" @@click="submitCreateBatch()" :disabled="createBatchLoading"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                <svg x-show="!createBatchLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <svg x-show="createBatchLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                Create Batch
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('sortBatchesTable', () => ({
        batches: [],
        loading: false,
        search: '',
        statusFilter: '',
        statusFilterName: '',
        dispatchModeFilter: '',
        dispatchModeFilterName: '',
        warehouseFilter: '',
        warehouseFilterName: '',
        sortBy: 'created_at',
        sortDirection: 'desc',
        perPage: 50,

        // Create batch modal
        createBatchModalOpen: false,
        createBatchLoading: false,
        createBatchError: '',
        newBatch: {
            origin_warehouse_id: '',
            dispatch_mode: 'local_delivery',
            destination_warehouse_id: '',
            notes: '',
        },
        columns: [
            { key: 'batch_number', label: 'Batch #' },
            { key: 'warehouse',    label: 'Warehouse' },
            { key: 'mode',         label: 'Mode' },
            { key: 'items',        label: 'Items' },
            { key: 'status',       label: 'Status' },
            { key: 'sealed_at',    label: 'Sealed At' },
            { key: 'linked',       label: 'Linked' },
            { key: 'created_at',   label: 'Created At' },
            { key: 'actions',      label: 'Actions' },
        ],
        visibleColumns: {
            batch_number: true,
            warehouse:    true,
            mode:         true,
            items:        true,
            status:       true,
            sealed_at:    true,
            linked:       true,
            created_at:   true,
            actions:      true,
        },
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },

        init() {
            this.loadData();
        },

        loadData(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({
                page,
                per_page: this.perPage,
                sort: this.sortBy,
                direction: this.sortDirection,
            });
            if (this.search)              params.set('search', this.search);
            if (this.statusFilter)        params.set('status', this.statusFilter);
            if (this.dispatchModeFilter)  params.set('dispatch_mode', this.dispatchModeFilter);
            if (this.warehouseFilter)     params.set('origin_warehouse_id', this.warehouseFilter);

            fetch(`{{ route('admin.sort-batches.data') }}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(r => r.json())
                .then(json => {
                    this.batches = json.data;
                    this.meta    = json.meta;
                })
                .finally(() => { this.loading = false; });
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy        = column;
                this.sortDirection = 'asc';
            }
            this.loadData();
        },

        firstPage()    { if (this.meta.current_page > 1) this.loadData(1); },
        previousPage() { if (this.meta.current_page > 1) this.loadData(this.meta.current_page - 1); },
        nextPage()     { if (this.meta.current_page < this.meta.last_page) this.loadData(this.meta.current_page + 1); },
        lastPage()     { if (this.meta.current_page < this.meta.last_page) this.loadData(this.meta.last_page); },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search)             params.append('search', this.search);
                if (this.statusFilter)       params.append('status', this.statusFilter);
                if (this.dispatchModeFilter) params.append('dispatch_mode', this.dispatchModeFilter);
                if (this.warehouseFilter)    params.append('origin_warehouse_id', this.warehouseFilter);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `{{ route('admin.sort-batches.export') }}?${params}`;
                    return;
                }

                const response = await fetch(`{{ route('admin.sort-batches.export') }}?${params}`, {
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
                if (this.search)             params.append('search', this.search);
                if (this.statusFilter)       params.append('status', this.statusFilter);
                if (this.dispatchModeFilter) params.append('dispatch_mode', this.dispatchModeFilter);
                if (this.warehouseFilter)    params.append('origin_warehouse_id', this.warehouseFilter);

                const response = await fetch(`{{ route('admin.sort-batches.export') }}?${params}`, {
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
            const printWindow = window.open('', '_blank');
            if (!printWindow) { alert('Pop-up blocked. Please allow pop-ups to print.'); return; }
            const doc = printWindow.document;
            const headers = Object.keys(data[0]);
            doc.title = 'Sort Batches Export';
            doc.body.innerHTML = '';
            const style = doc.createElement('style');
            style.textContent = 'body{font-family:sans-serif;padding:20px}h1{font-size:22px;margin-bottom:16px;color:#1e293b}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border:1px solid #e2e8f0;padding:7px 10px;text-align:left;font-size:11px}th{background:#f1f5f9;font-weight:600;color:#475569}tr:nth-child(even){background:#f8fafc}';
            doc.head.appendChild(style);
            const title = doc.createElement('h1');
            title.textContent = 'Sort Batches';
            doc.body.appendChild(title);
            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow);
            table.appendChild(thead);
            const tbody = doc.createElement('tbody');
            data.forEach(row => {
                const tr = doc.createElement('tr');
                headers.forEach(h => { const td = doc.createElement('td'); td.textContent = row[h] ?? '-'; tr.appendChild(td); });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);
            setTimeout(() => printWindow.print(), 250);
        },

        downloadCSV(data) {
            if (!data.length) return;
            const headers = Object.keys(data[0]);
            const csv = [headers.join(','), ...data.map(row => headers.map(h => `"${String(row[h] ?? '').replace(/"/g, '""')}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'sort_batches.csv';
            document.body.appendChild(a); a.click();
            document.body.removeChild(a); URL.revokeObjectURL(url);
        },

        formatDateTime(value) {
            if (!value) return '—';
            const d = new Date(value);
            if (isNaN(d)) return value;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        },

        async submitCreateBatch() {
            this.createBatchError = '';
            if (!this.newBatch.origin_warehouse_id) {
                this.createBatchError = 'Please select an origin warehouse.';
                return;
            }
            if (this.newBatch.dispatch_mode === 'transfer' && !this.newBatch.destination_warehouse_id) {
                this.createBatchError = 'Please select a destination warehouse for transfer mode.';
                return;
            }
            this.createBatchLoading = true;
            try {
                const body = {
                    origin_warehouse_id: this.newBatch.origin_warehouse_id,
                    dispatch_mode: this.newBatch.dispatch_mode,
                    notes: this.newBatch.notes || null,
                };
                if (this.newBatch.dispatch_mode === 'transfer') {
                    body.destination_warehouse_id = this.newBatch.destination_warehouse_id;
                }
                const resp = await fetch('{{ route('admin.sort-batches.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(body),
                });
                const json = await resp.json();
                if (json.success && json.data?.batch) {
                    this.createBatchModalOpen = false;
                    this.newBatch = { origin_warehouse_id: '', dispatch_mode: 'local_delivery', destination_warehouse_id: '', notes: '' };
                    window.location.href = '{{ route('admin.sort-batches.show', ['batch' => '__ID__']) }}'.replace('__ID__', json.data.batch.id);
                } else {
                    this.createBatchError = json.message || 'Failed to create sort batch.';
                }
            } catch (e) {
                this.createBatchError = 'An unexpected error occurred.';
            } finally {
                this.createBatchLoading = false;
            }
        },
    }));
});
</script>
@endpush
