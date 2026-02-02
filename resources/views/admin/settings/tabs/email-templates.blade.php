<div class="space-y-6">
    @if(empty($tabData['templates']))
    <div class="text-center py-12">
        <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-slate-900">No email templates found</h3>
        <p class="mt-1 text-sm text-slate-500">Email templates should be placed in resources/views/emails/</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($tabData['templates'] as $template)
        <div class="p-4 bg-white/70 backdrop-blur-sm rounded-xl border border-slate-200/60 hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">{{ $template['name'] }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $template['path'] }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">Modified: {{ $template['modified'] }}</span>
                <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-700">
                    Edit
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
