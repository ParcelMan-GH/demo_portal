@extends('web.layouts.vendor')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="profile-page" x-data="vendorProfilePage()">

    {{-- Toast --}}
    <div x-show="alert" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="profile-alert" :class="alert?.type">
        <span x-text="alert?.message"></span>
    </div>

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
                </div>
                <div class="profile-hero-body">
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

                    {{-- Quick Links in Hero --}}
                    <div class="profile-hero-links">
                        <a href="{{ route('web.vendor.home') }}" class="profile-hero-link-card">
                            <div class="profile-hero-link-icon blue">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <span class="profile-hero-link-label">Dashboard</span>
                        </a>
                        <a href="{{ route('web.vendor.shipments.create') }}" class="profile-hero-link-card">
                            <div class="profile-hero-link-icon orange">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <span class="profile-hero-link-label">New Shipment</span>
                        </a>
                        <a href="{{ route('web.vendor.shipments.index') }}" class="profile-hero-link-card">
                            <div class="profile-hero-link-icon green">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <span class="profile-hero-link-label">Shipments</span>
                        </a>
                        <a href="{{ route('web.vendor.invoices.index') }}" class="profile-hero-link-card">
                            <div class="profile-hero-link-icon amber">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </div>
                            <span class="profile-hero-link-label">Invoices</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Profile Details --}}
        <div class="profile-content">

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
                    <div class="profile-info-grid">

                        {{-- Full Name --}}
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;background:#f8fafc;border:1px solid #e9f0f8;border-radius:14px;padding:1rem 1.125rem;">
                            <div style="flex-shrink:0;width:34px;height:34px;border-radius:10px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;">
                                <svg width="16" height="16" fill="none" stroke="#0284c7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:0.68rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem;">Full Name</div>
                                <div style="font-size:0.9rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="profile?.name || '—'"></div>
                            </div>
                        </div>

                        {{-- Business Name --}}
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;background:#f8fafc;border:1px solid #e9f0f8;border-radius:14px;padding:1rem 1.125rem;">
                            <div style="flex-shrink:0;width:34px;height:34px;border-radius:10px;background:#fdf4ff;display:flex;align-items:center;justify-content:center;">
                                <svg width="16" height="16" fill="none" stroke="#9333ea" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:0.68rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem;">Business Name</div>
                                <div style="font-size:0.9rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="profile?.business_name || '—'"></div>
                            </div>
                        </div>

                        {{-- Phone Number --}}
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;background:#f8fafc;border:1px solid #e9f0f8;border-radius:14px;padding:1rem 1.125rem;">
                            <div style="flex-shrink:0;width:34px;height:34px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                                <svg width="16" height="16" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:0.68rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem;">Phone Number</div>
                                <div style="font-size:0.9rem;font-weight:700;color:#0f172a;" x-text="profile?.phone || '—'"></div>
                            </div>
                        </div>

                        {{-- Email Address --}}
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;background:#f8fafc;border:1px solid #e9f0f8;border-radius:14px;padding:1rem 1.125rem;">
                            <div style="flex-shrink:0;width:34px;height:34px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
                                <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:0.68rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem;">Email Address</div>
                                <div style="font-size:0.9rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="profile?.email || '—'"></div>
                            </div>
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
                            <button type="button" @click="cancelEditing()" class="profile-discard-btn">Close</button>
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

    </div>
</div>
@endsection
