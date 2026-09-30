@extends('admin.layouts.app')

@section('title', 'Order - ' . $shipment->shipment_number)
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', $shipment->shipment_number)

@php
$shipmentConfig = [
    'shipment' => $shipment,
    'saveUrl' => route('admin.orders.update', $shipment),
    'rejectEndpoint' => route('admin.orders.reject', $shipment),
    'reopenEndpoint' => route('admin.orders.reopen', $shipment),
    'itemsEndpoint' => route('admin.orders.items', $shipment),
    'trackingEndpoint' => route('admin.orders.tracking', $shipment),
    'assignDriverEndpoint' => route('admin.assignments.assign', $shipment),
    'cancelAssignmentEndpointTemplate' => route('admin.assignments.cancel', ['pickupAssignment' => '__ASSIGNMENT__']),
    'updateAssignmentEndpointTemplate' => route('admin.assignments.update', ['pickupAssignment' => '__ASSIGNMENT__']),
    'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
    'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
    'receiveAssignmentEndpointTemplate' => route('admin.assignments.receive', ['pickupAssignment' => '__ASSIGNMENT__']),
    'updateFulfillmentTypeEndpoint' => route('admin.orders.update-fulfillment-type', $shipment),
    'duplicateEndpoint' => route('admin.orders.duplicate', $shipment),
    'custodyDataEndpoint' => route('admin.orders.custody-data', $shipment),
    'createRunFromClaimsEndpoint' => route('admin.orders.create-run-from-claims'),
    'adminCompletePickupEndpoint' => route('admin.orders.admin-complete-pickup', $shipment),
    'chargesIndexEndpoint' => route('admin.orders.charges.index', $shipment),
    'chargesStoreEndpoint' => route('admin.orders.charges.store', $shipment),
    'chargesSeedPickupFeeEndpoint' => route('admin.orders.charges.seed-pickup-fee', $shipment),
    'chargesUpdateEndpointTemplate' => route('admin.orders.charges.update', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'chargesMarkPaidEndpointTemplate' => route('admin.orders.charges.mark-paid', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'chargesWaiveEndpointTemplate' => route('admin.orders.charges.waive', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'chargesCancelEndpointTemplate' => route('admin.orders.charges.cancel', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'receivingDataEndpoint' => route('admin.orders.receiving-data', $shipment),
    'addPackageUrl' => route('admin.orders.packages.add', $shipment),
    'deletePackageUrlTemplate' => route('admin.orders.packages.delete', ['shipment' => $shipment->id, 'item' => '__PKG__']),
    'receivingDetailsSaveEndpoint' => route('admin.orders.receiving.details', ['shipment' => $shipment->id, 'item' => '__ITEM__']),
    'receiveSaveEndpoint' => route('admin.orders.receiving.save', ['shipment' => $shipment->id, 'item' => '__ITEM__']),
    'receivePrintLabelEndpoint' => route('admin.orders.receiving.print-label', ['shipment' => $shipment->id, 'item' => '__ITEM__']),
    'receiveFinalizeEndpoint' => route('admin.orders.receiving.finalize', $shipment),
    'splitPackageUrlTemplate' => route('admin.orders.packages.split', ['shipment' => $shipment->id, 'item' => '__PKG__']),
    'autoGroupByPhoneEndpoint' => route('admin.orders.auto-group-by-phone', $shipment),
    'townsSearchUrl' => route('admin.locations.towns.data'),
    'canApproveReceivingDiscrepancy' => Auth::guard('admin')->user()?->hasPermission('warehouse.receiving.approve_discrepancy') ?? false,
    'canManage' => $canManage,
    'isSuperAdmin' => auth('admin')->user()?->isHqUser() ?? false,
    'assignment' => $currentAssignment,
    'assignmentHistory' => $assignmentHistory,
    'quantitySummary' => $quantitySummary,
    'sortBatchShowUrlTemplate' => route('admin.sort-batches.show', ['batch' => '__ID__']),
    'transportManifestShowUrlTemplate' => route('admin.transport-manifests.show', ['manifest' => '__ID__']),
    'deliveryRunShowUrlTemplate' => route('admin.delivery-runs.show', ['run' => '__ID__']),
];

$timeline = [
    ['label' => 'Created', 'value' => $shipment->created_at, 'dot' => 'bg-slate-400'],
    ['label' => 'Submitted', 'value' => $shipment->submitted_at ?? null, 'dot' => 'bg-blue-500'],
    ['label' => 'Rejected', 'value' => $shipment->rejected_at ?? null, 'dot' => 'bg-rose-500'],
    ['label' => 'Rider Assigned', 'value' => $currentAssignment?->assigned_at, 'dot' => 'bg-orange-600'],
    ['label' => 'En Route', 'value' => $currentAssignment?->en_route_at, 'dot' => 'bg-indigo-500'],
    ['label' => 'Arrived Pickup', 'value' => $currentAssignment?->arrived_at, 'dot' => 'bg-amber-500'],
    ['label' => 'Picked Up', 'value' => $currentAssignment?->picked_up_at, 'dot' => 'bg-violet-500'],
    ['label' => 'Arrived Warehouse', 'value' => $currentAssignment?->arrived_warehouse_at, 'dot' => 'bg-sky-500'],
    ['label' => 'Received Warehouse', 'value' => $currentAssignment?->received_at, 'dot' => 'bg-teal-500'],
    ['label' => 'Completed', 'value' => $currentAssignment?->completed_at, 'dot' => 'bg-emerald-500'],
];

$timelineEvents = collect($timeline)->filter(fn ($event) => filled($event['value']))->values();
$latestTimelineEvent = $timelineEvents->last();
$formatTimelineDate = fn ($value) => $value instanceof \Carbon\CarbonInterface
    ? $value->format('d M Y, h:i A')
    : \Illuminate\Support\Carbon::parse($value)->format('d M Y, h:i A');
@endphp

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="shipmentShow()" data-shipment-show-config="{{ json_encode($shipmentConfig) }}">

    <!-- Page Header (Positioned Above Workspace) -->
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <a href="{{ route('admin.orders.index') }}" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors shadow-sm">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight font-mono">{{ $shipment->shipment_number }}</h1>
                    
                    @php
                        $statusColors = match($shipment->status->value ?? $shipment->status) {
                            'draft' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-300',
                            'submitted' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-300',
                            'processing' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                            'pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted' => 'bg-violet-100 text-violet-800 ring-1 ring-violet-300',
                            'in_transit', 'at_destination', 'out_for_delivery' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                            'delivered' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
                            'cancelled', 'rejected' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-300',
                            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-300',
                        };
                    @endphp
                    <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider {{ $statusColors }}">
                        {{ $shipment->status->label() }}
                    </span>
                </div>
                
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-bold text-slate-700">
                    <span>{{ $shipment->vendor->name }}</span>
                    @if($shipment->vendor->business_name)
                        <span class="text-slate-300">•</span>
                        <span>{{ $shipment->vendor->business_name }}</span>
                    @endif
                    <span class="text-slate-300">•</span>
                    <span>Created {{ $shipment->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 sm:justify-end shrink-0">
            @if($canManage)
                <button type="button" @click="duplicateShipment()" :disabled="duplicating" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 shadow-sm disabled:opacity-50">
                    <svg x-show="!duplicating" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/></svg>
                    <svg x-show="duplicating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>
                    Duplicate
                </button>

                <button type="button" x-show="canManagePickupAssignment()" x-cloak @click="openAssignPickupDriver()" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-orange-200 bg-orange-50 px-4 text-sm font-bold text-orange-700 transition hover:bg-orange-100 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/></svg>
                    <span x-text="assignment ? 'Reassign Rider' : 'Assign Rider'"></span>
                </button>

                <button type="button" x-show="canRejectShipment()" x-cloak @click="openRejectShipmentModal()" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-bold text-rose-700 transition hover:bg-rose-100 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                    Reject
                </button>

                <button type="button" x-show="canReopenShipment()" x-cloak @click="reopenRejectedShipment()" :disabled="reopeningRejected" class="inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100 shadow-sm disabled:opacity-50">
                    <svg x-show="!reopeningRejected" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0 0 19 5M19 5h-5m5 0v5"/></svg>
                    <svg x-show="reopeningRejected" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>
                    <span x-text="reopeningRejected ? 'Reopening...' : 'Reopen'"></span>
                </button>
            @endif
        </div>
    </div>

    @if(($shipment->status->value ?? $shipment->status) === 'rejected' && $shipment->rejection_reason)
        <section class="rounded-3xl border border-rose-200 bg-rose-50 px-6 py-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-rose-600">Rejected request</p>
                    <p class="mt-1 text-sm font-bold leading-relaxed text-slate-900">{{ $shipment->rejection_reason }}</p>
                    @if($shipment->rejected_at)
                        <p class="mt-1 text-xs font-semibold text-rose-500">Rejected {{ $shipment->rejected_at->format('d M Y, h:i A') }}</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <!-- Main Workspace Container -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-lg shadow-slate-300/30">
        
        <!-- Tab Navigation Bar -->
        <div class="border-b border-slate-100 bg-slate-50/70 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="setActiveTab('overview')"
                    class="inline-flex w-auto items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition"
                    :class="activeTab === 'overview' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-200/50 bg-white border border-slate-200/80'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Overview
                </button>
                
                <button type="button" @click="setActiveTab('packages')"
                    class="inline-flex w-auto items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition"
                    :class="activeTab === 'packages' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-200/50 bg-white border border-slate-200/80'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Packages <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="activeTab === 'packages' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'">{{ $itemsCount }}</span>
                </button>

                <button type="button" @click="setActiveTab('receiving')"
                    class="inline-flex w-auto items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition"
                    :class="activeTab === 'receiving' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-200/50 bg-white border border-slate-200/80'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Receiving Workspace
                </button>

                <button type="button" @click="setActiveTab('tracking')"
                    class="inline-flex w-auto items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition"
                    :class="activeTab === 'tracking' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-200/50 bg-white border border-slate-200/80'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Tracking History
                </button>
            </div>
        </div>

        <!-- Tab Contents Workspace -->
        <div class="min-h-[500px] bg-slate-50/40 p-5 sm:p-8">

            <!-- ════════════ OVERVIEW TAB ════════════ -->
            <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">
                
                <template x-if="shipment.sender_notes">
                    <div class="rounded-3xl border border-amber-200/80 bg-amber-50 p-6 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-amber-900">Sender's Notes</h3>
                                <p class="mt-1 text-sm font-semibold text-amber-800 leading-relaxed" x-text="shipment.sender_notes"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    
                    <!-- Pickup Details Card -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <h3 class="text-sm font-black uppercase tracking-wider text-slate-500">Pickup Details</h3>
                            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-black" :class="pickupBadgeClass(shipment.pickup_status)" x-text="shipment.pickup_status_label || 'Pending'"></span>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase text-slate-400">Location</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="pickupLocationSummary()"></p>
                                <p x-show="shipment.pickup_landmark" class="text-xs text-slate-500 font-medium" x-text="'Landmark: ' + shipment.pickup_landmark"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-slate-400">Contact</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="shipment.pickup_contact_name || '-'"></p>
                                    <p class="text-xs font-mono text-slate-500 font-semibold" x-text="shipment.pickup_contact_phone || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-slate-400">Instructions</p>
                                    <p class="mt-0.5 text-sm font-medium text-slate-700" x-text="shipment.pickup_instructions || 'None'"></p>
                                </div>
                            </div>
                            
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase text-slate-400">Assigned Rider</p>
                                        <template x-if="assignment">
                                            <div>
                                                <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="assignmentDriverName()"></p>
                                                <p class="text-xs font-mono text-slate-500 font-semibold" x-text="assignmentDriverPhone()"></p>
                                            </div>
                                        </template>
                                        <template x-if="!assignment">
                                            <p class="mt-0.5 text-sm font-bold italic text-slate-500">Awaiting rider assignment</p>
                                        </template>
                                    </div>
                                    <div class="flex gap-2">
                                        <button x-show="canManage && canEditCurrentAssignment()" @click="openEditAssignment()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-black text-slate-700 shadow-sm transition hover:bg-slate-50">Change</button>
                                        <button x-show="assignmentHistory.length > 1" @click="openAssignmentHistoryModal()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-black text-slate-700 shadow-sm transition hover:bg-slate-50">History</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Destination & Fulfillment Card -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <h3 class="text-sm font-black uppercase tracking-wider text-slate-500">Destination & Delivery</h3>
                            <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-2.5 py-0.5 text-[10px] font-black text-orange-700 uppercase" x-text="shipmentDestinationModeLabel()"></span>
                        </div>

                        <div class="space-y-4">
                            <template x-if="isPerItemMode()">
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                                    <p class="text-sm font-bold text-slate-900">Per-Item Destinations</p>
                                    <p class="mt-1 text-xs text-slate-500">Recipient and location details are set individually on each package below.</p>
                                </div>
                            </template>
                            
                            <template x-if="!isPerItemMode()">
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase text-slate-400">Delivery Location</p>
                                        <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="deliveryLocationSummary()"></p>
                                        <p class="text-xs text-slate-500 font-medium" x-text="shipment.delivery_instructions ? 'Notes: ' + shipment.delivery_instructions : ''"></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[11px] font-bold uppercase text-slate-400">Recipient</p>
                                            <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="shipment.delivery_recipient_name || '—'"></p>
                                            <p class="text-xs font-mono text-slate-500 font-semibold" x-text="shipment.delivery_recipient_phone || '—'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="rounded-2xl bg-orange-50/50 border border-orange-100 p-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-orange-800/60">Target Drop-off Hub</p>
                                    <p class="mt-0.5 text-sm font-black text-slate-900" x-text="assignmentWarehouseName()"></p>
                                    <p class="text-xs font-mono text-slate-500 font-semibold" x-text="assignmentWarehouseCode()"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>

            <!-- ════════════ PACKAGES TAB ════════════ -->
            @if($editConfig)
            <div x-show="activeTab === 'packages'" x-cloak>
                @include('admin.shipments._packages-editor')
            </div>
            @endif

            <!-- ════════════ RECEIVING TAB ════════════ -->
            <div x-show="activeTab === 'receiving'" x-cloak>
                @include('shared.receiving.workspace', [
                    'packagesExpr' => 'receiving.packages',
                    'showPackageToolbar' => true,
                    'showAdminPackageControls' => true,
                    'detailsClick' => 'openPackageDetailsModal(pkg)',
                    'receiveClick' => 'openReceivingPackageModal(pkg, 1)',
                    'photosClick' => 'openReceivingPhotosModal(pkg)',
                    'printClick' => 'openReceivingLabelPrintModal(pkg)',
                    'finalizeClick' => 'openFinalizeConfirm()',
                    'finalizeDisabled' => '!canFinalizeReceiving()',
                    'finalizeLabelExpr' => 'finalizeReceivingButtonLabel()',
                    'finalizeSubtitle' => 'Mark all packages as received and move order to warehouse status.',
                    'showPickupFee' => false,
                    'showDropOffSelect' => false,
                ])
            </div>

            <!-- ════════════ TRACKING TAB ════════════ -->
            <div x-show="activeTab === 'tracking'" x-cloak>
                
                <div x-show="tracking.loading" class="flex flex-col items-center justify-center py-20 gap-4">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-orange-400 animate-bounce" style="animation-delay:0ms"></div>
                        <div class="w-2 h-2 rounded-full bg-orange-500 animate-bounce" style="animation-delay:120ms"></div>
                        <div class="w-2 h-2 rounded-full bg-orange-600 animate-bounce" style="animation-delay:240ms"></div>
                    </div>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">Loading Timeline...</p>
                </div>

                <template x-if="!tracking.loading">
                    <div class="space-y-8">
                        
                        <!-- Order Timeline Container -->
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-500">Order Pipeline History</h3>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500" x-text="tracking.data.length + ' events'"></span>
                            </div>

                            <template x-if="tracking.data.length === 0">
                                <div class="py-12 text-center text-sm font-semibold text-slate-500">No events recorded yet.</div>
                            </template>

                            <template x-if="tracking.data.length > 0">
                                <div class="space-y-4">
                                    <template x-for="(event, index) in tracking.data" :key="index">
                                        <div class="flex items-start gap-4">
                                            <div class="flex flex-col items-center mt-1">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl shadow-sm text-white" :class="timelineEventDotClass(event.status)">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <div x-show="index !== tracking.data.length - 1" class="h-full min-h-[24px] w-px bg-slate-200 my-1"></div>
                                            </div>
                                            <div class="flex-1 pb-4">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <p class="text-sm font-bold text-slate-900" x-text="event.label"></p>
                                                    <span class="text-xs font-semibold text-slate-500" x-text="formatDateTime(event.created_at)"></span>
                                                </div>
                                                <p x-show="event.description" class="mt-1 text-xs font-medium text-slate-600" x-text="event.description"></p>
                                                <p x-show="event.location" class="mt-1 flex items-center gap-1 text-[11px] font-bold text-slate-400">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                    <span x-text="event.location"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                    </div>
                </template>

            </div>
        </div>

    </div>

    <!-- Modals Logic Kept Exactly Intact -->
    @include('shared.receiving.add-package-modal', [
        'modal' => 'receivingAddPackageModal',
        'closeAction' => 'closeReceivingAddPackageModal()',
        'saveAction' => 'addReceivingPackage()',
    ])
    
    {{-- ══ MODAL: Assign Riders ══════════════════════════════════════════

         The "Assign Rider" button in the header calls openAssignPickupDriver(),
         which sets `assignDriverModalOpen` — for a while nothing rendered that
         flag, so the button looked dead and no rider could be added from this
         page. The markup lives here, inside the shipmentShow() scope that owns the
         state it binds to.

         Multi-select on purpose: a shipment can ask for more than one rider, and
         the endpoint now takes the whole `driver_ids` selection. --}}
    <div x-show="assignDriverModalOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="assignDriverModalOpen = false">
        <div x-show="assignDriverModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="assignDriverModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="assignDriverModalOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop
                 class="relative z-10 flex max-h-[calc(100vh-32px)] w-full max-w-lg flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="relative border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" x-text="assignment ? 'Reassign / Add Riders' : 'Assign Riders'"></h3>
                                <p class="mt-1 text-sm text-slate-500">Select one or more pickup riders and the target warehouse.</p>
                            </div>
                        </div>
                        <button type="button" @@click="assignDriverModalOpen = false" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form @@submit.prevent="assignDriver()" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">

                        <!-- Selected Riders Chips -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Selected Riders <span class="text-rose-500">*</span></label>

                            <div x-show="selectedAssignmentDrivers && selectedAssignmentDrivers.length > 0" class="mb-3 flex flex-wrap gap-2">
                                <template x-for="sDriver in selectedAssignmentDrivers" :key="sDriver.id">
                                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-orange-50 border border-orange-200 px-3 py-1.5 text-xs font-bold text-orange-800 shadow-sm">
                                        <span x-text="sDriver.name"></span>
                                        <button type="button" @@click="toggleAssignmentDriver(sDriver)" class="ml-1 text-orange-500 hover:text-orange-800 focus:outline-none font-black text-sm">
                                            &times;
                                        </button>
                                    </span>
                                </template>
                            </div>

                            <div class="relative" @@click.outside="assignmentDriverPickerOpen = false">
                                <input type="search" x-model="assignmentDriverSearch"
                                       @@focus="assignmentDriverPickerOpen = true"
                                       @@input="assignmentDriverPickerOpen = true; assignmentDriverActiveIndex = -1"
                                       @@keydown.arrow-down.prevent="moveAssignmentDriverFocus(1)"
                                       @@keydown.arrow-up.prevent="moveAssignmentDriverFocus(-1)"
                                       @@keydown.enter.prevent="selectActiveAssignmentDriver()"
                                       @@keydown.escape.stop.prevent="assignmentDriverPickerOpen = false; assignmentDriverActiveIndex = -1"
                                       role="combobox" aria-autocomplete="list" aria-controls="shipment-assign-rider-listbox"
                                       :aria-expanded="assignmentDriverPickerOpen"
                                       placeholder="Search and click to add riders..."
                                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100">

                                <div x-show="assignmentDriverPickerOpen" x-cloak class="absolute left-0 right-0 z-40 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                                    <div id="shipment-assign-rider-listbox" role="listbox" aria-label="Pickup riders" class="max-h-64 overflow-y-auto">
                                        <template x-for="(driver, index) in filteredAssignmentDrivers()" :key="driver.id">
                                            <button type="button" :id="`shipment-assign-rider-${driver.id}`" role="option"
                                                    :aria-selected="isDriverSelected(driver.id)"
                                                    @@mouseenter="assignmentDriverActiveIndex = index"
                                                    @@click="toggleAssignmentDriver(driver)"
                                                    class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-0 hover:bg-orange-50 transition-colors"
                                                    :class="(isDriverSelected(driver.id) || assignmentDriverActiveIndex === index) ? 'bg-orange-50' : ''">
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-bold text-slate-900" x-text="driver.name"></span>
                                                    <span class="block truncate text-xs text-slate-500" x-text="[driver.phone, driver.vehicle_type, driver.vehicle_number].filter(Boolean).join(' · ')"></span>
                                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold" :class="driver.is_busy ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'" x-text="driver.is_busy ? `Busy · ${driver.active_work_count} active jobs` : 'Available'"></span>
                                                    <span x-show="driver.is_busy" class="mt-1 block text-[10px] font-semibold text-amber-700" x-text="`${driver.active_work?.pickups || 0} pickups · ${driver.active_work?.transports || 0} transports · ${driver.active_work?.deliveries || 0} deliveries`"></span>
                                                </span>
                                                <span x-show="isDriverSelected(driver.id)" class="text-lg font-bold text-orange-600">✓</span>
                                            </button>
                                        </template>
                                        <p x-show="filteredAssignmentDrivers().length === 0" class="px-3 py-6 text-center text-sm text-slate-400">No matching riders.</p>
                                    </div>
                                </div>
                            </div>
                            <template x-if="availableDrivers.length === 0 && !assignmentForm.loadingDrivers">
                                <p class="mt-1.5 text-xs text-amber-600">No available riders right now</p>
                            </template>
                            <template x-if="assignmentForm.loadingDrivers">
                                <p class="mt-1.5 text-xs text-slate-400">Loading riders...</p>
                            </template>
                        </div>

                        <!-- Target Warehouse -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select x-model="assignmentForm.target_warehouse_id" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 pr-10 text-sm text-slate-900 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100" required>
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

                        <!-- Notes -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                            <textarea x-model="assignmentForm.notes" rows="3" class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Optional pickup notes for the rider(s)..."></textarea>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                        <button type="button" @@click="assignDriverModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="assignmentForm.submitting || (!selectedAssignmentDrivers || selectedAssignmentDrivers.length === 0) || !assignmentForm.target_warehouse_id" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition-all hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none">
                            <svg x-show="assignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="assignmentForm.submitting ? 'Saving...' : 'Assign Rider(s)'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection