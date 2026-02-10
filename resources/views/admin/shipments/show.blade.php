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
    'assignDriverEndpoint' => route('admin.assignments.assign', $shipment),
    'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
    'canManage' => $canManage,
    'invoice' => $shipment->invoice,
    'assignment' => $shipment->pickupAssignment,
];
@endphp

@section('content')
<script>
    window.shipmentShowConfig = {!! json_encode($shipmentConfig) !!};
</script>
<div x-data="shipmentShow()" class="space-y-6">

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

                            <!-- Recipient Info -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="truncate">{{ $shipment->recipient_name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $shipment->recipient_phone }}
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="truncate">
                                        {{ $shipment->region ?? '-' }}
                                        @if($shipment->district), {{ $shipment->district }}@endif
                                        @if($shipment->town), {{ $shipment->town }}@endif
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <!-- Status Badge -->
                                @php
                                    $statusColors = match($shipment->status->value ?? $shipment->status) {
                                        'draft' => 'bg-slate-500/20 text-slate-300',
                                        'submitted', 'invoice_sent', 'invoice_accepted' => 'bg-blue-500/20 text-blue-300',
                                        'pickup_assigned', 'picked_up', 'at_warehouse', 'sorted' => 'bg-violet-500/20 text-violet-300',
                                        'in_transit', 'at_destination', 'out_for_delivery' => 'bg-amber-500/20 text-amber-300',
                                        'delivered' => 'bg-emerald-500/20 text-emerald-300',
                                        'cancelled' => 'bg-rose-500/20 text-rose-300',
                                        default => 'bg-slate-500/20 text-slate-300',
                                    };
                                    $dotColors = match($shipment->status->value ?? $shipment->status) {
                                        'draft' => 'bg-slate-400',
                                        'submitted', 'invoice_sent', 'invoice_accepted' => 'bg-blue-400',
                                        'pickup_assigned', 'picked_up', 'at_warehouse', 'sorted' => 'bg-violet-400',
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
                                x-show="shipment.status === 'submitted'"
                                x-cloak
                                @@click="activeTab = 'invoice'"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 text-xs font-semibold rounded-xl border border-emerald-500/30 transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Create Invoice
                            </button>
                            <button
                                x-show="shipment.status === 'invoice_accepted'"
                                x-cloak
                                @@click="activeTab = 'assignment'; loadAvailableDrivers()"
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
                                    <p class="text-lg font-bold text-emerald-400 leading-none">{{ $shipment->invoice ? $shipment->invoice->status->label() : 'None' }}</p>
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
                                    <p class="text-lg font-bold text-white leading-none">{{ $shipment->pickupAssignment ? $shipment->pickupAssignment->status->label() : 'None' }}</p>
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
                    <span :class="activeTab === 'invoice' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ $shipment->invoice ? '1' : '0' }}</span>
                </button>

                <!-- Pickup Assignment Tab -->
                <button
                    @@click="activeTab = 'assignment'; if (!assignment && shipment.status === 'invoice_accepted') { loadAvailableDrivers(); }"
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
                    <span :class="activeTab === 'assignment' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ $shipment->pickupAssignment ? '1' : '0' }}</span>
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

            <!-- Items Tab -->
            <div x-show="activeTab === 'items'" x-cloak>
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
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Tracking Code</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Images</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="items.data.length === 0 && !items.loading">
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center">
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
                                            <span class="text-xs font-mono text-slate-600" x-text="item.tracking_code || '-'"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <template x-if="item.images && item.images.length > 0">
                                                <div class="flex items-center justify-center -space-x-2">
                                                    <template x-for="(img, idx) in item.images.slice(0, 3)" :key="idx">
                                                        <img :src="img" class="w-8 h-8 rounded-lg object-cover border-2 border-white shadow-sm" :alt="'Item image ' + (idx + 1)">
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
                <!-- No Invoice - Create Form -->
                <template x-if="!invoice">
                    <div class="max-w-2xl mx-auto">
                        <div class="bg-slate-50/50 rounded-2xl border border-slate-200 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Create Invoice</h3>
                                    <p class="text-sm text-slate-500">Set the fees for this shipment pickup</p>
                                </div>
                            </div>

                            <form @@submit.prevent="createInvoice()">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <!-- Pickup Fee -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Pickup Fee</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model="invoiceForm.pickup_fee"
                                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                            placeholder="0.00"
                                        >
                                    </div>

                                    <!-- Transport Fee -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Transport Fee</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model="invoiceForm.transport_fee"
                                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                            placeholder="0.00"
                                        >
                                    </div>

                                    <!-- Handling Fee -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Handling Fee</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model="invoiceForm.handling_fee"
                                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                            placeholder="0.00"
                                        >
                                    </div>

                                    <!-- Other Fee -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Other Fee</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model="invoiceForm.other_fee"
                                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                            placeholder="0.00"
                                        >
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                                    <textarea
                                        x-model="invoiceForm.notes"
                                        rows="3"
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none"
                                        placeholder="Optional notes for the vendor..."
                                    ></textarea>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="invoiceForm.submitting"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg x-show="invoiceForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg x-show="!invoiceForm.submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span x-text="invoiceForm.submitting ? 'Creating...' : 'Create Invoice'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- Invoice Exists - Show Details -->
                <template x-if="invoice">
                    <div class="max-w-2xl mx-auto">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <!-- Invoice Header -->
                            <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-5 border-b border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900" x-text="'Invoice #' + invoice.invoice_number"></h3>
                                            <p class="text-xs text-slate-500">Invoice details and fee breakdown</p>
                                        </div>
                                    </div>
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="{
                                            'bg-amber-100 text-amber-700': invoice.status === 'pending' || invoice.status === 'sent',
                                            'bg-emerald-100 text-emerald-700': invoice.status === 'accepted',
                                            'bg-rose-100 text-rose-700': invoice.status === 'rejected',
                                            'bg-slate-100 text-slate-700': !['pending', 'sent', 'accepted', 'rejected'].includes(invoice.status)
                                        }"
                                        x-text="invoice.status_label || (invoice.status ? invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) : 'Unknown')"
                                    ></span>
                                </div>
                            </div>

                            <!-- Fee Breakdown -->
                            <div class="px-6 py-5 space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-sm text-slate-600">Pickup Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="invoice.pickup_fee ? parseFloat(invoice.pickup_fee).toFixed(2) : '0.00'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-sm text-slate-600">Transport Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="invoice.transport_fee ? parseFloat(invoice.transport_fee).toFixed(2) : '0.00'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-sm text-slate-600">Handling Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="invoice.handling_fee ? parseFloat(invoice.handling_fee).toFixed(2) : '0.00'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-sm text-slate-600">Other Fee</span>
                                    <span class="text-sm font-semibold text-slate-900" x-text="invoice.other_fee ? parseFloat(invoice.other_fee).toFixed(2) : '0.00'"></span>
                                </div>
                                <div class="flex items-center justify-between py-3 bg-slate-50 -mx-6 px-6 rounded-lg">
                                    <span class="text-sm font-bold text-slate-900">Total</span>
                                    <span class="text-lg font-bold text-emerald-600" x-text="(parseFloat(invoice.pickup_fee || 0) + parseFloat(invoice.transport_fee || 0) + parseFloat(invoice.handling_fee || 0) + parseFloat(invoice.other_fee || 0)).toFixed(2)"></span>
                                </div>
                            </div>

                            <!-- Notes -->
                            <template x-if="invoice.notes">
                                <div class="px-6 py-4 border-t border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Notes</p>
                                    <p class="text-sm text-slate-700" x-text="invoice.notes"></p>
                                </div>
                            </template>

                            <!-- Vendor Notes / Rejection Reason -->
                            <template x-if="invoice.vendor_notes">
                                <div class="px-6 py-4 border-t border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Vendor Notes</p>
                                    <p class="text-sm text-slate-700" x-text="invoice.vendor_notes"></p>
                                </div>
                            </template>

                            <template x-if="invoice.rejection_reason">
                                <div class="px-6 py-4 border-t border-rose-100 bg-rose-50/30">
                                    <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider mb-1">Rejection Reason</p>
                                    <p class="text-sm text-rose-700" x-text="invoice.rejection_reason"></p>
                                </div>
                            </template>

                            <!-- Timestamps -->
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Timeline</p>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <template x-if="invoice.sent_at">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-medium">Sent</p>
                                            <p class="text-xs text-slate-700 font-medium" x-text="formatDateTime(invoice.sent_at)"></p>
                                        </div>
                                    </template>
                                    <template x-if="invoice.accepted_at">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-medium">Accepted</p>
                                            <p class="text-xs text-emerald-700 font-medium" x-text="formatDateTime(invoice.accepted_at)"></p>
                                        </div>
                                    </template>
                                    <template x-if="invoice.rejected_at">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-medium">Rejected</p>
                                            <p class="text-xs text-rose-700 font-medium" x-text="formatDateTime(invoice.rejected_at)"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pickup Assignment Tab -->
            <div x-show="activeTab === 'assignment'" x-cloak>
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
                                        :disabled="assignmentForm.submitting || !assignmentForm.driver_id"
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

                            <!-- Assignment Notes -->
                            <template x-if="assignment.notes">
                                <div class="px-6 py-4 border-b border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Notes</p>
                                    <p class="text-sm text-slate-700" x-text="assignment.notes"></p>
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
                                            'bg-violet-500': ['pickup_assigned', 'picked_up', 'at_warehouse', 'sorted'].includes(event.status),
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
                                                    'bg-violet-100 text-violet-700': ['pickup_assigned', 'picked_up', 'at_warehouse', 'sorted'].includes(event.status),
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

@push('scripts')
<script>
function shipmentShow() {
    return {
        config: {},
        shipment: {},
        canManage: false,
        invoice: null,
        assignment: null,

        activeTab: 'items',

        // Items state
        items: {
            data: [],
            loading: false
        },

        // Invoice form state
        invoiceForm: {
            pickup_fee: '',
            transport_fee: '',
            handling_fee: '',
            other_fee: '',
            notes: '',
            submitting: false
        },

        // Assignment form state
        assignmentForm: {
            driver_id: '',
            notes: '',
            submitting: false,
            loadingDrivers: false
        },

        // Available drivers
        availableDrivers: [],

        // Tracking state
        tracking: {
            data: [],
            loading: false
        },

        init() {
            this.config = window.shipmentShowConfig;
            this.shipment = this.config.shipment;
            this.canManage = this.config.canManage;
            this.invoice = this.config.invoice;
            this.assignment = this.config.assignment;
            this.loadItems();
        },

        async loadItems() {
            this.items.loading = true;
            try {
                const response = await fetch(this.config.itemsEndpoint);
                const data = await response.json();
                this.items.data = data.data || data;
            } catch (error) {
                console.error('Failed to load items:', error);
            } finally {
                this.items.loading = false;
            }
        },

        async loadTracking() {
            this.tracking.loading = true;
            try {
                const response = await fetch(this.config.trackingEndpoint);
                const data = await response.json();
                this.tracking.data = data.data || data;
            } catch (error) {
                console.error('Failed to load tracking:', error);
            } finally {
                this.tracking.loading = false;
            }
        },

        async createInvoice() {
            this.invoiceForm.submitting = true;
            try {
                const response = await fetch(this.config.invoiceStoreEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pickup_fee: this.invoiceForm.pickup_fee,
                        transport_fee: this.invoiceForm.transport_fee,
                        handling_fee: this.invoiceForm.handling_fee,
                        other_fee: this.invoiceForm.other_fee,
                        notes: this.invoiceForm.notes
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to create invoice');
                }

                if (window.showToast) {
                    window.showToast('Invoice created successfully', 'success');
                }

                // Reload page to reflect changes
                window.location.reload();
            } catch (error) {
                console.error('Create invoice error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to create invoice', 'error');
                }
            } finally {
                this.invoiceForm.submitting = false;
            }
        },

        async loadAvailableDrivers() {
            this.assignmentForm.loadingDrivers = true;
            try {
                const response = await fetch(this.config.availableDriversEndpoint);
                const data = await response.json();
                this.availableDrivers = data.data || data;
            } catch (error) {
                console.error('Failed to load available drivers:', error);
            } finally {
                this.assignmentForm.loadingDrivers = false;
            }
        },

        async assignDriver() {
            this.assignmentForm.submitting = true;
            try {
                const response = await fetch(this.config.assignDriverEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        driver_id: this.assignmentForm.driver_id,
                        notes: this.assignmentForm.notes
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to assign driver');
                }

                if (window.showToast) {
                    window.showToast('Driver assigned successfully', 'success');
                }

                // Reload page to reflect changes
                window.location.reload();
            } catch (error) {
                console.error('Assign driver error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to assign driver', 'error');
                }
            } finally {
                this.assignmentForm.submitting = false;
            }
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endpush
