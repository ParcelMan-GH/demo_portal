@extends('warehouse.layouts.app')

@section('title', 'Received Pickups')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Received Pickups')

@php
    $config = [
        'endpoint' => route('warehouse.pickups.received.data'),
        'statuses' => $statuses ?? [
            ['value' => 'received', 'label' => 'Received'],
            ['value' => 'processed', 'label' => 'Processed']
        ],
        'drivers' => isset($drivers) ? $drivers->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values() : [],
    ];
@endphp

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" 
     x-data="warehouseReceivedPickupsPage()" 
     x-init="init()"
     data-config="{{ json_encode($config) }}">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Received Pickups</h1>
            <p class="text-slate-500 text-sm mt-1">Pickups already checked into {{ $warehouse->name ?? 'this warehouse' }}.</p>
        </div>
    </div>

    {{-- ═══════════ MAIN TABLE CONTAINER ═══════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        
        {{-- Search & Action Bar --}}
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100">
            <div class="relative w-full sm:w-96">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search order, rider, or phone..."
                       class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-all">
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <button type="button" @click="filtersOpen = !filtersOpen"
                        class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5"
                        :class="filtersOpen ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                    <svg class="w-4 h-4 text-slate-500" :class="filtersOpen ? 'text-orange-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    Filters
                    <span x-show="activeFilterCount() > 0" class="ml-1 rounded-full bg-orange-200 px-1.5 py-0.5 text-[10px] text-orange-800" x-text="activeFilterCount()"></span>
                </button>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                        View
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-48 bg-white border border-slate-200 rounded-xl shadow-lg p-2" style="display:none">
                        <template x-for="col in columns" :key="col.key">
                            <button type="button" @click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <span x-text="col.label"></span>
                                <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <button type="button" @click="loadData(meta.current_page)" class="p-2.5 border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 rounded-xl shadow-sm transition-all" title="Refresh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        {{-- Expanded Filter Panel --}}
        <div x-show="filtersOpen" x-transition class="p-4 bg-slate-50/80 border-b border-slate-100" style="display:none">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                
                {{-- Select Filters --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Receipt Status</label>
                    <select x-model="filters.status" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All statuses</option>
                        <template x-for="status in config.statuses" :key="status.value">
                            <option :value="status.value" x-text="status.label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Pickup Rider</label>
                    <select x-model="filters.driver_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All riders</option>
                        <template x-for="driver in config.drivers" :key="driver.id">
                            <option :value="driver.id" x-text="driver.name + (driver.phone ? ' / ' + driver.phone : '')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Receipt Result</label>
                    <select x-model="filters.receipt_result" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All results</option>
                        <option value="matched">Matched</option>
                        <option value="discrepancy">Any discrepancy</option>
                        <option value="shortage">Shortage</option>
                        <option value="overage">Overage</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Receiving Notes</label>
                    <select x-model="filters.notes" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All note states</option>
                        <option value="with_notes">With notes</option>
                        <option value="without_notes">Without notes</option>
                    </select>
                </div>

                {{-- Date Pickers --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Received Date</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                        <input type="text" x-ref="receivedDateRange" placeholder="Select dates" readonly class="w-full cursor-pointer bg-white border border-slate-200 rounded-xl px-3 py-2.5 pl-9 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Assigned Date</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                        <input type="text" x-ref="assignedDateRange" placeholder="Select dates" readonly class="w-full cursor-pointer bg-white border border-slate-200 rounded-xl px-3 py-2.5 pl-9 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Arrived Date</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                        <input type="text" x-ref="arrivedDateRange" placeholder="Select dates" readonly class="w-full cursor-pointer bg-white border border-slate-200 rounded-xl px-3 py-2.5 pl-9 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>

                {{-- Min Max Input --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Rider Picked Qty</label>
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" x-model="filters.driver_qty_min" placeholder="Min" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <span class="text-slate-400">-</span>
                        <input type="number" min="0" x-model="filters.driver_qty_max" placeholder="Max" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="button" @click="clearFilters()" class="px-4 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold bg-white border border-slate-200 rounded-xl shadow-sm transition-colors">Clear All</button>
                <button type="button" @click="applyFilters()" class="px-5 py-2 bg-orange-600 text-white rounded-xl text-xs font-semibold shadow-sm hover:bg-orange-700 transition-colors">Apply Filters</button>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto relative">
            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>

            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead class="bg-[#FFF8F3] border-y border-orange-100/60">
                    <tr>
                        <th x-show="visibleColumns.shipment_number" @click="sort('shipment_number')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Order # <span x-show="sortBy==='shipment_number'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.driver_name" @click="sort('driver_name')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Rider <span x-show="sortBy==='driver_name'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.driver_phone" class="px-6 py-3.5 text-xs font-semibold text-slate-700">Rider Phone</th>
                        <th x-show="visibleColumns.status" @click="sort('status')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700 text-center">Status <span x-show="sortBy==='status'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.assigned_at" @click="sort('assigned_at')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Assigned At <span x-show="sortBy==='assigned_at'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.arrived_warehouse_at" @click="sort('arrived_warehouse_at')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Arrived <span x-show="sortBy==='arrived_warehouse_at'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.received_at" @click="sort('received_at')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Received <span x-show="sortBy==='received_at'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.actions" class="px-6 py-3.5 text-xs font-semibold text-slate-700 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-800 bg-white">
                    <template x-if="!loading && rows.length === 0">
                        <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">No received pickups found matching filters.</td></tr>
                    </template>

                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <td x-show="visibleColumns.shipment_number" class="px-6 py-4">
                                <a :href="row.view_url" class="font-mono font-bold text-orange-700 hover:text-orange-800 hover:underline" x-text="row.shipment_number || '-'"></a>
                            </td>
                            <td x-show="visibleColumns.driver_name" class="px-6 py-4 font-semibold text-slate-900" x-text="row.driver_name || '-'"></td>
                            <td x-show="visibleColumns.driver_phone" class="px-6 py-4 font-mono text-slate-600" x-text="row.driver_phone || '-'"></td>
                            <td x-show="visibleColumns.status" class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full inline-block border" :class="statusBadgeClass(row.status)" x-text="row.status_label || row.status || '-'"></span>
                            </td>
                            <td x-show="visibleColumns.assigned_at" class="px-6 py-4 text-slate-600" x-text="formatDisplayDate(row.assigned_at)"></td>
                            <td x-show="visibleColumns.arrived_warehouse_at" class="px-6 py-4 text-slate-600" x-text="formatDisplayDate(row.arrived_warehouse_at)"></td>
                            <td x-show="visibleColumns.received_at" class="px-6 py-4 text-slate-600" x-text="formatDisplayDate(row.received_at)"></td>
                            <td x-show="visibleColumns.actions" class="px-6 py-4 text-right">
                                <a :href="row.view_url" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                    Open
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div class="px-6 py-4 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 font-medium gap-4">
            <div>
                Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> pickups
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span>Rows</span>
                    <select x-model.number="perPage" @change="meta.current_page = 1; loadData()" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-bold text-slate-700 outline-none focus:ring-1 focus:ring-orange-500">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">Prev</button>
                    <span class="mx-2">Page <span class="font-bold text-slate-700" x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></span>
                    <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function warehouseReceivedPickupsPage() {
    return {
        config: {},
        rows: [],
        meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
        loading: false,
        search: '',
        
        // Filters
        filters: {
            status: '',
            driver_id: '',
            receipt_result: '',
            notes: '',
            driver_qty_min: '',
            driver_qty_max: '',
            received_from: '',
            received_to: '',
            assigned_from: '',
            assigned_to: '',
            arrived_from: '',
            arrived_to: ''
        },
        
        perPage: 25,
        sortBy: 'received_at',
        sortDirection: 'desc',
        filtersOpen: false,
        
        columns: [
            { key: 'shipment_number', label: 'Order #' },
            { key: 'driver_name', label: 'Rider' },
            { key: 'driver_phone', label: 'Rider Phone' },
            { key: 'status', label: 'Status' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'arrived_warehouse_at', label: 'Arrived Warehouse At' },
            { key: 'received_at', label: 'Received At' },
            { key: 'actions', label: 'Actions' }
        ],
        visibleColumns: {
            shipment_number: true, driver_name: true, driver_phone: true,
            status: true, assigned_at: true, arrived_warehouse_at: true, received_at: true, actions: true
        },

        init() {
            const el = this.$root;
            this.config = JSON.parse(el.dataset.config || '{}');
            this.$nextTick(() => {
                this.initDateRangePicker('receivedDateRange', 'received_from', 'received_to');
                this.initDateRangePicker('assignedDateRange', 'assigned_from', 'assigned_to');
                this.initDateRangePicker('arrivedDateRange', 'arrived_from', 'arrived_to');
            });
            this.loadData();
        },

        async loadData(page = this.meta.current_page) {
            this.loading = true;
            const params = new URLSearchParams({
                page,
                per_page: this.perPage,
                sort: this.sortBy,
                direction: this.sortDirection
            });
            
            if (this.search) params.set('search', this.search);
            
            // Apply all filters safely
            for (const [key, value] of Object.entries(this.filters)) {
                if (value !== '' && value !== null) {
                    params.set(key, value);
                }
            }

            try {
                const res = await fetch(`${this.config.endpoint}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || this.meta;
            } catch (e) {
                console.error("Failed to load received pickups", e);
            } finally {
                this.loading = false;
            }
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.loadData(1);
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        previousPage() {
            if (this.meta.current_page > 1) this.loadData(this.meta.current_page - 1);
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) this.loadData(this.meta.current_page + 1);
        },

        activeFilterCount() {
            let count = 0;
            for (const val of Object.values(this.filters)) {
                if (val !== '' && val !== null) count++;
            }
            return count;
        },

        applyFilters() {
            this.filtersOpen = false;
            this.loadData(1);
        },

        clearFilters() {
            for (const key of Object.keys(this.filters)) {
                this.filters[key] = '';
            }
            if (this.$refs.receivedDateRange) this.$refs.receivedDateRange.value = '';
            if (this.$refs.assignedDateRange) this.$refs.assignedDateRange.value = '';
            if (this.$refs.arrivedDateRange) this.$refs.arrivedDateRange.value = '';
            
            this.filtersOpen = false;
            this.loadData(1);
        },

        statusBadgeClass(status) {
            status = String(status).toLowerCase();
            if (status === 'received') return 'bg-emerald-50 border-emerald-200 text-emerald-700';
            if (status === 'processed') return 'bg-blue-50 border-blue-200 text-blue-700';
            return 'bg-slate-100 border-slate-200 text-slate-700';
        },

        formatDisplayDate(val) {
            if (!val) return '-';
            const date = new Date(String(val).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return val;
            return date.toLocaleString('en-GB', { 
                day: '2-digit', month: 'short', year: 'numeric', 
                hour: 'numeric', minute: '2-digit', hour12: true 
            });
        },

        initDateRangePicker(refName, fromProp, toProp) {
            const ref = this.$refs[refName];
            if (!ref) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                
                const $input = window.$(ref);
                if ($input.data('daterangepicker')) return;

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'left',
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    ranges: {
                        'Today': [window.moment(), window.moment()],
                        'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                    }
                });

                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.filters[fromProp] = picker.startDate.format('YYYY-MM-DD');
                    this.filters[toProp] = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.filters[fromProp]} - ${this.filters[toProp]}`);
                });

                $input.on('cancel.daterangepicker', () => {
                    this.filters[fromProp] = '';
                    this.filters[toProp] = '';
                    $input.val('');
                });
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            // Load Daterangepicker dynamically if not present
            const loadScript = (id, src, check) => new Promise((resolve) => {
                if (document.getElementById(id) || check()) return resolve();
                const script = document.createElement('script');
                script.id = id; script.src = src;
                script.onload = resolve;
                document.body.appendChild(script);
            });

            const loadCss = (id, href) => {
                if (document.getElementById(id)) return;
                const link = document.createElement('link');
                link.id = id; link.rel = 'stylesheet'; link.href = href;
                document.head.appendChild(link);
            };

            loadCss('daterange-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css');
            
            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js', () => window.jQuery)
                .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js', () => window.moment))
                .then(() => loadScript('daterange-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', () => window.$.fn.daterangepicker))
                .then(setupPicker);
        }
    };
}
</script>
@endpush