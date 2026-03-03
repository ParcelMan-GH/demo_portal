@extends('web.layouts.driver')

@section('title', 'My Pickups')

@section('content')
<div x-data="driverPickupsListPage()">

    {{-- Hero --}}
    <div class="inv-hero">
        <div class="inv-hero-content">
            <div class="inv-hero-text">
                <span class="inv-hero-title-icon">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
                <div class="inv-hero-text-group">
                    <h1>My Pickups</h1>
                    <p>Manage and track your pickup assignments</p>
                </div>
            </div>
        </div>

        {{-- Filter tabs --}}
        <div class="inv-filter-tabs">
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'all' }" @click="switchTab('all')">
                All <span class="inv-filter-count" x-text="tabCounts.all"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'assigned' }" @click="switchTab('assigned')">
                Assigned <span class="inv-filter-count" x-text="tabCounts.assigned"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'en_route' }" @click="switchTab('en_route')">
                En Route <span class="inv-filter-count" x-text="tabCounts.en_route"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'arrived' }" @click="switchTab('arrived')">
                Arrived <span class="inv-filter-count" x-text="tabCounts.arrived"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'picking_up' }" @click="switchTab('picking_up')">
                Picking Up <span class="inv-filter-count" x-text="tabCounts.picking_up"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'completed' }" @click="switchTab('completed')">
                Completed <span class="inv-filter-count" x-text="tabCounts.completed"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'cancelled' }" @click="switchTab('cancelled')">
                Cancelled <span class="inv-filter-count" x-text="tabCounts.cancelled"></span>
            </button>
        </div>
    </div>

    {{-- Alert --}}
    <div x-show="alert" x-cloak class="mb-4 rounded-xl border px-4 py-3 text-sm"
         :class="{
            'border-emerald-300/30 bg-emerald-50 text-emerald-800': alert?.type === 'success',
            'border-rose-300/30 bg-rose-50 text-rose-800': alert?.type === 'error'
         }">
        <span x-text="alert?.message"></span>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="inv-loading">
        <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Loading pickups...
    </div>

    {{-- Card grid --}}
    <div x-show="!loading" x-cloak>
        <div class="inv-grid">
            <template x-for="pickup in pickups" :key="pickup.id">
                <div class="inv-card" :class="`inv-card-${statusColor(pickup.status)}`">
                    <div class="inv-card-inner">
                        {{-- Icon --}}
                        <div class="inv-icon-wrap" :class="statusColor(pickup.status)">
                            <template x-if="pickup.status === 'assigned'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </template>
                            <template x-if="pickup.status === 'en_route'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            </template>
                            <template x-if="pickup.status === 'arrived'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </template>
                            <template x-if="pickup.status === 'picking_up'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </template>
                            <template x-if="pickup.status === 'completed'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="pickup.status === 'cancelled'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="!['assigned','en_route','arrived','picking_up','completed','cancelled'].includes(pickup.status)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </template>
                        </div>

                        {{-- Main info --}}
                        <div class="inv-main">
                            <a :href="`/driver/pickups/${pickup.id}`" class="inv-number"
                               x-text="pickup.shipment_number || `Pickup #${pickup.id}`"></a>
                            <div class="inv-meta-item">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span x-text="pickup.shipment?.vendor_name || 'No vendor'"></span>
                            </div>
                            <div class="inv-meta-item">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span x-text="pickup.shipment?.pickup?.contact_name || '-'"></span>
                                <span x-show="pickup.shipment?.pickup?.contact_phone" x-text="`· ${pickup.shipment?.pickup?.contact_phone}`"></span>
                            </div>
                            <div class="inv-meta-item" x-show="pickup.timeline?.assigned?.at">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Assigned: <span x-text="formatDateTime(pickup.timeline?.assigned?.at)"></span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="inv-actions">
                            <span class="inv-status-badge" :class="statusColor(pickup.status)" x-text="statusLabel(pickup.status)"></span>
                            <button x-show="pickup.status === 'assigned'" type="button" @click="startEnRoute(pickup)"
                                    class="inv-btn-accept">
                                Start En Route
                            </button>
                            <a :href="`/driver/pickups/${pickup.id}`" class="inv-btn-view">
                                View Details
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="pickups.length === 0" x-cloak class="inv-empty">
            <div class="inv-empty-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3>No pickups found</h3>
            <p>No pickups match the current filters. Try switching tabs or resetting filters.</p>
        </div>

        {{-- Pagination --}}
        <div class="inv-pagination" x-show="pickups.length > 0 && pagination.last_page > 1">
            <div class="inv-page-info">
                Page <strong x-text="pagination.current_page"></strong> of <strong x-text="pagination.last_page"></strong>
                &mdash; <strong x-text="pagination.total"></strong> total
            </div>
            <div class="inv-page-btns">
                <button type="button" @click="previousPage()" :disabled="pagination.current_page <= 1" class="inv-page-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Prev
                </button>
                <button type="button" @click="nextPage()" :disabled="!pagination.has_more" class="inv-page-btn">
                    Next
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
