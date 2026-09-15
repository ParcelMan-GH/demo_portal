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
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" 
     x-data="{
         selectedShipment: null,
         selectShipment(shipment) {
             this.selectedShipment = shipment;
         },
         ...window.shipmentsTable()
     }" 
     data-shipments-config='@json($config, JSON_INVALID_UTF8_SUBSTITUTE)'>

    <!-- Page Header (Positioned Above the Workspace) -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Orders Workspace</h1>
            </div>
            <p class="text-slate-500 text-sm font-semibold mt-1">
                Monitor vendor orders from submission through pickup and warehouse handoff.
            </p>
        </div>
        
        <div class="flex items-center gap-4">
            @if(Auth::guard('admin')->user()?->hasPermission('warehouse.receiving.manage') || Auth::guard('admin')->user()?->hasPermission('shipments.create'))
                <a href="{{ route('warehouse.walkin.create') }}" class="bg-[#E2762B] hover:bg-[#d1651d] text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-md transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Walk-in Order
                </a>
            @endif
        </div>
    </div>

    <!-- Minimal Cream Metric Cards Grid -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <template x-for="stat in statCards" :key="stat.key">
            <button type="button" @click="applySummaryFilter(stat.key)" 
                    class="group flex flex-col justify-between rounded-2xl border p-4 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none min-h-[96px]"
                    :class="isSummaryActive(stat.key) ? 'border-orange-300 bg-orange-50/70 ring-2 ring-orange-100' : 'border-amber-100/80 bg-[#FFFCF8] hover:border-orange-200'">
                
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400 group-hover:text-orange-600 transition-colors" x-text="stat.label"></span>
                    <span class="shrink-0 transition-colors" :class="isSummaryActive(stat.key) ? 'text-orange-500' : 'text-slate-300 group-hover:text-orange-400'">
                        <svg x-show="stat.icon === 'package'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <svg x-show="stat.icon === 'user'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m14-10 2 2 4-4M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                        <svg x-show="stat.icon === 'truck'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h11v8H3V7Zm11 3h3l3 3v2h-6v-5ZM7 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                        <svg x-show="stat.icon === 'check'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 12 2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <svg x-show="stat.icon === 'warehouse'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M4 21V8l8-5 8 5v13M9 21v-7h6v7M7 10h2m6 0h2"/></svg>
                        <svg x-show="stat.icon === 'alert'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-black text-slate-950" x-text="summary[stat.key] ?? 0"></p>
            </button>
        </template>
    </div>

    <!-- Master-Detail Split Screen Layout -->
    <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-slate-100/60 p-6 shadow-lg shadow-slate-300/20 min-h-[760px] flex flex-col lg:flex-row gap-6 lg:items-start">
        
        <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

        <!-- LEFT SIDE: Orders Master List (33% width) -->
        <div class="w-full lg:w-4/12 shrink-0 flex flex-col rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden min-h-[720px]">
            
            <!-- Search & Filter Controls -->
            <div class="border-b border-slate-100 bg-slate-50/70 p-5 space-y-4">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                    </svg>
                    <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search order, vendor, driver..." class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="filtersOpen = !filtersOpen" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50" :class="filtersOpen ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        Filter Orders
                        <span x-show="activeFilterCount() > 0" class="ml-1 rounded-full bg-orange-200/50 px-2 py-0.5 text-[10px] font-black text-orange-700" x-text="activeFilterCount()"></span>
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-40 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" style="display: none;">
                            <button type="button" @click="exportData('excel'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Excel</button>
                            <button type="button" @click="exportData('pdf'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">CSV</button>
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Filter Drawer (Scrollable if needed) -->
                <div x-show="filtersOpen" x-transition class="max-h-[400px] overflow-y-auto space-y-4 pt-3 border-t border-slate-200/60 custom-scrollbar" style="display:none">
                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Order Status</span>
                        <select x-model="statusFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <template x-for="status in statuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Pickup Status</span>
                        <select x-model="pickupStatusFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <option value="">All pickup statuses</option>
                            <template x-for="status in pickupStatuses" :key="status.value"><option :value="status.value" x-text="status.label"></option></template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Source</span>
                        <select x-model="sourceFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <option value="">All sources</option>
                            <template x-for="source in sources" :key="source.value"><option :value="source.value" x-text="source.label"></option></template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Assignment State</span>
                        <select x-model="assignmentStateFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <option value="">All orders</option>
                            <option value="needs_assignment">Needs rider</option>
                            <option value="assigned">Has pickup assignment</option>
                            <option value="active">Active pickup</option>
                            <option value="warehouse_received">Received at warehouse</option>
                            <option value="cancelled">Cancelled pickup</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Target Warehouse</span>
                        <select x-model="warehouseFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            <option value="">All warehouses</option>
                            <template x-for="warehouse in warehouses" :key="warehouse.id"><option :value="warehouse.id" x-text="warehouse.name"></option></template>
                        </select>
                    </label>
                    
                    <div class="pt-2 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="resetFilters()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Clear</button>
                        <button type="button" @click="meta.current_page = 1; loadData(); filtersOpen = false" class="rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-orange-700">Apply</button>
                    </div>
                </div>
            </div>

            <!-- Scrollable List of Orders -->
            <div class="flex-1 divide-y divide-slate-100 overflow-y-auto max-h-[580px]">
                <div x-show="!loading && loadError" x-cloak class="p-8 text-center text-sm font-black text-rose-700" x-text="loadError"></div>
                
                <template x-if="shipments.length === 0 && !loading && !loadError">
                    <div class="p-8 text-center text-sm font-semibold text-slate-500">
                        No orders match your query.
                    </div>
                </template>

                <template x-for="shipment in shipments" :key="shipment.id">
                    <div @click="selectShipment(shipment)"
                         class="cursor-pointer p-5 transition-all duration-150 hover:bg-orange-50/40"
                         :class="selectedShipment?.id === shipment.id ? 'bg-orange-50/90 border-l-4 border-orange-600 shadow-inner' : ''">
                        
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-black text-slate-900" x-text="shipment.shipment_number"></span>
                                    <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="statusBadgeClass(shipment.status)" x-text="shipment.status_label"></span>
                                </div>
                                <p class="mt-1 truncate text-xs font-bold text-slate-700" x-text="shipment.vendor_business || shipment.vendor_name || 'Unknown Vendor'"></p>
                                
                                <div class="mt-2.5 flex items-center gap-x-3 gap-y-1 text-[11px] font-semibold text-slate-500">
                                    <span class="flex items-center gap-1"><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> <span x-text="shipment.items_count || 0"></span> Items</span>
                                    <span>•</span>
                                    <span class="truncate" x-text="shipment.target_warehouse_name || 'No drop-off hub'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Left Pagination Controls -->
            <div class="mt-auto border-t border-slate-100 bg-slate-50/70 p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-extrabold text-slate-600">
                        Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="meta.current_page--; loadData()" :disabled="meta.current_page <= 1" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="meta.current_page++; loadData()" :disabled="meta.current_page >= meta.last_page" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Order Detail Pane (67% width) -->
        <div class="w-full lg:w-8/12 flex-1 flex flex-col rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden min-h-[720px]">
            
            <!-- Blank Empty State -->
            <div x-show="!selectedShipment" class="flex flex-1 flex-col items-center justify-center p-12 text-center min-h-[680px]">
                <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 mb-5 shadow-sm">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Select an Order to View Details</h3>
                <p class="mt-2 text-sm font-semibold text-slate-500 max-w-md">Click on any order from the list on the left to inspect its routing, pickup progress, and package details.</p>
            </div>

            <!-- Selected Order Detail View -->
            <div x-show="selectedShipment" class="flex flex-col h-full" style="display: none;">
                
                <!-- Header Banner -->
                <div class="relative overflow-hidden border-b border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-slate-50/50 p-8">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <h2 class="font-mono text-3xl font-black tracking-tight text-slate-950" x-text="selectedShipment?.shipment_number"></h2>
                                <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider" 
                                      :class="statusBadgeClass(selectedShipment?.status)" 
                                      x-text="selectedShipment?.status_label"></span>
                            </div>

                            <p class="mt-2 text-lg font-extrabold text-slate-700" x-text="selectedShipment?.vendor_business || selectedShipment?.vendor_name || 'Unknown Vendor'"></p>

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="'Submitted: ' + formatDateTime(selectedShipment?.submitted_at || selectedShipment?.created_at)"></span>
                                </span>
                                <span>•</span>
                                <span class="flex items-center gap-1.5 text-orange-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 110-8 4 4 0 010 8Zm8 0a4 4 0 100-8 4 4 0 000 8Z"/></svg>
                                    <span x-text="selectedShipment?.destination_mode_label"></span>
                                </span>
                            </div>
                        </div>

                        <button type="button" @click="selectedShipment = null" class="shrink-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition shadow-sm">
                            Deselect &times;
                        </button>
                    </div>
                </div>

                <!-- Management Actions Toolbar -->
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-white px-8 py-4">
                    <a :href="selectedShipment?.view_url"
                       class="inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-600 px-5 py-2.5 text-sm font-black text-white hover:bg-orange-700 transition shadow-md shadow-orange-600/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Open Full Order Page
                    </a>
                </div>

                <!-- Clean Detail Body -->
                <div class="flex-1 p-8 overflow-y-auto space-y-6 bg-slate-50/40">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Origin / Pickup Details -->
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-1 h-full bg-slate-400"></div>
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">Pickup & Origin</h4>
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-black" :class="pickupBadgeClass(selectedShipment?.pickup_status)" x-text="selectedShipment?.pickup_status_label || 'Pending'"></span>
                            </div>
                            
                            <div class="space-y-4 mt-2">
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-slate-400">Location</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="selectedShipment?.pickup_location || 'No location specified'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-slate-400">Contact Person</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="selectedShipment?.pickup_contact_name || '-'"></p>
                                    <p class="font-mono text-xs font-semibold text-slate-500" x-text="selectedShipment?.pickup_contact_phone || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-slate-400">Assigned Rider</p>
                                    <template x-if="selectedShipment?.pickup_driver_name">
                                        <div>
                                            <p class="mt-0.5 text-sm font-bold text-slate-900" x-text="selectedShipment.pickup_driver_name"></p>
                                            <p class="font-mono text-xs font-semibold text-slate-500" x-text="selectedShipment.pickup_driver_phone || '-'"></p>
                                        </div>
                                    </template>
                                    <template x-if="!selectedShipment?.pickup_driver_name">
                                        <p class="mt-0.5 text-sm font-bold italic text-slate-400">Unassigned</p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Routing & Destination Details -->
                        <div class="rounded-3xl border border-orange-200/80 bg-orange-50/30 p-6 shadow-sm space-y-4 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-1 h-full bg-orange-500"></div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-orange-800">Routing & Destination</h4>
                            
                            <div class="space-y-4 mt-2">
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-orange-600/70">Drop-off Hub</p>
                                    <p class="mt-0.5 text-base font-black text-slate-900" x-text="selectedShipment?.target_warehouse_name || '-'"></p>
                                    <p class="font-mono text-xs font-bold text-slate-500" x-text="selectedShipment?.target_warehouse_code || ''"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="rounded-2xl bg-white p-3 border border-orange-100">
                                        <p class="text-[10px] font-bold uppercase text-slate-400">Packages</p>
                                        <p class="text-xl font-black text-slate-900" x-text="selectedShipment?.items_count || 0"></p>
                                    </div>
                                    <div class="rounded-2xl bg-white p-3 border border-orange-100">
                                        <p class="text-[10px] font-bold uppercase text-slate-400">Source</p>
                                        <p class="text-sm font-bold text-slate-900 capitalize" x-text="selectedShipment?.source_label || '-'"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection