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
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 2l3 7h7l-5.5 4.3 2 7L12 16l-6.5 4.3 2-7L2 9h7l3-7z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Package Location Changes</h2>
                            <p class="text-sm text-slate-500">Rider-submitted delivery location corrections and proof photos.</p>
                        </div>
                    </div>
                </div>
                <div class="relative w-full lg:w-96">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                    <input x-model.debounce.350ms="search" @@input="load()" class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-300 focus:ring-4 focus:ring-orange-100" placeholder="Search package, rider, location...">
                </div>
            </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full text-left">
                <thead class="bg-slate-50/80 text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Package</th>
                        <th class="px-5 py-4">Rider</th>
                        <th class="px-5 py-4">Old Location</th>
                        <th class="px-5 py-4">New Location</th>
                        <th class="px-5 py-4">Changed</th>
                        <th class="px-5 py-4 text-right">Proof</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-orange-50/20">
                            <td class="px-5 py-4">
                                <a :href="packageUrl(row)" class="font-extrabold text-blue-600 underline decoration-blue-200" x-text="row.tracking_code || '-'"></a>
                                <p class="mt-1 text-xs font-medium text-slate-500" x-text="row.description || row.shipment_number || ''"></p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-900" x-text="row.driver?.name || '-'"></p>
                                <p class="mt-1 text-xs text-slate-500" x-text="row.driver?.phone || ''"></p>
                            </td>
                            <td class="max-w-xs px-5 py-4 text-slate-500" x-text="row.old_location || '-'"></td>
                            <td class="max-w-xs px-5 py-4 font-semibold text-slate-900" x-text="row.new_location || '-'"></td>
                            <td class="px-5 py-4 text-slate-600" x-text="formatDate(row.changed_at)"></td>
                            <td class="px-5 py-4 text-right">
                                <a x-show="row.proof_photo_url" :href="row.proof_photo_url" target="_blank" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">View Photo</a>
                                <span x-show="!row.proof_photo_url" class="text-slate-400">-</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="space-y-3 p-4 lg:hidden">
            <template x-for="row in rows" :key="row.id">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a :href="packageUrl(row)" class="font-extrabold text-blue-600" x-text="row.tracking_code || '-'"></a>
                            <p class="mt-1 text-xs text-slate-500" x-text="formatDate(row.changed_at)"></p>
                        </div>
                        <a x-show="row.proof_photo_url" :href="row.proof_photo_url" target="_blank" class="rounded-xl bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">Photo</a>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <p><span class="font-bold text-slate-500">Rider:</span> <span x-text="row.driver?.name || '-'"></span></p>
                        <p><span class="font-bold text-slate-500">Old:</span> <span x-text="row.old_location || '-'"></span></p>
                        <p><span class="font-bold text-slate-500">New:</span> <span class="font-semibold" x-text="row.new_location || '-'"></span></p>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loading && rows.length === 0" class="px-5 py-14 text-center text-sm font-semibold text-slate-500">No location changes found.</div>
    </div>
</div>

<script>
function riderLocationChangesPage() {
    return {
        rows: [],
        loading: false,
        search: '',
        async load() {
            this.loading = true;
            const url = new URL(@js(route('warehouse.package-location-changes.data')), window.location.origin);
            if (this.search) url.searchParams.set('search', this.search);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.rows = json.data || [];
            this.loading = false;
        },
        formatDate(value) {
            if (!value) return '-';
            return new Intl.DateTimeFormat('en-GH', { dateStyle: 'medium', timeStyle: 'short', hour12: true }).format(new Date(value));
        },
        packageUrl(row) {
            return row.id ? @js(route('warehouse.packages.index')) + '?search=' + encodeURIComponent(row.tracking_code || '') : '#';
        },
    };
}
</script>
@endsection
