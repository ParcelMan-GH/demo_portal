@extends('warehouse.layouts.app')

@section('title', 'Warehouse Dashboard')
@section('page-title', 'Dashboard')

@php
    $dashboardConfig = [
        'warehouse_name' => $warehouse->name,
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseDashboardPage" data-warehouse-dashboard-config="{{ e(json_encode($dashboardConfig)) }}">
    <!-- Hero Section -->
    <div class="relative rounded-3xl overflow-hidden shadow-xl" style="background:linear-gradient(150deg,#7c2d12 0%,#9a3412 55%,#c2410c 100%);">
        <!-- Texture overlay -->
        <div class="absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:22px 22px;pointer-events:none;"></div>
        <!-- Decorative circles -->
        <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full" style="background:rgba(255,255,255,0.04);pointer-events:none;"></div>
        <div class="absolute -bottom-24 -left-20 w-80 h-80 rounded-full" style="background:rgba(255,255,255,0.03);pointer-events:none;"></div>

        <div class="relative z-10 px-7 py-7">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.15em] font-bold" style="color:rgba(253,186,116,0.6);">Warehouse Overview</p>
                    <h2 class="mt-1.5 text-2xl font-extrabold text-white tracking-tight" x-text="warehouseName"></h2>
                    <p class="mt-1 text-sm" style="color:rgba(254,215,170,0.7);">Monitor intake flow and warehouse team activity from one place.</p>
                </div>
                @hasPermission('warehouse.receiving.manage')
                <a href="{{ route('warehouse.walkin.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg hover:shadow-xl active:scale-[0.97]" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.2);backdrop-filter:blur(8px);" onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Walk-in Receiving
                </a>
                @endhasPermission
            </div>

            <!-- Stats grid inside hero -->
            <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-2xl p-4" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(4px);">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold" style="color:rgba(253,186,116,0.55);">Pending Receipts</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['pending_receipts'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl p-4" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(4px);">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold" style="color:rgba(253,186,116,0.55);">Received Pickups</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['received_pickups'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl p-4" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(4px);">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold" style="color:rgba(253,186,116,0.55);">Received Items</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['received_items'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl p-4" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(4px);">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold" style="color:rgba(253,186,116,0.55);">Warehouse Users</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['warehouse_users'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="rounded-2xl border border-slate-200/80 bg-white/80 backdrop-blur-sm p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Quick Access</h3>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @hasPermission('warehouse.receiving.manage')
                <a href="{{ route('warehouse.receipts.pending.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-orange-200 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(194,65,12,0.08);">
                        <svg class="w-4 h-4" style="color:#c2410c;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-orange-800">Pending Receipts</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Awaiting intake</p>
                    </div>
                </a>
                <a href="{{ route('warehouse.pickups.received.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-orange-200 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(194,65,12,0.08);">
                        <svg class="w-4 h-4" style="color:#c2410c;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-orange-800">Received Pickups</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Completed intake</p>
                    </div>
                </a>
            @endhasPermission
            @hasPermission('warehouse.items.scan')
                <a href="{{ route('warehouse.items.received.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-orange-200 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(194,65,12,0.08);">
                        <svg class="w-4 h-4" style="color:#c2410c;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-orange-800">Received Items</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">All scanned items</p>
                    </div>
                </a>
            @endhasPermission
            @hasPermission('warehouse.users.view')
                <a href="{{ route('warehouse.users.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-orange-200 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(194,65,12,0.08);">
                        <svg class="w-4 h-4" style="color:#c2410c;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-orange-800">Warehouse Users</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Staff management</p>
                    </div>
                </a>
            @endhasPermission
        </div>
    </div>
</div>
@endsection
