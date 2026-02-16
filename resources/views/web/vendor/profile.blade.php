@extends('web.layouts.vendor')

@section('title', 'Profile Settings')
@section('page-title', 'Profile Settings')

@section('content')
<div x-data="vendorHomePage()">
    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <svg class="h-8 w-8 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
    </div>

    <div x-show="!loading && error" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>

    <div x-show="!loading && profile" x-cloak class="space-y-6">
        {{-- Current Profile --}}
        <div class="vendor-card p-5">
            <h3 class="border-b border-gray-100 pb-3 text-sm font-bold text-slate-900">Current Profile</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Full Name</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="profile?.name || '-'"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Business Name</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="profile?.business_name || '-'"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Phone</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="profile?.phone || '-'"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Email</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="profile?.email || '-'"></dd>
                </div>
            </dl>
        </div>

        {{-- Update Profile --}}
        <div class="vendor-card p-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Update Profile</h3>
                <span class="rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-700">Vendor Account</span>
            </div>

            <p class="mt-3 text-xs text-slate-400">Phone number is managed through the verification flow and cannot be changed here.</p>

            <div class="mt-3" x-show="profileAlert" x-cloak>
                <div class="rounded-lg px-3 py-2 text-xs font-medium"
                     :class="{ 'bg-green-50 text-green-700': profileAlert?.type === 'success', 'bg-red-50 text-red-700': profileAlert?.type === 'error' }"
                     x-text="profileAlert?.message"></div>
            </div>

            <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="updateProfile()">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Name</label>
                    <input x-model="profileForm.name" type="text" maxlength="255" class="vendor-input" placeholder="Your full name">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Business Name</label>
                    <input x-model="profileForm.business_name" type="text" maxlength="255" class="vendor-input" placeholder="Your business name">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label>
                    <input x-model="profileForm.email" type="email" maxlength="255" class="vendor-input" placeholder="Email address">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" :disabled="profileSaving"
                            class="rounded-lg bg-slate-800 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60">
                        <span x-show="!profileSaving">Save Changes</span>
                        <span x-show="profileSaving" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Logout --}}
        <div class="vendor-card p-5">
            <h3 class="border-b border-gray-100 pb-3 text-sm font-bold text-slate-900">Session</h3>
            <p class="mt-3 text-xs text-slate-500">You are signed in as a vendor user. Click below to sign out.</p>
            <button type="button" @click="logout()" class="mt-3 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                Logout
            </button>
        </div>
    </div>
</div>
@endsection
