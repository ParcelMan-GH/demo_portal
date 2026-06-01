@extends('warehouse.layouts.app')

@section('title', 'Rider Package Transfers')
@section('page-title', 'Rider Package Transfers')

@section('content')
<div class="space-y-5" x-data="riderTransfersPage()" x-init="load()">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 7h11m0 0l-4-4m4 4l-4 4M17 17H6m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900">Rider Package Transfers</h2>
                        <p class="text-sm text-slate-500">Pending, accepted, and rejected rider-to-rider package handovers.</p>
                    </div>
                </div>
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <select x-model="status" @@change="load()" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-900 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <div class="relative w-full md:w-96">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        <input x-model.debounce.350ms="search" @@input="load()" class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-300 focus:ring-4 focus:ring-orange-100" placeholder="Search package or rider...">
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full text-left">
                <thead class="bg-slate-50/80 text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Package</th>
                        <th class="px-5 py-4">From Rider</th>
                        <th class="px-5 py-4">To Rider</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Requested</th>
                        <th class="px-5 py-4">Resolved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-orange-50/20">
                            <td class="px-5 py-4">
                                <a :href="packageUrl(row)" class="font-extrabold text-blue-600 underline decoration-blue-200" x-text="row.tracking_code || '-'"></a>
                                <p class="mt-1 text-xs font-medium text-slate-500" x-text="row.description || row.shipment_number || ''"></p>
                            </td>
                            <td class="px-5 py-4"><p class="font-bold" x-text="row.from_driver?.name || '-'"></p><p class="mt-1 text-xs text-slate-500" x-text="row.from_driver?.phone || ''"></p></td>
                            <td class="px-5 py-4"><p class="font-bold" x-text="row.to_driver?.name || '-'"></p><p class="mt-1 text-xs text-slate-500" x-text="row.to_driver?.phone || ''"></p></td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-black uppercase" :class="statusClass(row.status)" x-text="formatStatus(row.status)"></span></td>
                            <td class="px-5 py-4 text-slate-600" x-text="formatDate(row.requested_at)"></td>
                            <td class="px-5 py-4 text-slate-600" x-text="formatDate(row.responded_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="space-y-3 p-4 lg:hidden">
            <template x-for="row in rows" :key="row.id">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <a :href="packageUrl(row)" class="font-extrabold text-blue-600" x-text="row.tracking_code || '-'"></a>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase" :class="statusClass(row.status)" x-text="formatStatus(row.status)"></span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <p><span class="block text-xs font-black uppercase text-slate-400">From</span><span x-text="row.from_driver?.name || '-'"></span></p>
                        <p><span class="block text-xs font-black uppercase text-slate-400">To</span><span x-text="row.to_driver?.name || '-'"></span></p>
                        <p><span class="block text-xs font-black uppercase text-slate-400">Requested</span><span x-text="formatDate(row.requested_at)"></span></p>
                        <p><span class="block text-xs font-black uppercase text-slate-400">Resolved</span><span x-text="formatDate(row.responded_at)"></span></p>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loading && rows.length === 0" class="px-5 py-14 text-center text-sm font-semibold text-slate-500">No rider transfers found.</div>
    </div>
</div>

<script>
function riderTransfersPage() {
    return {
        rows: [],
        loading: false,
        search: '',
        status: '',
        async load() {
            this.loading = true;
            const url = new URL(@js(route('warehouse.rider-package-transfers.data')), window.location.origin);
            if (this.search) url.searchParams.set('search', this.search);
            if (this.status) url.searchParams.set('status', this.status);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.rows = json.data || [];
            this.loading = false;
        },
        formatDate(value) {
            if (!value) return '-';
            return new Intl.DateTimeFormat('en-GH', { dateStyle: 'medium', timeStyle: 'short', hour12: true }).format(new Date(value));
        },
        formatStatus(value) {
            return (value || '-').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
        },
        statusClass(value) {
            if (value === 'accepted') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (value === 'rejected') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            if (value === 'cancelled') return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
        },
        packageUrl(row) {
            return @js(route('warehouse.packages.index')) + '?search=' + encodeURIComponent(row.tracking_code || '');
        },
    };
}
</script>
@endsection
