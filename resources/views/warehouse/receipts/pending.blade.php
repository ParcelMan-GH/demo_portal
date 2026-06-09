@extends('warehouse.layouts.app')

@section('title', 'Incoming Packages')
@section('page-title', 'Incoming Packages')

@php
    $config = [
        'endpoint' => route('warehouse.receipts.pending.data'),
        'statuses' => $statuses,
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="warehousePendingReceiptsPage" data-warehouse-pending-receipts-config="{{ e(json_encode($config)) }}">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-orange-100 bg-orange-50 text-orange-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v8H3V7Zm11 3h3l3 3v2h-6v-5ZM7 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Incoming Packages</h1>
                        <p class="mt-1 text-sm font-medium text-slate-500">Packages assigned to this warehouse and waiting for receipt.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4 border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="grid gap-4 xl:grid-cols-[minmax(360px,520px)_auto] xl:items-end xl:justify-between">
                <div class="relative w-full">
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Search</span>
                        <span class="relative block">
                            <input
                                type="text"
                                x-model="search"
                                @@input.debounce.500ms="meta.current_page = 1; loadData()"
                                placeholder="Search package, order, or rider..."
                                class="h-14 w-full rounded-2xl border-2 border-slate-200 bg-white px-4 pl-11 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                            >
                            <svg class="absolute left-4 top-4 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                        </span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @@click="filtersOpen = !filtersOpen" class="inline-flex h-14 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-5 text-sm font-black text-slate-700 shadow-md shadow-slate-200/60 transition hover:bg-slate-50">
                        <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18l-7 8v6l-4 2v-8L3 4Z"/>
                        </svg>
                        Filters
                        <span x-show="activeFilterCount() > 0" class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] text-orange-700" x-text="activeFilterCount()"></span>
                    </button>

                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex h-14 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-5 text-sm font-black text-slate-700 shadow-md shadow-slate-200/60 transition hover:bg-slate-50">
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white/70">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex h-14 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-5 text-sm font-black text-slate-700 shadow-md shadow-slate-200/60 transition hover:bg-slate-50">
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2Z"/>
                            </svg>
                            Export
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display: none;">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="filtersOpen" x-cloak x-transition class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Receipt Status</span>
                        <select x-model="statusFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value">
                                <option :value="status.value" x-text="status.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Assigned Date</span>
                        <span class="relative block">
                            <input
                                type="text"
                                x-ref="dateRange"
                                placeholder="Select date range"
                                class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/70 px-3 pl-10 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50"
                                readonly
                            >
                            <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                    </label>
                </div>

                <div class="mt-4 flex flex-col gap-2 border-t border-slate-200/70 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" @@click="filtersOpen = false" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                        Close Filters
                    </button>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" @@click="clearFilters()" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                            Clear Filters
                        </button>
                        <button type="button" @@click="applyFilters()" class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-950/15 transition hover:bg-slate-800">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px]"></div>

            <div class="block divide-y divide-slate-100 md:hidden">
                <div x-show="!loading && rows.length === 0" x-cloak class="px-4 py-10 text-center">
                    <p class="text-sm font-bold text-slate-500">No incoming packages found.</p>
                </div>
                <template x-for="row in rows" :key="row.id">
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order</p>
                                <a :href="row.view_url" class="mt-1 block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="row.shipment_number || '-'"></a>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(row.status)" x-text="row.status_label || row.status || '-'"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Rider</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="row.driver_name || '-'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                                <p class="mt-1 truncate font-mono text-sm font-bold text-slate-800" x-text="row.driver_phone || '-'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Assigned</p>
                                <p class="mt-1 text-sm font-bold text-slate-800" x-text="formatDisplayDate(row.assigned_at)"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Arrived</p>
                                <p class="mt-1 text-sm font-bold text-slate-800" x-text="formatDisplayDate(row.arrived_warehouse_at)"></p>
                            </div>
                        </div>

                        <a :href="row.view_url" class="mt-4 inline-flex w-full items-center justify-center rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/15 transition hover:border-orange-700 hover:bg-orange-700">
                            Open
                        </a>
                    </article>
                </template>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[920px] w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                        <tr>
                            <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="cursor-pointer px-4 py-3">Order #</th>
                            <th x-show="visibleColumns.driver_name" @@click="sort('driver_name')" class="cursor-pointer px-4 py-3">Rider</th>
                            <th x-show="visibleColumns.driver_phone" class="px-4 py-3">Rider Phone</th>
                            <th x-show="visibleColumns.status" @@click="sort('status')" class="cursor-pointer px-4 py-3 text-center">Status</th>
                            <th x-show="visibleColumns.assigned_at" @@click="sort('assigned_at')" class="cursor-pointer px-4 py-3">Assigned At</th>
                            <th x-show="visibleColumns.arrived_warehouse_at" @@click="sort('arrived_warehouse_at')" class="cursor-pointer px-4 py-3">Arrived Warehouse At</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center text-sm font-bold text-slate-500">No incoming packages found.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="transition hover:bg-orange-50/20">
                                <td x-show="visibleColumns.shipment_number" class="px-4 py-3">
                                    <a :href="row.view_url" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="row.shipment_number || '-' "></a>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="px-4 py-3 text-sm font-bold text-slate-800" x-text="row.driver_name || '-' "></td>
                                <td x-show="visibleColumns.driver_phone" class="px-4 py-3 font-mono text-xs font-bold text-slate-600" x-text="row.driver_phone || '-' "></td>
                                <td x-show="visibleColumns.status" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(row.status)" x-text="row.status_label || row.status || '-' "></span>
                                </td>
                                <td x-show="visibleColumns.assigned_at" class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="formatDisplayDate(row.assigned_at)"></td>
                                <td x-show="visibleColumns.arrived_warehouse_at" class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="formatDisplayDate(row.arrived_warehouse_at)"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-3 text-right">
                                    <a :href="row.view_url" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-700">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
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
    </section>
</div>
@endsection
