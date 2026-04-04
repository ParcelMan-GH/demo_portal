@extends('admin.layouts.app')

@section('title', 'Package Tracking')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Package Tracking')

@section('content')
<div x-data="packageTracking()" x-init="init()">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Package Tracking</h1>
        <p class="text-sm text-slate-500 mt-0.5">Real-time view of all package labels and who has them.</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Total Labels</p>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-emerald-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-emerald-700">{{ number_format($stats['claimed']) }}</p>
            <p class="text-xs text-emerald-600 font-semibold mt-0.5">With Drivers</p>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-slate-400">{{ number_format($stats['unclaimed']) }}</p>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">At Warehouse</p>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-blue-200/80 shadow-sm p-4">
            <p class="text-2xl font-bold text-blue-700">{{ number_format($stats['delivered']) }}</p>
            <p class="text-xs text-blue-600 font-semibold mt-0.5">Delivered</p>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 max-w-xs">
                        <input x-model="search" @@input.debounce.500ms="loadData()" type="text" placeholder="Search barcode, shipment, description..."
                               class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex gap-1 bg-slate-100/80 p-1 rounded-xl">
                        <button @@click="statusFilter = ''; loadData()" :class="statusFilter === '' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">All</button>
                        <button @@click="statusFilter = 'claimed'; loadData()" :class="statusFilter === 'claimed' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">With Drivers</button>
                        <button @@click="statusFilter = 'unclaimed'; loadData()" :class="statusFilter === 'unclaimed' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">At Warehouse</button>
                        <button @@click="statusFilter = 'delivered'; loadData()" :class="statusFilter === 'delivered' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all">Delivered</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            <div class="rounded-xl border border-slate-200/50 relative">
                <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 rounded-xl" style="display:none"></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Barcode</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Package</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Destination</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Holder</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Last Activity</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-if="!loading && rows.length === 0">
                                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400 text-xs">No labels found.</td></tr>
                            </template>
                            <template x-for="row in rows" :key="row.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="text-xs font-mono font-bold text-slate-900" x-text="row.barcode"></span>
                                        <span x-show="row.labels_total > 1" class="text-[9px] text-slate-400 ml-1" x-text="row.label_index + '/' + row.labels_total"></span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <a :href="'/admin/shipments/' + row.shipment_id" class="text-xs font-semibold text-blue-600 hover:underline" x-text="row.shipment_number" x-show="row.shipment_number"></a>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.description || '—'"></td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs text-slate-700" x-text="row.recipient_name || '—'"></div>
                                        <div class="text-[10px] text-slate-400" x-text="row.delivery_town || ''"></div>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <template x-if="row.driver">
                                            <div>
                                                <div class="text-xs font-semibold text-emerald-700" x-text="row.driver.name"></div>
                                                <div class="text-[10px] text-slate-400" x-text="row.driver.phone"></div>
                                            </div>
                                        </template>
                                        <template x-if="!row.driver">
                                            <span class="text-xs text-slate-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                              :class="row.status === 'claimed' ? 'bg-emerald-100 text-emerald-700' : row.status === 'delivered' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
                                              x-text="row.status === 'claimed' ? 'With Driver' : row.status === 'delivered' ? 'Delivered' : 'At Warehouse'"></span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[10px] text-slate-400" x-text="row.last_event_at || row.created_at"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing <span x-text="meta.total > 0 ? ((meta.current_page - 1) * meta.per_page) + 1 : 0"></span> to <span x-text="Math.min(meta.current_page * meta.per_page, meta.total)"></span> of <span x-text="meta.total"></span> results
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-xs font-medium text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></div>
                            <div class="flex space-x-1">
                                <button @@click="page = 1; loadData()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                <button @@click="page--; loadData()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <button @@click="page++; loadData()" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                <button @@click="page = meta.last_page; loadData()" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function packageTracking() {
    return {
        rows: [],
        loading: false,
        search: '',
        statusFilter: '',
        page: 1,
        meta: { total: 0, per_page: 25, current_page: 1, last_page: 1 },

        init() { this.loadData(); },

        async loadData() {
            this.loading = true;
            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.statusFilter) params.set('status', this.statusFilter);
            params.set('page', this.page);
            params.set('per_page', this.meta.per_page);

            try {
                const res = await fetch(`{{ route('admin.package-tracking.data') }}?${params}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || this.meta;
            } catch (e) { console.error(e); }
            this.loading = false;
        },
    };
}
</script>
@endpush
