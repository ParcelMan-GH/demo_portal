@extends('web.layouts.driver')

@section('title', 'My Transports')

@section('content')
<div x-data="driverTransportsListPage()">

    {{-- Hero --}}
    <div class="inv-hero">
        <div class="inv-hero-content">
            <div class="inv-hero-text">
                <div class="inv-hero-title-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    <div class="inv-hero-text-group">
                        <p>Driver Portal</p>
                        <h1>My Transports</h1>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <input x-model="filters.search" type="text" placeholder="Search manifest, warehouse..."
                       @keyup.enter="applyFilters()"
                       class="inv-search-input" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#fff;min-width:200px;">
                <button type="button" @click="applyFilters()" class="inv-search-btn">Search</button>
                <button type="button" @click="resetFilters()" class="inv-search-btn" style="background:rgba(255,255,255,0.12);">Reset</button>
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
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'loading' }" @click="switchTab('loading')">
                Loading <span class="inv-filter-count" x-text="tabCounts.loading"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'in_transit' }" @click="switchTab('in_transit')">
                In Transit <span class="inv-filter-count" x-text="tabCounts.in_transit"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'arrived' }" @click="switchTab('arrived')">
                Arrived <span class="inv-filter-count" x-text="tabCounts.arrived"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'received' }" @click="switchTab('received')">
                Received <span class="inv-filter-count" x-text="tabCounts.received"></span>
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
        Loading transports...
    </div>

    {{-- Card grid --}}
    <div x-show="!loading" x-cloak>
        <div class="inv-grid">
            <template x-for="transport in transports" :key="transport.id">
                <div class="inv-card" :class="`inv-card-${statusColor(transport.status)}`">
                    <div class="inv-card-inner">
                        {{-- Icon --}}
                        <div class="inv-icon-wrap" :class="statusColor(transport.status)">
                            <template x-if="transport.status === 'assigned'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </template>
                            <template x-if="transport.status === 'loading'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            </template>
                            <template x-if="transport.status === 'in_transit'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            </template>
                            <template x-if="transport.status === 'arrived'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </template>
                            <template x-if="transport.status === 'received'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="transport.status === 'cancelled'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="!['assigned','loading','in_transit','arrived','received','cancelled'].includes(transport.status)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </template>
                        </div>

                        {{-- Main info --}}
                        <div class="inv-main">
                            <a :href="`/driver/transports/${transport.id}`" class="inv-number"
                               x-text="transport.manifest_number || `Transport #${transport.id}`"></a>
                            <div class="inv-meta-item">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                <span x-text="transport.origin_warehouse?.name || '-'"></span>
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#10b981;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                <span x-text="transport.destination_warehouse?.name || '-'"></span>
                            </div>
                            <div class="inv-meta-item">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span x-text="`${transport.items_count ?? (transport.items || []).length} items`"></span>
                                <span x-show="transport.loaded_count !== undefined"> · <span x-text="transport.loaded_count"></span> loaded</span>
                            </div>
                            <div class="inv-meta-item" x-show="transport.assigned_at">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Assigned: <span x-text="formatDateTime(transport.assigned_at)"></span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="inv-actions">
                            <span class="inv-status-badge" :class="statusColor(transport.status)" x-text="statusLabel(transport.status)"></span>
                            <button x-show="transport.status === 'assigned'" type="button" @click="startLoading(transport)"
                                    class="inv-btn-accept">
                                Start Loading
                            </button>
                            <a :href="`/driver/transports/${transport.id}`" class="inv-btn-view">View</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="transports.length === 0" x-cloak class="inv-empty">
            <div class="inv-empty-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3>No transports found</h3>
            <p>No transport manifests match the current filters.</p>
        </div>

        {{-- Pagination --}}
        <div class="inv-pagination" x-show="transports.length > 0">
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
