@extends('admin.layouts.app')

@section('title', $batch->batch_number)
@section('breadcrumb-parent', 'Sort Batches')
@section('breadcrumb-current', $batch->batch_number)

@section('content')

<div class="space-y-6">

    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.sort-batches.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/70 hover:bg-white border border-slate-200/70 text-slate-700 text-sm font-medium transition-all shadow-sm hover:shadow">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="text-xs">Back to Sort Batches</span>
        </a>
    </div>

    <!-- Hero / Info Card -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="sbgrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#sbgrid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <!-- Icon + Batch Number -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>

                        <div class="space-y-1.5 min-w-0">
                            <h1 class="text-2xl font-bold text-white">{{ $batch->batch_number }}</h1>

                            <!-- Status + Mode Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">Open</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-500/30 text-slate-300 ring-1 ring-slate-400/30">Sealed</span>
                                @endif

                                @if($batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_TRANSFER)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 ring-1 ring-blue-400/30">Transfer</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/30">Local Delivery</span>
                                @endif
                            </div>

                            <!-- Warehouse Route -->
                            <div class="flex items-center gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span>{{ $batch->originWarehouse?->name ?? '—' }}</span>
                                @if($batch->destinationWarehouse)
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span>{{ $batch->destinationWarehouse->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Meta Info Grid -->
                    <div class="lg:ml-auto grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Created By</div>
                            <div class="mt-1 text-sm font-semibold text-white">{{ $batch->createdBy?->name ?? '—' }}</div>
                        </div>

                        @if($batch->status === \App\Models\SortBatch::STATUS_SEALED)
                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Sealed By</div>
                            <div class="mt-1 text-sm font-semibold text-white">{{ $batch->sealedBy?->name ?? '—' }}</div>
                        </div>
                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Sealed At</div>
                            <div class="mt-1 text-sm font-semibold text-white">{{ $batch->sealed_at?->format('d M Y, H:i') ?? '—' }}</div>
                        </div>
                        @endif

                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Created At</div>
                            <div class="mt-1 text-sm font-semibold text-white">{{ $batch->created_at->format('d M Y, H:i') }}</div>
                        </div>

                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Items</div>
                            <div class="mt-1 text-sm font-semibold text-white">{{ $batch->activeItems->count() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($batch->notes)
                <div class="mt-5 p-4 bg-white/5 rounded-xl border border-white/10">
                    <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Notes</div>
                    <p class="text-sm text-slate-300">{{ $batch->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Linked Manifest / Delivery Run Cards -->
    @if($batch->transportManifest || $batch->deliveryRun)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        @if($batch->transportManifest)
        @php $manifest = $batch->transportManifest; @endphp
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/80 shadow-lg shadow-slate-300/40 p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Linked Transport Manifest</div>
                        <div class="text-sm font-bold text-slate-900 mt-0.5">{{ $manifest->manifest_number }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @php
                        $manifestStatusColors = [
                            'draft'      => 'bg-slate-100 text-slate-700',
                            'assigned'   => 'bg-blue-100 text-blue-700',
                            'loading'    => 'bg-amber-100 text-amber-700',
                            'in_transit' => 'bg-violet-100 text-violet-700',
                            'arrived'    => 'bg-indigo-100 text-indigo-700',
                            'received'   => 'bg-emerald-100 text-emerald-700',
                            'cancelled'  => 'bg-rose-100 text-rose-700',
                        ];
                        $manifestStatusLabel = [
                            'draft'      => 'Draft',
                            'assigned'   => 'Assigned',
                            'loading'    => 'Loading',
                            'in_transit' => 'In Transit',
                            'arrived'    => 'Arrived',
                            'received'   => 'Received',
                            'cancelled'  => 'Cancelled',
                        ];
                        $mColor = $manifestStatusColors[$manifest->status] ?? 'bg-slate-100 text-slate-700';
                        $mLabel = $manifestStatusLabel[$manifest->status] ?? ucfirst($manifest->status);
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $mColor }}">{{ $mLabel }}</span>
                    <a
                        href="{{ route('admin.transport-manifests.show', $manifest->id) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors"
                    >
                        View
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        @if($batch->deliveryRun)
        @php $run = $batch->deliveryRun; @endphp
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/80 shadow-lg shadow-slate-300/40 p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-50">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Linked Delivery Run</div>
                        <div class="text-sm font-bold text-slate-900 mt-0.5">{{ $run->run_number }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @php
                        $runStatusColors = [
                            'draft'               => 'bg-slate-100 text-slate-700',
                            'assigned'            => 'bg-blue-100 text-blue-700',
                            'out_for_delivery'    => 'bg-amber-100 text-amber-700',
                            'partially_delivered' => 'bg-violet-100 text-violet-700',
                            'completed'           => 'bg-emerald-100 text-emerald-700',
                            'cancelled'           => 'bg-rose-100 text-rose-700',
                        ];
                        $runStatusLabel = [
                            'draft'               => 'Draft',
                            'assigned'            => 'Assigned',
                            'out_for_delivery'    => 'Out for Delivery',
                            'partially_delivered' => 'Partially Delivered',
                            'completed'           => 'Completed',
                            'cancelled'           => 'Cancelled',
                        ];
                        $rColor = $runStatusColors[$run->status] ?? 'bg-slate-100 text-slate-700';
                        $rLabel = $runStatusLabel[$run->status] ?? ucfirst($run->status);
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $rColor }}">{{ $rLabel }}</span>
                    <a
                        href="{{ route('admin.delivery-runs.show', $run->id) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-colors"
                    >
                        View
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

    <!-- Items Table -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Batch Items</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $batch->activeItems->count() }} {{ Str::plural('item', $batch->activeItems->count()) }} in this batch
                    </p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            @if($batch->activeItems->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                    <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-sm font-medium">No items in this batch</p>
                </div>
            @else
                <div class="rounded-xl border border-slate-200/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">SHIPMENT #</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">TRACKING CODE</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESCRIPTION</th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">QTY</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">RECIPIENT</th>
                                    <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESTINATION</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                @foreach($batch->activeItems as $index => $item)
                                @php $si = $item->shipmentItem; @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <!-- Row # -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-slate-500 font-medium">
                                        {{ $index + 1 }}
                                    </td>

                                    <!-- Shipment Number -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        @if($si && $si->shipment)
                                            <a
                                                href="{{ route('admin.shipments.show', $si->shipment->id) }}"
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                            >{{ $si->shipment->shipment_number }}</a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <!-- Tracking Code -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        @if($si && $si->tracking_code)
                                            <span class="font-mono text-xs text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded">{{ $si->tracking_code }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 py-2.5 text-slate-700 max-w-xs truncate">
                                        {{ $si?->description ?? '—' }}
                                    </td>

                                    <!-- Quantity -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700">
                                            {{ $item->quantity_allocated ?? $si?->quantity ?? '—' }}
                                        </span>
                                    </td>

                                    <!-- Recipient Name -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-medium text-slate-900">{{ $si?->delivery_recipient_name ?? '—' }}</div>
                                        @if($si?->delivery_recipient_phone)
                                            <div class="text-[10px] text-slate-500">{{ $si->delivery_recipient_phone }}</div>
                                        @endif
                                    </td>

                                    <!-- Destination Town -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600">
                                        {{ $si?->delivery_town ?? '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
