@extends('warehouse.layouts.app')

@section('title', 'Sorting')
@section('page-title', 'Sorting')

@php
    $config = [
        'items_endpoint' => route('warehouse.sorting.items.data'),
        'batches_endpoint' => route('warehouse.sorting.batches.data'),
        'create_batch_endpoint' => route('warehouse.sorting.batches.store'),
        'add_items_endpoint' => route('warehouse.sorting.batches.items.store', ['sortBatch' => '__BATCH__']),
        'remove_item_endpoint' => route('warehouse.sorting.batches.items.destroy', ['sortBatch' => '__BATCH__', 'shipmentItem' => '__ITEM__']),
        'seal_batch_endpoint' => route('warehouse.sorting.batches.seal', ['sortBatch' => '__BATCH__']),
        'reopen_batch_endpoint' => route('warehouse.sorting.batches.reopen', ['sortBatch' => '__BATCH__']),
        'can_reopen_batches' => (bool) ($canReopenBatches ?? false),
        'dispatch_modes' => [
            ['value' => 'transfer', 'label' => 'Transfer to Warehouse'],
            ['value' => 'local_delivery', 'label' => 'Local Delivery'],
        ],
        'destinations' => $destinationWarehouses->values(),
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseSortingPage" data-warehouse-sorting-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Destination Sorting Batches</h2>
                <p class="text-sm text-slate-500 mt-1">Group received items by destination warehouse before manifest creation.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select x-model="newBatchDispatchMode" class="rounded-xl border border-slate-200 px-3 py-2 text-sm min-w-[210px]">
                    <template x-for="mode in dispatchModes" :key="mode.value">
                        <option :value="mode.value" x-text="mode.label"></option>
                    </template>
                </select>
                <select
                    x-show="newBatchDispatchMode === 'transfer'"
                    x-model="newBatchDestinationId"
                    class="rounded-xl border border-slate-200 px-3 py-2 text-sm min-w-[240px]"
                >
                    <option value="">Select destination warehouse</option>
                    <template x-for="destination in destinations" :key="destination.id">
                        <option :value="destination.id" x-text="`${destination.name}${destination.code ? ' (' + destination.code + ')' : ''}`"></option>
                    </template>
                </select>
                <button type="button" @@click="createBatch()" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50" :disabled="loading">
                    Create Batch
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3 bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/30 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200/60">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-slate-900">Eligible Received Items</h3>
                    <input type="text" x-model.debounce.350ms="search" @@input="loadItems()" placeholder="Search by shipment, item, tracking..." class="w-72 rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
                </div>
            </div>
            <div class="overflow-auto">
                <table class="min-w-full divide-y divide-slate-200/60 text-xs">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Select</th>
                            <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Shipment / Item</th>
                            <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Destination</th>
                            <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Qty</th>
                            <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Discrepancy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="row in items" :key="row.warehouse_receipt_item_id">
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-2.5">
                                    <input type="checkbox" :value="row.warehouse_receipt_item_id" x-model="selectedReceiptItemIds">
                                </td>
                                <td class="px-4 py-2.5">
                                    <p class="font-semibold text-slate-900" x-text="row.shipment_number"></p>
                                    <p class="text-slate-600" x-text="row.item_description"></p>
                                    <p class="text-[11px] text-slate-500" x-text="row.tracking_code || '-'"></p>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">
                                    <p class="font-semibold" x-text="row.destination?.recipient_name || '-'"></p>
                                    <p class="text-[11px] text-slate-500" x-text="`${row.destination?.region || '-'} / ${row.destination?.district || '-'}`"></p>
                                    <p class="text-[11px] text-slate-500" x-text="row.destination?.town || '-'"></p>
                                </td>
                                <td class="px-4 py-2.5 text-center font-semibold text-slate-800" x-text="row.received_quantity"></td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                          :class="row.discrepancy_type === 'none' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                                          x-text="row.discrepancy_type"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!loading && items.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No eligible items found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/30 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200/60">
                <h3 class="text-sm font-semibold text-slate-900">Sort Batches</h3>
            </div>
            <div class="p-4 space-y-3 max-h-[650px] overflow-y-auto">
                <template x-for="batch in batches" :key="batch.id">
                    <div class="rounded-2xl border border-slate-200 p-3 bg-white">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold text-slate-900" x-text="batch.batch_number"></p>
                                <p class="text-[11px] text-slate-500" x-text="batch.dispatch_mode_label || 'Transfer'"></p>
                                <p class="text-[11px] text-slate-500" x-text="batch.dispatch_mode === 'transfer' ? (batch.destination_warehouse?.name || '-') : 'Deliver from this warehouse'"></p>
                            </div>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                  :class="batch.status === 'sealed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-indigo-200 bg-indigo-50 text-indigo-700'"
                                  x-text="batch.status"></span>
                        </div>

                        <div class="mt-2 text-[11px] text-slate-600">
                            <span x-text="batch.items_count"></span> item(s)
                        </div>

                        <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                            <template x-for="item in batch.items" :key="item.sort_batch_item_id">
                                <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-2 py-1.5 bg-slate-50/70">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-medium text-slate-700 truncate" x-text="item.description"></p>
                                        <p class="text-[10px] text-slate-500 truncate" x-text="item.tracking_code || '-'"></p>
                                    </div>
                                    <button type="button" class="text-[10px] font-semibold text-rose-600 hover:text-rose-700"
                                            @@click="removeItem(batch.id, item.shipment_item_id)" x-show="batch.status === 'open'">
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <button type="button" class="rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                    @@click="addSelectedToBatch(batch.id)" :disabled="batch.status !== 'open' || selectedReceiptItemIds.length === 0">
                                Add Selected
                            </button>
                            <button type="button" class="rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                    @@click="sealBatch(batch.id)" :disabled="batch.status !== 'open'">
                                Seal
                            </button>
                            <button type="button" class="rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-50"
                                    @@click="reopenBatch(batch.id)" x-show="canReopenBatches && batch.status === 'sealed'" :disabled="loading">
                                Reopen
                            </button>
                        </div>
                    </div>
                </template>
                <div x-show="!loading && batches.length === 0" class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-xs text-slate-500">
                    No batches yet.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
