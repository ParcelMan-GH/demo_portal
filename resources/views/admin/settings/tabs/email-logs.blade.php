<div x-data="emailLogsTable()" x-init="loadData()">
    <!-- Controls -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div class="flex flex-1 items-center gap-3">
            <div class="relative flex-1 max-w-xs">
                <input type="text"
                       x-model="search"
                       @@input.debounce.500ms="loadData()"
                       placeholder="Search emails..."
                       class="w-full px-3.5 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200/50">
        <div x-show="loading" class="flex items-center justify-center py-12">
            <svg class="w-6 h-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <template x-if="!loading && logs.length === 0">
            <div class="text-center py-12">
                <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3 class="mt-4 text-sm font-medium text-slate-900">No email logs</h3>
                <p class="mt-1 text-sm text-slate-500">Email logs will appear here once emails are sent</p>
            </div>
        </template>

        <table x-show="!loading && logs.length > 0" class="min-w-full divide-y divide-slate-200/50">
            <thead class="bg-slate-50/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sent At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/50">
                <template x-for="log in logs" :key="log.id">
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-sm text-slate-900" x-text="log.recipient"></td>
                        <td class="px-4 py-3 text-sm text-slate-600" x-text="log.subject"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                  :class="log.status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                  x-text="log.status"></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500" x-text="log.sent_at"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('emailLogsTable', () => ({
        logs: [],
        loading: true,
        search: '',
        meta: { total: 0, current_page: 1, last_page: 1 },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.meta.current_page });
                if (this.search) params.append('search', this.search);

                const response = await fetch(window.settingsConfig.emailLogsDataEndpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                this.logs = result.data;
                this.meta = result.meta;
            } catch (error) {
                console.error('Failed to load email logs:', error);
            } finally {
                this.loading = false;
            }
        },
    }));
});
</script>
@endpush
