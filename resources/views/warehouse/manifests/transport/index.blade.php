@extends('warehouse.layouts.app')

@section('title', 'Transport Manifests')
@section('page-title', 'Transport Manifests')

@php
    $config = [
        'data_endpoint' => route('warehouse.manifests.transport.data'),
        'create_endpoint' => route('warehouse.manifests.transport.store'),
        'transfer_batches' => $transferBatches->values(),
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseTransportManifestsPage" data-warehouse-transport-manifests-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 shrink-0">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Transport Manifests</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Outbound manifests created from sealed transfer batches.</p>
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:items-end">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Records'"></span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <select
                            x-model="newManifestBatchId"
                            class="rounded-xl border border-slate-200/70 bg-white/70 backdrop-blur-sm px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 min-w-[220px]"
                        >
                            <option value="">Select sealed transfer batch</option>
                            <template x-for="batch in transferBatches" :key="batch.id">
                                <option :value="batch.id" x-text="batch.batch_number"></option>
                            </template>
                        </select>
                        <button
                            type="button"
                            @@click="createManifest()"
                            :disabled="loading || !newManifestBatchId"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Manifest
                        </button>
                    </div>
                </div>
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
                            placeholder="Search manifest, driver..."
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

                    {{-- Date Range --}}
                    <div class="relative w-full sm:w-52">
                        <input
                            type="text"
                            x-ref="dateRange"
                            placeholder="Date range"
                            class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 cursor-pointer"
                            readonly
                        >
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">

                    {{-- View (column visibility) --}}
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
                            <th x-show="visibleColumns.manifest_number" @@click="sort('manifest_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    MANIFEST #
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'manifest_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                    </svg>
                                </div>
                            </th>
                            <th x-show="visibleColumns.destination_warehouse" @@click="sort('destination_warehouse')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center">
                                    DESTINATION
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'destination_warehouse' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <th x-show="visibleColumns.driver_name" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DRIVER</th>
                            <th x-show="visibleColumns.items_count" @@click="sort('items_count')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                <div class="flex items-center justify-center">
                                    ITEMS
                                    <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'items_count' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No transport manifests found.</td>
                        </tr>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70 align-top">
                                <td x-show="visibleColumns.manifest_number" class="px-4 py-2.5">
                                    <a :href="row.view_url" class="text-xs font-semibold text-slate-900 hover:text-blue-600 hover:underline" x-text="row.manifest_number"></a>
                                    <p class="text-[11px] text-slate-500 mt-0.5" x-text="row.dispatched_at ? 'Dispatched: ' + row.dispatched_at : 'Not dispatched'"></p>
                                </td>
                                <td x-show="visibleColumns.destination_warehouse" class="px-4 py-2.5 text-xs text-slate-600" x-text="row.destination_warehouse || '-'"></td>
                                <td x-show="visibleColumns.status" class="px-4 py-2.5 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm shadow-sm"
                                        :class="statusBadgeClass(row.status)"
                                        x-text="row.status"
                                    ></span>
                                </td>
                                <td x-show="visibleColumns.driver_name" class="px-4 py-2.5">
                                    <template x-if="row.driver_name">
                                        <div>
                                            <p class="text-xs font-medium text-slate-800" x-text="row.driver_name"></p>
                                            <p class="text-[11px] text-slate-500" x-text="row.driver_phone || '-'"></p>
                                        </div>
                                    </template>
                                    <template x-if="!row.driver_name">
                                        <span class="text-xs text-slate-400 italic">Not assigned</span>
                                    </template>
                                </td>
                                <td x-show="visibleColumns.items_count" class="px-4 py-2.5 text-center text-xs font-semibold text-slate-800" x-text="row.items_count"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-2.5 text-center">
                                    <a :href="row.view_url"
                                       class="inline-flex items-center rounded-lg border border-slate-200/70 bg-white/70 px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">
                                        View
                                    </a>
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
                                Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span>
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
</div>
@endsection
