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
        'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
        'loading' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'in_transit' => 'bg-violet-50 text-violet-700 border-violet-200',
        'arrived' => 'bg-amber-50 text-amber-700 border-amber-200',
        'received' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $statusDotClass = match ($manifest->status) {
        'draft' => 'bg-slate-400',
        'assigned' => 'bg-blue-500',
        'loading' => 'bg-indigo-500',
        'in_transit' => 'bg-violet-500',
        'arrived' => 'bg-amber-500',
        'received' => 'bg-emerald-500',
        'cancelled' => 'bg-rose-500',
        default => 'bg-slate-400',
    };

    $timeline = [
        ['label' => 'Created', 'value' => $manifest->created_at, 'dot' => 'bg-slate-500'],
        ['label' => 'Driver Assigned', 'value' => $manifest->assigned_at, 'dot' => 'bg-blue-500'],
        ['label' => 'Dispatched', 'value' => $manifest->dispatched_at, 'dot' => 'bg-violet-500'],
        ['label' => 'Arrived Destination', 'value' => $manifest->arrived_at, 'dot' => 'bg-amber-500'],
        ['label' => 'Received', 'value' => $manifest->received_at, 'dot' => 'bg-emerald-500'],
    ];
@endphp

@section('content')
<div class="space-y-6">
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

                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-300">
                                @if($manifest->assignedDriver)
                                    <div class="inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>{{ $manifest->assignedDriver->name }}</span>
                                    </div>
                                    <div class="inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span>{{ $manifest->assignedDriver->phone ?? '-' }}</span>
                                    </div>
                                    @if($manifest->assignedDriver->vehicle_type || $manifest->assignedDriver->vehicle_number)
                                        <div class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0014 6h-1"/>
                                            </svg>
                                            <span>{{ $manifest->assignedDriver->vehicle_type ?? '' }} {{ $manifest->assignedDriver->vehicle_number ?? '' }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400">No driver assigned</span>
                                @endif
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
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Expected Qty</p>
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
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Loaded Qty</p>
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
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Received Qty</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ activeTab: 'items' }">
        <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 border-b border-slate-200 px-4">
            <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
                <button
                    @click="activeTab = 'items'"
                    :class="activeTab === 'items'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Manifest Items
                </button>
                <button
                    @click="activeTab = 'details'"
                    :class="activeTab === 'details'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Details
                </button>
                <button
                    @click="activeTab = 'timeline'"
                    :class="activeTab === 'timeline'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Timeline
                </button>
            </nav>
        </div>

        <div class="p-6">
            <div x-show="activeTab === 'items'">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Manifest Line Items</h2>
                        <p class="text-xs text-slate-500 mt-1">All shipment items included in this transport manifest.</p>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200/50">
                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment Item</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Expected</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Loaded</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Received</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Line Status</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Timestamps</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            @forelse($items as $line)
                                @php
                                    $shipmentItem = $line->shipmentItem;
                                    $shipment = $shipmentItem?->shipment;
                                    $lineStatusClass = match ($line->line_status) {
                                        'loaded' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
                                        'received' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                        'short' => 'border-amber-200 bg-amber-50 text-amber-700',
                                        'excess' => 'border-sky-200 bg-sky-50 text-sky-700',
                                        'damaged' => 'border-rose-200 bg-rose-50 text-rose-700',
                                        default => 'border-slate-200 bg-slate-50 text-slate-700',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/70 align-top">
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[220px]">
                                        <p class="font-semibold text-slate-900">{{ $shipmentItem?->description ?? '-' }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">ID: {{ $line->shipment_item_id }}</p>
                                        @if($shipmentItem?->tracking_code)
                                            <p class="text-[11px] text-slate-500">Tracking: {{ $shipmentItem->tracking_code }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[160px]">
                                        <p class="font-semibold text-slate-900">{{ $shipment?->shipment_number ?? '-' }}</p>
                                        @if($shipment?->vendor?->name)
                                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $shipment->vendor->name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800">{{ $line->expected_quantity }}</td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800">{{ $line->loaded_quantity }}</td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800">{{ $line->received_quantity }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $lineStatusClass }}">
                                            {{ str($line->line_status ?? 'pending')->replace('_', ' ')->title() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-600 min-w-[180px]">
                                        {{ $line->notes ?: '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-500 min-w-[160px]">
                                        @if($line->loaded_at)
                                            <p>Loaded: {{ $line->loaded_at->format('M d, H:i') }}</p>
                                        @endif
                                        @if($line->received_at)
                                            <p>Received: {{ $line->received_at->format('M d, H:i') }}</p>
                                        @endif
                                        @if(!$line->loaded_at && !$line->received_at)
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-xs text-slate-500">No items in this manifest.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="activeTab === 'details'" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">
                            <h3 class="text-sm font-semibold text-slate-900">Transport Details</h3>
                        </div>
                        <div class="px-4 py-4 text-xs space-y-2">
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Manifest Number</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $manifest->manifest_number }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Status</span>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                                    {{ str($manifest->status)->replace('_', ' ')->title() }}
                                </span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Origin Warehouse</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $manifest->originWarehouse?->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Destination Warehouse</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $manifest->destinationWarehouse?->name ?? '-' }}</span>
                            </div>
                            @if($manifest->sortBatch)
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">Sort Batch</span>
                                    <span class="text-slate-900 font-semibold text-right">{{ $manifest->sortBatch->batch_number }}</span>
                                </div>
                            @endif
                            @if($manifest->createdBy)
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">Created By</span>
                                    <span class="text-slate-900 font-semibold text-right">{{ $manifest->createdBy->name ?? '-' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">
                            <h3 class="text-sm font-semibold text-slate-900">Driver Details</h3>
                        </div>
                        <div class="px-4 py-4 text-xs space-y-2">
                            @if($manifest->assignedDriver)
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">Name</span>
                                    <span class="text-slate-900 font-semibold text-right">{{ $manifest->assignedDriver->name }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">Phone</span>
                                    <span class="text-slate-900 font-semibold text-right">{{ $manifest->assignedDriver->phone ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">Vehicle Type</span>
                                    <span class="text-slate-900 font-semibold text-right">{{ $manifest->assignedDriver->vehicle_type ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">Vehicle Number</span>
                                    <span class="text-slate-900 font-semibold text-right">{{ $manifest->assignedDriver->vehicle_number ?? '-' }}</span>
                                </div>
                            @else
                                <p class="text-slate-500 py-2">No driver assigned to this manifest.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Item Summary</p>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Total Items</span>
                                <span class="font-semibold text-slate-900">{{ $items->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Expected Quantity</span>
                                <span class="font-semibold text-slate-900">{{ $totalExpected }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Loaded Quantity</span>
                                <span class="font-semibold text-slate-900">{{ $totalLoaded }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Received Quantity</span>
                                <span class="font-semibold text-slate-900">{{ $totalReceived }}</span>
                            </div>
                        </div>
                    </div>

                    @if($manifest->notes || $manifest->cancellation_reason)
                        <div class="space-y-4">
                            @if($manifest->notes)
                                <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Notes</p>
                                    <p class="text-xs text-slate-700">{{ $manifest->notes }}</p>
                                </div>
                            @endif
                            @if($manifest->cancellation_reason)
                                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                                    <p class="text-[11px] uppercase tracking-wider text-rose-600 font-semibold mb-2">Cancellation Reason</p>
                                    <p class="text-xs text-rose-700">{{ $manifest->cancellation_reason }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div x-show="activeTab === 'timeline'" x-cloak>
                <div class="max-w-3xl">
                    <div class="relative pl-8 space-y-5">
                        <div class="absolute left-[11px] top-3 bottom-3 w-0.5 bg-slate-200"></div>
                        @foreach($timeline as $event)
                            <div class="relative flex items-start gap-4">
                                <div class="absolute left-[-21px] w-4 h-4 rounded-full border-2 border-white shadow-sm {{ $event['value'] ? $event['dot'] : 'bg-slate-300' }}"></div>
                                <div class="flex-1 bg-white rounded-xl border border-slate-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold text-slate-800">{{ $event['label'] }}</p>
                                        <p class="text-[11px] text-slate-500">
                                            {{ $event['value'] ? $event['value']->format('Y-m-d H:i:s') : 'Pending' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($manifest->status === 'cancelled')
                            <div class="relative flex items-start gap-4">
                                <div class="absolute left-[-21px] w-4 h-4 rounded-full border-2 border-white shadow-sm bg-rose-500"></div>
                                <div class="flex-1 bg-rose-50 rounded-xl border border-rose-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold text-rose-700">Cancelled</p>
                                    </div>
                                    @if($manifest->cancellation_reason)
                                        <p class="text-xs text-rose-700 mt-2">{{ $manifest->cancellation_reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
