@extends('admin.layouts.app')

@section('title', 'Vendor - ' . $vendor->name)
@section('breadcrumb-parent', 'Vendors')
@section('breadcrumb-current', $vendor->name)

@php
$vendorConfig = [
    'vendor' => $vendor,
    'shipmentsEndpoint' => route('admin.vendors.shipments', $vendor),
    'packagesEndpoint' => route('admin.vendors.packages', $vendor),
    'activityLogsEndpoint' => route('admin.vendors.activity-logs', $vendor),
    'otpLogsEndpoint' => route('admin.vendors.otp-logs', $vendor),
    'payoutsEndpoint' => route('admin.vendors.payouts-data', $vendor),
    'createPayoutEndpoint' => route('admin.vendors.payouts.store', $vendor),
    'markPayoutSentEndpoint' => route('admin.vendor-payouts.mark-sent', ['payout' => '__PAYOUT__']),
    'confirmPayoutEndpoint' => route('admin.vendor-payouts.confirm', ['payout' => '__PAYOUT__']),
    'updateEndpoint' => route('admin.vendors.update', $vendor),
    'toggleActiveEndpoint' => route('admin.vendors.toggle-active', $vendor),
    'restoreEndpoint' => route('admin.vendors.restore', $vendor),
    'deleteEndpoint' => route('admin.vendors.destroy', $vendor),
    'isDeleted' => $vendor->trashed(),
    'canManage' => $canManage,
    'statuses' => $statuses,
    'globalCommissionRate' => number_format($globalCommissionRate, 2),
    'payoutSummary' => $payoutSummary,
];
@endphp

@section('content')
<div x-data="vendorShow()" data-vendor-show-config="{{ json_encode($vendorConfig) }}" class="space-y-6">

    @if($vendor->trashed())
    <!-- Deleted Vendor Banner -->
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-100">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-800">This vendor was deleted on {{ $vendor->deleted_at->format('M d, Y \a\t H:i') }}</p>
                    <p class="text-xs text-red-600">All API access has been revoked.</p>
                </div>
            </div>
            @if($canManage)
            <button
                @@click="showRestoreModal = true"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Restore Vendor
            </button>
            @endif
        </div>
    </div>
    @endif

    <!-- Hero Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ route('admin.vendors.index') }}" class="inline-flex h-11 w-auto shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back</span>
                </a>

                <div class="ml-auto flex w-auto max-w-[calc(100%-5.75rem)] flex-wrap items-center justify-end gap-2 sm:max-w-none">
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black {{ $vendor->is_active ? 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/30' : 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30' }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $vendor->is_active ? 'bg-emerald-300' : 'bg-slate-300' }}"></span>
                        {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($canManage)
                        <button
                            type="button"
                            @@click="openEditModal()"
                            class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Edit
                        </button>
                        <button
                            type="button"
                            @@click="showToggleModal = true"
                            class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-3 text-xs font-black transition"
                            :class="vendor.is_active
                                ? 'border-amber-400/45 bg-amber-500/15 text-amber-100 hover:bg-amber-500/25'
                                : 'border-emerald-400/45 bg-emerald-500/15 text-emerald-100 hover:bg-emerald-500/25'"
                        >
                            <span x-text="vendor.is_active ? 'Deactivate' : 'Activate'"></span>
                        </button>
                        <button
                            type="button"
                            @@click="showDeleteModal = true"
                            class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-rose-400/45 bg-rose-500/15 px-3 text-xs font-black text-rose-100 transition hover:bg-rose-500/25"
                        >
                            Delete
                        </button>
                    @endif
                </div>
            </div>

            <div class="relative mt-5 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[620px] lg:shrink">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-2xl font-black text-white shadow-lg shadow-orange-950/25">
                            {{ strtoupper(substr($vendor->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Vendor Workspace</p>
                            <h1 class="mt-1 max-w-4xl break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl xl:text-2xl 2xl:text-3xl">{{ $vendor->name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                @if($vendor->business_name)
                                    <span>{{ $vendor->business_name }}</span>
                                    <span class="text-slate-600">/</span>
                                @endif
                                <span>{{ $vendor->phone }}</span>
                                @if($vendor->email)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ $vendor->email }}</span>
                                @endif
                                <span class="text-slate-600">/</span>
                                <span>Created {{ $vendor->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-3 lg:ml-auto lg:w-[620px] lg:shrink-0 2xl:w-[680px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ number_format($shipmentStats['total']) }} Shipments</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">{{ number_format($packagesCount) }} Packages</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg" x-text="'GHS ' + formatMoney(payouts.summary.available_balance)"></p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">Unpaid payout</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg" x-text="'GHS ' + formatMoney(payouts.summary.total_paid)"></p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">Payout paid</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    </section>

    <!-- Tabs Section -->
    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @@click="setActiveTab('shipments')"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'shipments' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Shipments
            </button>
            <button type="button" @@click="setActiveTab('packages')"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'packages' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 8-9-5-9 5 9 5 9-5ZM3 8v8l9 5 9-5V8"/>
                </svg>
                Packages
            </button>
            <button type="button" @@click="setActiveTab('activity')"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'activity' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Activity
            </button>
            <button type="button" @@click="setActiveTab('otp')"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'otp' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/>
                </svg>
                OTP Logs
            </button>
            <button type="button" @@click="setActiveTab('payouts')"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'payouts' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/>
                </svg>
                Payouts
            </button>
        </div>
    </section>

    <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PAYOUTS TAB                                            --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'payouts'" x-cloak>
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Payouts</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Review earnings, pay vendors, and track payout history.</p>
                        </div>
                        <button type="button" @@click="openPayoutModal()" :disabled="!payouts.summary.can_request_payout"
                            class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-45">
                            Pay Vendor
                        </button>
                    </div>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/60 px-5 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:gap-4">
                                <span class="text-xs font-black uppercase tracking-wide text-orange-600">Available To Pay</span>
                                <span class="text-3xl font-black text-slate-950" x-text="'GHS ' + formatMoney(payouts.summary.available_balance)"></span>
                                <span class="inline-flex w-fit rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-wide"
                                    :class="payouts.summary.can_request_payout ? 'border-orange-200 bg-white text-orange-700' : 'border-slate-200 bg-white text-slate-500'"
                                    x-text="payouts.summary.can_request_payout ? 'Ready to pay' : 'Below minimum'"></span>
                            </div>
                            <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm">
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-wide text-slate-400">Total Earned</span>
                                    <span class="mt-1 block font-black text-slate-900" x-text="'GHS ' + formatMoney(payouts.summary.total_earned)"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-wide text-slate-400">Total Paid</span>
                                    <span class="mt-1 block font-black text-slate-900" x-text="'GHS ' + formatMoney(payouts.summary.total_paid)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="relative w-full xl:max-w-md">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <input type="text" x-model="payouts.search" @@input.debounce.500ms="payouts.page = 1; loadPayouts()" placeholder="Search reference, phone, or notes..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="payouts.showFilters = !payouts.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="payouts.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="payouts.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="payouts.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                                <select x-model="payouts.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="sent">Sent</option>
                                    <option value="confirmed">Confirmed</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Method</label>
                                <select x-model="payouts.method" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All methods</option>
                                    <option value="momo">MoMo</option>
                                    <option value="bank">Bank</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="payouts.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('payouts')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('payouts')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('payouts').length">
                        <template x-for="chip in activeFilterChips('payouts')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('payouts', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-white">
                    <div x-show="payouts.loading" class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Date</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Amount</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Method</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Reference</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Processed By</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="payouts.data.length === 0 && !payouts.loading">
                                    <tr><td colspan="7" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No payout history found</td></tr>
                                </template>
                                <template x-for="payout in payouts.data" :key="payout.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600" x-text="formatDateTime(payout.created_at)"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-black text-slate-900" x-text="'GHS ' + formatMoney(payout.amount)"></td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700" x-text="payoutMethodLabel(payout.payment_method) || '-'"></td>
                                        <td class="px-4 py-3 font-semibold text-slate-700" x-text="payout.payment_reference || '-'"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black" :class="payoutStatusClass(payout.status)" x-text="payoutStatusLabel(payout.status)"></span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600" x-text="payout.processed_by?.name || '-'"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <button x-show="payout.status === 'pending'" @@click="openMarkSentModal(payout)" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Mark Sent</button>
                                            <button x-show="payout.status === 'sent'" @@click="openConfirmPayoutModal(payout)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100">Confirm</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="payouts.meta.from || 0"></span> to <span x-text="payouts.meta.to || 0"></span> of <span x-text="payouts.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="payoutsPrevPage()" :disabled="payouts.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="payouts.meta.current_page || 1"></span> / <span x-text="payouts.meta.last_page || 1"></span></div>
                                <button @@click="payoutsNextPage()" :disabled="payouts.page >= payouts.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- SHIPMENTS TAB                                          --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'shipments'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="text" x-model="shipments.search" @@input.debounce.500ms="shipments.page = 1; loadShipments()" placeholder="Search shipments..."
                                class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="shipments.showFilters = !shipments.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="shipments.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="shipments.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <!-- View Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in shipments.columns" :key="col.key">
                                    <button type="button" @@click="toggleShipmentColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="shipments.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('shipments'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('shipments'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="shipments.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="shipmentsCreatedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Shipment Status</label>
                                <select x-model="shipments.status" @@change="shipments.statusName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Package Count</label>
                                <select x-model="shipments.packageCount" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">Any count</option>
                                    <option value="one">One package</option>
                                    <option value="multiple">Multiple packages</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Recipient Phone</label>
                                <input type="text" x-model="shipments.recipientPhone" placeholder="Phone number" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Location</label>
                                <input type="text" x-model="shipments.location" placeholder="Town, district, or region" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="shipments.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('shipments')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('shipments')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('shipments').length">
                        <template x-for="chip in activeFilterChips('shipments')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('shipments', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Table -->
                <div class="relative overflow-hidden bg-white">
                    <div x-show="shipments.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="shipments.visibleColumns.shipment_number" @@click="sortShipments('shipment_number')" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 cursor-pointer">
                                        <div class="flex items-center">SHIPMENT #<svg class="w-2.5 h-2.5 ml-1" :class="shipments.sortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="shipments.visibleColumns.recipient" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">RECIPIENT</th>
                                    <th x-show="shipments.visibleColumns.location" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">LOCATION</th>
                                    <th x-show="shipments.visibleColumns.items" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ITEMS</th>
                                    <th x-show="shipments.visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">STATUS</th>
                                    <th x-show="shipments.visibleColumns.created_at" @@click="sortShipments('created_at')" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 cursor-pointer">
                                        <div class="flex items-center">CREATED<svg class="w-2.5 h-2.5 ml-1" :class="shipments.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="shipments.data.length === 0 && !shipments.loading">
                                    <tr><td :colspan="visibleColumnCount('shipments')" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No shipments found</td></tr>
                                </template>
                                <template x-for="shipment in shipments.data" :key="shipment.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="shipments.visibleColumns.shipment_number" class="px-4 py-3 whitespace-nowrap text-xs font-black text-orange-700" x-text="shipment.shipment_number"></td>
                                        <td x-show="shipments.visibleColumns.recipient" class="px-4 py-3 whitespace-nowrap">
                                            <p class="text-xs font-bold text-slate-900" x-text="shipment.recipient_name"></p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-500" x-text="shipment.recipient_phone"></p>
                                        </td>
                                        <td x-show="shipments.visibleColumns.location" class="px-4 py-3 whitespace-nowrap">
                                            <p class="text-xs font-semibold text-slate-600" x-text="shipment.region || '-'"></p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-400" x-text="shipment.district || ''"></p>
                                        </td>
                                        <td x-show="shipments.visibleColumns.items" class="px-4 py-3 whitespace-nowrap text-center font-black text-slate-900" x-text="shipment.items_count">
                                        </td>
                                        <td x-show="shipments.visibleColumns.status" class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="{
                                                    'bg-slate-100 text-slate-700': shipment.status === 'draft',
                                                    'bg-blue-100 text-blue-700': ['submitted', 'invoice_sent', 'invoice_accepted'].includes(shipment.status),
                                                    'bg-violet-100 text-violet-700': ['pickup_assigned', 'picked_up', 'at_warehouse', 'sorted'].includes(shipment.status),
                                                    'bg-amber-100 text-amber-700': ['in_transit', 'at_destination', 'out_for_delivery'].includes(shipment.status),
                                                    'bg-emerald-100 text-emerald-700': shipment.status === 'delivered',
                                                    'bg-rose-100 text-rose-700': shipment.status === 'cancelled'
                                                }" x-text="shipment.status_label"></span>
                                        </td>
                                        <td x-show="shipments.visibleColumns.created_at" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-600" x-text="formatDateTime(shipment.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="shipments.meta.from || 0"></span> to <span x-text="shipments.meta.to || 0"></span> of <span x-text="shipments.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="shipmentsPrevPage()" :disabled="shipments.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="shipments.meta.current_page || 1"></span> / <span x-text="shipments.meta.last_page || 1"></span></div>
                                <button @@click="shipmentsNextPage()" :disabled="shipments.page >= shipments.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PACKAGES TAB                                           --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'packages'" x-cloak>
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="w-full xl:max-w-md">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="packages.search" @@input.debounce.500ms="packages.page = 1; loadPackages()" placeholder="Search shipment, package, recipient, phone..."
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                        <button type="button" @@click="packages.showFilters = !packages.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="packages.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="packages.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="packages.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="packagesCreatedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Package Status</label>
                                <select x-model="packages.status" @@change="packages.statusName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="picked_up">Picked Up</option>
                                    <option value="at_warehouse">At Warehouse</option>
                                    <option value="sorted">Sorted</option>
                                    <option value="in_transit">In Transit</option>
                                    <option value="out_for_delivery">Out for Delivery</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="returned">Returned</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery Method</label>
                                <select x-model="packages.deliveryMethod" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All methods</option>
                                    <option value="direct">Recipient delivery</option>
                                    <option value="bus_handoff">Bus handoff</option>
                                    <option value="pickup">Self pickup</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Recipient Phone</label>
                                <input type="text" x-model="packages.recipientPhone" placeholder="Phone number" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Location</label>
                                <input type="text" x-model="packages.location" placeholder="Town, district, or region" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Quantity Range</label>
                                <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                                    <input type="number" min="1" x-model="packages.quantityMin" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                                    <div class="w-px bg-slate-200"></div>
                                    <input type="number" min="1" x-model="packages.quantityMax" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="packages.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('packages')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('packages')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('packages').length">
                        <template x-for="chip in activeFilterChips('packages')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('packages', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-white">
                    <div x-show="packages.loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Package</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Shipment</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Recipient</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Location</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Qty</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Method</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="packages.data.length === 0 && !packages.loading">
                                    <tr><td colspan="8" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No packages found for this vendor.</td></tr>
                                </template>
                                <template x-for="pkg in packages.data" :key="pkg.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="max-w-[320px] px-4 py-3">
                                            <p class="text-sm font-black text-slate-900" x-text="pkg.description || 'Package'"></p>
                                            <p class="mt-1 font-mono text-[11px] font-semibold text-slate-500" x-text="pkg.tracking_code || 'No tracking code'"></p>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a :href="'/admin/orders/' + pkg.shipment_id" class="text-xs font-black text-orange-700 hover:underline" x-text="pkg.shipment_number || '-'"></a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-800" x-text="pkg.recipient_name || '-'"></p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-500" x-text="pkg.recipient_phone || '-'"></p>
                                        </td>
                                        <td class="max-w-[280px] px-4 py-3 text-xs font-semibold text-slate-600" x-text="pkg.location || '-'"></td>
                                        <td class="px-4 py-3 text-center font-black text-slate-900" x-text="pkg.quantity || 1"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-[10px] font-black"
                                                :class="pkg.delivery_method === 'bus_handoff' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-50 text-slate-600'"
                                                x-text="pkg.delivery_method_label"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="statusBadgeClass(pkg.status)"
                                                x-text="pkg.status_label"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a :href="'/admin/orders/' + pkg.shipment_id" class="inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Open</a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="divide-y divide-slate-100 lg:hidden">
                        <template x-if="packages.data.length === 0 && !packages.loading">
                            <div class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No packages found for this vendor.</div>
                        </template>
                        <template x-for="pkg in packages.data" :key="pkg.id">
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-slate-900" x-text="pkg.description || 'Package'"></p>
                                        <p class="mt-1 font-mono text-[11px] font-semibold text-slate-500" x-text="pkg.tracking_code || 'No tracking code'"></p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(pkg.status)" x-text="pkg.status_label"></span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                    <div><p class="font-black uppercase tracking-wide text-slate-400">Shipment</p><a :href="'/admin/orders/' + pkg.shipment_id" class="font-black text-orange-700" x-text="pkg.shipment_number || '-'"></a></div>
                                    <div><p class="font-black uppercase tracking-wide text-slate-400">Qty</p><p class="font-black text-slate-900" x-text="pkg.quantity || 1"></p></div>
                                    <div><p class="font-black uppercase tracking-wide text-slate-400">Recipient</p><p class="font-bold text-slate-800" x-text="pkg.recipient_name || '-'"></p><p class="font-semibold text-slate-500" x-text="pkg.recipient_phone || '-'"></p></div>
                                    <div><p class="font-black uppercase tracking-wide text-slate-400">Method</p><p class="font-bold text-slate-800" x-text="pkg.delivery_method_label"></p></div>
                                    <div class="col-span-2"><p class="font-black uppercase tracking-wide text-slate-400">Location</p><p class="font-bold text-slate-800" x-text="pkg.location || '-'"></p></div>
                                </div>
                                <a :href="'/admin/orders/' + pkg.shipment_id" class="mt-4 inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">Open</a>
                            </div>
                        </template>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="packages.meta.from || 0"></span> to <span x-text="packages.meta.to || 0"></span> of <span x-text="packages.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="packagesPrevPage()" :disabled="packages.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="packages.meta.current_page || 1"></span> / <span x-text="packages.meta.last_page || 1"></span></div>
                                <button @@click="packagesNextPage()" :disabled="packages.page >= packages.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- ACTIVITY LOGS TAB                                      --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'activity'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="text" x-model="activity.search" @@input.debounce.500ms="activity.page = 1; loadActivityLogs()" placeholder="Search activity..."
                                class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="activity.showFilters = !activity.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="activity.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="activity.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <!-- View Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in activity.columns" :key="col.key">
                                    <button type="button" @@click="toggleActivityColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="activity.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activity.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Activity Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="activityCreatedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Action</label>
                                <select x-model="activity.action" @@change="activity.actionName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All Actions</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="register">Register</option>
                                    <option value="login_otp_requested">Login OTP Requested</option>
                                    <option value="verify_phone">Verify Phone</option>
                                    <option value="profile_updated">Profile Updated</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Device Type</label>
                                <select x-model="activity.deviceType" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All devices</option>
                                    <option value="web">Web</option>
                                    <option value="mobile">Mobile</option>
                                    <option value="desktop">Desktop</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">IP Address</label>
                                <input type="text" x-model="activity.ipAddress" placeholder="IP address" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="activity.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('activity')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('activity')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('activity').length">
                        <template x-for="chip in activeFilterChips('activity')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('activity', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Table -->
                <div class="relative overflow-hidden bg-white">
                    <div x-show="activity.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="activity.visibleColumns.action" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ACTION</th>
                                    <th x-show="activity.visibleColumns.description" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">DESCRIPTION</th>
                                    <th x-show="activity.visibleColumns.device" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">DEVICE</th>
                                    <th x-show="activity.visibleColumns.ip_address" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">IP ADDRESS</th>
                                    <th x-show="activity.visibleColumns.created_at" @@click="sortActivity('created_at')" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 cursor-pointer">
                                        <div class="flex items-center">DATE<svg class="w-2.5 h-2.5 ml-1" :class="activity.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="activity.data.length === 0 && !activity.loading">
                                    <tr><td :colspan="visibleColumnCount('activity')" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No activity logs found</td></tr>
                                </template>
                                <template x-for="log in activity.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="activity.visibleColumns.action" class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-700': ['login', 'register', 'verify_phone'].includes(log.action),
                                                    'bg-blue-100 text-blue-700': ['profile_updated', 'login_otp_requested'].includes(log.action),
                                                    'bg-slate-100 text-slate-700': log.action === 'logout'
                                                }" x-text="log.action.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                        </td>
                                        <td x-show="activity.visibleColumns.description" class="max-w-xs px-4 py-3 text-xs font-semibold text-slate-600" x-text="log.description || '-'"></td>
                                        <td x-show="activity.visibleColumns.device" class="px-4 py-3 whitespace-nowrap">
                                            <p class="text-xs font-bold text-slate-900" x-text="log.device_name || 'Unknown'"></p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-400" x-text="(log.device_type || '') + ' ' + (log.os_version || '')"></p>
                                        </td>
                                        <td x-show="activity.visibleColumns.ip_address" class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-slate-600" x-text="log.ip_address || '-'"></td>
                                        <td x-show="activity.visibleColumns.created_at" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="activity.meta.from || 0"></span> to <span x-text="activity.meta.to || 0"></span> of <span x-text="activity.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="activityPrevPage()" :disabled="activity.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="activity.meta.current_page || 1"></span> / <span x-text="activity.meta.last_page || 1"></span></div>
                                <button @@click="activityNextPage()" :disabled="activity.page >= activity.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- OTP LOGS TAB                                           --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'otp'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-center xl:justify-end">
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="otp.showFilters = !otp.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="otp.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="otp.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <!-- View Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in otp.columns" :key="col.key">
                                    <button type="button" @@click="toggleOtpColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="otp.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50/40">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('otp'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('otp'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="otp.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Created Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="otpCreatedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Purpose</label>
                                <select x-model="otp.purpose" @@change="otp.purposeName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All Purposes</option>
                                    <option value="registration">Registration</option>
                                    <option value="login">Login</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">OTP Status</label>
                                <select x-model="otp.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All statuses</option>
                                    <option value="verified">Verified</option>
                                    <option value="pending">Pending</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Expires Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="otpExpiresRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="otp.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('otp')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('otp')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('otp').length">
                        <template x-for="chip in activeFilterChips('otp')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('otp', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Table -->
                <div class="relative overflow-hidden bg-white">
                    <div x-show="otp.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="otp.visibleColumns.code" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">CODE</th>
                                    <th x-show="otp.visibleColumns.purpose" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">PURPOSE</th>
                                    <th x-show="otp.visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">STATUS</th>
                                    <th x-show="otp.visibleColumns.expires_at" @@click="sortOtp('created_at')" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 cursor-pointer">
                                        <div class="flex items-center">EXPIRES AT<svg class="w-2.5 h-2.5 ml-1" :class="otp.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="otp.visibleColumns.verified_at" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">VERIFIED AT</th>
                                    <th x-show="otp.visibleColumns.created_at" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">CREATED</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="otp.data.length === 0 && !otp.loading">
                                    <tr><td :colspan="visibleColumnCount('otp')" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No OTP logs found</td></tr>
                                </template>
                                <template x-for="log in otp.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="otp.visibleColumns.code" class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-mono font-bold text-slate-900 tracking-wider" x-text="log.code"></span>
                                        </td>
                                        <td x-show="otp.visibleColumns.purpose" class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="{ 'bg-blue-100 text-blue-700': log.purpose === 'registration', 'bg-emerald-100 text-emerald-700': log.purpose === 'login' }"
                                                x-text="log.purpose.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                        </td>
                                        <td x-show="otp.visibleColumns.status" class="px-4 py-3 whitespace-nowrap text-center">
                                            <template x-if="log.is_verified">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-black bg-emerald-100 text-emerald-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    Verified
                                                </span>
                                            </template>
                                            <template x-if="!log.is_verified && log.is_expired">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-black bg-rose-100 text-rose-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                    Expired
                                                </span>
                                            </template>
                                            <template x-if="!log.is_verified && !log.is_expired">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-black bg-amber-100 text-amber-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                                    Pending
                                                </span>
                                            </template>
                                        </td>
                                        <td x-show="otp.visibleColumns.expires_at" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-600" x-text="formatDateTime(log.expires_at)"></td>
                                        <td x-show="otp.visibleColumns.verified_at" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-600" x-text="log.verified_at ? formatDateTime(log.verified_at) : '-'"></td>
                                        <td x-show="otp.visibleColumns.created_at" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="otp.meta.from || 0"></span> to <span x-text="otp.meta.to || 0"></span> of <span x-text="otp.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="otpPrevPage()" :disabled="otp.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="otp.meta.current_page || 1"></span> / <span x-text="otp.meta.last_page || 1"></span></div>
                                <button @@click="otpNextPage()" :disabled="otp.page >= otp.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <!-- Pay Vendor Modal -->
    <div x-show="showPayoutModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="closePayoutModal()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closePayoutModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <form @@submit.prevent="submitPayout()" class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900">Pay Vendor</h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Available balance: <span x-text="'GHS ' + formatMoney(payouts.summary.available_balance)"></span></p>
                            </div>
                        </div>
                        <button type="button" @@click="closePayoutModal()" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Amount</label>
                        <input type="number" step="0.01" min="1" x-model="payoutForm.amount" required class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Method</label>
                        <select x-model="payoutForm.payment_method" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="momo">MOMO</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div x-show="payoutForm.payment_method === 'momo'">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Phone</label>
                        <input type="text" x-model="payoutForm.payment_phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Reference <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="payoutForm.payment_reference" required class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Notes</label>
                        <textarea rows="3" x-model="payoutForm.notes" class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <button type="button" @@click="closePayoutModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" :disabled="payoutSaving" class="rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700 disabled:opacity-50">
                        <span x-text="payoutSaving ? 'Paying...' : 'Pay Vendor'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mark Payout Sent Modal -->
    <div x-show="showMarkSentModal" x-cloak class="fixed inset-0 z-[110] overflow-y-auto" @@keydown.escape.window="showMarkSentModal = false">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="showMarkSentModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <form @@submit.prevent="submitMarkSent()" class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="border-b border-slate-200 px-6 py-5">
                    <h3 class="text-xl font-black text-slate-900">Mark Payout Sent</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Record the payment transaction reference.</p>
                </div>
                <div class="px-6 py-5">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Reference</label>
                    <input type="text" x-model="markSentForm.payment_reference" required class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <button type="button" @@click="showMarkSentModal = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" :disabled="markSentSaving" class="rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700 disabled:opacity-50">
                        <span x-text="markSentSaving ? 'Saving...' : 'Mark Sent'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Payout Modal -->
    <div x-show="showConfirmPayoutModal" x-cloak class="fixed inset-0 z-[120] overflow-y-auto" @@keydown.escape.window="closeConfirmPayoutModal()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closeConfirmPayoutModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900">Confirm Payout?</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">This marks the payout as fully confirmed.</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-3 px-6 py-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</p>
                        <p class="mt-1 text-lg font-black text-slate-900" x-text="confirmPayoutTarget ? 'GHS ' + formatMoney(confirmPayoutTarget.amount) : '-'"></p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Method</p>
                            <p class="mt-1 text-sm font-bold text-slate-800" x-text="confirmPayoutTarget ? payoutMethodLabel(confirmPayoutTarget.payment_method) : '-'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</p>
                            <p class="mt-1 break-words text-sm font-bold text-slate-800" x-text="confirmPayoutTarget?.payment_reference || '-'"></p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <button type="button" @@click="closeConfirmPayoutModal()" :disabled="confirmPayoutSaving" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50">Cancel</button>
                    <button type="button" @@click="submitConfirmPayout()" :disabled="confirmPayoutSaving" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 disabled:opacity-50">
                        <span x-text="confirmPayoutSaving ? 'Confirming...' : 'Confirm Payout'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @@keydown.escape.window="showEditModal = false"
    >
        <!-- Backdrop -->
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showEditModal = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showEditModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @@click.stop
                class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl"
            >
                <!-- Header -->
                <div class="relative border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Edit Vendor</h3>
                                <p class="text-sm text-slate-500 mt-1">Update vendor information and settings</p>
                            </div>
                        </div>
                        <button @@click="showEditModal = false" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <form @@submit.prevent="saveVendor()">
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
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="John Doe"
                                    required
                                >
                            </div>
                            <template x-if="errors.name">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.name[0]"></p>
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
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="Acme Corporation"
                                >
                            </div>
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="vendor@example.com"
                                    >
                                </div>
                                <template x-if="errors.email">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.email[0]"></p>
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="+233 24 123 4567"
                                        required
                                    >
                                </div>
                                <template x-if="errors.phone">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.phone[0]"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Status Toggle -->
                        <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
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

                        <!-- Payout Settings -->
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
                                    :placeholder="'Default: GHS ' + (config.globalCommissionRate ?? '0.00')"
                                    class="w-full pl-14 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-4 focus:ring-orange-100 focus:border-orange-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                >
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                <strong>Leave blank</strong> to use the global default (GHS <span x-text="config.globalCommissionRate ?? '0.00'"></span>).
                                <br>
                                <strong>Enter 0</strong> to give this vendor no commission per package.
                            </p>
                            <template x-if="errors.commission_rate_override">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.commission_rate_override[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                        <button
                            type="button"
                            @@click="showEditModal = false"
                            class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all shadow-sm"
                        >
                            Cancel
                        </button>
                        <button
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
                            <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div
        x-show="showToggleModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @@keydown.escape.window="showToggleModal = false"
    >
        <!-- Backdrop -->
        <div x-show="showToggleModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showToggleModal = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showToggleModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @@click.stop
                class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50 overflow-hidden"
            >
                <!-- Header -->
                <div class="p-6 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4"
                         :class="vendor.is_active ? 'bg-amber-100' : 'bg-emerald-100'">
                        <svg x-show="vendor.is_active" class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <svg x-show="!vendor.is_active" x-cloak class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900" x-text="vendor.is_active ? 'Deactivate Vendor?' : 'Activate Vendor?'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        <span x-show="vendor.is_active">
                            Are you sure you want to deactivate <strong x-text="vendor.name"></strong>? They will no longer be able to access the vendor portal.
                        </span>
                        <span x-show="!vendor.is_active" x-cloak>
                            Are you sure you want to activate <strong x-text="vendor.name"></strong>? They will be able to access the vendor portal again.
                        </span>
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                    <button
                        type="button"
                        @@click="showToggleModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @@click="toggleActive()"
                        :disabled="toggling"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl transition-all disabled:opacity-50"
                        :class="vendor.is_active
                            ? 'bg-amber-500 hover:bg-amber-600 text-white'
                            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
                    >
                        <svg x-show="toggling" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="toggling ? 'Processing...' : (vendor.is_active ? 'Yes, Deactivate' : 'Yes, Activate')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Vendor Modal -->
    <div x-show="showRestoreModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display:none">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="showRestoreModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @@click.stop>
            <div class="px-6 py-8 text-center">
                <div class="mx-auto mb-4 flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Restore Vendor?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to restore <strong x-text="vendor.name"></strong>? Their account will be recovered in an inactive state and will need to be manually activated.
                </p>
            </div>
            <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                <button type="button" @@click="showRestoreModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="button" @@click="restoreVendor()" :disabled="restoring"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-all disabled:opacity-50">
                    <svg x-show="restoring" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="restoring ? 'Restoring...' : 'Yes, Restore'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Vendor Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display:none">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="showDeleteModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @@click.stop>
            <div class="px-6 py-8 text-center">
                <div class="mx-auto mb-4 flex items-center justify-center w-16 h-16 rounded-full bg-rose-100">
                    <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Delete Vendor?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to delete <strong x-text="vendor.name"></strong>? Their API access will be revoked and phone number freed for re-registration. Shipment and invoice records will be preserved.
                </p>
            </div>
            <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                <button type="button" @@click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="button" @@click="deleteVendor()" :disabled="deleting"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl bg-rose-500 hover:bg-rose-600 text-white transition-all disabled:opacity-50">
                    <svg x-show="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="deleting ? 'Deleting...' : 'Yes, Delete'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
