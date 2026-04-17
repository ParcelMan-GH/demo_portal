@extends('admin.layouts.app')

@section('title', 'Vendor Payouts')
@section('breadcrumb-parent', 'Finance')
@section('breadcrumb-current', 'Vendor Payouts')

@section('content')
<div
    x-data="vendorPayouts()"
    x-init="init()"
    data-payout-config='@json($payoutConfig)'
    class="space-y-6"
>
    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Total Vendors with Balance --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Vendors with Balance</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900" x-text="stats.vendorsWithBalance ?? '—'"></p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Available Balance --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Available Balance</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600" x-text="stats.totalAvailableBalance != null ? 'GHS ' + parseFloat(stats.totalAvailableBalance).toFixed(2) : '—'"></p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Pending Payouts --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending Payouts</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600" x-text="stats.pendingPayoutsTotal != null ? 'GHS ' + parseFloat(stats.pendingPayoutsTotal).toFixed(2) : '—'"></p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">Vendor Payouts</h2>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    x-model.debounce.300ms="search"
                    @@input="loadData()"
                    placeholder="Search vendors..."
                    class="pl-10 pr-4 py-2 text-xs border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none w-64"
                />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Total Earned</th>
                        <th class="px-6 py-3 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Available Balance</th>
                        <th class="px-6 py-3 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Total Paid</th>
                        <th class="px-6 py-3 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Pending</th>
                        <th class="px-6 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="loading">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                            <svg class="animate-spin h-5 w-5 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </td>
                    </tr>
                    <tr x-show="!loading && vendors.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">No vendors found.</td>
                    </tr>
                    <template x-for="vendor in vendors" :key="vendor.id">
                        <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <button @@click="openHistory(vendor)" class="text-left group">
                                    <p class="font-semibold text-slate-900 group-hover:text-blue-600 transition-colors" x-text="vendor.name"></p>
                                    <p class="text-slate-500 text-[10px]" x-text="vendor.business_name"></p>
                                    <p class="text-slate-400 text-[10px]" x-text="vendor.phone"></p>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-700" x-text="'GHS ' + parseFloat(vendor.total_earned || 0).toFixed(2)"></td>
                            <td class="px-6 py-4 text-right">
                                <span
                                    class="font-semibold"
                                    :class="vendor.can_payout ? 'text-emerald-600' : 'text-slate-500'"
                                    x-text="'GHS ' + parseFloat(vendor.available_balance || 0).toFixed(2)"
                                ></span>
                                <div class="mt-1">
                                    <span x-show="vendor.can_payout" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700">Ready</span>
                                    <span x-show="!vendor.can_payout" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">Below minimum</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-700" x-text="'GHS ' + parseFloat(vendor.total_paid || 0).toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-medium text-amber-600" x-text="'GHS ' + parseFloat(vendor.pending_payouts || 0).toFixed(2)"></td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    @@click="openCreateModal(vendor)"
                                    :disabled="!vendor.can_payout"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    Create Payout
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between" x-show="meta.last_page > 1">
            <p class="text-xs text-slate-500">
                Showing <span x-text="meta.from ?? 0"></span> to <span x-text="meta.to ?? 0"></span> of <span x-text="meta.total ?? 0"></span>
            </p>
            <div class="flex items-center gap-1">
                <button
                    @@click="goToPage(meta.current_page - 1)"
                    :disabled="meta.current_page <= 1"
                    class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                >Prev</button>
                <button
                    @@click="goToPage(meta.current_page + 1)"
                    :disabled="meta.current_page >= meta.last_page"
                    class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                >Next</button>
            </div>
        </div>
    </div>

    {{-- Create Payout Modal --}}
        <div
            x-cloak
            x-show="createModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @@click="createModalOpen = false"></div>

            {{-- Modal Content --}}
            <div
                x-show="createModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white/90 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-2xl w-full max-w-md p-6"
            >
                <h3 class="text-lg font-bold text-slate-900 mb-1">Create Payout</h3>
                <p class="text-xs text-slate-500 mb-5" x-text="'For ' + (selectedVendor?.name ?? '')"></p>

                <form @@submit.prevent="submitPayout()">
                    {{-- Amount --}}
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount (GHS)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            x-model="payoutForm.amount"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none"
                            required
                        />
                    </div>

                    {{-- Payment Method --}}
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Method</label>
                        <select
                            x-model="payoutForm.payment_method"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none"
                            required
                        >
                            <option value="momo">MoMo</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>

                    {{-- Payment Phone (MoMo only) --}}
                    <div class="mb-4" x-show="payoutForm.payment_method === 'momo'"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Phone</label>
                        <input
                            type="text"
                            x-model="payoutForm.payment_phone"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none"
                        />
                    </div>

                    {{-- Notes --}}
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Notes (optional)</label>
                        <textarea
                            x-model="payoutForm.notes"
                            rows="2"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none resize-none"
                        ></textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @@click="createModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                        >Cancel</button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50"
                        >
                            <span x-show="!submitting">Create Payout</span>
                            <span x-show="submitting" class="flex items-center gap-2">
                                <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Creating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    {{-- Payout History Modal --}}
        <div
            x-cloak
            x-show="historyModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @@click="historyModalOpen = false"></div>

            {{-- Panel --}}
            <div
                x-show="historyModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white/90 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col"
            >
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-slate-200/80 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Payout History</h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="historyVendor?.name ?? ''"></p>
                    </div>
                    <button @@click="historyModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
                    <template x-if="historyLoading">
                        <div class="py-12 text-center">
                            <svg class="animate-spin h-5 w-5 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    </template>
                    <template x-if="!historyLoading && payoutHistory.length === 0">
                        <div class="py-12 text-center text-sm text-slate-400">No payouts yet.</div>
                    </template>
                    <template x-for="payout in payoutHistory" :key="payout.id">
                        <div class="bg-white/60 border border-slate-100 rounded-2xl p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="text-sm font-bold text-slate-900" x-text="'GHS ' + parseFloat(payout.amount).toFixed(2)"></span>
                                    <span class="ml-2 text-xs text-slate-500" x-text="payout.payment_method"></span>
                                </div>
                                {{-- Status Badge --}}
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold"
                                    :class="{
                                        'bg-amber-50 text-amber-700': payout.status === 'pending',
                                        'bg-blue-50 text-blue-700': payout.status === 'sent',
                                        'bg-emerald-50 text-emerald-700': payout.status === 'confirmed',
                                    }"
                                    x-text="payout.status.charAt(0).toUpperCase() + payout.status.slice(1)"
                                ></span>
                            </div>

                            <div class="text-[10px] text-slate-400 space-y-0.5">
                                <template x-if="payout.reference">
                                    <p>Ref: <span class="text-slate-600" x-text="payout.reference"></span></p>
                                </template>
                                <p>Created: <span class="text-slate-600" x-text="payout.created_at"></span></p>
                                <template x-if="payout.sent_at">
                                    <p>Sent: <span class="text-slate-600" x-text="payout.sent_at"></span></p>
                                </template>
                                <template x-if="payout.confirmed_at">
                                    <p>Confirmed: <span class="text-slate-600" x-text="payout.confirmed_at"></span></p>
                                </template>
                            </div>

                            {{-- Payout Actions --}}
                            <div class="mt-3 flex items-center gap-2">
                                <template x-if="payout.status === 'pending'">
                                    <button
                                        @@click="promptMarkSent(payout)"
                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors"
                                    >Mark as Sent</button>
                                </template>
                                <template x-if="payout.status === 'sent'">
                                    <button
                                        @@click="confirmPayout(payout)"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition-colors"
                                    >Confirm</button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    {{-- Mark as Sent Prompt Modal --}}
        <div
            x-cloak
            x-show="markSentModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @@click="markSentModalOpen = false"></div>
            <div
                x-show="markSentModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white/90 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-2xl w-full max-w-sm p-6"
            >
                <h3 class="text-base font-bold text-slate-900 mb-4">Mark Payout as Sent</h3>
                <form @@submit.prevent="submitMarkSent()">
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Reference</label>
                        <input
                            type="text"
                            x-model="markSentReference"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none"
                            placeholder="e.g. MoMo transaction ID"
                            required
                        />
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @@click="markSentModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">
                            <span x-show="!submitting">Submit</span>
                            <span x-show="submitting">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
</div>

@push('scripts')
<script>
function vendorPayouts() {
    return {
        config: {},
        vendors: [],
        meta: {},
        stats: {},
        loading: false,
        search: '',
        page: 1,

        // Create modal
        createModalOpen: false,
        selectedVendor: null,
        submitting: false,
        payoutForm: {
            amount: '',
            payment_method: 'momo',
            payment_phone: '',
            notes: '',
        },

        // History modal
        historyModalOpen: false,
        historyVendor: null,
        historyLoading: false,
        payoutHistory: [],

        // Mark sent modal
        markSentModalOpen: false,
        markSentPayout: null,
        markSentReference: '',

        init() {
            this.config = JSON.parse(this.$el.getAttribute('data-payout-config'));
            this.loadData();
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    search: this.search,
                });
                const resp = await fetch(this.config.dataUrl + '?' + params.toString());
                const json = await resp.json();
                this.vendors = Array.isArray(json.data) ? json.data : [];
                this.meta = json.meta ?? {};
                if (json.stats) {
                    this.stats = json.stats;
                }
            } catch (e) {
                console.error('Failed to load payout data', e);
                this.vendors = [];
                this.meta = {};
            } finally {
                this.loading = false;
            }
        },

        goToPage(p) {
            if (p < 1 || p > this.meta.last_page) return;
            this.page = p;
            this.loadData();
        },

        openCreateModal(vendor) {
            this.selectedVendor = vendor;
            this.payoutForm = {
                amount: vendor.available_balance,
                payment_method: 'momo',
                payment_phone: vendor.phone ?? '',
                notes: '',
            };
            this.createModalOpen = true;
        },

        async submitPayout() {
            this.submitting = true;
            try {
                const url = this.config.createPayoutUrl.replace('__VENDOR__', this.selectedVendor.id);
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.payoutForm),
                });
                const json = await resp.json();
                if (resp.ok) {
                    window.showToast?.(json.message ?? 'Payout created successfully', 'success');
                    this.createModalOpen = false;
                    this.loadData();
                } else {
                    window.showToast?.(json.message ?? 'Failed to create payout', 'error');
                }
            } catch (e) {
                window.showToast?.('An error occurred', 'error');
                console.error(e);
            } finally {
                this.submitting = false;
            }
        },

        async openHistory(vendor) {
            this.historyVendor = vendor;
            this.historyModalOpen = true;
            this.historyLoading = true;
            this.payoutHistory = [];
            try {
                const url = this.config.vendorPayoutsUrl.replace('__VENDOR__', vendor.id);
                const resp = await fetch(url);
                const json = await resp.json();
                this.payoutHistory = json.data ?? json;
            } catch (e) {
                console.error('Failed to load payout history', e);
            } finally {
                this.historyLoading = false;
            }
        },

        promptMarkSent(payout) {
            this.markSentPayout = payout;
            this.markSentReference = '';
            this.markSentModalOpen = true;
        },

        async submitMarkSent() {
            this.submitting = true;
            try {
                const url = this.config.markSentUrl.replace('__PAYOUT__', this.markSentPayout.id);
                const resp = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reference: this.markSentReference }),
                });
                const json = await resp.json();
                if (resp.ok) {
                    window.showToast?.(json.message ?? 'Payout marked as sent', 'success');
                    this.markSentModalOpen = false;
                    // Refresh history
                    this.openHistory(this.historyVendor);
                    this.loadData();
                } else {
                    window.showToast?.(json.message ?? 'Failed to update payout', 'error');
                }
            } catch (e) {
                window.showToast?.('An error occurred', 'error');
                console.error(e);
            } finally {
                this.submitting = false;
            }
        },

        async confirmPayout(payout) {
            this.submitting = true;
            try {
                const url = this.config.confirmUrl.replace('__PAYOUT__', payout.id);
                const resp = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Accept': 'application/json',
                    },
                });
                const json = await resp.json();
                if (resp.ok) {
                    window.showToast?.(json.message ?? 'Payout confirmed', 'success');
                    this.openHistory(this.historyVendor);
                    this.loadData();
                } else {
                    window.showToast?.(json.message ?? 'Failed to confirm payout', 'error');
                }
            } catch (e) {
                window.showToast?.('An error occurred', 'error');
                console.error(e);
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush
@endsection
