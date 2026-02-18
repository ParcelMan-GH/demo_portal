@extends('web.layouts.vendor')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="vendorHomePage()">

    {{-- Loading --}}
    <div x-show="loading" class="dash-loading">
        <svg class="animate-spin" width="28" height="28" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Error --}}
    <div x-show="!loading && error" x-cloak class="dash-error" x-text="error"></div>

    {{-- Dashboard --}}
    <div x-show="!loading && profile" x-cloak>

        {{-- Hero Header --}}
        <div class="dash-hero">
            <div class="dash-hero-inner">
                {{-- Top row --}}
                <div class="dash-hero-top-row">
                    <div class="dash-hero-date">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="todayFormatted"></span>
                    </div>
                    <a href="{{ route('web.vendor.shipments.create') }}" class="dash-hero-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        New Shipment
                    </a>
                </div>

                {{-- Welcome --}}
                <h1 class="dash-hero-title">Welcome back, <span x-text="profile?.name || 'Vendor'"></span></h1>
                <p class="dash-hero-subtitle" x-show="profile?.business_name" x-text="profile?.business_name"></p>

                {{-- Meta row --}}
                <div class="dash-hero-meta">
                    <div class="dash-hero-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span><strong x-text="stats.shipments.total"></strong> Shipments</span>
                    </div>
                    <div class="dash-hero-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        <span><strong x-text="stats.invoices.total"></strong> Invoices</span>
                    </div>
                    <div class="dash-hero-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong x-text="stats.shipments.delivered"></strong> Delivered</span>
                    </div>
                    <div class="dash-hero-meta-item" x-show="stats.shipments.in_progress > 0">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <span><strong x-text="stats.shipments.in_progress"></strong> In Transit</span>
                    </div>
                </div>

                {{-- Status summary --}}
                <div class="dash-hero-summary">
                    <div class="dash-hero-summary-icon">
                        <svg x-show="stats.invoices.pending > 0" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg x-show="stats.invoices.pending === 0" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="dash-hero-summary-text">
                        <h3 x-show="stats.invoices.pending > 0" x-text="`You have ${stats.invoices.pending} pending invoice${stats.invoices.pending !== 1 ? 's' : ''} to review`"></h3>
                        <h3 x-show="stats.invoices.pending === 0 && stats.shipments.draft > 0" x-text="`You have ${stats.shipments.draft} draft shipment${stats.shipments.draft !== 1 ? 's' : ''} ready to submit`"></h3>
                        <h3 x-show="stats.invoices.pending === 0 && stats.shipments.draft === 0">All caught up! No pending actions.</h3>
                        <p x-show="stats.shipments.in_progress > 0" x-text="`${stats.shipments.in_progress} shipment${stats.shipments.in_progress !== 1 ? 's' : ''} currently being processed`"></p>
                        <p x-show="stats.shipments.in_progress === 0 && stats.shipments.total > 0">All your shipments have been processed.</p>
                        <p x-show="stats.shipments.total === 0">Create your first shipment to get started.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="dash-stats">
            {{-- Total Shipments --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent blue"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon blue">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.shipments.total"></span>
                        <span class="dash-stat-label">Total Shipments</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill gray">
                        <span class="dash-stat-pill-dot gray"></span>
                        <span x-text="stats.shipments.draft + ' draft'"></span>
                    </span>
                    <span class="dash-stat-pill red" x-show="stats.shipments.cancelled > 0">
                        <span class="dash-stat-pill-dot red"></span>
                        <span x-text="stats.shipments.cancelled + ' cancelled'"></span>
                    </span>
                </div>
            </div>

            {{-- In Progress --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent orange"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon orange">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.shipments.in_progress"></span>
                        <span class="dash-stat-label">In Progress</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill orange">
                        <span class="dash-stat-pill-dot orange"></span>
                        Active shipments
                    </span>
                </div>
            </div>

            {{-- Pending Invoices --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent amber"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon amber">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.invoices.pending"></span>
                        <span class="dash-stat-label">Pending Invoices</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill green">
                        <span class="dash-stat-pill-dot green"></span>
                        <span x-text="stats.invoices.accepted + ' accepted'"></span>
                    </span>
                    <span class="dash-stat-pill red" x-show="stats.invoices.rejected > 0">
                        <span class="dash-stat-pill-dot red"></span>
                        <span x-text="stats.invoices.rejected + ' rejected'"></span>
                    </span>
                </div>
            </div>

            {{-- Delivered --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent green"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon green">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.shipments.delivered"></span>
                        <span class="dash-stat-label">Delivered</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill blue">
                        <span class="dash-stat-pill-dot blue"></span>
                        <span x-text="stats.invoices.total + ' total invoices'"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="dash-actions">
            <h3 class="dash-section-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Actions
            </h3>
            <div class="dash-actions-row">
                <a href="{{ route('web.vendor.shipments.create') }}" class="dash-action-btn primary">
                    <div class="dash-action-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span>Create Shipment</span>
                </a>
                <a href="{{ route('web.vendor.shipments.index') }}" class="dash-action-btn">
                    <div class="dash-action-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span>All Shipments</span>
                </a>
                <a href="{{ route('web.vendor.invoices.index') }}" class="dash-action-btn">
                    <div class="dash-action-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <span>All Invoices</span>
                </a>
            </div>
        </div>

        {{-- Recent Activity Grid --}}
        <div class="dash-grid">

            {{-- Recent Shipments --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div class="dash-panel-title">
                        <div class="dash-panel-icon orange">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        Recent Shipments
                    </div>
                    <a href="{{ route('web.vendor.shipments.index') }}" class="dash-panel-link">
                        View All
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="dash-panel-body">
                    <template x-for="s in recentShipments" :key="s.id">
                        <a :href="`/vendor/shipments/${s.id}`" class="dash-list-item" :class="statusColor(s.status)">
                            {{-- Status avatar --}}
                            <div class="dash-list-avatar" :class="statusColor(s.status)">
                                <svg x-show="s.status === 'draft'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <svg x-show="s.status === 'submitted'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <svg x-show="s.status === 'delivered'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="s.status === 'cancelled'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="!['draft','submitted','delivered','cancelled'].includes(s.status)" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                            </div>
                            {{-- Details --}}
                            <div class="dash-list-details">
                                <div class="dash-list-header">
                                    <span class="dash-list-title" x-text="s.shipment_number"></span>
                                    <span class="dash-list-badge" :class="statusColor(s.status)" x-text="statusLabel(s.status)"></span>
                                </div>
                                <div class="dash-list-sub">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span x-text="(s.items?.length || 0) + ' items'"></span>
                                </div>
                            </div>
                            {{-- Meta --}}
                            <div class="dash-list-meta">
                                <div class="dash-list-date">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="formatDate(s.created_at)"></span>
                                </div>
                            </div>
                            {{-- Arrow --}}
                            <div class="dash-list-arrow">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                    </template>
                    <div x-show="recentShipments.length === 0" class="dash-empty-mini">
                        <div class="dash-empty-icon">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="dash-empty-title">No Shipments Yet</div>
                        <div class="dash-empty-text">Your recent shipments will appear here</div>
                    </div>
                </div>
            </div>

            {{-- Recent Invoices --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div class="dash-panel-title">
                        <div class="dash-panel-icon amber">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        Recent Invoices
                    </div>
                    <a href="{{ route('web.vendor.invoices.index') }}" class="dash-panel-link">
                        View All
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="dash-panel-body">
                    <template x-for="inv in recentInvoices" :key="inv.id">
                        <a :href="`/vendor/invoices/${inv.id}`" class="dash-list-item" :class="invoiceStatusColor(inv.status)">
                            {{-- Status avatar --}}
                            <div class="dash-list-avatar" :class="invoiceStatusColor(inv.status)">
                                <svg x-show="inv.status === 'pending' || inv.status === 'sent'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="inv.status === 'accepted'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="inv.status === 'rejected'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="inv.status === 'cancelled'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            {{-- Details --}}
                            <div class="dash-list-details">
                                <div class="dash-list-header">
                                    <span class="dash-list-title" x-text="inv.invoice_number"></span>
                                    <span class="dash-list-badge" :class="invoiceStatusColor(inv.status)" x-text="statusLabel(inv.status)"></span>
                                </div>
                                <div class="dash-list-sub">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span x-text="formatMoney(inv.total_amount, inv.currency)"></span>
                                </div>
                            </div>
                            {{-- Meta --}}
                            <div class="dash-list-meta">
                                <div class="dash-list-date">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="formatDate(inv.created_at)"></span>
                                </div>
                            </div>
                            {{-- Arrow --}}
                            <div class="dash-list-arrow">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                    </template>
                    <div x-show="recentInvoices.length === 0" class="dash-empty-mini">
                        <div class="dash-empty-icon">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        <div class="dash-empty-title">No Invoices Yet</div>
                        <div class="dash-empty-text">Your recent invoices will appear here</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
