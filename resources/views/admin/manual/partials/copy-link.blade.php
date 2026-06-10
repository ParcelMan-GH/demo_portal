{{-- Copy-deep-link button for a manual section heading. Expects $anchor. --}}
<button type="button" @click="copyLink(@js($anchor))" title="Copy link to this section"
        class="text-slate-300 transition hover:text-orange-600 print:hidden">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m7.156-2.328a4 4 0 015.656 0 4 4 0 010 5.656l-1.5 1.5m-7.156 2.328a4 4 0 01-5.656 0"/></svg>
</button>
<span x-show="copied === @js($anchor)" x-transition.opacity class="text-[10px] font-black uppercase tracking-wide text-emerald-600 print:hidden" x-cloak>Copied</span>
