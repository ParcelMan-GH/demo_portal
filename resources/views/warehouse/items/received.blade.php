@extends('warehouse.layouts.app')

@section('title', 'Received Items')
@section('page-title', 'Received Items')

@php
    $config = [
        'endpoint' => route('warehouse.items.received.data'),
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseReceivedItemsPage" data-warehouse-received-items-config="{{ e(json_encode($config)) }}">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Received Items</h2>
                <p class="text-sm text-slate-500">Item-level warehouse receipt records finalized in this warehouse.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" x-text="meta.total + ' Records'"></span>
        </div>

        <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full md:max-w-xs">
                <input type="text" x-model="search" @@input.debounce.400ms="meta.current_page = 1; loadData()" placeholder="Search shipment, item, driver..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40">
                <svg class="pointer-events-none absolute right-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <div class="relative" x-data="{ open: false }">
                    <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                        View
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" class="absolute right-0 z-30 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <template x-for="column in columns" :key="column.key">
                            <button type="button" @@click="toggleColumn(column.key)" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 flex items-center justify-between">
                                <span x-text="column.label"></span>
                                <svg x-show="visibleColumns[column.key]" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" class="absolute right-0 z-30 mt-2 w-40 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">CSV</button>
                        <button type="button" @@click="exportData('pdf'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">PDF</button>
                        <button type="button" @@click="exportData('print'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Print</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 relative">
            <div x-show="loading" x-cloak class="absolute inset-0 z-10 bg-white/65 backdrop-blur-[1px]"></div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Shipment #</th>
                        <th x-show="visibleColumns.item_description" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Item</th>
                        <th x-show="visibleColumns.expected_quantity" @@click="sort('expected_quantity')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Expected Qty</th>
                        <th x-show="visibleColumns.received_quantity" @@click="sort('received_quantity')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Received Qty</th>
                        <th x-show="visibleColumns.damaged_quantity" @@click="sort('damaged_quantity')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Damaged Qty</th>
                        <th x-show="visibleColumns.driver_name" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Driver</th>
                        <th x-show="visibleColumns.received_at" @@click="sort('received_at')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Received At</th>
                        <th x-show="visibleColumns.discrepancy_type" @@click="sort('discrepancy_type')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Discrepancy</th>
                        <th x-show="visibleColumns.notes" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr x-show="!loading && rows.length === 0" x-cloak>
                        <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center text-sm text-slate-500">No received items found.</td>
                    </tr>
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.shipment_number" class="px-4 py-3 font-semibold text-slate-900" x-text="row.shipment_number || '-' "></td>
                            <td x-show="visibleColumns.item_description" class="px-4 py-3 text-slate-700" x-text="row.item_description || '-' "></td>
                            <td x-show="visibleColumns.expected_quantity" class="px-4 py-3 text-slate-700" x-text="row.expected_quantity ?? 0"></td>
                            <td x-show="visibleColumns.received_quantity" class="px-4 py-3 text-slate-700" x-text="row.received_quantity ?? 0"></td>
                            <td x-show="visibleColumns.damaged_quantity" class="px-4 py-3 text-slate-700" x-text="row.damaged_quantity ?? 0"></td>
                            <td x-show="visibleColumns.driver_name" class="px-4 py-3 text-slate-700" x-text="row.driver_name || '-' "></td>
                            <td x-show="visibleColumns.received_at" class="px-4 py-3 text-slate-600" x-text="row.received_at || '-' "></td>
                            <td x-show="visibleColumns.discrepancy_type" class="px-4 py-3 text-slate-600" x-text="row.discrepancy_type || 'none'"></td>
                            <td x-show="visibleColumns.notes" class="px-4 py-3 text-slate-600" x-text="row.notes || '-' "></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600" x-text="`Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total || 0} records`"></p>
                <div class="flex items-center gap-3 justify-end">
                    <div class="flex items-center gap-2 text-sm text-slate-600"><span>Rows</span><select x-model.number="perPage" @@change="setPerPage($event.target.value)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></div>
                    <span class="text-sm text-slate-700" x-text="`Page ${meta.current_page || 1} of ${meta.last_page || 1}`"></span>
                    <div class="flex items-center gap-1"><button @@click="firstPage()" :disabled="meta.current_page <= 1" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#171;</button><button @@click="previousPage()" :disabled="meta.current_page <= 1" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#8249;</button><button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#8250;</button><button @@click="lastPage()" :disabled="meta.current_page >= meta.last_page" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#187;</button></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
