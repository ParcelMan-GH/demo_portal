@extends('admin.layouts.app')

@section('title', $run->run_number)
@section('breadcrumb-parent', 'Delivery Runs')
@section('breadcrumb-current', $run->run_number)

@php
use App\Models\Driver;
$deliveryDrivers = Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);

$canAssignDriver = in_array($run->status, ['draft', 'assigned']);
$canDispatch     = $run->status === 'assigned' && $run->assignedDriver !== null;
$isDispatched    = in_array($run->status, ['out_for_delivery', 'partially_delivered', 'completed']);

$statusColors = match($run->status) {
    'draft'               => 'bg-slate-500/20 text-slate-300',
    'assigned'            => 'bg-blue-500/20 text-blue-300',
    'out_for_delivery'    => 'bg-amber-500/20 text-amber-300',
    'partially_delivered' => 'bg-orange-500/20 text-orange-300',
    'completed'           => 'bg-emerald-500/20 text-emerald-300',
    'cancelled'           => 'bg-red-500/20 text-red-300',
    default               => 'bg-slate-500/20 text-slate-300',
};
$dotColors = match($run->status) {
    'draft'               => 'bg-slate-400',
    'assigned'            => 'bg-blue-400',
    'out_for_delivery'    => 'bg-amber-400',
    'partially_delivered' => 'bg-orange-400',
    'completed'           => 'bg-emerald-400',
    'cancelled'           => 'bg-red-400',
    default               => 'bg-slate-400',
};
$stopStatusColors = [
    'pending'   => ['badge' => 'bg-slate-100 text-slate-600',   'dot' => 'bg-slate-400'],
    'arrived'   => ['badge' => 'bg-blue-100 text-blue-700',     'dot' => 'bg-blue-400'],
    'delivered' => ['badge' => 'bg-emerald-100 text-emerald-700','dot' => 'bg-emerald-400'],
    'failed'    => ['badge' => 'bg-red-100 text-red-700',       'dot' => 'bg-red-400'],
];
$itemStatusColors = [
    'pending'   => 'bg-slate-100 text-slate-600',
    'delivered' => 'bg-emerald-100 text-emerald-700',
    'failed'    => 'bg-red-100 text-red-700',
    'partial'   => 'bg-orange-100 text-orange-700',
];
@endphp

@section('content')
<div class="space-y-6" x-data="adminDeliveryRunPage">

    <!-- Hero / Header Card -->
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
                    <a href="{{ route('admin.delivery-runs.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Delivery Runs</span>
                    </a>
                </div>

                <!-- Main Content Row -->
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <!-- Icon + Title -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-indigo-500/30 ring-4 ring-white/10">
                                <svg class="w-10 h-10 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $run->run_number }}</h1>
                                <p class="text-slate-400 text-sm mt-0.5">
                                    {{ $run->warehouse?->name ?? 'No Warehouse' }}
                                    @if($run->warehouse?->code)
                                        &mdash; {{ $run->warehouse->code }}
                                    @endif
                                </p>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusColors }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColors }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="flex flex-wrap gap-4 lg:ml-auto">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 border border-white/10 min-w-[100px]">
                            <div class="text-2xl font-bold text-white">{{ $run->stops->count() }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Stops</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 border border-white/10 min-w-[100px]">
                            <div class="text-2xl font-bold text-white">{{ $run->items->count() }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Items</div>
                        </div>
                        @php
                            $deliveredStops = $run->stops->where('status', 'delivered')->count();
                        @endphp
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 border border-white/10 min-w-[100px]">
                            <div class="text-2xl font-bold text-white">{{ $deliveredStops }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Delivered</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    @if($canAssignDriver || $canDispatch)
    <div class="flex flex-wrap items-center gap-3 px-1">
        @if($canAssignDriver)
        <button @@click="showAssignModal = true"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-500 to-violet-600 hover:from-purple-600 hover:to-violet-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-purple-500/25 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ $run->assignedDriver ? 'Reassign Driver' : 'Assign Driver' }}
        </button>
        @endif

        @if($canDispatch)
        <button @@click="confirmDispatch()"
            :disabled="actionLoading"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
            <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg x-show="!actionLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
            <span x-text="actionLoading ? 'Dispatching...' : 'Dispatch Run'"></span>
        </button>
        @endif
    </div>
    @endif

    <!-- Info Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <!-- Run Details Card -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">Run Details</h3>
                </div>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-slate-500 flex-shrink-0">Run Number</span>
                    <span class="text-xs font-semibold text-slate-800 text-right">{{ $run->run_number }}</span>
                </div>
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-slate-500 flex-shrink-0">Status</span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                        @if($run->status === 'draft') bg-slate-100 text-slate-700
                        @elseif($run->status === 'assigned') bg-blue-100 text-blue-700
                        @elseif($run->status === 'out_for_delivery') bg-amber-100 text-amber-700
                        @elseif($run->status === 'partially_delivered') bg-orange-100 text-orange-700
                        @elseif($run->status === 'completed') bg-emerald-100 text-emerald-700
                        @elseif($run->status === 'cancelled') bg-red-100 text-red-700
                        @else bg-slate-100 text-slate-700
                        @endif
                    ">{{ $statusLabel }}</span>
                </div>
                @if($run->sortBatch)
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-slate-500 flex-shrink-0">Sort Batch</span>
                    <span class="text-xs font-semibold text-slate-800 text-right">{{ $run->sortBatch->batch_number }}</span>
                </div>
                @endif
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-slate-500 flex-shrink-0">Created By</span>
                    <span class="text-xs font-semibold text-slate-800 text-right">{{ $run->createdBy?->name ?? '—' }}</span>
                </div>
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-slate-500 flex-shrink-0">Created At</span>
                    <span class="text-xs text-slate-700 text-right">{{ $run->created_at->format('d M Y, H:i') }}</span>
                </div>
                @if($run->notes)
                <div class="pt-1 border-t border-slate-100">
                    <span class="text-xs text-slate-500">Notes</span>
                    <p class="mt-1 text-xs text-slate-700 leading-relaxed">{{ $run->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Driver Card -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">Assigned Driver</h3>
                </div>
            </div>
            <div class="px-5 py-4 space-y-3">
                @if($run->assignedDriver)
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ strtoupper(substr($run->assignedDriver->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-800 truncate">{{ $run->assignedDriver->name }}</div>
                            @if($run->assignedDriver->phone)
                                <div class="text-xs text-slate-500">{{ $run->assignedDriver->phone }}</div>
                            @endif
                        </div>
                    </div>
                    @if($run->assignedDriver->vehicle_type || $run->assignedDriver->vehicle_number)
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs text-slate-500 flex-shrink-0">Vehicle</span>
                            <span class="text-xs font-semibold text-slate-800 text-right">
                                {{ $run->assignedDriver->vehicle_type }}
                                @if($run->assignedDriver->vehicle_number)
                                    &mdash; {{ $run->assignedDriver->vehicle_number }}
                                @endif
                            </span>
                        </div>
                    @endif
                    @if($run->assigned_at)
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs text-slate-500 flex-shrink-0">Assigned At</span>
                            <span class="text-xs text-slate-700 text-right">{{ $run->assigned_at->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center gap-2 py-4 text-center">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p class="text-xs text-slate-400">No driver assigned</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Timeline Card -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">Timeline</h3>
                </div>
            </div>
            <div class="px-5 py-4">
                <ol class="relative border-l border-slate-200 ml-3 space-y-4">
                    <li class="pl-5">
                        <span class="absolute -left-1.5 flex items-center justify-center w-3 h-3 rounded-full bg-slate-300 ring-2 ring-white"></span>
                        <p class="text-xs font-semibold text-slate-700">Created</p>
                        <p class="text-[10px] text-slate-500">{{ $run->created_at->format('d M Y, H:i') }}</p>
                    </li>
                    @if($run->assigned_at)
                    <li class="pl-5">
                        <span class="absolute -left-1.5 flex items-center justify-center w-3 h-3 rounded-full bg-blue-400 ring-2 ring-white"></span>
                        <p class="text-xs font-semibold text-slate-700">Assigned</p>
                        <p class="text-[10px] text-slate-500">{{ $run->assigned_at->format('d M Y, H:i') }}</p>
                    </li>
                    @endif
                    @if($run->dispatched_at)
                    <li class="pl-5">
                        <span class="absolute -left-1.5 flex items-center justify-center w-3 h-3 rounded-full bg-amber-400 ring-2 ring-white"></span>
                        <p class="text-xs font-semibold text-slate-700">Dispatched</p>
                        <p class="text-[10px] text-slate-500">{{ $run->dispatched_at->format('d M Y, H:i') }}</p>
                    </li>
                    @endif
                    @if($run->completed_at)
                    <li class="pl-5">
                        <span class="absolute -left-1.5 flex items-center justify-center w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-white"></span>
                        <p class="text-xs font-semibold text-slate-700">Completed</p>
                        <p class="text-[10px] text-slate-500">{{ $run->completed_at->format('d M Y, H:i') }}</p>
                    </li>
                    @endif
                </ol>
            </div>
        </div>
    </div>

    <!-- Stops Section -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Section Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Delivery Stops</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $run->stops->count() }} stop(s) in this run</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-violet-50 text-violet-700">
                    {{ $run->stops->count() }} stops
                </span>
            </div>
        </div>

        @if($run->stops->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm text-slate-400">No stops have been added to this run yet.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100/70">
                @foreach($run->stops->sortBy('id') as $index => $stop)
                @php
                    $stopBadgeClass = $stopStatusColors[$stop->status]['badge'] ?? 'bg-slate-100 text-slate-600';
                    $stopDotClass   = $stopStatusColors[$stop->status]['dot']   ?? 'bg-slate-400';
                    $stopStatusLabel = match($stop->status) {
                        'pending'   => 'Pending',
                        'arrived'   => 'Arrived',
                        'delivered' => 'Delivered',
                        'failed'    => 'Failed',
                        default     => ucwords(str_replace('_', ' ', $stop->status)),
                    };
                @endphp
                <div class="px-6 py-5">
                    <!-- Stop Header -->
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                        <div class="flex items-start gap-3">
                            <!-- Stop Number Bubble -->
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 flex-shrink-0 mt-0.5">
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-sm font-semibold text-slate-800">{{ $stop->recipient_name }}</span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $stopBadgeClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $stopDotClass }}"></span>
                                        {{ $stopStatusLabel }}
                                    </span>
                                    {{-- Delivery Method Badge + Toggle --}}
                                    <div x-data="{ method: '{{ $stop->delivery_method ?? 'direct' }}', changing: false }" class="inline-flex">
                                        <button @@click="
                                            if (changing) return;
                                            const newMethod = method === 'direct' ? 'bus_handoff' : 'direct';
                                            changing = true;
                                            fetch('{{ route('admin.delivery-runs.stops.update-delivery-method', ['run' => $run->id, 'stop' => $stop->id]) }}', {
                                                method: 'PATCH',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                                body: JSON.stringify({ delivery_method: newMethod })
                                            }).then(r => r.json()).then(j => {
                                                if (j.success) { method = newMethod; window.showToast?.(j.message, 'success'); }
                                                else window.showToast?.(j.message || 'Failed', 'error');
                                            }).catch(() => window.showToast?.('Error', 'error')).finally(() => changing = false);
                                        "
                                        :class="method === 'bus_handoff' ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-slate-50 text-slate-500 ring-slate-200'"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 cursor-pointer hover:opacity-80 transition-all"
                                        :title="method === 'bus_handoff' ? 'Click to change to Direct Delivery' : 'Click to change to Bus Handoff'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            <span x-text="method === 'bus_handoff' ? 'Bus Handoff' : 'Direct'"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    @if($stop->recipient_phone)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            {{ $stop->recipient_phone }}
                                        </span>
                                    @endif
                                    @if($stop->town)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ $stop->town }}
                                        </span>
                                    @endif
                                    @if($stop->landmark)
                                        <span class="inline-flex items-center gap-1 text-slate-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21l1.9-5.7a8.5 8.5 0 113.8 3.8z"/>
                                            </svg>
                                            {{ $stop->landmark }}
                                        </span>
                                    @endif
                                    @if($stop->handoff_courier_phone)
                                        <span class="inline-flex items-center gap-1 text-violet-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <a href="tel:{{ $stop->handoff_courier_phone }}" class="hover:underline">{{ $stop->handoff_courier_phone }}</a>
                                        </span>
                                    @endif
                                    @if($stop->handoff_vehicle_number)
                                        <span class="inline-flex items-center gap-1 text-violet-600 font-mono text-[10px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            {{ $stop->handoff_vehicle_number }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Stop Meta (timestamps + OTP attempts) -->
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 sm:flex-shrink-0">
                            @if($stop->arrived_at)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Arrived {{ $stop->arrived_at->format('H:i') }}
                                </span>
                            @endif
                            @if($stop->delivered_at)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ ($stop->delivery_method ?? 'direct') === 'bus_handoff' ? 'Handed Off' : 'Delivered' }} {{ $stop->delivered_at->format('H:i') }}
                                </span>
                            @endif
                            @if($stop->verification_attempts > 0)
                                <span class="inline-flex items-center gap-1 text-amber-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    {{ $stop->verification_attempts }} OTP attempt(s)
                                </span>
                            @endif
                            @if($stop->failure_reason)
                                <span class="inline-flex items-center gap-1 text-red-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $stop->failure_reason }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Items in this Stop -->
                    @if($stop->items->isNotEmpty())
                        <div class="ml-11">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Items ({{ $stop->items->count() }})</p>
                            <div class="rounded-xl border border-slate-100 divide-y divide-slate-50 overflow-hidden">
                                @foreach($stop->items as $item)
                                @php
                                    $itemBadgeClass = $itemStatusColors[$item->status] ?? 'bg-slate-100 text-slate-600';
                                    $itemStatusLabel = match($item->status) {
                                        'pending'   => 'Pending',
                                        'delivered' => 'Delivered',
                                        'failed'    => 'Failed',
                                        'partial'   => 'Partial',
                                        default     => ucwords(str_replace('_', ' ', $item->status)),
                                    };
                                @endphp
                                <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/50">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div class="w-6 h-6 rounded-lg bg-slate-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-slate-800 truncate">
                                                {{ $item->shipmentItem?->description ?? 'Item #' . $item->id }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                                @if($item->shipmentItem?->tracking_code)
                                                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-500 font-mono">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                        </svg>
                                                        {{ $item->shipmentItem->tracking_code }}
                                                    </span>
                                                @endif
                                                @if($item->shipmentItem?->shipment)
                                                    <span class="text-[10px] text-slate-400">
                                                        Shipment: {{ $item->shipmentItem->shipment->shipment_number }}
                                                    </span>
                                                @endif
                                            </div>
                                            @php
                                                $recipName = $item->shipmentItem?->delivery_recipient_name ?? $item->shipmentItem?->shipment?->delivery_recipient_name;
                                                $recipPhone = $item->shipmentItem?->delivery_recipient_phone ?? $item->shipmentItem?->shipment?->delivery_recipient_phone;
                                                $recipTown = $item->shipmentItem?->delivery_town ?? $item->shipmentItem?->shipment?->delivery_town;
                                            @endphp
                                            @if($recipName || $recipPhone || $recipTown)
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1">
                                                @if($recipName)
                                                    <span class="text-[10px] text-slate-600 font-medium">
                                                        <svg class="w-3 h-3 inline text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                        {{ $recipName }}
                                                    </span>
                                                @endif
                                                @if($recipPhone)
                                                    <a href="tel:{{ $recipPhone }}" class="text-[10px] text-blue-600 hover:underline font-medium">
                                                        <svg class="w-3 h-3 inline text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                        {{ $recipPhone }}
                                                    </a>
                                                @endif
                                                @if($recipTown)
                                                    <span class="text-[10px] text-slate-500">
                                                        <svg class="w-3 h-3 inline text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                        {{ $recipTown }}
                                                    </span>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 sm:flex-shrink-0">
                                        <!-- Quantity -->
                                        <span class="text-[10px] text-slate-500">
                                            <span class="font-semibold text-slate-700">{{ $item->delivered_quantity ?? 0 }}</span>
                                            /
                                            {{ $item->expected_quantity }} qty
                                        </span>
                                        <!-- Item Status Badge -->
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $itemBadgeClass }}">
                                            {{ $itemStatusLabel }}
                                        </span>
                                        @if($item->notes)
                                            <span class="text-[10px] text-slate-400 italic max-w-[160px] truncate" title="{{ $item->notes }}">
                                                {{ $item->notes }}
                                            </span>
                                        @endif
                                        {{-- Per-item confirm/fail for bus handoff --}}
                                        @if(($stop->delivery_method ?? 'direct') === 'bus_handoff' && $item->status === 'handed_off')
                                            <div x-data="{ action: null, notes: '', submitting: false }" class="flex items-center gap-1">
                                                <template x-if="!action">
                                                    <div class="flex gap-1">
                                                        <button @@click="action = 'delivered'" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-semibold rounded-md transition-colors">Delivered</button>
                                                        <button @@click="action = 'failed'" class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-semibold rounded-md transition-colors">Failed</button>
                                                    </div>
                                                </template>
                                                <template x-if="action">
                                                    <div class="flex items-center gap-1">
                                                        <input type="text" x-model="notes" placeholder="Notes..." class="px-2 py-1 text-[10px] border border-slate-200 rounded-md w-32 focus:ring-1 focus:ring-blue-400 outline-none">
                                                        <button @@click="
                                                            submitting = true;
                                                            fetch('{{ route('admin.delivery-runs.stops.items.confirm-handoff', ['run' => $run->id, 'stop' => $stop->id, 'item' => $item->id]) }}', {
                                                                method: 'POST',
                                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                                                body: JSON.stringify({ action: action, notes: notes })
                                                            }).then(r => r.json()).then(j => {
                                                                if (j.success) { window.showToast?.(j.message, 'success'); setTimeout(() => window.location.reload(), 500); }
                                                                else { window.showToast?.(j.message || 'Failed', 'error'); submitting = false; }
                                                            }).catch(() => { window.showToast?.('Error', 'error'); submitting = false; });
                                                        " :disabled="submitting"
                                                        :class="action === 'delivered' ? 'bg-emerald-600' : 'bg-rose-600'"
                                                        class="px-2 py-1 text-white text-[10px] font-semibold rounded-md disabled:opacity-50">
                                                            <span x-show="!submitting">Save</span>
                                                            <span x-show="submitting">...</span>
                                                        </button>
                                                        <button @@click="action = null" class="text-[10px] text-slate-400 hover:text-slate-600">Cancel</button>
                                                    </div>
                                                </template>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="ml-11">
                            <p class="text-xs text-slate-400 italic">No items at this stop.</p>
                        </div>
                    @endif


                    {{-- Per-item confirmation info for bus handoff --}}
                    @if(($stop->delivery_method ?? 'direct') === 'bus_handoff' && $stop->status === 'handed_off')
                        <div class="ml-11 mt-2 px-3 py-2 bg-amber-50 rounded-lg border border-amber-200">
                            <p class="text-[10px] font-semibold text-amber-700">Call each recipient to confirm delivery, then use the Delivered/Failed buttons per item above.</p>
                        </div>
                    @endif

                    <!-- OTP Verification Attempts (if any) — hide for bus handoff -->
                    @if(($stop->delivery_method ?? 'direct') !== 'bus_handoff' && $stop->verificationAttempts->isNotEmpty())
                        <div class="ml-11 mt-3">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">OTP Verification Attempts ({{ $stop->verificationAttempts->count() }})</p>
                            <div class="rounded-xl border border-amber-100 divide-y divide-amber-50 overflow-hidden">
                                @foreach($stop->verificationAttempts as $attempt)
                                <div class="px-4 py-2.5 flex items-center justify-between gap-2 bg-amber-50/40">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                        <span class="text-xs text-slate-700">
                                            Attempt #{{ $loop->iteration }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">
                                        {{ $attempt->created_at->format('d M Y, H:i:s') }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- OTP Code Display — hide for bus handoff --}}
                    @if(($stop->delivery_method ?? 'direct') !== 'bus_handoff' && $stop->verification_code_sent_at && $stop->status !== 'delivered')
                        @php
                            $otpCode = \App\Models\OtpCode::where('phone', (string) $stop->recipient_phone)
                                ->where('purpose', 'delivery_verification')
                                ->whereNull('verified_at')
                                ->where('expires_at', '>', now())
                                ->latest('created_at')
                                ->value('code');
                        @endphp
                        @if($otpCode)
                        <div class="flex items-center gap-3 mt-3 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-xl">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            <div>
                                <span class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wider">OTP Code</span>
                                <span class="ml-2 text-lg font-black text-indigo-700 tracking-[0.3em]">{{ $otpCode }}</span>
                            </div>
                            <span class="text-[10px] text-indigo-400 ml-auto">Sent {{ $stop->verification_code_sent_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    @endif

                    <!-- Resend OTP Button — hide for bus handoff -->
                    @if(($stop->delivery_method ?? 'direct') !== 'bus_handoff' && $isDispatched && in_array($stop->status, ['pending', 'arrived']))
                    <div class="flex justify-end mt-3">
                        <button @@click="resendCode({{ $stop->id }})"
                            :disabled="actionLoading"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="actionLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!actionLoading" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Resend OTP
                        </button>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Assign Driver Modal -->
    <div x-show="showAssignModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @@keydown.escape.window="showAssignModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @@click="showAssignModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" @@click.stop>
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ $run->assignedDriver ? 'Reassign Driver' : 'Assign Driver' }}</h3>
                        <p class="text-xs text-slate-500">Select a delivery driver for this run</p>
                    </div>
                </div>
                <button @@click="showAssignModal = false"
                    class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="px-6 py-5">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Select Driver <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select x-model="selectedDriverId"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                            <option value="">Choose a driver...</option>
                            @foreach($deliveryDrivers as $driver)
                                <option value="{{ $driver->id }}">
                                    {{ $driver->name }}
                                    @if($driver->phone) &mdash; {{ $driver->phone }}@endif
                                    @if($driver->vehicle_type) ({{ $driver->vehicle_type }}@if($driver->vehicle_number) {{ $driver->vehicle_number }}@endif)@endif
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <button @@click="showAssignModal = false"
                    class="px-4 py-2 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button @@click="assignDriver()"
                    :disabled="actionLoading || !selectedDriverId"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-purple-500 to-violet-600 hover:from-purple-600 hover:to-violet-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-purple-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="actionLoading ? 'Assigning...' : '{{ $run->assignedDriver ? "Reassign Driver" : "Assign Driver" }}'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Dispatch Confirmation Modal -->
    <div x-show="showDispatchConfirm" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @@keydown.escape.window="showDispatchConfirm = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @@click="showDispatchConfirm = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden" @@click.stop>
            <div class="px-6 py-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-200">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Dispatch Delivery Run?</h3>
                <p class="text-sm text-slate-500 mb-6">
                    This will dispatch <span class="font-semibold text-slate-700">{{ $run->run_number }}</span> and the assigned driver will be notified to begin deliveries.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button @@click="showDispatchConfirm = false"
                        class="px-5 py-2 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button @@click="dispatchRun()"
                        :disabled="actionLoading"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="actionLoading ? 'Dispatching...' : 'Yes, Dispatch'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminDeliveryRunPage', () => {
        const csrfToken = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const endpoints = {
            assignDriver: '{{ route('admin.delivery-runs.assign-driver', $run) }}',
            dispatch:     '{{ route('admin.delivery-runs.dispatch', $run) }}',
            resendCode:   '{{ route('admin.delivery-runs.stops.resend-code', ['run' => $run->id, 'stop' => '__STOP__']) }}',
        };

        return {
            actionLoading:       false,
            showAssignModal:     false,
            showDispatchConfirm: false,
            selectedDriverId:    '',

            confirmDispatch() {
                this.showDispatchConfirm = true;
            },

            async assignDriver() {
                if (!this.selectedDriverId) {
                    window.showToast?.('Please select a driver first.', 'warning');
                    return;
                }
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoints.assignDriver, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ driver_id: Number(this.selectedDriverId) }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to assign driver.');
                    }
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

            async dispatchRun() {
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoints.dispatch, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to dispatch run.');
                    }
                    window.showToast?.(result.message || 'Delivery run dispatched successfully.', 'success');
                    this.showDispatchConfirm = false;
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
                    const url = endpoints.resendCode.replace('__STOP__', stopId);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to resend OTP.');
                    }
                    window.showToast?.(result.message || 'OTP code resent successfully.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to resend OTP.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
        };
    });
});
</script>
@endpush
