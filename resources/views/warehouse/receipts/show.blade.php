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
    $heroStatusClasses = match ($statusValue) {
        'assigned' => 'border-orange-300/20 bg-orange-500/15 text-orange-100',
        'en_route' => 'border-indigo-300/20 bg-indigo-500/15 text-indigo-100',
        'arrived' => 'border-amber-300/20 bg-amber-500/15 text-amber-100',
        'picking_up' => 'border-violet-300/20 bg-violet-500/15 text-violet-100',
        'completed' => 'border-emerald-300/20 bg-emerald-500/15 text-emerald-100',
        'cancelled' => 'border-rose-300/20 bg-rose-500/15 text-rose-100',
        default => 'border-white/10 bg-white/10 text-slate-200',
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

    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.24),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ route('warehouse.receipts.pending.index') }}" class="group inline-flex h-11 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-white backdrop-blur transition hover:bg-white/15">
                    <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back</span>
                </a>
                <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                    <span class="inline-flex h-9 items-center gap-2 rounded-full border px-3 text-[11px] font-black uppercase tracking-wide {{ $heroStatusClasses }}">
                        <span class="h-2 w-2 rounded-full {{ $statusDotClasses }}"></span>
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
                        <span class="inline-flex h-9 items-center rounded-full border border-white/15 px-3 text-[11px] font-black uppercase tracking-wide {{ $receiptStatusClasses }}">
                            Receipt: {{ $receiptLabel }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="relative mt-7 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[760px] lg:shrink">
                    <div class="flex min-w-0 items-start gap-4 sm:gap-5">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-orange-600 text-white shadow-xl shadow-orange-500/25 ring-4 ring-white/10 sm:h-20 sm:w-20">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7.5 12 3 4 7.5m16 0-8 4.5m8-4.5v9L12 21m0-9L4 7.5m8 4.5v9M4 7.5v9L12 21"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-200">Warehouse Receiving</p>
                            <h1 class="mt-2 break-words text-2xl font-black leading-tight text-white sm:text-3xl lg:text-4xl">{{ $shipment?->shipment_number ?? '-' }}</h1>
                            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-semibold text-slate-300">
                                <span>{{ $shipment?->vendor?->name ?? 'Vendor' }}</span>
                                @if($shipment?->vendor?->business_name)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ $shipment->vendor->business_name }}</span>
                                @endif
                                <span class="hidden h-1 w-1 rounded-full bg-slate-600 sm:inline-flex"></span>
                                <span>Driver: {{ $assignment->driver?->name ?? '-' }}</span>
                                @if($assignment->driver?->phone)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ $assignment->driver->phone }}</span>
                                @endif
                                <span class="hidden h-1 w-1 rounded-full bg-slate-600 sm:inline-flex"></span>
                                <span>{{ $assignment->targetWarehouse?->name ?? '-' }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-200">
                                    Pickup Assignment #{{ $assignment->id }}
                                </span>
                                <span class="inline-flex items-center rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-200">
                                    Assigned {{ optional($assignment->assigned_at)?->format('d M Y, h:i A') ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:w-[430px] lg:shrink-0 2xl:w-[480px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg"><span x-text="receivingDeclaredQuantity()">{{ number_format($items->first()?->shipment?->vendor_declared_quantity ?: $items->sum('quantity')) }}</span> declared</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs"><span x-text="receivingReceivedUnits()">0</span> received at warehouse</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-7 4h8m4-14v16l-2-1-2 1-2-1-2 1-2-1-2 1V4l2 1 2-1 2 1 2-1 2 1 2-1z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg"><span x-text="receivingExpectedUnits()">0</span> expected</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs"><span x-text="receivingPendingUnits()">0</span> pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 13 4 4L19 7"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg"><span x-text="receivingReceivedUnits()">0</span> received</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">quantity recorded</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-500/20 text-sky-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg"><span x-text="receivingPhotoCount()">0</span> photos</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs"><span x-text="discrepancyCount()">0</span> issues</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $timelineEvents = collect($timeline)->filter(fn ($event) => filled($event['value']))->values();
        $latestTimelineEvent = $timelineEvents->last();
    @endphp

    @if($latestTimelineEvent)
        <section x-data="{ timelineOpen: false }" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/30">
            <button type="button" @@click="timelineOpen = !timelineOpen" class="flex w-full items-center justify-between gap-3 px-4 py-4 text-left transition hover:bg-slate-50 sm:px-5">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Receiving Timeline</p>
                    <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                        <span class="text-sm font-black text-slate-950">{{ $latestTimelineEvent['label'] }}</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-xs font-bold text-slate-500">{{ $latestTimelineEvent['value']->format('d M Y, h:i A') }}</span>
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
                                    <p class="text-xs font-bold text-slate-500">{{ $event['value']->format('d M Y, h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('shared.receiving.workspace', [
        'title' => 'Packages to Receive',
        'subtitle' => 'Review quantities, condition, photos, destinations, labels, and finalize this receipt.',
        'loadingExpr' => 'false',
        'packagesExpr' => 'items',
        'showPickupActions' => false,
        'pickupBadgeLabel' => $statusLabel,
        'pickupBadgeClasses' => $statusClasses,
        'showPickupFee' => false,
        'showTargetWarehouse' => false,
        'showDropOffSelect' => false,
        'sharedDestinationClick' => 'openReceivingSharedDestinationModal()',
        'showSharedDestinationEditExpr' => '!isPerItemMode() && canReceive && !isFinalized()',
        'showPackageToolbar' => true,
        'showSplitControls' => true,
        'showRemoveControls' => false,
        'detailsClick' => 'openReceivingPackageModal(pkg, 1)',
        'receiveClick' => 'openReceivingPackageModal(pkg, 1)',
        'photosClick' => 'openReceivingPhotosModal(pkg)',
        'printClick' => 'openPrintLabelModal(pkg)',
        'finalizeClick' => 'openFinalizeConfirm()',
        'finalizeDisabled' => 'isFinalized() || items.length === 0 || saving',
        'finalizeLabelExpr' => "isFinalized() ? 'Finalized' : 'Finalize Receipt'",
        'finalizeSubtitle' => 'Confirm the warehouse has completed receiving for this assignment.',
    ])

                <template x-teleport="body">
                    @include('shared.receiving.add-package-modal', [
                        'modal' => 'receivingAddPackageModal',
                        'closeAction' => 'closeReceivingAddPackageModal()',
                        'saveAction' => 'addReceivingPackage()',
                    ])
                </template>

                <template x-teleport="body">
                    <div x-show="splitModal.open" x-transition.opacity class="fixed inset-0 z-[189] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4" style="display:none">
                        <div @@click.stop class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                            <div class="shrink-0 border-b border-slate-100 bg-white p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-600 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.047 1.124-.047s.751.016 1.124.047c1.131.094 1.976 1.057 1.976 2.192V7.5M8.25 7.5h7.5M8.25 7.5l-.621 8.696A2.25 2.25 0 009.873 18.6h4.254a2.25 2.25 0 002.244-2.404L15.75 7.5"/></svg>
                                        </span>
                                        <div>
                                            <h3 class="text-lg font-extrabold text-slate-950">Split Photos</h3>
                                            <p class="mt-1 text-sm leading-relaxed text-slate-500">Move selected vendor photos into a new package.</p>
                                        </div>
                                    </div>
                                    <button type="button" @@click="closeReceivingSplitModal()" :disabled="splitModal.saving" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-y-auto p-5">
                                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                    <template x-for="photo in (splitModal.pkg?.vendor_photos || [])" :key="photo.id || photo.url">
                                        <button type="button" @@click="toggleSplitPhoto(photo.id, !(splitModal.selectedPhotoIds || []).includes(Number(photo.id)))" class="relative overflow-hidden rounded-2xl border-2 bg-slate-50 transition-all" :class="(splitModal.selectedPhotoIds || []).includes(Number(photo.id)) ? 'border-orange-600 ring-4 ring-orange-500/15' : 'border-slate-200 hover:border-orange-300'">
                                            <img :src="vendorPhotoUrl(photo)" class="aspect-square w-full object-cover">
                                            <span x-show="(splitModal.selectedPhotoIds || []).includes(Number(photo.id))" class="absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-white shadow-sm" style="display:none">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="shrink-0 border-t border-slate-100 bg-slate-50 px-5 py-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs text-slate-500">A new package will be created with the selected photos.</p>
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" @@click="closeReceivingSplitModal()" :disabled="splitModal.saving" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50">Cancel</button>
                                        <button type="button" @@click="submitSplitPackage()" :disabled="splitModal.saving || (splitModal.selectedPhotoIds || []).length === 0" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-6 py-3 text-sm font-bold text-white transition hover:bg-slate-800 disabled:opacity-50">
                                            <span x-text="splitModal.saving ? 'Splitting...' : ('Split (' + (splitModal.selectedPhotoIds || []).length + ')')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-teleport="body">
                    <div x-show="sharedDestinationModal.open" x-transition.opacity class="fixed inset-0 z-[187] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4" style="display:none">
                        <template x-if="sharedDestinationModal.form">
                            <div @@click.stop class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                                <div class="shrink-0 border-b border-slate-100 bg-white p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-4">
                                            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-600 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                            </span>
                                            <div>
                                                <h3 class="text-lg font-extrabold text-slate-950">Shared Destination</h3>
                                                <p class="mt-1 text-sm leading-relaxed text-slate-500">This location applies to all packages in one drop-off mode.</p>
                                            </div>
                                        </div>
                                        <button type="button" @@click="closeSharedDestinationModal()" :disabled="sharedDestinationModal.saving" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Recipient Name</label>
                                            <input type="text" x-model="sharedDestinationModal.form.delivery_recipient_name" :disabled="sharedDestinationModal.saving" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10 disabled:bg-slate-50">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Recipient Phone</label>
                                            <input type="text" x-model="sharedDestinationModal.form.delivery_recipient_phone" :disabled="sharedDestinationModal.saving" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10 disabled:bg-slate-50">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Location</label>
                                        <input type="text" x-model="sharedDestinationModal.form.delivery_town" :disabled="sharedDestinationModal.saving" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10 disabled:bg-slate-50">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Instructions</label>
                                        <textarea rows="3" x-model="sharedDestinationModal.form.delivery_instructions" :disabled="sharedDestinationModal.saving" class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10 disabled:bg-slate-50"></textarea>
                                    </div>
                                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-black text-slate-900">Bus handoff</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Apply bus-courier handling to this destination.</p>
                                        </div>
                                        <input type="checkbox" :checked="sharedDestinationModal.form.delivery_method === 'bus_handoff'" @@change="sharedDestinationModal.form.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                    </label>
                                </div>
                                <div class="shrink-0 border-t border-slate-100 bg-slate-50 px-5 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" @@click="closeSharedDestinationModal()" :disabled="sharedDestinationModal.saving" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50">Cancel</button>
                                        <button type="button" @@click="saveSharedDestination()" :disabled="sharedDestinationModal.saving" class="inline-flex items-center gap-2 rounded-2xl bg-orange-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-700 disabled:opacity-50">
                                            <span x-text="sharedDestinationModal.saving ? 'Saving...' : 'Save Destination'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-teleport="body">
                    <div x-show="photoModal.open" @@keydown.escape.window="closeItemPhotos()" x-transition.opacity class="fixed inset-0 z-[200] flex min-h-dvh w-screen items-center justify-center bg-black/85 p-4 backdrop-blur-sm" style="display:none">
                        <div class="absolute inset-0" @@click="closeItemPhotos()"></div>
                        <button type="button" @@click="closeItemPhotos()" class="absolute right-4 top-4 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                        <button type="button" x-show="photoModal.photos.length > 1" @@click.stop="previousItemPhoto()" class="absolute left-4 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" style="display:none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <template x-if="photoModal.photos[photoModal.index]">
                            <div class="relative z-10 max-w-[92vw] text-center">
                                <img :src="photoModal.photos[photoModal.index].url" class="max-h-[82dvh] max-w-full rounded-2xl object-contain shadow-2xl">
                                <p class="mt-3 text-sm font-semibold text-white/80" x-text="photoModal.title"></p>
                            </div>
                        </template>
                        <button type="button" x-show="photoModal.photos.length > 1" @@click.stop="nextItemPhoto()" class="absolute right-4 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" style="display:none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </template>

                {{-- ═══════════════════════════════════════ --}}
                {{-- RECEIVE ITEM MODAL                      --}}
                {{-- ═══════════════════════════════════════ --}}
                <template x-if="receiveModal.open && receiveModal.itemIndex >= 0">
                    <div class="fixed inset-0 z-[120] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4">
                        <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">

                            {{-- Modal Header --}}
                            <div class="shrink-0 border-b border-slate-100 bg-white p-5">
                                <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-600 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-extrabold text-slate-950">Receive Package</h4>
                                        <p class="mt-1 text-sm font-semibold leading-relaxed text-slate-500" x-text="items[receiveModal.itemIndex]?.description"></p>
                                    </div>
                                </div>
                                <button type="button" @@click="closeReceiveModal()" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                                </div>
                            </div>

                            {{-- Reference quantities --}}
                            <div class="grid shrink-0 grid-cols-3 border-b border-slate-100 bg-slate-50/70">
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
                            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">

                                {{-- Qty inputs --}}
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-2">Received Qty <span class="text-rose-400">*</span></label>
                                        <input
                                            type="number" min="0"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10"
                                            x-model.number="items[receiveModal.itemIndex].received_quantity"
                                            placeholder="0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-2">Damaged Qty</label>
                                        <input
                                            type="number" min="0"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10"
                                            x-model.number="items[receiveModal.itemIndex].damaged_quantity"
                                            placeholder="0"
                                        >
                                    </div>
                                </div>

                                {{-- Condition --}}
                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-600">Condition</label>
                                    <select x-model="items[receiveModal.itemIndex].condition_status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10">
                                        <option value="ok">Good</option>
                                        <option value="damaged">Damaged</option>
                                        <option value="partial">Partial Damage</option>
                                    </select>
                                </div>

                                {{-- Package destination for multiple drop-offs --}}
                                <div x-show="isPerItemMode()" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4" style="display:none">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wide text-slate-700">Package Destination</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">Recipient and location saved here apply only to this package.</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Name</label>
                                            <input type="text"
                                                   x-model="items[receiveModal.itemIndex].delivery_recipient_name"
                                                   placeholder="Who receives it?"
                                                   class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Recipient Phone</label>
                                            <input type="text"
                                                   x-model="items[receiveModal.itemIndex].delivery_recipient_phone"
                                                   placeholder="0241234567"
                                                   class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10">
                                        </div>
                                    </div>
                                    <div class="relative" @@click.outside="closeReceivingTownSearch(items[receiveModal.itemIndex])">
                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Town / Area</label>
                                        <div class="relative">
                                            <input type="text"
                                                   :value="items[receiveModal.itemIndex]._town_query"
                                                   @@input="updateReceivingTownQuery(items[receiveModal.itemIndex], $event.target.value)"
                                                   placeholder="Search saved towns or keep free text"
                                                   class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10">
                                            <div x-show="items[receiveModal.itemIndex]._town_loading" class="absolute inset-y-0 right-10 flex items-center text-slate-400" style="display:none">
                                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            </div>
                                            <button type="button" x-show="items[receiveModal.itemIndex]._town_query" @@click.prevent="clearReceivingTown(items[receiveModal.itemIndex])"
                                                    class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition-colors hover:text-slate-600" style="display:none">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                            <div x-show="items[receiveModal.itemIndex]._town_open" x-transition
                                                 class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl" style="display:none">
                                                <template x-for="town in items[receiveModal.itemIndex]._town_results" :key="`${town.id}-${town.region_id}`">
                                                    <button type="button" @@click.prevent="selectReceivingTownOption(items[receiveModal.itemIndex], town)"
                                                            class="w-full border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-slate-50">
                                                        <p class="text-sm font-bold text-slate-800" x-text="town.name"></p>
                                                        <p class="text-xs text-slate-500" x-text="town.context"></p>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <p x-show="items[receiveModal.itemIndex]._town_linked && items[receiveModal.itemIndex]._town_context" class="mt-1 text-[10px] font-medium text-emerald-600" x-text="'Linked to ' + items[receiveModal.itemIndex]._town_context" style="display:none"></p>
                                        <p x-show="items[receiveModal.itemIndex].delivery_town && !items[receiveModal.itemIndex]._town_linked" class="mt-1 text-[10px] text-amber-600" style="display:none">Free-text town. Region and district stay empty until you select a saved town.</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Landmark</label>
                                        <input type="text"
                                               x-model="items[receiveModal.itemIndex].delivery_landmark"
                                               placeholder="Near..."
                                               class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Delivery Instructions</label>
                                        <textarea rows="2"
                                                  x-model="items[receiveModal.itemIndex].delivery_instructions"
                                                  placeholder="e.g. Call before delivery"
                                                  class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10"></textarea>
                                    </div>
                                </div>

                                {{-- Delivery method tag: direct vs bus courier --}}
                                <div class="rounded-2xl border border-orange-100 bg-orange-50/60 p-4">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox"
                                               :checked="items[receiveModal.itemIndex].delivery_method === 'bus_handoff'"
                                               @@change="items[receiveModal.itemIndex].delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'"
                                               class="mt-0.5 h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        <div>
                                            <span class="text-xs font-bold text-orange-700 uppercase tracking-wider">Send via Bus Courier</span>
                                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">Tag this package for bus-courier handoff. A bus-handoff driver will pick it up; they choose the station in the field.</p>
                                        </div>
                                    </label>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">Notes</label>
                                    <textarea
                                        rows="3"
                                        class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10"
                                        placeholder="Any notes about this item..."
                                        x-model="items[receiveModal.itemIndex].notes"
                                    ></textarea>
                                </div>

                                {{-- Upload Photos --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">Add Photos</label>
                                    <label class="flex h-24 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 transition-colors hover:border-orange-300 hover:bg-orange-50/40">
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
                                                    <template x-for="(photo, pi) in (items[receiveModal.itemIndex]?.vendor_photos || [])" :key="'v-' + (photo.id || pi)">
                                                        <a :href="vendorPhotoUrl(photo)" target="_blank" rel="noopener">
                                                            <img :src="vendorPhotoUrl(photo)" alt="Vendor photo" class="h-16 w-16 object-cover rounded-xl border border-slate-200 hover:opacity-80 transition">
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
                            <div class="shrink-0 rounded-b-3xl border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-3">
                                <button type="button" @@click="closeReceiveModal()" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @@click="saveItem(receiveModal.itemId)"
                                    :disabled="saving"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-orange-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50"
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
                    </div>
                </template>

                <template x-teleport="body">
                    <div x-show="printLabelModal.open" x-transition.opacity class="fixed inset-0 z-[190] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4" style="display:none">
                        <div @@click.stop class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-md flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                            <div class="shrink-0 border-b border-slate-100 bg-white p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-600 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5V6A2.25 2.25 0 019 3.75h6A2.25 2.25 0 0117.25 6v1.5m-10.5 0h10.5m-10.5 0A2.25 2.25 0 004.5 9.75v3A2.25 2.25 0 006.75 15h.75m9.75-7.5A2.25 2.25 0 0119.5 9.75v3A2.25 2.25 0 0117.25 15h-.75m-9 0v5.25h9V15m-9 0h9"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-lg font-extrabold text-slate-950">Print Labels</h3>
                                            <p class="mt-1 text-sm leading-relaxed text-slate-500">Choose how many package labels to print.</p>
                                            <p class="mt-2 truncate font-mono text-xs font-black text-slate-500" x-text="printLabelModal.trackingCode"></p>
                                        </div>
                                    </div>
                                    <button type="button" @@click="closePrintLabelModal()" :disabled="printLabelModal.printing" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @@click="setPrintLabelCount(1)" class="rounded-2xl border px-4 py-4 text-left transition" :class="Number(printLabelModal.labelCount) === 1 ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                                        <span class="block text-sm font-black">1 label</span>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500">Single package</span>
                                    </button>
                                    <button type="button" x-show="Number(printLabelModal.receivedQuantity || 0) > 1" @@click="setPrintLabelCount(printLabelModal.receivedQuantity)" class="rounded-2xl border px-4 py-4 text-left transition" :class="Number(printLabelModal.labelCount) === Number(printLabelModal.receivedQuantity || 1) ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'" style="display:none">
                                        <span class="block text-sm font-black"><span x-text="printLabelModal.receivedQuantity"></span> labels</span>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500">One per unit</span>
                                    </button>
                                </div>
                                <div class="rounded-2xl border border-orange-100 bg-orange-50/50 p-4">
                                    <label class="mb-3 block text-xs font-black uppercase tracking-wide text-slate-600">Labels to print</label>
                                    <div class="flex items-center justify-center gap-3">
                                        <button type="button" @@click="setPrintLabelCount(Number(printLabelModal.labelCount || 1) - 1)" :disabled="printLabelModal.printing || Number(printLabelModal.labelCount || 1) <= 1" class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">-</button>
                                        <input type="number" min="1" max="500" x-model.number="printLabelModal.labelCount" @@input="setPrintLabelCount(printLabelModal.labelCount)" :disabled="printLabelModal.printing" class="h-12 w-28 rounded-2xl border-2 border-slate-200 bg-white text-center text-lg font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:cursor-not-allowed disabled:bg-slate-50">
                                        <button type="button" @@click="setPrintLabelCount(Number(printLabelModal.labelCount || 1) + 1)" :disabled="printLabelModal.printing || Number(printLabelModal.labelCount || 1) >= 500" class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                                    </div>
                                    <p class="mt-3 text-center text-xs font-semibold text-slate-400">Printed codes will use the package label format, like <span class="font-mono">TR...-001</span>.</p>
                                </div>
                            </div>
                            <div class="shrink-0 rounded-b-3xl border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @@click="closePrintLabelModal()" :disabled="printLabelModal.printing" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">Cancel</button>
                                    <button type="button" @@click="printLabelFromModal()" :disabled="printLabelModal.printing || !Number(printLabelModal.labelCount || 0)" class="inline-flex min-w-36 items-center justify-center gap-2 rounded-2xl bg-orange-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        <svg x-show="printLabelModal.printing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span x-text="printLabelModal.printing ? 'Printing...' : 'Print Labels'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ═══════════════════════════════════════ --}}
                {{-- FINALIZE RECEIPT MODAL                  --}}
                {{-- ═══════════════════════════════════════ --}}
                <div x-show="showFinalizeModal" x-cloak class="fixed inset-0 z-[120] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4">
                    <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
                        <div class="shrink-0 border-b border-slate-100 bg-white p-5">
                            <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-600 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-extrabold text-slate-950">Finalize Receipt</h4>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-500">Lock the receiving record after the warehouse confirms the package counts.</p>
                                </div>
                            </div>
                            <button type="button" @@click="showFinalizeModal = false" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                            </div>
                        </div>
                        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-sm font-semibold text-slate-600">Finalization locks all receiving edits for this pickup. Review the quantities before confirming.</p>
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Packages</p>
                                        <p class="mt-1 text-sm font-black text-slate-950"><span x-text="receivingReceivedPackageCount()">0</span>/<span x-text="receivingPackageCount()">0</span></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Pending</p>
                                        <p class="mt-1 text-sm font-black text-amber-700" x-text="receivingPendingUnits()">0</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Issues</p>
                                        <p class="mt-1 text-sm font-black text-slate-950" x-text="discrepancyCount()">0</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Notes <span class="text-slate-400 font-normal normal-case">(optional)</span></label>
                                <textarea rows="3" class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/10" placeholder="Any final notes..." x-model="finalizeNotes"></textarea>
                            </div>
                            <template x-if="hasDiscrepancies()">
                                <div class="space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <p class="text-xs font-bold text-amber-700">Discrepancy Detected — Approval Required</p>
                                    </div>
                                    <textarea rows="3" class="w-full resize-none rounded-2xl border border-amber-300 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100" placeholder="Approval reason..." x-model="approvalReason"></textarea>
                                </div>
                            </template>
                        </div>
                        <div class="shrink-0 rounded-b-3xl border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                            <div class="flex items-center justify-end gap-3">
                            <button type="button" @@click="showFinalizeModal = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">Cancel</button>
                            <button type="button" @@click="finalizeReceipt()" class="inline-flex items-center gap-2 rounded-2xl bg-orange-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="saving">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Confirm Finalize
                            </button>
                            </div>
                        </div>
                    </div>
                </div>

</div>
@endsection
