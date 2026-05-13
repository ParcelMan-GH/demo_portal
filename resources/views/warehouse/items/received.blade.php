@extends('warehouse.layouts.app')

@section('title', 'Warehouse Packages')
@section('page-title', 'Warehouse Packages')

@php
    $config = [
        'endpoint' => route('warehouse.packages.data'),
        'update_url' => route('warehouse.packages.update', ['warehouseReceiptItem' => '__ITEM__']),
        'print_label_url' => route('warehouse.packages.print-label', ['warehouseReceiptItem' => '__ITEM__']),
        'location_search_url' => route('warehouse.locations.search'),
        'transfer_warehouses' => $transferWarehouses,
        'open_batches' => $openBatches->map(fn ($batch) => [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'dispatch_mode' => $batch->dispatch_mode,
        ])->values(),
        'warehouse_users' => $warehouseUsers->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
        ])->values(),
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
<div class="space-y-5" x-data="warehousePackagesPage" data-warehouse-packages-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-7">
        <template x-for="stat in statCards" :key="stat.key">
            <button type="button" @@click="applySummaryFilter(stat.key)" class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1" :class="stat.iconClass">
                    <svg x-show="stat.icon === 'package'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <svg x-show="stat.icon === 'warehouse'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 21h18M4 21V8l8-5 8 5v13M9 21v-7h6v7M7 10h2m6 0h2"/></svg>
                    <svg x-show="stat.icon === 'truck'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                    <svg x-show="stat.icon === 'route'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M6 19a3 3 0 100-6 3 3 0 000 6zm12-8a3 3 0 100-6 3 3 0 000 6zM8.5 14.5l7-5"/></svg>
                    <svg x-show="stat.icon === 'check'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg x-show="stat.icon === 'cash'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h18v10H3V7zm4 3h.01M17 14h.01M12 15a3 3 0 100-6 3 3 0 000 6z"/></svg>
                    <span x-show="stat.icon === 'cedi'" class="text-base font-black leading-none">₵</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.12em] leading-snug text-slate-400" x-text="stat.label"></p>
                    <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="stat.money ? formatMoney(summary[stat.key] ?? 0) : (summary[stat.key] ?? 0)"></p>
                </div>
            </button>
        </template>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Warehouse Packages</h2>
                            <p class="truncate text-sm text-slate-500">Every package that has passed through {{ $warehouse->name ?? 'this warehouse' }}.</p>
                        </div>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700" x-text="meta.total + ' packages'">0 packages</span>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search packages..."
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
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Received Date</label>
                        <input type="text" x-ref="dateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivered Date</label>
                        <input type="text" x-ref="deliveredDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Package Status</label>
                        <select x-model="filters.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Custody</label>
                        <select x-model="filters.custody" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All custody states</option>
                            <option value="at_warehouse">At warehouse</option>
                            <option value="with_driver">With driver</option>
                            <option value="bus_handoff">Bus/courier handoff</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Sort Batch</label>
                        <select x-model="filters.sort_batch" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All sort states</option>
                            <option value="none">No batch</option>
                            <option value="open">Open batch</option>
                            <option value="sealed">Sealed batch</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Sort Batch Search</label>
                        <select x-model="filters.sort_batch_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any batch</option>
                            <template x-for="batch in openBatches" :key="batch.id"><option :value="batch.id" x-text="batch.batch_number"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Manifest</label>
                        <select x-model="filters.manifest_status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All manifests</option>
                            <template x-for="status in manifestStatuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery</label>
                        <select x-model="filters.delivery_status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All delivery states</option>
                            <option value="not_assigned">Not assigned</option>
                            <option value="pending">Assigned / pending</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                            <option value="handed_off">Handed off</option>
                            <option value="bus_handoff">Bus handoff</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery Method</label>
                        <select x-model="filters.delivery_method" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All methods</option>
                            <option value="direct">Direct</option>
                            <option value="bus_handoff">Bus handoff</option>
                            <option value="pickup">Self pickup</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment</label>
                        <select x-model="filters.payment_status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All payment states</option>
                            <option value="no_fee">No fee</option>
                            <option value="due">Due</option>
                            <option value="paid">Paid</option>
                            <option value="waived">Waived</option>
                            <option value="overridden">Override</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery Staff</label>
                        <select x-model="filters.delivery_staff_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All staff</option>
                            <template x-for="user in warehouseUsers" :key="user.id"><option :value="user.id" x-text="user.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Staff</label>
                        <select x-model="filters.payment_staff_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All staff</option>
                            <template x-for="user in warehouseUsers" :key="user.id"><option :value="user.id" x-text="user.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery Fee Range</label>
                        <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" step="0.01" x-model="filters.amount_min" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <div class="w-px bg-slate-200"></div>
                            <input type="number" min="0" step="0.01" x-model="filters.amount_max" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Vendor</label>
                        <input type="text" x-model="filters.vendor" placeholder="Vendor name or phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
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
                    <table class="w-full min-w-[1900px] divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <template x-for="column in columns" :key="column.key">
                                    <th x-show="visibleColumns[column.key]" @@click="sort(column.key)" class="px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="[isSortable(column.key) ? 'cursor-pointer' : '', tableHeaderClass(column.key)]">
                                        <div class="flex items-center gap-1" :class="tableHeaderContentClass(column.key)">
                                            <span x-text="column.label"></span>
                                            <svg x-show="isSortable(column.key)" class="h-2.5 w-2.5" :class="sortBy === column.key ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
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
                                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-500">No packages match the current filters</p>
                                            <button type="button" @@click="clearFilters()" class="text-xs font-semibold text-orange-600 hover:underline">Clear filters</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="row in rows" :key="row.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td x-show="visibleColumns.package" class="px-4 py-3">
                                        <p class="font-bold text-slate-900" x-text="row.item_description || '-'"></p>
                                        <p class="mt-1 font-mono text-[11px] text-slate-500" x-text="row.tracking_code || row.barcode_value || '-'"></p>
                                    </td>
                                    <td x-show="visibleColumns.qty" class="whitespace-nowrap px-4 py-3 text-center">
                                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="row.received_quantity || row.quantity || 0"></span>
                                    </td>
                                    <td x-show="visibleColumns.shipment" class="whitespace-nowrap px-4 py-3">
                                        <a :href="row.shipment_url" class="font-semibold text-blue-700 hover:underline" x-text="row.shipment_number || '-'"></a>
                                        <p class="text-[10px] text-slate-400" x-text="row.vendor_name || ''"></p>
                                    </td>
                                    <td x-show="visibleColumns.recipient" class="px-4 py-3">
                                        <p class="font-semibold text-slate-700" x-text="row.recipient_name || '-'"></p>
                                        <p class="text-[11px] text-slate-500" x-text="row.recipient_phone || '-'"></p>
                                    </td>
                                    <td x-show="visibleColumns.destination" class="px-4 py-3 text-slate-600" x-text="row.destination || '-'"></td>
                                    <td x-show="visibleColumns.stage" class="whitespace-nowrap px-4 py-3 text-center"><span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="stageClass(row.current_stage?.tone)" x-text="row.current_stage?.label || '-'"></span></td>
                                    <td x-show="visibleColumns.custody" class="whitespace-nowrap px-4 py-3">
                                        <p class="font-semibold text-slate-900" x-text="row.custody?.label || '-'"></p>
                                        <p x-show="row.custody?.holder" class="text-[11px] text-slate-500" x-text="row.custody?.holder"></p>
                                        <p x-show="row.custody?.detail" class="text-[10px] text-slate-400" x-text="row.custody?.detail"></p>
                                    </td>
                                    <td x-show="visibleColumns.sort_batch" class="whitespace-nowrap px-4 py-3">
                                        <div x-show="row.sort_batch_url" class="inline-flex flex-col items-start gap-1">
                                            <a :href="row.sort_batch_url" class="font-semibold leading-none text-violet-700 hover:underline" x-text="row.sort_batch?.number"></a>
                                            <span x-show="row.sort_batch?.status" class="inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700 ring-1 ring-violet-200" x-text="row.sort_batch?.status"></span>
                                        </div>
                                        <span x-show="!row.sort_batch_url" class="text-slate-400">-</span>
                                    </td>
                                    <td x-show="visibleColumns.manifest" class="whitespace-nowrap px-4 py-3">
                                        <div x-show="row.manifest_url" class="inline-flex flex-col items-start gap-1">
                                            <a :href="row.manifest_url" class="font-semibold leading-none text-blue-700 hover:underline" x-text="row.transport_manifest?.number"></a>
                                            <span x-show="row.transport_manifest?.status" class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-blue-200" x-text="row.transport_manifest?.status"></span>
                                        </div>
                                        <span x-show="!row.manifest_url" class="text-slate-400">-</span>
                                    </td>
                                    <td x-show="visibleColumns.delivery" class="whitespace-nowrap px-4 py-3">
                                        <div x-show="row.delivery_run_url" class="inline-flex flex-col items-start gap-1">
                                            <a :href="row.delivery_run_url" class="font-semibold leading-none text-emerald-700 hover:underline" x-text="row.delivery_run?.number"></a>
                                            <span x-show="row.delivery_run?.status || row.delivery_run?.stop_status" class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200" x-text="row.delivery_run?.status || row.delivery_run?.stop_status"></span>
                                        </div>
                                        <span x-show="!row.delivery_run_url" class="text-slate-400">-</span>
                                    </td>
                                    <td x-show="visibleColumns.payment" class="min-w-[150px] whitespace-nowrap px-4 py-3 text-left align-middle">
                                        <template x-if="row.payment?.status === 'no_fee'">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200">No delivery fee</span>
                                        </template>
                                        <template x-if="row.payment?.status !== 'no_fee'">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-900" x-text="paymentAmount(row.payment)"></span>
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="paymentClass(row.payment?.status_label)" x-text="row.payment?.status_label || ''"></span>
                                                </div>
                                                <p class="mt-1 text-[10px] text-slate-400" x-show="row.payment?.paid_by" x-text="'By ' + row.payment.paid_by"></p>
                                            </div>
                                        </template>
                                    </td>
                                    <td x-show="visibleColumns.received" class="whitespace-nowrap px-4 py-3 text-slate-600">
                                        <p x-text="row.received_at || '-'"></p>
                                        <p class="text-[10px] text-slate-400" x-text="row.received_by || row.pickup_driver || ''"></p>
                                    </td>
                                    <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                View
                                            </a>
                                            <button type="button" @@click="openEditModal(row)" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" @@click="openPrintModal(row)" class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-slate-700">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0v4h12v-4H6z"/></svg>
                                                Print
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    <template x-if="!loading && rows.length === 0">
                        <div class="px-4 py-12 text-center text-sm text-slate-400">No packages match the current filters.</div>
                    </template>
                    <template x-for="row in rows" :key="row.id">
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-slate-900" x-text="row.item_description || '-'"></p>
                                    <p class="mt-1 font-mono text-[11px] text-slate-500" x-text="row.tracking_code || row.barcode_value || '-'"></p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold" :class="stageClass(row.current_stage?.tone)" x-text="row.current_stage?.label || '-'"></span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Shipment</p><p class="font-bold text-slate-800" x-text="row.shipment_number || '-'"></p></div>
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Qty</p><p class="font-bold text-slate-800" x-text="row.received_quantity || row.quantity || 0"></p></div>
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Recipient</p><p class="font-bold text-slate-800" x-text="row.recipient_name || '-'"></p><p class="text-slate-500" x-text="row.recipient_phone || '-'"></p></div>
                                <div>
                                    <p class="font-black uppercase tracking-wide text-slate-400">Payment</p>
                                    <template x-if="row.payment?.status === 'no_fee'">
                                        <p class="font-bold text-slate-500">No delivery fee</p>
                                    </template>
                                    <template x-if="row.payment?.status !== 'no_fee'">
                                        <div>
                                            <p class="font-bold text-slate-800" x-text="paymentAmount(row.payment)"></p>
                                            <p class="text-slate-500" x-text="row.payment?.status_label || ''"></p>
                                        </div>
                                    </template>
                                </div>
                                <div class="col-span-2">
                                    <p class="font-black uppercase tracking-wide text-slate-400">Custody</p>
                                    <p class="font-bold text-slate-800" x-text="row.custody?.label || '-'"></p>
                                    <p x-show="row.custody?.holder || row.custody?.detail" class="text-slate-500" x-text="row.custody?.holder || row.custody?.detail"></p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a :href="row.view_url" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">View</a>
                                <button type="button" @@click="openEditModal(row)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">Edit</button>
                                <button type="button" @@click="openPrintModal(row)" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">Print</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> results</div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @@click="open = !open"
                                            class="inline-flex min-w-[60px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90">
                                        <span x-text="perPage"></span>
                                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition
                                         class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200/70 bg-white/95 p-1 shadow-lg backdrop-blur-xl" style="display:none">
                                        <button type="button" @@click="setPerPage(10); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                        <button type="button" @@click="setPerPage(25); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                        <button type="button" @@click="setPerPage(50); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        <button type="button" @@click="setPerPage(100); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs font-medium text-slate-600">Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></div>
                            <div class="flex space-x-1">
                                <button @@click="firstPage()" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                </button>
                                <button @@click="previousPage()" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <button @@click="lastPage()" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <div x-show="editModalOpen" x-cloak x-transition.opacity @@keydown.window.escape="closeEditModal()" class="fixed inset-0 z-50 flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" style="display:none">
        <div @@click.stop class="flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
            <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8.5l5-3 5 3-5 3-5-3zM4 13l5 3 5-3M10 16l5 3 5-3M4 13l5-3 5 3-5 3-5-3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-extrabold text-slate-900">Edit Package</h3>
                        <p class="mt-1 truncate text-sm text-slate-500" x-text="activeRow?.tracking_code || activeRow?.barcode_value || activeRow?.shipment_number || 'Update package and recipient details'"></p>
                    </div>
                </div>
                <button type="button" @@click="closeEditModal()" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Receipt photos</label>
                    <div class="space-y-3 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-3 py-3">
                        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <span class="min-w-0 max-w-full">
                                <span class="block truncate text-sm font-bold text-slate-700" x-text="photoUploadFiles.length ? photoUploadFiles.length + ' new photo(s) selected' : (rowPhotoList().length ? activeRow?.photos?.primary_label + ' available' : 'Upload or take package photos')"></span>
                                <span class="block text-xs font-medium text-slate-400">
                                    PNG, JPG or WEBP up to 12MB each
                                    <span x-show="removePhotoIds.length"> · <span x-text="removePhotoIds.length"></span> receipt photo(s) marked for removal</span>
                                </span>
                                <span x-show="!hasRequiredPackagePhotos()" class="mt-1 block text-xs font-bold text-rose-600">At least one package photo is required.</span>
                            </span>
                            <span class="flex w-fit shrink-0 gap-2">
                                <button type="button" x-show="rowPhotoList().length" @@click="openPackagePhotos()" class="rounded-lg bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-sm ring-1 ring-slate-200">View photos</button>
                                <label class="cursor-pointer rounded-lg bg-white px-3 py-2 text-xs font-black text-orange-700 shadow-sm ring-1 ring-orange-100">
                                    Choose
                                    <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" capture="environment" multiple class="hidden" @@change="handlePackagePhotos($event)">
                                </label>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-9">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Description <span class="text-rose-500">*</span></label>
                        <input x-model="editForm.description" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Quantity <span class="text-rose-500">*</span></label>
                        <input type="number" min="1" x-model.number="editForm.quantity" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Recipient details</p>
                        <p class="mt-0.5 text-xs text-slate-400">Name, phone, and local delivery address for this package.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient name</label>
                            <input x-model="editForm.delivery_recipient_name" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient phone</label>
                            <input x-model="editForm.delivery_recipient_phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div class="relative sm:col-span-2" @@click.outside="editForm._showDropdown = false" @@focusout="closeLocationDropdownSoon(editForm)">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Location</label>
                            <input x-model="editForm.locationQuery" @@input="searchLocation(editForm)" @@focus="editForm.locationResults?.length && (editForm._showDropdown = true)"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <div x-show="editForm._showDropdown && editForm.locationResults?.length" class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                <template x-for="loc in editForm.locationResults" :key="loc.id">
                                    <button type="button" @@click="selectLocation(editForm, loc)" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-orange-50">
                                        <span x-text="loc.display"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Landmark</label>
                            <input x-model="editForm.delivery_landmark" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Instructions</label>
                            <textarea x-model="editForm.delivery_instructions" rows="2" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div>
                    <label x-show="activeRow?.can_edit_bus_handoff" class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 transition hover:border-orange-200 hover:bg-orange-50/20">
                        <span>
                            <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Bus station</span>
                            <span class="block text-sm font-black text-slate-900">Send to bus station</span>
                        </span>
                        <input type="checkbox" :checked="editForm.delivery_method === 'bus_handoff'" @@change="editForm.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    </label>
                    <div x-show="!activeRow?.can_edit_bus_handoff" class="rounded-xl border-2 border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Bus station</p>
                        <p class="mt-1 text-sm font-black text-slate-900">Locked</p>
                        <p class="mt-1 text-xs text-slate-500" x-text="activeRow?.bus_handoff_lock_reason || 'Bus handoff cannot be changed for this package.'"></p>
                    </div>
                </div>

                <div x-show="activeRow?.can_forward_to_warehouse" class="rounded-xl border-2 border-orange-100 bg-orange-50/40 px-3 py-3">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-orange-600">Forward to warehouse</label>
                    <select x-model="editForm.forward_to_warehouse_id"
                            class="w-full rounded-xl border-2 border-orange-100 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">Keep at this warehouse</option>
                        <template x-for="warehouse in transferWarehouses" :key="warehouse.id">
                            <option :value="warehouse.id" x-text="warehouse.name"></option>
                        </template>
                    </select>
                </div>
                <div x-show="!activeRow?.can_forward_to_warehouse" class="rounded-xl border-2 border-slate-200 bg-slate-50 px-3 py-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Forward to warehouse</p>
                    <p class="mt-1 text-sm font-black text-slate-900" x-text="activeRow?.sort_batch?.destination || 'Locked'"></p>
                    <p class="mt-1 text-xs text-slate-500" x-text="activeRow?.forward_lock_reason || 'Forwarding cannot be changed for this package.'"></p>
                </div>
            </div>

            <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="closeEditModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button type="button" @@click="savePackage()" :disabled="!canSaveEditPackage()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm">
                    <span x-text="modalLoading ? 'Saving...' : 'Save Package'"></span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="printModalOpen" x-cloak x-transition.opacity @@keydown.window.escape="closePrintModal()" class="fixed inset-0 z-50 flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" style="display:none">
        <div @@click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-lg shadow-slate-900/15">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0v4h12v-4H6z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-lg font-extrabold text-slate-900">Print Labels</h3>
                        <p class="mt-1 truncate text-sm text-slate-500" x-text="activeRow?.tracking_code || activeRow?.barcode_value || activeRow?.shipment_number || 'Package label'"></p>
                    </div>
                </div>
                <button type="button" @@click="closePrintModal()" :disabled="printLoading" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Package</p>
                    <p class="mt-1 text-sm font-black text-slate-900" x-text="activeRow?.item_description || '-'"></p>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <p class="font-black uppercase tracking-wide text-slate-400">Quantity</p>
                            <p class="mt-1 font-bold text-slate-800" x-text="activeRow?.received_quantity || activeRow?.quantity || 1"></p>
                        </div>
                        <div>
                            <p class="font-black uppercase tracking-wide text-slate-400">Existing labels</p>
                            <p class="mt-1 font-bold text-slate-800" x-text="activeRow?.label_count || 0"></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Labels to print</label>
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" @@click="setPrintLabelCount(Number(printForm.label_count || 1) - 1)" :disabled="printLoading || Number(printForm.label_count || 1) <= 1" class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">-</button>
                        <input type="number" min="1" max="500" x-model.number="printForm.label_count" @@input="setPrintLabelCount(printForm.label_count)" :disabled="printLoading" class="h-11 w-24 rounded-xl border-2 border-slate-200 bg-white text-center text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:cursor-not-allowed disabled:bg-slate-50">
                        <button type="button" @@click="setPrintLabelCount(Number(printForm.label_count || 1) + 1)" :disabled="printLoading || Number(printForm.label_count || 1) >= 500" class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                    </div>
                    <p class="mt-2 text-center text-xs font-semibold text-slate-400">Maximum 500 labels per print.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="closePrintModal()" :disabled="printLoading" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm">Cancel</button>
                <button type="button" @@click="printLabel()" :disabled="printLoading || !Number(printForm.label_count || 0)" class="rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white shadow-lg shadow-slate-900/15 transition hover:border-slate-800 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm">
                    <span x-text="printLoading ? 'Printing...' : 'Print Labels'"></span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="photoPreviewOpen" x-cloak x-transition.opacity @@click="closePackagePhotos()" @@keydown.window.escape="closePackagePhotos()" @@keydown.window.arrow-left="previousPackagePhoto()" @@keydown.window.arrow-right="nextPackagePhoto()"
         class="fixed left-0 top-0 z-[9999] flex h-[100dvh] w-[100vw] items-center justify-center bg-black p-4" style="display:none">
        <button type="button" @@click.stop="closePackagePhotos()" class="absolute right-4 top-4 z-20 rounded-full bg-white/10 p-3 text-white/80 transition hover:bg-white/20 hover:text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button type="button" x-show="canRemoveActivePhoto()" @@click.stop="removeActivePhoto()" class="absolute left-4 top-4 z-20 rounded-full bg-rose-500/90 px-4 py-2 text-xs font-black text-white shadow-lg shadow-rose-950/30 transition hover:bg-rose-500">
            Remove photo
        </button>
        <button type="button" x-show="photoPreviewUrls.length > 1" @@click.stop="previousPackagePhoto()" class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white/80 transition hover:bg-white/20 hover:text-white">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <template x-if="activePackagePhoto()">
            <img @@click.stop :src="activePackagePhoto().url" :alt="activePackagePhoto().name || 'Package photo'" class="max-h-[92dvh] max-w-[94vw] rounded-2xl object-contain shadow-2xl ring-1 ring-white/10">
        </template>
        <button type="button" x-show="photoPreviewUrls.length > 1" @@click.stop="nextPackagePhoto()" class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white/80 transition hover:bg-white/20 hover:text-white">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-4 left-1/2 z-20 -translate-x-1/2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white/90">
            <span x-text="activePackagePhoto()?.source || photoPreviewPackage?.photos?.primary_label || 'Package photo'"></span>
            <span x-show="photoPreviewUrls.length > 1"> · <span x-text="`${activePhotoIndex + 1} / ${photoPreviewUrls.length}`"></span></span>
        </div>
    </div>
</div>
@endsection
