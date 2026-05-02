@extends('admin.layouts.app')

@section('title', 'Transport Manifests')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Transport Manifests')

@section('content')

<div class="space-y-6" x-data="transportManifestsTable">
    <!-- Transport Manifests Datatable -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Transport Manifests</h2>
                        <p class="mt-0.5 text-sm text-slate-500">View and track all inter-warehouse transport manifests</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Total Manifests'">
                    </span>
                    <button
                        type="button"
                        @@click="openCreateManifestModal()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-800"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Manifest
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Search + Status + Warehouse filters -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search manifests..."
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
                            class="absolute left-0 mt-2 w-full min-w-[160px] rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
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

                    <!-- Origin Warehouse Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-48">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span class="truncate" x-text="originWarehouseFilterName || 'Origin warehouse'"></span>
                            <svg class="w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-56 max-h-64 overflow-y-auto rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="originWarehouseFilter = ''; originWarehouseFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="originWarehouseFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="originWarehouseFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All origins</span>
                            </button>
                            @foreach($warehouses as $warehouse)
                            <button
                                type="button"
                                @@click="originWarehouseFilter = '{{ $warehouse->id }}'; originWarehouseFilterName = '{{ $warehouse->name }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="originWarehouseFilter === '{{ $warehouse->id }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="originWarehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="truncate">{{ $warehouse->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Destination Warehouse Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-48">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span class="truncate" x-text="destWarehouseFilterName || 'Destination warehouse'"></span>
                            <svg class="w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-56 max-h-64 overflow-y-auto rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="destWarehouseFilter = ''; destWarehouseFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="destWarehouseFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="destWarehouseFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All destinations</span>
                            </button>
                            @foreach($warehouses as $warehouse)
                            <button
                                type="button"
                                @@click="destWarehouseFilter = '{{ $warehouse->id }}'; destWarehouseFilterName = '{{ $warehouse->name }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="destWarehouseFilter === '{{ $warehouse->id }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="destWarehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="truncate">{{ $warehouse->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-52">
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

                <!-- Right Controls: Export + View -->
                <div class="flex flex-wrap items-center justify-end gap-3">
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
                                <th x-show="visibleColumns.manifest_number" @@click="sort('manifest_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        MANIFEST #
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'manifest_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.route" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    FROM &rarr; TO
                                </th>
                                <th x-show="visibleColumns.driver" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    DRIVER
                                </th>
                                <th x-show="visibleColumns.items" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    ITEMS
                                </th>
                                <th x-show="visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    STATUS
                                </th>
                                <th x-show="visibleColumns.dispatched_at" @@click="sort('dispatched_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        DISPATCHED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'dispatched_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.arrived_at" @@click="sort('arrived_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        ARRIVED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'arrived_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.received_at" @@click="sort('received_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        RECEIVED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'received_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <template x-if="manifests.length === 0 && !loading">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                            </svg>
                                            <span>No transport manifests found</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="manifest in manifests" :key="manifest.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td x-show="visibleColumns.manifest_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="manifest.manifest_number"></td>
                                    <td x-show="visibleColumns.route" class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <span class="font-semibold text-slate-900" x-text="manifest.origin_warehouse || '—'"></span>
                                            <span class="text-slate-400">
                                                <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                </svg>
                                            </span>
                                            <span class="font-semibold text-slate-900" x-text="manifest.destination_warehouse || '—'"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5" x-text="[manifest.origin_warehouse_code, manifest.destination_warehouse_code].filter(Boolean).join(' → ')"></div>
                                    </td>
                                    <td x-show="visibleColumns.driver" class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-900" x-text="manifest.driver_name || '—'"></div>
                                        <div class="text-[10px] text-slate-500" x-text="manifest.driver_phone || ''"></div>
                                    </td>
                                    <td x-show="visibleColumns.items" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700" x-text="manifest.items_count"></span>
                                    </td>
                                    <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-slate-100 text-slate-700':   manifest.status === 'draft',
                                                'bg-blue-100 text-blue-700':     manifest.status === 'assigned',
                                                'bg-purple-100 text-purple-700': manifest.status === 'loading',
                                                'bg-amber-100 text-amber-700':   manifest.status === 'in_transit',
                                                'bg-cyan-100 text-cyan-700':     manifest.status === 'arrived',
                                                'bg-emerald-100 text-emerald-700': manifest.status === 'received',
                                                'bg-red-100 text-red-700':       manifest.status === 'cancelled'
                                            }"
                                            x-text="manifest.status_label"
                                        ></span>
                                    </td>
                                    <td x-show="visibleColumns.dispatched_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(manifest.dispatched_at)"></td>
                                    <td x-show="visibleColumns.arrived_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(manifest.arrived_at)"></td>
                                    <td x-show="visibleColumns.received_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(manifest.received_at)"></td>
                                    <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                        <div class="inline-flex items-center gap-1">
                                            <a
                                                :href="'{{ route('admin.transport-manifests.show', ['manifest' => '__ID__']) }}'.replace('__ID__', manifest.id)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="View manifest">
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

    <div
        x-show="createManifestModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
    >
        <div
            @@click.outside="createManifestModalOpen = false"
            x-transition
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900">Create Transport Manifest</h3>
                <p class="mt-1 text-xs text-slate-500">Select a sealed transfer batch that has not yet been converted into a transport manifest.</p>
            </div>

            <div class="space-y-4 px-5 py-4">
                @if($transferBatches->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
                        <p class="text-sm font-semibold text-slate-700">No sealed transfer batches available.</p>
                        <p class="mt-1 text-xs text-slate-500">Create and seal a transfer sort batch first, then return here.</p>
                    </div>
                @else
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Transfer Batch</label>
                        <select
                            x-model="createManifestForm.sort_batch_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="">Choose sealed batch...</option>
                            @foreach($transferBatches as $batch)
                                <option value="{{ $batch->id }}">
                                    {{ $batch->batch_number ?? '#' . $batch->id }}
                                    · {{ $batch->originWarehouse?->name ?? 'Origin missing' }}
                                    → {{ $batch->destinationWarehouse?->name ?? 'Destination missing' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                        <p class="text-xs font-semibold text-blue-800">What happens next</p>
                        <p class="mt-1 text-xs text-blue-700">The manifest will be created from the selected batch, and a default loose container will be created for the batch items.</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-5 py-4">
                <button type="button" @@click="createManifestModalOpen = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">Cancel</button>
                <button
                    type="button"
                    @@click="submitCreateManifest()"
                    :disabled="actionLoading || !createManifestForm.sort_batch_id"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-show="!actionLoading">Create Manifest</span>
                    <span x-show="actionLoading">Creating...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transportManifestsTable', () => ({
        manifests: [],
        loading: false,
        actionLoading: false,
        createManifestModalOpen: false,
        createManifestForm: {
            sort_batch_id: '',
        },
        search: '',
        statusFilter: '',
        statusFilterName: '',
        originWarehouseFilter: '',
        originWarehouseFilterName: '',
        destWarehouseFilter: '',
        destWarehouseFilterName: '',
        dateFrom: '',
        dateTo: '',
        sortBy: 'created_at',
        sortDir: 'desc',
        perPage: 25,
        meta: {
            total: 0,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
        },
        columns: [
            { key: 'manifest_number', label: 'Manifest #' },
            { key: 'route',           label: 'From → To' },
            { key: 'driver',          label: 'Driver' },
            { key: 'items',           label: 'Items' },
            { key: 'status',          label: 'Status' },
            { key: 'dispatched_at',   label: 'Dispatched' },
            { key: 'arrived_at',      label: 'Arrived' },
            { key: 'received_at',     label: 'Received' },
            { key: 'actions',         label: 'Actions' },
        ],
        visibleColumns: {
            manifest_number: true,
            route:           true,
            driver:          true,
            items:           true,
            status:          true,
            dispatched_at:   true,
            arrived_at:      true,
            received_at:     true,
            actions:         true,
        },

        init() {
            this.loadData();
            this.initDateRange();
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        openCreateManifestModal() {
            this.createManifestForm = { sort_batch_id: '' };
            this.createManifestModalOpen = true;
        },

        async submitCreateManifest() {
            if (!this.createManifestForm.sort_batch_id || this.actionLoading) return;

            this.actionLoading = true;
            try {
                const response = await fetch('{{ route('admin.transport-manifests.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        sort_batch_id: Number(this.createManifestForm.sort_batch_id),
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to create transport manifest.');
                }

                window.showToast?.(result.message || 'Transport manifest created.', 'success');
                const manifestId = result.data?.manifest?.id;
                if (manifestId) {
                    window.location.href = '{{ route('admin.transport-manifests.show', ['manifest' => '__ID__']) }}'.replace('__ID__', manifestId);
                    return;
                }

                this.createManifestModalOpen = false;
                await this.loadData();
            } catch (err) {
                console.error(err);
                window.showToast?.(err.message || 'Unable to create manifest.', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        initDateRange() {
            if (!this.$refs.createdRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                const $input = window.$(this.$refs.createdRange);
                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'left',
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
                    this.dateFrom = picker.startDate.format('YYYY-MM-DD');
                    this.dateTo   = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.dateFrom} - ${this.dateTo}`);
                    this.meta.current_page = 1;
                    this.loadData();
                });
                $input.on('cancel.daterangepicker', () => {
                    this.dateFrom = '';
                    this.dateTo   = '';
                    $input.val('');
                    this.meta.current_page = 1;
                    this.loadData();
                });
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

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page:    this.meta.current_page,
                    per_page: this.perPage,
                    sort:    this.sortBy,
                    direction: this.sortDir,
                });
                if (this.search)               params.set('search', this.search);
                if (this.statusFilter)         params.set('status', this.statusFilter);
                if (this.originWarehouseFilter) params.set('origin_warehouse_id', this.originWarehouseFilter);
                if (this.destWarehouseFilter)  params.set('destination_warehouse_id', this.destWarehouseFilter);
                if (this.dateFrom)             params.set('date_from', this.dateFrom);
                if (this.dateTo)               params.set('date_to', this.dateTo);

                const response = await fetch('{{ route('admin.transport-manifests.data') }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await response.json();
                this.manifests = json.data;
                this.meta      = json.meta;
            } catch (err) {
                console.error('Failed to load transport manifests:', err);
            } finally {
                this.loading = false;
            }
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy  = column;
                this.sortDir = 'asc';
            }
            this.meta.current_page = 1;
            this.loadData();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        firstPage() {
            if (this.meta.current_page !== 1) {
                this.meta.current_page = 1;
                this.loadData();
            }
        },

        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page--;
                this.loadData();
            }
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page++;
                this.loadData();
            }
        },

        lastPage() {
            if (this.meta.current_page !== this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
                this.loadData();
            }
        },

        formatDateTime(value) {
            if (!value) return '—';
            const d = new Date(value);
            if (isNaN(d.getTime())) return '—';
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search)               params.append('search', this.search);
                if (this.statusFilter)         params.append('status', this.statusFilter);
                if (this.originWarehouseFilter) params.append('origin_warehouse_id', this.originWarehouseFilter);
                if (this.destWarehouseFilter)  params.append('destination_warehouse_id', this.destWarehouseFilter);
                if (this.dateFrom)             params.append('date_from', this.dateFrom);
                if (this.dateTo)               params.append('date_to', this.dateTo);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `{{ route('admin.transport-manifests.export') }}?${params}`;
                    return;
                }

                const response = await fetch(`{{ route('admin.transport-manifests.export') }}?${params}`, {
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
                if (this.search)               params.append('search', this.search);
                if (this.statusFilter)         params.append('status', this.statusFilter);
                if (this.originWarehouseFilter) params.append('origin_warehouse_id', this.originWarehouseFilter);
                if (this.destWarehouseFilter)  params.append('destination_warehouse_id', this.destWarehouseFilter);
                if (this.dateFrom)             params.append('date_from', this.dateFrom);
                if (this.dateTo)               params.append('date_to', this.dateTo);

                const response = await fetch(`{{ route('admin.transport-manifests.export') }}?${params}`, {
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
            doc.title = 'Transport Manifests Export';
            doc.body.innerHTML = '';
            const style = doc.createElement('style');
            style.textContent = 'body{font-family:sans-serif;padding:20px}h1{font-size:22px;margin-bottom:16px;color:#1e293b}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border:1px solid #e2e8f0;padding:7px 10px;text-align:left;font-size:11px}th{background:#f1f5f9;font-weight:600;color:#475569}tr:nth-child(even){background:#f8fafc}';
            doc.head.appendChild(style);
            const title = doc.createElement('h1');
            title.textContent = 'Transport Manifests';
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
            a.href = url; a.download = 'transport_manifests.csv';
            document.body.appendChild(a); a.click();
            document.body.removeChild(a); URL.revokeObjectURL(url);
        },
    }));
});
</script>
@endpush
