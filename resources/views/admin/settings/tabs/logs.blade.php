<div x-data="systemLogsTable()" x-init="loadData(); initDateRange()">
    <div class="mb-5 space-y-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="w-full xl:max-w-md">
                <div class="relative">
                    <input type="text" x-model="search" @@input.debounce.500ms="refreshData()" placeholder="Search logs..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                <button type="button" @@click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                </button>
                <div x-data="{ open: false }" class="relative">
                    <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                    <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-36 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border border-slate-200/60 py-1 z-50">
                        <button type="button" @@click="exportLogs('txt'); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">Text (.log)</button>
                        <button type="button" @@click="exportLogs('json'); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">JSON</button>
                    </div>
                </div>
                <button type="button" @@click="loadData()" title="Refresh logs" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200/70 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                <button type="button" @@click="clearLogs()" title="Clear all logs" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 shadow-sm transition hover:bg-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>

        <div x-show="showFilters" x-transition class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4" style="display:none">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Level</label>
                    <select x-model="levelFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All Levels</option><option value="emergency">Emergency</option><option value="alert">Alert</option><option value="critical">Critical</option><option value="error">Error</option><option value="warning">Warning</option><option value="notice">Notice</option><option value="info">Info</option><option value="debug">Debug</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Range</label>
                    <input type="text" x-ref="dateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="p-4 bg-red-50/70 rounded-xl border border-red-200/50">
            <div class="text-2xl font-bold text-red-600" x-text="stats.error || 0"></div>
            <div class="text-xs font-medium text-red-500 mt-1">Errors</div>
        </div>
        <div class="p-4 bg-amber-50/70 rounded-xl border border-amber-200/50">
            <div class="text-2xl font-bold text-amber-600" x-text="stats.warning || 0"></div>
            <div class="text-xs font-medium text-amber-500 mt-1">Warnings</div>
        </div>
        <div class="p-4 bg-blue-50/70 rounded-xl border border-blue-200/50">
            <div class="text-2xl font-bold text-blue-600" x-text="stats.info || 0"></div>
            <div class="text-xs font-medium text-blue-500 mt-1">Info</div>
        </div>
        <div class="p-4 bg-slate-50/70 rounded-xl border border-slate-200/50">
            <div class="text-2xl font-bold text-slate-600" x-text="stats.debug || 0"></div>
            <div class="text-xs font-medium text-slate-500 mt-1">Debug</div>
        </div>
    </div>

    <!-- Table -->
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <!-- Loading Overlay -->
        <div x-show="loading" x-transition.opacity class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center">
            <svg class="w-6 h-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <template x-if="!loading && logs.length === 0">
            <div class="text-center py-12">
                <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-4 text-sm font-medium text-slate-900">No logs found</h3>
                <p class="mt-1 text-sm text-slate-500">System logs will appear here</p>
            </div>
        </template>

        <table x-show="logs.length > 0" class="min-w-full divide-y divide-slate-200/50 text-xs">
            <thead class="bg-slate-50/70">
                <tr>
                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-40">Date</th>
                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-24">Level</th>
                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Message</th>
                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-20">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/50">
                <template x-for="log in logs" :key="log.id">
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-2.5 text-xs text-slate-500 whitespace-nowrap" x-text="log.date"></td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full uppercase"
                                  :class="{
                                      'bg-red-100 text-red-700': ['ERROR', 'EMERGENCY', 'ALERT', 'CRITICAL'].includes(log.level),
                                      'bg-amber-100 text-amber-700': log.level === 'WARNING',
                                      'bg-blue-100 text-blue-700': log.level === 'INFO',
                                      'bg-slate-100 text-slate-600': log.level === 'DEBUG',
                                      'bg-purple-100 text-purple-700': log.level === 'NOTICE'
                                  }"
                                  x-text="log.level"></span>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-700">
                            <div class="max-w-md truncate" x-text="log.message"></div>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <button type="button"
                                    @@click="viewLog(log)"
                                    class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Pagination -->
        <div x-show="logs.length > 0" class="border-t border-slate-200/70 bg-slate-50/40 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-semibold text-slate-600">
                    Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span>
                </div>
                <div class="flex items-center gap-3">
                    <select x-model="perPage" @@change="loadData()"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-bold text-slate-700">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <button type="button" @@click="goToPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Prev</button>
                    <span class="text-sm font-bold text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></span>
                    <button type="button" @@click="goToPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Detail Modal -->
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-[100] overflow-y-auto"
         @@keydown.escape.window="showModal = false">
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop
                 class="relative w-full max-w-3xl bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50">

                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200/50">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full uppercase"
                              :class="{
                                  'bg-red-100 text-red-700': ['ERROR', 'EMERGENCY', 'ALERT', 'CRITICAL'].includes(selectedLog?.level),
                                  'bg-amber-100 text-amber-700': selectedLog?.level === 'WARNING',
                                  'bg-blue-100 text-blue-700': selectedLog?.level === 'INFO',
                                  'bg-slate-100 text-slate-600': selectedLog?.level === 'DEBUG'
                              }"
                              x-text="selectedLog?.level"></span>
                        <span class="text-sm text-slate-500" x-text="selectedLog?.date"></span>
                    </div>
                    <button @@click="showModal = false" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="mb-4">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase mb-2">Message</h4>
                        <p class="text-sm text-slate-800 font-medium" x-text="selectedLog?.message"></p>
                    </div>

                    <template x-if="selectedLog?.context">
                        <div>
                            <h4 class="text-xs font-semibold text-slate-500 uppercase mb-2">Stack Trace / Context</h4>
                            <pre class="p-4 bg-slate-900 text-slate-100 rounded-xl text-xs overflow-x-auto max-h-80 overflow-y-auto"><code x-text="selectedLog?.context"></code></pre>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200/50">
                    <button type="button"
                            @@click="copyLog()"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Copy to Clipboard
                    </button>
                    <button type="button"
                            @@click="showModal = false"
                            class="px-4 py-2 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('systemLogsTable', () => ({
        logs: [],
        loading: true,
        search: '',
        levelFilter: '',
        dateFrom: '',
        dateTo: '',
        dateRangePicker: null,
        showFilters: false,
        perPage: 50,
        meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
        stats: { error: 0, warning: 0, info: 0, debug: 0 },
        showModal: false,
        selectedLog: null,

        initDateRange() {
            this.$nextTick(() => window.setupSettingsDateRangePicker?.(this, this.$refs.dateRange, 'dateFrom', 'dateTo', () => { this.meta.current_page = 1; this.loadData(); }));
        },

        refreshData() {
            this.meta.current_page = 1;
            this.loadData();
        },

        applyFilters() {
            this.refreshData();
        },

        clearFilters() {
            this.levelFilter = '';
            this.dateFrom = '';
            this.dateTo = '';
            if (this.$refs.dateRange) this.$refs.dateRange.value = '';
            this.refreshData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    per_page: this.perPage,
                });
                if (this.search) params.append('search', this.search);
                if (this.levelFilter) params.append('level', this.levelFilter);
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);

                const response = await fetch(window.settingsConfig.logsDataEndpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                this.logs = result.data;
                this.meta = result.meta;

                // Calculate stats
                this.stats = { error: 0, warning: 0, info: 0, debug: 0 };
                this.logs.forEach(log => {
                    const level = log.level.toLowerCase();
                    if (['error', 'emergency', 'alert', 'critical'].includes(level)) this.stats.error++;
                    else if (level === 'warning') this.stats.warning++;
                    else if (level === 'info') this.stats.info++;
                    else if (level === 'debug') this.stats.debug++;
                });
            } catch (error) {
                console.error('Failed to load logs:', error);
            } finally {
                this.loading = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            this.loadData();
        },

        viewLog(log) {
            this.selectedLog = log;
            this.showModal = true;
        },

        copyLog() {
            if (!this.selectedLog) return;
            const text = '[' + this.selectedLog.date + '] ' + this.selectedLog.level + ': ' + this.selectedLog.message + '\n' + (this.selectedLog.context || '');
            navigator.clipboard.writeText(text);
            alert('Copied to clipboard');
        },

        async clearLogs() {
            if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) return;

            try {
                const response = await fetch(window.settingsConfig.logsClearEndpoint, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                });
                const result = await response.json();
                if (result.success) {
                    this.loadData();
                    alert('Logs cleared successfully');
                }
            } catch (error) {
                console.error('Failed to clear logs:', error);
                alert('Failed to clear logs');
            }
        },

        exportLogs(format) {
            const params = new URLSearchParams({ format });
            window.location.href = window.settingsConfig.logsExportEndpoint + '?' + params.toString();
        },
    }));
});
</script>
@endpush
