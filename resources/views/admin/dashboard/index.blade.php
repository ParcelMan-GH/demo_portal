@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- Map CSS & Custom Marker Styles --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* Custom Map Marker styles based on your image */
    .custom-map-marker {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: -30px;
    }
    .marker-label {
        background: white;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        color: #334155;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 4px;
        white-space: nowrap;
    }
    .marker-pin {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: #64748b; /* Gray default */
        border: 3px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        position: relative;
    }
    .marker-pin::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 6px;
        background-color: #64748b;
    }
    /* Active State for Marker */
    .custom-map-marker.active .marker-pin,
    .custom-map-marker.active .marker-pin::after {
        background-color: #0f172a; /* Black/Dark slate when selected */
    }
</style>

<!-- Add Alpine Data Object to wrap the content -->
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8" x-data="dashboardController()">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-slate-900">
            @php $hour = now()->hour; @endphp
            {{ $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening') }}, {{ $admin->name ?? 'Admin' }}
        </h1>
        <p class="text-slate-500 mt-1">It's {{ now()->format('l, M j, Y') }}.</p>
    </div>

    {{-- ═══ STATS GRID ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        
        {{-- Total Deliveries --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-slate-500">Total Deliveries</span>
                <a href="{{ route('admin.orders.index') ?? '#' }}" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
            </div>
            <div class="text-4xl font-normal text-slate-900 mb-6">{{ number_format($totalShipmentsMonth) }}</div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">vs last month</span>
                <span class="{{ $shipmentsChange >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                    {{ $shipmentsChange > 0 ? '+' : '' }}{{ $shipmentsChange }}%
                </span>
            </div>
        </div>

        {{-- Active Deliveries --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-slate-500">Active Deliveries (Today)</span>
                <a href="{{ route('admin.delivery-runs.index') ?? '#' }}" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
            </div>
            <div class="text-4xl font-normal text-slate-900 mb-6">{{ number_format($outForDelivery) }}</div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">vs yesterday</span>
                <span class="{{ $activeDeliveriesChange >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                    {{ $activeDeliveriesChange > 0 ? '+' : '' }}{{ $activeDeliveriesChange }}%
                </span>
            </div>
        </div>

        {{-- Total Vendors --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-slate-500">Total Vendors</span>
                <a href="#" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
            </div>
            <div class="text-4xl font-normal text-slate-900 mb-6">{{ number_format($totalVendors) }}</div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">vs last month</span>
                <span class="{{ $vendorsChange >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                    {{ $vendorsChange > 0 ? '+' : '' }}{{ $vendorsChange }}%
                </span>
            </div>
        </div>

        {{-- On Time Delivery --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-slate-500">On Time Delivery</span>
                <a href="#" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
            </div>
            <div class="text-4xl font-normal text-slate-900 mb-6">{{ $onTimeDeliveryRate }}%</div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">vs last month</span>
                <span class="{{ $onTimeChange >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                    {{ $onTimeChange > 0 ? '+' : '' }}{{ $onTimeChange }}%
                </span>
            </div>
        </div>

    </div>

    {{-- ═══ MAIN CONTENT GRID ═══ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left: Live Tracking Map --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 z-10">
                <h2 class="text-sm font-medium text-slate-700">Live Tracking</h2>
                <span class="text-xs text-slate-400 font-medium" x-show="riders.length" x-text="lastUpdated ? 'Updated ' + lastUpdated : ''"></span>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Search" class="pl-9 pr-4 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 w-48 transition-all">
                    </div>
                </div>
            </div>
            
            {{-- Map Area --}}
            <div class="relative flex-1 min-h-[500px] bg-slate-100 rounded-b-2xl overflow-hidden z-0" id="map-container" style="height: 500px;" wire:ignore>
               
               {{-- Empty state: no active deliveries right now --}}
               <div x-show="riders.length === 0"
                    class="absolute inset-0 flex flex-col items-center justify-center text-center bg-slate-50 z-[500]">
                   <div class="w-14 h-14 rounded-full bg-slate-200 flex items-center justify-center mb-3">
                       <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                   </div>
                   <p class="text-sm font-medium text-slate-700">No active deliveries</p>
                   <p class="text-xs text-slate-400 mt-1">Riders will appear here automatically when they're out on a run.</p>
               </div>

               {{-- EXACT FIXED SIDE PANEL FROM YOUR SCREENSHOT --}}
               <div x-show="selectedRider" style="display: none;"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    class="absolute top-4 right-4 bottom-4 w-80 bg-slate-50/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200 p-6 z-[1000] overflow-y-auto">
                    
                    <div class="absolute right-4 top-4 text-slate-400 cursor-pointer hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </div>

                    <!-- Carousel Arrows & Avatar -->
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <button class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm text-slate-400 hover:text-slate-600 border border-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="w-20 h-20 rounded-full bg-white border-2 border-white shadow-md overflow-hidden flex items-center justify-center">
                            <!-- Real Avatar bound from selectedRider -->
                            <img :src="selectedRider?.avatar" class="w-full h-full object-cover">
                        </div>
                        <button class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm text-slate-400 hover:text-slate-600 border border-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-slate-900" x-text="selectedRider?.name">Vincent</h3>
                        <span class="inline-block mt-1 text-xs font-semibold text-blue-800 bg-blue-100 px-3 py-0.5 rounded-full">Rider</span>
                    </div>

                    <!-- Stats Row -->
                    <div class="flex items-center justify-center gap-2 text-[11px] text-slate-500 mb-8">
                        <span><strong class="text-slate-900" x-text="selectedRider?.assigned">0</strong> Assigned</span>
                        <span>•</span>
                        <span><strong class="text-slate-900" x-text="selectedRider?.delivered">0</strong> Delivered</span>
                        <span>•</span>
                        <span><strong class="text-slate-900" x-text="selectedRider?.remaining">0</strong> Remaining</span>
                    </div>

                    <!-- Delivery Progress -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="font-medium flex items-center gap-1.5"><span class="text-orange-500 text-sm">🔥</span> Delivery Progress</span>
                            <span class="font-bold text-slate-900" x-text="`${selectedRider?.progress ?? 0}%`">0%</span>
                        </div>
                        <div class="w-full bg-white rounded-full h-2.5 shadow-inner">
                            <div class="bg-orange-500 h-2.5 rounded-full" :style="`width: ${selectedRider?.progress ?? 0}%`"></div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="relative pl-6 space-y-5 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-300">
                        <div class="relative">
                            <div class="absolute -left-[29px] top-0.5 w-4 h-4 bg-slate-900 rounded-full border-[3px] border-slate-50"></div>
                            <p class="text-sm font-bold text-slate-900">Current Location</p>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="selectedRider?.current_location">Unknown</p>
                        </div>
                        
                        <div class="relative">
                            <div class="absolute -left-[29px] top-0.5 w-4 h-4 bg-orange-500 rounded-full border-[3px] border-slate-50"></div>
                            <p class="text-sm font-bold text-slate-900">Next Stop</p>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="selectedRider?.next_stop">Pending</p>
                        </div>
                    </div>

               </div>
               {{-- END FIXED SIDE PANEL --}}
            </div>
        </div>

        {{-- Right: Activities Feed --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100">
                <h2 class="text-sm font-medium text-slate-700">Activities Feed</h2>
                <button class="flex items-center gap-1.5 px-3 py-1.5 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors">
                    Today
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[600px]">
                <div class="relative space-y-6 before:absolute before:inset-0 before:ml-2 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-100 before:to-transparent">
                    
                    @forelse($recentShipments as $shipment)
                        @php
                            $rawStatus = $shipment->status instanceof \BackedEnum ? $shipment->status->value : $shipment->status;
                            $cleanStatus = strtolower((string) $rawStatus);

                            $statusColor = match($cleanStatus) {
                                'delivered' => 'bg-emerald-500',
                                'in_transit' => 'bg-amber-500',
                                'at_warehouse' => 'bg-purple-500',
                                'submitted' => 'bg-blue-500',
                                default => 'bg-slate-400',
                            };
                            
                            $dateObj = $shipment->updated_at ?? $shipment->created_at;
                        @endphp
                        <div class="relative flex items-start gap-4">
                            <div class="w-4 h-4 rounded-full border-4 border-white shadow-sm {{ $statusColor }} flex-shrink-0 mt-1 z-10 relative"></div>
                            <div class="flex-1 min-w-0 border-b border-slate-100 pb-4">
                                <p class="text-sm text-slate-900 font-medium truncate">
                                    {{ $shipment->shipment_number }} advanced to <span class="capitalize">{{ str_replace('_', ' ', $cleanStatus) }}</span>
                                </p>
                                <p class="text-xs text-slate-400 mt-1">{{ $dateObj ? $dateObj->diffForHumans() : 'Recently' }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-sm text-slate-400 mt-4 relative z-10">No recent activity.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Scripts --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardController', () => ({
            selectedRider: null,
            riders: @json($activeRiders),
            markers: {},
            map: null,
            lastUpdated: '',
            pollTimer: null,

            init() {
                // Initialize Map centered on Accra
                this.map = L.map('map-container', {
                    zoomControl: false 
                }).setView([5.6200, -0.1700], 12);

                // Add Carto Light Map theme (matches the gray map style)
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO',
                    subdomains: 'abcd',
                    maxZoom: 20
                }).addTo(this.map);

                // Recalculate layout after the container has its final size
                // (prevents the blank grey map caused by a 0-height container)
                setTimeout(() => this.map.invalidateSize(), 150);

                // Add markers
                this.riders.forEach(rider => {
                    this.addRiderMarker(rider);
                });

                // Poll for live position updates every 12 seconds
                this.pollTimer = setInterval(() => this.refreshRiders(), 12000);
            },

            addRiderMarker(rider) {
                const el = document.createElement('div');
                el.className = 'custom-map-marker';
                el.innerHTML = `
                    <div class="marker-label">${rider.name}</div>
                    <div class="marker-pin"></div>
                `;

                const icon = L.divIcon({ html: el, className: '', iconSize: [40, 40], iconAnchor: [20, 20] });
                const marker = L.marker([rider.lat, rider.lng], { icon }).addTo(this.map);

                // Store the Leaflet marker object (not the DOM element)
                this.markers[rider.id] = marker;

                // Click event sets active rider in Alpine
                marker.on('click', () => {
                    this.selectRider(rider.id);
                });
            },

            async refreshRiders() {
                try {
                    const res = await fetch('/admin/dashboard/live-riders', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const riders = await res.json();

                    // Move existing markers to their latest positions
                    riders.forEach(rider => {
                        const marker = this.markers[rider.id];
                        if (marker && rider.lat && rider.lng) {
                            marker.setLatLng([rider.lat, rider.lng]);
                        }
                    });

                    // Remove markers for runs that are no longer active
                    const activeIds = new Set(riders.map(r => r.id));
                    Object.keys(this.markers).forEach(id => {
                        if (!activeIds.has(Number(id))) {
                            this.map.removeLayer(this.markers[id]);
                            delete this.markers[id];
                        }
                    });

                    this.riders = riders;

                    // Keep the side panel in sync with fresh data
                    if (this.selectedRider) {
                        const fresh = riders.find(r => r.id === this.selectedRider.id);
                        if (fresh) this.selectedRider = fresh;
                    }

                    this.lastUpdated = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                } catch (e) {
                    // Silent — the next poll will retry
                }
            },

            selectRider(id) {
                this.selectedRider = this.riders.find(r => r.id === id);

                // Reset all markers
                Object.values(this.markers).forEach(marker => {
                    marker.getElement()?.querySelector('.custom-map-marker')?.classList.remove('active');
                });

                // Set clicked marker to active (turns black)
                const marker = this.markers[id];
                if (marker) {
                    marker.getElement()?.querySelector('.custom-map-marker')?.classList.add('active');
                }
            },

            destroy() {
                if (this.pollTimer) clearInterval(this.pollTimer);
            }
        }))
    });
</script>
@endsection