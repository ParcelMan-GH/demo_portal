@extends('warehouse.layouts.app')

@section('title', 'Delivery Runs')
@section('page-title', 'Delivery Runs')

@php
    $config = [
        'data_endpoint' => route('warehouse.deliveries.runs.data'),
        'create_endpoint' => route('warehouse.deliveries.runs.store'),
        'eligible_items_endpoint' => route('warehouse.deliveries.runs.eligible-items'),
        'create_from_items_endpoint' => route('warehouse.deliveries.runs.store-from-items'),
        'assign_endpoint' => route('warehouse.deliveries.runs.assign-driver', ['run' => '__RUN__']),
        'dispatch_endpoint' => route('warehouse.deliveries.runs.dispatch', ['run' => '__RUN__']),
        'resend_code_endpoint' => route('warehouse.deliveries.runs.stops.resend-code', ['run' => '__RUN__', 'stop' => '__STOP__']),
        'delivery_drivers' => $deliveryDrivers->values(),
        'local_delivery_batches' => $localDeliveryBatches->values(),
        'run_stats' => $runStats ?? [],
        'can_reset_codes' => (bool) ($canResetCodes ?? false),
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="warehouseDeliveryRunsPage" data-warehouse-delivery-runs-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Total Runs</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="runStats.total || 0">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h8M8 12h8m-8 5h5M5 3h14a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Ready Batches</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="runStats.ready_batches || localDeliveryBatches.length">{{ $localDeliveryBatches->count() }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Active</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="runStats.active || 0">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Completed</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="runStats.completed || 0">0</p>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        {{-- Header --}}
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Delivery Runs</h2>
                        <p class="truncate text-sm text-slate-500">Create, assign, dispatch, and monitor local delivery runs from {{ $warehouse->name ?? 'this warehouse' }}.</p>
                    </div>
                </div>
                <button
                    type="button"
                    @@click="createRun()"
                    :disabled="loading"
                    class="inline-flex w-fit items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700"
                >
                    <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span x-text="loading ? 'Creating...' : 'Create Run'"></span>
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    {{-- Search --}}
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                        >
                    </div>
                </div>

                {{-- View / Export --}}
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @@click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    {{-- View Column Toggle --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button"
                                        @@click="toggleColumn(column.key)"
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Export --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">CSV</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">PDF</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                        <input type="text" x-ref="createdDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assigned Date</label>
                        <input type="text" x-ref="assignedDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Dispatched Date</label>
                        <input type="text" x-ref="dispatchedDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Completed Date</label>
                        <input type="text" x-ref="completedDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Run Status</label>
                        <select x-model="filters.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Driver</label>
                        <select x-model="filters.driver_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All drivers</option>
                            <template x-for="driver in deliveryDrivers" :key="driver.id"><option :value="driver.id" x-text="driver.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Stop Status</label>
                        <select x-model="filters.stop_status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All stop statuses</option>
                            <template x-for="status in stopStatuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Verification</label>
                        <select x-model="filters.verification" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All verification states</option>
                            <option value="verified">Verified by code</option>
                            <option value="skipped">Verification skipped</option>
                            <option value="code_sent">Code sent</option>
                            <option value="no_code">No code sent</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Stops Range</label>
                        <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="filters.stops_min" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <div class="w-px bg-slate-200"></div>
                            <input type="number" min="0" x-model="filters.stops_max" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Items Range</label>
                        <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="filters.items_min" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <div class="w-px bg-slate-200"></div>
                            <input type="number" min="0" x-model="filters.items_max" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
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

        {{-- Table --}}
        <div class="relative overflow-hidden">
                <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

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
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No delivery runs found.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.run_number" class="w-[22%] max-w-[320px] whitespace-nowrap px-4 py-3">
                                    <a :href="row.view_url" class="font-bold text-slate-900 hover:text-orange-700 hover:underline" x-text="row.run_number || '-'"></a>
                                </td>
                                <td x-show="visibleColumns.status" class="w-[12%] whitespace-nowrap px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                        :class="statusBadgeClass(row.status)"
                                        x-text="statusLabel(row.status)"
                                    ></span>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="w-[18%] whitespace-nowrap px-4 py-3">
                                    <template x-if="row.driver_name">
                                        <div>
                                            <p class="font-semibold text-slate-900" x-text="row.driver_name"></p>
                                            <p class="text-[11px] text-slate-500" x-text="row.driver_phone || '-'"></p>
                                        </div>
                                    </template>
                                    <template x-if="!row.driver_name">
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">Needs rider</span>
                                    </template>
                                </td>
                                <td x-show="visibleColumns.stops_count" class="w-[8%] whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="row.stops_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.items_count" class="w-[8%] whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="row.items_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.assigned_at" class="w-[14%] whitespace-nowrap px-4 py-3 text-slate-600">
                                    <p x-text="formatDisplayDate(row.assigned_at)"></p>
                                </td>
                                <td x-show="visibleColumns.dispatched_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.dispatched_at)"></td>
                                <td x-show="visibleColumns.completed_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.completed_at)"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a
                                            :href="row.view_url"
                                            class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </a>
                                        <button
                                            type="button"
                                            x-show="canDispatch(row)"
                                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100"
                                            @@click="dispatchRun(row.id)"
                                            :disabled="loading"
                                        >
                                            Dispatch
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {{-- Expandable Stops Row --}}
                            <tr x-show="expandedRunId === row.id" x-cloak class="bg-slate-50/40">
                                <td :colspan="visibleColumnCount()" class="px-4 py-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <template x-for="stop in row.stops" :key="`stop-${row.id}-${stop.id}`">
                                            <div class="rounded-xl border border-slate-200/70 bg-white/80 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-slate-800 truncate" x-text="stop.recipient_name || '-'"></p>
                                                        <p class="text-[11px] text-slate-500 truncate" x-text="stop.recipient_phone || '-'"></p>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span x-show="stop.verification_skipped" class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700" title="OTP verification was skipped">
                                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                                            Unverified
                                                        </span>
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="stopStatusClass(stop.status)" x-text="stop.status"></span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex items-center justify-between gap-2">
                                                    <p class="text-[11px] text-slate-500">Attempts: <span x-text="`${stop.attempts}/${stop.max_attempts}`"></span></p>
                                                    <button
                                                        type="button"
                                                        class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                                        @@click="resendCode(row.id, stop.id)"
                                                        :disabled="!canResendCode(row, stop)"
                                                    >
                                                        Resend Code
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    <template x-if="!loading && rows.length === 0">
                        <div class="px-4 py-12 text-center text-sm text-slate-400">No delivery runs found.</div>
                    </template>
                    <template x-for="row in rows" :key="row.id">
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a :href="row.view_url" class="truncate text-sm font-extrabold text-orange-700 hover:underline" x-text="row.run_number || '-'"></a>
                                    <p class="mt-1 text-xs text-slate-500" x-text="row.driver_name ? row.driver_name + ' · ' + (row.driver_phone || '') : 'No driver assigned'"></p>
                                </div>
                                <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="statusBadgeClass(row.status)" x-text="statusLabel(row.status)"></span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Stops</p><p class="font-bold text-slate-800" x-text="row.stops_count || 0"></p></div>
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Items</p><p class="font-bold text-slate-800" x-text="row.items_count || 0"></p></div>
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Assigned</p><p class="font-bold text-slate-800" x-text="row.assigned_at || '-'"></p></div>
                                <div><p class="font-black uppercase tracking-wide text-slate-400">Dispatched</p><p class="font-bold text-slate-800" x-text="row.dispatched_at || '-'"></p></div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a :href="row.view_url" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">View Run</a>
                                <button type="button" x-show="canDispatch(row)" @@click="dispatchRun(row.id)" :disabled="loading" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Dispatch</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Pagination --}}
                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs font-semibold text-slate-600">
                            Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600">Rows</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button
                                        type="button"
                                        @@click="open = !open"
                                        class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700"
                                    >
                                        <span x-text="perPage"></span>
                                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        @@click.away="open = false"
                                        x-transition
                                        class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg"
                                        style="display: none;"
                                    >
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

    {{-- Create Run Modal --}}
    <template x-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @@click.self="showCreateModal = false" @@keydown.escape.window="showCreateModal = false">
            <div class="w-full max-h-[85vh] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col" @@click.stop
                 :class="createMode === 'items' ? 'max-w-4xl' : 'max-w-lg'">

                {{-- Modal Header --}}
                <div class="px-6 py-5 border-b border-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Create Delivery Run</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Choose how you'd like to create a new delivery run.</p>
                            </div>
                        </div>
                        <button type="button" @@click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Mode Tabs --}}
                    <div class="flex mt-4 bg-slate-100/80 rounded-xl p-1 gap-1">
                        <button
                            type="button"
                            @@click="createMode = 'batch'"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="createMode === 'batch' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            From Batch
                        </button>
                        <button
                            type="button"
                            @@click="createMode = 'items'; loadEligibleItems()"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="createMode === 'items' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            From Items
                        </button>
                    </div>
                </div>

                {{-- FROM BATCH --}}
                <div x-show="createMode === 'batch'" class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Sealed Local-Delivery Batch</label>
                            <p class="text-xs text-slate-500 mb-3">Select a sealed batch to automatically create a delivery run with all its items.</p>
                            <select x-model="newRunBatchId" class="w-full rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                                <option value="">Select a batch...</option>
                                <template x-for="batch in localDeliveryBatches" :key="batch.id">
                                    <option :value="batch.id" x-text="batch.batch_number"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="localDeliveryBatches.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-6 text-center">
                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm font-medium text-slate-600">No sealed batches available</p>
                            <p class="text-xs text-slate-500 mt-1">Seal a local-delivery sort batch first, or use "From Items" mode.</p>
                        </div>
                    </div>
                </div>

                {{-- FROM ITEMS --}}
                <template x-if="createMode === 'items'">
                    <div class="flex flex-col flex-1 min-h-0">
                        <div class="px-6 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                            <div class="relative flex-1 max-w-xs">
                                <input type="text" x-model.debounce.350ms="eligibleSearch" @@input="loadEligibleItems()" placeholder="Search shipment, item, recipient..." class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 text-sm placeholder-slate-400">
                                <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                <span x-text="selectedReceiptItemIds.length"></span>&nbsp;selected
                            </span>
                        </div>

                        <div class="flex-1 overflow-auto">
                            <table class="min-w-full divide-y divide-slate-200/60 text-xs">
                                <thead class="bg-slate-50/70 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left w-10">
                                            <input type="checkbox" @@change="toggleAllEligible($event)" :checked="selectedReceiptItemIds.length > 0 && selectedReceiptItemIds.length === eligibleItems.length" class="rounded border-slate-300">
                                        </th>
                                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Shipment / Item</th>
                                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Destination</th>
                                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in eligibleItems" :key="row.warehouse_receipt_item_id">
                                        <tr class="hover:bg-slate-50/70 cursor-pointer" @@click="toggleItem(row.warehouse_receipt_item_id)">
                                            <td class="px-4 py-2.5">
                                                <input type="checkbox" :value="row.warehouse_receipt_item_id" x-model.number="selectedReceiptItemIds" @@click.stop class="rounded border-slate-300">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <p class="font-semibold text-slate-900" x-text="row.shipment_number"></p>
                                                <p class="text-slate-600" x-text="row.item_description"></p>
                                                <p class="text-[11px] text-slate-500" x-text="row.tracking_code || '-'"></p>
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700">
                                                <p class="font-medium" x-text="row.destination?.recipient_name || '-'"></p>
                                                <p class="text-[11px] text-slate-500" x-text="(row.destination?.region || '-') + ' / ' + (row.destination?.district || '-')"></p>
                                                <p class="text-[11px] text-slate-500" x-text="row.destination?.town || '-'"></p>
                                            </td>
                                            <td class="px-4 py-2.5 text-center font-semibold text-slate-800" x-text="row.received_quantity"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!eligibleLoading && eligibleItems.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center">
                                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="text-sm font-medium text-slate-600">No eligible items</p>
                                            <p class="text-xs text-slate-500 mt-1">All received items have already been assigned to delivery runs.</p>
                                        </td>
                                    </tr>
                                    <tr x-show="eligibleLoading">
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-xs">Loading items...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-slate-200/60 flex items-center justify-between gap-3">
                    <button type="button" @@click="showCreateModal = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>

                    {{-- Batch mode button --}}
                    <button
                        x-show="createMode === 'batch'"
                        type="button"
                        @@click="createRun()"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors"
                        :disabled="loading || !newRunBatchId"
                    >
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Create from Batch
                    </button>

                    {{-- Items mode button --}}
                    <button
                        x-show="createMode === 'items'"
                        type="button"
                        @@click="createRunFromItems()"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors"
                        :disabled="loading || selectedReceiptItemIds.length === 0"
                    >
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Create from Items (<span x-text="selectedReceiptItemIds.length"></span>)
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
