@extends('warehouse.layouts.app')

@section('title', 'Transport Manifest Details')
@section('page-title', 'Transport Manifest Details')

@php
    $items = $manifest->items;
    $totalExpected = $items->sum('expected_quantity');
    $totalLoaded = $items->sum('loaded_quantity');
    $totalReceived = $items->sum('received_quantity');

    $statusClass = match ($manifest->status) {
        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'assigned' => 'bg-orange-50 text-orange-800 border-orange-200',
        'loading' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'in_transit' => 'bg-violet-50 text-violet-700 border-violet-200',
        'arrived' => 'bg-amber-50 text-amber-700 border-amber-200',
        'received' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $statusDotClass = match ($manifest->status) {
        'draft' => 'bg-slate-400',
        'assigned' => 'bg-orange-600',
        'loading' => 'bg-indigo-500',
        'in_transit' => 'bg-violet-500',
        'arrived' => 'bg-amber-500',
        'received' => 'bg-emerald-500',
        'cancelled' => 'bg-rose-500',
        default => 'bg-slate-400',
    };

    $timeline = [
        ['label' => 'Created', 'value' => $manifest->created_at, 'dot' => 'bg-slate-500'],
        ['label' => 'Driver Assigned', 'value' => $manifest->assigned_at, 'dot' => 'bg-orange-600'],
        ['label' => 'Dispatched', 'value' => $manifest->dispatched_at, 'dot' => 'bg-violet-500'],
        ['label' => 'Arrived Destination', 'value' => $manifest->arrived_at, 'dot' => 'bg-amber-500'],
        ['label' => 'Received', 'value' => $manifest->received_at, 'dot' => 'bg-emerald-500'],
    ];
@endphp

@section('content')
<div class="space-y-6"
     x-data="transportManifestShowPage"
     data-transport-manifest-show-config="{{ json_encode($manifestConfig, JSON_INVALID_UTF8_SUBSTITUTE) }}">

    {{-- ── Hero ────────────────────────────────────────────────────────── --}}
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="manifestGrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#manifestGrid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <div class="mb-6">
                    <a href="{{ route('warehouse.manifests.transport.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Transport Manifests</span>
                    </a>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-violet-500 via-violet-600 to-indigo-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-violet-500/30 ring-4 ring-white/10">
                                <svg class="w-10 h-10 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $manifest->manifest_number }}</h1>
                                <p class="text-slate-300 text-sm mt-0.5 truncate">
                                    {{ $manifest->originWarehouse?->name ?? '-' }}
                                    <svg class="inline w-3.5 h-3.5 text-slate-400 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                    {{ $manifest->destinationWarehouse?->name ?? '-' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ str($manifest->status)->replace('_', ' ')->title() }}
                                </span>
                                @if($manifest->sortBatch)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                        Batch: {{ $manifest->sortBatch->batch_number }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap lg:ml-auto lg:self-start">
                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-600/30 to-orange-700/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($items->count()) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Items</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($totalExpected) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Expected</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500/30 to-indigo-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($totalLoaded) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Loaded</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($totalReceived) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Received</p>
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

            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Manifest</p>

            {{-- Overview --}}
            <button @@click="activeTab = 'overview'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'overview' ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'overview' ? 'bg-violet-500 shadow-sm shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'overview' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'overview' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Overview</span>
            </button>

            {{-- Items --}}
            <button @@click="activeTab = 'items'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'items' ? 'bg-orange-50 ring-1 ring-orange-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'items' ? 'bg-orange-500 shadow-sm shadow-orange-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'items' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'items' ? 'font-bold text-orange-800' : 'font-medium text-slate-500 group-hover:text-slate-700'">Items</span>
                <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'items' ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500'">{{ $items->count() }}</span>
            </button>

            {{-- Divider: Operations --}}
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Operations</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            {{-- Assignment --}}
            <button @@click="activeTab = 'assignment'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'assignment' ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'assignment' ? 'bg-violet-500 shadow-sm shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'assignment' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'assignment' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Assignment</span>
                @if($assignmentHistory->count() > 0)
                    <span class="ml-auto inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold transition-colors"
                        :class="activeTab === 'assignment' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-500'">{{ $assignmentHistory->count() }}</span>
                @endif
            </button>

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

                        {{-- Card A: Manifest Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Manifest Summary</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Transport manifest details and routing</p>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ str($manifest->status)->replace('_', ' ')->title() }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Manifest #</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $manifest->manifest_number }}</p>
                                    @if($manifest->sortBatch)
                                        <p class="text-xs text-slate-500">Batch: {{ $manifest->sortBatch->batch_number }}</p>
                                    @endif
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Created</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $manifest->created_at?->format('M d, Y') ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $manifest->created_at?->format('H:i') ?? '' }}</p>
                                </div>
                                <div class="rounded-xl border px-4 py-3 {{ $manifest->dispatched_at ? 'bg-violet-50 border-violet-100' : 'bg-slate-50 border-slate-100' }}">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ $manifest->dispatched_at ? 'text-violet-500' : 'text-slate-400' }} mb-1">Dispatched</p>
                                    <p class="text-sm font-bold {{ $manifest->dispatched_at ? 'text-slate-900' : 'text-slate-400' }}">{{ $manifest->dispatched_at ? $manifest->dispatched_at->format('M d, Y') : '—' }}</p>
                                    <p class="text-xs {{ $manifest->dispatched_at ? 'text-violet-600/70' : 'text-slate-400' }}">{{ $manifest->dispatched_at ? $manifest->dispatched_at->format('H:i') : 'Not yet dispatched' }}</p>
                                </div>
                            </div>

                        </div>

                        {{-- Card B: Route (Origin → Destination) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Origin Warehouse --}}
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Origin</h3>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Warehouse</span>
                                        <span class="font-semibold text-slate-800">{{ $manifest->originWarehouse?->name ?? '—' }}</span>
                                    </div>
                                    @if($manifest->originWarehouse?->code)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Code</span>
                                            <span class="font-mono font-semibold text-slate-700">{{ $manifest->originWarehouse->code }}</span>
                                        </div>
                                    @endif
                                    @if($manifest->originWarehouse?->location)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Location</span>
                                            <span class="text-slate-600">{{ $manifest->originWarehouse->location }}</span>
                                        </div>
                                    @endif
                                    @if($manifest->createdBy)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Created By</span>
                                            <span class="font-semibold text-slate-800">{{ $manifest->createdBy->name }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Destination Warehouse --}}
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Destination</h3>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Warehouse</span>
                                        <span class="font-semibold text-slate-800">{{ $manifest->destinationWarehouse?->name ?? '—' }}</span>
                                    </div>
                                    @if($manifest->destinationWarehouse?->code)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Code</span>
                                            <span class="font-mono font-semibold text-slate-700">{{ $manifest->destinationWarehouse->code }}</span>
                                        </div>
                                    @endif
                                    @if($manifest->destinationWarehouse?->location)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Location</span>
                                            <span class="text-slate-600">{{ $manifest->destinationWarehouse->location }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Arrived</span>
                                        <span class="font-semibold {{ $manifest->arrived_at ? 'text-emerald-700' : 'text-slate-400' }}">{{ $manifest->arrived_at ? $manifest->arrived_at->format('M d, Y H:i') : 'Pending' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card C: Transport Progress --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h4 class="text-sm font-bold text-slate-800">Transport Progress</h4>
                            </div>
                            <div class="overflow-x-auto -mx-2 px-2">
                                <div class="relative flex min-w-[480px]">
                                    <div class="absolute top-5 left-[calc(100%/10)] right-[calc(100%/10)] h-px bg-slate-200 -z-0"></div>
                                    @php
                                        $steps = [
                                            ['label' => 'Created', 'at' => $manifest->created_at, 'color' => 'slate'],
                                            ['label' => 'Assigned', 'at' => $manifest->assigned_at, 'color' => 'blue'],
                                            ['label' => 'Dispatched', 'at' => $manifest->dispatched_at, 'color' => 'violet'],
                                            ['label' => 'Arrived', 'at' => $manifest->arrived_at, 'color' => 'amber'],
                                            ['label' => 'Received', 'at' => $manifest->received_at, 'color' => 'emerald'],
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

                        {{-- Notes / Cancellation --}}
                        @if($manifest->notes || $manifest->cancellation_reason)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @if($manifest->notes)
                                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Notes</p>
                                        </div>
                                        <p class="text-xs text-slate-700">{{ $manifest->notes }}</p>
                                    </div>
                                @endif
                                @if($manifest->cancellation_reason)
                                    <div class="bg-rose-50 rounded-2xl border border-rose-200 shadow-sm p-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-rose-600">Cancellation Reason</p>
                                        </div>
                                        <p class="text-xs text-rose-700">{{ $manifest->cancellation_reason }}</p>
                                    </div>
                                @endif
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
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-orange-600/20 to-orange-700/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Total Items</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($items->count()) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/20 to-violet-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Expected Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalExpected) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wide">Loaded Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalLoaded) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl border {{ $totalReceived > 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100' }}">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $totalReceived > 0 ? 'from-emerald-500/20 to-emerald-600/10' : 'from-slate-400/20 to-slate-500/10' }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 {{ $totalReceived > 0 ? 'text-emerald-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold {{ $totalReceived > 0 ? 'text-emerald-600' : 'text-slate-400' }} uppercase tracking-wide">Received Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalReceived) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Widget: Driver Assignment --}}
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            {{-- Gradient Header --}}
                            <div class="bg-gradient-to-br from-violet-500 to-indigo-600 px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-white leading-none">Driver Assignment</h3>
                                            @if($manifest->assignedDriver)
                                                <p class="text-[11px] text-white/60 mt-0.5">{{ $manifest->assignedDriver->name }}</p>
                                            @else
                                                <p class="text-[11px] text-white/50 mt-0.5">No driver assigned</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-white/20 text-white ring-1 ring-white/30 capitalize">
                                            {{ str($manifest->status)->replace('_', ' ')->title() }}
                                        </span>
                                        <button @@click="activeTab = 'assignment'" class="text-[11px] text-white/60 hover:text-white font-medium transition-colors flex items-center gap-0.5">
                                            Details <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            {{-- Body --}}
                            <div class="p-5">
                                @if(!$manifest->assignedDriver)
                                    {{-- No driver: show assign form --}}
                                    @if($manifest->status === 'draft')
                                        <div class="text-center py-4">
                                            <div class="w-11 h-11 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center mx-auto mb-3">
                                                <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-500 mb-3">No driver assigned</p>
                                            <div class="mb-3">
                                                <select x-model="selectedDriverId"
                                                    class="w-full text-xs rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:ring-2 focus:ring-violet-500/30 focus:border-violet-400 outline-none transition-all">
                                                    <option value="">Choose a driver...</option>
                                                    @foreach($transportDrivers as $driver)
                                                        <option value="{{ $driver->id }}">{{ $driver->name }} — {{ $driver->vehicle_type ?? '' }} {{ $driver->vehicle_number ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button @@click="assignDriver()"
                                                :disabled="actionLoading || !selectedDriverId"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-violet-500 to-indigo-600 hover:opacity-90 text-white text-xs font-bold rounded-xl shadow-lg shadow-violet-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                                <svg x-show="actionLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <svg x-show="!actionLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Assign Driver
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <svg class="w-9 h-9 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <p class="text-xs text-slate-400">No driver assignment</p>
                                        </div>
                                    @endif
                                @else
                                    {{-- Has driver --}}
                                    {{-- Driver info --}}
                                    <div class="flex items-center gap-3 mb-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="w-10 h-10 rounded-full ring-2 ring-violet-200 bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-sm flex-shrink-0">
                                            {{ strtoupper(substr($manifest->assignedDriver->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-900 truncate leading-none">{{ $manifest->assignedDriver->name }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $manifest->assignedDriver->phone ?? '—' }}</p>
                                        </div>
                                    </div>
                                    {{-- Destination Warehouse --}}
                                    <div class="flex items-center gap-2.5 mb-4 px-3 py-2.5 rounded-xl bg-amber-50 border border-amber-100/80">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wide leading-none mb-0.5">Destination Warehouse</p>
                                            <p class="text-xs font-bold text-slate-800 truncate">{{ $manifest->destinationWarehouse?->name ?? 'Not set' }}</p>
                                        </div>
                                        @if($manifest->destinationWarehouse?->code)
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded-md flex-shrink-0">{{ $manifest->destinationWarehouse->code }}</span>
                                        @endif
                                    </div>
                                    {{-- Progress pill bars --}}
                                    <div class="mb-4">
                                        @php
                                            $progressSteps = [
                                                ['label' => 'Assigned', 'at' => $manifest->assigned_at, 'color' => 'violet'],
                                                ['label' => 'Dispatched', 'at' => $manifest->dispatched_at, 'color' => 'blue'],
                                                ['label' => 'In Transit', 'at' => $manifest->dispatched_at, 'color' => 'indigo'],
                                                ['label' => 'Arrived', 'at' => $manifest->arrived_at, 'color' => 'amber'],
                                            ];
                                            $progressSteps2 = [
                                                ['label' => 'Received', 'at' => $manifest->received_at, 'color' => 'emerald'],
                                            ];
                                        @endphp
                                        <div class="grid grid-cols-4 gap-1 mb-2">
                                            @foreach($progressSteps as $ps)
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors {{ $ps['at'] ? 'bg-'.$ps['color'].'-500' : 'bg-slate-200' }}"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none {{ $ps['at'] ? 'text-'.$ps['color'].'-600' : 'text-slate-300' }}">{{ $ps['label'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="grid grid-cols-1 gap-1">
                                            @foreach($progressSteps2 as $ps)
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors {{ $ps['at'] ? 'bg-'.$ps['color'].'-500' : 'bg-slate-200' }}"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none {{ $ps['at'] ? 'text-'.$ps['color'].'-600' : 'text-slate-300' }}">{{ $ps['label'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    {{-- Action buttons --}}
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if(in_array($manifest->status, ['draft', 'assigned']))
                                            <button @@click="showUnassignModal = true"
                                                :disabled="actionLoading"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-rose-200 text-rose-600 text-xs font-bold hover:bg-rose-50 transition-colors disabled:opacity-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                Unassign
                                            </button>
                                        @endif
                                        @if($manifest->status === 'assigned')
                                            <button @@click="dispatchManifest()"
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
                            <h2 class="text-lg font-semibold text-slate-900">Manifest Line Items</h2>
                            <p class="mt-0.5 text-sm text-slate-500">All shipment items included in this transport manifest.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="filteredItems().length + ' Items'"></span>
                </div>

                {{-- Search --}}
                <div class="mb-4">
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" x-model="itemSearch" @@input="itemPage = 1"
                            placeholder="Search item, shipment, vendor..."
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
                                        SHIPMENT ITEM
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
                                <th @@click="sortItems('expected_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        EXPECTED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'expected_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('loaded_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        LOADED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'loaded_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('received_quantity')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        RECEIVED
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'received_quantity' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sortItems('line_status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        STATUS
                                        <svg class="w-2.5 h-2.5 ml-1" :class="itemSortBy === 'line_status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">TIMESTAMPS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            {{-- Empty state --}}
                            <tr x-show="filteredItems().length === 0" x-cloak>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 text-xs">
                                    <span x-text="itemSearch ? 'No items match your search.' : 'No items in this manifest.'"></span>
                                </td>
                            </tr>
                            {{-- Data rows --}}
                            <template x-for="item in paginatedItems()" :key="item.id">
                                <tr class="hover:bg-slate-50/70 align-top">
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[220px]">
                                        <p class="font-semibold text-slate-900" x-text="item.description"></p>
                                        <p class="text-[11px] text-slate-500 mt-0.5" x-text="'ID: ' + item.shipment_item_id"></p>
                                        <p x-show="item.tracking_code" class="text-[11px] text-slate-500" x-text="'Tracking: ' + item.tracking_code"></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[160px]">
                                        <p class="font-semibold text-slate-900" x-text="item.shipment_number"></p>
                                        <p x-show="item.vendor_name" class="text-[11px] text-slate-500 mt-0.5" x-text="item.vendor_name"></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800" x-text="item.expected_quantity"></td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800" x-text="item.loaded_quantity"></td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800" x-text="item.received_quantity"></td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm shadow-sm"
                                            :class="itemStatusClass(item.line_status)"
                                            x-text="item.line_status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-600 min-w-[180px]" x-text="item.notes || '-'"></td>
                                    <td class="px-4 py-2.5 text-xs text-slate-500 min-w-[160px]">
                                        <span x-show="item.loaded_at" x-text="'Loaded: ' + item.loaded_at"></span>
                                        <span x-show="item.loaded_at && item.received_at"> · </span>
                                        <span x-show="item.received_at" x-text="'Received: ' + item.received_at"></span>
                                        <span x-show="!item.loaded_at && !item.received_at">-</span>
                                    </td>
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

            {{-- ── Assignment Tab ─────────────────────────────────────── --}}
            <div x-show="activeTab === 'assignment'" x-cloak>

                {{-- Section Header --}}
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-violet-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Assignment History</h3>
                            <p class="text-xs text-slate-500">All transport assignments for this manifest</p>
                        </div>
                    </div>
                    @if(in_array($manifest->status, ['draft', 'assigned']))
                        <div class="flex items-center gap-2">
                            @if($manifest->assignedDriver)
                                <button @@click="showUnassignModal = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 text-xs font-semibold shadow-sm transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Unassign
                                </button>
                            @endif
                            <button @@click="showAssignModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                {{ $manifest->assignedDriver ? 'Reassign Driver' : 'Assign Driver' }}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Empty state --}}
                @if($assignmentHistory->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <svg class="w-12 h-12 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-slate-400 text-sm font-medium">No assignment history</p>
                        <p class="text-slate-300 text-xs mt-1">Assignments will appear here once a driver is assigned</p>
                    </div>
                @endif

                {{-- Assignment History Cards --}}
                <div class="space-y-3">
                    @foreach($assignmentHistory as $assignment)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            {{-- Card Top Bar: Driver + Status --}}
                            <div class="flex items-center gap-4 px-5 pt-5 pb-4">
                                {{-- Avatar --}}
                                <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-base shadow-sm {{ $assignment->unassigned_at ? 'bg-gradient-to-br from-slate-400 to-slate-500' : 'bg-gradient-to-br from-violet-500 to-purple-600' }}">
                                    {{ strtoupper(substr($assignment->driver?->name ?? '?', 0, 1)) }}
                                </div>
                                {{-- Name + phone --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate">{{ $assignment->driver?->name ?? 'Unknown Driver' }}</p>
                                    <p class="text-xs text-slate-400">{{ $assignment->driver?->phone ?? '—' }}</p>
                                </div>
                                {{-- Status badge --}}
                                @if(!$assignment->unassigned_at)
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold flex-shrink-0 ring-1 bg-emerald-50 text-emerald-700 ring-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold flex-shrink-0 ring-1 bg-slate-50 text-slate-500 ring-slate-200">
                                        Unassigned
                                    </span>
                                @endif
                            </div>

                            {{-- Divider + Meta Row --}}
                            <div class="border-t border-slate-100 px-5 py-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 bg-slate-50/60">
                                {{-- Destination Warehouse --}}
                                @if($manifest->destinationWarehouse)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                        <svg class="w-3.5 h-3.5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span class="font-semibold text-slate-700">{{ $manifest->destinationWarehouse->name }}</span>
                                        @if($manifest->destinationWarehouse->code)
                                            <span class="text-slate-400">({{ $manifest->destinationWarehouse->code }})</span>
                                        @endif
                                    </span>
                                @endif
                                {{-- Assigned At --}}
                                @if($assignment->assigned_at)
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Assigned: <span class="font-medium text-slate-600">{{ $assignment->assigned_at->format('M d, Y H:i') }}</span>
                                    </span>
                                @endif
                                {{-- Assigned By --}}
                                @if($assignment->assignedBy)
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        By: <span class="font-medium text-slate-600">{{ $assignment->assignedBy->name }}</span>
                                    </span>
                                @endif
                                {{-- Unassigned At --}}
                                @if($assignment->unassigned_at)
                                    <span class="inline-flex items-center gap-1 text-xs text-rose-500">
                                        <svg class="w-3 h-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Removed: <span class="font-medium">{{ $assignment->unassigned_at->format('M d, Y H:i') }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Unassign Reason --}}
                            @if($assignment->unassign_reason)
                                <div class="px-5 py-2.5 bg-rose-50 border-t border-rose-100 flex items-start gap-2 text-xs text-rose-600">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                    <span>{{ $assignment->unassign_reason }}</span>
                                </div>
                            @endif

                            {{-- Vehicle Info --}}
                            @if($assignment->driver?->vehicle_type || $assignment->driver?->vehicle_number)
                                <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 text-xs text-slate-500">
                                    <svg class="inline w-3 h-3 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0014 6h-1"/></svg>
                                    {{ $assignment->driver->vehicle_type ?? '' }} {{ $assignment->driver->vehicle_number ?? '' }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Timeline Tab ─────────────────────────────────────────── --}}
            <div x-show="activeTab === 'timeline'" x-cloak>
                @php
                    $completedCount = collect($timeline)->filter(fn($e) => !is_null($e['value']))->count();
                    $totalCount = count($timeline);
                    $timelineDescriptions = [
                        'Created'              => 'Transport manifest created from sealed batch',
                        'Driver Assigned'      => 'Driver assigned for transport',
                        'Dispatched'           => 'Manifest dispatched, items in transit',
                        'Arrived Destination'  => 'Driver arrived at destination warehouse',
                        'Received'             => 'Items received and verified at destination',
                    ];
                    $timelineIcons = [
                        'Created'              => 'M12 4v16m8-8H4',
                        'Driver Assigned'      => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'Dispatched'           => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                        'Arrived Destination'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                        'Received'             => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ];
                    $timelineIconColor = [
                        'bg-slate-500'   => ['badge' => 'bg-slate-100 text-slate-600'],
                        'bg-orange-500'    => ['badge' => 'bg-orange-50 text-orange-800'],
                        'bg-violet-500'  => ['badge' => 'bg-violet-50 text-violet-700'],
                        'bg-amber-500'   => ['badge' => 'bg-amber-50 text-amber-700'],
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
                            <h3 class="text-base font-bold text-slate-900">Transport Timeline</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Step-by-step progress from creation to delivery</p>
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

                    @if($manifest->status === 'cancelled')
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
                                <p class="text-sm font-bold text-rose-700 leading-snug">Transport Manifest Cancelled</p>
                                @if($manifest->cancellation_reason)
                                    <p class="text-[11px] text-rose-600 mt-1.5">{{ $manifest->cancellation_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Unassign Driver Modal --}}
    <div x-show="showUnassignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @@keydown.escape.window="showUnassignModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @@click="showUnassignModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" @@click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Unassign Driver</h3>
                    <p class="text-xs text-slate-500">Remove the currently assigned driver from this manifest.</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-slate-700 mb-1.5">Reason (optional)</label>
                <textarea x-model="unassignReason" rows="3" placeholder="Enter reason for unassigning..."
                    class="w-full text-xs rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:ring-2 focus:ring-rose-500/30 focus:border-rose-400 outline-none transition-all resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button @@click="showUnassignModal = false"
                    class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button @@click="unassignDriver()"
                    :disabled="actionLoading"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="actionLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="actionLoading ? 'Unassigning...' : 'Unassign Driver'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Assign Driver Modal --}}
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @@keydown.escape.window="showAssignModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @@click="showAssignModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" @@click.stop>
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ $manifest->assignedDriver ? 'Reassign Driver' : 'Assign Driver' }}</h3>
                        <p class="text-xs text-slate-500">Select a transport driver for this manifest</p>
                    </div>
                </div>
                <button @@click="showAssignModal = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Select Driver <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select x-model="selectedDriverId"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                            <option value="">Choose a driver...</option>
                            @foreach($transportDrivers as $driver)
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
                    class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-violet-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="actionLoading ? 'Assigning...' : '{{ $manifest->assignedDriver ? 'Reassign Driver' : 'Assign Driver' }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.__manifestItems = @json($itemsData);
</script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transportManifestShowPage', () => {
        const container = document.querySelector('[data-transport-manifest-show-config]');
        let config = {};
        if (container) {
            try { config = JSON.parse(container.getAttribute('data-transport-manifest-show-config')) || {}; } catch (e) { console.error('Invalid manifest show config', e); }
        }
        config.items = window.__manifestItems || [];

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        return {
            activeTab: 'overview',
            selectedDriverId: '',
            actionLoading: false,
            showUnassignModal: false,
            showAssignModal: false,
            unassignReason: '',

            // Items table state
            itemSearch: '',
            itemSortBy: 'description',
            itemSortDir: 'asc',
            itemPage: 1,
            itemPerPage: 10,

            itemStatusClass(status) {
                const map = {
                    loaded: 'border-indigo-200 bg-indigo-50 text-indigo-700',
                    received: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    short: 'border-amber-200 bg-amber-50 text-amber-700',
                    excess: 'border-sky-200 bg-sky-50 text-sky-700',
                    damaged: 'border-rose-200 bg-rose-50 text-rose-700',
                };
                return map[status] || 'border-slate-200 bg-slate-50 text-slate-700';
            },

            filteredItems() {
                let list = config.items || [];
                if (this.itemSearch) {
                    const q = this.itemSearch.toLowerCase();
                    list = list.filter(i =>
                        (i.description || '').toLowerCase().includes(q) ||
                        (i.tracking_code || '').toLowerCase().includes(q) ||
                        (i.shipment_number || '').toLowerCase().includes(q) ||
                        (i.vendor_name || '').toLowerCase().includes(q) ||
                        (i.line_status || '').toLowerCase().includes(q) ||
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
                    window.showToast?.('Select a driver first.', 'warning');
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
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to assign driver.');
                    window.showToast?.(result.message || 'Driver assigned successfully.', 'success');
                    this.showAssignModal = false;
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to assign driver.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async dispatchManifest() {
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
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to dispatch manifest.');
                    window.showToast?.(result.message || 'Manifest dispatched successfully.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to dispatch manifest.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async unassignDriver() {
                this.actionLoading = true;
                try {
                    const response = await fetch(config.unassign_driver_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ reason: this.unassignReason || null }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to unassign driver.');
                    window.showToast?.(result.message || 'Driver unassigned successfully.', 'success');
                    this.showUnassignModal = false;
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to unassign driver.', 'error');
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
