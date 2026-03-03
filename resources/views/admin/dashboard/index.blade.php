@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb-parent', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Welcome header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-[20px] font-bold text-slate-800">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ Auth::guard('admin')->user()->name }}!</h1>
            <p class="text-[13px] text-slate-500 mt-0.5">Here's what's happening with Parcelman today.</p>
        </div>
        <div class="text-[12px] text-slate-400 bg-white px-3 py-1.5 rounded-xl border border-slate-100 shadow-sm">
            {{ now()->format('l, F j Y') }}
        </div>
    </div>

    {{-- Row 1: Today's Snapshot (6 stat cards) --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- Today's Shipments --}}
        <a href="{{ route('admin.shipments.index') }}" class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">Today</span>
            </div>
            <p class="text-[22px] font-bold text-slate-800">{{ number_format($todayShipments) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">New Shipments</p>
        </a>

        {{-- Pending Pickups --}}
        <a href="{{ route('admin.shipments.index') }}?status=pickup_assigned" class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-amber-500 bg-amber-50 px-2 py-0.5 rounded-full">Pending</span>
            </div>
            <p class="text-[22px] font-bold text-slate-800">{{ number_format($pendingPickups) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Awaiting Pickup</p>
        </a>

        {{-- At Warehouse --}}
        <a href="{{ route('admin.shipments.index') }}?status=at_warehouse" class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">Warehouse</span>
            </div>
            <p class="text-[22px] font-bold text-slate-800">{{ number_format($atWarehouse) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">At Warehouse</p>
        </a>

        {{-- Out for Delivery --}}
        <a href="#" class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
            </div>
            <p class="text-[22px] font-bold text-slate-800">{{ number_format($outForDelivery) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Out for Delivery</p>
        </a>

        {{-- Delivered Today --}}
        <a href="{{ route('admin.shipments.index') }}?status=delivered" class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-teal-500 bg-teal-50 px-2 py-0.5 rounded-full">Today</span>
            </div>
            <p class="text-[22px] font-bold text-slate-800">{{ number_format($deliveredToday) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Delivered Today</p>
        </a>

        {{-- Pending Invoices --}}
        <a href="{{ route('admin.shipments.index') }}?status=invoice_sent" class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md hover:border-slate-200 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full">Pending</span>
            </div>
            <p class="text-[22px] font-bold text-slate-800">{{ number_format($pendingInvoices) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Pending Invoices</p>
        </a>

    </div>

    {{-- Row 2: Monthly Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-5 text-white">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Invoiced This Month</p>
            <p class="text-[28px] font-bold">GHS {{ number_format($totalInvoicedMonth, 2) }}</p>
            <p class="text-[12px] text-slate-400 mt-1">Accepted invoices</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Shipments</p>
            <p class="text-[28px] font-bold text-slate-800">{{ number_format($totalShipmentsMonth) }}</p>
            <p class="text-[12px] text-slate-400 mt-1">Created this month</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Active Vendors</p>
            <p class="text-[28px] font-bold text-slate-800">{{ number_format($activeVendorsMonth) }}</p>
            <p class="text-[12px] text-slate-400 mt-1">With shipments this month</p>
        </div>

    </div>

    {{-- Row 3: Recent Shipments + Live Operations --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

        {{-- Recent Shipments (wider) --}}
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="text-[14px] font-semibold text-slate-800">Recent Shipments</h3>
                <a href="{{ route('admin.shipments.index') }}" class="text-[12px] text-primary-600 hover:underline font-medium">View all</a>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($recentShipments as $shipment)
                <a href="{{ route('admin.shipments.show', $shipment) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/70 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                        {{ strtoupper(substr($shipment->shipment_number, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-slate-800 truncate">{{ $shipment->shipment_number }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ $shipment->vendor?->business_name ?: $shipment->vendor?->name }}</p>
                    </div>
                    @php
                        $statusColors = [
                            'draft'            => 'bg-slate-100 text-slate-500',
                            'submitted'        => 'bg-blue-50 text-blue-600',
                            'invoice_sent'     => 'bg-amber-50 text-amber-600',
                            'invoice_accepted' => 'bg-emerald-50 text-emerald-600',
                            'pickup_assigned'  => 'bg-purple-50 text-purple-600',
                            'picked_up'        => 'bg-indigo-50 text-indigo-600',
                            'at_warehouse'     => 'bg-cyan-50 text-cyan-600',
                            'sorted'           => 'bg-teal-50 text-teal-600',
                            'in_transit'       => 'bg-orange-50 text-orange-600',
                            'at_destination'   => 'bg-lime-50 text-lime-600',
                            'out_for_delivery' => 'bg-green-50 text-green-600',
                            'delivered'        => 'bg-emerald-50 text-emerald-700',
                            'cancelled'        => 'bg-red-50 text-red-500',
                        ];
                        $statusLabels = [
                            'draft'            => 'Draft',
                            'submitted'        => 'Submitted',
                            'invoice_sent'     => 'Invoice Sent',
                            'invoice_accepted' => 'Invoice Accepted',
                            'pickup_assigned'  => 'Pickup Assigned',
                            'picked_up'        => 'Picked Up',
                            'at_warehouse'     => 'At Warehouse',
                            'sorted'           => 'Sorted',
                            'in_transit'       => 'In Transit',
                            'at_destination'   => 'At Destination',
                            'out_for_delivery' => 'Out for Delivery',
                            'delivered'        => 'Delivered',
                            'cancelled'        => 'Cancelled',
                        ];
                        $statusValue = $shipment->status instanceof \BackedEnum ? $shipment->status->value : (string) $shipment->status;
                        $color = $statusColors[$statusValue] ?? 'bg-slate-100 text-slate-500';
                        $label = $statusLabels[$statusValue] ?? ucfirst(str_replace('_', ' ', $statusValue));
                    @endphp
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $color }} whitespace-nowrap">{{ $label }}</span>
                    <span class="text-[11px] text-slate-400 whitespace-nowrap hidden sm:block">{{ $shipment->created_at->diffForHumans() }}</span>
                </a>
                @empty
                <div class="px-5 py-8 text-center">
                    <p class="text-[13px] text-slate-400">No shipments yet</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Live Operations --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Active Delivery Runs --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <h3 class="text-[13px] font-semibold text-slate-800 flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        Active Delivery Runs
                    </h3>
                    <span class="text-[11px] text-slate-400">{{ $activeDeliveryRuns->count() }} active</span>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($activeDeliveryRuns as $run)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-semibold text-slate-800 truncate">{{ $run->run_number }}</p>
                            <p class="text-[11px] text-slate-400 truncate">{{ $run->assignedDriver?->name ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-5 text-center">
                        <p class="text-[12px] text-slate-400">No active runs</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Active Transport Manifests --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <h3 class="text-[13px] font-semibold text-slate-800 flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                        In-Transit Manifests
                    </h3>
                    <span class="text-[11px] text-slate-400">{{ $activeManifests->count() }} active</span>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($activeManifests as $manifest)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-semibold text-slate-800 truncate">{{ $manifest->manifest_number }}</p>
                            <p class="text-[11px] text-slate-400 truncate">{{ $manifest->originWarehouse?->name }} → {{ $manifest->destinationWarehouse?->name }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-5 text-center">
                        <p class="text-[12px] text-slate-400">No in-transit manifests</p>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    {{-- Role Info banner for non-super admins --}}
    @if(!Auth::guard('admin')->user()->isSuperAdmin())
    <div class="bg-amber-50 border border-amber-200/60 rounded-2xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-[13px] font-semibold text-amber-800">Limited Access</p>
            <p class="text-[12px] text-amber-700 mt-0.5">
                You're logged in as <strong>{{ Auth::guard('admin')->user()->roles->first()?->name ?? 'User' }}</strong>. Some data may be restricted based on your permissions.
            </p>
        </div>
    </div>
    @endif

</div>
@endsection
