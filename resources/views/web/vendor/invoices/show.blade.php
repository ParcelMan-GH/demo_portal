@extends('web.layouts.vendor')

@section('title', 'Invoice Details')
@section('page-title', 'Invoice Details')

@section('content')
<div x-data="vendorInvoiceShowPage()" data-invoice-id="{{ $invoiceId }}">
    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
            <svg class="mx-auto h-6 w-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-3 text-sm text-slate-500">Loading invoice...</p>
        </div>
    </div>

    {{-- Alert --}}
    <div x-show="alert" x-cloak style="margin-bottom: 1rem;">
        <div class="rounded-lg border px-4 py-3 text-sm"
             :class="{ 'border-green-200 bg-green-50 text-green-700': alert?.type === 'success', 'border-red-200 bg-red-50 text-red-700': alert?.type === 'error' }">
            <span x-text="alert?.message"></span>
        </div>
    </div>

    {{-- Validation Errors --}}
    <div x-show="validationErrors.length" x-cloak style="margin-bottom: 1rem;">
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>
    </div>

    {{-- Action Dialog (Accept / Reject) --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center" x-show="actionDialog" x-cloak>
        <div class="fixed inset-0 bg-black/40" @click="closeActionDialog()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative rounded-xl bg-white p-6 shadow-xl max-w-md w-full mx-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            {{-- Header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full flex-shrink-0"
                     :class="actionDialog?.type === 'accept' ? 'bg-green-100' : 'bg-red-100'">
                    <template x-if="actionDialog?.type === 'accept'">
                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="actionDialog?.type === 'reject'">
                        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-slate-900" x-text="actionDialog?.title"></h3>
                    <p class="mt-1 text-sm text-slate-600" x-text="actionDialog?.message"></p>
                </div>
            </div>
            {{-- Text input --}}
            <div class="mb-5">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" x-text="actionDialog?.inputLabel"></label>
                <textarea x-model="actionInput" rows="3"
                          class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-orange-300 focus:bg-white focus:ring-2 focus:ring-orange-100"
                          :placeholder="actionDialog?.inputPlaceholder"></textarea>
                <p class="mt-1 text-[11px] text-red-500" x-show="actionDialog?.inputRequired && actionBusy === false && actionInput.trim() === '' && actionDialog"
                   x-cloak style="display: none;"></p>
            </div>
            {{-- Actions --}}
            <div class="flex justify-end gap-2">
                <button type="button" @click="closeActionDialog()"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        :disabled="actionBusy">
                    Cancel
                </button>
                <button type="button" @click="confirmAction()"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-semibold text-white transition"
                        :class="actionDialog?.type === 'accept' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
                        :disabled="actionBusy">
                    <svg x-show="actionBusy" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="actionBusy ? 'Processing...' : actionDialog?.confirmLabel"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Invoice content --}}
    <div x-show="!loading && invoice" x-cloak>

        {{-- ==================== Hero Section ==================== --}}
        <div class="sh-hero no-progress" :class="heroClass">
            <div class="sh-hero-inner">
                {{-- Top row: back button + title | download pdf + status badge --}}
                <div class="sh-hero-top">
                    <div class="sh-hero-left">
                        <a href="{{ route('web.vendor.invoices.index') }}" class="sh-back" title="Back to Invoices">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <span class="sh-title-text">Invoice Details</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <button type="button" class="inv-pdf-btn" title="Download PDF" @click="downloadPdf()" :disabled="downloadingPdf" :class="{ 'opacity-60 pointer-events-none': downloadingPdf }">
                            <svg x-show="!downloadingPdf" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <svg x-show="downloadingPdf" x-cloak class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="downloadingPdf ? 'Downloading...' : 'Download PDF'"></span>
                        </button>
                        <div class="sh-status-badge">
                            <span x-text="vendorStatusLabel(invoice.status)"></span>
                        </div>
                    </div>
                </div>

                {{-- Invoice number --}}
                <h1 class="sh-number" x-text="invoice.invoice_number"></h1>

                {{-- Meta row --}}
                <div class="sh-meta">
                    <div class="sh-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Created <span x-text="formatDateTime(invoice.created_at)"></span></span>
                    </div>
                    <div class="sh-meta-item" x-show="invoice.shipment_number">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span x-text="invoice.shipment_number"></span>
                    </div>
                    <div class="sh-meta-item" x-show="invoice.sent_at">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        <span>Sent <span x-text="formatDateTime(invoice.sent_at)"></span></span>
                    </div>
                    <div class="sh-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="formatMoney(invoice.total_amount, invoice.currency)"></span>
                    </div>
                </div>

                {{-- Status message section --}}
                <div>
                    <div class="sh-status-section">
                        <div class="sh-status-icon">
                            <svg x-show="invoice.status === 'pending'" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="invoice.status === 'sent'" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <svg x-show="invoice.status === 'accepted'" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="invoice.status === 'rejected'" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="invoice.status === 'cancelled'" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </div>
                        <div class="sh-status-content">
                            <h3 class="sh-status-title" x-text="heroMessage.title"></h3>
                            <p class="sh-status-text" x-text="heroMessage.text"></p>
                        </div>
                        <div class="sh-status-action" x-show="canRespond" x-cloak>
                            <button type="button" class="sh-action-btn" @click="openActionDialog('accept')">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Accept
                            </button>
                            <button type="button" class="sh-action-btn sh-action-btn-danger" @click="openActionDialog('reject')">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== Grid Layout ==================== --}}
        <div class="sh-grid">

            {{-- ===== Left Column - Main Content ===== --}}
            <div class="sh-main-col">

                {{-- Fee Breakdown Card --}}
                <div class="sh-card">
                    <div class="sh-card-head">
                        <div class="sh-card-icon orange">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        <h3>Fee Breakdown</h3>
                    </div>
                    <div class="sh-card-body">
                        {{-- Pickup Fee --}}
                        <div class="inv-fee-row">
                            <div class="inv-fee-left">
                                <div class="inv-fee-icon">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div>
                                    <div class="inv-fee-name">Pickup Fee</div>
                                    <div class="inv-fee-desc">Collection from sender</div>
                                </div>
                            </div>
                            <div class="inv-fee-amount" x-text="formatMoney(invoice.pickup_fee, invoice.currency)"></div>
                        </div>

                        {{-- Transport Fee --}}
                        <div class="inv-fee-row">
                            <div class="inv-fee-left">
                                <div class="inv-fee-icon">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                </div>
                                <div>
                                    <div class="inv-fee-name">Transport Fee</div>
                                    <div class="inv-fee-desc">Shipment transportation</div>
                                </div>
                            </div>
                            <div class="inv-fee-amount" x-text="formatMoney(invoice.transport_fee, invoice.currency)"></div>
                        </div>

                        {{-- Handling Fee --}}
                        <div class="inv-fee-row">
                            <div class="inv-fee-left">
                                <div class="inv-fee-icon">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </div>
                                <div>
                                    <div class="inv-fee-name">Handling Fee</div>
                                    <div class="inv-fee-desc">Package handling & processing</div>
                                </div>
                            </div>
                            <div class="inv-fee-amount" x-text="formatMoney(invoice.handling_fee, invoice.currency)"></div>
                        </div>

                        {{-- Other Fee --}}
                        <div class="inv-fee-row">
                            <div class="inv-fee-left">
                                <div class="inv-fee-icon">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <div class="inv-fee-name">Other Fee</div>
                                    <div class="inv-fee-desc">Additional charges</div>
                                </div>
                            </div>
                            <div class="inv-fee-amount" x-text="formatMoney(invoice.other_fee, invoice.currency)"></div>
                        </div>

                        {{-- Summary total --}}
                        <div class="inv-fee-summary">
                            <div class="inv-fee-total">
                                <span class="inv-fee-total-label">Total Amount</span>
                                <span class="inv-fee-total-value" x-text="formatMoney(invoice.total_amount, invoice.currency)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes Card --}}
                <div class="sh-card" x-show="hasNotes" x-cloak>
                    <div class="sh-card-head">
                        <div class="sh-card-icon orange">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                        <h3>Notes</h3>
                    </div>
                    <div class="sh-card-body">
                        <div class="inv-note" x-show="invoice.notes">
                            <div class="inv-note-label">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Admin Notes
                            </div>
                            <div class="inv-note-text" x-text="invoice.notes"></div>
                        </div>
                        <div class="inv-note" x-show="invoice.vendor_notes">
                            <div class="inv-note-label">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Vendor Notes
                            </div>
                            <div class="inv-note-text" x-text="invoice.vendor_notes"></div>
                        </div>
                        <div class="inv-note" x-show="invoice.rejection_reason">
                            <div class="inv-note-label inv-note-label-danger">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                Rejection Reason
                            </div>
                            <div class="inv-note-text" x-text="invoice.rejection_reason"></div>
                        </div>
                        <div class="inv-note" x-show="invoice.cancel_reason">
                            <div class="inv-note-label inv-note-label-danger">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Cancel Reason
                            </div>
                            <div class="inv-note-text" x-text="invoice.cancel_reason"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Right Column - Sidebar ===== --}}
            <div class="sh-sidebar-col">

                {{-- Invoice Summary Card --}}
                <div class="inv-summary-card">
                    <div class="inv-summary-header" :class="summaryHeaderClass">
                        <div class="inv-summary-amount" x-text="formatMoney(invoice.total_amount, invoice.currency)"></div>
                        <div class="inv-summary-label">Total Amount</div>
                    </div>
                    <div class="inv-summary-body">
                        <div class="sh-info-row">
                            <span class="sh-info-label">Invoice #</span>
                            <span class="sh-info-value" x-text="invoice.invoice_number"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Currency</span>
                            <span class="sh-info-value" x-text="invoice.currency || '-'"></span>
                        </div>
                        <div class="sh-info-row" x-show="invoice.sent_at">
                            <span class="sh-info-label">Created</span>
                            <span class="sh-info-value" x-text="formatDateTime(invoice.sent_at)"></span>
                        </div>
                        <div class="sh-info-row" x-show="invoice.accepted_at">
                            <span class="sh-info-label">Accepted</span>
                            <span class="sh-info-value" style="color: #10b981;" x-text="formatDateTime(invoice.accepted_at)"></span>
                        </div>
                        <div class="sh-info-row" x-show="invoice.rejected_at">
                            <span class="sh-info-label">Rejected</span>
                            <span class="sh-info-value" style="color: #ef4444;" x-text="formatDateTime(invoice.rejected_at)"></span>
                        </div>

                        {{-- Accept / Reject buttons --}}
                        <div class="inv-summary-actions" x-show="canRespond" x-cloak>
                            <button type="button" class="sh-accept-btn" @click="openActionDialog('accept')">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Accept Invoice
                            </button>
                            <button type="button" class="sh-reject-btn" @click="openActionDialog('reject')">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject Invoice
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Related Shipment Card --}}
                <div class="sh-sidebar-card" x-show="invoice.shipment_id" x-cloak>
                    <div class="sh-sidebar-head">
                        <div class="head-icon blue">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h4>Related Shipment</h4>
                    </div>
                    <div class="sh-sidebar-body">
                        <div class="sh-info-row" style="padding-top: 0;">
                            <span class="sh-info-label">Shipment #</span>
                            <span class="sh-info-value" x-text="invoice.shipment_number"></span>
                        </div>
                        <a :href="'/vendor/shipments/' + invoice.shipment_id"
                           class="inv-shipment-link">
                            View Shipment Details
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Not found state --}}
    <div x-show="!loading && !invoice" x-cloak class="vendor-card px-6 py-12 text-center">
        <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        <p class="mt-2 text-sm text-slate-500">Invoice details could not be loaded.</p>
        <a href="{{ route('web.vendor.invoices.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Invoices
        </a>
    </div>
</div>
@endsection
