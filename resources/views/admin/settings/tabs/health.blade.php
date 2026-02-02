<div class="space-y-6">
    <!-- System Health Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($tabData['checks'] as $key => $check)
        <div class="p-4 bg-white/70 backdrop-blur-sm rounded-xl border border-slate-200/60">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                        {{ $check['status'] === 'ok' ? 'bg-emerald-50' : ($check['status'] === 'warning' ? 'bg-amber-50' : 'bg-red-50') }}">
                        @if($check['status'] === 'ok')
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        @elseif($check['status'] === 'warning')
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">{{ $check['label'] }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $check['message'] }}</p>
                    </div>
                </div>
                <span class="text-sm font-mono text-slate-600">{{ $check['value'] }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3 mt-8 p-6 bg-slate-50/70 rounded-2xl border border-slate-200/50">
        <h3 class="w-full text-sm font-semibold text-slate-800 mb-2">Quick Actions</h3>
        <button type="button"
                @@click="clearCache()"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200/70 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Clear Cache
        </button>
        <a href="{{ route('admin.settings.index', ['tab' => 'logs']) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200/70 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            View System Logs
        </a>
    </div>

    <!-- Server Info -->
    <div class="p-6 bg-slate-50/70 rounded-2xl border border-slate-200/50">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Server Information</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <span class="text-xs text-slate-500">Server Time</span>
                <p class="text-sm font-medium text-slate-800 mt-1">{{ now()->format('Y-m-d H:i:s') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-500">Timezone</span>
                <p class="text-sm font-medium text-slate-800 mt-1">{{ config('app.timezone') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-500">Environment</span>
                <p class="text-sm font-medium text-slate-800 mt-1">{{ config('app.env') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-500">Debug Mode</span>
                <p class="text-sm font-medium mt-1 {{ config('app.debug') ? 'text-amber-600' : 'text-emerald-600' }}">
                    {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                </p>
            </div>
        </div>
    </div>
</div>
