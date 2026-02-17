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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pages/warehouse-portal.css', 'resources/js/warehouse/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 text-sm antialiased" x-data="{ sidebarOpen: false }">
    @php
        $authUser = Auth::guard('admin')->user();
        $warehouse = $authUser?->warehouse;

        $canDashboard = $authUser?->hasPermission('warehouse.dashboard.view');
        $canUsers = $authUser?->hasPermission('warehouse.users.view');
        $canReceiving = $authUser?->hasPermission('warehouse.receiving.manage');
        $canItems = $authUser?->hasPermission('warehouse.items.scan');
    @endphp

    <div class="min-h-screen flex">
        <aside class="fixed inset-y-0 left-0 z-50 w-[260px] -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-gradient-to-b from-[#0b1220] via-[#111a2d] to-[#0b1220] border-r border-white/10" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="h-16 border-b border-white/10 px-5 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/25">
                    <img src="{{ asset('logo.png') }}" alt="Parcelman" class="h-6 w-6 object-contain">
                </div>
                <div class="min-w-0">
                    <p class="text-white text-[15px] font-semibold truncate">Warehouse Portal</p>
                    <p class="text-teal-300/80 text-[10px] uppercase tracking-[0.16em] font-semibold truncate">Parcelman</p>
                </div>
            </div>

            <nav class="warehouse-sidebar-scroll h-[calc(100vh-64px)] overflow-y-auto px-3 py-4 space-y-2">
                @if($canDashboard)
                    <a href="{{ route('warehouse.dashboard') }}" class="warehouse-nav-link {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 13h8V3H3v10zm10 8h8V3h-8v18z"/>
                        </svg>
                        <span class="text-[13px] font-medium">Dashboard</span>
                    </a>
                @endif

                @if($canUsers)
                    <a href="{{ route('warehouse.users.index') }}" class="warehouse-nav-link {{ request()->routeIs('warehouse.users.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5V9a2 2 0 00-2-2h-3m-4 13H2V9a2 2 0 012-2h3m8 13V9a4 4 0 10-8 0v11"/>
                        </svg>
                        <span class="text-[13px] font-medium">Users</span>
                    </a>
                @endif

                @if($canReceiving || $canItems)
                    <div class="pt-3 mt-3 border-t border-white/10">
                        <p class="px-3 text-[10px] uppercase tracking-[0.14em] text-slate-500 font-semibold mb-2">Shipments</p>

                        @if($canReceiving)
                            <a href="{{ route('warehouse.receipts.pending.index') }}" class="warehouse-nav-link {{ request()->routeIs('warehouse.receipts.pending.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 00-2 2v12h14V7a2 2 0 00-2-2h-2m-4 0V3m0 2h4m-8 7h8m-8 4h5"/>
                                </svg>
                                <span class="text-[13px] font-medium">Pending Receipts</span>
                            </a>

                            <a href="{{ route('warehouse.pickups.received.index') }}" class="warehouse-nav-link {{ request()->routeIs('warehouse.pickups.received.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                </svg>
                                <span class="text-[13px] font-medium">Received Pickups</span>
                            </a>
                        @endif

                        @if($canItems)
                            <a href="{{ route('warehouse.items.received.index') }}" class="warehouse-nav-link {{ request()->routeIs('warehouse.items.received.*') ? 'active' : '' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7h16M4 12h16M4 17h16"/>
                                </svg>
                                <span class="text-[13px] font-medium">Received Items</span>
                            </a>
                        @endif
                    </div>
                @endif
            </nav>
        </aside>

        <div x-show="sidebarOpen" x-cloak @@click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

        <div class="flex-1 min-w-0 lg:ml-[260px] flex flex-col">
            <header class="sticky top-0 z-30 h-[56px] border-b border-slate-200 bg-white/95 backdrop-blur px-4 lg:px-6 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" @@click="sidebarOpen = !sidebarOpen" class="lg:hidden h-9 w-9 rounded-lg border border-slate-200 text-slate-600 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-500 font-medium uppercase tracking-[0.14em]">Warehouse</p>
                        <h1 class="text-sm lg:text-base font-semibold text-slate-900 truncate">@yield('page-title', $warehouse?->name ?? 'Warehouse')</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:block text-right">
                        <p class="text-[11px] text-slate-500">{{ $warehouse?->name ?? 'Warehouse' }}</p>
                        <p class="text-[12px] font-semibold text-slate-800">{{ $authUser?->name }}</p>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </header>

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
