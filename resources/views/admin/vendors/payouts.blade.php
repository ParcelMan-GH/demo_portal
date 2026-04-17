@extends('admin.layouts.app')

@section('title', 'Vendor Payouts')
@section('breadcrumb-parent', 'Finance')
@section('breadcrumb-current', 'Vendor Payouts')

@section('content')
<div id="vendor-payouts-app" class="space-y-6">

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Vendors with Balance</p>
                    <p id="stat-vendors-count" class="mt-2 text-2xl font-bold text-slate-900">&mdash;</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Available Balance</p>
                    <p id="stat-total-balance" class="mt-2 text-2xl font-bold text-emerald-600">&mdash;</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending Payouts</p>
                    <p id="stat-pending" class="mt-2 text-2xl font-bold text-amber-600">&mdash;</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg">
        <div class="px-6 py-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">Vendor Payouts</h2>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="search-input" placeholder="Search vendors..." class="pl-10 pr-4 py-2 text-xs border border-slate-200 rounded-xl bg-white/60 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none w-64" />
            </div>
        </div>

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
                <tbody id="vendors-tbody">
                    <tr id="loading-row">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                            <svg class="animate-spin h-5 w-5 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </td>
                    </tr>
                    <tr id="empty-row" style="display:none;">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">No vendors found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="pagination-row" class="px-6 py-4 border-t border-slate-100 flex items-center justify-between" style="display:none;">
            <p class="text-xs text-slate-500">Showing <span id="pag-from">0</span> to <span id="pag-to">0</span> of <span id="pag-total">0</span></p>
            <div class="flex items-center gap-1">
                <button id="btn-prev" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                <button id="btn-next" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>

    {{-- Create Payout Modal --}}
    <div id="create-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="VendorPayouts.closeCreate()"></div>
        <div class="relative bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Create Payout</h3>
            <p id="create-modal-vendor" class="text-xs text-slate-500 mb-5"></p>
            <form id="create-form" onsubmit="VendorPayouts.submitCreate(event)">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount (GHS)</label>
                    <input type="number" id="create-amount" step="0.01" min="0" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" required />
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Method</label>
                    <select id="create-method" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" required onchange="VendorPayouts.togglePhone()">
                        <option value="momo">MoMo</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div id="phone-field" class="mb-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Phone</label>
                    <input type="text" id="create-phone" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" />
                </div>
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Notes (optional)</label>
                    <textarea id="create-notes" rows="2" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none resize-none"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="VendorPayouts.closeCreate()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">Cancel</button>
                    <button type="submit" id="create-submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">Create Payout</button>
                </div>
            </form>
        </div>
    </div>

    {{-- History Modal --}}
    <div id="history-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="VendorPayouts.closeHistory()"></div>
        <div class="relative bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
            <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Payout History</h3>
                    <p id="history-vendor-name" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button onclick="VendorPayouts.closeHistory()" class="p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="history-content" class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
                <div class="py-12 text-center text-sm text-slate-400">Loading...</div>
            </div>
        </div>
    </div>

    {{-- Mark Sent Modal --}}
    <div id="mark-sent-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="VendorPayouts.closeMarkSent()"></div>
        <div class="relative bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-slate-900 mb-4">Mark Payout as Sent</h3>
            <form onsubmit="VendorPayouts.submitMarkSent(event)">
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Reference</label>
                    <input type="text" id="mark-sent-ref" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" placeholder="e.g. MoMo transaction ID" required />
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="VendorPayouts.closeMarkSent()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">Cancel</button>
                    <button type="submit" id="mark-sent-submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var CONFIG = @json($payoutConfig);
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    var state = {
        vendors: [],
        meta: {},
        search: '',
        page: 1,
        selectedVendor: null,
        historyVendor: null,
        markSentPayoutId: null
    };

    var debounceTimer = null;

    // ─── Helpers ───
    function $(id) { return document.getElementById(id); }
    function ghs(n) { return 'GHS ' + parseFloat(n || 0).toFixed(2); }

    async function api(url, opts) {
        opts = opts || {};
        opts.headers = opts.headers || {};
        opts.headers['Accept'] = 'application/json';
        opts.headers['X-CSRF-TOKEN'] = CSRF;
        if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(opts.body);
        }
        var resp = await fetch(url, opts);
        var json = await resp.json();
        return { ok: resp.ok, data: json };
    }

    function toast(msg, type) {
        if (window.showToast) window.showToast(msg, type);
    }

    // ─── Load Data ───
    async function loadData() {
        $('loading-row').style.display = '';
        $('empty-row').style.display = 'none';
        removeVendorRows();

        var params = new URLSearchParams({ page: state.page, search: state.search });
        try {
            var result = await api(CONFIG.dataUrl + '?' + params.toString());
            state.vendors = result.data.data || [];
            state.meta = result.data.meta || {};
        } catch(e) {
            console.error(e);
            state.vendors = [];
            state.meta = {};
        }

        $('loading-row').style.display = 'none';
        renderTable();
        renderStats();
        renderPagination();
    }

    function removeVendorRows() {
        var rows = document.querySelectorAll('.vendor-row');
        for (var i = 0; i < rows.length; i++) rows[i].remove();
    }

    // ─── Render Table ───
    function renderTable() {
        var tbody = $('vendors-tbody');

        if (state.vendors.length === 0) {
            $('empty-row').style.display = '';
            return;
        }

        $('empty-row').style.display = 'none';

        for (var i = 0; i < state.vendors.length; i++) {
            var v = state.vendors[i];
            var tr = document.createElement('tr');
            tr.className = 'vendor-row border-b border-slate-50 hover:bg-slate-50/50 transition-colors';
            tr.innerHTML =
                '<td class="px-6 py-4">' +
                    '<button class="text-left group" data-action="history" data-idx="' + i + '">' +
                        '<p class="font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">' + esc(v.vendor_name) + '</p>' +
                        '<p class="text-slate-500 text-[10px]">' + esc(v.business_name || '') + '</p>' +
                        '<p class="text-slate-400 text-[10px]">' + esc(v.vendor_phone || '') + '</p>' +
                    '</button>' +
                '</td>' +
                '<td class="px-6 py-4 text-right font-medium text-slate-700">' + ghs(v.total_earned) + '</td>' +
                '<td class="px-6 py-4 text-right">' +
                    '<span class="font-semibold ' + (v.can_payout ? 'text-emerald-600' : 'text-slate-500') + '">' + ghs(v.available_balance) + '</span>' +
                    '<div class="mt-1">' +
                        (v.can_payout
                            ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700">Ready</span>'
                            : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">Below minimum</span>') +
                    '</div>' +
                '</td>' +
                '<td class="px-6 py-4 text-right font-medium text-slate-700">' + ghs(v.total_paid) + '</td>' +
                '<td class="px-6 py-4 text-right font-medium text-amber-600">' + ghs(v.pending_payouts) + '</td>' +
                '<td class="px-6 py-4 text-center">' +
                    '<button data-action="create" data-idx="' + i + '" ' + (v.can_payout ? '' : 'disabled') +
                        ' class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-40 disabled:cursor-not-allowed">Create Payout</button>' +
                '</td>';
            tbody.appendChild(tr);
        }
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ─── Render Stats ───
    function renderStats() {
        var totalBalance = 0;
        var totalPending = 0;
        var withBalance = 0;
        for (var i = 0; i < state.vendors.length; i++) {
            var v = state.vendors[i];
            totalBalance += parseFloat(v.available_balance || 0);
            totalPending += parseFloat(v.pending_payouts || 0);
            if (parseFloat(v.available_balance || 0) > 0) withBalance++;
        }
        $('stat-vendors-count').textContent = withBalance;
        $('stat-total-balance').textContent = ghs(totalBalance);
        $('stat-pending').textContent = ghs(totalPending);
    }

    // ─── Pagination ───
    function renderPagination() {
        var m = state.meta;
        if (!m.last_page || m.last_page <= 1) {
            $('pagination-row').style.display = 'none';
            return;
        }
        $('pagination-row').style.display = '';
        $('pag-from').textContent = m.from || 0;
        $('pag-to').textContent = m.to || 0;
        $('pag-total').textContent = m.total || 0;
        $('btn-prev').disabled = m.current_page <= 1;
        $('btn-next').disabled = m.current_page >= m.last_page;
    }

    // ─── Create Payout ───
    function openCreate(idx) {
        var v = state.vendors[idx];
        state.selectedVendor = v;
        $('create-modal-vendor').textContent = 'For ' + v.vendor_name;
        $('create-amount').value = v.available_balance;
        $('create-method').value = 'momo';
        $('create-phone').value = v.vendor_phone || '';
        $('create-notes').value = '';
        $('phone-field').style.display = '';
        $('create-modal').style.display = '';
    }

    function closeCreate() { $('create-modal').style.display = 'none'; }

    function togglePhone() {
        $('phone-field').style.display = $('create-method').value === 'momo' ? '' : 'none';
    }

    async function submitCreate(e) {
        e.preventDefault();
        var btn = $('create-submit');
        btn.disabled = true;
        btn.textContent = 'Creating...';
        try {
            var url = CONFIG.createPayoutUrl.replace('__VENDOR__', state.selectedVendor.vendor_id);
            var result = await api(url, {
                method: 'POST',
                body: {
                    amount: $('create-amount').value,
                    payment_method: $('create-method').value,
                    payment_phone: $('create-phone').value,
                    notes: $('create-notes').value
                }
            });
            if (result.ok && result.data.success !== false) {
                toast(result.data.message || 'Payout created', 'success');
                closeCreate();
                loadData();
            } else {
                toast(result.data.message || 'Failed to create payout', 'error');
            }
        } catch(e) {
            toast('An error occurred', 'error');
            console.error(e);
        }
        btn.disabled = false;
        btn.textContent = 'Create Payout';
    }

    // ─── History ───
    function openHistory(idx) {
        var v = state.vendors[idx];
        state.historyVendor = v;
        $('history-vendor-name').textContent = v.vendor_name;
        $('history-content').innerHTML = '<div class="py-12 text-center"><svg class="animate-spin h-5 w-5 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        $('history-modal').style.display = '';
        loadHistory(v.vendor_id);
    }

    function closeHistory() { $('history-modal').style.display = 'none'; }

    async function loadHistory(vendorId) {
        try {
            var url = CONFIG.vendorPayoutsUrl.replace('__VENDOR__', vendorId);
            var result = await api(url);
            var payouts = result.data.data || [];
            renderHistory(payouts);
        } catch(e) {
            $('history-content').innerHTML = '<div class="py-12 text-center text-sm text-red-400">Failed to load history.</div>';
            console.error(e);
        }
    }

    function renderHistory(payouts) {
        if (payouts.length === 0) {
            $('history-content').innerHTML = '<div class="py-12 text-center text-sm text-slate-400">No payouts yet.</div>';
            return;
        }

        var html = '';
        for (var i = 0; i < payouts.length; i++) {
            var p = payouts[i];
            var statusClass = p.status === 'pending' ? 'bg-amber-50 text-amber-700' : p.status === 'sent' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700';
            var statusLabel = p.status.charAt(0).toUpperCase() + p.status.slice(1);

            html += '<div class="bg-white/60 border border-slate-100 rounded-2xl p-4">';
            html += '<div class="flex items-start justify-between mb-2"><div>';
            html += '<span class="text-sm font-bold text-slate-900">' + ghs(p.amount) + '</span>';
            html += '<span class="ml-2 text-xs text-slate-500">' + esc(p.payment_method || '') + '</span>';
            html += '</div>';
            html += '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold ' + statusClass + '">' + statusLabel + '</span>';
            html += '</div>';
            html += '<div class="text-[10px] text-slate-400 space-y-0.5">';
            if (p.payment_reference) html += '<p>Ref: <span class="text-slate-600">' + esc(p.payment_reference) + '</span></p>';
            html += '<p>Created: <span class="text-slate-600">' + esc(p.created_at || '') + '</span></p>';
            if (p.sent_at) html += '<p>Sent: <span class="text-slate-600">' + esc(p.sent_at) + '</span></p>';
            if (p.confirmed_at) html += '<p>Confirmed: <span class="text-slate-600">' + esc(p.confirmed_at) + '</span></p>';
            html += '</div>';
            html += '<div class="mt-3 flex items-center gap-2">';
            if (p.status === 'pending') html += '<button onclick="VendorPayouts.openMarkSent(' + p.id + ')" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors">Mark as Sent</button>';
            if (p.status === 'sent') html += '<button onclick="VendorPayouts.confirmPayout(' + p.id + ')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition-colors">Confirm</button>';
            html += '</div></div>';
        }
        $('history-content').innerHTML = html;
    }

    // ─── Mark Sent ───
    function openMarkSent(payoutId) {
        state.markSentPayoutId = payoutId;
        $('mark-sent-ref').value = '';
        $('mark-sent-modal').style.display = '';
    }

    function closeMarkSent() { $('mark-sent-modal').style.display = 'none'; }

    async function submitMarkSent(e) {
        e.preventDefault();
        var ref = $('mark-sent-ref').value.trim();
        if (!ref) { toast('Reference required', 'error'); return; }
        var btn = $('mark-sent-submit');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        try {
            var url = CONFIG.markSentUrl.replace('__PAYOUT__', state.markSentPayoutId);
            var result = await api(url, { method: 'PATCH', body: { payment_reference: ref } });
            if (result.ok && result.data.success !== false) {
                toast(result.data.message || 'Marked as sent', 'success');
                closeMarkSent();
                if (state.historyVendor) loadHistory(state.historyVendor.vendor_id);
                loadData();
            } else {
                toast(result.data.message || 'Failed', 'error');
            }
        } catch(e) {
            toast('An error occurred', 'error');
            console.error(e);
        }
        btn.disabled = false;
        btn.textContent = 'Submit';
    }

    // ─── Confirm Payout ───
    async function confirmPayout(payoutId) {
        try {
            var url = CONFIG.confirmUrl.replace('__PAYOUT__', payoutId);
            var result = await api(url, { method: 'PATCH' });
            if (result.ok && result.data.success !== false) {
                toast(result.data.message || 'Confirmed', 'success');
                if (state.historyVendor) loadHistory(state.historyVendor.vendor_id);
                loadData();
            } else {
                toast(result.data.message || 'Failed', 'error');
            }
        } catch(e) {
            toast('An error occurred', 'error');
            console.error(e);
        }
    }

    // ─── Event Delegation ───
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var idx = parseInt(btn.getAttribute('data-idx'));
        if (action === 'create') openCreate(idx);
        if (action === 'history') openHistory(idx);
    });

    $('btn-prev').addEventListener('click', function() {
        if (state.page > 1) { state.page--; loadData(); }
    });

    $('btn-next').addEventListener('click', function() {
        if (state.meta.last_page && state.page < state.meta.last_page) { state.page++; loadData(); }
    });

    $('search-input').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var val = this.value;
        debounceTimer = setTimeout(function() {
            state.search = val;
            state.page = 1;
            loadData();
        }, 300);
    });

    // ─── Public API (for onclick handlers in dynamic HTML) ───
    window.VendorPayouts = {
        openCreate: openCreate,
        closeCreate: closeCreate,
        togglePhone: togglePhone,
        submitCreate: submitCreate,
        openHistory: openHistory,
        closeHistory: closeHistory,
        openMarkSent: openMarkSent,
        closeMarkSent: closeMarkSent,
        submitMarkSent: submitMarkSent,
        confirmPayout: confirmPayout
    };

    // ─── Init ───
    loadData();
})();
</script>
@endpush
