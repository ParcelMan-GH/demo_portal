<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Warehouse Portal') - Parcelman Express</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/pages/warehouse-portal.css', 'resources/js/warehouse/app.js'])
    @stack('styles')
</head>
<body class="warehouse-portal bg-slate-50 text-sm antialiased" x-data="{ sidebarOpen: false }">
    @php
        $authUser = Auth::guard('admin')->user();
        $warehouse = $authUser?->warehouse;

        $canDashboard = $authUser?->hasPermission('warehouse.dashboard.view');
        $canUsers = $authUser?->hasPermission('warehouse.users.view');
        $canReceiving = $authUser?->hasPermission('warehouse.receiving.manage');
        $canItems = $authUser?->hasPermission('warehouse.items.scan');
        $canSorting = $authUser?->hasPermission('warehouse.sorting.manage');
        $canManifest = $authUser?->hasPermission('warehouse.manifest.manage');
        $canTransportAssign = $authUser?->hasPermission('warehouse.transport.assign');
        $canDeliveryAssign = $authUser?->hasPermission('warehouse.delivery.assign');
    @endphp

    <div class="min-h-screen flex">
        {{-- ==================== SIDEBAR ==================== --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 w-[260px] bg-gradient-to-b from-[#0b1220] via-[#111a2d] to-[#0b1220] transition-transform duration-200 flex flex-col wh-sidebar-shadow"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            {{-- Logo --}}
            <div class="flex items-center h-[60px] px-5">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/25 ring-1 ring-white/10">
                    <img src="{{ asset('logo.png') }}" alt="Parcelman" class="h-6 w-6 object-contain">
                </div>
                <div class="ml-3 min-w-0">
                    <span class="text-white text-[15px] font-bold tracking-tight block truncate">Warehouse</span>
                    <span class="block text-teal-400/70 text-[10px] font-semibold uppercase tracking-[0.15em] truncate">Parcelman Express</span>
                </div>
            </div>
            <div class="mx-4 h-px bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto warehouse-sidebar-scroll py-4 px-3">
                @if($canDashboard)
                    <a href="{{ route('warehouse.dashboard') }}"
                       class="warehouse-nav-link {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                        <div class="wh-nav-icon">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                        </div>
                        <span class="text-[13px] font-medium">Dashboard</span>
                    </a>
                @endif

                @if($canUsers)
                    <a href="{{ route('warehouse.users.index') }}"
                       class="warehouse-nav-link {{ request()->routeIs('warehouse.users.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                        <div class="wh-nav-icon">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <span class="text-[13px] font-medium">Users</span>
                    </a>
                @endif

                @if($canReceiving || $canItems || $canSorting || $canManifest || $canTransportAssign || $canDeliveryAssign)
                    <div class="pt-4 mt-3">
                        <div class="flex items-center gap-2 px-3 mb-2">
                            <span class="text-[10px] uppercase tracking-[0.14em] text-slate-500 font-semibold">Shipments</span>
                            <div class="flex-1 h-px bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                        </div>

                        @if($canReceiving)
                            <a href="{{ route('warehouse.receipts.pending.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.receipts.pending.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Pending Receipts</span>
                            </a>

                            <a href="{{ route('warehouse.pickups.received.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.pickups.received.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Received Pickups</span>
                            </a>
                        @endif

                        @if($canItems)
                            <a href="{{ route('warehouse.items.received.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.items.received.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Received Items</span>
                            </a>
                        @endif

                        @if($canSorting)
                            <a href="{{ route('warehouse.sorting.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.sorting.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7h16M4 12h10M4 17h7m9 0h-3m6-5h-3m3-5h-6"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Sorting</span>
                            </a>
                        @endif

                        @if($canManifest || $canTransportAssign)
                            <a href="{{ route('warehouse.manifests.transport.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.manifests.transport.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 17H7a2 2 0 01-2-2V9a2 2 0 012-2h2m0 10h6m-6 0V7m6 10h2a2 2 0 002-2V9a2 2 0 00-2-2h-2m0 10V7m-6 3h6"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Transport Manifests</span>
                            </a>

                            <a href="{{ route('warehouse.manifests.incoming.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.manifests.incoming.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7h16M4 12h16M4 17h16"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Incoming Manifests</span>
                            </a>
                        @endif

                        @if($canDeliveryAssign)
                            <a href="{{ route('warehouse.deliveries.runs.index') }}"
                               class="warehouse-nav-link {{ request()->routeIs('warehouse.deliveries.runs.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <div class="wh-nav-icon">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 19V6l12-2v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-2c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-2"/>
                                    </svg>
                                </div>
                                <span class="text-[13px] font-medium">Delivery Runs</span>
                            </a>
                        @endif
                    </div>
                @endif
            </nav>

        </aside>

        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        ></div>

        {{-- ==================== MAIN CONTENT ==================== --}}
        <div class="flex-1 min-w-0 lg:ml-[260px] flex flex-col" x-data="{ userMenuOpen: false }">

            {{-- Header --}}
            <header class="wh-header sticky top-0 z-30 h-[56px] flex items-center px-4 lg:px-6">
                {{-- Left: Toggle + Breadcrumb --}}
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button"
                            @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100/80 hover:bg-slate-200/80 text-slate-500 hover:text-slate-700 transition-all">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                        </svg>
                    </button>

                    {{-- Breadcrumb --}}
                    <nav class="flex items-center text-[13px]">
                        <span class="text-slate-400 font-medium">@yield('breadcrumb-parent', 'Warehouse')</span>
                        @hasSection('page-title')
                        <svg class="w-3.5 h-3.5 mx-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-slate-800 font-semibold">@yield('page-title', 'Dashboard')</span>
                        @endif
                    </nav>
                </div>

                {{-- Center: Search --}}
                <div class="hidden md:flex flex-1 justify-center px-8">
                    <div class="relative w-full max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text"
                               placeholder="Search shipments, receipts..."
                               class="w-full h-9 pl-10 pr-16 text-[13px] bg-slate-50/80 border border-slate-200/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-300 focus:bg-white transition-all placeholder-slate-400">
                        <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                            <span class="text-[10px] text-slate-400 bg-white px-1.5 py-0.5 rounded-md border border-slate-200/60 font-medium tracking-wide">⌘K</span>
                        </div>
                    </div>
                </div>

                {{-- Right section --}}
                <div class="flex items-center gap-1.5">
                    {{-- Date & Time --}}
                    <div class="hidden lg:flex items-center text-[12px] text-slate-500 px-3 py-1.5 rounded-xl bg-slate-50/80 border border-slate-100/80">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-data x-text="new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })"></span>
                        <span class="mx-1.5 text-slate-300">|</span>
                        <span x-data x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }), 1000)"></span>
                    </div>

                    {{-- Notifications --}}
                    <button class="relative flex items-center justify-center w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100/80 transition-all">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-teal-500 rounded-full ring-2 ring-white/80"></span>
                    </button>

                    {{-- Separator --}}
                    <div class="w-px h-6 bg-slate-200/60 mx-1"></div>

                    {{-- Warehouse badge --}}
                    <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-teal-50/60 border border-teal-100/80">
                        <div class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></div>
                        <span class="text-[11px] font-semibold text-teal-700">{{ $warehouse?->name ?? 'Warehouse' }}</span>
                    </div>

                    {{-- User Avatar Dropdown --}}
                    <div class="relative">
                        <button @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 py-1 px-1.5 rounded-xl hover:bg-slate-50/80 transition-all">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center text-white text-[12px] font-bold shadow-sm ring-1 ring-slate-200/50">
                                {{ substr($authUser?->name ?? 'W', 0, 1) }}
                            </div>
                            <div class="hidden lg:block text-left mr-1">
                                <p class="text-[12px] font-semibold text-slate-700 leading-tight">{{ $authUser?->name }}</p>
                                <p class="text-[10px] text-slate-400 leading-tight">{{ $authUser?->roles->first()?->name ?? 'Warehouse Staff' }}</p>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Dropdown --}}
                        <div
                            x-show="userMenuOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            @click.away="userMenuOpen = false"
                            class="absolute right-0 mt-2 w-56 wh-dropdown py-1.5 z-50"
                        >
                            {{-- User info --}}
                            <div class="px-4 py-2.5 border-b border-slate-100/80">
                                <p class="text-[13px] font-semibold text-slate-800">{{ $authUser?->name }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $authUser?->email }}</p>
                            </div>

                            {{-- Warehouse info --}}
                            <div class="px-4 py-2 border-b border-slate-100/80">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-teal-500"></div>
                                    <span class="text-[11px] font-medium text-slate-500">{{ $warehouse?->name ?? 'Warehouse' }}</span>
                                </div>
                            </div>

                            {{-- Menu items --}}
                            <div class="py-1">
                                <a href="#" class="wh-dropdown-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    My Profile
                                </a>
                                <a href="#" class="wh-dropdown-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>
                            </div>

                            {{-- Logout --}}
                            <div class="border-t border-slate-100/80 pt-1 mt-0.5">
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="wh-dropdown-item danger w-full">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-4 lg:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="admin-toast-container"
         class="fixed top-5 right-5 z-[120] flex w-full max-w-sm flex-col gap-3 pointer-events-none"
         data-flash-success="{{ session('success') }}"
         data-flash-error="{{ session('error') }}"></div>
    @stack('scripts')
</body>
</html>
