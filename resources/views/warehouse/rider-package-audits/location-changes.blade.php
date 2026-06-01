@extends('warehouse.layouts.app')

@section('title', 'Package Location Changes')
@section('page-title', 'Package Location Changes')

@section('content')
<div class="space-y-5" x-data="riderLocationChangesPage()" x-init="load()">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 21s7-4.438 7-11a7 7 0 10-14 0c0 6.562 7 11 7 11z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 10.5h.01"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Package Location Changes</h2>
                            <p class="truncate text-sm text-slate-500">Rider-submitted delivery location corrections and proof photos.</p>
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
                        <input type="text" x-model="search" @@input.debounce.500ms="page = 1; load()" placeholder="Search package, rider, location..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
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
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-4.438 7-11a7 7 0 10-14 0c0 6.562 7 11 7 11z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10.5h.01"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No package location changes found</p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in pagedRows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.package" class="w-[20%] whitespace-nowrap px-4 py-3">
                                    <a :href="packageUrl(row)" class="font-bold text-slate-900 hover:text-orange-700 hover:underline" x-text="row.tracking_code || '-'"></a>
                                    <p class="mt-0.5 max-w-[220px] truncate text-[11px] font-semibold text-slate-500" x-text="row.description || row.shipment_number || ''"></p>
                                </td>
                                <td x-show="visibleColumns.rider" class="w-[18%] whitespace-nowrap px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="row.driver?.name || '-'"></p>
                                    <p class="text-[11px] text-slate-500" x-text="row.driver?.phone || ''"></p>
                                </td>
                                <td x-show="visibleColumns.old_location" class="w-[22%] max-w-[320px] px-4 py-3 font-medium text-slate-500">
                                    <span class="line-clamp-2" x-text="row.old_location || '-'"></span>
                                </td>
                                <td x-show="visibleColumns.new_location" class="w-[24%] max-w-[340px] px-4 py-3 font-semibold text-slate-800">
                                    <span class="line-clamp-2" x-text="row.new_location || '-'"></span>
                                </td>
                                <td x-show="visibleColumns.changed_at" class="w-[14%] whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDate(row.changed_at)"></td>
                                <td x-show="visibleColumns.proof" class="w-[10%] whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" x-show="row.proof_photo_url" @@click="openProof(row)" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        View
                                    </button>
                                    <span x-show="!row.proof_photo_url" class="text-slate-400">-</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="!loading && filteredRows.length === 0">
                    <div class="px-4 py-12 text-center text-sm text-slate-400">No package location changes found.</div>
                </template>
                <template x-for="row in pagedRows" :key="row.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="packageUrl(row)" class="truncate text-sm font-extrabold text-slate-900 hover:text-orange-700" x-text="row.tracking_code || '-'"></a>
                                <p class="mt-1 text-xs font-semibold text-slate-500" x-text="row.description || row.shipment_number || ''"></p>
                            </div>
                            <button type="button" x-show="row.proof_photo_url" @@click="openProof(row)" class="shrink-0 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">Proof</button>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Rider</p><p class="font-bold text-slate-800" x-text="row.driver?.name || '-'"></p><p class="text-slate-500" x-text="row.driver?.phone || ''"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Old Location</p><p class="font-bold text-slate-700" x-text="row.old_location || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">New Location</p><p class="font-bold text-slate-900" x-text="row.new_location || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Changed</p><p class="font-bold text-slate-800" x-text="formatDate(row.changed_at)"></p></div>
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

    <div x-show="lightbox.open" x-cloak x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/85 p-4 sm:p-6" style="display:none" @@keydown.escape.window="closeProof()">
        <button type="button" @@click="closeProof()" class="absolute right-4 top-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="max-h-[92vh] w-full max-w-5xl">
            <img :src="lightbox.url" :alt="lightbox.title || 'Proof photo'" class="mx-auto max-h-[84vh] max-w-full rounded-2xl object-contain shadow-2xl">
            <p class="mt-3 text-center text-sm font-semibold text-white/80" x-text="lightbox.title"></p>
        </div>
    </div>
</div>

<script>
function riderLocationChangesPage() {
    return {
        rows: [],
        loading: false,
        search: '',
        page: 1,
        perPage: 25,
        lightbox: {
            open: false,
            url: null,
            title: '',
        },
        columns: [
            { key: 'package', label: 'Package' },
            { key: 'rider', label: 'Rider' },
            { key: 'old_location', label: 'Old Location' },
            { key: 'new_location', label: 'New Location' },
            { key: 'changed_at', label: 'Changed' },
            { key: 'proof', label: 'Proof' },
        ],
        visibleColumns: {
            package: true,
            rider: true,
            old_location: true,
            new_location: true,
            changed_at: true,
            proof: true,
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
                const url = new URL(@js(route('warehouse.package-location-changes.data')), window.location.origin);
                if (this.search) url.searchParams.set('search', this.search);
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
            return key === 'proof' ? 'text-right' : 'text-left';
        },
        tableHeaderContentClass(key) {
            return key === 'proof' ? 'justify-end' : 'justify-start';
        },
        formatDate(value) {
            if (!value) return '-';
            return new Intl.DateTimeFormat('en-GH', { dateStyle: 'medium', timeStyle: 'short', hour12: true }).format(new Date(value));
        },
        packageUrl(row) {
            return row.package_url || '#';
        },
        openProof(row) {
            this.lightbox.url = row.proof_photo_url;
            this.lightbox.title = row.tracking_code ? `Proof photo for ${row.tracking_code}` : 'Proof photo';
            this.lightbox.open = true;
            document.body.classList.add('overflow-hidden');
        },
        closeProof() {
            this.lightbox.open = false;
            this.lightbox.url = null;
            this.lightbox.title = '';
            document.body.classList.remove('overflow-hidden');
        },
        exportRows() {
            const headers = ['Package', 'Description', 'Rider', 'Rider Phone', 'Old Location', 'New Location', 'Changed At', 'Proof Photo'];
            const lines = this.filteredRows.map(row => [
                row.tracking_code || '',
                row.description || row.shipment_number || '',
                row.driver?.name || '',
                row.driver?.phone || '',
                row.old_location || '',
                row.new_location || '',
                this.formatDate(row.changed_at),
                row.proof_photo_url || '',
            ]);
            this.downloadCsv('package-location-changes.csv', [headers, ...lines]);
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
