@extends('warehouse.layouts.app')

@section('title', 'Delivery Runs')
@section('page-title', 'Delivery Runs')

@php
    $config = [
        'data_endpoint' => route('warehouse.deliveries.runs.data'),
        'create_endpoint' => route('warehouse.deliveries.runs.store'),
        'eligible_items_endpoint' => route('warehouse.deliveries.runs.eligible-items'),
        'create_from_items_endpoint' => route('warehouse.deliveries.runs.store-from-items'),
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

    {{-- Main Table Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Delivery Runs</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Manage delivery runs, assign drivers, and monitor stops.</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Records'"></span>
            </div>
        </div>

        {{-- Filters --}}
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search run, driver, recipient..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    {{-- Status Filter --}}
                    <div class="relative w-full sm:w-52" x-data="{ open: false }">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="statusFilterName"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            x-cloak
                            @@click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="setStatusFilter('', 'All statuses'); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <span>All statuses</span>
                            </button>
                            <template x-for="status in statuses" :key="status.value">
                                <button
                                    type="button"
                                    @@click="setStatusFilter(status.value, status.label); open = false"
                                    class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="statusFilter === status.value ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                                >
                                    <span x-text="status.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- View / Export --}}
                <div class="flex flex-wrap items-center justify-end gap-3">
                    {{-- View Column Toggle --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button"
                                        @@click="toggleColumn(column.key)"
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Export --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">CSV</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">PDF</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">Print</button>
                        </div>
                    </div>

                    {{-- Create Run --}}
                    <button
                        type="button"
                        @@click="showCreateModal = true; createMode = 'batch'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Run
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="px-6 py-4">
            <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
                <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

                <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.run_number" @@click="sort('run_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    RUN #
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'run_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.status" @@click="sort('status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center">
                                    STATUS
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.driver_name" @@click="sort('driver_name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    DRIVER
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'driver_name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.stops_count" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STOPS</th>
                            <th x-show="visibleColumns.items_count" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ITEMS</th>
                            <th x-show="visibleColumns.assigned_at" @@click="sort('assigned_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    ASSIGNED AT
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.dispatched_at" @@click="sort('dispatched_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    DISPATCHED AT
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'dispatched_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.completed_at" @@click="sort('completed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    COMPLETED AT
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No delivery runs found.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.run_number" class="px-4 py-2.5 text-xs font-semibold text-slate-900" x-text="row.run_number || '-'"></td>
                                <td x-show="visibleColumns.status" class="px-4 py-2.5 text-xs text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm shadow-sm"
                                        :class="statusBadgeClass(row.status)"
                                        x-text="row.status || '-'"
                                    ></span>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="px-4 py-2.5 text-xs text-slate-600 min-w-[200px]">
                                    <template x-if="row.driver_name">
                                        <div>
                                            <p class="font-medium text-slate-800" x-text="row.driver_name"></p>
                                            <p class="text-[11px] text-slate-500" x-text="row.driver_phone || '-'"></p>
                                        </div>
                                    </template>
                                    <template x-if="!row.driver_name">
                                        <span class="text-slate-400 italic">Not assigned</span>
                                    </template>
                                </td>
                                <td x-show="visibleColumns.stops_count" class="px-4 py-2.5 text-xs text-center font-semibold text-slate-800" x-text="row.stops_count"></td>
                                <td x-show="visibleColumns.items_count" class="px-4 py-2.5 text-xs text-center font-semibold text-slate-800" x-text="row.items_count"></td>
                                <td x-show="visibleColumns.assigned_at" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.assigned_at || '-'"></td>
                                <td x-show="visibleColumns.dispatched_at" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.dispatched_at || '-'"></td>
                                <td x-show="visibleColumns.completed_at" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.completed_at || '-'"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-2.5 text-center text-xs">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a
                                            :href="row.view_url"
                                            class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2 py-1 text-[10px] font-semibold text-orange-800 hover:bg-orange-50 transition-colors"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </a>
                                        <button
                                            type="button"
                                            x-show="canDispatch(row)"
                                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700 hover:bg-emerald-100"
                                            @@click="dispatchRun(row.id)"
                                            :disabled="loading"
                                        >
                                            Dispatch
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {{-- Expandable Stops Row --}}
                            <tr x-show="expandedRunId === row.id" x-cloak class="bg-slate-50/40">
                                <td :colspan="visibleColumnCount()" class="px-4 py-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <template x-for="stop in row.stops" :key="`stop-${row.id}-${stop.id}`">
                                            <div class="rounded-xl border border-slate-200/70 bg-white/80 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-slate-800 truncate" x-text="stop.recipient_name || '-'"></p>
                                                        <p class="text-[11px] text-slate-500 truncate" x-text="stop.recipient_phone || '-'"></p>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span x-show="stop.verification_skipped" class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700" title="OTP verification was skipped">
                                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                                            Unverified
                                                        </span>
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="stopStatusClass(stop.status)" x-text="stop.status"></span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex items-center justify-between gap-2">
                                                    <p class="text-[11px] text-slate-500">Attempts: <span x-text="`${stop.attempts}/${stop.max_attempts}`"></span></p>
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
                            </tr>
                        </template>
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing
                            <span x-text="meta.from || 0"></span>
                            to
                            <span x-text="meta.to || 0"></span>
                            of
                            <span x-text="meta.total || 0"></span>
                            results
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button
                                        type="button"
                                        @@click="open = !open"
                                        class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"
                                    >
                                        <span x-text="perPage"></span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        @@click.away="open = false"
                                        x-transition
                                        class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                        style="display: none;"
                                    >
                                        <button type="button" @@click="setPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                        <button type="button" @@click="setPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                        <button type="button" @@click="setPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        <button type="button" @@click="setPerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs font-medium text-slate-600">
                                Page
                                <span x-text="meta.current_page || 1"></span>
                                of
                                <span x-text="meta.last_page || 1"></span>
                            </div>

                            <div class="flex space-x-1">
                                <button
                                    @@click="firstPage()"
                                    :disabled="meta.current_page <= 1"
                                    :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <button
                                    @@click="previousPage()"
                                    :disabled="meta.current_page <= 1"
                                    :class="meta.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <button
                                    @@click="nextPage()"
                                    :disabled="meta.current_page >= meta.last_page"
                                    :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>

                                <button
                                    @@click="lastPage()"
                                    :disabled="meta.current_page >= meta.last_page"
                                    :class="meta.current_page >= meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                    class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Run Modal --}}
    <template x-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @@click.self="showCreateModal = false" @@keydown.escape.window="showCreateModal = false">
            <div class="w-full max-h-[85vh] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col" @@click.stop
                 :class="createMode === 'items' ? 'max-w-4xl' : 'max-w-lg'">

                {{-- Modal Header --}}
                <div class="px-6 py-5 border-b border-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Create Delivery Run</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Choose how you'd like to create a new delivery run.</p>
                            </div>
                        </div>
                        <button type="button" @@click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Mode Tabs --}}
                    <div class="flex mt-4 bg-slate-100/80 rounded-xl p-1 gap-1">
                        <button
                            type="button"
                            @@click="createMode = 'batch'"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="createMode === 'batch' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            From Batch
                        </button>
                        <button
                            type="button"
                            @@click="createMode = 'items'; loadEligibleItems()"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="createMode === 'items' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            From Items
                        </button>
                    </div>
                </div>

                {{-- FROM BATCH --}}
                <div x-show="createMode === 'batch'" class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Sealed Local-Delivery Batch</label>
                            <p class="text-xs text-slate-500 mb-3">Select a sealed batch to automatically create a delivery run with all its items.</p>
                            <select x-model="newRunBatchId" class="w-full rounded-xl border border-slate-200/70 bg-white/70 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                                <option value="">Select a batch...</option>
                                <template x-for="batch in localDeliveryBatches" :key="batch.id">
                                    <option :value="batch.id" x-text="batch.batch_number"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="localDeliveryBatches.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-6 text-center">
                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm font-medium text-slate-600">No sealed batches available</p>
                            <p class="text-xs text-slate-500 mt-1">Seal a local-delivery sort batch first, or use "From Items" mode.</p>
                        </div>
                    </div>
                </div>

                {{-- FROM ITEMS --}}
                <template x-if="createMode === 'items'">
                    <div class="flex flex-col flex-1 min-h-0">
                        <div class="px-6 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                            <div class="relative flex-1 max-w-xs">
                                <input type="text" x-model.debounce.350ms="eligibleSearch" @@input="loadEligibleItems()" placeholder="Search shipment, item, recipient..." class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 text-sm placeholder-slate-400">
                                <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                <span x-text="selectedReceiptItemIds.length"></span>&nbsp;selected
                            </span>
                        </div>

                        <div class="flex-1 overflow-auto">
                            <table class="min-w-full divide-y divide-slate-200/60 text-xs">
                                <thead class="bg-slate-50/70 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left w-10">
                                            <input type="checkbox" @@change="toggleAllEligible($event)" :checked="selectedReceiptItemIds.length > 0 && selectedReceiptItemIds.length === eligibleItems.length" class="rounded border-slate-300">
                                        </th>
                                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Shipment / Item</th>
                                        <th class="px-4 py-2 text-left text-[10px] uppercase tracking-wider font-semibold text-slate-500">Destination</th>
                                        <th class="px-4 py-2 text-center text-[10px] uppercase tracking-wider font-semibold text-slate-500">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in eligibleItems" :key="row.warehouse_receipt_item_id">
                                        <tr class="hover:bg-slate-50/70 cursor-pointer" @@click="toggleItem(row.warehouse_receipt_item_id)">
                                            <td class="px-4 py-2.5">
                                                <input type="checkbox" :value="row.warehouse_receipt_item_id" x-model.number="selectedReceiptItemIds" @@click.stop class="rounded border-slate-300">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <p class="font-semibold text-slate-900" x-text="row.shipment_number"></p>
                                                <p class="text-slate-600" x-text="row.item_description"></p>
                                                <p class="text-[11px] text-slate-500" x-text="row.tracking_code || '-'"></p>
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700">
                                                <p class="font-medium" x-text="row.destination?.recipient_name || '-'"></p>
                                                <p class="text-[11px] text-slate-500" x-text="(row.destination?.region || '-') + ' / ' + (row.destination?.district || '-')"></p>
                                                <p class="text-[11px] text-slate-500" x-text="row.destination?.town || '-'"></p>
                                            </td>
                                            <td class="px-4 py-2.5 text-center font-semibold text-slate-800" x-text="row.received_quantity"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!eligibleLoading && eligibleItems.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center">
                                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="text-sm font-medium text-slate-600">No eligible items</p>
                                            <p class="text-xs text-slate-500 mt-1">All received items have already been assigned to delivery runs.</p>
                                        </td>
                                    </tr>
                                    <tr x-show="eligibleLoading">
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-xs">Loading items...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-slate-200/60 flex items-center justify-between gap-3">
                    <button type="button" @@click="showCreateModal = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>

                    {{-- Batch mode button --}}
                    <button
                        x-show="createMode === 'batch'"
                        type="button"
                        @@click="createRun()"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors"
                        :disabled="loading || !newRunBatchId"
                    >
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Create from Batch
                    </button>

                    {{-- Items mode button --}}
                    <button
                        x-show="createMode === 'items'"
                        type="button"
                        @@click="createRunFromItems()"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors"
                        :disabled="loading || selectedReceiptItemIds.length === 0"
                    >
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Create from Items (<span x-text="selectedReceiptItemIds.length"></span>)
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
