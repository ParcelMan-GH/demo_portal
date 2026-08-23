@extends('warehouse.layouts.app')

@section('title', $pageTitle ?? 'Warehouse Packages')
@section('page-title', $pageTitle ?? 'Warehouse Packages')

@php
    $isBusStationPackagesPage = (($forcedFilters['delivery_method'] ?? null) === 'bus_handoff');
    $config = [
        'endpoint' => $dataEndpoint ?? route('warehouse.packages.data'),
        'update_url' => route('warehouse.packages.update', ['warehouseReceiptItem' => '__ITEM__']),
        'print_label_url' => route('warehouse.packages.print-label', ['warehouseReceiptItem' => '__ITEM__']),
        'delay_notice_url' => $delayNoticeUrl ?? route('warehouse.packages.delay-notice', ['warehouseReceiptItem' => '__ITEM__']),
        'location_search_url' => route('warehouse.locations.search'),
        'page_title' => $pageTitle ?? 'Warehouse Packages',
        'page_subtitle' => $pageSubtitle ?? 'Every package that has passed through',
        'export_file_name' => $exportFileName ?? 'warehouse-packages',
        'forced_filters' => $forcedFilters ?? [],
        'transfer_warehouses' => $transferWarehouses ?? [],
        'open_batches' => isset($openBatches) ? $openBatches->map(fn ($batch) => [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'dispatch_mode' => $batch->dispatch_mode,
        ])->values() : [],
        'warehouse_users' => isset($warehouseUsers) ? $warehouseUsers->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
        ])->values() : [],
        'drivers' => isset($drivers) ? $drivers->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values() : [],
        'delay_reasons' => $delayReasons ?? [],
        'statuses' => \App\Enums\ItemStatus::toArray(),
        'manifest_statuses' => [
            ['value' => 'none', 'label' => 'No manifest'],
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'assigned', 'label' => 'Assigned'],
            ['value' => 'loading', 'label' => 'Loading'],
            ['value' => 'in_transit', 'label' => 'In transit'],
            ['value' => 'arrived', 'label' => 'Arrived'],
            ['value' => 'received', 'label' => 'Received'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ],
    ];
@endphp

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" 
     x-data="warehousePackagesPage()" 
     x-init="init()"
     data-config="{{ json_encode($config) }}">

    {{-- Alert Toast --}}
    <div x-show="notice.message" x-cloak x-transition class="fixed right-6 bottom-6 z-[200] flex items-center gap-3 rounded-2xl border px-5 py-3 text-sm font-semibold shadow-2xl"
         :class="notice.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path x-show="!notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="notice.message"></span>
        <button type="button" class="ml-4 opacity-60 hover:opacity-100" @click="notice.message = ''">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $pageTitle ?? 'Warehouse Packages' }}</h1>
            <p class="text-slate-500 text-sm mt-1">{{ $pageSubtitle ?? 'Every package that has passed through' }} {{ $warehouse->name ?? 'this warehouse' }}.</p>
        </div>
    </div>

    {{-- ═══════════ STATS CARDS ═══════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
        <template x-for="stat in statCards" :key="stat.key">
            <button type="button" @click="applySummaryFilter(stat.key)" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col justify-center gap-2 shadow-sm cursor-pointer hover:border-orange-300 hover:shadow-md transition-all text-left h-[100px]">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <svg x-show="stat.icon === 'package'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <svg x-show="stat.icon === 'warehouse'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M4 21V8l8-5 8 5v13M9 21v-7h6v7M7 10h2m6 0h2"/></svg>
                        <svg x-show="stat.icon === 'truck'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        <svg x-show="stat.icon === 'route'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 19a3 3 0 100-6 3 3 0 000 6zm12-8a3 3 0 100-6 3 3 0 000 6zM8.5 14.5l7-5"/></svg>
                        <svg x-show="stat.icon === 'check'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-show="stat.icon === 'cedi'" class="text-sm font-black leading-none">₵</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide truncate" x-text="stat.label"></span>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-900" x-text="stat.money ? formatMoney(summary[stat.key] ?? 0) : (summary[stat.key] ?? 0)"></span>
                </div>
            </button>
        </template>
    </div>

    {{-- ═══════════ MAIN TABLE CONTAINER ═══════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        
        {{-- Search & Action Bar --}}
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search packages..."
                       class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-all">
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <button type="button" @click="filtersOpen = !filtersOpen"
                        class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5"
                        :class="filtersOpen ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                    <svg class="w-4 h-4 text-slate-500" :class="filtersOpen ? 'text-orange-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    Filters
                    <span x-show="activeFilterCount() > 0" class="ml-1 rounded-full bg-orange-200 px-1.5 py-0.5 text-[10px] text-orange-800" x-text="activeFilterCount()"></span>
                </button>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                        View
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-48 bg-white border border-slate-200 rounded-xl shadow-lg p-2" style="display:none">
                        <template x-for="col in columns" :key="col.key">
                            <button type="button" @click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <span x-text="col.label"></span>
                                <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 002 3h12a3 3 0 002-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-lg p-1" style="display:none">
                        <button type="button" @click="exportData('csv'); open = false" class="w-full text-left px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 rounded-lg">CSV</button>
                    </div>
                </div>

                <button type="button" @click="loadData(meta.current_page)" class="p-2.5 border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 rounded-xl shadow-sm transition-all" title="Refresh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        {{-- Expanded Filter Panel --}}
        <div x-show="filtersOpen" x-transition class="p-4 bg-slate-50/80 border-b border-slate-100" style="display:none">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select x-model="filters.status" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Region</label>
                    <select x-model="filters.region_id" @change="onFilterRegionChange()" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All regions</option>
                        <template x-for="region in regions" :key="region.id">
                            <option :value="region.id" x-text="region.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">District</label>
                    <select x-model="filters.district_id" :disabled="!filters.region_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500 disabled:opacity-50">
                        <option value="">All districts</option>
                        <template x-for="district in filterDistricts" :key="district.id">
                            <option :value="district.id" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Capacity (m³)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" x-model="filters.capacity_min" placeholder="Min" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <span class="text-slate-400">-</span>
                        <input type="number" min="0" x-model="filters.capacity_max" placeholder="Max" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" @click="clearFilters()" class="px-4 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold bg-white border border-slate-200 rounded-xl shadow-sm transition-colors">Clear All</button>
                <button type="button" @click="applyFilters()" class="px-5 py-2 bg-orange-600 text-white rounded-xl text-xs font-semibold shadow-sm hover:bg-orange-700 transition-colors">Apply Filters</button>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto relative">
            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>

            <table class="w-full text-left border-collapse min-w-[1500px]">
                <thead class="bg-[#FFF8F3] border-y border-[#F6E8E1]">
                    <tr>
                        <th x-show="visibleColumns.package" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Package</th>
                        <th x-show="visibleColumns.qty" class="px-5 py-3.5 text-xs font-semibold text-slate-700 text-center">Qty</th>
                        <th x-show="visibleColumns.shipment" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Shipment</th>
                        <th x-show="visibleColumns.recipient" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Recipient</th>
                        <th x-show="visibleColumns.destination" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Destination</th>
                        <th x-show="visibleColumns.stage" class="px-5 py-3.5 text-xs font-semibold text-slate-700 text-center">Stage</th>
                        {{-- Custody Column Removed --}}
                        <th x-show="visibleColumns.sort_batch" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Sort Batch</th>
                        <th x-show="visibleColumns.manifest" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Manifest</th>
                        <th x-show="visibleColumns.delivery" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Delivery</th>
                        <th x-show="visibleColumns.eta" class="px-5 py-3.5 text-xs font-semibold text-slate-700">ETA</th>
                        <th x-show="visibleColumns.payment" class="px-5 py-3.5 text-xs font-semibold text-slate-700">Payment</th>
                        <th x-show="visibleColumns.received" @click="sort('received_at')" class="cursor-pointer px-5 py-3.5 text-xs font-semibold text-slate-700">Received <span x-show="sortBy==='received_at'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.actions" class="px-5 py-3.5 text-xs font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-800 bg-white">
                    <template x-if="!loading && rows.length === 0">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-6 py-16 text-center">
                                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 mb-4">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 mb-1">No packages found</h3>
                                <p class="text-xs text-slate-500">Try adjusting your filters or search query.</p>
                            </td>
                        </tr>
                    </template>

                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-orange-50/30 transition-colors duration-200 ease-in-out group align-middle">
                            <td x-show="visibleColumns.package" class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100/50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-extrabold text-slate-900" x-text="row.item_description || 'Unknown Package'"></p>
                                        <p class="mt-0.5 truncate font-mono text-[11px] font-medium text-slate-500" x-text="row.tracking_code || row.barcode_value || '-'"></p>
                                    </div>
                                </div>
                            </td>
                            <td x-show="visibleColumns.qty" class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="row.received_quantity || row.quantity || 0"></span>
                            </td>
                            <td x-show="visibleColumns.shipment" class="px-5 py-4 whitespace-nowrap">
                                <a :href="row.shipment_url" class="font-bold text-blue-600 hover:text-blue-800 transition-colors" x-text="row.shipment_number || '-'"></a>
                                <p class="text-[10px] text-slate-500 mt-1 truncate max-w-[120px]" x-text="row.vendor_name || ''"></p>
                            </td>
                            <td x-show="visibleColumns.recipient" class="px-5 py-4 whitespace-nowrap">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900" x-text="row.recipient_name || '-'"></p>
                                    <p class="mt-0.5 truncate text-[11px] font-medium text-slate-500" x-text="row.recipient_phone || '-'"></p>
                                </div>
                            </td>
                            <td x-show="visibleColumns.destination" class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="truncate text-xs font-semibold" x-text="row.destination || '-'"></span>
                                </div>
                            </td>
                            <td x-show="visibleColumns.stage" class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider border" :class="stageClass(row.current_stage?.tone)" x-text="row.current_stage?.label || '-'"></span>
                                {{-- Custody information merged into Stage --}}
                                <p x-show="row.custody?.holder && row.custody?.holder.toLowerCase() !== 'warehouse'" class="text-[10px] text-slate-500 mt-1.5 font-medium truncate max-w-[120px]" x-text="'With: ' + row.custody.holder"></p>
                            </td>
                            {{-- Custody td Removed --}}
                            <td x-show="visibleColumns.sort_batch" class="px-5 py-4 whitespace-nowrap">
                                <div x-show="row.sort_batch_url" class="flex flex-col items-start gap-1">
                                    <a :href="row.sort_batch_url" class="font-semibold text-violet-600 hover:text-violet-800 transition-colors" x-text="row.sort_batch?.number"></a>
                                    <span x-show="row.sort_batch?.status" class="inline-flex rounded-md bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-violet-700 border border-violet-200" x-text="row.sort_batch?.status"></span>
                                </div>
                                <span x-show="!row.sort_batch_url" class="text-slate-400">-</span>
                            </td>
                            <td x-show="visibleColumns.manifest" class="px-5 py-4 whitespace-nowrap">
                                <div x-show="row.manifest_url" class="flex flex-col items-start gap-1">
                                    <a :href="row.manifest_url" class="font-semibold text-blue-600 hover:text-blue-800 transition-colors" x-text="row.transport_manifest?.number"></a>
                                    <span x-show="row.transport_manifest?.status" class="inline-flex rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700 border border-blue-200" x-text="row.transport_manifest?.status"></span>
                                </div>
                                <span x-show="!row.manifest_url" class="text-slate-400">-</span>
                            </td>
                            <td x-show="visibleColumns.delivery" class="px-5 py-4 whitespace-nowrap">
                                <div x-show="row.delivery_run_url" class="flex flex-col items-start gap-1">
                                    <a :href="row.delivery_run_url" class="font-semibold text-emerald-600 hover:text-emerald-800 transition-colors" x-text="row.delivery_run?.number"></a>
                                    <span x-show="row.delivery_run?.status || row.delivery_run?.stop_status" class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 border border-emerald-200" x-text="row.delivery_run?.status || row.delivery_run?.stop_status"></span>
                                </div>
                                <span x-show="!row.delivery_run_url" class="text-slate-400">-</span>
                            </td>
                            <td x-show="visibleColumns.eta" class="px-5 py-4 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border" :class="delayClass(row.eta?.tone)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="row.eta?.label || '-'"></span>
                                    </span>
                                    <span x-show="row.eta?.expected_delivery_at" class="text-[10px] text-slate-500" x-text="row.eta?.expected_delivery_at"></span>
                                </div>
                            </td>
                            <td x-show="visibleColumns.payment" class="px-5 py-4 whitespace-nowrap">
                                <template x-if="row.payment?.status === 'no_fee'">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500 border border-slate-200">No fee</span>
                                </template>
                                <template x-if="row.payment?.status !== 'no_fee'">
                                    <div>
                                        <p class="font-bold text-slate-900" x-text="paymentAmount(row.payment)"></p>
                                        <span class="inline-flex mt-1 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border" :class="paymentClass(row.payment?.status_label)" x-text="row.payment?.status_label || ''"></span>
                                    </div>
                                </template>
                            </td>
                            <td x-show="visibleColumns.received" class="px-5 py-4 text-slate-600 whitespace-nowrap">
                                <p class="text-xs font-semibold" x-text="formatDisplayDate(row.received_at)"></p>
                                <p class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[120px]" x-text="row.received_by || row.pickup_driver || ''"></p>
                            </td>
                            
                            {{-- 3-Dot Action Menu --}}
                            <td x-show="visibleColumns.actions" class="px-5 py-4 text-right whitespace-nowrap">
                                <div x-data="{ menuOpen: false }" class="relative inline-block text-left" @click.away="menuOpen = false">
                                    <button @click="menuOpen = !menuOpen" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 10a2 2 0 110 4 2 2 0 010-4zm0-6a2 2 0 110 4 2 2 0 010-4zm0 12a2 2 0 110 4 2 2 0 010-4z"/></svg>
                                    </button>
                                    <div x-show="menuOpen" x-transition class="absolute right-0 z-50 mt-1 w-40 bg-white rounded-xl shadow-lg border border-slate-100 py-1 overflow-hidden" style="display:none">
                                        <a :href="row.view_url" class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Details
                                        </a>
                                        <button type="button" @click="openEditModal(row); menuOpen = false" class="flex w-full items-center gap-2 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit Package
                                        </button>
                                        <button type="button" @click="openPrintModal(row); menuOpen = false" class="flex w-full items-center gap-2 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Print Labels
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div class="px-6 py-4 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 font-medium gap-4">
            <div>
                Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> packages
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span>Rows</span>
                    <select x-model.number="perPage" @change="meta.current_page = 1; loadData()" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-bold text-slate-700 outline-none focus:ring-1 focus:ring-orange-500">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">Prev</button>
                    <span class="mx-2">Page <span class="font-bold text-slate-700" x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></span>
                    <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">Next</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODALS ═══════════ --}}
    
    {{-- Edit Modal --}}
    <template x-teleport="body">
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity @click.self="closeEditModal()" @keydown.escape.window="closeEditModal()">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl relative overflow-hidden" @click.stop>
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8.5l5-3 5 3-5 3-5-3zM4 13l5 3 5-3M10 16l5 3 5-3M4 13l5-3 5 3-5 3-5-3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Edit Package</h3>
                            <p class="text-sm text-slate-500 mt-1" x-text="activeRow?.tracking_code || activeRow?.shipment_number || 'Update package details'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeEditModal()" class="p-2 text-slate-400 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto space-y-5">
                    
                    {{-- Photos --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Photos</label>
                        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-700" x-text="photoUploadFiles.length ? photoUploadFiles.length + ' new photo(s) selected' : (rowPhotoList().length ? 'Photos available' : 'Upload package photos')"></span>
                                <span x-show="!hasRequiredPackagePhotos()" class="text-xs font-bold text-rose-600 mt-0.5">At least one photo required.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" x-show="rowPhotoList().length" @click="openPackagePhotos()" class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">View</button>
                                <label class="cursor-pointer px-3 py-1.5 text-xs font-bold text-orange-700 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
                                    Choose
                                    <input type="file" accept="image/png,image/jpeg,image/webp" multiple class="hidden" @change="handlePackagePhotos($event)">
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-5">
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Description <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editForm.description" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Qty <span class="text-rose-500">*</span></label>
                            <input type="number" min="1" x-model.number="editForm.quantity" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500">
                        </div>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-4">
                        <h4 class="text-sm font-bold text-slate-900">Recipient Details</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Name</label>
                                <input type="text" x-model="editForm.delivery_recipient_name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Phone</label>
                                <input type="text" x-model="editForm.delivery_recipient_phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-500">
                            </div>
                            <div class="col-span-2 relative" @click.outside="editForm._showDropdown = false">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Location</label>
                                <input type="text" x-model="editForm.locationQuery" @input="searchLocation(editForm)" @focus="editForm.locationResults?.length && (editForm._showDropdown = true)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-500">
                                <div x-show="editForm._showDropdown && editForm.locationResults?.length" class="absolute z-40 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                                    <template x-for="loc in editForm.locationResults" :key="loc.id">
                                        <button type="button" @click="selectLocation(editForm, loc)" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-orange-50">
                                            <span x-text="loc.display"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Instructions</label>
                                <textarea x-model="editForm.delivery_instructions" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-500 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Forward / Bus settings (Only if available) --}}
                    <div x-show="activeRow?.can_edit_bus_handoff" class="flex items-center justify-between p-4 border border-slate-200 rounded-xl bg-white">
                        <div>
                            <p class="text-sm font-bold text-slate-900">Send to Bus Station</p>
                            <p class="text-xs text-slate-500">Mark delivery method as bus handoff.</p>
                        </div>
                        <input type="checkbox" :checked="editForm.delivery_method === 'bus_handoff'" @change="editForm.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'" class="w-5 h-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    </div>

                    <div x-show="activeRow?.can_forward_to_warehouse" class="p-4 border border-orange-100 bg-orange-50/50 rounded-xl">
                        <label class="block text-xs font-bold text-orange-700 mb-2">Forward to another warehouse</label>
                        <select x-model="editForm.forward_to_warehouse_id" class="w-full rounded-xl border border-orange-200 px-3 py-2 text-sm outline-none focus:border-orange-500">
                            <option value="">Keep at this warehouse</option>
                            <template x-for="wh in config.transfer_warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.name"></option>
                            </template>
                        </select>
                    </div>

                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 rounded-b-3xl">
                    <button type="button" @click="closeEditModal()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Cancel</button>
                    <button type="button" @click="savePackage()" :disabled="modalLoading || !canSaveEditPackage()" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm disabled:opacity-50">
                        <span x-text="modalLoading ? 'Saving...' : 'Save Package'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Print Modal --}}
    <template x-teleport="body">
        <div x-show="printModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity @click.self="closePrintModal()">
            <div class="bg-white rounded-3xl max-w-sm w-full shadow-2xl p-6" @click.stop>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-900">Print Labels</h3>
                    <button type="button" @click="closePrintModal()" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-6">
                    <p class="text-xs text-slate-500 font-semibold mb-1">Package</p>
                    <p class="text-sm font-bold text-slate-900 mb-2" x-text="activeRow?.item_description || '-'"></p>
                    <div class="flex items-center gap-4 text-sm text-slate-600">
                        <span>Qty: <strong x-text="activeRow?.quantity || 1"></strong></span>
                        <span>Labels: <strong x-text="activeRow?.label_count || 0"></strong></span>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold text-slate-600 mb-2 text-center">Number of labels to print</label>
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" @click="setPrintLabelCount((printForm.label_count || 1) - 1)" :disabled="modalLoading || printForm.label_count <= 1" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 disabled:opacity-50">-</button>
                        <input type="number" min="1" max="500" x-model.number="printForm.label_count" class="w-20 h-10 text-center rounded-xl border border-slate-200 font-bold text-slate-900 outline-none focus:border-orange-500">
                        <button type="button" @click="setPrintLabelCount((printForm.label_count || 1) + 1)" :disabled="modalLoading || printForm.label_count >= 500" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 disabled:opacity-50">+</button>
                    </div>
                </div>

                <button type="button" @click="printLabel()" :disabled="modalLoading || !printForm.label_count" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-sm transition-colors disabled:opacity-50">
                    <span x-text="modalLoading ? 'Printing...' : 'Print Labels'"></span>
                </button>
            </div>
        </div>
    </template>

    {{-- Delay Notice Modal --}}
    <template x-teleport="body">
        <div x-show="delayModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity @click.self="closeDelayModal()">
            <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl" @click.stop>
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Send Delay Notice</h3>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="activeRow?.tracking_code || 'Update delivery ETA'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeDelayModal()" class="p-2 text-slate-400 hover:text-slate-700 bg-slate-50 rounded-full transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Delay Reason <span class="text-rose-500">*</span></label>
                        <select x-model="delayForm.reason_id" @change="updateDelayMessage()" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-500">
                            <option value="">Select reason</option>
                            <template x-for="r in config.delay_reasons" :key="r.id">
                                <option :value="r.id" x-text="r.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Revised ETA <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <input type="text" x-ref="delayEtaInput" placeholder="Select new date and time" readonly class="w-full bg-white cursor-pointer rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-500">
                    </div>

                    <div class="flex gap-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" x-model="delayForm.notify_recipient" class="rounded text-orange-600 focus:ring-orange-500 border-slate-300">
                            Recipient SMS
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" x-model="delayForm.notify_vendor" class="rounded text-orange-600 focus:ring-orange-500 border-slate-300">
                            Vendor App
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Notice Message</label>
                        <textarea x-model="delayForm.message" @input="delayForm.message_touched = true" rows="3" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 outline-none focus:border-amber-400"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3 rounded-b-3xl">
                    <button type="button" @click="closeDelayModal()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl">Cancel</button>
                    <button type="button" @click="sendDelayNotice()" :disabled="modalLoading || !delayForm.reason_id" class="px-5 py-2.5 bg-orange-600 text-white text-sm font-bold rounded-xl disabled:opacity-50">
                        <span x-text="modalLoading ? 'Sending...' : 'Send Notice'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Photo Lightbox Modal --}}
    <template x-teleport="body">
        <div x-show="photoPreviewOpen" x-cloak x-transition.opacity @click="closePackagePhotos()" @keydown.window.escape="closePackagePhotos()" @keydown.window.arrow-left="previousPackagePhoto()" @keydown.window.arrow-right="nextPackagePhoto()"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4">
            
            <button type="button" @click.stop="closePackagePhotos()" class="absolute right-6 top-6 z-20 rounded-full bg-white/10 p-3 text-white transition hover:bg-white/20">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <button type="button" x-show="canRemoveActivePhoto()" @click.stop="removeActivePhoto()" class="absolute left-6 top-6 z-20 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-rose-700 shadow-md">
                Remove photo
            </button>

            <button type="button" x-show="photoPreviewUrls.length > 1" @click.stop="previousPackagePhoto()" class="absolute left-6 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white transition hover:bg-white/20">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <template x-if="activePackagePhoto()">
                <img @click.stop :src="activePackagePhoto().url" class="max-h-[90dvh] max-w-[90vw] rounded-lg object-contain shadow-2xl ring-1 ring-white/20">
            </template>

            <button type="button" x-show="photoPreviewUrls.length > 1" @click.stop="nextPackagePhoto()" class="absolute right-6 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white transition hover:bg-white/20">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <div class="absolute bottom-6 left-1/2 z-20 -translate-x-1/2 rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur-md">
                <span x-text="activePackagePhoto()?.source || 'Package photo'"></span>
                <span x-show="photoPreviewUrls.length > 1" class="ml-2 pl-2 border-l border-white/20" x-text="`${activePhotoIndex + 1} / ${photoPreviewUrls.length}`"></span>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function warehousePackagesPage() {
    return {
        config: {},
        rows: [],
        meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
        loading: false,
        modalLoading: false,
        search: '',
        perPage: 25,
        sortBy: 'received_at',
        sortDirection: 'desc',
        
        statCards: [
            { key: 'total', label: 'Total Packages', icon: 'package' },
            { key: 'pending', label: 'Pending', icon: 'warehouse' },
            { key: 'in_transit', label: 'In Transit', icon: 'truck' },
            { key: 'delivered', label: 'Delivered', icon: 'check' },
            { key: 'failed', label: 'Failed', icon: 'route' },
            { key: 'expected_cash', label: 'Expected Value', icon: 'cedi', money: true },
            { key: 'collected_cash', label: 'Collected', icon: 'cedi', money: true },
        ],
        summary: {},
        filtersOpen: false,
        
        // Dynamic Filters
        filters: { status: '', region_id: '', district_id: '', capacity_min: '', capacity_max: '', assigned_date: '', received_date: '' },
        
        columns: [
            { key: 'package', label: 'Package' },
            { key: 'qty', label: 'Qty' },
            { key: 'shipment', label: 'Shipment' },
            { key: 'recipient', label: 'Recipient' },
            { key: 'destination', label: 'Destination' },
            { key: 'stage', label: 'Stage' },
            { key: 'sort_batch', label: 'Sort Batch' },
            { key: 'manifest', label: 'Manifest' },
            { key: 'delivery', label: 'Delivery' },
            { key: 'eta', label: 'ETA' },
            { key: 'payment', label: 'Payment' },
            { key: 'received', label: 'Received' },
            { key: 'actions', label: 'Actions' }
        ],
        // Custody removed from active columns
        visibleColumns: {
            package: true, qty: true, shipment: true, recipient: true, destination: true, 
            stage: true, sort_batch: false, manifest: false, delivery: true, 
            eta: false, payment: true, received: true, actions: true
        },

        // Modals Data
        editModalOpen: false,
        printModalOpen: false,
        delayModalOpen: false,
        photoPreviewOpen: false,
        activeRow: null,

        editForm: { description: '', quantity: 1, delivery_recipient_name: '', delivery_recipient_phone: '', delivery_town: '', delivery_landmark: '', delivery_instructions: '', delivery_method: 'direct', forward_to_warehouse_id: '', locationQuery: '', locationResults: [], _showDropdown: false },
        printForm: { label_count: 1 },
        delayForm: { reason_id: '', revised_eta: '', notify_recipient: true, notify_vendor: false, notify_vendor_sms: false, notes: '', message: '', message_touched: false },
        
        photoUploadFiles: [],
        removePhotoIds: [],
        photoPreviewPackage: null,
        photoPreviewUrls: [],
        activePhotoIndex: 0,

        notice: { success: true, message: '' },
        _searchTimeout: null,
        _locationSearchTimer: null,

        init() {
            const el = this.$root;
            this.config = JSON.parse(el.dataset.config || '{}');
            this.loadData();
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(v => v).length;
        },

        async loadData(page = this.meta.current_page) {
            this.loading = true;
            const params = new URLSearchParams({ page, per_page: this.perPage, sort: this.sortBy, direction: this.sortDirection });
            if (this.search) params.set('search', this.search);
            
            // Append filters safely
            for (const [key, value] of Object.entries(this.filters)) {
                if (value !== '' && value !== null) params.set(key, value);
            }

            try {
                const res = await fetch(`${this.config.endpoint}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || this.meta;
                this.summary = json.summary || {};
            } catch (e) {
                this.toast(false, 'Failed to load packages.');
            } finally {
                this.loading = false;
            }
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.loadData(1);
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.loadData(1), 300);
        },

        toggleColumn(key) { this.visibleColumns[key] = !this.visibleColumns[key]; },
        previousPage() { if(this.meta.current_page > 1) this.loadData(this.meta.current_page - 1); },
        nextPage() { if(this.meta.current_page < this.meta.last_page) this.loadData(this.meta.current_page + 1); },

        applySummaryFilter(key) {
            this.filters.status = key;
            this.loadData(1);
        },

        applyFilters() { this.filtersOpen = false; this.loadData(1); },
        clearFilters() {
            for (let key in this.filters) this.filters[key] = '';
            if(this.$refs.dateRange) this.$refs.dateRange.value = '';
            this.filtersOpen = false;
            this.loadData(1);
        },

        activeFilterCount() {
            let count = 0;
            for (let key in this.filters) { if(this.filters[key]) count++; }
            return count;
        },

        // Helper Classes & Formats
        formatMoney(value) { return Number(value || 0).toFixed(2); },
        
        formatDisplayDate(val) {
            if (!val) return '-';
            const date = new Date(String(val).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return val;
            return date.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
        },

        stageClass(tone) {
            if (tone === 'emerald') return 'bg-emerald-50 border-emerald-200 text-emerald-700';
            if (tone === 'blue') return 'bg-blue-50 border-blue-200 text-blue-700';
            if (tone === 'amber') return 'bg-amber-50 border-amber-200 text-amber-700';
            if (tone === 'rose') return 'bg-rose-50 border-rose-200 text-rose-700';
            return 'bg-slate-100 border-slate-200 text-slate-700';
        },

        delayClass(tone) {
            if (tone === 'rose') return 'bg-rose-50 border-rose-200 text-rose-700';
            if (tone === 'amber') return 'bg-amber-50 border-amber-200 text-amber-700';
            if (tone === 'emerald') return 'bg-emerald-50 border-emerald-200 text-emerald-700';
            return 'bg-slate-100 border-slate-200 text-slate-600';
        },

        paymentAmount(payment) { return payment?.amount ? `GHS ${this.formatMoney(payment.amount)}` : '-'; },
        
        paymentClass(label) {
            label = String(label || '').toLowerCase();
            if (label.includes('paid')) return 'bg-emerald-50 border-emerald-200 text-emerald-700';
            if (label.includes('waive')) return 'bg-slate-100 border-slate-200 text-slate-600';
            return 'bg-amber-50 border-amber-200 text-amber-700';
        },

        toast(success, message) {
            this.notice = { success, message };
            if (success) setTimeout(() => { this.notice.message = ''; }, 4000);
        },

        // Modals
        openEditModal(row) {
            this.activeRow = row;
            this.editForm = {
                description: row.item_description || '', quantity: row.quantity || row.received_quantity || 1,
                delivery_recipient_name: row.recipient_name || '', delivery_recipient_phone: row.recipient_phone || '',
                delivery_town: row.destination || '', delivery_landmark: '', delivery_instructions: '',
                delivery_method: row.delivery_method || 'direct', forward_to_warehouse_id: '',
                locationQuery: row.destination || '', locationResults: [], _showDropdown: false
            };
            this.photoUploadFiles = [];
            this.removePhotoIds = [];
            this.editModalOpen = true;
        },

        closeEditModal() { this.editModalOpen = false; this.activeRow = null; },

        canSaveEditPackage() { return this.editForm.description && this.editForm.quantity > 0 && this.hasRequiredPackagePhotos(); },

        hasRequiredPackagePhotos() {
            const existingCount = this.rowPhotoList().length - this.removePhotoIds.length;
            return (existingCount + this.photoUploadFiles.length) > 0;
        },

        async savePackage() {
            this.modalLoading = true;
            const url = this.config.update_url.replace('__ITEM__', this.activeRow.id);
            const fd = new FormData();
            fd.append('_method', 'PUT');
            for(let key in this.editForm) {
                if(!key.startsWith('_') && key !== 'locationQuery' && key !== 'locationResults') fd.append(key, this.editForm[key]);
            }
            this.photoUploadFiles.forEach(file => fd.append('photos[]', file));
            this.removePhotoIds.forEach(id => fd.append('remove_photo_ids[]', id));

            try {
                const res = await fetch(url, {
                    method: 'POST', // using POST with _method=PUT for FormData
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: fd
                });
                const json = await res.json();
                this.toast(res.ok && json.success, json.message || (res.ok ? 'Saved.' : 'Failed to save.'));
                if (res.ok && json.success) { this.closeEditModal(); this.loadData(); }
            } catch (e) { this.toast(false, 'Network error.'); }
            finally { this.modalLoading = false; }
        },

        // Locations
        async searchLocation(form) {
            clearTimeout(this._locationSearchTimer);
            if(form.locationQuery.length < 2) { form.locationResults = []; form._showDropdown = false; return; }
            this._locationSearchTimer = setTimeout(async () => {
                const res = await fetch(`${this.config.location_search_url}?q=${encodeURIComponent(form.locationQuery)}`, { headers: { 'Accept': 'application/json' }});
                const json = await res.json();
                form.locationResults = json.locations || [];
                form._showDropdown = form.locationResults.length > 0;
            }, 300);
        },
        selectLocation(form, loc) {
            form.delivery_town = loc.display;
            form.locationQuery = loc.display;
            form._showDropdown = false;
        },

        // Photos
        handlePackagePhotos(e) {
            this.photoUploadFiles = Array.from(e.target.files);
        },
        rowPhotoList() { return this.activeRow?.photos?.items || []; },
        openPackagePhotos() {
            this.photoPreviewPackage = this.activeRow;
            this.photoPreviewUrls = this.rowPhotoList();
            this.activePhotoIndex = 0;
            this.photoPreviewOpen = true;
        },
        closePackagePhotos() { this.photoPreviewOpen = false; this.photoPreviewPackage = null; },
        activePackagePhoto() { return this.photoPreviewUrls[this.activePhotoIndex] || null; },
        nextPackagePhoto() { if(this.photoPreviewUrls.length > 1) this.activePhotoIndex = (this.activePhotoIndex + 1) % this.photoPreviewUrls.length; },
        previousPackagePhoto() { if(this.photoPreviewUrls.length > 1) this.activePhotoIndex = (this.activePhotoIndex - 1 + this.photoPreviewUrls.length) % this.photoPreviewUrls.length; },
        canRemoveActivePhoto() { return this.editModalOpen && this.activePackagePhoto() && !this.removePhotoIds.includes(this.activePackagePhoto().id); },
        removeActivePhoto() {
            const photo = this.activePackagePhoto();
            if(photo) { this.removePhotoIds.push(photo.id); this.closePackagePhotos(); }
        },

        // Print
        openPrintModal(row) {
            this.activeRow = row;
            this.printForm.label_count = row.label_count || 1;
            this.printModalOpen = true;
        },
        closePrintModal() { this.printModalOpen = false; this.activeRow = null; },
        setPrintLabelCount(val) { this.printForm.label_count = Math.max(1, Math.min(500, parseInt(val) || 1)); },
        async printLabel() {
            this.modalLoading = true;
            try {
                const url = this.config.print_label_url.replace('__ITEM__', this.activeRow.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ label_count: this.printForm.label_count })
                });
                const json = await res.json();
                this.toast(res.ok && json.success, json.message || 'Labels printed.');
                if (res.ok && json.success) this.closePrintModal();
            } finally { this.modalLoading = false; }
        },

        // Delay Notice
        openDelayModal(row) {
            this.activeRow = row;
            this.delayForm = { reason_id: '', revised_eta: '', notify_recipient: true, notify_vendor: false, notify_vendor_sms: false, notes: '', message: '', message_touched: false };
            this.delayModalOpen = true;
        },
        closeDelayModal() { this.delayModalOpen = false; this.activeRow = null; },
        updateDelayMessage() {
            if (this.delayForm.message_touched) return;
            const reason = this.config.delay_reasons.find(r => r.id == this.delayForm.reason_id);
            this.delayForm.message = reason ? `Your package delay reason is: ${reason.label}` : '';
        },
        async sendDelayNotice() {
            this.modalLoading = true;
            try {
                const url = this.config.delay_notice_url.replace('__ITEM__', this.activeRow.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(this.delayForm)
                });
                const json = await res.json();
                this.toast(res.ok && json.success, json.message || 'Notice sent.');
                if (res.ok && json.success) { this.closeDelayModal(); this.loadData(); }
            } finally { this.modalLoading = false; }
        },

        exportData(format) {
            const params = new URLSearchParams({ format, sort: this.sortBy, direction: this.sortDirection });
            for (let key in this.filters) { if(this.filters[key]) params.set(key, this.filters[key]); }
            window.location.href = `${this.config.endpoint.replace('/data', '/export')}?${params.toString()}`;
        }
    };
}
</script>
@endpush