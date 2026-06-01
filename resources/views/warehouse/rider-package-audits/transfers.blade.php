@extends('warehouse.layouts.app')

@section('title', 'Rider Package Transfers')
@section('page-title', 'Rider Package Transfers')

@section('content')
<div class="space-y-5" x-data="riderTransfersPage()" x-init="load()">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 7h11m0 0l-4-4m4 4l-4 4M17 17H6m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Rider Package Transfers</h2>
                            <p class="truncate text-sm text-slate-500">Pending, accepted, rejected, and cancelled rider-to-rider package handovers.</p>
                        </div>
                    </div>
                </div>
                <button type="button" @@click="load()" :disabled="loading" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg class="h-4 w-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M20 9A8 8 0 006.35 4.65M4 15a8 8 0 0013.65 4.35"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="page = 1; load()" placeholder="Search package or rider..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters || status ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                            View
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <button type="button" @@click="exportRows()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="status = ''; page = 1; load()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="page = 1; load()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2" x-show="status">
                <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                    <span>Status: <span x-text="formatStatus(status)"></span></span>
                    <button type="button" @@click="status = ''; page = 1; load()" class="text-orange-500 hover:text-orange-800">&times;</button>
                </span>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <template x-for="column in columns" :key="column.key">
                                <th x-show="visibleColumns[column.key]" class="px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="tableHeaderClass(column.key)">
                                    <div class="flex items-center gap-1" :class="tableHeaderContentClass(column.key)">
                                        <span x-text="column.label"></span>
                                    </div>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50 bg-transparent">
                        <template x-if="!loading && filteredRows.length === 0">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h11m0 0l-4-4m4 4l-4 4M17 17H6m0 0l4 4m-4-4l4-4"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No rider package transfers found</p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in pagedRows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.package" class="w-[22%] whitespace-nowrap px-4 py-3">
                                    <a :href="packageUrl(row)" class="font-bold text-slate-900 hover:text-orange-700 hover:underline" x-text="row.tracking_code || '-'"></a>
                                    <p class="mt-0.5 max-w-[220px] truncate text-[11px] font-semibold text-slate-500" x-text="row.description || row.shipment_number || ''"></p>
                                </td>
                                <td x-show="visibleColumns.from_driver" class="w-[18%] whitespace-nowrap px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="row.from_driver?.name || '-'"></p>
                                    <p class="text-[11px] text-slate-500" x-text="row.from_driver?.phone || ''"></p>
                                </td>
                                <td x-show="visibleColumns.to_driver" class="w-[18%] whitespace-nowrap px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="row.to_driver?.name || '-'"></p>
                                    <p class="text-[11px] text-slate-500" x-text="row.to_driver?.phone || ''"></p>
                                </td>
                                <td x-show="visibleColumns.status" class="w-[12%] whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusClass(row.status)" x-text="formatStatus(row.status)"></span>
                                </td>
                                <td x-show="visibleColumns.requested_at" class="w-[15%] whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDate(row.requested_at)"></td>
                                <td x-show="visibleColumns.responded_at" class="w-[15%] whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDate(row.responded_at)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="!loading && filteredRows.length === 0">
                    <div class="px-4 py-12 text-center text-sm text-slate-400">No rider package transfers found.</div>
                </template>
                <template x-for="row in pagedRows" :key="row.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="packageUrl(row)" class="truncate text-sm font-extrabold text-slate-900 hover:text-orange-700" x-text="row.tracking_code || '-'"></a>
                                <p class="mt-1 text-xs font-semibold text-slate-500" x-text="row.description || row.shipment_number || ''"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="statusClass(row.status)" x-text="formatStatus(row.status)"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">From Rider</p><p class="font-bold text-slate-800" x-text="row.from_driver?.name || '-'"></p><p class="text-slate-500" x-text="row.from_driver?.phone || ''"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">To Rider</p><p class="font-bold text-slate-800" x-text="row.to_driver?.name || '-'"></p><p class="text-slate-500" x-text="row.to_driver?.phone || ''"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Requested</p><p class="font-bold text-slate-800" x-text="formatDate(row.requested_at)"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Resolved</p><p class="font-bold text-slate-800" x-text="formatDate(row.responded_at)"></p></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="filteredRows.length"></span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-600">Rows</span>
                            <select x-model.number="perPage" @@change="page = 1" class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-1">
                            <button type="button" @@click="previousPage()" :disabled="page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="page"></span> / <span x-text="lastPage"></span></div>
                            <button type="button" @@click="nextPage()" :disabled="page >= lastPage" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function riderTransfersPage() {
    return {
        rows: [],
        loading: false,
        showFilters: false,
        search: '',
        status: '',
        page: 1,
        perPage: 25,
        columns: [
            { key: 'package', label: 'Package' },
            { key: 'from_driver', label: 'From Rider' },
            { key: 'to_driver', label: 'To Rider' },
            { key: 'status', label: 'Status' },
            { key: 'requested_at', label: 'Requested' },
            { key: 'responded_at', label: 'Resolved' },
        ],
        visibleColumns: {
            package: true,
            from_driver: true,
            to_driver: true,
            status: true,
            requested_at: true,
            responded_at: true,
        },
        get filteredRows() {
            return this.rows;
        },
        get lastPage() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
        },
        get pagedRows() {
            if (this.page > this.lastPage) this.page = this.lastPage;
            const start = (this.page - 1) * this.perPage;
            return this.filteredRows.slice(start, start + this.perPage);
        },
        get pageFrom() {
            return this.filteredRows.length ? ((this.page - 1) * this.perPage) + 1 : 0;
        },
        get pageTo() {
            return Math.min(this.page * this.perPage, this.filteredRows.length);
        },
        async load() {
            this.loading = true;
            try {
                const url = new URL(@js(route('warehouse.rider-package-transfers.data')), window.location.origin);
                if (this.search) url.searchParams.set('search', this.search);
                if (this.status) url.searchParams.set('status', this.status);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.rows = json.data || [];
            } finally {
                this.loading = false;
            }
        },
        toggleColumn(key) {
            if (Object.values(this.visibleColumns).filter(Boolean).length === 1 && this.visibleColumns[key]) return;
            this.visibleColumns[key] = !this.visibleColumns[key];
        },
        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length || 1;
        },
        previousPage() {
            if (this.page > 1) this.page--;
        },
        nextPage() {
            if (this.page < this.lastPage) this.page++;
        },
        tableHeaderClass(key) {
            return key === 'status' ? 'text-center' : 'text-left';
        },
        tableHeaderContentClass(key) {
            return key === 'status' ? 'justify-center' : 'justify-start';
        },
        formatDate(value) {
            if (!value) return '-';
            return new Intl.DateTimeFormat('en-GH', { dateStyle: 'medium', timeStyle: 'short', hour12: true }).format(new Date(value));
        },
        formatStatus(value) {
            return (value || '-').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
        },
        statusClass(value) {
            if (value === 'accepted') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
            if (value === 'rejected') return 'border-rose-200 bg-rose-50 text-rose-700';
            if (value === 'cancelled') return 'border-slate-200 bg-slate-100 text-slate-600';
            return 'border-amber-200 bg-amber-50 text-amber-700';
        },
        packageUrl(row) {
            return @js(route('warehouse.packages.index')) + '?search=' + encodeURIComponent(row.tracking_code || '');
        },
        exportRows() {
            const headers = ['Package', 'Description', 'From Rider', 'From Phone', 'To Rider', 'To Phone', 'Status', 'Requested At', 'Resolved At'];
            const lines = this.filteredRows.map(row => [
                row.tracking_code || '',
                row.description || row.shipment_number || '',
                row.from_driver?.name || '',
                row.from_driver?.phone || '',
                row.to_driver?.name || '',
                row.to_driver?.phone || '',
                this.formatStatus(row.status),
                this.formatDate(row.requested_at),
                this.formatDate(row.responded_at),
            ]);
            this.downloadCsv('rider-package-transfers.csv', [headers, ...lines]);
        },
        downloadCsv(filename, rows) {
            const csv = rows.map(row => row.map(value => `"${String(value).replaceAll('"', '""')}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        },
    };
}
</script>
@endsection
