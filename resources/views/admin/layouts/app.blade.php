<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Parcelman Express</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 text-sm antialiased" x-data="{ sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', sidebarMobileOpen: false }" x-init="$watch('sidebarCollapsed', val => localStorage.setItem('sidebarCollapsed', val))">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-[#0f172a] transition-all duration-300 ease-in-out flex flex-col sidebar-shadow"
            :class="[
                sidebarCollapsed ? 'w-[72px]' : 'w-[260px]',
                sidebarMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center h-[60px] transition-all duration-300" :class="sidebarCollapsed ? 'px-4 justify-center' : 'px-5'">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/25 ring-1 ring-white/10">
                    <img src="{{ asset('logo.png') }}" alt="Parcelman" class="h-6 w-6 object-contain">
                </div>
                <div class="ml-3 overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">
                    <span class="text-white text-[15px] font-bold tracking-tight whitespace-nowrap">Parcelman</span>
                    <span class="block text-primary-400/70 text-[10px] font-semibold uppercase tracking-[0.15em] whitespace-nowrap">Admin Portal</span>
                </div>
            </div>
            <div class="mx-4 h-px bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto sidebar-nav py-4 px-3">
                <!-- Dashboard -->
                @hasPermission('dashboard.view')
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.dashboard') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Dashboard</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Dashboard</span>
                    </template>
                </a>
                @endhasPermission

                <!-- OPERATIONS Section -->
                <div class="mt-3 mb-1 transition-all duration-300" :class="sidebarCollapsed ? 'px-0' : 'px-3'">
                    <div class="section-label flex items-center gap-3">
                        <span x-show="!sidebarCollapsed" class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.12em] whitespace-nowrap">Operations</span>
                        <span x-show="sidebarCollapsed" x-cloak class="block w-8 h-[2px] bg-gradient-to-r from-slate-600 to-transparent mx-auto rounded-full"></span>
                    </div>
                </div>

                @hasPermission('vendors.view')
                <a href="{{ route('admin.vendors.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.vendors.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Vendors</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Vendors</span>
                    </template>
                </a>
                @endhasPermission

                @hasPermission('drivers.view')
                <a href="{{ route('admin.drivers.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.drivers.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Drivers</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Drivers</span>
                    </template>
                </a>
                @endhasPermission

                @hasPermission('shipments.view')
                <a href="{{ route('admin.shipments.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.shipments.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Parcels</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Parcels</span>
                    </template>
                </a>
                @endhasPermission

                @hasPermission('shipments.view')
                <a href="{{ route('admin.package-tracking.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.package-tracking.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Package Tracking</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Package Tracking</span>
                    </template>
                </a>
                @endhasPermission

                <!-- FINANCE Section -->
                <div class="mt-3 mb-1 transition-all duration-300" :class="sidebarCollapsed ? 'px-0' : 'px-3'">
                    <div class="section-label flex items-center gap-3">
                        <span x-show="!sidebarCollapsed" class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.12em] whitespace-nowrap">Finance</span>
                        <span x-show="sidebarCollapsed" x-cloak class="block w-8 h-[2px] bg-gradient-to-r from-slate-600 to-transparent mx-auto rounded-full"></span>
                    </div>
                </div>

                @hasPermission('invoices.view')
                <a href="{{ route('admin.invoices.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.invoices.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Invoices</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Invoices</span>
                    </template>
                </a>
                @endhasPermission

                <!-- SYSTEM Section -->
                <div class="mt-3 mb-1 transition-all duration-300" :class="sidebarCollapsed ? 'px-0' : 'px-3'">
                    <div class="section-label flex items-center gap-3">
                        <span x-show="!sidebarCollapsed" class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.12em] whitespace-nowrap">System</span>
                        <span x-show="sidebarCollapsed" x-cloak class="block w-8 h-[2px] bg-gradient-to-r from-slate-600 to-transparent mx-auto rounded-full"></span>
                    </div>
                </div>

                @php
                    $canSeeRoles = Auth::guard('admin')->user()->hasPermission('roles.view');
                    $isWarehouseRoleContext = request()->routeIs('admin.roles.warehouse.*')
                        || (request()->routeIs('admin.roles.*') && request()->query('scope') === 'warehouse');
                    $canSeeUsers = Auth::guard('admin')->user()->hasPermission('users.view');
                    $systemRolesActive = request()->routeIs('admin.roles.*') && !$isWarehouseRoleContext;
                    $userMgmtActive = request()->routeIs('admin.admins.*') || $systemRolesActive;
                @endphp

                @if($canSeeUsers || $canSeeRoles)
                <div x-data="{ expanded: {{ $userMgmtActive ? 'true' : 'false' }} }" class="relative">
                    <button @click="expanded = !expanded"
                            class="summary-item nav-item relative w-full flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ $userMgmtActive ? 'active text-white' : '' }} cursor-pointer"
                            :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                        <div class="nav-icon-wrap">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">User Management</span>
                        <span x-show="!sidebarCollapsed"
                              class="ml-auto flex items-center justify-center w-5 h-5 rounded-md text-slate-400 transition-all duration-200">
                            <svg class="w-4 h-4 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                        <template x-if="sidebarCollapsed">
                            <span class="sidebar-tooltip">User Management</span>
                        </template>
                    </button>

                    <div x-show="expanded && !sidebarCollapsed"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mt-1 ml-5 pl-4 border-l border-white/10 space-y-1">
                        @if($canSeeUsers)
                        <a href="{{ route('admin.admins.index') }}"
                           class="flex items-center gap-2 py-1.5 px-3 rounded-md text-slate-400 hover:text-white hover:bg-white/5 transition-all {{ request()->routeIs('admin.admins.*') ? 'text-white bg-white/5' : '' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('admin.admins.*') ? 'bg-primary-400' : 'bg-slate-500' }}"></span>
                            <span class="text-[12px] font-medium">Users</span>
                        </a>
                        @endif
                        @if($canSeeRoles)
                        <a href="{{ route('admin.roles.index') }}"
                           class="flex items-center gap-2 py-1.5 px-3 rounded-md text-slate-400 hover:text-white hover:bg-white/5 transition-all {{ $systemRolesActive ? 'text-white bg-white/5' : '' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $systemRolesActive ? 'bg-primary-400' : 'bg-slate-500' }}"></span>
                            <span class="text-[12px] font-medium">System Roles</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                @hasPermission('settings.view')
                <a href="{{ route('admin.marketing.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.marketing.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Marketing</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Marketing Broadcasts</span>
                    </template>
                </a>
                @endhasPermission

                @hasPermission('settings.view')
                <a href="{{ route('admin.settings.index') }}"
                   class="nav-item relative flex items-center py-1.5 rounded-lg text-slate-400 hover:text-white transition-all {{ request()->routeIs('admin.settings.*') ? 'active text-white' : '' }}"
                   :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
                    <div class="nav-icon-wrap">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-[12px] font-medium ml-2.5 whitespace-nowrap transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0 ml-0 hidden' : 'w-auto opacity-100'">Settings</span>
                    <template x-if="sidebarCollapsed">
                        <span class="sidebar-tooltip">Settings</span>
                    </template>
                </a>
                @endhasPermission
            </nav>

        </aside>

        <!-- Overlay for mobile -->
        <div
            x-show="sidebarMobileOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarMobileOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        ></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 transition-[margin-left] duration-300" :class="sidebarCollapsed ? 'lg:ml-[72px]' : 'lg:ml-[260px]'" x-data="{ darkMode: false, createOpen: false }">
            <!-- Top Header -->
            <header class="admin-header h-[56px] flex items-center px-5 sticky top-0 z-30">
                <!-- Left Section: Toggle + Breadcrumb -->
                <div class="flex items-center">
                    <!-- Sidebar Toggle Button -->
                    <button @click="sidebarCollapsed = !sidebarCollapsed; sidebarMobileOpen = !sidebarMobileOpen"
                            class="flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100/80 hover:bg-slate-200/80 text-slate-500 hover:text-slate-700 transition-all duration-200"
                            title="Toggle Sidebar">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                        </svg>
                    </button>

                    <!-- Breadcrumb -->
                    <nav class="hidden sm:flex items-center text-[13px] ml-5">
                        <span class="text-slate-400 font-medium">@yield('breadcrumb-parent', 'Dashboard')</span>
                        @hasSection('breadcrumb-current')
                        <svg class="w-3.5 h-3.5 mx-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-slate-800 font-semibold">@yield('breadcrumb-current')</span>
                        @endif
                    </nav>
                </div>

                <!-- Search (Center) -->
                <div class="hidden md:flex flex-1 justify-center px-8"
                     x-data="{
                         query: '',
                         results: {},
                         searching: false,
                         open: false,
                         _timer: null,
                         search() {
                             clearTimeout(this._timer);
                             if (this.query.length < 2) { this.results = {}; this.open = false; return; }
                             this._timer = setTimeout(async () => {
                                 this.searching = true;
                                 try {
                                     const r = await fetch('/admin/search?q=' + encodeURIComponent(this.query), {
                                         headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                     });
                                     this.results = (await r.json()).data || {};
                                     this.open = true;
                                 } catch(e) {}
                                 this.searching = false;
                             }, 300);
                         },
                         close() { this.open = false; this.query = ''; this.results = {}; }
                     }"
                     @keydown.escape.window="close()"
                     @click.away="open = false">
                    <div class="relative w-full max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg x-show="!searching" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <svg x-show="searching" x-cloak class="w-4 h-4 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                        <input type="text"
                               x-model="query"
                               @input="search()"
                               @focus="if(query.length >= 2) open = true"
                               placeholder="Search shipments, vendors, drivers..."
                               class="w-full h-9 pl-10 pr-16 text-[13px] bg-slate-50/80 border border-slate-200/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300 focus:bg-white transition-all placeholder-slate-400">
                        <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                            <span class="text-[10px] text-slate-400 bg-white px-1.5 py-0.5 rounded-md border border-slate-200/60 font-medium tracking-wide">⌘K</span>
                        </div>

                        {{-- Results Dropdown --}}
                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute top-full left-0 right-0 mt-2 bg-white/95 backdrop-blur-xl rounded-xl shadow-xl shadow-slate-200/60 border border-slate-100/80 z-50 max-h-[420px] overflow-y-auto">

                            {{-- Shipments --}}
                            <template x-if="results.shipments && results.shipments.length">
                                <div>
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Shipments</span>
                                    </div>
                                    <template x-for="item in results.shipments" :key="item.id">
                                        <a :href="item.url" @click="close()" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 transition-colors">
                                            <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-semibold text-slate-800 truncate" x-text="item.label"></p>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="item.sub"></p>
                                            </div>
                                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap" x-text="item.status"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            {{-- Vendors --}}
                            <template x-if="results.vendors && results.vendors.length">
                                <div>
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Vendors</span>
                                    </div>
                                    <template x-for="item in results.vendors" :key="item.id">
                                        <a :href="item.url" @click="close()" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 transition-colors">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-semibold text-slate-800 truncate" x-text="item.label"></p>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="item.sub"></p>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            {{-- Drivers --}}
                            <template x-if="results.drivers && results.drivers.length">
                                <div>
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Drivers</span>
                                    </div>
                                    <template x-for="item in results.drivers" :key="item.id">
                                        <a :href="item.url" @click="close()" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 transition-colors">
                                            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-semibold text-slate-800 truncate" x-text="item.label"></p>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="item.sub"></p>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            {{-- Invoices --}}
                            <template x-if="results.invoices && results.invoices.length">
                                <div>
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Invoices</span>
                                    </div>
                                    <template x-for="item in results.invoices" :key="item.id">
                                        <a :href="item.url" @click="close()" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 transition-colors">
                                            <div class="w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-semibold text-slate-800 truncate" x-text="item.label"></p>
                                                <p class="text-[11px] text-slate-400 truncate" x-text="item.sub"></p>
                                            </div>
                                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap" x-text="item.status"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            {{-- Empty state --}}
                            <template x-if="open && !searching && !results.shipments?.length && !results.vendors?.length && !results.drivers?.length && !results.invoices?.length">
                                <div class="px-4 py-6 text-center">
                                    <p class="text-[12px] text-slate-400">No results for "<span x-text="query"></span>"</p>
                                </div>
                            </template>

                            <div class="border-t border-slate-100/80 mt-1 px-3 py-2">
                                <p class="text-[10px] text-slate-400">Press <kbd class="bg-slate-100 px-1 rounded text-[10px]">Esc</kbd> to close</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Section -->
                <div class="flex items-center gap-1.5">
                    <!-- Date & Time -->
                    <div class="hidden lg:flex items-center text-[12px] text-slate-500 px-3 py-1.5 rounded-xl bg-slate-50/80 border border-slate-100/80">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-data x-text="new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })"></span>
                        <span class="mx-1.5 text-slate-300">|</span>
                        <span x-data x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }), 1000)"></span>
                    </div>

                    <!-- Create Button with Dropdown -->
                    <div class="relative">
                        <button @click="createOpen = !createOpen"
                                class="flex items-center h-9 px-4 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-700 hover:to-slate-800 text-white text-[12px] font-semibold rounded-xl transition-all shadow-sm shadow-slate-900/20 hover:shadow-md hover:shadow-slate-900/25">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create
                            <svg class="w-3 h-3 ml-1.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="createOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            @click.away="createOpen = false"
                            class="absolute right-0 mt-2 w-48 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg shadow-slate-200/60 border border-slate-100/80 py-1.5 z-50"
                        >
                            @hasPermission('vendors.create')
                            <a href="{{ route('admin.vendors.index') }}" class="flex items-center px-3 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                New Vendor
                            </a>
                            @endhasPermission
                            @hasPermission('drivers.create')
                            <a href="{{ route('admin.drivers.index') }}" class="flex items-center px-3 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                New Driver
                            </a>
                            @endhasPermission
                            @hasPermission('shipments.assign_driver')
                            <a href="{{ route('admin.shipments.index') }}?status=submitted" class="flex items-center px-3 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                </svg>
                                Assign Pickup
                            </a>
                            @endhasPermission
                            @hasPermission('users.create')
                            <a href="{{ route('admin.admins.create') }}" class="flex items-center px-3 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                New User
                            </a>
                            @endhasPermission
                            @hasPermission('settings.view')
                            <a href="{{ route('admin.locations.index') }}" class="flex items-center px-3 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                New Location
                            </a>
                            @endhasPermission
                        </div>
                    </div>

                    <!-- Notifications -->
                    <button class="relative flex items-center justify-center w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100/80 transition-all">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white/80"></span>
                    </button>

                    <!-- Separator -->
                    <div class="w-px h-6 bg-slate-200/60 mx-1"></div>

                    <!-- User Avatar Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 py-1 px-1.5 rounded-xl hover:bg-slate-50/80 transition-all">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-white text-[12px] font-bold shadow-sm ring-1 ring-slate-200/50">
                                {{ substr(Auth::guard('admin')->user()->name, 0, 1) }}
                            </div>
                            <div class="hidden xl:block text-left mr-1">
                                <p class="text-[12px] font-semibold text-slate-700 leading-tight">{{ Auth::guard('admin')->user()->name }}</p>
                                <p class="text-[10px] text-slate-400 leading-tight">{{ Auth::guard('admin')->user()->roles->first()?->name ?? 'Admin' }}</p>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            @click.away="open = false"
                            class="absolute right-0 mt-2 w-52 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg shadow-slate-200/60 border border-slate-100/80 py-1.5 z-50"
                        >
                            <div class="px-3.5 py-2.5 border-b border-slate-100/80">
                                <p class="text-[13px] font-semibold text-slate-800">{{ Auth::guard('admin')->user()->name }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ Auth::guard('admin')->user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('admin.profile.edit') }}" class="flex items-center px-3.5 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                    <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    My Profile
                                </a>
                                <a href="#" class="flex items-center px-3.5 py-2 text-[12px] text-slate-600 hover:bg-slate-50/80 hover:text-slate-900 transition-colors rounded-lg mx-1">
                                    <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>
                            </div>
                            <div class="border-t border-slate-100/80 pt-1 mt-0.5">
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center w-[calc(100%-0.5rem)] mx-1 px-3.5 py-2 text-[12px] text-rose-600 hover:bg-rose-50/80 transition-colors rounded-lg">
                                        <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <!-- Page Content -->
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
    <script>
        console.log('[FCM] push_enabled=' + @json((bool)$pushEnabled) + ' api_key_set=' + @json(!empty($fcmApiKey)));
    </script>
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
