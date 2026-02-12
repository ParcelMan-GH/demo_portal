@extends('web.layouts.portal')

@section('title', 'Vendor Home')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-6xl px-6 py-10" x-data="vendorHomePage()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Vendor Home</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('web.vendor.shipments.index') }}" class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/25">
                My Shipments
            </a>
            <a href="{{ route('web.vendor.invoices.index') }}" class="rounded-xl border border-sky-300/30 bg-sky-500/15 px-4 py-2 text-sm font-semibold text-sky-100 hover:bg-sky-500/25">
                My Invoices
            </a>
            <a href="{{ route('web.vendor.profile') }}" class="rounded-xl border border-orange-300/30 bg-orange-500/15 px-4 py-2 text-sm font-semibold text-orange-100 hover:bg-orange-500/25">
                Update Profile
            </a>
            <a href="{{ route('web.landing') }}" class="rounded-xl border border-slate-200/20 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
                Portal
            </a>
            <button type="button" @click="logout()" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-400">
                Logout
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl lg:col-span-2">
            <h2 class="text-lg font-bold text-white">Profile Overview</h2>

            <div x-show="loading" class="mt-4 text-sm text-slate-300">Loading profile...</div>
            <div x-show="!loading && error" x-cloak class="mt-4 rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100" x-text="error"></div>

            <dl x-show="!loading && profile" x-cloak class="mt-4 grid gap-3 text-sm text-slate-200 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Name</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.name || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Business</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.business_name || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Phone</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.phone || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Email</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.email || '-'"></dd>
                </div>
            </dl>
        </section>

        <section class="rounded-3xl border border-orange-300/25 bg-orange-500/10 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-orange-100">Session</h2>
            <p class="mt-3 text-sm text-orange-100">You are signed in as a vendor user.</p>
        </section>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
        <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-200">Quick Links</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-300">
            <li><a href="{{ route('web.vendor.shipments.index') }}" class="text-emerald-200 hover:text-white">Manage Shipments</a></li>
            <li><a href="{{ route('web.vendor.shipments.create') }}" class="text-emerald-200 hover:text-white">Create Shipment</a></li>
            <li><a href="{{ route('web.vendor.invoices.index') }}" class="text-sky-200 hover:text-white">Manage Invoices</a></li>
            <li><a href="{{ route('web.vendor.profile') }}" class="text-orange-200 hover:text-white">Profile & Account Settings</a></li>
            <li>Pickup tracking (next phase)</li>
        </ul>
    </section>
</main>
@endsection
