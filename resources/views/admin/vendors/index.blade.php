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

<div class="space-y-5" x-data="vendorsTable" data-vendors-config='@json($vendorsConfig)'>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <button type="button" @@click="clearFilter('all')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Total Vendors</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.summary.total_vendors || 0"></p>
            </div>
        </button>
        <button type="button" @@click="setStatusFilter('active', 'Active')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Active Vendors</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.summary.active_vendors || 0"></p>
            </div>
        </button>
        <button type="button" @@click="shipmentCountMin = 1; meta.current_page = 1; loadData()" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">With Shipments</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.summary.vendors_with_shipments || 0"></p>
            </div>
        </button>
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Open Shipments</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.summary.open_shipments || 0"></p>
            </div>
        </div>
        <button type="button" @@click="shipmentStatus = 'delivered'; meta.current_page = 1; loadData()" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Delivered</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.summary.delivered_shipments || 0"></p>
            </div>
        </button>
        <button type="button" @@click="earningsStatus = 'approved'; meta.current_page = 1; loadData()" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Unpaid Earnings</p>
                <p class="mt-1 truncate text-xl font-extrabold text-slate-900" x-text="formatMoney(meta.summary.unpaid_earnings || 0)"></p>
            </div>
        </button>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Vendors</h2>
                            <p class="truncate text-sm text-slate-500">Manage sender records, contacts, account status, and shipment activity.</p>
                        </div>
                    </div>
                </div>
                @if(Auth::guard('admin')->user()->hasPermission('vendors.create'))
                <div class="flex lg:justify-end">
                    <button type="button" @@click="openAddModal()" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Vendor
                    </button>
                </div>
                @endif
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search name, business, email, or phone..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                            View
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="col.label"></span>
                                    <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <button type="button" @@click="exportData('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <div class="my-1 border-t border-slate-100"></div>
                            <button type="button" @@click="printData(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="statusFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="deleted">Deleted</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Range</label>
                        <input type="text" x-ref="createdRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Email</label>
                        <select x-model="hasEmail" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any email state</option>
                            <option value="yes">Has email</option>
                            <option value="no">No email</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Business Name</label>
                        <select x-model="hasBusinessName" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any business state</option>
                            <option value="yes">Has business name</option>
                            <option value="no">No business name</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Push Access</label>
                        <select x-model="hasPushToken" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any push state</option>
                            <option value="yes">Push ready</option>
                            <option value="no">No push token</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Commission Override</label>
                        <select x-model="hasCommissionOverride" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any commission state</option>
                            <option value="yes">Has override</option>
                            <option value="no">No override</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Commission Range</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" step="0.01" x-model="commissionMin" placeholder="Min" class="min-w-0 border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <input type="number" min="0" step="0.01" x-model="commissionMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Shipment Count</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="shipmentCountMin" placeholder="Min" class="min-w-0 border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <input type="number" min="0" x-model="shipmentCountMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Shipment Status</label>
                        <select x-model="shipmentStatus" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any shipment status</option>
                            @foreach(\App\Enums\ShipmentStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Shipment Source</label>
                        <select x-model="shipmentSource" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any source</option>
                            @foreach(\App\Enums\ShipmentSource::cases() as $source)
                                <option value="{{ $source->value }}">{{ $source->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Destination Mode</label>
                        <select x-model="destinationMode" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any destination mode</option>
                            @foreach(\App\Enums\ShipmentDestinationMode::cases() as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Last Shipment</label>
                        <input type="text" x-ref="lastShipmentRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Activity</label>
                        <select x-model="activityState" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any activity</option>
                            <option value="has_activity">Has activity</option>
                            <option value="never_logged_in">Never logged in</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Last Activity</label>
                        <input type="text" x-ref="lastActivityRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Earnings</label>
                        <select x-model="earningsStatus" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Any earnings state</option>
                            <option value="approved">Approved unpaid</option>
                            <option value="paid">Paid</option>
                            <option value="none">No earnings</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Unpaid Earnings</label>
                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" step="0.01" x-model="unpaidEarningsMin" placeholder="Min" class="min-w-0 border-0 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <input type="number" min="0" step="0.01" x-model="unpaidEarningsMax" placeholder="Max" class="min-w-0 border-0 border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2" x-show="activeFilterChips().length">
                <template x-for="chip in activeFilterChips()" :key="chip.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        <span x-text="chip.label"></span>
                        <button type="button" @@click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-[1500px] w-full table-fixed divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.name" @@click="sort('name')" class="w-[180px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                <div class="flex items-center gap-1">Name <svg class="h-2.5 w-2.5" :class="sortBy === 'name' ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                            </th>
                            <th x-show="visibleColumns.business_name" @@click="sort('business_name')" class="w-[180px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                <div class="flex items-center gap-1">Business <svg class="h-2.5 w-2.5" :class="sortBy === 'business_name' ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                            </th>
                            <th x-show="visibleColumns.email" @@click="sort('email')" class="w-[230px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Email</th>
                            <th x-show="visibleColumns.phone" @@click="sort('phone')" class="w-[140px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Phone</th>
                            <th x-show="visibleColumns.status" class="w-[100px] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th x-show="visibleColumns.shipments" @@click="sort('shipments_count')" class="w-[105px] cursor-pointer px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Shipments</th>
                            <th x-show="visibleColumns.delivered_shipments" @@click="sort('delivered_shipments_count')" class="w-[105px] cursor-pointer px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Delivered</th>
                            <th x-show="visibleColumns.open_shipments" @@click="sort('open_shipments_count')" class="w-[95px] cursor-pointer px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Open</th>
                            <th x-show="visibleColumns.cancelled_shipments" @@click="sort('cancelled_shipments_count')" class="w-[105px] cursor-pointer px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Cancelled</th>
                            <th x-show="visibleColumns.last_shipment_at" @@click="sort('last_shipment_at')" class="w-[170px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Last Shipment</th>
                            <th x-show="visibleColumns.last_activity_at" @@click="sort('last_activity_at')" class="w-[170px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Last Activity</th>
                            <th x-show="visibleColumns.total_earnings" @@click="sort('total_earnings')" class="w-[135px] cursor-pointer px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Total Earnings</th>
                            <th x-show="visibleColumns.unpaid_earnings" @@click="sort('unpaid_earnings')" class="w-[135px] cursor-pointer px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Unpaid</th>
                            <th x-show="visibleColumns.total_paid" @@click="sort('total_paid')" class="w-[135px] cursor-pointer px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Total Paid</th>
                            <th x-show="visibleColumns.push_ready" class="w-[105px] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Push</th>
                            <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="w-[170px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Created At</th>
                            <th x-show="visibleColumns.actions" class="w-[260px] px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50 bg-transparent">
                        <template x-if="vendors.length === 0 && !loading">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No vendors match the current filters</p>
                                        <button type="button" @@click="clearFilter('all')" class="text-xs font-semibold text-orange-600 hover:underline">Clear filters</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="vendor in vendors" :key="vendor.id">
                            <tr class="hover:bg-slate-50/70">
                                <td x-show="visibleColumns.name" class="px-4 py-3 align-top">
                                    <a :href="baseEndpoint + '/' + vendor.id" class="block break-words font-bold leading-5 text-slate-900 hover:text-orange-700 hover:underline" x-text="vendor.name"></a>
                                </td>
                                <td x-show="visibleColumns.business_name" class="break-words px-4 py-3 align-top font-semibold text-slate-700" x-text="vendor.business_name || '-'"></td>
                                <td x-show="visibleColumns.email" class="break-all px-4 py-3 align-top text-slate-600" x-text="vendor.email || '-'"></td>
                                <td x-show="visibleColumns.phone" class="whitespace-nowrap px-4 py-3 align-top text-slate-600" x-text="vendor.phone || '-'"></td>
                                <td x-show="visibleColumns.status" class="whitespace-nowrap px-4 py-3 align-top text-center">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusBadgeClass(vendor)" x-text="vendorStatusLabel(vendor)"></span>
                                </td>
                                <td x-show="visibleColumns.shipments" class="whitespace-nowrap px-3 py-3 align-top text-center">
                                    <span class="font-bold text-slate-900" x-text="vendor.shipments_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.delivered_shipments" class="whitespace-nowrap px-3 py-3 align-top text-center">
                                    <span class="font-bold text-slate-900" x-text="vendor.delivered_shipments_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.open_shipments" class="whitespace-nowrap px-3 py-3 align-top text-center">
                                    <span class="font-bold text-slate-900" x-text="vendor.open_shipments_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.cancelled_shipments" class="whitespace-nowrap px-3 py-3 align-top text-center">
                                    <span class="font-bold text-slate-900" x-text="vendor.cancelled_shipments_count || 0"></span>
                                </td>
                                <td x-show="visibleColumns.last_shipment_at" class="whitespace-nowrap px-4 py-3 align-top text-slate-600" x-text="formatDateTime(vendor.last_shipment_at)"></td>
                                <td x-show="visibleColumns.last_activity_at" class="whitespace-nowrap px-4 py-3 align-top text-slate-600" x-text="formatDateTime(vendor.last_activity_at)"></td>
                                <td x-show="visibleColumns.total_earnings" class="whitespace-nowrap px-4 py-3 align-top text-right font-semibold text-slate-700" x-text="formatMoney(vendor.total_earnings)"></td>
                                <td x-show="visibleColumns.unpaid_earnings" class="whitespace-nowrap px-4 py-3 align-top text-right font-semibold text-slate-700" x-text="formatMoney(vendor.unpaid_earnings)"></td>
                                <td x-show="visibleColumns.total_paid" class="whitespace-nowrap px-4 py-3 align-top text-right font-semibold text-slate-700" x-text="formatMoney(vendor.total_paid)"></td>
                                <td x-show="visibleColumns.push_ready" class="whitespace-nowrap px-4 py-3 align-top text-center">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="vendor.has_push_token ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-slate-200 bg-slate-50 text-slate-500'" x-text="vendor.has_push_token ? 'Ready' : 'No'"></span>
                                </td>
                                <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-4 py-3 align-top text-slate-600" x-text="formatDateTime(vendor.created_at)"></td>
                                <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 align-top text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a :href="baseEndpoint + '/' + vendor.id" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            View
                                        </a>
                                        <template x-if="vendor.is_deleted && vendor.can_manage">
                                            <button @@click="openRestoreModal(vendor)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100">Restore</button>
                                        </template>
                                        <template x-if="!vendor.is_deleted && vendor.can_manage">
                                            <button @@click="openEditModal(vendor)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                                        </template>
                                        <template x-if="!vendor.is_deleted && vendor.can_manage">
                                            <button @@click="toggleVendorStatus(vendor)" class="rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold" :class="vendor.is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100'" x-text="vendor.is_active ? 'Disable' : 'Enable'"></button>
                                        </template>
                                        <template x-if="!vendor.is_deleted && vendor.can_manage">
                                            <button @@click="openDeleteModal(vendor)" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="vendors.length === 0 && !loading">
                    <div class="px-4 py-12 text-center text-sm text-slate-400">No vendors match the current filters.</div>
                </template>
                <template x-for="vendor in vendors" :key="vendor.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="baseEndpoint + '/' + vendor.id" class="truncate text-sm font-extrabold text-slate-900 hover:text-orange-700" x-text="vendor.name"></a>
                                <p class="mt-1 text-xs font-semibold text-slate-500" x-text="vendor.business_name || 'No business name'"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="statusBadgeClass(vendor)" x-text="vendorStatusLabel(vendor)"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Phone</p><p class="font-bold text-slate-800" x-text="vendor.phone || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Shipments</p><p class="font-bold text-slate-800" x-text="vendor.shipments_count || 0"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Open</p><p class="font-bold text-slate-800" x-text="vendor.open_shipments_count || 0"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Delivered</p><p class="font-bold text-slate-800" x-text="vendor.delivered_shipments_count || 0"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Last Shipment</p><p class="font-bold text-slate-800" x-text="formatDateTime(vendor.last_shipment_at)"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Unpaid</p><p class="font-bold text-slate-800" x-text="formatMoney(vendor.unpaid_earnings)"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Email</p><p class="break-words font-bold text-slate-800" x-text="vendor.email || '-'"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Created</p><p class="font-bold text-slate-800" x-text="formatDateTime(vendor.created_at)"></p></div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a :href="baseEndpoint + '/' + vendor.id" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">View</a>
                            <template x-if="vendor.is_deleted && vendor.can_manage">
                                <button @@click="openRestoreModal(vendor)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Restore</button>
                            </template>
                            <template x-if="!vendor.is_deleted && vendor.can_manage">
                                <button @@click="openEditModal(vendor)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">Edit</button>
                            </template>
                            <template x-if="!vendor.is_deleted && vendor.can_manage">
                                <button @@click="toggleVendorStatus(vendor)" class="rounded-lg border px-3 py-2 text-xs font-bold" :class="vendor.is_active ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-orange-200 bg-orange-50 text-orange-700'" x-text="vendor.is_active ? 'Disable' : 'Enable'"></button>
                            </template>
                            <template x-if="!vendor.is_deleted && vendor.can_manage">
                                <button @@click="openDeleteModal(vendor)" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">Delete</button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-600">Rows</span>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700">
                                    <span x-text="perPage"></span>
                                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display:none">
                                    <button type="button" @@click="setPerPage(10); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 10 ? 'bg-slate-100' : ''">10</button>
                                    <button type="button" @@click="setPerPage(25); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 25 ? 'bg-slate-100' : ''">25</button>
                                    <button type="button" @@click="setPerPage(50); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 50 ? 'bg-slate-100' : ''">50</button>
                                    <button type="button" @@click="setPerPage(100); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 100 ? 'bg-slate-100' : ''">100</button>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @@click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                            <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @keydown.escape.window="closeModal()"
    >
        <!-- Backdrop -->
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @click="closeModal()"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl"
            >
                <!-- Header -->
                <div class="relative border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <!-- Icon Badge -->
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <!-- Title & Description -->
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" x-text="modalMode === 'add' ? 'Add New Vendor' : (modalMode === 'edit' ? 'Edit Vendor' : 'View Vendor')"></h3>
                                <p class="text-sm text-slate-500 mt-1" x-text="modalMode === 'add' ? 'Create a new vendor account with contact details' : (modalMode === 'edit' ? 'Update vendor information and settings' : 'View vendor account details')"></p>
                            </div>
                        </div>
                        <!-- Close Button -->
                        <button @click="closeModal()" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <form @submit.prevent="saveVendor()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Vendor Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.name"
                                        :disabled="modalMode === 'view'"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="John Doe"
                                        required
                                    >
                                </div>
                                <template x-if="errors.name">
                                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="errors.name[0]"></span>
                                    </p>
                                </template>
                            </div>

                            <!-- Business Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Business Name <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.business_name"
                                        :disabled="modalMode === 'view'"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="Acme Corporation"
                                    >
                                </div>
                                <template x-if="errors.business_name">
                                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="errors.business_name[0]"></span>
                                    </p>
                                </template>
                            </div>

                            <!-- Email & Phone Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Email <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="email"
                                            x-model="form.email"
                                            :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="vendor@example.com"
                                        >
                                    </div>
                                    <template x-if="errors.email">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.email[0]"></span>
                                        </p>
                                    </template>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Phone <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <input
                                            type="text"
                                            x-model="form.phone"
                                        :disabled="modalMode === 'view'"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500"
                                            placeholder="+233 24 123 4567"
                                            required
                                        >
                                    </div>
                                    <template x-if="errors.phone">
                                        <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="errors.phone[0]"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                                            <!-- Status Toggle -->
                            <div x-show="modalMode !== 'view'" class="bg-slate-50/70 rounded-2xl p-5 border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 flex items-center justify-center shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Account Status</h4>
                                            <p class="text-xs text-slate-500" x-text="form.is_active ? 'Vendor can access portal' : 'Vendor access disabled'"></p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @@click="form.is_active = !form.is_active"
                                        :class="form.is_active ? 'bg-orange-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-orange-100 focus:ring-offset-2 shadow-sm"
                                    >
                                        <span
                                            :class="form.is_active ? 'translate-x-7' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                                        ></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Commission Rate Override -->
                            <template x-if="modalMode !== 'view'">
                                <div class="bg-amber-50/40 rounded-2xl p-5 border border-amber-200/60">
                                    <div class="flex items-start gap-3 mb-4">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-sm shrink-0">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Commission Rate Override</h4>
                                            <p class="text-xs text-slate-500 mt-0.5">Overrides the global commission per delivered package for this vendor only.</p>
                                        </div>
                                    </div>

                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Rate per package (GHS)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-slate-400 pointer-events-none">GHS</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model="form.commission_rate_override"
                                            placeholder="Leave blank to use global default"
                                            class="w-full pl-14 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        >
                                    </div>
                                    <p class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                        <strong>Leave blank</strong> to use the global default (set on Settings → Revenue & Pricing).
                                        <br>
                                        <strong>Enter 0</strong> to give this vendor no commission per package.
                                    </p>
                                    <template x-if="errors.commission_rate_override">
                                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.commission_rate_override[0]"></p>
                                    </template>
                                </div>
                            </template>

                            <!-- View mode additional info -->
                            <template x-if="modalMode === 'view'">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Status Card -->
                                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-4 border border-emerald-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-xs font-bold text-slate-700">Account Status</span>
                                        </div>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold shadow-sm"
                                            :class="form.is_active ? 'bg-emerald-500 text-white' : 'bg-slate-400 text-white'"
                                            x-text="form.is_active ? 'Active' : 'Inactive'"
                                        ></span>
                                    </div>

                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                            <p class="text-xs text-slate-500">
                                <span class="text-rose-500">*</span> Required fields
                            </p>
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @@click="closeModal()"
                                    class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all shadow-sm"
                                >
                                    <span x-text="modalMode === 'view' ? 'Close' : 'Cancel'"></span>
                                </button>
                                <button
                                    x-show="modalMode !== 'view'"
                                    type="submit"
                                    :disabled="saving"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-orange-600/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                                >
                                    <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="saving ? 'Saving...' : (modalMode === 'add' ? 'Create Vendor' : 'Save Changes')"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div
        x-show="$store.vendorsRestore.show"
        x-cloak
        class="fixed inset-0 z-[110] overflow-y-auto"
        @keydown.escape.window="$store.vendorsRestore.show = false"
    >
        <div x-show="$store.vendorsRestore.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-600/60 backdrop-blur-[2px]"
             @click="$store.vendorsRestore.show = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="$store.vendorsRestore.show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-sm bg-white rounded-2xl shadow-[0_18px_45px_rgba(15,23,42,0.28)] border border-slate-200/80 overflow-hidden"
            >
                <div class="px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Restore Vendor</h3>
                            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">
                                Are you sure you want to restore
                                <span class="font-semibold text-slate-800" x-text="$store.vendorsRestore.vendor?.name"></span>?
                                Their account will be recovered in an inactive state.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-5">
                    <button
                        type="button"
                        @click="$store.vendorsRestore.show = false"
                        class="text-sm font-medium text-slate-600 hover:text-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="$store.vendorsRestore.onConfirm && $store.vendorsRestore.onConfirm()"
                        :disabled="$store.vendorsRestore.restoring"
                        class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-full shadow-lg shadow-emerald-600/25 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="$store.vendorsRestore.restoring">Restoring...</span>
                        <span x-show="!$store.vendorsRestore.restoring">Restore</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
        x-show="$store.vendorsDelete.show"
        x-cloak
        class="fixed inset-0 z-[110] overflow-y-auto"
        @keydown.escape.window="$store.vendorsDelete.show = false"
    >
        <!-- Backdrop -->
        <div x-show="$store.vendorsDelete.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-600/60 backdrop-blur-[2px]"
             @click="$store.vendorsDelete.show = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="$store.vendorsDelete.show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-lg bg-white rounded-2xl shadow-[0_18px_45px_rgba(15,23,42,0.28)] border border-slate-200/80 overflow-hidden"
            >
                <div class="px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Delete Vendor</h3>
                            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">
                                Are you sure you want to delete
                                <span class="font-semibold text-slate-800" x-text="$store.vendorsDelete.vendor?.name"></span>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-5">
                    <button
                        type="button"
                        @click="$store.vendorsDelete.show = false"
                        class="text-sm font-medium text-slate-600 hover:text-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="$store.vendorsDelete.onConfirm && $store.vendorsDelete.onConfirm()"
                        :disabled="$store.vendorsDelete.deleting"
                        class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-full shadow-lg shadow-rose-600/25 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="$store.vendorsDelete.deleting">Deleting...</span>
                        <span x-show="!$store.vendorsDelete.deleting">Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
