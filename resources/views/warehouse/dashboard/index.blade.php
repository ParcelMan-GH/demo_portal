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
    <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-slate-700 px-6 py-6 text-white shadow-xl">
        <p class="text-xs uppercase tracking-[0.14em] text-slate-300 font-semibold">Warehouse Overview</p>
        <h2 class="mt-2 text-2xl font-bold" x-text="warehouseName"></h2>
        <p class="mt-1 text-sm text-slate-300">Monitor intake flow and warehouse team activity from one place.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.14em] text-slate-500 font-semibold">Pending Receipts</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($stats['pending_receipts'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.14em] text-slate-500 font-semibold">Received Pickups</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($stats['received_pickups'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.14em] text-slate-500 font-semibold">Received Items Qty</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($stats['received_items'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.14em] text-slate-500 font-semibold">Warehouse Users</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($stats['warehouse_users'] ?? 0) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Quick Access</h3>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @hasPermission('warehouse.receiving.manage')
                <a href="{{ route('warehouse.receipts.pending.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">Pending Receipts</a>
                <a href="{{ route('warehouse.pickups.received.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">Received Pickups</a>
            @endhasPermission
            @hasPermission('warehouse.items.scan')
                <a href="{{ route('warehouse.items.received.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">Received Items</a>
            @endhasPermission
            @hasPermission('warehouse.users.view')
                <a href="{{ route('warehouse.users.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">Warehouse Users</a>
            @endhasPermission
        </div>
    </div>
</div>
@endsection
