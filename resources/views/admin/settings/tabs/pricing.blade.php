<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ in_array($setting['type'], ['textarea', 'toggle']) ? 'md:col-span-2' : '' }}">
            @if($setting['type'] === 'toggle')
                <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                    <div>
                        <span class="text-sm font-extrabold text-slate-800">{{ $setting['label'] }}</span>
                        @if(!empty($setting['help']))
                            <p class="mt-1 text-sm font-medium leading-5 text-slate-500">{{ $setting['help'] }}</p>
                        @endif
                    </div>
                    <input type="checkbox"
                           :checked="settings['{{ $key }}']?.value === '1' || settings['{{ $key }}']?.value === 1"
                           @@change="settings['{{ $key }}'].value = $event.target.checked ? '1' : '0'"
                           class="mt-1 h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                </label>
            @else
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">{{ $setting['label'] }}</label>
                <input type="{{ $setting['type'] }}"
                       x-model="settings['{{ $key }}'].value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
                @if(!empty($setting['help']))
                    <p class="mt-2 text-sm font-medium leading-5 text-slate-500">{{ $setting['help'] }}</p>
                @endif
            @endif
        </div>
        @endforeach
    </div>
</div>
