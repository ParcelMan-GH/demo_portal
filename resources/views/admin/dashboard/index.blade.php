@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb-parent', 'Dashboard')

@section('content')
<div class="space-y-5">

    {{-- ── Welcome Banner ──────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 shadow-xl p-6 md:p-8">
        {{-- Grid pattern overlay --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
             style="background-image:linear-gradient(to right,#fff 1px,transparent 1px),linear-gradient(to bottom,#fff 1px,transparent 1px);background-size:40px 40px"></div>
        {{-- Glow blobs --}}
        <div class="pointer-events-none absolute -top-20 -right-20 w-80 h-80 bg-primary-500/20 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 left-10 w-56 h-56 bg-indigo-500/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
            <div>
                <p class="text-[12px] font-semibold text-slate-400 uppercase tracking-widest mb-1">
                    {{ now()->format('l, F j Y') }} &middot; {{ now()->format('g:i A') }}
                </p>
                <h1 class="text-[26px] md:text-[30px] font-bold text-white leading-tight">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                    {{ Auth::guard('admin')->user()->name }}
                </h1>
                <p class="text-[13px] text-slate-400 mt-1">Here's what's happening at Parcelman today.</p>
            </div>
            <div class="flex flex-wrap gap-2 sm:flex-nowrap sm:flex-col sm:items-end">
                <a href="{{ route('admin.shipments.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-[12px] font-semibold border border-white/10 backdrop-blur-sm transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Parcel
                </a>
                <a href="{{ route('admin.package-tracking.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-[12px] font-semibold border border-white/10 backdrop-blur-sm transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    Package Tracking
                </a>
                <a href="{{ route('admin.collection-center.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-[12px] font-semibold border border-white/10 backdrop-blur-sm transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    Collection Center
                </a>
            </div>
        </div>
    </div>

    {{-- ── Row 2: Key Metrics (4 cards) ────────────────────────────────── --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Today's Shipments --}}
        <a href="{{ route('admin.shipments.index') }}"
           class="group bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg hover:shadow-xl hover:border-blue-200 transition-all p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-blue-500 bg-blue-50 px-2.5 py-1 rounded-full tracking-wide uppercase">Today</span>
            </div>
            <p class="text-[32px] font-bold text-slate-900 leading-none">{{ number_format($todayShipments) }}</p>
            <p class="text-[12px] text-slate-400 mt-1.5 font-medium">New Shipments</p>
        </a>

        {{-- Pending Pickups --}}
        <a href="{{ route('admin.shipments.index') }}?status=pickup_assigned"
           class="group bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg hover:shadow-xl hover:border-amber-200 transition-all p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-amber-500 bg-amber-50 px-2.5 py-1 rounded-full tracking-wide uppercase">Pending</span>
            </div>
            <p class="text-[32px] font-bold text-slate-900 leading-none">{{ number_format($pendingPickups) }}</p>
            <p class="text-[12px] text-slate-400 mt-1.5 font-medium">Awaiting Pickup</p>
        </a>

        {{-- Out for Delivery --}}
        <a href="{{ route('admin.delivery-runs.index') }}"
           class="group bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg hover:shadow-xl hover:border-emerald-200 transition-all p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-emerald-500 bg-emerald-50 px-2.5 py-1 rounded-full tracking-wide uppercase">Active</span>
            </div>
            <p class="text-[32px] font-bold text-slate-900 leading-none">{{ number_format($outForDelivery) }}</p>
            <p class="text-[12px] text-slate-400 mt-1.5 font-medium">Out for Delivery</p>
        </a>

        {{-- Delivered Today --}}
        <a href="{{ route('admin.shipments.index') }}?status=delivered"
           class="group bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg hover:shadow-xl hover:border-teal-200 transition-all p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 flex items-center justify-center group-hover:bg-teal-100 transition-colors">
                    <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-teal-500 bg-teal-50 px-2.5 py-1 rounded-full tracking-wide uppercase">Today</span>
            </div>
            <p class="text-[32px] font-bold text-slate-900 leading-none">{{ number_format($deliveredToday) }}</p>
            <p class="text-[12px] text-slate-400 mt-1.5 font-medium">Delivered Today</p>
        </a>

    </div>

    {{-- ── Row 3: Pipeline Overview ─────────────────────────────────────── --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-[14px] font-bold text-slate-800">Shipment Pipeline</h3>
            <span class="text-[11px] text-slate-400">Live counts</span>
        </div>
        @php
            $pipeline = [
                ['label' => 'Submitted',    'count' => $submitted,      'color' => 'bg-blue-500',    'dot' => 'bg-blue-500',    'text' => 'text-blue-600',    'bg' => 'bg-blue-50'],
                ['label' => 'Pickup Assigned', 'count' => $pendingPickups, 'color' => 'bg-amber-500',  'dot' => 'bg-amber-400',   'text' => 'text-amber-600',   'bg' => 'bg-amber-50'],
                ['label' => 'Picked Up',    'count' => $pickedUp,       'color' => 'bg-indigo-500',  'dot' => 'bg-indigo-500',  'text' => 'text-indigo-600',  'bg' => 'bg-indigo-50'],
                ['label' => 'At Warehouse', 'count' => $atWarehouse,    'color' => 'bg-cyan-500',    'dot' => 'bg-cyan-500',    'text' => 'text-cyan-600',    'bg' => 'bg-cyan-50'],
                ['label' => 'Sorted',       'count' => $sorted,         'color' => 'bg-violet-500',  'dot' => 'bg-violet-500',  'text' => 'text-violet-600',  'bg' => 'bg-violet-50'],
                ['label' => 'In Transit',   'count' => $inTransit,      'color' => 'bg-orange-500',  'dot' => 'bg-orange-400',  'text' => 'text-orange-600',  'bg' => 'bg-orange-50'],
                ['label' => 'Out for Delivery', 'count' => $outForDelivery, 'color' => 'bg-emerald-500','dot' => 'bg-emerald-500','text' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
                ['label' => 'Delivered',    'count' => $deliveredToday, 'color' => 'bg-teal-500',    'dot' => 'bg-teal-500',    'text' => 'text-teal-600',    'bg' => 'bg-teal-50'],
            ];
        @endphp
        <div class="flex items-center gap-0 overflow-x-auto pb-2 -mx-1 px-1">
            @foreach($pipeline as $i => $step)
            <div class="flex items-center flex-shrink-0">
                <div class="flex flex-col items-center gap-1.5 px-2 @if($step['count'] == 0) opacity-40 @endif">
                    <div class="w-9 h-9 rounded-2xl {{ $step['bg'] }} flex items-center justify-center">
                        <span class="w-2.5 h-2.5 rounded-full {{ $step['dot'] }} @if($step['count'] > 0) ring-4 ring-offset-1 ring-{{ explode('-', $step['dot'])[1] }}-200 @endif"></span>
                    </div>
                    <p class="text-[17px] font-bold text-slate-800 leading-none">{{ number_format($step['count']) }}</p>
                    <p class="text-[10px] text-slate-400 font-medium text-center leading-tight whitespace-nowrap">{{ $step['label'] }}</p>
                </div>
                @if(!$loop->last)
                <div class="flex-shrink-0 mx-0.5">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Row 4: Recent Parcels + Side Cards ──────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

        {{-- Recent Parcels (left 60%) --}}
        <div class="xl:col-span-3 bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-[14px] font-bold text-slate-800">Recent Parcels</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Last 10 shipments</p>
                </div>
                <a href="{{ route('admin.shipments.index') }}"
                   class="text-[12px] text-primary-600 hover:text-primary-700 font-semibold hover:underline">View all →</a>
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
            @endphp
            <div class="divide-y divide-slate-50">
                @forelse($recentShipments as $shipment)
                @php
                    $sv = $shipment->status instanceof \BackedEnum ? $shipment->status->value : (string) $shipment->status;
                    $sc = $statusColors[$sv] ?? 'bg-slate-100 text-slate-500';
                    $sl = $statusLabels[$sv] ?? ucfirst(str_replace('_', ' ', $sv));
                @endphp
                <a href="{{ route('admin.shipments.show', $shipment) }}"
                   class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/70 transition-colors group">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                        {{ strtoupper(substr($shipment->shipment_number, -2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-slate-800 group-hover:text-primary-600 transition-colors truncate">{{ $shipment->shipment_number }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ $shipment->vendor?->business_name ?: $shipment->vendor?->name }}</p>
                    </div>
                    <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full {{ $sc }} whitespace-nowrap">{{ $sl }}</span>
                    <span class="text-[11px] text-slate-300 whitespace-nowrap hidden md:block">{{ $shipment->created_at->diffForHumans() }}</span>
                </a>
                @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-[13px] text-slate-400">No shipments yet</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Right column (40%) --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Package Custody card --}}
            <a href="{{ route('admin.package-tracking.index') }}"
               class="block bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5 hover:shadow-xl hover:border-violet-200 transition-all group">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[13px] font-bold text-slate-800 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-violet-50 flex items-center justify-center group-hover:bg-violet-100 transition-colors">
                            <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        Package Custody
                    </h3>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-violet-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="text-center p-2.5 rounded-2xl bg-slate-50">
                        <p class="text-[20px] font-bold text-slate-800">{{ number_format($totalLabels) }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Total Labels</p>
                    </div>
                    <div class="text-center p-2.5 rounded-2xl bg-violet-50">
                        <p class="text-[20px] font-bold text-violet-700">{{ number_format($claimedLabels) }}</p>
                        <p class="text-[10px] text-violet-400 mt-0.5 font-medium">Claimed</p>
                    </div>
                    <div class="text-center p-2.5 rounded-2xl bg-slate-50">
                        <p class="text-[20px] font-bold text-slate-600">{{ number_format(max(0, $totalLabels - $claimedLabels)) }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Unclaimed</p>
                    </div>
                </div>
                @if($totalDrivers > 0)
                <p class="text-[11px] text-slate-400 mt-3">
                    <span class="text-slate-600 font-semibold">{{ $driversWithPackages }}</span> of
                    <span class="text-slate-600 font-semibold">{{ $totalDrivers }}</span> active drivers carrying packages
                </p>
                @endif
            </a>

            {{-- Active Delivery Runs --}}
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100">
                    <h3 class="text-[13px] font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        Active Delivery Runs
                    </h3>
                    <a href="{{ route('admin.delivery-runs.index') }}" class="text-[11px] text-primary-600 font-semibold hover:underline">View all →</a>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($activeDeliveryRuns as $run)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <div class="w-7 h-7 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-semibold text-slate-800 truncate">{{ $run->run_number }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ $run->assignedDriver?->name ?? 'Unassigned' }}</p>
                        </div>
                        <span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full uppercase tracking-wide">Live</span>
                    </div>
                    @empty
                    <div class="px-4 py-5 text-center">
                        <p class="text-[12px] text-slate-400">No active runs</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Monthly Summary --}}
            <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl p-5 relative overflow-hidden">
                <div class="pointer-events-none absolute inset-0 opacity-[0.05]"
                     style="background-image:linear-gradient(to right,#fff 1px,transparent 1px),linear-gradient(to bottom,#fff 1px,transparent 1px);background-size:24px 24px"></div>
                <p class="relative text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">This Month</p>
                <div class="relative grid grid-cols-3 gap-3">
                    <div>
                        <p class="text-[20px] font-bold text-white">{{ number_format($totalShipmentsMonth) }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Shipments</p>
                    </div>
                    <div>
                        <p class="text-[20px] font-bold text-emerald-400">GHS {{ number_format($totalInvoicedMonth, 0) }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Invoiced</p>
                    </div>
                    <div>
                        <p class="text-[20px] font-bold text-white">{{ number_format($activeVendorsMonth) }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Vendors Active</p>
                    </div>
                </div>
                <div class="relative mt-4 pt-4 border-t border-white/10 flex items-center justify-between">
                    <p class="text-[11px] text-slate-400">Total vendors on platform</p>
                    <span class="text-[13px] font-bold text-white">{{ number_format($totalVendors) }}</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Row 5: Active Transport Manifests ───────────────────────────── --}}
    @if($activeManifests->isNotEmpty())
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="text-[14px] font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                Active Transport Manifests
            </h3>
            <a href="{{ route('admin.transport-manifests.index') }}" class="text-[12px] text-primary-600 font-semibold hover:underline">View all →</a>
        </div>
        <div class="flex gap-3 overflow-x-auto p-4">
            @foreach($activeManifests as $manifest)
            @php
                $mColors = ['in_transit'=>'bg-orange-50 text-orange-600','assigned'=>'bg-blue-50 text-blue-600','loading'=>'bg-indigo-50 text-indigo-600'];
                $mStatus = $manifest->status instanceof \BackedEnum ? $manifest->status->value : (string) $manifest->status;
                $mColor  = $mColors[$mStatus] ?? 'bg-slate-50 text-slate-500';
                $mLabel  = ucfirst(str_replace('_', ' ', $mStatus));
            @endphp
            <a href="{{ route('admin.transport-manifests.show', $manifest) }}"
               class="flex-shrink-0 w-56 rounded-2xl border border-slate-200/80 p-4 hover:border-blue-300 hover:shadow-md transition-all bg-white group">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[12px] font-bold text-slate-800 group-hover:text-primary-600 transition-colors">{{ $manifest->manifest_number }}</p>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $mColor }} uppercase tracking-wide">{{ $mLabel }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                    <span class="truncate max-w-[72px]">{{ $manifest->originWarehouse?->name ?? '—' }}</span>
                    <svg class="w-3 h-3 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="truncate max-w-[72px]">{{ $manifest->destinationWarehouse?->name ?? '—' }}</span>
                </div>
                @if($manifest->dispatched_at)
                <p class="text-[10px] text-slate-300 mt-2">{{ $manifest->dispatched_at->diffForHumans() }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Role Info banner for non-super admins --}}
    @if(!Auth::guard('admin')->user()->isSuperAdmin())
    <div class="bg-amber-50 border border-amber-200/60 rounded-2xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-[13px] font-semibold text-amber-800">Limited Access</p>
            <p class="text-[12px] text-amber-700 mt-0.5">
                You're logged in as <strong>{{ Auth::guard('admin')->user()->role?->value ?? 'Staff' }}</strong>. Some data may be restricted based on your permissions.
            </p>
        </div>
    </div>
    @endif

</div>
@endsection
