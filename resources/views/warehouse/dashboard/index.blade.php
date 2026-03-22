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
    <div class="relative rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/20 bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950">
        <div class="absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:22px 22px;pointer-events:none;"></div>
        <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-orange-500/5 pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-20 w-80 h-80 rounded-full bg-white/[0.02] pointer-events-none"></div>

        <div class="relative z-10 px-7 py-7">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.15em] font-bold text-orange-400/70">Warehouse Overview</p>
                    <h2 class="mt-1.5 text-2xl font-extrabold text-white tracking-tight" x-text="warehouseName"></h2>
                    <p class="mt-1 text-sm text-slate-400">Monitor intake flow and warehouse team activity from one place.</p>
                </div>
                @hasPermission('warehouse.receiving.manage')
                <a href="{{ route('warehouse.walkin.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-orange-600 hover:bg-orange-500 transition-all shadow-lg shadow-orange-600/20 hover:shadow-xl active:scale-[0.97]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Walk-in Receiving
                </a>
                @endhasPermission
            </div>

            <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-2xl p-4 bg-white/[0.06] border border-white/[0.06]">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold text-slate-500">Pending Receipts</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['pending_receipts'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl p-4 bg-white/[0.06] border border-white/[0.06]">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold text-slate-500">Received Pickups</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['received_pickups'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl p-4 bg-white/[0.06] border border-white/[0.06]">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold text-slate-500">Received Items</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ number_format($stats['received_items'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl p-4 bg-white/[0.06] border border-white/[0.06]">
                    <p class="text-[10px] uppercase tracking-[0.12em] font-semibold text-slate-500">Warehouse Users</p>
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
                <a href="{{ route('warehouse.receipts.pending.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-50">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-slate-900">Pending Receipts</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Awaiting intake</p>
                    </div>
                </a>
                <a href="{{ route('warehouse.pickups.received.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-50">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-slate-900">Received Pickups</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Completed intake</p>
                    </div>
                </a>
            @endhasPermission
            @hasPermission('warehouse.items.scan')
                <a href="{{ route('warehouse.items.received.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-50">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-slate-900">Received Items</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">All scanned items</p>
                    </div>
                </a>
            @endhasPermission
            @hasPermission('warehouse.users.view')
                <a href="{{ route('warehouse.users.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 hover:border-slate-300 hover:shadow-sm transition-all">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-50">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-slate-900">Warehouse Users</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Staff management</p>
                    </div>
                </a>
            @endhasPermission
        </div>
    </div>
</div>
@endsection
