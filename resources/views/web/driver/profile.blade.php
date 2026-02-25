@extends('web.layouts.driver')

@section('title', 'My Profile')

@section('content')
<div class="profile-page" x-data="driverProfilePage()">

    {{-- Alert --}}
    <div x-show="alert" x-cloak class="profile-alert" :class="alert?.type" x-text="alert?.message"></div>

    {{-- Hero --}}
    <div class="profile-hero">
        <div class="profile-hero-inner">
            <div class="profile-hero-top">
                <div class="profile-avatar" x-text="profile ? (profile.name || 'D').charAt(0).toUpperCase() : 'D'"></div>
                <div class="profile-hero-info">
                    <div class="profile-hero-name" x-text="profile?.name || 'Driver'"></div>
                    <div class="profile-hero-meta">
                        <div class="profile-hero-meta-item" x-show="profile?.email">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span x-text="profile?.email"></span>
                        </div>
                        <div class="profile-hero-meta-item" x-show="profile?.phone">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span x-text="profile?.phone"></span>
                        </div>
                        <div class="profile-hero-meta-item" x-show="profile?.vehicle_type">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                            <span x-text="profile?.vehicle_type"></span>
                        </div>
                        <div class="profile-hero-meta-item" x-show="profile?.vehicle_number">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span x-text="profile?.vehicle_number"></span>
                        </div>
                    </div>
                    <div class="profile-hero-status" :class="profile?.status === 'active' ? 'active' : 'inactive'">
                        <span class="profile-hero-status-dot"></span>
                        <span x-text="profile?.status || 'Unknown'"></span>
                    </div>
                </div>
            </div>
            {{-- Quick links --}}
            <div class="profile-hero-links">
                <a href="{{ route('web.driver.home') }}" class="profile-hero-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('web.driver.pickups.index') }}" class="profile-hero-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    My Pickups
                </a>
                <a href="{{ route('web.driver.transports.index') }}" class="profile-hero-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1"/></svg>
                    My Transports
                </a>
                <a href="{{ route('web.driver.deliveries.index') }}" class="profile-hero-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    My Deliveries
                </a>
            </div>
        </div>
    </div>

    {{-- Profile Details Section --}}
    <div class="profile-section">
        <div class="profile-section-head">
            <div class="profile-section-icon emerald">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <h2 class="profile-section-title">Profile Details</h2>
            </div>
            <button type="button" x-show="!editMode" @click="startEdit()"
                    style="margin-left:auto;padding:0.4rem 0.9rem;border-radius:10px;font-size:0.78rem;font-weight:600;color:#059669;background:#d1fae5;border:1px solid #a7f3d0;cursor:pointer;">
                Edit
            </button>
        </div>
        <div class="profile-section-body">

            {{-- View mode --}}
            <div x-show="!editMode" class="profile-form-grid">
                <div class="profile-form-field">
                    <div class="profile-form-label">Name</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.name || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">Email</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.email || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">Phone</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.phone || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">Status</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.status || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">Vehicle Type</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.vehicle_type || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">Vehicle Number</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.vehicle_number || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">License Number</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.license_number || '-'"></div>
                </div>
                <div class="profile-form-field">
                    <div class="profile-form-label">Base Location</div>
                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;padding:0.5rem 0;" x-text="profile?.base_location || '-'"></div>
                </div>
            </div>

            {{-- Edit mode --}}
            <form x-show="editMode" x-cloak @submit.prevent="saveProfile()" class="profile-form-grid">
                <div class="profile-form-field">
                    <label class="profile-form-label">Name</label>
                    <input x-model="profileForm.name" type="text" maxlength="255"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                           onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                </div>
                <div class="profile-form-field">
                    <label class="profile-form-label">Email <span style="color:#94a3b8;font-weight:400;">(locked)</span></label>
                    <input :value="profile?.email" type="email" disabled
                           style="width:100%;border:1px solid #f1f5f9;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#94a3b8;background:#f8fafc;cursor:not-allowed;box-sizing:border-box;">
                </div>
                <div class="profile-form-field">
                    <label class="profile-form-label">Phone</label>
                    <input x-model="profileForm.phone" type="tel" maxlength="20"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                           onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                </div>
                <div class="profile-form-field">
                    <label class="profile-form-label">Vehicle Type</label>
                    <select x-model="profileForm.vehicle_type"
                            style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;background:#fff;outline:none;box-sizing:border-box;">
                        <option value="">Select type</option>
                        <template x-for="type in vehicleTypeOptions" :key="type">
                            <option :value="type" x-text="type"></option>
                        </template>
                    </select>
                </div>
                <div class="profile-form-field">
                    <label class="profile-form-label">Vehicle Number</label>
                    <input x-model="profileForm.vehicle_number" type="text" maxlength="50"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                           onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                </div>
                <div class="profile-form-field">
                    <label class="profile-form-label">License Number</label>
                    <input x-model="profileForm.license_number" type="text" maxlength="50"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                           onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                </div>
                <div class="profile-form-field" style="grid-column:1/-1;">
                    <label class="profile-form-label">Base Location</label>
                    <input x-model="profileForm.base_location" type="text" maxlength="255"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                           onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                </div>
                <div class="profile-form-actions" style="grid-column:1/-1;">
                    <button type="submit" :disabled="profileSaving" class="profile-save-btn">
                        <span x-show="!profileSaving">Save Profile</span>
                        <span x-show="profileSaving" x-cloak>Saving...</span>
                    </button>
                    <button type="button" @click="cancelEdit()" class="profile-discard-btn">Discard</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change Password Section --}}
    <div class="profile-section">
        <div class="profile-section-head">
            <div class="profile-section-icon blue">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <h2 class="profile-section-title">Change Password</h2>
            </div>
        </div>
        <div class="profile-section-body">

            <div x-show="passwordAlert" x-cloak class="profile-alert" :class="passwordAlert?.type" x-text="passwordAlert?.message" style="margin-bottom:1rem;"></div>

            <form @submit.prevent="changePassword()" class="profile-form-grid full">
                <div class="profile-form-field" x-data="{ show: false }">
                    <label class="profile-form-label">Current Password</label>
                    <div style="position:relative;">
                        <input x-model="passwordForm.current_password" :type="show ? 'text' : 'password'" autocomplete="current-password"
                               style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 3.5rem 0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                               onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                        <button type="button" @click="show = !show"
                                style="position:absolute;right:0.5rem;top:50%;transform:translateY(-50%);padding:0.25rem 0.6rem;border-radius:6px;font-size:0.72rem;font-weight:600;color:#64748b;background:none;border:none;cursor:pointer;"
                                x-text="show ? 'Hide' : 'Show'"></button>
                    </div>
                </div>
                <div class="profile-form-field" x-data="{ show: false }">
                    <label class="profile-form-label">New Password</label>
                    <div style="position:relative;">
                        <input x-model="passwordForm.new_password" :type="show ? 'text' : 'password'" autocomplete="new-password"
                               style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 3.5rem 0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                               onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                        <button type="button" @click="show = !show"
                                style="position:absolute;right:0.5rem;top:50%;transform:translateY(-50%);padding:0.25rem 0.6rem;border-radius:6px;font-size:0.72rem;font-weight:600;color:#64748b;background:none;border:none;cursor:pointer;"
                                x-text="show ? 'Hide' : 'Show'"></button>
                    </div>
                </div>
                <div class="profile-form-field" x-data="{ show: false }">
                    <label class="profile-form-label">Confirm New Password</label>
                    <div style="position:relative;">
                        <input x-model="passwordForm.confirm_password" :type="show ? 'text' : 'password'" autocomplete="new-password"
                               style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:0.55rem 3.5rem 0.55rem 0.875rem;font-size:0.875rem;color:#1e293b;outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                               onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                        <button type="button" @click="show = !show"
                                style="position:absolute;right:0.5rem;top:50%;transform:translateY(-50%);padding:0.25rem 0.6rem;border-radius:6px;font-size:0.72rem;font-weight:600;color:#64748b;background:none;border:none;cursor:pointer;"
                                x-text="show ? 'Hide' : 'Show'"></button>
                    </div>
                </div>
                <div class="profile-form-actions">
                    <button type="submit" :disabled="passwordSaving" class="profile-save-btn">
                        <span x-show="!passwordSaving">Change Password</span>
                        <span x-show="passwordSaving" x-cloak>Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
