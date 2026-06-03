@extends($layoutName ?? 'warehouse.layouts.app')

@section('title', 'Incoming Manifest Details')
@section('page-title', 'Incoming Manifest Details')
@section('breadcrumb-parent', 'Incoming Manifests')
@section('breadcrumb-current', $manifest->manifest_number)

@php
    $storageService = app(\App\Services\StorageService::class);
    $photoPayload = function ($photo, string $source) use ($storageService) {
        $url = $storageService->getUrl($photo->path);

        return [
            'id' => $photo->id,
            'url' => $url,
            'name' => $photo->original_name ?: $source . ' photo',
            'source' => $source,
        ];
    };

    $containerLineMeta = collect();

    foreach ($manifest->containers->sortBy('sequence_number') as $container) {
        foreach ($container->items as $containerItem) {
            if (!$containerItem->transport_manifest_item_id) {
                continue;
            }

            $containerLineMeta->put($containerItem->transport_manifest_item_id, [
                'id' => $container->id,
                'code' => $container->container_code,
                'type' => str($container->container_type)->replace('_', ' ')->title()->toString(),
                'sequence' => (int) $container->sequence_number,
                'status' => $container->status,
                'item_status' => $containerItem->status,
            ]);
        }
    }

    $items = $manifest->items
        ->sortBy(fn ($line) => sprintf('%06d-%010d', $containerLineMeta->get($line->id)['sequence'] ?? 999999, $line->id))
        ->values();
    $totalExpected = $items->sum('expected_quantity');
    $totalLoaded = $items->sum('loaded_quantity');
    $totalReceived = $items->sum('received_quantity');

    $statusClass = match ($manifest->status) {
        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'assigned' => 'bg-orange-50 text-orange-800 border-orange-200',
        'loading' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'in_transit' => 'bg-violet-50 text-violet-700 border-violet-200',
        'arrived' => 'bg-amber-50 text-amber-700 border-amber-200',
        'received' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $statusDotClass = match ($manifest->status) {
        'draft' => 'bg-slate-400',
        'assigned' => 'bg-orange-600',
        'loading' => 'bg-indigo-500',
        'in_transit' => 'bg-violet-500',
        'arrived' => 'bg-amber-500',
        'received' => 'bg-emerald-500',
        'cancelled' => 'bg-rose-500',
        default => 'bg-slate-400',
    };

    $timeline = [
        ['label' => 'Created', 'value' => $manifest->created_at, 'dot' => 'bg-slate-500'],
        ['label' => 'Driver Assigned', 'value' => $manifest->assigned_at, 'dot' => 'bg-orange-600'],
        ['label' => 'Dispatched', 'value' => $manifest->dispatched_at, 'dot' => 'bg-violet-500'],
        ['label' => 'Arrived', 'value' => $manifest->arrived_at, 'dot' => 'bg-amber-500'],
        ['label' => 'Received', 'value' => $manifest->received_at, 'dot' => 'bg-emerald-500'],
    ];

    $physicalPackageTotal = 0;
    $itemsData = $items->map(function ($line) use (&$physicalPackageTotal, $containerLineMeta, $photoPayload, $manifest) {
        $labels = $line->shipmentItem?->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->labels)
            ->filter()
            ->unique('id')
            ->values() ?? collect();
        $containerMeta = $containerLineMeta->get($line->id, []);
        $physicalPackageCount = max($labels->count(), 1);
        $loadedPackageCount = $line->labelScans?->count() ?: ((int) $line->loaded_quantity > 0 ? $physicalPackageCount : 0);
        $physicalPackageTotal += $physicalPackageCount;
        $shipmentItem = $line->shipmentItem;
        $vendorPhotos = $shipmentItem?->images
            ?->map(fn ($photo) => $photoPayload($photo, 'Vendor'))
            ->values() ?? collect();
        $driverPhotos = $shipmentItem?->shipment?->pickupAssignment?->photos
            ?->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $shipmentItem?->id)
            ->map(fn ($photo) => $photoPayload($photo, 'Driver'))
            ->values() ?? collect();
        $receiptPhotos = $shipmentItem?->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->photos)
            ->map(fn ($photo) => $photoPayload($photo, 'Receipt'))
            ->values() ?? collect();
        $primaryPhotos = $vendorPhotos->isNotEmpty()
            ? $vendorPhotos
            : ($driverPhotos->isNotEmpty() ? $driverPhotos : $receiptPhotos);

        return [
            'manifest_id' => $manifest->id,
            'manifest_number' => $manifest->manifest_number,
            'manifest_status' => $manifest->status,
            'manifest_url' => route('warehouse.manifests.incoming.show', $manifest),
            'shipment_item_id' => $line->shipment_item_id,
            'shipment_number' => $line->shipmentItem?->shipment?->shipment_number,
            'description' => $line->shipmentItem?->description,
            'tracking_code' => $line->shipmentItem?->tracking_code,
            'recipient_name' => $line->shipmentItem?->delivery_recipient_name ?: $line->shipmentItem?->shipment?->delivery_recipient_name,
            'recipient_phone' => $line->shipmentItem?->delivery_recipient_phone ?: $line->shipmentItem?->shipment?->delivery_recipient_phone,
            'container_id' => $containerMeta['id'] ?? null,
            'container_code' => $containerMeta['code'] ?? 'Unassigned',
            'container_url' => isset($containerMeta['id'])
                ? route('warehouse.manifests.incoming.show', $manifest) . '?container=' . $containerMeta['id']
                : null,
            'container_type' => $containerMeta['type'] ?? 'No container',
            'container_sequence' => $containerMeta['sequence'] ?? null,
            'container_status' => $containerMeta['status'] ?? null,
            'container_item_status' => $containerMeta['item_status'] ?? null,
            'physical_package_count' => $physicalPackageCount,
            'loaded_package_count' => min($loadedPackageCount, $physicalPackageCount),
            'labels' => $labels->map(fn ($label) => [
                'id' => $label->id,
                'barcode_value' => $label->barcode_value,
                'label_index' => $label->label_index,
                'labels_total' => $label->labels_total,
                'label_type' => $label->label_type,
            ])->values(),
            'expected_quantity' => (int) $line->expected_quantity,
            'loaded_quantity' => (int) $line->loaded_quantity,
            'received_quantity' => (int) $line->received_quantity,
            'line_status' => $line->line_status,
            'vendor_name' => $line->shipmentItem?->shipment?->vendor?->name,
            'photos' => [
                'primary' => $primaryPhotos->values(),
                'primary_source' => $vendorPhotos->isNotEmpty() ? 'Vendor' : ($driverPhotos->isNotEmpty() ? 'Driver' : 'Receipt'),
                'vendor' => $vendorPhotos,
                'driver' => $driverPhotos,
                'receipt' => $receiptPhotos,
                'total' => $vendorPhotos->count() + $driverPhotos->count() + $receiptPhotos->count(),
            ],
            'notes' => $line->notes,
            'loaded_at' => optional($line->loaded_at)?->format('Y-m-d H:i:s'),
            'received_at' => optional($line->received_at)?->format('Y-m-d H:i:s'),
        ];
    })->values();
@endphp

@section('content')
<div class="space-y-6"
     x-data="warehouseIncomingManifestShowPage">

    {{-- ── Hero ────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.20),transparent_58%)]"></div>
        </div>
        <div class="relative p-5 sm:p-6">
            <a href="{{ $indexRoute ?? route('warehouse.manifests.incoming.index') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-xs font-black text-slate-200 transition hover:bg-white/15 sm:gap-2 sm:px-3">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Back</span>
            </a>

            <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20 sm:h-20 sm:w-20">
                        <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-orange-200">From {{ $manifest->originWarehouse?->name ?? 'Origin Warehouse' }}</p>
                        <h1 class="mt-2 text-3xl font-black leading-tight text-white sm:text-4xl">{{ $manifest->manifest_number }}</h1>
                        <p class="mt-3 text-sm font-bold text-slate-300">
                            {{ $manifest->assignedDriver?->name ?? 'No driver' }}
                            @if($manifest->assignedDriver?->phone)
                                <span class="px-2 text-slate-500">/</span>{{ $manifest->assignedDriver->phone }}
                            @endif
                            <span class="px-2 text-slate-500">/</span>
                            {{ $manifest->arrived_at ? 'Arrived ' . $manifest->arrived_at->format('d M Y, h:i A') : 'Not arrived' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:min-w-40">
                        <p class="text-2xl font-black text-white">{{ number_format($items->count()) }} lines</p>
                        <p class="mt-1 text-sm font-bold text-slate-400">{{ number_format($totalExpected) }} qty expected</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:min-w-40">
                        <p class="text-2xl font-black text-white">{{ number_format($manifest->containers->count()) }} containers</p>
                        <p class="mt-1 text-sm font-bold text-slate-400">{{ str($manifest->status)->replace('_', ' ')->title() }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:min-w-40">
                        <p class="text-2xl font-black text-white">{{ number_format($totalReceived) }} received</p>
                        <p class="mt-1 text-sm font-bold text-slate-400">of {{ number_format($items->count()) }} lines</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Container Receiving Workspace ──────────────────────────────── --}}
    <div class="space-y-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-2xl font-black text-slate-950">Containers</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Inspect each container, verify package photos, then receive the lines.</p>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2">
                <span class="inline-flex h-10 items-center whitespace-nowrap rounded-xl px-3 text-xs font-black"
                    :class="isFinalized() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : (canReceive() ? 'bg-orange-50 text-orange-700 ring-1 ring-orange-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200')"
                    x-text="isFinalized() ? 'Finalized' : (canReceive() ? 'Ready to Receive' : 'Waiting for Arrival')"></span>
                <button type="button" x-show="!isFinalized()" @@click="showFinalizeModal = true" :disabled="!canFinalize() || loading"
                    class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-xl bg-slate-950 px-3 text-xs font-black text-white shadow-sm hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-45 sm:px-4">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span class="hidden sm:inline">Finalize Receipt</span>
                    <span class="sm:hidden">Finalize</span>
                </button>
            </div>
        </div>

        <div x-show="isFinalized()" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
            This incoming receipt has been finalized. Container lines are read-only.
        </div>
        <div x-show="!isFinalized() && !canReceive()" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
            This transfer has not arrived yet. You can inspect the containers, but receiving is locked.
        </div>

        <template x-if="containerGroups().length === 0">
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-base font-black text-slate-900">No containers found</p>
                <p class="mt-1 text-sm font-semibold text-slate-500">This incoming transfer has no packed containers.</p>
            </div>
        </template>

        <div class="space-y-5">
            <template x-for="container in containerGroups()" :key="container.id || container.code">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition"
                    :data-incoming-container-id="container.id">
                    <button type="button" @@click="toggleContainer(container.id)" class="flex w-full flex-col gap-4 bg-orange-50/55 px-5 py-4 text-left sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-black text-slate-950" x-text="container.type || 'Container'"></h3>
                                <span class="inline-flex rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-black text-orange-700" x-text="`${container.lines} ${container.lines === 1 ? 'LINE' : 'LINES'} - ${container.qty} QTY`"></span>
                                <span x-show="container.issues > 0" class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-black text-rose-700" x-text="`${container.issues} ${container.issues === 1 ? 'ISSUE' : 'ISSUES'}`"></span>
                            </div>
                            <p class="mt-1 truncate font-mono text-sm font-black text-slate-500" x-text="container.code || 'Unassigned'"></p>
                        </div>
                        <div class="flex min-w-[220px] items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between text-xs font-black text-slate-600">
                                    <span x-text="`${container.receivedLines}/${container.lines} lines`"></span>
                                    <span x-text="`${container.receivedQty}/${container.qty} qty`"></span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-orange-600 transition-all" :style="`width: ${container.progress}%`"></div>
                                </div>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-500 transition-transform" :class="isContainerOpen(container.id) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>
                    </button>

                    <div x-show="isContainerOpen(container.id)">
                        <div class="divide-y divide-slate-100">
                            <template x-for="row in container.items" :key="row.shipment_item_id">
                                <div class="grid gap-3 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                                    <div class="flex min-w-0 gap-3">
                                        <button type="button" @@click.stop="openPhotoViewer(row)" class="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                            <template x-if="primaryPhoto(row)">
                                                <img :src="primaryPhoto(row).url" :alt="primaryPhoto(row).name" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!primaryPhoto(row)">
                                                <div class="flex h-full w-full items-center justify-center text-[10px] font-black text-slate-400">No Photo</div>
                                            </template>
                                            <span x-show="photoCount(row) > 1" class="absolute bottom-1 right-1 rounded-full bg-slate-950/75 px-1.5 py-0.5 text-[10px] font-black text-white" x-text="'+' + (photoCount(row) - 1)"></span>
                                        </button>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex min-w-0 items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-black text-slate-950" x-text="row.description || 'Package'"></p>
                                                    <p class="mt-0.5 truncate font-mono text-xs font-black text-slate-500" x-text="row.tracking_code || 'No tracking'"></p>
                                                </div>
                                                <span class="shrink-0 text-right text-sm font-black text-slate-700 md:hidden" x-text="`Qty ${row.expected_quantity}`"></span>
                                            </div>
                                            <p class="mt-1 text-sm font-semibold text-slate-500">
                                                <span x-text="row.recipient_name || 'No recipient'"></span>
                                                <span x-show="row.recipient_phone" class="text-slate-300"> / </span>
                                                <span x-show="row.recipient_phone" x-text="row.recipient_phone"></span>
                                            </p>
                                            <div x-show="hasDiscrepancy(row)" class="mt-2 inline-flex max-w-full items-center rounded-full border px-3 py-1 text-xs font-black" :class="discrepancyTone(row)">
                                                <span class="truncate" x-text="discrepancyCopy(row)"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-4">
                                        <span class="hidden min-w-16 text-right text-sm font-black text-slate-700 md:inline" x-text="`Qty ${row.expected_quantity}`"></span>
                                        <button type="button" @@click="openReceiveModal(row.shipment_item_id)" :disabled="isFinalized() || !canReceive() || loading"
                                            class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-600 px-4 text-xs font-black text-white shadow-sm shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none">
                                            <span x-text="row.received_at ? 'Edit' : 'Receive'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </div>

    {{-- Photo Viewer Modal --}}
    <template x-teleport="body">
        <template x-if="photoViewer.open && photoViewer.row">
            <div class="fixed inset-0 z-[230] flex min-h-dvh w-screen items-center justify-center bg-slate-950/95 p-3" @@keydown.escape.window="closePhotoViewer()" @@keydown.arrow-right.window="nextViewerPhoto()" @@keydown.arrow-left.window="previousViewerPhoto()">
                <button type="button" class="absolute inset-0 cursor-zoom-out" @@click="closePhotoViewer()" aria-label="Close photo viewer"></button>

                <div class="pointer-events-none absolute left-0 right-0 top-0 z-10 bg-gradient-to-b from-slate-950 via-slate-950/70 to-transparent px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 text-white">
                            <h3 class="truncate text-base font-black sm:text-xl" x-text="photoViewer.row.description || 'Package photos'"></h3>
                            <p class="mt-1 truncate font-mono text-xs font-black text-slate-300 sm:text-sm" x-text="photoViewer.row.tracking_code || 'No tracking code'"></p>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-300 sm:text-sm">
                                <span x-text="photoViewer.row.recipient_name || 'No recipient'"></span>
                                <span x-show="photoViewer.row.recipient_phone"> / </span>
                                <span x-show="photoViewer.row.recipient_phone" x-text="photoViewer.row.recipient_phone"></span>
                            </p>
                        </div>
                        <button type="button" @@click="closePhotoViewer()" class="pointer-events-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition hover:bg-white/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div @@click.stop class="relative z-[1] flex h-full w-full items-center justify-center px-1 py-24 sm:px-14">
                    <template x-if="currentViewerPhoto()">
                        <img :src="currentViewerPhoto().url" :alt="currentViewerPhoto().name" class="max-h-full max-w-full object-contain shadow-2xl">
                    </template>

                    <button type="button" x-show="viewerPhotos().length > 1" @@click="previousViewerPhoto()" class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 sm:left-5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" x-show="viewerPhotos().length > 1" @@click="nextViewerPhoto()" class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 sm:right-5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                    </button>
                </div>

                <div class="absolute bottom-0 left-0 right-0 z-10 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent px-4 pb-4 pt-10 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <p class="text-xs font-black text-slate-300">
                            <span x-text="currentViewerPhoto()?.source || 'Photo'"></span>
                            <span> photo </span>
                            <span x-text="photoViewer.index + 1"></span>
                            <span> of </span>
                            <span x-text="viewerPhotos().length"></span>
                        </p>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <template x-for="(photo, index) in viewerPhotos()" :key="`${photo.source}-${photo.id}`">
                                <button type="button" @@click="selectViewerPhoto(index)" class="h-14 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-900 transition" :class="index === photoViewer.index ? 'border-orange-500 opacity-100' : 'border-white/20 opacity-60 hover:opacity-100'">
                                    <img :src="photo.url" :alt="photo.name" class="h-full w-full object-cover">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </template>

    @include('shared.incoming-receive-modal')

    @if(false)
    {{-- Receive Item Modal --}}
    <template x-teleport="body">
        <template x-if="receiveModal.open && receiveModal.itemIndex >= 0">
        <div
            class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center p-4"
            @@keydown.escape.window="closeReceiveModal()"
        >
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @@click="closeReceiveModal()"></div>

            <div
                @@click.stop
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                class="relative flex max-h-[90dvh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 4.5h2.5v15h-2.5zM9 4.5h1.5v15H9zM13.25 4.5h3v15h-3zM19 4.5h1.25v15H19z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-black text-slate-900">Receive Package</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Scan the package label or verify manually before saving.</p>
                        </div>
                    </div>
                    <button type="button" @@click="closeReceiveModal()" class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div class="rounded-3xl border-2 border-orange-200 bg-orange-50/60 p-4">
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-orange-800">Scan package label</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <div class="relative flex-1">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 4.5h2.5v15h-2.5zM9 4.5h1.5v15H9zM13.25 4.5h3v15h-3zM19 4.5h1.25v15H19z"/>
                                </svg>
                                <input
                                    type="text"
                                    x-model="receiveScanCode"
                                    @@keydown.enter.prevent="scanReceiveCurrentItem()"
                                    class="h-14 w-full rounded-2xl border-2 border-orange-200 bg-white pl-12 pr-4 text-base font-black text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                    placeholder="Scan or enter label code..."
                                >
                            </div>
                            <button type="button" @@click="scanReceiveCurrentItem()" :disabled="loading" class="h-14 rounded-2xl bg-orange-600 px-5 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:opacity-50">
                                Scan Receive
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_1.15fr]">
                        <div class="space-y-4">
                            <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Package</p>
                                <h4 class="mt-2 text-xl font-black text-slate-950" x-text="items[receiveModal.itemIndex]?.description || 'Package'"></h4>
                                <p class="mt-1 font-mono text-sm font-black text-slate-500" x-text="items[receiveModal.itemIndex]?.tracking_code || 'No tracking code'"></p>
                                <div class="mt-3 grid gap-2 text-sm font-bold text-slate-600">
                                    <p>
                                        <span class="text-slate-400">Recipient:</span>
                                        <span x-text="items[receiveModal.itemIndex]?.recipient_name || 'No recipient'"></span>
                                        <span x-show="items[receiveModal.itemIndex]?.recipient_phone"> / </span>
                                        <span x-show="items[receiveModal.itemIndex]?.recipient_phone" x-text="items[receiveModal.itemIndex]?.recipient_phone"></span>
                                    </p>
                                    <p>
                                        <span class="text-slate-400">Container:</span>
                                        <span class="font-mono" x-text="items[receiveModal.itemIndex]?.container_code || 'Unassigned'"></span>
                                        <span class="text-slate-400"> / </span>
                                        <span x-text="items[receiveModal.itemIndex]?.container_type || 'No container'"></span>
                                    </p>
                                </div>
                                <div class="mt-4 grid grid-cols-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                    <div class="px-3 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Expected</p>
                                        <p class="mt-1 text-xl font-black text-slate-900" x-text="items[receiveModal.itemIndex]?.expected_quantity ?? 0"></p>
                                    </div>
                                    <div class="border-l border-slate-200 px-3 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Loaded</p>
                                        <p class="mt-1 text-xl font-black text-slate-900" x-text="items[receiveModal.itemIndex]?.loaded_quantity ?? 0"></p>
                                    </div>
                                    <div class="border-l border-slate-200 px-3 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Received</p>
                                        <p class="mt-1 text-xl font-black text-emerald-700" x-text="items[receiveModal.itemIndex]?.received_quantity || 0"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Labels</p>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-600" x-text="(items[receiveModal.itemIndex]?.labels?.length || 0) + ((items[receiveModal.itemIndex]?.labels?.length || 0) === 1 ? ' label' : ' labels')"></span>
                                </div>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2" x-show="items[receiveModal.itemIndex]?.labels?.length">
                                    <template x-for="label in items[receiveModal.itemIndex]?.labels || []" :key="label.id">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                            <p class="font-mono text-xs font-black text-slate-800" x-text="label.barcode_value"></p>
                                            <p class="mt-0.5 text-[10px] font-semibold text-slate-400" x-text="label.labels_total ? ('Label ' + label.label_index + ' of ' + label.labels_total) : 'Label'"></p>
                                        </div>
                                    </template>
                                </div>
                                <p x-show="!items[receiveModal.itemIndex]?.labels?.length" class="mt-3 text-sm font-semibold text-slate-400">No labels recorded for this line.</p>
                            </div>
                        </div>

	                        <div class="space-y-5">
	                            <div>
	                                <label class="mb-2 block text-sm font-bold text-slate-800">Description</label>
	                                <input
	                                    type="text"
	                                    x-model="receiveDraft.description"
	                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
	                                    placeholder="What's inside?"
	                                >
	                            </div>

	                            <div>
	                                <label class="mb-2 block text-sm font-bold text-slate-800">Received Quantity</label>
                                <input
                                    type="number"
                                    min="0"
	                                    x-model.number="receiveDraft.received_quantity"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-lg font-black text-slate-900 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                    placeholder="0"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-800">Receipt Status</label>
                                <select
	                                    x-model="receiveDraft.line_status"
                                    class="h-14 w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-900 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                >
                                    <option value="received">Received</option>
                                    <option value="short">Short</option>
                                    <option value="excess">Excess</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-800">Notes</label>
                                <textarea
                                    rows="4"
	                                    x-model="receiveDraft.notes"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                    placeholder="Shortage, damage, or receiving notes..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" @@click="closeReceiveModal()" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                        Cancel
                    </button>
                    <button
                        type="button"
                        @@click="markExpected(receiveModal.itemId)"
                        :disabled="loading"
                        class="rounded-2xl border border-orange-200 bg-orange-50 px-5 py-3 text-sm font-black text-orange-700 transition hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Receive As Expected
                    </button>
                    <button
                        type="button"
                        @@click="saveItem(receiveModal.itemId)"
                        :disabled="loading"
                        class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-6 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Saving...' : 'Save Receipt'"></span>
                    </button>
                </div>
            </div>
        </div>
        </template>
    </template>

    @endif

    @if(false)
    {{-- ── Sidebar + Content ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        {{-- Sidebar Nav --}}
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Incoming</p>

            {{-- Overview --}}
            <button @@click="activeTab = 'overview'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'overview' ? 'bg-emerald-50 ring-1 ring-emerald-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'overview' ? 'bg-emerald-500 shadow-sm shadow-emerald-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'overview' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'overview' ? 'font-bold text-emerald-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Overview</span>
            </button>

            {{-- Receiving --}}
            <button @@click="activeTab = 'receiving'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'receiving' ? 'bg-orange-50 ring-1 ring-orange-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'receiving' ? 'bg-orange-500 shadow-sm shadow-orange-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'receiving' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'receiving' ? 'font-bold text-orange-800' : 'font-medium text-slate-500 group-hover:text-slate-700'">Receiving</span>
                <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold"
                    :class="activeTab === 'receiving' ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500'">{{ $items->count() }}</span>
            </button>

            {{-- Divider --}}
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Operations</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            {{-- Timeline --}}
            <button @@click="activeTab = 'timeline'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
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

        {{-- Tab Content --}}
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">

            {{-- ── Overview Tab ─────────────────────────────────────────── --}}
            <div x-show="activeTab === 'overview'">
                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

                    {{-- Left Column (3/5) --}}
                    <div class="xl:col-span-3 space-y-4">

                        {{-- Card A: Manifest Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Manifest Summary</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Incoming transport manifest details</p>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ str($manifest->status)->replace('_', ' ')->title() }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Manifest #</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $manifest->manifest_number }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Created</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $manifest->created_at?->format('M d, Y') ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $manifest->created_at?->format('H:i') ?? '' }}</p>
                                </div>
                                <div class="rounded-xl border px-4 py-3 {{ $manifest->arrived_at ? 'bg-amber-50 border-amber-100' : 'bg-slate-50 border-slate-100' }}">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ $manifest->arrived_at ? 'text-amber-500' : 'text-slate-400' }} mb-1">Arrived</p>
                                    <p class="text-sm font-bold {{ $manifest->arrived_at ? 'text-slate-900' : 'text-slate-400' }}">{{ $manifest->arrived_at ? $manifest->arrived_at->format('M d, Y') : '—' }}</p>
                                    <p class="text-xs {{ $manifest->arrived_at ? 'text-amber-600/70' : 'text-slate-400' }}">{{ $manifest->arrived_at ? $manifest->arrived_at->format('H:i') : 'Not yet arrived' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Card B: Route (Origin → Destination) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Origin Warehouse --}}
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Origin</h3>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Warehouse</span>
                                        <span class="font-semibold text-slate-800">{{ $manifest->originWarehouse?->name ?? '—' }}</span>
                                    </div>
                                    @if($manifest->originWarehouse?->code)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Code</span>
                                            <span class="font-mono font-semibold text-slate-700">{{ $manifest->originWarehouse->code }}</span>
                                        </div>
                                    @endif
                                    @if($manifest->originWarehouse?->location)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Location</span>
                                            <span class="text-slate-600">{{ $manifest->originWarehouse->location }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Destination Warehouse (this warehouse) --}}
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900">Destination</h3>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Warehouse</span>
                                        <span class="font-semibold text-slate-800">{{ $manifest->destinationWarehouse?->name ?? '—' }}</span>
                                    </div>
                                    @if($manifest->destinationWarehouse?->code)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Code</span>
                                            <span class="font-mono font-semibold text-slate-700">{{ $manifest->destinationWarehouse->code }}</span>
                                        </div>
                                    @endif
                                    @if($manifest->destinationWarehouse?->location)
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Location</span>
                                            <span class="text-slate-600">{{ $manifest->destinationWarehouse->location }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-start gap-2 text-xs">
                                        <span class="text-slate-400 w-20 flex-shrink-0 mt-0.5">Received</span>
                                        <span class="font-semibold {{ $manifest->received_at ? 'text-emerald-700' : 'text-slate-400' }}">{{ $manifest->received_at ? $manifest->received_at->format('M d, Y H:i') : 'Pending' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card C: Transport Progress --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h4 class="text-sm font-bold text-slate-800">Transport Progress</h4>
                            </div>
                            <div class="overflow-x-auto -mx-2 px-2">
                                <div class="relative flex min-w-[480px]">
                                    <div class="absolute top-5 left-[calc(100%/10)] right-[calc(100%/10)] h-px bg-slate-200 -z-0"></div>
                                    @php
                                        $steps = [
                                            ['label' => 'Created', 'at' => $manifest->created_at, 'color' => 'slate'],
                                            ['label' => 'Assigned', 'at' => $manifest->assigned_at, 'color' => 'blue'],
                                            ['label' => 'Dispatched', 'at' => $manifest->dispatched_at, 'color' => 'violet'],
                                            ['label' => 'Arrived', 'at' => $manifest->arrived_at, 'color' => 'amber'],
                                            ['label' => 'Received', 'at' => $manifest->received_at, 'color' => 'emerald'],
                                        ];
                                    @endphp
                                    @foreach($steps as $step)
                                        <div class="relative flex-1 flex flex-col items-center gap-2 z-10">
                                            <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center shadow-sm {{ $step['at'] ? 'border-'.$step['color'].'-500 bg-'.$step['color'].'-500 text-white' : 'border-slate-200 bg-white text-slate-300' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <p class="text-[11px] font-semibold text-center leading-tight {{ $step['at'] ? 'text-'.$step['color'].'-700' : 'text-slate-400' }}">{{ $step['label'] }}</p>
                                            <p class="text-[10px] text-slate-400 text-center leading-tight">{{ $step['at'] ? $step['at']->format('M d, H:i') : '—' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column (2/5) --}}
                    <div class="xl:col-span-2 space-y-4">

                        {{-- Quantity Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-900 mb-3">Quantity Summary</h3>
                            <div class="space-y-2.5">
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-orange-600/20 to-orange-700/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Total Items</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($items->count()) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/20 to-violet-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Expected Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalExpected) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wide">Loaded Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalLoaded) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl border {{ $totalReceived > 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100' }}">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $totalReceived > 0 ? 'from-emerald-500/20 to-emerald-600/10' : 'from-slate-400/20 to-slate-500/10' }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 {{ $totalReceived > 0 ? 'text-emerald-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold {{ $totalReceived > 0 ? 'text-emerald-600' : 'text-slate-400' }} uppercase tracking-wide">Received Qty</p>
                                        <p class="text-lg font-bold text-slate-900 leading-tight">{{ number_format($totalReceived) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Driver Info Widget --}}
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200/80">
                            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-white leading-none">Transport Driver</h3>
                                        @if($manifest->assignedDriver)
                                            <p class="text-[11px] text-white/60 mt-0.5">{{ $manifest->assignedDriver->name }}</p>
                                        @else
                                            <p class="text-[11px] text-white/50 mt-0.5">No driver assigned</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-5">
                                @if($manifest->assignedDriver)
                                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="w-10 h-10 rounded-full ring-2 ring-emerald-200 bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-black text-sm shadow-sm flex-shrink-0">
                                            {{ strtoupper(substr($manifest->assignedDriver->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-900 truncate leading-none">{{ $manifest->assignedDriver->name }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $manifest->assignedDriver->phone ?? '—' }}</p>
                                        </div>
                                    </div>
                                    @if($manifest->assignedDriver->vehicle_type || $manifest->assignedDriver->vehicle_number)
                                        <div class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 border border-slate-100">
                                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0014 6h-1"/>
                                            </svg>
                                            <span class="text-xs text-slate-600 font-medium">{{ $manifest->assignedDriver->vehicle_type ?? '' }} {{ $manifest->assignedDriver->vehicle_number ?? '' }}</span>
                                        </div>
                                    @endif
                                @else
                                    <div class="text-center py-4">
                                        <svg class="w-9 h-9 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <p class="text-xs text-slate-400">No driver assigned</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Finalize Receipt Widget --}}
                        <div class="bg-white rounded-2xl border shadow-sm p-5 {{ $manifest->status === 'received' ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200' }}">
                            <div class="flex items-center gap-2.5 mb-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $manifest->status === 'received' ? 'bg-emerald-100' : 'bg-slate-100' }}">
                                    <svg class="w-4 h-4 {{ $manifest->status === 'received' ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold {{ $manifest->status === 'received' ? 'text-emerald-800' : 'text-slate-900' }}">Receipt Status</h4>
                                    <p class="text-[11px] {{ $manifest->status === 'received' ? 'text-emerald-600' : 'text-slate-400' }}">{{ $manifest->status === 'received' ? 'Finalized on ' . $manifest->received_at?->format('M d, Y H:i') : 'Not yet finalized' }}</p>
                                </div>
                            </div>
                            @if($manifest->status !== 'received')
                                <button
                                    type="button"
                                    @@click="showFinalizeModal = true"
                                    :disabled="isFinalized() || !canReceive() || loading"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-700 hover:to-slate-800 text-white text-xs font-bold rounded-xl shadow-lg shadow-slate-900/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Finalize Receipt
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Receiving Tab ──────────────────────────────────────────── --}}
            <div x-show="activeTab === 'receiving'" x-cloak>
                @include('shared.transport-containers-section', ['manifest' => $manifest, 'allowContainerActions' => false, 'hideEmptyContainers' => true])

                {{-- Header --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mt-6 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-200/60 shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Warehouse Receiving</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Verify and record items received from transport</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide"
                            :class="isFinalized() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (canReceive() ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700 border border-amber-200')"
                            x-text="isFinalized() ? 'Finalized' : (canReceive() ? 'In Progress' : 'Waiting for Arrival')"></span>
                        <button
                            type="button"
                            x-show="!isFinalized()"
                            @@click="showFinalizeModal = true"
                            :disabled="!canReceive() || loading"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-slate-800 to-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white hover:from-slate-700 hover:to-slate-800 shadow-lg shadow-slate-900/25 disabled:opacity-50 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Finalize Receipt
                        </button>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-orange-600/20 to-orange-700/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Physical Packages</p>
                            <p class="text-lg font-bold text-slate-900 leading-tight" x-text="physicalPackageTotal()"></p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Item Qty</p>
                            <p class="text-lg font-bold text-slate-900 leading-tight" x-text="itemQuantityTotal()"></p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Received Lines</p>
                            <p class="text-lg font-bold text-slate-900 leading-tight" x-text="receivedCount()"></p>
                        </div>
                    </div>
                </div>

                {{-- Finalized banner --}}
                <div x-show="isFinalized()" class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs font-semibold text-emerald-700">This manifest receipt has been finalized. Items are read-only.</p>
                </div>

                {{-- Waiting for arrival banner --}}
                <div x-show="!isFinalized() && !canReceive()" class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-amber-50 border border-amber-200">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                    </svg>
                    <p class="text-xs font-semibold text-amber-700">Receiving is locked until the driver marks this manifest as arrived at the destination warehouse.</p>
                </div>

                {{-- Items Grid --}}
                <template x-if="items.length > 0">
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        <template x-for="(row, idx) in items" :key="row.shipment_item_id">
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md hover:border-slate-300 transition-all duration-200">

                                {{-- Card Header --}}
                                <div class="px-4 pt-4 pb-3 border-b border-slate-100">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-100 to-slate-50 border border-slate-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-900 truncate" x-text="row.description || 'Item'"></p>
                                                <p class="text-[10px] text-slate-400 mt-0.5 font-mono" x-text="'#' + row.shipment_item_id"></p>
                                                <p class="text-[10px] text-slate-400 truncate" x-show="row.tracking_code" x-text="row.tracking_code"></p>
                                            </div>
                                        </div>
                                        <div>
                                            <template x-if="row.received_at">
                                                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusClass(row.line_status)">
                                                    <span x-text="statusLabel(row.line_status)"></span>
                                                </span>
                                            </template>
                                            <template x-if="!row.received_at">
                                                <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-400">
                                                    Pending
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Physical Package Summary --}}
                                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Physical Packages</p>
                                            <p class="mt-1 text-sm font-bold text-slate-900" x-text="packageUnitLabel(row.physical_package_count)"></p>
                                            <p class="mt-0.5 text-xs text-slate-500" x-text="'Contains ' + itemUnitLabel(row.expected_quantity)"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Loaded</p>
                                            <p class="mt-1 text-sm font-bold" :class="Number(row.loaded_package_count) >= Number(row.physical_package_count) ? 'text-emerald-600' : 'text-amber-600'">
                                                <span x-text="row.loaded_package_count || 0"></span>/<span x-text="row.physical_package_count || 0"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5" x-show="row.labels && row.labels.length">
                                        <template x-for="label in row.labels" :key="label.id">
                                            <span class="inline-flex items-center rounded-lg bg-white px-2 py-1 text-[10px] font-bold text-slate-600 border border-slate-200 font-mono" x-text="label.barcode_value"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Quantity Comparison Strip --}}
                                <div class="grid grid-cols-3 divide-x divide-slate-100 bg-slate-50/50 border-b border-slate-100">
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Item Qty</p>
                                        <p class="text-base font-bold text-slate-800 mt-0.5" x-text="row.expected_quantity"></p>
                                    </div>
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Loaded Qty</p>
                                        <p class="text-base font-bold mt-0.5" :class="Number(row.loaded_quantity) !== Number(row.expected_quantity) ? 'text-amber-600' : 'text-slate-800'" x-text="row.loaded_quantity"></p>
                                    </div>
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[9px] font-bold uppercase tracking-wider" :class="row.received_at ? 'text-emerald-500' : 'text-slate-400'">Received Qty</p>
                                        <p class="text-base font-bold mt-0.5" :class="row.received_at ? (Number(row.received_quantity) === Number(row.expected_quantity) ? 'text-emerald-600' : 'text-amber-600') : 'text-slate-300'" x-text="row.received_quantity || '—'"></p>
                                    </div>
                                </div>

                                {{-- Receipt Summary --}}
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <div class="flex items-center gap-3 text-xs">
                                        <div class="flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="row.received_at ? 'bg-emerald-400' : 'bg-slate-300'"></span>
                                            <span class="font-semibold" :class="row.received_at ? 'text-emerald-700' : 'text-slate-400'">Rcv <span x-text="row.received_quantity || 0"></span></span>
                                        </div>
                                        <template x-if="row.notes">
                                            <div class="flex items-center gap-1 text-slate-400">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                                <span class="text-[10px]">Notes</span>
                                            </div>
                                        </template>
                                        <template x-if="row.received_at">
                                            <span class="ml-auto text-[10px] text-slate-400" x-text="row.received_at"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Shipment Info --}}
                                <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50/30">
                                    <div class="flex items-center gap-2 text-xs">
                                        <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="text-slate-500">Shipment:</span>
                                        <span class="font-semibold text-slate-700" x-text="row.shipment_number || '—'"></span>
                                        <template x-if="row.vendor_name">
                                            <span class="text-slate-400 ml-auto truncate max-w-[120px]" x-text="row.vendor_name"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Receive Button --}}
                                <div class="px-4 py-3">
                                    <button type="button"
                                        @@click="openReceiveModal(row.shipment_item_id)"
                                        :disabled="isFinalized() || !canReceive() || loading"
                                        class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="row.received_at ? 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200' : 'bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <template x-if="row.received_at">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </template>
                                            <template x-if="!row.received_at">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </template>
                                        </svg>
                                        <span x-text="row.received_at ? 'Edit Receipt' : 'Receive Package'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Empty State --}}
                <template x-if="items.length === 0">
                    <div class="text-center py-16">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-400">No items in this manifest</p>
                    </div>
                </template>
            </div>

            {{-- ── Receive Item Modal ─────────────────────────────────────── --}}
            <template x-if="receiveModal.open && receiveModal.itemIndex >= 0">
                <div class="fixed inset-0 z-[120] flex items-center justify-center p-4" @@keydown.escape.window="closeReceiveModal()">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @@click="closeReceiveModal()"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden" @@click.stop>

                        {{-- Modal Header --}}
                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900">Receive Package</h3>
                                    <p class="text-xs text-slate-500 truncate" x-text="items[receiveModal.itemIndex]?.description || 'Item'"></p>
                                </div>
                            </div>
                            <button @@click="closeReceiveModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Physical package context --}}
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/60">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Receiving Physical Packages</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900" x-text="packageUnitLabel(items[receiveModal.itemIndex]?.physical_package_count)"></p>
                                    <p class="mt-0.5 text-xs text-slate-500" x-text="'Contains ' + itemUnitLabel(items[receiveModal.itemIndex]?.expected_quantity)"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Loaded</p>
                                    <p class="mt-1 text-sm font-bold text-emerald-600">
                                        <span x-text="items[receiveModal.itemIndex]?.loaded_package_count || 0"></span>/<span x-text="items[receiveModal.itemIndex]?.physical_package_count || 0"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5" x-show="items[receiveModal.itemIndex]?.labels?.length">
                                <template x-for="label in items[receiveModal.itemIndex]?.labels || []" :key="label.id">
                                    <span class="inline-flex items-center rounded-lg bg-white px-2 py-1 text-[10px] font-bold text-slate-600 border border-slate-200 font-mono" x-text="label.barcode_value"></span>
                                </template>
                            </div>
                        </div>

                        {{-- Reference Quantities --}}
                        <div class="grid grid-cols-3 divide-x divide-slate-100 bg-slate-50 border-b border-slate-200">
                            <div class="px-4 py-3 text-center">
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Item Qty</p>
                                <p class="text-xl font-bold text-slate-800 mt-0.5" x-text="items[receiveModal.itemIndex]?.expected_quantity ?? 0"></p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Loaded Qty</p>
                                <p class="text-xl font-bold text-slate-800 mt-0.5" x-text="items[receiveModal.itemIndex]?.loaded_quantity ?? 0"></p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-wider">Received Qty</p>
                                <p class="text-xl font-bold text-emerald-600 mt-0.5" x-text="items[receiveModal.itemIndex]?.received_quantity || 0"></p>
                            </div>
                        </div>

	                        {{-- Modal Body --}}
	                        <div class="px-6 py-5 space-y-5 max-h-[50vh] overflow-y-auto">

	                            <div>
	                                <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
	                                <input type="text"
	                                    x-model="receiveDraft.description"
	                                    class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
	                                    placeholder="What's inside?">
	                            </div>

	                            {{-- Received Quantity --}}
	                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Received Item Quantity</label>
                                <input type="number" min="0"
	                                    x-model.number="receiveDraft.received_quantity"
                                    class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    placeholder="0">
                                <p class="mt-1.5 text-xs text-slate-500" x-text="'Enter the item quantity inside the physical package(s), not the number of labels.'"></p>
                            </div>

                            {{-- Line Status --}}
	                            <div>
	                                <label class="block text-sm font-semibold text-slate-700 mb-2">Line Status</label>
	                                <div class="grid grid-cols-3 gap-2">
	                                    <button type="button" @@click="receiveDraft.line_status = 'received'"
	                                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all"
	                                        :class="receiveDraft.line_status === 'received' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Received
                                    </button>
	                                    <button type="button" @@click="receiveDraft.line_status = 'short'"
	                                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all"
	                                        :class="receiveDraft.line_status === 'short' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.999L13.732 4.001c-.77-1.333-2.694-1.333-3.464 0L3.34 16.001C2.57 17.334 3.536 19 5.072 19z"/></svg>
                                        Short
                                    </button>
	                                    <button type="button" @@click="receiveDraft.line_status = 'excess'"
	                                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all"
	                                        :class="receiveDraft.line_status === 'excess' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Excess
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mt-2">
	                                    <button type="button" @@click="receiveDraft.line_status = 'damaged'"
	                                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all"
	                                        :class="receiveDraft.line_status === 'damaged' ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Damaged
                                    </button>
	                                    <button type="button" @@click="receiveDraft.line_status = 'pending'"
	                                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all"
	                                        :class="(!receiveDraft.line_status || receiveDraft.line_status === 'pending') ? 'border-slate-500 bg-slate-50 text-slate-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Pending
                                    </button>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                                <textarea rows="3"
	                                    x-model="receiveDraft.notes"
                                    class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    placeholder="Any notes about this item..."></textarea>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                            <button type="button" @@click="closeReceiveModal()"
                                class="px-4 py-2 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                            <button type="button" @@click="saveItem(receiveModal.itemId)"
                                :disabled="loading"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="loading ? 'Saving...' : 'Save & Close'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ── Timeline Tab ─────────────────────────────────────────── --}}
            <div x-show="activeTab === 'timeline'" x-cloak>
                @php
                    $completedCount = collect($timeline)->filter(fn($e) => !is_null($e['value']))->count();
                    $totalCount = count($timeline);
                    $timelineDescriptions = [
                        'Created'          => 'Transport manifest created from sealed batch',
                        'Driver Assigned'  => 'Driver assigned for transport',
                        'Dispatched'       => 'Manifest dispatched, items in transit',
                        'Arrived'          => 'Driver arrived at destination warehouse',
                        'Received'         => 'Items received and verified at destination',
                    ];
                    $timelineIcons = [
                        'Created'          => 'M12 4v16m8-8H4',
                        'Driver Assigned'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'Dispatched'       => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                        'Arrived'          => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                        'Received'         => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ];
                    $timelineIconColor = [
                        'bg-slate-500'   => ['badge' => 'bg-slate-100 text-slate-600'],
                        'bg-orange-500'    => ['badge' => 'bg-orange-50 text-orange-800'],
                        'bg-violet-500'  => ['badge' => 'bg-violet-50 text-violet-700'],
                        'bg-amber-500'   => ['badge' => 'bg-amber-50 text-amber-700'],
                        'bg-emerald-500' => ['badge' => 'bg-emerald-50 text-emerald-700'],
                    ];
                @endphp

                {{-- Section Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-200/60 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Transport Timeline</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Step-by-step progress from creation to receipt</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ $completedCount }} / {{ $totalCount }} steps</span>
                </div>

                {{-- Timeline Events --}}
                <div>
                    @foreach($timeline as $index => $event)
                        @php
                            $isCompleted = !is_null($event['value']);
                            $dotClass    = $event['dot'] ?? 'bg-slate-400';
                            $colors      = $timelineIconColor[$dotClass] ?? ['badge' => 'bg-slate-100 text-slate-500'];
                            $iconBg      = $isCompleted ? $dotClass : 'bg-slate-200';
                            $badgeClass  = $isCompleted ? $colors['badge'] : 'bg-slate-100 text-slate-400';
                            $desc        = $timelineDescriptions[$event['label']] ?? $event['label'];
                            $iconPath    = $timelineIcons[$event['label']] ?? 'M5 13l4 4L19 7';
                        @endphp

                        @if($index > 0)
                            <div class="flex justify-start pl-[22px] my-0.5">
                                <div class="w-px h-4 bg-slate-200"></div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3 bg-white rounded-2xl border border-slate-100 shadow-sm {{ $isCompleted ? 'hover:shadow-md hover:border-slate-200' : 'opacity-55' }} transition-all duration-200 p-3.5">
                            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm mt-0.5 {{ $iconBg }}">
                                <svg class="w-4 h-4 {{ $isCompleted ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                                </svg>
                            </div>
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

                    @if($manifest->status === 'cancelled')
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
                                </div>
                                <p class="text-sm font-bold text-rose-700 leading-snug">Transport Manifest Cancelled</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @endif

    {{-- Finalize Receipt Modal --}}
    <template x-teleport="body">
    <div x-show="showFinalizeModal" x-cloak class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center p-4" style="display: none;" @@keydown.escape.window="showFinalizeModal = false">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @@click="showFinalizeModal = false"></div>
        <div
            class="relative flex max-h-[90dvh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            @@click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-2xl font-black leading-tight text-slate-950">Finalize Receipt</h3>
                        <p class="mt-1 text-base font-semibold text-slate-500">Lock this incoming transfer after verification.</p>
                    </div>
                </div>
                <button type="button" @@click="showFinalizeModal = false" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Incoming Transfer</p>
                    <p class="mt-2 font-mono text-lg font-black text-slate-950">{{ $manifest->manifest_number }}</p>
                    <div class="mt-4 grid grid-cols-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="px-3 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Lines</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ number_format($items->count()) }}</p>
                        </div>
                        <div class="border-l border-slate-200 px-3 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Expected</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ number_format($totalExpected) }}</p>
                        </div>
                        <div class="border-l border-slate-200 px-3 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Received</p>
                            <p class="mt-1 text-xl font-black text-emerald-700" x-text="receivedQuantityTotal()"></p>
                        </div>
                        <div class="border-l border-slate-200 px-3 py-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Issues</p>
                            <p class="mt-1 text-xl font-black" :class="discrepancyCount() ? 'text-rose-700' : 'text-slate-950'" x-text="discrepancyCount()"></p>
                        </div>
                    </div>
                </div>

                <div x-show="discrepancyCount() > 0" class="mt-5 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-4">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black text-rose-800">Discrepancies recorded</p>
                            <div class="mt-3 divide-y divide-rose-200/70 rounded-2xl border border-rose-200 bg-white/70">
                                <template x-for="item in discrepancyItems()" :key="item.shipment_item_id">
                                    <div class="px-3 py-2">
                                        <p class="text-sm font-black text-slate-950" x-text="item.description || 'Package'"></p>
                                        <p class="mt-0.5 font-mono text-xs font-black text-slate-500" x-text="item.tracking_code || 'No tracking'"></p>
                                        <p class="mt-1 text-xs font-bold text-rose-700" x-text="discrepancyCopy(item)"></p>
                                        <p x-show="item.notes" class="mt-1 text-xs font-semibold text-slate-500" x-text="item.notes"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-3xl border border-amber-200 bg-amber-50 px-4 py-4">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                        <p class="text-sm font-black leading-6 text-amber-800">Finalize only after all received quantities and exceptions are correct.</p>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-black text-slate-900">Notes</label>
                    <textarea
                        x-model="finalizeNotes"
                        rows="4"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                        placeholder="Final receiving notes..."
                    ></textarea>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button type="button" @@click="showFinalizeModal = false"
                    class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-black text-slate-700 transition hover:bg-slate-50 sm:h-14 sm:px-8 sm:text-base">
                    Cancel
                </button>
                <button type="button" @@click="finalizeReceipt()"
                    :disabled="!canFinalize() || loading"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-950/15 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-45 sm:h-14 sm:px-8 sm:text-base">
                    <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="loading ? 'Finalizing...' : 'Finalize Receipt'"></span>
                </button>
            </div>
        </div>
    </div>
    </template>
</div>

@push('scripts')
<script>
    window.__incomingManifestConfig = {
        manifest_status: @json($manifest->status),
        items: @json($itemsData),
        scan_receive_endpoint: @json($manifestConfig['scan_receive_endpoint']),
        finalize_endpoint: @json($manifestConfig['finalize_endpoint'])
    };
</script>
@endpush
@endsection
