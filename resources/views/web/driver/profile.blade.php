@extends('web.layouts.portal')

@section('title', 'Driver Profile')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-6xl px-6 py-10" x-data="driverHomePage()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-200">Driver Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Profile & Security</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('web.driver.pickups.index') }}" class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/25">
                My Pickups
            </a>
            <a href="{{ route('web.driver.home') }}" class="rounded-xl border border-sky-300/30 bg-sky-500/15 px-4 py-2 text-sm font-semibold text-sky-100 hover:bg-sky-500/25">
                Back to Home
            </a>
            <button type="button" @click="logout()" class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-400">
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
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Email</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.email || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Phone</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.phone || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Status</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.status || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Vehicle Type</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.vehicle_type || '-'"></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.12em] text-slate-400">Vehicle Number</dt>
                    <dd class="mt-1 font-semibold text-white" x-text="profile?.vehicle_number || '-'"></dd>
                </div>
            </dl>
        </section>

        <section class="rounded-3xl border border-sky-300/25 bg-sky-500/10 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-sky-100">Account</h2>
            <p class="mt-3 text-sm text-sky-100">Manage your profile details and password from this page.</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-white">Update Profile</h2>
                <span class="rounded-full bg-slate-700 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-200">Driver</span>
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

            <form class="mt-5 grid gap-4" @submit.prevent="updateProfile()">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Name</label>
                    <input x-model="profileForm.name" type="text" maxlength="255"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Phone</label>
                    <input x-model="profileForm.phone" type="tel" maxlength="20"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Vehicle Type</label>
                    <select x-model="profileForm.vehicle_type"
                            class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                        <option value="" class="text-slate-900">Select vehicle type</option>
                        <template x-for="type in vehicleTypeOptions" :key="type">
                            <option :value="type" class="text-slate-900" x-text="type"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Vehicle Number</label>
                    <input x-model="profileForm.vehicle_number" type="text" maxlength="50"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">License Number</label>
                    <input x-model="profileForm.license_number" type="text" maxlength="50"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Base Location</label>
                    <input x-model="profileForm.base_location" type="text" maxlength="255"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                </div>
                <div>
                    <button type="submit" :disabled="profileSaving || loading"
                            class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!profileSaving">Save Profile</span>
                        <span x-show="profileSaving" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
            <h2 class="text-lg font-bold text-white">Change Password</h2>
            <p class="mt-2 text-sm text-slate-300">Update your password and keep your account secure.</p>

            <div class="mt-4" x-show="passwordAlert" x-cloak>
                <div class="rounded-xl border px-4 py-3 text-sm"
                     :class="{
                        'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': passwordAlert?.type === 'success',
                        'border-rose-300/30 bg-rose-500/10 text-rose-100': passwordAlert?.type === 'error'
                     }">
                    <span x-text="passwordAlert?.message"></span>
                </div>
            </div>

            <form class="mt-5 grid gap-4" @submit.prevent="changePassword()">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Current Password</label>
                    <div class="relative">
                        <input x-model="passwordForm.current_password" :type="showCurrentPassword ? 'text' : 'password'" autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 pr-20 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                        <button type="button" @click="showCurrentPassword = !showCurrentPassword"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-3 py-1.5 text-xs font-semibold text-sky-200 hover:bg-sky-400/15">
                            <span x-text="showCurrentPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">New Password</label>
                    <div class="relative">
                        <input x-model="passwordForm.new_password" :type="showNewPassword ? 'text' : 'password'" autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 pr-20 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                        <button type="button" @click="showNewPassword = !showNewPassword"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-3 py-1.5 text-xs font-semibold text-sky-200 hover:bg-sky-400/15">
                            <span x-text="showNewPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Confirm New Password</label>
                    <div class="relative">
                        <input x-model="passwordForm.confirm_password" :type="showConfirmPassword ? 'text' : 'password'" autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 pr-20 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-3 py-1.5 text-xs font-semibold text-sky-200 hover:bg-sky-400/15">
                            <span x-text="showConfirmPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>
                <div>
                    <button type="submit" :disabled="passwordSaving || loading"
                            class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!passwordSaving">Change Password</span>
                        <span x-show="passwordSaving" x-cloak>Updating...</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>
@endsection
