@extends('web.layouts.vendor')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div x-data="vendorProfilePage()">

    {{-- Loading --}}
    <div x-show="loading" class="dash-loading">
        <svg class="animate-spin" width="28" height="28" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Error --}}
    <div x-show="!loading && error" x-cloak class="dash-error" x-text="error"></div>

    {{-- Profile Content --}}
    <div x-show="!loading && profile" x-cloak>

        {{-- Profile Hero --}}
        <div class="profile-hero">
            <div class="profile-hero-inner">
                <div class="profile-hero-top">
                    <a href="{{ route('web.vendor.home') }}" class="profile-hero-back">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to Dashboard
                    </a>
                    <span class="profile-hero-badge">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Verified Vendor
                    </span>
                </div>
                <div class="profile-hero-identity">
                    <div class="profile-hero-avatar" x-text="initial"></div>
                    <div class="profile-hero-info">
                        <h1 class="profile-hero-name" x-text="profile?.name || 'Vendor'"></h1>
                        <p class="profile-hero-business" x-show="profile?.business_name" x-text="profile?.business_name"></p>
                        <div class="profile-hero-meta">
                            <span class="profile-hero-meta-item">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span x-text="profile?.phone || '-'"></span>
                            </span>
                            <span class="profile-hero-meta-item" x-show="profile?.email">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span x-text="profile?.email"></span>
                            </span>
                            <span class="profile-hero-meta-item">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Joined <span x-text="memberSince"></span></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert --}}
        <div x-show="alert" x-cloak x-transition class="profile-alert" :class="alert?.type">
            <svg x-show="alert?.type === 'success'" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="alert?.type === 'error'" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="alert?.message"></span>
        </div>

        {{-- Content Grid --}}
        <div class="profile-grid">

            {{-- Left Column: Profile Info --}}
            <div class="profile-col-main">

                {{-- Profile Details Card --}}
                <div class="profile-card no-hover">
                    <div class="profile-card-header">
                        <div class="profile-card-title">
                            <div class="dash-panel-icon dark">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            Profile Details
                        </div>
                        <button x-show="!editing" @click="startEditing()" class="profile-edit-btn">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Profile
                        </button>
                    </div>

                    {{-- View Mode --}}
                    <div x-show="!editing" class="profile-card-body">
                        <div class="profile-field">
                            <div class="profile-field-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="profile-field-content">
                                <span class="profile-field-label">Full Name</span>
                                <span class="profile-field-value" x-text="profile?.name || '-'"></span>
                            </div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div class="profile-field-content">
                                <span class="profile-field-label">Business Name</span>
                                <span class="profile-field-value" x-text="profile?.business_name || 'Not set'"></span>
                            </div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div class="profile-field-content">
                                <span class="profile-field-label">Phone Number</span>
                                <span class="profile-field-value" x-text="profile?.phone || '-'"></span>
                            </div>
                            <span class="profile-field-lock">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="profile-field-content">
                                <span class="profile-field-label">Email Address</span>
                                <span class="profile-field-value" x-text="profile?.email || 'Not set'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Mode --}}
                    <div x-show="editing" x-cloak class="profile-card-body">
                        <form @submit.prevent="saveProfile()" class="profile-form">
                            <div class="profile-form-group">
                                <label class="profile-form-label">Full Name <span class="profile-form-required">*</span></label>
                                <input x-model="form.name" type="text" maxlength="255" class="profile-form-input" placeholder="Your full name">
                            </div>
                            <div class="profile-form-group">
                                <label class="profile-form-label">Business Name</label>
                                <input x-model="form.business_name" type="text" maxlength="255" class="profile-form-input" placeholder="Your business name">
                            </div>
                            <div class="profile-form-group">
                                <label class="profile-form-label">Email Address</label>
                                <input x-model="form.email" type="email" maxlength="255" class="profile-form-input" placeholder="your@email.com">
                            </div>
                            <div class="profile-form-actions">
                                <button type="button" @click="cancelEditing()" class="profile-discard-btn">Cancel</button>
                                <button type="submit" :disabled="saving" class="profile-save-btn">
                                    <svg x-show="!saving" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="saving" x-cloak class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Right Column: Sidebar --}}
            <div class="profile-col-side">

                {{-- Quick Links Card --}}
                <div class="profile-card no-hover">
                    <div class="profile-card-header">
                        <div class="profile-card-title">
                            <div class="dash-panel-icon dark">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            Quick Links
                        </div>
                    </div>
                    <div class="profile-card-body" style="padding: 0.5rem;">
                        <a href="{{ route('web.vendor.home') }}" class="profile-quick-link">
                            <div class="profile-quick-link-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <span>Dashboard</span>
                            <svg class="profile-quick-link-arrow" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('web.vendor.shipments.create') }}" class="profile-quick-link">
                            <div class="profile-quick-link-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <span>New Shipment</span>
                            <svg class="profile-quick-link-arrow" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('web.vendor.shipments.index') }}" class="profile-quick-link">
                            <div class="profile-quick-link-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <span>All Shipments</span>
                            <svg class="profile-quick-link-arrow" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('web.vendor.invoices.index') }}" class="profile-quick-link">
                            <div class="profile-quick-link-icon">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </div>
                            <span>All Invoices</span>
                            <svg class="profile-quick-link-arrow" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Session Card --}}
                <div class="profile-card no-hover">
                    <div class="profile-card-header">
                        <div class="profile-card-title">
                            <div class="dash-panel-icon dark">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            Session
                        </div>
                    </div>
                    <div class="profile-card-body">
                        <div class="profile-session-info">
                            <div class="profile-session-status">
                                <span class="profile-session-dot"></span>
                                Active Session
                            </div>
                            <p class="profile-session-text">You are signed in as a vendor user. Your session is secured with token-based authentication.</p>
                        </div>
                        <button type="button" @click="logout()" class="profile-logout-btn">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign Out
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
