@extends('admin.layouts.app')

@section('title', 'Vendor Management')
@section('breadcrumb-parent', 'Management')
@section('breadcrumb-current', 'Vendors')

@section('content')

@php
    $vendorsConfig = [
        'endpoint' => route('admin.vendors.data'),
        'exportEndpoint' => route('admin.vendors.export'),
        'storeEndpoint' => route('admin.vendors.store'),
        'baseEndpoint' => route('admin.vendors.index'),
        'csrfToken' => csrf_token(),
    ];
@endphp

<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="{
    selectedVendor: null,
    selectVendor(vendor) {
        this.selectedVendor = vendor;
    },
    ...vendorsTable()
}" data-vendors-config='@json($vendorsConfig)'>

    <!-- Page Header (Positioned Above Cards) -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Vendors Workspace</h1>
            </div>
            <p class="text-slate-400 text-sm font-semibold mt-1">Manage sender records, contacts, payout accounts, and shipment activity.</p>
        </div>

        @if(Auth::guard('admin')->user()->hasPermission('vendors.create'))
        <div>
            <button type="button" @click="openAddModal()" class="bg-[#E2762B] hover:bg-[#d1651d] text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add New Vendor
            </button>
        </div>
        @endif
    </div>

    <!-- Minimal Cream Metric Cards Grid -->
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6 lg:gap-4">
        
        <!-- Total Vendors -->
        <button type="button" @click="clearFilter('all')" class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-4 text-left shadow-sm transition hover:border-orange-200 hover:shadow-md focus:outline-none min-h-[96px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Total Vendors</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 transition-colors group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900" x-text="meta.summary?.total_vendors || 0"></p>
        </button>

        <!-- Active Vendors -->
        <button type="button" @click="setStatusFilter('active', 'Active')" class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-4 text-left shadow-sm transition hover:border-orange-200 hover:shadow-md focus:outline-none min-h-[96px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Active Vendors</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 transition-colors group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900" x-text="meta.summary?.active_vendors || 0"></p>
        </button>

        <!-- With Shipments -->
        <button type="button" @click="shipmentCountMin = 1; meta.current_page = 1; loadData()" class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-4 text-left shadow-sm transition hover:border-orange-200 hover:shadow-md focus:outline-none min-h-[96px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">With Shipments</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 transition-colors group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900" x-text="meta.summary?.vendors_with_shipments || 0"></p>
        </button>

        <!-- Open Shipments -->
        <div class="flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-4 text-left shadow-sm min-h-[96px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Open Shipments</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900" x-text="meta.summary?.open_shipments || 0"></p>
        </div>

        <!-- Delivered -->
        <button type="button" @click="shipmentStatus = 'delivered'; meta.current_page = 1; loadData()" class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-4 text-left shadow-sm transition hover:border-orange-200 hover:shadow-md focus:outline-none min-h-[96px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Delivered</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 transition-colors group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="mt-3 text-3xl font-black text-slate-900" x-text="meta.summary?.delivered_shipments || 0"></p>
        </button>

        <!-- Unpaid Earnings -->
        <button type="button" @click="earningsStatus = 'approved'; meta.current_page = 1; loadData()" class="group flex flex-col justify-between rounded-2xl border border-amber-100/80 bg-amber-50/20 p-4 text-left shadow-sm transition hover:border-orange-200 hover:shadow-md focus:outline-none min-h-[96px]">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">Unpaid Earnings</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 transition-colors group-hover:text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            </div>
            <p class="mt-3 truncate text-3xl font-black text-slate-900" x-text="formatMoney(meta.summary?.unpaid_earnings || 0)"></p>
        </button>
    </div>

    <!-- Master-Detail Split Screen Layout -->
    <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30 min-h-[760px] flex flex-col lg:flex-row">
        <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

        <!-- LEFT SIDE: Vendor Roster Master List (33% width) -->
        <div class="w-full lg:w-4/12 shrink-0 flex flex-col border-b lg:border-b-0 lg:border-r border-slate-200/90 bg-white min-h-[720px]">
            
            <!-- Search & Filter Controls -->
            <div class="border-b border-slate-100 bg-slate-50/70 p-5 space-y-4">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search name, business, email, phone..." class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        Filter Roster
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

                <!-- Filter Options Drawer -->
                <div x-show="showFilters" x-transition class="space-y-3 pt-3 border-t border-slate-200/60" style="display:none">
                    <select x-model="statusFilter" @change="applyFilters()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
            </div>

            <!-- Scrollable List of Vendors -->
            <div class="flex-1 divide-y divide-slate-100 overflow-y-auto max-h-[580px]">
                <template x-if="vendors.length === 0 && !loading">
                    <div class="p-8 text-center text-sm font-semibold text-slate-500">
                        No vendors found.
                    </div>
                </template>

                <template x-for="vendor in vendors" :key="vendor.id">
                    <div @click="selectVendor(vendor)"
                         class="cursor-pointer p-5 transition-all duration-150 hover:bg-orange-50/40"
                         :class="selectedVendor?.id === vendor.id ? 'bg-orange-50/90 border-l-4 border-orange-600 shadow-inner' : ''">
                        <div class="flex items-center gap-4">
                            <div class="flex h-13 w-13 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-orange-100 text-base font-black text-orange-700 ring-1 ring-orange-200">
                                <span x-text="vendor.name ? vendor.name.charAt(0).toUpperCase() : 'V'"></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-sm font-black text-slate-900" x-text="vendor.name"></p>
                                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider" 
                                          :class="statusBadgeClass(vendor)" 
                                          x-text="vendorStatusLabel(vendor)"></span>
                                </div>

                                <p class="truncate text-xs font-semibold text-slate-500 mt-0.5" x-text="vendor.business_name || vendor.phone || vendor.email || 'No business name'"></p>

                                <div class="mt-2 flex items-center justify-between text-xs font-bold text-slate-500">
                                    <span><strong class="text-slate-900" x-text="vendor.shipments_count || 0"></strong> Shipments</span>
                                    <span class="text-emerald-700 font-extrabold" x-text="formatMoney(vendor.unpaid_earnings || 0) + ' Unpaid'"></span>
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
                        <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Vendor Detail Pane (67% width) -->
        <div class="w-full lg:w-8/12 flex-1 flex flex-col bg-slate-50/40 min-h-[720px]">
            
            <!-- Blank Empty State -->
            <div x-show="!selectedVendor" class="flex flex-1 flex-col items-center justify-center p-12 text-center min-h-[680px]">
                <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 mb-5 shadow-sm">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Select a Vendor to View Details</h3>
                <p class="mt-2 text-sm font-semibold text-slate-500 max-w-md">Click on any vendor from the left roster to inspect their account info, MoMo payout settings, and management actions.</p>
            </div>

            <!-- Selected Vendor Detail View -->
            <div x-show="selectedVendor" class="flex flex-col h-full" style="display: none;">
                
                <!-- Light Header Banner -->
                <div class="relative overflow-hidden border-b border-slate-200/80 bg-white p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div class="flex items-center gap-6">
                            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-orange-600 text-4xl font-black text-white shadow-xl shadow-orange-600/20 ring-4 ring-orange-50">
                                <span x-text="selectedVendor?.name ? selectedVendor.name.charAt(0).toUpperCase() : 'V'"></span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-3xl font-black tracking-tight text-slate-950" x-text="selectedVendor?.name"></h2>
                                    <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider" 
                                          :class="statusBadgeClass(selectedVendor)" 
                                          x-text="vendorStatusLabel(selectedVendor)"></span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-bold text-slate-700">
                                    <span x-text="selectedVendor?.business_name || 'No Business Name'"></span>
                                    <span class="text-slate-300">•</span>
                                    <span x-text="selectedVendor?.phone || 'No phone'"></span>
                                    <span class="text-slate-300">•</span>
                                    <span x-text="selectedVendor?.email || 'No email'"></span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold text-slate-500">
                                    <span x-text="'Registered: ' + formatDateTime(selectedVendor?.created_at)"></span>
                                    <span>•</span>
                                    <span x-text="'Last Activity: ' + formatDateTime(selectedVendor?.last_activity_at)"></span>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="selectedVendor = null" class="self-start rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition">
                            Deselect &times;
                        </button>
                    </div>
                </div>

                <!-- Management Actions Toolbar -->
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-white px-8 py-4">
                    <template x-if="!selectedVendor?.is_deleted && selectedVendor?.can_manage">
                        <button type="button" @click="openEditModal(selectedVendor)"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Details
                        </button>
                    </template>

                    <template x-if="!selectedVendor?.is_deleted && selectedVendor?.can_manage">
                        <button type="button" @click="toggleVendorStatus(selectedVendor)"
                                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-xs font-black transition"
                                :class="selectedVendor?.is_active ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
                                x-text="selectedVendor?.is_active ? 'Disable Account' : 'Enable Account'">
                        </button>
                    </template>

                    <template x-if="selectedVendor?.is_deleted && selectedVendor?.can_manage">
                        <button type="button" @click="openRestoreModal(selectedVendor)"
                                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-black text-emerald-800 hover:bg-emerald-100 transition">
                            Restore Account
                        </button>
                    </template>

                    <a :href="baseEndpoint + '/' + selectedVendor?.id"
                       class="inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-xs font-black text-orange-800 hover:bg-orange-100 transition">
                        Detailed Vendor Page &rarr;
                    </a>

                    <template x-if="!selectedVendor?.is_deleted && selectedVendor?.can_manage">
                        <button type="button" @click="openDeleteModal(selectedVendor)"
                                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-black text-rose-700 hover:bg-rose-100 transition ml-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </button>
                    </template>
                </div>

                <!-- Detail Cards Body -->
                <div class="flex-1 p-8 overflow-y-auto space-y-6">
                    
                    <!-- Financial & Shipment Overview Cards -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Unpaid Earnings</p>
                            <p class="mt-2 text-2xl font-black text-emerald-600" x-text="'GHS ' + formatMoney(selectedVendor?.unpaid_earnings || 0)"></p>
                        </div>
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Paid</p>
                            <p class="mt-2 text-2xl font-black text-slate-900" x-text="'GHS ' + formatMoney(selectedVendor?.total_paid || 0)"></p>
                        </div>
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Shipments Processed</p>
                            <p class="mt-2 text-2xl font-black text-slate-900" x-text="selectedVendor?.shipments_count || 0"></p>
                        </div>
                    </div>

                    <!-- Profile Details Card -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Vendor Profile & Contact Info</h4>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold text-slate-500">Contact Name</p>
                                <p class="mt-1 text-base font-black text-slate-900" x-text="selectedVendor?.name"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Business Name</p>
                                <p class="mt-1 text-base font-black text-slate-900" x-text="selectedVendor?.business_name || 'Not specified'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Email Address</p>
                                <p class="mt-1 text-base font-black text-slate-900" x-text="selectedVendor?.email || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Phone Number</p>
                                <p class="mt-1 text-base font-black text-slate-900" x-text="selectedVendor?.phone || '-'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- MoMo Payout Account Card -->
                    <div class="rounded-3xl border border-emerald-200/80 bg-emerald-50/30 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800">MoMo Payout Account</h4>
                            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-black"
                                  :class="selectedVendor?.has_push_token ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-slate-200 bg-white text-slate-500'"
                                  x-text="selectedVendor?.has_push_token ? 'Push Ready' : 'No Push Token'"></span>
                        </div>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold text-slate-500">Network</p>
                                <p class="mt-1 text-sm font-black uppercase text-slate-900" x-text="selectedVendor?.payout_momo_network || 'None'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Account Number</p>
                                <p class="mt-1 font-mono text-sm font-black text-slate-900" x-text="selectedVendor?.payout_account_number || 'Not provided'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">Account Name</p>
                                <p class="mt-1 text-sm font-black text-slate-900" x-text="selectedVendor?.payout_account_name || 'Not provided'"></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Inline Add/Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @keydown.escape.window="closeModal()">
        <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" @click.stop class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="relative border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" x-text="modalMode === 'add' ? 'Add New Vendor' : (modalMode === 'edit' ? 'Edit Vendor' : 'View Vendor')"></h3>
                                <p class="text-sm text-slate-500 mt-1" x-text="modalMode === 'add' ? 'Create a new vendor account with contact details' : (modalMode === 'edit' ? 'Update vendor information and settings' : 'View vendor account details')"></p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="saveVendor()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Vendor Name <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.name" :disabled="modalMode === 'view'" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 transition-all disabled:bg-slate-50" placeholder="John Doe" required>
                            <template x-if="errors.name"><p class="mt-1.5 text-xs text-rose-600" x-text="errors.name[0]"></p></template>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Business Name <span class="text-slate-400 text-xs font-normal">(Optional)</span></label>
                            <input type="text" x-model="form.business_name" :disabled="modalMode === 'view'" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 transition-all disabled:bg-slate-50" placeholder="Acme Corporation">
                            <template x-if="errors.business_name"><p class="mt-1.5 text-xs text-rose-600" x-text="errors.business_name[0]"></p></template>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Email <span class="text-slate-400 text-xs font-normal">(Optional)</span></label>
                                <input type="email" x-model="form.email" :disabled="modalMode === 'view'" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 transition-all disabled:bg-slate-50" placeholder="vendor@example.com">
                                <template x-if="errors.email"><p class="mt-1.5 text-xs text-rose-600" x-text="errors.email[0]"></p></template>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Phone <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="form.phone" :disabled="modalMode === 'view'" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 transition-all disabled:bg-slate-50" placeholder="+233 24 123 4567" required>
                                <template x-if="errors.phone"><p class="mt-1.5 text-xs text-rose-600" x-text="errors.phone[0]"></p></template>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/30 p-5 space-y-4">
                            <h4 class="text-sm font-bold text-slate-800">MoMo Payout Account</h4>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Network</label>
                                    <select x-model="form.payout_momo_network" :disabled="modalMode === 'view'" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 outline-none">
                                        <option value="">No payout account</option>
                                        <option value="mtn">MTN MoMo</option>
                                        <option value="telecel">Telecel Cash</option>
                                        <option value="airteltigo">AirtelTigo Money</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Account Number</label>
                                    <input type="text" x-model="form.payout_account_number" :disabled="modalMode === 'view'" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 outline-none" placeholder="0551234567">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Account Name</label>
                                <input type="text" x-model="form.payout_account_name" :disabled="modalMode === 'view'" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 outline-none" placeholder="Name on MoMo account">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                        <button type="button" @click="closeModal()" class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border-2 border-slate-200 rounded-xl">Cancel</button>
                        <button x-show="modalMode !== 'view'" type="submit" :disabled="saving" class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-orange-600/20 disabled:opacity-50">
                            <span x-text="saving ? 'Saving...' : 'Save Vendor'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Inline Restore Modal -->
    <div x-show="$store.vendorsRestore.show" x-cloak class="fixed inset-0 z-[110] overflow-y-auto" @keydown.escape.window="$store.vendorsRestore.show = false">
        <div x-show="$store.vendorsRestore.show" class="fixed inset-0 bg-slate-600/60 backdrop-blur-[2px]" @click="$store.vendorsRestore.show = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="$store.vendorsRestore.show" @click.stop class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-5">
                    <h3 class="text-lg font-semibold text-slate-900">Restore Vendor</h3>
                    <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">Are you sure you want to restore <span class="font-semibold text-slate-800" x-text="$store.vendorsRestore.vendor?.name"></span>?</p>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" @click="$store.vendorsRestore.show = false" class="text-sm font-medium text-slate-600">Cancel</button>
                    <button type="button" @click="$store.vendorsRestore.onConfirm && $store.vendorsRestore.onConfirm()" :disabled="$store.vendorsRestore.restoring" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-xl disabled:opacity-50">Restore</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Delete Modal -->
    <div x-show="$store.vendorsDelete.show" x-cloak class="fixed inset-0 z-[110] overflow-y-auto" @keydown.escape.window="$store.vendorsDelete.show = false">
        <div x-show="$store.vendorsDelete.show" class="fixed inset-0 bg-slate-600/60 backdrop-blur-[2px]" @click="$store.vendorsDelete.show = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="$store.vendorsDelete.show" @click.stop class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-5">
                    <h3 class="text-lg font-semibold text-slate-900">Delete Vendor</h3>
                    <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">Are you sure you want to delete <span class="font-semibold text-slate-800" x-text="$store.vendorsDelete.vendor?.name"></span>?</p>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" @click="$store.vendorsDelete.show = false" class="text-sm font-medium text-slate-600">Cancel</button>
                    <button type="button" @click="$store.vendorsDelete.onConfirm && $store.vendorsDelete.onConfirm()" :disabled="$store.vendorsDelete.deleting" class="px-5 py-2 text-sm font-semibold text-white bg-rose-600 rounded-xl disabled:opacity-50">Delete</button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection