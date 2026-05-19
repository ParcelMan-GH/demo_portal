<div x-data="emailLogsTable()" x-init="loadData(); initDateRange()" class="space-y-4">
    <div class="border-b border-slate-100 pb-4">
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="w-full xl:max-w-md">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @@input.debounce.500ms="refreshData()" placeholder="Search recipient, subject, or status..."
                           class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                <button type="button" @@click="showFilters = !showFilters"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                </button>
                <button type="button" @@click="loadData()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div x-show="showFilters" x-transition class="mb-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4" style="display:none">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                    <select x-model="statusFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All statuses</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Range</label>
                    <input type="text" x-ref="dateRange" placeholder="Select date range" readonly class="w-full max-w-sm cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div x-show="loading" x-transition.opacity class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Recipient</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                        <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Sent At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    <template x-if="!loading && logs.length === 0">
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No email logs match the current filters</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900" x-text="log.recipient || '-'"></td>
                            <td class="px-4 py-3 text-sm text-slate-600" x-text="log.subject || '-'"></td>
                            <td class="px-4 py-3 text-center"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold" :class="statusClass(log.status)" x-text="log.status || '-'"></span></td>
                            <td class="px-4 py-3 text-sm text-slate-500" x-text="log.sent_at || '-'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 bg-slate-50/40 px-4 py-3" x-show="meta.total >= 0">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-slate-600">Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span></p>
                <div class="flex items-center gap-3">
                    <select x-model="perPage" @@change="setPerPage(perPage)" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-bold text-slate-700">
                        <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                    </select>
                    <button type="button" @@click="prevPage()" :disabled="meta.current_page <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Prev</button>
                    <span class="text-sm font-bold text-slate-600">Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></span>
                    <button type="button" @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('emailLogsTable', () => ({
        logs: [],
        loading: true,
        search: '',
        statusFilter: '',
        dateFrom: '',
        dateTo: '',
        showFilters: false,
        dateRangePicker: null,
        perPage: 25,
        meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
        statusClass(status) {
            if (status === 'sent') return 'bg-emerald-50 text-emerald-700';
            if (status === 'failed') return 'bg-rose-50 text-rose-700';
            return 'bg-amber-50 text-amber-700';
        },
        refreshData() { this.meta.current_page = 1; this.loadData(); },
        initDateRange() {
            this.$nextTick(() => window.setupSettingsDateRangePicker?.(this, this.$refs.dateRange, 'dateFrom', 'dateTo', () => this.refreshData()));
        },
        clearDateRange() {
            this.dateFrom = '';
            this.dateTo = '';
            if (this.$refs.dateRange) this.$refs.dateRange.value = '';
        },
        applyFilters() { this.refreshData(); },
        clearFilters() { this.statusFilter = ''; this.dateFrom = ''; this.dateTo = ''; this.search = ''; this.clearDateRange(); this.refreshData(); },
        setPerPage(value) { this.perPage = value; this.meta.current_page = 1; this.loadData(); },
        prevPage() { if (this.meta.current_page > 1) { this.meta.current_page--; this.loadData(); } },
        nextPage() { if (this.meta.current_page < this.meta.last_page) { this.meta.current_page++; this.loadData(); } },
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.meta.current_page, per_page: this.perPage });
                if (this.search.trim()) params.append('search', this.search.trim());
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);
                const response = await fetch(window.settingsConfig.emailLogsDataEndpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const result = await response.json();
                this.logs = Array.isArray(result.data) ? result.data : [];
                this.meta = { ...this.meta, ...(result.meta || {}) };
            } catch (error) {
                this.logs = [];
                console.error('Failed to load email logs:', error);
            } finally {
                this.loading = false;
            }
        },
    }));
});
</script>
@endpush
