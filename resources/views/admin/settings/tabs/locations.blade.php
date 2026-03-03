<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
    </div>
    <h3 class="text-[15px] font-semibold text-slate-800 mb-2">Locations are managed separately</h3>
    <p class="text-[13px] text-slate-500 max-w-sm mb-6">
        Regions, districts, and towns are now managed from the dedicated Location Management page where you can add, edit, activate, or delete them.
    </p>
    <a href="{{ route('admin.locations.index') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
        Go to Location Management
    </a>
</div>
