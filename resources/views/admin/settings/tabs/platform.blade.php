<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ $setting['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $setting['label'] }}</label>

            @if($setting['type'] === 'text' || $setting['type'] === 'email' || $setting['type'] === 'number')
                <input type="{{ $setting['type'] }}"
                       x-model="settings.{{ $key }}.value"
                       class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
            @elseif($setting['type'] === 'textarea')
                <textarea x-model="settings.{{ $key }}.value"
                          rows="3"
                          class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors resize-none"
                          placeholder="Enter {{ strtolower($setting['label']) }}"></textarea>
            @elseif($setting['type'] === 'select')
                <select x-model="settings.{{ $key }}.value"
                        class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                    @foreach($setting['options'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        @endforeach
    </div>
</div>
