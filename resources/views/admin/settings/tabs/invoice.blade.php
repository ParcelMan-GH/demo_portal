<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($settings as $key => $setting)
        <div class="{{ $setting['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">{{ $setting['label'] }}</label>

            @if($setting['type'] === 'text' || $setting['type'] === 'number')
                <input type="{{ $setting['type'] }}"
                       x-model="settings.{{ $key }}.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="Enter {{ strtolower($setting['label']) }}">
            @elseif($setting['type'] === 'textarea')
                <textarea x-model="settings.{{ $key }}.value"
                          rows="3"
                          class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                          placeholder="Enter {{ strtolower($setting['label']) }}"></textarea>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Preview -->
    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
        <h3 class="mb-4 text-sm font-extrabold text-slate-800">Invoice Number Preview</h3>
        <div class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 font-mono text-lg font-extrabold text-slate-900">
            <span x-text="settings.invoice_prefix?.value || 'INV-'"></span><span x-text="settings.invoice_start_number?.value || '1000'"></span>
        </div>
    </div>
</div>
