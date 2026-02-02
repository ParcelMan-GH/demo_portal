<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ $setting['type'] === 'tags' ? 'md:col-span-2' : '' }}">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $setting['label'] }}</label>

            @if($setting['type'] === 'text')
                <input type="text"
                       x-model="settings.{{ $key }}"
                       class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
            @elseif($setting['type'] === 'tags')
                <div x-data="{ newRegion: '', regions: (settings.{{ $key }} || '').split(',').filter(r => r.trim()) }">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <template x-for="(region, index) in regions" :key="index">
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 text-slate-700 text-sm font-medium rounded-full">
                                <span x-text="region.trim()"></span>
                                <button type="button" @@click="regions.splice(index, 1); settings.{{ $key }} = regions.join(',')" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="newRegion"
                               @@keydown.enter.prevent="if(newRegion.trim()) { regions.push(newRegion.trim()); settings.{{ $key }} = regions.join(','); newRegion = ''; }"
                               class="flex-1 px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                               placeholder="Add a region and press Enter">
                        <button type="button"
                                @@click="if(newRegion.trim()) { regions.push(newRegion.trim()); settings.{{ $key }} = regions.join(','); newRegion = ''; }"
                                class="px-4 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                            Add
                        </button>
                    </div>
                </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
