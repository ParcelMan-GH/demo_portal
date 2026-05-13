@extends($layoutName ?? 'admin.layouts.app')

@section('title', $batch->batch_number)
@section('breadcrumb-parent', 'Sort Batches')
@section('breadcrumb-current', $batch->batch_number)

@section('content')
@php
    $sortBatchShowConfig = $sortBatchShowConfig ?? [
        'indexUrl' => route('admin.sort-batches.index'),
        'itemsDataUrl' => route('admin.sort-batches.items-data', $batch->id),
        'eligibleItemsUrl' => route('admin.sort-batches.eligible-items', $batch->id),
        'addItemsUrl' => route('admin.sort-batches.add-items', $batch->id),
        'removeItemUrlTemplate' => route('admin.sort-batches.remove-item', ['batch' => $batch->id, 'shipmentItem' => '__ITEM__']),
        'sealUrl' => route('admin.sort-batches.seal', $batch->id),
        'reopenUrl' => route('admin.sort-batches.reopen', $batch->id),
        'deleteBatchUrl' => route('admin.sort-batches.destroy', $batch->id),
        'createManifestUrl' => route('admin.sort-batches.create-transport-manifest', $batch->id),
        'createRunUrl' => route('admin.sort-batches.create-delivery-run', $batch->id),
        'shipmentShowUrlTemplate' => route('admin.shipments.show', '__ID__'),
        'packageShowUrlTemplate' => route('warehouse.packages.show', '__ID__'),
        'manifestShowUrlTemplate' => route('admin.transport-manifests.show', '__ID__'),
        'deliveryRunShowUrlTemplate' => route('admin.delivery-runs.show', '__ID__'),
    ];

    $reopenLockReason = null;

    if ($batch->transportManifest?->status === \App\Models\TransportManifest::STATUS_RECEIVED) {
        $reopenLockReason = 'Transport manifest completed';
    } elseif ($batch->deliveryRun?->status === \App\Models\DeliveryRun::STATUS_COMPLETED) {
        $reopenLockReason = 'Delivery run completed';
    }

    $batchListModeUrl = url()->current() . '#batch-items';
    $batchAddModeUrl = url()->current() . '?mode=add#warehouse-packages';
    $initialItemsMode = request('mode') === 'add' && $batch->status === \App\Models\SortBatch::STATUS_OPEN ? 'add' : 'list';
    $initialEligibleItems = collect($initialEligibleItems ?? []);
    $initialEligibleMeta = $initialEligibleMeta ?? [
        'total' => $initialEligibleItems->count(),
        'per_page' => 25,
        'current_page' => 1,
        'last_page' => 1,
        'from' => $initialEligibleItems->isNotEmpty() ? 1 : 0,
        'to' => $initialEligibleItems->count(),
    ];
    $isWarehousePortal = ($layoutName ?? null) === 'warehouse.layouts.app' || request()->routeIs('warehouse.*');
@endphp

<div class="space-y-6" x-data="sortBatchShow()" x-init="init()">

    <!-- Hero Section -->
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="relative p-5 sm:p-6">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_58%)]"></div>

            <div class="relative flex items-center justify-between gap-3">
                <a href="{{ $sortBatchShowConfig['indexUrl'] }}" class="inline-flex min-w-0 items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-slate-200 transition hover:bg-white/15">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="truncate">Back to Sort Batches</span>
                </a>

                <div class="flex shrink-0 items-center gap-2">
                    @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                        <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-black text-emerald-300 ring-1 ring-emerald-400/25">Open</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-500/20 px-3 py-1 text-xs font-black text-slate-300 ring-1 ring-slate-400/25">Sealed</span>
                    @endif

                    @if($batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_TRANSFER)
                        <span class="inline-flex items-center rounded-full bg-orange-500/15 px-3 py-1 text-xs font-black text-orange-200 ring-1 ring-orange-400/30">Transfer</span>
                    @else
                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-500/15 px-3 py-1 text-xs font-black text-amber-200 ring-1 ring-amber-400/30">Local Delivery</span>
                    @endif
                </div>
            </div>

            <div class="relative mt-5 flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0 xl:max-w-[640px]">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-950/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Sort Batch Workspace</p>
                            <h1 class="mt-1 break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl xl:text-2xl 2xl:text-3xl">{{ $batch->batch_number }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                @if($isWarehousePortal)
                                    @if($batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_TRANSFER)
                                        <span>Transfer to {{ $batch->destinationWarehouse?->name ?? 'destination warehouse' }}</span>
                                    @else
                                        <span>Local delivery batch</span>
                                    @endif
                                @else
                                    <span>{{ $batch->originWarehouse?->name ?? '—' }}</span>
                                    @if($batch->destinationWarehouse)
                                        <span class="text-slate-600">/</span>
                                        <span>{{ $batch->destinationWarehouse->name }}</span>
                                    @endif
                                @endif
                                <span class="text-slate-600">/</span>
                                <span>Created by {{ $batch->createdBy?->name ?? '—' }}</span>
                                <span class="text-slate-600">/</span>
                                <span>{{ $batch->created_at->format('d M Y, H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    @if($batch->status === \App\Models\SortBatch::STATUS_SEALED && $reopenLockReason === null)
                    <button type="button" @@click="reopenBatch()" :disabled="actionLoading"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl border border-amber-300/30 bg-amber-500/15 px-4 py-3 text-sm font-black text-amber-100 transition hover:bg-amber-500/25 disabled:opacity-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reopen
                    </button>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 xl:w-[430px] xl:shrink-0 2xl:w-[480px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 xl:p-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-lg font-black leading-tight text-white"><span x-text="batchItemsCount">{{ $batch->active_items_count }}</span> items</p>
                                <p class="mt-1 text-xs font-bold text-slate-400">{{ (int) ($recipientPaymentSummary['pending'] ?? 0) }} pending</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 xl:p-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-7 4h8m4-14v16l-2-1-2 1-2-1-2 1-2-1-2 1V4l2 1 2-1 2 1 2-1 2 1 2-1z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-base font-black leading-tight text-white">GHS {{ number_format((float) ($recipientPaymentSummary['paid_total'] ?? 0), 2) }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-400">{{ (int) ($recipientPaymentSummary['paid'] ?? 0) }} paid</p>
                            </div>
                        </div>
                    </div>

                @if($batch->transportManifest)
                <a href="{{ str_replace('__ID__', $batch->transportManifest->id, $sortBatchShowConfig['manifestShowUrlTemplate']) }}"
                       class="rounded-2xl border border-white/10 bg-white/10 p-3 transition hover:border-orange-300/40 hover:bg-white/15 sm:p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-white">{{ $batch->transportManifest->manifest_number }}</p>
                            <p class="text-xs font-bold text-slate-400">Transport manifest</p>
                        </div>
                    </div>
                </a>
                @endif

                @if($batch->deliveryRun)
                <a href="{{ str_replace('__ID__', $batch->deliveryRun->id, $sortBatchShowConfig['deliveryRunShowUrlTemplate']) }}"
                       class="rounded-2xl border border-white/10 bg-white/10 p-3 transition hover:border-orange-300/40 hover:bg-white/15 sm:p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-white">{{ $batch->deliveryRun->run_number }}</p>
                            <p class="text-xs font-bold text-slate-400">Delivery run</p>
                        </div>
                    </div>
                </a>
                @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Action Toast -->
    <div x-show="actionMessage" x-cloak x-transition
         class="fixed right-4 top-4 z-[200] flex w-[min(440px,calc(100vw-2rem))] items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-bold shadow-2xl sm:right-6 sm:top-6"
         :class="actionSuccess ? 'border-emerald-200 bg-emerald-50 text-emerald-800 shadow-emerald-900/10' : 'border-rose-200 bg-rose-50 text-rose-800 shadow-rose-900/10'"
         role="alert">
        <svg x-show="actionSuccess" class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <svg x-show="!actionSuccess" class="h-4 w-4 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="actionMessage"></span>
        <button type="button" @@click="actionMessage = ''" class="ml-auto text-current opacity-50 hover:opacity-100">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Batch Items Workspace -->
    <div id="batch-items" class="space-y-5">
                    <div id="batch-items-card" x-ref="itemsListPanel" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 pb-6 pt-4 sm:px-5">
                            <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-950">Batch Items</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">
                                        <span x-text="itemsMeta.total"></span> <span x-text="itemsMeta.total === 1 ? 'item' : 'items'"></span> currently selected
                                    </p>
                                    <p x-show="blockedItemsCount() > 0" x-cloak class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-rose-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span x-text="blockedItemsCount()"></span>
                                        <span x-text="blockedItemsCount() === 1 ? 'item needs attention' : 'items need attention'"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                <button type="button" onclick="document.getElementById('batch-items-card').style.display = 'none'; document.getElementById('warehouse-packages').style.display = 'block'; document.getElementById('warehouse-packages').scrollIntoView({ behavior: 'smooth', block: 'start' }); return false;"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Packages
                                </button>
                                <button type="button" @@click="sealBatch()" :disabled="actionLoading"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Seal Batch
                                </button>
                                @endif

                                @if($batch->status === \App\Models\SortBatch::STATUS_SEALED && $batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_LOCAL_DELIVERY && !$batch->deliveryRun)
                                <button type="button" @@click="createDeliveryRun()" :disabled="actionLoading"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-xs font-bold text-white transition-colors shadow-sm disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Create Delivery Run
                                </button>
                                @endif

                                @if($batch->status === \App\Models\SortBatch::STATUS_SEALED && $batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_TRANSFER && !$batch->transportManifest)
                                <button type="button" @@click="createTransportManifest()" :disabled="actionLoading"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-xs font-bold text-white transition-colors shadow-sm disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Create Manifest
                                </button>
                                @endif

                                @if($batch->transportManifest)
                                <a href="{{ str_replace('__ID__', $batch->transportManifest->id, $sortBatchShowConfig['manifestShowUrlTemplate']) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition-colors">
                                    View Manifest
                                    <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                                @endif

                                @if($batch->deliveryRun)
                                <a href="{{ str_replace('__ID__', $batch->deliveryRun->id, $sortBatchShowConfig['deliveryRunShowUrlTemplate']) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition-colors">
                                    View Delivery Run
                                    <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                                @endif

                                @if($deleteState['deletable'])
                                <button type="button" @@click="deleteBatch()" :disabled="actionLoading"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-rose-100 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 transition hover:border-rose-200 hover:bg-rose-100 disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z"/>
                                    </svg>
                                    Delete Batch
                                </button>
                                @endif
                            </div>
                            </div>
                            <div class="relative mt-1 w-full sm:max-w-md">
                                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" x-model="itemsSearch" @@input="onItemsSearch()" placeholder="Search selected packages"
                                       class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                        </div>
                        </div>

                        <div x-show="itemsLoading" class="flex items-center justify-center py-16">
                            <svg class="w-6 h-6 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <div x-show="!itemsLoading && itemsLoaded && items.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                            <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-sm font-medium" x-text="itemsSearch ? 'No selected packages match your search' : 'No packages in this batch'"></p>
                            <p class="text-xs text-slate-400 mt-1" x-show="!itemsSearch">Add packages from the warehouse package selector.</p>
                            @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                            <button type="button" x-show="!itemsSearch" onclick="document.getElementById('batch-items-card').style.display = 'none'; document.getElementById('warehouse-packages').style.display = 'block'; document.getElementById('warehouse-packages').scrollIntoView({ behavior: 'smooth', block: 'start' }); return false;"
                                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-sm transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Packages
                            </button>
                            @endif
                        </div>

                        <div x-show="!itemsLoading && items.length > 0" class="divide-y divide-slate-100 md:hidden">
                            <template x-for="item in items" :key="item.id">
                                <article class="p-4" :class="item.is_sortable ? 'bg-white' : 'bg-rose-50/70'">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <template x-if="item.shipment_id">
                                                <a :href="shipmentUrl(item.shipment_id)" class="block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4" x-text="item.shipment_number"></a>
                                            </template>
                                            <template x-if="item.warehouse_receipt_item_id">
                                                <a :href="packageUrl(item.warehouse_receipt_item_id)" class="mt-1 block truncate text-base font-black text-slate-950 hover:text-orange-700 hover:underline" x-text="item.description || 'No description'"></a>
                                            </template>
                                            <p x-show="!item.warehouse_receipt_item_id" class="mt-1 truncate text-base font-black text-slate-950" x-text="item.description || 'No description'"></p>
                                            <p class="mt-1 truncate font-mono text-xs font-bold text-slate-500" x-text="item.tracking_code || 'No tracking code'"></p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">
                                            Qty <span class="ml-1" x-text="item.quantity || '—'"></span>
                                        </span>
                                    </div>

                                    <div x-show="!item.is_sortable" x-cloak class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700" x-text="item.sort_block_reason || 'No longer eligible'"></div>

                                    <div class="mt-4 grid grid-cols-2 gap-3">
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Recipient</p>
                                            <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="item.delivery_recipient_name || 'No recipient'"></p>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                                            <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="item.delivery_recipient_phone || 'No phone'"></p>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Town</p>
                                            <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="item.delivery_town || 'No town'"></p>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Payment</p>
                                            <span class="mt-1 inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                                                  :class="paymentBadgeClass(item.recipient_payment?.status)"
                                                  x-text="item.recipient_payment?.label || 'Not queued'"></span>
                                        </div>
                                    </div>

                                    @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                    <div class="mt-4 flex justify-end">
                                        <button type="button" @@click="removeItem(item)"
                                                :disabled="actionLoading"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-rose-100 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 transition hover:border-rose-200 hover:bg-rose-100 disabled:opacity-40">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Remove
                                        </button>
                                    </div>
                                    @endif
                                </article>
                            </template>
                        </div>

                        <div x-show="!itemsLoading && items.length > 0" class="hidden overflow-x-auto md:block">
                            <table class="min-w-[1180px] w-full text-left text-xs">
                                <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="min-w-[160px] px-4 py-3">Shipment</th>
                                        <th class="min-w-[140px] px-3 py-3">Tracking</th>
                                        <th class="px-3 py-3">Description</th>
                                        <th class="px-3 py-3">Vendor</th>
                                        <th class="px-3 py-3">Recipient</th>
                                        <th class="px-3 py-3">Phone</th>
                                        <th class="px-3 py-3">Town</th>
                                        <th class="px-3 py-3">Payment</th>
                                        <th class="px-3 py-3 text-center">Qty</th>
                                        <th class="px-3 py-3">Added</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="item in items" :key="item.id">
                                        <tr class="transition-colors"
                                            :class="item.is_sortable ? 'hover:bg-orange-50/20' : 'bg-rose-50/80 ring-1 ring-inset ring-rose-200 hover:bg-rose-50'">
                                            <td class="px-4 py-4 align-middle">
                                                <template x-if="item.shipment_id">
                                                    <a :href="shipmentUrl(item.shipment_id)" class="whitespace-nowrap font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="item.shipment_number"></a>
                                                </template>
                                                <span x-show="!item.shipment_id" class="text-sm font-bold text-slate-400">No shipment #</span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span x-show="item.tracking_code" class="block max-w-[170px] truncate font-mono text-xs font-black text-slate-700" x-text="item.tracking_code"></span>
                                                <span x-show="!item.tracking_code" class="text-xs font-semibold text-slate-400">—</span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <template x-if="item.warehouse_receipt_item_id">
                                                    <a :href="packageUrl(item.warehouse_receipt_item_id)" class="block max-w-[210px] truncate text-sm font-bold text-slate-800 hover:text-orange-700 hover:underline" x-text="item.description || '—'"></a>
                                                </template>
                                                <span x-show="!item.warehouse_receipt_item_id" class="block max-w-[210px] truncate text-sm font-bold text-slate-800" x-text="item.description || '—'"></span>
                                                <span x-show="!item.is_sortable" x-cloak
                                                      class="mt-1 inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black text-rose-700 ring-1 ring-rose-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span x-text="item.sort_block_reason || 'No longer eligible'"></span>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[150px] truncate text-sm font-semibold text-slate-600" x-text="item.vendor_name || 'No vendor'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[150px] truncate text-sm font-bold text-slate-800" x-text="item.delivery_recipient_name || 'No recipient'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="whitespace-nowrap text-sm font-semibold text-slate-600" x-text="item.delivery_recipient_phone || 'No phone'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[130px] truncate text-sm font-semibold text-slate-600" x-text="item.delivery_town || 'No town'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <div class="space-y-1">
                                                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                                                          :class="paymentBadgeClass(item.recipient_payment?.status)"
                                                          x-text="item.recipient_payment?.label || 'Not queued'"></span>
                                                    <p class="text-[10px] font-bold text-slate-400" x-show="item.recipient_payment?.amount !== null && item.recipient_payment?.amount !== undefined">
                                                        GHS <span x-text="formatMoney(item.recipient_payment?.amount)"></span>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-center align-middle">
                                                <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-700" x-text="item.quantity || '—'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[140px] truncate text-xs font-semibold text-slate-600" x-text="item.added_at || '—'"></span>
                                            </td>
                                            <td class="px-4 py-4 text-right align-middle">
                                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                                <button type="button" @@click="removeItem(item)"
                                                        :disabled="actionLoading"
                                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 transition hover:border-rose-200 hover:bg-rose-100 disabled:opacity-40">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Remove
                                                </button>
                                                @else
                                                <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="!itemsLoading && itemsMeta.last_page > 1" class="flex flex-col gap-3 border-t border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <p class="text-sm text-slate-500">
                                Showing <span class="font-semibold text-slate-700" x-text="itemsMeta.from"></span>
                                to <span class="font-semibold text-slate-700" x-text="itemsMeta.to"></span>
                                of <span class="font-semibold text-slate-700" x-text="itemsMeta.total"></span>
                            </p>
                            <div class="flex items-center gap-1">
                                <button @@click="goItemsPage(itemsMeta.current_page - 1)" :disabled="itemsMeta.current_page <= 1" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Prev</button>
                                <button @@click="goItemsPage(itemsMeta.current_page + 1)" :disabled="itemsMeta.current_page >= itemsMeta.last_page" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                            </div>
                        </div>
                    </div>
                    <div id="warehouse-packages" x-ref="addPackagesPanel" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" style="display: none;">
                        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h18M3 12h18M3 17h18"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-slate-950">Warehouse Packages</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">
                                            <span x-text="eligibleMeta.total"></span> eligible at {{ $batch->originWarehouse?->name ?? 'warehouse' }}
                                        </p>
                                    </div>
                                </div>
                                <button type="button" onclick="document.getElementById('warehouse-packages').style.display = 'none'; document.getElementById('batch-items-card').style.display = 'block'; document.getElementById('batch-items-card').scrollIntoView({ behavior: 'smooth', block: 'start' }); return false;"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                            </div>
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                                <div class="relative w-full lg:max-w-md">
                                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text" x-model="eligibleSearch" @@input="onEligibleSearch()" placeholder="Search tracking, shipment, recipient, phone, town"
                                           class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                </div>
                                <div class="inline-flex w-full rounded-xl border border-slate-200 bg-slate-50 p-1 sm:w-auto">
                                    <button type="button" @@click="setEligibleDeliveryFilter('')"
                                            class="flex-1 rounded-lg px-3 py-2 text-xs font-black transition-colors sm:flex-none"
                                            :class="eligibleDeliveryMethod === '' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                        All
                                    </button>
                                    <button type="button" @@click="setEligibleDeliveryFilter('bus_handoff')"
                                            class="flex-1 rounded-lg px-3 py-2 text-xs font-black transition-colors sm:flex-none"
                                            :class="eligibleDeliveryMethod === 'bus_handoff' ? 'bg-orange-600 text-white shadow-sm' : 'text-slate-500 hover:text-orange-700'">
                                        Bus Courier
                                    </button>
                                    <button type="button" @@click="setEligibleDeliveryFilter('direct')"
                                            class="flex-1 rounded-lg px-3 py-2 text-xs font-black transition-colors sm:flex-none"
                                            :class="eligibleDeliveryMethod === 'direct' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                        Direct
                                    </button>
                                </div>
                                <button type="button" @@click="addSelectedItems()"
                                        :disabled="selectedEligibleIds.length === 0 || eligibleLoading"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:opacity-60 disabled:shadow-none lg:ml-auto">
                                    Add Selected <span x-text="selectedEligibleIds.length ? '(' + selectedEligibleIds.length + ')' : ''"></span>
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[640px] overflow-auto">
                            <table class="w-full min-w-[1140px] text-left text-xs">
                                <thead class="sticky top-0 z-10 border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="w-12 px-4 py-3"></th>
                                        <th class="min-w-[160px] px-4 py-3">Shipment</th>
                                        <th class="min-w-[140px] px-4 py-3">Tracking</th>
                                        <th class="px-4 py-3">Description</th>
                                        <th class="px-4 py-3">Method</th>
                                        <th class="px-4 py-3">Vendor</th>
                                        <th class="px-4 py-3">Recipient</th>
                                        <th class="px-4 py-3">Phone</th>
                                        <th class="px-4 py-3">Town</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($initialEligibleItems as $item)
                                    <tr @@click="toggleEligible({{ (int) $item['warehouse_receipt_item_id'] }})"
                                        class="cursor-pointer transition-colors hover:bg-orange-50/20"
                                        :class="selectedEligibleIds.includes({{ (int) $item['warehouse_receipt_item_id'] }}) ? 'bg-orange-50/70' : ''">
                                        <td class="px-4 py-4 align-middle">
                                            <button type="button" @@click.stop="toggleEligible({{ (int) $item['warehouse_receipt_item_id'] }})"
                                                    class="flex h-6 w-6 items-center justify-center rounded-md border-2 transition-all"
                                                    :class="selectedEligibleIds.includes({{ (int) $item['warehouse_receipt_item_id'] }}) ? 'bg-orange-600 border-orange-600' : 'border-slate-300 hover:border-orange-400'">
                                                <svg x-show="selectedEligibleIds.includes({{ (int) $item['warehouse_receipt_item_id'] }})" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="whitespace-nowrap font-mono text-sm font-black text-orange-700">{{ $item['shipment_number'] ?? 'No shipment #' }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="block max-w-[170px] truncate font-mono text-xs font-black text-slate-700">{{ $item['tracking_code'] ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <a href="{{ route('warehouse.packages.show', $item['warehouse_receipt_item_id']) }}"
                                               @@click.stop
                                               class="block max-w-[210px] truncate text-sm font-bold text-slate-800 hover:text-orange-700 hover:underline">
                                                {{ $item['item_description'] ?? '—' }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black {{ ($item['delivery_method'] ?? null) === \App\Models\ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF ? 'bg-orange-100 text-orange-700 ring-1 ring-orange-200' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $item['delivery_method_label'] ?? 'Direct Delivery' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="block max-w-[150px] truncate text-sm font-semibold text-slate-600">{{ $item['vendor_name'] ?? 'No vendor' }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="block max-w-[150px] truncate text-sm font-bold text-slate-800">{{ data_get($item, 'destination.recipient_name') ?? 'No recipient' }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="whitespace-nowrap text-sm font-semibold text-slate-600">{{ data_get($item, 'destination.recipient_phone') ?? 'No phone' }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-middle">
                                            <span class="block max-w-[130px] truncate text-sm font-semibold text-slate-600">{{ data_get($item, 'destination.town') ?? 'No town' }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center align-middle">
                                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-700">{{ $item['received_quantity'] ?? 0 }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-right align-middle">
                                            <button type="button" @@click.stop="toggleEligible({{ (int) $item['warehouse_receipt_item_id'] }})"
                                                    class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-black transition-colors"
                                                    :class="selectedEligibleIds.includes({{ (int) $item['warehouse_receipt_item_id'] }}) ? 'border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100' : 'bg-orange-600 text-white shadow-sm shadow-orange-600/15 hover:bg-orange-700'">
                                                <span x-text="selectedEligibleIds.includes({{ (int) $item['warehouse_receipt_item_id'] }}) ? 'Selected' : 'Add'">Add</span>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                            No eligible warehouse packages.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div x-show="eligibleLoading" class="flex items-center justify-center py-16" @if($initialItemsMode === 'add' && $initialEligibleItems->isNotEmpty()) style="display:none" @endif>
                            <svg class="w-6 h-6 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </div>

                        <div x-show="!eligibleLoading && eligibleError" class="px-4 py-8">
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm font-bold text-rose-700" x-text="eligibleError"></div>
                        </div>

                        <div x-show="!eligibleLoading && !eligibleError && eligibleItems.length === 0" class="px-4 py-16 text-center">
                            <p class="text-sm font-medium text-slate-500" x-text="eligibleSearch ? 'No warehouse packages match your search' : 'No eligible warehouse packages'"></p>
                            <p class="text-xs text-slate-400 mt-1">Packages already in an active sort batch are hidden here.</p>
                        </div>

                        <div x-show="false" class="divide-y divide-slate-100 md:hidden">
                            <template x-for="item in eligibleItems" :key="item.warehouse_receipt_item_id">
                                <article class="p-4" :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-orange-50/50' : 'bg-white'">
                                    <div class="flex items-start gap-3">
                                        @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                        <button type="button" @@click="toggleEligible(item.warehouse_receipt_item_id)"
                                                class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-md border-2 transition-all"
                                                :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-orange-600 border-orange-600' : 'border-slate-300 hover:border-orange-400'">
                                            <svg x-show="selectedEligibleIds.includes(item.warehouse_receipt_item_id)" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="truncate font-mono text-sm font-black text-slate-950" x-text="item.shipment_number || 'No shipment #'"></p>
                                                    <p class="mt-1 truncate text-base font-black text-slate-950" x-text="item.item_description || 'No description'"></p>
                                                    <p class="mt-1 truncate font-mono text-xs font-bold text-slate-500" x-text="item.tracking_code || 'No tracking code'"></p>
                                                </div>
                                                <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">
                                                    Qty <span class="ml-1" x-text="item.received_quantity"></span>
                                                </span>
                                            </div>
                                            <div class="mt-3 grid grid-cols-2 gap-3">
                                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Recipient</p>
                                                    <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="item.destination?.recipient_name || 'No recipient'"></p>
                                                </div>
                                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Phone</p>
                                                    <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="item.destination?.recipient_phone || 'No phone'"></p>
                                                </div>
                                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Town</p>
                                                    <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="item.destination?.town || 'No town'"></p>
                                                </div>
                                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Method</p>
                                                    <span class="mt-1 inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                                                          :class="item.delivery_method === 'bus_handoff' ? 'bg-orange-100 text-orange-700 ring-1 ring-orange-200' : 'bg-slate-100 text-slate-600'"
                                                          x-text="item.delivery_method_label || 'Direct Delivery'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div x-show="false" class="hidden max-h-[640px] overflow-auto md:block">
                            <table class="w-full min-w-[1140px] text-left text-xs">
                                <thead class="sticky top-0 z-10 border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="w-12 px-4 py-3"></th>
                                        <th class="min-w-[160px] px-4 py-3">Shipment</th>
                                        <th class="min-w-[140px] px-4 py-3">Tracking</th>
                                        <th class="px-4 py-3">Description</th>
                                        <th class="px-4 py-3">Method</th>
                                        <th class="px-4 py-3">Vendor</th>
                                        <th class="px-4 py-3">Recipient</th>
                                        <th class="px-4 py-3">Phone</th>
                                        <th class="px-4 py-3">Town</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="item in eligibleItems" :key="item.warehouse_receipt_item_id">
                                        <tr class="transition-colors hover:bg-orange-50/20"
                                            :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-orange-50/70' : ''">
                                            <td class="px-4 py-4 align-middle">
                                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                                <button type="button" @@click="toggleEligible(item.warehouse_receipt_item_id)"
                                                        class="flex h-6 w-6 items-center justify-center rounded-md border-2 transition-all"
                                                        :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-orange-600 border-orange-600' : 'border-slate-300 hover:border-orange-400'">
                                                    <svg x-show="selectedEligibleIds.includes(item.warehouse_receipt_item_id)" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="whitespace-nowrap font-mono text-sm font-black text-orange-700" x-text="item.shipment_number || 'No shipment #'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span x-show="item.tracking_code" class="block max-w-[170px] truncate font-mono text-xs font-black text-slate-700" x-text="item.tracking_code"></span>
                                                <span x-show="!item.tracking_code" class="text-xs font-semibold text-slate-400">—</span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[210px] truncate text-sm font-bold text-slate-800" x-text="item.item_description || '—'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                                                      :class="item.delivery_method === 'bus_handoff' ? 'bg-orange-100 text-orange-700 ring-1 ring-orange-200' : 'bg-slate-100 text-slate-600'"
                                                      x-text="item.delivery_method_label || 'Direct Delivery'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[150px] truncate text-sm font-semibold text-slate-600" x-text="item.vendor_name || 'No vendor'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[150px] truncate text-sm font-bold text-slate-800" x-text="item.destination?.recipient_name || 'No recipient'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="whitespace-nowrap text-sm font-semibold text-slate-600" x-text="item.destination?.recipient_phone || 'No phone'"></span>
                                            </td>
                                            <td class="px-4 py-4 align-middle">
                                                <span class="block max-w-[130px] truncate text-sm font-semibold text-slate-600" x-text="item.destination?.town || 'No town'"></span>
                                            </td>
                                            <td class="px-4 py-4 text-center align-middle">
                                                <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-700" x-text="item.received_quantity"></span>
                                            </td>
                                            <td class="px-4 py-4 text-right align-middle">
                                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                                <button type="button" @@click="addOneItem(item)"
                                                        :disabled="eligibleLoading"
                                                        class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-black transition-colors disabled:opacity-40"
                                                        :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100' : 'bg-orange-600 text-white shadow-sm shadow-orange-600/15 hover:bg-orange-700'">
                                                    <span x-text="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'Selected' : 'Add'"></span>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="!eligibleLoading && !eligibleError && eligibleItems.length > 0" class="border-t border-slate-100 px-4 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="text-sm text-slate-600">
                                    Showing <span x-text="eligibleMeta.from || 0"></span> to <span x-text="eligibleMeta.to || 0"></span> of <span x-text="eligibleMeta.total || 0"></span> results
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-600">Rows</span>
                                        <div x-data="{ open: false }" class="relative">
                                            <button type="button" @@click="open = !open"
                                                    class="inline-flex h-10 min-w-[72px] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                                                <span x-text="eligibleMeta.per_page"></span>
                                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" @@click.away="open = false" x-transition
                                                 class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display:none">
                                                <button type="button" @@click="setEligiblePerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="eligibleMeta.per_page == 10 ? 'bg-slate-100/70' : ''">10</button>
                                                <button type="button" @@click="setEligiblePerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="eligibleMeta.per_page == 25 ? 'bg-slate-100/70' : ''">25</button>
                                                <button type="button" @@click="setEligiblePerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="eligibleMeta.per_page == 50 ? 'bg-slate-100/70' : ''">50</button>
                                                <button type="button" @@click="setEligiblePerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="eligibleMeta.per_page == 100 ? 'bg-slate-100/70' : ''">100</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm font-black text-slate-700">Page <span x-text="eligibleMeta.current_page || 1"></span> / <span x-text="eligibleMeta.last_page || 1"></span></div>
                                    <div class="flex space-x-1">
                                        <button @@click="firstEligiblePage()" :disabled="eligibleMeta.current_page <= 1" :class="eligibleMeta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50'" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                        </button>
                                        <button @@click="previousEligiblePage()" :disabled="eligibleMeta.current_page <= 1" :class="eligibleMeta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50'" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <button @@click="nextEligiblePage()" :disabled="eligibleMeta.current_page >= eligibleMeta.last_page" :class="eligibleMeta.current_page >= eligibleMeta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50'" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <button @@click="lastEligiblePage()" :disabled="eligibleMeta.current_page >= eligibleMeta.last_page" :class="eligibleMeta.current_page >= eligibleMeta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50'" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
    </div>

    <div x-show="confirmModal.open" x-cloak x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm"
         @@keydown.escape.window="closeConfirmModal()" style="display:none">
        <div @@click.stop
             class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
             x-transition.scale.origin.center>
            <div class="px-5 py-5">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-sm"
                          :class="confirmModal.iconClass">
                        <svg x-show="confirmModal.icon === 'remove'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <svg x-show="confirmModal.icon === 'delete'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z"/>
                        </svg>
                        <svg x-show="confirmModal.icon === 'seal'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <svg x-show="confirmModal.icon === 'run'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <svg x-show="confirmModal.icon === 'manifest'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <svg x-show="confirmModal.icon === 'reopen'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900" x-text="confirmModal.title"></h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500" x-text="confirmModal.message"></p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" @@click="closeConfirmModal()" :disabled="actionLoading"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                    Cancel
                </button>
                <button type="button" @@click="confirmAction()" :disabled="actionLoading"
                        class="inline-flex min-w-32 items-center justify-center gap-2 rounded-xl px-5 py-2 text-sm font-bold transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                        :class="confirmModal.confirmClass">
                    <svg x-show="actionLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="actionLoading ? 'Please wait...' : confirmModal.confirmText"></span>
                </button>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function sortBatchShow() {
    return {
        // Action state
        actionLoading: false,
        actionMessage: '',
        actionSuccess: true,
        batchItemsCount: {{ (int) $batch->active_items_count }},
        reopenLocked: @json($reopenLockReason !== null),
        reopenLockReason: @json($reopenLockReason),
        confirmModal: {
            open: false,
            action: null,
            payload: null,
            title: '',
            message: '',
            confirmText: 'Continue',
            icon: 'seal',
            iconClass: 'bg-orange-100 text-orange-600',
            confirmClass: 'bg-orange-600 text-white hover:bg-orange-700',
        },

        // Items tab state
        itemsMode: @json($initialItemsMode),
        items: [],
        itemsMeta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        itemsSearch: '',
        itemsLoading: false,
        itemsLoaded: false,
        _searchTimeout: null,
        _eligibleSearchTimeout: null,
        _indexUrl: @json($sortBatchShowConfig['indexUrl']),
        _itemsDataUrl: @json($sortBatchShowConfig['itemsDataUrl']),
        _eligibleItemsUrl: @json($sortBatchShowConfig['eligibleItemsUrl']),
        _addItemsUrl: @json($sortBatchShowConfig['addItemsUrl']),
        _removeItemUrl: @json($sortBatchShowConfig['removeItemUrlTemplate']),
        _sealUrl: @json($sortBatchShowConfig['sealUrl']),
        _reopenUrl: @json($sortBatchShowConfig['reopenUrl']),
        _deleteBatchUrl: @json($sortBatchShowConfig['deleteBatchUrl']),
        _createManifestUrl: @json($sortBatchShowConfig['createManifestUrl']),
        _createRunUrl: @json($sortBatchShowConfig['createRunUrl']),
        _shipmentShowUrl: @json($sortBatchShowConfig['shipmentShowUrlTemplate']),
        _packageShowUrl: @json($sortBatchShowConfig['packageShowUrlTemplate']),
        _manifestShowUrl: @json($sortBatchShowConfig['manifestShowUrlTemplate']),
        _deliveryRunShowUrl: @json($sortBatchShowConfig['deliveryRunShowUrlTemplate']),
        _listModeUrl: @json($batchListModeUrl),
        _addModeUrl: @json($batchAddModeUrl),

        // Warehouse package state
        eligibleItems: @json($initialEligibleItems->values()),
        eligibleMeta: @json($initialEligibleMeta),
        eligibleSearch: '',
        eligibleDeliveryMethod: '',
        eligibleLoading: false,
        eligibleError: '',
        eligibleHydrated: @json($initialEligibleItems->isEmpty()),
        selectedEligibleIds: [],

        init() {
            this.fetchItems(1);
            if (this.itemsMode === 'add') {
                this.loadEligibleItems(1);
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.$refs.addPackagesPanel?.scrollIntoView({ behavior: 'auto', block: 'start' });
                    }, 40);
                });
            }
        },

        requestConfirm(action, {
            title,
            message,
            confirmText = 'Continue',
            payload = null,
            icon = 'seal',
            iconClass = 'bg-orange-100 text-orange-600',
            confirmClass = 'bg-orange-600 text-white hover:bg-orange-700',
        }) {
            this.confirmModal = {
                open: true,
                action,
                payload,
                title,
                message,
                confirmText,
                icon,
                iconClass,
                confirmClass,
            };
        },

        closeConfirmModal() {
            if (this.actionLoading) return;
            this.confirmModal.open = false;
        },

        confirmAction() {
            const action = this.confirmModal.action;
            const payload = this.confirmModal.payload;
            this.confirmModal.open = false;

            if (action === 'remove-item') return this.performRemoveItem(payload);
            if (action === 'seal-batch') return this.performSealBatch();
            if (action === 'reopen-batch') return this.performReopenBatch();
            if (action === 'delete-batch') return this.performDeleteBatch();
            if (action === 'create-transport-manifest') return this.performCreateTransportManifest();
            if (action === 'create-delivery-run') return this.performCreateDeliveryRun();
        },

        // ─── Items Tab ────────────────────────────────────────────────────────

        fetchItems(page) {
            this.itemsLoading = true;
            const params = new URLSearchParams({
                page: page || this.itemsMeta.current_page,
                per_page: this.itemsMeta.per_page,
            });
            if (this.itemsSearch) params.set('search', this.itemsSearch);

            return fetch(this._itemsDataUrl + '?' + params.toString())
                .then(r => r.json())
                .then(json => {
                    this.items = json.data;
                    this.itemsMeta = json.meta;
                    this.batchItemsCount = json.meta?.total ?? this.batchItemsCount;
                    this.itemsLoading = false;
                    this.itemsLoaded = true;
                })
                .catch(() => { this.itemsLoading = false; });
        },

        onItemsSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.fetchItems(1), 300);
        },

        goItemsPage(p) {
            if (p < 1 || p > this.itemsMeta.last_page) return;
            this.fetchItems(p);
        },

        shipmentUrl(id) {
            return this._shipmentShowUrl.replace('__ID__', id);
        },

        packageUrl(id) {
            return this._packageShowUrl.replace('__ID__', id);
        },

        blockedItemsCount() {
            return this.items.filter((item) => item.is_sortable === false).length;
        },

        // ─── Warehouse Packages ───────────────────────────────────────────────

        async openAddPackages() {
            this.itemsMode = 'add';
            this.showPackageSelector();
            this.$nextTick(() => {
                setTimeout(() => {
                    this.$refs.addPackagesPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 40);
                const input = this.$refs.addPackagesPanel?.querySelector('input[x-model="eligibleSearch"]');
                input?.focus({ preventScroll: true });
            });
            await this.loadEligibleItems(1);
        },

        closeAddPackages() {
            this.itemsMode = 'list';
            this.selectedEligibleIds = [];
            this.showBatchItems();
            this.$nextTick(() => {
                this.$refs.itemsListPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },

        showPackageSelector() {
            document.getElementById('batch-items-card')?.classList.add('hidden');
            document.getElementById('warehouse-packages')?.classList.remove('hidden');
        },

        showBatchItems() {
            document.getElementById('warehouse-packages')?.classList.add('hidden');
            document.getElementById('batch-items-card')?.classList.remove('hidden');
        },

        loadEligibleItems(page) {
            this.eligibleLoading = true;
            this.eligibleError = '';
            const params = new URLSearchParams({
                page: page || this.eligibleMeta.current_page,
                per_page: this.eligibleMeta.per_page,
            });
            if (this.eligibleSearch) params.set('search', this.eligibleSearch);
            if (this.eligibleDeliveryMethod) params.set('delivery_method', this.eligibleDeliveryMethod);
            return fetch(this._eligibleItemsUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            })
                .then(async r => {
                    const json = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        throw new Error(json.message || 'Could not load warehouse packages.');
                    }
                    return json;
                })
                .then(json => {
                    this.eligibleItems = json.data || [];
                    this.eligibleMeta = json.meta || this.eligibleMeta;
                    this.eligibleHydrated = true;
                    this.selectedEligibleIds = this.selectedEligibleIds.filter((id) =>
                        this.eligibleItems.some((item) => item.warehouse_receipt_item_id === id)
                    );
                    this.eligibleLoading = false;
                })
                .catch((error) => {
                    this.eligibleItems = [];
                    this.eligibleLoading = false;
                    this.eligibleHydrated = true;
                    this.eligibleError = error.message || 'Could not load warehouse packages.';
                });
        },

        onEligibleSearch() {
            clearTimeout(this._eligibleSearchTimeout);
            this._eligibleSearchTimeout = setTimeout(() => this.loadEligibleItems(1), 300);
        },

        setEligibleDeliveryFilter(method) {
            this.eligibleDeliveryMethod = method;
            this.selectedEligibleIds = [];
            this.loadEligibleItems(1);
        },

        goEligiblePage(p) {
            if (p < 1 || p > this.eligibleMeta.last_page) return;
            this.loadEligibleItems(p);
        },

        setEligiblePerPage(limit) {
            this.eligibleMeta.per_page = Number(limit) || 25;
            this.selectedEligibleIds = [];
            this.loadEligibleItems(1);
        },

        firstEligiblePage() {
            if (this.eligibleMeta.current_page > 1) this.loadEligibleItems(1);
        },

        previousEligiblePage() {
            if (this.eligibleMeta.current_page > 1) this.loadEligibleItems(this.eligibleMeta.current_page - 1);
        },

        nextEligiblePage() {
            if (this.eligibleMeta.current_page < this.eligibleMeta.last_page) this.loadEligibleItems(this.eligibleMeta.current_page + 1);
        },

        lastEligiblePage() {
            if (this.eligibleMeta.current_page < this.eligibleMeta.last_page) this.loadEligibleItems(this.eligibleMeta.last_page);
        },

        toggleEligible(id) {
            if (this.selectedEligibleIds.includes(id)) {
                this.selectedEligibleIds = this.selectedEligibleIds.filter((selectedId) => selectedId !== id);
                return;
            }

            this.selectedEligibleIds.push(id);
        },

        addOneItem(item) {
            this.toggleEligible(item.warehouse_receipt_item_id);
        },

        async addSelectedItems() {
            if (!this.selectedEligibleIds.length) return;
            this.eligibleLoading = true;
            try {
                const resp = await fetch(this._addItemsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ warehouse_receipt_item_ids: this.selectedEligibleIds }),
                });
                const json = await resp.json();
                if (json.success) {
                    this.selectedEligibleIds = [];
                    this.showAction(true, json.message || 'Items added successfully.');
                    window.location.href = this._listModeUrl;
                } else {
                    this.showAction(false, json.message || 'Failed to add items.');
                    this.eligibleLoading = false;
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
                this.eligibleLoading = false;
            }
        },

        async refreshSortingLists() {
            await Promise.all([
                this.fetchItems(1),
                this.loadEligibleItems(this.eligibleMeta.current_page),
            ]);
        },

        // ─── Remove Item ──────────────────────────────────────────────────────

        async removeItem(item) {
            this.requestConfirm('remove-item', {
                title: 'Remove Package',
                message: `Remove "${item.description || item.tracking_code || 'this item'}" from this batch?`,
                confirmText: 'Remove',
                payload: item,
                icon: 'remove',
                iconClass: 'bg-rose-100 text-rose-600',
                confirmClass: 'border border-rose-200 bg-white text-rose-700 hover:bg-rose-50',
            });
        },

        async performRemoveItem(item) {
            this.actionLoading = true;
            const removeUrl = this._removeItemUrl.replace('__ITEM__', item.shipment_item_id);
            try {
                const resp = await fetch(removeUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Item removed.' : 'Failed to remove item.'));
                if (json.success) {
                    await this.refreshSortingLists();
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        // ─── Seal / Reopen ────────────────────────────────────────────────────

        async sealBatch() {
            this.requestConfirm('seal-batch', {
                title: 'Seal Batch',
                message: 'Seal this sort batch? No more items can be added after sealing.',
                confirmText: 'Seal Batch',
                icon: 'seal',
                iconClass: 'bg-orange-100 text-orange-600',
                confirmClass: 'bg-orange-600 text-white hover:bg-orange-700',
            });
        },

        async performSealBatch() {
            this.actionLoading = true;
            try {
                const resp = await fetch(this._sealUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Batch sealed.' : 'Failed to seal batch.'));
                if (json.success) setTimeout(() => window.location.reload(), 800);
                if (!json.success) await this.fetchItems(this.itemsMeta.current_page);
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        async reopenBatch() {
            if (this.reopenLocked) {
                this.showAction(false, this.reopenLockReason || 'This batch cannot be reopened.');
                return;
            }

            this.requestConfirm('reopen-batch', {
                title: 'Reopen Batch',
                message: 'Reopen this sort batch? Items can be added or removed again.',
                confirmText: 'Reopen',
                icon: 'reopen',
                iconClass: 'bg-amber-100 text-amber-700',
                confirmClass: 'bg-amber-600 text-white hover:bg-amber-700',
            });
        },

        async performReopenBatch() {
            this.actionLoading = true;
            try {
                const resp = await fetch(this._reopenUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Batch reopened.' : 'Failed to reopen.'));
                if (json.success) setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        async deleteBatch() {
            this.requestConfirm('delete-batch', {
                title: 'Delete Batch',
                message: 'Delete this sort batch? Packages in this batch will be returned to warehouse inventory.',
                confirmText: 'Delete Batch',
                icon: 'delete',
                iconClass: 'bg-rose-100 text-rose-600',
                confirmClass: 'bg-rose-600 text-white hover:bg-rose-700',
            });
        },

        async performDeleteBatch() {
            this.actionLoading = true;
            try {
                const resp = await fetch(this._deleteBatchUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Batch deleted.' : 'Failed to delete batch.'));
                if (json.success) {
                    setTimeout(() => {
                        window.location.href = this._indexUrl;
                    }, 800);
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        // ─── Create Manifest / Delivery Run ─────────────────────────────────

        async createTransportManifest() {
            this.requestConfirm('create-transport-manifest', {
                title: 'Create Manifest',
                message: 'Create a transport manifest from this sealed transfer batch?',
                confirmText: 'Create Manifest',
                icon: 'manifest',
                iconClass: 'bg-orange-100 text-orange-600',
                confirmClass: 'bg-orange-600 text-white hover:bg-orange-700',
            });
        },

        async performCreateTransportManifest() {
            this.actionLoading = true;
            try {
                const resp = await fetch(this._createManifestUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, this.responseMessage(json, json.success ? 'Manifest created.' : 'Failed to create manifest.'));
                if (json.success) {
                    const manifestId = json.data?.manifest?.id;
                    if (manifestId) {
                        setTimeout(() => {
                            window.location.href = this._manifestShowUrl.replace('__ID__', manifestId);
                        }, 800);
                    } else {
                        setTimeout(() => window.location.reload(), 800);
                    }
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        async createDeliveryRun() {
            this.requestConfirm('create-delivery-run', {
                title: 'Create Delivery Run',
                message: 'Create a delivery run from this sealed batch? This cannot be undone.',
                confirmText: 'Create Run',
                icon: 'run',
                iconClass: 'bg-orange-100 text-orange-600',
                confirmClass: 'bg-orange-600 text-white hover:bg-orange-700',
            });
        },

        async performCreateDeliveryRun() {
            this.actionLoading = true;
            try {
                const resp = await fetch(this._createRunUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, this.responseMessage(json, json.success ? 'Delivery run created.' : 'Failed to create delivery run.'));
                if (json.success) {
                    // Navigate to the delivery run if we have an id
                    const runId = json.data?.run?.id;
                    if (runId) {
                        setTimeout(() => {
                            window.location.href = this._deliveryRunShowUrl.replace('__ID__', runId);
                        }, 800);
                    } else {
                        setTimeout(() => window.location.reload(), 800);
                    }
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        // ─── Helpers ──────────────────────────────────────────────────────────

        showAction(success, message) {
            this.actionSuccess = success;
            this.actionMessage = message;
            setTimeout(() => { this.actionMessage = ''; }, success ? 4000 : 7000);
        },

        responseMessage(json, fallback) {
            const rawBlockers = json?.data?.recipient_payment_blockers || json?.recipient_payment_blockers || [];
            const blockers = Array.isArray(rawBlockers) ? rawBlockers : (rawBlockers.items || []);
            if (!blockers.length) return json.message || fallback;

            const lines = blockers.slice(0, 5).map((item) => {
                const code = item.tracking_code || item.shipment_number || `Package #${item.shipment_item_id}`;
                const amount = item.amount ? `, GHS ${this.formatMoney(item.amount)}` : '';
                return `${code} (${item.recipient_name || 'No recipient'}${amount})`;
            });
            const extra = blockers.length > 5 ? ` and ${blockers.length - 5} more` : '';
            return `${json.message || fallback} Blocking packages: ${lines.join('; ')}${extra}.`;
        },

        paymentBadgeClass(status) {
            if (status === 'paid') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (status === 'waived' || status === 'overridden') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            if (status === 'not_queued') return 'bg-slate-50 text-slate-400 ring-1 ring-slate-100';
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200';
        },

        formatMoney(value) {
            return Number(value || 0).toFixed(2);
        },
    };
}

</script>
@endpush
