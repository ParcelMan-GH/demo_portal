<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ $setting['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">{{ $setting['label'] }}</label>

            @if($setting['type'] === 'text' || $setting['type'] === 'email' || $setting['type'] === 'number')
                <input type="{{ $setting['type'] }}"
                       x-model="settings.{{ $key }}.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
            @elseif($setting['type'] === 'textarea')
                <textarea x-model="settings.{{ $key }}.value"
                          rows="3"
                          class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                          placeholder="Enter {{ strtolower($setting['label']) }}"></textarea>
            @elseif($setting['type'] === 'select')
                <select x-model="settings.{{ $key }}.value"
                        class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    @foreach($setting['options'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        @endforeach
    </div>
</div>
