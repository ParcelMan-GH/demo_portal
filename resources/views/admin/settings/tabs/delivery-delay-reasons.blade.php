@php($canEditSettings = auth('admin')->user()?->hasPermission('settings.edit') ?? false)

<style>
    @media (min-width: 768px) {
        .delivery-delay-reason-form-row { display: grid; grid-template-columns: minmax(0, 1fr) 10rem; gap: 1rem; align-items: start; }
    }
</style>

<div x-data="deliveryDelayReasonsManager(@js($tabData['reasons'] ?? []))" @@delivery-delay-reason-create.window="openCreate()" class="space-y-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
        <div class="w-full xl:max-w-md">
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @@input.debounce.300ms="currentPage = 1" placeholder="Search delay reasons..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
            <div x-data="{ open: false }" class="relative">
                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/></svg>
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
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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
            <table class="min-w-[760px] w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th x-show="visibleColumns.reason" class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Reason</th>
                        <th x-show="visibleColumns.order" class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Order</th>
                        <th x-show="visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        @if($canEditSettings)
                            <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    <template x-if="pagedRows().length === 0">
                        <tr><td colspan="{{ $canEditSettings ? 4 : 3 }}" class="px-4 py-10 text-center"><p class="text-sm font-medium text-slate-500">No delay reasons match your search</p></td></tr>
                    </template>
                    <template x-for="reason in pagedRows()" :key="reason.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.reason" class="whitespace-nowrap px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900" x-text="reason.label"></p>
                                <p class="mt-0.5 text-[11px] font-semibold text-slate-400" x-text="reason.slug || '-'"></p>
                            </td>
                            <td x-show="visibleColumns.order" class="whitespace-nowrap px-4 py-3 text-center text-sm font-bold text-slate-700" x-text="reason.sort_order || 0"></td>
                            <td x-show="visibleColumns.status" class="whitespace-nowrap px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold" :class="reason.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="reason.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            @if($canEditSettings)
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" @@click="openEdit(reason)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Edit</button>
                                        <button type="button" @@click="toggle(reason)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold transition hover:bg-slate-50" :class="reason.is_active ? 'text-amber-700' : 'text-emerald-700'" x-text="reason.is_active ? 'Disable' : 'Enable'"></button>
                                        <button type="button" @@click="remove(reason)" class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">Delete</button>
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
                <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xl font-black text-slate-950" x-text="editingId ? 'Edit Delivery Delay Reason' : 'Add Delivery Delay Reason'"></h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Reasons appear when admins send manual delay notices.</p>
                            </div>
                            <button type="button" @@click="closeModal()" class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 transition hover:text-slate-900">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <form @@submit.prevent="save()">
                        <div class="space-y-4 p-6">
                            <div class="delivery-delay-reason-form-row">
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Reason</label>
                                    <input x-model="form.label" required maxlength="120" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Traffic delay">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Order</label>
                                    <input x-model.number="form.sort_order" type="number" min="0" max="9999" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                </div>
                            </div>
                            <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span>
                                    <span class="block text-sm font-black text-slate-900">Active</span>
                                    <span class="block text-xs font-semibold text-slate-500">Show this reason when sending delay notices.</span>
                                </span>
                                <input x-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                            </label>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50/80 px-6 py-4">
                            <button type="button" @@click="closeModal()" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" :disabled="saving" class="rounded-2xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save Reason'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-[130] flex min-h-screen w-screen items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" style="display:none" @@keydown.escape.window="closeConfirm()">
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @@click.outside="closeConfirm()">
                    <div class="px-6 py-5">
                        <h3 class="text-base font-black text-slate-900">Delete Delivery Delay Reason</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">This removes <span class="font-bold text-slate-900" x-text="deleteTarget?.label || 'this reason'"></span> from future delay forms. Existing records keep their saved reason.</p>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" @@click="closeConfirm()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" @@click="deleteReason()" :disabled="saving" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50" x-text="saving ? 'Deleting...' : 'Delete Reason'"></button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deliveryDelayReasonsManager', (initialReasons = []) => ({
        reasons: initialReasons,
        modalOpen: false,
        confirmModalOpen: false,
        deleteTarget: null,
        editingId: null,
        saving: false,
        search: '',
        currentPage: 1,
        perPage: 25,
        columns: [
            { key: 'reason', label: 'Reason' },
            { key: 'order', label: 'Order' },
            { key: 'status', label: 'Status' },
        ],
        visibleColumns: { reason: true, order: true, status: true },
        form: { label: '', sort_order: 1, is_active: true },
        setPerPage(value) { this.perPage = Number(value) || 25; this.currentPage = 1; },
        lastPage() { return Math.max(Math.ceil(this.filteredRows().length / this.perPage), 1); },
        prevPage() { if (this.currentPage > 1) this.currentPage--; },
        nextPage() { if (this.currentPage < this.lastPage()) this.currentPage++; },
        metaFrom() { return this.filteredRows().length ? ((this.currentPage - 1) * this.perPage) + 1 : 0; },
        metaTo() { return Math.min(this.currentPage * this.perPage, this.filteredRows().length); },
        toggleColumn(key) { this.visibleColumns[key] = !this.visibleColumns[key]; },
        sortedRows() { return [...this.reasons].sort((a, b) => Number(b.id || 0) - Number(a.id || 0)); },
        filteredRows() {
            const search = this.search.trim().toLowerCase();
            return this.sortedRows().filter((reason) => !search
                || String(reason.label || '').toLowerCase().includes(search)
                || String(reason.slug || '').toLowerCase().includes(search));
        },
        pagedRows() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredRows().slice(start, start + this.perPage);
        },
        exportCsv() {
            const rows = [['Reason', 'Order', 'Status'], ...this.filteredRows().map((reason) => [reason.label, reason.sort_order || 0, reason.is_active ? 'Active' : 'Inactive'])];
            const csv = rows.map((row) => row.map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(',')).join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
            link.download = 'delivery-delay-reasons.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        },
        printTable() {
            const rows = this.filteredRows().map((reason) => [reason.label, reason.sort_order || 0, reason.is_active ? 'Active' : 'Inactive']);
            const win = window.open('', '_blank');
            if (!win) return;
            win.document.body.innerHTML = `<style>body{font-family:system-ui,sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:11px}th{background:#f1f5f9}</style><h1>Delivery Delay Reasons</h1><table><thead><tr><th>Reason</th><th>Order</th><th>Status</th></tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${cell || '-'}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
            win.document.close();
            win.print();
        },
        resetForm() {
            this.editingId = null;
            this.form = { label: '', sort_order: (Math.max(0, ...this.reasons.map((r) => Number(r.sort_order || 0))) + 1), is_active: true };
        },
        openCreate() { this.resetForm(); this.modalOpen = true; },
        openEdit(reason) {
            this.editingId = reason.id;
            this.form = { label: reason.label || '', sort_order: Number(reason.sort_order || 0), is_active: !!reason.is_active };
            this.modalOpen = true;
        },
        closeModal() { if (!this.saving) this.modalOpen = false; },
        async save() {
            this.saving = true;
            try {
                const endpoint = this.editingId
                    ? window.settingsConfig.deliveryDelayReasonsUpdateEndpoint.replace('__ID__', this.editingId)
                    : window.settingsConfig.deliveryDelayReasonsStoreEndpoint;
                const res = await fetch(endpoint, {
                    method: this.editingId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.settingsConfig.csrfToken },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) throw new Error(data.message || 'Unable to save reason.');
                const row = data.reason;
                const index = this.reasons.findIndex((reason) => Number(reason.id) === Number(row.id));
                if (index >= 0) this.reasons.splice(index, 1, row);
                else this.reasons.unshift(row);
                this.modalOpen = false;
                window.showToast?.(data.message || 'Reason saved.', 'success');
            } catch (error) {
                window.showToast?.(error.message || 'Unable to save reason.', 'error');
            } finally {
                this.saving = false;
            }
        },
        async toggle(reason) {
            this.saving = true;
            try {
                const res = await fetch(window.settingsConfig.deliveryDelayReasonsToggleEndpoint.replace('__ID__', reason.id), {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.settingsConfig.csrfToken },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) throw new Error(data.message || 'Unable to update reason.');
                const index = this.reasons.findIndex((row) => Number(row.id) === Number(data.reason.id));
                if (index >= 0) this.reasons.splice(index, 1, data.reason);
                window.showToast?.(data.message || 'Reason updated.', 'success');
            } catch (error) {
                window.showToast?.(error.message || 'Unable to update reason.', 'error');
            } finally {
                this.saving = false;
            }
        },
        remove(reason) { this.deleteTarget = reason; this.confirmModalOpen = true; },
        closeConfirm() { if (!this.saving) { this.confirmModalOpen = false; this.deleteTarget = null; } },
        async deleteReason() {
            if (!this.deleteTarget) return;
            this.saving = true;
            try {
                const res = await fetch(window.settingsConfig.deliveryDelayReasonsDeleteEndpoint.replace('__ID__', this.deleteTarget.id), {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.settingsConfig.csrfToken },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) throw new Error(data.message || 'Unable to delete reason.');
                this.reasons = this.reasons.filter((reason) => Number(reason.id) !== Number(this.deleteTarget.id));
                this.confirmModalOpen = false;
                this.deleteTarget = null;
                window.showToast?.(data.message || 'Reason deleted.', 'success');
            } catch (error) {
                window.showToast?.(error.message || 'Unable to delete reason.', 'error');
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endpush
