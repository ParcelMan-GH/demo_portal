@extends('warehouse.layouts.app')

@section('title', 'Collections')

@section('content')

<div x-data="collectionsPage()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Ready for Collection</h1>
            <p class="text-xs text-slate-500 mt-0.5">Self-pickup shipments waiting for recipient collection at {{ $warehouse->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-xs font-bold text-emerald-700">{{ $readyCount }} Ready</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-slate-600">{{ $collectedCount }} Collected</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 mb-6 p-4">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2 bg-slate-100 rounded-xl p-1">
                <button @@click="status = 'ready'; fetchData(1)" class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all"
                        :class="status === 'ready' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    Ready
                </button>
                <button @@click="status = 'collected'; fetchData(1)" class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all"
                        :class="status === 'collected' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    Collected
                </button>
            </div>
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @@input="onSearch()" placeholder="Search shipment, vendor, recipient..."
                       class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none">
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex items-center justify-center py-16">
        <svg class="w-6 h-6 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>

    <!-- Empty -->
    <div x-show="!loading && rows.length === 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm py-16 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-500" x-text="status === 'ready' ? 'No shipments awaiting collection' : 'No collected shipments yet'"></p>
    </div>

    <!-- Table -->
    <div x-show="!loading && rows.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shipment</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider" x-text="status === 'ready' ? 'Ready Since' : 'Collected At'"></th>
                        <th x-show="status === 'collected'" class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Collected By</th>
                        <th x-show="status === 'ready'" class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs font-bold text-orange-700" x-text="row.shipment_number"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-700" x-text="row.vendor_name || '—'"></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs font-medium text-slate-900" x-text="row.recipient_name || '—'"></div>
                                <div class="text-[10px] text-slate-500" x-text="row.recipient_phone"></div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-bold text-slate-700" x-text="row.items_count"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="status === 'ready' ? row.ready_at : row.collected_at"></td>
                            <td x-show="status === 'collected'" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="row.collected_by_name || '—'"></td>
                            <td x-show="status === 'ready'" class="px-4 py-3 whitespace-nowrap text-right">
                                <button @@click="openHandover(row)" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-lg transition-all active:scale-[0.97]">
                                    Hand Over
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="meta.last_page > 1" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
            <p class="text-[11px] text-slate-500">
                Showing <span class="font-bold" x-text="meta.from"></span> to <span class="font-bold" x-text="meta.to"></span> of <span class="font-bold" x-text="meta.total"></span>
            </p>
            <div class="flex items-center gap-1">
                <button @@click="fetchData(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                <button @@click="fetchData(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>

    <!-- Handover Modal -->
    <template x-teleport="body">
        <div x-show="handoverOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @@click="handoverOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4" @@click.outside="handoverOpen = false">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Hand Over Shipment</h3>
                    <button @@click="handoverOpen = false" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-orange-50 rounded-xl p-3 border border-orange-200">
                    <p class="text-xs text-orange-900 font-bold" x-text="handoverRow?.shipment_number"></p>
                    <p class="text-[11px] text-orange-800 mt-0.5">Recipient: <span x-text="handoverRow?.recipient_name"></span> &middot; <span x-text="handoverRow?.recipient_phone"></span></p>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Collector Name <span class="text-red-400">*</span></label>
                        <input type="text" x-model="handoverForm.collected_by_name" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Collector Phone <span class="text-red-400">*</span></label>
                        <input type="text" x-model="handoverForm.collected_by_phone" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none font-mono">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">ID Type</label>
                            <select x-model="handoverForm.collected_by_id_type" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none">
                                <option value="">None</option>
                                <option value="national_id">National ID (Ghana Card)</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="voter_id">Voter ID</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">ID Number</label>
                            <input type="text" x-model="handoverForm.collected_by_id_number" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Notes</label>
                        <textarea x-model="handoverForm.notes" rows="2" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none resize-none"></textarea>
                    </div>
                </div>

                <div x-show="handoverError" class="px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-xs text-red-700 font-medium" x-text="handoverError"></p>
                </div>

                <div class="flex gap-2.5 pt-1">
                    <button @@click="submitHandover()" :disabled="handoverSubmitting || !handoverForm.collected_by_name || !handoverForm.collected_by_phone"
                            class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.98]">
                        <span x-show="!handoverSubmitting">Confirm Handover</span>
                        <span x-show="handoverSubmitting">Processing...</span>
                    </button>
                    <button @@click="handoverOpen = false" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    </template>
</div>

@endsection

@push('scripts')
<script>
function collectionsPage() {
    return {
        rows: [], meta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        loading: false, status: 'ready', search: '', _searchTimeout: null,

        // Handover modal
        handoverOpen: false, handoverRow: null, handoverSubmitting: false, handoverError: '',
        handoverForm: { collected_by_name: '', collected_by_phone: '', collected_by_id_type: '', collected_by_id_number: '', notes: '' },

        init() { this.fetchData(1); },

        fetchData(page) {
            this.loading = true;
            const params = new URLSearchParams({ page, per_page: this.meta.per_page, status: this.status });
            if (this.search) params.set('search', this.search);

            fetch('{{ route("warehouse.collections.data") }}?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(json => { this.rows = json.data; this.meta = json.meta; this.loading = false; })
            .catch(() => { this.loading = false; });
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.fetchData(1), 300);
        },

        openHandover(row) {
            this.handoverRow = row;
            this.handoverForm = {
                collected_by_name: row.recipient_name || '',
                collected_by_phone: row.recipient_phone || '',
                collected_by_id_type: '', collected_by_id_number: '', notes: ''
            };
            this.handoverError = '';
            this.handoverOpen = true;
        },

        async submitHandover() {
            this.handoverSubmitting = true; this.handoverError = '';
            try {
                const url = '{{ route("warehouse.collections.handover", "__ID__") }}'.replace('__ID__', this.handoverRow.shipment_id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify(this.handoverForm),
                });
                const json = await res.json();
                if (json.success) { this.handoverOpen = false; this.fetchData(1); }
                else if (json.errors) { this.handoverError = Object.values(json.errors).flat().join(', '); }
                else { this.handoverError = json.message || 'Failed to record handover.'; }
            } catch { this.handoverError = 'An unexpected error occurred.'; }
            this.handoverSubmitting = false;
        },
    };
}
</script>
@endpush
