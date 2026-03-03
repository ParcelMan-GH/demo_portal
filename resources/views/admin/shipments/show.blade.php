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
    'updateAssignmentEndpointTemplate' => route('admin.assignments.update', ['pickupAssignment' => '__ASSIGNMENT__']),
    'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
    'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
    'receiveAssignmentEndpointTemplate' => route('admin.assignments.receive', ['pickupAssignment' => '__ASSIGNMENT__']),
    'canManage' => $canManage,
    'isSuperAdmin' => auth('admin')->user()?->isSuperAdmin() ?? false,
    'paymentsDataEndpoint' => route('admin.shipments.payments.data', $shipment),
    'storePaymentEndpoint' => route('admin.shipments.payments.store', $shipment),
    'destroyPaymentEndpointTemplate' => route('admin.payments.destroy', ['payment' => '__PAYMENT__']),
    'invoice' => $currentInvoice,
    'invoiceHistory' => $invoiceHistory,
    'assignment' => $currentAssignment,
    'assignmentHistory' => $assignmentHistory,
];
@endphp

@section('content')
<div x-data="shipmentShow()" data-shipment-show-config="{{ json_encode($shipmentConfig) }}" class="space-y-6">

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
                            {{-- Phase 3: Assign Driver button available from SUBMITTED (no invoice required) --}}
                            <button
                                x-show="['submitted', 'invoice_accepted'].includes(shipment.status)"
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
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        <!-- Sidebar Nav -->
        <aside class="w-60 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-5 px-3">

            <!-- Section: Shipment -->
            <p class="px-2 mb-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Shipment</p>

            <!-- Overview -->
            <button @@click="activeTab = 'overview'"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'overview'
                    ? 'bg-sky-50 ring-1 ring-sky-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'overview' ? 'bg-sky-500 shadow-md shadow-sky-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'overview' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-sm transition-colors" :class="activeTab === 'overview' ? 'font-bold text-sky-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Overview</span>
            </button>

            <!-- Invoice -->
            <button @@click="activeTab = 'invoice'; loadInvoiceHistory()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'invoice'
                    ? 'bg-emerald-50 ring-1 ring-emerald-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'invoice' ? 'bg-emerald-500 shadow-md shadow-emerald-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'invoice' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'invoice' ? 'font-bold text-emerald-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Invoice</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'invoice' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ count($invoiceHistory) }}</span>
            </button>

            <!-- Assignment -->
            <button @@click="activeTab = 'assignment'"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'assignment'
                    ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'assignment' ? 'bg-violet-500 shadow-md shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'assignment' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'assignment' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Assignment</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'assignment' ? 'bg-violet-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $currentAssignment ? '1' : '0' }}</span>
            </button>

            <!-- Divider: Finance -->
            <div class="flex items-center gap-2 mt-4 mb-2 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Finance</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Payments -->
            <button @@click="activeTab = 'payments'; if (!paymentsLoaded) loadPayments()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'payments'
                    ? 'bg-teal-50 ring-1 ring-teal-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'payments' ? 'bg-teal-500 shadow-md shadow-teal-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'payments' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-sm transition-colors" :class="activeTab === 'payments' ? 'font-bold text-teal-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Payments</span>
            </button>

            <!-- Divider: Logistics -->
            <div class="flex items-center gap-2 mt-4 mb-2 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Logistics</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Tracking -->
            <button @@click="activeTab = 'tracking'; loadTracking()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl transition-all duration-150 text-left"
                :class="activeTab === 'tracking'
                    ? 'bg-amber-50 ring-1 ring-amber-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'tracking' ? 'bg-amber-500 shadow-md shadow-amber-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'tracking' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-sm transition-colors" :class="activeTab === 'tracking' ? 'font-bold text-amber-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Tracking</span>
            </button>

        </aside>

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">

            <!-- ═══════════════════════════════════════ -->
            <!-- OVERVIEW TAB                            -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

                    <!-- Left Column: Shipment Info + Items -->
                    <div class="xl:col-span-3 space-y-4">

                        <!-- Card A: Shipment Profile -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Shipment Profile</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Core details and workflow context</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold" :class="shipmentDestinationModeBadgeClass()" x-text="shipmentDestinationModeLabel()"></span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Shipment #</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="shipment.shipment_number || '—'"></p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Vendor</p>
                                    <p class="text-sm font-bold text-slate-900 truncate" x-text="shipment.vendor?.name || '—'"></p>
                                    <p class="text-xs text-slate-500 truncate" x-text="shipment.vendor?.business_name || ''"></p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Created</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="formatDateTime(shipment.created_at)"></p>
                                    <p class="text-xs text-slate-500">Submitted: <span x-text="formatDateTime(shipment.submitted_at)"></span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Card B: Pickup & Destination -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Pickup</h3>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Contact</span>
                                        <span class="font-semibold text-slate-800" x-text="shipment.pickup_contact_name || '—'"></span>
                                    </div>
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Phone</span>
                                        <span class="font-semibold text-slate-800" x-text="shipment.pickup_contact_phone || '—'"></span>
                                    </div>
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Location</span>
                                        <span class="font-semibold text-slate-800" x-text="pickupLocationSummary()"></span>
                                    </div>
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Notes</span>
                                        <span class="text-slate-600" x-text="shipment.pickup_instructions || '—'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Destination</h3>
                                </div>
                                <template x-if="isPerItemMode()">
                                    <div class="space-y-2 text-xs">
                                        <p class="text-slate-500">Mode: <span class="font-semibold text-slate-800">Per-item destination</span></p>
                                        <p class="text-slate-400 italic">Each item has its own recipient &amp; delivery address — see Items below.</p>
                                    </div>
                                </template>
                                <template x-if="!isPerItemMode()">
                                    <div class="space-y-2">
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Recipient</span>
                                            <span class="font-semibold text-slate-800" x-text="shipment.delivery_recipient_name || '—'"></span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Phone</span>
                                            <span class="font-semibold text-slate-800" x-text="shipment.delivery_recipient_phone || '—'"></span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Location</span>
                                            <span class="font-semibold text-slate-800" x-text="deliveryLocationSummary()"></span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-16 flex-shrink-0 mt-0.5">Notes</span>
                                            <span class="text-slate-600" x-text="shipment.delivery_instructions || '—'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Card C: Items -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-slate-900">Items</h3>
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600">{{ $itemsCount }}</span>
                                </div>
                                <span x-show="isPerItemMode()" class="text-[10px] text-slate-400 font-medium">Per-item destinations</span>
                                <span x-show="!isPerItemMode()" class="text-[10px] text-slate-400 font-medium">Shared destination</span>
                            </div>
                            <!-- Loading skeleton -->
                            <div x-show="items.loading" class="p-5 space-y-3">
                                <div class="animate-pulse flex items-center gap-4">
                                    <div class="h-3.5 bg-slate-100 rounded flex-1"></div>
                                    <div class="h-3.5 bg-slate-100 rounded w-10"></div>
                                    <div class="h-3.5 bg-slate-100 rounded w-20"></div>
                                </div>
                                <div class="animate-pulse flex items-center gap-4">
                                    <div class="h-3.5 bg-slate-100 rounded flex-1"></div>
                                    <div class="h-3.5 bg-slate-100 rounded w-10"></div>
                                    <div class="h-3.5 bg-slate-100 rounded w-20"></div>
                                </div>
                                <div class="animate-pulse flex items-center gap-4">
                                    <div class="h-3.5 bg-slate-100 rounded flex-1"></div>
                                    <div class="h-3.5 bg-slate-100 rounded w-10"></div>
                                    <div class="h-3.5 bg-slate-100 rounded w-20"></div>
                                </div>
                            </div>
                            <!-- Empty state -->
                            <template x-if="!items.loading && items.data.length === 0">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <svg class="w-10 h-10 text-slate-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-slate-400 text-sm">No items yet</p>
                                </div>
                            </template>
                            <!-- Item rows -->
                            <div x-show="!items.loading && items.data.length > 0" class="divide-y divide-slate-100">
                                <template x-for="item in items.data" :key="item.id">
                                    <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/70 transition-colors">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate" x-text="item.description || '—'"></p>
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                                <span x-show="item.tracking_code" class="text-[10px] text-slate-400 font-mono" x-text="item.tracking_code"></span>
                                                <span class="text-[10px] text-slate-400" x-text="itemDestinationTitle(item)"></span>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex-shrink-0" x-text="item.quantity"></span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold flex-shrink-0" :class="{
                                            'bg-slate-100 text-slate-600': !item.status || item.status === 'pending',
                                            'bg-blue-100 text-blue-700': item.status === 'processing',
                                            'bg-emerald-100 text-emerald-700': item.status === 'delivered',
                                            'bg-amber-100 text-amber-700': item.status === 'in_transit',
                                            'bg-rose-100 text-rose-700': item.status === 'cancelled'
                                        }" x-text="item.status_label || item.status || 'Pending'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Action Widgets -->
                    <div class="xl:col-span-2 space-y-4">

                        <!-- Widget 1: Invoice -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">Invoice</h3>
                                        <template x-if="activeInvoice()">
                                            <p class="text-[10px] text-slate-400" x-text="'#' + activeInvoice().invoice_number"></p>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="activeInvoice()">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold ring-1" :class="invoiceStatusClass(activeInvoice().status)" x-text="activeInvoice().status_label || activeInvoice().status"></span>
                                    </template>
                                    <button @@click="activeTab = 'invoice'" class="text-xs text-slate-400 hover:text-slate-600 font-medium transition-colors flex items-center gap-1">
                                        History <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-5">
                                <template x-if="invoiceUiError">
                                    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="invoiceUiError"></div>
                                </template>
                                <!-- No active invoice -->
                                <template x-if="!activeInvoice()">
                                    <div>
                                        <template x-if="canCreateInvoice()">
                                            <div class="text-center py-5">
                                                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-slate-500 mb-3">No invoice yet</p>
                                                <button x-show="canManage" @@click="openCreateInvoiceModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Create Invoice
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!canCreateInvoice()">
                                            <div class="text-center py-5">
                                                <svg class="w-10 h-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="text-xs text-slate-400" x-text="activeInvoiceBlockReason()"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <!-- Has active invoice -->
                                <template x-if="activeInvoice()">
                                    <div>
                                        <div class="space-y-1.5 mb-4">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-slate-500">Pickup Fee</span>
                                                <span class="font-semibold text-slate-800" x-text="formatMoney(activeInvoice().pickup_fee, activeInvoice().currency)"></span>
                                            </div>
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-slate-500">Transport Fee</span>
                                                <span class="font-semibold text-slate-800" x-text="formatMoney(activeInvoice().transport_fee, activeInvoice().currency)"></span>
                                            </div>
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-slate-500">Handling Fee</span>
                                                <span class="font-semibold text-slate-800" x-text="formatMoney(activeInvoice().handling_fee, activeInvoice().currency)"></span>
                                            </div>
                                            <template x-if="activeInvoice().other_fee > 0">
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-slate-500">Other Fee</span>
                                                    <span class="font-semibold text-slate-800" x-text="formatMoney(activeInvoice().other_fee, activeInvoice().currency)"></span>
                                                </div>
                                            </template>
                                            <div class="flex items-center justify-between py-2 border-t border-slate-100 mt-1">
                                                <span class="text-sm font-bold text-slate-900">Total</span>
                                                <span class="text-base font-bold text-emerald-600" x-text="formatMoney(activeInvoice().total_amount, activeInvoice().currency)"></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button x-show="canManage && activeInvoice().status === 'pending'" @@click="sendInvoice(activeInvoice().id)" class="flex-1 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-xs font-semibold transition-colors">Send</button>
                                            <button x-show="canManage && activeInvoice().status === 'sent'" @@click="adminAcceptInvoice(activeInvoice().id)" class="flex-1 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-xs font-semibold transition-colors">Accept</button>
                                            <button @@click="openActiveInvoiceModal()" class="flex-1 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200 text-xs font-semibold transition-colors">View</button>
                                            <button x-show="canManage && ['pending','sent','accepted'].includes(activeInvoice().status)" @@click="cancelInvoice(activeInvoice().id)" class="flex-1 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 text-xs font-semibold transition-colors">Cancel</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Widget 2: Pickup Assignment -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Assignment</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="assignment">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold ring-1" :class="{
                                            'bg-amber-50 text-amber-700 ring-amber-200': assignment.status === 'assigned',
                                            'bg-blue-50 text-blue-700 ring-blue-200': assignment.status === 'en_route',
                                            'bg-sky-50 text-sky-700 ring-sky-200': assignment.status === 'arrived',
                                            'bg-teal-50 text-teal-700 ring-teal-200': assignment.status === 'picking_up',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200': assignment.status === 'completed',
                                            'bg-rose-50 text-rose-700 ring-rose-200': assignment.status === 'cancelled',
                                            'bg-slate-100 text-slate-600 ring-slate-200': !['assigned','en_route','arrived','picking_up','completed','cancelled'].includes(assignment.status)
                                        }" x-text="assignment.status_label || assignment.status"></span>
                                    </template>
                                    <button @@click="activeTab = 'assignment'" class="text-xs text-slate-400 hover:text-slate-600 font-medium transition-colors flex items-center gap-1">
                                        History <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-5">
                                <template x-if="assignmentUiError">
                                    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="assignmentUiError"></div>
                                </template>
                                <!-- No assignment -->
                                <template x-if="!assignment">
                                    <div>
                                        <template x-if="['submitted', 'invoice_accepted'].includes(shipment.status)">
                                            <div class="text-center py-5">
                                                <div class="w-12 h-12 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-slate-500 mb-3">No driver assigned</p>
                                                <button x-show="canManage" @@click="loadAssignmentDependencies(); assignDriverModalOpen = true" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Assign Driver
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!['submitted', 'invoice_accepted'].includes(shipment.status)">
                                            <div class="text-center py-5">
                                                <svg class="w-10 h-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                </svg>
                                                <p class="text-xs text-slate-400">No pickup assignment</p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <!-- Has assignment -->
                                <template x-if="assignment">
                                    <div>
                                        <!-- Driver info -->
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-sm flex-shrink-0">
                                                <span x-text="(assignment.driver?.name || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-slate-900 truncate" x-text="assignment.driver?.name || 'Unknown'"></p>
                                                <p class="text-xs text-slate-500" x-text="assignment.driver?.phone || '—'"></p>
                                            </div>
                                        </div>
                                        <!-- Warehouse -->
                                        <div class="flex items-center gap-2 mb-3 px-3 py-2 rounded-xl bg-amber-50 border border-amber-100">
                                            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-slate-800 truncate" x-text="assignment.target_warehouse?.name || 'No warehouse'"></p>
                                                <p class="text-[10px] text-amber-600" x-text="assignment.target_warehouse?.code || ''"></p>
                                            </div>
                                        </div>
                                        <!-- Compact 7-step progress stepper -->
                                        <div class="overflow-x-auto -mx-1 px-1 mb-4">
                                            <div class="relative flex min-w-[280px]">
                                                <div class="absolute top-3 left-[calc(100%/14)] right-[calc(100%/14)] h-px bg-slate-200"></div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="assignment.assigned_at ? 'border-violet-500 bg-violet-500 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="assignment.assigned_at ? 'text-violet-700' : 'text-slate-400'">Assigned</p>
                                                </div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="assignment.en_route_at ? 'border-blue-500 bg-blue-500 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="assignment.en_route_at ? 'text-blue-700' : 'text-slate-400'">En Route</p>
                                                </div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="assignment.arrived_at ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="assignment.arrived_at ? 'text-amber-700' : 'text-slate-400'">Arrived</p>
                                                </div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="['picking_up','completed'].includes(assignment.status) ? 'border-teal-400 bg-teal-400 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="['picking_up','completed'].includes(assignment.status) ? 'text-teal-700' : 'text-slate-400'">Pick Up</p>
                                                </div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="assignment.picked_up_at ? 'border-teal-600 bg-teal-600 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="assignment.picked_up_at ? 'text-teal-700' : 'text-slate-400'">Picked</p>
                                                </div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="assignment.arrived_warehouse_at ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="assignment.arrived_warehouse_at ? 'text-indigo-700' : 'text-slate-400'">At WH</p>
                                                </div>
                                                <div class="relative flex-1 flex flex-col items-center gap-1 z-10">
                                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white" :class="assignment.received_at ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-200 text-slate-300'">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <p class="text-[8px] font-semibold text-center" :class="assignment.received_at ? 'text-emerald-700' : 'text-slate-400'">Received</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Action buttons -->
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" x-show="canManage && canEditCurrentAssignment()" @@click="openEditAssignment()" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 text-xs font-semibold shadow-sm transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" x-show="canManage && canUnassignCurrentAssignment()" @@click="unassignDriver()" :disabled="assignmentActionLoading" class="flex-1 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition-colors disabled:opacity-50">
                                                <span x-text="assignmentActionLoading ? '...' : 'Unassign'"></span>
                                            </button>
                                            <button type="button" x-show="canManage && canReceiveCurrentAssignment()" @@click="receiveAtWarehouse()" :disabled="assignmentActionLoading" class="flex-1 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold transition-colors disabled:opacity-50">
                                                <span x-text="assignmentActionLoading ? '...' : 'Receive'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- INVOICE TAB (history only)              -->
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
                    <button type="button" x-show="canManage" @@click="openCreateInvoiceModal()" :disabled="!canCreateInvoice()" :title="!canCreateInvoice() ? activeInvoiceBlockReason() : 'Create invoice'" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Invoice
                    </button>
                </div>

                <div class="space-y-4">
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

            <!-- ═══════════════════════════════════════ -->
            <!-- ASSIGNMENT TAB (history only)           -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'assignment'" x-cloak>

                <!-- Section Header -->
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
                            <p class="text-xs text-slate-500">All pickup assignments for this shipment</p>
                        </div>
                    </div>
                    <button x-show="canManage && ['submitted','invoice_accepted'].includes(shipment.status)" @@click="loadAssignmentDependencies(); assignDriverModalOpen = true" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Assign Driver
                    </button>
                </div>

                <template x-if="assignmentUiError">
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="assignmentUiError"></div>
                </template>

                <!-- Empty state when no history -->
                <template x-if="assignmentHistory.length === 0">
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <svg class="w-12 h-12 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-slate-400 text-sm font-medium">No assignment history</p>
                        <p class="text-slate-300 text-xs mt-1">Assignments will appear here once created</p>
                    </div>
                </template>

                {{-- HISTORY CARDS (kept verbatim) --}}
                {{-- Active assignment managed in Overview tab --}}

                {{-- Assignment Exists: managed in Overview tab widget --}}
                <template x-if="false">
                    <div>
                        <div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm flex-shrink-0">
                                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">Pickup Assignment</p>
                                            <p class="text-xs text-slate-500" x-text="assignment.assigned_at ? 'Assigned ' + formatDateTime(assignment.assigned_at) : 'Awaiting assignment'"></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Status Badge -->
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                            :class="{
                                                'bg-amber-50 text-amber-700 ring-amber-200': assignment.status === 'assigned',
                                                'bg-blue-50 text-blue-700 ring-blue-200': assignment.status === 'en_route',
                                                'bg-sky-50 text-sky-700 ring-sky-200': assignment.status === 'arrived',
                                                'bg-teal-50 text-teal-700 ring-teal-200': assignment.status === 'picking_up',
                                                'bg-emerald-50 text-emerald-700 ring-emerald-200': assignment.status === 'completed',
                                                'bg-rose-50 text-rose-700 ring-rose-200': assignment.status === 'cancelled',
                                                'bg-slate-100 text-slate-600 ring-slate-200': !['assigned','en_route','arrived','picking_up','completed','cancelled'].includes(assignment.status)
                                            }"
                                            x-text="assignment.status_label || (assignment.status ? assignment.status.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) : 'Unknown')"
                                        ></span>
                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            x-show="canManage && canEditCurrentAssignment()"
                                            @@click="openEditAssignment()"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 text-xs font-semibold shadow-sm transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <!-- Unassign -->
                                        <button
                                            type="button"
                                            x-show="canManage && canUnassignCurrentAssignment()"
                                            @@click="unassignDriver()"
                                            :disabled="assignmentActionLoading"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 text-xs font-semibold shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span x-text="assignmentActionLoading ? 'Removing...' : 'Unassign'"></span>
                                        </button>
                                        <!-- Receive at Warehouse -->
                                        <button
                                            type="button"
                                            x-show="canManage && canReceiveCurrentAssignment()"
                                            @@click="receiveAtWarehouse()"
                                            :disabled="assignmentActionLoading"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span x-text="assignmentActionLoading ? 'Saving...' : 'Receive at Warehouse'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Grid -->
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <!-- Driver -->
                                    <div class="flex items-center gap-3.5 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-bold text-base shadow-sm flex-shrink-0">
                                            <span x-text="(assignment.driver?.name || '?').charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Driver</p>
                                            <p class="text-sm font-bold text-slate-900 truncate" x-text="assignment.driver?.name || 'Unknown'"></p>
                                            <p class="text-xs text-slate-500" x-text="assignment.driver?.phone || '—'"></p>
                                        </div>
                                    </div>
                                    <!-- Target Warehouse -->
                                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-100">
                                        <div class="flex items-center gap-1.5 mb-2">
                                            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <p class="text-[10px] font-semibold text-amber-600 uppercase tracking-wide">Target Warehouse</p>
                                        </div>
                                        <p class="text-sm font-bold text-slate-900" x-text="assignment.target_warehouse?.name || 'Not set'"></p>
                                        <p class="text-xs text-amber-700/70" x-text="assignment.target_warehouse?.code || ''"></p>
                                    </div>
                                    <!-- Received At Warehouse -->
                                    <div class="p-4 rounded-xl border" :class="assignment.received_at ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100'">
                                        <div class="flex items-center gap-1.5 mb-2">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" :class="assignment.received_at ? 'text-emerald-500' : 'text-slate-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-[10px] font-semibold uppercase tracking-wide" :class="assignment.received_at ? 'text-emerald-600' : 'text-slate-400'">Received At</p>
                                        </div>
                                        <p class="text-sm font-bold" :class="assignment.received_at ? 'text-slate-900' : 'text-slate-400'" x-text="assignment.received_warehouse?.name || (assignment.received_at ? '—' : 'Pending')"></p>
                                        <p class="text-xs text-emerald-700/70" x-text="assignment.received_at ? formatDateTime(assignment.received_at) : ''"></p>
                                    </div>
                                </div>

                                <!-- Notes & Photos -->
                                <template x-if="assignment.notes || assignment.receive_notes || assignment.photos_count > 0">
                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <template x-if="assignment.notes">
                                            <div class="flex-1 min-w-[180px] rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Pickup Notes</p>
                                                <p class="text-sm text-slate-700" x-text="assignment.notes"></p>
                                            </div>
                                        </template>
                                        <template x-if="assignment.receive_notes">
                                            <div class="flex-1 min-w-[180px] rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Receive Notes</p>
                                                <p class="text-sm text-slate-700" x-text="assignment.receive_notes"></p>
                                            </div>
                                        </template>
                                        <template x-if="assignment.photos_count > 0">
                                            <div class="flex items-center gap-2 px-4 py-3 rounded-xl bg-slate-50 border border-slate-100">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span class="text-xs font-medium text-slate-600" x-text="assignment.photos_count + ' photo(s) attached'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- ── Timeline Section ──────────────────────────── -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h4 class="text-sm font-bold text-slate-800">Assignment Progress</h4>
                            </div>
                            <div class="overflow-x-auto -mx-2 px-2">
                                <div class="relative flex min-w-[560px]">
                                    <!-- Background connector line -->
                                    <div class="absolute top-5 left-[calc(100%/14)] right-[calc(100%/14)] h-px bg-slate-200 -z-0"></div>
                                    <!-- Step: Assigned -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="assignment.assigned_at ? 'border-violet-500 bg-violet-500 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="assignment.assigned_at ? 'text-violet-700' : 'text-slate-400'">Assigned</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight" x-text="assignment.assigned_at ? formatDateTime(assignment.assigned_at) : '—'"></p>
                                    </div>
                                    <!-- Step: En Route -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="assignment.en_route_at ? 'border-blue-500 bg-blue-500 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="assignment.en_route_at ? 'text-blue-700' : 'text-slate-400'">En Route</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight" x-text="assignment.en_route_at ? formatDateTime(assignment.en_route_at) : '—'"></p>
                                    </div>
                                    <!-- Step: Arrived -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="assignment.arrived_at ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="assignment.arrived_at ? 'text-amber-700' : 'text-slate-400'">Arrived</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight" x-text="assignment.arrived_at ? formatDateTime(assignment.arrived_at) : '—'"></p>
                                    </div>
                                    <!-- Step: Picking Up -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="['picking_up','completed'].includes(assignment.status) ? 'border-teal-400 bg-teal-400 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="['picking_up','completed'].includes(assignment.status) ? 'text-teal-700' : 'text-slate-400'">Picking Up</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight">—</p>
                                    </div>
                                    <!-- Step: Picked Up -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="assignment.picked_up_at ? 'border-teal-600 bg-teal-600 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="assignment.picked_up_at ? 'text-teal-700' : 'text-slate-400'">Picked Up</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight" x-text="assignment.picked_up_at ? formatDateTime(assignment.picked_up_at) : '—'"></p>
                                    </div>
                                    <!-- Step: At Warehouse -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="assignment.arrived_warehouse_at ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="assignment.arrived_warehouse_at ? 'text-indigo-700' : 'text-slate-400'">At Warehouse</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight" x-text="assignment.arrived_warehouse_at ? formatDateTime(assignment.arrived_warehouse_at) : '—'"></p>
                                    </div>
                                    <!-- Step: Received -->
                                    <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm bg-white"
                                             :class="assignment.received_at ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-200 text-slate-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-center leading-tight" :class="assignment.received_at ? 'text-emerald-700' : 'text-slate-400'">Received</p>
                                        <p class="text-[10px] text-slate-400 text-center leading-tight" x-text="assignment.received_at ? formatDateTime(assignment.received_at) : '—'"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                </template>

                <!-- ── Assignment History Cards ───────────────────────── -->
                <div class="space-y-3">
                    <template x-for="history in assignmentHistory" :key="history.id">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            <!-- Card Top Bar: Driver + Status -->
                            <div class="flex items-center gap-4 px-5 pt-5 pb-4">
                                <!-- Avatar -->
                                <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-base shadow-sm"
                                    :class="history.status === 'cancelled' ? 'bg-gradient-to-br from-rose-400 to-rose-500' : 'bg-gradient-to-br from-violet-500 to-purple-600'">
                                    <span x-text="(history.driver_name || '?').charAt(0).toUpperCase()"></span>
                                </div>
                                <!-- Name + phone -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate" x-text="history.driver_name || 'Unknown Driver'"></p>
                                    <p class="text-xs text-slate-400" x-text="history.driver_phone || '—'"></p>
                                </div>
                                <!-- Status badge -->
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold flex-shrink-0 ring-1"
                                    :class="assignmentStatusClass(history.status)"
                                    x-text="history.status_label || history.status"></span>
                            </div>

                            <!-- Divider + Meta Row -->
                            <div class="border-t border-slate-100 px-5 py-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 bg-slate-50/60">
                                <!-- Warehouse -->
                                <span x-show="history.target_warehouse_name" class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span class="font-semibold text-slate-700" x-text="history.target_warehouse_name"></span>
                                    <span x-show="history.target_warehouse_code" class="text-slate-400" x-text="'(' + history.target_warehouse_code + ')'"></span>
                                </span>
                                <!-- Assigned At -->
                                <span x-show="history.assigned_at" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Assigned: <span class="font-medium text-slate-600" x-text="formatDateTime(history.assigned_at)"></span>
                                </span>
                                <!-- Received At -->
                                <span x-show="history.received_at" class="inline-flex items-center gap-1 text-xs text-emerald-600">
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Received: <span class="font-medium" x-text="formatDateTime(history.received_at)"></span>
                                </span>
                                <!-- Cancelled At -->
                                <span x-show="history.cancelled_at" class="inline-flex items-center gap-1 text-xs text-rose-500">
                                    <svg class="w-3 h-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Cancelled: <span class="font-medium" x-text="formatDateTime(history.cancelled_at)"></span>
                                </span>
                            </div>

                            <!-- Cancellation Reason -->
                            <template x-if="history.cancellation_reason">
                                <div class="px-5 py-2.5 bg-rose-50 border-t border-rose-100 flex items-start gap-2 text-xs text-rose-600">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                    <span x-text="history.cancellation_reason"></span>
                                </div>
                            </template>
                            <!-- Notes -->
                            <template x-if="history.notes || history.receive_notes">
                                <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 text-xs text-slate-400 italic" x-text="[history.notes, history.receive_notes].filter(Boolean).join(' · ')"></div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Assign Driver + Edit Assignment modals are placed globally below --}}
                <template x-if="false"><div><div>
                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Assign Driver</h3>
                                    <p class="text-xs text-slate-500">Select driver and target warehouse</p>
                                </div>
                            </div>
                            <button @@click="assignDriverModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="px-6 py-5">
                            <form @@submit.prevent="assignDriver()">
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Select Driver <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <select x-model="assignmentForm.driver_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none" required>
                                            <option value="">Choose a driver...</option>
                                            <template x-for="driver in availableDrivers" :key="driver.id">
                                                <option :value="driver.id" x-text="driver.name + ' (' + driver.phone + ')'"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <template x-if="availableDrivers.length === 0 && !assignmentForm.loadingDrivers">
                                        <p class="mt-1.5 text-xs text-amber-600">No available drivers right now</p>
                                    </template>
                                    <template x-if="assignmentForm.loadingDrivers">
                                        <p class="mt-1.5 text-xs text-slate-400">Loading drivers...</p>
                                    </template>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <select x-model="assignmentForm.target_warehouse_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none" required>
                                            <option value="">Choose warehouse...</option>
                                            <template x-for="warehouse in availableWarehouses" :key="warehouse.id">
                                                <option :value="warehouse.id" x-text="warehouse.name + (warehouse.code ? ' (' + warehouse.code + ')' : '')"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <template x-if="availableWarehouses.length === 0 && !assignmentForm.loadingWarehouses">
                                        <p class="mt-1.5 text-xs text-amber-600">No active warehouses found</p>
                                    </template>
                                    <template x-if="assignmentForm.loadingWarehouses">
                                        <p class="mt-1.5 text-xs text-slate-400">Loading warehouses...</p>
                                    </template>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <textarea x-model="assignmentForm.notes" rows="3" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none" placeholder="Optional pickup notes for the driver..."></textarea>
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" @@click="assignDriverModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="assignmentForm.submitting || !assignmentForm.driver_id || !assignmentForm.target_warehouse_id" class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                        <svg x-show="assignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span x-text="assignmentForm.submitting ? 'Assigning...' : 'Assign Driver'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ══ MODAL: Edit Assignment ════════════════════════════ -->
                <div x-show="editAssignmentOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="editAssignmentOpen = false"></div>
                    <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Edit Assignment</h3>
                                    <p class="text-xs text-slate-500">Change driver or target warehouse</p>
                                </div>
                            </div>
                            <button @@click="editAssignmentOpen = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="px-6 py-5">
                            <form @@submit.prevent="updateAssignment()">
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Driver</label>
                                    <div class="relative">
                                        <select x-model="editAssignmentForm.driver_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                                            <option value="">Choose a driver...</option>
                                            <template x-for="driver in availableDriversForEdit" :key="driver.id">
                                                <option :value="driver.id" x-text="driver.name + ' (' + driver.phone + ')'"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <template x-if="editAssignmentForm.loadingDrivers">
                                        <p class="mt-1.5 text-xs text-slate-400">Loading drivers...</p>
                                    </template>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse</label>
                                    <div class="relative">
                                        <select x-model="editAssignmentForm.target_warehouse_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                                            <option value="">Choose warehouse...</option>
                                            <template x-for="warehouse in availableWarehouses" :key="warehouse.id">
                                                <option :value="warehouse.id" x-text="warehouse.name + (warehouse.code ? ' (' + warehouse.code + ')' : '')"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <template x-if="editAssignmentForm.loadingWarehouses">
                                        <p class="mt-1.5 text-xs text-slate-400">Loading warehouses...</p>
                                    </template>
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" @@click="editAssignmentOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="editAssignmentForm.submitting" class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                        <svg x-show="editAssignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <svg x-show="!editAssignmentForm.submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span x-text="editAssignmentForm.submitting ? 'Updating...' : 'Update Assignment'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>{{-- end x-if="false" dead modal block --}}

            </div>

            <!-- Payments Tab -->
            <div x-show="activeTab === 'payments'" x-cloak>

                <!-- Section Header -->
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Payment History</h3>
                            <p class="text-xs text-slate-500">Track all payments recorded for this shipment</p>
                        </div>
                    </div>
                    <button x-show="canManage && !paymentForm.open" @@click="paymentForm.open = true; paymentForm.payment_date = new Date().toISOString().split('T')[0]"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Record Payment
                    </button>
                </div>

                <!-- Gradient Stat Cards -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <!-- Total Invoiced -->
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
                    <!-- Total Paid -->
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
                    <!-- Balance Due -->
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

                <!-- Record Payment Form -->
                <div x-show="paymentForm.open" x-cloak class="bg-slate-50 rounded-2xl border border-slate-200 p-6 mb-6">
                    <h4 class="text-sm font-bold text-slate-800 mb-4">Record Payment</h4>
                    <form @@submit.prevent="submitPayment()">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Amount <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="0.01" x-model="paymentForm.amount" required
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                                <select x-model="paymentForm.payment_method" required
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300">
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
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Reference Number</label>
                                <input type="text" x-model="paymentForm.reference_number"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300" placeholder="Optional">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                                <textarea x-model="paymentForm.notes" rows="2"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300" placeholder="Optional notes"></textarea>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-4">
                            <button type="submit" :disabled="paymentForm.submitting"
                                class="px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-colors disabled:opacity-50">
                                <span x-text="paymentForm.submitting ? 'Saving...' : 'Record Payment'"></span>
                            </button>
                            <button type="button" @@click="paymentForm.open = false"
                                class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h4 class="text-sm font-bold text-slate-800">Transactions</h4>
                    </div>

                    <!-- Loading -->
                    <div x-show="!paymentsLoaded" class="flex items-center justify-center py-10 text-slate-400 text-sm gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Loading payments...
                    </div>

                    <!-- Empty state -->
                    <div x-show="paymentsLoaded && paymentsData.payments && !paymentsData.payments.length"
                        class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <svg class="w-10 h-10 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm font-medium">No payments recorded</p>
                    </div>

                    <!-- Table -->
                    <table x-show="paymentsLoaded && paymentsData.payments && paymentsData.payments.length" class="w-full">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-100">
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Method</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Invoice</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Recorded By</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="payment in paymentsData.payments" :key="payment.id">
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-slate-600" x-text="payment.payment_date"></td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-semibold text-emerald-700" x-text="'GHS ' + payment.formatted_amount"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700" x-text="payment.method_label"></span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500 font-mono" x-text="payment.reference_number || '—'"></td>
                                    <td class="px-4 py-3 text-xs text-slate-500" x-text="payment.invoice_number || '—'"></td>
                                    <td class="px-4 py-3 text-xs text-slate-600" x-text="payment.recorded_by || '—'"></td>
                                    <td class="px-4 py-3 text-xs text-slate-500" x-text="payment.notes || '—'"></td>
                                    <td class="px-4 py-3">
                                        <button x-show="isSuperAdmin" @@click="voidPayment(payment.id)"
                                            class="p-1 rounded text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Void payment">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tracking Tab -->
            <div x-show="activeTab === 'tracking'" x-cloak>

                <!-- Section Header -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Shipment Timeline</h3>
                        <p class="text-xs text-slate-500">Complete status history for this shipment</p>
                    </div>
                </div>

                <!-- Loading -->
                <div x-show="tracking.loading" class="flex flex-col items-center justify-center py-16 gap-3">
                    <svg class="animate-spin w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-xs text-slate-400 font-medium">Loading timeline...</p>
                </div>

                <!-- Empty State -->
                <template x-if="tracking.data.length === 0 && !tracking.loading">
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-slate-600 text-sm font-semibold mb-1">No tracking events yet</p>
                        <p class="text-slate-400 text-xs max-w-xs">Status updates will appear here as this shipment moves through the delivery pipeline</p>
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

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- GLOBAL MODALS — inside x-data scope, outside the card             --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}

    <!-- ══ MODAL: Create Invoice ════════════════════════════════════════ -->
    <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="invoiceModalOpen = false"></div>
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
                <button @@click="invoiceModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
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
                        <button type="button" @@click="invoiceModalOpen = false"
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

    <!-- ══ MODAL: Invoice Detail ════════════════════════════════════════ -->
    <div x-show="invoiceDetailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="invoiceDetailModalOpen = false"></div>
        <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
            <template x-if="invoiceDetail">
                <div>
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900" x-text="'Invoice ' + (invoiceDetail.invoice_number || '')"></h3>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1"
                                    :class="invoiceStatusClass(invoiceDetail.status)"
                                    x-text="invoiceDetail.status_label || invoiceDetail.status"></span>
                            </div>
                        </div>
                        <button @@click="invoiceDetailModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <!-- Fee breakdown -->
                        <div class="bg-slate-50 rounded-2xl border border-slate-100 overflow-hidden mb-5">
                            <div class="divide-y divide-slate-100">
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm text-slate-600">Pickup Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="'GHS ' + (Number(invoiceDetail.pickup_fee) || 0).toFixed(2)"></span>
                                </div>
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm text-slate-600">Transport Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="'GHS ' + (Number(invoiceDetail.transport_fee) || 0).toFixed(2)"></span>
                                </div>
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm text-slate-600">Handling Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="'GHS ' + (Number(invoiceDetail.handling_fee) || 0).toFixed(2)"></span>
                                </div>
                                <template x-if="invoiceDetail.other_fee && Number(invoiceDetail.other_fee) > 0">
                                    <div class="flex items-center justify-between px-5 py-3">
                                        <span class="text-sm text-slate-600">Other Fee</span>
                                        <span class="text-sm font-semibold text-slate-900" x-text="'GHS ' + Number(invoiceDetail.other_fee).toFixed(2)"></span>
                                    </div>
                                </template>
                                <div class="flex items-center justify-between px-5 py-3.5 bg-white">
                                    <span class="text-sm font-bold text-slate-900">Total</span>
                                    <span class="text-base font-bold text-slate-900" x-text="'GHS ' + (Number(invoiceDetail.total_amount) || 0).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                        <!-- Notes -->
                        <template x-if="invoiceDetail.notes">
                            <div class="mb-5 bg-amber-50 rounded-xl border border-amber-100 px-4 py-3">
                                <p class="text-[10px] font-semibold text-amber-600 uppercase tracking-wide mb-1">Notes</p>
                                <p class="text-sm text-slate-700" x-text="invoiceDetail.notes"></p>
                            </div>
                        </template>
                        <!-- Dates -->
                        <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500 mb-5">
                            <span x-show="invoiceDetail.created_at">Created: <span class="font-medium text-slate-700" x-text="formatDateTime(invoiceDetail.created_at)"></span></span>
                            <span x-show="invoiceDetail.sent_at">Sent: <span class="font-medium text-slate-700" x-text="formatDateTime(invoiceDetail.sent_at)"></span></span>
                            <span x-show="invoiceDetail.accepted_at">Accepted: <span class="font-medium text-slate-700" x-text="formatDateTime(invoiceDetail.accepted_at)"></span></span>
                            <span x-show="invoiceDetail.cancelled_at" class="text-rose-600">Cancelled: <span x-text="formatDateTime(invoiceDetail.cancelled_at)"></span></span>
                        </div>
                        <!-- Actions -->
                        <div class="flex flex-wrap justify-end gap-2">
                            <button type="button" @@click="invoiceDetailModalOpen = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                                Close
                            </button>
                            <button type="button" x-show="canManage && invoiceDetail.status === 'pending'"
                                @@click="sendInvoice(invoiceDetail.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Send to Vendor
                            </button>
                            <button type="button" x-show="canManage && invoiceDetail.status === 'sent'"
                                @@click="adminAcceptInvoice(invoiceDetail.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Admin Accept
                            </button>
                            <button type="button" x-show="canManage && ['pending','sent'].includes(invoiceDetail.status)"
                                @@click="cancelInvoice(invoiceDetail.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Cancel Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ══ MODAL: Assign Driver ════════════════════════════════════════ -->
    <div x-show="assignDriverModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="assignDriverModalOpen = false"></div>
        <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Assign Driver</h3>
                        <p class="text-xs text-slate-500">Select driver and target warehouse</p>
                    </div>
                </div>
                <button @@click="assignDriverModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <form @@submit.prevent="assignDriver()">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Select Driver <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select x-model="assignmentForm.driver_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none" required>
                                <option value="">Choose a driver...</option>
                                <template x-for="driver in availableDrivers" :key="driver.id">
                                    <option :value="driver.id" x-text="driver.name + ' (' + driver.phone + ')'"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <template x-if="availableDrivers.length === 0 && !assignmentForm.loadingDrivers">
                            <p class="mt-1.5 text-xs text-amber-600">No available drivers right now</p>
                        </template>
                        <template x-if="assignmentForm.loadingDrivers">
                            <p class="mt-1.5 text-xs text-slate-400">Loading drivers...</p>
                        </template>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select x-model="assignmentForm.target_warehouse_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none" required>
                                <option value="">Choose warehouse...</option>
                                <template x-for="warehouse in availableWarehouses" :key="warehouse.id">
                                    <option :value="warehouse.id" x-text="warehouse.name + (warehouse.code ? ' (' + warehouse.code + ')' : '')"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <template x-if="availableWarehouses.length === 0 && !assignmentForm.loadingWarehouses">
                            <p class="mt-1.5 text-xs text-amber-600">No active warehouses found</p>
                        </template>
                        <template x-if="assignmentForm.loadingWarehouses">
                            <p class="mt-1.5 text-xs text-slate-400">Loading warehouses...</p>
                        </template>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                        <textarea x-model="assignmentForm.notes" rows="3" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none" placeholder="Optional pickup notes for the driver..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @@click="assignDriverModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="assignmentForm.submitting || !assignmentForm.driver_id || !assignmentForm.target_warehouse_id" class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <svg x-show="assignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="assignmentForm.submitting ? 'Assigning...' : 'Assign Driver'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ MODAL: Edit Assignment ══════════════════════════════════════ -->
    <div x-show="editAssignmentOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="editAssignmentOpen = false"></div>
        <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Edit Assignment</h3>
                        <p class="text-xs text-slate-500">Change driver or target warehouse</p>
                    </div>
                </div>
                <button @@click="editAssignmentOpen = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <form @@submit.prevent="updateAssignment()">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Driver</label>
                        <div class="relative">
                            <select x-model="editAssignmentForm.driver_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                                <option value="">Choose a driver...</option>
                                <template x-for="driver in availableDriversForEdit" :key="driver.id">
                                    <option :value="driver.id" x-text="driver.name + ' (' + driver.phone + ')'"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <template x-if="editAssignmentForm.loadingDrivers">
                            <p class="mt-1.5 text-xs text-slate-400">Loading drivers...</p>
                        </template>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse</label>
                        <div class="relative">
                            <select x-model="editAssignmentForm.target_warehouse_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm text-slate-900 transition-all cursor-pointer appearance-none">
                                <option value="">Choose warehouse...</option>
                                <template x-for="warehouse in availableWarehouses" :key="warehouse.id">
                                    <option :value="warehouse.id" x-text="warehouse.name + (warehouse.code ? ' (' + warehouse.code + ')' : '')"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <template x-if="editAssignmentForm.loadingWarehouses">
                            <p class="mt-1.5 text-xs text-slate-400">Loading warehouses...</p>
                        </template>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @@click="editAssignmentOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editAssignmentForm.submitting" class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <svg x-show="editAssignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg x-show="!editAssignmentForm.submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="editAssignmentForm.submitting ? 'Updating...' : 'Update Assignment'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection



