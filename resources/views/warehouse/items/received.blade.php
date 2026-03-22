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
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Received Items</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Item-level warehouse receipt records finalized in this warehouse.</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Records'"></span>
            </div>
        </div>

        <div class="p-6 pb-0">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-wrap gap-3 items-center">
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search shipment, item, driver..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    {{-- Date Range --}}
                    <div class="relative w-full sm:w-52">
                        <input
                            type="text"
                            x-ref="dateRange"
                            placeholder="Date range"
                            class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 cursor-pointer"
                            readonly
                        >
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
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

                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
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
        </div>

        <div class="px-6 py-4">
            <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
                <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

                <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    SHIPMENT #
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.item_description" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ITEM</th>
                            <th x-show="visibleColumns.expected_quantity" @@click="sort('expected_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center">
                                    EXPECTED QTY
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'expected_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.received_quantity" @@click="sort('received_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center">
                                    RECEIVED QTY
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'received_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.damaged_quantity" @@click="sort('damaged_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center">
                                    DAMAGED QTY
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'damaged_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.driver_name" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DRIVER</th>
                            <th x-show="visibleColumns.received_at" @@click="sort('received_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    RECEIVED AT
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'received_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.discrepancy_type" @@click="sort('discrepancy_type')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center">
                                    DISCREPANCY
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'discrepancy_type' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.notes" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No received items found.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.shipment_number" class="px-4 py-2.5 text-xs font-semibold text-slate-900" x-text="row.shipment_number || '-'"></td>
                                <td x-show="visibleColumns.item_description" class="px-4 py-2.5 text-xs text-slate-600 max-w-[180px] truncate" x-text="row.item_description || '-'"></td>
                                <td x-show="visibleColumns.expected_quantity" class="px-4 py-2.5 text-xs text-slate-600 tabular-nums text-center" x-text="row.expected_quantity ?? 0"></td>
                                <td x-show="visibleColumns.received_quantity" class="px-4 py-2.5 text-xs text-slate-600 tabular-nums text-center" x-text="row.received_quantity ?? 0"></td>
                                <td x-show="visibleColumns.damaged_quantity" class="px-4 py-2.5 text-xs tabular-nums text-center">
                                    <span :class="(row.damaged_quantity ?? 0) > 0 ? 'text-rose-600 font-semibold' : 'text-slate-600'" x-text="row.damaged_quantity ?? 0"></span>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.driver_name || '-'"></td>
                                <td x-show="visibleColumns.received_at" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.received_at || '-'"></td>
                                <td x-show="visibleColumns.discrepancy_type" class="px-4 py-2.5 text-xs text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm shadow-sm"
                                        :class="discrepancyBadgeClass(row.discrepancy_type)"
                                        x-text="discrepancyLabel(row.discrepancy_type)"
                                    ></span>
                                </td>
                                <td x-show="visibleColumns.notes" class="px-4 py-2.5 text-xs text-slate-600 max-w-[160px] truncate" x-text="row.notes || '-'"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-right">
                                    <a :href="row.view_url" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-semibold text-orange-800 bg-orange-50 border border-orange-200/60 rounded-lg hover:bg-orange-50 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing
                            <span x-text="meta.from || 0"></span>
                            to
                            <span x-text="meta.to || 0"></span>
                            of
                            <span x-text="meta.total || 0"></span>
                            results
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button
                                        type="button"
                                        @@click="open = !open"
                                        class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"
                                    >
                                        <span x-text="perPage"></span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        @@click.away="open = false"
                                        x-transition
                                        class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                        style="display: none;"
                                    >
                                        <button type="button" @@click="setPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                        <button type="button" @@click="setPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                        <button type="button" @@click="setPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        <button type="button" @@click="setPerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs font-medium text-slate-600">
                                Page
                                <span x-text="meta.current_page || 1"></span>
                                of
                                <span x-text="meta.last_page || 1"></span>
                            </div>

                            <div class="flex space-x-1">
                                <button
                                    @@click="firstPage()"
                                    :disabled="meta.current_page <= 1"
                                    :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button
                                    @@click="previousPage()"
                                    :disabled="meta.current_page <= 1"
                                    :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button
                                    @@click="nextPage()"
                                    :disabled="meta.current_page >= meta.last_page"
                                    :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                                <button
                                    @@click="lastPage()"
                                    :disabled="meta.current_page >= meta.last_page"
                                    :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
