@extends('admin.layouts.app')

@section('title', 'Shipment - ' . $shipment->shipment_number)
@section('breadcrumb-parent', 'Shipments')
@section('breadcrumb-current', $shipment->shipment_number)

@php
$shipmentConfig = [
    'shipment' => $shipment,
    'saveUrl' => route('admin.shipments.update', $shipment),
    'itemsEndpoint' => route('admin.shipments.items', $shipment),
    'trackingEndpoint' => route('admin.shipments.tracking', $shipment),
    'invoiceStoreEndpoint' => route('admin.invoices.store', $shipment),
    'invoiceDownloadUrlTemplate' => route('admin.invoices.download', ['invoice' => '__INVOICE__']),
    'invoiceSendEndpointTemplate' => route('admin.invoices.send', ['invoice' => '__INVOICE__']),
    'invoiceCancelEndpointTemplate' => route('admin.invoices.cancel', ['invoice' => '__INVOICE__']),
    'assignDriverEndpoint' => route('admin.assignments.assign', $shipment),
    'cancelAssignmentEndpointTemplate' => route('admin.assignments.cancel', ['pickupAssignment' => '__ASSIGNMENT__']),
    'updateAssignmentEndpointTemplate' => route('admin.assignments.update', ['pickupAssignment' => '__ASSIGNMENT__']),
    'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
    'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
    'receiveAssignmentEndpointTemplate' => route('admin.assignments.receive', ['pickupAssignment' => '__ASSIGNMENT__']),
    'updateFulfillmentTypeEndpoint' => route('admin.shipments.update-fulfillment-type', $shipment),
    'duplicateEndpoint' => route('admin.shipments.duplicate', $shipment),
    'custodyDataEndpoint' => route('admin.shipments.custody-data', $shipment),
    'createRunFromClaimsEndpoint' => route('admin.shipments.create-run-from-claims'),
    'adminCompletePickupEndpoint' => route('admin.shipments.admin-complete-pickup', $shipment),
    'chargesIndexEndpoint' => route('admin.shipments.charges.index', $shipment),
    'chargesStoreEndpoint' => route('admin.shipments.charges.store', $shipment),
    'chargesSeedPickupFeeEndpoint' => route('admin.shipments.charges.seed-pickup-fee', $shipment),
    'chargesUpdateEndpointTemplate' => route('admin.shipments.charges.update', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'chargesMarkPaidEndpointTemplate' => route('admin.shipments.charges.mark-paid', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'chargesWaiveEndpointTemplate' => route('admin.shipments.charges.waive', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'chargesCancelEndpointTemplate' => route('admin.shipments.charges.cancel', ['shipment' => $shipment->id, 'charge' => '__CHARGE__']),
    'receivingDataEndpoint' => route('admin.shipments.receiving-data', $shipment),
    'addPackageUrl' => route('admin.shipments.packages.add', $shipment),
    'deletePackageUrlTemplate' => route('admin.shipments.packages.delete', ['shipment' => $shipment->id, 'item' => '__PKG__']),
    'receivingDetailsSaveEndpoint' => route('admin.shipments.receiving.details', ['shipment' => $shipment->id, 'item' => '__ITEM__']),
    'receiveSaveEndpoint' => route('admin.shipments.receiving.save', ['shipment' => $shipment->id, 'item' => '__ITEM__']),
    'receivePrintLabelEndpoint' => route('admin.shipments.receiving.print-label', ['shipment' => $shipment->id, 'item' => '__ITEM__']),
    'receiveFinalizeEndpoint' => route('admin.shipments.receiving.finalize', $shipment),
    'splitPackageUrlTemplate' => route('admin.shipments.packages.split', ['shipment' => $shipment->id, 'item' => '__PKG__']),
    'autoGroupByPhoneEndpoint' => route('admin.shipments.auto-group-by-phone', $shipment),
    'townsSearchUrl' => route('admin.locations.towns.data'),
    'canApproveReceivingDiscrepancy' => Auth::guard('admin')->user()?->hasPermission('warehouse.receiving.approve_discrepancy') ?? false,
    'canManage' => $canManage,
    'canManageCharges' => $canManageCharges ?? false,
    'isSuperAdmin' => auth('admin')->user()?->isSuperAdmin() ?? false,
    'paymentsDataEndpoint' => route('admin.shipments.payments.data', $shipment),
    'storePaymentEndpoint' => route('admin.shipments.payments.store', $shipment),
    'destroyPaymentEndpointTemplate' => route('admin.payments.destroy', ['payment' => '__PAYMENT__']),
    'downloadReceiptUrlTemplate' => route('admin.payments.download', ['payment' => '__PAYMENT__']),
    'printReceiptUrlTemplate' => route('admin.payments.print', ['payment' => '__PAYMENT__']),
    'invoice' => $currentInvoice,
    'invoiceHistory' => $invoiceHistory,
    'assignment' => $currentAssignment,
    'assignmentHistory' => $assignmentHistory,
    'sortBatchShowUrlTemplate' => route('admin.sort-batches.show', ['batch' => '__ID__']),
    'transportManifestShowUrlTemplate' => route('admin.transport-manifests.show', ['manifest' => '__ID__']),
    'deliveryRunShowUrlTemplate' => route('admin.delivery-runs.show', ['run' => '__ID__']),
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
                <!-- Top Row: Back Button + Action Buttons -->
                <div class="flex items-center justify-between mb-6">
                    <a href="{{ route('admin.shipments.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Shipments</span>
                    </a>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px] font-bold {{ $statusColors }} border border-white/10 backdrop-blur-sm">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColors }}"></span>
                            {{ $shipment->status->label() }}
                        </span>
                        <button type="button"
                                @@click="openTrackingHistoryModal()"
                                class="inline-flex items-center gap-2 px-3 py-2 bg-slate-500/20 hover:bg-slate-500/30 text-slate-300 text-xs font-semibold rounded-xl border border-slate-500/30 transition-all backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                            Tracking History
                        </button>
                        @if($canManage)
                        <!-- Fulfillment Type Changer -->
                        <div class="relative" x-data="{ ftOpen: false }" x-show="canChangeFulfillmentType()" x-cloak>
                            <button @@click="ftOpen = !ftOpen" :disabled="ftLoading" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 text-xs font-semibold rounded-xl border border-indigo-500/30 transition-all backdrop-blur-sm disabled:opacity-50">
                                <svg x-show="!ftLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <svg x-show="ftLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span x-text="ftLoading ? 'Updating...' : fulfillmentTypeLabel()"></span>
                                <svg x-show="!ftLoading" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="ftOpen && !ftLoading" @@click.outside="ftOpen = false" x-transition
                                 class="absolute right-0 mt-1 w-56 bg-white rounded-xl shadow-2xl border border-slate-200 z-50 py-1">
                                <template x-for="ft in [
                                    {v:'warehouse', label:'Warehouse Delivery', desc:'Standard flow'},
                                    {v:'direct', label:'Direct Delivery', desc:'Driver delivers directly'}
                                ]" :key="ft.v">
                                    <button @@click="changeFulfillmentType(ft.v); ftOpen = false"
                                            class="w-full text-left px-4 py-2.5 hover:bg-slate-50 transition-colors flex items-center justify-between"
                                            :class="shipment.fulfillment_type === ft.v ? 'bg-blue-50' : ''">
                                        <div>
                                            <p class="text-xs font-bold text-slate-900" x-text="ft.label"></p>
                                            <p class="text-[10px] text-slate-500" x-text="ft.desc"></p>
                                        </div>
                                        <svg x-show="shipment.fulfillment_type === ft.v" class="w-4 h-4 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <button
                            x-show="['submitted', 'invoice_accepted'].includes(shipment.status)"
                            x-cloak
                            @@click="loadAssignmentDependencies(); assignDriverModalOpen = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-violet-500/20 hover:bg-violet-500/30 text-violet-300 text-xs font-semibold rounded-xl border border-violet-500/30 transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Assign Driver
                        </button>
                        {{-- Legacy pre-pickup Packages editor hidden in favor of the Receiving workspace now labelled Packages. --}}
                        {{-- @if($editConfig)
                        <button type="button" @@click="activeTab = 'packages'; window.scrollTo({ top: 0, behavior: 'smooth' })"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 text-xs font-semibold rounded-xl border border-blue-500/30 transition-all backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit Packages
                        </button>
                        @endif --}}
                        <button @@click="duplicateShipment()" :disabled="duplicating"
                                title="Duplicate shipment"
                                aria-label="Duplicate shipment"
                                class="inline-flex h-10 w-10 items-center justify-center bg-slate-500/20 hover:bg-slate-500/30 text-slate-300 rounded-xl border border-slate-500/30 transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg x-show="!duplicating" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="duplicating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Main Row: Profile LEFT, Summary + Actions RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
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
                                        <span>{{ number_format($itemsCount) }} package(s)</span>
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
                                @if($shipment->delivery_recipient_name || $shipment->delivery_recipient_phone)
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    @if($shipment->delivery_recipient_name)
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="truncate">{{ $shipment->delivery_recipient_name }}</span>
                                    </div>
                                    @endif
                                    @if($shipment->delivery_recipient_phone)
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ $shipment->delivery_recipient_phone }}
                                    </div>
                                    @endif
                                </div>
                                @endif

                                @if($shipment->deliveryRegion?->name || $shipment->delivery_town)
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="truncate">
                                            {{ $shipment->deliveryRegion?->name }}@if($shipment->deliveryDistrict?->name), {{ $shipment->deliveryDistrict?->name }}@endif
                                            @if($shipment->delivery_town), {{ $shipment->delivery_town }}@endif
                                        </span>
                                    </div>
                                </div>
                                @endif
                            @endif

                            <div class="flex flex-wrap items-center gap-1.5">
                                @php
                                    $dpColors = ($shipment->delivery_preference ?? 'deliver') === 'self_pickup'
                                        ? 'bg-emerald-500/20 text-emerald-300'
                                        : 'bg-blue-500/20 text-blue-300';
                                    $ftColors = match($shipment->fulfillment_type?->value ?? null) {
                                        'direct' => 'bg-amber-500/20 text-amber-300',
                                        'warehouse' => 'bg-slate-500/20 text-slate-300',
                                        default => '',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $dpColors }}">
                                    {{ ($shipment->delivery_preference ?? 'deliver') === 'self_pickup' ? 'Self Pickup' : 'Deliver to Recipient' }}
                                </span>
                                @if($shipment->fulfillment_type)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $ftColors }}" x-text="fulfillmentTypeLabel()"></span>
                                @endif
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $shipment->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Stats + Action Buttons -->
                    <div class="flex flex-col gap-3 lg:ml-auto lg:items-end">
                        <!-- Row 1: Package summary stats -->
                        <div class="flex items-stretch gap-2 flex-wrap lg:flex-nowrap">
                            <!-- Total Packages -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 h-20 lg:h-24 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none" x-text="receivingPackageCount()">{{ number_format($itemsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Total Packages</p>
                                </div>
                            </div>

                            <!-- Received Qty -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 h-20 lg:h-24 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-emerald-400 leading-none" x-text="receivingReceivedUnits()">0</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Received Qty</p>
                                </div>
                            </div>

                            <!-- Pending Qty -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 h-20 lg:h-24 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-amber-400 leading-none" x-text="receivingPendingUnits()">0</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Pending Qty</p>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Packages Workspace -->
    <div class="min-h-[680px]">
        <!-- Main Content Area -->
        <div class="min-w-0">

            <!-- ═══════════════════════════════════════ -->
            <!-- OVERVIEW TAB                            -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'overview'" x-cloak>
                {{-- Sender Notes Banner --}}
                <template x-if="shipment.sender_notes">
                    <div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-2xl">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-amber-900">Sender's Notes</h3>
                                <p class="text-sm text-amber-800 mt-1 leading-relaxed" x-text="shipment.sender_notes"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

                    <!-- Left Column: Shipment Info + Packages -->
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
                                        <p class="text-slate-400 italic">Each package has its own recipient &amp; delivery address — see Packages below.</p>
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

                        <!-- Card C: Packages -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <h3 class="text-sm font-bold text-slate-900">Packages</h3>
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
                                                <span x-show="item.fulfillment_type" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold"
                                                      :class="item.fulfillment_type === 'direct' ? 'bg-amber-100 text-amber-700' : item.fulfillment_type === 'self_pickup' ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-500'"
                                                      x-text="item.fulfillment_type === 'direct' ? 'Direct' : item.fulfillment_type === 'self_pickup' ? 'Self Pickup' : 'Warehouse'"></span>
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
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            <!-- Gradient Header -->
                            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-white/20 text-white ring-1 ring-white/30 capitalize" x-text="activeInvoice().status_label || activeInvoice().status"></span>
                                        </template>
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
                                        <template x-if="canCreateInvoice()">
                                            <div class="text-center py-4">
                                                <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-medium text-slate-500 mb-3">No invoice yet</p>
                                                <button x-show="canManage" @@click="openCreateInvoiceModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:opacity-90 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Create Invoice
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!canCreateInvoice()">
                                            <div class="text-center py-4">
                                                <svg class="w-9 h-9 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        <!-- Fee rows -->
                                        <div class="mb-4">
                                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 text-xs mb-0.5">
                                                <span class="text-slate-500">Pickup Fee</span>
                                                <span class="font-bold text-slate-700" x-text="formatMoney(activeInvoice().pickup_fee, activeInvoice().currency)"></span>
                                            </div>
                                            <div class="flex items-center justify-between px-3 py-2 text-xs mb-0.5">
                                                <span class="text-slate-500">Transport Fee</span>
                                                <span class="font-bold text-slate-700" x-text="formatMoney(activeInvoice().transport_fee, activeInvoice().currency)"></span>
                                            </div>
                                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 text-xs mb-0.5">
                                                <span class="text-slate-500">Handling Fee</span>
                                                <span class="font-bold text-slate-700" x-text="formatMoney(activeInvoice().handling_fee, activeInvoice().currency)"></span>
                                            </div>
                                            <template x-if="activeInvoice().other_fee > 0">
                                                <div class="flex items-center justify-between px-3 py-2 text-xs mb-0.5">
                                                    <span class="text-slate-500">Other Fee</span>
                                                    <span class="font-bold text-slate-700" x-text="formatMoney(activeInvoice().other_fee, activeInvoice().currency)"></span>
                                                </div>
                                            </template>
                                            <!-- Total featured -->
                                            <div class="mt-2 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl px-4 py-3 flex items-center justify-between shadow-sm shadow-emerald-200/60">
                                                <span class="text-[11px] font-bold text-white/70 uppercase tracking-wide">Total</span>
                                                <span class="text-base font-black text-white" x-text="formatMoney(activeInvoice().total_amount, activeInvoice().currency)"></span>
                                            </div>
                                        </div>
                                        <!-- Action buttons -->
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a :href="config.invoiceDownloadUrlTemplate.replace('__INVOICE__', activeInvoice().id)" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                PDF
                                            </a>
                                            <button x-show="canManage && activeInvoice().status === 'pending'" @@click="sendInvoice(activeInvoice().id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                                Send
                                            </button>
                                            <button x-show="canManage && activeInvoice().status === 'sent'" @@click="adminAcceptInvoice(activeInvoice().id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Accept
                                            </button>
                                            <button @@click="openActiveInvoiceModal()"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </button>
                                            <button x-show="canManage && ['pending','sent','accepted'].includes(activeInvoice().status)" @@click="openCancelInvoiceModal(activeInvoice().id)"
                                                class="inline-flex items-center px-3 py-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 text-xs font-semibold transition-colors">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Widget 2: Pickup Assignment -->
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            <!-- Gradient Header -->
                            <div class="bg-gradient-to-br from-violet-500 to-purple-600 px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-white leading-none">Pickup Assignment</h3>
                                            <template x-if="assignment">
                                                <p class="text-[11px] text-white/60 mt-0.5" x-text="assignment.driver?.name || 'Driver assigned'"></p>
                                            </template>
                                            <template x-if="!assignment">
                                                <p class="text-[11px] text-white/50 mt-0.5">No driver assigned</p>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5">
                                        <template x-if="assignment">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-white/20 text-white ring-1 ring-white/30 capitalize" x-text="assignment.status_label || (assignment.status ? assignment.status.replace(/_/g, ' ') : '')"></span>
                                        </template>
                                        <button @@click="activeTab = 'assignment'" class="text-[11px] text-white/60 hover:text-white font-medium transition-colors flex items-center gap-0.5">
                                            History <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Body -->
                            <div class="p-5">
                                <template x-if="assignmentUiError">
                                    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="assignmentUiError"></div>
                                </template>
                                <!-- No assignment -->
                                <template x-if="!assignment">
                                    <div>
                                        <template x-if="['submitted', 'invoice_accepted'].includes(shipment.status)">
                                            <div class="text-center py-4">
                                                <div class="w-11 h-11 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-medium text-slate-500 mb-3">No driver assigned</p>
                                                <button x-show="canManage" @@click="loadAssignmentDependencies(); assignDriverModalOpen = true" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:opacity-90 text-white text-xs font-bold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Assign Driver
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!['submitted', 'invoice_accepted'].includes(shipment.status)">
                                            <div class="text-center py-4">
                                                <svg class="w-9 h-9 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        <div class="flex items-center gap-3 mb-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                            <div class="w-10 h-10 rounded-full ring-2 ring-violet-200 bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-black text-sm shadow-sm flex-shrink-0">
                                                <span x-text="(assignment.driver?.name || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-slate-900 truncate leading-none" x-text="assignment.driver?.name || 'Unknown Driver'"></p>
                                                <p class="text-xs text-slate-400 mt-0.5" x-text="assignment.driver?.phone || '—'"></p>
                                            </div>
                                        </div>
                                        <!-- Warehouse -->
                                        <div class="flex items-center gap-2.5 mb-4 px-3 py-2.5 rounded-xl bg-amber-50 border border-amber-100/80">
                                            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wide leading-none mb-0.5">Target Warehouse</p>
                                                <p class="text-xs font-bold text-slate-800 truncate" x-text="assignment.target_warehouse?.name || 'No warehouse'"></p>
                                            </div>
                                            <template x-if="assignment.target_warehouse?.code">
                                                <span class="text-[10px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded-md flex-shrink-0" x-text="assignment.target_warehouse.code"></span>
                                            </template>
                                        </div>
                                        <!-- Progress: 2-row pill bars -->
                                        <div class="mb-4">
                                            <div class="grid grid-cols-4 gap-1 mb-2">
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="assignment.assigned_at ? 'bg-violet-500' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="assignment.assigned_at ? 'text-violet-600' : 'text-slate-300'">Assigned</p>
                                                </div>
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="assignment.en_route_at ? 'bg-blue-500' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="assignment.en_route_at ? 'text-blue-600' : 'text-slate-300'">En Route</p>
                                                </div>
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="assignment.arrived_at ? 'bg-amber-500' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="assignment.arrived_at ? 'text-amber-600' : 'text-slate-300'">Arrived</p>
                                                </div>
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="['picking_up','completed'].includes(assignment.status) ? 'bg-teal-400' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="['picking_up','completed'].includes(assignment.status) ? 'text-teal-600' : 'text-slate-300'">Picking Up</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-3 gap-1">
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="assignment.picked_up_at ? 'bg-teal-600' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="assignment.picked_up_at ? 'text-teal-700' : 'text-slate-300'">Picked Up</p>
                                                </div>
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="assignment.arrived_warehouse_at ? 'bg-indigo-500' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="assignment.arrived_warehouse_at ? 'text-indigo-600' : 'text-slate-300'">At Warehouse</p>
                                                </div>
                                                <div>
                                                    <div class="h-1.5 rounded-full mb-1 transition-colors" :class="assignment.received_at ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                                                    <p class="text-[9px] text-center font-semibold leading-none" :class="assignment.received_at ? 'text-emerald-600' : 'text-slate-300'">Received</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Action buttons -->
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" x-show="canManage && canEditCurrentAssignment()" @@click="openEditAssignment()"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 text-xs font-semibold transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" x-show="canManage && canUnassignCurrentAssignment()" @@click="openUnassignModal()" :disabled="assignmentActionLoading"
                                                class="inline-flex items-center px-3 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition-colors disabled:opacity-50">
                                                <span x-text="assignmentActionLoading ? '...' : 'Unassign'"></span>
                                            </button>
                                            <button type="button" x-show="canManage && canReceiveCurrentAssignment()" @@click="receiveAtWarehouse()" :disabled="assignmentActionLoading"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-bold shadow-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span x-text="assignmentActionLoading ? 'Saving...' : 'Receive'"></span>
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
                                                            <button x-show="canManage && ['pending', 'sent', 'accepted'].includes(historyInvoice.status)" @@click="openCancelInvoiceModal(historyInvoice.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Cancel invoice">
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
                                            :class="assignmentStatusClass(assignment.status)"
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
                                            @@click="openUnassignModal()"
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
                                    <button type="submit" :disabled="assignmentForm.submitting || !assignmentForm.driver_id || !assignmentForm.target_warehouse_id" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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
                                    <button type="submit" :disabled="editAssignmentForm.submitting" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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
                                <template x-if="canManage && !config.invoice">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        No active invoice
                                    </span>
                                </template>
                                <button x-show="canManage && config.invoice" @@click="paymentForm.open = true; paymentForm.payment_date = new Date().toISOString().split('T')[0]"
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
                            <!-- Left: Search -->
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="relative w-full sm:w-64">
                                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text" x-model="paymentSearch" @@input="paymentPage = 1" placeholder="Search payments..." class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300">
                                </div>
                            </div>

                            <!-- Right: Export & View -->
                            <div class="flex items-center gap-2">
                                <!-- Export -->
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
                                        <button type="button" @@click="exportPayments('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            CSV
                                        </button>
                                        <div class="border-t border-slate-200/50 my-1"></div>
                                        <button type="button" @@click="printPayments(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Print
                                        </button>
                                    </div>
                                </div>

                                <!-- Customize Columns -->
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
                                    <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                        <template x-for="col in paymentColumns" :key="col.key">
                                            <button type="button" @@click="togglePaymentColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                                <span x-text="col.label"></span>
                                                <svg x-show="paymentVisibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </template>
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
                                            <th x-show="paymentVisibleColumns.payment_date" @@click="sortPayments('payment_date')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">
                                                    DATE
                                                    <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'payment_date' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th x-show="paymentVisibleColumns.amount" @@click="sortPayments('amount')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">
                                                    AMOUNT
                                                    <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'amount' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th x-show="paymentVisibleColumns.method" @@click="sortPayments('method_label')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">
                                                    METHOD
                                                    <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'method_label' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                                    </svg>
                                                </div>
                                            </th>
                                            <th x-show="paymentVisibleColumns.reference" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                                REFERENCE
                                            </th>
                                            <th x-show="paymentVisibleColumns.invoice" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                                INVOICE
                                            </th>
                                            <th x-show="paymentVisibleColumns.recorded_by" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                                RECORDED BY
                                            </th>
                                            <th x-show="paymentVisibleColumns.notes" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                                NOTES
                                            </th>
                                            <th x-show="paymentVisibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                                ACTIONS
                                            </th>
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
                                                <td x-show="paymentVisibleColumns.payment_date" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="payment.payment_date"></td>
                                                <td x-show="paymentVisibleColumns.amount" class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="text-xs font-semibold text-emerald-700" x-text="'GHS ' + payment.formatted_amount"></span>
                                                </td>
                                                <td x-show="paymentVisibleColumns.method" class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700" x-text="payment.method_label"></span>
                                                </td>
                                                <td x-show="paymentVisibleColumns.reference" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-500 font-mono" x-text="payment.reference_number || '—'"></td>
                                                <td x-show="paymentVisibleColumns.invoice" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-500" x-text="payment.invoice_number || '—'"></td>
                                                <td x-show="paymentVisibleColumns.recorded_by" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="payment.recorded_by || '—'"></td>
                                                <td x-show="paymentVisibleColumns.notes" class="px-4 py-2.5 text-xs text-slate-500 max-w-[150px] truncate" x-text="payment.notes || '—'"></td>
                                                <td x-show="paymentVisibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <a :href="config.downloadReceiptUrlTemplate.replace('__PAYMENT__', payment.id)" target="_blank"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition-colors inline-flex" title="Download receipt PDF">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                        </a>
                                                        <a :href="config.printReceiptUrlTemplate.replace('__PAYMENT__', payment.id)" target="_blank"
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
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
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
                                            <button @@click="paymentPage = 1" :disabled="paymentPage === 1"
                                                :class="paymentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = Math.max(1, paymentPage - 1)" :disabled="paymentPage === 1"
                                                :class="paymentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = Math.min(paymentLastPage(), paymentPage + 1)" :disabled="paymentPage >= paymentLastPage()"
                                                :class="paymentPage >= paymentLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = paymentLastPage()" :disabled="paymentPage >= paymentLastPage()"
                                                :class="paymentPage >= paymentLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
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
                                <svg class="w-4.5 h-4.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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
                            class="inline-flex items-center gap-2 px-4 py-2 border border-rose-200 bg-white text-rose-700 hover:bg-rose-50 text-sm font-bold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="voidConfirm.loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="voidConfirm.loading ? 'Voiding...' : 'Yes, Void Payment'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tracking Tab -->
            <div x-show="activeTab === 'tracking'" x-cloak>

                <!-- Loading -->
                <div x-show="tracking.loading" class="flex flex-col items-center justify-center py-20 gap-4">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0ms"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay:120ms"></div>
                        <div class="w-2 h-2 rounded-full bg-violet-500 animate-bounce" style="animation-delay:240ms"></div>
                    </div>
                    <p class="text-xs text-slate-400 font-semibold tracking-wide uppercase">Loading tracking data</p>
                </div>

                <!-- Content (loaded) -->
                <template x-if="!tracking.loading">
                    <div class="space-y-10">

                        {{-- ─── SECTION 1: Shipment Timeline ─── --}}
                        <div>
                            <!-- Section Header -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-200/60 flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">Shipment Timeline</h3>
                                        <p class="text-xs text-slate-400 mt-0.5">Full pipeline history from creation to delivery</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full" x-text="tracking.data.length + ' events'"></span>
                            </div>

                            <!-- Empty Timeline -->
                            <template x-if="tracking.data.length === 0">
                                <div class="flex flex-col items-center justify-center py-14 text-center bg-gradient-to-br from-slate-50 to-slate-100/40 rounded-2xl border border-dashed border-slate-200">
                                    <div class="w-14 h-14 rounded-2xl bg-white shadow-sm border border-slate-200 flex items-center justify-center mb-4">
                                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-slate-600 text-sm font-bold mb-1">No events yet</p>
                                    <p class="text-slate-400 text-xs max-w-xs leading-relaxed">Timeline events appear as this shipment progresses through the pipeline</p>
                                </div>
                            </template>

                            <!-- Timeline Events -->
                            <template x-if="tracking.data.length > 0">
                                <div>
                                    <template x-for="(event, index) in tracking.data" :key="index">
                                        <div>
                                            <!-- Short connector between cards (not before first) -->
                                            <div x-show="index > 0" class="flex justify-start pl-[22px] my-0.5">
                                                <div class="w-px h-4 bg-slate-200"></div>
                                            </div>

                                            <!-- Event Card — icon inside, left side -->
                                            <div class="flex items-start gap-3 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-200 overflow-hidden p-3.5">

                                                <!-- Colored icon square inside the card -->
                                                <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm mt-0.5"
                                                     :class="timelineEventDotClass(event.status)">
                                                    <template x-if="['created','submitted'].includes(event.status)">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </template>
                                                    <template x-if="event.status.startsWith('invoice')">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </template>
                                                    <template x-if="['pickup_assigned','en_route','arrived','picked_up','arrived_warehouse','at_warehouse'].includes(event.status)">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                    </template>
                                                    <template x-if="event.status === 'sorted'">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                    </template>
                                                    <template x-if="['in_transit','at_destination','received_at_destination'].includes(event.status)">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                                                    </template>
                                                    <template x-if="['out_for_delivery','delivered'].includes(event.status)">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                                    </template>
                                                </div>

                                                <!-- Content -->
                                                <div class="flex-1 min-w-0">
                                                    <!-- Badge + timestamp row -->
                                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                                        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                                              :class="timelineEventBadgeClass(event.status)"
                                                              x-text="event.status_label"></span>
                                                        <span class="text-[10px] text-slate-400 font-medium whitespace-nowrap flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            <span x-text="formatDateTime(event.created_at)"></span>
                                                        </span>
                                                    </div>
                                                    <!-- Label -->
                                                    <p class="text-sm font-bold text-slate-800 leading-snug" x-text="event.label"></p>
                                                    <!-- Location + description -->
                                                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5">
                                                        <template x-if="event.location">
                                                            <span class="text-[11px] text-slate-500 flex items-center gap-1">
                                                                <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                                <span x-text="event.location"></span>
                                                            </span>
                                                        </template>
                                                        <template x-if="event.description">
                                                            <span class="text-[11px] text-slate-400" x-text="event.description"></span>
                                                        </template>
                                                    </div>
                                                    <!-- Entity link pills -->
                                                    <div class="mt-2.5 flex flex-wrap gap-2" x-show="event.meta && (event.meta.batch_id || event.meta.manifest_id || event.meta.run_id)">
                                                        <template x-if="event.meta && event.meta.batch_id">
                                                            <a :href="config.sortBatchShowUrlTemplate.replace('__ID__', event.meta.batch_id)"
                                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-[10px] font-bold text-indigo-600 hover:bg-indigo-100 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                                <span x-text="event.meta.batch_number"></span>
                                                                <svg class="w-2.5 h-2.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </template>
                                                        <template x-if="event.meta && event.meta.manifest_id">
                                                            <a :href="config.transportManifestShowUrlTemplate.replace('__ID__', event.meta.manifest_id)"
                                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 border border-orange-100 text-[10px] font-bold text-orange-600 hover:bg-orange-100 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                                <span x-text="event.meta.manifest_number"></span>
                                                                <svg class="w-2.5 h-2.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </template>
                                                        <template x-if="event.meta && event.meta.run_id">
                                                            <a :href="config.deliveryRunShowUrlTemplate.replace('__ID__', event.meta.run_id)"
                                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-100 text-[10px] font-bold text-amber-600 hover:bg-amber-100 transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                                <span x-text="event.meta.run_number"></span>
                                                                <svg class="w-2.5 h-2.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- ─── SECTION 2: Packages Journey ─── --}}
                        <template x-if="tracking.items.length > 0">
                            <div>
                                <!-- Section Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-md shadow-teal-200/50 flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold text-slate-900">Packages Journey</h3>
                                            <p class="text-xs text-slate-400 mt-0.5">Individual item tracking through the pipeline</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full" x-text="tracking.items.length + ' item' + (tracking.items.length === 1 ? '' : 's')"></span>
                                </div>

                                <!-- Divergence Warning -->
                                <template x-if="itemsAreDivergent()">
                                    <div class="flex items-start gap-3 p-3.5 bg-amber-50 border border-amber-200/70 rounded-xl mb-4">
                                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        </svg>
                                        <div>
                                            <p class="text-xs font-bold text-amber-800">Packages Separated</p>
                                            <p class="text-xs text-amber-600 mt-0.5 leading-relaxed">Packages have been placed into different sort batches. Check each package below for its individual route.</p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Item Cards (stacked, full width) -->
                                <div class="space-y-3">
                                    <template x-for="(item, idx) in tracking.items" :key="item.id">
                                        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-200">

                                            <!-- Item Info Row -->
                                            <div class="flex items-center gap-3.5 px-4 py-3.5">
                                                <!-- Status icon -->
                                                <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center"
                                                     :class="timelineEventDotClass(item.status)">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                                    </svg>
                                                </div>
                                                <!-- Item meta (grows) -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-0.5">
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest" x-text="'Item #' + (idx + 1)"></span>
                                                        <template x-if="item.tracking_code">
                                                            <span class="text-[10px] font-mono text-slate-400" x-text="'· ' + item.tracking_code"></span>
                                                        </template>
                                                    </div>
                                                    <p class="text-sm font-bold text-slate-800 truncate" x-text="item.description || 'No description'"></p>
                                                </div>
                                                <!-- Right: qty + status badge -->
                                                <div class="flex items-center gap-2.5 flex-shrink-0">
                                                    <span class="text-xs text-slate-500">Qty <span class="font-bold text-slate-700" x-text="item.quantity"></span></span>
                                                    <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                                          :class="itemStatusBadgeClass(item.status)"
                                                          x-text="item.status_label"></span>
                                                </div>
                                            </div>

                                            <!-- Pipeline Stepper -->
                                            <div class="px-4 pt-3 pb-4 border-t border-slate-100 bg-slate-50/40">
                                                <div class="relative flex items-start">
                                                    <div class="absolute top-3.5 left-3.5 right-3.5 h-px bg-slate-200 z-0"></div>
                                                    <template x-for="(stage, si) in itemPipelineStages(item)" :key="stage.key">
                                                        <div class="relative z-10 flex flex-col items-center" style="flex:1;">
                                                            <div class="w-7 h-7 rounded-full flex items-center justify-center border-2 transition-all duration-300"
                                                                 :class="{
                                                                     'border-emerald-500 bg-emerald-500 shadow-md shadow-emerald-200/60': stage.completed && !stage.failed,
                                                                     'border-rose-500 bg-rose-500 shadow-md shadow-rose-200/60': stage.failed,
                                                                     'border-indigo-400 bg-white ring-4 ring-indigo-100': stage.active && !stage.completed && !stage.failed,
                                                                     'border-slate-200 bg-white': !stage.completed && !stage.active && !stage.failed
                                                                 }">
                                                                <template x-if="stage.completed && !stage.failed">
                                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </template>
                                                                <template x-if="stage.failed">
                                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                </template>
                                                                <template x-if="stage.active && !stage.completed && !stage.failed">
                                                                    <div class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></div>
                                                                </template>
                                                                <template x-if="!stage.completed && !stage.active && !stage.failed">
                                                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                                                </template>
                                                            </div>
                                                            <span class="text-[9px] font-semibold text-center leading-tight mt-1.5 px-1 whitespace-nowrap"
                                                                  :class="{
                                                                      'text-emerald-600': stage.completed && !stage.failed,
                                                                      'text-rose-500': stage.failed,
                                                                      'text-indigo-600': stage.active && !stage.completed,
                                                                      'text-slate-400': !stage.completed && !stage.active && !stage.failed
                                                                  }"
                                                                  x-text="stage.label"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Quantity Audit Row -->
                                            <div class="px-4 py-3 border-t border-slate-100 bg-white">
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Quantity Audit</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <!-- Vendor declared -->
                                                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg">
                                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Declared</span>
                                                        <span class="text-xs font-bold text-slate-700" x-text="item.quantities.vendor_declared"></span>
                                                    </div>
                                                    <!-- Driver confirmed -->
                                                    <template x-if="item.quantities.driver_confirmed !== null">
                                                        <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border"
                                                             :class="item.quantities.driver_confirmed !== item.quantities.driver_expected ? 'bg-rose-50 border-rose-200' : 'bg-violet-50 border-violet-100'">
                                                            <span class="text-[9px] font-bold uppercase tracking-wide"
                                                                  :class="item.quantities.driver_confirmed !== item.quantities.driver_expected ? 'text-rose-400' : 'text-violet-400'">Driver</span>
                                                            <span class="text-xs font-bold"
                                                                  :class="item.quantities.driver_confirmed !== item.quantities.driver_expected ? 'text-rose-700' : 'text-violet-700'"
                                                                  x-text="item.quantities.driver_confirmed + (item.quantities.driver_expected ? ' / ' + item.quantities.driver_expected : '')"></span>
                                                        </div>
                                                    </template>
                                                    <!-- Warehouse received -->
                                                    <template x-if="item.quantities.warehouse_received !== null">
                                                        <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border"
                                                             :class="item.quantities.warehouse_received !== item.quantities.warehouse_expected ? 'bg-rose-50 border-rose-200' : 'bg-blue-50 border-blue-100'">
                                                            <span class="text-[9px] font-bold uppercase tracking-wide"
                                                                  :class="item.quantities.warehouse_received !== item.quantities.warehouse_expected ? 'text-rose-400' : 'text-blue-400'">Warehouse</span>
                                                            <span class="text-xs font-bold"
                                                                  :class="item.quantities.warehouse_received !== item.quantities.warehouse_expected ? 'text-rose-700' : 'text-blue-700'"
                                                                  x-text="item.quantities.warehouse_received + (item.quantities.warehouse_expected ? ' / ' + item.quantities.warehouse_expected : '')"></span>
                                                            <template x-if="item.quantities.warehouse_damaged > 0">
                                                                <span class="text-[9px] font-bold text-rose-500" x-text="'(' + item.quantities.warehouse_damaged + ' dmg)'"></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <!-- Manifest loaded → received -->
                                                    <template x-if="item.quantities.manifest_loaded !== null">
                                                        <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border"
                                                             :class="item.quantities.manifest_received !== null && item.quantities.manifest_received !== item.quantities.manifest_loaded ? 'bg-rose-50 border-rose-200' : 'bg-orange-50 border-orange-100'">
                                                            <span class="text-[9px] font-bold uppercase tracking-wide"
                                                                  :class="item.quantities.manifest_received !== null && item.quantities.manifest_received !== item.quantities.manifest_loaded ? 'text-rose-400' : 'text-orange-400'">Transit</span>
                                                            <span class="text-xs font-bold"
                                                                  :class="item.quantities.manifest_received !== null && item.quantities.manifest_received !== item.quantities.manifest_loaded ? 'text-rose-700' : 'text-orange-700'"
                                                                  x-text="item.quantities.manifest_received !== null ? item.quantities.manifest_received + ' / ' + item.quantities.manifest_loaded : item.quantities.manifest_loaded"></span>
                                                        </div>
                                                    </template>
                                                    <!-- Delivered -->
                                                    <template x-if="item.quantities.delivery_actual !== null">
                                                        <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border"
                                                             :class="item.quantities.delivery_actual !== item.quantities.delivery_expected ? 'bg-rose-50 border-rose-200' : 'bg-emerald-50 border-emerald-100'">
                                                            <span class="text-[9px] font-bold uppercase tracking-wide"
                                                                  :class="item.quantities.delivery_actual !== item.quantities.delivery_expected ? 'text-rose-400' : 'text-emerald-400'">Delivered</span>
                                                            <span class="text-xs font-bold"
                                                                  :class="item.quantities.delivery_actual !== item.quantities.delivery_expected ? 'text-rose-700' : 'text-emerald-700'"
                                                                  x-text="item.quantities.delivery_actual + (item.quantities.delivery_expected ? ' / ' + item.quantities.delivery_expected : '')"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Toggle -->
                                            <button type="button" @@click="toggleItemDetails(item.id)"
                                                    class="w-full flex items-center justify-between px-4 py-2.5 border-t border-slate-100 text-[11px] font-semibold text-slate-500 hover:text-indigo-600 hover:bg-slate-50/50 transition-all">
                                                <span x-text="isItemExpanded(item.id) ? 'Hide journey details' : 'View full journey details'"></span>
                                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200"
                                                     :class="isItemExpanded(item.id) ? 'rotate-180' : ''"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>

                                            <!-- Expanded Details -->
                                            <div x-show="isItemExpanded(item.id)" x-cloak class="border-t border-slate-100 bg-slate-50/60 divide-y divide-slate-100">

                                                <!-- Sort Batch -->
                                                <template x-if="item.sort_batch">
                                                    <div class="p-4">
                                                        <div class="flex items-center justify-between mb-2.5">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-5 h-5 rounded-md bg-indigo-100 flex items-center justify-center">
                                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                                </div>
                                                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wide">Sort Batch</span>
                                                            </div>
                                                            <a :href="item.sort_batch.show_url" class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 border border-indigo-100 rounded-md text-[10px] font-bold text-indigo-600 hover:bg-indigo-100 transition-colors">
                                                                <span x-text="item.sort_batch.batch_number"></span>
                                                                <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </div>
                                                        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                                                            <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                <span class="text-slate-400">Mode</span>
                                                                <span class="font-semibold text-slate-700" x-text="item.sort_batch.dispatch_mode_label"></span>
                                                            </div>
                                                            <template x-if="item.sort_batch.origin_warehouse || item.sort_batch.destination_warehouse">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Route</span>
                                                                    <span class="font-semibold text-slate-700 text-right" x-text="(item.sort_batch.origin_warehouse || '?') + ' → ' + (item.sort_batch.destination_warehouse || '?')"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.sort_batch.quantity_allocated">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Qty allocated</span>
                                                                    <span class="font-semibold text-slate-700" x-text="item.sort_batch.quantity_allocated"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.sort_batch.sealed_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Sealed</span>
                                                                    <span class="font-semibold text-slate-700" x-text="formatDateTime(item.sort_batch.sealed_at)"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Transport Manifest -->
                                                <template x-if="item.sort_batch && item.sort_batch.dispatch_mode === 'transfer' && item.transport_manifest">
                                                    <div class="p-4">
                                                        <div class="flex items-center justify-between mb-2.5">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-5 h-5 rounded-md bg-orange-100 flex items-center justify-center">
                                                                    <svg class="w-3 h-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                                                                </div>
                                                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wide">Transport Manifest</span>
                                                            </div>
                                                            <a :href="item.transport_manifest.show_url" class="inline-flex items-center gap-1 px-2 py-0.5 bg-orange-50 border border-orange-100 rounded-md text-[10px] font-bold text-orange-600 hover:bg-orange-100 transition-colors">
                                                                <span x-text="item.transport_manifest.manifest_number"></span>
                                                                <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </div>
                                                        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                                                            <template x-if="item.transport_manifest.driver_name">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Driver</span>
                                                                    <span class="font-semibold text-slate-700" x-text="item.transport_manifest.driver_name"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.transport_manifest.origin_warehouse || item.transport_manifest.destination_warehouse">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Route</span>
                                                                    <span class="font-semibold text-slate-700 text-right" x-text="(item.transport_manifest.origin_warehouse || '?') + ' → ' + (item.transport_manifest.destination_warehouse || '?')"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.transport_manifest.dispatched_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Dispatched</span>
                                                                    <span class="font-semibold text-slate-700" x-text="formatDateTime(item.transport_manifest.dispatched_at)"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.transport_manifest.arrived_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Arrived</span>
                                                                    <span class="font-semibold text-slate-700" x-text="formatDateTime(item.transport_manifest.arrived_at)"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.transport_manifest.received_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Received</span>
                                                                    <span class="font-semibold text-emerald-600" x-text="formatDateTime(item.transport_manifest.received_at)"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Delivery Run -->
                                                <template x-if="item.delivery_run">
                                                    <div class="p-4">
                                                        <div class="flex items-center justify-between mb-2.5">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-5 h-5 rounded-md bg-amber-100 flex items-center justify-center">
                                                                    <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                                </div>
                                                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wide">Delivery Run</span>
                                                            </div>
                                                            <a :href="item.delivery_run.show_url" class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 border border-amber-100 rounded-md text-[10px] font-bold text-amber-600 hover:bg-amber-100 transition-colors">
                                                                <span x-text="item.delivery_run.run_number"></span>
                                                                <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </div>
                                                        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                                                            <template x-if="item.delivery_run.driver_name">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Driver</span>
                                                                    <span class="font-semibold text-slate-700" x-text="item.delivery_run.driver_name"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_run.warehouse">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">From warehouse</span>
                                                                    <span class="font-semibold text-slate-700" x-text="item.delivery_run.warehouse"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_run.dispatched_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Dispatched</span>
                                                                    <span class="font-semibold text-slate-700" x-text="formatDateTime(item.delivery_run.dispatched_at)"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_run.completed_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Completed</span>
                                                                    <span class="font-semibold text-emerald-600" x-text="formatDateTime(item.delivery_run.completed_at)"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Delivery Stop -->
                                                <template x-if="item.delivery_stop">
                                                    <div class="p-4">
                                                        <div class="flex items-center gap-2 mb-2.5">
                                                            <div class="w-5 h-5 rounded-md bg-emerald-100 flex items-center justify-center">
                                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                                            </div>
                                                            <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wide">Delivery Stop</span>
                                                        </div>
                                                        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                                                            <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                <span class="text-slate-400">Recipient</span>
                                                                <span class="font-semibold text-slate-700" x-text="item.delivery_stop.recipient_name"></span>
                                                            </div>
                                                            <template x-if="item.delivery_stop.recipient_phone">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Phone</span>
                                                                    <span class="font-semibold text-slate-700" x-text="item.delivery_stop.recipient_phone"></span>
                                                                </div>
                                                            </template>
                                                            <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                <span class="text-slate-400">Status</span>
                                                                <span class="font-bold capitalize px-2 py-0.5 rounded-md text-[10px]"
                                                                      :class="{
                                                                          'bg-emerald-100 text-emerald-700': item.delivery_stop.status === 'delivered',
                                                                          'bg-rose-100 text-rose-600': item.delivery_stop.status === 'failed',
                                                                          'bg-amber-100 text-amber-700': item.delivery_stop.status === 'arrived',
                                                                          'bg-slate-100 text-slate-500': item.delivery_stop.status === 'pending'
                                                                      }"
                                                                      x-text="item.delivery_stop.status"></span>
                                                            </div>
                                                            <template x-if="item.delivery_stop.arrived_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Arrived at stop</span>
                                                                    <span class="font-semibold text-slate-700" x-text="formatDateTime(item.delivery_stop.arrived_at)"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_stop.delivered_at">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Delivered</span>
                                                                    <span class="font-semibold text-emerald-600" x-text="formatDateTime(item.delivery_stop.delivered_at)"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_stop.failure_reason">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Failure reason</span>
                                                                    <span class="font-semibold text-rose-600 text-right max-w-[60%]" x-text="item.delivery_stop.failure_reason"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_outcome">
                                                                <div class="flex justify-between items-center px-3 py-2 text-[11px]">
                                                                    <span class="text-slate-400">Qty delivered</span>
                                                                    <span class="font-semibold text-slate-700" x-text="(item.delivery_outcome.delivered_quantity ?? '—') + ' / ' + (item.delivery_outcome.expected_quantity ?? '—')"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.delivery_stop.has_proof_photo">
                                                                <div class="flex items-center gap-2 px-3 py-2 bg-emerald-50">
                                                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                                    <span class="text-[10px] font-bold text-emerald-700">Proof photo captured</span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Not in pipeline yet -->
                                                <template x-if="!item.sort_batch && !item.delivery_run && !item.delivery_stop">
                                                    <div class="px-4 py-5 text-center">
                                                        <p class="text-[11px] text-slate-400 italic">This item has not entered the sorting pipeline yet.</p>
                                                    </div>
                                                </template>

                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>

            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- PACKAGES TAB (full editor)              -->
            <!-- ═══════════════════════════════════════ -->
            @if($editConfig)
            <div x-show="activeTab === 'packages'" x-cloak>
                @include('admin.shipments._packages-editor')
            </div>
            @endif

            <!-- ═══════════════════════════════════════ -->
            <!-- CHARGES LEDGER (shown inside Packages)  -->
            <!-- ═══════════════════════════════════════ -->
            <div>
                <!-- Loading state -->
                <div x-show="false && chargesLoading" class="hidden mb-5 flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-8">
                    <svg class="w-6 h-6 animate-spin text-slate-300" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div x-show="false && !chargesLoading && chargesLoaded" class="hidden mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {{-- ─── Header / actions ───────────────────────────── --}}
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Charges Ledger</h3>
                            <p class="text-xs text-slate-500">Shipment-level pickup and station charges.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button x-show="canManageCharges && !hasPickupFee()" @@click="seedPickupFee()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                <span>Add Pickup Fee</span>
                                <span class="text-slate-400" x-text="'(default GHS ' + (chargesDefaults.pickup_fee ?? 0).toFixed(2) + ')'"></span>
                            </button>
                            <button x-show="canManageCharges" @@click="openAddCharge()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Charge
                            </button>
                        </div>
                    </div>

                    {{-- ─── Charges list ───────────────────────────────── --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr class="text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-2.5">Type</th>
                                    <th class="px-4 py-2.5">Payer</th>
                                    <th class="px-4 py-2.5">Due at</th>
                                    <th class="px-4 py-2.5 text-right">Amount</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr x-show="chargesData.length === 0">
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                                        No charges on this shipment yet.
                                    </td>
                                </tr>
                                <template x-for="charge in chargesData" :key="charge.id">
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/40 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                      :class="{
                                                          'bg-emerald-50 text-emerald-700': charge.direction === 'revenue',
                                                          'bg-rose-50 text-rose-700': charge.direction === 'expense',
                                                      }"
                                                      x-text="formatChargeType(charge.charge_type)"></span>
                                            </div>
                                            <template x-if="charge.notes">
                                                <p class="mt-1 text-[11px] text-slate-400" x-text="charge.notes"></p>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="capitalize text-slate-700" x-text="charge.payer_type"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-slate-600" x-text="formatStage(charge.due_stage)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold"
                                            :class="charge.direction === 'expense' ? 'text-rose-600' : 'text-slate-900'"
                                            x-text="(charge.direction === 'expense' ? '-' : '') + 'GHS ' + Number(charge.amount).toFixed(2)"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                  :class="{
                                                      'bg-amber-50 text-amber-700': ['pending','draft'].includes(charge.status),
                                                      'bg-emerald-50 text-emerald-700': charge.status === 'paid',
                                                      'bg-slate-100 text-slate-600': charge.status === 'waived',
                                                      'bg-rose-50 text-rose-600': charge.status === 'cancelled',
                                                  }"
                                                  x-text="charge.status.charAt(0).toUpperCase() + charge.status.slice(1)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <button x-show="canManageCharges && ['pending','draft'].includes(charge.status)"
                                                        @@click="openMarkPaid(charge)"
                                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors">Mark Paid</button>
                                                <button x-show="canManageCharges && ['pending','draft'].includes(charge.status)"
                                                        @@click="waiveCharge(charge)"
                                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Waive</button>
                                                <button x-show="canManageCharges && ['pending','draft'].includes(charge.status)"
                                                        @@click="cancelCharge(charge)"
                                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Cancel</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ─── Pickup Fee modal ─────────────────────────────── --}}
                <div x-show="pickupFeeModal.open" x-cloak x-transition.opacity
                     class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" style="display:none">
                    <div @@click.away="closePickupFeeModal()" x-show="pickupFeeModal.open" x-transition
                         class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Pickup Fee</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Amount the pickup driver collects from the vendor.</p>
                            </div>
                            <button @@click="closePickupFeeModal()" class="p-1.5 rounded-lg hover:bg-slate-100">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Amount (GHS)</label>
                                <input type="number" step="0.01" min="0" x-model.number="pickupFeeModal.amount"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Notes (optional)</label>
                                <textarea x-model="pickupFeeModal.notes" rows="2"
                                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white"
                                          placeholder="e.g. Vendor will pay cash at pickup"></textarea>
                            </div>

                            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700">
                                <input type="checkbox" x-model="pickupFeeModal.mark_paid" :disabled="pickupFeeIsPaid()" class="rounded border-slate-300 disabled:opacity-60">
                                <span>Mark as paid</span>
                            </label>

                            <template x-if="pickupFeeModal.mark_paid">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Payment method</label>
                                        <select x-model="pickupFeeModal.payment_method"
                                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white">
                                            <option value="cash">Cash</option>
                                            <option value="momo">Mobile Money</option>
                                            <option value="bank">Bank</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Reference</label>
                                        <input type="text" x-model="pickupFeeModal.payment_reference"
                                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white"
                                               placeholder="MoMo txn ID, receipt #, etc.">
                                    </div>
                                </div>
                            </template>
                            <p x-show="pickupFeeIsPaid()" class="rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                                This pickup fee is already paid. You can correct the amount, notes, or payment reference, but it will remain paid.
                            </p>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-2">
                            <button @@click="closePickupFeeModal()"
                                    class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button @@click="submitPickupFee()" :disabled="pickupFeeModal.saving || pickupFeeModal.amount === ''"
                                    class="px-4 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!pickupFeeModal.saving" x-text="pickupFeeModal.chargeId ? 'Save Pickup Fee' : 'Set Pickup Fee'"></span>
                                <span x-show="pickupFeeModal.saving">Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ─── Mark Paid modal ──────────────────────────────── --}}
                <div x-show="markPaidOpen" x-cloak x-transition.opacity
                     class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" style="display:none">
                    <div @@click.away="markPaidOpen = false" x-show="markPaidOpen" x-transition
                         class="w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-900">Mark Paid</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5" x-text="markPaidCharge ? ('GHS ' + Number(markPaidCharge.amount).toFixed(2) + ' — ' + formatChargeType(markPaidCharge.charge_type)) : ''"></p>
                        </div>
                        <div class="p-6 space-y-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Payment method</label>
                                <select x-model="markPaidForm.payment_method"
                                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white">
                                    <option value="cash">Cash</option>
                                    <option value="momo">Mobile Money</option>
                                    <option value="bank">Bank</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Reference (optional)</label>
                                <input type="text" x-model="markPaidForm.payment_reference"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white">
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-2">
                            <button @@click="markPaidOpen = false"
                                    class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button @@click="submitMarkPaid()" :disabled="chargeSubmitting || !markPaidForm.payment_method"
                                    class="px-4 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors disabled:opacity-50">
                                <span x-show="!chargeSubmitting">Mark Paid</span>
                                <span x-show="chargeSubmitting">Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- RECEIVING TAB                           -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'receiving'" x-cloak>

                <!-- Loading -->
                <div x-show="receiving.loading" class="flex items-center justify-center py-20">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:0ms"></div>
                        <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:150ms"></div>
                        <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:300ms"></div>
                    </div>
                </div>

                <!-- Pickup details and driver -->
                <div x-show="false && !receiving.loading && !receiving.canReceive" x-cloak class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6.75V15m0 0l-2.25-2.25M9 15l2.25-2.25M15 6.75h.01M18.25 17.25V6.75A2.25 2.25 0 0016 4.5H8a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 008 19.5h8a2.25 2.25 0 002.25-2.25z"/></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Pickup Details</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Pickup contact, driver, and warehouse handoff summary.</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button type="button"
                                    x-show="assignmentHistory.length > 1"
                                    @@click="openAssignmentHistoryModal()"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
                                Driver History
                            </button>
                            <button type="button"
                                    x-show="canManage"
                                    @@click="openPickupEditModal()"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
                                Edit Pickup
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2.5 px-5 py-4 text-[12px]">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-36">Location:</p>
                            <p class="min-w-0 font-semibold text-slate-900">
                                <span x-text="pickupLocationSummary()"></span>
                                <span x-show="shipment.pickup_landmark" class="text-slate-500" x-text="' - ' + shipment.pickup_landmark"></span>
                            </p>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-36">Instructions:</p>
                            <p class="min-w-0 font-medium text-slate-700" x-text="shipment.pickup_instructions || '-'"></p>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-36">Pickup Driver:</p>
                            <p class="min-w-0 font-semibold text-slate-900">
                                <span x-text="assignment ? (assignmentDriverName() + ', ' + assignmentDriverPhone()) : 'Unassigned'"></span>
                                <button type="button"
                                        x-show="canCreatePickupAssignment()"
                                        @@click="openAssignPickupDriver()"
                                        class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800">Assign</button>
                                <button type="button"
                                        x-show="canManage && canEditCurrentAssignment()"
                                        @@click="openEditAssignment()"
                                        class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800">Change</button>
                            </p>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-36">Target Warehouse:</p>
                            <p class="min-w-0 font-semibold text-slate-900">
                                <span x-text="assignmentWarehouseName()"></span>
                                <span x-show="assignmentWarehouseCode()" class="text-slate-500" x-text="' (' + assignmentWarehouseCode() + ')'"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Not picked up yet -->
                <template x-if="false && !receiving.loading && !receiving.canReceive">
                    <div class="text-center py-16">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-amber-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800">Waiting for Pickup</p>
                        <p class="text-xs text-slate-500 mt-1">The driver hasn't confirmed pickup yet. If the packages have arrived, you can mark it as picked up manually.</p>
                        <p x-show="!assignment" class="mt-3 text-xs font-semibold text-indigo-600">Assign a pickup driver before manually completing pickup.</p>
                        <button x-show="assignment" @@click="adminCompletePickup()" :disabled="receiving.completingPickup"
                                class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-xl transition-all disabled:opacity-50 shadow-sm">
                            <svg x-show="receiving.completingPickup" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <svg x-show="!receiving.completingPickup" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="receiving.completingPickup ? 'Completing...' : 'Mark as Picked Up'"></span>
                        </button>
                    </div>
                </template>

		                <!-- Packages workspace -->
		                <template x-if="!receiving.loading">
		                    <div class="space-y-5">
                                <div x-show="!receiving.canReceive" class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800" style="display:none">
                                    <p class="font-bold">Receiving is not active yet.</p>
                                    <p class="mt-1 text-xs" x-text="receivingRestrictionMessage()"></p>
                                </div>
                                <div class="grid gap-5 lg:grid-cols-2">
                                    <!-- Pickup details and driver -->
                                    <div class="h-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="flex items-start gap-3">
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6.75V15m0 0l-2.25-2.25M9 15l2.25-2.25M15 6.75h.01M18.25 17.25V6.75A2.25 2.25 0 0016 4.5H8a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 008 19.5h8a2.25 2.25 0 002.25-2.25z"/></svg>
                                                </span>
                                                <div>
                                                    <h3 class="text-sm font-black text-slate-900">Pickup Details</h3>
                                                    <p class="mt-0.5 text-xs text-slate-500">Pickup contact, driver, and warehouse handoff summary.</p>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-2 xl:justify-end">
                                                <button type="button"
                                                        x-show="assignmentHistory.length > 1"
                                                        @@click="openAssignmentHistoryModal()"
                                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
                                                    Driver History
                                                </button>
                                                <button type="button"
                                                        x-show="canManage"
                                                        @@click="openPickupEditModal()"
                                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
                                                    Edit Pickup
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-2.5 px-5 py-4 text-[12px]">
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Location:</p>
                                                <p class="min-w-0 font-semibold text-slate-900">
                                                    <span x-text="pickupLocationSummary()"></span>
                                                    <span x-show="shipment.pickup_landmark" class="text-slate-500" x-text="' - ' + shipment.pickup_landmark"></span>
                                                </p>
                                            </div>
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Instructions:</p>
                                                <p class="min-w-0 font-medium text-slate-700" x-text="shipment.pickup_instructions || '-'"></p>
                                            </div>
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Pickup Driver:</p>
                                                <p class="min-w-0 font-semibold text-slate-900">
                                                    <span x-text="assignment ? (assignmentDriverName() + ', ' + assignmentDriverPhone()) : 'Unassigned'"></span>
                                                    <button type="button"
                                                            x-show="canCreatePickupAssignment()"
                                                            @@click="openAssignPickupDriver()"
                                                            class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800">Assign</button>
                                                    <button type="button"
                                                            x-show="canManage && canEditCurrentAssignment()"
                                                            @@click="openEditAssignment()"
                                                            class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800">Change</button>
                                                </p>
                                            </div>
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Pickup Fee:</p>
                                                <p class="min-w-0 font-semibold text-slate-900">
                                                    <span x-text="pickupFeeValueLabel()"></span>
                                                    <button type="button"
                                                            x-show="canManageCharges"
                                                            @@click="openPickupFeeModal()"
                                                            class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800"
                                                            x-text="pickupFeeActionLabel()"></button>
                                                </p>
                                            </div>
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Target Warehouse:</p>
                                                <p class="min-w-0 font-semibold text-slate-900">
                                                    <span x-text="assignmentWarehouseName()"></span>
                                                    <span x-show="assignmentWarehouseCode()" class="text-slate-500" x-text="' (' + assignmentWarehouseCode() + ')'"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

		                        <!-- Drop-off type -->
		                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
		                            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
		                                <div class="flex items-center gap-3">
		                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
		                                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
		                                    </span>
		                                    <div>
		                                        <p class="text-sm font-bold text-slate-900">Drop-off Type</p>
		                                        <p class="text-xs text-slate-500">Controls whether delivery destination is shared or set package by package.</p>
		                                    </div>
		                                </div>
		                                <div class="flex items-center justify-end">
		                                    <select :value="shipmentDestinationMode()"
		                                            @@change="handleReceivingDestinationModeChange($event)"
		                                            :disabled="receiving.dropOffSaving"
		                                            class="min-w-[190px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
		                                        <option value="single">One Drop-off</option>
		                                        <option value="per_item">Multiple Drop-offs</option>
		                                    </select>
		                                </div>
		                            </div>
		                            <div class="px-5 py-4">
		                                <div class="min-w-0">
		                                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400" x-text="isPerItemMode() ? 'Package destinations' : 'Shared destination'"></p>
		                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="isPerItemMode() ? 'Set recipient and location inside each package.' : receivingSharedDestinationSummary()"></p>
		                                        <p class="mt-0.5 text-xs text-slate-500" x-text="isPerItemMode() ? 'Use this when packages are going to different phone numbers or places.' : 'Use this when the packages go to one recipient or location.'"></p>
		                                    </div>
		                                    <button type="button"
		                                            x-show="!isPerItemMode()"
		                                            @@click="openReceivingSharedDestinationModal()"
		                                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-slate-700 underline decoration-slate-300 underline-offset-4 transition-colors hover:text-slate-950 hover:decoration-slate-700">
		                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5"/></svg>
		                                        Edit Shared Destination
		                                    </button>
		                                    <span x-show="isPerItemMode()" class="inline-flex shrink-0 text-xs font-bold text-slate-500">Per-package delivery is active</span>
		                            </div>
		                        </div>
                                </div>

	                        <!-- Packages Table -->
	                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
	                            <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
	                                <div class="flex items-start gap-3">
	                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
	                                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
	                                    </span>
	                                    <div>
	                                        <h3 class="text-sm font-bold text-slate-900">Packages</h3>
	                                        <p class="text-xs text-slate-500">Open a package to receive it or save delivery changes.</p>
	                                        <p x-show="receiving.autoGroupLockReason" class="mt-1 text-[10px] text-amber-600" x-text="receiving.autoGroupLockReason" style="display:none"></p>
	                                    </div>
	                                </div>
	                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center lg:justify-end">
	                                    <button type="button"
	                                            @@click="openReceivingAddPackageModal()"
	                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
	                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
	                                        Add Package
	                                    </button>
	                                    <button type="button"
	                                            @@click="autoGroupReceivingPackagesByPhone()"
	                                            :disabled="receiving.autoGrouping || !receiving.canAutoGroup"
	                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold transition-all shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
	                                            :class="receiving.canAutoGroup ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500'">
	                                        <svg x-show="!receiving.autoGrouping" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
	                                        <svg x-show="receiving.autoGrouping" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                        <span x-text="receiving.autoGrouping ? 'Grouping...' : 'Auto-group by Phone'"></span>
	                                    </button>
	                                    <button type="button"
	                                            @@click="loadReceiving()"
	                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
	                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
	                                        Refresh
	                                    </button>
	                                </div>
	                            </div>
	                            <div class="overflow-x-auto">
	                                <table class="min-w-[1180px] w-full divide-y divide-slate-100 text-left">
	                                    <thead class="bg-slate-50">
	                                        <tr>
	                                            <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Package</th>
	                                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">Expected</th>
		                                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">Received</th>
		                                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">Remaining</th>
		                                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
		                                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">Condition</th>
		                                            <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Recipient</th>
		                                            <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Delivery Method</th>
		                                            <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Custody</th>
	                                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wide text-slate-500">Actions</th>
	                                        </tr>
	                                    </thead>
		                                    <tbody class="divide-y divide-slate-100">
		                                        <template x-if="receiving.packages.length === 0">
		                                            <tr>
		                                                <td colspan="10" class="px-4 py-12 text-center">
		                                                    <p class="text-sm font-semibold text-slate-600">No packages found for receiving.</p>
		                                                    <p class="mt-1 text-xs text-slate-400">Add a package here if more packages arrived during receiving.</p>
		                                                </td>
	                                            </tr>
	                                        </template>
	                                        <template x-for="pkg in receiving.packages" :key="pkg.shipment_item_id">
	                                            <tr class="align-top hover:bg-slate-50/60">
	                                                <td class="px-4 py-4">
	                                                    <div class="min-w-0 space-y-1">
	                                                        <p class="truncate text-[11px] font-bold text-slate-900" x-text="pkg.description || 'No description'"></p>
	                                                        <p x-show="pkg.tracking_code" class="mt-0.5 text-[10px] font-mono text-slate-400" x-text="pkg.tracking_code"></p>
	                                                    </div>
	                                                </td>
	                                                <td class="px-4 py-4 text-center">
	                                                    <span class="text-[11px] font-bold text-slate-700" x-text="receivingExpectedQuantity(pkg)"></span>
	                                                </td>
	                                                <td class="px-4 py-4 text-center">
	                                                    <span class="text-[11px] font-bold text-emerald-700" x-text="pkg.received_quantity"></span>
	                                                </td>
	                                                <td class="px-4 py-4 text-center">
	                                                    <span class="text-[11px] font-bold text-amber-700" x-text="receivingPendingQuantity(pkg)"></span>
		                                                </td>
		                                                <td class="px-4 py-4 text-center">
		                                                    <span class="text-[10px] font-black uppercase tracking-wide"
		                                                          :class="receivingPackageStatusTextClass(pkg)"
		                                                          x-text="receivingPackageStatusLabel(pkg)"></span>
		                                                </td>
		                                                <td class="px-4 py-4 text-center">
		                                                    <span x-show="receivingPackageIsReceived(pkg)"
		                                                          class="text-[10px] font-bold uppercase tracking-wide"
		                                                          :class="receivingConditionTextClass(pkg.condition_status)"
		                                                          x-text="receivingConditionLabel(pkg.condition_status)" style="display:none"></span>
		                                                    <span x-show="!receivingPackageIsReceived(pkg)"
		                                                          class="text-[10px] font-bold uppercase tracking-wide text-slate-400" style="display:none">-</span>
		                                                </td>
	                                                <td class="px-4 py-4">
	                                                    <div class="space-y-1 text-[11px]">
	                                                        <p class="flex max-w-xs items-center gap-1.5 truncate font-semibold text-slate-800">
	                                                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
	                                                            <span class="truncate" x-text="pkg.delivery_recipient_name || 'No recipient set'"></span>
	                                                        </p>
	                                                        <p x-show="pkg.delivery_recipient_phone" class="flex items-center gap-1.5 text-slate-500">
	                                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372a1.125 1.125 0 00-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293a1.125 1.125 0 01-1.21.38 12.035 12.035 0 01-7.143-7.143 1.125 1.125 0 01.38-1.21l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 3.102A1.125 1.125 0 005.872 2.25H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
	                                                            <span x-text="pkg.delivery_recipient_phone"></span>
	                                                        </p>
	                                                        <p x-show="pkg.delivery_town" class="flex max-w-xs items-center gap-1.5 truncate text-slate-500">
	                                                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
	                                                            <span class="truncate" x-text="pkg.delivery_town"></span>
	                                                        </p>
	                                                    </div>
	                                                </td>
	                                                <td class="px-4 py-4">
	                                                    <div class="space-y-1 text-[11px]">
	                                                        <p class="flex items-center gap-1.5 font-bold"
	                                                           :class="pkg.delivery_method === 'bus_handoff' ? 'text-violet-700' : 'text-orange-700'">
	                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h7.5m-7.5 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m13.5 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.875c.621 0 1.125-.504 1.125-1.125V14.25m-17.25 0h17.25m-17.25 0V6.375c0-.621.504-1.125 1.125-1.125h10.5c.621 0 1.125.504 1.125 1.125v7.875"/></svg>
	                                                            <span x-text="pkg.delivery_method === 'bus_handoff' ? 'Bus courier' : 'Direct delivery'"></span>
	                                                        </p>
	                                                        <p class="flex items-center gap-1.5 font-semibold"
	                                                           :class="receivingDeliveryFeeClass(pkg)">
	                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m-3-2.818c.696.424 1.626.682 3 .682 2.25 0 3-1.007 3-2.25 0-1.244-.75-2.25-3-2.25s-3-1.006-3-2.25S9.75 6.864 12 6.864c1.374 0 2.304.258 3 .682"/></svg>
	                                                            <span x-text="receivingDeliveryFeeLabel(pkg)"></span>
	                                                        </p>
	                                                    </div>
	                                                </td>
	                                                <td class="px-4 py-4">
	                                                    <button type="button"
	                                                            @@click="openPackageCustodyModal(pkg)"
	                                                            :disabled="!packageCustodyCanOpen(pkg)"
		                                                            class="block text-left disabled:cursor-default">
		                                                        <span class="block truncate text-[11px] font-bold"
		                                                              :class="packageCustodyClass(pkg)"
		                                                              x-text="packageCustodySummary(pkg)"></span>
		                                                        <span class="mt-1 block space-y-0.5 text-[10px] text-slate-400">
		                                                            <template x-for="line in packageCustodyDetailLines(pkg)" :key="line">
		                                                                <span class="block whitespace-nowrap" x-text="line"></span>
		                                                            </template>
		                                                        </span>
		                                                    </button>
	                                                </td>
	                                                <td class="px-4 py-4 text-right">
	                                                    <div class="flex flex-col items-end gap-1.5">
	                                                        <button type="button"
	                                                                @@click="openReceivingPackageModal(pkg, 1)"
	                                                                class="inline-flex items-center gap-1 text-[11px] font-black text-slate-700 underline decoration-slate-300 underline-offset-4 transition-colors hover:text-slate-950 hover:decoration-slate-700">
	                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5"/></svg>
	                                                            <span x-text="receivingPackageIsReceived(pkg) ? 'Edit / Update' : 'Receive'"></span>
	                                                        </button>
	                                                        <button type="button"
	                                                                @@click="openReceivingPhotosModal(pkg)"
	                                                                class="inline-flex items-center gap-1 text-[11px] font-black text-slate-700 underline decoration-slate-300 underline-offset-4 transition-colors hover:text-slate-950 hover:decoration-slate-700">
	                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.827 6.175A2.31 2.31 0 019.186 4.5h5.628a2.31 2.31 0 012.36 1.675l.365 1.286A1.875 1.875 0 0019.342 8.8H20.25A2.25 2.25 0 0122.5 11.05v6.2a2.25 2.25 0 01-2.25 2.25H3.75a2.25 2.25 0 01-2.25-2.25v-6.2A2.25 2.25 0 013.75 8.8h.908a1.875 1.875 0 001.803-1.339l.366-1.286z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 13.5a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
	                                                            View Photos
	                                                        </button>
	                                                        <button type="button"
	                                                                @@click="openReceivingSplitModal(pkg)"
	                                                                :disabled="!pkg.can_split || pkg.vendor_photos.length < 2"
	                                                                class="inline-flex items-center gap-1 text-[11px] font-black underline underline-offset-4 transition-colors disabled:cursor-not-allowed disabled:opacity-40"
	                                                                :class="pkg.can_split && pkg.vendor_photos.length >= 2 ? 'text-slate-700 decoration-slate-300 hover:text-slate-950 hover:decoration-slate-700' : 'text-slate-400 decoration-slate-200'">
	                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.047 1.124-.047s.751.016 1.124.047c1.131.094 1.976 1.057 1.976 2.192V7.5M8.25 7.5h7.5M8.25 7.5l-.621 8.696A2.25 2.25 0 009.873 18.6h4.254a2.25 2.25 0 002.244-2.404L15.75 7.5"/></svg>
	                                                            Split Photos
	                                                        </button>
	                                                        <button type="button"
	                                                                @@click="removeReceivingPackage(pkg)"
	                                                                :disabled="!pkg.can_delete"
	                                                                class="inline-flex items-center gap-1 text-[11px] font-black underline underline-offset-4 transition-colors disabled:cursor-not-allowed disabled:opacity-40"
	                                                                :class="pkg.can_delete ? 'text-rose-700 decoration-rose-300 hover:text-rose-900 hover:decoration-rose-700' : 'text-slate-400 decoration-slate-200'">
	                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
	                                                            Remove
	                                                        </button>
	                                                        <div x-show="receivingPackageIsReceived(pkg)" class="inline-flex" style="display:none">
	                                                            <button type="button"
	                                                                    @@click="openReceivingLabelPrintModal(pkg)"
	                                                                    class="inline-flex items-center gap-1 text-[11px] font-black text-slate-700 underline decoration-slate-300 underline-offset-4 transition-colors hover:text-slate-950 hover:decoration-slate-700">
	                                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5V6A2.25 2.25 0 019 3.75h6A2.25 2.25 0 0117.25 6v1.5m-10.5 0h10.5m-10.5 0A2.25 2.25 0 004.5 9.75v3A2.25 2.25 0 006.75 15h.75m9.75-7.5A2.25 2.25 0 0119.5 9.75v3A2.25 2.25 0 0117.25 15h-.75m-9 0v5.25h9V15m-9 0h9"/></svg>
	                                                                Print Labels
	                                                            </button>
	                                                        </div>
	                                                    </div>
	                                                </td>
	                                            </tr>
	                                        </template>
	                                    </tbody>
	                                </table>
	                            </div>

	                            <!-- Table Footer: Finalize -->
	                            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
	                                <div>
	                                    <p class="text-[11px] font-bold text-slate-800">Finalize Receiving</p>
	                                    <p class="text-[10px] text-slate-500">Mark all packages as received and move shipment to warehouse status.</p>
	                                </div>
	                                <button @@click="openFinalizeConfirm()" :disabled="!canFinalizeReceiving()"
	                                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-slate-800 disabled:opacity-50">
	                                    <span x-text="finalizeReceivingButtonLabel()"></span>
	                                </button>
	                            </div>
	                        </div>

                            <!-- Charges Ledger -->
                            <div class="hidden">
                                <div x-show="false && chargesLoading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-8">
                                    <svg class="w-6 h-6 animate-spin text-slate-300" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>

                                <div x-show="false && !chargesLoading && chargesLoaded" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-900">Charges Ledger</h3>
                                            <p class="text-xs text-slate-500">Shipment-level pickup and station charges.</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button x-show="canManageCharges && !hasPickupFee()" @@click="seedPickupFee()"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                <span>Add Pickup Fee</span>
                                                <span class="text-slate-400" x-text="'(default GHS ' + (chargesDefaults.pickup_fee ?? 0).toFixed(2) + ')'"></span>
                                            </button>
                                            <button x-show="canManageCharges" @@click="openAddCharge()"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Add Charge
                                            </button>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-100">
                                                <tr class="text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th class="px-4 py-2.5">Type</th>
                                                    <th class="px-4 py-2.5">Payer</th>
                                                    <th class="px-4 py-2.5">Due at</th>
                                                    <th class="px-4 py-2.5 text-right">Amount</th>
                                                    <th class="px-4 py-2.5">Status</th>
                                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr x-show="chargesData.length === 0">
                                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                                                        No charges on this shipment yet.
                                                    </td>
                                                </tr>
                                                <template x-for="charge in chargesData" :key="charge.id">
                                                    <tr class="border-b border-slate-50 hover:bg-slate-50/40 transition-colors">
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                                      :class="{
                                                                          'bg-emerald-50 text-emerald-700': charge.direction === 'revenue',
                                                                          'bg-rose-50 text-rose-700': charge.direction === 'expense',
                                                                      }"
                                                                      x-text="formatChargeType(charge.charge_type)"></span>
                                                            </div>
                                                            <template x-if="charge.notes">
                                                                <p class="mt-1 text-[11px] text-slate-400" x-text="charge.notes"></p>
                                                            </template>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="capitalize text-slate-700" x-text="charge.payer_type"></span>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-slate-600" x-text="formatStage(charge.due_stage)"></span>
                                                        </td>
                                                        <td class="px-4 py-3 text-right font-semibold"
                                                            :class="charge.direction === 'expense' ? 'text-rose-600' : 'text-slate-900'"
                                                            x-text="(charge.direction === 'expense' ? '-' : '') + 'GHS ' + Number(charge.amount).toFixed(2)"></td>
                                                        <td class="px-4 py-3">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                                  :class="{
                                                                      'bg-amber-50 text-amber-700': ['pending','draft'].includes(charge.status),
                                                                      'bg-emerald-50 text-emerald-700': charge.status === 'paid',
                                                                      'bg-slate-100 text-slate-600': charge.status === 'waived',
                                                                      'bg-rose-50 text-rose-600': charge.status === 'cancelled',
                                                                  }"
                                                                  x-text="charge.status.charAt(0).toUpperCase() + charge.status.slice(1)"></span>
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <div class="inline-flex items-center gap-1">
                                                                <button x-show="canManageCharges && ['pending','draft'].includes(charge.status)"
                                                                        @@click="openMarkPaid(charge)"
                                                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors">Mark Paid</button>
                                                                <button x-show="canManageCharges && ['pending','draft'].includes(charge.status)"
                                                                        @@click="waiveCharge(charge)"
                                                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Waive</button>
                                                                <button x-show="canManageCharges && ['pending','draft'].includes(charge.status)"
                                                                        @@click="cancelCharge(charge)"
                                                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Cancel</button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
	                    </div>
	                </template>

	                {{-- Driver Assignment History Modal --}}
		                <div x-show="assignmentHistoryModalOpen" @@click="assignmentHistoryModalOpen = false" x-transition.opacity class="fixed inset-0 z-[188] flex justify-end bg-black/55 backdrop-blur-sm" style="display:none">
		                    <div @@click.stop x-transition:enter="transition ease-out duration-200"
		                         x-transition:enter-start="translate-x-full"
		                         x-transition:enter-end="translate-x-0"
		                         x-transition:leave="transition ease-in duration-150"
		                         x-transition:leave-start="translate-x-0"
		                         x-transition:leave-end="translate-x-full"
		                         class="flex h-full w-full max-w-xl flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
	                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
	                            <div>
	                                <h3 class="text-lg font-bold text-slate-900">Driver Assignment History</h3>
	                                <p class="mt-1 text-sm text-slate-500">Pickup driver assignment records for this shipment.</p>
	                            </div>
	                            <button type="button" @@click="assignmentHistoryModalOpen = false" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
	                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                            </button>
	                        </div>
		                        <div class="flex-1 overflow-x-auto overflow-y-auto">
		                            <table class="min-w-[760px] w-full divide-y divide-slate-100 text-left">
	                                <thead class="bg-slate-50">
	                                    <tr>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Driver</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Target Warehouse</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Assigned</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Received</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Cancelled</th>
	                                    </tr>
	                                </thead>
	                                <tbody class="divide-y divide-slate-100">
	                                    <template x-if="assignmentHistory.length === 0">
	                                        <tr>
	                                            <td colspan="6" class="px-4 py-10 text-center text-[11px] font-semibold text-slate-500">No assignment history found.</td>
	                                        </tr>
	                                    </template>
	                                    <template x-for="history in assignmentHistory" :key="history.id">
	                                        <tr class="align-top hover:bg-slate-50/60">
	                                            <td class="px-4 py-4">
	                                                <p class="text-[11px] font-bold text-slate-900" x-text="history.driver_name || 'Unknown Driver'"></p>
	                                                <p class="mt-0.5 text-[10px] text-slate-500" x-text="history.driver_phone || '-'"></p>
	                                            </td>
	                                            <td class="px-4 py-4">
	                                                <p class="text-[10px] font-black uppercase tracking-wide" :class="assignmentStatusTextClass(history.status)" x-text="history.status_label || history.status || '-'"></p>
	                                            </td>
	                                            <td class="px-4 py-4">
	                                                <p class="text-[11px] font-semibold text-slate-800" x-text="history.target_warehouse_name || '-'"></p>
	                                                <p x-show="history.target_warehouse_code" class="mt-0.5 text-[10px] text-slate-500" x-text="history.target_warehouse_code"></p>
	                                            </td>
	                                            <td class="px-4 py-4 text-[11px] font-semibold text-slate-700" x-text="history.assigned_at ? formatDateTime(history.assigned_at) : '-'"></td>
	                                            <td class="px-4 py-4 text-[11px] font-semibold text-emerald-700" x-text="history.received_at ? formatDateTime(history.received_at) : '-'"></td>
	                                            <td class="px-4 py-4">
	                                                <p class="text-[11px] font-semibold text-rose-700" x-text="history.cancelled_at ? formatDateTime(history.cancelled_at) : '-'"></p>
	                                                <p x-show="history.cancellation_reason" class="mt-0.5 text-[10px] text-rose-500" x-text="history.cancellation_reason"></p>
	                                            </td>
	                                        </tr>
	                                    </template>
	                                </tbody>
	                            </table>
	                        </div>
	                    </div>
	                </div>

	                {{-- Tracking History Modal --}}
		                <div x-show="trackingHistoryModalOpen" @@click="trackingHistoryModalOpen = false" x-transition.opacity class="fixed inset-0 z-[188] flex justify-end bg-black/55 backdrop-blur-sm" style="display:none">
		                    <div @@click.stop x-transition:enter="transition ease-out duration-200"
		                         x-transition:enter-start="translate-x-full"
		                         x-transition:enter-end="translate-x-0"
		                         x-transition:leave="transition ease-in duration-150"
		                         x-transition:leave-start="translate-x-0"
		                         x-transition:leave-end="translate-x-full"
		                         class="flex h-full w-full max-w-xl flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
	                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
	                            <div>
	                                <h3 class="text-lg font-bold text-slate-900">Tracking History</h3>
	                                <p class="mt-1 text-sm text-slate-500">Shipment timeline events from creation through delivery.</p>
	                            </div>
	                            <button type="button" @@click="trackingHistoryModalOpen = false" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
	                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                            </button>
	                        </div>
		                        <div class="flex-1 overflow-x-auto overflow-y-auto">
		                            <table class="min-w-[720px] w-full divide-y divide-slate-100 text-left">
	                                <thead class="bg-slate-50">
	                                    <tr>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Event</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Location</th>
	                                        <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Time</th>
	                                    </tr>
	                                </thead>
	                                <tbody class="divide-y divide-slate-100">
	                                    <tr x-show="tracking.loading">
	                                        <td colspan="4" class="px-4 py-10 text-center text-[11px] font-semibold text-slate-500">Loading tracking history...</td>
	                                    </tr>
	                                    <template x-if="!tracking.loading && tracking.data.length === 0">
	                                        <tr>
	                                            <td colspan="4" class="px-4 py-10 text-center text-[11px] font-semibold text-slate-500">No tracking events found.</td>
	                                        </tr>
	                                    </template>
	                                    <template x-for="(event, index) in tracking.data" :key="index">
	                                        <tr class="align-top hover:bg-slate-50/60">
	                                            <td class="px-4 py-4">
	                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-700" x-text="event.status_label || event.status || '-'"></p>
	                                            </td>
	                                            <td class="px-4 py-4">
	                                                <p class="text-[11px] font-bold text-slate-900" x-text="event.label || '-'"></p>
	                                                <p x-show="event.description" class="mt-0.5 text-[10px] text-slate-500" x-text="event.description"></p>
	                                            </td>
	                                            <td class="px-4 py-4 text-[11px] font-semibold text-slate-700" x-text="event.location || '-'"></td>
	                                            <td class="px-4 py-4 text-[11px] font-semibold text-slate-700" x-text="event.created_at ? formatDateTime(event.created_at) : '-'"></td>
	                                        </tr>
	                                    </template>
	                                </tbody>
	                            </table>
	                        </div>
	                    </div>
	                </div>

	                {{-- Package Custody Modal --}}
		                <div x-show="packageCustodyModal.open" @@click="closePackageCustodyModal()" x-transition.opacity class="fixed inset-0 z-[188] flex justify-end bg-black/55 backdrop-blur-sm" style="display:none">
		                    <template x-if="packageCustodyModal.pkg">
		                        <div @@click.stop x-transition:enter="transition ease-out duration-200"
		                             x-transition:enter-start="translate-x-full"
		                             x-transition:enter-end="translate-x-0"
		                             x-transition:leave="transition ease-in duration-150"
		                             x-transition:leave-start="translate-x-0"
		                             x-transition:leave-end="translate-x-full"
		                             class="flex h-full w-full max-w-xl flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
		                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
		                                <div>
		                                    <h3 class="text-lg font-bold text-slate-900">Package Custody</h3>
	                                    <p class="mt-1 text-sm text-slate-500" x-text="packageCustodyModal.packageLabel"></p>
	                                </div>
	                                <button type="button" @@click="closePackageCustodyModal()" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
	                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
		                                </button>
		                            </div>
		                            <div class="flex-1 space-y-4 overflow-y-auto p-6">
		                                <div x-show="(packageCustodyModal.pkg.custody?.drivers || []).length" class="space-y-2">
		                                    <template x-for="driverGroup in packageCustodyModal.pkg.custody.drivers" :key="driverGroup.driver_id">
		                                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
		                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white" x-text="(driverGroup.name || 'U').charAt(0).toUpperCase()"></div>
		                                            <div class="min-w-0 flex-1">
		                                                <p class="text-[11px] font-bold text-slate-800" x-text="driverGroup.name || 'Unknown driver'"></p>
		                                                <p class="text-[10px] text-slate-500" x-text="driverGroup.count + ' label' + (driverGroup.count === 1 ? '' : 's')"></p>
		                                            </div>
	                                            <button type="button"
	                                                    @@click="createRunFromClaims(driverGroup.driver_id)"
	                                                    :disabled="custody.creatingRun"
	                                                    class="ml-2 rounded-lg bg-slate-900 px-2.5 py-1 text-[10px] font-bold text-white transition-colors hover:bg-slate-700 disabled:opacity-50">
	                                                <span x-text="custody.creatingRun ? '...' : 'Create Run'"></span>
	                                            </button>
		                                        </div>
		                                    </template>
		                                </div>
		                                <div class="overflow-hidden rounded-2xl border border-slate-200">
		                                    <table class="min-w-full w-full divide-y divide-slate-100 text-left">
	                                        <thead class="bg-slate-50">
	                                            <tr>
	                                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Label</th>
	                                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
	                                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Driver</th>
	                                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">Claimed</th>
	                                            </tr>
	                                        </thead>
	                                        <tbody class="divide-y divide-slate-100">
	                                            <template x-for="label in (packageCustodyModal.pkg.custody?.labels || [])" :key="label.id">
	                                                <tr class="align-top hover:bg-slate-50/60">
	                                                    <td class="px-4 py-4">
	                                                        <p class="text-[11px] font-mono font-bold text-slate-900" x-text="label.barcode"></p>
	                                                        <p class="mt-0.5 text-[10px] text-slate-500" x-text="'Label ' + label.label_index + ' of ' + label.labels_total"></p>
	                                                    </td>
	                                                    <td class="px-4 py-4">
	                                                        <p class="text-[10px] font-black uppercase tracking-wide"
	                                                           :class="label.status === 'claimed' ? 'text-emerald-700' : label.status === 'delivered' ? 'text-blue-700' : 'text-slate-600'"
	                                                           x-text="label.status.replace('_', ' ')"></p>
	                                                    </td>
	                                                    <td class="px-4 py-4">
	                                                        <p class="text-[11px] font-bold text-slate-800" x-text="label.current_driver?.name || '-'"></p>
	                                                        <p x-show="label.current_driver?.phone" class="mt-0.5 text-[10px] text-slate-500" x-text="label.current_driver?.phone"></p>
	                                                    </td>
	                                                    <td class="px-4 py-4 text-[11px] font-semibold text-slate-700" x-text="label.claimed_at || '-'"></td>
	                                                </tr>
	                                            </template>
	                                        </tbody>
	                                    </table>
	                                </div>
	                            </div>
	                        </div>
	                    </template>
	                </div>

	                {{-- Shared Destination Modal --}}
	                <div x-show="sharedDestinationModal.open" x-transition.opacity class="fixed inset-0 z-[190] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
	                    <template x-if="sharedDestinationModal.pkg">
	                        <div @@click.stop class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
	                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
	                                <div>
	                                    <h3 class="text-base font-bold text-slate-900">Edit Shared Destination</h3>
	                                    <p class="mt-0.5 text-xs text-slate-500">These recipient and location details apply to all packages in One Drop-off mode.</p>
	                                </div>
	                                <button type="button"
	                                        @@click="closeSharedDestinationModal()"
	                                        :disabled="sharedDestinationModal.saving"
	                                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
	                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                </button>
	                            </div>
	                            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
	                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Name</label>
	                                        <input type="text"
	                                               x-model="sharedDestinationModal.pkg.delivery_recipient_name"
	                                               :disabled="sharedDestinationModal.saving"
	                                               placeholder="Who receives it?"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Phone</label>
	                                        <input type="text"
	                                               x-model="sharedDestinationModal.pkg.delivery_recipient_phone"
	                                               :disabled="sharedDestinationModal.saving"
	                                               placeholder="0241234567"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                </div>
	                                <div class="relative" @@click.outside="closeReceivingTownSearch(sharedDestinationModal.pkg)">
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Town / Area</label>
	                                    <div class="relative">
	                                        <input type="text"
	                                               :value="sharedDestinationModal.pkg._town_query"
	                                               @@input="updateReceivingTownQuery(sharedDestinationModal.pkg, $event.target.value)"
	                                               :disabled="sharedDestinationModal.saving"
	                                               placeholder="Search saved towns or keep free text"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-16 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                        <div x-show="sharedDestinationModal.pkg._town_loading" class="absolute inset-y-0 right-10 flex items-center text-slate-400" style="display:none">
	                                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                        </div>
	                                        <button type="button"
	                                                x-show="sharedDestinationModal.pkg._town_query"
	                                                @@click.prevent="clearReceivingTown(sharedDestinationModal.pkg)"
	                                                :disabled="sharedDestinationModal.saving"
	                                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600 disabled:cursor-not-allowed disabled:opacity-50" style="display:none">
	                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                        </button>
	                                        <div x-show="sharedDestinationModal.pkg._town_open" x-transition
	                                             class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl" style="display:none">
	                                            <template x-for="town in sharedDestinationModal.pkg._town_results" :key="`shared-${town.id}-${town.region_id}`">
	                                                <button type="button"
	                                                        @@click.prevent="selectReceivingTownOption(sharedDestinationModal.pkg, town)"
	                                                        class="w-full border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-slate-50">
	                                                    <p class="text-sm font-bold text-slate-800" x-text="town.name"></p>
	                                                    <p class="text-xs text-slate-500" x-text="town.context"></p>
	                                                </button>
	                                            </template>
	                                        </div>
	                                    </div>
	                                    <p x-show="sharedDestinationModal.pkg._town_linked && sharedDestinationModal.pkg._town_context" class="mt-1 text-[10px] font-medium text-emerald-600" x-text="'Linked to ' + sharedDestinationModal.pkg._town_context" style="display:none"></p>
	                                    <p x-show="sharedDestinationModal.pkg.delivery_town && !sharedDestinationModal.pkg._town_linked" class="mt-1 text-[10px] text-amber-600" style="display:none">Free-text town. Region and district stay empty until you select a saved town.</p>
	                                </div>
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Landmark</label>
	                                    <input type="text"
	                                           x-model="sharedDestinationModal.pkg.delivery_landmark"
	                                           :disabled="sharedDestinationModal.saving"
	                                           placeholder="Near..."
	                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                </div>
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Delivery Instructions</label>
	                                    <textarea rows="2"
	                                              x-model="sharedDestinationModal.pkg.delivery_instructions"
	                                              :disabled="sharedDestinationModal.saving"
	                                              placeholder="e.g. Call before delivery"
	                                              class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"></textarea>
	                                </div>
	                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
	                                    <input type="checkbox"
	                                           :checked="sharedDestinationModal.pkg.delivery_method === 'bus_handoff'"
	                                           @@change="sharedDestinationModal.pkg.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'"
	                                           :disabled="sharedDestinationModal.saving"
	                                           class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 disabled:cursor-not-allowed disabled:opacity-50">
	                                    <span>
	                                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-700">Send via Bus Courier</span>
	                                        <span class="mt-1 block text-xs text-slate-500">The driver will choose and record the station during handoff.</span>
	                                    </span>
	                                </label>
	                            </div>
	                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
	                                <button type="button"
	                                        @@click="closeSharedDestinationModal()"
	                                        :disabled="sharedDestinationModal.saving"
	                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Cancel</button>
	                                <button type="button"
	                                        @@click="saveSharedDestinationDetails()"
	                                        :disabled="sharedDestinationModal.saving"
	                                        class="inline-flex min-w-40 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
	                                    <svg x-show="sharedDestinationModal.saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                    <span x-text="sharedDestinationModal.saving ? 'Saving...' : 'Save Destination'"></span>
	                                </button>
	                            </div>
	                        </div>
	                    </template>
	                </div>

	                {{-- Receiving Package Modal --}}
	                <div x-show="receivingPackageModal.open" x-transition.opacity class="fixed inset-0 z-[190] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
	                    <template x-if="receivingPackageModal.pkg">
	                        <div @@click.stop class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
	                            <div class="border-b border-slate-100 px-5 py-4">
	                                <div class="flex items-start justify-between gap-4">
	                                    <div class="flex min-w-0 items-start gap-3">
	                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
	                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25m0-9L3 7.5m9 5.25v9M3 7.5v9l9 5.25"/></svg>
	                                        </span>
	                                        <div class="min-w-0">
	                                            <h3 class="text-base font-bold text-slate-900" x-text="receivingPackageModal.step === 2 ? 'Delivery Details' : 'Intake Details'"></h3>
	                                            <p class="mt-0.5 text-xs text-slate-500" x-show="receivingPackageModal.step === 1">
	                                                Confirm quantity, condition, photos, and package description.
	                                            </p>
	                                            <p class="mt-0.5 text-xs text-slate-500" x-show="receivingPackageModal.step === 2" style="display:none">
	                                                Set the recipient, location, delivery method, and fee for this package.
	                                            </p>
	                                            <p class="mt-2 flex flex-wrap items-center gap-2 text-xs">
	                                                <span class="rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-900" x-text="receivingPackageModal.packageLabel"></span>
	                                                <span x-show="receivingPackageModal.pkg.tracking_code" class="font-mono text-[10px] font-bold text-slate-400" x-text="receivingPackageModal.pkg.tracking_code"></span>
	                                            </p>
	                                        </div>
	                                    </div>
	                                    <button type="button" @@click="closeReceivingPackageModal()" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
	                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                    </button>
	                                </div>
	                                <div x-show="!isPerItemMode()" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
	                                    One Drop-off is active. This form only receives the package; destination is managed from Edit Shared Destination.
	                                </div>
	                            </div>

	                            <div class="flex-1 overflow-y-auto px-5 py-4">
	                                <div x-show="receivingPackageModal.step === 1" class="space-y-4">
	                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
	                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
	                                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Expected</p>
	                                            <p class="mt-1 text-lg font-bold text-slate-900" x-text="receivingExpectedQuantity(receivingPackageModal.pkg)"></p>
	                                        </div>
	                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
	                                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Driver Confirmed</p>
	                                            <p class="mt-1 text-lg font-bold text-slate-900" x-text="receivingPackageModal.pkg.driver_confirmed_quantity ?? '-'"></p>
	                                        </div>
	                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
	                                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Vendor Qty</p>
	                                            <p class="mt-1 text-lg font-bold text-slate-900" x-text="receivingPackageModal.pkg.vendor_quantity ?? '-'"></p>
	                                        </div>
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Description</label>
	                                        <input type="text" x-model="receivingPackageModal.pkg.description" placeholder="What's inside?"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                    </div>
	                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
	                                        <div>
	                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Received Qty</label>
	                                            <input type="number" x-model.number="receivingPackageModal.pkg.received_quantity" min="0"
	                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                        </div>
	                                        <div>
	                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Damaged Qty</label>
	                                            <input type="number" x-model.number="receivingPackageModal.pkg.damaged_quantity" min="0"
	                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                        </div>
	                                        <div>
	                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Condition</label>
	                                            <select x-model="receivingPackageModal.pkg.condition_status"
	                                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                                <option value="ok">OK</option>
	                                                <option value="damaged">Damaged</option>
	                                                <option value="partial">Partial</option>
	                                            </select>
	                                        </div>
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Receiving Notes</label>
	                                        <textarea rows="2" x-model="receivingPackageModal.pkg.notes" placeholder="Receiving notes..."
	                                                  class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20"></textarea>
	                                    </div>
	                                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-4">
	                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
	                                            <div>
	                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Receipt Photos</p>
	                                                <p class="mt-1 text-xs text-slate-500">Attach warehouse intake photos when saving this receipt.</p>
	                                                <p x-show="receivingReceiptPhotoNames(receivingPackageModal.pkg)" class="mt-1 text-[11px] font-semibold text-slate-700" x-text="receivingReceiptPhotoNames(receivingPackageModal.pkg)" style="display:none"></p>
	                                            </div>
	                                            <label class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
	                                                <input type="file" class="hidden" multiple accept="image/*"
	                                                       @@change="setReceivingReceiptPhotos(receivingPackageModal.pkg, $event.target.files)">
	                                                Choose Photos
	                                            </label>
	                                        </div>
	                                    </div>
	                                </div>

	                                <div x-show="isPerItemMode() && receivingPackageModal.step === 2" class="space-y-4" style="display:none">
	                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
	                                        <p class="text-xs font-black uppercase tracking-wide text-slate-700">Multiple Drop-offs Active</p>
	                                        <p class="mt-1 text-xs text-slate-600">Recipient and location saved here apply only to this package.</p>
	                                    </div>
	                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
	                                        <div>
	                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Name</label>
	                                            <input type="text" x-model="receivingPackageModal.pkg.delivery_recipient_name" placeholder="Who receives it?"
	                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                        </div>
	                                        <div>
	                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Phone</label>
	                                            <input type="text" x-model="receivingPackageModal.pkg.delivery_recipient_phone" placeholder="0241234567"
	                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                        </div>
	                                    </div>
	                                    <div class="relative" @@click.outside="closeReceivingTownSearch(receivingPackageModal.pkg)">
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Town / Area</label>
	                                        <div class="relative">
	                                            <input type="text"
	                                                   :value="receivingPackageModal.pkg._town_query"
	                                                   @@input="updateReceivingTownQuery(receivingPackageModal.pkg, $event.target.value)"
	                                                   placeholder="Search saved towns or keep free text"
	                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-16 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                            <div x-show="receivingPackageModal.pkg._town_loading" class="absolute inset-y-0 right-10 flex items-center text-slate-400" style="display:none">
	                                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                            </div>
	                                            <button type="button" x-show="receivingPackageModal.pkg._town_query" @@click.prevent="clearReceivingTown(receivingPackageModal.pkg)"
	                                                    class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600" style="display:none">
	                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                            </button>
	                                            <div x-show="receivingPackageModal.pkg._town_open" x-transition
	                                                 class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl" style="display:none">
	                                                <template x-for="town in receivingPackageModal.pkg._town_results" :key="`${town.id}-${town.region_id}`">
	                                                    <button type="button" @@click.prevent="selectReceivingTownOption(receivingPackageModal.pkg, town)"
	                                                            class="w-full border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-slate-50">
	                                                        <p class="text-sm font-bold text-slate-800" x-text="town.name"></p>
	                                                        <p class="text-xs text-slate-500" x-text="town.context"></p>
	                                                    </button>
	                                                </template>
	                                            </div>
	                                        </div>
	                                        <p x-show="receivingPackageModal.pkg._town_linked && receivingPackageModal.pkg._town_context" class="mt-1 text-[10px] font-medium text-emerald-600" x-text="'Linked to ' + receivingPackageModal.pkg._town_context" style="display:none"></p>
	                                        <p x-show="receivingPackageModal.pkg.delivery_town && !receivingPackageModal.pkg._town_linked" class="mt-1 text-[10px] text-amber-600" style="display:none">Free-text town. Region and district stay empty until you select a saved town.</p>
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Landmark</label>
	                                        <input type="text" x-model="receivingPackageModal.pkg.delivery_landmark" placeholder="Near..."
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Delivery Instructions</label>
	                                        <textarea rows="2" x-model="receivingPackageModal.pkg.delivery_instructions" placeholder="e.g. Call before delivery"
	                                                  class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20"></textarea>
	                                    </div>
	                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
	                                        <input type="checkbox"
	                                               :checked="receivingPackageModal.pkg.delivery_method === 'bus_handoff'"
	                                               @@change="receivingPackageModal.pkg.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'"
	                                               class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
	                                        <span>
	                                            <span class="block text-xs font-bold uppercase tracking-wide text-slate-700">Send via Bus Courier</span>
	                                            <span class="mt-1 block text-xs text-slate-500">The driver will choose and record the bus station during handoff.</span>
	                                        </span>
	                                    </label>
	                                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
	                                        <div class="flex flex-col gap-4">
	                                            <div>
	                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Delivery Fee</p>
	                                                <p class="mt-1 text-xs text-slate-500">Package-level fee for this recipient. Paid fees tell the driver not to collect again.</p>
	                                            </div>
	                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
	                                                <div>
	                                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Fee Status</label>
	                                                    <select x-model="receivingPackageModal.pkg.delivery_fee.mode"
	                                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                                        <option value="none">No fee set</option>
	                                                        <option value="collect">Collect from recipient</option>
	                                                        <option value="paid">Already paid</option>
	                                                    </select>
	                                                </div>
	                                                <div x-show="receivingPackageModal.pkg.delivery_fee.mode !== 'none'" style="display:none">
	                                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Amount (GHS)</label>
	                                                    <input type="number" min="0" step="0.01" x-model.number="receivingPackageModal.pkg.delivery_fee.amount"
	                                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                                </div>
	                                                <div x-show="receivingPackageModal.pkg.delivery_fee.mode === 'paid'" style="display:none">
	                                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Payment Method</label>
	                                                    <select x-model="receivingPackageModal.pkg.delivery_fee.payment_method"
	                                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                                        <option value="cash">Cash</option>
	                                                        <option value="momo">Mobile Money</option>
	                                                        <option value="bank">Bank</option>
	                                                        <option value="other">Other</option>
	                                                    </select>
	                                                </div>
	                                            </div>
	                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="receivingPackageModal.pkg.delivery_fee.mode !== 'none'" style="display:none">
	                                                <div>
	                                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Fee Notes</label>
	                                                    <input type="text" x-model="receivingPackageModal.pkg.delivery_fee.notes"
	                                                           placeholder="e.g. Quoted to recipient over phone"
	                                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                                </div>
	                                                <div x-show="receivingPackageModal.pkg.delivery_fee.mode === 'paid'" style="display:none">
	                                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Payment Reference</label>
	                                                    <input type="text" x-model="receivingPackageModal.pkg.delivery_fee.payment_reference"
	                                                           placeholder="MoMo txn ID, receipt no., etc."
	                                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                                </div>
	                                            </div>
	                                            <p class="text-[11px] text-slate-500" x-show="receivingPackageModal.pkg.delivery_fee.status === 'paid' && receivingPackageModal.pkg.delivery_fee.paid_at" style="display:none">
	                                                Previously marked paid at <span x-text="formatDateTime(receivingPackageModal.pkg.delivery_fee.paid_at)"></span>.
	                                            </p>
	                                        </div>
	                                    </div>
	                                </div>
	                            </div>

	                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
	                                    <button type="button" @@click="closeReceivingPackageModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">Cancel</button>
	                                    <button type="button" x-show="isPerItemMode() && receivingPackageModal.step === 1" @@click="setReceivingPackageModalStep(2)"
	                                            class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-slate-800">Next: Delivery Details</button>
	                                    <button type="button" x-show="isPerItemMode() && receivingPackageModal.step === 2" @@click="setReceivingPackageModalStep(1)"
	                                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50" style="display:none">Back</button>
	                                    <button type="button" x-show="!isPerItemMode() || receivingPackageModal.step === 2"
	                                            @@click="saveReceivingPackageModalDetails()"
	                                            :disabled="receivingPackageModal.savingDetails || receivingPackageModal.savingReceive"
	                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-50" style="display:none">
	                                        <svg x-show="receivingPackageModal.savingDetails" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                        <span x-text="receivingPackageModal.savingDetails ? 'Saving...' : 'Save Changes'"></span>
	                                    </button>
	                                    <button type="button" x-show="!isPerItemMode() || receivingPackageModal.step === 2"
	                                            @@click="receivePackageFromModal()"
	                                            :disabled="!receiving.canReceive || receivingPackageModal.savingDetails || receivingPackageModal.savingReceive"
	                                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:opacity-50" style="display:none">
	                                        <svg x-show="receivingPackageModal.savingReceive" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                        <span x-text="receivingPackageModal.savingReceive ? 'Saving...' : 'Save and Receive'"></span>
	                                    </button>
	                            </div>
	                        </div>
		                    </template>
		                </div>

	                {{-- Receiving Label Print Modal --}}
	                <div x-show="receivingLabelPrintModal.open" x-transition.opacity class="fixed inset-0 z-[191] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
	                    <template x-if="receivingLabelPrintModal.pkg">
	                        <div @@click.stop class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
	                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
	                                <div class="flex min-w-0 items-start gap-3">
	                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
	                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5V6A2.25 2.25 0 019 3.75h6A2.25 2.25 0 0117.25 6v1.5m-10.5 0h10.5m-10.5 0A2.25 2.25 0 004.5 9.75v3A2.25 2.25 0 006.75 15h.75m9.75-7.5A2.25 2.25 0 0119.5 9.75v3A2.25 2.25 0 0117.25 15h-.75m-9 0v5.25h9V15m-9 0h9"/></svg>
	                                    </span>
	                                    <div class="min-w-0">
	                                        <h3 class="text-base font-bold text-slate-900">Print Labels</h3>
	                                        <p class="mt-0.5 text-xs text-slate-500">Choose how many labels to print for this received package.</p>
	                                        <p class="mt-2 flex flex-wrap items-center gap-2 text-xs">
	                                            <span class="rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-900" x-text="receivingLabelPrintModal.packageLabel"></span>
	                                            <span x-show="receivingLabelPrintModal.trackingCode" class="font-mono text-[10px] font-bold text-slate-400" x-text="receivingLabelPrintModal.trackingCode"></span>
	                                        </p>
	                                    </div>
	                                </div>
	                                <button type="button" @@click="closeReceivingLabelPrintModal()" :disabled="receivingLabelPrintModal.printing" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
	                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                </button>
	                            </div>
	                            <div class="space-y-4 px-5 py-4">
	                                <div class="grid grid-cols-2 gap-3">
	                                    <button type="button"
	                                            @@click="receivingLabelPrintModal.labelCount = 1"
	                                            class="rounded-xl border px-3 py-3 text-left transition-colors"
	                                            :class="Number(receivingLabelPrintModal.labelCount) === 1 ? 'border-slate-900 bg-slate-50' : 'border-slate-200 bg-white hover:bg-slate-50'">
	                                        <span class="block text-sm font-black text-slate-900">1 Label</span>
	                                        <span class="mt-1 block text-xs text-slate-500">One sealed package</span>
	                                    </button>
	                                    <button type="button"
	                                            x-show="Number(receivingLabelPrintModal.pkg.received_quantity || 0) > 1"
	                                            @@click="receivingLabelPrintModal.labelCount = Number(receivingLabelPrintModal.pkg.received_quantity || 1)"
	                                            class="rounded-xl border px-3 py-3 text-left transition-colors"
	                                            :class="Number(receivingLabelPrintModal.labelCount) === Number(receivingLabelPrintModal.pkg.received_quantity || 1) ? 'border-slate-900 bg-slate-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
	                                            style="display:none">
	                                        <span class="block text-sm font-black text-slate-900"><span x-text="receivingLabelPrintModal.pkg.received_quantity"></span> Labels</span>
	                                        <span class="mt-1 block text-xs text-slate-500">One per unit</span>
	                                    </button>
	                                </div>
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Custom Label Count</label>
	                                    <input type="number"
	                                           x-model.number="receivingLabelPrintModal.labelCount"
	                                           min="1"
	                                           max="500"
	                                           :disabled="receivingLabelPrintModal.printing"
	                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50">
	                                </div>
	                            </div>
	                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
	                                <button type="button"
	                                        @@click="closeReceivingLabelPrintModal()"
	                                        :disabled="receivingLabelPrintModal.printing"
	                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Skip</button>
	                                <button type="button"
	                                        @@click="printLabelsFromReceivingModal()"
	                                        :disabled="receivingLabelPrintModal.printing || !Number(receivingLabelPrintModal.labelCount)"
	                                        class="inline-flex min-w-36 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
	                                    <svg x-show="receivingLabelPrintModal.printing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                    <span x-text="receivingLabelPrintModal.printing ? 'Printing...' : 'Print Labels'"></span>
	                                </button>
	                            </div>
	                        </div>
	                    </template>
	                </div>

	                {{-- Remove Package Confirm Modal --}}
	                <div x-show="receivingRemoveConfirm.open" @@click="closeReceivingRemoveConfirm()" x-transition.opacity class="fixed inset-0 z-[192] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
	                    <div @@click.stop class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
	                        <div class="px-5 py-5">
	                            <div class="flex items-start gap-4">
	                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
	                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
	                                </span>
	                                <div>
	                                    <h3 class="text-base font-bold text-slate-900" x-text="receivingRemoveConfirm.title || 'Remove package?'"></h3>
	                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500" x-text="receivingRemoveConfirm.message"></p>
	                                </div>
	                            </div>
	                        </div>
	                        <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
	                            <button type="button"
	                                    @@click="closeReceivingRemoveConfirm()"
	                                    :disabled="receivingRemoveConfirm.loading"
	                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Cancel</button>
	                            <button type="button"
	                                    @@click="confirmRemoveReceivingPackage()"
	                                    :disabled="receivingRemoveConfirm.loading"
	                                    class="inline-flex min-w-36 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-5 py-2 text-sm font-bold text-rose-700 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60">
	                                <svg x-show="receivingRemoveConfirm.loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                <span x-text="receivingRemoveConfirm.loading ? 'Removing...' : 'Remove Package'"></span>
	                            </button>
	                        </div>
	                    </div>
	                </div>

		                {{-- Pickup Edit Modal --}}
		                <div x-show="pickupEditModal.open" x-transition.opacity class="fixed inset-0 z-[189] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
	                    <template x-if="pickupEditModal.form">
	                        <div @@click.stop class="flex max-h-[88vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
	                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
	                                <div>
	                                    <h3 class="text-base font-bold text-slate-900">Edit Pickup Details</h3>
	                                    <p class="mt-0.5 text-xs text-slate-500">Update the pickup contact and location.</p>
	                                </div>
	                                <button type="button" @@click="closePickupEditModal()" :disabled="pickupEditModal.saving" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
	                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                </button>
	                            </div>
	                            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
	                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Contact Name</label>
	                                        <input type="text" x-model="pickupEditModal.form.contact_name"
	                                               :disabled="pickupEditModal.saving"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Contact Phone</label>
	                                        <input type="text" x-model="pickupEditModal.form.contact_phone"
	                                               :disabled="pickupEditModal.saving"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                </div>
	                                <div class="relative" @@click.outside="closePickupTownSearch()">
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Town / Area</label>
	                                    <div class="relative">
	                                        <input type="text"
	                                               :value="pickupEditModal.form._town_query"
	                                               @@input="updatePickupTownQuery($event.target.value)"
	                                               placeholder="Search saved towns or keep vendor text"
	                                               :disabled="pickupEditModal.saving"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-16 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                        <div x-show="pickupEditModal.form._town_loading" class="absolute inset-y-0 right-10 flex items-center text-slate-400" style="display:none">
	                                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                        </div>
	                                        <button type="button" x-show="pickupEditModal.form._town_query" @@click.prevent="clearPickupTown()"
	                                                :disabled="pickupEditModal.saving"
	                                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600 disabled:cursor-not-allowed disabled:opacity-50" style="display:none">
	                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                        </button>
	                                        <div x-show="pickupEditModal.form._town_open" x-transition
	                                             class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl" style="display:none">
	                                            <template x-for="town in pickupEditModal.form._town_results" :key="`pickup-${town.id}-${town.region_id}`">
	                                                <button type="button" @@click.prevent="selectPickupTownOption(town)"
	                                                        class="w-full border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-violet-50">
	                                                    <p class="text-sm font-bold text-slate-800" x-text="town.name"></p>
	                                                    <p class="text-xs text-slate-500" x-text="town.context"></p>
	                                                </button>
	                                            </template>
	                                        </div>
	                                    </div>
	                                    <p x-show="pickupEditModal.form._town_linked && pickupEditModal.form._town_context" class="mt-1 text-[10px] font-medium text-emerald-600" x-text="'Linked to ' + pickupEditModal.form._town_context" style="display:none"></p>
	                                    <p x-show="pickupEditModal.form.town && !pickupEditModal.form._town_linked" class="mt-1 text-[10px] text-amber-600" style="display:none">Free-text town. Region and district stay empty until you select a saved town.</p>
	                                </div>
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Landmark</label>
	                                    <input type="text" x-model="pickupEditModal.form.landmark"
	                                           :disabled="pickupEditModal.saving"
	                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                </div>
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Pickup Instructions</label>
	                                    <textarea rows="2" x-model="pickupEditModal.form.instructions"
	                                              :disabled="pickupEditModal.saving"
	                                              class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"></textarea>
	                                </div>
	                            </div>
	                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
	                                <button type="button" @@click="closePickupEditModal()" :disabled="pickupEditModal.saving" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Cancel</button>
	                                <button type="button"
	                                        @@click="savePickupFromReceiving()"
	                                        :disabled="pickupEditModal.saving"
	                                        class="inline-flex min-w-40 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
	                                    <svg x-show="pickupEditModal.saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                    <span x-text="pickupEditModal.saving ? 'Saving...' : 'Save Pickup Details'"></span>
	                                </button>
	                            </div>
	                        </div>
	                    </template>
	                </div>

	                {{-- Receiving Add Package Modal --}}
	                <div x-show="receivingAddPackageModal.open" x-transition.opacity class="fixed inset-0 z-[188] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" style="display:none">
	                    <div @@click.stop class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
	                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
	                            <div>
	                                <h3 class="text-base font-bold text-slate-900">Add Package</h3>
	                                <p class="mt-0.5 text-xs text-slate-500">Create and set up the package found during receiving.</p>
	                            </div>
	                            <button type="button" @@click="closeReceivingAddPackageModal()" :disabled="receivingAddPackageModal.saving" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
	                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                            </button>
	                        </div>
	                        <div class="flex-1 space-y-5 overflow-y-auto px-5 py-4">
	                            <div>
	                                <p class="mb-3 text-xs font-black uppercase tracking-wide text-slate-400">Package</p>
	                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_150px]">
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Description <span class="text-rose-500">*</span></label>
	                                        <input type="text"
	                                               x-model="receivingAddPackageModal.description"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               required
	                                               placeholder="e.g. Extra carton"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Quantity <span class="text-rose-500">*</span></label>
	                                        <input type="number"
	                                               x-model.number="receivingAddPackageModal.quantity"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               min="1"
	                                               required
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                </div>
	                            </div>
	                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-4">
	                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
	                                    <div>
	                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Receipt Photos</p>
	                                        <p class="mt-1 text-xs text-slate-500">Attach photos for this newly received package.</p>
	                                        <p x-show="receivingReceiptPhotoNames(receivingAddPackageModal)" class="mt-1 text-[11px] font-semibold text-slate-700" x-text="receivingReceiptPhotoNames(receivingAddPackageModal)" style="display:none"></p>
	                                    </div>
	                                    <label class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
	                                        <input type="file"
	                                               class="hidden"
	                                               multiple
	                                               accept="image/*"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               @@change="setReceivingReceiptPhotos(receivingAddPackageModal, $event.target.files)">
	                                        Choose Photos
	                                    </label>
	                                </div>
	                            </div>
	                            <div x-show="!isPerItemMode()" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3">
	                                <p class="text-xs font-bold uppercase tracking-wide text-slate-600">One Drop-off Active</p>
	                                <p class="mt-1 text-xs text-slate-500">
	                                    This new package will use the shared destination:
	                                    <span class="font-semibold text-slate-700" x-text="receivingSharedDestinationSummary()"></span>
	                                </p>
	                            </div>
	                            <div x-show="isPerItemMode()" style="display:none">
	                                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
	                                    <div>
	                                        <p class="text-xs font-black uppercase tracking-wide text-slate-400">Delivery Details</p>
	                                        <p class="mt-0.5 text-xs text-slate-500">Set the destination for this new package.</p>
	                                    </div>
	                                    <span class="text-[10px] font-bold text-slate-500">Multiple Drop-offs</span>
	                                </div>
	                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Name</label>
	                                        <input type="text"
	                                               x-model="receivingAddPackageModal.delivery_recipient_name"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               placeholder="Who receives it?"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Phone</label>
	                                        <input type="text"
	                                               x-model="receivingAddPackageModal.delivery_recipient_phone"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               placeholder="0241234567"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                </div>
	                            </div>
	                            <div x-show="isPerItemMode()" class="relative" @@click.outside="closeReceivingTownSearch(receivingAddPackageModal)" style="display:none">
	                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Town / Area</label>
	                                <div class="relative">
	                                    <input type="text"
	                                           :value="receivingAddPackageModal._town_query"
	                                           @@input="updateReceivingTownQuery(receivingAddPackageModal, $event.target.value)"
	                                           :disabled="receivingAddPackageModal.saving"
	                                           placeholder="Search saved towns or keep free text"
	                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-16 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    <div x-show="receivingAddPackageModal._town_loading" class="absolute inset-y-0 right-10 flex items-center text-slate-400" style="display:none">
	                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                    </div>
	                                    <button type="button"
	                                            x-show="receivingAddPackageModal._town_query"
	                                            @@click.prevent="clearReceivingTown(receivingAddPackageModal)"
	                                            :disabled="receivingAddPackageModal.saving"
	                                            class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600 disabled:cursor-not-allowed disabled:opacity-50" style="display:none">
	                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                    </button>
	                                    <div x-show="receivingAddPackageModal._town_open" x-transition
	                                         class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl" style="display:none">
	                                        <template x-for="town in receivingAddPackageModal._town_results" :key="`add-${town.id}-${town.region_id}`">
	                                            <button type="button"
	                                                    @@click.prevent="selectReceivingTownOption(receivingAddPackageModal, town)"
	                                                    class="w-full border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-slate-50">
	                                                <p class="text-sm font-bold text-slate-800" x-text="town.name"></p>
	                                                <p class="text-xs text-slate-500" x-text="town.context"></p>
	                                            </button>
	                                        </template>
	                                    </div>
	                                </div>
	                                <p x-show="receivingAddPackageModal._town_linked && receivingAddPackageModal._town_context" class="mt-1 text-[10px] font-medium text-emerald-600" x-text="'Linked to ' + receivingAddPackageModal._town_context" style="display:none"></p>
	                                <p x-show="receivingAddPackageModal.delivery_town && !receivingAddPackageModal._town_linked" class="mt-1 text-[10px] text-amber-600" style="display:none">Free-text town. Region and district stay empty until you select a saved town.</p>
	                            </div>
	                            <div x-show="isPerItemMode()" class="grid grid-cols-1 gap-4 sm:grid-cols-2" style="display:none">
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Landmark</label>
	                                    <input type="text"
	                                           x-model="receivingAddPackageModal.delivery_landmark"
	                                           :disabled="receivingAddPackageModal.saving"
	                                           placeholder="Near..."
	                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                </div>
	                                <div>
	                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Delivery Method</label>
	                                    <select x-model="receivingAddPackageModal.delivery_method"
	                                            :disabled="receivingAddPackageModal.saving"
	                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                        <option value="direct">Direct delivery</option>
	                                        <option value="bus_handoff">Bus courier</option>
	                                    </select>
	                                </div>
	                            </div>
	                            <div x-show="isPerItemMode()" style="display:none">
	                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Delivery Instructions</label>
	                                <textarea rows="2"
	                                          x-model="receivingAddPackageModal.delivery_instructions"
	                                          :disabled="receivingAddPackageModal.saving"
	                                          placeholder="e.g. Call before delivery"
	                                          class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"></textarea>
	                            </div>
	                            <div x-show="isPerItemMode()" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4" style="display:none">
	                                <div class="mb-3">
	                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Delivery Fee</p>
	                                    <p class="mt-1 text-xs text-slate-500">Set a package-level fee if this recipient must pay or already paid.</p>
	                                </div>
	                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Fee Status</label>
	                                        <select x-model="receivingAddPackageModal.delivery_fee.mode"
	                                                :disabled="receivingAddPackageModal.saving"
	                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                            <option value="none">No fee set</option>
	                                            <option value="collect">Collect from recipient</option>
	                                            <option value="paid">Already paid</option>
	                                        </select>
	                                    </div>
	                                    <div x-show="receivingAddPackageModal.delivery_fee.mode !== 'none'" style="display:none">
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Amount (GHS)</label>
	                                        <input type="number" min="0" step="0.01"
	                                               x-model.number="receivingAddPackageModal.delivery_fee.amount"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                    <div x-show="receivingAddPackageModal.delivery_fee.mode === 'paid'" style="display:none">
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Payment Method</label>
	                                        <select x-model="receivingAddPackageModal.delivery_fee.payment_method"
	                                                :disabled="receivingAddPackageModal.saving"
	                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                            <option value="cash">Cash</option>
	                                            <option value="momo">Mobile Money</option>
	                                            <option value="bank">Bank</option>
	                                            <option value="other">Other</option>
	                                        </select>
	                                    </div>
	                                </div>
	                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="receivingAddPackageModal.delivery_fee.mode !== 'none'" style="display:none">
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Fee Notes</label>
	                                        <input type="text"
	                                               x-model="receivingAddPackageModal.delivery_fee.notes"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               placeholder="e.g. Quoted to recipient"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                    <div x-show="receivingAddPackageModal.delivery_fee.mode === 'paid'" style="display:none">
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Payment Reference</label>
	                                        <input type="text"
	                                               x-model="receivingAddPackageModal.delivery_fee.payment_reference"
	                                               :disabled="receivingAddPackageModal.saving"
	                                               placeholder="MoMo txn ID, receipt no., etc."
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
	                                    </div>
	                                </div>
	                            </div>
	                        </div>
	                        <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
	                            <button type="button" @@click="closeReceivingAddPackageModal()" :disabled="receivingAddPackageModal.saving" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Cancel</button>
	                            <button type="button"
	                                    @@click="addReceivingPackage()"
	                                    :disabled="receivingAddPackageModal.saving"
	                                    class="inline-flex min-w-36 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
	                                <svg x-show="receivingAddPackageModal.saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
	                                <span x-text="receivingAddPackageModal.saving ? 'Adding...' : 'Add Package'"></span>
	                            </button>
	                        </div>
	                    </div>
	                </div>

                {{-- Finalize Confirm Modal --}}
                <div x-show="finalizeConfirmOpen" x-transition.opacity class="fixed inset-0 z-[190] flex items-center justify-center bg-black/50 p-4" style="display:none">
                    <div @@click.stop class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden">
                        <div class="px-6 py-5">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Finalize Receiving</h3>
                                    <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">This will mark all packages as received and update the shipment status to <strong class="text-slate-700">At Warehouse</strong>. This action cannot be undone.</p>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 pb-5 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                                <textarea rows="3"
                                          x-model="finalizeNotes"
                                          class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 transition resize-none"
                                          placeholder="Any final receiving notes..."></textarea>
                            </div>

                            <template x-if="receiving.packages.some(pkg => pkg.discrepancy_type && pkg.discrepancy_type !== 'none')">
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-3">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <div>
                                            <p class="text-xs font-bold text-amber-800">Discrepancy detected</p>
                                            <p class="mt-1 text-xs text-amber-700">Discrepancy is detected automatically from the received, damaged, and expected quantities. Approval reason is required before finalizing.</p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <template x-for="(pkg, idx) in receiving.packages.filter(pkg => pkg.discrepancy_type && pkg.discrepancy_type !== 'none')" :key="`finalize-discrepancy-${pkg.shipment_item_id}`">
                                            <div class="flex items-center justify-between gap-3 rounded-lg border border-amber-200 bg-white/80 px-3 py-2">
                                                <div>
                                                    <p class="text-xs font-semibold text-slate-800">Package <span x-text="idx + 1"></span><span x-show="pkg.description" x-text="pkg.description ? ` - ${pkg.description}` : ''"></span></p>
                                                    <p class="text-[11px] text-slate-500">
                                                        Expected <span x-text="pkg.expected_quantity"></span>,
                                                        received <span x-text="pkg.received_quantity"></span>,
                                                        damaged <span x-text="pkg.damaged_quantity"></span>
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700"
                                                      x-text="pkg.discrepancy_label"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <template x-if="!canApproveReceivingDiscrepancy">
                                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                                            You do not have permission to approve discrepancy finalization. A warehouse manager or super admin needs to finalize this receipt.
                                        </div>
                                    </template>

                                    <template x-if="canApproveReceivingDiscrepancy">
                                        <div>
                                            <label class="block text-xs font-bold text-amber-800 mb-1.5">Approval Reason <span class="text-rose-500">*</span></label>
                                            <textarea rows="3"
                                                      x-model="approvalReason"
                                                      class="w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-400 transition resize-none"
                                                      placeholder="Explain why this discrepancy is approved for finalization..."></textarea>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                            <button @@click="finalizeConfirmOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 border border-slate-200 rounded-xl hover:bg-white transition-colors">Cancel</button>
                            <button @@click="finalizeReceiving()"
                                    :disabled="receiving.saving || (receiving.packages.some(pkg => pkg.discrepancy_type && pkg.discrepancy_type !== 'none') && (!canApproveReceivingDiscrepancy || !approvalReason.trim()))"
                                    class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-colors disabled:opacity-50 flex items-center gap-2">
                                <svg x-show="receiving.saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="receiving.saving ? 'Finalizing...' : 'Finalize'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Receiving Photos Modal --}}
                <div x-show="receivingPhotosModal.open" x-transition.opacity class="fixed inset-0 z-[194] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display:none">
                    <template x-if="receivingPhotosModal.pkg">
                        <div @@click.stop class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Package Photos</h3>
                                    <p class="mt-1 text-sm text-slate-500" x-text="receivingPhotosModal.packageLabel"></p>
                                </div>
                                <button type="button" @@click="closeReceivingPhotosModal()" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                <section>
                                    <div class="mb-3 flex items-center justify-between">
                                        <h4 class="text-xs font-black uppercase tracking-wide text-orange-700">Vendor Photos</h4>
                                        <span class="rounded-full bg-orange-50 px-2 py-1 text-[10px] font-bold text-orange-700" x-text="receivingPhotoCount(receivingPhotosModal.pkg, 'vendor_photos')"></span>
                                    </div>
                                    <template x-if="receivingPhotoCount(receivingPhotosModal.pkg, 'vendor_photos') === 0">
                                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs text-slate-400">No vendor photos uploaded.</p>
                                    </template>
                                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                                        <template x-for="photo in receivingPhotosModal.pkg.vendor_photos" :key="`vendor-${photo.id || photo.url}`">
                                            <button type="button" @@click="receivingLightbox = photo.url" class="group overflow-hidden rounded-2xl border border-orange-100 bg-orange-50 text-left transition hover:border-orange-300">
                                                <img :src="photo.url" class="aspect-square w-full object-cover">
                                                <div class="px-3 py-2">
                                                    <p class="truncate text-[10px] font-semibold text-orange-700" x-text="photo.original_name || 'Vendor photo'"></p>
                                                    <p x-show="photo.recipient_phone" class="truncate text-[10px] text-slate-500" x-text="photo.recipient_phone"></p>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </section>

                                <section>
                                    <div class="mb-3 flex items-center justify-between">
                                        <h4 class="text-xs font-black uppercase tracking-wide text-blue-700">Driver Photos</h4>
                                        <span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700" x-text="receivingPhotoCount(receivingPhotosModal.pkg, 'driver_photos')"></span>
                                    </div>
                                    <template x-if="receivingPhotoCount(receivingPhotosModal.pkg, 'driver_photos') === 0">
                                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs text-slate-400">No driver photos uploaded.</p>
                                    </template>
                                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                                        <template x-for="photo in receivingPhotosModal.pkg.driver_photos" :key="`driver-${photo.id || photo.url}`">
                                            <button type="button" @@click="receivingLightbox = photo.url" class="overflow-hidden rounded-2xl border border-blue-100 bg-blue-50 text-left transition hover:border-blue-300">
                                                <img :src="photo.url" class="aspect-square w-full object-cover">
                                                <div class="px-3 py-2">
                                                    <p class="truncate text-[10px] font-semibold text-blue-700">Driver photo</p>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </section>

                                <section>
                                    <div class="mb-3 flex items-center justify-between">
                                        <h4 class="text-xs font-black uppercase tracking-wide text-emerald-700">Receipt Photos</h4>
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700" x-text="receivingPhotoCount(receivingPhotosModal.pkg, 'photos')"></span>
                                    </div>
                                    <template x-if="receivingPhotoCount(receivingPhotosModal.pkg, 'photos') === 0">
                                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs text-slate-400">No receipt photos uploaded yet.</p>
                                    </template>
                                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                                        <template x-for="photo in receivingPhotosModal.pkg.photos" :key="`receipt-${photo.id || photo.url}`">
                                            <button type="button" @@click="receivingLightbox = photo.url" class="overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50 text-left transition hover:border-emerald-300">
                                                <img :src="photo.url" class="aspect-square w-full object-cover">
                                                <div class="px-3 py-2">
                                                    <p class="truncate text-[10px] font-semibold text-emerald-700" x-text="photo.original_name || 'Receipt photo'"></p>
                                                    <p class="truncate text-[10px] text-slate-500" x-text="photo.photo_type || 'condition'"></p>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </section>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Upload receipt photos</p>
                                    <p class="text-[11px] text-slate-500" x-show="receivingPackageIsReceived(receivingPhotosModal.pkg)">Adds photos to the existing warehouse receipt item.</p>
                                    <p class="text-[11px] text-amber-600" x-show="!receivingPackageIsReceived(receivingPhotosModal.pkg)" style="display:none">Select files here, then confirm intake details to attach them.</p>
                                    <p x-show="receivingPhotosModal.files.length" class="mt-1 text-[11px] font-semibold text-emerald-700" x-text="receivingPhotosModal.files.length + ' photo' + (receivingPhotosModal.files.length === 1 ? '' : 's') + ' selected'" style="display:none"></p>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <label class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                                        <input type="file" class="hidden" multiple accept="image/*"
                                               @@change="setReceivingPhotosModalFiles($event.target.files)">
                                        Choose Photos
                                    </label>
                                    <button type="button" @@click="uploadReceiptPhotosFromModal()"
                                            :disabled="receivingPhotosModal.uploading || receivingPhotosModal.files.length === 0"
                                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-slate-800 disabled:opacity-50">
                                        <svg x-show="receivingPhotosModal.uploading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span x-text="receivingPhotosModal.uploading ? 'Uploading...' : 'Upload Receipt Photos'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Receiving Split Modal --}}
                <div x-show="receivingSplitModal.open" x-transition.opacity class="fixed inset-0 z-[195] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display:none">
                    <div @@click.stop class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-3xl overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Split Vendor Photos</h3>
                                <p class="text-sm text-slate-500 mt-1">Select the vendor photos to move into a new package for <span class="font-semibold text-slate-700" x-text="receivingSplitModal.packageLabel"></span>.</p>
                            </div>
                            <button @@click="closeReceivingSplitModal()" class="w-10 h-10 rounded-xl border border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-50 transition-colors">
                                <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <template x-for="photo in receivingSplitModal.photos" :key="photo.id">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <button type="button"
                                                @@click="toggleReceivingSplitPhoto(photo.id)"
                                                class="relative w-full rounded-2xl overflow-hidden border-2 transition-all"
                                                :class="receivingSplitModal.selectedIds.includes(photo.id) ? 'border-indigo-500 ring-2 ring-indigo-500/20 scale-[0.98]' : 'border-slate-200 hover:border-indigo-300'">
                                            <img :src="photo.url" class="w-full aspect-square object-cover">
                                            <div x-show="receivingSplitModal.selectedIds.includes(photo.id)"
                                                 class="absolute inset-0 bg-indigo-600/20 flex items-start justify-end p-2" style="display:none">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </span>
                                            </div>
                                        </button>
                                        <template x-if="photo.recipient_phone">
                                            <span class="flex items-center gap-1 text-[10px] font-semibold text-indigo-600 max-w-full truncate" :title="photo.recipient_phone">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                <span x-text="photo.recipient_phone"></span>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">A new package will be created with the selected vendor photos and the same delivery setup.</p>
                            <div class="flex items-center gap-3">
                                <button @@click="closeReceivingSplitModal()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 border border-slate-200 rounded-xl hover:bg-white transition-colors">Cancel</button>
                                <button @@click="executeReceivingSplit()" :disabled="receivingSplitModal.selectedIds.length === 0 || receivingSplitModal.saving"
                                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-colors disabled:opacity-50">
                                    <svg x-show="receivingSplitModal.saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="receivingSplitModal.saving ? 'Splitting...' : ('Split (' + receivingSplitModal.selectedIds.length + ' photo' + (receivingSplitModal.selectedIds.length === 1 ? '' : 's') + ')')"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Receiving Lightbox --}}
                <div x-show="receivingLightbox" @@click="receivingLightbox = null" @@keydown.escape.window="receivingLightbox = null"
                     x-transition.opacity class="fixed inset-0 z-[200] bg-black/85 flex items-center justify-center p-8 cursor-pointer" style="display:none">
                    <img :src="receivingLightbox" class="max-w-full max-h-full rounded-2xl shadow-2xl">
                    <button @@click="receivingLightbox = null" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- GLOBAL MODALS — inside x-data scope, outside the card             --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}

    <!-- ══ MODAL: Create Invoice ════════════════════════════════════════ -->
    <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="invoiceModalOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="invoiceModalOpen = false"></div>
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
                        <button type="button" @@click="invoiceModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="invoiceForm.submitting"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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

    <!-- ══ MODAL: Invoice Detail ════════════════════════════════════════ -->
    <div x-show="invoiceDetailModalOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="invoiceDetailModalOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="invoiceDetailModalOpen = false"></div>
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
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
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Send to Vendor
                            </button>
                            <button type="button" x-show="canManage && invoiceDetail.status === 'sent'"
                                @@click="adminAcceptInvoice(invoiceDetail.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Admin Accept
                            </button>
                            <button type="button" x-show="canManage && ['pending','sent'].includes(invoiceDetail.status)"
                                @@click="openCancelInvoiceModal(invoiceDetail.id)"
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
    </div>

    <!-- ══ MODAL: Assign Driver ════════════════════════════════════════ -->
    <div x-show="assignDriverModalOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="assignDriverModalOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="assignDriverModalOpen = false"></div>
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
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
                        <button type="submit" :disabled="assignmentForm.submitting || !assignmentForm.driver_id || !assignmentForm.target_warehouse_id" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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
    </div>

    <!-- ══ MODAL: Edit Assignment ══════════════════════════════════════ -->
    <div x-show="editAssignmentOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="editAssignmentOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="editAssignmentOpen = false"></div>
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
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
                        <button type="submit" :disabled="editAssignmentForm.submitting" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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

    {{-- Unassign Driver Modal --}}
    <div x-show="showUnassignModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="showUnassignModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="showUnassignModal = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div x-show="showUnassignModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200/50 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Unassign Driver</h3>
                            <p class="text-xs text-slate-500">This action cannot be undone</p>
                        </div>
                    </div>
                    <button @@click="showUnassignModal = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4">
                    <label for="unassign-reason" class="block text-xs font-semibold text-slate-700 mb-1.5">Reason for unassignment <span class="text-rose-500">*</span></label>
                    <textarea id="unassign-reason" x-model="unassignReason" rows="3" placeholder="Provide a reason for unassigning this driver..." class="w-full px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-rose-400/50 focus:border-rose-300 transition-colors resize-none"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Minimum 3 characters required</p>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                    <button type="button" @@click="showUnassignModal = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">Cancel</button>
                    <button type="button" @@click="confirmUnassign()" :disabled="assignmentActionLoading || !unassignReason.trim()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-rose-200 bg-white text-rose-700 text-sm font-semibold hover:bg-rose-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="assignmentActionLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="assignmentActionLoading ? 'Unassigning...' : 'Unassign Driver'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Invoice Modal --}}
    <div x-show="showCancelInvoiceModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="showCancelInvoiceModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="showCancelInvoiceModal = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div x-show="showCancelInvoiceModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200/50 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Cancel Invoice</h3>
                            <p class="text-xs text-slate-500">This action cannot be undone</p>
                        </div>
                    </div>
                    <button @@click="showCancelInvoiceModal = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-slate-600 mb-3">Are you sure you want to cancel this invoice? This will void the invoice and cannot be reversed.</p>
                    <label for="cancel-invoice-reason" class="block text-xs font-semibold text-slate-700 mb-1.5">Cancellation reason <span class="text-slate-400">(optional)</span></label>
                    <textarea id="cancel-invoice-reason" x-model="cancelInvoiceReason" rows="3" placeholder="Provide a reason for cancellation..." class="w-full px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-rose-400/50 focus:border-rose-300 transition-colors resize-none"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                    <button type="button" @@click="showCancelInvoiceModal = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">Go Back</button>
                    <button type="button" @@click="confirmCancelInvoice()" :disabled="cancelInvoiceLoading" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-rose-200 bg-white text-rose-700 text-sm font-semibold hover:bg-rose-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="cancelInvoiceLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="cancelInvoiceLoading ? 'Cancelling...' : 'Cancel Invoice'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fulfillment Type Toast -->
    <template x-teleport="body">
        <div x-show="ftToast" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-6 right-6 z-[9999] max-w-sm">
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl border"
                 :class="ftToastType === 'success' ? 'bg-emerald-600 border-emerald-500 text-white' : 'bg-red-600 border-red-500 text-white'">
                <svg x-show="ftToastType === 'success'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <svg x-show="ftToastType === 'error'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold" x-text="ftToast"></p>
                <button @@click="ftToast = ''" class="ml-auto shrink-0 hover:opacity-70">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </template>

</div>

@endsection
