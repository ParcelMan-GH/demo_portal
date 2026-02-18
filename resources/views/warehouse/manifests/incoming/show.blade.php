@extends('warehouse.layouts.app')

@section('title', 'Incoming Manifest Details')
@section('page-title', 'Incoming Manifest Details')

@php
    $items = $manifest->items->map(function ($line) {
        return [
            'shipment_item_id' => $line->shipment_item_id,
            'shipment_number' => $line->shipmentItem?->shipment?->shipment_number,
            'description' => $line->shipmentItem?->description,
            'tracking_code' => $line->shipmentItem?->tracking_code,
            'expected_quantity' => (int) $line->expected_quantity,
            'loaded_quantity' => (int) $line->loaded_quantity,
            'received_quantity' => (int) $line->received_quantity,
            'line_status' => $line->line_status,
            'notes' => $line->notes,
            'loaded_at' => optional($line->loaded_at)?->format('Y-m-d H:i:s'),
            'received_at' => optional($line->received_at)?->format('Y-m-d H:i:s'),
        ];
    })->values();

    $config = [
        'manifest_status' => $manifest->status,
        'items' => $items,
        'scan_receive_endpoint' => $manifestConfig['scan_receive_endpoint'],
        'finalize_endpoint' => $manifestConfig['finalize_endpoint'],
    ];

    $statusClass = match ($manifest->status) {
        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
        'loading' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'in_transit' => 'bg-violet-50 text-violet-700 border-violet-200',
        'arrived' => 'bg-amber-50 text-amber-700 border-amber-200',
        'received' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseIncomingManifestShowPage" data-warehouse-incoming-show-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative px-6 lg:px-8 py-6">
            <div class="mb-6">
                <a href="{{ route('warehouse.manifests.incoming.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="text-xs">Back to Incoming Manifests</span>
                </a>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                <div class="flex items-start gap-5 lg:flex-shrink-0">
                    <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-emerald-500/30 ring-4 ring-white/10">
                        {{ strtoupper(substr($manifest->manifest_number, 0, 1)) }}
                    </div>
                    <div class="space-y-2 min-w-0">
                        <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $manifest->manifest_number }}</h1>
                        <p class="text-slate-300 text-sm truncate">From {{ $manifest->originWarehouse?->name ?? '-' }} to {{ $manifest->destinationWarehouse?->name ?? '-' }}</p>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-300">
                            <span>{{ $manifest->assignedDriver?->name ?? 'No driver assigned' }}</span>
                            <span>{{ $manifest->assignedDriver?->phone ?? '' }}</span>
                        </div>
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                            {{ str($manifest->status)->replace('_', ' ')->title() }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap lg:ml-auto lg:self-start">
                    <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center">
                        <p class="text-base lg:text-lg font-bold text-white leading-none">{{ $manifest->items->count() }}</p>
                        <p class="text-[9px] text-slate-400 mt-0.5 font-medium">Items</p>
                    </div>
                    <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center">
                        <p class="text-base lg:text-lg font-bold text-white leading-none">{{ optional($manifest->arrived_at)?->format('M d') ?? '-' }}</p>
                        <p class="text-[9px] text-slate-400 mt-0.5 font-medium">Arrived</p>
                    </div>
                    <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/5 rounded-xl border border-white/10 p-2 flex flex-col items-center justify-center text-center">
                        <p class="text-base lg:text-lg font-bold text-white leading-none">{{ optional($manifest->received_at)?->format('M d') ?? '-' }}</p>
                        <p class="text-[9px] text-slate-400 mt-0.5 font-medium">Received</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Incoming Manifest Lines</h2>
                    <p class="text-xs text-slate-500 mt-1">Scan and confirm received quantities for each shipment item.</p>
                </div>
                <button
                    type="button"
                    @@click="showFinalizeModal = true"
                    :disabled="isFinalized() || loading"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                >
                    Finalize Receipt
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200/50">
                <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment Item</th>
                            <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Expected</th>
                            <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Loaded</th>
                            <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Received</th>
                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Line Status</th>
                            <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Notes</th>
                            <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                        <template x-for="row in items" :key="row.shipment_item_id">
                            <tr class="hover:bg-slate-50/70 align-top">
                                <td class="px-4 py-2.5 text-xs text-slate-700 min-w-[260px]">
                                    <p class="font-semibold text-slate-900" x-text="row.description || '-'"></p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Shipment: <span x-text="row.shipment_number || '-'"></span></p>
                                    <p class="text-[11px] text-slate-500">Tracking: <span x-text="row.tracking_code || '-'"></span></p>
                                </td>
                                <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-700" x-text="row.expected_quantity"></td>
                                <td class="px-4 py-2.5 text-center text-xs font-semibold text-slate-700" x-text="row.loaded_quantity"></td>
                                <td class="px-4 py-2.5 text-center">
                                    <input
                                        type="number"
                                        min="0"
                                        x-model.number="row.received_quantity"
                                        :disabled="isFinalized()"
                                        class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-xs text-center"
                                    >
                                </td>
                                <td class="px-4 py-2.5 min-w-[160px]">
                                    <select x-model="row.line_status" :disabled="isFinalized()" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs">
                                        <option value="pending">Pending</option>
                                        <option value="loaded">Loaded</option>
                                        <option value="received">Received</option>
                                        <option value="short">Short</option>
                                        <option value="excess">Excess</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusClass(row.line_status)" x-text="row.line_status"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 min-w-[200px]">
                                    <textarea x-model="row.notes" :disabled="isFinalized()" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs" rows="2" placeholder="Optional notes"></textarea>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <button
                                        type="button"
                                        @@click="saveLine(row.shipment_item_id)"
                                        :disabled="isFinalized() || loading"
                                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                    >
                                        Save
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="showFinalizeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @@click="showFinalizeModal = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">Finalize Incoming Receipt</h3>
                <p class="text-xs text-slate-500 mt-1">Finalize this incoming manifest receipt after all lines are scanned.</p>
            </div>
            <div class="px-5 py-4 space-y-3">
                <label class="text-xs font-semibold text-slate-700">Notes (optional)</label>
                <textarea x-model="finalizeNotes" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Add final receiving notes"></textarea>
            </div>
            <div class="px-5 py-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" @@click="showFinalizeModal = false" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" @@click="finalizeReceipt()" :disabled="loading" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Finalize</button>
            </div>
        </div>
    </div>
</div>
@endsection

