<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e293b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Parcelman">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.jpg') }}">
    <title>@yield('title', 'Vendor Portal') - Parcelman</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/pages/vendor-portal.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 text-slate-800 antialiased" x-data="vendorLayout()">

    {{-- Top Header / Navbar --}}
    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="flex h-14 items-center justify-between gap-4">
                {{-- Left: Brand + Nav --}}
                <div class="flex items-center gap-6">
                    {{-- Brand --}}
                    <a href="{{ route('web.vendor.home') }}" class="flex items-center">
                        <img src="{{ asset('logo.png') }}" alt="ParcelMan Express" class="h-9 w-auto">
                    </a>

                    {{-- Desktop navigation --}}
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="{{ route('web.vendor.home') }}"
                           class="vendor-topnav-item {{ request()->routeIs('web.vendor.home') || request()->routeIs('web.vendor.profile') ? 'active' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('web.vendor.shipments.index') }}"
                           class="vendor-topnav-item {{ request()->routeIs('web.vendor.shipments.index') || request()->routeIs('web.vendor.shipments.show') || request()->routeIs('web.vendor.shipments.edit') ? 'active' : '' }}">
                            Shipments
                        </a>
                        <a href="{{ route('web.vendor.shipments.create') }}"
                           class="vendor-topnav-item {{ request()->routeIs('web.vendor.shipments.create') ? 'active' : '' }}">
                            New Shipment
                        </a>
                        <a href="{{ route('web.vendor.invoices.index') }}"
                           class="vendor-topnav-item {{ request()->routeIs('web.vendor.invoices.*') ? 'active' : '' }}">
                            Invoices
                        </a>
                    </nav>
                </div>

                {{-- Right: Actions --}}
                <div class="flex items-center gap-2.5">
                    {{-- PWA Install button --}}
                    <button type="button" id="pwa-install-btn" class="pwa-install-btn hidden"
                            @click="installPwa()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span class="hidden sm:inline">Install App</span>
                    </button>

                    {{-- User avatar dropdown --}}
                    <div class="relative" x-data="{ profileOpen: false }">
                        <button @click="profileOpen = !profileOpen" class="flex items-center gap-2 rounded-full border border-gray-200 py-1 pl-1 pr-3 hover:bg-gray-50 transition cursor-pointer">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-xs font-bold text-white" x-text="vendorInitial"></div>
                            <span class="hidden text-sm font-medium text-slate-700 sm:inline" x-text="vendorName"></span>
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="profileOpen" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             @click.away="profileOpen = false"
                             class="vendor-profile-dropdown">
                            {{-- User info --}}
                            <div class="vendor-profile-dropdown-header">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-slate-700 to-slate-900 text-white text-sm font-bold shadow-sm" x-text="vendorInitial"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="vendor-profile-dropdown-name" x-text="vendorName"></p>
                                    <p class="vendor-profile-dropdown-role">Vendor</p>
                                </div>
                            </div>
                            {{-- Links --}}
                            <div class="vendor-profile-dropdown-links">
                                <a href="{{ route('web.vendor.home') }}" class="vendor-profile-dropdown-link">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Dashboard
                                </a>
                                <a href="{{ route('web.vendor.profile') }}" class="vendor-profile-dropdown-link">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    My Profile
                                </a>
                                <a href="{{ route('web.vendor.shipments.index') }}" class="vendor-profile-dropdown-link">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    My Shipments
                                </a>
                                <a href="{{ route('web.vendor.invoices.index') }}" class="vendor-profile-dropdown-link">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                    My Invoices
                                </a>
                            </div>
                            {{-- Logout --}}
                            <div class="vendor-profile-dropdown-footer">
                                <button type="button" @click="vendorLogout()" class="vendor-profile-dropdown-logout">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Mobile menu button --}}
                    <button type="button" @click="mobileMenuOpen = !mobileMenuOpen"
                            class="flex items-center justify-center rounded-lg border border-gray-200 p-2 text-slate-500 hover:bg-gray-50 md:hidden">
                        <svg x-show="!mobileMenuOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile navigation dropdown --}}
        <div class="border-t border-gray-100 bg-white md:hidden"
             x-show="mobileMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             @click.outside="mobileMenuOpen = false">
            <nav class="space-y-1 px-4 py-3">
                <a href="{{ route('web.vendor.home') }}"
                   class="vendor-mobilenav-item {{ request()->routeIs('web.vendor.home') || request()->routeIs('web.vendor.profile') ? 'active' : '' }}">
                    <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('web.vendor.shipments.index') }}"
                   class="vendor-mobilenav-item {{ request()->routeIs('web.vendor.shipments.index') || request()->routeIs('web.vendor.shipments.show') || request()->routeIs('web.vendor.shipments.edit') ? 'active' : '' }}">
                    <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Shipments
                </a>
                <a href="{{ route('web.vendor.shipments.create') }}"
                   class="vendor-mobilenav-item {{ request()->routeIs('web.vendor.shipments.create') ? 'active' : '' }}">
                    <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Shipment
                </a>
                <a href="{{ route('web.vendor.invoices.index') }}"
                   class="vendor-mobilenav-item {{ request()->routeIs('web.vendor.invoices.*') ? 'active' : '' }}">
                    <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    Invoices
                </a>
                <a href="{{ route('web.landing') }}"
                   class="vendor-mobilenav-item">
                    <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    Main Portal
                </a>
            </nav>
        </div>
    </header>

    {{-- Page content --}}
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="vendor-footer">
        <div class="vendor-footer-main">
            {{-- Brand Column --}}
            <div class="vendor-footer-brand">
                <a href="{{ route('web.vendor.home') }}" class="vendor-footer-brand-link">
                    <img src="{{ asset('logo.png') }}" alt="ParcelMan Express" class="vendor-footer-logo">
                </a>
                <p class="vendor-footer-desc">
                    Your trusted logistics partner. Fast, reliable parcel delivery across Ghana with real-time tracking and seamless vendor integration.
                </p>
                <div class="vendor-footer-social">
                    <a href="#" title="Facebook">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" title="Twitter">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    <a href="#" title="Instagram">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="vendor-footer-section">
                <h4>Quick Links</h4>
                <ul class="vendor-footer-links">
                    <li>
                        <a href="{{ route('web.vendor.home') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('web.vendor.shipments.index') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Shipments
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('web.vendor.shipments.create') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            New Shipment
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('web.vendor.invoices.index') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Invoices
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Resources --}}
            <div class="vendor-footer-section">
                <h4>Resources</h4>
                <ul class="vendor-footer-links">
                    <li>
                        <a href="{{ route('web.landing') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Main Portal
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Help Center
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            FAQs
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Contact Info --}}
            <div class="vendor-footer-section">
                <h4>Contact Us</h4>
                <div class="vendor-footer-contact">
                    <div class="vendor-footer-contact-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <strong>Address</strong>
                        Accra, Ghana
                    </div>
                </div>
                <div class="vendor-footer-contact">
                    <div class="vendor-footer-contact-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <strong>Phone</strong>
                        +233 20 000 0000
                    </div>
                </div>
                <div class="vendor-footer-contact">
                    <div class="vendor-footer-contact-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <strong>Email</strong>
                        support@parcelman.com
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="vendor-footer-bottom">
            <div class="vendor-footer-bottom-content">
                <p class="vendor-footer-copyright">
                    &copy; {{ date('Y') }} <span>ParcelMan Express</span>. Delivering with speed & care.
                </p>
                <div class="vendor-footer-legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            <div class="vendor-footer-bottom-content" style="justify-content: center; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <p class="vendor-footer-copyright">
                    Crafted with <span style="color: #ef4444;">&#10084;</span> by <a href="https://smartqix.com" target="_blank" rel="noopener noreferrer" style="color: #fb923c; font-weight: 600; text-decoration: none;">Smartqix</a>
                </p>
            </div>
        </div>
    </footer>

    {{-- Toast notification container --}}
    <div id="vendor-toast-container" class="vendor-toast-container"></div>

    <script>
        // Global toast notification function
        window.vendorToast = function(type, message, duration = 4000) {
            const container = document.getElementById('vendor-toast-container');
            if (!container) return;

            const icons = {
                success: '<svg class="vendor-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                error: '<svg class="vendor-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                info: '<svg class="vendor-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            };

            const toast = document.createElement('div');
            toast.className = `vendor-toast toast-${type}`;
            toast.innerHTML = `
                ${icons[type] || icons.info}
                <span class="vendor-toast-message">${message}</span>
                <svg class="vendor-toast-close" onclick="this.parentElement.classList.add('vendor-toast-out'); setTimeout(() => this.parentElement.remove(), 200)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.add('vendor-toast-out');
                    setTimeout(() => toast.remove(), 200);
                }
            }, duration);
        };

        // PWA Service Worker registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/vendor-sw.js', { scope: '/vendor/' })
                .catch(() => {});
        }

        // PWA Install prompt
        let deferredPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const btn = document.getElementById('pwa-install-btn');
            if (btn) btn.classList.remove('hidden');
        });

        // Vendor layout Alpine component
        function vendorLayout() {
            return {
                mobileMenuOpen: false,
                vendorName: '...',
                vendorInitial: '?',

                init() {
                    this.loadVendorInfo();
                },

                async loadVendorInfo() {
                    const token = localStorage.getItem('parcelman_vendor_token');
                    if (!token) return;

                    try {
                        const response = await fetch('/api/v1/vendor/profile', {
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${token}`,
                            },
                        });
                        const data = await response.json();
                        if (data?.success && data?.data?.user) {
                            this.vendorName = data.data.user.name || 'Vendor';
                            this.vendorInitial = (data.data.user.name || 'V').charAt(0).toUpperCase();
                        }
                    } catch {}
                },

                installPwa() {
                    if (!deferredPrompt) return;
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(() => {
                        deferredPrompt = null;
                        const btn = document.getElementById('pwa-install-btn');
                        if (btn) btn.classList.add('hidden');
                    });
                },

                async vendorLogout() {
                    try {
                        const token = localStorage.getItem('parcelman_vendor_token');
                        if (token) {
                            await fetch('/api/v1/auth/vendor/logout', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Authorization': `Bearer ${token}`,
                                },
                            });
                        }
                    } catch {}
                    localStorage.removeItem('parcelman_vendor_token');
                    window.location.href = '/vendor/login';
                },
            };
        }

        // Register the layout component
        document.addEventListener('alpine:init', () => {
            if (window.Alpine) {
                window.Alpine.data('vendorLayout', vendorLayout);
            }
        });
    </script>
</body>
</html>
