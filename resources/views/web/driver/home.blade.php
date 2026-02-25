@extends('web.layouts.driver')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="driverHomePage()">

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
                    <a href="{{ route('web.driver.pickups.index') }}" class="dash-hero-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        My Pickups
                    </a>
                </div>

                {{-- Welcome --}}
                <h1 class="dash-hero-title">Welcome back, <span x-text="profile?.name || 'Driver'"></span></h1>
                <p class="dash-hero-subtitle" x-show="profile?.vehicle_type || profile?.vehicle_number">
                    <span x-text="vehicleSubtitle"></span>
                </p>

                {{-- Meta row --}}
                <div class="dash-hero-meta">
                    <div class="dash-hero-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span><strong x-text="stats.pickups.total"></strong> Total Pickups</span>
                    </div>
                    <div class="dash-hero-meta-item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span><strong x-text="totalActiveTasks"></strong> Active Tasks</span>
                    </div>
                    <div class="dash-hero-meta-item" x-show="profile?.task_capabilities?.length > 0">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        <template x-for="cap in (profile?.task_capabilities || [])" :key="cap">
                            <span class="dash-hero-capability-pill" x-text="cap.charAt(0).toUpperCase() + cap.slice(1)"></span>
                        </template>
                    </div>
                </div>

                {{-- Status summary --}}
                <div class="dash-hero-summary">
                    <div class="dash-hero-summary-icon">
                        <svg x-show="totalActiveTasks > 0" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <svg x-show="totalActiveTasks === 0" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="dash-hero-summary-text">
                        <h3 x-show="stats.pickups.active > 0" x-text="`You have ${stats.pickups.active} active pickup${stats.pickups.active !== 1 ? 's' : ''} to complete`"></h3>
                        <h3 x-show="stats.pickups.active === 0 && stats.transports.active > 0" x-text="`${stats.transports.active} transport${stats.transports.active !== 1 ? 's' : ''} in progress`"></h3>
                        <h3 x-show="stats.pickups.active === 0 && stats.transports.active === 0 && stats.deliveries.active > 0" x-text="`${stats.deliveries.active} delivery run${stats.deliveries.active !== 1 ? 's' : ''} in progress`"></h3>
                        <h3 x-show="totalActiveTasks === 0">All clear! No active tasks right now.</h3>
                        <p x-show="stats.pickups.en_route > 0" x-text="`${stats.pickups.en_route} pickup${stats.pickups.en_route !== 1 ? 's' : ''} currently en route`"></p>
                        <p x-show="stats.pickups.en_route === 0 && stats.deliveries.out_for_delivery > 0" x-text="`${stats.deliveries.out_for_delivery} delivery run${stats.deliveries.out_for_delivery !== 1 ? 's' : ''} out for delivery`"></p>
                        <p x-show="stats.pickups.en_route === 0 && stats.deliveries.out_for_delivery === 0 && stats.pickups.completed > 0" x-text="`${stats.pickups.completed} pickup${stats.pickups.completed !== 1 ? 's' : ''} completed`"></p>
                        <p x-show="totalActiveTasks === 0 && stats.pickups.completed === 0">Start by completing your assigned pickups.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="dash-stats">

            {{-- Active Pickups --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent emerald"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon emerald">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.pickups.active"></span>
                        <span class="dash-stat-label">Active Pickups</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill orange" x-show="stats.pickups.en_route > 0">
                        <span class="dash-stat-pill-dot orange"></span>
                        <span x-text="stats.pickups.en_route + ' en route'"></span>
                    </span>
                    <span class="dash-stat-pill gray" x-show="stats.pickups.en_route === 0">
                        <span class="dash-stat-pill-dot gray"></span>
                        <span x-text="stats.pickups.total + ' total'"></span>
                    </span>
                    <span class="dash-stat-pill red" x-show="stats.pickups.cancelled > 0">
                        <span class="dash-stat-pill-dot red"></span>
                        <span x-text="stats.pickups.cancelled + ' cancelled'"></span>
                    </span>
                </div>
            </div>

            {{-- Transports --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent blue"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon blue">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.transports.active"></span>
                        <span class="dash-stat-label">Transports</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill blue" x-show="stats.transports.in_transit > 0">
                        <span class="dash-stat-pill-dot blue"></span>
                        <span x-text="stats.transports.in_transit + ' in transit'"></span>
                    </span>
                    <span class="dash-stat-pill blue" x-show="stats.transports.in_transit === 0">
                        <span class="dash-stat-pill-dot blue"></span>
                        Active manifests
                    </span>
                    <span class="dash-stat-pill green" x-show="stats.transports.arrived > 0">
                        <span class="dash-stat-pill-dot green"></span>
                        <span x-text="stats.transports.arrived + ' arrived'"></span>
                    </span>
                </div>
            </div>

            {{-- Pending Deliveries --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent amber"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon amber">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.deliveries.active"></span>
                        <span class="dash-stat-label">Pending Deliveries</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill orange" x-show="stats.deliveries.out_for_delivery > 0">
                        <span class="dash-stat-pill-dot orange"></span>
                        <span x-text="stats.deliveries.out_for_delivery + ' out for delivery'"></span>
                    </span>
                    <span class="dash-stat-pill amber" x-show="stats.deliveries.active === 0">
                        <span class="dash-stat-pill-dot amber"></span>
                        Delivery runs
                    </span>
                    <span class="dash-stat-pill amber" x-show="stats.deliveries.partially_delivered > 0">
                        <span class="dash-stat-pill-dot amber"></span>
                        <span x-text="stats.deliveries.partially_delivered + ' partial'"></span>
                    </span>
                </div>
            </div>

            {{-- Completed --}}
            <div class="dash-stat-card">
                <div class="dash-stat-accent green"></div>
                <div class="dash-stat-top">
                    <div class="dash-stat-icon green">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="dash-stat-data">
                        <span class="dash-stat-value" x-text="stats.pickups.completed + stats.deliveries.completed"></span>
                        <span class="dash-stat-label">Completed</span>
                    </div>
                </div>
                <div class="dash-stat-footer">
                    <span class="dash-stat-pill emerald">
                        <span class="dash-stat-pill-dot emerald"></span>
                        <span x-text="stats.pickups.completed + ' pickups'"></span>
                    </span>
                    <span class="dash-stat-pill blue" x-show="stats.deliveries.completed > 0">
                        <span class="dash-stat-pill-dot blue"></span>
                        <span x-text="stats.deliveries.completed + ' deliveries'"></span>
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
                <a href="{{ route('web.driver.pickups.index') }}" class="dash-action-btn primary">
                    <div class="dash-action-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <span>My Pickups</span>
                </a>
                <a href="{{ route('web.driver.profile') }}" class="dash-action-btn">
                    <div class="dash-action-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <span>My Profile</span>
                </a>
            </div>
        </div>

        {{-- Recent Activity Grid --}}
        <div class="dash-grid">

            {{-- Recent Pickups --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div class="dash-panel-title">
                        <div class="dash-panel-icon emerald">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        Recent Pickups
                    </div>
                    <a href="{{ route('web.driver.pickups.index') }}" class="dash-panel-link">
                        View All
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="dash-panel-body">
                    <template x-for="p in recentPickups" :key="p.id">
                        <a :href="`/driver/pickups/${p.id}`" class="dash-list-item" :class="pickupStatusColor(p.status)">
                            {{-- Status avatar --}}
                            <div class="dash-list-avatar" :class="pickupStatusColor(p.status)">
                                {{-- assigned --}}
                                <svg x-show="p.status === 'assigned'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{-- en_route --}}
                                <svg x-show="p.status === 'en_route'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                {{-- arrived --}}
                                <svg x-show="p.status === 'arrived'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{-- picking_up --}}
                                <svg x-show="p.status === 'picking_up'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                {{-- completed --}}
                                <svg x-show="p.status === 'completed'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{-- cancelled --}}
                                <svg x-show="p.status === 'cancelled'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            {{-- Details --}}
                            <div class="dash-list-details">
                                <div class="dash-list-header">
                                    <span class="dash-list-title" x-text="p.shipment?.shipment_number || 'Pickup #' + p.id"></span>
                                    <span class="dash-list-badge" :class="pickupStatusColor(p.status)" x-text="statusLabel(p.status)"></span>
                                </div>
                                <div class="dash-list-sub">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    <span x-text="(p.shipment?.items?.length || 0) + ' items'"></span>
                                </div>
                            </div>
                            {{-- Meta --}}
                            <div class="dash-list-meta">
                                <div class="dash-list-date">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="formatDate(p.created_at)"></span>
                                </div>
                            </div>
                            {{-- Arrow --}}
                            <div class="dash-list-arrow">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                    </template>
                    <div x-show="recentPickups.length === 0" class="dash-empty-mini">
                        <div class="dash-empty-icon">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="dash-empty-title">No Pickups Yet</div>
                        <div class="dash-empty-text">Your assigned pickups will appear here</div>
                    </div>
                </div>
            </div>

            {{-- Recent Deliveries --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div class="dash-panel-title">
                        <div class="dash-panel-icon amber">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        Recent Deliveries
                    </div>
                </div>
                <div class="dash-panel-body">
                    <template x-for="d in recentDeliveries" :key="d.id">
                        <div class="dash-list-item" :class="deliveryStatusColor(d.status)">
                            {{-- Status avatar --}}
                            <div class="dash-list-avatar" :class="deliveryStatusColor(d.status)">
                                <svg x-show="d.status === 'assigned'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="d.status === 'out_for_delivery'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                <svg x-show="d.status === 'partially_delivered'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <svg x-show="d.status === 'completed'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="d.status === 'cancelled'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg x-show="!['assigned','out_for_delivery','partially_delivered','completed','cancelled'].includes(d.status)" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            </div>
                            {{-- Details --}}
                            <div class="dash-list-details">
                                <div class="dash-list-header">
                                    <span class="dash-list-title" x-text="d.run_number || 'Run #' + d.id"></span>
                                    <span class="dash-list-badge" :class="deliveryStatusColor(d.status)" x-text="statusLabel(d.status)"></span>
                                </div>
                                <div class="dash-list-sub">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span x-text="(d.stops?.length || 0) + ' stops'"></span>
                                </div>
                            </div>
                            {{-- Meta --}}
                            <div class="dash-list-meta">
                                <div class="dash-list-date">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="formatDate(d.created_at)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="recentDeliveries.length === 0" class="dash-empty-mini">
                        <div class="dash-empty-icon">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        </div>
                        <div class="dash-empty-title">No Deliveries Yet</div>
                        <div class="dash-empty-text">Your assigned delivery runs will appear here</div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
