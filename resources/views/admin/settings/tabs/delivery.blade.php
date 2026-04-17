<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ in_array($setting['type'], ['textarea', 'toggle']) ? 'md:col-span-2' : '' }}">
            @if($setting['type'] === 'toggle')
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" x-model="settings.{{ $key }}.value" :true-value="'1'" :false-value="'0'" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <div>
                        <span class="text-sm font-medium text-slate-700">{{ $setting['label'] }}</span>
                        @if(!empty($setting['help']))
                            <p class="text-xs text-slate-500 mt-0.5">{{ $setting['help'] }}</p>
                        @endif
                    </div>
                </label>
            @else
                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $setting['label'] }}</label>
                @if(!empty($setting['help']))
                    <p class="text-xs text-slate-500 mb-2">{{ $setting['help'] }}</p>
                @endif
                <input type="{{ $setting['type'] }}"
                       x-model="settings.{{ $key }}.value"
                       class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
            @endif
        </div>
        @endforeach
    </div>
</div>
