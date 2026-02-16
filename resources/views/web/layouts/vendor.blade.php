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

                    {{-- User avatar --}}
                    <div class="flex items-center gap-2 rounded-full border border-gray-200 py-1 pl-1 pr-3 hover:bg-gray-50 transition cursor-default">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700" x-text="vendorInitial"></div>
                        <span class="hidden text-sm font-medium text-slate-700 sm:inline" x-text="vendorName"></span>
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
