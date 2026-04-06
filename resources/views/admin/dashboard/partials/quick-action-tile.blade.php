<a href="{{ $route }}"
   class="group relative bg-white rounded-2xl p-4 border border-slate-200 hover:border-orange-400 hover:shadow-lg hover:shadow-orange-500/10 hover:-translate-y-0.5 transition-all overflow-hidden">
    <div class="flex items-center justify-center h-20 mb-3">
        <img src="{{ asset('images/undraw/' . $img) }}"
             alt="{{ $title }}"
             class="h-full w-auto object-contain group-hover:scale-110 transition-transform duration-300"
             onerror="this.outerHTML='<div class=\'w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center\'><svg class=\'w-8 h-8 text-orange-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 10V3L4 14h7v7l9-11h-7z\'/></svg></div>'">
    </div>
    <div class="text-center">
        <p class="text-sm font-bold text-slate-900 group-hover:text-orange-600 transition-colors">{{ $title }}</p>
        <p class="text-xs text-slate-500 mt-0.5">{{ $desc }}</p>
    </div>
</a>
