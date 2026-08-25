<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Back Office') - Parcelman Express</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap" rel="stylesheet">
    @stack('head-scripts')
    @vite(['resources/css/app.css', 'resources/css/pages/warehouse-portal.css', 'resources/js/admin/app.js'])
    @stack('styles')
    
    <style>
        /* Custom scrollbar for a cleaner look */
        .wh-sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .wh-sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .wh-sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .wh-sidebar-scroll:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
    </style>
</head>
<body class="bg-[#FCF9F6] text-sm antialiased text-slate-800"
      x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('whSidebarCollapsed') === 'true' }"
      x-init="$watch('sidebarCollapsed', val => localStorage.setItem('whSidebarCollapsed', val))">
    
    @php
        $authUser = Auth::guard('admin')->user();
        $warehouse = $backOfficeSelectedWarehouse ?? $backOfficeCurrentWarehouse ?? $authUser?->warehouse;
        $warehouseLabel = $backOfficeScopeLabel ?? ($warehouse?->name ?? 'Warehouse');

        // Permissions
        $canDashboard = $authUser?->hasPermission('warehouse.dashboard.view') || $authUser?->hasPermission('dashboard.view');
        $canReceiving = $authUser?->hasPermission('warehouse.receiving.manage') || $authUser?->hasPermission('shipments.view');
        $canItems = $authUser?->hasPermission('warehouse.items.scan') || $authUser?->hasPermission('shipments.view');
        $canSorting = $authUser?->hasPermission('warehouse.sorting.manage') || $authUser?->hasPermission('shipments.view');
        $canManifest = $authUser?->hasPermission('warehouse.manifest.manage') || $authUser?->hasPermission('shipments.view');
        $canDeliveryAssign = $authUser?->hasPermission('warehouse.delivery.assign') || $authUser?->hasPermission('shipments.view');
        $canContacts = $authUser?->hasPermission('warehouse.contacts.manage') || $authUser?->hasPermission('shipments.view');
        $canRecipientPayments = $authUser?->hasPermission('warehouse.recipient_payments.view') || $authUser?->hasPermission('recipient_payments.view');
        $canUsers = $authUser?->hasPermission('warehouse.users.view') || $authUser?->hasPermission('users.view');
        
        $backOfficeAccess = app(\App\Services\BackOfficeAccess::class);
        $canRoles = $authUser ? $backOfficeAccess->canUsePermission($authUser, 'roles.view') : false;
        $canOrders = $authUser ? $backOfficeAccess->canUsePermission($authUser, 'shipments.view') : false;
        $canRiderTeams = $authUser && collect(['drivers.view', 'warehouse.delivery.assign', 'warehouse.manifest.manage'])
                ->contains(fn (string $permission) => $backOfficeAccess->canUsePermission($authUser, $permission));
        $canHqControls = $authUser && collect(['shipments.view', 'vendors.view', 'vendors.manage', 'drivers.view', 'warehouses.view', 'settings.view'])
                ->contains(fn (string $permission) => $backOfficeAccess->canUsePermission($authUser, $permission));

        // Styling Helpers
        $baseLinkCls = "relative flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl font-semibold transition-all duration-200 group mb-1 w-full text-left";
        $activeCls = "bg-[#ea580c] text-white shadow-md shadow-orange-500/20";
        $inactiveCls = "text-slate-500 hover:bg-orange-50 hover:text-orange-600";

        // Active States for Dropdowns
        $isWhActive = request()->routeIs('admin.warehouses.*') || request()->routeIs('admin.locations.*') || request()->routeIs('warehouse.packages.*') || request()->routeIs('warehouse.receipts.pending.*') || request()->routeIs('warehouse.pickups.received.*');
        $isWorkersActive = request()->routeIs('warehouse.users.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.roles.*');
        $isHqActive = request()->routeIs('admin.orders.*') || request()->routeIs('admin.vendors.*');
        $isSettingsActive = request()->routeIs('admin.settings.*');

        $initialDropdown = $isWhActive ? 'warehouses' : ($isWorkersActive ? 'workers' : ($isHqActive ? 'hq' : ($isSettingsActive ? 'settings' : '')));
    @endphp

    <div class="min-h-screen flex">
        
        {{-- ═══ SIDEBAR ═══ --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 bg-[#FCF9F6] border-r border-slate-200/60 transition-all duration-300 ease-in-out flex flex-col"
            :class="[sidebarCollapsed ? 'w-[80px]' : 'w-[260px]', sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0']"
        >
            {{-- Logo Area --}}
            <div class="flex items-center h-[72px] transition-all duration-300 px-4" :class="sidebarCollapsed ? 'justify-center' : 'justify-start pl-6'">
                <a href="{{ route('warehouse.dashboard') }}" class="flex items-center overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'w-12' : 'w-48'">
                    <img src="{{ asset('pm-logo.png') }}" alt="ParcelMan Express" class="h-12 w-auto max-w-none object-cover object-left block">
                </a>
            </div>

            <div class="h-px mx-6 bg-slate-200/60 transition-all duration-300" :class="sidebarCollapsed ? 'mx-4' : 'mx-6'"></div>

            {{-- Navigation Links --}}
            <nav class="flex-1 overflow-y-auto wh-sidebar-scroll py-6 px-4" x-data="{ activeDropdown: '{{ $initialDropdown }}' }">
                
                @if($canDashboard)
                    <div class="mb-6">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 px-2" x-show="!sidebarCollapsed">Operations</div>
                        
                        <a href="{{ route('warehouse.dashboard') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.dashboard') || request()->routeIs('admin.dashboard') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                                <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Dashboard</span>
                            </div>
                        </a>

                        @if($canReceiving)
                            <a href="{{ route('warehouse.walkin.create') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.walkin.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Walk-ins</span>
                                </div>
                            </a>
                        @endif

                        @if($canContacts)
                            <a href="{{ route('warehouse.contacts.index') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.contacts.*') || request()->routeIs('admin.contacts.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Recipient Desk</span>
                                </div>
                            </a>
                        @endif

                        @if($canRecipientPayments)
                            <a href="{{ route('warehouse.recipient-payments.index') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.recipient-payments.*') || request()->routeIs('admin.recipient-payments.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm5 4h4"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Payment Collection</span>
                                </div>
                            </a>
                        @endif

                        {{-- Smart Call Allocation --}}
                        <a href="{{ route('admin.agents.allocation') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('admin.agents.allocation*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Call Allocation</span>
                            </div>
                        </a>

                        {{-- Commission Ledger --}}
                        <a href="{{ route('admin.agents.ledger') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('admin.agents.ledger*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Commission Ledger</span>
                            </div>
                        </a>

                        {{-- Warehouses Dropdown --}}
                        @hasPermission('warehouses.view')
                            <div>
                                <button type="button" @click="activeDropdown = activeDropdown === 'warehouses' ? null : 'warehouses'" class="{{ $baseLinkCls }} {{ $isWhActive ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M4 10h16v11H4V10Zm-.5-3L12 3l8.5 4M8 14v3m4-3v3m4-3v3"/></svg>
                                        <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Warehouses</span>
                                    </div>
                                    <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="activeDropdown === 'warehouses' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'warehouses' && !sidebarCollapsed" x-transition.opacity x-collapse class="pl-[52px] pr-4 pb-4 pt-2 space-y-4" x-cloak>
                                    <a href="{{ route('warehouse.receipts.pending.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('warehouse.receipts.pending.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">Incoming Packages</a>
                                    <a href="{{ route('warehouse.pickups.received.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('warehouse.pickups.received.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">Received Pickups</a>
                                    <a href="{{ route('warehouse.packages.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('warehouse.packages.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">Warehouse Packages</a>
                                    <a href="{{ route('admin.warehouses.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('admin.warehouses.index') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">Manage Warehouses</a>
                                </div>
                            </div>
                        @endhasPermission
                    </div>
                @endif

                @if($canManifest || $canDeliveryAssign || $canRiderTeams || $canSorting)
                    <div class="mb-6">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 px-2" x-show="!sidebarCollapsed">Transport & Deliveries</div>
                        <div class="mx-auto w-6 h-px bg-slate-200 mb-4" x-show="sidebarCollapsed" x-cloak></div>

                        @if($canManifest)
                            <a href="{{ route('warehouse.manifests.transport.index') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.manifests.transport.*') || request()->routeIs('admin.transport-manifests.*') && !request()->routeIs('admin.transport-manifests.incoming.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Outgoing Batches</span>
                                </div>
                            </a>
                            <a href="{{ route('warehouse.manifests.incoming.index') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.manifests.incoming.*') || request()->routeIs('admin.transport-manifests.incoming.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Incoming Batches</span>
                                </div>
                            </a>
                        @endif

                        @if($canDeliveryAssign)
                            <a href="{{ route('warehouse.deliveries.runs.index') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.deliveries.runs.*') || request()->routeIs('admin.delivery-runs.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Delivery Runs</span>
                                </div>
                            </a>
                            <a href="{{ route('warehouse.deliveries.pending-confirmations') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('warehouse.deliveries.pending-confirmations*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Confirmations</span>
                                </div>
                            </a>
                        @endif

                        @if($canRiderTeams)
                            <a href="{{ route('admin.rider-teams.index') }}" class="{{ $baseLinkCls }} {{ request()->routeIs('admin.rider-teams.*') ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 2a3 3 0 1 1-5.196-2.052"/></svg>
                                    <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Riders Team</span>
                                </div>
                            </a>
                        @endif
                    </div>
                @endif

                @if($canUsers || $canRoles || $canHqControls)
                    <div class="mb-6">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 px-2" x-show="!sidebarCollapsed">Extra</div>
                        <div class="mx-auto w-6 h-px bg-slate-200 mb-4" x-show="sidebarCollapsed" x-cloak></div>

                        {{-- Workers Dropdown --}}
                        @if($canUsers || $canRoles)
                            <div>
                                <button type="button" @click="activeDropdown = activeDropdown === 'workers' ? null : 'workers'" class="{{ $baseLinkCls }} {{ $isWorkersActive ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Workers</span>
                                    </div>
                                    <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="activeDropdown === 'workers' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'workers' && !sidebarCollapsed" x-transition.opacity x-collapse class="pl-[52px] pr-4 pb-4 pt-2 space-y-4" x-cloak>
                                    @if($canUsers)
                                        <a href="{{ route('warehouse.users.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('warehouse.users.*') || request()->routeIs('admin.admins.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">All Workers</a>
                                    @endif
                                    @if($canRoles)
                                        <a href="{{ route('admin.roles.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('admin.roles.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">Roles & Permissions</a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- HQ Control Dropdown --}}
                        @if($canHqControls)
                            <div>
                                <button type="button" @click="activeDropdown = activeDropdown === 'hq' ? null : 'hq'" class="{{ $baseLinkCls }} {{ $isHqActive ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">HQ Control</span>
                                    </div>
                                    <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="activeDropdown === 'hq' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'hq' && !sidebarCollapsed" x-transition.opacity x-collapse class="pl-[52px] pr-4 pb-4 pt-2 space-y-4" x-cloak>
                                    <a href="{{ route('admin.orders.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('admin.orders.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">All Orders</a>
                                    <a href="{{ route('admin.vendors.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('admin.vendors.*') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">Vendors</a>
                                </div>
                            </div>
                        @endif

                        {{-- Settings Dropdown --}}
                        @hasPermission('settings.view')
                            <div>
                                <button type="button" @click="activeDropdown = activeDropdown === 'settings' ? null : 'settings'" class="{{ $baseLinkCls }} {{ $isSettingsActive ? $activeCls : $inactiveCls }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="transition-all duration-300 whitespace-nowrap" :class="sidebarCollapsed ? 'w-0 opacity-0 hidden' : ''">Settings</span>
                                    </div>
                                    <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="activeDropdown === 'settings' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'settings' && !sidebarCollapsed" x-transition.opacity x-collapse class="pl-[52px] pr-4 pb-4 pt-2 space-y-4" x-cloak>
                                    <a href="{{ route('admin.settings.index') }}" class="block text-[13px] font-semibold transition-colors {{ request()->routeIs('admin.settings.index') ? 'text-orange-600' : 'text-slate-500 hover:text-orange-600' }}">General Settings</a>
                                </div>
                            </div>
                        @endhasPermission
                    </div>
                @endif
            </nav>
        </aside>

        {{-- Mobile Overlay Backdrop --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

        {{-- ═══ MAIN CONTENT WRAPPER ═══ --}}
        <div class="flex-1 min-w-0 flex flex-col transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-[80px]' : 'lg:ml-[260px]'" x-data="{ userMenuOpen: false }">
            
            {{-- ═══ TOP NAVBAR ═══ --}}
            <header class="sticky top-0 z-30 h-[72px] flex items-center px-4 lg:px-8 bg-[#FCF9F6]">
                
                {{-- Left: Breadcrumbs / Mobile Toggle --}}
                <div class="flex items-center gap-4 min-w-0 flex-1">
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-orange-600 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                    </button>
                    <button type="button" @click="sidebarCollapsed = !sidebarCollapsed" class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-200/50 hover:text-slate-600 transition-all active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    </button>
                </div>

                {{-- Right: Controls & Profile --}}
                <div class="flex items-center gap-4 ml-auto">
                    
                    {{-- Global Search --}}
                    <div x-data="adminGlobalSearch(@js(route('admin.search')), @js(route('admin.search.results')))" x-init="init()" @keydown.escape.window="close()" class="hidden md:block relative">
                        <button type="button" @click="openMobile()" class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 flex items-center justify-center hover:text-orange-600 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                        <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-[60] bg-slate-900/40 backdrop-blur-sm" @click="close()"></div>
                        <div x-show="mobileOpen" x-cloak x-transition class="fixed left-3 right-3 top-3 z-[70] bg-white rounded-2xl border border-slate-100 shadow-2xl overflow-hidden">
                            <div class="relative border-b border-slate-100">
                                <input x-ref="mobileInput" type="text" x-model="query" @input="search()" placeholder="Search..." class="w-full h-14 pl-5 pr-12 text-[15px] bg-white focus:outline-none">
                                <button type="button" @click="close()" class="absolute right-0 inset-y-0 px-4 text-slate-400 hover:text-slate-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Warehouse Context Selector --}}
                    <div class="hidden sm:block">
                        @include('admin.shared.warehouse-context-selector')
                    </div>

                    {{-- Notifications --}}
                    <div class="relative" x-data="adminNotifications({ indexUrl: @js(route('admin.in-app-notifications.index')), readAllUrl: @js(route('admin.in-app-notifications.read-all')), markReadUrlTemplate: @js(route('admin.in-app-notifications.mark-read', ['notification' => '__ID__'])), csrfToken: @js(csrf_token()) })" x-init="init()">
                        <button type="button" @click="toggle()" class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white border border-slate-200 text-slate-500 shadow-sm transition-colors hover:text-orange-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9" /></svg>
                            <span x-show="unreadCount > 0" x-cloak class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white"></span>
                        </button>
                        <div x-show="open" x-cloak x-transition @click.away="open = false" class="absolute right-0 mt-3 w-80 rounded-2xl border border-slate-100 bg-white shadow-2xl z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <span class="font-bold text-slate-800">Notifications</span>
                                <button @click="markAllRead()" x-show="unreadCount > 0" class="text-[11px] text-orange-600 font-semibold hover:underline">Mark all read</button>
                            </div>
                            <div class="max-h-72 overflow-y-auto">
                                <template x-if="notifications.length === 0">
                                    <div class="px-4 py-8 text-center text-slate-400 text-sm">No new notifications</div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- User Profile Dropdown --}}
                    <div class="relative flex items-center gap-2">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-3 py-1 px-1.5 rounded-full hover:bg-slate-200/50 transition-all">
                            <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm flex items-center justify-center bg-[#FFE8DA] text-orange-600 font-bold text-lg">
                                {{ substr($authUser?->name ?? 'A', 0, 1) }}
                            </div>
                            <div class="hidden lg:flex flex-col text-left pr-2">
                                <p class="text-[13px] font-bold text-slate-900 leading-tight">{{ $authUser?->name ?? 'Admin' }}</p>
                                <p class="text-[11px] font-semibold text-slate-400 leading-tight uppercase mt-0.5">{{ $authUser?->roles->first()?->name ?? 'Administrator' }}</p>
                            </div>
                        </button>

                        <div x-show="userMenuOpen" x-cloak x-transition @click.away="userMenuOpen = false" class="absolute right-0 top-[110%] mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-[13px] font-bold text-slate-900">{{ $authUser?->name }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $authUser?->email }}</p>
                            </div>
                            <div class="py-2">
                                <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-orange-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    My Profile
                                </a>
                            </div>
                            <div class="border-t border-slate-100 pt-2">
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 font-medium hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            @include('shared.impersonation-banner')

            {{-- Main Content Area --}}
            <main class="flex-1 lg:px-2 pb-10">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- System Toasts & Firebase Push Scripts --}}
    <div id="admin-toast-container"
         class="fixed top-5 right-5 z-[9999] flex w-full max-w-sm flex-col gap-3 pointer-events-none"
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