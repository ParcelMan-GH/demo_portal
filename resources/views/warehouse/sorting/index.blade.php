@extends('warehouse.layouts.app')

@section('title', 'Sorting')
@section('page-title', 'Sorting')

@php
    $config = [
        'items_endpoint' => route('warehouse.sorting.items.data'),
        'batches_endpoint' => route('warehouse.sorting.batches.data'),
        'create_batch_endpoint' => route('warehouse.sorting.batches.store'),
        'add_items_endpoint' => route('warehouse.sorting.batches.items.store', ['sortBatch' => '__BATCH__']),
        'remove_item_endpoint' => route('warehouse.sorting.batches.items.destroy', ['sortBatch' => '__BATCH__', 'shipmentItem' => '__ITEM__']),
        'seal_batch_endpoint' => route('warehouse.sorting.batches.seal', ['sortBatch' => '__BATCH__']),
        'reopen_batch_endpoint' => route('warehouse.sorting.batches.reopen', ['sortBatch' => '__BATCH__']),
        'can_reopen_batches' => (bool) ($canReopenBatches ?? false),
        'dispatch_modes' => [
            ['value' => 'transfer', 'label' => 'Transfer to Warehouse'],
            ['value' => 'local_delivery', 'label' => 'Local Delivery'],
        ],
        'destinations' => $destinationWarehouses->values(),
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseSortingPage" data-warehouse-sorting-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">

    {{-- ── Batches Datatable ────────────────────────────────────────────────── --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">

        {{-- Card header --}}
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-100">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Sort Batches</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Manage batches and assign received items to destinations.</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Records'">0 Records</span>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search batch number, destination…"
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-violet-400/50 focus:border-violet-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    {{-- Status filter --}}
                    <div class="relative w-full sm:w-44" x-data="{ open: false }">
                        <button type="button" @@click="open = !open"
                                class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                            <span x-text="statusFilterName">All statuses</span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display:none">
                            <button type="button" @@click="setStatusFilter('', 'All statuses'); open = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">All statuses</button>
                            <button type="button" @@click="setStatusFilter('open', 'Open'); open = false"
                                    class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="statusFilter === 'open' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Open</button>
                            <button type="button" @@click="setStatusFilter('sealed', 'Sealed'); open = false"
                                    class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="statusFilter === 'sealed' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Sealed</button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    {{-- New Batch --}}
                    <button type="button" @@click="createBatchModalOpen = true"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-sm font-semibold text-white shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Batch
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="px-6 py-4">
            <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
                <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

                <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.batch_number" @@click="sort('batch_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center gap-1">BATCH #
                                    <svg class="w-2.5 h-2.5" :class="sortBy === 'batch_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.dispatch_mode_label" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">MODE</th>
                            <th x-show="visibleColumns.destination" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESTINATION</th>
                            <th x-show="visibleColumns.status" @@click="sort('status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center gap-1">STATUS
                                    <svg class="w-2.5 h-2.5" :class="sortBy === 'status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.items_count" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ITEMS</th>
                            <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center gap-1">CREATED AT
                                    <svg class="w-2.5 h-2.5" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No sort batches found</p>
                                    <button type="button" @@click="createBatchModalOpen = true" class="text-xs text-violet-600 hover:underline font-semibold">Create the first batch</button>
                                </div>
                            </td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.batch_number" class="px-4 py-3 font-bold text-slate-900 font-mono" x-text="row.batch_number"></td>
                                <td x-show="visibleColumns.dispatch_mode_label" class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600"
                                          x-text="row.dispatch_mode_label || row.dispatch_mode"></span>
                                </td>
                                <td x-show="visibleColumns.destination" class="px-4 py-3 text-slate-700">
                                    <span x-show="row.dispatch_mode === 'transfer'" x-text="row.destination_warehouse?.name || '—'" class="text-sm font-medium"></span>
                                    <span x-show="row.dispatch_mode !== 'transfer'" class="text-xs text-slate-400 italic">Local delivery</span>
                                </td>
                                <td x-show="visibleColumns.status" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                          :class="statusBadgeClass(row.status)"
                                          x-text="row.status"></span>
                                </td>
                                <td x-show="visibleColumns.items_count" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 font-bold text-slate-700" x-text="row.items_count"></span>
                                </td>
                                <td x-show="visibleColumns.created_at" class="px-4 py-3 text-slate-500" x-text="row.created_at || '—'"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        {{-- Manage Items --}}
                                        <button type="button"
                                                @@click="openManageModal(row)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-[11px] font-semibold text-violet-700 hover:bg-violet-100 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                            Items
                                        </button>
                                        {{-- Seal --}}
                                        <button type="button"
                                                x-show="row.status === 'open'"
                                                @@click="sealBatch(row.id)"
                                                :disabled="loading"
                                                class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-700 disabled:opacity-40 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Seal
                                        </button>
                                        {{-- Reopen --}}
                                        <button type="button"
                                                x-show="canReopenBatches && row.status === 'sealed'"
                                                @@click="reopenBatch(row.id)"
                                                :disabled="loading"
                                                class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-40 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Reopen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> results
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @@click="open = !open"
                                            class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                        <span x-text="perPage"></span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition
                                         class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display:none">
                                        <button type="button" @@click="setPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                        <button type="button" @@click="setPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                        <button type="button" @@click="setPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        <button type="button" @@click="setPerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs font-medium text-slate-600">Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></div>
                            <div class="flex space-x-1">
                                <button @@click="firstPage()" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                </button>
                                <button @@click="previousPage()" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <button @@click="lastPage()" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Create Batch                                                      --}}
    {{-- ════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="createBatchModalOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @@keydown.escape.window="createBatchModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="createBatchModalOpen = false"></div>
        <div x-show="createBatchModalOpen"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl shadow-slate-900/30 overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">New Sort Batch</h3>
                        <p class="text-violet-200 text-xs mt-0.5">Configure destination and dispatch mode</p>
                    </div>
                </div>
                <button type="button" @@click="createBatchModalOpen = false" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Dispatch Mode</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="mode in dispatchModes" :key="mode.value">
                            <label class="relative flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                   :class="newBatchDispatchMode === mode.value ? 'border-violet-500 bg-violet-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                                <input type="radio" :value="mode.value" x-model="newBatchDispatchMode" class="sr-only">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                     :class="newBatchDispatchMode === mode.value ? 'border-violet-600' : 'border-slate-300'">
                                    <div class="w-2 h-2 rounded-full bg-violet-600 transition-all" :class="newBatchDispatchMode === mode.value ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <span class="text-xs font-semibold" :class="newBatchDispatchMode === mode.value ? 'text-violet-700' : 'text-slate-600'" x-text="mode.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
                <div x-show="newBatchDispatchMode === 'transfer'" x-transition>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Destination Warehouse</label>
                    <select x-model="newBatchDestinationId" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500/30 focus:border-violet-300 transition-colors">
                        <option value="">Select destination warehouse…</option>
                        <template x-for="destination in destinations" :key="destination.id">
                            <option :value="destination.id" x-text="`${destination.name}${destination.code ? ' (' + destination.code + ')' : ''}`"></option>
                        </template>
                    </select>
                </div>
                <div x-show="newBatchDispatchMode === 'local_delivery'" x-transition
                     class="flex items-start gap-3 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3">
                    <svg class="w-4 h-4 text-sky-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-sky-700">Items will be delivered directly from this warehouse to their recipients.</p>
                </div>
            </div>
            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-3">
                <button type="button" @@click="createBatchModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Cancel</button>
                <button type="button" @@click="createBatch()" :disabled="loading"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-sm font-semibold text-white hover:from-violet-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    Create Batch
                </button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Manage Items                                                       --}}
    {{-- ════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="manageModalOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @@keydown.escape.window="manageModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="manageModalOpen = false"></div>
        <div x-show="manageModalOpen"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl shadow-slate-900/30 overflow-hidden flex flex-col max-h-[90vh]">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-bold text-white" x-text="activeBatch?.batch_number">Batch</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                  :class="activeBatch?.status === 'sealed' ? 'bg-emerald-400/30 text-emerald-100' : 'bg-white/20 text-white'"
                                  x-text="activeBatch?.status"></span>
                        </div>
                        <p class="text-indigo-200 text-xs mt-0.5">
                            <span x-text="activeBatch?.dispatch_mode_label || activeBatch?.dispatch_mode"></span>
                            <span x-show="activeBatch?.dispatch_mode === 'transfer'"> — <span x-text="activeBatch?.destination_warehouse?.name"></span></span>
                        </p>
                    </div>
                </div>
                <button type="button" @@click="manageModalOpen = false" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Loading overlay --}}
            <div x-show="modalLoading" x-cloak x-transition.opacity.duration.100ms
                 class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex items-center justify-center">
                <svg class="w-6 h-6 text-violet-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </div>

            {{-- Two-column body --}}
            <div class="flex flex-1 min-h-0 divide-x divide-slate-200">

                {{-- Left: Current items in batch --}}
                <div class="w-2/5 flex flex-col min-h-0">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/60 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">In this Batch</h4>
                                <p class="text-[10px] text-slate-500 mt-0.5" x-text="activeBatchItems.length + ' item' + (activeBatchItems.length !== 1 ? 's' : '')">0 items</p>
                            </div>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-bold"
                                  x-text="activeBatchItems.length">0</span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-3 space-y-1.5">
                        <template x-for="item in activeBatchItems" :key="item.sort_batch_item_id">
                            <div class="group flex items-center gap-2 rounded-xl border border-slate-100 bg-white px-3 py-2.5 hover:border-slate-200 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold text-slate-800 truncate" x-text="item.description"></p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] text-slate-400 font-mono truncate" x-text="item.tracking_code || '—'"></span>
                                        <span x-show="item.fulfillment_type && item.fulfillment_type !== 'warehouse'" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-semibold flex-shrink-0"
                                              :class="item.fulfillment_type === 'direct' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700'"
                                              x-text="item.fulfillment_type === 'direct' ? 'Direct' : 'Self Pickup'"></span>
                                    </div>
                                </div>
                                <button type="button"
                                        x-show="activeBatch?.status === 'open'"
                                        @@click="removeItemFromBatch(item.shipment_item_id)"
                                        class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-slate-300 hover:bg-rose-50 hover:text-rose-500 transition-colors opacity-0 group-hover:opacity-100">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <div x-show="activeBatchItems.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                            <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center mb-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5"/></svg>
                            </div>
                            <p class="text-xs font-medium text-slate-400">No items yet</p>
                            <p class="text-[10px] text-slate-300 mt-0.5">Select items on the right to add</p>
                        </div>
                    </div>
                </div>

                {{-- Right: Add eligible items --}}
                <div class="w-3/5 flex flex-col min-h-0">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/60 flex-shrink-0">
                        <div class="flex items-center justify-between mb-2.5">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Eligible Items</h4>
                                <p class="text-[10px] text-slate-500 mt-0.5" x-text="eligibleItems.length + ' available'">0 available</p>
                            </div>
                            <span x-show="selectedEligibleIds.length > 0"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                <span x-text="selectedEligibleIds.length + ' selected'"></span>
                                <button type="button" @@click="selectedEligibleIds = []" class="hover:text-amber-900 transition-colors">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </span>
                        </div>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input type="text"
                                   x-model.debounce.400ms="eligibleSearch"
                                   @@input="loadEligibleItems()"
                                   placeholder="Search by shipment, item, tracking…"
                                   class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/30 focus:border-violet-300 transition-colors">
                        </div>
                    </div>

                    {{-- Items table --}}
                    <div class="flex-1 overflow-y-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50/80 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 w-10"><span class="sr-only">Select</span></th>
                                    <th class="px-3 py-2 text-left text-[10px] uppercase tracking-wider font-bold text-slate-500">Shipment / Item</th>
                                    <th class="px-3 py-2 text-left text-[10px] uppercase tracking-wider font-bold text-slate-500">Destination</th>
                                    <th class="px-3 py-2 text-center text-[10px] uppercase tracking-wider font-bold text-slate-500">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template x-for="item in eligibleItems" :key="item.warehouse_receipt_item_id">
                                    <tr class="group hover:bg-violet-50/40 transition-colors cursor-pointer"
                                        :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-violet-50/60' : ''"
                                        @@click="activeBatch?.status === 'open' && (selectedEligibleIds.includes(item.warehouse_receipt_item_id)
                                            ? selectedEligibleIds = selectedEligibleIds.filter(id => id !== item.warehouse_receipt_item_id)
                                            : selectedEligibleIds.push(item.warehouse_receipt_item_id))">
                                        <td class="px-3 py-2.5">
                                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all"
                                                 :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id)
                                                     ? 'bg-violet-600 border-violet-600'
                                                     : 'border-slate-300 group-hover:border-violet-400'">
                                                <svg x-show="selectedEligibleIds.includes(item.warehouse_receipt_item_id)"
                                                     class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <p class="font-bold text-slate-900" x-text="item.shipment_number"></p>
                                            <p class="text-slate-500 mt-0.5" x-text="item.item_description"></p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="item.tracking_code || '—'"></span>
                                                <span x-show="item.fulfillment_type && item.fulfillment_type !== 'warehouse'" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-semibold"
                                                      :class="item.fulfillment_type === 'direct' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700'"
                                                      x-text="item.fulfillment_type === 'direct' ? 'Direct' : 'Self Pickup'"></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <p class="font-medium text-slate-700 truncate" x-text="item.destination?.recipient_name || '—'"></p>
                                            <p class="text-[10px] text-slate-400 truncate" x-text="item.destination?.town || ''"></p>
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 font-bold text-slate-700" x-text="item.received_quantity"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!modalLoading && eligibleItems.length === 0">
                                    <td colspan="4" class="px-4 py-10 text-center">
                                        <p class="text-xs font-medium text-slate-400">No eligible items available</p>
                                        <p class="text-[10px] text-slate-300 mt-0.5">All items may already be sorted</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer: Add Selected --}}
                    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between flex-shrink-0">
                        <p class="text-[10px] text-slate-400">
                            <span x-show="activeBatch?.status === 'sealed'" class="text-amber-600 font-semibold">Batch is sealed — items cannot be added.</span>
                            <span x-show="activeBatch?.status !== 'sealed'">Click rows to select items, then add them.</span>
                        </p>
                        <button type="button"
                                @@click="addSelectedToActiveBatch()"
                                :disabled="activeBatch?.status !== 'open' || selectedEligibleIds.length === 0 || modalLoading"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:from-violet-700 hover:to-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add <span x-show="selectedEligibleIds.length > 0" x-text="'(' + selectedEligibleIds.length + ')'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
