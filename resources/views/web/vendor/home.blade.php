@extends('web.layouts.vendor')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="vendorHomePage()">
    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
            <svg class="mx-auto h-8 w-8 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-3 text-sm text-slate-500">Loading dashboard...</p>
        </div>
    </div>

    <div x-show="!loading && error" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>

    <div x-show="!loading && profile" x-cloak class="space-y-6">
        {{-- Welcome --}}
        <div class="vendor-card overflow-hidden">
            <div class="bg-slate-800 px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-white">Welcome back, <span x-text="profile?.name || 'Vendor'"></span>!</h2>
                        <p class="mt-1 text-sm text-slate-300">Manage your shipments and invoices from here.</p>
                    </div>
                    <a href="{{ route('web.vendor.shipments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Shipment
                    </a>
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('web.vendor.shipments.index') }}" class="vendor-card p-4 transition hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Shipments</div>
                        <div class="text-lg font-bold text-slate-900">View All</div>
                    </div>
                </div>
            </a>
            <a href="{{ route('web.vendor.shipments.create') }}" class="vendor-card p-4 transition hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Create</div>
                        <div class="text-lg font-bold text-slate-900">New Shipment</div>
                    </div>
                </div>
            </a>
            <a href="{{ route('web.vendor.invoices.index') }}" class="vendor-card p-4 transition hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Invoices</div>
                        <div class="text-lg font-bold text-slate-900">View All</div>
                    </div>
                </div>
            </a>
            <button type="button" @click="logout()" class="vendor-card p-4 text-left transition hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Account</div>
                        <div class="text-lg font-bold text-slate-900">Logout</div>
                    </div>
                </div>
            </button>
        </div>

        {{-- Profile --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="vendor-card p-5 lg:col-span-2">
                <h3 class="border-b border-gray-100 pb-3 text-sm font-bold text-slate-900">Profile Information</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
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

            <div class="vendor-card p-5">
                <h3 class="border-b border-gray-100 pb-3 text-sm font-bold text-slate-900">Update Profile</h3>
                <div class="mt-3" x-show="profileAlert" x-cloak>
                    <div class="rounded-lg px-3 py-2 text-xs font-medium"
                         :class="{ 'bg-green-50 text-green-700': profileAlert?.type === 'success', 'bg-red-50 text-red-700': profileAlert?.type === 'error' }"
                         x-text="profileAlert?.message"></div>
                </div>
                <form class="mt-3 space-y-3" @submit.prevent="updateProfile()">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Name</label>
                        <input x-model="profileForm.name" type="text" class="vendor-input">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Business Name</label>
                        <input x-model="profileForm.business_name" type="text" class="vendor-input">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Email</label>
                        <input x-model="profileForm.email" type="email" class="vendor-input">
                    </div>
                    <button type="submit" :disabled="profileSaving"
                            class="w-full rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60">
                        <span x-show="!profileSaving">Save Changes</span>
                        <span x-show="profileSaving" x-cloak>Saving...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
