{{-- Shared pagination footer for inventory tabs. Pass $noun (e.g. 'items', 'pickups') --}}
<div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/60">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-xs text-slate-600">
            Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span>
            of <span x-text="meta.total"></span> {{ $noun ?? 'entries' }}
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-slate-600">Rows</span>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @@click="open = !open"
                            class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 text-xs font-medium text-slate-700 hover:bg-white/90">
                        <span x-text="perPage"></span>
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @@click.away="open = false" x-transition
                         class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                         style="display:none;">
                        <button type="button" @@click="setPerPage(10); open = false"  class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10  ? 'bg-slate-100/70' : ''">10</button>
                        <button type="button" @@click="setPerPage(25); open = false"  class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25  ? 'bg-slate-100/70' : ''">25</button>
                        <button type="button" @@click="setPerPage(50); open = false"  class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50  ? 'bg-slate-100/70' : ''">50</button>
                        <button type="button" @@click="setPerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                    </div>
                </div>
            </div>

            <div class="text-xs font-medium text-slate-600">
                Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span>
            </div>

            <div class="flex space-x-1">
                <button @@click="firstPage()" :disabled="meta.current_page === 1"
                        :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                </button>
                <button @@click="prevPage()" :disabled="meta.current_page === 1"
                        :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @@click="nextPage()" :disabled="meta.current_page === meta.last_page"
                        :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @@click="lastPage()" :disabled="meta.current_page === meta.last_page"
                        :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
