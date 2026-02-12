@extends('web.layouts.portal')

@section('title', 'Parcelman')

@section('content')
<main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-6 py-12">
    <div class="mb-8">
        <span class="inline-flex items-center rounded-full border border-orange-300/40 bg-orange-400/10 px-4 py-1 text-xs font-semibold tracking-[0.14em] text-orange-200">
            PARCELMAN AUTH PORTAL
        </span>
    </div>

    <div class="grid gap-8 md:grid-cols-2">
        <section class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-8 shadow-2xl shadow-slate-950/50 backdrop-blur-xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                One portal for Vendor and Driver access
            </h1>
            <p class="mt-4 max-w-md text-base text-slate-300">
                Login using the same API flows configured in API Tester. Choose your role to continue.
            </p>

            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('web.vendor.login') }}" class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400">
                    Vendor Login
                </a>
                <a href="{{ route('web.driver.login') }}" class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">
                    Driver Login
                </a>
            </div>
        </section>

        <section class="grid gap-5">
            <a href="{{ route('web.vendor.login') }}" class="group rounded-3xl border border-orange-300/25 bg-orange-500/10 p-6 backdrop-blur-xl transition hover:border-orange-200/60 hover:bg-orange-500/15">
                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</div>
                <h2 class="mt-2 text-2xl font-bold text-white">OTP + Registration</h2>
                <p class="mt-2 text-slate-200">Send OTP, verify phone, and complete registration only when required.</p>
                <div class="mt-4 text-sm font-semibold text-orange-100 group-hover:text-white">Continue as Vendor</div>
            </a>

            <a href="{{ route('web.driver.login') }}" class="group rounded-3xl border border-sky-300/25 bg-sky-500/10 p-6 backdrop-blur-xl transition hover:border-sky-200/60 hover:bg-sky-500/15">
                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-100">Driver Portal</div>
                <h2 class="mt-2 text-2xl font-bold text-white">Email + Password</h2>
                <p class="mt-2 text-slate-200">Sign in with driver credentials and access pickup operations.</p>
                <div class="mt-4 text-sm font-semibold text-sky-100 group-hover:text-white">Continue as Driver</div>
            </a>
        </section>
    </div>
</main>
@endsection
