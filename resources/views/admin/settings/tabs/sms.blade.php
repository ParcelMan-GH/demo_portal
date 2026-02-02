<div class="space-y-6">
    <!-- SMS Toggle -->
    <div class="flex items-center justify-between p-4 bg-slate-50/70 rounded-2xl border border-slate-200/50">
        <div>
            <h3 class="text-sm font-semibold text-slate-800">SMS Notifications</h3>
            <p class="text-sm text-slate-500 mt-0.5">Enable or disable SMS notifications</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" x-model="settings.sms_enabled.value" :value="settings.sms_enabled.value == '1' || settings.sms_enabled.value === true" class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
            @if($key !== 'sms_enabled')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $setting['label'] }}</label>

                @if($setting['type'] === 'text')
                    <input type="text"
                           x-model="settings.{{ $key }}.value"
                           class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                           placeholder="Enter {{ strtolower($setting['label']) }}">
                @elseif($setting['type'] === 'password')
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'"
                               x-model="settings.{{ $key }}.value"
                               class="w-full px-3.5 py-2.5 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                               placeholder="Enter {{ strtolower($setting['label']) }}">
                        <button type="button" @@click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                @elseif($setting['type'] === 'select')
                    <select x-model="settings.{{ $key }}.value"
                            class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                        @foreach($setting['options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            @endif
        @endforeach
    </div>

    <!-- Test SMS -->
    <div class="mt-8 p-6 bg-slate-50/70 rounded-2xl border border-slate-200/50" x-data="{ testPhone: '' }">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Test SMS</h3>
        <div class="flex gap-3">
            <input type="text"
                   x-model="testPhone"
                   class="flex-1 px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                   placeholder="Enter phone number (e.g., +233...)" >
            <button type="button"
                    @@click="testSms(testPhone)"
                    class="px-4 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                Send Test
            </button>
        </div>
    </div>
</div>
