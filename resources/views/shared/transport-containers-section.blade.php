@php
    $containers = $manifest->containers->sortBy('sequence_number')->values();
    $allowContainerActions = $allowContainerActions ?? true;
    $canManageContainers = $allowContainerActions && in_array($manifest->status, ['draft', 'assigned', 'loading'], true);
    $canMarkContainersLoaded = $allowContainerActions && in_array($manifest->status, ['assigned', 'loading'], true);
    $containerStatusClass = [
        'open' => 'bg-slate-50 text-slate-700 border-slate-200',
        'sealed' => 'bg-blue-50 text-blue-700 border-blue-200',
        'loaded' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'in_transit' => 'bg-amber-50 text-amber-700 border-amber-200',
        'received' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'reconciled' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'damaged' => 'bg-rose-50 text-rose-700 border-rose-200',
        'missing' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
    <div class="px-6 py-5 border-b border-slate-200/50">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Transport Containers</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Drivers scan container codes during loading. Items stay visible for audit.</p>
                </div>
            </div>

            @if($canManageContainers)
                <button
                    type="button"
                    @@click="openCreateContainerModal()"
                    :disabled="actionLoading"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition-colors disabled:opacity-50"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Container
                </button>
            @endif
        </div>
    </div>

    <div class="px-6 py-5">
        @if($containers->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-5 py-8 text-center">
                <p class="text-sm font-semibold text-slate-700">No transport containers yet.</p>
                <p class="text-xs text-slate-500 mt-1">A default loose container will be created automatically when this page loads.</p>
            </div>
        @else
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @foreach($containers as $container)
                    @php
                        $itemQuantity = $container->items->sum('expected_quantity');
                        $statusClass = $containerStatusClass[$container->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-slate-900 font-mono">{{ $container->container_code }}</p>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                                        {{ str($container->status)->replace('_', ' ')->title() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ str($container->container_type)->replace('_', ' ')->title() }}
                                    · {{ $container->items->count() }} package line(s)
                                    · {{ $itemQuantity }} item(s)
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($allowContainerActions)
                                    <button
                                        type="button"
                                        @@click="printContainerLabel({{ $container->id }})"
                                        :disabled="actionLoading || {{ $container->items->isEmpty() ? 'true' : 'false' }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 text-[11px] font-semibold transition-colors disabled:opacity-50"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0h12v4H8v-4z"/>
                                        </svg>
                                        Print Label
                                    </button>
                                @endif

                                @if($canMarkContainersLoaded && !in_array($container->status, ['loaded', 'in_transit', 'received', 'reconciled'], true))
                                    <button
                                        type="button"
                                        @@click="markContainerLoaded({{ $container->id }})"
                                        :disabled="actionLoading || {{ $container->items->isEmpty() ? 'true' : 'false' }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-semibold transition-colors disabled:opacity-50"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Mark Loaded
                                    </button>
                                @endif

                                @if($canMarkContainersLoaded && $container->status === 'loaded')
                                    <button
                                        type="button"
                                        @@click="markContainerNotLoaded({{ $container->id }})"
                                        :disabled="actionLoading || {{ $container->items->isEmpty() ? 'true' : 'false' }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-[11px] font-semibold transition-colors disabled:opacity-50"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Mark Not Loaded
                                    </button>
                                @endif

                                @if($canManageContainers)
                                    <button
                                        type="button"
                                        @@click="deleteContainer({{ $container->id }}, {{ $container->items->count() }}, {{ $containers->count() }})"
                                        :disabled="actionLoading"
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-semibold transition-colors disabled:opacity-50"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 001-1h4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="px-4 py-3">
                            @if($container->items->isEmpty())
                                <p class="text-xs text-slate-400">No items packed into this container yet.</p>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach($container->items as $containerItem)
                                        @php
                                            $line = $containerItem->manifestItem;
                                            $shipmentItem = $line?->shipmentItem;
                                            $shipment = $shipmentItem?->shipment;
                                            $isLooseContainer = strtolower((string) $container->container_type) === 'loose';
                                            $labelCode = $containerItem->label_barcode ?: $shipmentItem?->tracking_code;
                                            $scannedCodes = $line?->labelScans?->pluck('barcode_value')->filter() ?? collect();
                                            $lineFullyLoaded = $line && (int) $line->loaded_quantity >= (int) $line->expected_quantity;
                                            $printedLabelCodes = $shipmentItem?->warehouseReceiptItems
                                                ? $shipmentItem->warehouseReceiptItems
                                                    ->flatMap(fn ($receiptItem) => $receiptItem->labels)
                                                    ->sortBy(fn ($label) => [(int) ($label->label_index ?? 0), (int) $label->id])
                                                    ->pluck('barcode_value')
                                                    ->filter()
                                                    ->values()
                                                : collect();
                                        @endphp
                                        <div class="py-2.5 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-slate-900 truncate">{{ $shipmentItem?->description ?? 'Manifest item #' . $containerItem->transport_manifest_item_id }}</p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    {{ $shipment?->shipment_number ?? 'No shipment' }}
                                                </p>
                                                @if($isLooseContainer && $printedLabelCodes->isNotEmpty())
                                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                                        @foreach($printedLabelCodes as $printedLabelCode)
                                                            @php $isScanned = $lineFullyLoaded || $scannedCodes->contains($printedLabelCode); @endphp
                                                            <span @class([
                                                                'inline-flex items-center rounded-md px-2 py-0.5 font-mono text-[10px] font-semibold',
                                                                'bg-emerald-50 text-emerald-700' => $isScanned,
                                                                'bg-slate-100 text-slate-700' => !$isScanned,
                                                            ])>
                                                                {{ $printedLabelCode }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @elseif($isLooseContainer && $labelCode)
                                                    <p class="text-[10px] text-amber-600 mt-0.5">Printed labels not found. Reprint package labels before driver loading.</p>
                                                @elseif(!$isLooseContainer && $labelCode)
                                                    <p class="text-[10px] text-slate-400 mt-0.5 font-mono">{{ $labelCode }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-xs font-bold text-slate-800">{{ $containerItem->expected_quantity }}</p>
                                                <p class="text-[10px] text-slate-400">{{ str($containerItem->status)->title() }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if($container->loaded_at || $container->received_at || $container->notes)
                            <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/60 text-[11px] text-slate-500 space-y-1">
                                @if($container->loaded_at)
                                    <p><span class="font-semibold text-slate-600">Loaded:</span> {{ $container->loaded_at->format('M d, Y H:i') }}</p>
                                @endif
                                @if($container->received_at)
                                    <p><span class="font-semibold text-slate-600">Received:</span> {{ $container->received_at->format('M d, Y H:i') }}</p>
                                @endif
                                @if($container->notes)
                                    <p><span class="font-semibold text-slate-600">Notes:</span> {{ $container->notes }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
