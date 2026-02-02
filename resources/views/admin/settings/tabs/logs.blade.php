<div x-data="systemLogsTable()" x-init="loadData()">
    <!-- Controls -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-6">
        <div class="flex flex-1 flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <input type="text"
                       x-model="search"
                       @@input.debounce.500ms="loadData()"
                       placeholder="Search logs..."
                       class="w-full px-3.5 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Level Filter -->
            <select x-model="levelFilter"
                    @@change="loadData()"
                    class="px-3.5 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                <option value="">All Levels</option>
                <option value="emergency">Emergency</option>
                <option value="alert">Alert</option>
                <option value="critical">Critical</option>
                <option value="error">Error</option>
                <option value="warning">Warning</option>
                <option value="notice">Notice</option>
                <option value="info">Info</option>
                <option value="debug">Debug</option>
            </select>

            <!-- Date Range -->
            <input type="date"
                   x-model="dateFrom"
                   @@change="loadData()"
                   class="px-3.5 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                   placeholder="From">
            <input type="date"
                   x-model="dateTo"
                   @@change="loadData()"
                   class="px-3.5 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                   placeholder="To">
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <!-- Export -->
            <div x-data="{ open: false }" class="relative">
                <button @@click="open = !open"
                        class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </button>
                <div x-show="open" @@click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-36 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border border-slate-200/60 py-1 z-50">
                    <button type="button" @@click="exportLogs('txt'); open = false"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Text (.log)
                    </button>
                    <button type="button" @@click="exportLogs('json'); open = false"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        JSON
                    </button>
                </div>
            </div>

            <!-- Refresh -->
            <button type="button"
                    @@click="loadData()"
                    class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            <!-- Clear Logs -->
            <button type="button"
                    @@click="clearLogs()"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200/70 rounded-xl text-sm font-medium text-red-600 hover:bg-red-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear All
            </button>
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
    <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
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
            <thead class="bg-slate-50/50">
                <tr>
                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-40">Date</th>
                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-24">Level</th>
                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Message</th>
                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-20">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/50">
                <template x-for="log in logs" :key="log.id">
                    <tr class="hover:bg-slate-50/50">
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
        <div x-show="logs.length > 0" class="px-4 py-3 border-t border-slate-200/50 bg-slate-50/30">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-600">
                    Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> entries
                </div>
                <div class="flex items-center gap-2">
                    <select x-model="perPage" @@change="loadData()"
                            class="px-2 py-1 border border-slate-200/70 rounded-lg bg-white/70 text-xs text-slate-700">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div class="flex gap-1">
                        <button @@click="goToPage(1)" :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                        </button>
                        <button @@click="goToPage(meta.current_page - 1)" :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="px-3 py-1 text-xs text-slate-600" x-text="meta.current_page + ' / ' + meta.last_page"></span>
                        <button @@click="goToPage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <button @@click="goToPage(meta.last_page)" :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                        </button>
                    </div>
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
        perPage: 50,
        meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
        stats: { error: 0, warning: 0, info: 0, debug: 0 },
        showModal: false,
        selectedLog: null,

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
