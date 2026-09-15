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
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="warehouseDeliveryRunsPage" data-warehouse-delivery-runs-config="{{ json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE) }}">
    
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Delivery Runs</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">Create, assign, dispatch, and monitor local delivery runs from {{ $warehouse->name ?? 'this warehouse' }}.</p>
        </div>

        <div>
            <button
                type="button"
                @click="createRun()"
                :disabled="loading"
                class="inline-flex w-fit items-center gap-2 rounded-2xl bg-orange-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700"
            >
                <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="loading ? 'Creating...' : 'Create Delivery Run'"></span>
            </button>
        </div>
    </div>

    <!-- Unified Minimal Cream Metric Cards Grid -->
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="relative flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-[#FFFCF8] p-5 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200/60 bg-white text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM7 18a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[10px] font-black uppercase tracking-widest text-slate-400">Total Runs</p>
                <p class="mt-0.5 text-2xl font-black text-slate-900" x-text="runStats.total || 0">0</p>
            </div>
        </div>
        
        <div class="relative flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-[#FFFCF8] p-5 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200/60 bg-white text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h8M8 12h8m-8 5h5M5 3h14a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[10px] font-black uppercase tracking-widest text-slate-400">Ready Batches</p>
                <p class="mt-0.5 text-2xl font-black text-slate-900" x-text="runStats.ready_batches || localDeliveryBatches.length">{{ $localDeliveryBatches->count() }}</p>
            </div>
        </div>
        
        <div class="relative flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-[#FFFCF8] p-5 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200/60 bg-white text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[10px] font-black uppercase tracking-widest text-slate-400">Active</p>
                <p class="mt-0.5 text-2xl font-black text-slate-900" x-text="runStats.active || 0">0</p>
            </div>
        </div>
        
        <div class="relative flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-[#FFFCF8] p-5 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200/60 bg-white text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[10px] font-black uppercase tracking-widest text-slate-400">Completed</p>
                <p class="mt-0.5 text-2xl font-black text-slate-900" x-text="runStats.completed || 0">0</p>
            </div>
        </div>
    </div>

    <!-- Main Table Workspace -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        
        <!-- Filters & Actions -->
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <!-- Search -->
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            x-model="search"
                            @input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search..."
                            class="w-full rounded-2xl border-2 border-slate-200 bg-white py-3 pl-12 pr-4 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                        >
                    </div>
                </div>

                <!-- Export / Filter / Columns Toggles -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button"
                                        @click="toggleColumn(column.key)"
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <button type="button" @click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">CSV</button>
                            <button type="button" @click="exportData('print'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Drawer -->
            <div x-show="showFilters" x-transition class="border-b border-slate-100 bg-slate-50/70 px-5 py-4" style="display:none">
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
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Rider</label>
                        <select x-model="filters.driver_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All riders</option>
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
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <!-- Filter Chips -->
            <div class="mb-3 flex flex-wrap gap-2 px-5 py-2" x-show="activeFilterChips().length">
                <template x-for="chip in activeFilterChips()" :key="chip.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        <span x-text="chip.label"></span>
                        <button type="button" @click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <!-- Main Data Table -->
        <div class="relative overflow-hidden min-h-[500px]">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px] table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <template x-for="column in columns" :key="column.key">
                                <th x-show="visibleColumns[column.key]" @click="sort(column.key)" class="px-5 py-4 text-[11px] font-extrabold uppercase tracking-wide text-slate-500" :class="[isSortable(column.key) ? 'cursor-pointer' : '', tableHeaderClass(column.key)]">
                                    <div class="flex items-center gap-1" :class="tableHeaderContentClass(column.key)">
                                        <span x-text="column.label"></span>
                                        <svg x-show="isSortable(column.key)" class="h-3 w-3" :class="isSortedColumn(column.key) ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100">
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-16 text-center text-slate-500 font-semibold text-sm">No delivery runs found matching your search.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td x-show="visibleColumns.run_number" class="w-[22%] max-w-[320px] whitespace-nowrap px-5 py-4">
                                    <a :href="row.view_url" class="font-bold text-slate-900 hover:text-orange-700 hover:underline text-sm" x-text="row.run_number || '-'"></a>
                                </td>
                                <td x-show="visibleColumns.status" class="w-[12%] whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider" :class="statusBadgeClass(row.status)" x-text="statusLabel(row.status)"></span>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="w-[18%] whitespace-nowrap px-5 py-4">
                                    <template x-if="row.driver_name">
                                        <div>
                                            <p class="font-bold text-slate-900" x-text="row.driver_name"></p>
                                            <p class="text-[11px] text-slate-500 font-mono mt-0.5" x-text="row.driver_phone || '-'"></p>
                                        </div>
                                    </template>
                                    <template x-if="!row.driver_name">
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black text-amber-700 ring-1 ring-amber-200">Needs rider</span>
                                    </template>
                                </td>
                                <td x-show="visibleColumns.stops_count" class="w-[8%] whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-black text-slate-700" x-text="row.stops_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.items_count" class="w-[8%] whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-black text-slate-700" x-text="row.items_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.assigned_at" class="w-[14%] whitespace-nowrap px-5 py-4 text-slate-600 font-semibold">
                                    <p x-text="formatDisplayDate(row.assigned_at)"></p>
                                </td>
                                <td x-show="visibleColumns.dispatched_at" class="whitespace-nowrap px-5 py-4 text-slate-600 font-semibold" x-text="formatDisplayDate(row.dispatched_at)"></td>
                                <td x-show="visibleColumns.completed_at" class="whitespace-nowrap px-5 py-4 text-slate-600 font-semibold" x-text="formatDisplayDate(row.completed_at)"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button type="button" @click="toggleRunDetails(row.id)" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50">
                                            <span x-text="expandedRunId === row.id ? 'Hide Stops' : 'View Stops'"></span>
                                        </button>
                                        <a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-[11px] font-bold text-orange-700 transition-colors hover:bg-orange-100">
                                            Manage
                                        </a>
                                        <button type="button" x-show="canDispatch(row)" class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100" @click="dispatchRun(row.id)" :disabled="loading">
                                            Dispatch
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Expandable Stops Row -->
                            <tr x-show="expandedRunId === row.id" x-cloak class="bg-slate-50/50 border-t border-slate-100">
                                <td :colspan="visibleColumnCount()" class="px-5 py-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                        <template x-for="stop in row.stops" :key="`stop-${row.id}-${stop.id}`">
                                            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-orange-200">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-bold text-slate-900 truncate" x-text="stop.recipient_name || '-'"></p>
                                                        <p class="text-[11px] text-slate-500 font-mono mt-0.5 truncate" x-text="stop.recipient_phone || '-'"></p>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[9px] font-black uppercase" :class="stopStatusClass(stop.status)" x-text="stop.status"></span>
                                                </div>
                                                <div class="mt-3 flex items-center justify-between gap-2">
                                                    <p class="text-[10px] text-slate-500">Attempts: <span class="font-bold text-slate-700" x-text="`${stop.attempts}/${stop.max_attempts}`"></span></p>
                                                    <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="resendCode(row.id, stop.id)" :disabled="!canResendCode(row, stop)">
                                                        Resend Code
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!row.stops || row.stops.length === 0">
                                            <div class="col-span-full text-center py-4 text-sm font-semibold text-slate-400">No stops recorded for this run.</div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Pagination -->
        <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs font-semibold text-slate-600">
                    Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> runs
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 sm:justify-end">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600">Rows</span>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">
                                <span x-text="perPage"></span>
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display: none;">
                                <button type="button" @click="perPage = 10; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 10 ? 'bg-slate-100' : ''">10</button>
                                <button type="button" @click="perPage = 25; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 25 ? 'bg-slate-100' : ''">25</button>
                                <button type="button" @click="perPage = 50; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 50 ? 'bg-slate-100' : ''">50</button>
                                <button type="button" @click="perPage = 100; meta.current_page = 1; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 100 ? 'bg-slate-100' : ''">100</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button @click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                        <button @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Create Run Modal -->
    <template x-if="showCreateModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" @click.self="showCreateModal = false" @keydown.escape.window="showCreateModal = false">
            <div class="w-full max-h-[85vh] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col" @click.stop
                 :class="createMode === 'items' ? 'max-w-4xl' : 'max-w-lg'">

                <div class="px-6 py-5 border-b border-slate-200/60">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Create Delivery Run</h3>
                                <p class="text-sm text-slate-500 mt-0.5">Select a mode to generate a new run.</p>
                            </div>
                        </div>
                        <button type="button" @click="showCreateModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="flex mt-6 bg-slate-100/80 rounded-xl p-1 gap-1">
                        <button
                            type="button"
                            @click="createMode = 'batch'"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-black transition-all"
                            :class="createMode === 'batch' ? 'bg-white text-orange-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            From Batch
                        </button>
                        <button
                            type="button"
                            @click="createMode = 'items'; loadEligibleItems()"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-black transition-all"
                            :class="createMode === 'items' ? 'bg-white text-orange-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            From Items
                        </button>
                    </div>
                </div>

                <!-- FROM BATCH -->
                <div x-show="createMode === 'batch'" class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Sealed Local-Delivery Batch</label>
                            <select x-model="newRunBatchId" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-colors">
                                <option value="">Select a sealed batch...</option>
                                <template x-for="batch in localDeliveryBatches" :key="batch.id">
                                    <option :value="batch.id" x-text="batch.batch_number"></option>
                                </template>
                            </select>
                            <p class="text-[11px] font-semibold text-slate-500 mt-2">Select a sealed batch to automatically map its items to a delivery run.</p>
                        </div>

                        <div x-show="localDeliveryBatches.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-6 text-center mt-2">
                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm font-bold text-slate-600">No sealed batches available</p>
                            <p class="text-[11px] font-medium text-slate-500 mt-1">Seal a local-delivery sort batch first, or switch to "From Items" mode.</p>
                        </div>
                    </div>
                </div>

                <!-- FROM ITEMS -->
                <template x-if="createMode === 'items'">
                    <div class="flex flex-col flex-1 min-h-[400px]">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
                            <div class="relative flex-1 max-w-sm">
                                <input type="text" x-model.debounce.350ms="eligibleSearch" @input="loadEligibleItems()" placeholder="Search shipment, item, recipient..." class="w-full px-4 py-2.5 pr-10 border-2 border-slate-200 rounded-xl bg-white text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100 placeholder-slate-400">
                                <svg class="absolute right-3.5 top-3 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-black bg-orange-100 text-orange-700">
                                <span x-text="selectedReceiptItemIds.length"></span>&nbsp;Selected
                            </span>
                        </div>

                        <div class="flex-1 overflow-auto">
                            <table class="min-w-full divide-y divide-slate-200/60 text-xs">
                                <thead class="bg-slate-50/70 sticky top-0 shadow-sm z-10">
                                    <tr>
                                        <th class="px-5 py-3 text-left w-10">
                                            <input type="checkbox" @change="toggleAllEligible($event)" :checked="selectedReceiptItemIds.length > 0 && selectedReceiptItemIds.length === eligibleItems.length" class="rounded border-slate-300">
                                        </th>
                                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-slate-500">Order / Item</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-slate-500">Destination</th>
                                        <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wider text-slate-500">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in eligibleItems" :key="row.warehouse_receipt_item_id">
                                        <tr class="hover:bg-orange-50/30 cursor-pointer transition-colors" @click="toggleItem(row.warehouse_receipt_item_id)">
                                            <td class="px-5 py-3">
                                                <input type="checkbox" :value="row.warehouse_receipt_item_id" x-model.number="selectedReceiptItemIds" @click.stop class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="font-bold text-slate-900 text-sm" x-text="row.shipment_number"></p>
                                                <p class="text-slate-600 mt-0.5" x-text="row.item_description"></p>
                                                <p class="text-[10px] font-mono font-semibold text-slate-400 mt-0.5" x-text="row.tracking_code || '-'"></p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="font-bold text-slate-800" x-text="row.destination?.recipient_name || '-'"></p>
                                                <p class="text-[10px] font-semibold text-slate-500 mt-0.5" x-text="row.destination?.town || '-'"></p>
                                                <p class="text-[10px] text-slate-400" x-text="(row.destination?.region || '-') + ' / ' + (row.destination?.district || '-')"></p>
                                            </td>
                                            <td class="px-4 py-3 text-center font-black text-slate-900 text-sm" x-text="row.received_quantity"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!eligibleLoading && eligibleItems.length === 0">
                                        <td colspan="4" class="px-4 py-12 text-center">
                                            <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="text-sm font-bold text-slate-600">No eligible items</p>
                                            <p class="text-xs font-semibold text-slate-500 mt-1">All received items have already been mapped.</p>
                                        </td>
                                    </tr>
                                    <tr x-show="eligibleLoading">
                                        <td colspan="4" class="px-4 py-12 text-center text-slate-500 text-xs font-semibold">Loading available items...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200/60 bg-slate-50/50 mt-auto">
                    <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>

                    <button x-show="createMode === 'batch'" type="button" @click="createRun()" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-50 disabled:shadow-none shadow-lg shadow-orange-600/20 transition-all" :disabled="loading || !newRunBatchId">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Create Run
                    </button>

                    <button x-show="createMode === 'items'" type="button" @click="createRunFromItems()" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-50 disabled:shadow-none shadow-lg shadow-orange-600/20 transition-all" :disabled="loading || selectedReceiptItemIds.length === 0">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Create Run (<span x-text="selectedReceiptItemIds.length"></span>)
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection