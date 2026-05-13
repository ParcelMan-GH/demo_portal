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
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm">
        <div class="relative px-4 py-5 sm:px-6">
            <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.24),transparent_58%)]"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div class="min-w-0 max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-orange-200">
                        Warehouse Intake
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">Incoming Packages</h1>
                    <p class="mt-2 text-sm font-medium leading-6 text-slate-300">Packages assigned to this warehouse and waiting to be checked in by the receiving team.</p>
                </div>
                <div class="shrink-0 rounded-xl border border-white/10 bg-white/10 px-3 py-3 text-right">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Records</p>
                    <p class="mt-1 text-2xl font-black text-white" x-text="meta.total || 0"></p>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="relative sm:col-span-1">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search shipment or driver"
                            class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 pr-10 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                        >
                        <svg class="absolute right-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="inline-flex w-full items-center justify-between rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                        >
                            <span x-text="statusFilterName"></span>
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            x-cloak
                            @@click.away="open = false"
                            x-transition
                            class="absolute right-0 z-50 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                            style="display: none;"
                        >
                            <button type="button" @@click="setStatusFilter('', 'All statuses'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50" :class="statusFilter === '' ? 'bg-orange-50 text-orange-700' : ''">All statuses</button>
                            <template x-for="status in statuses" :key="status.value">
                                <button type="button" @@click="setStatusFilter(status.value, status.label); open = false" class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50" :class="statusFilter === status.value ? 'bg-orange-50 text-orange-700' : ''" x-text="status.label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            x-ref="dateRange"
                            placeholder="Date range"
                            class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 pl-10 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                            readonly
                        >
                        <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" style="display: none;">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
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
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Shipment</p>
                                <a :href="row.view_url" class="mt-1 block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="row.shipment_number || '-'"></a>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(row.status)" x-text="row.status_label || row.status || '-'"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Driver</p>
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
                            <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="cursor-pointer px-4 py-3">Shipment #</th>
                            <th x-show="visibleColumns.driver_name" @@click="sort('driver_name')" class="cursor-pointer px-4 py-3">Driver</th>
                            <th x-show="visibleColumns.driver_phone" class="px-4 py-3">Driver Phone</th>
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
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700">
                                    <span x-text="perPage"></span>
                                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display: none;">
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
    </section>
</div>
@endsection
