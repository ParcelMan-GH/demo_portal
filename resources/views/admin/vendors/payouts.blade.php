@extends('admin.layouts.app')

@section('title', 'Commission Payouts')
@section('breadcrumb-parent', 'Finance')
@section('breadcrumb-current', 'Commission Payouts')

@section('content')
<div x-data="vendorPayoutsPage(@js($payoutConfig))" x-init="init()" class="space-y-6">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Vendors</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="summary.vendors_with_balance || 0"></p>
            </div>
        </div>
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Ready To Pay</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="summary.ready_to_pay || 0"></p>
            </div>
        </div>
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Below Minimum</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="summary.below_minimum || 0"></p>
            </div>
        </div>
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Available</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="'GHS ' + formatMoney(summary.available_balance)"></p>
            </div>
        </div>
    </div>

    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">Commission Payouts</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Process vendors with accrued unpaid commission.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="relative w-full xl:max-w-md">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                <input type="text" x-model="search" @@input.debounce.500ms="page = 1; loadVendors()" placeholder="Search vendor, business, or phone..."
                    class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <button type="button" @@click="showFilters = !showFilters"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4Z"/></svg>
                    <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                </button>
                <button type="button" @@click="loadVendors()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.58m15.36 2A8.001 8.001 0 0 0 4.58 9m0 0H9m11 11v-5h-.58m0 0a8.003 8.003 0 0 1-15.36-2m15.36 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div x-show="showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payout State</label>
                        <select x-model="status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All accrued vendors</option>
                            <option value="ready">Ready to pay</option>
                            <option value="below_minimum">Below minimum</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payout Account</label>
                        <select x-model="payoutAccount" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All account states</option>
                            <option value="set">Account set</option>
                            <option value="missing">Missing account</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Sort</label>
                        <select x-model="sort" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="available_balance">Available balance</option>
                            <option value="total_earned">Total earned</option>
                            <option value="total_paid">Total paid</option>
                            <option value="vendor_name">Vendor name</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Direction</label>
                        <select x-model="sortDir" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="desc">Highest first</option>
                            <option value="asc">Lowest first</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="page = 1; loadVendors()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[1080px] divide-y divide-slate-200/60 text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Vendor</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Payout Account</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Available</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Total Earned</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Total Paid</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">State</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="vendors.length === 0 && !loading">
                            <tr><td colspan="7" class="px-4 py-12 text-center text-sm font-semibold text-slate-400">No vendors with accrued commission found</td></tr>
                        </template>
                        <template x-for="vendor in vendors" :key="vendor.vendor_id">
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="max-w-[260px]">
                                        <p class="font-black text-slate-900" x-text="vendor.vendor_name"></p>
                                        <p class="mt-0.5 truncate text-xs font-semibold text-slate-500" x-text="vendor.business_name || 'No business name'"></p>
                                        <p class="mt-0.5 text-xs font-semibold text-slate-400" x-text="vendor.vendor_phone || '-'"></p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="vendor.payout_account?.is_set">
                                        <div>
                                            <p class="font-black text-slate-800" x-text="vendor.payout_account.account_name"></p>
                                            <p class="mt-0.5 text-xs font-semibold text-slate-500" x-text="payoutAccountText(vendor)"></p>
                                        </div>
                                    </template>
                                    <template x-if="!vendor.payout_account?.is_set">
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-amber-700">Missing</span>
                                    </template>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-black text-emerald-700" x-text="'GHS ' + formatMoney(vendor.available_balance)"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-slate-700" x-text="'GHS ' + formatMoney(vendor.total_earned)"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-slate-700" x-text="'GHS ' + formatMoney(vendor.total_paid)"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide" :class="vendor.can_payout ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-slate-200 bg-slate-50 text-slate-500'" x-text="vendor.can_payout ? 'Ready to pay' : 'Below minimum'"></span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button type="button" @@click="openHistory(vendor)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">History</button>
                                        <button type="button" x-show="vendor.can_payout" @@click="openPayout(vendor)" :disabled="!vendor.payout_account?.is_set" :class="vendor.payout_account?.is_set ? 'bg-orange-600 text-white shadow-sm shadow-orange-600/20 hover:bg-orange-700' : 'cursor-not-allowed bg-slate-200 text-slate-500'" class="rounded-lg px-3 py-2 text-xs font-bold">Pay Vendor</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 bg-white lg:hidden">
                <template x-if="vendors.length === 0 && !loading">
                    <div class="px-5 py-12 text-center text-sm font-semibold text-slate-400">No vendors with accrued commission found</div>
                </template>
                <template x-for="vendor in vendors" :key="vendor.vendor_id">
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-black text-slate-900" x-text="vendor.vendor_name"></p>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-500" x-text="vendor.business_name || 'No business name'"></p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-400" x-text="vendor.vendor_phone || '-'"></p>
                                <p class="mt-1 text-xs font-bold" :class="vendor.payout_account?.is_set ? 'text-emerald-700' : 'text-amber-700'" x-text="vendor.payout_account?.is_set ? payoutAccountText(vendor) : 'No payout account'"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide" :class="vendor.can_payout ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-slate-200 bg-slate-50 text-slate-500'" x-text="vendor.can_payout ? 'Ready' : 'Below min'"></span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Available</p>
                                <p class="mt-1 font-black text-emerald-700" x-text="'GHS ' + formatMoney(vendor.available_balance)"></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Earned</p>
                                <p class="mt-1 font-black text-slate-800" x-text="'GHS ' + formatMoney(vendor.total_earned)"></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Paid</p>
                                <p class="mt-1 font-black text-slate-800" x-text="'GHS ' + formatMoney(vendor.total_paid)"></p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-end gap-2">
                            <button type="button" @@click="openHistory(vendor)" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">History</button>
                            <button type="button" x-show="vendor.can_payout" @@click="openPayout(vendor)" :disabled="!vendor.payout_account?.is_set" :class="vendor.payout_account?.is_set ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700' : 'cursor-not-allowed bg-slate-200 text-slate-500'" class="rounded-xl px-4 py-2.5 text-sm font-bold">Pay Vendor</button>
                        </div>
                    </article>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span></div>
                    <div class="flex items-center gap-1">
                        <button type="button" @@click="prevPage()" :disabled="page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg></button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                        <button type="button" @@click="nextPage()" :disabled="page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div x-show="showPayoutModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="closePayout()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closePayout()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <form @@submit.prevent="submitPayout()" class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900">Pay Vendor</h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500" x-text="selectedVendor ? selectedVendor.vendor_name + ' / GHS ' + formatMoney(selectedVendor.available_balance) + ' available' : ''"></p>
                            </div>
                        </div>
                        <button type="button" @@click="closePayout()" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700">MoMo payout account</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="selectedVendor?.payout_account?.account_name || '-'"></p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-600" x-text="selectedVendor ? payoutAccountText(selectedVendor) : '-'"></p>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Amount</label>
                        <input type="number" step="0.01" min="1" x-model="payoutForm.amount" required class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Reference</label>
                        <input type="text" x-model="payoutForm.payment_reference" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Notes</label>
                        <textarea rows="3" x-model="payoutForm.notes" class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <button type="button" @@click="closePayout()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" :disabled="savingPayout" class="rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700 disabled:opacity-50">
                        <span x-text="savingPayout ? 'Paying...' : 'Pay Vendor'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showHistoryModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @@keydown.escape.window="closeHistory()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closeHistory()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-900">Payout History</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500" x-text="historyVendor ? historyVendor.vendor_name : ''"></p>
                        </div>
                        <button type="button" @@click="closeHistory()" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="relative w-full xl:max-w-md">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <input type="text" x-model="history.search" @@input.debounce.500ms="history.page = 1; loadHistory()" placeholder="Search reference, phone, or notes..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <select x-model="history.status" @@change="history.page = 1; loadHistory()" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="confirmed">Confirmed</option>
                        </select>
                        <select x-model="history.method" @@change="history.page = 1; loadHistory()" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All methods</option>
                            <option value="momo">MOMO</option>
                            <option value="bank">Bank</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full min-w-[820px] divide-y divide-slate-200/60 text-xs">
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
                                <template x-if="history.data.length === 0 && !history.loading">
                                    <tr><td colspan="7" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No payout history found</td></tr>
                                </template>
                                <template x-for="payout in history.data" :key="payout.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600" x-text="formatDateTime(payout.created_at)"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-black text-slate-900" x-text="'GHS ' + formatMoney(payout.amount)"></td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700" x-text="methodLabel(payout.payment_method)"></td>
                                        <td class="px-4 py-3 font-semibold text-slate-700" x-text="payout.payment_reference || '-'"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black" :class="statusClass(payout.status)" x-text="statusLabel(payout.status)"></span></td>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600" x-text="payout.processed_by?.name || '-'"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <button x-show="payout.status === 'pending'" @@click="openMarkSent(payout)" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Mark Sent</button>
                                            <button x-show="payout.status === 'sent'" @@click="openConfirm(payout)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100">Confirm</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="divide-y divide-slate-100 lg:hidden">
                        <template x-if="history.data.length === 0 && !history.loading">
                            <div class="px-5 py-10 text-center text-sm font-semibold text-slate-400">No payout history found</div>
                        </template>
                        <template x-for="payout in history.data" :key="payout.id">
                            <article class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-900" x-text="'GHS ' + formatMoney(payout.amount)"></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="formatDateTime(payout.created_at)"></p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="statusClass(payout.status)" x-text="statusLabel(payout.status)"></span>
                                </div>
                                <div class="mt-3 space-y-1 text-sm font-semibold text-slate-600">
                                    <p x-text="methodLabel(payout.payment_method) + (payout.payment_phone ? ' / ' + payout.payment_phone : '')"></p>
                                    <p x-text="'Reference: ' + (payout.payment_reference || '-')"></p>
                                    <p x-text="'Processed by: ' + (payout.processed_by?.name || '-')"></p>
                                </div>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button x-show="payout.status === 'pending'" @@click="openMarkSent(payout)" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Mark Sent</button>
                                    <button x-show="payout.status === 'sent'" @@click="openConfirm(payout)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100">Confirm</button>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
                <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs font-semibold text-slate-600">Showing <span x-text="history.meta.from || 0"></span> to <span x-text="history.meta.to || 0"></span> of <span x-text="history.meta.total || 0"></span></div>
                        <div class="flex items-center gap-1">
                            <button type="button" @@click="historyPrev()" :disabled="history.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg></button>
                            <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="history.meta.current_page || 1"></span> / <span x-text="history.meta.last_page || 1"></span></div>
                            <button type="button" @@click="historyNext()" :disabled="history.page >= history.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showMarkSentModal" x-cloak class="fixed inset-0 z-[110] overflow-y-auto" @@keydown.escape.window="closeMarkSent()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closeMarkSent()"></div>
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
                    <button type="button" @@click="closeMarkSent()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" :disabled="savingMarkSent" class="rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700 disabled:opacity-50">
                        <span x-text="savingMarkSent ? 'Saving...' : 'Mark Sent'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-[120] overflow-y-auto" @@keydown.escape.window="closeConfirm()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closeConfirm()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
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
                        <p class="mt-1 text-lg font-black text-slate-900" x-text="confirmTarget ? 'GHS ' + formatMoney(confirmTarget.amount) : '-'"></p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Method</p>
                            <p class="mt-1 text-sm font-bold text-slate-800" x-text="confirmTarget ? methodLabel(confirmTarget.payment_method) : '-'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</p>
                            <p class="mt-1 break-words text-sm font-bold text-slate-800" x-text="confirmTarget?.payment_reference || '-'"></p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <button type="button" @@click="closeConfirm()" :disabled="savingConfirm" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50">Cancel</button>
                    <button type="button" @@click="submitConfirm()" :disabled="savingConfirm" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 disabled:opacity-50">
                        <span x-text="savingConfirm ? 'Confirming...' : 'Confirm Payout'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function vendorPayoutsPage(config) {
    return {
        config,
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        vendors: [],
        summary: {},
        meta: {},
        loading: false,
        search: '',
        status: '',
        payoutAccount: '',
        sort: 'available_balance',
        sortDir: 'desc',
        page: 1,
        showFilters: false,
        selectedVendor: null,
        showPayoutModal: false,
        savingPayout: false,
        payoutForm: { amount: '', payment_method: 'momo', payment_phone: '', payment_reference: '', notes: '' },
        historyVendor: null,
        showHistoryModal: false,
        history: { data: [], meta: {}, loading: false, search: '', status: '', method: '', page: 1 },
        showMarkSentModal: false,
        markSentTarget: null,
        markSentForm: { payment_reference: '' },
        savingMarkSent: false,
        showConfirmModal: false,
        confirmTarget: null,
        savingConfirm: false,

        init() {
            const params = new URLSearchParams(window.location.search);
            this.search = params.get('search') || '';
            this.loadVendors();
        },
        async request(url, options = {}) {
            const headers = Object.assign({
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrf,
            }, options.headers || {});

            if (options.body && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            const response = await fetch(url, Object.assign({}, options, { headers }));
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw data;
            }
            return data;
        },
        toast(message, type = 'success') {
            if (window.showToast) {
                window.showToast(message, type);
            }
        },
        async loadVendors() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.page,
                per_page: 15,
                search: this.search || '',
                status: this.status || '',
                payout_account: this.payoutAccount || '',
                sort: this.sort || 'available_balance',
                sort_dir: this.sortDir || 'desc',
            });

            try {
                const data = await this.request(this.config.dataUrl + '?' + params.toString());
                this.vendors = data.data || [];
                this.summary = data.summary || {};
                this.meta = data.meta || {};
            } catch (error) {
                this.toast(error.message || 'Failed to load commission payouts', 'error');
            } finally {
                this.loading = false;
            }
        },
        clearFilters() {
            this.status = '';
            this.payoutAccount = '';
            this.sort = 'available_balance';
            this.sortDir = 'desc';
            this.page = 1;
            this.loadVendors();
        },
        prevPage() {
            if (this.page <= 1) return;
            this.page--;
            this.loadVendors();
        },
        nextPage() {
            if (this.page >= (this.meta.last_page || 1)) return;
            this.page++;
            this.loadVendors();
        },
        openPayout(vendor) {
            if (!vendor.payout_account?.is_set) {
                this.toast('Set the vendor MoMo payout account before processing payout.', 'error');
                return;
            }
            this.selectedVendor = vendor;
            this.payoutForm = {
                amount: vendor.available_balance,
                payment_method: 'momo',
                payment_phone: vendor.payout_account?.account_number || '',
                payment_reference: '',
                notes: '',
            };
            this.showPayoutModal = true;
        },
        closePayout() {
            this.showPayoutModal = false;
            this.selectedVendor = null;
        },
        async submitPayout() {
            if (!this.selectedVendor) return;
            this.savingPayout = true;
            try {
                const url = this.config.createPayoutUrl.replace('__VENDOR__', this.selectedVendor.vendor_id);
                const data = await this.request(url, { method: 'POST', body: this.payoutForm });
                this.toast(data.message || 'Payout created');
                this.closePayout();
                await this.loadVendors();
                if (this.showHistoryModal) await this.loadHistory();
            } catch (error) {
                this.toast(error.message || 'Failed to create payout', 'error');
            } finally {
                this.savingPayout = false;
            }
        },
        openHistory(vendor) {
            this.historyVendor = vendor;
            this.history = { data: [], meta: {}, loading: false, search: '', status: '', method: '', page: 1 };
            this.showHistoryModal = true;
            this.loadHistory();
        },
        closeHistory() {
            this.showHistoryModal = false;
            this.historyVendor = null;
        },
        async loadHistory() {
            if (!this.historyVendor) return;
            this.history.loading = true;
            const params = new URLSearchParams({
                page: this.history.page,
                per_page: 10,
                search: this.history.search || '',
                status: this.history.status || '',
                method: this.history.method || '',
            });

            try {
                const url = this.config.vendorPayoutsUrl.replace('__VENDOR__', this.historyVendor.vendor_id);
                const data = await this.request(url + '?' + params.toString());
                this.history.data = data.data || [];
                this.history.meta = data.meta || {};
            } catch (error) {
                this.toast(error.message || 'Failed to load payout history', 'error');
            } finally {
                this.history.loading = false;
            }
        },
        historyPrev() {
            if (this.history.page <= 1) return;
            this.history.page--;
            this.loadHistory();
        },
        historyNext() {
            if (this.history.page >= (this.history.meta.last_page || 1)) return;
            this.history.page++;
            this.loadHistory();
        },
        openMarkSent(payout) {
            this.markSentTarget = payout;
            this.markSentForm = { payment_reference: payout.payment_reference || '' };
            this.showMarkSentModal = true;
        },
        closeMarkSent() {
            this.showMarkSentModal = false;
            this.markSentTarget = null;
        },
        async submitMarkSent() {
            if (!this.markSentTarget) return;
            this.savingMarkSent = true;
            try {
                const url = this.config.markSentUrl.replace('__PAYOUT__', this.markSentTarget.id);
                const data = await this.request(url, { method: 'PATCH', body: this.markSentForm });
                this.toast(data.message || 'Payout marked as sent');
                this.closeMarkSent();
                await this.loadHistory();
                await this.loadVendors();
            } catch (error) {
                this.toast(error.message || 'Failed to mark payout as sent', 'error');
            } finally {
                this.savingMarkSent = false;
            }
        },
        openConfirm(payout) {
            this.confirmTarget = payout;
            this.showConfirmModal = true;
        },
        closeConfirm() {
            this.showConfirmModal = false;
            this.confirmTarget = null;
        },
        async submitConfirm() {
            if (!this.confirmTarget) return;
            this.savingConfirm = true;
            try {
                const url = this.config.confirmUrl.replace('__PAYOUT__', this.confirmTarget.id);
                const data = await this.request(url, { method: 'PATCH' });
                this.toast(data.message || 'Payout confirmed');
                this.closeConfirm();
                await this.loadHistory();
                await this.loadVendors();
            } catch (error) {
                this.toast(error.message || 'Failed to confirm payout', 'error');
            } finally {
                this.savingConfirm = false;
            }
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatDateTime(value) {
            if (!value) return '-';
            return new Date(value).toLocaleString('en-GH', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        },
        methodLabel(method) {
            return { momo: 'MOMO', bank: 'Bank Transfer', cash: 'Cash' }[method] || '-';
        },
        networkLabel(network) {
            return { mtn: 'MTN MoMo', telecel: 'Telecel Cash', airteltigo: 'AirtelTigo Money' }[network] || '-';
        },
        payoutAccountText(vendor) {
            if (!vendor?.payout_account?.is_set) return 'No payout account';
            return `${this.networkLabel(vendor.payout_account.network)} / ${vendor.payout_account.account_number}`;
        },
        statusLabel(status) {
            return { pending: 'Pending', sent: 'Sent', confirmed: 'Confirmed' }[status] || status || '-';
        },
        statusClass(status) {
            if (status === 'confirmed') return 'bg-emerald-50 text-emerald-700';
            if (status === 'sent') return 'bg-blue-50 text-blue-700';
            return 'bg-amber-50 text-amber-700';
        },
    };
}
</script>
@endpush
