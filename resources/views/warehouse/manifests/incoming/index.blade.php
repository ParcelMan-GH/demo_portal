@extends($layoutName ?? 'warehouse.layouts.app')

@section('title', $pageTitle ?? 'Incoming Transfers')
@section('page-title', $pageTitle ?? 'Incoming Transfers')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', $pageTitle ?? 'Incoming Transfers')

@php
    $config = [
        'data_endpoint' => $dataEndpoint ?? route('warehouse.manifests.incoming.data'),
        'scan_endpoint' => route('warehouse.manifests.incoming.scan'),
        'index_url' => route('warehouse.manifests.incoming.index'),
        'origin_warehouses' => ($originWarehouses ?? collect())->values(),
        'transport_drivers' => ($transportDrivers ?? collect())->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values(),
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="warehouseIncomingManifestsPage" data-warehouse-incoming-manifests-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-6">
        <template x-for="stat in statCards" :key="stat.key">
            <button type="button" @@click="applySummaryFilter(stat.key)" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1" :class="stat.iconClass">
                    <svg x-show="stat.icon === 'truck'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                    <svg x-show="stat.icon === 'check'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg x-show="stat.icon === 'box'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <svg x-show="stat.icon === 'road'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 20l3-16m3 16l-3-16M4 20h16M6 12h12"/></svg>
                    <svg x-show="stat.icon === 'pin'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 21s7-4.438 7-11a7 7 0 10-14 0c0 6.562 7 11 7 11z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 10.5h.01"/></svg>
                    <svg x-show="stat.icon === 'alert'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="max-w-full truncate text-[9px] font-black uppercase leading-snug tracking-wide text-slate-400" x-text="stat.label"></p>
                    <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="summary[stat.key] ?? 0"></p>
                </div>
            </button>
        </template>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3">
                <div class="flex min-w-0 items-center gap-3 overflow-hidden">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1 overflow-hidden">
                        <h2 class="truncate whitespace-nowrap text-lg font-extrabold text-slate-900">Incoming Transfers</h2>
                    </div>
                </div>
                <button type="button" @@click="openScanModal()" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                    </svg>
                    Scan
                </button>
                <p class="col-span-2 text-sm leading-snug text-slate-500">Receive manifests arriving at {{ $warehouse->name ?? 'this warehouse' }} from other warehouses.</p>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search manifest, origin, driver..."
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
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="filters.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Origin</label>
                        <select x-model="filters.origin_warehouse_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All origins</option>
                            <template x-for="warehouse in originWarehouses" :key="warehouse.id"><option :value="warehouse.id" x-text="warehouse.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Driver</label>
                        <select x-model="filters.driver_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All drivers</option>
                            <template x-for="driver in transportDrivers" :key="driver.id"><option :value="driver.id" x-text="driver.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Type</label>
                        <select x-model="filters.date_type" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="created_at">Created At</option>
                            <option value="assigned_at">Assigned At</option>
                            <option value="dispatched_at">Dispatched At</option>
                            <option value="arrived_at">Arrived At</option>
                            <option value="received_at">Received At</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Range</label>
                        <input type="text" x-ref="dateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap gap-2" x-show="activeFilterChips().length">
                <template x-for="chip in activeFilterChips()" :key="chip.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        <span x-text="chip.label"></span>
                        <button type="button" @@click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <template x-for="column in columns" :key="column.key">
                                <th x-show="visibleColumns[column.key]" @@click="sort(column.key)" class="px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="[isSortable(column.key) ? 'cursor-pointer' : '', tableHeaderClass(column.key)]">
                                    <div class="flex items-center gap-1" :class="tableHeaderContentClass(column.key)">
                                        <span x-text="column.label"></span>
                                        <svg x-show="isSortable(column.key)" class="h-2.5 w-2.5" :class="isSortedColumn(column.key) ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No incoming transfers match the current filters</p>
                                        <button type="button" @@click="clearFilters()" class="text-xs font-semibold text-orange-600 hover:underline">Clear filters</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.manifest_number" class="w-[24%] max-w-[320px] whitespace-nowrap px-4 py-3">
                                    <a :href="row.view_url" class="font-bold text-slate-900 hover:text-orange-700 hover:underline" x-text="row.manifest_number || '-'"></a>
                                </td>
                                <td x-show="visibleColumns.origin_warehouse" class="w-[22%] px-4 py-3">
                                    <p class="font-semibold text-slate-800" x-text="row.origin_warehouse || '-'"></p>
                                </td>
                                <td x-show="visibleColumns.destination_warehouse" class="px-4 py-3 font-semibold text-slate-700" x-text="row.destination_warehouse || '-'"></td>
                                <td x-show="visibleColumns.status" class="w-[12%] whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusBadgeClass(row.status, row.status_label)" x-text="row.status_label || statusLabel(row.status)"></span>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="w-[16%] whitespace-nowrap px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="row.driver_name || '-'"></p>
                                    <p class="text-[11px] text-slate-500" x-text="row.driver_phone || ''"></p>
                                </td>
                                <td x-show="visibleColumns.items_count" class="w-[7%] whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="row.items_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.received_count" class="w-[9%] whitespace-nowrap px-3 py-3 text-center">
                                    <span class="font-bold text-slate-900" x-text="row.received_display || '0 / 0'"></span>
                                </td>
                                <td x-show="visibleColumns.arrived_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.arrived_at)"></td>
                                <td x-show="visibleColumns.received_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.received_at)"></td>
                                <td x-show="visibleColumns.dispatched_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.dispatched_at)"></td>
                                <td x-show="visibleColumns.assigned_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.assigned_at)"></td>
                                <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.created_at)"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                    <a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="!loading && rows.length === 0">
                    <div class="px-4 py-12 text-center text-sm text-slate-400">No incoming transfers match the current filters.</div>
                </template>
                <template x-for="row in rows" :key="row.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="row.view_url" class="truncate text-sm font-extrabold text-slate-900 hover:text-orange-700" x-text="row.manifest_number || '-'"></a>
                                <p class="mt-1 text-xs font-semibold text-slate-500" x-text="row.origin_warehouse || '-'"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="statusBadgeClass(row.status, row.status_label)" x-text="row.status_label || statusLabel(row.status)"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Received</p><p class="font-bold text-slate-800" x-text="row.received_display || '0 / 0'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Driver</p><p class="font-bold text-slate-800" x-text="row.driver_name || '-'"></p><p class="text-slate-500" x-text="row.driver_phone || ''"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Arrived</p><p class="font-bold text-slate-800" x-text="formatDisplayDate(row.arrived_at)"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Received At</p><p class="font-bold text-slate-800" x-text="formatDisplayDate(row.received_at)"></p></div>
                        </div>
                        <div class="mt-4">
                            <a :href="row.view_url" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">View</a>
                        </div>
                    </div>
                </template>
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
        </div>
    </div>
    <template x-teleport="body">
        <div x-show="scanModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[210] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
            <div @@click.stop class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Scan Package</h3>
                            <p class="mt-1 text-sm text-slate-500">Scan the printed label to receive an incoming package.</p>
                        </div>
                    </div>
                    <button type="button" @@click="closeScanModal()" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <video x-ref="scanVideo" class="hidden aspect-video w-full object-contain" playsinline muted></video>
                        <canvas x-ref="scanCanvas" class="hidden"></canvas>
                        <div x-show="scannerActive" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-between p-4" style="display:none">
                            <div class="rounded-full bg-black/55 px-3 py-1.5 text-xs font-bold text-white shadow-lg" x-text="scannerStatus || 'Scanning barcode...'"></div>
                            <div></div>
                            <p class="rounded-full bg-black/55 px-3 py-1.5 text-[11px] font-semibold text-white">Point the camera anywhere on the package label</p>
                        </div>
                        <div x-show="!scannerActive" class="flex aspect-video flex-col items-center justify-center gap-3 p-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-white">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-white" x-text="scannerStatus || 'Camera scanner is ready when supported by this browser.'"></p>
                            <button type="button" @@click="startScanner()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700">Start Camera Scan</button>
                        </div>
                    </div>

                    <div x-show="scanModalMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700" x-text="scanModalMessage"></div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Manual entry</label>
                        <form class="flex flex-col gap-3" @@submit.prevent="scanIncomingPackage()">
                            <input type="text" x-model="scannerCode" @@input="scanModalMessage = ''" x-ref="scannerInput" placeholder="Enter or paste label code"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <button type="submit" x-show="scannerCode.trim()" :disabled="scannerLoading" class="w-full rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white shadow-lg shadow-slate-900/20 transition hover:border-slate-800 hover:bg-slate-800 disabled:opacity-50 sm:text-sm">
                                <span x-text="scannerLoading ? 'Checking...' : 'Load Package'"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @include('shared.incoming-receive-modal')
</div>
@endsection
