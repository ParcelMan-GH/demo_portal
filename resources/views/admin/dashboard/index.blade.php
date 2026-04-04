@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-[1400px] mx-auto">

    {{-- Header --}}
    <div class="flex items-end justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                @php $hour = now()->hour; @endphp
                {{ $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ $admin->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">{{ now()->format('l, F j, Y') }}</p>
        </div>
    </div>

    {{-- ═══ NEEDS ATTENTION ═══ --}}
    @if($totalNeedsAttention > 0)
    <div class="mb-8 bg-amber-50 border border-amber-200 rounded-2xl overflow-hidden">
        <div class="px-5 py-3 flex items-center justify-between border-b border-amber-100">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <h2 class="text-sm font-bold text-amber-900">{{ $totalNeedsAttention }} parcel{{ $totalNeedsAttention > 1 ? 's' : '' }} need{{ $totalNeedsAttention === 1 ? 's' : '' }} your attention</h2>
            </div>
            <a href="{{ route('admin.shipments.index') }}?status=submitted" class="text-xs font-semibold text-amber-700 hover:text-amber-900">View all &rarr;</a>
        </div>
        <div class="divide-y divide-amber-100">
            @foreach($needsAttention as $parcel)
            <a href="{{ route('admin.shipments.edit', $parcel) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-amber-100/50 transition-colors">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-900">{{ $parcel->shipment_number }}</span>
                        <span class="text-xs text-slate-500">from {{ $parcel->vendor?->name ?? 'Unknown' }}</span>
                    </div>
                    @if($parcel->sender_notes)
                    <p class="text-xs text-amber-700 mt-0.5 truncate">"{{ $parcel->sender_notes }}"</p>
                    @endif
                </div>
                <span class="text-xs text-slate-400">{{ $parcel->created_at->diffForHumans() }}</span>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══ STATS GRID ═══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($todayShipments) }}</p>
            <p class="text-xs text-slate-500 mt-1">New parcels today</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($pendingPickups) }}</p>
            <p class="text-xs text-slate-500 mt-1">Awaiting pickup</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0m-10 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($outForDelivery) }}</p>
            <p class="text-xs text-slate-500 mt-1">Out for delivery</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($deliveredToday) }}</p>
            <p class="text-xs text-slate-500 mt-1">Delivered today</p>
        </div>
    </div>

    {{-- ═══ MAIN CONTENT ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Left: Recent Parcels --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900">Recent Parcels</h2>
                    <a href="{{ route('admin.shipments.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">View all &rarr;</a>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($recentShipments as $shipment)
                    <a href="{{ route('admin.shipments.show', $shipment) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-semibold text-slate-900">{{ $shipment->shipment_number }}</span>
                            <span class="text-xs text-slate-400 ml-2">{{ $shipment->vendor?->name }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @php
                                $dotColor = match($shipment->status->value) {
                                    'submitted' => 'bg-blue-500',
                                    'pickup_assigned' => 'bg-violet-500',
                                    'picked_up' => 'bg-indigo-500',
                                    'at_warehouse' => 'bg-purple-500',
                                    'sorted' => 'bg-cyan-500',
                                    'in_transit' => 'bg-orange-500',
                                    'out_for_delivery' => 'bg-amber-500',
                                    'delivered' => 'bg-emerald-500',
                                    'cancelled' => 'bg-red-500',
                                    default => 'bg-slate-400',
                                };
                            @endphp
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                            <span class="text-xs text-slate-500">{{ ucwords(str_replace('_', ' ', $shipment->status->value)) }}</span>
                        </div>
                        <span class="text-xs text-slate-400 flex-shrink-0 w-16 text-right">{{ $shipment->created_at->diffForHumans(null, true, true) }}</span>
                    </a>
                    @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-400">No parcels yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Operations --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900">Operations</h2>
                </div>
                <div class="p-5 space-y-3">
                    <a href="{{ route('admin.shipments.index') }}?status=submitted" class="flex items-center justify-between py-1 group">
                        <span class="text-sm text-slate-600 group-hover:text-slate-900">Submitted</span>
                        <span class="text-sm font-bold text-slate-900">{{ number_format($submitted) }}</span>
                    </a>
                    <a href="{{ route('admin.shipments.index') }}?status=pickup_assigned" class="flex items-center justify-between py-1 group">
                        <span class="text-sm text-slate-600 group-hover:text-slate-900">Pickup assigned</span>
                        <span class="text-sm font-bold text-slate-900">{{ number_format($pendingPickups) }}</span>
                    </a>
                    <a href="{{ route('admin.shipments.index') }}?status=at_warehouse" class="flex items-center justify-between py-1 group">
                        <span class="text-sm text-slate-600 group-hover:text-slate-900">At warehouse</span>
                        <span class="text-sm font-bold text-slate-900">{{ number_format($atWarehouse) }}</span>
                    </a>
                    <a href="{{ route('admin.shipments.index') }}?status=out_for_delivery" class="flex items-center justify-between py-1 group">
                        <span class="text-sm text-slate-600 group-hover:text-slate-900">Out for delivery</span>
                        <span class="text-sm font-bold text-orange-600">{{ number_format($outForDelivery) }}</span>
                    </a>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-sm text-slate-600">Pending invoices</span>
                        <span class="text-sm font-bold text-amber-600">{{ number_format($pendingInvoices) }}</span>
                    </div>
                </div>
            </div>

            {{-- Active Deliveries --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900">Active Deliveries</h2>
                    <a href="{{ route('admin.delivery-runs.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">View all &rarr;</a>
                </div>
                @if($activeDeliveryRuns->count() > 0)
                <div class="divide-y divide-slate-50">
                    @foreach($activeDeliveryRuns as $run)
                    <a href="{{ route('admin.delivery-runs.show', $run) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0m-10 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ $run->run_number }}</p>
                            <p class="text-xs text-slate-400">{{ $run->assignedDriver?->name ?? 'No driver' }}</p>
                        </div>
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="px-5 py-8 text-center text-sm text-slate-400">No active deliveries</div>
                @endif
            </div>

            {{-- This Month --}}
            <div class="bg-slate-900 rounded-2xl p-5 text-white">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">This Month</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-2xl font-bold">{{ number_format($totalShipmentsMonth) }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Total parcels</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-lg font-bold text-emerald-400">GHS {{ number_format($totalInvoicedMonth, 2) }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Invoiced</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold">{{ number_format($activeVendorsMonth) }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Active vendors</p>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-700 grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-sm font-bold">{{ number_format($totalVendors) }}</p>
                            <p class="text-[10px] text-slate-500">Vendors</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold">{{ number_format($totalDrivers) }}</p>
                            <p class="text-[10px] text-slate-500">Drivers</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold">{{ number_format($totalLabels) }}</p>
                            <p class="text-[10px] text-slate-500">Labels</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
