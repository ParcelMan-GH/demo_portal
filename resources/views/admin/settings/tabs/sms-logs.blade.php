<div x-data="smsLogsTable()" x-init="loadData()">
    <!-- Controls -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div class="flex flex-1 items-center gap-3">
            <div class="relative flex-1 max-w-xs">
                <input type="text"
                       x-model="search"
                       @@input.debounce.500ms="loadData()"
                       placeholder="Search SMS logs..."
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="mt-4 text-sm font-medium text-slate-900">No SMS logs</h3>
                <p class="mt-1 text-sm text-slate-500">SMS logs will appear here once messages are sent</p>
            </div>
        </template>

        <table x-show="!loading && logs.length > 0" class="min-w-full divide-y divide-slate-200/50">
            <thead class="bg-slate-50/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Message</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sent At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/50">
                <template x-for="log in logs" :key="log.id">
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-sm text-slate-900" x-text="log.recipient"></td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate" x-text="log.message"></td>
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
    Alpine.data('smsLogsTable', () => ({
        logs: [],
        loading: true,
        search: '',
        meta: { total: 0, current_page: 1, last_page: 1 },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.meta.current_page });
                if (this.search) params.append('search', this.search);

                const response = await fetch(window.settingsConfig.smsLogsDataEndpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                this.logs = result.data;
                this.meta = result.meta;
            } catch (error) {
                console.error('Failed to load SMS logs:', error);
            } finally {
                this.loading = false;
            }
        },
    }));
});
</script>
@endpush
