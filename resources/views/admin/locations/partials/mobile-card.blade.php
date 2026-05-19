<div class="p-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold text-slate-900" x-text="item.name"></p>
            <p class="mt-1 text-xs font-semibold text-slate-500" x-text="'{{ $tab === 'towns' ? 'Town / City' : ($tab === 'districts' ? 'District' : 'Region') }}'"></p>
        </div>
        @include('admin.locations.partials.status')
    </div>
    <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
        @if($tab !== 'towns')
            <div><p class="font-black uppercase tracking-wide text-slate-400">Code</p><p class="font-bold text-slate-800" x-text="item.code"></p></div>
        @endif
        @if($tab === 'regions')
            <div><p class="font-black uppercase tracking-wide text-slate-400">Districts</p><p class="font-bold text-slate-800" x-text="item.districts_count || 0"></p></div>
            <div><p class="font-black uppercase tracking-wide text-slate-400">Towns</p><p class="font-bold text-slate-800" x-text="item.locations_count || 0"></p></div>
        @elseif($tab === 'districts')
            <div><p class="font-black uppercase tracking-wide text-slate-400">Region</p><p class="font-bold text-slate-800" x-text="item.region_name"></p></div>
            <div><p class="font-black uppercase tracking-wide text-slate-400">Towns</p><p class="font-bold text-slate-800" x-text="item.locations_count || 0"></p></div>
        @else
            <div><p class="font-black uppercase tracking-wide text-slate-400">Type</p><p class="font-bold capitalize text-slate-800" x-text="item.type"></p></div>
            <div><p class="font-black uppercase tracking-wide text-slate-400">District</p><p class="font-bold text-slate-800" x-text="item.district_name"></p></div>
            <div><p class="font-black uppercase tracking-wide text-slate-400">Region</p><p class="font-bold text-slate-800" x-text="item.region_name"></p></div>
        @endif
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        @include('admin.locations.partials.actions', ['type' => $tab])
    </div>
</div>
