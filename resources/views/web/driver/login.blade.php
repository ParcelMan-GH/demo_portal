@extends('web.layouts.portal')

@section('title', 'Driver Login')

@section('content')
<main class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-6 py-10" x-data="driverAuthPage()">
    <div class="grid w-full gap-8 lg:grid-cols-[1fr_0.9fr]">
        <section class="rounded-3xl border border-sky-200/20 bg-slate-900/70 p-8 shadow-2xl shadow-slate-950/50 backdrop-blur-xl">
            <a href="{{ route('web.landing') }}" class="text-sm font-medium text-sky-200 hover:text-white">&larr; Back to portal</a>
            <h1 class="mt-5 text-3xl font-extrabold text-white">Driver Login</h1>
            <p class="mt-3 text-slate-300">Use your driver credentials to sign in.</p>

            <div class="mt-5" x-cloak x-show="alert" role="alert">
                <div class="rounded-xl border px-4 py-3 text-sm"
                     :class="{
                        'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': alert?.type === 'success',
                        'border-rose-300/30 bg-rose-500/10 text-rose-100': alert?.type === 'error'
                    }">
                    <span x-text="alert?.message"></span>
                </div>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="login()">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Email</label>
                    <input x-model="email" type="email" autocomplete="username"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-100">Password</label>
                    <div class="relative">
                        <input x-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 pr-20 text-sm text-white outline-none transition focus:border-sky-300/70 focus:ring-2 focus:ring-sky-400/35">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-3 py-1.5 text-xs font-semibold text-sky-200 hover:bg-sky-400/15">
                            <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>

                <button type="submit" :disabled="loading"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!loading">Login</span>
                    <span x-show="loading" x-cloak>Signing in...</span>
                </button>
            </form>
        </section>

        <section class="rounded-3xl border border-sky-200/20 bg-sky-500/10 p-8 backdrop-blur-xl">
            <h2 class="text-xl font-bold text-white">Secure sign-in</h2>
            <p class="mt-4 text-sm text-sky-100">
                Access your pickup tasks and status updates through your assigned driver account.
            </p>
            <ul class="mt-5 space-y-3 text-sm text-sky-100">
                <li>Use your assigned work email address.</li>
                <li>Enter your account password carefully.</li>
                <li>Contact admin if your account is deactivated.</li>
            </ul>
        </section>
    </div>
</main>
@endsection
