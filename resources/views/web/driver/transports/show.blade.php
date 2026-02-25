@extends('web.layouts.driver')

@section('title', 'Transport Manifest')

@section('content')
<div x-data="driverTransportShowPage()" data-manifest-id="{{ $manifestId }}">

    {{-- Toast stack --}}
    <div class="sh-toast-stack">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="sh-toast" :class="toast.type">
                <span x-text="toast.message"></span>
                <span class="sh-toast-close" @click="dismissToast(toast.id)">Close</span>
            </div>
        </template>
    </div>

    {{-- Confirm dialog --}}
    <div class="sh-confirm-overlay" x-show="confirmDialog.open" x-cloak>
        <div class="sh-confirm-box">
            <h3 x-text="confirmDialog.title || 'Confirm'"></h3>
            <p x-text="confirmDialog.message"></p>
            <div class="sh-confirm-btns">
                <button type="button" @click="confirmNo()" class="sh-confirm-cancel">Cancel</button>
                <button type="button" @click="confirmYes()" class="sh-confirm-ok">Confirm</button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="inv-loading" style="padding:4rem 1rem;">
        <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Loading transport...
    </div>

    <div x-show="!loading && transport" x-cloak>

        {{-- Hero --}}
        <div class="sh-hero" :class="heroClass">
            <div class="sh-hero-inner">
                <div class="sh-hero-top">
                    <div class="sh-hero-left">
                        <a href="{{ route('web.driver.transports.index') }}" class="sh-back">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            My Transports
                        </a>
                        <div class="sh-title-text">Transport Manifest</div>
                        <div class="sh-status-badge" x-text="statusLabel(transport?.status)"></div>
                        <div class="sh-number" x-text="transport?.manifest_number || `Transport #${transport?.id}`"></div>
                        <div class="sh-meta">
                            <div class="sh-meta-item" x-show="transport?.origin_warehouse?.name">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                <span x-text="transport?.origin_warehouse?.name"></span>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:12px;height:12px;color:#10b981;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                <span x-text="transport?.destination_warehouse?.name"></span>
                            </div>
                            <div class="sh-meta-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span x-text="`${(transport?.items || []).length} items`"></span>
                            </div>
                            <div class="sh-meta-item" x-show="transport?.assigned_at">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="formatDateTime(transport?.assigned_at)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status action section --}}
                <div class="sh-status-section">
                    <div class="sh-status-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="sh-status-content">
                        <div class="sh-status-title" x-text="heroMessage.title"></div>
                        <div class="sh-status-text" x-text="heroMessage.text"></div>
                    </div>
                    <div class="sh-status-action" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <button x-show="canStartLoading" type="button" @click="startLoading()" :disabled="actionLoading" class="sh-action-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Start Loading
                        </button>
                        <button x-show="canDepart" type="button" @click="depart()" :disabled="actionLoading" class="sh-action-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            Depart
                        </button>
                        <button x-show="canArrive" type="button" @click="arriveAtDestination()" :disabled="actionLoading" class="sh-action-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            Arrive at Destination
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress steps --}}
        <div class="sh-progress" x-show="transport?.status !== 'cancelled'">
            <div class="sh-progress-header">
                <span class="sh-progress-title">Transport Progress</span>
                <span class="sh-progress-status" x-text="statusLabel(transport?.status)"></span>
            </div>
            <div class="sh-steps">
                <template x-for="step in progressSteps" :key="step.key">
                    <div class="sh-step" :class="step.state">
                        <div class="sh-step-dot">
                            <template x-if="step.state === 'sh-step-done'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step.state !== 'sh-step-done'">
                                <span x-text="step.num"></span>
                            </template>
                        </div>
                        <div class="sh-step-text" x-text="step.label"></div>
                    </div>
                </template>
            </div>
            <div class="sh-steps-mobile">
                <template x-for="step in progressSteps" :key="step.key">
                    <div class="sh-step" :class="step.state">
                        <div class="sh-step-dot">
                            <template x-if="step.state === 'sh-step-done'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step.state !== 'sh-step-done'">
                                <span x-text="step.num"></span>
                            </template>
                        </div>
                        <div class="sh-step-text" x-text="step.label"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Main grid --}}
        <div class="sh-grid sh-grid-full mt-4">

            {{-- Items card --}}
            <div class="sh-card sh-items-card">
                <div class="sh-card-head">
                    <div class="sh-card-icon green">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h3>Manifest Items</h3>
                    <span class="sh-card-count">
                        <span x-text="(transport?.items || []).filter(i => i.status === 'loaded').length"></span>
                        / <span x-text="(transport?.items || []).length"></span> loaded
                    </span>
                </div>

                <div x-show="(transport?.items || []).length === 0" style="padding:2rem;text-align:center;color:#94a3b8;">
                    No items in this manifest.
                </div>

                <template x-for="item in (transport?.items || [])" :key="item.id">
                    <div class="sh-item">
                        <div class="sh-item-status-dot"
                             :class="item.status === 'loaded' ? 'green' : item.status === 'unloaded' ? 'amber' : 'gray'"
                             style="margin-top:0.4rem;flex-shrink:0;"></div>
                        <div class="sh-item-main">
                            <div class="sh-item-header">
                                <div class="sh-item-name" x-text="item.description || item.tracking_code"></div>
                                <span class="sh-item-qty" :class="item.status === 'loaded' ? 'confirmed' : 'expected'"
                                      x-text="item.status"></span>
                            </div>
                            <div class="sh-item-tracking" x-show="item.tracking_code" x-text="item.tracking_code"></div>
                        </div>
                    </div>
                </template>

                {{-- Scan form --}}
                <div class="sh-scan-form" x-show="canScanItems">
                    <input x-model="scanForm.tracking_code" type="text" placeholder="Scan or enter tracking code..."
                           @keyup.enter="scanItem()" class="sh-scan-input">
                    <button type="button" @click="scanItem()" :disabled="scanLoading" class="sh-scan-btn">
                        <span x-show="!scanLoading">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px;display:inline;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            Scan Item
                        </span>
                        <span x-show="scanLoading" x-cloak>Scanning...</span>
                    </button>
                </div>
            </div>

        </div>

        {{-- Sidebar info row --}}
        <div class="sh-grid mt-4" style="grid-template-columns:1fr 1fr 1fr;">

            {{-- Origin warehouse --}}
            <div class="sh-sidebar-card">
                <div class="sh-card-head">
                    <div class="sh-card-icon blue">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                    <h3>Origin</h3>
                </div>
                <div class="sh-card-body">
                    <div class="sh-info-row">
                        <span class="sh-info-label">Warehouse</span>
                        <span class="sh-info-value" x-text="transport?.origin_warehouse?.name || '-'"></span>
                    </div>
                    <div class="sh-info-row" x-show="transport?.origin_warehouse?.code">
                        <span class="sh-info-label">Code</span>
                        <span class="sh-info-value mono" x-text="transport?.origin_warehouse?.code"></span>
                    </div>
                </div>
            </div>

            {{-- Destination warehouse --}}
            <div class="sh-sidebar-card">
                <div class="sh-card-head">
                    <div class="sh-card-icon green">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                    <h3>Destination</h3>
                </div>
                <div class="sh-card-body">
                    <div class="sh-info-row">
                        <span class="sh-info-label">Warehouse</span>
                        <span class="sh-info-value" x-text="transport?.destination_warehouse?.name || '-'"></span>
                    </div>
                    <div class="sh-info-row" x-show="transport?.destination_warehouse?.code">
                        <span class="sh-info-label">Code</span>
                        <span class="sh-info-value mono" x-text="transport?.destination_warehouse?.code"></span>
                    </div>
                </div>
            </div>

            {{-- Assignment info --}}
            <div class="sh-sidebar-card">
                <div class="sh-card-head">
                    <div class="sh-card-icon slate">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3>Timeline</h3>
                </div>
                <div class="sh-card-body">
                    <div class="sh-info-row">
                        <span class="sh-info-label">Assigned</span>
                        <span class="sh-info-value" x-text="formatDateTime(transport?.assigned_at) || '-'"></span>
                    </div>
                    <div class="sh-info-row">
                        <span class="sh-info-label">Departed</span>
                        <span class="sh-info-value" x-text="formatDateTime(transport?.dispatched_at) || '-'"></span>
                    </div>
                    <div class="sh-info-row">
                        <span class="sh-info-label">Arrived</span>
                        <span class="sh-info-value" x-text="formatDateTime(transport?.arrived_at) || '-'"></span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
