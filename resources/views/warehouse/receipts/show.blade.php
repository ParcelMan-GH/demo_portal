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
        'assigned' => 'bg-orange-100 text-orange-800 ring-1 ring-orange-200',
        'en_route' => 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200',
        'arrived' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'picking_up' => 'bg-violet-100 text-violet-700 ring-1 ring-violet-200',
        'completed' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
        'cancelled' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    };
    $statusDotClasses = match ($statusValue) {
        'assigned' => 'bg-orange-600',
        'en_route' => 'bg-indigo-500',
        'arrived' => 'bg-amber-500',
        'picking_up' => 'bg-violet-500',
        'completed' => 'bg-emerald-500',
        'cancelled' => 'bg-rose-500',
        default => 'bg-slate-400',
    };

    $timeline = [
        ['label' => 'Assigned', 'value' => $assignment->assigned_at, 'dot' => 'bg-orange-600'],
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
    <div x-show="!canReceive" x-cloak class="flex items-center gap-3 px-5 py-4 rounded-2xl border border-amber-200/70 bg-amber-50/80 backdrop-blur-sm">
        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-100">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-amber-800">Awaiting Pickup</p>
            <p class="text-xs text-amber-700 mt-0.5">The driver has not picked up this shipment yet. Receiving and finalization are disabled until pickup is confirmed.</p>
        </div>
    </div>

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
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-orange-600 via-orange-700 to-orange-800 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-orange-600/30 ring-4 ring-white/10">
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
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-600/30 to-orange-700/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base lg:text-lg font-bold text-white leading-none">{{ number_format($items->count()) }}</p>
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Packages</p>
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
                                <p class="text-[9px] leading-tight text-slate-400 mt-0.5 font-medium">Confirmed Pkgs</p>
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

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        <!-- Sidebar Nav -->
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <!-- Section: Receipt -->
            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Receipt</p>

            <!-- Overview -->
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

            <!-- Receiving -->
            <button @@click="activeTab = 'receiving'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'receiving' ? 'bg-emerald-50 ring-1 ring-emerald-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'receiving' ? 'bg-emerald-500 shadow-sm shadow-emerald-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'receiving' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'receiving' ? 'font-bold text-emerald-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Receiving</span>
            </button>

            <!-- Divider: Finance -->
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Finance</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Invoice -->
            <button @@click="activeTab = 'invoice'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'invoice' ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'invoice' ? 'bg-violet-500 shadow-sm shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'invoice' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'invoice' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Invoice</span>
            </button>

            <!-- Payments -->
            <button @@click="switchTab('payments')"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'payments' ? 'bg-teal-50 ring-1 ring-teal-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'payments' ? 'bg-teal-500 shadow-sm shadow-teal-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'payments' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'payments' ? 'font-bold text-teal-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Payments</span>
            </button>

            <!-- Divider: Activity -->
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Activity</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Timeline -->
            <button @@click="activeTab = 'timeline'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg transition-all duration-150 text-left"
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

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

                    <!-- Left Column -->
                    <div class="xl:col-span-3 space-y-4">

                        <!-- Card A: Assignment Summary -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Assignment Summary</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Pickup assignment details for this delivery</p>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $statusClasses }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClasses }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Shipment</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $shipment?->shipment_number ?? '—' }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $shipment?->vendor?->name ?? '—' }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Assigned</p>
                                    <p class="text-sm font-bold text-slate-900">{{ optional($assignment->assigned_at)?->format('M d, Y') ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ optional($assignment->assigned_at)?->format('H:i') ?? '' }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Received</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $assignment->received_at ? $assignment->received_at->format('M d, Y') : '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $assignment->received_at ? $assignment->received_at->format('H:i') : 'Not yet received' }}</p>
                                </div>
                            </div>
                            <!-- Driver Info -->
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2.5">Driver</p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2 text-xs">
                                    <div>
                                        <p class="text-slate-400">Name</p>
                                        <p class="font-semibold text-slate-800 mt-0.5">{{ $assignment->driver?->name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">Phone</p>
                                        <p class="font-semibold text-slate-800 mt-0.5">{{ $assignment->driver?->phone ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">Vehicle</p>
                                        <p class="font-semibold text-slate-800 mt-0.5">{{ $assignment->driver?->vehicle_type ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400">License Plate</p>
                                        <p class="font-mono font-semibold text-slate-800 mt-0.5">{{ $assignment->driver?->license_plate ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card B: Pickup + Destination -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Pickup Location -->
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Pickup Location</h3>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Contact</span>
                                        <span class="font-semibold text-slate-800">{{ $shipment?->pickup_contact_name ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Phone</span>
                                        <span class="font-semibold text-slate-800">{{ $shipment?->pickup_contact_phone ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Region</span>
                                        <span class="font-semibold text-slate-800">
                                            {{ $shipment?->pickupRegion?->name ?? '—' }}@if($shipment?->pickupDistrict?->name) / {{ $shipment->pickupDistrict->name }}@endif
                                        </span>
                                    </div>
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Town</span>
                                        <span class="font-semibold text-slate-800">{{ $shipment?->pickup_town ?? '—' }}</span>
                                    </div>
                                    @if($shipment?->pickup_landmark)
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Landmark</span>
                                        <span class="text-slate-600">{{ $shipment->pickup_landmark }}</span>
                                    </div>
                                    @endif
                                    @if($shipment?->pickup_gh_post_address)
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">GH Post</span>
                                        <span class="font-mono text-slate-700">{{ $shipment->pickup_gh_post_address }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Destination -->
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Destination</h3>
                                </div>
                                @if($shipment?->isPerItemDestination())
                                    <div class="space-y-2 text-xs">
                                        <p class="text-slate-500">Mode: <span class="font-semibold text-slate-800">Per-item destination</span></p>
                                        <p class="text-slate-400 italic">Each item has its own recipient &amp; delivery address.</p>
                                    </div>
                                @else
                                    <div class="space-y-2.5">
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Recipient</span>
                                            <span class="font-semibold text-slate-800">{{ $shipment?->delivery_recipient_name ?? '—' }}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Phone</span>
                                            <span class="font-semibold text-slate-800">{{ $shipment?->delivery_recipient_phone ?? '—' }}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Region</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ $shipment?->deliveryRegion?->name ?? '—' }}@if($shipment?->deliveryDistrict?->name) / {{ $shipment->deliveryDistrict->name }}@endif
                                            </span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Town</span>
                                            <span class="font-semibold text-slate-800">{{ $shipment?->delivery_town ?? '—' }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Notes -->
                        @if($assignment->notes || $assignment->receive_notes || $assignment->cancellation_reason)
                        <div class="space-y-3">
                            @if($assignment->notes)
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex gap-3">
                                <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Assignment Notes</p>
                                    <p class="text-xs text-slate-700">{{ $assignment->notes }}</p>
                                </div>
                            </div>
                            @endif
                            @if($assignment->receive_notes)
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex gap-3">
                                <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-sky-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Receive Notes</p>
                                    <p class="text-xs text-slate-700">{{ $assignment->receive_notes }}</p>
                                </div>
                            </div>
                            @endif
                            @if($assignment->cancellation_reason)
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 flex gap-3">
                                <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500 mb-1">Cancellation Reason</p>
                                    <p class="text-xs text-rose-700">{{ $assignment->cancellation_reason }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- Card C: Packages -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-slate-900">Packages</h3>
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600">{{ $items->count() }}</span>
                                </div>
                                @if($shipment?->isPerItemDestination())
                                    <span class="text-[10px] text-slate-400 font-medium">Per-item destinations</span>
                                @else
                                    <span class="text-[10px] text-slate-400 font-medium">Shared destination</span>
                                @endif
                            </div>
                            @forelse($items as $item)
                                @php
                                    $confirmation = $confirmationsByItem->get($item->id);
                                    $hasConfirmation = (bool) $confirmation;
                                    $qtyMismatch = $hasConfirmation && ((int)$confirmation->confirmed_quantity !== (int)$item->quantity);
                                @endphp
                                <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/70 transition-colors border-b border-slate-100 last:border-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">{{ $item->description ?: '—' }}</p>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                            @if($item->tracking_code)
                                                <span class="text-[10px] text-slate-400 font-mono">{{ $item->tracking_code }}</span>
                                            @endif
                                            @if($shipment?->isPerItemDestination())
                                                <span class="text-[10px] text-slate-400">{{ $item->delivery_recipient_name ?? '—' }}</span>
                                            @else
                                                <span class="text-[10px] text-slate-400">{{ $shipment?->delivery_recipient_name ?? '—' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex-shrink-0" title="Expected qty">{{ (int)$item->quantity }}</span>
                                    @if($hasConfirmation)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full flex-shrink-0 text-xs font-bold {{ $qtyMismatch ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}" title="Driver confirmed qty">{{ (int)$confirmation->confirmed_quantity }}</span>
                                    @endif
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold flex-shrink-0 {{ $hasConfirmation ? ($qtyMismatch ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') : 'bg-slate-100 text-slate-600' }}">
                                        {{ $hasConfirmation ? ($qtyMismatch ? 'Mismatch' : 'Confirmed') : 'Pending' }}
                                    </span>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-10">
                                    <svg class="w-10 h-10 text-slate-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-slate-400 text-sm">No items found</p>
                                </div>
                            @endforelse
                        </div>

                    </div>

                    <!-- Right Column: Widgets -->
                    <div class="xl:col-span-2 space-y-4">

                        <!-- Widget: Invoice (matches admin design) -->
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            <!-- Gradient Header -->
                            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-white leading-none">Active Invoice</h3>
                                            <template x-if="activeInvoice()">
                                                <p class="text-[11px] text-white/60 mt-0.5" x-text="'#' + activeInvoice().invoice_number"></p>
                                            </template>
                                            <template x-if="!activeInvoice()">
                                                <p class="text-[11px] text-white/50 mt-0.5">No active invoice</p>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5">
                                        <template x-if="activeInvoice()">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-white/20 text-white ring-1 ring-white/30 capitalize" x-text="invoiceStatusLabel(activeInvoice().status)"></span>
                                        </template>
                                        <button @@click="activeTab = 'invoice'" class="text-[11px] text-white/60 hover:text-white font-medium transition-colors flex items-center gap-0.5">
                                            History <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Body -->
                            <div class="p-5">
                                <template x-if="invoiceUiError">
                                    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="invoiceUiError"></div>
                                </template>
                                <!-- No active invoice -->
                                <template x-if="!activeInvoice()">
                                    <div>
                                        <template x-if="!hasActiveInvoice() && canCreateInvoice">
                                            <div class="text-center py-4">
                                                <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-medium text-slate-500 mb-3">No invoice yet</p>
                                                <button @@click="openCreateInvoiceModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:opacity-90 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Create Invoice
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="hasActiveInvoice() || !canCreateInvoice">
                                            <div class="text-center py-4">
                                                <svg class="w-9 h-9 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="text-xs text-slate-400">No active invoice</p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <!-- Has active invoice -->
                                <template x-if="activeInvoice()">
                                    <div>
                                        <!-- Fee rows -->
                                        <div class="mb-4">
                                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 text-xs mb-0.5">
                                                <span class="text-slate-500">Pickup Fee</span>
                                                <span class="font-bold text-slate-700" x-text="formatCurrency(activeInvoice().pickup_fee)"></span>
                                            </div>
                                            <div class="flex items-center justify-between px-3 py-2 text-xs mb-0.5">
                                                <span class="text-slate-500">Transport Fee</span>
                                                <span class="font-bold text-slate-700" x-text="formatCurrency(activeInvoice().transport_fee)"></span>
                                            </div>
                                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 text-xs mb-0.5">
                                                <span class="text-slate-500">Handling Fee</span>
                                                <span class="font-bold text-slate-700" x-text="formatCurrency(activeInvoice().handling_fee)"></span>
                                            </div>
                                            <template x-if="activeInvoice().other_fee > 0">
                                                <div class="flex items-center justify-between px-3 py-2 text-xs mb-0.5">
                                                    <span class="text-slate-500">Other Fee</span>
                                                    <span class="font-bold text-slate-700" x-text="formatCurrency(activeInvoice().other_fee)"></span>
                                                </div>
                                            </template>
                                            <!-- Total featured -->
                                            <div class="mt-2 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl px-4 py-3 flex items-center justify-between shadow-sm shadow-emerald-200/60">
                                                <span class="text-[11px] font-bold text-white/70 uppercase tracking-wide">Total</span>
                                                <span class="text-base font-black text-white" x-text="formatCurrency(activeInvoice().total_amount)"></span>
                                            </div>
                                        </div>
                                        <!-- Action buttons -->
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a :href="activeInvoice().download_url" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                PDF
                                            </a>
                                            <template x-if="canEditInvoice && activeInvoice().status === 'pending'">
                                                <button @@click="sendInvoice(activeInvoice())"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-orange-600 to-orange-700 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                                    Send
                                                </button>
                                            </template>
                                            <template x-if="canEditInvoice && isSuperAdmin && activeInvoice().status === 'sent'">
                                                <button @@click="adminAcceptInvoice(activeInvoice())"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Accept
                                                </button>
                                            </template>
                                            <a :href="invoiceShowUrl.replace('__INVOICE__', activeInvoice().id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-violet-500 to-purple-600 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View Details
                                            </a>
                                            <template x-if="canEditInvoice && ['pending','sent','accepted'].includes(activeInvoice().status)">
                                                <button @@click="openCancelInvoiceModal(activeInvoice())"
                                                    class="inline-flex items-center px-3 py-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 text-xs font-semibold transition-colors">
                                                    Cancel
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Widget: Warehouse Receiving -->
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            <div class="bg-gradient-to-br from-orange-600 to-orange-700 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-white leading-none">Warehouse Receiving</h3>
                                        @if($receipt)
                                            @php
                                                $receiptLabel = match ($receipt->status ?? 'draft') {
                                                    'discrepancy_open' => 'Discrepancy Open',
                                                    'finalized' => 'Finalized',
                                                    default => 'Draft',
                                                };
                                            @endphp
                                            <p class="text-[11px] text-white/60 mt-0.5">{{ $receiptLabel }}</p>
                                        @else
                                            <p class="text-[11px] text-white/50 mt-0.5">No receipt yet</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-4 divide-y divide-slate-100 text-xs">
                                <div class="flex items-center justify-between py-2.5 first:pt-0">
                                    <span class="text-slate-400">Receipt Status</span>
                                    @if($receipt)
                                        @php
                                            $receiptBadgeClass = match ($receipt->status ?? 'draft') {
                                                'finalized' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'discrepancy_open' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-semibold {{ $receiptBadgeClass }}">{{ $receiptLabel }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[10px] font-semibold text-slate-500">No Receipt</span>
                                    @endif
                                </div>
                                <div class="flex items-start justify-between gap-3 py-2.5">
                                    <div>
                                        <p class="text-xs text-slate-700 font-medium">Total Packages</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Shipment line items declared by vendor</p>
                                    </div>
                                    <span class="font-bold text-slate-800 text-sm flex-shrink-0">{{ $items->count() }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-3 py-2.5">
                                    <div>
                                        <p class="text-xs text-slate-700 font-medium">Driver Confirmed</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Packages the driver verified at pickup</p>
                                    </div>
                                    <span class="font-bold text-slate-800 text-sm flex-shrink-0">{{ $itemConfirmations->count() }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-3 py-2.5 last:pb-0">
                                    <div>
                                        <p class="text-xs text-slate-700 font-medium">Confirmed Qty</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Total units the driver counted across all items</p>
                                    </div>
                                    <span class="font-bold text-slate-800 text-sm flex-shrink-0">{{ number_format($itemConfirmations->sum('confirmed_quantity')) }}</span>
                                </div>
                            </div>
                            <div class="px-5 pb-4 flex justify-end">
                                <button @@click="activeTab = 'receiving'" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-sky-600 text-white text-xs font-semibold hover:bg-sky-700 transition-colors">
                                    Go to Receiving
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div x-show="activeTab === 'timeline'" x-cloak>
                @php
                    $completedCount = collect($timeline)->filter(fn($e) => !is_null($e['value']))->count();
                    $totalCount = count($timeline);
                    $timelineDescriptions = [
                        'Assigned'          => 'Driver assigned to this pickup',
                        'En Route'          => 'Driver en route to vendor location',
                        'Arrived Pickup'    => 'Driver arrived at vendor location',
                        'Picked Up'         => 'Packages picked up from vendor',
                        'Arrived Warehouse' => 'Driver arrived at warehouse',
                        'Received'          => 'Shipment received at warehouse',
                        'Completed'         => 'Pickup assignment completed',
                    ];
                    $timelineIconColor = [
                        'bg-orange-600'    => ['badge' => 'bg-orange-50 text-orange-800',    'icon' => 'text-white'],
                        'bg-indigo-500'  => ['badge' => 'bg-indigo-50 text-indigo-700', 'icon' => 'text-white'],
                        'bg-amber-500'   => ['badge' => 'bg-amber-50 text-amber-700',   'icon' => 'text-white'],
                        'bg-violet-500'  => ['badge' => 'bg-violet-50 text-violet-700', 'icon' => 'text-white'],
                        'bg-sky-500'     => ['badge' => 'bg-sky-50 text-sky-700',       'icon' => 'text-white'],
                        'bg-teal-500'    => ['badge' => 'bg-teal-50 text-teal-700',     'icon' => 'text-white'],
                        'bg-emerald-500' => ['badge' => 'bg-emerald-50 text-emerald-700','icon' => 'text-white'],
                    ];
                @endphp

                <!-- Section Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-200/60 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Pickup Timeline</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Step-by-step progress from assignment to completion</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ $completedCount }} / {{ $totalCount }} steps</span>
                </div>

                <!-- Timeline Events -->
                <div>
                    @foreach($timeline as $index => $event)
                        @php
                            $isCompleted = !is_null($event['value']);
                            $dotClass    = $event['dot'] ?? 'bg-slate-400';
                            $colors      = $timelineIconColor[$dotClass] ?? ['badge' => 'bg-slate-100 text-slate-500', 'icon' => 'text-white'];
                            $iconBg      = $isCompleted ? $dotClass : 'bg-slate-200';
                            $badgeClass  = $isCompleted ? $colors['badge'] : 'bg-slate-100 text-slate-400';
                            $desc        = $timelineDescriptions[$event['label']] ?? $event['label'];
                        @endphp

                        @if($index > 0)
                            <div class="flex justify-start pl-[22px] my-0.5">
                                <div class="w-px h-4 bg-slate-200"></div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3 bg-white rounded-2xl border border-slate-100 shadow-sm {{ $isCompleted ? 'hover:shadow-md hover:border-slate-200' : 'opacity-55' }} transition-all duration-200 p-3.5">
                            <!-- Colored icon square -->
                            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm mt-0.5 {{ $iconBg }}">
                                @if($event['label'] === 'Assigned')
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                @elseif($event['label'] === 'En Route')
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                @elseif($event['label'] === 'Arrived Pickup')
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                @elseif($event['label'] === 'Picked Up')
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                @elseif($event['label'] === 'Arrived Warehouse')
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                @elseif($event['label'] === 'Received')
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <!-- Badge + timestamp row -->
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $badgeClass }}">
                                        {{ $event['label'] }}
                                    </span>
                                    <span class="text-[10px] font-medium whitespace-nowrap flex items-center gap-1 {{ $isCompleted ? 'text-slate-400' : 'text-slate-300' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $event['value'] ? $event['value']->format('Y-m-d H:i:s') : 'Pending' }}
                                    </span>
                                </div>
                                <!-- Description -->
                                <p class="text-sm font-bold leading-snug {{ $isCompleted ? 'text-slate-800' : 'text-slate-400' }}">{{ $desc }}</p>
                            </div>
                        </div>
                    @endforeach

                    @if($assignment->cancelled_at)
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
                                    <span class="text-[10px] text-rose-400 font-medium whitespace-nowrap flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $assignment->cancelled_at->format('Y-m-d H:i:s') }}
                                    </span>
                                </div>
                                <p class="text-sm font-bold text-rose-700 leading-snug">Pickup Assignment Cancelled</p>
                                @if($assignment->cancellation_reason)
                                    <p class="text-[11px] text-rose-600 mt-1.5">{{ $assignment->cancellation_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- INVOICE TAB                             -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'invoice'" x-cloak>
                <!-- Section Header -->
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Invoice History</h3>
                            <p class="text-xs text-slate-500">All invoices created for this shipment</p>
                        </div>
                    </div>
                    <button type="button" x-show="canCreateInvoice && !hasActiveInvoice()" @@click="openCreateInvoiceModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Invoice
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                            <!-- Search -->
                            <div class="relative flex-1 max-w-xs">
                                <input type="text" x-model="invoiceTable.search" @@input.debounce.300ms="invoiceTable.page = 1"
                                    placeholder="Search invoices..."
                                    class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                                <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <!-- Status Filter -->
                            <div x-data="{ open: false }" class="relative w-full sm:w-56">
                                <button type="button" @@click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                    <span x-text="invoiceTable.statusFilter ? (invoiceTable.statusFilter.charAt(0).toUpperCase() + invoiceTable.statusFilter.slice(1)) : 'All statuses'"></span>
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display: none;">
                                    <button type="button" @@click="invoiceTable.statusFilter = ''; invoiceTable.page = 1; open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">All statuses</button>
                                    <button type="button" @@click="invoiceTable.statusFilter = 'pending'; invoiceTable.page = 1; open = false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'pending' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Pending</button>
                                    <button type="button" @@click="invoiceTable.statusFilter = 'sent'; invoiceTable.page = 1; open = false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'sent' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Sent</button>
                                    <button type="button" @@click="invoiceTable.statusFilter = 'accepted'; invoiceTable.page = 1; open = false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'accepted' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Accepted</button>
                                    <button type="button" @@click="invoiceTable.statusFilter = 'rejected'; invoiceTable.page = 1; open = false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'rejected' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Rejected</button>
                                    <button type="button" @@click="invoiceTable.statusFilter = 'cancelled'; invoiceTable.page = 1; open = false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'cancelled' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Cancelled</button>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <button type="button" x-show="activeInvoice()" @@click="activeTab = 'overview'"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View Active Invoice
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200/50 relative">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] divide-y divide-slate-200/50 text-xs">
                                <thead class="bg-slate-50/50">
                                    <tr>
                                        <th @@click="sortInvoice('invoice_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">INVOICE #</th>
                                        <th @@click="sortInvoice('status')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">STATUS</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIVE</th>
                                        <th @@click="sortInvoice('total_amount')" class="px-4 py-2 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">TOTAL</th>
                                        <th @@click="sortInvoice('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">CREATED</th>
                                        <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-transparent divide-y divide-slate-100/50">
                                    <template x-if="paginatedInvoiceRows().length === 0">
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">No invoices found</td>
                                        </tr>
                                    </template>
                                    <template x-for="historyInvoice in paginatedInvoiceRows()" :key="historyInvoice.id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="historyInvoice.invoice_number"></td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-xs">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="invoiceStatusClass(historyInvoice.status)" x-text="historyInvoice.status_label || invoiceStatusLabel(historyInvoice.status)"></span>
                                            </td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-xs">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="historyInvoice.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="historyInvoice.is_active ? 'Yes' : 'No'"></span>
                                            </td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-right text-xs text-slate-700 font-semibold" x-text="formatCurrency(historyInvoice.total_amount)"></td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="historyInvoice.created_at || '—'"></td>
                                            <td class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                                <div class="inline-flex items-center gap-1">
                                                    <a :href="invoiceShowUrl.replace('__INVOICE__', historyInvoice.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-violet-600 hover:bg-violet-50 transition-colors" title="View details">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    </a>
                                                    <a :href="historyInvoice.download_url" target="_blank" class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition-colors" title="Download PDF">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                    <button x-show="canEditInvoice && historyInvoice.status === 'pending'" @@click="sendInvoice(historyInvoice)" class="p-1.5 rounded-lg text-slate-400 hover:text-orange-700 hover:bg-orange-50 transition-colors" title="Send invoice">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.868v4.264a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12a7 7 0 1114 0A7 7 0 015 12z"/>
                                                        </svg>
                                                    </button>
                                                    <button x-show="canEditInvoice && ['pending', 'sent', 'accepted'].includes(historyInvoice.status)" @@click="openCancelInvoiceModal(historyInvoice)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Cancel invoice">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="text-xs text-slate-600">
                                    Showing <span x-text="invoiceMeta().from"></span> to <span x-text="invoiceMeta().to"></span> of <span x-text="invoiceMeta().total"></span> results
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="text-xs font-medium text-slate-600">Page <span x-text="invoiceMeta().page"></span> of <span x-text="invoiceMeta().lastPage"></span></div>
                                    <div class="flex space-x-1">
                                        <button @@click="invoiceTable.page = 1" :disabled="invoiceMeta().page === 1" :class="invoiceMeta().page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                        </button>
                                        <button @@click="invoiceTable.page = Math.max(1, invoiceTable.page - 1)" :disabled="invoiceMeta().page === 1" :class="invoiceMeta().page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <button @@click="invoiceTable.page = Math.min(invoiceMeta().lastPage, invoiceTable.page + 1)" :disabled="invoiceMeta().page >= invoiceMeta().lastPage" :class="invoiceMeta().page >= invoiceMeta().lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <button @@click="invoiceTable.page = invoiceMeta().lastPage" :disabled="invoiceMeta().page >= invoiceMeta().lastPage" :class="invoiceMeta().page >= invoiceMeta().lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- PAYMENTS TAB                            -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'payments'" x-cloak>
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
                    <!-- Card Header -->
                    <div class="px-6 py-5 border-b border-slate-200/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-teal-100">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
                                    <p class="mt-0.5 text-sm text-slate-500">Payment transactions for this shipment</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="filteredPayments().length + ' Total Payments'"></span>
                                <template x-if="canEditInvoice && !invoice">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        No active invoice
                                    </span>
                                </template>
                                <button x-show="canEditInvoice && invoice" @@click="paymentForm.open = true; paymentForm.payment_date = new Date().toISOString().split('T')[0]"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Record Payment
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="px-6 pt-5">
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 p-5 shadow-md">
                                <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/5 -translate-y-8 translate-x-8"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Invoiced</p>
                                    </div>
                                    <p class="text-2xl font-bold text-white" x-text="'GHS ' + (paymentsData.summary?.total_invoiced || 0).toFixed(2)">GHS 0.00</p>
                                </div>
                            </div>
                            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 shadow-md">
                                <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wider">Total Paid</p>
                                    </div>
                                    <p class="text-2xl font-bold text-white" x-text="'GHS ' + (paymentsData.summary?.total_paid || 0).toFixed(2)">GHS 0.00</p>
                                </div>
                            </div>
                            <div class="relative overflow-hidden rounded-2xl p-5 shadow-md"
                                :class="(paymentsData.summary?.balance_due || 0) > 0 ? 'bg-gradient-to-br from-rose-500 to-pink-600' : 'bg-gradient-to-br from-emerald-500 to-teal-600'">
                                <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-white/80 uppercase tracking-wider">Balance Due</p>
                                    </div>
                                    <p class="text-2xl font-bold text-white" x-text="'GHS ' + (paymentsData.summary?.balance_due || 0).toFixed(2)">GHS 0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Controls -->
                    <div class="px-6 pb-0">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="relative w-full sm:w-64">
                                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text" x-model="paymentSearch" @@input="paymentPage = 1" placeholder="Search payments..." class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div x-data="{ open: false }" class="relative">
                                    <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Export
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                        <button type="button" @@click="open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                            <svg class="w-4 h-4 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            CSV
                                        </button>
                                        <div class="border-t border-slate-200/50 my-1"></div>
                                        <button type="button" @@click="window.print(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Print
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="px-6 py-4">
                        <div class="rounded-xl border border-slate-200/50 relative">
                            <!-- Loading overlay -->
                            <div x-show="!paymentsLoaded" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center" style="display: none;">
                                <div class="flex items-center gap-2 text-slate-400 text-sm">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Loading payments...
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[900px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th @@click="sortPayments('payment_date')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">DATE <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'payment_date' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                            </th>
                                            <th @@click="sortPayments('amount')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">AMOUNT <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'amount' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                            </th>
                                            <th @@click="sortPayments('method_label')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">METHOD <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'method_label' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                            </th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">REFERENCE</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">INVOICE</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">RECORDED BY</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                                            <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                                        <template x-if="paymentsLoaded && filteredPayments().length === 0">
                                            <tr>
                                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 text-xs">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                        </svg>
                                                        <span>No payments found</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-for="payment in paginatedPayments()" :key="payment.id">
                                            <tr class="hover:bg-slate-50/70 transition-colors">
                                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="payment.payment_date"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="text-xs font-semibold text-emerald-700" x-text="'GHS ' + (payment.formatted_amount || formatCurrency(payment.amount))"></span>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-orange-100 text-orange-800" x-text="payment.method_label"></span>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-500 font-mono" x-text="payment.reference_number || '—'"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-500" x-text="payment.invoice_number || '—'"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="payment.recorded_by || '—'"></td>
                                                <td class="px-4 py-2.5 text-xs text-slate-500 max-w-[150px] truncate" x-text="payment.notes || '—'"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <a :href="paymentDownloadUrl(payment.id)" target="_blank"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition-colors inline-flex" title="Download receipt PDF">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                        </a>
                                                        <a :href="paymentPrintUrl(payment.id)" target="_blank"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex" title="Print receipt">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                        </a>
                                                        <button x-show="isSuperAdmin" @@click="voidPayment(payment.id)"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors inline-flex" title="Void payment">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div x-show="paymentsLoaded && filteredPayments().length > 0" class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="text-xs text-slate-600">
                                        Showing
                                        <span x-text="Math.min(((paymentPage - 1) * paymentPerPage) + 1, filteredPayments().length)"></span>
                                        to
                                        <span x-text="Math.min(paymentPage * paymentPerPage, filteredPayments().length)"></span>
                                        of
                                        <span x-text="filteredPayments().length"></span>
                                        results
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                            <div x-data="{ open: false }" class="relative">
                                                <button type="button" @@click="open = !open"
                                                    class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                                    <span x-text="paymentPerPage"></span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                                <div x-show="open" @@click.away="open = false" x-transition
                                                    class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                                    <button type="button" @@click="paymentPerPage = 10; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                                    <button type="button" @@click="paymentPerPage = 25; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                                    <button type="button" @@click="paymentPerPage = 50; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                                    <button type="button" @@click="paymentPerPage = 100; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs font-medium text-slate-600">
                                            Page <span x-text="paymentPage"></span> of <span x-text="paymentLastPage()"></span>
                                        </div>
                                        <div class="flex space-x-1">
                                            <button @@click="paymentPage = 1" :disabled="paymentPage === 1" :class="paymentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = Math.max(1, paymentPage - 1)" :disabled="paymentPage === 1" :class="paymentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = Math.min(paymentLastPage(), paymentPage + 1)" :disabled="paymentPage >= paymentLastPage()" :class="paymentPage >= paymentLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = paymentLastPage()" :disabled="paymentPage >= paymentLastPage()" :class="paymentPage >= paymentLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Record Payment Modal -->
            <div x-show="paymentForm.open" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="paymentForm.open = false"></div>
                <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Record Payment</h3>
                        </div>
                        <button @@click="paymentForm.open = false" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @@submit.prevent="submitPayment()" class="px-6 py-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Amount <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="0.01" x-model="paymentForm.amount" required
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                                <select x-model="paymentForm.payment_method" required
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300">
                                    <option value="">Select method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                                <input type="date" x-model="paymentForm.payment_date" required
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Reference Number</label>
                                <input type="text" x-model="paymentForm.reference_number"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300" placeholder="Optional">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                                <textarea x-model="paymentForm.notes" rows="2"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300" placeholder="Optional notes"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-slate-100">
                            <button type="button" @@click="paymentForm.open = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="paymentForm.submitting"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-teal-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                <svg x-show="paymentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="paymentForm.submitting ? 'Saving...' : 'Record Payment'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Void Payment Confirm Modal -->
            <div x-show="voidConfirm.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="voidConfirm.open = false"></div>
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Void this payment?</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">This action is permanent and cannot be undone. The payment record will be removed.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" @@click="voidConfirm.open = false" :disabled="voidConfirm.loading"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50">
                            Cancel
                        </button>
                        <button type="button" @@click="confirmVoidPayment()" :disabled="voidConfirm.loading"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="voidConfirm.loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="voidConfirm.loading ? 'Voiding...' : 'Yes, Void Payment'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- RECEIVING TAB                           --}}
            {{-- ═══════════════════════════════════════ --}}
            <div x-show="activeTab === 'receiving'" x-cloak>

                {{-- ── Header ───────────────────────────────── --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-200/60 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Warehouse Receiving</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Verify and record items received from driver</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold"
                              :class="{
                                  'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200': receipt?.status === 'finalized',
                                  'bg-amber-50 text-amber-700 ring-1 ring-amber-200': receipt?.status === 'discrepancy_open',
                                  'bg-slate-100 text-slate-600': !receipt?.status || receipt?.status === 'draft',
                              }">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="{
                                      'bg-emerald-500': receipt?.status === 'finalized',
                                      'bg-amber-500': receipt?.status === 'discrepancy_open',
                                      'bg-slate-400': !receipt?.status || receipt?.status === 'draft',
                                  }"></span>
                            <span x-text="receiptStatusLabel()"></span>
                        </span>
                        <button
                            type="button"
                            x-show="canReceive"
                            x-cloak
                            @@click="openFinalizeModal()"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-sm"
                            :disabled="isFinalized() || items.length === 0 || saving"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Finalize Receipt
                        </button>
                    </div>
                </div>

                {{-- ── Summary Cards ──────────────────────────── --}}
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                        <p class="text-2xl font-black text-slate-800" x-text="items.length"></p>
                        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mt-1">Total Packages</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                        <p class="text-2xl font-black text-emerald-600" x-text="items.filter(i => i.received_quantity > 0).length"></p>
                        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mt-1">Received</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                        <p class="text-2xl font-black"
                           :class="items.filter(i => i.discrepancy_type && i.discrepancy_type !== 'none').length > 0 ? 'text-amber-600' : 'text-slate-800'"
                           x-text="items.filter(i => i.discrepancy_type && i.discrepancy_type !== 'none').length"></p>
                        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mt-1">Discrepancies</p>
                    </div>
                </div>

                {{-- ── Empty State ────────────────────────────── --}}
                <template x-if="items.length === 0">
                    <div class="flex flex-col items-center justify-center py-16 text-center bg-gradient-to-br from-slate-50 to-slate-100/40 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-14 h-14 rounded-2xl bg-white shadow-sm border border-slate-200 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <p class="text-slate-600 text-sm font-bold mb-1">No items</p>
                        <p class="text-slate-400 text-xs">No shipment items found for this pickup assignment</p>
                    </div>
                </template>

                {{-- ── Packages Grid ──────────────────────────────── --}}
                <template x-if="items.length > 0">
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        <template x-for="item in items" :key="item.shipment_item_id">
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-200 overflow-hidden flex flex-col">

                                {{-- Item header --}}
                                <div class="px-4 pt-4 pb-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-900 leading-snug" x-text="item.description"></p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="text-[11px] text-slate-400">ID: <span x-text="item.shipment_item_id"></span><template x-if="item.tracking_code"><span> · <span x-text="item.tracking_code"></span></span></template></span>
                                                    <span x-show="item.fulfillment_type" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold"
                                                          :class="item.fulfillment_type === 'direct' ? 'bg-amber-100 text-amber-700' : item.fulfillment_type === 'self_pickup' ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-500'"
                                                          x-text="item.fulfillment_type === 'direct' ? 'Direct' : item.fulfillment_type === 'self_pickup' ? 'Self Pickup' : 'Warehouse'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <template x-if="item.discrepancy_type && item.discrepancy_type !== 'none'">
                                                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200" x-text="item.discrepancy_type"></span>
                                            </template>
                                            <template x-if="(!item.discrepancy_type || item.discrepancy_type === 'none') && item.received_quantity > 0">
                                                <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Done
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Qty comparison strip --}}
                                <div class="grid grid-cols-3 border-y border-slate-100 bg-slate-50/60">
                                    <div class="text-center py-2.5 px-2">
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Vendor</p>
                                        <p class="text-base font-black text-slate-700 mt-0.5" x-text="item.vendor_quantity"></p>
                                    </div>
                                    <div class="text-center py-2.5 px-2 border-x border-slate-200/70">
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Driver</p>
                                        <p class="text-base font-black mt-0.5"
                                           :class="item.driver_qty_matches_vendor === false ? 'text-amber-600' : 'text-slate-700'"
                                           x-text="item.driver_confirmed_quantity !== null ? item.driver_confirmed_quantity : '—'"></p>
                                    </div>
                                    <div class="text-center py-2.5 px-2">
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Expected</p>
                                        <p class="text-base font-black text-slate-700 mt-0.5" x-text="item.expected_quantity"></p>
                                    </div>
                                </div>

                                {{-- Receipt summary --}}
                                <div class="px-4 py-3 flex items-center flex-wrap gap-x-4 gap-y-1.5 border-b border-slate-100">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rcv</span>
                                        <span class="text-sm font-black" :class="item.received_quantity > 0 ? 'text-emerald-600' : 'text-slate-300'" x-text="item.received_quantity || 0"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dmg</span>
                                        <span class="text-sm font-black" :class="item.damaged_quantity > 0 ? 'text-rose-500' : 'text-slate-300'" x-text="item.damaged_quantity || 0"></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[11px] text-slate-500" x-text="((item.vendor_photos||[]).length + (item.driver_photos||[]).length + (item.photos||[]).length) + ' photo(s)'"></span>
                                    </div>
                                    <template x-if="item.notes">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-[11px] text-slate-500">Notes</span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Photos gallery --}}
                                <template x-if="(item.vendor_photos||[]).length > 0 || (item.driver_photos||[]).length > 0">
                                    <div class="px-4 py-3 border-b border-slate-100" x-data="{ expanded: false, lbOpen: false, lbPhotos: [], lbIndex: 0, openLightbox(photos, idx) { this.lbPhotos = photos; this.lbIndex = idx; this.lbOpen = true; }, lbPrev() { this.lbIndex = (this.lbIndex - 1 + this.lbPhotos.length) % this.lbPhotos.length; }, lbNext() { this.lbIndex = (this.lbIndex + 1) % this.lbPhotos.length; } }">
                                        <button type="button" @click="expanded = !expanded" class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 hover:text-slate-700 transition-colors w-full">
                                            <svg class="w-3 h-3 transition-transform" :class="expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span x-text="'View Photos (' + ((item.vendor_photos||[]).length + (item.driver_photos||[]).length) + ')'"></span>
                                        </button>
                                        <div x-show="expanded" x-collapse class="mt-3 space-y-3">
                                            <template x-if="(item.vendor_photos||[]).length > 0">
                                                <div>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Vendor Photos</p>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <template x-for="(url, pi) in (item.vendor_photos||[])" :key="'vp-'+pi">
                                                            <button type="button" @click="openLightbox(item.vendor_photos, pi)" class="block w-14 h-14 rounded-lg overflow-hidden border border-slate-200 hover:border-slate-400 transition-colors cursor-zoom-in">
                                                                <img :src="url" class="w-full h-full object-cover" />
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="(item.driver_photos||[]).length > 0">
                                                <div>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Driver Photos</p>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <template x-for="(photo, pi) in (item.driver_photos||[])" :key="'dp-'+pi">
                                                            <button type="button" @click="openLightbox((item.driver_photos||[]).map(p => p.url), pi)" class="block w-14 h-14 rounded-lg overflow-hidden border border-slate-200 hover:border-slate-400 transition-colors cursor-zoom-in">
                                                                <img :src="photo.url" class="w-full h-full object-cover" />
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Lightbox overlay --}}
                                        <div x-show="lbOpen" x-cloak @keydown.escape.window="lbOpen = false" @keydown.left.window="lbPrev()" @keydown.right.window="lbNext()"
                                             class="fixed inset-0 z-[99999] flex items-center justify-center" style="display:none">
                                            <div class="absolute inset-0 bg-black/90" @click="lbOpen = false"></div>
                                            {{-- Close --}}
                                            <button type="button" @click="lbOpen = false" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                            {{-- Counter --}}
                                            <div class="absolute top-4 left-4 z-10 text-white/70 text-sm font-medium" x-text="(lbIndex + 1) + ' / ' + lbPhotos.length"></div>
                                            {{-- Prev --}}
                                            <button type="button" x-show="lbPhotos.length > 1" @click.stop="lbPrev()" class="absolute left-3 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            {{-- Image --}}
                                            <img :src="lbPhotos[lbIndex]" class="relative z-[1] max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl" @click.stop />
                                            {{-- Next --}}
                                            <button type="button" x-show="lbPhotos.length > 1" @click.stop="lbNext()" class="absolute right-3 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                {{-- Barcode row --}}
                                <div class="px-4 py-3 flex items-center justify-between border-b border-slate-100">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Barcode</p>
                                        <p class="text-xs font-mono font-bold text-slate-700 mt-0.5" x-text="item.barcode_value || '—'"></p>
                                        <p class="text-[10px] text-slate-400">Prints: <span x-text="item.barcode_print_count || 0"></span></p>
                                    </div>
                                    <template x-if="item.received_quantity > 0">
                                        <button
                                            type="button"
                                            @@click="printLabel(item.shipment_item_id)"
                                            :disabled="saving"
                                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-[11px] font-semibold disabled:opacity-50 transition-colors"
                                            :class="item.barcode_value
                                                ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300'
                                                : 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                                        >
                                            <template x-if="!item.barcode_value">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </template>
                                            <template x-if="item.barcode_value">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            </template>
                                            <span x-text="item.barcode_value ? 'Reprint Label' : 'Generate & Print'"></span>
                                        </button>
                                    </template>
                                    <template x-if="!item.received_quantity || item.received_quantity === 0">
                                        <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 italic">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Receive item first
                                        </span>
                                    </template>
                                </div>

                                {{-- Receive button --}}
                                <div class="px-4 py-3 mt-auto" x-show="canReceive" x-cloak>
                                    <button
                                        type="button"
                                        @@click="openReceiveModal(item.shipment_item_id)"
                                        :disabled="isFinalized()"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed"
                                        :class="item.received_quantity > 0
                                            ? 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                            : 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-200/50 hover:from-emerald-600 hover:to-teal-600'"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <template x-if="item.received_quantity > 0">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </template>
                                            <template x-if="!item.received_quantity || item.received_quantity === 0">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </template>
                                        </svg>
                                        <span x-text="item.received_quantity > 0 ? 'Edit Receipt' : 'Receive Package'"></span>
                                    </button>
                                </div>

                            </div>
                        </template>
                    </div>
                </template>

                {{-- ═══════════════════════════════════════ --}}
                {{-- RECEIVE ITEM MODAL                      --}}
                {{-- ═══════════════════════════════════════ --}}
                <template x-if="receiveModal.open && receiveModal.itemIndex >= 0">
                    <div class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center sm:p-4 bg-slate-900/60 backdrop-blur-sm">
                        <div class="w-full sm:max-w-2xl bg-white sm:rounded-3xl shadow-2xl max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col">

                            {{-- Modal Header --}}
                            <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4 flex-shrink-0">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg shadow-emerald-200/50">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-bold text-slate-900">Receive Package</h4>
                                        <p class="text-xs text-slate-500 mt-0.5 font-medium" x-text="items[receiveModal.itemIndex]?.description"></p>
                                    </div>
                                </div>
                                <button type="button" @@click="closeReceiveModal()" class="w-8 h-8 rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Reference quantities --}}
                            <div class="grid grid-cols-3 border-b border-slate-100 bg-slate-50/60 flex-shrink-0">
                                <div class="text-center py-3">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Vendor Qty</p>
                                    <p class="text-xl font-black text-slate-700 mt-1" x-text="items[receiveModal.itemIndex]?.vendor_quantity"></p>
                                </div>
                                <div class="text-center py-3 border-x border-slate-200/70">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Driver Qty</p>
                                    <p class="text-xl font-black mt-1"
                                       :class="items[receiveModal.itemIndex]?.driver_qty_matches_vendor === false ? 'text-amber-600' : 'text-slate-700'"
                                       x-text="items[receiveModal.itemIndex]?.driver_confirmed_quantity !== null ? items[receiveModal.itemIndex]?.driver_confirmed_quantity : '—'"></p>
                                </div>
                                <div class="text-center py-3">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Expected</p>
                                    <p class="text-xl font-black text-slate-700 mt-1" x-text="items[receiveModal.itemIndex]?.expected_quantity"></p>
                                </div>
                            </div>

                            {{-- Modal Body (scrollable) --}}
                            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                                {{-- Qty inputs --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-2">Received Qty <span class="text-rose-400">*</span></label>
                                        <input
                                            type="number" min="0"
                                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                                            x-model.number="items[receiveModal.itemIndex].received_quantity"
                                            placeholder="0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-2">Damaged Qty</label>
                                        <input
                                            type="number" min="0"
                                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent transition"
                                            x-model.number="items[receiveModal.itemIndex].damaged_quantity"
                                            placeholder="0"
                                        >
                                    </div>
                                </div>

                                {{-- Condition buttons --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">Condition</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button type="button"
                                            @@click="items[receiveModal.itemIndex].condition_status = 'ok'"
                                            class="rounded-xl border-2 px-3 py-2.5 text-xs font-bold transition-all"
                                            :class="!items[receiveModal.itemIndex].condition_status || items[receiveModal.itemIndex].condition_status === 'ok'
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'">
                                            ✓ Good
                                        </button>
                                        <button type="button"
                                            @@click="items[receiveModal.itemIndex].condition_status = 'damaged'"
                                            class="rounded-xl border-2 px-3 py-2.5 text-xs font-bold transition-all"
                                            :class="items[receiveModal.itemIndex].condition_status === 'damaged'
                                                ? 'border-amber-500 bg-amber-50 text-amber-700'
                                                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'">
                                            ⚠ Damaged
                                        </button>
                                        <button type="button"
                                            @@click="items[receiveModal.itemIndex].condition_status = 'lost'"
                                            class="rounded-xl border-2 px-3 py-2.5 text-xs font-bold transition-all"
                                            :class="items[receiveModal.itemIndex].condition_status === 'lost'
                                                ? 'border-rose-500 bg-rose-50 text-rose-700'
                                                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'">
                                            ✗ Lost
                                        </button>
                                    </div>
                                </div>

                                {{-- Delivery method tag: direct vs bus courier --}}
                                <div class="rounded-xl border border-violet-100 bg-violet-50/40 p-4">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox"
                                               :checked="items[receiveModal.itemIndex].delivery_method === 'bus_handoff'"
                                               @@change="items[receiveModal.itemIndex].delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'"
                                               class="mt-0.5 w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                        <div>
                                            <span class="text-xs font-bold text-violet-700 uppercase tracking-wider">Send via Bus Courier</span>
                                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">Tag this package for bus-courier handoff. A bus-handoff driver will pick it up; they choose the station in the field.</p>
                                        </div>
                                    </label>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">Notes</label>
                                    <textarea
                                        rows="3"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition resize-none"
                                        placeholder="Any notes about this item..."
                                        x-model="items[receiveModal.itemIndex].notes"
                                    ></textarea>
                                </div>

                                {{-- Upload Photos --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">Add Photos</label>
                                    <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition-colors">
                                        <svg class="w-6 h-6 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-xs text-slate-400 font-medium">Click to upload photos</span>
                                        <span class="text-[11px] text-slate-300 mt-0.5" x-text="(pendingFiles[receiveModal.itemId] || []).length > 0 ? ((pendingFiles[receiveModal.itemId] || []).length + ' file(s) selected') : 'PNG, JPG up to 10MB'"></span>
                                        <input type="file" accept="image/*" multiple class="hidden" @@change="setItemFiles(receiveModal.itemId, $event)">
                                    </label>
                                </div>

                                {{-- Saved Photos --}}
                                <template x-if="(items[receiveModal.itemIndex]?.photos || []).length > 0">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-2">
                                            Saved Photos (<span x-text="(items[receiveModal.itemIndex]?.photos || []).length"></span>)
                                            <span class="text-[11px] text-slate-400 font-normal ml-1">— click to toggle removal</span>
                                        </label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <template x-for="photo in (items[receiveModal.itemIndex]?.photos || [])" :key="photo.id">
                                                <div class="relative group cursor-pointer" @@click="toggleRemovePhoto(receiveModal.itemId, photo.id, !isPhotoMarkedForRemoval(receiveModal.itemId, photo.id))">
                                                    <img :src="photo.url" alt="Receipt photo" class="w-full h-20 object-cover rounded-xl border transition"
                                                         :class="isPhotoMarkedForRemoval(receiveModal.itemId, photo.id) ? 'border-rose-400 opacity-50' : 'border-slate-200'">
                                                    <div class="absolute inset-0 flex items-end justify-center pb-1.5 rounded-xl"
                                                         :class="isPhotoMarkedForRemoval(receiveModal.itemId, photo.id) ? 'bg-rose-500/20' : ''">
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full transition"
                                                              :class="isPhotoMarkedForRemoval(receiveModal.itemId, photo.id)
                                                                  ? 'bg-rose-500 text-white'
                                                                  : 'bg-black/30 text-white opacity-0 group-hover:opacity-100'"
                                                              x-text="isPhotoMarkedForRemoval(receiveModal.itemId, photo.id) ? '✕ Remove' : 'Keep'"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- Reference Photos --}}
                                <template x-if="(items[receiveModal.itemIndex]?.vendor_photos || []).length > 0 || (items[receiveModal.itemIndex]?.driver_photos || []).length > 0">
                                    <div class="border-t border-slate-100 pt-5 space-y-4">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reference Photos</p>

                                        <template x-if="(items[receiveModal.itemIndex]?.vendor_photos || []).length > 0">
                                            <div>
                                                <p class="text-[11px] font-semibold text-slate-500 mb-2">Vendor (<span x-text="(items[receiveModal.itemIndex]?.vendor_photos || []).length"></span>)</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="(photoUrl, pi) in (items[receiveModal.itemIndex]?.vendor_photos || [])" :key="'v-' + pi">
                                                        <a :href="photoUrl" target="_blank" rel="noopener">
                                                            <img :src="photoUrl" alt="Vendor photo" class="h-16 w-16 object-cover rounded-xl border border-slate-200 hover:opacity-80 transition">
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="(items[receiveModal.itemIndex]?.driver_photos || []).length > 0">
                                            <div>
                                                <p class="text-[11px] font-semibold text-slate-500 mb-2">Driver (<span x-text="(items[receiveModal.itemIndex]?.driver_photos || []).length"></span>)</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="(photo, pi) in (items[receiveModal.itemIndex]?.driver_photos || [])" :key="'d-' + pi">
                                                        <a :href="photo.url" target="_blank" rel="noopener">
                                                            <img :src="photo.url" alt="Driver photo" class="h-16 w-16 object-cover rounded-xl border border-slate-200 hover:opacity-80 transition">
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                            </div>

                            {{-- Modal Footer --}}
                            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-3 flex-shrink-0 bg-white">
                                <button type="button" @@click="closeReceiveModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @@click="saveItem(receiveModal.itemId)"
                                    :disabled="saving"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-sm font-bold text-white hover:from-emerald-600 hover:to-teal-600 disabled:opacity-50 transition-all shadow-lg shadow-emerald-200/50"
                                >
                                    <template x-if="!saving">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <template x-if="saving">
                                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </template>
                                    <span x-text="saving ? 'Saving...' : 'Save & Close'"></span>
                                </button>
                            </div>

                        </div>
                    </div>
                </template>

                {{-- ═══════════════════════════════════════ --}}
                {{-- FINALIZE RECEIPT MODAL                  --}}
                {{-- ═══════════════════════════════════════ --}}
                <div x-show="showFinalizeModal" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                    <div class="w-full max-w-lg rounded-3xl bg-white border border-slate-200 shadow-2xl">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-base font-bold text-slate-900">Finalize Receipt</h4>
                            </div>
                            <button type="button" @@click="showFinalizeModal = false" class="w-8 h-8 rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <p class="text-sm text-slate-500">Finalization locks all receiving edits for this pickup. This cannot be undone.</p>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                                <textarea rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 transition resize-none" placeholder="Any final notes..." x-model="finalizeNotes"></textarea>
                            </div>
                            <template x-if="hasDiscrepancies()">
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <p class="text-xs font-bold text-amber-700">Discrepancy Detected — Approval Required</p>
                                    </div>
                                    <textarea rows="3" class="w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 transition resize-none" placeholder="Approval reason..." x-model="approvalReason"></textarea>
                                </div>
                            </template>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button type="button" @@click="showFinalizeModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                            <button type="button" @@click="finalizeReceipt()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors" :disabled="saving">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Confirm Finalize
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ═══ CREATE INVOICE MODAL ═══ -->
    <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-[120] overflow-y-auto" @@keydown.escape.window="invoiceModalOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="closeCreateInvoiceModal()"></div>
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Create Invoice</h3>
                        <p class="text-xs text-slate-500">Set fees for this shipment</p>
                    </div>
                </div>
                <button @@click="closeCreateInvoiceModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <template x-if="invoiceUiError">
                    <div class="mb-4 flex items-start gap-2.5 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-3">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span x-text="invoiceUiError"></span>
                    </div>
                </template>
                <form @@submit.prevent="createInvoice()">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pickup Fee</label>
                            <input type="number" step="0.01" min="0" x-model="invoiceForm.pickup_fee"
                                class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-400/30 focus:border-emerald-400 transition-all" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Transport Fee</label>
                            <input type="number" step="0.01" min="0" x-model="invoiceForm.transport_fee"
                                class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-400/30 focus:border-emerald-400 transition-all" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Handling Fee</label>
                            <input type="number" step="0.01" min="0" x-model="invoiceForm.handling_fee"
                                class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-400/30 focus:border-emerald-400 transition-all" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Other Fee</label>
                            <input type="number" step="0.01" min="0" x-model="invoiceForm.other_fee"
                                class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-400/30 focus:border-emerald-400 transition-all" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 p-[1px] shadow-lg shadow-emerald-500/15">
                        <div class="rounded-[11px] bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-semibold text-emerald-200 uppercase tracking-wider">Invoice Total</span>
                                <div class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span class="text-[10px] text-emerald-300 font-medium" x-text="[invoiceForm.pickup_fee, invoiceForm.transport_fee, invoiceForm.handling_fee, invoiceForm.other_fee].filter(f => parseFloat(f || 0) > 0).length + ' fee(s)'"></span>
                                </div>
                            </div>
                            <div class="flex items-baseline justify-end gap-1.5">
                                <span class="text-sm font-semibold text-emerald-200">GHS</span>
                                <span class="text-2xl font-extrabold text-white tracking-tight" x-text="(parseFloat(invoiceForm.pickup_fee || 0) + parseFloat(invoiceForm.transport_fee || 0) + parseFloat(invoiceForm.handling_fee || 0) + parseFloat(invoiceForm.other_fee || 0)).toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                        <textarea x-model="invoiceForm.notes" rows="2"
                            class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-400/30 focus:border-emerald-400 resize-none transition-all" placeholder="Optional notes..."></textarea>
                    </div>
                    <label class="flex items-center gap-3 mb-6 cursor-pointer select-none">
                        <input type="checkbox" x-model="invoiceForm.send_now"
                            class="w-4 h-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-400">
                        <span class="text-sm text-slate-700">Send invoice to vendor immediately</span>
                    </label>
                    <div class="flex justify-end gap-3">
                        <button type="button" @@click="closeCreateInvoiceModal()"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="invoiceForm.submitting"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <svg x-show="invoiceForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="invoiceForm.submitting ? 'Creating...' : (invoiceForm.send_now ? 'Create & Send' : 'Create Invoice')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>

    <!-- ═══ CANCEL INVOICE MODAL ═══ -->
    <div x-show="showCancelInvoiceModal" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="text-base font-semibold text-rose-700">Cancel Invoice</h4>
                <button type="button" @@click="showCancelInvoiceModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <p class="text-sm text-slate-600">Please provide a reason for cancelling this invoice.</p>
                <textarea rows="3" x-model="cancelInvoiceReason" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Cancellation reason (optional)"></textarea>
            </div>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" @@click="showCancelInvoiceModal = false" class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</button>
                <button type="button" @@click="confirmCancelInvoice()" :disabled="cancelInvoiceLoading" class="px-3 py-1.5 rounded-lg bg-rose-600 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50">
                    <span x-text="cancelInvoiceLoading ? 'Cancelling…' : 'Cancel Invoice'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

