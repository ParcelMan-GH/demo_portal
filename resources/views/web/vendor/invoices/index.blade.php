@extends('web.layouts.vendor')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div x-data="vendorInvoicesListPage()">

    {{-- Page Hero --}}
    <div class="inv-hero">
        <div class="inv-hero-content">
            <div class="inv-hero-text">
                <span class="inv-hero-title-icon">
                    <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </span>
                <div class="inv-hero-text-group">
                    <h1>Invoices</h1>
                    <p>View and manage all your invoices</p>
                </div>
            </div>
            <div class="inv-hero-stats">
                <div class="inv-hero-stat">
                    <span class="inv-hero-stat-value" x-text="tabCounts.all"></span>
                    <span class="inv-hero-stat-label">Total</span>
                </div>
                <div class="inv-hero-stat">
                    <span class="inv-hero-stat-value" x-text="tabCounts.pending"></span>
                    <span class="inv-hero-stat-label">Pending</span>
                </div>
                <div class="inv-hero-stat">
                    <span class="inv-hero-stat-value" x-text="tabCounts.accepted"></span>
                    <span class="inv-hero-stat-label">Accepted</span>
                </div>
                <div class="inv-hero-stat">
                    <span class="inv-hero-stat-value" x-text="tabCounts.rejected"></span>
                    <span class="inv-hero-stat-label">Rejected</span>
                </div>
            </div>
        </div>

        {{-- Filter Tabs (fused into hero) --}}
        <div class="inv-filter-tabs">
            <template x-for="tab in [
                { key: 'all', label: 'All' },
                { key: 'pending', label: 'Pending' },
                { key: 'accepted', label: 'Accepted' },
                { key: 'rejected', label: 'Rejected' },
                { key: 'cancelled', label: 'Cancelled' },
            ]" :key="tab.key">
                <button type="button"
                    class="inv-filter-tab"
                    :class="{ 'active': activeTab === tab.key }"
                    @click="switchTab(tab.key)">
                    <span x-text="tab.label"></span>
                    <span class="inv-filter-count" x-text="tabCounts[tab.key]"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="inv-loading">
        <svg class="animate-spin" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Invoice Cards --}}
    <div x-show="!loading" x-cloak>
        <div class="inv-grid">
            <template x-for="invoice in invoices" :key="invoice.id">
                <div class="inv-card" :class="'inv-card-' + statusColor(invoice.status)">
                    <div class="inv-card-inner">

                        {{-- Status icon --}}
                        <div class="inv-icon-wrap" :class="statusColor(invoice.status)">
                            <template x-if="invoice.status === 'accepted'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="invoice.status === 'pending' || invoice.status === 'sent'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="invoice.status === 'rejected'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="invoice.status === 'cancelled'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </template>
                        </div>

                        {{-- Main content - inline row --}}
                        <div class="inv-main">
                            <a :href="`/vendor/invoices/${invoice.id}`" class="inv-number" x-text="invoice.invoice_number"></a>
                            <span class="inv-meta-item amount">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span x-text="formatMoney(invoice.total_amount, invoice.currency)"></span>
                            </span>
                            <span class="inv-meta-item">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span x-text="formatDate(invoice.created_at)"></span>
                            </span>
                            <a x-show="invoice.shipment_number" :href="`/vendor/shipments/${invoice.shipment_id}`" class="inv-meta-item inv-shipment-link">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span x-text="invoice.shipment_number"></span>
                            </a>
                        </div>

                        {{-- Actions (status + buttons pushed right) --}}
                        <div class="inv-actions">
                            <button x-show="invoice.status === 'sent'" type="button" @click="acceptInvoice(invoice)" class="inv-btn-accept">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Accept
                            </button>
                            <button x-show="invoice.status === 'sent'" type="button" @click="rejectInvoice(invoice)" class="inv-btn-reject">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject
                            </button>
                            <span class="inv-status-badge" :class="statusColor(invoice.status)">
                                <span x-text="vendorStatusLabel(invoice.status)"></span>
                            </span>
                            <a :href="`/vendor/invoices/${invoice.id}`" class="inv-btn-view">
                                View Details
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="invoices.length === 0" class="inv-empty">
            <div class="inv-empty-icon">
                <svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            </div>
            <h3>No invoices found</h3>
            <p>There are no invoices matching your current filter.</p>
        </div>

        {{-- Pagination --}}
        <div class="inv-pagination" x-show="invoices.length > 0 && pagination.last_page > 1">
            <button type="button" @click="previousPage()" :disabled="filters.offset <= 0" class="inv-page-btn">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Previous
            </button>
            <span class="inv-page-info">Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span></span>
            <button type="button" @click="nextPage()" :disabled="!pagination.has_more" class="inv-page-btn">
                Next
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</div>
@endsection
