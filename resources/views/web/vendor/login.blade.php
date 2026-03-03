@extends('web.layouts.portal')

@section('title', 'Vendor Login')

@section('content')
<main class="flex min-h-screen items-center justify-center px-4 py-12" x-data="vendorAuthPage()" style="background:#f1f5f9;">
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
                            Ship smarter,<br>
                            <span style="color:#fdba74;">grow faster.</span>
                        </h2>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed" style="color:rgba(254,215,170,0.75);">
                        Sign in to create shipments, track deliveries, and manage your business in real time.
                    </p>
                </div>

                {{-- Bottom: Feature points --}}
                <div class="relative z-10 space-y-4">
                    <div class="flex items-center gap-4">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="#fdba74" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/><rect x="3" y="3" width="18" height="18" rx="2" stroke="#fdba74" stroke-width="2" fill="none"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Create Shipments</p>
                            <p class="text-xs" style="color:rgba(254,215,170,0.65);">Book pickups and send parcels easily</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="#fdba74" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Live Tracking</p>
                            <p class="text-xs" style="color:rgba(254,215,170,0.65);">Follow every parcel from pickup to delivery</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="#fdba74" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Invoices & Payments</p>
                            <p class="text-xs" style="color:rgba(254,215,170,0.65);">Review and accept invoices instantly</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Right: Auth Form ====== --}}
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
                <div class="flex items-center gap-4 mb-6">
                    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:54px;height:54px;border-radius:16px;background:linear-gradient(135deg,#ea580c,#f97316);">
                        <svg width="28" height="28" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold" style="color:#0f172a;">ParcelMan Express</h1>
                        <p class="mt-0.5 text-sm" style="color:#64748b;">Enter your phone number to sign in or create your account</p>
                    </div>
                </div>

                {{-- Alert --}}
                <div class="mb-5" x-cloak x-show="alert" role="alert">
                    <div class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm"
                         :class="{
                            'border-emerald-200 bg-emerald-50 text-emerald-700': alert?.type === 'success',
                            'border-sky-200 bg-sky-50 text-sky-700': alert?.type === 'info',
                            'border-rose-200 bg-rose-50 text-rose-700': alert?.type === 'error'
                        }">
                        <span x-text="alert?.message"></span>
                    </div>
                </div>

                {{-- Step: Phone --}}
                <form class="space-y-4" x-show="step === 'phone'" @submit.prevent="sendOtp()">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" style="color:#374151;">Phone Number</label>
                        <div style="position:relative;display:flex;align-items:stretch;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;overflow:hidden;transition:border-color 0.15s,box-shadow 0.15s;"
                             x-ref="phoneWrap"
                             onfocusin="this.style.borderColor='rgba(249,115,22,0.6)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.12)'"
                             onfocusout="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            {{-- Ghana flag prefix --}}
                            <div style="display:flex;align-items:center;gap:7px;padding:0 0.875rem;background:#f1f5f9;border-right:1px solid #e2e8f0;flex-shrink:0;user-select:none;pointer-events:none;">
                                {{-- Ghana flag SVG (red / gold with star / green) --}}
                                <svg width="22" height="16" viewBox="0 0 22 16" style="border-radius:2px;display:block;" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="22" height="16" fill="#006B3F"/>
                                    <rect width="22" height="10.67" fill="#FCD116"/>
                                    <rect width="22" height="5.33" fill="#CE1126"/>
                                    {{-- Star centered at (11,8), outer r=2.2, inner r=0.85 — fits within gold stripe y:5.33–10.67 --}}
                                    <polygon points="11,5.8 11.5,7.3 13.1,7.3 11.8,8.3 12.3,9.8 11,8.9 9.7,9.8 10.2,8.3 8.9,7.3 10.5,7.3" fill="#000000"/>
                                </svg>
                                <span style="font-size:0.8125rem;font-weight:700;color:#475569;letter-spacing:0.01em;">+233</span>
                            </div>
                            <input x-model="phone" @input="onPhoneInput($event)" @blur="formatPhoneOnBlur()"
                                   type="tel" inputmode="tel" autocomplete="tel" maxlength="14" spellcheck="false"
                                   placeholder="XX XXX XXXX"
                                   style="flex:1;border:none;background:transparent;padding:0.8125rem 1rem;font-size:0.875rem;color:#0f172a;outline:none;min-width:0;">
                        </div>
                        <p class="mt-1.5 text-xs" style="color:#94a3b8;">We'll send you a verification code</p>
                    </div>
                    <button type="submit" :disabled="loading"
                            class="w-full flex items-center justify-center gap-2.5 rounded-xl py-3.5 text-[0.9375rem] font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                            style="background:linear-gradient(135deg,#ea580c,#f97316);"
                            onmouseover="if(!this.disabled)this.style.opacity='0.9';"
                            onmouseout="if(!this.disabled)this.style.opacity='1';">
                        <span x-show="!loading">Send OTP</span>
                        <span x-show="loading" x-cloak>Processing...</span>
                    </button>
                    <p class="text-center text-xs" style="color:#94a3b8;">
                        By continuing, you agree to our
                        <a href="#" style="color:#ea580c;font-weight:600;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#ea580c'">Terms of Service</a>
                        and
                        <a href="#" style="color:#ea580c;font-weight:600;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#ea580c'">Privacy Policy</a>
                    </p>
                </form>

                {{-- Step: OTP --}}
                <form class="space-y-5" x-show="step === 'otp'" @submit.prevent="verifyOtp()">

                    {{-- OTP sent info card --}}
                    <div style="background:linear-gradient(135deg,#fff7ed,#fef9f0);border:1px solid #fed7aa;border-radius:16px;padding:1rem 1.25rem;display:flex;align-items:center;gap:0.875rem;">
                        <div style="flex-shrink:0;width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#ea580c,#f97316);display:flex;align-items:center;justify-content:center;">
                            <svg width="20" height="20" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.18 19.79 19.79 0 010 .17h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14v2.92z"/>
                            </svg>
                        </div>
                        <div style="min-width:0;">
                            <p style="font-size:0.7rem;color:#92400e;font-weight:500;margin:0 0 0.2rem;text-transform:uppercase;letter-spacing:0.06em;">A 6-digit OTP has been sent to this number.</p>
                            <p style="font-size:1rem;font-weight:800;color:#7c2d12;letter-spacing:0.06em;margin:0;font-family:monospace;" x-text="maskedPhone"></p>
                        </div>
                    </div>

                    {{-- 6 digit boxes --}}
                    <div>
                        <label class="mb-3 block text-sm font-medium" style="color:#374151;">Enter the 6-digit code</label>
                        <div class="flex gap-2 justify-between">
                            <input x-ref="otp0" :value="otpDigits[0]" @input="onOtpDigit($event,0)" @keydown="onOtpBack($event,0)" @paste.prevent="onOtpPaste($event)" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code"
                                   style="width:100%;aspect-ratio:1;text-align:center;font-size:1.375rem;font-weight:700;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.7)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.15)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            <input x-ref="otp1" :value="otpDigits[1]" @input="onOtpDigit($event,1)" @keydown="onOtpBack($event,1)" @paste.prevent="onOtpPaste($event)" type="text" inputmode="numeric" maxlength="1"
                                   style="width:100%;aspect-ratio:1;text-align:center;font-size:1.375rem;font-weight:700;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.7)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.15)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            <input x-ref="otp2" :value="otpDigits[2]" @input="onOtpDigit($event,2)" @keydown="onOtpBack($event,2)" @paste.prevent="onOtpPaste($event)" type="text" inputmode="numeric" maxlength="1"
                                   style="width:100%;aspect-ratio:1;text-align:center;font-size:1.375rem;font-weight:700;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.7)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.15)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            <input x-ref="otp3" :value="otpDigits[3]" @input="onOtpDigit($event,3)" @keydown="onOtpBack($event,3)" @paste.prevent="onOtpPaste($event)" type="text" inputmode="numeric" maxlength="1"
                                   style="width:100%;aspect-ratio:1;text-align:center;font-size:1.375rem;font-weight:700;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.7)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.15)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            <input x-ref="otp4" :value="otpDigits[4]" @input="onOtpDigit($event,4)" @keydown="onOtpBack($event,4)" @paste.prevent="onOtpPaste($event)" type="text" inputmode="numeric" maxlength="1"
                                   style="width:100%;aspect-ratio:1;text-align:center;font-size:1.375rem;font-weight:700;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.7)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.15)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                            <input x-ref="otp5" :value="otpDigits[5]" @input="onOtpDigit($event,5)" @keydown="onOtpBack($event,5)" @paste.prevent="onOtpPaste($event)" type="text" inputmode="numeric" maxlength="1"
                                   style="width:100%;aspect-ratio:1;text-align:center;font-size:1.375rem;font-weight:700;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;"
                                   onfocus="this.style.borderColor='rgba(249,115,22,0.7)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.15)'"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                        </div>
                    </div>

                    {{-- Verify button (full width) --}}
                    <button type="submit" :disabled="loading || otpExpiresIn <= 0"
                            class="w-full flex items-center justify-center gap-2.5 rounded-xl py-3.5 text-[0.9375rem] font-bold transition disabled:cursor-not-allowed"
                            :style="otpExpiresIn <= 0 ? 'background:#e2e8f0;color:#94a3b8;' : (loading ? 'background:linear-gradient(135deg,#ea580c,#f97316);color:white;opacity:0.65;' : 'background:linear-gradient(135deg,#ea580c,#f97316);color:white;')"
                            onmouseover="if(!this.disabled)this.style.opacity='0.9';"
                            onmouseout="if(!this.disabled)this.style.opacity='1';">
                        <span x-show="!loading" x-text="otpExpiresIn <= 0 ? 'Code Expired' : 'Verify Phone'"></span>
                        <span x-show="loading" x-cloak>Verifying...</span>
                    </button>

                    {{-- Bottom row: change phone (left) | expires/resend (right) --}}
                    <div class="flex items-center justify-between text-sm">
                        <button type="button" @click="backToPhone()"
                                class="font-medium transition"
                                style="color:#94a3b8;"
                                onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#94a3b8'">
                            ← Change phone number
                        </button>
                        <span class="tabular-nums font-semibold" x-show="otpExpiresIn > 0"
                              :style="otpExpiresIn <= 30 ? 'color:#ef4444;' : 'color:#94a3b8;'"
                              x-text="'Expires in ' + formatOtpExpiry()"></span>
                        <button type="button" x-show="otpExpiresIn <= 0" x-cloak
                                @click="resendOtp()" :disabled="loading"
                                class="font-semibold transition disabled:cursor-not-allowed"
                                style="color:#ea580c;"
                                onmouseover="if(!this.disabled)this.style.textDecoration='underline'"
                                onmouseout="this.style.textDecoration='none'">
                            Resend OTP
                        </button>
                    </div>
                </form>

                {{-- Step: Register --}}
                <form class="space-y-4" x-show="step === 'register'" @submit.prevent="registerVendor()">

                    {{-- Registration heading --}}
                    <div>
                        <h2 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Almost there!</h2>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0;">Fill in your details below to complete your account setup.</p>
                    </div>

                    {{-- Verified phone card --}}
                    <div style="background:linear-gradient(135deg,#fff7ed,#fef9f0);border:1px solid #fed7aa;border-radius:16px;padding:0.875rem 1.25rem;display:flex;align-items:center;gap:0.875rem;">
                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#ea580c,#f97316);display:flex;align-items:center;justify-content:center;">
                            <svg width="19" height="19" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.18 19.79 19.79 0 010 .17h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14v2.92z"/>
                            </svg>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <p style="font-size:0.7rem;color:#92400e;font-weight:500;margin:0 0 0.25rem;text-transform:uppercase;letter-spacing:0.06em;">Signing up with</p>
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <span style="font-size:0.9375rem;font-weight:800;color:#7c2d12;letter-spacing:0.04em;font-family:monospace;" x-text="maskedPhone"></span>
                                <button type="button" @click="backToPhone()"
                                        style="font-size:0.75rem;font-weight:600;color:#ea580c;background:none;border:none;padding:0;cursor:pointer;white-space:nowrap;flex-shrink:0;text-decoration:underline;">
                                    Change
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" style="color:#374151;">Full Name</label>
                        <input x-model="registerForm.name" type="text"
                               style="width:100%;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;padding:0.8125rem 1rem;font-size:0.875rem;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(249,115,22,0.6)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" style="color:#374151;">Business Name <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input x-model="registerForm.business_name" type="text"
                               style="width:100%;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;padding:0.8125rem 1rem;font-size:0.875rem;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(249,115,22,0.6)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" style="color:#374151;">Email <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input x-model="registerForm.email" type="email"
                               style="width:100%;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;padding:0.8125rem 1rem;font-size:0.875rem;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(249,115,22,0.6)';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.12)'"
                               onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                    </div>
                    <button type="submit" :disabled="loading || !registerForm.name.trim()"
                            class="w-full flex items-center justify-center gap-2.5 rounded-xl py-3.5 text-[0.9375rem] font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                            style="background:linear-gradient(135deg,#ea580c,#f97316);"
                            onmouseover="if(!this.disabled)this.style.opacity='0.9';"
                            onmouseout="if(!this.disabled)this.style.opacity='1';">
                        <span x-show="!loading">Complete Registration</span>
                        <span x-show="loading" x-cloak>Submitting...</span>
                    </button>

                    {{-- Session countdown --}}
                    <div class="flex items-center justify-center gap-1.5 tabular-nums"
                         :style="registrationExpiresIn <= 60 ? 'color:#ef4444;' : 'color:#94a3b8;'"
                         style="font-size:0.75rem;font-weight:500;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Session expires in</span>
                        <span x-text="formatRegistrationExpiry()" style="font-weight:700;"></span>
                    </div>

                    <p class="text-center text-xs" style="color:#94a3b8;">
                        By continuing, you agree to our
                        <a href="#" style="color:#ea580c;font-weight:600;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#ea580c'">Terms of Service</a>
                        and
                        <a href="#" style="color:#ea580c;font-weight:600;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#ea580c'">Privacy Policy</a>
                    </p>
                </form>


            </div>
        </div>

        {{-- Bottom copyright --}}
        <p class="mt-6 text-center text-xs" style="color:#94a3b8;">
            &copy; {{ date('Y') }} ParcelMan Express &mdash; All rights reserved
        </p>

    </div>

    {{-- Registration Success Overlay --}}
    <div x-show="registrationSuccess" x-cloak
         style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);">
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:calc(100% - 2rem);max-width:360px;background:#ffffff;border-radius:24px;padding:2rem;box-shadow:0 25px 60px rgba(0,0,0,0.25);text-align:center;">
            {{-- Checkmark icon --}}
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#dcfce7,#f0fdf4);border:2px solid #86efac;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                <svg width="34" height="34" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h3 style="font-size:1.1875rem;font-weight:800;color:#0f172a;margin:0 0 0.5rem;">Registration Successful!</h3>
            <p style="font-size:0.875rem;color:#64748b;line-height:1.6;margin:0;">
                Welcome aboard! Taking you to your dashboard…
            </p>
        </div>
    </div>

    {{-- Session Expired Modal --}}
    <div x-show="registrationExpired" x-cloak
         style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);">
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:calc(100% - 2rem);max-width:380px;background:#ffffff;border-radius:24px;padding:2rem;box-shadow:0 25px 60px rgba(0,0,0,0.25);text-align:center;">

            {{-- Icon --}}
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#fff7ed,#fef3c7);border:2px solid #fed7aa;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                <svg width="34" height="34" fill="none" stroke="#ea580c" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>

            {{-- Heading --}}
            <h3 style="font-size:1.1875rem;font-weight:800;color:#0f172a;margin:0 0 0.5rem;">Session Expired</h3>

            {{-- Body --}}
            <p style="font-size:0.875rem;color:#64748b;line-height:1.6;margin:0 0 1.75rem;">
                Your 10-minute verification window has timed out. Please re-enter your phone number to get a fresh code.
            </p>

            {{-- Confirm button --}}
            <button type="button" @@click="backToPhone()"
                    style="width:100%;border-radius:12px;padding:0.875rem 1rem;font-size:0.9375rem;font-weight:700;color:white;background:linear-gradient(135deg,#ea580c,#f97316);border:none;cursor:pointer;transition:opacity 0.15s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                OK, Start Again
            </button>
        </div>
    </div>

</main>
@endsection
