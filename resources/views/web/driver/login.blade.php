@extends('web.layouts.portal')

@section('title', 'Rider Login')

@section('content')
<main class="flex min-h-screen items-center justify-center px-4 py-12" x-data="driverAuthPage()" style="background:#f1f5f9;">
    <div class="w-full max-w-4xl">

        {{-- Login Card --}}
        <div class="overflow-hidden rounded-3xl shadow-2xl shadow-slate-300/80 grid grid-cols-1 lg:grid-cols-2">

            {{-- ====== Left: Brand Panel ====== --}}
            <div class="relative hidden lg:flex flex-col justify-between p-10"
                 style="background:linear-gradient(150deg,#7c2d12 0%,#9a3412 55%,#c2410c 100%);">

                {{-- Dot-grid texture --}}
                <div style="position:absolute;inset:0;opacity:0.06;background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:22px 22px;pointer-events:none;"></div>
                {{-- Decorative circles --}}
                <div style="position:absolute;top:-5rem;right:-5rem;width:18rem;height:18rem;border-radius:50%;background:rgba(255,255,255,0.05);pointer-events:none;"></div>
                <div style="position:absolute;bottom:-6rem;left:-5rem;width:22rem;height:22rem;border-radius:50%;background:rgba(255,255,255,0.04);pointer-events:none;"></div>
                <div style="position:absolute;top:40%;left:50%;width:10rem;height:10rem;border-radius:50%;background:rgba(251,146,60,0.1);transform:translate(-50%,-50%);pointer-events:none;"></div>

                {{-- Top: Logo + Headline --}}
                <div class="relative z-10">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('logo-2.png') }}" alt="Parcelman" class="h-20 w-auto flex-shrink-0" style="filter:brightness(0) invert(1);">
                        <h2 class="min-w-0 text-3xl font-bold text-white leading-snug whitespace-nowrap">
                            Your deliveries,<br>
                            <span style="color:#fdba74;">managed smartly.</span>
                        </h2>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed" style="color:rgba(254,215,170,0.75);">
                        Sign in to access your pickup tasks, transport runs, and delivery assignments in real time.
                    </p>
                </div>

                {{-- Bottom: Feature points --}}
                <div class="relative z-10 space-y-4">
                    <div class="flex items-center gap-4">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="#fdba74" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Pickup Assignments</p>
                            <p class="text-xs" style="color:rgba(254,215,170,0.65);">View & manage your daily pickups</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="#fdba74" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Transport Runs</p>
                            <p class="text-xs" style="color:rgba(254,215,170,0.65);">Track your transport manifests</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="#fdba74" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Last-Mile Delivery</p>
                            <p class="text-xs" style="color:rgba(254,215,170,0.65);">Complete deliveries with real-time updates</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Right: Login Form ====== --}}
            <div class="flex flex-col justify-center p-8 sm:p-10" style="background:#ffffff;">

                {{-- Mobile logo --}}
                <div class="mb-8 flex justify-center lg:hidden">
                    <img src="{{ asset('logo-2.png') }}" alt="Parcelman" class="h-12 w-auto">
                </div>

                {{-- Back link --}}
                <a href="{{ route('web.landing') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-medium transition-colors mb-8"
                   style="color:#94a3b8;"
                   onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to portal
                </a>

                {{-- Icon + Heading --}}
                <div class="flex items-center gap-4 mb-8">
                    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:54px;height:54px;border-radius:16px;background:linear-gradient(135deg,#ea580c,#f97316);">
                        {{-- Delivery van --}}
                        <svg width="28" height="28" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <rect x="1" y="7" width="13" height="11" rx="1"/>
                            <path d="M14 9h4l3 4v5h-7V9z"/>
                            <circle cx="5.5" cy="18.5" r="1.5"/>
                            <circle cx="18.5" cy="18.5" r="1.5"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold" style="color:#0f172a;">Welcome back</h1>
                        <p class="mt-0.5 text-sm" style="color:#64748b;">Sign in to your rider account</p>
                    </div>
                </div>

                {{-- Alert --}}
                <div class="mb-5" x-cloak x-show="alert" role="alert">
                    <div class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm"
                         :class="{
                            'border-emerald-200 bg-emerald-50 text-emerald-700': alert?.type === 'success',
                            'border-rose-200 bg-rose-50 text-rose-700': alert?.type === 'error'
                        }">
                        <svg x-show="alert?.type === 'success'" class="flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg x-show="alert?.type === 'error'" class="flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="alert?.message"></span>
                    </div>
                </div>

                {{-- Form --}}
                <form class="space-y-5 w-full" @submit.prevent="login()">

                    {{-- Email --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" style="color:#374151;">Email Address</label>
                        <input x-model="email" type="email" autocomplete="username" placeholder="rider@example.com"
                               style="width:100%;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;padding:0.8125rem 1rem;font-size:0.875rem;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(249,115,22,0.6)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" style="color:#374151;">Password</label>
                        <div style="position:relative;">
                            <input x-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password"
                                   style="width:100%;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;padding:0.8125rem 4.5rem 0.8125rem 1rem;font-size:0.875rem;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.6)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.12)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            <button type="button" @click="showPassword = !showPassword"
                                    style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);padding:0.25rem 0.5rem;border-radius:8px;font-size:0.7rem;font-weight:700;border:none;background:none;cursor:pointer;color:#94a3b8;letter-spacing:0.03em;transition:color 0.15s;"
                                    onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">
                                <span x-text="showPassword ? 'HIDE' : 'SHOW'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" :disabled="loading"
                            class="w-full flex items-center justify-center gap-2.5 rounded-xl py-3.5 text-[0.9375rem] font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                            style="background:linear-gradient(135deg,#ea580c,#f97316);margin-top:0.25rem;"
                            onmouseover="if(!this.disabled)this.style.opacity='0.9';"
                            onmouseout="if(!this.disabled)this.style.opacity='1';">
                        <svg x-show="!loading" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <svg x-show="loading" x-cloak class="animate-spin" width="18" height="18" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="loading ? 'Signing in...' : 'Sign In'"></span>
                    </button>
                </form>

                {{-- Footer note --}}
                <p class="mt-8 text-center text-xs" style="color:#94a3b8;">
                    Issues with your account?
                    <a href="#" style="color:#ea580c;font-weight:600;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#ea580c'">Contact admin</a>
                </p>

            </div>
        </div>

        {{-- Bottom copyright --}}
        <p class="mt-6 text-center text-xs" style="color:#94a3b8;">
            &copy; {{ date('Y') }} ParcelMan Express &mdash; Secure Rider Portal
        </p>

    </div>
</main>
@endsection
