@extends('web.layouts.vendor')

@section('title', 'Shipments')
@section('page-title', 'Shipments')

@section('content')
<div x-data="vendorShipmentsListPage()" class="shp-listing">

    {{-- Page Hero --}}
    <div class="inv-hero">
        <div class="inv-hero-content">
            <div class="inv-hero-text">
                <span class="inv-hero-title-icon">
                    <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
                <div class="inv-hero-text-group">
                    <h1>Shipments</h1>
                    <p>Manage and track all your shipments</p>
                </div>
            </div>
            <a href="{{ route('web.vendor.shipments.create') }}" class="shp-hero-btn">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Shipment
            </a>
        </div>

        {{-- Filter Tabs (fused into hero) --}}
        <div class="inv-filter-tabs">
            <template x-for="tab in [
                { key: 'all', label: 'All' },
                { key: 'draft', label: 'Draft' },
                { key: 'submitted', label: 'Submitted' },
                { key: 'in_progress', label: 'In Progress' },
                { key: 'delivered', label: 'Delivered' },
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

    {{-- Alert --}}
    <div class="mb-4" x-show="alert" x-cloak>
        <div class="rounded-lg border px-4 py-3 text-sm"
             :class="{ 'border-green-200 bg-green-50 text-green-700': alert?.type === 'success', 'border-red-200 bg-red-50 text-red-700': alert?.type === 'error' }">
            <span x-text="alert?.message"></span>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="inv-loading">
        <svg class="animate-spin" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Shipment Cards --}}
    <div x-show="!loading" x-cloak>
        <div class="inv-grid">
            <template x-for="shipment in shipments" :key="shipment.id">
                <div class="inv-card" :class="'inv-card-' + statusColor(shipment.status)">
                    <div class="inv-card-inner">

                        {{-- Status icon --}}
                        <div class="inv-icon-wrap" :class="statusColor(shipment.status)">
                            <template x-if="statusColor(shipment.status) === 'gray'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </template>
                            <template x-if="statusColor(shipment.status) === 'blue'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </template>
                            <template x-if="statusColor(shipment.status) === 'orange'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </template>
                            <template x-if="statusColor(shipment.status) === 'cyan'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                            </template>
                            <template x-if="statusColor(shipment.status) === 'green'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="statusColor(shipment.status) === 'red'">
                                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </template>
                        </div>

                        {{-- Main content --}}
                        <div class="inv-main">
                            <a :href="`/vendor/shipments/${shipment.id}`" class="inv-number" x-text="shipment.shipment_number"></a>
                            <span class="inv-meta-item">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span x-text="modeLabel(shipment.destination_mode)"></span>
                            </span>
                            <span class="inv-meta-item">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span x-text="(shipment.items?.length ?? 0) + ' items'"></span>
                            </span>
                            <span x-show="shipment.submitted_at" class="inv-meta-item">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span x-text="formatDate(shipment.submitted_at)"></span>
                            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="inv-actions">
                            <button x-show="shipment.can_submit" type="button" @click="submitShipment(shipment)" class="inv-btn-accept">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Submit
                            </button>
                            <button x-show="shipment.can_delete" type="button" @click="deleteShipment(shipment)" class="inv-btn-reject">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                            <span class="inv-status-badge" :class="statusColor(shipment.status)">
                                <span x-text="statusLabel(shipment.status)"></span>
                            </span>
                            <a :href="`/vendor/shipments/${shipment.id}`" class="inv-btn-view">
                                View Details
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="shipments.length === 0" class="inv-empty">
            <div class="inv-empty-icon">
                <svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3>No shipments found</h3>
            <p>There are no shipments matching your current filter.</p>
        </div>

        {{-- Pagination --}}
        <div class="inv-pagination" x-show="shipments.length > 0 && pagination.last_page > 1">
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
