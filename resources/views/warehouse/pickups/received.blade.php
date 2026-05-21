@extends('warehouse.layouts.app')

@section('title', 'Received Pickups')
@section('page-title', 'Received Pickups')

@php
    $config = [
        'endpoint' => route('warehouse.pickups.received.data'),
        'statuses' => $statuses,
        'drivers' => $drivers->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values(),
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="warehouseReceivedPickupsPage" data-warehouse-received-pickups-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Received Pickups</h2>
                            <p class="truncate text-sm text-slate-500">Pickups already checked into {{ $warehouse->name ?? 'this warehouse' }}.</p>
                        </div>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700" x-text="meta.total + ' pickups'">0 pickups</span>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search order, driver, or phone..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4Z"/>
                        </svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                        <span x-show="activeFilterCount() > 0" class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] text-orange-700" x-text="activeFilterCount()"></span>
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2Z"/>
                            </svg>
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
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assigned Date</label>
                        <input type="text" x-ref="assignedDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Arrived Date</label>
                        <input type="text" x-ref="arrivedDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Receipt Status</label>
                        <select x-model="statusFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value">
                                <option :value="status.value" x-text="status.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Pickup Driver</label>
                        <select x-model="filters.driver_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All drivers</option>
                            <template x-for="driver in drivers" :key="driver.id">
                                <option :value="driver.id" x-text="driver.name + (driver.phone ? ' / ' + driver.phone : '')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Receipt Result</label>
                        <select x-model="filters.receipt_result" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All results</option>
                            <option value="matched">Matched</option>
                            <option value="discrepancy">Any discrepancy</option>
                            <option value="shortage">Shortage</option>
                            <option value="overage">Overage</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Receiving Notes</label>
                        <select x-model="filters.notes" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All notes states</option>
                            <option value="with_notes">With notes</option>
                            <option value="without_notes">Without notes</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Driver Picked Qty</label>
                        <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="filters.driver_qty_min" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <div class="w-px bg-slate-200"></div>
                            <input type="number" min="0" x-model="filters.driver_qty_max" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
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

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[1180px] divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <template x-for="column in columns" :key="column.key">
                                <th x-show="visibleColumns[column.key]" @@click="sort(column.key)" class="px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="[isSortable(column.key) ? 'cursor-pointer' : '', tableHeaderClass(column.key)]">
                                    <div class="flex items-center gap-1" :class="tableHeaderContentClass(column.key)">
                                        <span x-text="column.label"></span>
                                        <svg x-show="isSortable(column.key)" class="h-2.5 w-2.5" :class="sortBy === column.key ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
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
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No received pickups match the current filters</p>
                                        <button type="button" @@click="clearFilters()" class="text-xs font-semibold text-orange-600 hover:underline">Clear filters</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.shipment_number" class="px-4 py-3">
                                    <a :href="row.view_url" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="row.shipment_number || '-'"></a>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="px-4 py-3">
                                    <p class="font-bold text-slate-900" x-text="row.driver_name || '-'"></p>
                                    <p class="mt-1 font-mono text-[11px] text-slate-500" x-text="row.driver_phone || ''"></p>
                                </td>
                                <td x-show="visibleColumns.status" class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusBadgeClass(row.status)" x-text="row.status_label || row.status || '-'"></span>
                                </td>
                                <td x-show="visibleColumns.assigned_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.assigned_at)"></td>
                                <td x-show="visibleColumns.arrived_warehouse_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.arrived_warehouse_at)"></td>
                                <td x-show="visibleColumns.received_at" class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDisplayDate(row.received_at)"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                    <a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
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
                    <div class="px-4 py-12 text-center text-sm text-slate-400">No received pickups match the current filters.</div>
                </template>
                <template x-for="row in rows" :key="row.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="row.view_url" class="block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4" x-text="row.shipment_number || '-'"></a>
                                <p class="mt-1 text-sm font-bold text-slate-900" x-text="row.driver_name || '-'"></p>
                                <p class="font-mono text-[11px] text-slate-500" x-text="row.driver_phone || ''"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="statusBadgeClass(row.status)" x-text="row.status_label || row.status || '-'"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Assigned</p><p class="font-bold text-slate-800" x-text="formatDisplayDate(row.assigned_at)"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Arrived</p><p class="font-bold text-slate-800" x-text="formatDisplayDate(row.arrived_warehouse_at)"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Received</p><p class="font-bold text-slate-800" x-text="formatDisplayDate(row.received_at)"></p></div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
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
                            <select x-model.number="perPage" @@change="meta.current_page = 1; loadData()" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @@click="previousPage()" :disabled="meta.current_page <= 1" class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Prev</button>
                            <span class="text-xs font-black text-slate-600">Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></span>
                            <button type="button" @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
