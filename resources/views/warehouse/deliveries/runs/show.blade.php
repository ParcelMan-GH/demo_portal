@extends('warehouse.layouts.app')

@section('title', 'Delivery Run Details')
@section('page-title', 'Delivery Run Details')

@php
    $stops = $run->stops;
    $items = $run->items;
    $totalExpected = $items->sum('expected_quantity');
    $totalDelivered = $items->sum('delivered_quantity');

    $statusClass = match ($run->status) {
        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
        'out_for_delivery' => 'bg-violet-50 text-violet-700 border-violet-200',
        'partially_delivered' => 'bg-amber-50 text-amber-700 border-amber-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $statusDotClass = match ($run->status) {
        'draft' => 'bg-slate-400',
        'assigned' => 'bg-blue-500',
        'out_for_delivery' => 'bg-violet-500',
        'partially_delivered' => 'bg-amber-500',
        'completed' => 'bg-emerald-500',
        'cancelled' => 'bg-rose-500',
        default => 'bg-slate-400',
    };

    $timeline = [
        ['label' => 'Created', 'value' => $run->created_at, 'dot' => 'bg-slate-500'],
        ['label' => 'Rider Assigned', 'value' => $run->assigned_at, 'dot' => 'bg-blue-500'],
        ['label' => 'Dispatched', 'value' => $run->dispatched_at, 'dot' => 'bg-violet-500'],
        ['label' => 'Completed', 'value' => $run->completed_at, 'dot' => 'bg-emerald-500'],
    ];
@endphp

@section('content')
<div class="space-y-6"
     x-data="deliveryRunShowPage">

    {{-- ── Hero ────────────────────────────────────────────────────────── --}}
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="runGrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#runGrid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <div class="mb-6">
                    <a href="{{ route('warehouse.deliveries.runs.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Delivery Runs</span>
                    </a>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-emerald-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-teal-500/30 ring-4 ring-white/10">
                                <svg class="w-10 h-10 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0014 6h-1"/>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $run->run_number }}</h1>
                                <p class="text-slate-300 text-sm mt-0.5 truncate">
                                    {{ $run->warehouse?->name ?? '-' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ str($run->status)->replace('_', ' ')->title() }}
                                </span>
                                @if($run->sortBatch)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                        Batch: {{ $run->sortBatch->batch_number }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap lg:ml-auto lg:self-start">
                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500/30 to-orange-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($stops->count()) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Stops</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($items->count()) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Items</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500/30 to-indigo-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($totalExpected) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Expected</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($totalDelivered) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Delivered</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar + Content ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        {{-- Sidebar Nav --}}
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Delivery Run</p>

            {{-- Overview --}}
            <button @@click="activeTab = 'overview'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'overview' ? 'bg-teal-50 ring-1 ring-teal-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'overview' ? 'bg-teal-500 shadow-sm shadow-teal-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'overview' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'overview' ? 'font-bold text-teal-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Overview</span>
            </button>

            {{-- Stops --}}
            <button @@click="activeTab = 'stops'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'stops' ? 'bg-orange-50 ring-1 ring-orange-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'stops' ? 'bg-slate-800 shadow-sm shadow-slate-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'stops' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'stops' ? 'font-bold text-orange-800' : 'font-medium text-slate-500 group-hover:text-slate-700'">Stops</span>
                <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'stops' ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500'">{{ $stops->count() }}</span>
            </button>

            {{-- Items --}}
            <button @@click="activeTab = 'items'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'items' ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'items' ? 'bg-violet-500 shadow-sm shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'items' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'items' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Items</span>
                <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'items' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-500'">{{ $items->count() }}</span>
            </button>

            {{-- Divider: Operations --}}
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Operations</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            {{-- Timeline --}}
            <button @@click="activeTab = 'timeline'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'timeline' ? 'bg-amber-50 ring-1 ring-amber-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'timeline' ? 'bg-amber-500 shadow-sm shadow-amber-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'timeline' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'timeline' ? 'font-bold text-amber-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Timeline</span>
            </button>

        </aside>

        {{-- Tab Content --}}
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">

            {{-- ── Overview Tab ─────────────────────────────────────────── --}}
            <div x-show="activeTab === 'overview'">
                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

                    {{-- Left Column (3/5) --}}
                    <div class="xl:col-span-3 space-y-4">

                        {{-- Card A: Run Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Run Summary</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Delivery run details and scheduling</p>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ str($run->status)->replace('_', ' ')->title() }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Run #</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $run->run_number }}</p>
                                    @if($run->sortBatch)
                                        <p class="text-xs text-slate-500">Batch: {{ $run->sortBatch->batch_number }}</p>
                                    @endif
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Created</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $run->created_at?->format('M d, Y') ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $run->created_at?->format('H:i') ?? '' }}</p>
                                </div>
                                <div class="rounded-xl border px-4 py-3 {{ $run->dispatched_at ? 'bg-violet-50 border-violet-100' : 'bg-slate-50 border-slate-100' }}">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ $run->dispatched_at ? 'text-violet-500' : 'text-slate-400' }} mb-1">Dispatched</p>
                                    <p class="text-sm font-bold {{ $run->dispatched_at ? 'text-slate-900' : 'text-slate-400' }}">{{ $run->dispatched_at ? $run->dispatched_at->format('M d, Y') : '—' }}</p>
                                    <p class="text-xs {{ $run->dispatched_at ? 'text-violet-600/70' : 'text-slate-400' }}">{{ $run->dispatched_at ? $run->dispatched_at->format('H:i') : 'Not yet dispatched' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Card B: Delivery Progress --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h4 class="text-sm font-bold text-slate-800">Delivery Progress</h4>
                            </div>
                            <div class="overflow-x-auto -mx-2 px-2">
                                <div class="relative flex min-w-[380px]">
                                    <div class="absolute top-5 left-[calc(100%/8)] right-[calc(100%/8)] h-px bg-slate-200 -z-0"></div>
                                    @php
                                        $steps = [
                                            ['label' => 'Created', 'at' => $run->created_at, 'color' => 'slate'],
                                            ['label' => 'Assigned', 'at' => $run->assigned_at, 'color' => 'blue'],
                                            ['label' => 'Dispatched', 'at' => $run->dispatched_at, 'color' => 'violet'],
                                            ['label' => 'Completed', 'at' => $run->completed_at, 'color' => 'emerald'],
                                        ];
                                    @endphp
                                    @foreach($steps as $step)
                                        <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                            <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm {{ $step['at'] ? 'border-'.$step['color'].'-500 bg-'.$step['color'].'-500 text-white' : 'border-slate-200 bg-white text-slate-300' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <p class="text-[11px] font-semibold text-center leading-tight {{ $step['at'] ? 'text-'.$step['color'].'-700' : 'text-slate-400' }}">{{ $step['label'] }}</p>
                                            <p class="text-[10px] text-slate-400 text-center leading-tight">{{ $step['at'] ? $step['at']->format('M d, H:i') : '—' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        @if($run->notes)
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Notes</p>
                                </div>
                                <p class="text-xs text-slate-700">{{ $run->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Right Column (2/5) --}}
                    <div class="xl:col-span-2 space-y-4">

                        {{-- Quantity Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-900 mb-3">Quantity Summary</h3>
                            <div class="space-y-2.5">
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-orange-500/20 to-orange-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Total Stops</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($stops->count()) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/20 to-violet-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Total Items</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($items->count()) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wide">Expected Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalExpected) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl border {{ $totalDelivered > 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100' }}">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $totalDelivered > 0 ? 'from-emerald-500/20 to-emerald-600/10' : 'from-slate-400/20 to-slate-500/10' }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 {{ $totalDelivered > 0 ? 'text-emerald-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold {{ $totalDelivered > 0 ? 'text-emerald-600' : 'text-slate-400' }} uppercase tracking-wide">Delivered Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalDelivered) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Widget: Rider Assignment --}}
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            {{-- Gradient Header --}}
                            <div class="bg-gradient-to-br from-teal-500 to-emerald-600 px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-white leading-none">Rider Assignment</h3>
                                            @if($run->assignedDriver)
                                                <p class="text-[11px] text-white/60 mt-0.5">{{ $run->assignedDriver->name }}</p>
                                            @else
                                                <p class="text-[11px] text-white/50 mt-0.5">No rider assigned</p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-white/20 text-white ring-1 ring-white/30 capitalize">
                                        {{ str($run->status)->replace('_', ' ')->title() }}
                                    </span>
                                </div>
                            </div>
                            {{-- Body --}}
                            <div class="p-5">
                                @if(!$run->assignedDriver)
                                    {{-- No rider: show assign button that opens modal --}}
                                    <div class="text-center py-4">
                                        <div class="w-11 h-11 rounded-xl bg-teal-50 border border-teal-100 flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500 mb-3">No rider assigned</p>
                                        @if($run->status === 'draft')
                                            <button @@click="showAssignModal = true"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-teal-500/25 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Assign Rider
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    {{-- Has driver --}}
                                    {{-- Rider info --}}
                                    <div class="flex items-center gap-3 mb-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="w-10 h-10 rounded-full ring-2 ring-teal-200 bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center text-white font-black text-sm shadow-sm flex-shrink-0">
                                            {{ strtoupper(substr($run->assignedDriver->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-900 truncate leading-none">{{ $run->assignedDriver->name }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $run->assignedDriver->phone ?? '—' }}</p>
                                        </div>
                                    </div>
                                    {{-- Progress pill bars --}}
                                    <div class="mb-4">
                                        @php
                                            $progressSteps = [
                                                ['label' => 'Assigned', 'at' => $run->assigned_at, 'color' => 'blue'],
                                                ['label' => 'Dispatched', 'at' => $run->dispatched_at, 'color' => 'violet'],
                                                ['label' => 'Delivering', 'at' => $run->dispatched_at, 'color' => 'indigo'],
                                                ['label' => 'Completed', 'at' => $run->completed_at, 'color' => 'emerald'],
                                            ];
                                        @endphp
                                        <div class="grid grid-cols-4 gap-1">
                                            @foreach($progressSteps as $ps)
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors {{ $ps['at'] ? 'bg-'.$ps['color'].'-500' : 'bg-slate-200' }}"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none {{ $ps['at'] ? 'text-'.$ps['color'].'-600' : 'text-slate-300' }}">{{ $ps['label'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    {{-- Action buttons --}}
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if(in_array($run->status, ['draft', 'assigned']))
                                            <button @@click="showAssignModal = true"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-teal-50 hover:border-teal-200 hover:text-teal-700 text-xs font-bold shadow-sm transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Reassign
                                            </button>
                                        @endif
                                        @if($run->status === 'assigned')
                                            <button @@click="dispatchRun()"
                                                :disabled="actionLoading"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                                <svg x-show="actionLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <svg x-show="!actionLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                <span x-text="actionLoading ? 'Dispatching...' : 'Dispatch'"></span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Stops Tab ────────────────────────────────────────────── --}}
            <div x-show="activeTab === 'stops'" x-cloak>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 shrink-0">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Delivery Stops</h2>
                            <p class="mt-0.5 text-sm text-slate-500">All delivery stops in this run.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="filteredStops().length + ' Stops'"></span>
                </div>

                {{-- Search --}}
                <div class="mb-4">
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" x-model="stopSearch" placeholder="Search recipient, town, region..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Empty state --}}
                <div x-show="filteredStops().length === 0" x-cloak class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-12 h-12 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-slate-400 text-sm font-medium" x-text="stopSearch ? 'No stops match your search.' : 'No stops in this delivery run.'"></p>
                </div>

                {{-- Stop Cards --}}
                <div class="space-y-3">
                    <template x-for="stop in filteredStops()" :key="stop.id">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            {{-- Card Header: Recipient + Status --}}
                            <div class="flex items-center gap-4 px-5 pt-5 pb-4">
                                <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-base shadow-sm"
                                    :class="stop.status === 'delivered' ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : stop.status === 'failed' ? 'bg-gradient-to-br from-rose-500 to-red-600' : stop.status === 'arrived' ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-slate-400 to-slate-500'">
                                    <span x-text="(stop.recipient_name || '?').charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate" x-text="stop.recipient_name"></p>
                                    <p class="text-xs text-slate-400" x-text="stop.recipient_phone || '—'"></p>
                                </div>
                                <template x-if="stop.delivery_method === 'bus_handoff'">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-semibold flex-shrink-0 bg-violet-50 text-violet-700 ring-1 ring-violet-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        Bus Handoff
                                    </span>
                                </template>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold flex-shrink-0 ring-1"
                                    :class="stopStatusClass(stop.status)"
                                    x-text="stop.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                            </div>

                            {{-- Packages --}}
                            <div class="border-t border-slate-100 px-5 py-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span class="text-xs font-semibold text-slate-700"><span x-text="stop.total_packages"></span> package<span x-show="stop.total_packages !== 1">s</span></span>
                                    <span class="text-[10px] text-slate-400">(<span x-text="stop.items_count"></span> item<span x-show="stop.items_count !== 1">s</span>)</span>
                                </div>
                                <template x-if="stop.status !== 'delivered' && stop.status !== 'failed'">
                                    <div x-data="{ editing: false, pkg: stop.total_packages, saving: false }" class="flex items-center gap-1.5">
                                        <template x-if="!editing">
                                            <button @@click="editing = true; pkg = stop.total_packages" class="text-[10px] font-medium text-indigo-600 hover:text-indigo-800 transition-colors">Edit</button>
                                        </template>
                                        <template x-if="editing">
                                            <div class="flex items-center gap-1">
                                                <input type="number" x-model.number="pkg" min="1" max="999" class="w-14 h-6 px-1.5 text-xs border border-slate-200 rounded-md text-center focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400">
                                                <button @@click="
                                                    if (pkg < 1) return;
                                                    saving = true;
                                                    fetch(`{{ route('warehouse.deliveries.runs.stops.update-packages', ['run' => '__RUN__', 'stop' => '__STOP__']) }}`.replace('__RUN__', {{ $run->id }}).replace('__STOP__', stop.id), {
                                                        method: 'PATCH',
                                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                                        body: JSON.stringify({ total_packages: pkg })
                                                    })
                                                    .then(r => r.json())
                                                    .then(data => {
                                                        if (data.success) { stop.total_packages = data.data.total_packages; window.showToast?.('Package count updated.', 'success'); }
                                                        else { window.showToast?.(data.message || 'Failed.', 'error'); }
                                                    })
                                                    .catch(() => window.showToast?.('Failed to update.', 'error'))
                                                    .finally(() => { saving = false; editing = false; });
                                                " :disabled="saving" class="h-6 px-2 text-[10px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md disabled:opacity-50">
                                                    <span x-show="!saving">Save</span>
                                                    <span x-show="saving">...</span>
                                                </button>
                                                <button @@click="editing = false" class="h-6 px-1.5 text-[10px] text-slate-500 hover:text-slate-700">Cancel</button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            {{-- Location row --}}
                            <div class="border-t border-slate-100 px-5 py-3 bg-slate-50/60">
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5">
                                    <span x-show="stop.region_name" class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                        <svg class="w-3.5 h-3.5 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                                        </svg>
                                        <span class="font-semibold text-slate-700" x-text="stop.region_name"></span>
                                    </span>
                                    <span x-show="stop.district_name" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <span x-text="stop.district_name"></span>
                                    </span>
                                    <span x-show="stop.town" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <span x-text="stop.town"></span>
                                    </span>
                                    <span x-show="stop.gh_post_address" class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        <span class="font-mono text-[11px]" x-text="stop.gh_post_address"></span>
                                    </span>
                                    <span x-show="stop.landmark" class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span x-text="stop.landmark"></span>
                                    </span>
                                </div>
                            </div>

                            {{-- Verification + Timestamps --}}
                            <div class="border-t border-slate-100 px-5 py-3">
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5">
                                    <span x-show="stop.code_sent_at" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Code sent: <span class="font-medium text-slate-600" x-text="stop.code_sent_at"></span>
                                    </span>
                                    <template x-if="stop.verification_code && stop.status !== 'delivered'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-xs font-bold text-indigo-700 tracking-widest">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            OTP: <span x-text="stop.verification_code"></span>
                                        </span>
                                    </template>
                                    <span x-show="stop.max_attempts" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                        Attempts: <span class="font-medium text-slate-600" x-text="stop.attempts + '/' + stop.max_attempts"></span>
                                    </span>
                                    <span x-show="stop.arrived_at" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Arrived: <span class="font-medium text-slate-600" x-text="stop.arrived_at"></span>
                                    </span>
                                    <span x-show="stop.delivered_at" class="inline-flex items-center gap-1 text-xs text-emerald-600">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Delivered: <span class="font-medium" x-text="stop.delivered_at"></span>
                                    </span>
                                    <span x-show="stop.proof_photo_path" class="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Proof photo
                                    </span>
                                </div>
                            </div>

                            {{-- Verification skipped warning --}}
                            <template x-if="stop.verification_skipped">
                                <div class="border-t border-amber-200 px-5 py-3 bg-amber-50">
                                    <div class="flex items-start gap-2 text-xs text-amber-700">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                        <div>
                                            <span class="font-semibold">OTP Verification Skipped</span>
                                            <span x-show="stop.verification_skip_reason" class="ml-1">— <span x-text="stop.verification_skip_reason"></span></span>
                                            <span x-show="stop.verification_skipped_at" class="block mt-0.5 text-amber-500" x-text="'Skipped at: ' + stop.verification_skipped_at"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Handoff courier info --}}
                            <template x-if="stop.handoff && stop.handoff.courier_name">
                                <div class="border-t border-violet-100 px-5 py-3 bg-violet-50/50">
                                    <div class="flex items-start gap-2.5 text-xs">
                                        <svg class="w-4 h-4 text-violet-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        <div class="flex-1">
                                            <p class="font-semibold text-violet-800 mb-1">Bus Courier Handoff</p>
                                            <div class="grid grid-cols-3 gap-3">
                                                <div>
                                                    <span class="text-[10px] text-violet-400 uppercase tracking-wider">Courier</span>
                                                    <p class="font-semibold text-slate-800" x-text="stop.handoff.courier_name"></p>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-violet-400 uppercase tracking-wider">Phone</span>
                                                    <p><a :href="'tel:' + stop.handoff.courier_phone" class="font-semibold text-violet-700 hover:underline" x-text="stop.handoff.courier_phone"></a></p>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-violet-400 uppercase tracking-wider">Vehicle</span>
                                                    <p class="font-mono font-semibold text-slate-800" x-text="stop.handoff.vehicle_number"></p>
                                                </div>
                                            </div>
                                            <p x-show="stop.handoff.handed_off_at" class="mt-1.5 text-violet-500">Handed off: <span class="font-medium" x-text="stop.handoff.handed_off_at"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Admin confirmation info (for handed_off stops confirmed by admin) --}}
                            <template x-if="stop.confirmation_notes && (stop.status === 'delivered' || stop.status === 'failed') && stop.confirmed_at">
                                <div class="border-t border-emerald-100 px-5 py-3 bg-emerald-50/50">
                                    <div class="flex items-start gap-2 text-xs text-emerald-700">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <div>
                                            <span class="font-semibold">Admin Confirmed</span> — <span x-text="stop.confirmed_at"></span>
                                            <p x-show="stop.confirmation_notes" class="mt-0.5 text-emerald-600" x-text="stop.confirmation_notes"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Failure info --}}
                            <template x-if="stop.status === 'failed' && (stop.failure_reason || stop.failure_notes)">
                                <div class="border-t border-rose-100 px-5 py-3 bg-rose-50">
                                    <div class="flex items-start gap-2 text-xs text-rose-600">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                        <div>
                                            <p x-show="stop.failure_reason" class="font-semibold" x-text="stop.failure_reason"></p>
                                            <p x-show="stop.failure_notes" class="mt-0.5" x-text="stop.failure_notes"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Delivery Notes --}}
                            <template x-if="stop.delivery_notes">
                                <div class="border-t border-slate-100 px-5 py-3 bg-slate-50/50">
                                    <div class="flex items-start gap-2 text-xs text-slate-600">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                        <div>
                                            <p class="font-semibold text-slate-500">Delivery Notes</p>
                                            <p class="mt-0.5" x-text="stop.delivery_notes"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Resend Code button --}}
                            <template x-if="stop.can_resend_code">
                                <div class="border-t border-slate-100 px-5 py-3 flex justify-end">
                                    <button @@click="resendCode(stop.id)"
                                        :disabled="actionLoading"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg x-show="actionLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <svg x-show="!actionLoading" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Resend Code
                                    </button>
                                </div>
                            </template>

                            {{-- Expandable Items --}}
                            <template x-if="stop.items && stop.items.length > 0">
                                <div x-data="{ expanded: false }">
                                    <button @@click="expanded = !expanded" class="w-full border-t border-slate-100 px-5 py-2.5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                        <span class="text-xs font-semibold text-slate-500">
                                            <span x-text="stop.items.length"></span> item(s)
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="expanded" x-collapse class="border-t border-slate-100">
                                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                                            <thead class="bg-slate-50/50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Expected</th>
                                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Delivered</th>
                                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-50">
                                                <template x-for="si in stop.items" :key="si.id">
                                                    <tr class="hover:bg-slate-50/70">
                                                        <td class="px-4 py-2 text-xs text-slate-700">
                                                            <p class="font-semibold text-slate-900" x-text="si.description"></p>
                                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                                <span class="text-[11px] text-slate-500" x-text="'Shipment: ' + (si.shipment_number || '—')"></span>
                                                                <span x-show="si.fulfillment_type && si.fulfillment_type !== 'warehouse'" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-semibold"
                                                                      :class="si.fulfillment_type === 'direct' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700'"
                                                                      x-text="si.fulfillment_type === 'direct' ? 'Direct' : 'Self Pickup'"></span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2 text-center text-xs font-semibold text-slate-800" x-text="si.expected_quantity"></td>
                                                        <td class="px-4 py-2 text-center text-xs font-semibold text-slate-800" x-text="si.delivered_quantity"></td>
                                                        <td class="px-4 py-2 text-center">
                                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                                :class="itemStatusClass(si.status)"
                                                                x-text="si.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── Items Tab ────────────────────────────────────────────── --}}
            <div x-show="activeTab === 'items'" x-cloak>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 shrink-0">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Delivery Items</h2>
                            <p class="mt-0.5 text-sm text-slate-500">All shipment items included in this delivery run.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="filteredItems().length + ' Items'"></span>
                </div>

                {{-- Search --}}
                <div class="mb-4">
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" x-model="itemSearch" @@input="itemPage = 1"
                            placeholder="Search item, shipment, recipient..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th @@click="sortItems('description')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        ITEM DESCRIPTION
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'description' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('shipment_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        SHIPMENT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('stop_recipient')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        STOP
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'stop_recipient' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('expected_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        EXPECTED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'expected_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('delivered_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        DELIVERED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'delivered_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        STATUS
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            {{-- Empty state --}}
                            <tr x-show="filteredItems().length === 0" x-cloak>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 text-xs">
                                    <span x-text="itemSearch ? 'No items match your search.' : 'No items in this delivery run.'"></span>
                                </td>
                            </tr>
                            {{-- Data rows --}}
                            <template x-for="item in paginatedItems()" :key="item.id">
                                <tr class="hover:bg-slate-50/70 align-top">
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[200px]">
                                        <p class="font-semibold text-slate-900" x-text="item.description"></p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[11px] text-slate-500" x-text="'ID: ' + item.shipment_item_id"></span>
                                            <span x-show="item.fulfillment_type && item.fulfillment_type !== 'warehouse'" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-semibold"
                                                  :class="item.fulfillment_type === 'direct' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700'"
                                                  x-text="item.fulfillment_type === 'direct' ? 'Direct' : 'Self Pickup'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[140px]">
                                        <p class="font-semibold text-slate-900" x-text="item.shipment_number"></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[140px]">
                                        <p class="font-semibold text-slate-900" x-text="item.stop_recipient || '—'"></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800" x-text="item.expected_quantity"></td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800" x-text="item.delivered_quantity"></td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm shadow-sm"
                                            :class="itemStatusClass(item.status)"
                                            x-text="item.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-600 min-w-[160px]" x-text="item.notes || '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    {{-- Pagination --}}
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">
                                Showing
                                <span x-text="Math.min((itemPage - 1) * itemPerPage + 1, filteredItems().length)"></span>
                                to
                                <span x-text="Math.min(itemPage * itemPerPage, filteredItems().length)"></span>
                                of
                                <span x-text="filteredItems().length"></span>
                                results
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open"
                                            class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="itemPerPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition
                                            class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                            style="display: none;">
                                            <button type="button" @@click="itemPerPage = 10; itemPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="itemPerPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="itemPerPage = 25; itemPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="itemPerPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="itemPerPage = 50; itemPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="itemPerPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                            <button type="button" @@click="itemPerPage = 100; itemPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="itemPerPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs font-medium text-slate-600">
                                    Page <span x-text="itemPage"></span> of <span x-text="itemLastPage()"></span>
                                </div>

                                <div class="flex space-x-1">
                                    <button @@click="itemPage = 1" :disabled="itemPage <= 1"
                                        :class="itemPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button @@click="itemPage = Math.max(1, itemPage - 1)" :disabled="itemPage <= 1"
                                        :class="itemPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button @@click="itemPage = Math.min(itemLastPage(), itemPage + 1)" :disabled="itemPage >= itemLastPage()"
                                        :class="itemPage >= itemLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button @@click="itemPage = itemLastPage()" :disabled="itemPage >= itemLastPage()"
                                        :class="itemPage >= itemLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                        class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Timeline Tab ─────────────────────────────────────────── --}}
            <div x-show="activeTab === 'timeline'" x-cloak>
                @php
                    $completedCount = collect($timeline)->filter(fn($e) => !is_null($e['value']))->count();
                    $totalCount = count($timeline);
                    $timelineDescriptions = [
                        'Created'          => 'Delivery run created from sorted batch',
                        'Rider Assigned'  => 'Rider assigned for delivery',
                        'Dispatched'       => 'Run dispatched, rider out for delivery',
                        'Completed'        => 'All deliveries completed',
                    ];
                    $timelineIcons = [
                        'Created'          => 'M12 4v16m8-8H4',
                        'Rider Assigned'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'Dispatched'       => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z',
                        'Completed'        => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ];
                    $timelineIconColor = [
                        'bg-slate-500'   => ['badge' => 'bg-slate-100 text-slate-600'],
                        'bg-blue-500'    => ['badge' => 'bg-blue-50 text-blue-700'],
                        'bg-violet-500'  => ['badge' => 'bg-violet-50 text-violet-700'],
                        'bg-emerald-500' => ['badge' => 'bg-emerald-50 text-emerald-700'],
                    ];
                @endphp

                {{-- Section Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-200/60 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Delivery Timeline</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Step-by-step progress from creation to completion</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ $completedCount }} / {{ $totalCount }} steps</span>
                </div>

                {{-- Timeline Events --}}
                <div>
                    @foreach($timeline as $index => $event)
                        @php
                            $isCompleted = !is_null($event['value']);
                            $dotClass    = $event['dot'] ?? 'bg-slate-400';
                            $colors      = $timelineIconColor[$dotClass] ?? ['badge' => 'bg-slate-100 text-slate-500'];
                            $iconBg      = $isCompleted ? $dotClass : 'bg-slate-200';
                            $badgeClass  = $isCompleted ? $colors['badge'] : 'bg-slate-100 text-slate-400';
                            $desc        = $timelineDescriptions[$event['label']] ?? $event['label'];
                            $iconPath    = $timelineIcons[$event['label']] ?? 'M5 13l4 4L19 7';
                        @endphp

                        @if($index > 0)
                            <div class="flex justify-start pl-[22px] my-0.5">
                                <div class="w-px h-4 bg-slate-200"></div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3 bg-white rounded-2xl border border-slate-100 shadow-sm {{ $isCompleted ? 'hover:shadow-md hover:border-slate-200' : 'opacity-55' }} transition-all duration-200 p-3.5">
                            {{-- Colored icon square --}}
                            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm mt-0.5 {{ $iconBg }}">
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                                </svg>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $badgeClass }}">
                                        {{ $event['label'] }}
                                    </span>
                                    <span class="text-[10px] font-medium whitespace-nowrap flex items-center gap-1 {{ $isCompleted ? 'text-slate-400' : 'text-slate-300' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $event['value'] ? $event['value']->format('Y-m-d H:i:s') : 'Pending' }}
                                    </span>
                                </div>
                                <p class="text-sm font-bold leading-snug {{ $isCompleted ? 'text-slate-800' : 'text-slate-400' }}">{{ $desc }}</p>
                            </div>
                        </div>
                    @endforeach

                    @if($run->status === 'cancelled')
                        <div class="flex justify-start pl-[22px] my-0.5">
                            <div class="w-px h-4 bg-rose-200"></div>
                        </div>
                        <div class="flex items-start gap-3 bg-rose-50 rounded-2xl border border-rose-200 shadow-sm p-3.5">
                            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm mt-0.5 bg-rose-500">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-700">
                                        Cancelled
                                    </span>
                                </div>
                                <p class="text-sm font-bold text-rose-700 leading-snug">Delivery Run Cancelled</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Assign Rider Modal --}}
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @@keydown.escape.window="showAssignModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @@click="showAssignModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" @@click.stop>
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ $run->assignedDriver ? 'Reassign Rider' : 'Assign Rider' }}</h3>
                        <p class="text-xs text-slate-500">Select a delivery rider for this run</p>
                    </div>
                </div>
                <button @@click="showAssignModal = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Select Rider <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select x-model="selectedDriverId"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                            <option value="">Choose a rider...</option>
                            @foreach($deliveryDrivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }} — {{ $driver->vehicle_type ?? '' }} {{ $driver->vehicle_number ?? '' }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <button @@click="showAssignModal = false"
                    class="px-4 py-2 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button @@click="assignDriver()"
                    :disabled="actionLoading || !selectedDriverId"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-teal-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="actionLoading ? 'Assigning...' : '{{ $run->assignedDriver ? 'Reassign Rider' : 'Assign Rider' }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.__deliveryRunConfig = @json($runConfig);
    window.__deliveryRunStops = @json($stopsData);
    window.__deliveryRunItems = @json($itemsData);
</script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deliveryRunShowPage', () => {
        let config = window.__deliveryRunConfig || {};
        config.stops = window.__deliveryRunStops || [];
        config.items = window.__deliveryRunItems || [];

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        return {
            activeTab: 'overview',
            selectedDriverId: '',
            actionLoading: false,
            showAssignModal: false,

            // Stops state
            stopSearch: '',

            // Items table state
            itemSearch: '',
            itemSortBy: 'description',
            itemSortDir: 'asc',
            itemPage: 1,
            itemPerPage: 10,

            stopStatusClass(status) {
                const map = {
                    pending: 'bg-slate-50 text-slate-600 ring-slate-200',
                    arrived: 'bg-amber-50 text-amber-700 ring-amber-200',
                    delivered: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    failed: 'bg-rose-50 text-rose-700 ring-rose-200',
                    handed_off: 'bg-violet-50 text-violet-700 ring-violet-200',
                };
                return map[status] || 'bg-slate-50 text-slate-600 ring-slate-200';
            },

            itemStatusClass(status) {
                const map = {
                    pending: 'border-slate-200 bg-slate-50 text-slate-700',
                    delivered: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    failed: 'border-rose-200 bg-rose-50 text-rose-700',
                    partial: 'border-amber-200 bg-amber-50 text-amber-700',
                };
                return map[status] || 'border-slate-200 bg-slate-50 text-slate-700';
            },

            filteredStops() {
                let list = config.stops || [];
                if (this.stopSearch) {
                    const q = this.stopSearch.toLowerCase();
                    list = list.filter(s =>
                        (s.recipient_name || '').toLowerCase().includes(q) ||
                        (s.recipient_phone || '').toLowerCase().includes(q) ||
                        (s.town || '').toLowerCase().includes(q) ||
                        (s.region_name || '').toLowerCase().includes(q) ||
                        (s.district_name || '').toLowerCase().includes(q) ||
                        (s.gh_post_address || '').toLowerCase().includes(q) ||
                        (s.landmark || '').toLowerCase().includes(q) ||
                        (s.status || '').toLowerCase().includes(q)
                    );
                }
                return list;
            },

            filteredItems() {
                let list = config.items || [];
                if (this.itemSearch) {
                    const q = this.itemSearch.toLowerCase();
                    list = list.filter(i =>
                        (i.description || '').toLowerCase().includes(q) ||
                        (i.shipment_number || '').toLowerCase().includes(q) ||
                        (i.stop_recipient || '').toLowerCase().includes(q) ||
                        (i.status || '').toLowerCase().includes(q) ||
                        (i.notes || '').toLowerCase().includes(q)
                    );
                }
                const dir = this.itemSortDir === 'asc' ? 1 : -1;
                const key = this.itemSortBy;
                list = [...list].sort((a, b) => {
                    const av = typeof a[key] === 'number' ? a[key] : (a[key] ?? '').toString().toLowerCase();
                    const bv = typeof b[key] === 'number' ? b[key] : (b[key] ?? '').toString().toLowerCase();
                    return av < bv ? -dir : av > bv ? dir : 0;
                });
                return list;
            },

            paginatedItems() {
                const all = this.filteredItems();
                const start = (this.itemPage - 1) * this.itemPerPage;
                return all.slice(start, start + this.itemPerPage);
            },

            itemLastPage() {
                return Math.max(1, Math.ceil(this.filteredItems().length / this.itemPerPage));
            },

            sortItems(col) {
                if (this.itemSortBy === col) {
                    this.itemSortDir = this.itemSortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.itemSortBy = col;
                    this.itemSortDir = 'asc';
                }
                this.itemPage = 1;
            },

            async assignDriver() {
                if (!this.selectedDriverId) {
                    window.showToast?.('Select a rider first.', 'warning');
                    return;
                }
                this.actionLoading = true;
                try {
                    const response = await fetch(config.assign_driver_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ driver_id: Number(this.selectedDriverId) }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to assign rider.');
                    window.showToast?.(result.message || 'Rider assigned successfully.', 'success');
                    this.showAssignModal = false;
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to assign rider.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async dispatchRun() {
                this.actionLoading = true;
                try {
                    const response = await fetch(config.dispatch_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to dispatch run.');
                    window.showToast?.(result.message || 'Delivery run dispatched successfully.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to dispatch delivery run.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async resendCode(stopId) {
                this.actionLoading = true;
                try {
                    const url = (config.resend_code_endpoint || '').replace('__STOP__', stopId);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to resend code.');
                    window.showToast?.(result.message || 'Verification code resent successfully.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to resend verification code.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
        };
    });
});
</script>
@endpush
@endsection
