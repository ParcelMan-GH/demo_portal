<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ $setting['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $setting['label'] }}</label>

            @if($setting['type'] === 'text' || $setting['type'] === 'number')
                <input type="{{ $setting['type'] }}"
                       x-model="settings.{{ $key }}"
                       class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
            @elseif($setting['type'] === 'textarea')
                <textarea x-model="settings.{{ $key }}"
                          rows="3"
                          class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors resize-none"
                          placeholder="Enter {{ strtolower($setting['label']) }}"></textarea>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Preview -->
    <div class="mt-8 p-6 bg-slate-50/70 rounded-2xl border border-slate-200/50">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Invoice Number Preview</h3>
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-xl border border-slate-200/70 text-lg font-mono text-slate-700">
            <span x-text="settings.invoice_prefix || 'INV-'"></span><span x-text="settings.invoice_start_number || '1000'"></span>
        </div>
    </div>
</div>
