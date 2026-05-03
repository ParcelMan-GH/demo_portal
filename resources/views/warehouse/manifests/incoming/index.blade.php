@extends($layoutName ?? 'warehouse.layouts.app')

@section('title', $pageTitle ?? 'Incoming Manifests')
@section('page-title', $pageTitle ?? 'Incoming Manifests')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', $pageTitle ?? 'Incoming Manifests')

@php
    $config = [
        'endpoint' => $dataEndpoint ?? route('warehouse.manifests.incoming.data'),
        'showDestinationWarehouse' => $showDestinationWarehouse ?? false,
        'statuses' => [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'assigned', 'label' => 'Assigned'],
            ['value' => 'loading', 'label' => 'Loading'],
            ['value' => 'in_transit', 'label' => 'In Transit'],
            ['value' => 'arrived', 'label' => 'Arrived'],
            ['value' => 'received', 'label' => 'Received'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ],
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseIncomingManifestsPage" data-warehouse-incoming-manifests-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Incoming Manifests</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Receive transport manifests arriving from other warehouses.</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Records'"></span>
            </div>
        </div>

        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search manifests..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div class="relative w-full sm:w-56" x-data="{ open: false }">
                        <button type="button" @@click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                            <span x-text="statusFilterName"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display: none;">
                            <button type="button" @@click="setStatusFilter('', 'All statuses'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">All statuses</button>
                            <template x-for="status in statuses" :key="status.value">
                                <button type="button" @@click="setStatusFilter(status.value, status.label); open = false" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="statusFilter === status.value ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                    <span x-text="status.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            <div class="overflow-hidden rounded-xl border border-slate-200/50 relative">
                <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

                <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.manifest_number" @@click="sort('manifest_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">MANIFEST #</th>
                            <th x-show="visibleColumns.origin_warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ORIGIN</th>
                            <th x-show="visibleColumns.destination_warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESTINATION</th>
                            <th x-show="visibleColumns.driver_name" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DRIVER</th>
                            <th x-show="visibleColumns.status" @@click="sort('status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">STATUS</th>
                            <th x-show="visibleColumns.items_count" @@click="sort('items_count')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">ITEMS</th>
                            <th x-show="visibleColumns.arrived_at" @@click="sort('arrived_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">ARRIVED</th>
                            <th x-show="visibleColumns.received_at" @@click="sort('received_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">RECEIVED</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                        <tr x-show="!loading && rows.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No incoming manifests found.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.manifest_number" class="px-4 py-2.5 text-xs font-semibold text-slate-900" x-text="row.manifest_number || '-'"></td>
                                <td x-show="visibleColumns.origin_warehouse" class="px-4 py-2.5 text-xs text-slate-700" x-text="row.origin_warehouse || '-'"></td>
                                <td x-show="visibleColumns.destination_warehouse" class="px-4 py-2.5 text-xs text-slate-700" x-text="row.destination_warehouse || '-'"></td>
                                <td x-show="visibleColumns.driver_name" class="px-4 py-2.5 text-xs text-slate-700">
                                    <p class="font-medium" x-text="row.driver_name || '-'"></p>
                                </td>
                                <td x-show="visibleColumns.status" class="px-4 py-2.5 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusBadgeClass(row.status)" x-text="row.status"></span>
                                </td>
                                <td x-show="visibleColumns.items_count" class="px-4 py-2.5 text-center text-xs font-semibold text-slate-700" x-text="row.items_count || 0"></td>
                                <td x-show="visibleColumns.arrived_at" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.arrived_at || '-'"></td>
                                <td x-show="visibleColumns.received_at" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.received_at || '-'"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-2.5 text-center">
                                    <a :href="row.view_url" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

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
    </div>
</div>
@endsection
