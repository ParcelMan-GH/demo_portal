@extends('warehouse.layouts.app')

@section('title', 'Outgoing Batches')
@section('page-title', 'Outgoing Batches')

@php
    $config = [
        'data_endpoint' => route('admin.transport-manifests.data'),
        'transfer_batches' => collect($transferBatches ?? [])->values(),
        'transport_drivers' => collect($transportDrivers ?? $drivers ?? [])->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values(),
        'destination_warehouses' => $warehouses ?? [],
    ];
@endphp

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="adminTransportManifestsPage" data-admin-transport-manifests-config="{{ json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE) }}">
    
    {{-- Header Section matching your exact image layout --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Outgoing Batches</h1>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-1 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                        <span x-text="selectedDateLabel">Today</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute left-0 mt-2 w-32 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-1 text-xs font-bold text-slate-700" style="display:none">
                        <button type="button" @click="setDateFilter('today', 'Today'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 rounded-lg">Today</button>
                        <button type="button" @click="setDateFilter('this_week', 'This Week'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 rounded-lg">This Week</button>
                        <button type="button" @click="setDateFilter('all', 'All Time'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 rounded-lg">All Time</button>
                    </div>
                </div>
            </div>
            <p class="text-slate-400 text-sm font-semibold mt-1">Monitor and dispatch regional batches ready for warehouse transfer.</p>
        </div>

        <div>
            <button type="button" @click="createManualBatch()" class="bg-[#E2762B] hover:bg-[#d1651d] text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-md transition-colors flex items-center gap-2">
                Manually Create A Batch
            </button>
        </div>
    </div>

    {{-- Regional Stat Cards matching your layout --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <template x-for="(card, index) in summaryCards" :key="index">
            <div class="bg-amber-50/20 border border-amber-100/80 rounded-2xl p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 truncate" x-text="card.label"></p>
                <p class="text-2xl font-black text-slate-900 mt-2" x-text="card.value"></p>
            </div>
        </template>
    </div>

    {{-- Main Datatable Container directly below cards --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        
        {{-- Search & Controls Bar --}}
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search Batch</label>
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search batch number (e.g. BATCH-...)"
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>
                </div>
            </div>

            {{-- Filter Drawer --}}
            <div x-show="showFilters" x-transition class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="filters.status" @change="loadData()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="open">Open</option>
                            <option value="dispatched">Dispatched</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Table View --}}
        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-extrabold uppercase tracking-wider text-slate-500">Batch Number</th>
                            <th class="px-4 py-3 text-left font-extrabold uppercase tracking-wider text-slate-500">Destination Context</th>
                            <th class="px-4 py-3 text-center font-extrabold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-4 py-3 text-center font-extrabold uppercase tracking-wider text-slate-500">Parcels Count</th>
                            <th class="px-4 py-3 text-left font-extrabold uppercase tracking-wider text-slate-500">Created At</th>
                            <th class="px-4 py-3 text-right font-extrabold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50">
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-400 font-bold">
                                    No outgoing batches match the current query
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 font-extrabold text-slate-900" x-text="row.batch_number"></td>
                                <td class="px-4 py-3 font-bold text-slate-700" x-text="row.destination_warehouse"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                          :class="row.status === 'open' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                          x-text="row.status_label"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-black text-slate-700" x-text="row.items_count"></span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-600" x-text="row.created_at"></td>
                                <td class="px-4 py-3 text-right">
                                    <template x-if="row.status === 'open'">
                                        <button type="button" @click="closeAndDispatch(row.id)" class="inline-flex items-center gap-1 rounded-xl bg-slate-900 hover:bg-slate-800 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors">
                                            <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Close & Dispatch
                                        </button>
                                    </template>
                                    <template x-if="row.status !== 'open'">
                                        <span class="text-xs font-extrabold text-slate-400 bg-slate-100 px-3 py-1.5 rounded-xl inline-block">Dispatched</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Footer Pagination Bar --}}
            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="meta.current_page--; loadData()" :disabled="meta.current_page <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40">Prev</button>
                        <span class="text-xs font-black text-slate-700">Page <span x-text="meta.current_page"></span> / <span x-text="meta.last_page"></span></span>
                        <button @click="meta.current_page++; loadData()" :disabled="meta.current_page >= meta.last_page" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminTransportManifestsPage', () => ({
            search: '',
            showFilters: false,
            loading: false,
            selectedDateLabel: 'Today',
            dateFilter: 'today',
            rows: [],
            summaryCards: [
                { label: 'Items Heading to Kumasi', value: '0' },
                { label: 'Items Heading to Koforidua', value: '0' },
                { label: 'Items Heading to Takoradi', value: '0' },
                { label: 'Items Heading to Tamale', value: '0' },
                { label: 'Expected Value Today', value: 'GH₵ 0.00' }
            ],
            filters: { status: '' },
            meta: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
            config: {},
            init() {
                const configAttr = this.$el.getAttribute('data-admin-transport-manifests-config');
                this.config = configAttr ? JSON.parse(configAttr) : {};
                this.loadData();
            },
            setDateFilter(filterKey, label) {
                this.dateFilter = filterKey;
                this.selectedDateLabel = label;
                this.loadData();
            },
            loadData() {
                this.loading = true;
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    search: this.search,
                    status: this.filters.status,
                    date_filter: this.dateFilter,
                });

                fetch(`${this.config.data_endpoint}?${params.toString()}`)
                    .then(res => res.json())
                    .then(res => {
                        this.rows = res.data || [];
                        this.meta = res.meta || this.meta;
                        if (res.summaryCards) this.summaryCards = res.summaryCards;
                    })
                    .finally(() => { this.loading = false; });
            },
            createManualBatch() {
                alert('Manual Batch creation popup coming up!');
            },
            closeAndDispatch(batchId) {
                if (!confirm('Are you sure you want to close and dispatch this batch?')) return;

                fetch(`/admin/transport-manifests/${batchId}/dispatch`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.loadData();
                    } else {
                        alert(data.message || 'Failed to dispatch batch.');
                    }
                })
                .catch(() => alert('Error processing batch dispatch.'));
            }
        }));
    });
</script>
@endsection