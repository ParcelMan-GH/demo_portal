@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb-parent', 'Dashboard')

@section('content')
@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $statusDots = [
        'draft'            => 'bg-slate-400',
        'submitted'        => 'bg-blue-500',
        'invoice_sent'     => 'bg-amber-500',
        'invoice_accepted' => 'bg-emerald-500',
        'pickup_assigned'  => 'bg-purple-500',
        'picked_up'        => 'bg-indigo-500',
        'at_warehouse'     => 'bg-cyan-500',
        'sorted'           => 'bg-teal-500',
        'in_transit'       => 'bg-orange-500',
        'at_destination'   => 'bg-lime-500',
        'out_for_delivery' => 'bg-green-500',
        'delivered'        => 'bg-emerald-600',
        'cancelled'        => 'bg-red-400',
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

<div class="max-w-6xl mx-auto space-y-8 py-2">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ $greeting }}, {{ $admin->name }}</h1>
        <p class="text-sm text-slate-400 mt-1">{{ now()->format('l, F j Y') }}</p>
    </div>

    {{-- Key numbers --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-8 gap-y-6 pb-8 border-b border-slate-100">
        <div>
            <p class="text-3xl font-semibold text-slate-900">{{ number_format($todayShipments) }}</p>
            <p class="text-sm text-slate-500 mt-1">New today</p>
        </div>
        <div>
            <p class="text-3xl font-semibold text-slate-900">{{ number_format($pendingPickups) }}</p>
            <p class="text-sm text-slate-500 mt-1">Awaiting pickup</p>
        </div>
        <div>
            <p class="text-3xl font-semibold text-slate-900">{{ number_format($outForDelivery) }}</p>
            <p class="text-sm text-slate-500 mt-1">Out for delivery</p>
        </div>
        <div>
            <p class="text-3xl font-semibold text-slate-900">{{ number_format($deliveredToday) }}</p>
            <p class="text-sm text-slate-500 mt-1">Delivered today</p>
        </div>
    </div>

    {{-- Main two-column layout --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-10">

        {{-- Left: Recent Parcels (65%) --}}
        <div class="xl:col-span-3">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-900">Recent Parcels</h2>
                <a href="{{ route('admin.shipments.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View all &rarr;</a>
            </div>

            @if($recentShipments->isEmpty())
                <p class="text-sm text-slate-400">No shipments yet.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recentShipments->take(8) as $shipment)
                    @php
                        $sv  = $shipment->status instanceof \BackedEnum ? $shipment->status->value : (string) $shipment->status;
                        $dot = $statusDots[$sv]   ?? 'bg-slate-400';
                        $lbl = $statusLabels[$sv]  ?? ucfirst(str_replace('_', ' ', $sv));
                    @endphp
                    <div class="flex items-center gap-4 py-3 hover:bg-slate-50 -mx-3 px-3 rounded-lg transition-colors">
                        <a href="{{ route('admin.shipments.show', $shipment) }}"
                           class="text-sm font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap">
                            {{ $shipment->shipment_number }}
                        </a>
                        <span class="flex-1 text-sm text-slate-500 truncate">
                            {{ $shipment->vendor?->business_name ?: ($shipment->vendor?->name ?? '—') }}
                        </span>
                        <span class="flex items-center gap-1.5 text-sm text-slate-600 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full {{ $dot }} flex-shrink-0"></span>
                            {{ $lbl }}
                        </span>
                        <span class="text-xs text-slate-400 whitespace-nowrap hidden sm:block">
                            {{ $shipment->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right: Active Deliveries + Quick Stats (35%) --}}
        <div class="xl:col-span-2 space-y-8">

            {{-- Active Deliveries --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        Active Deliveries
                        @if($activeDeliveryRuns->isNotEmpty())
                            <span class="ml-1.5 text-sm font-normal text-slate-400">({{ $activeDeliveryRuns->count() }})</span>
                        @endif
                    </h2>
                    <a href="{{ route('admin.delivery-runs.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View all &rarr;</a>
                </div>

                @if($activeDeliveryRuns->isEmpty())
                    <p class="text-sm text-slate-400">No active deliveries.</p>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($activeDeliveryRuns as $run)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $run->run_number }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $run->assignedDriver?->name ?? 'Unassigned' }}</p>
                            </div>
                            <span class="text-xs text-slate-500">
                                {{ $run->stops->count() }} stop{{ $run->stops->count() !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Quick Stats --}}
            <div>
                <h2 class="text-base font-semibold text-slate-900 mb-4">Quick Stats</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Active vendors this month</span>
                        <span class="text-sm font-semibold text-slate-900">{{ number_format($activeVendorsMonth) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Invoiced this month</span>
                        <span class="text-sm font-semibold text-slate-900">GHS {{ number_format($totalInvoicedMonth, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Packages at warehouse</span>
                        <span class="text-sm font-semibold text-slate-900">{{ number_format($atWarehouse) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Drivers on duty</span>
                        <span class="text-sm font-semibold text-slate-900">{{ number_format($totalDrivers) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Pending invoices</span>
                        <span class="text-sm font-semibold text-slate-900">{{ number_format($pendingInvoices) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
