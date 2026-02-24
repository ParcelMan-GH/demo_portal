@extends('warehouse.layouts.app')

@section('title', 'Pending Receipt Details')
@section('page-title', 'Pending Receipt Details')

@php
    $shipment = $assignment->shipment;
    $items = $shipment?->items ?? collect();
    $itemConfirmations = $assignment->itemConfirmations ?? collect();
    $confirmationsByItem = $itemConfirmations->keyBy('shipment_item_id');
    $assignmentPhotos = $assignment->photos ?? collect();
    $photosByItem = $assignmentPhotos
        ->filter(fn ($photo) => !empty($photo->shipment_item_id))
        ->groupBy('shipment_item_id');

    $statusValue = $assignment->status?->value ?? (string) $assignment->status;
    $statusClasses = match ($statusValue) {
        'assigned' => 'bg-blue-500/20 text-blue-300',
        'en_route' => 'bg-indigo-500/20 text-indigo-300',
        'arrived' => 'bg-amber-500/20 text-amber-300',
        'picking_up' => 'bg-violet-500/20 text-violet-300',
        'completed' => 'bg-emerald-500/20 text-emerald-300',
        'cancelled' => 'bg-rose-500/20 text-rose-300',
        default => 'bg-slate-500/20 text-slate-300',
    };
    $statusDotClasses = match ($statusValue) {
        'assigned' => 'bg-blue-400',
        'en_route' => 'bg-indigo-400',
        'arrived' => 'bg-amber-400',
        'picking_up' => 'bg-violet-400',
        'completed' => 'bg-emerald-400',
        'cancelled' => 'bg-rose-400',
        default => 'bg-slate-400',
    };

    $timeline = [
        ['label' => 'Assigned', 'value' => $assignment->assigned_at, 'dot' => 'bg-blue-500'],
        ['label' => 'En Route', 'value' => $assignment->en_route_at, 'dot' => 'bg-indigo-500'],
        ['label' => 'Arrived Pickup', 'value' => $assignment->arrived_at, 'dot' => 'bg-amber-500'],
        ['label' => 'Picked Up', 'value' => $assignment->picked_up_at, 'dot' => 'bg-violet-500'],
        ['label' => 'Arrived Warehouse', 'value' => $assignment->arrived_warehouse_at, 'dot' => 'bg-sky-500'],
        ['label' => 'Received', 'value' => $assignment->received_at, 'dot' => 'bg-teal-500'],
        ['label' => 'Completed', 'value' => $assignment->completed_at, 'dot' => 'bg-emerald-500'],
    ];

    $receiptStatusClasses = match ($receipt?->status) {
        'finalized' => 'bg-emerald-500/20 text-emerald-300',
        'discrepancy_open' => 'bg-amber-500/20 text-amber-300',
        default => 'bg-slate-500/20 text-slate-300',
    };
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseReceiptShowPage" data-warehouse-receipt-show-config="{{ e(json_encode($receiptConfig, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="pendingReceiptGrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#pendingReceiptGrid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <div class="mb-6">
                    <a href="{{ route('warehouse.receipts.pending.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Pending Receipts</span>
                    </a>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                                {{ strtoupper(substr($shipment?->shipment_number ?? 'S', 0, 1)) }}
                            </div>
                        </div>

                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $shipment?->shipment_number ?? '-' }}</h1>
                                <p class="text-slate-400 text-sm mt-0.5 truncate">
                                    {{ $shipment?->vendor?->name ?? 'Vendor' }}
                                    @if($shipment?->vendor?->business_name)
                                        - {{ $shipment->vendor->business_name }}
                                    @endif
                                </p>
                                <p class="text-slate-400 text-xs mt-1">Pickup assignment #{{ $assignment->id }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-300">
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>{{ $assignment->driver?->name ?? '-' }}</span>
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span>{{ $assignment->driver?->phone ?? '-' }}</span>
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ $assignment->targetWarehouse?->name ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusClasses }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClasses }}"></span>
                                    {{ $statusLabel }}
                                </span>
                                @if($receipt)
                                    @php
                                        $receiptLabel = match ($receipt->status ?? 'draft') {
                                            'discrepancy_open' => 'Discrepancy Open',
                                            'finalized' => 'Finalized',
                                            default => 'Draft',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $receiptStatusClasses }}">
                                        Receipt: {{ $receiptLabel }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ optional($assignment->assigned_at)?->format('M d, Y') ?? '-' }}
                                </span>
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
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($itemConfirmations->count()) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Confirmed Items</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h8m0 0l-3-3m3 3l-3 3M13 19H5a2 2 0 01-2-2V7a2 2 0 012-2h8"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($itemConfirmations->sum('confirmed_quantity')) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Confirmed Qty</p>
                            </div>
                        </div>

                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($assignmentPhotos->count()) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Driver Photos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 border-b border-slate-200 px-4">
            <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
                <button
                    @@click="activeTab = 'overview'"
                    :class="activeTab === 'overview'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Overview
                </button>
                <button
                    @@click="activeTab = 'items'"
                    :class="activeTab === 'items'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Shipment Items
                </button>
                <button
                    @@click="activeTab = 'timeline'"
                    :class="activeTab === 'timeline'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Timeline
                </button>
                <button
                    @@click="activeTab = 'receiving'"
                    :class="activeTab === 'receiving'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/70 border-transparent'"
                    class="inline-flex items-center gap-2 px-4 py-3 text-xs font-semibold border-t border-l border-r rounded-t-lg transition-all whitespace-nowrap"
                >
                    Receiving
                </button>
            </nav>
        </div>

        <div class="p-6">
            <div x-show="activeTab === 'overview'" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">
                            <h3 class="text-sm font-semibold text-slate-900">Driver Details</h3>
                        </div>
                        <div class="px-4 py-4 text-xs space-y-2">
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Name</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $assignment->driver?->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Phone</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $assignment->driver?->phone ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Target Warehouse</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $assignment->targetWarehouse?->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Warehouse Code</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $assignment->targetWarehouse?->code ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">
                            <h3 class="text-sm font-semibold text-slate-900">Pickup Details</h3>
                        </div>
                        <div class="px-4 py-4 text-xs space-y-2">
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Contact Name</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $shipment?->pickup_contact_name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Contact Phone</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $shipment?->pickup_contact_phone ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Region / District</span>
                                <span class="text-slate-900 font-semibold text-right">
                                    {{ $shipment?->pickupRegion?->name ?? '-' }}
                                    @if($shipment?->pickupDistrict?->name)
                                        / {{ $shipment->pickupDistrict->name }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Town</span>
                                <span class="text-slate-900 font-semibold text-right">{{ $shipment?->pickup_town ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Pickup Coordinates</p>
                        <p class="text-xs text-slate-700">
                            Lat: {{ $shipment?->pickup_latitude ?? '-' }} |
                            Lng: {{ $shipment?->pickup_longitude ?? '-' }}
                        </p>
                        <p class="mt-2 text-xs text-slate-700">Ghana Post: {{ $shipment?->pickup_gh_post_address ?? '-' }}</p>
                        <p class="mt-1 text-xs text-slate-700">Landmark: {{ $shipment?->pickup_landmark ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Shipment Destination Mode</p>
                        <p class="text-xs font-semibold text-slate-900">
                            {{ $shipment?->isPerItemDestination() ? 'Per-item destination' : 'Single destination' }}
                        </p>
                        <p class="mt-2 text-xs text-slate-700">
                            @if($shipment?->isPerItemDestination())
                                Recipients and destinations are set per shipment item.
                            @else
                                Recipient: {{ $shipment?->delivery_recipient_name ?? '-' }} ({{ $shipment?->delivery_recipient_phone ?? '-' }})
                            @endif
                        </p>
                    </div>
                </div>

                @if($assignment->notes || $assignment->receive_notes || $assignment->cancellation_reason)
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                        @if($assignment->notes)
                            <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Assignment Notes</p>
                                <p class="text-xs text-slate-700">{{ $assignment->notes }}</p>
                            </div>
                        @endif

                        @if($assignment->receive_notes)
                            <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Receive Notes</p>
                                <p class="text-xs text-slate-700">{{ $assignment->receive_notes }}</p>
                            </div>
                        @endif

                        @if($assignment->cancellation_reason)
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                                <p class="text-[11px] uppercase tracking-wider text-rose-600 font-semibold mb-2">Cancellation Reason</p>
                                <p class="text-xs text-rose-700">{{ $assignment->cancellation_reason }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div x-show="activeTab === 'items'" x-cloak>
                <div class="overflow-x-auto rounded-xl border border-slate-200/50">
                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Destination / Recipient</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Expected Qty</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Confirmed Qty</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Vendor Photos</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Driver Photos</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Confirmation Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            @forelse($items as $item)
                                @php
                                    $confirmation = $confirmationsByItem->get($item->id);
                                    $itemDriverPhotos = $photosByItem->get($item->id, collect());
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-2.5 text-xs font-medium text-slate-800">
                                        <p class="font-semibold">{{ $item->description }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Item ID: {{ $item->id }}</p>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-600">
                                        @if($shipment?->isPerItemDestination())
                                            <p class="font-medium text-slate-700">{{ $item->delivery_recipient_name ?? '-' }} ({{ $item->delivery_recipient_phone ?? '-' }})</p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                {{ $item->deliveryRegion?->name ?? '-' }}
                                                @if($item->deliveryDistrict?->name), {{ $item->deliveryDistrict->name }}@endif
                                                @if($item->delivery_town), {{ $item->delivery_town }}@endif
                                            </p>
                                        @else
                                            <p class="font-medium text-slate-700">{{ $shipment?->delivery_recipient_name ?? '-' }} ({{ $shipment?->delivery_recipient_phone ?? '-' }})</p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                {{ $shipment?->deliveryRegion?->name ?? '-' }}
                                                @if($shipment?->deliveryDistrict?->name), {{ $shipment->deliveryDistrict->name }}@endif
                                                @if($shipment?->delivery_town), {{ $shipment->delivery_town }}@endif
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 text-center font-semibold">{{ (int) $item->quantity }}</td>
                                    <td class="px-4 py-2.5 text-xs text-center">
                                        @if($confirmation)
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                                {{ (int) $confirmation->confirmed_quantity }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                                -
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 text-center font-semibold">{{ $item->images->count() }}</td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 text-center font-semibold">{{ $itemDriverPhotos->count() }}</td>
                                    <td class="px-4 py-2.5 text-xs text-slate-600">
                                        {{ $confirmation?->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-500">No items found for this shipment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                        @if($assignment->cancelled_at)
                            <div class="relative flex items-start gap-4">
                                <div class="absolute left-[-21px] w-4 h-4 rounded-full border-2 border-white shadow-sm bg-rose-500"></div>
                                <div class="flex-1 bg-rose-50 rounded-xl border border-rose-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold text-rose-700">Cancelled</p>
                                        <p class="text-[11px] text-rose-600">{{ $assignment->cancelled_at->format('Y-m-d H:i:s') }}</p>
                                    </div>
                                    <p class="text-xs text-rose-700 mt-2">{{ $assignment->cancellation_reason ?: '-' }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'receiving'" x-cloak class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Warehouse Receiving</h3>
                        <p class="text-xs text-slate-600 mt-1">Save line items as draft, print barcode labels, then finalize receiving.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700">
                            <span class="mr-1.5">Status:</span>
                            <span x-text="receiptStatusLabel()"></span>
                        </span>
                        <button
                            type="button"
                            @@click="openFinalizeModal()"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isFinalized() || items.length === 0 || saving"
                        >
                            Finalize Receipt
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200/50">
                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Vendor (Declared)</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Driver (Confirmed)</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Expected</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Received</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Damaged</th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Photos</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Barcode</th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-for="item in items" :key="item.shipment_item_id">
                                <tr class="hover:bg-slate-50/70 align-top">
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[220px]">
                                        <p class="font-semibold text-slate-900" x-text="item.description"></p>
                                        <p class="text-[11px] text-slate-500 mt-1">ID: <span x-text="item.shipment_item_id"></span></p>
                                        <template x-if="item.discrepancy_type && item.discrepancy_type !== 'none'">
                                            <span class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700" x-text="item.discrepancy_type"></span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[200px]">
                                        <p class="font-semibold text-slate-900">Qty: <span x-text="item.vendor_quantity"></span></p>
                                        <p class="text-[11px] text-slate-500 mt-1">Photos: <span x-text="(item.vendor_photos || []).length"></span></p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <template x-for="photo in (item.vendor_photos || []).slice(0, 3)" :key="`vendor-${photo.id}`">
                                                <a :href="photo.url" target="_blank" rel="noopener" class="block">
                                                    <img :src="photo.url" alt="Vendor item photo" class="h-8 w-8 rounded border border-slate-200 object-cover">
                                                </a>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[210px]">
                                        <p class="font-semibold text-slate-900">
                                            Qty:
                                            <span x-text="item.driver_confirmed_quantity === null ? '-' : item.driver_confirmed_quantity"></span>
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-1">Photos: <span x-text="(item.driver_photos || []).length"></span></p>
                                        <template x-if="item.driver_qty_matches_vendor !== null">
                                            <span
                                                class="mt-1 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                :class="item.driver_qty_matches_vendor ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
                                                x-text="item.driver_qty_matches_vendor ? 'Matches vendor qty' : 'Mismatch vs vendor qty'"
                                            ></span>
                                        </template>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <template x-for="photo in (item.driver_photos || []).slice(0, 3)" :key="`driver-${photo.id}`">
                                                <a :href="photo.url" target="_blank" rel="noopener" class="block">
                                                    <img :src="photo.url" alt="Driver item photo" class="h-8 w-8 rounded border border-slate-200 object-cover">
                                                </a>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800">
                                        <span x-text="item.expected_quantity"></span>
                                        <p class="text-[10px] text-slate-500 mt-0.5" x-text="item.expected_source === 'driver' ? 'Driver confirmed qty' : 'Vendor qty fallback'"></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <input type="number" min="0" class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-xs text-center"
                                               x-model.number="item.received_quantity" :disabled="isFinalized()">
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <input type="number" min="0" class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-xs text-center"
                                               x-model.number="item.damaged_quantity" :disabled="isFinalized()">
                                    </td>
                                    <td class="px-4 py-2.5 min-w-[220px]">
                                        <textarea rows="2" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs"
                                                  x-model="item.notes" :disabled="isFinalized()"></textarea>
                                    </td>
                                    <td class="px-4 py-2.5 text-center min-w-[170px]">
                                        <input type="file" accept="image/*" multiple class="w-full text-[11px]" :disabled="isFinalized()"
                                               @@change="setItemFiles(item.shipment_item_id, $event)">
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            <span x-text="(item.photos || []).length"></span> saved
                                        </p>
                                        <div class="mt-2 max-h-24 overflow-y-auto space-y-1">
                                            <template x-for="photo in (item.photos || [])" :key="photo.id">
                                                <label class="flex items-center gap-1.5 text-[10px] text-slate-600">
                                                    <input
                                                        type="checkbox"
                                                        class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                                        :disabled="isFinalized()"
                                                        :checked="isPhotoMarkedForRemoval(item.shipment_item_id, photo.id)"
                                                        @@change="toggleRemovePhoto(item.shipment_item_id, photo.id, $event.target.checked)"
                                                    >
                                                    <span>Remove #<span x-text="photo.id"></span></span>
                                                </label>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-center min-w-[170px]">
                                        <p class="text-[11px] font-medium text-slate-700" x-text="item.barcode_value || '-'"></p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">Prints: <span x-text="item.barcode_print_count || 0"></span></p>
                                        <button type="button" class="mt-1 inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                                @@click="printLabel(item.shipment_item_id)" :disabled="isFinalized() || saving">
                                            Print Label
                                        </button>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <button type="button"
                                                class="inline-flex items-center rounded-md bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                                @@click="saveItem(item.shipment_item_id)"
                                                :disabled="isFinalized() || saving">
                                            Save
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-xs text-slate-500">No shipment items found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div x-show="showFinalizeModal" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
                    <div class="w-full max-w-xl rounded-2xl bg-white border border-slate-200 shadow-xl">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h4 class="text-base font-semibold text-slate-900">Finalize Receipt</h4>
                            <button type="button" @@click="showFinalizeModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <p class="text-sm text-slate-600">Finalization will lock receiving edits for this pickup.</p>
                            <textarea rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Receive notes (optional)" x-model="finalizeNotes"></textarea>
                            <template x-if="hasDiscrepancies()">
                                <div>
                                    <label class="block text-xs font-semibold text-amber-700 mb-1">Approval Reason (required for discrepancy)</label>
                                    <textarea rows="3" class="w-full rounded-lg border border-amber-300 px-3 py-2 text-sm" x-model="approvalReason"></textarea>
                                </div>
                            </template>
                        </div>
                        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @@click="showFinalizeModal = false" class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700">Cancel</button>
                            <button type="button" @@click="finalizeReceipt()" class="px-3 py-1.5 rounded-lg bg-slate-900 text-sm font-semibold text-white disabled:opacity-50" :disabled="saving">
                                Confirm Finalize
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

