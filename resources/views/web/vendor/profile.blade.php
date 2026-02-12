@extends('web.layouts.portal')

@section('title', 'Vendor Profile')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-6xl px-6 py-10" x-data="vendorHomePage()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Profile Settings</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('web.vendor.shipments.index') }}" class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/25">
                My Shipments
            </a>
            <a href="{{ route('web.vendor.invoices.index') }}" class="rounded-xl border border-sky-300/30 bg-sky-500/15 px-4 py-2 text-sm font-semibold text-sky-100 hover:bg-sky-500/25">
                My Invoices
            </a>
            <a href="{{ route('web.vendor.home') }}" class="rounded-xl border border-orange-300/30 bg-orange-500/15 px-4 py-2 text-sm font-semibold text-orange-100 hover:bg-orange-500/25">
                Back to Home
            </a>
            <button type="button" @click="logout()" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-400">
                Logout
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl lg:col-span-2">
            <h2 class="text-lg font-bold text-white">Current Profile</h2>

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
            <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-orange-100">Account</h2>
            <p class="mt-3 text-sm text-orange-100">Update your profile details here. Phone number is managed through verification flow.</p>
        </section>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-white">Update Profile</h2>
            <span class="rounded-full bg-slate-700 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-200">Vendor</span>
        </div>

        <div class="mt-4" x-show="profileAlert" x-cloak>
            <div class="rounded-xl border px-4 py-3 text-sm"
                 :class="{
                    'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': profileAlert?.type === 'success',
                    'border-rose-300/30 bg-rose-500/10 text-rose-100': profileAlert?.type === 'error'
                 }">
                <span x-text="profileAlert?.message"></span>
            </div>
        </div>

        <form class="mt-5 grid gap-4 md:grid-cols-2" @submit.prevent="updateProfile()">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-100">Name</label>
                <input x-model="profileForm.name" type="text" maxlength="255"
                       class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-100">Business Name</label>
                <input x-model="profileForm.business_name" type="text" maxlength="255"
                       class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-100">Email</label>
                <input x-model="profileForm.email" type="email" maxlength="255"
                       class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
            </div>
            <div class="md:col-span-2">
                <button type="submit" :disabled="profileSaving || loading"
                        class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!profileSaving">Save Changes</span>
                    <span x-show="profileSaving" x-cloak>Saving...</span>
                </button>
            </div>
        </form>
    </section>
</main>
@endsection
