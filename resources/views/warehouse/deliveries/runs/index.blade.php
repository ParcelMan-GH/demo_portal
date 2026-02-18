@extends('warehouse.layouts.app')

@section('title', 'Delivery Runs')
@section('page-title', 'Delivery Runs')

@php
    $config = [
        'data_endpoint' => route('warehouse.deliveries.runs.data'),
        'create_endpoint' => route('warehouse.deliveries.runs.store'),
        'assign_endpoint' => route('warehouse.deliveries.runs.assign-driver', ['run' => '__RUN__']),
        'dispatch_endpoint' => route('warehouse.deliveries.runs.dispatch', ['run' => '__RUN__']),
        'resend_code_endpoint' => route('warehouse.deliveries.runs.stops.resend-code', ['run' => '__RUN__', 'stop' => '__STOP__']),
        'delivery_drivers' => $deliveryDrivers->values(),
        'local_delivery_batches' => $localDeliveryBatches->values(),
        'can_reset_codes' => (bool) ($canResetCodes ?? false),
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseDeliveryRunsPage" data-warehouse-delivery-runs-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/30 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Delivery Runs</h2>
                <p class="text-sm text-slate-500 mt-1">Create runs from sealed local-delivery batches, assign delivery drivers, dispatch, and monitor recipient verification codes.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select x-model="newRunBatchId" class="rounded-xl border border-slate-200 px-3 py-2 text-sm min-w-[280px]">
                    <option value="">Select sealed local-delivery batch</option>
                    <template x-for="batch in localDeliveryBatches" :key="batch.id">
                        <option :value="batch.id" x-text="batch.batch_number"></option>
                    </template>
                </select>
                <button type="button" @@click="createRun()" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50" :disabled="loading || !newRunBatchId">
                    Create Run
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/30 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/60 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Runs</h3>
            <div class="flex items-center gap-2 flex-wrap">
                <input type="text" x-model.debounce.350ms="search" @@input="meta.current_page = 1; loadData()" placeholder="Search run, recipient, driver..." class="w-72 rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
                <select x-model="statusFilter" @@change="setStatusFilter(statusFilter)" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="assigned">Assigned</option>
                    <option value="out_for_delivery">Out For Delivery</option>
                    <option value="partially_delivered">Partially Delivered</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="min-w-full divide-y divide-slate-200/60 text-xs">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Run</th>
                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Status</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Driver</th>
                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Stops</th>
                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Items</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Timeline</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Recipient Stops</th>
                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="row in rows" :key="row.id">
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-4 py-2.5 min-w-[140px]">
                                <p class="font-semibold text-slate-900" x-text="row.run_number"></p>
                                <p class="text-[11px] text-slate-500" x-text="row.dispatched_at ? 'Dispatched: ' + row.dispatched_at : 'Not dispatched'"></p>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusClass(row.status)" x-text="row.status"></span>
                            </td>
                            <td class="px-4 py-2.5 min-w-[220px]">
                                <template x-if="row.driver_name">
                                    <div>
                                        <p class="font-medium text-slate-800" x-text="row.driver_name"></p>
                                        <p class="text-[11px] text-slate-500" x-text="row.driver_phone || '-'"></p>
                                    </div>
                                </template>
                                <template x-if="!row.driver_name">
                                    <div class="flex items-center gap-2">
                                        <select class="rounded-lg border border-slate-200 px-2 py-1 text-[11px]" x-model="selectedDriverByRun[row.id]">
                                            <option value="">Select driver</option>
                                            <template x-for="driver in deliveryDrivers" :key="driver.id">
                                                <option :value="driver.id" x-text="`${driver.name} (${driver.vehicle_type || '-'})`"></option>
                                            </template>
                                        </select>
                                        <button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50" @@click="assignDriver(row.id)" :disabled="!selectedDriverByRun[row.id] || loading">
                                            Assign
                                        </button>
                                    </div>
                                </template>
                            </td>
                            <td class="px-4 py-2.5 text-center font-semibold text-slate-800" x-text="row.stops_count"></td>
                            <td class="px-4 py-2.5 text-center font-semibold text-slate-800" x-text="row.items_count"></td>
                            <td class="px-4 py-2.5 min-w-[180px] text-[11px] text-slate-600">
                                <p><span class="font-medium">Assigned:</span> <span x-text="row.assigned_at || '-'"></span></p>
                                <p class="mt-1"><span class="font-medium">Dispatched:</span> <span x-text="row.dispatched_at || '-'"></span></p>
                                <p class="mt-1"><span class="font-medium">Completed:</span> <span x-text="row.completed_at || '-'"></span></p>
                            </td>
                            <td class="px-4 py-2.5 min-w-[260px]">
                                <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                                    <template x-for="stop in row.stops" :key="`stop-${row.id}-${stop.id}`">
                                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-semibold text-slate-800 truncate" x-text="stop.recipient_name || '-'"></p>
                                                    <p class="text-[10px] text-slate-500 truncate" x-text="stop.recipient_phone || '-'"></p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="stopStatusClass(stop.status)" x-text="stop.status"></span>
                                            </div>
                                            <div class="mt-1 flex items-center justify-between gap-2">
                                                <p class="text-[10px] text-slate-500">Attempts: <span x-text="`${stop.attempts}/${stop.max_attempts}`"></span></p>
                                                <button
                                                    type="button"
                                                    class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                                    @@click="resendCode(row.id, stop.id)"
                                                    :disabled="!canResendCode(row, stop)"
                                                >
                                                    Resend Code
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 min-w-[110px]">
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                    @@click="dispatchRun(row.id)"
                                    :disabled="!canDispatch(row)"
                                >
                                    Dispatch
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No delivery runs found.</td>
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

