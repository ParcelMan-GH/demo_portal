@extends('warehouse.layouts.app')

@section('title', 'Item Detail — ' . ($receiptItem->shipmentItem?->shipment?->shipment_number ?? 'N/A'))
@section('page-title', 'Received Item Detail')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lightgallery.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lg-thumbnail.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lg-zoom.min.css">
@endpush

@php
    $discrepancy   = strtolower($receiptItem->discrepancy_type ?? 'none');
    $discrepancyLabel = match ($discrepancy) {
        'none'    => 'No Discrepancy',
        'short'   => 'Short',
        'damaged' => 'Damaged',
        'extra'   => 'Extra',
        'partial' => 'Partial',
        default   => ucfirst($discrepancy),
    };
    $discrepancyBadgeHero = match ($discrepancy) {
        'none'    => 'bg-emerald-500/20 text-emerald-300',
        'short'   => 'bg-amber-500/20 text-amber-300',
        'damaged' => 'bg-rose-500/20 text-rose-300',
        'extra'   => 'bg-blue-500/20 text-blue-300',
        'partial' => 'bg-orange-500/20 text-orange-300',
        default   => 'bg-slate-500/20 text-slate-300',
    };
    $discrepancyBadge = match ($discrepancy) {
        'none'    => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
        'short'   => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'damaged' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
        'extra'   => 'bg-blue-100 text-blue-700 ring-1 ring-blue-200',
        'partial' => 'bg-orange-100 text-orange-700 ring-1 ring-orange-200',
        default   => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    };
    $conditionLabel = match ($receiptItem->condition_status) {
        'ok'      => 'OK',
        'damaged' => 'Damaged',
        'partial' => 'Partial',
        default   => ucfirst($receiptItem->condition_status ?? 'Unknown'),
    };
    $conditionBadgeHero = match ($receiptItem->condition_status) {
        'ok'      => 'bg-emerald-500/20 text-emerald-300',
        'damaged' => 'bg-rose-500/20 text-rose-300',
        'partial' => 'bg-amber-500/20 text-amber-300',
        default   => 'bg-slate-500/20 text-slate-300',
    };
    $conditionBadge = match ($receiptItem->condition_status) {
        'ok'      => 'bg-emerald-100 text-emerald-700',
        'damaged' => 'bg-rose-100 text-rose-700',
        'partial' => 'bg-amber-100 text-amber-700',
        default   => 'bg-slate-100 text-slate-600',
    };

    $shipmentNumber     = $receiptItem->shipmentItem?->shipment?->shipment_number ?? 'N/A';
    $description        = $receiptItem->shipmentItem?->description ?? 'Item Detail';
    $driver             = $receiptItem->receipt?->pickupAssignment?->driver;
    $pickup             = $receiptItem->receipt?->pickupAssignment;
    $totalPhotos        = $vendorPhotos->count() + $warehousePhotos->flatten(1)->count();
    $avatarLetter       = strtoupper(substr($description, 0, 1));
    $pickupAssignmentId = $receiptItem->receipt?->pickup_assignment_id;

    // Vendor photos for lightGallery (passed to Alpine as JS)
    $vendorPhotosJs = $vendorPhotos->map(fn($p) => [
        'src'      => $p['url'],
        'thumb'    => $p['url'],
        'subHtml'  => $p['original_name'] ?? '',
    ])->values()->toArray();

    // Warehouse photo groups for lightGallery
    $warehousePhotoGroupsJs = $warehousePhotos->map(fn($group, $type) => [
        'type'   => $type,
        'label'  => match (strtolower($type)) {
            'condition' => 'Condition',
            'damage'    => 'Damage',
            'proof'     => 'Proof of Receipt',
            default     => ucfirst($type),
        },
        'pill'   => match (strtolower($type)) {
            'damage'    => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
            'condition' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
            'proof'     => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
            default     => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
        },
        'photos' => $group->map(fn($p) => [
            'src'     => $p['url'],
            'thumb'   => $p['url'],
            'subHtml' => $p['original_name'] ?? '',
        ])->values()->toArray(),
    ])->values()->toArray();

    // Timeline
    $timeline = [
        ['label' => 'Shipment Created',       'value' => $receiptItem->shipmentItem?->shipment?->created_at, 'dot' => 'bg-blue-500'],
        ['label' => 'Pickup Assigned',        'value' => $pickup?->assigned_at,                              'dot' => 'bg-indigo-500'],
        ['label' => 'En Route',               'value' => $pickup?->en_route_at,                              'dot' => 'bg-violet-500'],
        ['label' => 'Arrived at Pickup',      'value' => $pickup?->arrived_at,                               'dot' => 'bg-amber-500'],
        ['label' => 'Picked Up',              'value' => $pickup?->picked_up_at,                             'dot' => 'bg-orange-500'],
        ['label' => 'Arrived at Warehouse',   'value' => $pickup?->arrived_warehouse_at,                     'dot' => 'bg-sky-500'],
        ['label' => 'Received at Warehouse',  'value' => $receiptItem->received_at,                          'dot' => 'bg-emerald-500'],
    ];
    $completedCount = collect($timeline)->filter(fn($e) => !is_null($e['value']))->count();
    $totalCount     = count($timeline);

    $timelineDescriptions = [
        'Shipment Created'      => 'Vendor submitted the shipment into the system',
        'Pickup Assigned'       => 'Driver assigned to collect this shipment',
        'En Route'              => 'Driver en route to vendor location',
        'Arrived at Pickup'     => 'Driver arrived at vendor / pickup location',
        'Picked Up'             => 'Items collected and loaded by driver',
        'Arrived at Warehouse'  => 'Driver arrived at ' . ($warehouse->name ?? 'warehouse'),
        'Received at Warehouse' => $receiptItem->receivedBy ? 'Received by ' . $receiptItem->receivedBy->name : 'Item processed into warehouse inventory',
    ];
    $timelineIconColors = [
        'bg-blue-500'   => ['badge' => 'bg-blue-50 text-blue-700',    'icon' => 'text-white'],
        'bg-indigo-500' => ['badge' => 'bg-indigo-50 text-indigo-700','icon' => 'text-white'],
        'bg-violet-500' => ['badge' => 'bg-violet-50 text-violet-700','icon' => 'text-white'],
        'bg-amber-500'  => ['badge' => 'bg-amber-50 text-amber-700',  'icon' => 'text-white'],
        'bg-orange-500' => ['badge' => 'bg-orange-50 text-orange-700','icon' => 'text-white'],
        'bg-sky-500'    => ['badge' => 'bg-sky-50 text-sky-700',      'icon' => 'text-white'],
        'bg-emerald-500'=> ['badge' => 'bg-emerald-50 text-emerald-700','icon' => 'text-white'],
    ];
@endphp

@section('content')
<div
    class="space-y-6"
    x-data="{
        activeTab: 'overview',
        _lg: null,
        openGallery(images, startIndex) {
            if (this._lg) { try { this._lg.destroy(); } catch(e) {} this._lg = null; }
            let c = document.getElementById('_ri_lg_c');
            if (!c) { c = document.createElement('div'); c.id = '_ri_lg_c'; document.body.appendChild(c); }
            this._lg = window.lightGallery(c, {
                dynamic: true,
                dynamicEl: images,
                plugins: [window.lgZoom, window.lgThumbnail],
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                toggleThumb: false,
                thumbWidth: 100,
                thumbHeight: '65px',
                download: false,
                loop: false,
                speed: 300,
                zoomFromOrigin: false
            });
            setTimeout(() => this._lg.openGallery(startIndex), 50);
        }
    }"
>

    {{-- ── Hero ────────────────────────────────────────────────────────────── --}}
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="riGrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#riGrid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <div class="mb-6">
                    <a href="{{ route('warehouse.items.received.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Received Items</span>
                    </a>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-emerald-500/30 ring-4 ring-white/10 flex-shrink-0">
                            {{ $avatarLetter }}
                        </div>
                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white">{{ $description }}</h1>
                                <p class="text-slate-400 text-sm mt-0.5 font-mono">{{ $shipmentNumber }}</p>
                                <p class="text-slate-500 text-xs mt-1">Receipt item #{{ $receiptItem->id }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-300">
                                @if ($driver)
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>{{ $driver->name }}</span>
                                </div>
                                @if ($driver->phone)
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span>{{ $driver->phone }}</span>
                                </div>
                                @endif
                                @endif
                                @if ($receiptItem->received_at)
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ $receiptItem->received_at->format('M d, Y H:i') }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $discrepancyBadgeHero }}">{{ $discrepancyLabel }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $conditionBadgeHero }}">Condition: {{ $conditionLabel }}</span>
                                @if ($receiptItem->barcode_value)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300 font-mono">{{ $receiptItem->barcode_value }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stat tiles --}}
                    <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap lg:ml-auto lg:self-start">
                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ $receiptItem->expected_quantity ?? 0 }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Expected</p>
                            </div>
                        </div>
                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ $receiptItem->received_quantity ?? 0 }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Received</p>
                            </div>
                        </div>
                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500/30 to-rose-600/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ $receiptItem->damaged_quantity ?? 0 }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Damaged</p>
                            </div>
                        </div>
                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center gap-1.5 transition-colors shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ $totalPhotos }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Photos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabbed body ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[600px]">

        {{-- Sidebar --}}
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Item</p>

            {{-- Overview --}}
            <button @@click="activeTab = 'overview'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'overview' ? 'bg-sky-50 ring-1 ring-sky-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'overview' ? 'bg-sky-500 shadow-sm shadow-sky-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'overview' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'overview' ? 'font-bold text-sky-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Overview</span>
            </button>

            {{-- Tracking --}}
            <button @@click="activeTab = 'tracking'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'tracking' ? 'bg-amber-50 ring-1 ring-amber-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'tracking' ? 'bg-amber-500 shadow-sm shadow-amber-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'tracking' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'tracking' ? 'font-bold text-amber-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Timeline</span>
            </button>

        </aside>

        {{-- Tab content --}}
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">

            {{-- ══════════════════════════════════════════ --}}
            {{-- OVERVIEW TAB                               --}}
            {{-- ══════════════════════════════════════════ --}}
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

                    {{-- Left (3/5) --}}
                    <div class="xl:col-span-3 space-y-4">

                        {{-- Item Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Item Summary</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Warehouse receipt details for this item</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $discrepancyBadge }}">{{ $discrepancyLabel }}</span>
                            </div>

                            {{-- Qty tiles --}}
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                <div class="rounded-xl bg-blue-50 border border-blue-100 px-4 py-3 text-center">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-400 mb-1">Expected</p>
                                    <p class="text-2xl font-bold text-blue-700">{{ $receiptItem->expected_quantity ?? 0 }}</p>
                                </div>
                                <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-center">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400 mb-1">Received</p>
                                    <p class="text-2xl font-bold text-emerald-700">{{ $receiptItem->received_quantity ?? 0 }}</p>
                                </div>
                                <div class="rounded-xl {{ ($receiptItem->damaged_quantity ?? 0) > 0 ? 'bg-rose-50 border-rose-100' : 'bg-slate-50 border-slate-100' }} border px-4 py-3 text-center">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ ($receiptItem->damaged_quantity ?? 0) > 0 ? 'text-rose-400' : 'text-slate-400' }} mb-1">Damaged</p>
                                    <p class="text-2xl font-bold {{ ($receiptItem->damaged_quantity ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $receiptItem->damaged_quantity ?? 0 }}</p>
                                </div>
                            </div>

                            @php $diff = ($receiptItem->received_quantity ?? 0) - ($receiptItem->expected_quantity ?? 0); @endphp
                            @if ($diff !== 0)
                            <div class="mb-4 rounded-xl px-4 py-2.5 {{ $diff < 0 ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200' }}">
                                <p class="text-xs font-semibold {{ $diff < 0 ? 'text-amber-700' : 'text-blue-700' }}">
                                    {{ $diff > 0 ? '+' : '' }}{{ $diff }} unit{{ abs($diff) !== 1 ? 's' : '' }} {{ $diff < 0 ? 'short' : 'extra' }} vs expected
                                </p>
                            </div>
                            @endif

                            {{-- Detail grid --}}
                            <div class="pt-4 border-t border-slate-100 grid grid-cols-2 gap-x-6 gap-y-3 text-xs">
                                <div>
                                    <p class="text-slate-400 mb-0.5">Condition</p>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $conditionBadge }}">{{ $conditionLabel }}</span>
                                </div>
                                <div>
                                    <p class="text-slate-400 mb-0.5">Received At</p>
                                    <p class="font-semibold text-slate-800">{{ $receiptItem->received_at?->format('M d, Y H:i') ?? '—' }}</p>
                                </div>
                                @if ($receiptItem->receivedBy)
                                <div>
                                    <p class="text-slate-400 mb-0.5">Received By</p>
                                    <p class="font-semibold text-slate-800">{{ $receiptItem->receivedBy->name }}</p>
                                </div>
                                @endif
                                @if ($receiptItem->barcode_value)
                                <div>
                                    <p class="text-slate-400 mb-0.5">Barcode</p>
                                    <p class="font-mono font-bold text-slate-800">{{ $receiptItem->barcode_value }}</p>
                                </div>
                                @endif
                                @if ($receiptItem->notes)
                                <div class="col-span-2">
                                    <p class="text-slate-400 mb-1">Notes</p>
                                    <p class="text-slate-700 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 leading-relaxed">{{ $receiptItem->notes }}</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Barcode --}}
                        @if ($barcodeSvg)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Barcode</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Code 128 — scan to identify this item</p>
                                </div>
                                @if ($receiptItem->barcode_print_count)
                                <span class="text-xs text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">Printed {{ $receiptItem->barcode_print_count }}×</span>
                                @endif
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="w-full max-w-xs border border-slate-100 rounded-xl p-3 bg-white">{!! $barcodeSvg !!}</div>
                                <p class="mt-2 text-xs font-mono font-semibold text-slate-600 tracking-widest">{{ $receiptItem->barcode_value }}</p>
                            </div>
                        </div>
                        @endif

                        {{-- Photos --}}
                        @if ($totalPhotos > 0)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Photos</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">All item photos from every phase</p>
                                </div>
                                <span class="text-xs text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">{{ $totalPhotos }} total</span>
                            </div>

                            <div class="space-y-5">
                                {{-- Vendor photos --}}
                                @if ($vendorPhotos->isNotEmpty())
                                <div>
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-100 text-violet-700 ring-1 ring-violet-200">Vendor Photos</span>
                                        <span class="text-xs text-slate-400">{{ $vendorPhotos->count() }}</span>
                                    </div>
                                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                                        @foreach ($vendorPhotos as $idx => $photo)
                                        <button type="button"
                                                @@click="openGallery({{ json_encode($vendorPhotosJs) }}, {{ $idx }})"
                                                class="group relative aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-100 hover:border-violet-300 focus:outline-none transition-colors">
                                            <img src="{{ $photo['url'] }}" alt="{{ $photo['original_name'] ?? '' }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                </svg>
                                            </div>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                {{-- Warehouse photos by group --}}
                                @foreach ($warehousePhotoGroupsJs as $group)
                                <div>
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $group['pill'] }}">{{ $group['label'] }}</span>
                                        <span class="text-xs text-slate-400">{{ count($group['photos']) }}</span>
                                    </div>
                                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                                        @foreach ($group['photos'] as $idx => $photo)
                                        <button type="button"
                                                @@click="openGallery({{ json_encode($group['photos']) }}, {{ $idx }})"
                                                class="group relative aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-100 hover:border-emerald-300 focus:outline-none transition-colors">
                                            <img src="{{ $photo['src'] }}" alt="{{ $photo['subHtml'] ?? '' }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                </svg>
                                            </div>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                    </div>

                    {{-- Right (2/5) --}}
                    <div class="xl:col-span-2 space-y-4">

                        {{-- Receipt Info --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-900 mb-3">Receipt Info</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Shipment</p>
                                    <p class="text-xs font-bold text-slate-900 font-mono">{{ $shipmentNumber }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Receipt</p>
                                    <p class="text-xs font-bold text-slate-900">#{{ $receiptItem->warehouse_receipt_id }}</p>
                                </div>
                                <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400 mb-1">Status</p>
                                    <p class="text-xs font-bold text-emerald-700">Finalized</p>
                                </div>
                            </div>
                            @if ($receiptItem->receipt?->finalized_at)
                            <div class="text-xs pt-3 border-t border-slate-100">
                                <p class="text-slate-400">Finalized At</p>
                                <p class="font-semibold text-slate-800 mt-0.5">{{ $receiptItem->receipt->finalized_at->format('M d, Y H:i') }}</p>
                            </div>
                            @endif
                            @if ($pickupAssignmentId)
                            <div class="pt-3 border-t border-slate-100 mt-3">
                                <a href="{{ route('warehouse.receipts.pending.show', $pickupAssignmentId) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    View Full Receipt
                                </a>
                            </div>
                            @endif
                        </div>

                        {{-- Driver --}}
                        @if ($driver)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-900 mb-3">Driver</h3>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-indigo-700">{{ strtoupper(substr($driver->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $driver->name }}</p>
                                    @if ($driver->phone)
                                    <a href="tel:{{ $driver->phone }}" class="text-xs text-blue-600 hover:text-blue-800 transition-colors">{{ $driver->phone }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════ --}}
            {{-- TRACKING TAB                               --}}
            {{-- ══════════════════════════════════════════ --}}
            <div x-show="activeTab === 'tracking'" x-cloak>

                {{-- Section Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-200/60 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Item Journey</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Full tracking history from shipment creation to warehouse receipt</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ $completedCount }} / {{ $totalCount }} steps</span>
                </div>

                {{-- Timeline Events --}}
                <div>
                    @foreach ($timeline as $index => $event)
                    @php
                        $isCompleted = !is_null($event['value']);
                        $dotClass    = $event['dot'] ?? 'bg-slate-400';
                        $colors      = $timelineIconColors[$dotClass] ?? ['badge' => 'bg-slate-100 text-slate-500', 'icon' => 'text-white'];
                        $iconBg      = $isCompleted ? $dotClass : 'bg-slate-200';
                        $badgeClass  = $isCompleted ? $colors['badge'] : 'bg-slate-100 text-slate-400';
                        $desc        = $timelineDescriptions[$event['label']] ?? $event['label'];
                    @endphp

                    @if ($index > 0)
                    <div class="flex justify-start pl-[22px] my-0.5">
                        <div class="w-px h-4 bg-slate-200"></div>
                    </div>
                    @endif

                    <div class="flex items-start gap-3 bg-white rounded-2xl border border-slate-100 shadow-sm {{ $isCompleted ? 'hover:shadow-md hover:border-slate-200' : 'opacity-55' }} transition-all duration-200 p-3.5">
                        {{-- Colored icon square --}}
                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm mt-0.5 {{ $iconBg }}">
                            @if ($event['label'] === 'Shipment Created')
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            @elseif ($event['label'] === 'Pickup Assigned')
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            @elseif ($event['label'] === 'En Route')
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            @elseif ($event['label'] === 'Arrived at Pickup')
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            @elseif ($event['label'] === 'Picked Up')
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            @elseif ($event['label'] === 'Arrived at Warehouse')
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            @else
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
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
                </div>

            </div>{{-- /tracking --}}

        </div>{{-- /tab content --}}
    </div>{{-- /tabbed body --}}

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/lightgallery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/plugins/thumbnail/lg-thumbnail.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/plugins/zoom/lg-zoom.min.js"></script>
@endpush
