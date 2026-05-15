@extends('warehouse.layouts.app')

@section('title', 'Collections')

@section('content')
@php
    $collectionsConfig = [
        'dataUrl' => route('warehouse.collections.data'),
        'handoverUrlTemplate' => route('warehouse.collections.handover', '__ID__'),
        'warehouseName' => $warehouse->name,
        'readyCount' => $readyCount,
        'collectedCount' => $collectedCount,
    ];
@endphp

<div
    class="space-y-5"
    x-data="collectionsPage()"
    x-init="init()"
    data-config='@json($collectionsConfig)'
>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7 10 17l-5-5"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Ready</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.ready_count ?? {{ $readyCount }}">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Collected</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.collected_count ?? {{ $collectedCount }}">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Viewing</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="statusLabel(status)">Ready</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16M4 12h16M4 18h7"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">This Page</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="rows.length || 0">0</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Collections</h2>
                        <p class="truncate text-sm text-slate-500">Self-pickup shipments waiting at {{ $warehouse->name }}.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full lg:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.400ms="currentPage = 1; fetchData()"
                            placeholder="Search..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                        >
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1">
                        <button type="button" @@click="setStatus('ready')" class="rounded-lg px-3.5 py-2 text-sm font-bold transition" :class="status === 'ready' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">Ready</button>
                        <button type="button" @@click="setStatus('collected')" class="rounded-lg px-3.5 py-2 text-sm font-bold transition" :class="status === 'collected' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">Collected</button>
                    </div>

                    <button type="button" @@click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                            View
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white p-2 shadow-2xl" style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2Z"/></svg>
                            Export
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white p-2 shadow-2xl" style="display: none;">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>

                    <button type="button" @@click="fetchData()" :disabled="loading" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50">
                        <svg class="h-4 w-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.64 18.36A9 9 0 0 0 18.36 5.64M18.36 5.64H14m4.36 0V10M5.64 18.36H10m-4.36 0V14"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Collection State</label>
                        <select x-model="filters.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="ready">Ready</option>
                            <option value="collected">Collected</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600" x-text="filters.status === 'collected' ? 'Collected Date' : 'Ready Date'"></label>
                        <input
                            type="text"
                            x-ref="collectionDateRange"
                            placeholder="Select date range"
                            readonly
                            class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                        >
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Item Range</label>
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
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.shipment" class="w-[15%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Shipment</th>
                            <th x-show="visibleColumns.vendor" class="w-[19%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Vendor</th>
                            <th x-show="visibleColumns.recipient" class="w-[22%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Recipient</th>
                            <th x-show="visibleColumns.items" class="w-[8%] px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Items</th>
                            <th x-show="visibleColumns.date" class="w-[15%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500" x-text="status === 'ready' ? 'Ready Since' : 'Collected At'"></th>
                            <th x-show="visibleColumns.collector" class="w-[14%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Collector</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/70 bg-transparent">
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600" x-text="status === 'ready' ? 'No shipments awaiting collection' : 'No collected shipments yet'"></p>
                                        <p class="text-xs font-semibold text-slate-400">Try changing your filters or search term.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.shipment" class="whitespace-nowrap px-4 py-3">
                                    <p class="font-bold text-slate-900" x-text="row.shipment_number || '-'"></p>
                                    <span class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="row.status === 'collected' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-orange-200 bg-orange-50 text-orange-700'" x-text="statusLabel(row.status)"></span>
                                </td>
                                <td x-show="visibleColumns.vendor" class="px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="row.vendor_name || '-'"></p>
                                </td>
                                <td x-show="visibleColumns.recipient" class="px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="row.recipient_name || '-'"></p>
                                    <a :href="'tel:' + row.recipient_phone" class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-orange-700">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .948.684l1.498 4.493a1 1 0 0 1-.502 1.21l-2.257 1.13a11 11 0 0 0 5.516 5.516l1.13-2.257a1 1 0 0 1 1.21-.502l4.493 1.498a1 1 0 0 1 .684.949V19a2 2 0 0 1-2 2h-1C9.716 21 3 14.284 3 6V5Z"/></svg>
                                        <span x-text="row.recipient_phone || '-'"></span>
                                    </a>
                                </td>
                                <td x-show="visibleColumns.items" class="whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="row.items_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.date" class="whitespace-nowrap px-4 py-3">
                                    <p class="font-semibold text-slate-700" x-text="row.status === 'collected' ? (row.collected_at || '-') : (row.ready_at || '-')"></p>
                                </td>
                                <td x-show="visibleColumns.collector" class="px-4 py-3">
                                    <template x-if="row.status === 'collected'">
                                        <div>
                                            <p class="font-semibold text-slate-900" x-text="row.collected_by_name || '-'"></p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-500" x-text="row.collected_by_phone || row.handed_over_by || '-'"></p>
                                        </div>
                                    </template>
                                    <template x-if="row.status !== 'collected'">
                                        <span class="text-slate-400">-</span>
                                    </template>
                                </td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                    <button x-show="row.status === 'ready'" type="button" @@click="openHandover(row)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">Hand Over</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="!loading && rows.length === 0">
                    <div class="px-4 py-12 text-center">
                        <p class="text-sm font-bold text-slate-600" x-text="status === 'ready' ? 'No shipments awaiting collection' : 'No collected shipments yet'"></p>
                        <p class="mt-1 text-xs font-semibold text-slate-400">Try changing your filters or search term.</p>
                    </div>
                </template>
                <template x-for="row in rows" :key="row.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-extrabold text-slate-900" x-text="row.shipment_number || '-'"></p>
                                <p class="mt-1 text-xs font-bold text-slate-500" x-text="row.vendor_name || '-'"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="row.status === 'collected' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-orange-200 bg-orange-50 text-orange-700'" x-text="statusLabel(row.status)"></span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Recipient</p><p class="font-bold text-slate-800" x-text="row.recipient_name || '-'"></p><a :href="'tel:' + row.recipient_phone" class="text-slate-500" x-text="row.recipient_phone || '-'"></a></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Items</p><p class="font-bold text-slate-800" x-text="row.items_count || 0"></p></div>
                            <div class="col-span-2"><p class="font-black uppercase tracking-wide text-slate-400" x-text="row.status === 'collected' ? 'Collected At' : 'Ready Since'"></p><p class="font-bold text-slate-800" x-text="row.status === 'collected' ? (row.collected_at || '-') : (row.ready_at || '-')"></p></div>
                            <div x-show="row.status === 'collected'" class="col-span-2"><p class="font-black uppercase tracking-wide text-slate-400">Collector</p><p class="font-bold text-slate-800" x-text="row.collected_by_name || '-'"></p><p class="text-slate-500" x-text="row.collected_by_phone || row.handed_over_by || '-'"></p></div>
                        </div>

                        <div x-show="row.status === 'ready'" class="mt-4">
                            <button type="button" @@click="openHandover(row)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Hand Over</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @@click="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                        <button @@click="goToPage(currentPage + 1)" :disabled="currentPage >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-show="handoverOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[120] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
            style="display: none;"
            @@click.self="closeHandover()"
            @@keydown.escape.window="closeHandover()"
        >
            <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]" @@click.stop>
                <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg shadow-emerald-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-extrabold text-slate-900">Hand Over Shipment</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Record who collected the self-pickup shipment.</p>
                        </div>
                    </div>
                    <button type="button" @@click="closeHandover()" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50/70 p-5">
                    <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-orange-700">Shipment</p>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-base font-black text-slate-900" x-text="handoverRow?.shipment_number || '-'"></p>
                                <p class="mt-0.5 text-sm font-semibold text-orange-900" x-text="(handoverRow?.recipient_name || '-') + ' · ' + (handoverRow?.recipient_phone || '-')"></p>
                            </div>
                            <span class="shrink-0 rounded-xl bg-white/70 px-3 py-2 text-xs font-black text-orange-800 ring-1 ring-orange-100" x-text="(handoverRow?.items_count || 0) + ' item(s)'"></span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Package Photos</p>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Check package photos before handing over.</p>
                            </div>
                            <span class="shrink-0 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700" x-text="handoverPhotoCount() + ' photo(s)'"></span>
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-for="pkg in (handoverRow?.packages || [])" :key="pkg.id">
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-900" x-text="pkg.description || 'Package'"></p>
                                        <p class="mt-0.5 truncate font-mono text-[11px] font-semibold text-slate-500" x-text="pkg.tracking_code || 'No tracking code'"></p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span class="rounded-lg bg-white px-2 py-1 text-[11px] font-black text-slate-600 ring-1 ring-slate-200" x-text="'Qty ' + (pkg.quantity || 0)"></span>
                                        <button
                                            type="button"
                                            x-show="packagePhotos(pkg).length"
                                            @@click="openPhotoViewer(pkg)"
                                            class="whitespace-nowrap text-xs font-black text-orange-700 underline decoration-orange-300 underline-offset-4 transition hover:text-orange-800"
                                        >
                                            View Photos
                                        </button>
                                        <span x-show="!packagePhotos(pkg).length" class="text-xs font-bold text-slate-400">No photos</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Collector Name <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="handoverForm.collected_by_name" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Collector Phone <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="handoverForm.collected_by_phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">ID Type</label>
                            <select x-model="handoverForm.collected_by_id_type" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">None</option>
                                <option value="national_id">Ghana Card</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="voter_id">Voter ID</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">ID Number</label>
                            <input type="text" x-model="handoverForm.collected_by_id_number" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                            <textarea x-model="handoverForm.notes" rows="3" placeholder="Add handover notes..." class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div x-show="handoverError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2">
                        <p class="text-xs font-semibold text-rose-700" x-text="handoverError"></p>
                    </div>
                </div>

                <div class="shrink-0 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button" @@click="closeHandover()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                    <button
                        type="button"
                        @@click="submitHandover()"
                        :disabled="handoverSubmitting || !handoverForm.collected_by_name || !handoverForm.collected_by_phone"
                        class="rounded-xl border-2 border-emerald-600 bg-emerald-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-emerald-600/20 transition hover:border-emerald-700 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                        x-text="handoverSubmitting ? 'Saving...' : 'Confirm Handover'"
                    ></button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <template x-if="photoViewer.open && photoViewer.package">
            <div class="fixed inset-0 z-[230] flex min-h-dvh w-screen items-center justify-center bg-slate-950/95 p-3" @@keydown.escape.window="closePhotoViewer()" @@keydown.arrow-right.window="nextViewerPhoto()" @@keydown.arrow-left.window="previousViewerPhoto()">
                <button type="button" class="absolute inset-0 cursor-zoom-out" @@click="closePhotoViewer()" aria-label="Close photo viewer"></button>

                <div class="pointer-events-none absolute left-0 right-0 top-0 z-10 bg-gradient-to-b from-slate-950 via-slate-950/70 to-transparent px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 text-white">
                            <h3 class="truncate text-base font-black sm:text-xl" x-text="photoViewer.package.description || 'Package photos'"></h3>
                            <p class="mt-1 truncate font-mono text-xs font-black text-slate-300 sm:text-sm" x-text="photoViewer.package.tracking_code || 'No tracking code'"></p>
                        </div>
                        <button type="button" @@click="closePhotoViewer()" class="pointer-events-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition hover:bg-white/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div @@click.stop class="relative z-[1] flex h-full w-full items-center justify-center px-1 py-24 sm:px-14">
                    <template x-if="currentViewerPhoto()">
                        <img :src="currentViewerPhoto().url" :alt="currentViewerPhoto().original_name || 'Package photo'" class="max-h-full max-w-full object-contain shadow-2xl">
                    </template>

                    <button type="button" x-show="viewerPhotos().length > 1" @@click="previousViewerPhoto()" class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 sm:left-5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" x-show="viewerPhotos().length > 1" @@click="nextViewerPhoto()" class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 sm:right-5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                    </button>
                </div>

                <div class="absolute bottom-0 left-0 right-0 z-10 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent px-4 pb-4 pt-10 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <p class="text-xs font-black text-slate-300">
                            <span x-text="currentViewerPhoto()?.source || 'Photo'"></span>
                            <span> photo </span>
                            <span x-text="photoViewer.index + 1"></span>
                            <span> of </span>
                            <span x-text="viewerPhotos().length"></span>
                        </p>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <template x-for="(photo, index) in viewerPhotos()" :key="`${photo.source || 'photo'}-${photo.id || index}`">
                                <button type="button" @@click="selectViewerPhoto(index)" class="h-14 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-900 transition" :class="index === photoViewer.index ? 'border-orange-500 opacity-100' : 'border-white/20 opacity-60 hover:opacity-100'">
                                    <img :src="photo.url" :alt="photo.original_name || 'Package photo'" class="h-full w-full object-cover">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </template>
</div>

@endsection

@push('scripts')
<script>
function collectionsPage() {
    const config = JSON.parse(document.querySelector('[data-config]').getAttribute('data-config'));

    return {
        rows: [],
        loading: true,
        showFilters: false,
        status: 'ready',
        search: '',
        currentPage: 1,
        meta: { total: 0, ready_count: config.readyCount || 0, collected_count: config.collectedCount || 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        filters: {
            status: 'ready',
            date_from: '',
            date_to: '',
            items_min: '',
            items_max: '',
        },
        columns: [
            { key: 'shipment', label: 'Shipment' },
            { key: 'vendor', label: 'Vendor' },
            { key: 'recipient', label: 'Recipient' },
            { key: 'items', label: 'Items' },
            { key: 'date', label: 'Date' },
            { key: 'collector', label: 'Collector' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            shipment: true,
            vendor: true,
            recipient: true,
            items: true,
            date: true,
            collector: true,
            actions: true,
        },

        handoverOpen: false,
        handoverRow: null,
        handoverSubmitting: false,
        handoverError: '',
        handoverForm: { collected_by_name: '', collected_by_phone: '', collected_by_id_type: '', collected_by_id_number: '', notes: '' },
        photoViewer: { open: false, package: null, index: 0 },

        init() {
            this.fetchData();
            this.$nextTick(() => this.initDateRange());
        },

        statusLabel(value) {
            if (value === 'collected') return 'Collected';
            return 'Ready';
        },

        setStatus(value) {
            this.status = value;
            this.filters.status = value;
            this.currentPage = 1;
            this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.meta.per_page,
                    status: this.status,
                    search: this.search,
                });
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.set(key, value);
                    }
                });

                const response = await fetch(config.dataUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const json = await response.json();
                this.rows = json.data || [];
                this.meta = { ...this.meta, ...(json.meta || {}) };
                this.currentPage = this.meta.current_page || this.currentPage;
                this.status = this.filters.status || this.status;
            } catch (e) {
                console.error('Failed to fetch collections', e);
                window.showToast?.('Unable to load collections.', 'error');
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.status = this.filters.status || 'ready';
            this.currentPage = 1;
            this.fetchData();
        },

        clearFilters() {
            this.filters = { status: this.status, date_from: '', date_to: '', items_min: '', items_max: '' };
            if (this.$refs.collectionDateRange) {
                this.$refs.collectionDateRange.value = '';
            }
            this.currentPage = 1;
            this.fetchData();
        },

        goToPage(page) {
            if (page < 1 || page > this.meta.last_page || this.loading) return;
            this.currentPage = page;
            this.fetchData();
        },

        openHandover(row) {
            this.handoverRow = row;
            this.handoverForm = {
                collected_by_name: row.recipient_name || '',
                collected_by_phone: row.recipient_phone || '',
                collected_by_id_type: '',
                collected_by_id_number: '',
                notes: '',
            };
            this.handoverError = '';
            this.handoverOpen = true;
        },

        closeHandover(force = false) {
            if (this.handoverSubmitting && !force) return;
            this.handoverOpen = false;
            this.handoverRow = null;
            this.handoverError = '';
        },

        async submitHandover() {
            if (!this.handoverRow) return;
            this.handoverSubmitting = true;
            this.handoverError = '';
            try {
                const url = config.handoverUrlTemplate.replace('__ID__', this.handoverRow.shipment_id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.handoverForm),
                });
                const json = await response.json();
                if (!response.ok || json.success === false) {
                    if (json.errors) {
                        throw new Error(Object.values(json.errors).flat().join(', '));
                    }
                    throw new Error(json.message || 'Failed to record handover.');
                }

                this.closeHandover(true);
                window.showToast?.(json.message || 'Shipment handed over successfully.', 'success');
                this.fetchData();
            } catch (e) {
                this.handoverError = e.message || 'An unexpected error occurred.';
            } finally {
                this.handoverSubmitting = false;
            }
        },

        toggleColumn(key) {
            if (key === 'actions') return;
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length || 1;
        },

        packagePhotos(pkg) {
            return pkg?.photos?.primary || [];
        },

        handoverPhotoCount() {
            return (this.handoverRow?.packages || []).reduce((total, pkg) => total + this.packagePhotos(pkg).length, 0);
        },

        openPhotoViewer(pkg, index = 0) {
            if (!this.packagePhotos(pkg).length) return;
            this.photoViewer = { open: true, package: pkg, index };
        },

        closePhotoViewer() {
            this.photoViewer = { open: false, package: null, index: 0 };
        },

        viewerPhotos() {
            return this.packagePhotos(this.photoViewer.package);
        },

        currentViewerPhoto() {
            return this.viewerPhotos()[this.photoViewer.index] || null;
        },

        nextViewerPhoto() {
            const photos = this.viewerPhotos();
            if (!photos.length) return;
            this.photoViewer.index = (this.photoViewer.index + 1) % photos.length;
        },

        previousViewerPhoto() {
            const photos = this.viewerPhotos();
            if (!photos.length) return;
            this.photoViewer.index = (this.photoViewer.index - 1 + photos.length) % photos.length;
        },

        selectViewerPhoto(index) {
            this.photoViewer.index = index;
        },

        exportRows() {
            return this.rows.map((row) => ({
                Shipment: row.shipment_number || '',
                Vendor: row.vendor_name || '',
                Recipient: row.recipient_name || '',
                Phone: row.recipient_phone || '',
                Items: row.items_count || 0,
                Status: this.statusLabel(row.status),
                Date: row.status === 'collected' ? (row.collected_at || '') : (row.ready_at || ''),
                Collector: row.collected_by_name || '',
                'Collector Phone': row.collected_by_phone || '',
                'Handed Over By': row.handed_over_by || '',
            }));
        },

        exportData(type) {
            const rows = this.exportRows();
            if (!rows.length) {
                window.showToast?.('No collections to export.', 'warning');
                return;
            }

            if (type === 'print') {
                const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
                const htmlRows = rows.map((row) => `<tr>${Object.values(row).map((value) => `<td>${escapeHtml(value)}</td>`).join('')}</tr>`).join('');
                const win = window.open('', '_blank');
                win.document.write(`<html><head><title>Collections</title><style>body{font-family:Arial,sans-serif;padding:24px}table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #e2e8f0;padding:8px;text-align:left}th{background:#f8fafc}</style></head><body><h1>Collections</h1><table><thead><tr>${Object.keys(rows[0]).map((key) => `<th>${key}</th>`).join('')}</tr></thead><tbody>${htmlRows}</tbody></table></body></html>`);
                win.document.close();
                win.print();
                return;
            }

            const headers = Object.keys(rows[0]);
            const csv = [headers.join(','), ...rows.map((row) => headers.map((header) => `"${String(row[header] ?? '').replace(/"/g, '""')}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `collections-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        },

        initDateRange() {
            const setupPicker = () => {
                if (!this.$refs.collectionDateRange) return;
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.collectionDateRange);
                if ($input.data('daterangepicker')) return;

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'left',
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    ranges: {
                        Today: [window.moment(), window.moment()],
                        Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });

                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.filters.date_from = picker.startDate.format('YYYY-MM-DD');
                    this.filters.date_to = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.filters.date_from} - ${this.filters.date_to}`);
                });

                $input.on('cancel.daterangepicker', () => {
                    this.filters.date_from = '';
                    this.filters.date_to = '';
                    $input.val('');
                });
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            const cssId = 'daterangepicker-css';
            if (!document.getElementById(cssId)) {
                const link = document.createElement('link');
                link.id = cssId;
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                document.head.appendChild(link);
            }

            const loadScript = (id, src) => new Promise((resolve) => {
                if (document.getElementById(id)) return resolve();
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = () => resolve();
                document.body.appendChild(script);
            });

            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                .then(() => loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js'))
                .then(setupPicker);
        },
    };
}
</script>
@endpush
