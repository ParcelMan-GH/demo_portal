@extends('web.layouts.driver')

@section('title', 'My Deliveries')

@section('content')
<div x-data="driverDeliveriesListPage()">

    {{-- Hero --}}
    <div class="inv-hero">
        <div class="inv-hero-content">
            <div class="inv-hero-text">
                <div class="inv-hero-title-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    <div class="inv-hero-text-group">
                        <p>Rider Portal</p>
                        <h1>My Deliveries</h1>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <input x-model="filters.search" type="text" placeholder="Search run, recipient, address..."
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
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'out_for_delivery' }" @click="switchTab('out_for_delivery')">
                Out for Delivery <span class="inv-filter-count" x-text="tabCounts.out_for_delivery"></span>
            </button>
            <button type="button" class="inv-filter-tab" :class="{ active: activeTab === 'partially_delivered' }" @click="switchTab('partially_delivered')">
                Partial <span class="inv-filter-count" x-text="tabCounts.partially_delivered"></span>
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
        Loading deliveries...
    </div>

    {{-- Card grid --}}
    <div x-show="!loading" x-cloak>
        <div class="inv-grid">
            <template x-for="run in deliveries" :key="run.id">
                <div class="inv-card" :class="`inv-card-${statusColor(run.status)}`">
                    <div class="inv-card-inner">
                        {{-- Icon --}}
                        <div class="inv-icon-wrap" :class="statusColor(run.status)">
                            <template x-if="run.status === 'assigned'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </template>
                            <template x-if="run.status === 'out_for_delivery'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            </template>
                            <template x-if="run.status === 'partially_delivered'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="run.status === 'completed'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="run.status === 'cancelled'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="!['assigned','out_for_delivery','partially_delivered','completed','cancelled'].includes(run.status)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            </template>
                        </div>

                        {{-- Main info --}}
                        <div class="inv-main">
                            <a :href="`/driver/deliveries/${run.id}`" class="inv-number"
                               x-text="run.run_number || `Delivery Run #${run.id}`"></a>
                            <div class="inv-meta-item" x-show="run.warehouse?.name">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                <span x-text="run.warehouse?.name"></span>
                            </div>
                            <div class="inv-meta-item">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <span x-text="`${(run.stops || []).length} stop${(run.stops || []).length !== 1 ? 's' : ''}`"></span>
                                <span x-show="run.completed_stops_count !== undefined"> · <span x-text="run.completed_stops_count"></span> done</span>
                            </div>
                            <div class="inv-meta-item" x-show="run.assigned_at">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Assigned: <span x-text="formatDateTime(run.assigned_at)"></span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="inv-actions">
                            <span class="inv-status-badge" :class="statusColor(run.status)" x-text="statusLabel(run.status)"></span>
                            <a :href="`/driver/deliveries/${run.id}`" class="inv-btn-view">View</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="deliveries.length === 0" x-cloak class="inv-empty">
            <div class="inv-empty-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <h3>No deliveries found</h3>
            <p>No delivery runs match the current filters.</p>
        </div>

        {{-- Pagination --}}
        <div class="inv-pagination" x-show="deliveries.length > 0">
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
