@extends('warehouse.layouts.app')

@section('title', 'Outgoing Batches')
@section('page-title', 'Outgoing Batches')

@php
    $config = [
        'data_endpoint' => route('admin.transport-manifests.data'),
        'create_endpoint' => route('admin.transport-manifests.store'),
        'transfer_batches' => collect($transferBatches ?? [])->values(),
        'transport_drivers' => collect($transportDrivers ?? $drivers ?? [])->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values(),
        'destination_warehouses' => $warehouses ?? [],
        'available_regions' => [
            ['id' => 1, 'name' => 'Kumasi'],
            ['id' => 2, 'name' => 'Koforidua'],
            ['id' => 3, 'name' => 'Takoradi'],
            ['id' => 4, 'name' => 'Tamale'],
            ['id' => 5, 'name' => 'Accra'],
            ['id' => 6, 'name' => 'Sunyani'],
            ['id' => 7, 'name' => 'Cape Coast'],
            ['id' => 8, 'name' => 'Ho'],
        ]
    ];
@endphp

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="adminTransportManifestsPage" data-admin-transport-manifests-config="{{ json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE) }}">
    
    {{-- Header Section --}}
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
            <button type="button" @click="showCreateModal = true" class="bg-[#E2762B] hover:bg-[#d1651d] text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Manually Create A Batch
            </button>
        </div>
    </div>

    {{-- Customizable Regional Stat Cards Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <template x-for="(card, index) in activeCards" :key="index">
            <div class="bg-amber-50/20 border border-amber-100/80 rounded-2xl p-4 shadow-sm relative group">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 truncate" x-text="`Items Heading to ${card.region_name}`"></p>
                    
                    {{-- Customizable Location Dropdown Selector --}}
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="text-slate-300 hover:text-slate-600 transition-colors p-1 rounded-lg hover:bg-white/80">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-1 text-xs font-bold text-slate-700 max-h-48 overflow-y-auto" style="display:none">
                            <div class="px-2 py-1 text-[9px] uppercase tracking-wider text-slate-400 font-extrabold border-b border-slate-100">Select Location</div>
                            <template x-for="region in availableRegions" :key="region.id">
                                <button type="button" @click="updateCardRegion(index, region.id, region.name); open = false" class="w-full text-left px-2.5 py-1.5 hover:bg-slate-50 rounded-lg flex items-center justify-between">
                                    <span x-text="region.name"></span>
                                    <svg x-show="card.region_id === region.id" class="w-3 h-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <p class="text-3xl font-black text-slate-900 mt-2" x-text="card.count"></p>
            </div>
        </template>
    </div>

    {{-- Main Datatable Container --}}
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

    {{-- Manually Create Batch Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-5 border border-slate-100" @click.away="showCreateModal = false">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-900 text-lg">Create Outgoing Batch</h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Delivery Region ID</label>
                    <input type="number" x-model="newBatch.delivery_region_id" placeholder="e.g. 1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Delivery District ID</label>
                    <input type="number" x-model="newBatch.delivery_district_id" placeholder="e.g. 1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-orange-500">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="submitCreateBatch()" :disabled="creating" class="px-5 py-2.5 rounded-xl bg-[#E2762B] text-white text-xs font-bold shadow-md hover:bg-[#d1651d] disabled:opacity-50">Create Batch</button>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminTransportManifestsPage', () => ({
            search: '',
            showFilters: false,
            showCreateModal: false,
            creating: false,
            loading: false,
            selectedDateLabel: 'Today',
            dateFilter: 'today',
            newBatch: { delivery_region_id: 1, delivery_district_id: 1 },
            rows: [],
            availableRegions: [],
            activeCards: [
                { region_id: 1, region_name: 'Kumasi', count: 0 },
                { region_id: 2, region_name: 'Koforidua', count: 0 },
                { region_id: 3, region_name: 'Takoradi', count: 0 },
                { region_id: 4, region_name: 'Tamale', count: 0 }
            ],
            filters: { status: '' },
            meta: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
            config: {},
            init() {
                const configAttr = this.$el.getAttribute('data-admin-transport-manifests-config');
                this.config = configAttr ? JSON.parse(configAttr) : {};
                this.availableRegions = this.config.available_regions || [];
                this.loadData();
            },
            setDateFilter(filterKey, label) {
                this.dateFilter = filterKey;
                this.selectedDateLabel = label;
                this.loadData();
            },
            updateCardRegion(cardIndex, newRegionId, newRegionName) {
                this.activeCards[cardIndex].region_id = newRegionId;
                this.activeCards[cardIndex].region_name = newRegionName;
                this.loadData();
            },
            loadData() {
                this.loading = true;
                const trackedRegionIds = this.activeCards.map(c => c.region_id).join(',');

                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    search: this.search,
                    status: this.filters.status,
                    date_filter: this.dateFilter,
                    region_ids: trackedRegionIds
                });

                fetch(`${this.config.data_endpoint}?${params.toString()}`)
                    .then(res => res.json())
                    .then(res => {
                        this.rows = res.data || [];
                        this.meta = res.meta || this.meta;
                        
                        if (res.cardCounts) {
                            this.activeCards.forEach(card => {
                                card.count = res.cardCounts[card.region_id] || 0;
                            });
                        }
                    })
                    .finally(() => { this.loading = false; });
            },
            submitCreateBatch() {
                this.creating = true;
                fetch(this.config.create_endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.newBatch)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.showCreateModal = false;
                        this.loadData();
                    } else {
                        alert(data.message || 'Error creating batch.');
                    }
                })
                .finally(() => { this.creating = false; });
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