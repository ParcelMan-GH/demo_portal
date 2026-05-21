@extends('admin.layouts.app')

@section('title', 'Pickup Assignments')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Pickup Assignments')

@section('content')

@php
    $pickupsConfig = [
        'endpoint' => route('admin.pickups.data'),
        'availableDriversEndpoint' => route('admin.assignments.available-drivers'),
        'availableWarehousesEndpoint' => route('admin.assignments.available-warehouses'),
        'updateEndpointTemplate' => url('/admin/assignments/__ID__/update'),
        'cancelEndpointTemplate' => url('/admin/assignments/__ID__/cancel'),
        'receiveEndpointTemplate' => url('/admin/assignments/__ID__/receive'),
        'statuses' => $statuses,
    ];
@endphp

<div class="space-y-6" x-data="pickupsTable" data-pickups-config='@json($pickupsConfig)'>
    <!-- Datatable Card -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-100">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Pickup Assignments</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Track driver pickup assignments and statuses</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-violet-100 text-violet-700" x-text="meta.total + ' Total'">
                </span>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Search + Status + Date Range -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search shipment, driver, vendor..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-56">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="statusFilterName || 'All statuses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="statusFilter = ''; statusFilterName = 'All statuses'; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="statusFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All statuses</span>
                            </button>
                            @foreach($statuses as $status)
                            <button
                                type="button"
                                @@click="statusFilter = '{{ $status['value'] }}'; statusFilterName = '{{ $status['label'] }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '{{ $status['value'] }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="statusFilter === '{{ $status['value'] }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $status['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-56">
                            <input
                                type="text"
                                x-ref="assignedRange"
                                placeholder="Assigned date range"
                                class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 cursor-pointer"
                                readonly
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Right Controls -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <!-- Customize Columns -->
                    <div x-data="{ open: false }" class="relative">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)"
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                    <span x-text="col.label"></span>
                                    <svg x-show="visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Export -->
                    <div x-data="{ open: false }" class="relative">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                             style="display: none;">
                            <button type="button" @@click="downloadCSV(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                CSV
                            </button>
                            <div class="border-t border-slate-200/50 my-1"></div>
                            <button type="button" @@click="printData(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="px-6 py-4">
            <div class="rounded-xl border border-slate-200/50 relative">
            <!-- Loading overlay -->
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>

            <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th x-show="visibleColumns.shipment" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            SHIPMENT #
                        </th>
                        <th x-show="visibleColumns.vendor" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            VENDOR
                        </th>
                        <th x-show="visibleColumns.driver" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            DRIVER
                        </th>
                        <th x-show="visibleColumns.warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            TARGET WAREHOUSE
                        </th>
                        <th x-show="visibleColumns.status" @@click="sort('status')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                STATUS
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                </svg>
                            </div>
                        </th>
                        <th x-show="visibleColumns.assigned_at" @@click="sort('assigned_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                            <div class="flex items-center">
                                ASSIGNED AT
                                <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <th x-show="visibleColumns.assigned_by" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            ASSIGNED BY
                        </th>
                        <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                            ACTIONS
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-transparent divide-y divide-slate-100/50">
                    <template x-if="assignments.length === 0 && !loading">
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 text-xs">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                    </svg>
                                    <span>No pickup assignments found</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="a in assignments" :key="a.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.shipment" class="px-4 py-2.5 whitespace-nowrap">
                                <a :href="`/admin/orders/${a.shipment_id}`" class="text-xs font-semibold text-slate-900 hover:text-violet-600 transition-colors" x-text="a.shipment_number"></a>
                            </td>
                            <td x-show="visibleColumns.vendor" class="px-4 py-2.5 whitespace-nowrap">
                                <span class="text-xs text-slate-600" x-text="a.vendor_name"></span>
                            </td>
                            <td x-show="visibleColumns.driver" class="px-4 py-2.5 whitespace-nowrap">
                                <div>
                                    <span class="text-xs font-medium text-slate-900" x-text="a.driver_name"></span>
                                    <span class="block text-[10px] text-slate-500" x-text="a.driver_phone"></span>
                                </div>
                            </td>
                            <td x-show="visibleColumns.warehouse" class="px-4 py-2.5 whitespace-nowrap">
                                <span class="text-xs text-slate-600" x-text="a.target_warehouse"></span>
                            </td>
                            <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      :class="statusBadgeClass(a.status)"
                                      x-text="a.status_label"></span>
                            </td>
                            <td x-show="visibleColumns.assigned_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(a.assigned_at)"></td>
                            <td x-show="visibleColumns.completed_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(a.completed_at)"></td>
                            <td x-show="visibleColumns.assigned_by" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="a.assigned_by"></td>
                            <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- View Shipment -->
                                    <a :href="`/admin/orders/${a.shipment_id}`"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                                       title="View Shipment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <!-- Edit (only when assigned) -->
                                    <button x-show="a.status === 'assigned'"
                                            type="button"
                                            @@click="openEditModal(a)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Edit Assignment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                    <!-- Cancel (when not completed/cancelled) -->
                                    <button x-show="!['completed','cancelled'].includes(a.status)"
                                            type="button"
                                            @@click="openCancelModal(a)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Cancel Assignment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    <!-- Receive (when completed and not received) -->
                                    <button x-show="a.status === 'completed' && !a.received_at"
                                            type="button"
                                            @@click="openReceiveModal(a)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                            title="Mark as Received">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="text-xs text-slate-600">
                        Showing
                        <span x-text="meta.from"></span>
                        to
                        <span x-text="meta.to"></span>
                        of
                        <span x-text="meta.total"></span>
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
                                    <button type="button" @@click="perPage = 10; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                    <button type="button" @@click="perPage = 25; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                    <button type="button" @@click="perPage = 50; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                    <button type="button" @@click="perPage = 100; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                </div>
                            </div>
                        </div>

                        <div class="text-xs font-medium text-slate-600">
                            Page
                            <span x-text="meta.current_page"></span>
                            of
                            <span x-text="meta.last_page"></span>
                        </div>

                        <div class="flex space-x-1">
                            <button
                                @@click="firstPage()"
                                :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                </svg>
                            </button>

                            <button
                                @@click="previousPage()"
                                :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>

                            <button
                                @@click="nextPage()"
                                :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                            <button
                                @@click="lastPage()"
                                :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
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

<!-- Edit Assignment Modal -->
<div x-show="showEditModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="showEditModal = false">
    <div x-show="showEditModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="showEditModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showEditModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @@click.stop class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50">
            <div class="px-6 py-5 border-b border-slate-200/50">
                <h3 class="text-lg font-bold text-slate-900">Edit Assignment</h3>
                <p class="text-sm text-slate-500 mt-1">Change driver or target warehouse</p>
            </div>
            <form @@submit.prevent="saveEdit()">
                <div class="space-y-5 px-6 py-6">
                    <!-- Driver Select -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Driver</label>
                        <select x-model="editForm.driver_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition-all">
                            <template x-for="d in availableDrivers" :key="d.id">
                                <option :value="d.id" x-text="d.name + ' (' + d.phone + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <!-- Warehouse Select -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse</label>
                        <select x-model="editForm.target_warehouse_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition-all">
                            <template x-for="w in availableWarehouses" :key="w.id">
                                <option :value="w.id" x-text="w.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200/50 px-6 py-4">
                    <button type="button" @@click="showEditModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit" :disabled="editSaving" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50">
                        <svg x-show="editSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Assignment Modal -->
<div x-show="showCancelModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="showCancelModal = false">
    <div x-show="showCancelModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="showCancelModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showCancelModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @@click.stop class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50">
            <div class="px-6 py-5 border-b border-slate-200/50">
                <h3 class="text-lg font-bold text-rose-700">Cancel Assignment</h3>
                <p class="text-sm text-slate-500 mt-1">This will cancel the pickup assignment for <strong x-text="cancelTarget?.shipment_number"></strong></p>
            </div>
            <form @@submit.prevent="confirmCancel()">
                <div class="px-6 py-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Cancellation Reason <span class="text-rose-500">*</span></label>
                    <textarea x-model="cancelReason" rows="3" required minlength="3"
                              class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-400 transition-all placeholder-slate-400"
                              placeholder="Reason for cancellation..."></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200/50 px-6 py-4">
                    <button type="button" @@click="showCancelModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">Back</button>
                    <button type="submit" :disabled="cancelSaving" class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50">
                        <svg x-show="cancelSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Cancel Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receive at Warehouse Modal -->
<div x-show="showReceiveModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="showReceiveModal = false">
    <div x-show="showReceiveModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="showReceiveModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showReceiveModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @@click.stop class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50">
            <div class="px-6 py-5 border-b border-slate-200/50">
                <h3 class="text-lg font-bold text-emerald-700">Receive at Warehouse</h3>
                <p class="text-sm text-slate-500 mt-1">Confirm warehouse receipt for <strong x-text="receiveTarget?.shipment_number"></strong></p>
            </div>
            <form @@submit.prevent="confirmReceive()">
                <div class="space-y-5 px-6 py-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Received at Warehouse</label>
                        <select x-model="receiveForm.received_warehouse_id" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-all">
                            <option value="">Same as target warehouse</option>
                            <template x-for="w in availableWarehouses" :key="w.id">
                                <option :value="w.id" x-text="w.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                        <textarea x-model="receiveForm.receive_notes" rows="3"
                                  class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-all placeholder-slate-400"
                                  placeholder="Any notes about the receipt..."></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200/50 px-6 py-4">
                    <button type="button" @@click="showReceiveModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit" :disabled="receiveSaving" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50">
                        <svg x-show="receiveSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Confirm Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>{{-- end x-data="pickupsTable" --}}

@endsection
