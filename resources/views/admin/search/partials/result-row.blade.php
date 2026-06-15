{{-- One uniform search result row. Expects $row = ['label','sub','status','url']. --}}
<a href="{{ $row['url'] }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-slate-50">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-extrabold text-slate-800">{{ $row['label'] }}</p>
        <p class="truncate text-xs text-slate-400">{{ $row['sub'] }}</p>
    </div>
    @if(!empty($row['status']))
        <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">{{ $row['status'] }}</span>
    @endif
    <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>
