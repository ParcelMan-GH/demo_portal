@extends('warehouse.layouts.app')

@section('title', 'Pending Confirmations')

@section('content')
<div
    x-data="pendingConfirmations()"
    x-init="init()"
    data-config='@json([
        "dataUrl" => route("warehouse.deliveries.pending-confirmations-data"),
        "confirmUrlTemplate" => route("warehouse.deliveries.runs.stops.confirm-handoff", ["run" => "__RUN__", "stop" => "__STOP__"]),
    ])'
>
    {{-- Hero Section --}}
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 -mx-4 -mt-4 px-4 sm:px-6 lg:px-8 py-8 mb-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-white">Pending Confirmations</h1>
            <p class="mt-1 text-sm text-slate-400">Deliveries handed to bus couriers awaiting recipient confirmation</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        {{-- Search --}}
        <div class="mb-6">
            <div class="relative max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    x-model="search"
                    @@input.debounce.400ms="currentPage = 1; fetchData()"
                    placeholder="Search recipient, courier, vehicle number..."
                    class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-slate-500/20 focus:border-slate-400 placeholder-slate-400"
                >
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Loading --}}
            <div x-show="loading" class="flex items-center justify-center py-16">
                <svg class="animate-spin h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="ml-2 text-sm text-slate-500">Loading...</span>
            </div>

            {{-- Empty State --}}
            <div x-show="!loading && stops.length === 0" class="text-center py-16 px-4">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-3 text-sm font-medium text-slate-900">No pending confirmations</h3>
                <p class="mt-1 text-xs text-slate-500">All bus courier handoffs have been confirmed.</p>
            </div>

            {{-- Table --}}
            <div x-show="!loading && stops.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50/70">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Run #</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Destination</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Courier Details</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Handed Off</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Packages</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="stop in stops" :key="stop.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                {{-- Run # --}}
                                <td class="px-4 py-3">
                                    <a :href="stop.run_url" class="text-xs font-semibold text-orange-700 hover:text-orange-800 hover:underline" x-text="'#' + stop.run_number"></a>
                                </td>

                                {{-- Recipient --}}
                                <td class="px-4 py-3">
                                    <div class="text-xs font-medium text-slate-900" x-text="stop.recipient_name"></div>
                                    <a :href="'tel:' + stop.recipient_phone" class="text-xs text-slate-500 hover:text-orange-700 flex items-center gap-1 mt-0.5">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span x-text="stop.recipient_phone"></span>
                                    </a>
                                </td>

                                {{-- Destination --}}
                                <td class="px-4 py-3">
                                    <div class="text-xs font-medium text-slate-900" x-text="stop.destination_town"></div>
                                    <div class="text-xs text-slate-500" x-text="stop.destination_district"></div>
                                </td>

                                {{-- Courier Details --}}
                                <td class="px-4 py-3">
                                    <div class="text-xs font-medium text-slate-900" x-text="stop.courier_name"></div>
                                    <a :href="'tel:' + stop.courier_phone" class="text-xs text-slate-500 hover:text-orange-700" x-text="stop.courier_phone"></a>
                                    <div class="text-xs text-slate-400 mt-0.5" x-text="stop.vehicle_number"></div>
                                </td>

                                {{-- Handed Off --}}
                                <td class="px-4 py-3">
                                    <div class="text-xs text-slate-900" x-text="stop.handed_off_at"></div>
                                    <span
                                        class="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium"
                                        :class="stop.hours_ago > 24 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600'"
                                        x-text="stop.hours_ago + 'h ago'"
                                    ></span>
                                </td>

                                {{-- Packages --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center h-6 min-w-[24px] px-1.5 rounded-full bg-slate-100 text-xs font-semibold text-slate-700" x-text="stop.packages_count"></span>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                        :class="stop.hours_ago > 24 ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/10' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/10'"
                                        x-text="stop.hours_ago > 24 ? 'Needs Follow-up' : 'Pending'"
                                    ></span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 text-right">
                                    <div x-show="activeAction !== stop.id + '-delivered' && activeAction !== stop.id + '-failed'" class="flex items-center justify-end gap-2">
                                        <button
                                            @@click="activeAction = stop.id + '-delivered'"
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors"
                                        >
                                            Confirm Delivered
                                        </button>
                                        <button
                                            @@click="activeAction = stop.id + '-failed'"
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition-colors"
                                        >
                                            Mark Failed
                                        </button>
                                    </div>

                                    {{-- Inline Confirm Delivered Form --}}
                                    <div x-show="activeAction === stop.id + '-delivered'" x-transition class="text-left">
                                        <div class="border border-emerald-200 rounded-lg p-3 bg-emerald-50/50 space-y-2">
                                            <div class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider">Confirm Delivery</div>
                                            <textarea
                                                x-model="actionNotes"
                                                rows="2"
                                                placeholder="Optional notes..."
                                                class="w-full text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-slate-500/20 focus:border-slate-400 resize-none"
                                            ></textarea>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @@click="submitAction(stop, 'delivered')"
                                                    :disabled="submitting"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors disabled:opacity-50"
                                                >
                                                    <span x-show="!submitting">Submit</span>
                                                    <span x-show="submitting">Saving...</span>
                                                </button>
                                                <button
                                                    @@click="activeAction = null; actionNotes = ''"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Inline Mark Failed Form --}}
                                    <div x-show="activeAction === stop.id + '-failed'" x-transition class="text-left">
                                        <div class="border border-rose-200 rounded-lg p-3 bg-rose-50/50 space-y-2">
                                            <div class="text-[10px] font-semibold text-rose-800 uppercase tracking-wider">Mark as Failed</div>
                                            <textarea
                                                x-model="actionNotes"
                                                rows="2"
                                                placeholder="Optional notes..."
                                                class="w-full text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-slate-500/20 focus:border-slate-400 resize-none"
                                            ></textarea>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @@click="submitAction(stop, 'failed')"
                                                    :disabled="submitting"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition-colors disabled:opacity-50"
                                                >
                                                    <span x-show="!submitting">Submit</span>
                                                    <span x-show="submitting">Saving...</span>
                                                </button>
                                                <button
                                                    @@click="activeAction = null; actionNotes = ''"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div x-show="!loading && meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> results
                </div>
                <div class="flex items-center gap-1">
                    {{-- Previous --}}
                    <button
                        @@click="goToPage(currentPage - 1)"
                        :disabled="currentPage <= 1"
                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Prev
                    </button>

                    {{-- Page Numbers --}}
                    <template x-for="page in pageNumbers" :key="page">
                        <button
                            @@click="page !== '...' && goToPage(page)"
                            :class="page === currentPage ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                            class="inline-flex items-center justify-center min-w-[32px] px-2.5 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                            :disabled="page === '...'"
                            x-text="page"
                        ></button>
                    </template>

                    {{-- Next --}}
                    <button
                        @@click="goToPage(currentPage + 1)"
                        :disabled="currentPage >= meta.last_page"
                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function pendingConfirmations() {
    const config = JSON.parse(document.querySelector('[data-config]').getAttribute('data-config'));

    return {
        stops: [],
        loading: true,
        submitting: false,
        search: '',
        currentPage: 1,
        meta: { total: 0, per_page: 15, current_page: 1, last_page: 1, from: 0, to: 0 },
        activeAction: null,
        actionNotes: '',

        init() {
            this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    search: this.search,
                });
                const response = await fetch(config.dataUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const json = await response.json();
                this.stops = json.data || [];
                this.meta = json.meta || this.meta;
            } catch (e) {
                console.error('Failed to fetch pending confirmations', e);
            } finally {
                this.loading = false;
            }
        },

        async submitAction(stop, action) {
            this.submitting = true;
            try {
                const url = config.confirmUrlTemplate
                    .replace('__RUN__', stop.run_id)
                    .replace('__STOP__', stop.id);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        action: action,
                        notes: this.actionNotes,
                    }),
                });

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.message || 'Request failed');
                }

                // Remove row from table
                this.stops = this.stops.filter(s => s.id !== stop.id);
                this.meta.total = Math.max(0, this.meta.total - 1);

                this.activeAction = null;
                this.actionNotes = '';

                const label = action === 'delivered' ? 'confirmed as delivered' : 'marked as failed';
                window.showToast?.('Delivery ' + label + ' successfully', 'success');

                // Reload if page is now empty and not on first page
                if (this.stops.length === 0 && this.currentPage > 1) {
                    this.currentPage--;
                    this.fetchData();
                }
            } catch (e) {
                console.error('Action failed', e);
                window.showToast?.(e.message || 'Something went wrong', 'error');
            } finally {
                this.submitting = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.currentPage = page;
            this.activeAction = null;
            this.actionNotes = '';
            this.fetchData();
        },

        get pageNumbers() {
            const total = this.meta.last_page;
            const current = this.currentPage;
            const pages = [];

            if (total <= 7) {
                for (let i = 1; i <= total; i++) pages.push(i);
                return pages;
            }

            pages.push(1);
            if (current > 3) pages.push('...');

            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);

            if (current < total - 2) pages.push('...');
            pages.push(total);

            return pages;
        },
    };
}
</script>
@endsection
