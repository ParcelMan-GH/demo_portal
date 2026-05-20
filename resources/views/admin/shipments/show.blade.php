@extends('admin.layouts.app')

@section('title', 'Shipment - ' . $shipment->shipment_number)
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', $shipment->shipment_number)

@php
$shipmentConfig = [
    'shipment' => $shipment,
    'saveUrl' => route('admin.shipments.update', $shipment),
    'itemsEndpoint' => route('admin.shipments.items', $shipment),
    'trackingEndpoint' => route('admin.shipments.tracking', $shipment),
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
<div x-data="shipmentShow()" data-shipment-show-config="{{ json_encode($shipmentConfig) }}" class="space-y-6">

    <!-- Hero Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-6">
            @php
                $statusColors = match($shipment->status->value ?? $shipment->status) {
                    'draft' => 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30',
                    'submitted' => 'bg-blue-500/15 text-blue-100 ring-1 ring-blue-400/30',
                    'pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted' => 'bg-violet-500/15 text-violet-100 ring-1 ring-violet-400/30',
                    'in_transit', 'at_destination', 'out_for_delivery' => 'bg-amber-500/15 text-amber-100 ring-1 ring-amber-400/30',
                    'delivered' => 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/30',
                    'cancelled' => 'bg-rose-500/15 text-rose-100 ring-1 ring-rose-400/30',
                    default => 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30',
                };
                $dotColors = match($shipment->status->value ?? $shipment->status) {
                    'submitted' => 'bg-blue-300',
                    'pickup_assigned', 'picked_up', 'arrived_warehouse', 'at_warehouse', 'sorted' => 'bg-violet-300',
                    'in_transit', 'at_destination', 'out_for_delivery' => 'bg-amber-300',
                    'delivered' => 'bg-emerald-300',
                    'cancelled' => 'bg-rose-300',
                    default => 'bg-slate-300',
                };
                $pickupSummary = $currentAssignment
                    ? trim(($currentAssignment->driver?->name ?? 'Assigned') . ($currentAssignment->targetWarehouse?->name ? ' to ' . $currentAssignment->targetWarehouse->name : ''))
                    : 'Needs rider';
            @endphp

            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ route('admin.operations.shipments.index') }}" class="inline-flex h-11 w-auto shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back</span>
                </a>

                <div class="ml-auto flex w-auto max-w-[calc(100%-5.75rem)] flex-wrap items-center justify-end gap-2 sm:max-w-none">
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black {{ $statusColors }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $dotColors }}"></span>
                        {{ $shipment->status->label() }}
                    </span>
                    <button type="button"
                            @@click="openTrackingHistoryModal()"
                            class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-slate-400/30 bg-white/10 px-3 text-xs font-black text-slate-100 transition hover:bg-white/15">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                        </svg>
                        Activity
                    </button>
                    @if($canManage)
                        <button type="button"
                                x-show="canManagePickupAssignment()"
                                x-cloak
                                @@click="openAssignPickupDriver()"
                                class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/>
                            </svg>
                            <span x-text="assignment ? 'Reassign Rider' : 'Assign Rider'"></span>
                        </button>
                        <button @@click="duplicateShipment()" :disabled="duplicating"
                                title="Duplicate shipment"
                                aria-label="Duplicate shipment"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/10 text-slate-100 transition hover:bg-white/15 disabled:opacity-50">
                            <svg x-show="!duplicating" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                            </svg>
                            <svg x-show="duplicating" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="relative mt-5 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[700px] lg:shrink">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-950/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 8-9-5-9 5 9 5 9-5ZM3 8v8l9 5 9-5V8"/>
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Shipment Workspace</p>
                            <h1 class="mt-1 max-w-4xl break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl xl:text-2xl 2xl:text-3xl">{{ $shipment->shipment_number }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                <span>{{ $shipment->vendor->name }}</span>
                                @if($shipment->vendor->business_name)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ $shipment->vendor->business_name }}</span>
                                @endif
                                <span class="text-slate-600">/</span>
                                <span>{{ $shipment->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                                @if($shipment->isPerItemDestination())
                                    <span>Per-item recipients</span>
                                @elseif($shipment->delivery_recipient_name || $shipment->delivery_recipient_phone)
                                    <span>{{ trim(($shipment->delivery_recipient_name ?? '') . ($shipment->delivery_recipient_phone ? ' / ' . $shipment->delivery_recipient_phone : '')) }}</span>
                                @else
                                    <span>Recipient details pending</span>
                                @endif
                                <span class="text-slate-600">/</span>
                                <span x-text="fulfillmentTypeLabel()"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:ml-auto lg:w-[760px] lg:shrink-0 lg:grid-cols-4 2xl:w-[880px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg" x-text="formatQuantityValue(quantityVendorDeclared())">{{ number_format($shipment->vendor_declared_quantity ?: $itemsCount) }}</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">Vendor declared</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-violet-500/20 text-violet-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg" x-text="formatQuantityValue(quantityDriverPicked())"></p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs" x-text="quantityDriverPicked() === null ? 'Rider pending' : 'Rider picked'"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg" x-text="formatQuantityValue(quantityWarehouseReceived())"></p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs" x-text="quantityWarehouseReceived() === null ? 'Warehouse pending' : 'Warehouse received'"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-500/20 text-rose-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg" x-text="formatSignedQuantity(quantityDifference())"></p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs" x-text="quantityDifferenceLabel()"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($latestTimelineEvent)
        <section x-data="{ timelineOpen: false }" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/30">
            <button type="button" @@click="timelineOpen = !timelineOpen" class="flex w-full items-center justify-between gap-3 px-4 py-4 text-left transition hover:bg-slate-50 sm:px-5">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Shipment Timeline</p>
                    <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                        <span class="text-sm font-black text-slate-950">{{ $latestTimelineEvent['label'] }}</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-xs font-bold text-slate-500">{{ $formatTimelineDate($latestTimelineEvent['value']) }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600 sm:inline">{{ $timelineEvents->count() }} events</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500">
                        <svg class="h-4 w-4 transition-transform" :class="timelineOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                        </svg>
                    </span>
                </div>
            </button>

            <div x-show="timelineOpen" x-cloak x-transition.opacity.duration.150ms class="border-t border-slate-100 px-4 py-2 sm:px-5" style="display: none;">
                <div class="divide-y divide-slate-100">
                    @foreach($timelineEvents as $event)
                        <div class="flex gap-3 py-3">
                            <div class="flex w-3 shrink-0 justify-center pt-1.5">
                                <span class="h-2 w-2 rounded-full {{ $event['dot'] ?? 'bg-slate-400' }}"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-sm font-black text-slate-900">{{ $event['label'] }}</p>
                                    <p class="text-xs font-bold text-slate-500">{{ $formatTimelineDate($event['value']) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

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
                                        <template x-if="shipment.status === 'submitted'">
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
                                                    Assign Rider
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="shipment.status !== 'submitted'">
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
                    <button x-show="canManage && shipment.status === 'submitted'" @@click="loadAssignmentDependencies(); assignDriverModalOpen = true" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Assign Rider
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

                {{-- Assign Rider + Edit Assignment modals are placed globally below --}}
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
                                    <h3 class="text-base font-bold text-slate-900">Assign Rider</h3>
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
                                        <span x-text="assignmentForm.submitting ? 'Assigning...' : 'Assign Rider'"></span>
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
                    'finalizeSubtitle' => 'Mark all packages as received and move shipment to warehouse status.',
                    'showPickupFee' => false,
                    'showDropOffSelect' => false,
                ])

                <div class="hidden">
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
	                        @include('shared.receiving.packages-workspace', [
	                            'packagesExpr' => 'receiving.packages',
	                            'showToolbar' => true,
	                            'showAdminPackageControls' => true,
	                            'detailsClick' => 'openPackageDetailsModal(pkg)',
	                            'receiveClick' => 'openReceivingPackageModal(pkg, 1)',
	                            'photosClick' => 'openReceivingPhotosModal(pkg)',
	                            'printClick' => 'openReceivingLabelPrintModal(pkg)',
	                            'finalizeClick' => 'openFinalizeConfirm()',
	                            'finalizeDisabled' => '!canFinalizeReceiving()',
	                            'finalizeLabelExpr' => 'finalizeReceivingButtonLabel()',
	                            'finalizeSubtitle' => 'Mark all packages as received and move shipment to warehouse status.',
	                        ])

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
                </div>

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

	                {{-- Package Details Modal --}}
		                <div x-show="packageDetailsModal.open" @@click="closePackageDetailsModal()" x-transition.opacity class="fixed inset-0 z-[188] flex justify-end bg-black/55 backdrop-blur-sm" style="display:none">
		                    <template x-if="packageDetailsModal.pkg">
		                        <div @@click.stop x-transition:enter="transition ease-out duration-200"
		                             x-transition:enter-start="translate-x-full"
		                             x-transition:enter-end="translate-x-0"
		                             x-transition:leave="transition ease-in duration-150"
		                             x-transition:leave-start="translate-x-0"
		                             x-transition:leave-end="translate-x-full"
		                             class="flex h-full w-full max-w-2xl flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
		                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
		                                <div class="min-w-0">
		                                    <h3 class="text-lg font-bold text-slate-900">Package Details</h3>
		                                    <p class="mt-1 truncate text-sm text-slate-500" x-text="packageDetailsModal.packageLabel"></p>
		                                    <p x-show="packageDetailsModal.pkg.tracking_code" class="mt-1 font-mono text-xs font-bold text-slate-400" x-text="packageDetailsModal.pkg.tracking_code"></p>
		                                </div>
		                                <button type="button" @@click="closePackageDetailsModal()" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
		                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
		                                </button>
		                            </div>

		                            <div class="flex-1 space-y-5 overflow-y-auto bg-slate-50/70 p-6">
		                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
		                                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
		                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Expected</p>
		                                        <p class="mt-1 text-lg font-black text-slate-900" x-text="packageDetailsModal.pkg.details?.quantities?.expected ?? receivingExpectedQuantity(packageDetailsModal.pkg)"></p>
		                                    </div>
		                                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
		                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Received</p>
		                                        <p class="mt-1 text-lg font-black text-emerald-700" x-text="packageDetailsModal.pkg.details?.quantities?.received ?? packageDetailsModal.pkg.received_quantity"></p>
		                                    </div>
		                                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
		                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Remaining</p>
		                                        <p class="mt-1 text-lg font-black text-amber-700" x-text="packageDetailsModal.pkg.details?.quantities?.remaining ?? receivingPendingQuantity(packageDetailsModal.pkg)"></p>
		                                    </div>
		                                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
		                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Condition</p>
		                                        <p class="mt-1 text-sm font-black" :class="receivingConditionTextClass(packageDetailsModal.pkg.condition_status)" x-text="receivingConditionLabel(packageDetailsModal.pkg.condition_status)"></p>
		                                    </div>
		                                </div>

		                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
		                                    <div class="grid gap-4 sm:grid-cols-2">
		                                        <div>
		                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Vendor Submission</p>
		                                            <p class="mt-1 text-sm font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.shipment?.vendor_name || 'Unknown vendor'"></p>
		                                            <p class="mt-0.5 text-xs text-slate-500" x-text="packageDetailsModal.pkg.details?.shipment?.submitted_at ? formatDateTime(packageDetailsModal.pkg.details.shipment.submitted_at) : formatDateTime(packageDetailsModal.pkg.details?.shipment?.created_at)"></p>
		                                        </div>
		                                        <div>
		                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Status</p>
		                                            <p class="mt-1 text-sm font-black text-slate-900" x-text="receivingPackageStatusLabel(packageDetailsModal.pkg)"></p>
		                                            <p class="mt-0.5 text-xs text-slate-500" x-text="'Method: ' + packageDetailsMethodLabel(packageDetailsModal.pkg)"></p>
		                                            <p x-show="packageDeliveryStatusLabel(packageDetailsModal.pkg)" class="mt-0.5 text-xs font-bold" :class="packageDeliveryStatusClass(packageDetailsModal.pkg)" x-text="'Delivery: ' + packageDeliveryStatusLabel(packageDetailsModal.pkg)"></p>
		                                        </div>
		                                    </div>
		                                </div>

		                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Recipient & Delivery</h4>
		                                    <div class="mt-3 space-y-3">
		                                        <div>
		                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Recipient</p>
		                                            <p class="mt-0.5 text-sm font-bold text-slate-900">
		                                                <span x-text="packageDetailsModal.pkg.delivery_recipient_name || packageDetailsModal.pkg.details?.delivery?.recipient_name || '-'"></span>
		                                                <span x-show="packageDetailsModal.pkg.delivery_recipient_phone || packageDetailsModal.pkg.details?.delivery?.recipient_phone" class="text-slate-500" x-text="' · ' + (packageDetailsModal.pkg.delivery_recipient_phone || packageDetailsModal.pkg.details?.delivery?.recipient_phone)"></span>
		                                            </p>
		                                        </div>
		                                        <div>
		                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery Location</p>
		                                            <p class="mt-0.5 text-sm font-semibold leading-relaxed text-slate-800" x-text="packageDetailsLocation(packageDetailsModal.pkg.details?.delivery || {})"></p>
		                                        </div>
		                                        <div class="grid gap-3 sm:grid-cols-3">
		                                            <div x-show="packageDeliveryStatusLabel(packageDetailsModal.pkg)">
		                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery Status</p>
		                                                <p class="mt-0.5 text-sm font-bold" :class="packageDeliveryStatusClass(packageDetailsModal.pkg)" x-text="packageDeliveryStatusLabel(packageDetailsModal.pkg)"></p>
		                                            </div>
		                                            <div>
		                                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery Fee</p>
		                                                <p class="mt-0.5 text-sm font-bold" :class="receivingDeliveryFeeClass(packageDetailsModal.pkg)" x-text="receivingDeliveryFeeLabel(packageDetailsModal.pkg)"></p>
		                                            </div>
		                                        </div>
		                                    </div>
		                                </div>

		                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Pickup & Custody</h4>
		                                    <div class="mt-3 space-y-3 text-sm">
		                                        <p class="font-semibold text-slate-800" x-text="'Pickup driver: ' + (packageDetailsModal.pkg.details?.pickup?.driver_name || '-') + (packageDetailsModal.pkg.details?.pickup?.driver_phone ? ' · ' + packageDetailsModal.pkg.details.pickup.driver_phone : '')"></p>
		                                        <p class="font-semibold text-slate-800" x-text="'Current custody: ' + packageCustodySummary(packageDetailsModal.pkg)"></p>
		                                        <p class="text-slate-600" x-text="'Pickup location: ' + packageDetailsLocation(packageDetailsModal.pkg.details?.pickup || {})"></p>
		                                        <p x-show="packageDetailsModal.pkg.details?.pickup?.picked_up_at || packageDetailsModal.pkg.details?.pickup?.completed_at" class="text-xs font-semibold text-slate-500" x-text="'Picked up: ' + formatDateTime(packageDetailsModal.pkg.details?.pickup?.picked_up_at || packageDetailsModal.pkg.details?.pickup?.completed_at)"></p>
		                                    </div>
		                                </div>

		                                <div x-show="packageDetailsModal.pkg.delivery_method === 'bus_handoff' || packageDetailsModal.pkg.details?.bus_handoff?.station_name" class="rounded-2xl border border-violet-200 bg-white p-4" style="display:none">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-violet-700">Bus Handoff</h4>
		                                    <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
		                                        <p><span class="font-black text-slate-400">Station</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.bus_handoff?.station_name || '-'"></span></p>
		                                        <p><span class="font-black text-slate-400">Courier</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.bus_handoff?.courier_name || '-'"></span></p>
		                                        <p><span class="font-black text-slate-400">Phone</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.bus_handoff?.courier_phone || '-'"></span></p>
		                                        <p><span class="font-black text-slate-400">Vehicle</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.bus_handoff?.vehicle_number || '-'"></span></p>
		                                    </div>
		                                    <p x-show="packageDetailsModal.pkg.details?.bus_handoff?.handoff_at" class="mt-3 text-xs font-semibold text-slate-500" x-text="'Handed off: ' + formatDateTime(packageDetailsModal.pkg.details?.bus_handoff?.handoff_at)"></p>
		                                </div>

		                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Delivery Proof</h4>
		                                    <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
		                                        <p><span class="font-black text-slate-400">Run</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.delivery_proof?.run_number || '-'"></span></p>
		                                        <p><span class="font-black text-slate-400">Driver</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.delivery_proof?.driver_name || '-'"></span></p>
		                                        <p><span class="font-black text-slate-400">Delivered</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.delivery_proof?.delivered_at ? formatDateTime(packageDetailsModal.pkg.details.delivery_proof.delivered_at) : '-'"></span></p>
		                                        <p><span class="font-black text-slate-400">Coordinates</span><br><span class="font-bold text-slate-900" x-text="packageDetailsModal.pkg.details?.delivery_proof?.latitude && packageDetailsModal.pkg.details?.delivery_proof?.longitude ? packageDetailsModal.pkg.details.delivery_proof.latitude + ', ' + packageDetailsModal.pkg.details.delivery_proof.longitude : '-'"></span></p>
		                                    </div>
		                                </div>

		                                <div x-show="packageDetailsPhotoGroups(packageDetailsModal.pkg).length" class="rounded-2xl border border-slate-200 bg-white p-4" style="display:none">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Photos</h4>
		                                    <div class="mt-3 space-y-4">
		                                        <template x-for="group in packageDetailsPhotoGroups(packageDetailsModal.pkg)" :key="group.key">
		                                            <div>
		                                                <p class="mb-2 text-[10px] font-black uppercase tracking-wide text-slate-400" x-text="group.title"></p>
		                                                <div class="grid grid-cols-3 gap-2">
		                                                    <template x-for="photo in group.photos" :key="photo.id || photo.url">
		                                                        <button type="button" @@click="receivingLightbox = photo.url" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition hover:border-orange-300">
		                                                            <img :src="photo.url" class="h-24 w-full object-cover" :alt="photo.original_name || group.title">
		                                                        </button>
		                                                    </template>
		                                                </div>
		                                            </div>
		                                        </template>
		                                    </div>
		                                </div>

		                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Charges</h4>
		                                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-100">
		                                        <table class="w-full text-left text-xs">
		                                            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
		                                                <tr><th class="px-3 py-2">Fee</th><th class="px-3 py-2">Payer</th><th class="px-3 py-2 text-right">Amount</th><th class="px-3 py-2">Status</th></tr>
		                                            </thead>
		                                            <tbody class="divide-y divide-slate-100">
		                                                <tr x-show="packageDetailsChargeRows(packageDetailsModal.pkg).length === 0">
		                                                    <td colspan="4" class="px-3 py-4 text-center font-semibold text-slate-400">No charge records found.</td>
		                                                </tr>
		                                                <template x-for="charge in packageDetailsChargeRows(packageDetailsModal.pkg)" :key="charge.id">
		                                                    <tr>
		                                                        <td class="px-3 py-2 font-bold text-slate-900" x-text="packageDetailsStatusLabel(charge.type)"></td>
		                                                        <td class="px-3 py-2 font-semibold text-slate-600" x-text="packageDetailsStatusLabel(charge.payer)"></td>
		                                                        <td class="px-3 py-2 text-right font-black text-slate-900" x-text="formatMoney(charge.amount, charge.currency)"></td>
		                                                        <td class="px-3 py-2 font-bold text-slate-600" x-text="packageDetailsStatusLabel(charge.status)"></td>
		                                                    </tr>
		                                                </template>
		                                            </tbody>
		                                        </table>
		                                    </div>
		                                </div>

		                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
		                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Tracking History</h4>
		                                    <div class="mt-4 space-y-3">
		                                        <p x-show="packageDetailsTimeline(packageDetailsModal.pkg).length === 0" class="text-sm font-semibold text-slate-400">No package tracking history found.</p>
		                                        <template x-for="(event, index) in packageDetailsTimeline(packageDetailsModal.pkg)" :key="event.label + event.created_at + index">
		                                            <div class="relative border-l border-slate-200 pl-4">
		                                                <span class="absolute -left-1.5 top-1.5 h-3 w-3 rounded-full border-2 border-white bg-orange-500"></span>
		                                                <p class="text-sm font-black text-slate-900" x-text="event.label"></p>
		                                                <p class="mt-0.5 text-xs font-semibold text-slate-500" x-text="formatDateTime(event.created_at)"></p>
		                                                <p x-show="event.location" class="mt-1 text-xs text-slate-600" x-text="event.location"></p>
		                                                <p x-show="event.notes" class="mt-1 text-xs text-slate-500" x-text="event.notes"></p>
		                                            </div>
		                                        </template>
		                                    </div>
		                                </div>
		                            </div>
		                        </div>
		                    </template>
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
	                <div x-show="receivingPackageModal.open" x-transition.opacity class="fixed inset-0 z-[190] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4" style="display:none">
	                    <template x-if="receivingPackageModal.pkg">
	                        <div @@click.stop class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-3xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
	                            <div class="shrink-0 border-b border-slate-100 bg-white p-5 sm:p-6">
	                                <div class="flex items-start justify-between gap-4">
	                                    <div class="flex min-w-0 items-start gap-4">
	                                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-orange-600 text-white shadow-xl shadow-orange-500/25">
	                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6.75V15m0 0l-2.25-2.25M9 15l2.25-2.25M15 6.75h.01M18.25 17.25V6.75A2.25 2.25 0 0016 4.5H8a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 008 19.5h8a2.25 2.25 0 002.25-2.25z"/></svg>
	                                        </span>
	                                        <div class="min-w-0">
	                                            <h3 class="text-2xl font-black leading-tight text-slate-950" x-text="receiving.canReceive ? 'Receive Package' : 'Package Details'"></h3>
	                                            <p class="mt-1 text-sm font-semibold leading-relaxed text-slate-500" x-text="isPerItemMode() ? 'Confirm the line, quantity, condition, and destination before saving.' : 'Confirm the line, quantity, condition, and notes before saving.'"></p>
	                                        </div>
	                                    </div>
	                                    <button type="button" @@click="closeReceivingPackageModal()" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
	                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                                    </button>
	                                </div>
	                            </div>

	                            <div class="flex-1 overflow-y-auto p-5 sm:p-6">
	                                <div x-show="receivingPackageModal.step === 1" class="space-y-4">
	                                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
	                                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Package</p>
	                                        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
	                                            <div class="min-w-0">
	                                                <h4 class="break-words text-2xl font-black leading-tight text-slate-950" x-text="receivingPackageModal.pkg.description || receivingPackageModal.packageLabel || 'Package'"></h4>
	                                                <p class="mt-1 font-mono text-base font-black text-slate-500" x-text="receivingPackageModal.pkg.tracking_code || receivingPackageModal.packageLabel"></p>
	                                                <p class="mt-3 text-sm font-bold text-slate-600">
	                                                    <span x-text="receivingPackageModal.pkg.recipient_name || receivingPackageModal.pkg.delivery_recipient_name || 'Recipient pending'"></span>
	                                                    <span x-show="receivingPackageModal.pkg.recipient_phone || receivingPackageModal.pkg.delivery_recipient_phone" class="text-slate-300"> / </span>
	                                                    <span x-text="receivingPackageModal.pkg.recipient_phone || receivingPackageModal.pkg.delivery_recipient_phone || ''"></span>
	                                                </p>
	                                            </div>
	                                            <div class="grid min-w-[240px] grid-cols-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
	                                                <div class="border-r border-slate-200 p-3">
	                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Expected</p>
	                                                    <p class="mt-1 text-2xl font-black text-slate-950" x-text="receivingExpectedQuantity(receivingPackageModal.pkg)"></p>
	                                                </div>
	                                                <div class="border-r border-slate-200 p-3">
	                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Picked</p>
	                                                    <p class="mt-1 text-2xl font-black text-slate-950" x-text="receivingPackageModal.pkg.driver_confirmed_quantity ?? '-'"></p>
	                                                </div>
	                                                <div class="p-3">
	                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Received</p>
	                                                    <p class="mt-1 text-2xl font-black text-emerald-700" x-text="receivingPackageModal.pkg.received_quantity ?? 0"></p>
	                                                </div>
	                                            </div>
	                                        </div>
	                                    </div>
	                                    <div>
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Description</label>
	                                        <input type="text" x-model="receivingPackageModal.pkg.description" placeholder="What's inside?"
	                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20">
	                                    </div>
	                                    <div x-show="receiving.canReceive" class="grid grid-cols-1 gap-4 sm:grid-cols-3" style="display:none">
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
	                                    <div x-show="receiving.canReceive" style="display:none">
	                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Receiving Notes</label>
	                                        <textarea rows="2" x-model="receivingPackageModal.pkg.notes" placeholder="Receiving notes..."
	                                                  class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-500/20"></textarea>
	                                    </div>
	                                    <div x-show="receiving.canReceive" class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-4" style="display:none">
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
	                                    <button type="button" x-show="receiving.canReceive && (!isPerItemMode() || receivingPackageModal.step === 2)"
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
                                            You do not have permission to approve discrepancy finalization. A warehouse manager or HQ administrator needs to finalize this receipt.
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

    <!-- ══ MODAL: Assign Rider ════════════════════════════════════════ -->
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
             class="relative z-10 w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="relative border-b border-slate-200 px-6 py-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="assignment ? 'Reassign Rider' : 'Assign Rider'"></h3>
                        <p class="mt-1 text-sm text-slate-500">Select the pickup rider and receiving warehouse.</p>
                    </div>
                </div>
                <button @@click="assignDriverModalOpen = false" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                </div>
            </div>
            <form @@submit.prevent="assignDriver()">
                <div class="max-h-[calc(100vh-240px)] space-y-5 overflow-y-auto px-6 py-6">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Select Driver <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select x-model="assignmentForm.driver_id" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 pr-10 text-sm text-slate-900 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100" required>
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
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                        <textarea x-model="assignmentForm.notes" rows="3" class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Optional pickup notes for the rider..."></textarea>
                    </div>
                </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                        <button type="button" @@click="assignDriverModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="assignmentForm.submitting || !assignmentForm.driver_id || !assignmentForm.target_warehouse_id" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition-all hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none">
                            <svg x-show="assignmentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="assignmentForm.submitting ? 'Saving...' : (assignment ? 'Save Rider' : 'Assign Rider')"></span>
                        </button>
                    </div>
                </form>
        </div>
        </div>
    </div>

    <!-- ══ MODAL: Edit Assignment ══════════════════════════════════════ -->
    <div x-show="editAssignmentOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="editAssignmentOpen = false">
        <div x-show="editAssignmentOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="editAssignmentOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="editAssignmentOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @@click.stop
             class="relative z-10 w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="relative border-b border-slate-200 px-6 py-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Edit Assignment</h3>
                        <p class="mt-1 text-sm text-slate-500">Change the rider or receiving warehouse.</p>
                    </div>
                </div>
                <button @@click="editAssignmentOpen = false" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                </div>
            </div>
            <form @@submit.prevent="updateAssignment()">
                <div class="max-h-[calc(100vh-240px)] space-y-5 overflow-y-auto px-6 py-6">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Driver</label>
                        <div class="relative">
                            <select x-model="editAssignmentForm.driver_id" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 pr-10 text-sm text-slate-900 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
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
                            <select x-model="editAssignmentForm.target_warehouse_id" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 pr-10 text-sm text-slate-900 transition-all focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
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
                </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                        <button type="button" @@click="editAssignmentOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editAssignmentForm.submitting" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition-all hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none">
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

    {{-- Unassign Rider Modal --}}
    <div x-show="showUnassignModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="showUnassignModal = false">
        <div x-show="showUnassignModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showUnassignModal = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div x-show="showUnassignModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop
                 class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="relative border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-lg shadow-rose-600/20">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Unassign Rider</h3>
                            <p class="mt-1 text-sm text-slate-500">Record why this pickup rider is being removed.</p>
                        </div>
                    </div>
                    <button @@click="showUnassignModal = false" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    </div>
                </div>
                <div class="px-6 py-6">
                    <label for="unassign-reason" class="mb-2 block text-sm font-semibold text-slate-700">Reason for unassignment <span class="text-rose-500">*</span></label>
                    <textarea id="unassign-reason" x-model="unassignReason" rows="4" placeholder="Provide a reason for unassigning this rider..." class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-rose-400 focus:ring-4 focus:ring-rose-100"></textarea>
                    <p class="mt-2 text-xs text-slate-500">Minimum 3 characters required.</p>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <button type="button" @@click="showUnassignModal = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50">Cancel</button>
                    <button type="button" @@click="confirmUnassign()" :disabled="assignmentActionLoading || !unassignReason.trim()" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-rose-600/20 transition-all hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none">
                        <svg x-show="assignmentActionLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="assignmentActionLoading ? 'Unassigning...' : 'Unassign Rider'"></span>
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
