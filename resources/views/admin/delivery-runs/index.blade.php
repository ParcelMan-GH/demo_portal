@extends('admin.layouts.app')

@section('title', 'Delivery Runs')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Delivery Runs')

@section('content')

<div class="space-y-6" x-data="deliveryRunsTable">
    <!-- Delivery Runs Datatable -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
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
                        <p class="mt-0.5 text-sm text-slate-500">View and manage all delivery runs</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="meta.total + ' Total Runs'">
                </span>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="p-6 pb-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Search + Status + Warehouse + Date Range -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search runs..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-52">
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
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="statusFilter = ''; statusFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="statusFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <svg x-show="statusFilter === '{{ $status['value'] }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $status['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Warehouse Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-52">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                        >
                            <span x-text="warehouseFilterName || 'All warehouses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-60 overflow-y-auto"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="warehouseFilter = ''; warehouseFilterName = ''; loadData(); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="warehouseFilter === '' ? 'bg-white/70 shadow-sm' : ''"
                            >
                                <svg x-show="warehouseFilter === ''" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All warehouses</span>
                            </button>
                            @foreach($warehouses as $warehouse)
                            <button
                                type="button"
                                @@click="warehouseFilter = '{{ $warehouse->id }}'; warehouseFilterName = '{{ $warehouse->name }}'; loadData(); open = false"
                                class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                :class="warehouseFilter === '{{ $warehouse->id }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''"
                            >
                                <svg x-show="warehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $warehouse->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Date From -->
                    <div class="relative w-full sm:w-40">
                        <input
                            type="date"
                            x-model="dateFrom"
                            @@change="loadData()"
                            class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 transition-colors"
                            placeholder="From date"
                        >
                    </div>

                    <!-- Date To -->
                    <div class="relative w-full sm:w-40">
                        <input
                            type="date"
                            x-model="dateTo"
                            @@change="loadData()"
                            class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 transition-colors"
                            placeholder="To date"
                        >
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
                                <th @@click="sort('run_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        RUN #
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'run_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    WAREHOUSE
                                </th>
                                <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    DRIVER
                                </th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    STOPS / ITEMS
                                </th>
                                <th @@click="sort('status')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center justify-center">
                                        STATUS
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'status' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sort('dispatched_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        DISPATCHED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'dispatched_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th @@click="sort('completed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        COMPLETED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                    ACTIONS
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-if="runs.length === 0 && !loading">
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 text-xs">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            </svg>
                                            <span>No delivery runs found</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="run in runs" :key="run.id">
                                <tr class="hover:bg-slate-50/70">
                                    <!-- Run Number -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="run.run_number"></td>

                                    <!-- Warehouse -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-900" x-text="run.warehouse_name || '—'"></div>
                                        <div class="text-[10px] text-slate-500" x-text="run.warehouse_code || ''"></div>
                                    </td>

                                    <!-- Driver -->
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-900" x-text="run.driver_name || '—'"></div>
                                        <div class="text-[10px] text-slate-500" x-text="run.driver_phone || ''"></div>
                                    </td>

                                    <!-- Stops / Items -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-50 text-[10px] font-semibold text-blue-700" x-text="run.stops_count" title="Stops"></span>
                                            <span class="text-slate-300">/</span>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700" x-text="run.items_count" title="Items"></span>
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="{
                                                'bg-slate-100 text-slate-700':  run.status === 'draft',
                                                'bg-blue-100 text-blue-700':    run.status === 'assigned',
                                                'bg-amber-100 text-amber-700':  run.status === 'out_for_delivery',
                                                'bg-orange-100 text-orange-700': run.status === 'partially_delivered',
                                                'bg-emerald-100 text-emerald-700': run.status === 'completed',
                                                'bg-red-100 text-red-700':      run.status === 'cancelled'
                                            }"
                                            x-text="run.status_label"
                                        ></span>
                                    </td>

                                    <!-- Dispatched At -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="run.dispatched_at ? formatDateTime(run.dispatched_at) : '—'"></td>

                                    <!-- Completed At -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="run.completed_at ? formatDateTime(run.completed_at) : '—'"></td>

                                    <!-- Actions -->
                                    <td class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                        <div class="inline-flex items-center gap-1">
                                            <a
                                                :href="'{{ route('admin.delivery-runs.show', ['run' => '__ID__']) }}'.replace('__ID__', run.id)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="View delivery run"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
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
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deliveryRunsTable', () => ({
        runs: [],
        loading: false,
        search: '',
        statusFilter: '',
        statusFilterName: '',
        warehouseFilter: '',
        warehouseFilterName: '',
        dateFrom: '',
        dateTo: '',
        sortBy: 'created_at',
        sortDirection: 'desc',
        perPage: 25,
        page: 1,
        meta: {
            total: 0,
            from: 0,
            to: 0,
            current_page: 1,
            last_page: 1,
        },

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;

            const params = new URLSearchParams({
                page:         this.page,
                per_page:     this.perPage,
                sort:         this.sortBy,
                direction:    this.sortDirection,
            });

            if (this.search)         params.set('search',       this.search);
            if (this.statusFilter)   params.set('status',       this.statusFilter);
            if (this.warehouseFilter) params.set('warehouse_id', this.warehouseFilter);
            if (this.dateFrom)       params.set('date_from',    this.dateFrom);
            if (this.dateTo)         params.set('date_to',      this.dateTo);

            try {
                const response = await fetch('{{ route('admin.delivery-runs.data') }}?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const json = await response.json();
                this.runs = json.data;
                this.meta = json.meta;
            } catch (error) {
                console.error('Failed to load delivery runs:', error);
                this.runs = [];
            } finally {
                this.loading = false;
            }
        },

        sort(field) {
            if (this.sortBy === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy        = field;
                this.sortDirection = 'asc';
            }
            this.page = 1;
            this.loadData();
        },

        firstPage() {
            if (this.meta.current_page === 1) return;
            this.page = 1;
            this.loadData();
        },

        previousPage() {
            if (this.meta.current_page <= 1) return;
            this.page = this.meta.current_page - 1;
            this.loadData();
        },

        nextPage() {
            if (this.meta.current_page >= this.meta.last_page) return;
            this.page = this.meta.current_page + 1;
            this.loadData();
        },

        lastPage() {
            if (this.meta.current_page === this.meta.last_page) return;
            this.page = this.meta.last_page;
            this.loadData();
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('en-GB', {
                day:    '2-digit',
                month:  '2-digit',
                year:   'numeric',
                hour:   '2-digit',
                minute: '2-digit',
            });
        },
    }));
});
</script>
@endpush
