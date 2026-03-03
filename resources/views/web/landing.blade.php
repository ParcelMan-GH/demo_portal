@extends('web.layouts.portal')

@section('title', 'Parcelman — Ship Smarter, Grow Faster')

@section('content')

{{-- Full light override over the dark portal layout --}}
<div x-data="{ mobileOpen: false }" style="font-family:'Plus Jakarta Sans',sans-serif;background:#f8fafc;min-height:100vh;position:relative;z-index:10;">

{{-- ================================================================
     NAVBAR
================================================================ --}}
<header style="position:sticky;top:0;z-index:50;background:rgba(255,255,255,0.92);backdrop-filter:blur(20px);border-bottom:1px solid #e2e8f0;box-shadow:0 1px 8px rgba(0,0,0,0.06);">
    <div style="max-width:1200px;margin:0 auto;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;height:66px;">

        <a href="{{ route('web.landing') }}" style="display:flex;align-items:center;text-decoration:none;flex-shrink:0;">
            <img src="{{ asset('logo-2.png') }}" alt="Parcelman" style="height:40px;width:auto;">
        </a>

        <nav class="landing-desktop-nav" style="display:none;align-items:center;gap:0.25rem;">
            <a href="#features" style="padding:0.4rem 0.85rem;border-radius:8px;font-size:0.875rem;font-weight:500;color:#475569;text-decoration:none;transition:color 0.2s,background 0.2s;" onmouseover="this.style.color='#0f172a';this.style.background='#f1f5f9'" onmouseout="this.style.color='#475569';this.style.background='transparent'">Features</a>
            <a href="#how-it-works" style="padding:0.4rem 0.85rem;border-radius:8px;font-size:0.875rem;font-weight:500;color:#475569;text-decoration:none;transition:color 0.2s,background 0.2s;" onmouseover="this.style.color='#0f172a';this.style.background='#f1f5f9'" onmouseout="this.style.color='#475569';this.style.background='transparent'">How it Works</a>
            <a href="#portals" style="padding:0.4rem 0.85rem;border-radius:8px;font-size:0.875rem;font-weight:500;color:#475569;text-decoration:none;transition:color 0.2s,background 0.2s;" onmouseover="this.style.color='#0f172a';this.style.background='#f1f5f9'" onmouseout="this.style.color='#475569';this.style.background='transparent'">Portals</a>
        </nav>

        <div style="display:flex;align-items:center;gap:0.75rem;">
            <a href="{{ route('web.vendor.login') }}" class="landing-desktop-nav" style="display:none;padding:0.5rem 1.25rem;border-radius:10px;background:linear-gradient(135deg,#ea580c,#c2410c);font-size:0.875rem;font-weight:700;color:#fff;text-decoration:none;box-shadow:0 2px 8px rgba(234,88,12,0.3);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Get Started
            </a>
            <button type="button" @@click="mobileOpen = !mobileOpen" class="landing-mobile-btn" style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0;cursor:pointer;color:#475569;">
                <svg x-show="!mobileOpen" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="mobileOpen" x-cloak width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div x-show="mobileOpen" x-cloak style="background:#fff;border-top:1px solid #e2e8f0;padding:1rem 1.5rem 1.25rem;">
        <nav style="display:flex;flex-direction:column;gap:0.25rem;">
            <a href="#features" @@click="mobileOpen=false" style="padding:0.7rem 0.85rem;border-radius:8px;font-size:0.9rem;font-weight:500;color:#475569;text-decoration:none;">Features</a>
            <a href="#how-it-works" @@click="mobileOpen=false" style="padding:0.7rem 0.85rem;border-radius:8px;font-size:0.9rem;font-weight:500;color:#475569;text-decoration:none;">How it Works</a>
            <a href="#portals" @@click="mobileOpen=false" style="padding:0.7rem 0.85rem;border-radius:8px;font-size:0.9rem;font-weight:500;color:#475569;text-decoration:none;">Portals</a>
            <div style="height:1px;background:#e2e8f0;margin:0.5rem 0;"></div>
            <a href="{{ route('web.vendor.login') }}" style="padding:0.75rem 1rem;border-radius:10px;background:linear-gradient(135deg,#ea580c,#c2410c);font-size:0.9rem;font-weight:700;color:#fff;text-decoration:none;text-align:center;">Vendor Portal</a>
            <a href="{{ route('web.driver.login') }}" style="padding:0.75rem 1rem;border-radius:10px;background:#f0f9ff;border:1px solid #bae6fd;font-size:0.9rem;font-weight:700;color:#0369a1;text-decoration:none;text-align:center;margin-top:0.375rem;">Driver Portal</a>
        </nav>
    </div>
</header>

{{-- ================================================================
     HERO
================================================================ --}}
<section style="padding:5.5rem 1.5rem 5rem;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);">

    {{-- Subtle orange glow --}}
    <div style="position:absolute;top:-6rem;left:50%;transform:translateX(-50%);width:600px;height:300px;background:radial-gradient(ellipse,rgba(234,88,12,0.08) 0%,transparent 70%);pointer-events:none;"></div>

    <div style="max-width:780px;margin:0 auto;position:relative;z-index:1;">

        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.35rem 1rem;border-radius:100px;background:#fff7ed;border:1px solid #fed7aa;margin-bottom:1.75rem;">
            <span style="width:6px;height:6px;border-radius:50%;background:#f97316;display:inline-block;animation:pulse-dot 2s infinite;"></span>
            <span style="font-size:0.75rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#c2410c;">Ghana's Premier Parcel Logistics Platform</span>
        </div>

        <h1 style="font-size:clamp(2.5rem,6vw,4.25rem);font-weight:800;line-height:1.1;letter-spacing:-0.025em;margin:0 0 1.5rem;color:#0f172a;">
            Ship Smarter,<br>
            <span style="background:linear-gradient(135deg,#ea580c 0%,#f97316 50%,#fb923c 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Grow Faster.</span>
        </h1>

        <p style="font-size:clamp(1rem,2.5vw,1.175rem);color:#64748b;line-height:1.8;margin:0 auto 2.5rem;max-width:580px;">
            Parcelman connects vendors and drivers on a single platform — from booking pickups and live tracking to invoicing and last-mile delivery, all in real time.
        </p>

        <div style="display:flex;flex-wrap:wrap;gap:0.875rem;justify-content:center;">
            <a href="{{ route('web.vendor.login') }}" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.9rem 2rem;border-radius:14px;background:linear-gradient(135deg,#ea580c,#c2410c);font-size:1rem;font-weight:700;color:#fff;text-decoration:none;box-shadow:0 4px 20px rgba(234,88,12,0.3);transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 28px rgba(234,88,12,0.38)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 20px rgba(234,88,12,0.3)'">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Vendor Portal
            </a>
            <a href="{{ route('web.driver.login') }}" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.9rem 2rem;border-radius:14px;background:#f0f9ff;border:1.5px solid #bae6fd;font-size:1rem;font-weight:700;color:#0369a1;text-decoration:none;transition:transform 0.2s,background 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.background='#e0f2fe'" onmouseout="this.style.transform='translateY(0)';this.style.background='#f0f9ff'">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                Driver Portal
            </a>
        </div>
    </div>
</section>

{{-- ================================================================
     STATS BAR
================================================================ --}}
<section style="padding:0 1.5rem 4.5rem;background:#f8fafc;">
    <div style="max-width:860px;margin:0 auto;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:1.75rem 2rem;display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;text-align:center;box-shadow:0 1px 8px rgba(0,0,0,0.06);">
            <div style="padding:0.5rem 0;border-right:1px solid #e2e8f0;">
                <p style="font-size:clamp(1.75rem,4vw,2.5rem);font-weight:800;color:#ea580c;margin:0;letter-spacing:-0.02em;">50K+</p>
                <p style="font-size:0.8rem;font-weight:500;color:#94a3b8;margin:0.25rem 0 0;">Parcels Delivered</p>
            </div>
            <div style="padding:0.5rem 0;border-right:1px solid #e2e8f0;">
                <p style="font-size:clamp(1.75rem,4vw,2.5rem);font-weight:800;color:#ea580c;margin:0;letter-spacing:-0.02em;">1,200+</p>
                <p style="font-size:0.8rem;font-weight:500;color:#94a3b8;margin:0.25rem 0 0;">Active Vendors</p>
            </div>
            <div style="padding:0.5rem 0;">
                <p style="font-size:clamp(1.75rem,4vw,2.5rem);font-weight:800;color:#ea580c;margin:0;letter-spacing:-0.02em;">98.5%</p>
                <p style="font-size:0.8rem;font-weight:500;color:#94a3b8;margin:0.25rem 0 0;">On-time Delivery Rate</p>
            </div>
        </div>
    </div>
</section>

{{-- ================================================================
     FEATURES
================================================================ --}}
<section id="features" style="padding:4rem 1.5rem 5rem;background:#fff;">
    <div style="max-width:1120px;margin:0 auto;">

        <div style="text-align:center;margin-bottom:3.5rem;">
            <span style="display:inline-block;padding:0.3rem 0.875rem;border-radius:100px;background:#fff7ed;border:1px solid #fed7aa;font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#c2410c;margin-bottom:1rem;">Platform Features</span>
            <h2 style="font-size:clamp(1.875rem,4vw,2.75rem);font-weight:800;color:#0f172a;margin:0 0 1rem;letter-spacing:-0.02em;">Everything you need to<br>run your logistics</h2>
            <p style="font-size:1rem;color:#64748b;max-width:520px;margin:0 auto;line-height:1.75;">From the moment a vendor books a pickup to the instant a parcel is delivered, Parcelman handles every step.</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.25rem;">
            @php
            $features = [
                ['#ea580c','#fff7ed','#fed7aa','M12 4v16m8-8H4','Instant Shipment Booking','Vendors create and submit shipments in seconds. Specify items, dimensions, destination and get a pickup scheduled.'],
                ['#0ea5e9','#f0f9ff','#bae6fd','M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7','Real-Time Tracking','Follow every parcel at every stage — from pickup through warehouse sorting to last-mile delivery.'],
                ['#8b5cf6','#f5f3ff','#ddd6fe','M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','Smart Invoicing','Automated invoice generation with vendor acceptance flow, admin override, and full payment tracking.'],
                ['#10b981','#f0fdf4','#bbf7d0','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0','Driver Management','Assign drivers to pickups and transport runs, track their status, and notify them instantly via push.'],
                ['#f59e0b','#fffbeb','#fde68a','M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9','Push Notifications','Vendors and drivers get instant push notifications for every status change — no delays, fully real-time.'],
                ['#ec4899','#fdf2f8','#fbcfe8','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','Operations Dashboard','Admins get a powerful dashboard with delivery runs, transport manifests, sort batches, and live ops visibility.'],
            ];
            @endphp

            @foreach($features as $f)
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:1.75rem;transition:border-color 0.2s,box-shadow 0.2s;" onmouseover="this.style.borderColor='#cbd5e1';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                <div style="width:48px;height:48px;border-radius:14px;background:{{ $f[1] }};border:1px solid {{ $f[2] }};display:flex;align-items:center;justify-content:center;margin-bottom:1.125rem;">
                    <svg width="22" height="22" fill="none" stroke="{{ $f[0] }}" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $f[3] }}"/></svg>
                </div>
                <h3 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">{{ $f[4] }}</h3>
                <p style="font-size:0.875rem;color:#64748b;margin:0;line-height:1.7;">{{ $f[5] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     HOW IT WORKS
================================================================ --}}
<section id="how-it-works" style="padding:5rem 1.5rem 5.5rem;background:#f8fafc;">
    <div style="max-width:1080px;margin:0 auto;">

        <div style="text-align:center;margin-bottom:3.5rem;">
            <span style="display:inline-block;padding:0.3rem 0.875rem;border-radius:100px;background:#fff7ed;border:1px solid #fed7aa;font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#c2410c;margin-bottom:1rem;">How it Works</span>
            <h2 style="font-size:clamp(1.875rem,4vw,2.75rem);font-weight:800;color:#0f172a;margin:0 0 1rem;letter-spacing:-0.02em;">From booking to delivery<br>in four simple steps</h2>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;">
            @php
            $howSteps = [
                ['01','#ea580c','#fff7ed','#fed7aa','Book a Shipment','Vendor logs in, fills in parcel details and destination, and submits a new shipment request.'],
                ['02','#0ea5e9','#f0f9ff','#bae6fd','Driver Assigned','Admin assigns a driver. The driver gets an instant push notification with pickup details.'],
                ['03','#8b5cf6','#f5f3ff','#ddd6fe','Warehouse & Sort','Parcel arrives at warehouse, gets received, sorted, and packed into a transport manifest.'],
                ['04','#10b981','#f0fdf4','#bbf7d0','Last-Mile Delivery','Driver completes deliveries and recipient confirms with a secure OTP confirmation code.'],
            ];
            @endphp

            @foreach($howSteps as $s)
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:1.75rem;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <div style="width:52px;height:52px;border-radius:50%;background:{{ $s[2] }};border:2px solid {{ $s[3] }};display:flex;align-items:center;justify-content:center;margin:0 auto 1.125rem;">
                    <span style="font-size:1.125rem;font-weight:800;color:{{ $s[1] }};">{{ $s[0] }}</span>
                </div>
                <h3 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">{{ $s[4] }}</h3>
                <p style="font-size:0.85rem;color:#64748b;margin:0;line-height:1.7;">{{ $s[5] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     PORTALS SECTION
================================================================ --}}
<section id="portals" style="padding:5rem 1.5rem 5.5rem;background:#fff;">
    <div style="max-width:1000px;margin:0 auto;">

        <div style="text-align:center;margin-bottom:3rem;">
            <span style="display:inline-block;padding:0.3rem 0.875rem;border-radius:100px;background:#fff7ed;border:1px solid #fed7aa;font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#c2410c;margin-bottom:1rem;">Choose Your Portal</span>
            <h2 style="font-size:clamp(1.875rem,4vw,2.75rem);font-weight:800;color:#0f172a;margin:0;letter-spacing:-0.02em;">Two portals. One platform.</h2>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;">

            {{-- Vendor Card --}}
            <a href="{{ route('web.vendor.login') }}" style="display:block;text-decoration:none;background:linear-gradient(145deg,#fff7ed 0%,#fff 60%);border:1.5px solid #fed7aa;border-radius:24px;padding:2.25rem;transition:transform 0.25s,border-color 0.25s,box-shadow 0.25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='#fb923c';this.style.boxShadow='0 12px 40px rgba(234,88,12,0.12)'" onmouseout="this.style.transform='translateY(0)';this.style.borderColor='#fed7aa';this.style.boxShadow='none'">
                <div style="width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,#ea580c,#c2410c);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;box-shadow:0 6px 20px rgba(234,88,12,0.3);">
                    <svg width="24" height="24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <p style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#c2410c;margin:0 0 0.4rem;">Vendor Portal</p>
                <h3 style="font-size:1.375rem;font-weight:800;color:#0f172a;margin:0 0 0.75rem;">I'm sending parcels</h3>
                <p style="font-size:0.9rem;color:#64748b;line-height:1.75;margin:0 0 1.5rem;">Register or sign in with your phone number. Create shipments, track them live, review invoices, and manage your business.</p>
                <ul style="list-style:none;padding:0;margin:0 0 1.75rem;display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach(['OTP phone verification','Create & track shipments','Invoice management','Real-time push notifications'] as $b)
                    <li style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;color:#475569;">
                        <svg width="16" height="16" fill="none" stroke="#ea580c" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $b }}
                    </li>
                    @endforeach
                </ul>
                <div style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.9rem;font-weight:700;color:#ea580c;">
                    Access Vendor Portal
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
            </a>

            {{-- Driver Card --}}
            <a href="{{ route('web.driver.login') }}" style="display:block;text-decoration:none;background:linear-gradient(145deg,#f0f9ff 0%,#fff 60%);border:1.5px solid #bae6fd;border-radius:24px;padding:2.25rem;transition:transform 0.25s,border-color 0.25s,box-shadow 0.25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='#7dd3fc';this.style.boxShadow='0 12px 40px rgba(14,165,233,0.1)'" onmouseout="this.style.transform='translateY(0)';this.style.borderColor='#bae6fd';this.style.boxShadow='none'">
                <div style="width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,#0ea5e9,#0369a1);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;box-shadow:0 6px 20px rgba(14,165,233,0.25);">
                    <svg width="24" height="24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                </div>
                <p style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#0369a1;margin:0 0 0.4rem;">Driver Portal</p>
                <h3 style="font-size:1.375rem;font-weight:800;color:#0f172a;margin:0 0 0.75rem;">I'm delivering parcels</h3>
                <p style="font-size:0.9rem;color:#64748b;line-height:1.75;margin:0 0 1.5rem;">Sign in with your credentials. Accept pickup assignments, manage transport manifests, and complete delivery runs seamlessly.</p>
                <ul style="list-style:none;padding:0;margin:0 0 1.75rem;display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach(['Email & password login','Pickup assignments','Transport manifests','Delivery runs & OTP confirm'] as $b)
                    <li style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;color:#475569;">
                        <svg width="16" height="16" fill="none" stroke="#0ea5e9" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $b }}
                    </li>
                    @endforeach
                </ul>
                <div style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.9rem;font-weight:700;color:#0369a1;">
                    Access Driver Portal
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
            </a>

        </div>
    </div>
</section>

{{-- ================================================================
     BOTTOM CTA BANNER
================================================================ --}}
<section style="padding:3rem 1.5rem 5rem;background:#f8fafc;">
    <div style="max-width:900px;margin:0 auto;">
        <div style="background:linear-gradient(135deg,#ea580c 0%,#c2410c 60%,#9a3412 100%);border-radius:28px;padding:4rem 2.5rem;text-align:center;position:relative;overflow:hidden;box-shadow:0 16px 60px rgba(234,88,12,0.3);">
            <div style="position:absolute;top:-4rem;right:-4rem;width:14rem;height:14rem;border-radius:50%;background:rgba(255,255,255,0.06);pointer-events:none;"></div>
            <div style="position:absolute;bottom:-5rem;left:-4rem;width:18rem;height:18rem;border-radius:50%;background:rgba(255,255,255,0.04);pointer-events:none;"></div>
            <div style="position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.04) 1px,transparent 1px);background-size:22px 22px;pointer-events:none;opacity:0.6;"></div>
            <div style="position:relative;z-index:1;">
                <p style="font-size:0.7rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(254,215,170,0.9);margin:0 0 1rem;">Get Started Today</p>
                <h2 style="font-size:clamp(1.75rem,4vw,2.5rem);font-weight:800;color:#fff;margin:0 0 1rem;letter-spacing:-0.02em;">Ready to ship smarter?</h2>
                <p style="font-size:1rem;color:rgba(254,215,170,0.8);max-width:460px;margin:0 auto 2rem;line-height:1.75;">Join hundreds of vendors already using Parcelman to streamline their parcel deliveries across Ghana.</p>
                <div style="display:flex;flex-wrap:wrap;gap:0.875rem;justify-content:center;">
                    <a href="{{ route('web.vendor.login') }}" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.875rem 2rem;border-radius:12px;background:#fff;font-size:0.95rem;font-weight:700;color:#c2410c;text-decoration:none;box-shadow:0 4px 16px rgba(0,0,0,0.15);transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        Register as Vendor
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="{{ route('web.driver.login') }}" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.875rem 2rem;border-radius:12px;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);font-size:0.95rem;font-weight:700;color:#fff;text-decoration:none;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                        Driver Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================================================================
     FOOTER
================================================================ --}}
<footer style="background:#0f172a;padding:3.5rem 1.5rem 2rem;">
    <div style="max-width:1120px;margin:0 auto;">

        <div class="footer-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:2.5rem;margin-bottom:2.5rem;">

            <div>
                <a href="{{ route('web.landing') }}" style="display:inline-block;margin-bottom:1rem;text-decoration:none;">
                    <img src="{{ asset('logo-2.png') }}" alt="Parcelman" style="height:38px;width:auto;filter:brightness(0) invert(1);">
                </a>
                <p style="font-size:0.85rem;color:rgba(148,163,184,0.7);line-height:1.75;max-width:270px;margin:0 0 1.25rem;">Your trusted logistics partner. Fast, reliable parcel delivery across Ghana with real-time tracking.</p>
                <div style="display:flex;gap:0.625rem;">
                    <a href="#" title="Facebook" style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:rgba(148,163,184,0.6);text-decoration:none;transition:background 0.2s,color 0.2s;" onmouseover="this.style.background='rgba(234,88,12,0.2)';this.style.color='#fb923c'" onmouseout="this.style.background='rgba(255,255,255,0.07)';this.style.color='rgba(148,163,184,0.6)'">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" title="Instagram" style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:rgba(148,163,184,0.6);text-decoration:none;transition:background 0.2s,color 0.2s;" onmouseover="this.style.background='rgba(234,88,12,0.2)';this.style.color='#fb923c'" onmouseout="this.style.background='rgba(255,255,255,0.07)';this.style.color='rgba(148,163,184,0.6)'">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                </div>
            </div>

            <div>
                <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.35);margin:0 0 1rem;">Quick Links</p>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach([['Vendor Portal', route('web.vendor.login')], ['Driver Portal', route('web.driver.login')], ['Features','#features'], ['How it Works','#how-it-works']] as $l)
                    <li><a href="{{ $l[1] }}" style="font-size:0.875rem;color:rgba(148,163,184,0.65);text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='rgba(148,163,184,0.65)'">{{ $l[0] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.35);margin:0 0 1rem;">Company</p>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach(['About Us','Help Center','Privacy Policy','Terms of Service'] as $item)
                    <li><a href="#" style="font-size:0.875rem;color:rgba(148,163,184,0.65);text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='rgba(148,163,184,0.65)'">{{ $item }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.35);margin:0 0 1rem;">Contact</p>
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <div style="display:flex;align-items:flex-start;gap:0.625rem;">
                        <svg width="15" height="15" style="margin-top:2px;flex-shrink:0;" fill="none" stroke="#fb923c" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span style="font-size:0.825rem;color:rgba(148,163,184,0.65);">Accra, Ghana</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:0.625rem;">
                        <svg width="15" height="15" style="margin-top:2px;flex-shrink:0;" fill="none" stroke="#fb923c" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span style="font-size:0.825rem;color:rgba(148,163,184,0.65);">+233 20 000 0000</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:0.625rem;">
                        <svg width="15" height="15" style="margin-top:2px;flex-shrink:0;" fill="none" stroke="#fb923c" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span style="font-size:0.825rem;color:rgba(148,163,184,0.65);">support@parcelman.com</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="height:1px;background:rgba(255,255,255,0.07);margin-bottom:1.75rem;"></div>

        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.875rem;">
            <p style="font-size:0.8rem;color:rgba(148,163,184,0.4);margin:0;">&copy; {{ date('Y') }} ParcelMan Express. All rights reserved.</p>
            <p style="font-size:0.8rem;color:rgba(148,163,184,0.4);margin:0;">
                Crafted with <span style="color:#ef4444;">&#10084;</span> by <a href="https://smartqix.com" target="_blank" rel="noopener noreferrer" style="color:#fb923c;font-weight:600;text-decoration:none;">Smartqix</a>
            </p>
        </div>
    </div>
</footer>

</div>{{-- /wrapper --}}

<style>
@@keyframes pulse-dot {
    0%, 100% { opacity:1; transform:scale(1); }
    50%       { opacity:0.6; transform:scale(0.82); }
}
@@media (min-width: 768px) {
    .landing-desktop-nav { display:flex !important; }
    .landing-mobile-btn  { display:none !important; }
}
@@media (max-width: 900px) {
    .footer-grid { grid-template-columns:1fr 1fr !important; }
}
@@media (max-width: 560px) {
    .footer-grid { grid-template-columns:1fr !important; }
}
</style>

@endsection
