@extends('admin.layouts.app')

@section('title', 'Shipment - ' . $shipment->shipment_number)
@section('breadcrumb-parent', 'Shipments')
@section('breadcrumb-current', $shipment->shipment_number)

@php
$shipmentConfig = [
    'shipment' => $shipment,
    'itemsEndpoint' => route('admin.shipments.items', $shipment),
    'trackingEndpoint' => route('admin.shipments.tracking', $shipment),
    'invoiceStoreEndpoint' => route('admin.invoices.store', $shipment),
    'invoiceSendEndpointTemplate' => route('admin.invoices.send', ['invoice' => '__INVOICE__']),
    'invoiceCancelEndpointTemplate' => route('admin.invoices.cancel', ['invoice' => '__INVOICE__']),
    'assignDriverEndpoint' => route('admin.assignments.assign', $shipment),
    'cancelAssignmentEndpointTemplate' => route('admin.assignments.cancel', ['pickupAssignment' => '__ASSIGNMENT__']),
    'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
    'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
    'receiveAssignmentEndpointTemplate' => route('admin.assignments.receive', ['pickupAssignment' => '__ASSIGNMENT__']),
    'canManage' => $canManage,
    'invoice' => $currentInvoice,
    'invoiceHistory' => $invoiceHistory,
    'assignment' => $currentAssignment,
    'assignmentHistory' => $assignmentHistory,
];
@endphp

@section('content')
<div x-data="shipmentShow()" data-shipment-show-config="{{ e(json_encode($shipmentConfig)) }}" class="space-y-6">

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
                <!-- Top Row: Back Button -->
                <div class="mb-6">
                    <a href="{{ route('admin.shipments.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Shipments</span>
                    </a>
                </div>

                <!-- Main Row: Profile LEFT, Summary + Actions RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    <!-- LEFT: Shipment Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <!-- Icon -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                                {{ strtoupper(substr($shipment->shipment_number, 0, 1)) }}
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $shipment->shipment_number }}</h1>
                                <p class="text-slate-400 text-sm mt-0.5 truncate">
                                    {{ $shipment->vendor->name }}
                                    @if($shipment->vendor->business_name)
                                        &mdash; {{ $shipment->vendor->business_name }}
                                    @endif
                                </p>
                            </div>

                            <!-- Destination Info -->
                            @if($shipment->isPerItemDestination())
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="truncate">Per-item recipients</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span>{{ number_format($itemsCount) }} item(s)</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="truncate">Per-item destinations (set on each item)</span>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="truncate">{{ $shipment->delivery_recipient_name ?: '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ $shipment->delivery_recipient_phone ?: '-' }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="truncate">
                                            {{ $shipment->deliveryRegion?->name ?? '-' }}
                                            @if($shipment->deliveryDistrict?->name), {{ $shipment->deliveryDistrict?->name }}@endif
                                            @if($shipment->delivery_town), {{ $shipment->delivery_town }}@endif
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-1.5">
                                <!-- Status Badge -->
                                @php
                                    $statusColors = match($shipment->status->value ?? $shipment->status) {
                                        'draft' => 'bg-slate-500/20 text-slate-300',
                                        'submitted', 'invoice_sent', 'invoice_accepted' => 'bg-blue-500/20 text-blue-300',
                                        'pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted' => 'bg-violet-500/20 text-violet-300',
                                        'in_transit', 'at_destination', 'out_for_delivery' => 'bg-amber-500/20 text-amber-300',
                                        'delivered' => 'bg-emerald-500/20 text-emerald-300',
                                        'cancelled' => 'bg-rose-500/20 text-rose-300',
                                        default => 'bg-slate-500/20 text-slate-300',
                                    };
                                    $dotColors = match($shipment->status->value ?? $shipment->status) {
                                        'draft' => 'bg-slate-400',
                                        'submitted', 'invoice_sent', 'invoice_accepted' => 'bg-blue-400',
                                        'pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted' => 'bg-violet-400',
                                        'in_transit', 'at_destination', 'out_for_delivery' => 'bg-amber-400',
                                        'delivered' => 'bg-emerald-400',
                                        'cancelled' => 'bg-rose-400',
                                        default => 'bg-slate-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusColors }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColors }}"></span>
                                    {{ $shipment->status->label() }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $shipment->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Action Buttons (row 1) + Summary Stats (row 2) -->
                    <div class="flex flex-col gap-3 lg:ml-auto lg:items-end">
                        <!-- Row 1: Action Buttons -->
                        @if($canManage)
                        <div class="flex items-center gap-2">
                            <button
                                x-show="shipment.status === 'invoice_accepted'"
                                x-cloak
                                @@click="activeTab = 'assignment'; loadAssignmentDependencies()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-violet-500/20 hover:bg-violet-500/30 text-violet-300 text-xs font-semibold rounded-xl border border-violet-500/30 transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Assign Driver
                            </button>
                        </div>
                        @endif

                        <!-- Row 2: Summary Stats - 4 compact cards in one row -->
                        <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap">
                            <!-- Items Count -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ number_format($itemsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Items</p>
                                </div>
                            </div>

                            <!-- Invoice Status -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-emerald-400 leading-none">{{ $currentInvoice ? $currentInvoice->status->label() : 'None' }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Invoice</p>
                                </div>
                            </div>

                            <!-- Assignment Status -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $currentAssignment ? $currentAssignment->status->label() : 'None' }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Assignment</p>
                                </div>
                            </div>

                            <!-- Created Date -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $shipment->created_at->format('M d') }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Created</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Tab Headers -->
        <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 border-b border-slate-200 px-4">
            <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
                <!-- Overview Tab -->
                <button
                    @@click="activeTab = 'overview'"
                    :class="activeTab === 'overview'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'overview' ? 'text-sky-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    <span>Overview</span>
                </button>

                <!-- Items Tab -->
                <button
                    @@click="activeTab = 'items'; loadItems()"
                    :class="activeTab === 'items'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'items' ? 'text-blue-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Items</span>
                    <span :class="activeTab === 'items' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ $itemsCount }}</span>
                </button>

                <!-- Invoice Tab -->
                <button
                    @@click="activeTab = 'invoice'"
                    :class="activeTab === 'invoice'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'invoice' ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Invoice</span>
                    <span :class="activeTab === 'invoice' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ count($invoiceHistory) }}</span>
                </button>

                <!-- Pickup Assignment Tab -->
                <button
                    @@click="activeTab = 'assignment'; if (!assignment && shipment.status === 'invoice_accepted') { loadAssignmentDependencies(); }"
                    :class="activeTab === 'assignment'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'assignment' ? 'text-violet-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Pickup Assignment</span>
                    <span :class="activeTab === 'assignment' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ $currentAssignment ? '1' : '0' }}</span>
                </button>

                <!-- Tracking Tab -->
                <button
                    @@click="activeTab = 'tracking'; loadTracking()"
                    :class="activeTab === 'tracking'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'tracking' ? 'text-amber-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Tracking</span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">

            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/40 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Shipment Profile</h3>
                                <p class="text-xs text-slate-500 mt-1">Core details and workflow context for this shipment.</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold"
                                  :class="shipmentDestinationModeBadgeClass()"
                                  x-text="shipmentDestinationModeLabel()">
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Shipment Number</p>
                                <p class="text-sm font-semibold text-slate-900 mt-1" x-text="shipment.shipment_number || '-'"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</p>
                                <p class="text-sm font-semibold text-slate-900 mt-1" x-text="shipment.status || '-'"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Vendor</p>
                                <p class="text-sm font-semibold text-slate-900 mt-1" x-text="shipment.vendor?.name || '-'"></p>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="shipment.vendor?.business_name || '-'"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Created</p>
                                <p class="text-sm font-semibold text-slate-900 mt-1" x-text="formatDateTime(shipment.created_at)"></p>
                                <p class="text-xs text-slate-500 mt-0.5">Submitted: <span x-text="formatDateTime(shipment.submitted_at)"></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h3 class="text-sm font-bold text-slate-900">Snapshot</h3>
                        <p class="text-xs text-slate-500 mt-1">Quick status of item, invoice, and assignment workflow.</p>
                        <div class="space-y-2.5 mt-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Items</span>
                                <span class="font-semibold text-slate-900">{{ number_format($itemsCount) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Invoices</span>
                                <span class="font-semibold text-slate-900">{{ count($invoiceHistory) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Assignment</span>
                                <span class="font-semibold text-slate-900">{{ $currentAssignment ? $currentAssignment->status->label() : 'Not Assigned' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-sky-100 text-sky-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </span>
                            <h3 class="text-sm font-bold text-slate-900">Pickup Details</h3>
                        </div>
                        <div class="space-y-2 mt-4 text-sm">
                            <p><span class="text-slate-500">Contact:</span> <span class="font-semibold text-slate-900" x-text="shipment.pickup_contact_name || '-'"></span></p>
                            <p><span class="text-slate-500">Phone:</span> <span class="font-semibold text-slate-900" x-text="shipment.pickup_contact_phone || '-'"></span></p>
                            <p><span class="text-slate-500">Location:</span> <span class="font-semibold text-slate-900" x-text="pickupLocationSummary()"></span></p>
                            <p><span class="text-slate-500">Instructions:</span> <span class="font-semibold text-slate-900" x-text="shipment.pickup_instructions || '-'"></span></p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <h3 class="text-sm font-bold text-slate-900">Destination Details</h3>
                        </div>

                        <template x-if="isPerItemMode()">
                            <div class="space-y-2 mt-4 text-sm">
                                <p><span class="text-slate-500">Mode:</span> <span class="font-semibold text-slate-900">Per-item destination</span></p>
                                <p><span class="text-slate-500">Recipient/Location:</span> <span class="font-semibold text-slate-900">Defined on each item</span></p>
                                <p><span class="text-slate-500">Action:</span> <span class="font-semibold text-slate-900">See Items tab for exact recipient and destination per item.</span></p>
                            </div>
                        </template>

                        <template x-if="!isPerItemMode()">
                            <div class="space-y-2 mt-4 text-sm">
                                <p><span class="text-slate-500">Recipient:</span> <span class="font-semibold text-slate-900" x-text="shipment.delivery_recipient_name || '-'"></span></p>
                                <p><span class="text-slate-500">Phone:</span> <span class="font-semibold text-slate-900" x-text="shipment.delivery_recipient_phone || '-'"></span></p>
                                <p><span class="text-slate-500">Location:</span> <span class="font-semibold text-slate-900" x-text="deliveryLocationSummary()"></span></p>
                                <p><span class="text-slate-500">Instructions:</span> <span class="font-semibold text-slate-900" x-text="shipment.delivery_instructions || '-'"></span></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Items Tab -->
            <div x-show="activeTab === 'items'" x-cloak>
                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    <p class="text-xs text-slate-600" x-show="isPerItemMode()">
                        This shipment uses <span class="font-semibold text-slate-900">per-item destination</span>. Each row below includes recipient and destination for that item.
                    </p>
                    <p class="text-xs text-slate-600" x-show="!isPerItemMode()">
                        This shipment uses <span class="font-semibold text-slate-900">single destination</span>. Items share the shipment destination.
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white relative overflow-hidden">
                    <!-- Loading Overlay -->
                    <div x-show="items.loading" class="absolute inset-0 bg-white/80 z-10 flex items-center justify-center">
                        <svg class="animate-spin w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Destination</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Tracking Code</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Images</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="items.data.length === 0 && !items.loading">
                                    <tr>
                                        <td colspan="8" class="px-4 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                                <p class="text-slate-500 text-sm font-medium">No items found</p>
                                                <p class="text-slate-400 text-xs mt-1">This shipment has no items yet</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="item in items.data" :key="item.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-xs font-medium text-slate-900" x-text="item.description || '-'"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="item.quantity"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-slate-100 text-slate-700': !item.status || item.status === 'pending',
                                                    'bg-blue-100 text-blue-700': item.status === 'processing',
                                                    'bg-emerald-100 text-emerald-700': item.status === 'delivered',
                                                    'bg-amber-100 text-amber-700': item.status === 'in_transit',
                                                    'bg-rose-100 text-rose-700': item.status === 'cancelled'
                                                }"
                                                x-text="item.status_label || item.status || 'Pending'"
                                            ></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs font-medium text-slate-900" x-text="itemDestinationTitle(item)"></div>
                                            <div class="text-[10px] text-slate-500" x-text="itemDestinationSubtitle(item)"></div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs text-slate-900" x-text="itemLocationTitle(item)"></div>
                                            <div class="text-[10px] text-slate-500" x-text="itemLocationSubtitle(item)"></div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-xs font-mono text-slate-600" x-text="item.tracking_code || '-'"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <template x-if="item.images && item.images.length > 0">
                                                <div class="flex items-center justify-center -space-x-2">
                                                    <template x-for="(img, idx) in item.images.slice(0, 3)" :key="idx">
                                                        <img :src="img.url || img" class="w-8 h-8 rounded-lg object-cover border-2 border-white shadow-sm" :alt="'Item image ' + (idx + 1)">
                                                    </template>
                                                    <template x-if="item.images.length > 3">
                                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 border-2 border-white text-[10px] font-bold text-slate-600 shadow-sm" x-text="'+' + (item.images.length - 3)"></span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!item.images || item.images.length === 0">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400 text-xs font-semibold" x-text="item.images_count || 0"></span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(item.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Invoice Tab -->
            <div x-show="activeTab === 'invoice'" x-cloak>
                <div class="max-w-5xl mx-auto">
                    <template x-if="invoiceUiError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="invoiceUiError"></div>
                    </template>
                </div>

                <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @@keydown.escape.window="closeCreateInvoiceModal()">
                    <div class="absolute inset-0 bg-slate-900/60" @@click="closeCreateInvoiceModal()"></div>
                    <div class="relative w-full max-w-2xl rounded-2xl bg-white border border-slate-200 shadow-2xl">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Create Invoice</h3>
                                <p class="text-sm text-slate-500">Set the fees for this shipment pickup</p>
                            </div>
                            <button type="button" @@click="closeCreateInvoiceModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <form @@submit.prevent="createInvoice()" class="px-6 py-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pickup Fee</label>
                                    <input type="number" step="0.01" min="0" x-model="invoiceForm.pickup_fee" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all" placeholder="0.00" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Transport Fee</label>
                                    <input type="number" step="0.01" min="0" x-model="invoiceForm.transport_fee" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all" placeholder="0.00" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Handling Fee</label>
                                    <input type="number" step="0.01" min="0" x-model="invoiceForm.handling_fee" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all" placeholder="0.00" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Other Fee</label>
                                    <input type="number" step="0.01" min="0" x-model="invoiceForm.other_fee" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all" placeholder="0.00">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                                <textarea x-model="invoiceForm.notes" rows="3" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none" placeholder="Optional notes for the vendor..."></textarea>
                            </div>
                            <div class="mb-6">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="invoiceForm.send_now" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700">Send to vendor immediately</span>
                                </label>
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @@click="closeCreateInvoiceModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                                <button type="submit" :disabled="invoiceForm.submitting" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/25 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="invoiceForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="invoiceForm.submitting ? 'Saving...' : (invoiceForm.send_now ? 'Create & Send Invoice' : 'Create Draft Invoice')"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div
                    x-show="invoiceDetailModalOpen"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @@keydown.escape.window="closeInvoiceDetailModal()"
                >
                    <div class="absolute inset-0 bg-slate-900/60" @@click="closeInvoiceDetailModal()"></div>
                    <div class="relative w-full max-w-3xl rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                        <template x-if="invoiceDetail">
                            <div>
                                <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-5 border-b border-slate-200">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-slate-900" x-text="'Invoice #' + invoiceDetail.invoice_number"></h3>
                                                <p class="text-xs text-slate-500">Invoice details and fee breakdown</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                x-show="canManage && invoiceDetail.status === 'pending'"
                                                @@click="sendInvoice(invoiceDetail.id)"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-xs font-semibold"
                                            >
                                                Send
                                            </button>
                                            <button
                                                x-show="canManage && ['pending', 'sent', 'accepted'].includes(invoiceDetail.status)"
                                                @@click="cancelInvoice(invoiceDetail.id)"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 text-xs font-semibold"
                                            >
                                                Cancel
                                            </button>
                                            <button type="button" @@click="closeInvoiceDetailModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-6 py-5 space-y-3">
                                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                        <span class="text-sm text-slate-600">Pickup Fee</span>
                                        <span class="text-sm font-semibold text-slate-900" x-text="formatMoney(invoiceDetail.pickup_fee, invoiceDetail.currency)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                        <span class="text-sm text-slate-600">Transport Fee</span>
                                        <span class="text-sm font-semibold text-slate-900" x-text="formatMoney(invoiceDetail.transport_fee, invoiceDetail.currency)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                        <span class="text-sm text-slate-600">Handling Fee</span>
                                        <span class="text-sm font-semibold text-slate-900" x-text="formatMoney(invoiceDetail.handling_fee, invoiceDetail.currency)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                        <span class="text-sm text-slate-600">Other Fee</span>
                                        <span class="text-sm font-semibold text-slate-900" x-text="formatMoney(invoiceDetail.other_fee, invoiceDetail.currency)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 bg-slate-50 -mx-6 px-6 rounded-lg">
                                        <span class="text-sm font-bold text-slate-900">Total</span>
                                        <span class="text-lg font-bold text-emerald-600" x-text="formatMoney(invoiceDetail.total_amount, invoiceDetail.currency)"></span>
                                    </div>
                                </div>

                                <div class="px-6 py-4 border-t border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Notes</p>
                                    <p class="text-sm text-slate-700" x-text="invoiceDetail.notes || '-'"></p>
                                </div>
                                <div class="px-6 py-4 border-t border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Vendor Notes</p>
                                    <p class="text-sm text-slate-700" x-text="invoiceDetail.vendor_notes || '-'"></p>
                                </div>
                                <div class="px-6 py-4 border-t border-rose-100 bg-rose-50/30">
                                    <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider mb-1">Rejection Reason</p>
                                    <p class="text-sm text-rose-700" x-text="invoiceDetail.rejection_reason || '-'"></p>
                                </div>
                                <div class="px-6 py-4 border-t border-amber-100 bg-amber-50/30">
                                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Cancellation Reason</p>
                                    <p class="text-sm text-amber-700" x-text="invoiceDetail.cancel_reason || '-'"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                                    <div class="relative flex-1 max-w-xs">
                                        <input
                                            type="text"
                                            x-model="invoiceTable.search"
                                            @@input.debounce.300ms="invoiceTable.page = 1"
                                            placeholder="Search invoices..."
                                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                                        >
                                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>

                                    <div x-data="{ open: false }" class="relative w-full sm:w-56">
                                        <button type="button" @@click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="invoiceTable.statusFilterLabel"></span>
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display: none;">
                                            <button type="button" @@click="setInvoiceStatusFilter('')" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">All statuses</button>
                                            <button type="button" @@click="setInvoiceStatusFilter('pending')" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'pending' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Pending</button>
                                            <button type="button" @@click="setInvoiceStatusFilter('sent')" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'sent' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Sent</button>
                                            <button type="button" @@click="setInvoiceStatusFilter('accepted')" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'accepted' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Accepted</button>
                                            <button type="button" @@click="setInvoiceStatusFilter('rejected')" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'rejected' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Rejected</button>
                                            <button type="button" @@click="setInvoiceStatusFilter('cancelled')" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="invoiceTable.statusFilter === 'cancelled' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">Cancelled</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <div x-data="{ open: false }" class="relative">
                                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                            </svg>
                                            View
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-52 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                            <template x-for="col in invoiceTable.columns" :key="col.key">
                                                <button type="button" @@click="toggleInvoiceColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                                    <span x-text="col.label"></span>
                                                    <svg x-show="invoiceTable.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div x-data="{ open: false }" class="relative">
                                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                            Export
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-40 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                            <button type="button" @@click="exportInvoiceData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">CSV</button>
                                            <button type="button" @@click="exportInvoiceData('json'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">JSON</button>
                                            <button type="button" @@click="exportInvoiceData('print'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">Print</button>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        x-show="canManage"
                                        @@click="openCreateInvoiceModal()"
                                        :disabled="!canCreateInvoice()"
                                        :title="!canCreateInvoice() ? activeInvoiceBlockReason() : 'Create invoice'"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Create Invoice
                                    </button>
                                    <button
                                        type="button"
                                        x-show="activeInvoice()"
                                        @@click="openActiveInvoiceModal()"
                                        class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"
                                    >
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View Active Invoice
                                    </button>
                                </div>
                            </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200/50 relative">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[980px] divide-y divide-slate-200/50 text-xs">
                                        <thead class="bg-slate-50/50">
                                            <tr>
                                                <th x-show="invoiceTable.visibleColumns.invoice_number" @@click="sortInvoice('invoice_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">INVOICE #</th>
                                                <th x-show="invoiceTable.visibleColumns.status" @@click="sortInvoice('status')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">STATUS</th>
                                                <th x-show="invoiceTable.visibleColumns.is_active" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIVE</th>
                                                <th x-show="invoiceTable.visibleColumns.total_amount" @@click="sortInvoice('total_amount')" class="px-4 py-2 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">TOTAL</th>
                                                <th x-show="invoiceTable.visibleColumns.created_at" @@click="sortInvoice('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">CREATED</th>
                                                <th x-show="invoiceTable.visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                                            <template x-if="paginatedInvoiceRows().length === 0">
                                                <tr>
                                                    <td :colspan="visibleInvoiceColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No invoices found</td>
                                                </tr>
                                            </template>
                                            <template x-for="historyInvoice in paginatedInvoiceRows()" :key="historyInvoice.id">
                                                <tr class="hover:bg-slate-50/70">
                                                    <td x-show="invoiceTable.visibleColumns.invoice_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="historyInvoice.invoice_number"></td>
                                                    <td x-show="invoiceTable.visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="invoiceStatusClass(historyInvoice.status)" x-text="historyInvoice.status_label || historyInvoice.status"></span>
                                                    </td>
                                                    <td x-show="invoiceTable.visibleColumns.is_active" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="historyInvoice.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="historyInvoice.is_active ? 'Yes' : 'No'"></span>
                                                    </td>
                                                    <td x-show="invoiceTable.visibleColumns.total_amount" class="px-4 py-2.5 whitespace-nowrap text-right text-xs text-slate-700 font-semibold" x-text="formatMoney(historyInvoice.total_amount, historyInvoice.currency)"></td>
                                                    <td x-show="invoiceTable.visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(historyInvoice.created_at)"></td>
                                                    <td x-show="invoiceTable.visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                                        <div class="inline-flex items-center gap-1">
                                                            <button @@click="viewInvoice(historyInvoice.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="View invoice">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                </svg>
                                                            </button>
                                                            <button x-show="canManage && historyInvoice.status === 'pending'" @@click="sendInvoice(historyInvoice.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Send invoice">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.868v4.264a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12a7 7 0 1114 0A7 7 0 015 12z"/>
                                                                </svg>
                                                            </button>
                                                            <button x-show="canManage && ['pending', 'sent', 'accepted'].includes(historyInvoice.status)" @@click="cancelInvoice(historyInvoice.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Cancel invoice">
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
                                                <button @@click="invoiceFirstPage()" :disabled="invoiceMeta().page === 1" :class="invoiceMeta().page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                                </button>
                                                <button @@click="invoicePreviousPage()" :disabled="invoiceMeta().page === 1" :class="invoiceMeta().page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                                </button>
                                                <button @@click="invoiceNextPage()" :disabled="invoiceMeta().page >= invoiceMeta().lastPage" :class="invoiceMeta().page >= invoiceMeta().lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                </button>
                                                <button @@click="invoiceLastPage()" :disabled="invoiceMeta().page >= invoiceMeta().lastPage" :class="invoiceMeta().page >= invoiceMeta().lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>

            <!-- Pickup Assignment Tab -->
            <div x-show="activeTab === 'assignment'" x-cloak>
                <template x-if="assignmentUiError">
                    <div class="max-w-2xl mx-auto mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="assignmentUiError"></div>
                </template>

                <!-- No Assignment - Assign Form -->
                <template x-if="!assignment && shipment.status === 'invoice_accepted'">
                    <div class="max-w-2xl mx-auto">
                        <div class="bg-slate-50/50 rounded-2xl border border-slate-200 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Assign Driver</h3>
                                    <p class="text-sm text-slate-500">Select a driver to pick up this shipment</p>
                                </div>
                            </div>

                            <form @@submit.prevent="assignDriver()">
                                <!-- Driver Select -->
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Select Driver <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <select
                                            x-model="assignmentForm.driver_id"
                                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none"
                                            required
                                        >
                                            <option value="">Choose a driver...</option>
                                            <template x-for="driver in availableDrivers" :key="driver.id">
                                                <option :value="driver.id" x-text="driver.name + ' (' + driver.phone + ')'"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <template x-if="availableDrivers.length === 0 && !assignmentForm.loadingDrivers">
                                        <p class="mt-1.5 text-xs text-amber-600">No drivers available at the moment</p>
                                    </template>
                                    <template x-if="assignmentForm.loadingDrivers">
                                        <p class="mt-1.5 text-xs text-slate-400">Loading available drivers...</p>
                                    </template>
                                </div>

                                <!-- Target Warehouse -->
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <select
                                            x-model="assignmentForm.target_warehouse_id"
                                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none"
                                            required
                                        >
                                            <option value="">Choose warehouse...</option>
                                            <template x-for="warehouse in availableWarehouses" :key="warehouse.id">
                                                <option :value="warehouse.id" x-text="warehouse.name + (warehouse.code ? ' (' + warehouse.code + ')' : '')"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <template x-if="availableWarehouses.length === 0 && !assignmentForm.loadingWarehouses">
                                        <p class="mt-1.5 text-xs text-amber-600">No active warehouses found</p>
                                    </template>
                                    <template x-if="assignmentForm.loadingWarehouses">
                                        <p class="mt-1.5 text-xs text-slate-400">Loading warehouses...</p>
                                    </template>
                                </div>

                                <!-- Notes -->
                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                                    <textarea
                                        x-model="assignmentForm.notes"
                                        rows="3"
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none"
                                        placeholder="Optional pickup notes for the driver..."
                                    ></textarea>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="assignmentForm.submitting || !assignmentForm.driver_id || !assignmentForm.target_warehouse_id"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg x-show="assignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg x-show="!assignmentForm.submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span x-text="assignmentForm.submitting ? 'Assigning...' : 'Assign Driver'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- No Assignment and status is not invoice_accepted -->
                <template x-if="!assignment && shipment.status !== 'invoice_accepted'">
                    <div class="flex flex-col items-center justify-center py-12">
                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-slate-500 text-sm font-medium">No pickup assignment yet</p>
                        <p class="text-slate-400 text-xs mt-1">A driver can be assigned once the invoice is accepted</p>
                    </div>
                </template>

                <!-- Assignment Exists - Show Details -->
                <template x-if="assignment">
                    <div class="max-w-2xl mx-auto">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <!-- Assignment Header -->
                            <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-5 border-b border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900">Pickup Assignment</h3>
                                            <p class="text-xs text-slate-500">Driver and pickup status details</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            x-show="canManage && canUnassignCurrentAssignment()"
                                            @@click="unassignDriver()"
                                            :disabled="assignmentActionLoading"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 text-xs font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span x-text="assignmentActionLoading ? 'Unassigning...' : 'Unassign Driver'"></span>
                                        </button>
                                        <button
                                            type="button"
                                            x-show="canManage && canReceiveCurrentAssignment()"
                                            @@click="receiveAtWarehouse()"
                                            :disabled="assignmentActionLoading"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-xs font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span x-text="assignmentActionLoading ? 'Saving...' : 'Receive at Warehouse'"></span>
                                        </button>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="{
                                                'bg-amber-100 text-amber-700': assignment.status === 'assigned' || assignment.status === 'pending',
                                                'bg-blue-100 text-blue-700': assignment.status === 'en_route' || assignment.status === 'arrived',
                                                'bg-emerald-100 text-emerald-700': assignment.status === 'picked_up' || assignment.status === 'completed',
                                                'bg-rose-100 text-rose-700': assignment.status === 'cancelled' || assignment.status === 'failed',
                                                'bg-slate-100 text-slate-700': !['assigned', 'pending', 'en_route', 'arrived', 'picked_up', 'completed', 'cancelled', 'failed'].includes(assignment.status)
                                            }"
                                            x-text="assignment.status_label || (assignment.status ? assignment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : 'Unknown')"
                                        ></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Info -->
                            <div class="px-6 py-5 border-b border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                                        <span x-text="assignment.driver ? assignment.driver.name.charAt(0).toUpperCase() : '?'"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900" x-text="assignment.driver ? assignment.driver.name : 'Unknown Driver'"></p>
                                        <p class="text-xs text-slate-500" x-text="assignment.driver ? assignment.driver.phone : '-'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Warehouse Routing -->
                            <div class="px-6 py-4 border-b border-slate-100">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Target Warehouse</p>
                                        <p class="font-semibold text-slate-900" x-text="assignment.target_warehouse?.name || '-'"></p>
                                        <p class="text-xs text-slate-500" x-text="assignment.target_warehouse?.code || '-'"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Received At Warehouse</p>
                                        <p class="font-semibold text-slate-900" x-text="assignment.received_warehouse?.name || '-'"></p>
                                        <p class="text-xs text-slate-500" x-text="assignment.received_warehouse?.code || '-'"></p>
                                        <p class="text-xs text-slate-500 mt-1" x-text="'Received by user ID: ' + (assignment.received_by_user_id || '-')"></p>
                                        <p class="text-xs text-slate-500" x-text="'Received at: ' + (assignment.received_at ? formatDateTime(assignment.received_at) : '-')"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Notes -->
                            <template x-if="assignment.notes">
                                <div class="px-6 py-4 border-b border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Notes</p>
                                    <p class="text-sm text-slate-700" x-text="assignment.notes"></p>
                                </div>
                            </template>

                            <template x-if="assignment.receive_notes">
                                <div class="px-6 py-4 border-b border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Warehouse Receive Notes</p>
                                    <p class="text-sm text-slate-700" x-text="assignment.receive_notes"></p>
                                </div>
                            </template>

                            <!-- Photos Count -->
                            <template x-if="assignment.photos_count > 0">
                                <div class="px-6 py-4 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm text-slate-600" x-text="assignment.photos_count + ' photo(s) attached'"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Timeline -->
                            <div class="px-6 py-5 bg-slate-50/30">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Assignment Timeline</p>
                                <div class="relative pl-6 space-y-4">
                                    <!-- Vertical Line -->
                                    <div class="absolute left-[7px] top-2 bottom-2 w-0.5 bg-slate-200"></div>

                                    <!-- Assigned -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.assigned_at ? 'bg-violet-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Assigned</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.assigned_at ? formatDateTime(assignment.assigned_at) : 'Pending'"></p>
                                        </div>
                                    </div>

                                    <!-- En Route -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.en_route_at ? 'bg-blue-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">En Route</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.en_route_at ? formatDateTime(assignment.en_route_at) : 'Pending'"></p>
                                        </div>
                                    </div>

                                    <!-- Arrived -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.arrived_at ? 'bg-amber-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Arrived</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.arrived_at ? formatDateTime(assignment.arrived_at) : 'Pending'"></p>
                                        </div>
                                    </div>

                                    <!-- Picked Up -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.picked_up_at ? 'bg-teal-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Picked Up</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.picked_up_at ? formatDateTime(assignment.picked_up_at) : 'Pending'"></p>
                                        </div>
                                    </div>

                                    <!-- Arrived Warehouse -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.arrived_warehouse_at ? 'bg-indigo-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Arrived Warehouse</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.arrived_warehouse_at ? formatDateTime(assignment.arrived_warehouse_at) : 'Pending'"></p>
                                        </div>
                                    </div>

                                    <!-- Received -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.received_at ? 'bg-emerald-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Received at Warehouse</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.received_at ? formatDateTime(assignment.received_at) : 'Pending'"></p>
                                        </div>
                                    </div>

                                    <!-- Completed -->
                                    <div class="relative flex items-start gap-3">
                                        <div class="absolute left-[-17px] w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm" :class="assignment.completed_at ? 'bg-emerald-500' : 'bg-slate-300'"></div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Completed</p>
                                            <p class="text-[10px] text-slate-500" x-text="assignment.completed_at ? formatDateTime(assignment.completed_at) : 'Pending'"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="assignmentHistory.length > 0">
                    <div class="max-w-5xl mx-auto mt-6">
                        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/50">
                                <h4 class="text-sm font-semibold text-slate-900">Assignment Audit Trail</h4>
                                <p class="text-xs text-slate-500">History of all driver assignments and unassignments for this shipment</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[1300px] divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Driver</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Target Warehouse</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Received Warehouse</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Assigned At</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Received At</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Received By</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Unassigned At</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Unassign Reason</th>
                                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="history in assignmentHistory" :key="history.id">
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <p class="text-xs font-semibold text-slate-900" x-text="history.driver_name || 'Unknown Driver'"></p>
                                                    <p class="text-[11px] text-slate-500" x-text="history.driver_phone || '-'"></p>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="assignmentStatusClass(history.status)" x-text="history.status_label || history.status"></span>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-600">
                                                    <p class="font-semibold text-slate-700" x-text="history.target_warehouse_name || '-'"></p>
                                                    <p class="text-[11px] text-slate-500" x-text="history.target_warehouse_code || '-'"></p>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-600">
                                                    <p class="font-semibold text-slate-700" x-text="history.received_warehouse_name || '-'"></p>
                                                    <p class="text-[11px] text-slate-500" x-text="history.received_warehouse_code || '-'"></p>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-600" x-text="formatDateTime(history.assigned_at)"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-600" x-text="history.received_at ? formatDateTime(history.received_at) : '-'"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-600" x-text="history.received_by_user_id || '-'"></td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-600" x-text="history.cancelled_at ? formatDateTime(history.cancelled_at) : '-'"></td>
                                                <td class="px-4 py-2.5 text-slate-600" x-text="history.cancellation_reason || '-'"></td>
                                                <td class="px-4 py-2.5 text-slate-600" x-text="[history.notes, history.receive_notes].filter(Boolean).join(' | ') || '-'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Tracking Tab -->
            <div x-show="activeTab === 'tracking'" x-cloak>
                <!-- Loading -->
                <div x-show="tracking.loading" class="flex items-center justify-center py-12">
                    <svg class="animate-spin w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Empty State -->
                <template x-if="tracking.data.length === 0 && !tracking.loading">
                    <div class="flex flex-col items-center justify-center py-12">
                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <p class="text-slate-500 text-sm font-medium">No tracking events yet</p>
                        <p class="text-slate-400 text-xs mt-1">Tracking updates will appear here as the shipment progresses</p>
                    </div>
                </template>

                <!-- Timeline -->
                <template x-if="tracking.data.length > 0 && !tracking.loading">
                    <div class="max-w-2xl mx-auto">
                        <div class="relative pl-8 space-y-6">
                            <!-- Vertical Line -->
                            <div class="absolute left-[11px] top-3 bottom-3 w-0.5 bg-slate-200"></div>

                            <template x-for="(event, index) in tracking.data" :key="index">
                                <div class="relative flex items-start gap-4">
                                    <!-- Status Dot -->
                                    <div
                                        class="absolute left-[-21px] w-4 h-4 rounded-full border-3 border-white shadow-sm"
                                        :class="{
                                            'bg-slate-400': event.status === 'draft',
                                            'bg-blue-500': ['submitted', 'invoice_sent', 'invoice_accepted'].includes(event.status),
                                            'bg-violet-500': ['pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted'].includes(event.status),
                                            'bg-amber-500': ['in_transit', 'at_destination', 'out_for_delivery'].includes(event.status),
                                            'bg-emerald-500': event.status === 'delivered',
                                            'bg-rose-500': event.status === 'cancelled'
                                        }"
                                    ></div>

                                    <!-- Event Card -->
                                    <div class="flex-1 bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm transition-shadow">
                                        <div class="flex items-center justify-between mb-1">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-slate-100 text-slate-700': event.status === 'draft',
                                                    'bg-blue-100 text-blue-700': ['submitted', 'invoice_sent', 'invoice_accepted'].includes(event.status),
                                                    'bg-violet-100 text-violet-700': ['pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted'].includes(event.status),
                                                    'bg-amber-100 text-amber-700': ['in_transit', 'at_destination', 'out_for_delivery'].includes(event.status),
                                                    'bg-emerald-100 text-emerald-700': event.status === 'delivered',
                                                    'bg-rose-100 text-rose-700': event.status === 'cancelled'
                                                }"
                                                x-text="event.status_label || event.status"
                                            ></span>
                                            <span class="text-[10px] text-slate-400 font-medium" x-text="formatDateTime(event.created_at)"></span>
                                        </div>
                                        <template x-if="event.description">
                                            <p class="text-xs text-slate-600 mt-1" x-text="event.description"></p>
                                        </template>
                                        <template x-if="event.location">
                                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                </svg>
                                                <span x-text="event.location"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>
</div>

@endsection



