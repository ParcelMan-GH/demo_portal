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
    /* A transporter carrying a batch between hubs, not a city delivery round. */
    .custom-map-marker.is-transport .marker-pin,
    .custom-map-marker.is-transport .marker-pin::after {
        background-color: #f97316; /* Orange */
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
                @if(is_null($onTimeChange))
                    {{-- No deliveries last month: there is no honest comparison. --}}
                    <span class="text-slate-400 font-medium" title="No deliveries were completed last month to compare against">—</span>
                 @else
                    <span class="{{ $onTimeChange >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                        {{ $onTimeChange > 0 ? '+' : '' }}{{ $onTimeChange }}%
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ MAIN CONTENT GRID ═══ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left: Live Tracking Map --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between gap-4 border-b border-slate-100">
                <div class="flex items-baseline gap-3 min-w-0">
                    <h2 class="text-sm font-medium text-slate-700 flex-shrink-0">Live Tracking</h2>
                    <span class="text-xs text-slate-400 font-medium truncate"
                          x-text="riders.length === 0
                              ? 'Nobody is out right now'
                              : riders.length + (riders.length === 1 ? ' rider on the road' : ' riders on the road')
                                + (lastUpdated ? ' · updated ' + lastUpdated : '')"></span>
                </div>
                <div class="relative flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Search riders" class="pl-9 pr-4 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 w-40 transition-all">
                </div>
            </div>
            
            {{--
                The panel is a map with a column beside it, not layers on top of it.

                The riders list and the selected rider's detail both used to float
                over the map, along with the empty state — three overlays stacked
                on one canvas, which left a strip of map between two cards and
                read as clutter. The list and the detail share one column now, and
                the map keeps the rest.
            --}}
            <div class="flex-1 flex flex-col lg:flex-row" style="height: 560px;">

            {{-- Map Area --}}
            <div class="relative flex-1 bg-slate-100 overflow-hidden" id="map-container" wire:ignore>

               {{-- Empty state: nobody is out --}}
               <div x-show="riders.length === 0"
                    class="absolute inset-0 flex flex-col items-center justify-center text-center bg-slate-50 z-[500]">
                   <div class="w-14 h-14 rounded-full bg-slate-200 flex items-center justify-center mb-3">
                       <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                   </div>
                    <p class="text-sm font-medium text-slate-700">No riders on the road</p>
                    <p class="text-xs text-slate-400 mt-1">Delivery riders and transporters carrying batches appear here as soon as they're out.</p>
                </div>
            </div>


            {{--
                Everyone who is out, and the detail of whoever is picked.

                They share one column: the list until a rider is chosen, that
                rider's detail after. Riders with no fix are listed as position
                unknown rather than dropped — "on a run, no fix yet" is not the
                same thing as "nobody is working".
            --}}
            <aside class="w-full lg:w-80 flex-shrink-0 border-t lg:border-t-0 lg:border-l border-slate-100 bg-white flex flex-col min-h-0">

                <div x-show="!selectedRider" class="flex flex-col flex-1 min-h-0">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                        <span class="text-xs font-semibold text-slate-600">On the road</span>
                        <span class="text-[11px] text-slate-400" x-text="`${riders.filter(r => r.has_position).length} located`"></span>
                    </div>

                    <div class="flex-1 overflow-y-auto min-h-0">
                        <template x-for="rider in visibleRiders" :key="rider.id">
                            <button type="button" @click="selectRider(rider.id)"
                                    class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
                                <img :src="rider.avatar" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-800 truncate" x-text="rider.full_name"></p>
                                    <p class="text-[11px] text-slate-500 truncate" x-text="rider.reference + (rider.destination ? ' → ' + rider.destination : '')"></p>
                                    <p class="text-[11px] mt-0.5"
                                       :class="rider.has_position ? 'text-emerald-600' : 'text-slate-400'"
                                       x-text="positionLabel(rider)"></p>
                                </div>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full flex-shrink-0"
                                      :class="rider.kind === 'transport' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"
                                      x-text="rider.kind === 'transport' ? 'Transport' : 'Delivery'"></span>
                            </button>
                        </template>

                        <p x-show="visibleRiders.length === 0" class="px-4 py-8 text-center text-xs text-slate-400"
                           x-text="riders.length === 0 ? 'Nobody is out right now.' : 'No rider matches that search.'"></p>
                    </div>
                </div>

               {{-- Whoever is picked --}}
               <div x-show="selectedRider" style="display: none;"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    class="flex-1 min-h-0 overflow-y-auto p-6">

                   <div class="flex justify-end">
                       <button type="button" @click="selectedRider = null"
                               class="text-slate-400 hover:text-slate-700 transition-colors"
                               title="Back to everyone on the road">
                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                       </button>
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
                        <h3 class="text-xl font-bold text-slate-900" x-text="selectedRider?.full_name || selectedRider?.name || 'Rider'">Rider</h3>
                        <p class="text-xs text-slate-500 mt-1 truncate" x-text="selectedRider?.reference"></p>
                        <span class="inline-block mt-2 text-xs font-semibold px-3 py-0.5 rounded-full"
                              :class="selectedRider?.kind === 'transport' ? 'text-amber-800 bg-amber-100' : 'text-blue-800 bg-blue-100'"
                              x-text="selectedRider?.kind === 'transport' ? 'Transporter' : 'Delivery rider'"></span>
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
                            <p class="text-[11px] mt-1 font-medium"
                               :class="selectedRider?.has_position ? 'text-emerald-600' : 'text-slate-400'"
                               x-text="positionLabel(selectedRider)"></p>
                        </div>
                        
                        <div class="relative">
                            <div class="absolute -left-[29px] top-0.5 w-4 h-4 bg-orange-500 rounded-full border-[3px] border-slate-50"></div>
                            <p class="text-sm font-bold text-slate-900">Next Stop</p>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="selectedRider?.next_stop">Pending</p>
                        </div>
                    </div>

               </div>
               {{-- END rider detail --}}
            </aside>
            </div>
        </div>

        {{-- Right: Activities Feed --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100">
                <h2 class="text-sm font-medium text-slate-700">Activities Feed</h2>
                {{--
                    Was a "Today" dropdown button with a chevron that had no
                    handler behind it: a control that promised a filter and did
                    nothing. The feed is already the newest events, so it says so.
                --}}
                <span class="text-xs text-slate-400 font-medium">Newest first</span>
            </div>
            
            {{--
                Real events, newest first.

                This used to list the ten most recent Shipment rows and nothing
                else, so the transport side of the operation — a batch raised,
                dispatched, arrived, received, a payment taken — never appeared.
                Every entry below is a timestamp the system actually wrote; if it
                did not happen, it is not listed.
            --}}
            <div class="p-6 overflow-y-auto max-h-[600px]">
                <div class="relative space-y-6 before:absolute before:inset-0 before:ml-2 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-100 before:to-transparent">

                    @forelse($activityFeedItems as $event)
                        @php
                            $dotColor = match($event['tone']) {
                                'emerald' => 'bg-emerald-500',
                                'amber' => 'bg-amber-500',
                                'violet' => 'bg-purple-500',
                                'blue' => 'bg-blue-500',
                                'orange' => 'bg-orange-500',
                                default => 'bg-slate-400',
                            };
                        @endphp
                        <div class="relative flex items-start gap-4">
                            <div class="w-4 h-4 rounded-full border-4 border-white shadow-sm {{ $dotColor }} flex-shrink-0 mt-1 z-10 relative"></div>
                            <div class="flex-1 min-w-0 border-b border-slate-100 pb-4">
                                <p class="text-sm text-slate-900 font-medium truncate">
                                    @if($event['url'])
                                        <a href="{{ $event['url'] }}" class="hover:text-orange-600 transition-colors">{{ $event['label'] }}</a>
                                    @else
                                        {{ $event['label'] }}
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5 truncate font-medium">{{ $event['detail'] }}</p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $event['at']->diffForHumans() }}
                                    @if($event['actor'])
                                        · {{ $event['actor'] }}
                                    @endif
                                </p>
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
            search: "",
            markers: {},
            map: null,

            /**
             * The list, narrowed by the search box.
             *
             * Filtering happens here rather than on the map: an admin looking for
             * one rider should not lose the others off the map, and pins follow
             * `riders`, not the filtered view.
             */
            get visibleRiders() {
                const term = String(this.search || "").trim().toLowerCase();
                if (!term) return this.riders;

                return this.riders.filter((rider) =>
                    [rider.full_name, rider.reference, rider.destination, rider.next_stop]
                        .some((field) => String(field || "").toLowerCase().includes(term)),
                );
            },
            lastUpdated: '',
            pollTimer: null,

            init() {
                // Initialize Map centered on Accra
                this.map = L.map('map-container', {
                    zoomControl: false 
                }).setView([5.6200, -0.1700], 12);

                // Free OpenStreetMap tiles (CartoDB now requires an API key and
                // overlays a "API KEY REQUIRED" watermark without one)
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(this.map);

                // Recalculate layout after the container has its final size
                // (prevents the blank grey map caused by a 0-height container)
                setTimeout(() => this.map.invalidateSize(), 150);

                // The riders column stacks under the map on narrow screens, which
                // changes the canvas size. Without this the map keeps drawing for
                // the old size — the "map showing half of Africa" symptom — until
                // the page is reloaded.
                window.addEventListener('resize', () => this.map && this.map.invalidateSize());

                // Same code path as every poll, so a rider who reports in later
                // appears without the admin reloading the page.
                this.syncMarkers();

                // Poll for live position updates every 12 seconds
                this.pollTimer = setInterval(() => this.refreshRiders(), 12000);
            },

            /**
             * What to say about where a rider is, in words.
             *
             * A rider with no fix is not hidden any more — "we have not heard
             * from this phone" is a different thing from "this rider is not
             * working", and the admin needs to know which one they are looking at.
             */
            positionLabel(rider) {
                if (!rider) return '';
                if (!rider.has_position) return 'Position unknown — no fix from the phone yet';
                if (rider.position_age_minutes === null || rider.position_age_minutes === undefined) return 'Position reported';

                const age = rider.position_age_minutes;
                if (age <= 1) return 'Live · reported just now';

                return (rider.position_is_live ? 'Live · ' : 'Last seen ') + age + ' min ago';
            },

            markerElement(rider) {
                // A transporter and a delivery rider are different jobs, so the pin
                // says which without the admin having to open the panel.
                const el = document.createElement('div');
                el.className = 'custom-map-marker' + (rider.kind === 'transport' ? ' is-transport' : '');
                el.innerHTML = `
                    <div class="marker-label">${rider.name}</div>
                    <div class="marker-pin"></div>
                `;
                return el;
            },

            /**
             * Put a pin on everyone we can place, and take pins off everyone we
             * cannot.
             *
             * Riders are keyed by string ids ("manifest-18", "run-3"), so the
             * removal check compares strings — it used to run `Number(id)` over
             * them, which is NaN for every one of these, so every pin was deleted
             * and re-added on every poll.
             */
            syncMarkers() {
                const placed = this.riders.filter(r => r.has_position);
                const placedIds = new Set(placed.map(r => String(r.id)));

                placed.forEach(rider => {
                    const id = String(rider.id);
                    const existing = this.markers[id];

                    if (existing) {
                        existing.setLatLng([rider.lat, rider.lng]);
                        return;
                    }

                    const icon = L.divIcon({ html: this.markerElement(rider), className: '', iconSize: [40, 40], iconAnchor: [20, 20] });
                    const marker = L.marker([rider.lat, rider.lng], { icon }).addTo(this.map);
                    marker.on('click', () => this.selectRider(rider.id));
                    this.markers[id] = marker;
                });

                Object.keys(this.markers).forEach(id => {
                    if (placedIds.has(String(id))) return;

                    this.map.removeLayer(this.markers[id]);
                    delete this.markers[id];
                });
            },

            async refreshRiders() {
                try {
                    const res = await fetch('/admin/dashboard/live-riders', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const riders = await res.json();

                    this.riders = riders;
                    this.syncMarkers();

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