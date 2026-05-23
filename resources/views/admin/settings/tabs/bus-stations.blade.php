@php($canEditSettings = auth('admin')->user()?->hasPermission('settings.edit') ?? false)

<style>
    .bus-station-form-row {
        display: grid;
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .bus-station-form-row {
            grid-template-columns: minmax(0, 1fr) 12rem;
            align-items: start;
        }
    }
</style>

<div x-data="busStationsManager(@js($tabData['stations'] ?? []))" @@bus-station-create.window="openCreate()" class="space-y-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
        <div class="w-full xl:max-w-md">
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       x-model="search"
                       @@input.debounce.300ms="refreshData()"
                       placeholder="Search bus stations..."
                       class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
            <div x-data="{ open: false }" class="relative">
                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/>
                    </svg>
                    View
                </button>
                <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                    <template x-for="column in columns" :key="column.key">
                        <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <span x-text="column.label"></span>
                            <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </template>
                </div>
            </div>

            <div x-data="{ open: false }" class="relative">
                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </button>
                <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                    <button type="button" @@click="exportCsv(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                    <button type="button" @@click="printTable(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-[940px] w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th x-show="visibleColumns.station" class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Station</th>
                        <th x-show="visibleColumns.location" class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Location Hint</th>
                        <th x-show="visibleColumns.order" class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Order</th>
                        <th x-show="visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        @if($canEditSettings)
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    <template x-if="pagedRows().length === 0">
                        <tr>
                            <td colspan="{{ $canEditSettings ? 5 : 4 }}" class="px-4 py-10 text-center">
                                <p class="text-sm font-medium text-slate-500">No bus stations match your search</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="station in pagedRows()" :key="station.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.station" class="whitespace-nowrap px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900" x-text="station.name"></p>
                                    <p class="mt-0.5 text-[11px] font-semibold text-slate-400" x-text="station.slug || '-'"></p>
                                </div>
                            </td>
                            <td x-show="visibleColumns.location" class="px-4 py-3 text-sm font-medium text-slate-600">
                                <p class="max-w-md truncate" x-text="station.location_hint || '-'"></p>
                            </td>
                            <td x-show="visibleColumns.order" class="whitespace-nowrap px-4 py-3 text-center text-sm font-bold text-slate-700" x-text="station.sort_order || 0"></td>
                            <td x-show="visibleColumns.status" class="whitespace-nowrap px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold" :class="station.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="station.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            @if($canEditSettings)
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" @@click="openEdit(station)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Edit</button>
                                        <button type="button" @@click="toggle(station)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold transition hover:bg-slate-50" :class="station.is_active ? 'text-amber-700' : 'text-emerald-700'" x-text="station.is_active ? 'Disable' : 'Enable'"></button>
                                        <button type="button" @@click="remove(station)" class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">Delete</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 bg-slate-50/40 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-slate-600">Showing <span x-text="metaFrom()"></span> to <span x-text="metaTo()"></span> of <span x-text="filteredRows().length"></span></p>
                <div class="flex items-center gap-3">
                    <select x-model.number="perPage" @@change="setPerPage(perPage)" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-bold text-slate-700">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <button type="button" @@click="prevPage()" :disabled="currentPage <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Prev</button>
                    <span class="text-sm font-bold text-slate-600">Page <span x-text="currentPage"></span> of <span x-text="lastPage()"></span></span>
                    <button type="button" @@click="nextPage()" :disabled="currentPage >= lastPage()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </div>

    @if($canEditSettings)
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[120] flex min-h-screen w-screen items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" style="display:none">
                <button type="button" class="absolute inset-0" @@click="closeModal()"></button>
                <div class="relative w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xl font-black text-slate-950" x-text="editingId ? 'Edit Bus Station' : 'Add Bus Station'"></h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Create station shortcuts riders can select during handoff.</p>
                            </div>
                            <button type="button" @@click="closeModal()" class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 transition hover:text-slate-900">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <form @@submit.prevent="save()" class="space-y-4 p-6">
                        <div class="bus-station-form-row">
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Station Name</label>
                                <input x-model="form.name" required maxlength="120" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Accra Neoplan Station">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Sort Order</label>
                                <input x-model.number="form.sort_order" type="number" min="0" max="9999" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Location Hint</label>
                            <textarea x-model="form.location_hint" maxlength="180" rows="3" class="w-full resize-none rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Optional town, area, branch, or loading yard hint."></textarea>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span>
                                <span class="block text-sm font-black text-slate-900">Active</span>
                                <span class="block text-xs font-semibold text-slate-500">Show this station on the rider mobile handoff field.</span>
                            </span>
                            <input x-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        </label>

                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                            <button type="button" @@click="closeModal()" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" :disabled="saving" class="rounded-2xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save Station'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-[130] flex min-h-screen w-screen items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" style="display:none" @@keydown.escape.window="closeConfirm()">
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @@click.outside="closeConfirm()">
                    <div class="px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-black text-slate-900">Delete Bus Station</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    This removes <span class="font-bold text-slate-900" x-text="deleteTarget?.name || 'this bus station'"></span> from the rider dropdown. Existing handoff records keep their saved station names.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" @@click="closeConfirm()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" @@click="deleteStation()" :disabled="saving" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50" x-text="saving ? 'Deleting...' : 'Delete Station'"></button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('busStationsManager', (initialStations = []) => ({
        stations: initialStations,
        modalOpen: false,
        confirmModalOpen: false,
        deleteTarget: null,
        editingId: null,
        saving: false,
        search: '',
        currentPage: 1,
        perPage: 25,
        columns: [
            { key: 'station', label: 'Station' },
            { key: 'location', label: 'Location Hint' },
            { key: 'order', label: 'Order' },
            { key: 'status', label: 'Status' },
        ],
        visibleColumns: { station: true, location: true, order: true, status: true },
        form: { name: '', location_hint: '', sort_order: 1, is_active: true },

        refreshData() { this.currentPage = 1; },
        setPerPage(value) { this.perPage = Number(value) || 25; this.currentPage = 1; },
        lastPage() { return Math.max(Math.ceil(this.filteredRows().length / this.perPage), 1); },
        prevPage() { if (this.currentPage > 1) this.currentPage--; },
        nextPage() { if (this.currentPage < this.lastPage()) this.currentPage++; },
        metaFrom() { return this.filteredRows().length ? ((this.currentPage - 1) * this.perPage) + 1 : 0; },
        metaTo() { return Math.min(this.currentPage * this.perPage, this.filteredRows().length); },
        toggleColumn(key) { this.visibleColumns[key] = !this.visibleColumns[key]; },
        sortedRows() {
            return [...this.stations].sort((a, b) => Number(b.id || 0) - Number(a.id || 0));
        },
        filteredRows() {
            const search = this.search.trim().toLowerCase();
            return this.sortedRows().filter((station) => !search
                || String(station.name || '').toLowerCase().includes(search)
                || String(station.slug || '').toLowerCase().includes(search)
                || String(station.location_hint || '').toLowerCase().includes(search));
        },
        pagedRows() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredRows().slice(start, start + this.perPage);
        },
        exportRows() {
            return this.filteredRows().map((station) => [
                station.name || '',
                station.location_hint || '',
                station.sort_order || 0,
                station.is_active ? 'Active' : 'Inactive',
            ]);
        },
        exportCsv() {
            const rows = this.exportRows();
            const csv = [['Station', 'Location Hint', 'Order', 'Status'], ...rows]
                .map((row) => row.map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(','))
                .join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = 'bus-stations.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        printTable() {
            const rows = this.exportRows();
            const win = window.open('', '_blank');
            if (!win) {
                window.showToast?.('Pop-up blocked. Allow pop-ups to print this table.', 'error');
                return;
            }
            win.document.body.innerHTML = `<style>body{font-family:system-ui,sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:11px}th{background:#f1f5f9}</style><h1>Bus Stations</h1><table><thead><tr><th>Station</th><th>Location Hint</th><th>Order</th><th>Status</th></tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${cell || '-'}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
            setTimeout(() => win.print(), 250);
        },
        openCreate() {
            const nextOrder = this.stations.length ? Math.max(...this.stations.map((station) => Number(station.sort_order || 0))) + 1 : 1;
            this.editingId = null;
            this.form = { name: '', location_hint: '', sort_order: nextOrder, is_active: true };
            this.modalOpen = true;
        },
        openEdit(station) {
            this.editingId = station.id;
            this.form = {
                name: station.name || '',
                location_hint: station.location_hint || '',
                sort_order: Number(station.sort_order || 0),
                is_active: Boolean(station.is_active),
            };
            this.modalOpen = true;
        },
        closeModal() { this.modalOpen = false; this.editingId = null; },
        closeConfirm() { if (!this.saving) { this.confirmModalOpen = false; this.deleteTarget = null; } },
        async save() {
            this.saving = true;
            try {
                const url = this.editingId
                    ? window.settingsConfig.busStationsUpdateEndpoint.replace('__ID__', this.editingId)
                    : window.settingsConfig.busStationsStoreEndpoint;
                const response = await fetch(url, {
                    method: this.editingId ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to save bus station.');
                const row = data.bus_station;
                const index = this.stations.findIndex((station) => Number(station.id) === Number(row.id));
                if (index >= 0) this.stations.splice(index, 1, row);
                else this.stations.push(row);
                window.showToast?.(data.message || 'Bus station saved.', 'success');
                this.closeModal();
            } catch (error) {
                window.showToast?.(error.message || 'Unable to save bus station.', 'error');
            } finally {
                this.saving = false;
            }
        },
        async toggle(station) {
            try {
                const response = await fetch(window.settingsConfig.busStationsToggleEndpoint.replace('__ID__', station.id), {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.settingsConfig.csrfToken },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to update bus station.');
                const index = this.stations.findIndex((row) => Number(row.id) === Number(station.id));
                if (index >= 0) this.stations.splice(index, 1, data.bus_station);
                window.showToast?.(data.message || 'Bus station updated.', 'success');
            } catch (error) {
                window.showToast?.(error.message || 'Unable to update bus station.', 'error');
            }
        },
        remove(station) {
            this.deleteTarget = station;
            this.confirmModalOpen = true;
        },
        async deleteStation() {
            if (!this.deleteTarget?.id || this.saving) return;
            this.saving = true;
            try {
                const response = await fetch(window.settingsConfig.busStationsDeleteEndpoint.replace('__ID__', this.deleteTarget.id), {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.settingsConfig.csrfToken },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to delete bus station.');
                this.stations = this.stations.filter((row) => Number(row.id) !== Number(this.deleteTarget.id));
                if (this.currentPage > this.lastPage()) this.currentPage = this.lastPage();
                window.showToast?.(data.message || 'Bus station deleted.', 'success');
                this.confirmModalOpen = false;
                this.deleteTarget = null;
            } catch (error) {
                window.showToast?.(error.message || 'Unable to delete bus station.', 'error');
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endpush
