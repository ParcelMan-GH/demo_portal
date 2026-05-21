@extends('admin.layouts.app')

@section('title', 'Orders')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Orders')

@php
    $config = [
        'endpoint' => route('admin.orders.data'),
        'exportEndpoint' => route('admin.orders.export'),
        'statuses' => $statuses,
        'pickupStatuses' => $pickupStatuses,
        'sources' => $sources,
        'destinationModes' => $destinationModes,
        'fulfillmentTypes' => $fulfillmentTypes,
        'deliveryPreferences' => $deliveryPreferences,
        'regions' => $regions,
        'warehouses' => $warehouses,
        'drivers' => $drivers,
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="window.shipmentsTable()" data-shipments-config='@json($config, JSON_INVALID_UTF8_SUBSTITUTE)'>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
        <template x-for="stat in statCards" :key="stat.key">
            <button type="button" @@click="applySummaryFilter(stat.key)" class="flex min-h-[86px] cursor-pointer items-center gap-3 rounded-2xl border bg-white px-4 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100" :class="isSummaryActive(stat.key) ? 'border-orange-300 bg-orange-50/70 ring-2 ring-orange-100' : 'border-slate-200/80'">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1" :class="stat.iconClass">
                    <svg x-show="stat.icon === 'package'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <svg x-show="stat.icon === 'user'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m14-10 2 2 4-4M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                    </svg>
                    <svg x-show="stat.icon === 'truck'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v8H3V7Zm11 3h3l3 3v2h-6v-5ZM7 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                    </svg>
                    <svg x-show="stat.icon === 'check'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m9 12 2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <svg x-show="stat.icon === 'warehouse'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 21h18M4 21V8l8-5 8 5v13M9 21v-7h6v7M7 10h2m6 0h2"/>
                    </svg>
                    <svg x-show="stat.icon === 'alert'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400" x-text="stat.label"></p>
                    <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="summary[stat.key] ?? 0"></p>
                </div>
            </button>
        </template>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-orange-100 bg-orange-50 text-orange-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Orders</h1>
                        <p class="mt-1 text-sm font-medium text-slate-500">Vendor orders from submission through pickup and warehouse handoff.</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if(Auth::guard('admin')->user()?->hasPermission('warehouse.receiving.manage') || Auth::guard('admin')->user()?->hasPermission('shipments.create'))
                        <a href="{{ route('warehouse.walkin.create') }}" class="inline-flex h-12 items-center gap-2 rounded-2xl bg-orange-600 px-4 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="hidden sm:inline">Walk-in</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4 border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="grid gap-4 xl:grid-cols-[minmax(360px,520px)_auto] xl:items-end xl:justify-between">
                <div class="relative w-full">
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Search</span>
                        <span class="relative block">
                            <input
                                type="text"
                                x-model="search"
                                @@input.debounce.500ms="meta.current_page = 1; loadData()"
                                placeholder="Search order, vendor, pickup contact, driver..."
                                class="h-14 w-full rounded-2xl border-2 border-slate-200 bg-white px-4 pl-11 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                            >
                            <svg class="absolute left-4 top-4 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                        </span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="filtersOpen = !filtersOpen" class="inline-flex h-14 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-5 text-sm font-black text-slate-700 shadow-md shadow-slate-200/60 transition hover:bg-slate-50">
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18l-7 8v6l-4 2v-8L3 4Z"/>
                            </svg>
                            Filters
                            <span x-show="activeFilterCount() > 0" class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] text-orange-700" x-text="activeFilterCount()"></span>
                        </button>

                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @@click="open = !open" class="inline-flex h-14 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-5 text-sm font-black text-slate-700 shadow-md shadow-slate-200/60 transition hover:bg-slate-50">
                                <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display: none;">
                                <template x-for="column in columns" :key="column.key">
                                    <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="column.label"></span>
                                        <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @@click="open = !open" class="inline-flex h-14 items-center gap-3 rounded-2xl border-2 border-slate-200 bg-white px-5 text-sm font-black text-slate-700 shadow-md shadow-slate-200/60 transition hover:bg-slate-50">
                                <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2Z"/>
                                </svg>
                                Export
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 p-2 shadow-2xl backdrop-blur-xl" style="display: none;">
                                <button type="button" @@click="exportData('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70">Excel</button>
                                <button type="button" @@click="exportData('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70">PDF</button>
                                <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70">CSV</button>
                                <button type="button" @@click="printData(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70">Print</button>
                            </div>
                        </div>
                </div>
            </div>

            <div x-show="filtersOpen" x-cloak x-transition class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Order Status</span>
                        <select x-model="statusFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value">
                                <option :value="status.value" x-text="status.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Pickup Status</span>
                        <select x-model="pickupStatusFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All pickup statuses</option>
                            <template x-for="status in pickupStatuses" :key="status.value">
                                <option :value="status.value" x-text="status.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Source</span>
                        <select x-model="sourceFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All sources</option>
                            <template x-for="source in sources" :key="source.value">
                                <option :value="source.value" x-text="source.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Drop-off Type</span>
                        <select x-model="destinationModeFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All drop-off types</option>
                            <template x-for="mode in destinationModes" :key="mode.value">
                                <option :value="mode.value" x-text="mode.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Delivery Method</span>
                        <select x-model="deliveryPreferenceFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All methods</option>
                            <template x-for="preference in deliveryPreferences" :key="preference.value">
                                <option :value="preference.value" x-text="preference.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Fulfillment</span>
                        <select x-model="fulfillmentTypeFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All fulfillment types</option>
                            <template x-for="type in fulfillmentTypes" :key="type.value">
                                <option :value="type.value" x-text="type.label"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Assignment</span>
                        <select x-model="assignmentStateFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All orders</option>
                            <option value="needs_assignment">Needs driver</option>
                            <option value="assigned">Has pickup assignment</option>
                            <option value="active">Active pickup</option>
                            <option value="warehouse_received">Received at warehouse</option>
                            <option value="cancelled">Cancelled pickup</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Target Warehouse</span>
                        <select x-model="warehouseFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All warehouses</option>
                            <template x-for="warehouse in warehouses" :key="warehouse.id">
                                <option :value="warehouse.id" x-text="warehouse.name"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Pickup Driver</span>
                        <select x-model="driverFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All drivers</option>
                            <template x-for="driver in drivers" :key="driver.id">
                                <option :value="driver.id" x-text="driver.name + (driver.phone ? ' / ' + driver.phone : '')"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Pickup Region</span>
                        <select x-model="pickupRegionFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All pickup regions</option>
                            <template x-for="region in regions" :key="region.id">
                                <option :value="region.id" x-text="region.name"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Delivery Region</span>
                        <select x-model="deliveryRegionFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All delivery regions</option>
                            <template x-for="region in regions" :key="region.id">
                                <option :value="region.id" x-text="region.name"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Quantity State</span>
                        <select x-model="quantityStateFilter" class="h-11 w-full rounded-xl border border-slate-200/70 bg-white/70 px-3 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50">
                            <option value="">All quantity states</option>
                            <option value="matched">Matched totals</option>
                            <option value="shortage">Shortage</option>
                            <option value="excess">Excess</option>
                            <option value="has_discrepancy">Receipt discrepancy</option>
                            <option value="missing_vendor_total">Missing vendor total</option>
                            <option value="missing_driver_total">Missing driver total</option>
                            <option value="receipt_missing">No warehouse receipt</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Package Lines</span>
                        <span class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="itemsMin" placeholder="Min" class="min-w-0 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                            <input type="number" min="0" x-model="itemsMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Vendor Total Qty</span>
                        <span class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="vendorQtyMin" placeholder="Min" class="min-w-0 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                            <input type="number" min="0" x-model="vendorQtyMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Driver Picked Qty</span>
                        <span class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="driverQtyMin" placeholder="Min" class="min-w-0 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                            <input type="number" min="0" x-model="driverQtyMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400">
                        </span>
                    </label>

                    <label class="block md:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Created Date</span>
                        <span class="relative block">
                            <input type="text" x-ref="createdRange" placeholder="Select date range" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/70 px-3 pl-10 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50" readonly>
                            <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                    </label>

                    <label class="block md:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Submitted Date</span>
                        <span class="relative block">
                            <input type="text" x-ref="submittedRange" placeholder="Select date range" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/70 px-3 pl-10 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50" readonly>
                            <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                    </label>

                    <label class="block md:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Assigned Date</span>
                        <span class="relative block">
                            <input type="text" x-ref="assignedRange" placeholder="Select date range" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/70 px-3 pl-10 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50" readonly>
                            <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                    </label>

                    <label class="block md:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Picked Date</span>
                        <span class="relative block">
                            <input type="text" x-ref="pickedRange" placeholder="Select date range" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/70 px-3 pl-10 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50" readonly>
                            <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                    </label>

                    <label class="block md:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-[11px] font-black uppercase tracking-wide text-slate-500">Received Date</span>
                        <span class="relative block">
                            <input type="text" x-ref="receivedRange" placeholder="Select date range" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/70 px-3 pl-10 text-sm font-semibold text-slate-700 outline-none backdrop-blur-sm transition-colors focus:border-slate-300 focus:ring-2 focus:ring-slate-400/50" readonly>
                            <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </span>
                    </label>
                </div>
                <div class="mt-4 flex flex-wrap justify-end gap-2">
                    <button type="button" @@click="filtersOpen = false" class="mr-auto inline-flex h-11 items-center rounded-xl border border-slate-200/70 bg-white/70 px-4 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur-sm transition-colors hover:bg-white/90">Close Filters</button>
                    <button type="button" @@click="resetFilters()" class="inline-flex h-11 items-center rounded-xl border border-slate-200/70 bg-white/70 px-4 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur-sm transition-colors hover:bg-white/90">Clear Filters</button>
                    <button type="button" @@click="meta.current_page = 1; loadData()" class="inline-flex h-11 items-center rounded-xl bg-orange-600 px-4 text-sm font-semibold text-white shadow-sm shadow-orange-600/20 transition-colors hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>
        </div>

        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px]"></div>

            <div class="block divide-y divide-slate-100 md:hidden">
                <div x-show="!loading && loadError" x-cloak class="px-4 py-8 text-center">
                    <p class="text-sm font-black text-rose-700" x-text="loadError"></p>
                </div>
                <div x-show="!loading && !loadError && shipments.length === 0" x-cloak class="px-4 py-10 text-center">
                    <p class="text-sm font-bold text-slate-500">No orders found.</p>
                </div>
                <template x-for="shipment in shipments" :key="shipment.id">
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Order</p>
                                <a :href="shipment.view_url" class="mt-1 block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="shipment.shipment_number"></a>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900" x-text="shipment.vendor_business || shipment.vendor_name || '-'"></p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(shipment.status)" x-text="shipment.status_label"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Pickup</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="shipment.pickup_contact_name || '-'"></p>
                                <p class="truncate font-mono text-xs font-bold text-slate-500" x-text="shipment.pickup_contact_phone || '-'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Driver</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="shipment.pickup_driver_name || '-'"></p>
                                <p x-show="shipment.pickup_driver_name" class="truncate font-mono text-xs font-bold text-slate-500" x-text="shipment.pickup_driver_phone || '-'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Warehouse</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="shipment.target_warehouse_name || '-'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Packages</p>
                                <p class="mt-1 text-sm font-black text-slate-900" x-text="shipment.items_count || 0"></p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="pickupBadgeClass(shipment.pickup_status)" x-text="shipment.pickup_status_label || 'Pending'"></span>
                            <a :href="shipment.view_url" class="inline-flex h-10 items-center rounded-xl border-2 border-orange-600 bg-orange-600 px-4 text-xs font-black text-white shadow-lg shadow-orange-600/15 transition hover:bg-orange-700">Open</a>
                        </div>
                    </article>
                </template>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[1280px] w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                        <tr>
                            <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="cursor-pointer px-4 py-3">Order</th>
                            <th x-show="visibleColumns.vendor" class="px-4 py-3">Vendor</th>
                            <th x-show="visibleColumns.pickup_contact" @@click="sort('pickup_contact_name')" class="cursor-pointer px-4 py-3">Pickup Contact</th>
                            <th x-show="visibleColumns.pickup_location" class="px-4 py-3">Pickup Location</th>
                            <th x-show="visibleColumns.target_warehouse" class="px-4 py-3">Drop-off Warehouse</th>
                            <th x-show="visibleColumns.pickup_driver" class="px-4 py-3">Pickup Driver</th>
                            <th x-show="visibleColumns.items" @@click="sort('items_count')" class="cursor-pointer px-4 py-3 text-center">Packages</th>
                            <th x-show="visibleColumns.status" @@click="sort('status')" class="cursor-pointer px-4 py-3 text-center">Order Status</th>
                            <th x-show="visibleColumns.pickup_status" class="px-4 py-3 text-center">Pickup Status</th>
                            <th x-show="visibleColumns.submitted_at" @@click="sort('submitted_at')" class="cursor-pointer px-4 py-3">Submitted</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr x-show="!loading && loadError" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center text-sm font-black text-rose-700" x-text="loadError"></td>
                        </tr>
                        <tr x-show="!loading && !loadError && shipments.length === 0" x-cloak>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center text-sm font-bold text-slate-500">No orders found.</td>
                        </tr>
                        <template x-for="shipment in shipments" :key="shipment.id">
                            <tr class="transition hover:bg-orange-50/20">
                                <td x-show="visibleColumns.shipment_number" class="px-4 py-3">
                                    <a :href="shipment.view_url" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="shipment.shipment_number"></a>
                                    <p class="mt-1 text-[11px] font-bold text-slate-400" x-text="shipment.destination_mode_label"></p>
                                </td>
                                <td x-show="visibleColumns.vendor" class="px-4 py-3">
                                    <p class="text-sm font-black text-slate-900" x-text="shipment.vendor_business || shipment.vendor_name || '-'"></p>
                                    <p class="mt-0.5 font-mono text-[11px] font-bold text-slate-500" x-text="shipment.vendor_phone || '-'"></p>
                                </td>
                                <td x-show="visibleColumns.pickup_contact" class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-800" x-text="shipment.pickup_contact_name || '-'"></p>
                                    <p class="mt-0.5 font-mono text-[11px] font-bold text-slate-500" x-text="shipment.pickup_contact_phone || '-'"></p>
                                </td>
                                <td x-show="visibleColumns.pickup_location" class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="shipment.pickup_location || '-'"></td>
                                <td x-show="visibleColumns.target_warehouse" class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-800" x-text="shipment.target_warehouse_name || '-'"></p>
                                    <p class="mt-0.5 font-mono text-[11px] font-bold text-slate-500" x-text="shipment.target_warehouse_code || ''"></p>
                                </td>
                                <td x-show="visibleColumns.pickup_driver" class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-800" x-text="shipment.pickup_driver_name || '-'"></p>
                                    <p x-show="shipment.pickup_driver_name" class="mt-0.5 font-mono text-[11px] font-bold text-slate-500" x-text="shipment.pickup_driver_phone || '-'"></p>
                                </td>
                                <td x-show="visibleColumns.items" class="px-4 py-3 text-center">
                                    <span class="inline-flex min-w-10 justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700" x-text="shipment.items_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.status" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(shipment.status)" x-text="shipment.status_label || '-'"></span>
                                </td>
                                <td x-show="visibleColumns.pickup_status" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="pickupBadgeClass(shipment.pickup_status)" x-text="shipment.pickup_status_label || 'Pending'"></span>
                                </td>
                                <td x-show="visibleColumns.submitted_at" class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="formatDateTime(shipment.submitted_at || shipment.created_at)"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-3 text-right">
                                    <a :href="shipment.view_url" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-700">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-600">Rows</span>
                            <select x-model.number="perPage" @@change="meta.current_page = 1; loadData()" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @@click="previousPage()" :disabled="meta.current_page <= 1" class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Prev</button>
                            <span class="text-xs font-black text-slate-600">Page <span x-text="meta.current_page || 1"></span> of <span x-text="meta.last_page || 1"></span></span>
                            <button type="button" @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
