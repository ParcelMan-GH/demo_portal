@extends('admin.layouts.app')

@section('title', $manifest->manifest_number)
@section('breadcrumb-parent', 'Transport Manifests')
@section('breadcrumb-current', $manifest->manifest_number)

@php
$statusColors = match($manifest->status) {
    'draft'      => ['badge' => 'bg-slate-500/20 text-slate-300',   'dot' => 'bg-slate-400'],
    'assigned'   => ['badge' => 'bg-blue-500/20 text-blue-300',     'dot' => 'bg-blue-400'],
    'loading'    => ['badge' => 'bg-purple-500/20 text-purple-300', 'dot' => 'bg-purple-400'],
    'in_transit' => ['badge' => 'bg-amber-500/20 text-amber-300',   'dot' => 'bg-amber-400'],
    'arrived'    => ['badge' => 'bg-cyan-500/20 text-cyan-300',     'dot' => 'bg-cyan-400'],
    'received'   => ['badge' => 'bg-emerald-500/20 text-emerald-300', 'dot' => 'bg-emerald-400'],
    'cancelled'  => ['badge' => 'bg-rose-500/20 text-rose-300',     'dot' => 'bg-rose-400'],
    default      => ['badge' => 'bg-slate-500/20 text-slate-300',   'dot' => 'bg-slate-400'],
};

$lineStatusColors = [
    'pending'  => 'bg-slate-100 text-slate-700',
    'loaded'   => 'bg-blue-100 text-blue-700',
    'received' => 'bg-emerald-100 text-emerald-700',
    'short'    => 'bg-amber-100 text-amber-700',
    'excess'   => 'bg-purple-100 text-purple-700',
    'damaged'  => 'bg-rose-100 text-rose-700',
];
@endphp

@section('content')
<div class="space-y-6"
     x-data="adminTransportManifestShow"
     data-manifest-config="{{ json_encode($manifestConfig, JSON_INVALID_UTF8_SUBSTITUTE) }}"
     data-manifest-status="{{ $manifest->status }}"
     data-manifest-driver="{{ $manifest->assigned_driver_id }}">

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="{{ route('admin.transport-manifests.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Transport Manifests</span>
                    </a>
                </div>

                <!-- Main Row: Icon + Info LEFT, Stats RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    <!-- LEFT: Icon + Manifest Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-xl shadow-indigo-500/30 ring-4 ring-white/10">
                                <svg class="w-10 h-10 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $manifest->manifest_number }}</h1>
                                <p class="text-slate-400 text-sm mt-0.5">
                                    @if($manifest->originWarehouse && $manifest->destinationWarehouse)
                                        {{ $manifest->originWarehouse->name }}
                                        <svg class="w-3.5 h-3.5 inline text-slate-500 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                        {{ $manifest->destinationWarehouse->name }}
                                    @elseif($manifest->originWarehouse)
                                        From {{ $manifest->originWarehouse->name }}
                                    @elseif($manifest->destinationWarehouse)
                                        To {{ $manifest->destinationWarehouse->name }}
                                    @else
                                        No route assigned
                                    @endif
                                </p>
                            </div>

                            @if($manifest->assignedDriver)
                            <div class="flex items-center gap-1.5 text-xs text-slate-300">
                                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>{{ $manifest->assignedDriver->name }}</span>
                                @if($manifest->assignedDriver->phone)
                                    <span class="text-slate-500">&bull;</span>
                                    <span class="text-slate-400">{{ $manifest->assignedDriver->phone }}</span>
                                @endif
                            </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusColors['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusColors['dot'] }}"></span>
                                    {{ $statusLabel }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $manifest->created_at->format('M d, Y') }}
                                </span>
                                @if($manifest->sortBatch)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-500/20 text-violet-300">
                                    Batch: {{ $manifest->sortBatch->batch_number ?? '#' . $manifest->sortBatch->id }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Stats -->
                    <div class="flex flex-col gap-3 lg:ml-auto lg:items-end">
                        <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap">
                            <!-- Items Count -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500/30 to-indigo-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $manifest->items->count() }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Items</p>
                                </div>
                            </div>

                            <!-- Dispatched -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-none">
                                        {{ $manifest->dispatched_at ? $manifest->dispatched_at->format('M d') : '—' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Dispatched</p>
                                </div>
                            </div>

                            <!-- Arrived -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-500/30 to-cyan-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-none">
                                        {{ $manifest->arrived_at ? $manifest->arrived_at->format('M d') : '—' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Arrived</p>
                                </div>
                            </div>

                            <!-- Received -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-none">
                                        {{ $manifest->received_at ? $manifest->received_at->format('M d') : '—' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Received</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Panel (shown only when actions are available) -->
    @if(in_array($manifest->status, ['draft', 'assigned', 'loading']))
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 px-6 py-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mr-2">Actions</span>

            {{-- Assign Driver button: shown when draft or assigned (to reassign) --}}
            @if(in_array($manifest->status, ['draft', 'assigned']))
            <button
                type="button"
                @@click="openAssignDriverModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ $manifest->assigned_driver_id ? 'Reassign Driver' : 'Assign Driver' }}
            </button>
            @endif

            {{-- Unassign Driver button: only when assigned and there's a driver --}}
            @if($manifest->status === 'assigned' && $manifest->assigned_driver_id)
            <button
                type="button"
                @@click="openUnassignDriverModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-sm transition-colors"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Unassign Driver
            </button>
            @endif

            {{-- Dispatch button: only when assigned and has a driver --}}
            @if($manifest->status === 'assigned' && $manifest->assigned_driver_id)
            <button
                type="button"
                @@click="openDispatchModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold shadow-sm transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                Dispatch
            </button>
            @endif

            {{-- Mark all loaded: admin override for assigned/loading manifests --}}
            @if(in_array($manifest->status, ['assigned', 'loading']) && $manifest->assigned_driver_id && $manifest->items->isNotEmpty())
            <button
                type="button"
                @@click="markAllLoaded()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Mark All Loaded
            </button>
            @endif

            <!-- Spinner -->
            <div x-show="actionLoading" x-transition.opacity.duration.150ms class="flex items-center gap-2 text-xs text-slate-500" style="display:none;">
                <svg class="animate-spin w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Processing…
            </div>
        </div>
    </div>
    @endif

    <!-- Two-column info section -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Manifest Info Card (2/3 width) -->
        <div class="xl:col-span-2 bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Manifest Details</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                <!-- Manifest Number -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Manifest Number</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $manifest->manifest_number }}</p>
                </div>

                <!-- Status -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                    @php
                    $badgeClass = match($manifest->status) {
                        'draft'      => 'bg-slate-100 text-slate-700',
                        'assigned'   => 'bg-blue-100 text-blue-700',
                        'loading'    => 'bg-purple-100 text-purple-700',
                        'in_transit' => 'bg-amber-100 text-amber-700',
                        'arrived'    => 'bg-cyan-100 text-cyan-700',
                        'received'   => 'bg-emerald-100 text-emerald-700',
                        'cancelled'  => 'bg-red-100 text-red-700',
                        default      => 'bg-slate-100 text-slate-700',
                    };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <!-- Origin Warehouse -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Origin Warehouse</p>
                    @if($manifest->originWarehouse)
                        <p class="text-sm font-semibold text-slate-900">{{ $manifest->originWarehouse->name }}</p>
                        <p class="text-xs text-slate-500">{{ $manifest->originWarehouse->code }}</p>
                    @else
                        <p class="text-sm text-slate-400">—</p>
                    @endif
                </div>

                <!-- Destination Warehouse -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Destination Warehouse</p>
                    @if($manifest->destinationWarehouse)
                        <p class="text-sm font-semibold text-slate-900">{{ $manifest->destinationWarehouse->name }}</p>
                        <p class="text-xs text-slate-500">{{ $manifest->destinationWarehouse->code }}</p>
                    @else
                        <p class="text-sm text-slate-400">—</p>
                    @endif
                </div>

                <!-- Assigned Driver -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Assigned Driver</p>
                    @if($manifest->assignedDriver)
                        <p class="text-sm font-semibold text-slate-900">{{ $manifest->assignedDriver->name }}</p>
                        @if($manifest->assignedDriver->phone)
                            <p class="text-xs text-slate-500">{{ $manifest->assignedDriver->phone }}</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-400">—</p>
                    @endif
                </div>

                <!-- Sort Batch -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Sort Batch</p>
                    @if($manifest->sortBatch)
                        <p class="text-sm font-semibold text-slate-900">
                            {{ $manifest->sortBatch->batch_number ?? 'Batch #' . $manifest->sortBatch->id }}
                        </p>
                    @else
                        <p class="text-sm text-slate-400">—</p>
                    @endif
                </div>

                <!-- Assigned At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Assigned At</p>
                    <p class="text-sm text-slate-700">{{ $manifest->assigned_at ? $manifest->assigned_at->format('M d, Y H:i') : '—' }}</p>
                </div>

                <!-- Dispatched At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Dispatched At</p>
                    <p class="text-sm text-slate-700">{{ $manifest->dispatched_at ? $manifest->dispatched_at->format('M d, Y H:i') : '—' }}</p>
                </div>

                <!-- Arrived At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Arrived At</p>
                    <p class="text-sm text-slate-700">{{ $manifest->arrived_at ? $manifest->arrived_at->format('M d, Y H:i') : '—' }}</p>
                </div>

                <!-- Received At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Received At</p>
                    <p class="text-sm text-slate-700">{{ $manifest->received_at ? $manifest->received_at->format('M d, Y H:i') : '—' }}</p>
                </div>

                <!-- Created At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Created At</p>
                    <p class="text-sm text-slate-700">{{ $manifest->created_at->format('M d, Y H:i') }}</p>
                </div>

                <!-- Updated At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Last Updated</p>
                    <p class="text-sm text-slate-700">{{ $manifest->updated_at->format('M d, Y H:i') }}</p>
                </div>

                @if($manifest->notes)
                <!-- Notes -->
                <div class="sm:col-span-2">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Notes</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $manifest->notes }}</p>
                </div>
                @endif

                @if($manifest->status === 'cancelled' && $manifest->cancellation_reason)
                <!-- Cancellation Reason -->
                <div class="sm:col-span-2">
                    <p class="text-[10px] font-semibold text-red-400 uppercase tracking-wider mb-1">Cancellation Reason</p>
                    <p class="text-sm text-red-600 whitespace-pre-wrap">{{ $manifest->cancellation_reason }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Summary Card (1/3 width) -->
        <div class="flex flex-col gap-6">
            <!-- People / Summary -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Summary</h3>
                </div>

                <div class="space-y-4">
                    <!-- Item Count -->
                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                        <span class="text-xs font-medium text-slate-500">Total Items</span>
                        <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                            {{ $manifest->items->count() }}
                        </span>
                    </div>

                    @if($manifest->createdBy)
                    <!-- Created By -->
                    <div class="flex items-start justify-between py-2 border-b border-slate-100 gap-2">
                        <span class="text-xs font-medium text-slate-500 flex-shrink-0">Created By</span>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-slate-800">{{ $manifest->createdBy->name }}</p>
                        </div>
                    </div>
                    @endif

                    @if($manifest->receivedBy)
                    <!-- Received By -->
                    <div class="flex items-start justify-between py-2 border-b border-slate-100 gap-2">
                        <span class="text-xs font-medium text-slate-500 flex-shrink-0">Received By</span>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-slate-800">{{ $manifest->receivedBy->name }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Status Timeline -->
                    <div class="pt-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3">Timeline</p>
                        <ol class="relative ml-3 border-l border-slate-200 space-y-3">
                            <li class="pl-4">
                                <span class="absolute -left-1.5 mt-0.5 w-3 h-3 rounded-full border-2 {{ $manifest->created_at ? 'border-indigo-500 bg-indigo-100' : 'border-slate-300 bg-white' }}"></span>
                                <p class="text-[10px] font-semibold text-slate-600">Created</p>
                                <p class="text-[10px] text-slate-400">{{ $manifest->created_at->format('M d, Y H:i') }}</p>
                            </li>
                            <li class="pl-4">
                                <span class="absolute -left-1.5 mt-0.5 w-3 h-3 rounded-full border-2 {{ $manifest->assigned_at ? 'border-blue-500 bg-blue-100' : 'border-slate-300 bg-white' }}"></span>
                                <p class="text-[10px] font-semibold text-slate-600">Assigned</p>
                                <p class="text-[10px] text-slate-400">{{ $manifest->assigned_at ? $manifest->assigned_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </li>
                            <li class="pl-4">
                                <span class="absolute -left-1.5 mt-0.5 w-3 h-3 rounded-full border-2 {{ $manifest->dispatched_at ? 'border-amber-500 bg-amber-100' : 'border-slate-300 bg-white' }}"></span>
                                <p class="text-[10px] font-semibold text-slate-600">Dispatched</p>
                                <p class="text-[10px] text-slate-400">{{ $manifest->dispatched_at ? $manifest->dispatched_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </li>
                            <li class="pl-4">
                                <span class="absolute -left-1.5 mt-0.5 w-3 h-3 rounded-full border-2 {{ $manifest->arrived_at ? 'border-cyan-500 bg-cyan-100' : 'border-slate-300 bg-white' }}"></span>
                                <p class="text-[10px] font-semibold text-slate-600">Arrived</p>
                                <p class="text-[10px] text-slate-400">{{ $manifest->arrived_at ? $manifest->arrived_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </li>
                            <li class="pl-4">
                                <span class="absolute -left-1.5 mt-0.5 w-3 h-3 rounded-full border-2 {{ $manifest->received_at ? 'border-emerald-500 bg-emerald-100' : 'border-slate-300 bg-white' }}"></span>
                                <p class="text-[10px] font-semibold text-slate-600">Received</p>
                                <p class="text-[10px] text-slate-400">{{ $manifest->received_at ? $manifest->received_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('shared.transport-containers-section', ['manifest' => $manifest])

    <!-- Items Table -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Manifest Items</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $manifest->items->count() }} item(s) on this manifest</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            @if($manifest->items->isEmpty())
                <div class="flex flex-col items-center gap-2 py-10 text-slate-400">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-sm font-medium">No items on this manifest</p>
                </div>
            @else
                <div class="rounded-xl border border-slate-200/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">SHIPMENT</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESCRIPTION</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">TRACKING CODE</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">QTY</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">LOADED</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">RECEIVED</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">SCAN OUT</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">SCAN IN</th>
                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">CONTAINER</th>
                                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">LINE STATUS</th>
                                    @if(in_array($manifest->status, ['assigned', 'loading']))
                                    <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTION</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                @foreach($manifest->items as $index => $item)
                                @php
                                    $shipmentItem = $item->shipmentItem;
                                    $shipment     = $shipmentItem?->shipment;
                                    $lineClass    = $lineStatusColors[$item->line_status] ?? 'bg-slate-100 text-slate-700';
                                    $currentContainer = $manifest->containers
                                        ->first(fn ($container) => $container->items->contains('transport_manifest_item_id', $item->id));
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        @if($shipment)
                                            <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $shipment->shipment_number }}
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <p class="text-xs font-medium text-slate-800 max-w-[200px] truncate" title="{{ $shipmentItem?->description ?? '—' }}">
                                            {{ $shipmentItem?->description ?? '—' }}
                                        </p>
                                        @if($shipmentItem?->delivery_recipient_name)
                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $shipmentItem->delivery_recipient_name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        @if($shipmentItem?->tracking_code)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-[10px] font-mono font-semibold text-slate-700">
                                                {{ $shipmentItem->tracking_code }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="text-xs font-semibold text-slate-700">{{ $item->expected_quantity ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="text-xs text-slate-600">{{ $item->loaded_quantity ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="text-xs text-slate-600">{{ $item->received_quantity ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="text-xs text-slate-600">{{ $item->scan_out_count ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="text-xs text-slate-600">{{ $item->scan_in_count ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        @if(in_array($manifest->status, ['draft', 'assigned', 'loading']))
                                            <select
                                                @@change="moveItemToContainer({{ $item->id }}, $event.target.value)"
                                                :disabled="actionLoading"
                                                class="w-36 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-semibold text-slate-700 focus:border-slate-400 focus:ring-2 focus:ring-slate-100 disabled:opacity-50"
                                            >
                                                @foreach($manifest->containers->sortBy('sequence_number') as $container)
                                                    <option value="{{ $container->id }}" @selected($currentContainer?->id === $container->id)>
                                                        {{ $container->container_code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-[11px] font-mono font-semibold text-slate-600">{{ $currentContainer?->container_code ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        @if($item->line_status)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $lineClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $item->line_status)) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    @if(in_array($manifest->status, ['assigned', 'loading']))
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                        @if($item->line_status === 'loaded' || (int) $item->loaded_quantity >= (int) $item->expected_quantity)
                                            <span class="text-[10px] font-semibold text-emerald-600">Loaded</span>
                                        @else
                                            <button
                                                type="button"
                                                @@click="markItemLoaded({{ $item->id }})"
                                                :disabled="actionLoading"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[10px] font-semibold transition-colors disabled:opacity-50"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Mark Loaded
                                            </button>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @if($item->notes)
                                <tr class="bg-slate-50/50">
                                    <td class="px-4 py-1.5" colspan="{{ in_array($manifest->status, ['assigned', 'loading']) ? 12 : 11 }}">
                                        <p class="text-[10px] text-slate-500 italic">
                                            <span class="font-semibold not-italic text-slate-400">Note:</span>
                                            {{ $item->notes }}
                                        </p>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Warehouse Receipt Section -->
    @if($manifest->warehouseReceipt)
    @php $receipt = $manifest->warehouseReceipt; @endphp
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Warehouse Receipt</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Receipt created upon arrival at destination warehouse</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Receipt Status -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Receipt Status</p>
                    @php
                    $receiptBadgeClass = match($receipt->status) {
                        'draft'              => 'bg-slate-100 text-slate-700',
                        'discrepancy_open'   => 'bg-amber-100 text-amber-700',
                        'finalized'          => 'bg-emerald-100 text-emerald-700',
                        default              => 'bg-slate-100 text-slate-700',
                    };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $receiptBadgeClass }}">
                        {{ ucwords(str_replace('_', ' ', $receipt->status)) }}
                    </span>
                </div>

                <!-- Started At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Started At</p>
                    <p class="text-sm text-slate-700">{{ $receipt->started_at ? $receipt->started_at->format('M d, Y H:i') : '—' }}</p>
                    @if($receipt->startedBy)
                        <p class="text-[10px] text-slate-400 mt-0.5">by {{ $receipt->startedBy->name }}</p>
                    @endif
                </div>

                <!-- Finalized At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Finalized At</p>
                    <p class="text-sm text-slate-700">{{ $receipt->finalized_at ? $receipt->finalized_at->format('M d, Y H:i') : '—' }}</p>
                    @if($receipt->finalizedBy)
                        <p class="text-[10px] text-slate-400 mt-0.5">by {{ $receipt->finalizedBy->name }}</p>
                    @endif
                </div>

                <!-- Discrepancies -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Discrepancies</p>
                    @if($receipt->status === 'discrepancy_open')
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Open discrepancies
                        </span>
                    @else
                        <span class="text-xs text-slate-500">None</span>
                    @endif
                </div>
            </div>

            @if($receipt->notes)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Receipt Notes</p>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $receipt->notes }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    @include('shared.transport-container-modals')

    {{-- ── Assign Driver Modal ──────────────────────────────────────── --}}
    <div
        x-show="assignDriverModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
        @@keydown.escape.window="assignDriverModalOpen = false"
    >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="assignDriverModalOpen = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" @@click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Assign Driver</h3>
                </div>
                <button type="button" @@click="assignDriverModalOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-xs text-slate-500">Select a transport-capable driver to assign to this manifest.</p>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Driver</label>
                    <select
                        x-model="selectedDriverId"
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-2 focus:ring-blue-400/50 focus:border-blue-300 transition-colors"
                    >
                        <option value="">— Select a driver —</option>
                        @foreach($transportDrivers as $driver)
                        <option value="{{ $driver->id }}">
                            {{ $driver->name }}{{ $driver->phone ? ' · ' . $driver->phone : '' }}{{ $driver->vehicle_type ? ' (' . $driver->vehicle_type . ')' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" @@click="assignDriverModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button
                    type="button"
                    @@click="submitAssignDriver()"
                    :disabled="!selectedDriverId || actionLoading"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors"
                >
                    Assign Driver
                </button>
            </div>
        </div>
    </div>

    {{-- ── Unassign Driver Modal ─────────────────────────────────────── --}}
    <div
        x-show="unassignDriverModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
        @@keydown.escape.window="unassignDriverModalOpen = false"
    >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="unassignDriverModalOpen = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" @@click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Unassign Driver</h3>
                </div>
                <button type="button" @@click="unassignDriverModalOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-xs text-slate-500">Remove the currently assigned driver from this manifest. The manifest will revert to Draft status.</p>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Reason <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea
                        x-model="unassignReason"
                        rows="3"
                        placeholder="Enter reason for unassigning..."
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors resize-none"
                    ></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" @@click="unassignDriverModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button
                    type="button"
                    @@click="submitUnassignDriver()"
                    :disabled="actionLoading"
                    class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors"
                >
                    Unassign Driver
                </button>
            </div>
        </div>
    </div>

    {{-- ── Dispatch Modal ────────────────────────────────────────────── --}}
    <div
        x-show="dispatchModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
        @@keydown.escape.window="dispatchModalOpen = false"
    >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="dispatchModalOpen = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" @@click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Dispatch Manifest</h3>
                </div>
                <button type="button" @@click="dispatchModalOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div class="flex items-start gap-3 p-3 bg-amber-50 rounded-xl border border-amber-100 mb-4">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-xs text-amber-700">Dispatching will set manifest status to <strong>Loading</strong>, mark all items as <strong>In Transit</strong>, and record the dispatch timestamp.</p>
                </div>
                <p class="text-xs text-slate-600">Are you sure you want to dispatch manifest <strong>{{ $manifest->manifest_number }}</strong>?</p>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" @@click="dispatchModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button
                    type="button"
                    @@click="submitDispatch()"
                    :disabled="actionLoading"
                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors"
                >
                    Yes, Dispatch
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminTransportManifestShow', () => {
        const el = document.querySelector('[data-manifest-config]');
        const config = (() => {
            try { return JSON.parse(el?.getAttribute('data-manifest-config') || '{}'); }
            catch { return {}; }
        })();

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        return {
            // Modal state
            assignDriverModalOpen: false,
            unassignDriverModalOpen: false,
            dispatchModalOpen: false,
            createContainerModalOpen: false,
            actionLoading: false,

            // Form values
            selectedDriverId: '',
            unassignReason: '',
            containerForm: {
                container_type: 'box',
                notes: '',
            },

            openAssignDriverModal() {
                this.selectedDriverId = '';
                this.assignDriverModalOpen = true;
            },

            openUnassignDriverModal() {
                this.unassignReason = '';
                this.unassignDriverModalOpen = true;
            },

            openDispatchModal() {
                this.dispatchModalOpen = true;
            },

            openCreateContainerModal() {
                this.containerForm = { container_type: 'box', notes: '' };
                this.createContainerModalOpen = true;
            },

            async submitAssignDriver() {
                if (!this.selectedDriverId) {
                    window.showToast?.('Please select a driver.', 'warning');
                    return;
                }
                await this._postAction(config.assign_driver_endpoint, { driver_id: Number(this.selectedDriverId) }, () => {
                    this.assignDriverModalOpen = false;
                });
            },

            async submitUnassignDriver() {
                await this._postAction(config.unassign_driver_endpoint, { reason: this.unassignReason || null }, () => {
                    this.unassignDriverModalOpen = false;
                });
            },

            async submitDispatch() {
                await this._postAction(config.dispatch_endpoint, {}, () => {
                    this.dispatchModalOpen = false;
                });
            },

            async markItemLoaded(itemId) {
                const endpoint = (config.mark_item_loaded_endpoint_template || '').replace('__ITEM__', itemId);
                if (!endpoint) {
                    window.showToast?.('Missing item load endpoint.', 'error');
                    return;
                }
                await this._postAction(endpoint, {}, null);
            },

            async markAllLoaded() {
                if (!window.confirm('Mark every item on this manifest as loaded?')) {
                    return;
                }
                await this._postAction(config.mark_all_loaded_endpoint, {}, null);
            },

            async submitCreateContainer() {
                await this._postAction(config.create_container_endpoint, this.containerForm, () => {
                    this.createContainerModalOpen = false;
                });
            },

            async markContainerLoaded(containerId) {
                if (!window.confirm('Mark this whole container as loaded?')) {
                    return;
                }
                const endpoint = (config.mark_container_loaded_endpoint_template || '').replace('__CONTAINER__', containerId);
                await this._postAction(endpoint, {}, null);
            },

            async moveItemToContainer(itemId, containerId) {
                const endpoint = (config.move_item_container_endpoint_template || '').replace('__ITEM__', itemId);
                await this._postAction(endpoint, { container_id: Number(containerId) }, null);
            },

            async deleteContainer(containerId, itemCount, containerCount) {
                if (Number(containerCount) <= 1) {
                    window.showToast?.('At least one container must remain on the manifest.', 'warning');
                    return;
                }
                if (Number(itemCount) > 0) {
                    window.showToast?.('Move all items to another container before deleting this one.', 'warning');
                    return;
                }
                if (!window.confirm('Delete this empty transport container?')) {
                    return;
                }
                const endpoint = (config.delete_container_endpoint_template || '').replace('__CONTAINER__', containerId);
                await this._deleteAction(endpoint);
            },

            async printContainerLabel(containerId) {
                const endpoint = (config.print_container_label_endpoint_template || '').replace('__CONTAINER__', containerId);
                if (!endpoint) {
                    window.showToast?.('Missing print endpoint.', 'error');
                    return;
                }
                await this._printLabel(endpoint);
            },

            async _printLabel(endpoint) {
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to generate label.');
                    }

                    const printWindow = window.open('', '_blank');
                    if (!printWindow) {
                        throw new Error('Pop-up blocked. Allow pop-ups to print the container label.');
                    }
                    printWindow.document.open();
                    printWindow.document.write(result.data?.label_html || '');
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(() => printWindow.print(), 300);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'Unable to print label.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async _deleteAction(endpoint) {
                if (!endpoint) {
                    window.showToast?.('Missing action endpoint.', 'error');
                    return;
                }
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Action failed.');
                    }
                    window.showToast?.(result.message || 'Done.', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'An error occurred.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async _postAction(endpoint, body, onSuccess) {
                if (!endpoint) {
                    window.showToast?.('Missing action endpoint.', 'error');
                    return;
                }
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify(body),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Action failed.');
                    }
                    window.showToast?.(result.message || 'Done.', 'success');
                    onSuccess?.();
                    // Reload the page so updated status/driver is reflected
                    setTimeout(() => window.location.reload(), 800);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'An error occurred.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
        };
    });
});
</script>
@endpush
