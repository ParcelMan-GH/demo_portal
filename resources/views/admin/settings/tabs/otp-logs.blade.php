<div x-data="otpLogsTable()" x-init="loadData()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1 sm:max-w-xs">
                <input type="text"
                       x-model="search"
                       @@input.debounce.500ms="refreshData()"
                       placeholder="Search phone, purpose, OTP..."
                       class="w-full rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 pr-10 text-sm text-slate-900 placeholder-slate-400 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                <svg class="absolute right-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select x-model="purpose"
                    @@change="refreshData()"
                    class="rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2 text-sm text-slate-900 backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                <option value="">All purposes</option>
                <option value="login">Login</option>
                <option value="registration">Registration</option>
                <option value="delivery_verification">Delivery Verification</option>
            </select>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="mt-4 text-sm font-medium text-slate-900">No OTP logs</h3>
                <p class="mt-1 text-sm text-slate-500">OTP records will appear here once OTPs are requested.</p>
            </div>
        </template>

        <div x-show="!loading && logs.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/50">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Purpose</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Verified At</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Expires At</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-900" x-text="log.phone"></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700" x-text="log.code"></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600" x-text="formatPurpose(log.purpose)"></td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                      :class="statusClass(log)">
                                    <span x-text="statusLabel(log)"></span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500" x-text="log.verified_at || '-'"></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500" x-text="log.expires_at || '-'"></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500" x-text="log.created_at || '-'"></td>
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
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('otpLogsTable', () => ({
        logs: [],
        loading: true,
        search: '',
        purpose: '',
        meta: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
        },

        async refreshData() {
            this.meta.current_page = 1;
            await this.loadData();
        },

        async prevPage() {
            if (this.meta.current_page <= 1) {
                return;
            }
            this.meta.current_page -= 1;
            await this.loadData();
        },

        async nextPage() {
            if (this.meta.current_page >= this.meta.last_page) {
                return;
            }
            this.meta.current_page += 1;
            await this.loadData();
        },

        formatPurpose(value) {
            if (!value) return '-';
            return String(value).split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        },

        statusLabel(log) {
            if (log.is_verified) return 'Verified';
            if (log.is_expired) return 'Expired';
            return 'Pending';
        },

        statusClass(log) {
            if (log.is_verified) return 'bg-emerald-50 text-emerald-700';
            if (log.is_expired) return 'bg-rose-50 text-rose-700';
            return 'bg-amber-50 text-amber-700';
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    per_page: this.meta.per_page,
                });

                if (this.search.trim()) {
                    params.append('search', this.search.trim());
                }
                if (this.purpose) {
                    params.append('purpose', this.purpose);
                }

                const response = await fetch(window.settingsConfig.otpLogsDataEndpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                this.logs = Array.isArray(result.data) ? result.data : [];
                this.meta = {
                    ...this.meta,
                    ...(result.meta || {}),
                };
            } catch (error) {
                console.error('Failed to load OTP logs:', error);
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },
    }));
});
</script>
@endpush
