@extends('warehouse.layouts.app')

@section('title', 'Transport Manifests')
@section('page-title', 'Transport Manifests')

@php
    $config = [
        'data_endpoint' => route('warehouse.manifests.transport.data'),
        'create_endpoint' => route('warehouse.manifests.transport.store'),
        'assign_endpoint' => route('warehouse.manifests.transport.assign-driver', ['manifest' => '__MANIFEST__']),
        'dispatch_endpoint' => route('warehouse.manifests.transport.dispatch', ['manifest' => '__MANIFEST__']),
        'transport_drivers' => $transportDrivers->values(),
        'transfer_batches' => $transferBatches->values(),
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseTransportManifestsPage" data-warehouse-transport-manifests-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/30 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Outbound Transport Manifests</h2>
                <p class="text-sm text-slate-500 mt-1">Create manifests from sealed transfer batches, assign transport drivers, and dispatch.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select x-model="newManifestBatchId" class="rounded-xl border border-slate-200 px-3 py-2 text-sm min-w-[260px]">
                    <option value="">Select sealed transfer batch</option>
                    <template x-for="batch in transferBatches" :key="batch.id">
                        <option :value="batch.id" x-text="batch.batch_number"></option>
                    </template>
                </select>
                <button type="button" @@click="createManifest()" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50" :disabled="loading || !newManifestBatchId">
                    Create Manifest
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/30 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/60 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Manifests</h3>
            <div class="flex items-center gap-2">
                <input type="text" x-model.debounce.350ms="search" @@input="meta.current_page = 1; loadData()" placeholder="Search manifest, driver, shipment..." class="w-72 rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
                <select x-model="statusFilter" @@change="setStatusFilter(statusFilter)" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="assigned">Assigned</option>
                    <option value="loading">Loading</option>
                    <option value="in_transit">In Transit</option>
                    <option value="arrived">Arrived</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="overflow-auto">
            <table class="min-w-full divide-y divide-slate-200/60 text-xs">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Manifest</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Destination</th>
                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Status</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Driver</th>
                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Items</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-4 py-2.5">
                                <p class="font-semibold text-slate-900" x-text="row.manifest_number"></p>
                                <p class="text-[11px] text-slate-500" x-text="row.dispatched_at ? 'Dispatched: ' + row.dispatched_at : 'Not dispatched'"></p>
                            </td>
                            <td class="px-4 py-2.5 text-slate-700" x-text="row.destination_warehouse || '-'"></td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                      :class="statusClass(row.status)"
                                      x-text="row.status"></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <template x-if="row.driver_name">
                                    <div>
                                        <p class="font-medium text-slate-800" x-text="row.driver_name"></p>
                                        <p class="text-[11px] text-slate-500" x-text="row.driver_phone || '-'"></p>
                                    </div>
                                </template>
                                <template x-if="!row.driver_name">
                                    <div class="flex items-center gap-2">
                                        <select class="rounded-lg border border-slate-200 px-2 py-1 text-[11px]" x-model="selectedDriverByManifest[row.id]">
                                            <option value="">Select driver</option>
                                            <template x-for="driver in transportDrivers" :key="driver.id">
                                                <option :value="driver.id" x-text="`${driver.name} (${driver.vehicle_type || '-'})`"></option>
                                            </template>
                                        </select>
                                        <button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50"
                                                @@click="assignDriver(row.id)"
                                                :disabled="!selectedDriverByManifest[row.id] || loading">
                                            Assign
                                        </button>
                                    </div>
                                </template>
                            </td>
                            <td class="px-4 py-2.5 text-center font-semibold text-slate-800" x-text="row.items_count"></td>
                            <td class="px-4 py-2.5">
                                <button type="button"
                                        class="rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                        @@click="dispatchManifest(row.id)"
                                        :disabled="!canDispatch(row)">
                                    Dispatch
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No transport manifests found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-xs text-slate-600">
                    Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span> results
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-600">Rows per page</span>
                        <select x-model="perPage" @@change="setPerPage(perPage)" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="text-xs font-medium text-slate-600">
                        Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span>
                    </div>
                    <div class="flex space-x-1">
                        <button @@click="firstPage()" :disabled="meta.current_page <= 1" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">«</button>
                        <button @@click="previousPage()" :disabled="meta.current_page <= 1" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">‹</button>
                        <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">›</button>
                        <button @@click="lastPage()" :disabled="meta.current_page >= meta.last_page" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">»</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
