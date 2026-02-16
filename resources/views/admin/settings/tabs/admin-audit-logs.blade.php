<div x-data="adminAuditLogsTable()" x-init="loadData()">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-wrap items-center gap-3">
            <div class="relative min-w-[220px] flex-1 lg:max-w-sm">
                <input type="text"
                       x-model="filters.search"
                       @@input.debounce.500ms="refreshData()"
                       placeholder="Search actor, action, route..."
                       class="w-full rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 pr-10 text-sm text-slate-900 placeholder-slate-400 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                <svg class="absolute right-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select x-model="filters.scope" @@change="refreshData()"
                    class="rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 text-sm text-slate-900 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                <option value="">All scopes</option>
                <option value="system">System users</option>
                <option value="warehouse">Warehouse users</option>
            </select>

            <select x-model="filters.action_type" @@change="refreshData()"
                    class="rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 text-sm text-slate-900 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                <option value="">All actions</option>
                <option value="auth">Auth</option>
                <option value="read">Read</option>
                <option value="create">Create</option>
                <option value="update">Update</option>
                <option value="delete">Delete</option>
                <option value="export">Export</option>
                <option value="error">Error</option>
                <option value="other">Other</option>
            </select>

            <select x-model="filters.method" @@change="refreshData()"
                    class="rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 text-sm text-slate-900 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                <option value="">All methods</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
            </select>

            <input type="date"
                   x-model="filters.date_from"
                   @@change="refreshData()"
                   class="rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 text-sm text-slate-900 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
            <input type="date"
                   x-model="filters.date_to"
                   @@change="refreshData()"
                   class="rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 text-sm text-slate-900 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
        </div>

        <div class="flex items-center gap-2">
            <div x-data="{ open: false }" class="relative">
                <button type="button"
                        @@click="open = !open"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/70 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-white/90">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </button>
                <div x-show="open" @@click.away="open = false" x-transition
                     class="absolute right-0 z-50 mt-2 w-36 rounded-xl border border-slate-200/70 bg-white/95 py-1 shadow-lg">
                    <button type="button" @@click="exportLogs('json'); open = false"
                            class="block w-full px-3 py-2 text-left text-sm text-slate-700 transition-colors hover:bg-slate-50">JSON</button>
                    <button type="button" @@click="exportLogs('csv'); open = false"
                            class="block w-full px-3 py-2 text-left text-sm text-slate-700 transition-colors hover:bg-slate-50">CSV</button>
                </div>
            </div>

            <button type="button"
                    @@click="loadData()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/70 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-white/90">
                <svg class="h-4 w-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/50">
        <div x-show="loading" class="flex items-center justify-center py-12">
            <svg class="h-6 w-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <template x-if="!loading && logs.length === 0">
            <div class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3l8 3v6c0 5-3.5 9.5-8 11-4.5-1.5-8-6-8-11V6l8-3z"/>
                </svg>
                <h3 class="mt-4 text-sm font-medium text-slate-900">No audit logs</h3>
                <p class="mt-1 text-sm text-slate-500">Admin and warehouse user actions will appear here.</p>
            </div>
        </template>

        <div x-show="!loading && logs.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/50">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Actor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Scope</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700" x-text="log.created_at"></td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div class="font-medium" x-text="log.actor?.name || 'Unknown'"></div>
                                <div class="text-xs text-slate-500" x-text="log.actor?.email || '-'"></div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                      :class="log.scope === 'warehouse' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700'"
                                      x-text="log.scope === 'warehouse' ? 'Warehouse' : 'System'"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div class="font-medium" x-text="formatActionType(log.action_type)"></div>
                                <div class="text-xs text-slate-500" x-text="log.route_name || log.action"></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div class="font-medium" x-text="log.method || '-'"></div>
                                <div class="max-w-[260px] truncate text-xs text-slate-500" x-text="log.url || '-'"></div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                <div class="font-medium" x-text="log.status_code || '-'"></div>
                                <div class="text-xs text-slate-500" x-text="(log.duration_ms ?? '-') + ' ms'"></div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        @@click="openDetails(log)"
                                        class="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between" x-show="!loading && meta.total > 0">
        <p class="text-sm text-slate-600">
            Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span>
        </p>
        <div class="flex items-center gap-2">
            <button type="button"
                    @@click="prevPage()"
                    :disabled="meta.current_page <= 1"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                Previous
            </button>
            <span class="text-xs text-slate-600">
                Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span>
            </span>
            <button type="button"
                    @@click="nextPage()"
                    :disabled="meta.current_page >= meta.last_page"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                Next
            </button>
        </div>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="closeModal()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div @@click.stop class="relative w-full max-w-4xl rounded-2xl border border-slate-200/50 bg-white/95 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200/50 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Audit Log Details</h3>
                    <button @@click="closeModal()" class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-500">Actor</p>
                            <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedLog?.actor?.name || 'Unknown'"></p>
                            <p class="text-xs text-slate-500" x-text="selectedLog?.actor?.email || '-'"></p>
                        </div>
                        <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-500">Action</p>
                            <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedLog?.action || '-'"></p>
                            <p class="text-xs text-slate-500" x-text="selectedLog?.description || '-'"></p>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Metadata</p>
                        <pre class="max-h-80 overflow-auto rounded-xl bg-slate-900 p-4 text-xs text-slate-100"><code x-text="prettyMetadata"></code></pre>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200/50 px-6 py-4">
                    <button type="button"
                            @@click="copyDetails()"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200">
                        Copy
                    </button>
                    <button type="button"
                            @@click="closeModal()"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-slate-800">
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
    Alpine.data('adminAuditLogsTable', () => ({
        logs: [],
        loading: true,
        showModal: false,
        selectedLog: null,
        prettyMetadata: '',
        filters: {
            search: '',
            scope: '',
            action_type: '',
            method: '',
            date_from: '',
            date_to: '',
        },
        meta: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
        },

        formatActionType(value) {
            if (!value) return '-';
            return String(value).replaceAll('_', ' ').replace(/\b\w/g, m => m.toUpperCase());
        },

        async refreshData() {
            this.meta.current_page = 1;
            await this.loadData();
        },

        async prevPage() {
            if (this.meta.current_page <= 1) return;
            this.meta.current_page -= 1;
            await this.loadData();
        },

        async nextPage() {
            if (this.meta.current_page >= this.meta.last_page) return;
            this.meta.current_page += 1;
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    per_page: this.meta.per_page,
                    ...Object.fromEntries(Object.entries(this.filters).filter(([, value]) => String(value || '').trim() !== '')),
                });

                const response = await fetch(window.settingsConfig.adminAuditLogsDataEndpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                this.logs = Array.isArray(result.data) ? result.data : [];
                this.meta = { ...this.meta, ...(result.meta || {}) };
            } catch (error) {
                this.logs = [];
                if (window.showToast) {
                    window.showToast('Failed to load audit logs.', 'error');
                }
            } finally {
                this.loading = false;
            }
        },

        openDetails(log) {
            this.selectedLog = log;
            this.prettyMetadata = JSON.stringify(log.metadata || {}, null, 2);
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedLog = null;
            this.prettyMetadata = '';
        },

        copyDetails() {
            if (!this.prettyMetadata) return;
            navigator.clipboard.writeText(this.prettyMetadata).then(() => {
                if (window.showToast) {
                    window.showToast('Audit metadata copied.', 'success');
                }
            }).catch(() => {
                if (window.showToast) {
                    window.showToast('Unable to copy metadata.', 'error');
                }
            });
        },

        exportLogs(format) {
            const params = new URLSearchParams({
                format,
                ...Object.fromEntries(Object.entries(this.filters).filter(([, value]) => String(value || '').trim() !== '')),
            });
            window.location.href = window.settingsConfig.adminAuditLogsExportEndpoint + '?' + params.toString();
        },
    }));
});
</script>
@endpush

