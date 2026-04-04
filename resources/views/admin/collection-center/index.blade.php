@extends('admin.layouts.app')

@section('title', 'Collection Center')
@section('breadcrumb-parent', 'Warehouse')
@section('breadcrumb-current', 'Collection Center')

@section('content')
<div x-data="collectionCenter()" x-init="init()">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Collection Center</h1>
        <p class="text-sm text-slate-500 mt-0.5">Manage self-pickup parcels — when recipients come to the warehouse to collect their packages.</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-amber-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-amber-700">{{ number_format($readyCount) }}</p>
            <p class="text-xs text-amber-600 font-semibold mt-0.5">Pending Pickups</p>
            <p class="text-[11px] text-amber-500 mt-0.5">Awaiting recipient collection</p>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-emerald-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-emerald-700">{{ number_format($collectedTodayCount) }}</p>
            <p class="text-xs text-emerald-600 font-semibold mt-0.5">Collected Today</p>
            <p class="text-[11px] text-emerald-500 mt-0.5">Handed over so far today</p>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-slate-700">{{ number_format($totalCollectedCount) }}</p>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Total Collected</p>
            <p class="text-[11px] text-slate-400 mt-0.5">All time handovers</p>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">

        {{-- Toolbar --}}
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center flex-wrap">
                    {{-- Status Filter --}}
                    <div class="flex gap-1 bg-slate-100/80 p-1 rounded-xl">
                        <button @@click="statusFilter = 'ready'; page = 1; loadData()"
                                :class="statusFilter === 'ready' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">
                            Pending
                        </button>
                        <button @@click="statusFilter = 'collected'; page = 1; loadData()"
                                :class="statusFilter === 'collected' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">
                            Collected
                        </button>
                    </div>

                    {{-- Search --}}
                    <div class="relative flex-1 max-w-xs">
                        <input x-model="search" @@input.debounce.400ms="page = 1; loadData()" type="text"
                               placeholder="Search shipment, vendor, recipient..."
                               class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-orange-400/40 focus:border-orange-300 text-sm text-slate-900 placeholder-slate-400 transition-colors outline-none">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            <div class="rounded-xl border border-slate-200/50 relative">
                {{-- Loading Overlay --}}
                <div x-show="loading" x-transition.opacity.duration.150ms
                     class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 rounded-xl flex items-center justify-center"
                     style="display:none">
                    <svg class="w-6 h-6 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[750px] divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Items</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider"
                                    x-text="statusFilter === 'ready' ? 'Ready Since' : 'Collected At'"></th>
                                <th x-show="statusFilter === 'collected'"
                                    class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Collected By</th>
                                <th x-show="statusFilter === 'ready'"
                                    class="px-4 py-2 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-500"
                                               x-text="statusFilter === 'ready' ? 'No pending pickups at the moment' : 'No collected shipments found'"></p>
                                            <p class="text-xs text-slate-400">
                                                Self-pickup shipments will appear here once they are ready for collection.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="row in rows" :key="row.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a :href="'/admin/shipments/' + row.shipment_id"
                                           class="text-xs font-bold text-orange-700 hover:text-orange-900 hover:underline"
                                           x-text="row.shipment_number || '—'"></a>
                                        <div class="text-[10px] text-slate-400 mt-0.5" x-text="row.warehouse_name || ''"></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-700" x-text="row.vendor_name || '—'"></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-xs font-medium text-slate-900" x-text="row.recipient_name || '—'"></div>
                                        <div class="text-[10px] text-slate-400" x-text="row.recipient_phone || ''"></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-bold text-slate-700"
                                              x-text="row.items_count"></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600"
                                        x-text="statusFilter === 'ready' ? (row.ready_at || '—') : (row.collected_at || '—')"></td>
                                    <td x-show="statusFilter === 'collected'" class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-xs font-medium text-slate-700" x-text="row.collected_by_name || '—'"></div>
                                        <div class="text-[10px] text-slate-400" x-text="row.collected_by_phone || ''"></div>
                                        <div x-show="row.handed_over_by" class="text-[10px] text-slate-400 mt-0.5">
                                            by <span x-text="row.handed_over_by"></span>
                                        </div>
                                    </td>
                                    <td x-show="statusFilter === 'ready'" class="px-4 py-3 whitespace-nowrap text-right">
                                        <button @@click="openHandover(row)"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-lg transition-all active:scale-[0.97]">
                                            Hand Over
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing
                            <span x-text="meta.total > 0 ? ((meta.current_page - 1) * meta.per_page) + 1 : 0"></span>
                            to
                            <span x-text="Math.min(meta.current_page * meta.per_page, meta.total)"></span>
                            of
                            <span x-text="meta.total"></span>
                            results
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-xs font-medium text-slate-600">
                                Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span>
                            </div>
                            <div class="flex space-x-1">
                                <button @@click="page = 1; loadData()" :disabled="meta.current_page === 1"
                                        :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                </button>
                                <button @@click="page--; loadData()" :disabled="meta.current_page === 1"
                                        :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @@click="page++; loadData()" :disabled="meta.current_page >= meta.last_page"
                                        :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <button @@click="page = meta.last_page; loadData()" :disabled="meta.current_page >= meta.last_page"
                                        :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Handover Modal --}}
    <template x-teleport="body">
        <div x-show="handoverOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @@click="handoverOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Hand Over Shipment</h3>
                    <button @@click="handoverOpen = false"
                            class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Shipment Summary --}}
                <div class="bg-orange-50 rounded-xl p-3 border border-orange-200">
                    <p class="text-xs text-orange-900 font-bold" x-text="handoverRow?.shipment_number"></p>
                    <p class="text-[11px] text-orange-700 mt-0.5">
                        Vendor: <span class="font-medium" x-text="handoverRow?.vendor_name || '—'"></span>
                    </p>
                    <p class="text-[11px] text-orange-800 mt-0.5">
                        Recipient: <span x-text="handoverRow?.recipient_name || '—'"></span>
                        &middot; <span x-text="handoverRow?.recipient_phone || ''"></span>
                    </p>
                    <p class="text-[11px] text-orange-700 mt-0.5">
                        <span x-text="handoverRow?.items_count"></span>
                        <span x-text="handoverRow?.items_count === 1 ? 'item' : 'items'"></span>
                        &middot; Ready since <span x-text="handoverRow?.ready_at"></span>
                    </p>
                </div>

                {{-- Form --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">
                            Collector Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="handoverForm.collected_by_name"
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-400/30 focus:border-orange-400 outline-none transition-colors"
                               placeholder="Full name of person collecting">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">
                            Collector Phone <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="handoverForm.collected_by_phone"
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-400/30 focus:border-orange-400 outline-none transition-colors font-mono"
                               placeholder="0XX XXX XXXX">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">ID Type</label>
                            <select x-model="handoverForm.collected_by_id_type"
                                    class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-400/30 focus:border-orange-400 outline-none transition-colors">
                                <option value="">None</option>
                                <option value="national_id">National ID (Ghana Card)</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="voter_id">Voter ID</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">ID Number</label>
                            <input type="text" x-model="handoverForm.collected_by_id_number"
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-400/30 focus:border-orange-400 outline-none transition-colors"
                                   placeholder="Optional">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Notes</label>
                        <textarea x-model="handoverForm.notes" rows="2"
                                  class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-400/30 focus:border-orange-400 outline-none transition-colors resize-none"
                                  placeholder="Optional notes..."></textarea>
                    </div>
                </div>

                {{-- Error --}}
                <div x-show="handoverError" class="px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-xs text-red-700 font-medium" x-text="handoverError"></p>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2.5 pt-1">
                    <button @@click="submitHandover()"
                            :disabled="handoverSubmitting || !handoverForm.collected_by_name.trim() || !handoverForm.collected_by_phone.trim()"
                            class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.98]">
                        <span x-show="!handoverSubmitting">Confirm Handover</span>
                        <span x-show="handoverSubmitting">Processing...</span>
                    </button>
                    <button @@click="handoverOpen = false"
                            class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function collectionCenter() {
    return {
        rows: [],
        loading: false,
        statusFilter: 'ready',
        search: '',
        page: 1,
        meta: { total: 0, per_page: 20, current_page: 1, last_page: 1 },

        // Handover modal state
        handoverOpen: false,
        handoverRow: null,
        handoverSubmitting: false,
        handoverError: '',
        handoverForm: {
            collected_by_name: '',
            collected_by_phone: '',
            collected_by_id_type: '',
            collected_by_id_number: '',
            notes: '',
        },

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            const params = new URLSearchParams({
                status: this.statusFilter,
                page: this.page,
                per_page: this.meta.per_page,
            });
            if (this.search) params.set('search', this.search);

            try {
                const res = await fetch(`{{ route('admin.collection-center.data') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || this.meta;
            } catch (e) {
                console.error('Collection center load error:', e);
            }
            this.loading = false;
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

        async submitHandover() {
            this.handoverSubmitting = true;
            this.handoverError = '';
            try {
                const url = '{{ route("admin.collection-center.handover", ["shipment" => "__ID__"]) }}'
                    .replace('__ID__', this.handoverRow.shipment_id);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.handoverForm),
                });
                const json = await res.json();
                if (json.success) {
                    this.handoverOpen = false;
                    this.loadData();
                } else if (json.errors) {
                    this.handoverError = Object.values(json.errors).flat().join(', ');
                } else {
                    this.handoverError = json.message || 'Failed to record handover.';
                }
            } catch (e) {
                this.handoverError = 'An unexpected error occurred. Please try again.';
            }
            this.handoverSubmitting = false;
        },
    };
}
</script>
@endpush
