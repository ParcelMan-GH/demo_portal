<div x-data="notificationLogsTab" x-init="init()">

    {{-- Header row with count + controls --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

            {{-- Search --}}
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

            {{-- Type Filter --}}
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
                    @foreach($tabData['types'] ?? [] as $type)
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

            {{-- Channel Filter --}}
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
                    @foreach($tabData['channels'] ?? [] as $channel)
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

            {{-- Status Filter --}}
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
                    @foreach($tabData['statuses'] ?? [] as $status)
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

            {{-- Date Range --}}
            <div class="relative w-full sm:w-56">
                <input
                    type="text"
                    x-ref="dateRange"
                    placeholder="Date range"
                    class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 cursor-pointer"
                    readonly
                >
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-slate-200/50 relative">
        <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                CREATED AT
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.type" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">TYPE</th>
                        <th x-show="visibleColumns.channel" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">CHANNEL</th>
                        <th x-show="visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                        <th x-show="visibleColumns.recipient" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">RECIPIENT</th>
                        <th x-show="visibleColumns.title" @@click="sort('title')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                TITLE
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'title' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.body" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">BODY</th>
                        <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="bg-transparent divide-y divide-slate-100/50">
                    <template x-if="logs.length === 0 && !loading">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">
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
                            <td x-show="visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                            <td x-show="visibleColumns.type" class="px-4 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700" x-text="log.type ? log.type.replace(/_/g, ' ') : '—'"></span>
                            </td>
                            <td x-show="visibleColumns.channel" class="px-4 py-2.5 whitespace-nowrap">
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
                            <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
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
                            <td x-show="visibleColumns.recipient" class="px-4 py-2.5 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-900" x-text="log.notifiable_type || '—'"></div>
                                <div class="text-[10px] text-slate-500" x-text="log.notifiable_id ? '#' + log.notifiable_id : ''"></div>
                            </td>
                            <td x-show="visibleColumns.title" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700 font-medium" x-text="log.title || '—'"></td>
                            <td x-show="visibleColumns.body" class="px-4 py-2.5 text-xs text-slate-500 max-w-[350px]">
                                <span class="block truncate" x-text="log.body || '—'"></span>
                            </td>
                            <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center">
                                <button
                                    type="button"
                                    @@click="openDetail(log)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="View details"
                                >
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
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-xs text-slate-600">
                    Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> results
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-600">Rows per page</span>
                        <div x-data="{ open: false }" class="relative">
                            <button
                                type="button"
                                @@click="open = !open"
                                class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"
                            >
                                <span x-text="perPage"></span>
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                @@click.away="open = false"
                                x-transition
                                class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                style="display: none;"
                            >
                                <button type="button" @@click="setPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                <button type="button" @@click="setPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                <button type="button" @@click="setPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                <button type="button" @@click="setPerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs font-medium text-slate-600">
                        Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span>
                    </div>

                    <div class="flex space-x-1">
                        <button
                            @@click="firstPage()"
                            :disabled="meta.current_page === 1"
                            :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button
                            @@click="previousPage()"
                            :disabled="meta.current_page === 1"
                            :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button
                            @@click="nextPage()"
                            :disabled="meta.current_page >= meta.last_page"
                            :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button
                            @@click="lastPage()"
                            :disabled="meta.current_page >= meta.last_page"
                            :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
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

    {{-- Detail Modal (teleported to body to escape overflow-hidden) --}}
    <template x-teleport="body">
        <div
            x-show="detailOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="detailOpen = false"></div>

            {{-- Modal Content --}}
            <div
                x-show="detailOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden"
                @@click.away="detailOpen = false"
            >
                <template x-if="detailLog">
                <div>
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">Notification Details</h3>
                        </div>
                        <button @@click="detailOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Created At</div>
                            <div class="text-sm text-slate-800" x-text="formatDateTime(detailLog.created_at)"></div>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</div>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="{
                                    'bg-emerald-100 text-emerald-700': detailLog.status === 'sent',
                                    'bg-rose-100 text-rose-700':      detailLog.status === 'failed',
                                    'bg-amber-100 text-amber-700':    detailLog.status === 'pending'
                                }"
                                x-text="detailLog.status || '—'"
                            ></span>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Type</div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-slate-100 text-slate-700" x-text="detailLog.type ? detailLog.type.replace(/_/g, ' ') : '—'"></span>
                        </div>
                        <div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Channel</div>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="{
                                    'bg-blue-100 text-blue-700':   detailLog.channel === 'push',
                                    'bg-violet-100 text-violet-700': detailLog.channel === 'email',
                                    'bg-orange-100 text-orange-700': detailLog.channel === 'sms'
                                }"
                                x-text="detailLog.channel || '—'"
                            ></span>
                        </div>
                        <div class="col-span-2">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Recipient</div>
                            <div class="text-sm text-slate-800">
                                <span x-text="detailLog.notifiable_type || '—'"></span>
                                <span class="text-slate-400" x-show="detailLog.notifiable_id" x-text="' #' + detailLog.notifiable_id"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Title</div>
                        <div class="text-sm font-medium text-slate-800" x-text="detailLog.title || '—'"></div>
                    </div>

                    <div>
                        <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Body</div>
                        <div class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3 whitespace-pre-wrap break-words" x-text="detailLog.body || '—'"></div>
                    </div>

                    <template x-if="detailLog.status === 'failed' && detailLog.error">
                        <div>
                            <div class="text-[10px] font-semibold text-rose-400 uppercase tracking-wider mb-1">Error</div>
                            <div class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-3 whitespace-pre-wrap break-words" x-text="detailLog.error"></div>
                        </div>
                    </template>

                    <template x-if="detailLog.read_at">
                        <div>
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Read At</div>
                            <div class="text-sm text-slate-800" x-text="formatDateTime(detailLog.read_at)"></div>
                        </div>
                    </template>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                    <button @@click="detailOpen = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                        Close
                    </button>
                </div>
                </div>
            </template>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationLogsTab', () => ({
        logs: [],
        loading: false,
        search: '',
        typeFilter: '',
        typeFilterName: '',
        channelFilter: '',
        channelFilterName: '',
        statusFilter: '',
        statusFilterName: '',
        perPage: 20,
        sortBy: 'created_at',
        sortDirection: 'desc',
        createdFrom: '',
        createdTo: '',
        dateRangePicker: null,
        detailOpen: false,
        detailLog: null,
        meta: {
            total: 0,
            from: 0,
            to: 0,
            current_page: 1,
            last_page: 1,
        },
        columns: [
            { key: 'created_at', label: 'Created At' },
            { key: 'type', label: 'Type' },
            { key: 'channel', label: 'Channel' },
            { key: 'status', label: 'Status' },
            { key: 'recipient', label: 'Recipient' },
            { key: 'title', label: 'Title' },
            { key: 'body', label: 'Body' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            created_at: true,
            type: true,
            channel: true,
            status: true,
            recipient: true,
            title: true,
            body: true,
            actions: true,
        },

        init() {
            // Expose columns/visibleColumns/total to Alpine store for header buttons
            Alpine.store('notifLogs', {
                columns: this.columns,
                visibleColumns: this.visibleColumns,
                total: 0,
            });

            // Listen for header button events
            this.$el.addEventListener('notif-toggle-column', (e) => {
                this.toggleColumn(e.detail);
                Alpine.store('notifLogs').visibleColumns = { ...this.visibleColumns };
            });
            window.addEventListener('notif-toggle-column', (e) => {
                this.toggleColumn(e.detail);
                Alpine.store('notifLogs').visibleColumns = { ...this.visibleColumns };
            });
            window.addEventListener('notif-export-csv', () => this.exportCSV());
            window.addEventListener('notif-print', () => this.printData());

            this.initDateRange();
            this.loadData();
        },

        async loadData() {
            this.loading = true;

            const params = new URLSearchParams({
                page: this.meta.current_page,
                per_page: this.perPage,
                sort: this.sortBy,
                direction: this.sortDirection,
            });

            if (this.search)        params.set('search',  this.search);
            if (this.typeFilter)    params.set('type',    this.typeFilter);
            if (this.channelFilter) params.set('channel', this.channelFilter);
            if (this.statusFilter)  params.set('status',  this.statusFilter);
            if (this.createdFrom)   params.set('date_from', this.createdFrom);
            if (this.createdTo)     params.set('date_to', this.createdTo);

            try {
                const response = await fetch(window.settingsConfig.notificationLogsDataEndpoint + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const json = await response.json();
                this.logs = json.data;
                this.meta = json.meta;
                if (Alpine.store('notifLogs')) {
                    Alpine.store('notifLogs').total = json.meta.total || 0;
                }
            } catch (error) {
                console.error('Failed to load notification logs:', error);
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.meta.current_page = 1;
            this.loadData();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        applyFilters() {
            this.meta.current_page = 1;
            this.loadData();
        },

        setPerPage(value) {
            this.perPage = value;
            this.meta.current_page = 1;
            this.loadData();
        },

        firstPage() {
            if (this.meta.current_page !== 1) {
                this.meta.current_page = 1;
                this.loadData();
            }
        },
        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page--;
                this.loadData();
            }
        },
        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page++;
                this.loadData();
            }
        },
        lastPage() {
            if (this.meta.current_page !== this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
                this.loadData();
            }
        },

        openDetail(log) {
            this.detailLog = log;
            this.detailOpen = true;
        },

        initDateRange() {
            if (!this.$refs.dateRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.dateRange);

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'right',
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    ranges: {
                        'Today': [window.moment(), window.moment()],
                        'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });

                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.createdFrom = picker.startDate.format('YYYY-MM-DD');
                    this.createdTo = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.createdFrom} - ${this.createdTo}`);
                    this.meta.current_page = 1;
                    this.loadData();
                });

                $input.on('cancel.daterangepicker', () => {
                    this.createdFrom = '';
                    this.createdTo = '';
                    $input.val('');
                    this.meta.current_page = 1;
                    this.loadData();
                });

                this.dateRangePicker = $input.data('daterangepicker');
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            const cssId = 'daterangepicker-css';
            if (!document.getElementById(cssId)) {
                const link = document.createElement('link');
                link.id = cssId;
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                document.head.appendChild(link);
            }

            const loadScript = (id, src) => new Promise((resolve) => {
                if (document.getElementById(id)) return resolve();
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = () => resolve();
                document.body.appendChild(script);
            });

            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    window.moment = window.moment || moment;
                    return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
                })
                .then(setupPicker);
        },

        exportCSV() {
            if (!this.logs.length) {
                alert('No data to export.');
                return;
            }

            const exportCols = this.columns.filter(c => c.key !== 'actions');
            const keyMap = {
                created_at: 'created_at',
                type: 'type',
                channel: 'channel',
                status: 'status',
                recipient: 'notifiable_type',
                title: 'title',
                body: 'body',
            };

            const headers = exportCols.map(c => c.label);
            const csvLines = [headers.map(h => `"${h}"`).join(',')];

            this.logs.forEach(log => {
                const line = exportCols.map(c => {
                    let val = log[keyMap[c.key] || c.key] ?? '';
                    val = String(val).replace(/"/g, '""');
                    return `"${val}"`;
                });
                csvLines.push(line.join(','));
            });

            const blob = new Blob([csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'notification-logs.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        printData() {
            if (!this.logs.length) {
                alert('No data to print.');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Pop-up blocked. Please allow pop-ups.');
                return;
            }

            const exportCols = this.columns.filter(c => c.key !== 'actions');
            const keyMap = {
                created_at: 'created_at',
                type: 'type',
                channel: 'channel',
                status: 'status',
                recipient: 'notifiable_type',
                title: 'title',
                body: 'body',
            };

            const headersHtml = exportCols.map(c => `<th>${c.label}</th>`).join('');
            const rowsHtml = this.logs.map(log => {
                const cells = exportCols.map(c => `<td>${log[keyMap[c.key] || c.key] ?? '—'}</td>`).join('');
                return `<tr>${cells}</tr>`;
            }).join('');

            printWindow.document.write(`<html><head><title>Notification Logs</title><style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #0f172a; }
                h1 { margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 12px; text-align: left; }
                th { background: #f8fafc; font-weight: 700; }
            </style></head><body>
                <h1>Notification Logs</h1>
                <table><thead><tr>${headersHtml}</tr></thead><tbody>${rowsHtml}</tbody></table>
            </body></html>`);

            printWindow.document.close();
            setTimeout(() => { printWindow.focus(); printWindow.print(); }, 250);
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    }));
});
</script>
@endpush
