@extends('admin.layouts.app')

@section('title', 'Notification Logs')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Notification Logs')

@section('content')

<div class="space-y-6" x-data="notificationsTable">
    <!-- Notification Logs Datatable -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Notification Logs</h2>
                        <p class="mt-0.5 text-sm text-slate-500">View all outbound notification records</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Total Logs'">
                </span>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="applyFilters()"
                            placeholder="Search notifications..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Type Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-52">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="typeFilterName || 'All types'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-60 overflow-y-auto"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="typeFilter = ''; typeFilterName = ''; applyFilters(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="typeFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="typeFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All types</span>
                            </button>
                            @foreach($types as $type)
                            <button
                                type="button"
                                @@click="typeFilter = '{{ $type }}'; typeFilterName = '{{ ucfirst(str_replace('_', ' ', $type)) }}'; applyFilters(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="typeFilter === '{{ $type }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="typeFilter === '{{ $type }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Channel Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="channelFilterName || 'All channels'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="channelFilter = ''; channelFilterName = ''; applyFilters(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="channelFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="channelFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All channels</span>
                            </button>
                            @foreach($channels as $channel)
                            <button
                                type="button"
                                @@click="channelFilter = '{{ $channel }}'; channelFilterName = '{{ ucfirst($channel) }}'; applyFilters(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="channelFilter === '{{ $channel }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="channelFilter === '{{ $channel }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ ucfirst($channel) }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="statusFilterName || 'All statuses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="statusFilter = ''; statusFilterName = ''; applyFilters(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="statusFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All statuses</span>
                            </button>
                            @foreach($statuses as $status)
                            <button
                                type="button"
                                @@click="statusFilter = '{{ $status }}'; statusFilterName = '{{ ucfirst($status) }}'; applyFilters(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '{{ $status }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="statusFilter === '{{ $status }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ ucfirst($status) }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="px-6 py-4">
            <div class="rounded-xl border border-slate-200/50 relative">
                <!-- Loading overlay -->
                <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    CREATED AT
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    TYPE
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    CHANNEL
                                </th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    STATUS
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    RECIPIENT
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    TITLE
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    BODY
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    ERROR
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-if="logs.length === 0 && !loading">
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 text-xs">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                            </svg>
                                            <span>No notification logs found</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="log in logs" :key="log.id">
                                <tr class="hover:bg-slate-50/70">
                                    <!-- Created At -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.created_at)"></td>

                                    <!-- Type Badge -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700" x-text="log.type ? log.type.replace(/_/g, ' ') : '—'"></span>
                                    </td>

                                    <!-- Channel Badge -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-blue-100 text-blue-700':   log.channel === 'push',
                                                'bg-violet-100 text-violet-700': log.channel === 'email',
                                                'bg-orange-100 text-orange-700': log.channel === 'sms'
                                            }"
                                            x-text="log.channel || '—'"
                                        ></span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': log.status === 'sent',
                                                'bg-rose-100 text-rose-700':      log.status === 'failed',
                                                'bg-amber-100 text-amber-700':    log.status === 'pending'
                                            }"
                                            x-text="log.status || '—'"
                                        ></span>
                                    </td>

                                    <!-- Recipient -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-900" x-text="log.notifiable_type || '—'"></div>
                                        <div class="text-[10px] text-slate-500" x-text="log.notifiable_id ? '#' + log.notifiable_id : ''"></div>
                                    </td>

                                    <!-- Title -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700 font-medium" x-text="log.title || '—'"></td>

                                    <!-- Body (truncated to 80 chars) -->
                                    <td class="px-4 py-2.5 text-xs text-slate-500 max-w-[220px]">
                                        <span x-text="log.body ? (log.body.length > 80 ? log.body.substring(0, 80) + '…' : log.body) : '—'"></span>
                                    </td>

                                    <!-- Error (shown only for failed) -->
                                    <td class="px-4 py-2.5 text-xs text-rose-500 max-w-[180px]">
                                        <template x-if="log.status === 'failed' && log.error">
                                            <span x-text="log.error.length > 60 ? log.error.substring(0, 60) + '…' : log.error"></span>
                                        </template>
                                        <template x-if="!(log.status === 'failed' && log.error)">
                                            <span class="text-slate-300">—</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing
                            <span x-text="meta.from"></span>
                            to
                            <span x-text="meta.to"></span>
                            of
                            <span x-text="meta.total"></span>
                            results
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="text-xs font-medium text-slate-600">
                                Page
                                <span x-text="meta.current_page"></span>
                                of
                                <span x-text="meta.last_page"></span>
                            </div>

                            <div class="flex space-x-1">
                                <button
                                    @@click="changePage(1)"
                                    :disabled="meta.current_page === 1"
                                    :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <button
                                    @@click="changePage(meta.current_page - 1)"
                                    :disabled="meta.current_page === 1"
                                    :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <button
                                    @@click="changePage(meta.current_page + 1)"
                                    :disabled="meta.current_page === meta.last_page"
                                    :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>

                                <button
                                    @@click="changePage(meta.last_page)"
                                    :disabled="meta.current_page === meta.last_page"
                                    :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationsTable', () => ({
        logs: [],
        loading: false,
        search: '',
        typeFilter: '',
        typeFilterName: '',
        channelFilter: '',
        channelFilterName: '',
        statusFilter: '',
        statusFilterName: '',
        page: 1,
        meta: {
            total: 0,
            from: 0,
            to: 0,
            current_page: 1,
            last_page: 1,
        },

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;

            const params = new URLSearchParams({
                page: this.page,
            });

            if (this.search)        params.set('search',  this.search);
            if (this.typeFilter)    params.set('type',    this.typeFilter);
            if (this.channelFilter) params.set('channel', this.channelFilter);
            if (this.statusFilter)  params.set('status',  this.statusFilter);

            try {
                const response = await fetch('{{ route('admin.notifications.data') }}?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const json = await response.json();
                this.logs = json.data;
                this.meta = json.meta;
            } catch (error) {
                console.error('Failed to load notification logs:', error);
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.page = 1;
            this.loadData();
        },

        changePage(newPage) {
            if (newPage < 1 || newPage > this.meta.last_page) return;
            if (newPage === this.meta.current_page) return;
            this.page = newPage;
            this.loadData();
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('en-GB', {
                day:    '2-digit',
                month:  '2-digit',
                year:   'numeric',
                hour:   '2-digit',
                minute: '2-digit',
            });
        },
    }));
});
</script>
@endpush
