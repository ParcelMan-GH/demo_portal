@extends('web.layouts.portal')

@section('title', 'Vendor Login')

@section('content')
<main class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-6 py-10" x-data="vendorAuthPage()">
    <div class="grid w-full gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <section class="rounded-3xl border border-orange-200/20 bg-slate-900/70 p-8 shadow-2xl shadow-slate-950/50 backdrop-blur-xl">
            <a href="{{ route('web.landing') }}" class="text-sm font-medium text-orange-200 hover:text-white">&larr; Back to portal</a>
            <h1 class="mt-5 text-3xl font-extrabold text-white">Vendor Authentication</h1>
            <p class="mt-3 max-w-lg text-slate-300">
                Flow: send OTP, verify phone, then register only when no account exists.
            </p>

            <div class="mt-6 rounded-2xl border border-slate-200/15 bg-slate-900/40 p-4">
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em]">
                    <span :class="step === 'phone' ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-200'" class="rounded-full px-3 py-1">Phone</span>
                    <span class="text-slate-500">-></span>
                    <span :class="step === 'otp' ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-200'" class="rounded-full px-3 py-1">OTP</span>
                    <span class="text-slate-500">-></span>
                    <span :class="step === 'register' ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-200'" class="rounded-full px-3 py-1">Register</span>
                </div>
            </div>

            <div class="mt-5" x-cloak x-show="alert" role="alert">
                <div class="rounded-xl border px-4 py-3 text-sm"
                     :class="{
                        'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': alert?.type === 'success',
                        'border-sky-300/30 bg-sky-400/10 text-sky-100': alert?.type === 'info',
                        'border-rose-300/30 bg-rose-500/10 text-rose-100': alert?.type === 'error'
                    }">
                    <span x-text="alert?.message"></span>
                </div>
            </div>

            <form class="mt-6 space-y-4" x-show="step === 'phone'" @submit.prevent="sendOtp()">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Phone Number</label>
                    <input x-model="phone" @input="onPhoneInput($event)" type="tel" inputmode="tel" autocomplete="tel" maxlength="13" spellcheck="false" placeholder="e.g. 0241234567 or +233241234567"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <p class="mt-2 text-xs text-slate-400">Only Ghana phone format is accepted.</p>
                </div>
                <button type="submit" :disabled="loading"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!loading">Send OTP</span>
                    <span x-show="loading" x-cloak>Processing...</span>
                </button>
            </form>

            <form class="mt-6 space-y-4" x-show="step === 'otp'" @submit.prevent="verifyOtp()">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">OTP Code (6 digits)</label>
                    <input x-model="otp" @input="onOtpInput($event)" type="text" maxlength="6" inputmode="numeric" autocomplete="one-time-code" spellcheck="false" placeholder="Enter 6-digit OTP"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <p class="mt-2 text-xs text-slate-400">Code expires in <span x-text="otpExpiresIn"></span> seconds.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" :disabled="loading"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!loading">Verify Phone</span>
                        <span x-show="loading" x-cloak>Verifying...</span>
                    </button>
                    <button type="button" @click="resendOtp()" :disabled="!canResendOtp"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300/25 bg-slate-800/70 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="formatCooldown()"></span>
                    </button>
                </div>
                <button type="button" @click="backToPhone()" class="text-sm font-medium text-slate-300 hover:text-white">
                    Change phone number
                </button>
            </form>

            <form class="mt-6 space-y-4" x-show="step === 'register'" @submit.prevent="registerVendor()">
                <div class="rounded-xl border border-slate-200/15 bg-slate-900/60 p-3 text-xs text-slate-300">
                    Verified phone: <span class="font-semibold text-white" x-text="verifiedPhone"></span>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Full Name</label>
                    <input x-model="registerForm.name" type="text"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Business Name (optional)</label>
                    <input x-model="registerForm.business_name" type="text"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Email (optional)</label>
                    <input x-model="registerForm.email" type="email"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                </div>
                <button type="submit" :disabled="loading"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!loading">Complete Registration</span>
                    <span x-show="loading" x-cloak>Submitting...</span>
                </button>
            </form>
        </section>

        <section class="rounded-3xl border border-orange-200/20 bg-orange-500/10 p-8 backdrop-blur-xl">
            <h2 class="text-xl font-bold text-white">Secure account access</h2>
            <p class="mt-4 text-sm text-orange-100">
                Your number is verified with a one-time code before account access is granted.
            </p>
            <ul class="mt-5 space-y-3 text-sm text-orange-100">
                <li>Use a valid Ghana phone number.</li>
                <li>Enter the latest OTP sent to your phone.</li>
                <li>Complete registration only when prompted.</li>
            </ul>
        </section>
    </div>
</main>
@endsection
