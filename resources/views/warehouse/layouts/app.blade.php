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
<body class="warehouse-portal bg-slate-50 text-sm antialiased"
      x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('whSidebarCollapsed') === 'true' }" x-init="$watch('sidebarCollapsed', val => localStorage.setItem('whSidebarCollapsed', val))">
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
        $canContacts = $authUser?->hasPermission('warehouse.contacts.manage');
    @endphp

    <div class="min-h-screen flex">
        {{-- ==================== SIDEBAR ==================== --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 wh-sidebar transition-all duration-300 ease-in-out flex flex-col"
            :class="[
                sidebarCollapsed ? 'w-[72px]' : 'w-[280px]',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            {{-- Logo Area --}}
            <div class="flex items-center h-[72px] transition-all duration-300" :class="sidebarCollapsed ? 'px-3.5 justify-center' : 'px-5'">
                <div class="flex-shrink-0 rounded-xl bg-white shadow-sm overflow-hidden flex items-center justify-center" style="width:44px;height:44px;">
                    <img src="{{ asset('logo.png') }}" alt="Parcelman" style="transform:scale(1.8);transform-origin:center 40%;">
                </div>
                <div class="ml-3 min-w-0 overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0' : 'w-auto opacity-100'">
                    <span class="text-white text-[15px] font-extrabold tracking-tight block whitespace-nowrap">Parcelman Express</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.15em] whitespace-nowrap text-orange-400/60">Warehouse Portal</span>
                </div>
            </div>

            {{-- Divider --}}
            <div class="h-px transition-all duration-300" :class="sidebarCollapsed ? 'mx-3' : 'mx-5'" style="background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);"></div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto warehouse-sidebar-scroll pt-4 pb-6 transition-all duration-300" :class="sidebarCollapsed ? 'px-2' : 'px-4'">

                @php
                    // Helper: build dynamic classes for nav links
                    $linkCls = "wh-nav-link relative";
                @endphp

                {{-- Dashboard --}}
                @if($canDashboard)
                <a href="{{ route('warehouse.dashboard') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Dashboard</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Dashboard</span></template>
                </a>
                @endif

                {{-- ═══════════ OPERATIONS ═══════════ --}}
                @if($canReceiving || $canItems || $canSorting)
                <div class="wh-nav-section-label mt-6" x-show="!sidebarCollapsed">Operations</div>
                <div class="mt-4 mx-auto w-6 h-px" style="background:rgba(255,255,255,0.1);" x-show="sidebarCollapsed" x-cloak></div>

                @if($canReceiving)
                <a href="{{ route('warehouse.walkin.create') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.walkin.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Walk-in</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Walk-in</span></template>
                </a>
                <a href="{{ route('warehouse.receipts.pending.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.receipts.pending.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Pending Receipts</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Pending Receipts</span></template>
                </a>
                <a href="{{ route('warehouse.pickups.received.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.pickups.received.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Received Pickups</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Received Pickups</span></template>
                </a>
                @endif
                @if($canItems)
                <a href="{{ route('warehouse.items.received.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.items.received.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Received Items</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Received Items</span></template>
                </a>
                @endif
                @if($canSorting)
                <a href="{{ route('warehouse.sorting.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.sorting.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Sorting</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Sorting</span></template>
                </a>
                @endif
                @if($canContacts)
                <a href="{{ route('warehouse.contacts.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.contacts.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Contact Queue</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Contact Queue</span></template>
                </a>
                @endif
                @endif

                {{-- ═══════════ TRANSPORT ═══════════ --}}
                @if($canManifest || $canTransportAssign)
                <div class="wh-nav-section-label mt-6" x-show="!sidebarCollapsed">Transport</div>
                <div class="mt-4 mx-auto w-6 h-px" style="background:rgba(255,255,255,0.1);" x-show="sidebarCollapsed" x-cloak></div>

                <a href="{{ route('warehouse.manifests.transport.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.manifests.transport.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Transport Manifests</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Transport Manifests</span></template>
                </a>
                <a href="{{ route('warehouse.manifests.incoming.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.manifests.incoming.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Incoming Manifests</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Incoming Manifests</span></template>
                </a>
                @endif

                {{-- ═══════════ DELIVERY ═══════════ --}}
                @if($canDeliveryAssign || $canReceiving)
                <div class="wh-nav-section-label mt-6" x-show="!sidebarCollapsed">Delivery</div>
                <div class="mt-4 mx-auto w-6 h-px" style="background:rgba(255,255,255,0.1);" x-show="sidebarCollapsed" x-cloak></div>

                @if($canDeliveryAssign)
                <a href="{{ route('warehouse.deliveries.runs.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.deliveries.runs.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Delivery Runs</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Delivery Runs</span></template>
                </a>
                <a href="{{ route('warehouse.deliveries.pending-confirmations') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.deliveries.pending-confirmations*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Pending Confirmations</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Pending Confirmations</span></template>
                </a>
                @endif
                @if($canReceiving)
                <a href="{{ route('warehouse.collections.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.collections.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Collections</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Collections</span></template>
                </a>
                @endif
                @endif

                {{-- ═══════════ SYSTEM ═══════════ --}}
                @if($canUsers)
                <div class="wh-nav-section-label mt-6" x-show="!sidebarCollapsed">System</div>
                <div class="mt-4 mx-auto w-6 h-px" style="background:rgba(255,255,255,0.1);" x-show="sidebarCollapsed" x-cloak></div>

                <a href="{{ route('warehouse.users.index') }}" class="{{ $linkCls }} {{ request()->routeIs('warehouse.users.*') ? 'active' : '' }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <div class="wh-nav-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                    <span class="wh-nav-text transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Users</span>
                    <template x-if="sidebarCollapsed"><span class="wh-tooltip">Users</span></template>
                </a>
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
            @@click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        ></div>

        {{-- ==================== MAIN CONTENT ==================== --}}
        <div class="flex-1 min-w-0 flex flex-col transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-[72px]' : 'lg:ml-[280px]'" x-data="{ userMenuOpen: false }">

            {{-- Header --}}
            <header class="wh-header sticky top-0 z-30 h-[56px] flex items-center px-4 lg:px-6">
                {{-- Left: Toggle + Breadcrumb --}}
                <div class="flex items-center gap-2 min-w-0">
                    {{-- Mobile hamburger --}}
                    <button type="button"
                            @@click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100/80 hover:bg-slate-200/80 text-slate-500 hover:text-slate-700 transition-all">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                        </svg>
                    </button>

                    {{-- Sidebar collapse toggle (desktop) --}}
                    <button type="button"
                            @@click="sidebarCollapsed = !sidebarCollapsed"
                            class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200/60 bg-white shadow-sm hover:shadow hover:border-slate-300/80 text-slate-400 hover:text-slate-600 transition-all active:scale-95"
                            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <div class="relative w-[14px] h-[10px] flex flex-col justify-between">
                            <span class="block h-[1.5px] rounded-full bg-current transition-all duration-300" :class="sidebarCollapsed ? 'w-full' : 'w-full'"></span>
                            <span class="block h-[1.5px] rounded-full bg-current transition-all duration-300" :class="sidebarCollapsed ? 'w-full' : 'w-3/4'"></span>
                            <span class="block h-[1.5px] rounded-full bg-current transition-all duration-300" :class="sidebarCollapsed ? 'w-full' : 'w-1/2'"></span>
                        </div>
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
                               class="w-full h-9 pl-10 pr-16 text-[13px] bg-slate-50/80 border border-slate-200/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 focus:bg-white transition-all placeholder-slate-400">
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
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-orange-600 rounded-full ring-2 ring-white/80"></span>
                    </button>

                    {{-- Separator --}}
                    <div class="w-px h-6 bg-slate-200/60 mx-1"></div>

                    {{-- Warehouse badge --}}
                    <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-xl border bg-slate-50/80 border-slate-200/60">
                        <div class="w-2 h-2 rounded-full animate-pulse bg-orange-500"></div>
                        <span class="text-[11px] font-semibold text-slate-600">{{ $warehouse?->name ?? 'Warehouse' }}</span>
                    </div>

                    {{-- User Avatar Dropdown --}}
                    <div class="relative">
                        <button @@click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 py-1 px-1.5 rounded-xl hover:bg-slate-50/80 transition-all">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[12px] font-bold shadow-sm ring-1 ring-slate-200/50 bg-gradient-to-br from-slate-700 to-slate-900">
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
                            @@click.away="userMenuOpen = false"
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
                                    <div class="w-2 h-2 rounded-full bg-orange-500"></div>
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
    @php
        $pushEnabled = \App\Models\PlatformSetting::getValue('push_notifications_enabled');
        $fcmApiKey   = \App\Models\PlatformSetting::getValue('firebase_web_api_key');
    @endphp
    @if($pushEnabled && $fcmApiKey)
    <script>
        window.__fcmConfig = {
            apiKey:            @json(\App\Models\PlatformSetting::getValue('firebase_web_api_key')),
            authDomain:        @json(\App\Models\PlatformSetting::getValue('firebase_auth_domain')),
            projectId:         @json(\App\Models\PlatformSetting::getValue('firebase_project_id')),
            messagingSenderId: @json(\App\Models\PlatformSetting::getValue('firebase_messaging_sender_id')),
            appId:             @json(\App\Models\PlatformSetting::getValue('firebase_app_id')),
            vapidKey:          @json(\App\Models\PlatformSetting::getValue('firebase_vapid_key')),
        };
        window.__fcmEndpoint = {
            url: '{{ route('admin.fcm-token') }}',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        };
    </script>
    @vite(['resources/js/web/firebase-push.js'])
    @endif
</body>
</html>
