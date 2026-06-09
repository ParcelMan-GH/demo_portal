@extends('admin.layouts.app')

@section('title', 'Edit Shipment — ' . $shipment->shipment_number)
@section('breadcrumb-parent', 'Shipments')
@section('breadcrumb-current', 'Edit ' . $shipment->shipment_number)

@section('content')
<div x-data="shipmentEditor()" x-init="init()" data-edit-config='@json($editConfig)'>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HERO HEADER                                                --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30 mb-8">
        <div class="relative">
            {{-- Grid background pattern --}}
            <div class="absolute inset-0 opacity-[0.07]">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="editgrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#editgrid)"/>
                </svg>
            </div>
            {{-- Decorative glow --}}
            <div class="absolute top-0 right-0 w-96 h-64 bg-gradient-to-bl from-blue-500/20 via-violet-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative px-6 lg:px-8 py-6">
                {{-- Top row --}}
                <div class="flex items-center justify-between mb-6">
                    <a href="{{ route('admin.shipments.show', $shipment) }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-medium transition-all backdrop-blur-sm">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to Shipment
                    </a>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-white/10 text-white/80 border border-white/20 backdrop-blur-sm" x-text="shipment.status ? shipment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : ''"></span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold border backdrop-blur-sm"
                              :class="destinationMode === 'per_item' ? 'bg-violet-500/20 text-violet-200 border-violet-500/30' : 'bg-blue-500/20 text-blue-200 border-blue-500/30'"
                              x-text="destinationMode === 'per_item' ? 'Multiple Drop-offs' : 'One Drop-off'"></span>
                        <button @@click="duplicateShipment()" :disabled="duplicating"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-500/20 hover:bg-slate-500/30 text-slate-300 text-[10px] font-semibold rounded-lg border border-slate-500/30 transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="duplicating ? 'Duplicating...' : 'Duplicate'"></span>
                        </button>
                    </div>
                </div>

                {{-- Main hero row --}}
                <div class="flex items-start gap-5">
                    {{-- Icon --}}
                    <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold shadow-xl shadow-blue-500/30 ring-4 ring-white/10 flex-shrink-0">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    {{-- Info --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-semibold text-blue-400 uppercase tracking-widest">Editing Record</span>
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-black text-white tracking-tight font-mono" x-text="shipment.shipment_number || '{{ $shipment->shipment_number }}'"></h1>
                        <p class="text-sm text-slate-400 mt-1">{{ $shipment->vendor?->name ?? 'Unknown Vendor' }} &nbsp;·&nbsp; Fill in missing details, manage packages &amp; assign rider</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TWO-COLUMN LAYOUT                                          --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="max-w-[1600px] mx-auto pb-12 text-[13px]">
        <div class="flex flex-col lg:flex-row gap-4 items-start">

            {{-- ─── LEFT COLUMN (60%) ─────────────────────────────── --}}
            <div class="w-full lg:w-[62%] space-y-3">

                {{-- ─── SECTION: Drop-off Type ─────────────────────── --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 overflow-hidden"
                     style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.05s">
                    {{-- Section header --}}
                    <div class="flex items-center gap-0 border-b border-slate-100/80">
                        <div class="w-1.5 self-stretch bg-blue-500 rounded-l-3xl flex-shrink-0"></div>
                        <div class="flex items-center justify-between flex-1 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-[11px] font-black flex items-center justify-center flex-shrink-0">2</div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900 leading-tight">Drop-off Type</h2>
                                    <p class="text-[11px] text-slate-500 mt-0.5">One shared address or per-package addresses</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-5" :class="!canEditShipmentFields ? 'opacity-60 pointer-events-none' : ''">
                        <div class="grid grid-cols-2 gap-3">
                            <div @@click="if (canEditShipmentFields && destinationMode !== 'single') switchDestinationMode('single')"
                                 class="cursor-pointer p-4 rounded-2xl border-2 transition-all duration-200"
                                 :class="destinationMode === 'single' ? 'border-blue-500 bg-blue-50 shadow-sm shadow-blue-200/60' : 'border-slate-200 hover:border-blue-200 hover:bg-blue-50/40'">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                                         :class="destinationMode === 'single' ? 'border-blue-500 bg-blue-500' : 'border-slate-300'">
                                        <div x-show="destinationMode === 'single'" class="w-2 h-2 rounded-full bg-white"></div>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900">One Drop-off</p>
                                </div>
                                <p class="text-[11px] text-slate-500 ml-7">All packages go to one address</p>
                            </div>
                            <div @@click="if (canEditShipmentFields && destinationMode !== 'per_item') switchDestinationMode('per_item')"
                                 class="cursor-pointer p-4 rounded-2xl border-2 transition-all duration-200"
                                 :class="destinationMode === 'per_item' ? 'border-violet-500 bg-violet-50 shadow-sm shadow-violet-200/60' : 'border-slate-200 hover:border-violet-200 hover:bg-violet-50/40'">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                                         :class="destinationMode === 'per_item' ? 'border-violet-500 bg-violet-500' : 'border-slate-300'">
                                        <div x-show="destinationMode === 'per_item'" class="w-2 h-2 rounded-full bg-white"></div>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900">Multiple Drop-offs</p>
                                </div>
                                <p class="text-[11px] text-slate-500 ml-7">Each package has its own delivery address</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── SECTION: Pickup Details ─────────────────────── --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 overflow-hidden"
                     style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.1s">
                    <div class="flex items-center gap-0 border-b border-slate-100/80">
                        <div class="w-1.5 self-stretch bg-violet-500 rounded-l-3xl flex-shrink-0"></div>
                        <div class="flex items-center justify-between flex-1 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 text-[11px] font-black flex items-center justify-center flex-shrink-0">1</div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900 leading-tight">Pickup Details</h2>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Pickup location the rider will use to find the sender</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <fieldset :disabled="!canEditShipmentFields" class="p-4 space-y-3" :class="!canEditShipmentFields ? 'opacity-60 pointer-events-none' : ''">
                        {{-- Contact group --}}
                        <div class="bg-slate-50/60 border border-slate-100 rounded-xl p-3">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Contact
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Contact Name</label>
                                    <input type="text" x-model="form.pickup.contact_name"
                                           class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Contact Phone</label>
                                    <input type="text" x-model="form.pickup.contact_phone"
                                           class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        {{-- Location group --}}
                        <div class="bg-slate-50/60 border border-slate-100 rounded-xl p-3">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Location
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Region</label>
                                    <select x-model="form.pickup.region_id" @@change="loadDistricts('pickup')"
                                            class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all">
                                        <option value="">Select Region</option>
                                        <template x-for="r in regions" :key="r.id"><option :value="String(r.id)" :selected="String(r.id) === form.pickup.region_id" x-text="r.name"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">District</label>
                                    <select x-model="form.pickup.district_id"
                                            class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all">
                                        <option value="">Select District</option>
                                        <template x-for="d in pickupDistricts" :key="d.id"><option :value="String(d.id)" :selected="String(d.id) === form.pickup.district_id" x-text="d.name"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Town / Area</label>
                                    <input type="text" x-model="form.pickup.town"
                                           class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Landmark</label>
                                    <input type="text" x-model="form.pickup.landmark"
                                           class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Instructions</label>
                                    <textarea x-model="form.pickup.instructions" rows="2"
                                              class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none transition-all resize-none"></textarea>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    {{-- Footer --}}
                    <div x-show="canEditShipmentFields" class="px-5 py-3 border-t border-slate-100/80 bg-slate-50/30 rounded-b-3xl flex items-center justify-end gap-2">
                        <span x-show="pickupSaved" x-transition.opacity class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Saved
                        </span>
                        <button @@click="savePickup()" :disabled="savingPickup"
                                class="inline-flex items-center gap-1.5 px-5 py-2 bg-slate-900 hover:bg-slate-700 text-white text-[11px] font-semibold rounded-xl transition-all disabled:opacity-60 shadow-sm">
                            <svg x-show="savingPickup" class="w-3 h-3 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="savingPickup ? 'Saving…' : 'Save Pickup Details'"></span>
                        </button>
                    </div>
                </div>

                {{-- ─── SECTION: Delivery Details (single mode) ─────── --}}
                <template x-if="shipment.destination_mode === 'single'">
                    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 overflow-hidden"
                         style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.15s">
                        <div class="flex items-center gap-0 border-b border-slate-100/80">
                            <div class="w-1.5 self-stretch bg-emerald-500 rounded-l-3xl flex-shrink-0"></div>
                            <div class="flex items-center justify-between flex-1 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-black flex items-center justify-center flex-shrink-0">3</div>
                                    <div>
                                        <h2 class="text-sm font-bold text-slate-900 leading-tight">Delivery Details</h2>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Recipient and address for all packages</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <fieldset :disabled="!canEditShipmentFields" class="p-4 space-y-3" :class="!canEditShipmentFields ? 'opacity-60 pointer-events-none' : ''">
                            {{-- Routing preferences --}}
                            <div class="bg-slate-50/60 border border-slate-100 rounded-xl p-3">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Routing
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Delivery Preference</label>
                                        <select x-model="form.delivery_preference"
                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                            <option value="deliver">Deliver to Recipient</option>
                                            <option value="self_pickup">Recipient Collects</option>
                                        </select>
                                    </div>
                                    <div x-show="form.delivery_preference === 'deliver'">
                                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Routing (Admin)</label>
                                        <select x-model="form.fulfillment_type"
                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                            <option value="warehouse">Warehouse</option>
                                            <option value="direct">Direct Delivery</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Recipient group --}}
                            <div class="bg-slate-50/60 border border-slate-100 rounded-xl p-3">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Recipient
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Recipient Name</label>
                                        <input type="text" x-model="form.delivery.recipient_name"
                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Recipient Phone</label>
                                        <input type="text" x-model="form.delivery.recipient_phone"
                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                    </div>
                                </div>
                            </div>

                            {{-- Location group --}}
                            <div class="bg-slate-50/60 border border-slate-100 rounded-xl p-3">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Delivery Location
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Region</label>
                                        <select x-model="form.delivery.region_id" @@change="loadDistricts('delivery')"
                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                            <option value="">Select Region</option>
                                            <template x-for="r in regions" :key="r.id"><option :value="String(r.id)" :selected="String(r.id) === form.delivery.region_id" x-text="r.name"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">District</label>
                                        <select x-model="form.delivery.district_id"
                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                            <option value="">Select District</option>
                                            <template x-for="d in deliveryDistricts" :key="d.id"><option :value="String(d.id)" :selected="String(d.id) === form.delivery.district_id" x-text="d.name"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Town / Area</label>
                                        <input type="text" x-model="form.delivery.town"
                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Landmark</label>
                                        <input type="text" x-model="form.delivery.landmark"
                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Delivery Instructions</label>
                                        <textarea x-model="form.delivery.instructions" rows="2"
                                                  class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all resize-none"></textarea>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        {{-- Footer --}}
                        <div x-show="canEditShipmentFields" class="px-5 py-3 border-t border-slate-100/80 bg-slate-50/30 rounded-b-3xl flex items-center justify-end gap-2">
                            <span x-show="deliverySaved" x-transition.opacity class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Saved
                            </span>
                            <button @@click="saveDelivery()" :disabled="savingDelivery"
                                    class="inline-flex items-center gap-1.5 px-5 py-2 bg-slate-900 hover:bg-slate-700 text-white text-[11px] font-semibold rounded-xl transition-all disabled:opacity-60 shadow-sm">
                                <svg x-show="savingDelivery" class="w-3 h-3 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="savingDelivery ? 'Saving…' : 'Save Delivery Details'"></span>
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ─── SECTION: Packages ───────────────────────────── --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 overflow-hidden"
                     style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.2s">
                    <div class="flex items-center gap-0 border-b border-slate-100/80">
                        <div class="w-1.5 self-stretch bg-orange-500 rounded-l-3xl flex-shrink-0"></div>
                        <div class="flex items-center justify-between flex-1 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-orange-100 text-orange-700 text-[11px] font-black flex items-center justify-center flex-shrink-0">4</div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900 leading-tight">
                                        Packages
                                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-orange-100 text-orange-700" x-text="packages.length"></span>
                                    </h2>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Describe each package, upload photos, save individually</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Phone grouping now runs automatically when the vendor submits an order. --}}
                                <button @@click="addPackage()"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-900 text-white text-[11px] font-semibold rounded-xl hover:bg-slate-700 transition-all shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add Package
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 space-y-3">
                        {{-- Empty state --}}
                        <template x-if="packages.length === 0">
                            <div class="text-center py-14">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-orange-50 border-2 border-dashed border-orange-200 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-500">No packages yet</p>
                                <p class="text-xs text-slate-400 mt-1">Click "Add Package" to get started</p>
                            </div>
                        </template>

                        {{-- Package cards --}}
                        <template x-for="(pkg, pkgIndex) in packages" :key="pkg.id">
                            <div class="border border-slate-200/80 rounded-2xl overflow-hidden bg-white shadow-sm"
                                 x-data="{ expanded: true }">
                                {{-- Package card header --}}
                                <div class="px-4 py-3 bg-gradient-to-r from-slate-50 to-orange-50/40 border-b border-slate-200/60 flex items-center justify-between cursor-pointer select-none"
                                     @@click="expanded = !expanded">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-6 h-6 rounded-lg bg-orange-500 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0" x-text="pkgIndex + 1"></div>
                                        <span class="text-xs font-bold text-slate-800">Package <span x-text="pkgIndex + 1"></span></span>
                                        <span x-show="pkg.tracking_code" class="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded" x-text="pkg.tracking_code"></span>
                                        <span x-show="pkg.description" class="text-[11px] text-slate-400 truncate max-w-32" x-text="pkg.description"></span>
                                    </div>
                                    <div class="flex items-center gap-1" @@click.stop>
                                        <button @@click="splitPackageModal(pkg)" x-show="pkg.photos.length > 1"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" title="Split Package">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        </button>
                                        <button @@click="deletePackage(pkg)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete Package">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        <button @@click="expanded = !expanded"
                                                class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 transition-colors ml-1">
                                            <svg class="w-4 h-4 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="expanded" x-collapse>
                                    {{-- Photos section --}}
                                    <div class="px-4 py-3.5 border-b border-slate-100/80 bg-slate-50/30">
                                        <div class="flex items-center justify-between mb-2.5">
                                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Photos (<span x-text="pkg.photos.length"></span>)</span>
                                            <label class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold text-blue-600 hover:bg-blue-50 rounded-lg cursor-pointer transition-colors border border-blue-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Upload Photos
                                                <input type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @@change="uploadPhotos(pkg, $event)">
                                            </label>
                                        </div>
                                        <div class="flex flex-wrap gap-2" x-show="pkg.photos.length > 0">
                                            <template x-for="photo in pkg.photos" :key="photo.id">
                                                <div class="flex flex-col items-center gap-1">
                                                <div class="relative group w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-sm" x-data="{ moveOpen: false }">
                                                    <img :src="photo.url" class="w-full h-full object-cover cursor-pointer" @@click="openLightbox(photo.url)">
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-all flex items-center justify-center gap-1.5 opacity-0 group-hover:opacity-100">
                                                        <button x-show="packages.length > 1" @@click.stop="moveOpen = !moveOpen"
                                                                class="w-7 h-7 rounded-full bg-white/90 text-blue-600 flex items-center justify-center shadow" title="Move photo">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                        </button>
                                                        <button @@click="deletePhoto(pkg, photo)"
                                                                class="w-7 h-7 rounded-full bg-white/90 text-rose-600 flex items-center justify-center shadow" title="Delete photo">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                    {{-- Move dropdown --}}
                                                    <div x-show="moveOpen" @@click.away="moveOpen = false" x-transition
                                                         class="absolute top-full left-0 mt-1 w-36 bg-white rounded-xl border border-slate-200 shadow-xl z-30 py-1" style="display:none">
                                                        <p class="px-3 py-1 text-[9px] font-semibold text-slate-400 uppercase">Move to</p>
                                                        <template x-for="(targetPkg, tIdx) in packages.filter(p => p.id !== pkg.id)" :key="targetPkg.id">
                                                            <button @@click="movePhoto(pkg, photo, targetPkg); moveOpen = false"
                                                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2">
                                                                <span class="w-4 h-4 rounded bg-orange-500 text-white text-[8px] font-bold flex items-center justify-center" x-text="packages.indexOf(targetPkg) + 1"></span>
                                                                <span x-text="'Package ' + (packages.indexOf(targetPkg) + 1)"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                                <template x-if="photo.recipient_phone">
                                                    <span class="flex items-center gap-0.5 text-[9px] font-semibold text-indigo-600 max-w-20 truncate" :title="photo.recipient_phone">
                                                        <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                        <span x-text="photo.recipient_phone"></span>
                                                    </span>
                                                </template>
                                                </div>
                                            </template>
                                        </div>
                                        <p x-show="pkg.photos.length === 0" class="text-xs text-slate-400 italic">No photos uploaded yet</p>
                                    </div>

                                    {{-- Package fields --}}
                                    <div class="px-4 py-4">
                                        <div class="grid grid-cols-2 gap-3 mb-4">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Description <span class="text-slate-300 font-normal normal-case">(what's inside)</span></label>
                                                <input type="text" x-model="pkg.description" placeholder="e.g. 2x Nike shoes, 1x phone case"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-400 outline-none transition-all">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Quantity <span class="text-slate-300 font-normal normal-case">(skip if sealed)</span></label>
                                                <input type="number" x-model.number="pkg.quantity" min="1" placeholder="—"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-400 outline-none transition-all">
                                            </div>
                                        </div>

                                        {{-- Per-item delivery fields --}}
                                        <template x-if="shipment.destination_mode === 'per_item'">
                                            <div class="mt-1 bg-slate-50/70 border border-slate-100 rounded-xl p-3">
                                                <h4 class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                                    <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                                    Delivery details for this package
                                                </h4>
                                                <div class="grid grid-cols-2 gap-3 mb-3">
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Delivery Preference</label>
                                                        <select x-model="pkg.delivery_preference"
                                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                            <option value="deliver">Deliver to Recipient</option>
                                                            <option value="self_pickup">Recipient Collects</option>
                                                        </select>
                                                    </div>
                                                    <div x-show="pkg.delivery_preference === 'deliver'">
                                                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Routing</label>
                                                        <select x-model="pkg.fulfillment_type"
                                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                            <option value="warehouse">Warehouse</option>
                                                            <option value="direct">Direct</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Recipient Name</label>
                                                        <input type="text" x-model="pkg.delivery_recipient_name"
                                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Recipient Phone</label>
                                                        <input type="text" x-model="pkg.delivery_recipient_phone"
                                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Region</label>
                                                        <select x-model="pkg.delivery_region_id" @@change="loadPackageDistricts(pkg)"
                                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                            <option value="">Select Region</option>
                                                            <template x-for="r in regions" :key="r.id"><option :value="String(r.id)" :selected="String(r.id) === pkg.delivery_region_id" x-text="r.name"></option></template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">District</label>
                                                        <select x-model="pkg.delivery_district_id"
                                                                class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                            <option value="">Select District</option>
                                                            <template x-for="d in (pkg._districts || [])" :key="d.id"><option :value="String(d.id)" :selected="String(d.id) === pkg.delivery_district_id" x-text="d.name"></option></template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Town / Area</label>
                                                        <input type="text" x-model="pkg.delivery_town"
                                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Landmark</label>
                                                        <input type="text" x-model="pkg.delivery_landmark"
                                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all">
                                                    </div>
                                                    <div class="col-span-2">
                                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Delivery Instructions</label>
                                                        <input type="text" x-model="pkg.delivery_instructions"
                                                               class="w-full px-3 py-2 text-sm border border-slate-200/70 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none transition-all"
                                                               placeholder="e.g. Call before delivery">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Package save footer --}}
                                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                                            <span x-show="pkg._saved"
                                                  x-transition:enter="transition ease-out duration-200"
                                                  x-transition:enter-start="opacity-0 translate-x-2"
                                                  x-transition:enter-end="opacity-100 translate-x-0"
                                                  x-transition:leave="transition ease-in duration-150"
                                                  x-transition:leave-start="opacity-100"
                                                  x-transition:leave-end="opacity-0"
                                                  class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Saved
                                            </span>
                                            <button @@click="savePackage(pkg)" :disabled="pkg._saving"
                                                    class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-slate-900 hover:bg-slate-700 text-white text-[11px] font-semibold rounded-xl transition-all disabled:opacity-60 shadow-sm">
                                                <svg x-show="pkg._saving" class="w-3 h-3 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span x-text="pkg._saving ? 'Saving…' : 'Save Package'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>{{-- end left column --}}

            {{-- ─── RIGHT COLUMN (40%) ─────────────────────────────── --}}
            <div class="w-full lg:w-[38%] space-y-3 lg:sticky lg:top-4">

                {{-- ─── Progress Stepper ───────────────────────────── --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 overflow-hidden"
                     style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.08s">
                    <div class="px-5 py-4 border-b border-slate-100/80 flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800">Completion Checklist</h3>
                            <p class="text-[11px] text-slate-400">Track your progress filling this record</p>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="space-y-2">
                            {{-- Step: Sender notes --}}
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                     :class="shipment.sender_notes ? 'bg-emerald-500' : 'bg-slate-100 border-2 border-slate-200'">
                                    <svg x-show="shipment.sender_notes" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="!shipment.sender_notes" class="text-[10px] font-bold text-slate-400">1</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">Review Sender Notes</p>
                                    <p class="text-[11px] text-slate-400">Check vendor's instructions</p>
                                </div>
                                <div class="text-[10px] font-semibold"
                                     :class="shipment.sender_notes ? 'text-emerald-600' : 'text-slate-300'">
                                    <span x-text="shipment.sender_notes ? 'Done' : 'N/A'"></span>
                                </div>
                            </div>
                            <div class="h-px bg-slate-100 ml-9"></div>
                            {{-- Step: Drop-off type --}}
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                     :class="destinationMode ? 'bg-emerald-500' : 'bg-blue-500 animate-pulse'">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">Set Drop-off Type</p>
                                    <p class="text-[11px] text-slate-400" x-text="destinationMode === 'per_item' ? 'Multiple Drop-offs selected' : 'One Drop-off selected'"></p>
                                </div>
                                <span class="text-[10px] font-semibold text-emerald-600">Done</span>
                            </div>
                            <div class="h-px bg-slate-100 ml-9"></div>
                            {{-- Step: Pickup details --}}
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                     :class="(form.pickup.contact_name && form.pickup.town) ? 'bg-emerald-500' : 'bg-slate-100 border-2 border-slate-200'">
                                    <svg x-show="form.pickup.contact_name && form.pickup.town" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="!(form.pickup.contact_name && form.pickup.town)" class="text-[10px] font-bold text-slate-400">3</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">Fill Pickup Details</p>
                                    <p class="text-[11px] text-slate-400">Contact name + town required</p>
                                </div>
                                <span class="text-[10px] font-semibold"
                                      :class="(form.pickup.contact_name && form.pickup.town) ? 'text-emerald-600' : 'text-amber-500'"
                                      x-text="(form.pickup.contact_name && form.pickup.town) ? 'Done' : 'Pending'"></span>
                            </div>
                            <div class="h-px bg-slate-100 ml-9"></div>
                            {{-- Step: Delivery details --}}
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                     :class="((shipment.destination_mode === 'single' && form.delivery.recipient_name) || (shipment.destination_mode === 'per_item' && packages.some(p => p.delivery_recipient_name))) ? 'bg-emerald-500' : 'bg-slate-100 border-2 border-slate-200'">
                                    <svg x-show="(shipment.destination_mode === 'single' && form.delivery.recipient_name) || (shipment.destination_mode === 'per_item' && packages.some(p => p.delivery_recipient_name))" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="!((shipment.destination_mode === 'single' && form.delivery.recipient_name) || (shipment.destination_mode === 'per_item' && packages.some(p => p.delivery_recipient_name)))" class="text-[10px] font-bold text-slate-400">4</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">Fill Delivery Details</p>
                                    <p class="text-[11px] text-slate-400" x-text="shipment.destination_mode === 'per_item' ? 'Set per-package delivery' : 'Recipient name required'"></p>
                                </div>
                                <span class="text-[10px] font-semibold"
                                      :class="((shipment.destination_mode === 'single' && form.delivery.recipient_name) || (shipment.destination_mode === 'per_item' && packages.some(p => p.delivery_recipient_name))) ? 'text-emerald-600' : 'text-amber-500'"
                                      x-text="((shipment.destination_mode === 'single' && form.delivery.recipient_name) || (shipment.destination_mode === 'per_item' && packages.some(p => p.delivery_recipient_name))) ? 'Done' : 'Pending'"></span>
                            </div>
                            <div class="h-px bg-slate-100 ml-9"></div>
                            {{-- Step: Packages described --}}
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                     :class="packages.length > 0 && packages.some(p => p.description) ? 'bg-emerald-500' : 'bg-slate-100 border-2 border-slate-200'">
                                    <svg x-show="packages.length > 0 && packages.some(p => p.description)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="!(packages.length > 0 && packages.some(p => p.description))" class="text-[10px] font-bold text-slate-400">5</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">Review Packages</p>
                                    <p class="text-[11px] text-slate-400">At least 1 package described</p>
                                </div>
                                <span class="text-[10px] font-semibold"
                                      :class="packages.length > 0 && packages.some(p => p.description) ? 'text-emerald-600' : 'text-amber-500'"
                                      x-text="packages.length > 0 && packages.some(p => p.description) ? 'Done' : 'Pending'"></span>
                            </div>
                            <div class="h-px bg-slate-100 ml-9"></div>
                            {{-- Step: Rider assigned --}}
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                     :class="currentAssignment ? 'bg-emerald-500' : 'bg-slate-100 border-2 border-slate-200'">
                                    <svg x-show="currentAssignment" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="!currentAssignment" class="text-[10px] font-bold text-slate-400">6</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">Assign Rider</p>
                                    <p class="text-[11px] text-slate-400">Assign pickup rider & warehouse</p>
                                </div>
                                <span class="text-[10px] font-semibold"
                                      :class="currentAssignment ? 'text-emerald-600' : 'text-amber-500'"
                                      x-text="currentAssignment ? 'Done' : 'Pending'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Sender Notes Card ──────────────────────────── --}}
                <template x-if="shipment.sender_notes">
                    <div class="rounded-3xl overflow-hidden shadow-md shadow-amber-200/40 border border-amber-200/60"
                         style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.12s">
                        <div class="flex">
                            <div class="w-1.5 bg-amber-400 flex-shrink-0"></div>
                            <div class="flex-1 bg-gradient-to-br from-amber-50 to-orange-50/60 p-5">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-xs font-bold text-amber-900 mb-1.5">Sender's Notes</h3>
                                        <p class="text-sm text-amber-800 leading-relaxed" x-text="shipment.sender_notes"></p>
                                        <p class="text-[11px] text-amber-600/70 mt-3 font-medium">{{ $shipment->vendor?->name ?? '' }}{{ $shipment->vendor?->phone ? ' · ' . $shipment->vendor->phone : '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ─── Assignment / Driver Card ───────────────────── --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 overflow-hidden"
                     x-show="['submitted', 'processing', 'pickup_assigned'].includes(shipment.status)"
                     style="animation: fadeSlideUp 0.35s ease both; animation-delay: 0.16s">
                    <div class="flex items-center gap-0 border-b border-slate-100/80">
                        <div class="w-1.5 self-stretch bg-indigo-500 rounded-l-3xl flex-shrink-0"></div>
                        <div class="flex items-center justify-between flex-1 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-[11px] font-black flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800">Pickup Assignment</h3>
                                    <p class="text-[11px] text-slate-400" x-text="currentAssignment ? 'Rider assigned' : 'No rider assigned yet'"></p>
                                </div>
                            </div>
                            {{-- Assign / change buttons --}}
                            <div>
                                <template x-if="!currentAssignment">
                                    <button @@click="openAssignModal()"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gradient-to-r from-indigo-500 to-violet-600 hover:opacity-90 text-white text-[11px] font-bold rounded-xl shadow-md shadow-indigo-400/30 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Assign Rider
                                    </button>
                                </template>
                                <template x-if="currentAssignment && currentAssignment.status === 'assigned' && !currentAssignment.picked_up_at">
                                    <button @@click="openAssignModal(true)"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white border border-indigo-200 text-indigo-700 text-[11px] font-semibold rounded-xl hover:bg-indigo-50 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Change Rider
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Not assigned state --}}
                    <template x-if="!currentAssignment">
                        <div class="px-5 py-8 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-indigo-50 border-2 border-dashed border-indigo-200 flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-500">No rider assigned</p>
                            <p class="text-xs text-slate-400 mt-1">Assign a rider to begin the pickup process</p>
                        </div>
                    </template>

                    {{-- Assigned rider info --}}
                    <template x-if="currentAssignment">
                        <div class="px-5 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-indigo-300/40 flex-shrink-0"
                                     x-text="(currentAssignment.driver_name || '?').charAt(0).toUpperCase()"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900" x-text="currentAssignment.driver_name"></p>
                                    <p class="text-xs text-slate-500" x-text="currentAssignment.driver_phone"></p>
                                    <p class="text-[11px] text-slate-400 mt-0.5" x-text="currentAssignment.warehouse_name"></p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold"
                                          :class="currentAssignment.status === 'assigned' ? 'bg-blue-100 text-blue-700' : currentAssignment.status === 'en_route' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'"
                                          x-text="currentAssignment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>{{-- end right column --}}

        </div>{{-- end two-column flex --}}
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- MODALS                                                     --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

    {{-- Assign Rider Modal --}}
    <div x-show="assignModal.open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display:none">
        <div @@click.stop class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 w-full max-w-md overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-indigo-50/40">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900" x-text="assignModal.isEdit ? 'Change Rider' : 'Assign Rider'"></h3>
                        <p class="text-xs text-slate-500" x-text="assignModal.isEdit ? 'Select a new rider or warehouse' : 'Select rider and target warehouse'"></p>
                    </div>
                </div>
                <button @@click="assignModal.open = false" class="w-8 h-8 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Rider <span class="text-rose-500">*</span></label>
                    <select x-model="assignModal.driver_id"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 outline-none transition-all">
                        <option value="">Choose a rider...</option>
                        <template x-for="d in assignModal.drivers" :key="d.id">
                            <option :value="d.id" x-text="d.name + ' (' + d.phone + ')'"></option>
                        </template>
                    </select>
                    <p x-show="assignModal.drivers.length === 0 && !assignModal.loading" class="mt-1.5 text-xs text-amber-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        No available riders
                    </p>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Target Warehouse <span class="text-rose-500">*</span></label>
                    <select x-model="assignModal.warehouse_id"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 outline-none transition-all">
                        <option value="">Choose warehouse...</option>
                        <template x-for="w in assignModal.warehouses" :key="w.id">
                            <option :value="w.id" x-text="w.name + (w.code ? ' (' + w.code + ')' : '')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Notes <span class="text-slate-300 font-normal normal-case">(optional)</span></label>
                    <textarea x-model="assignModal.notes" rows="2"
                              class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 outline-none transition-all resize-none"
                              placeholder="Pickup notes for the rider..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button @@click="assignModal.open = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                    <button @@click="submitAssignment()"
                            :disabled="assignModal.submitting || !assignModal.driver_id || !assignModal.warehouse_id"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-500 to-violet-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-400/30 disabled:opacity-50 transition-all hover:opacity-90">
                        <svg x-show="assignModal.submitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="assignModal.submitting ? (assignModal.isEdit ? 'Updating...' : 'Assigning...') : (assignModal.isEdit ? 'Update Assignment' : 'Assign Rider')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Lightbox --}}
    <div x-show="lightboxUrl" @@click="lightboxUrl = null" x-transition.opacity
         class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-8 cursor-zoom-out" style="display:none">
        <img :src="lightboxUrl" class="max-w-full max-h-full rounded-2xl shadow-2xl ring-1 ring-white/10">
    </div>

    {{-- Split Package Modal --}}
    <div x-show="splitModal.open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display:none">
        <div @@click.stop class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 w-full max-w-md overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-indigo-50/30">
                <h3 class="text-sm font-bold text-slate-900">Split Package</h3>
                <p class="text-xs text-slate-500 mt-0.5">Select photos to move into a new package</p>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-2.5">
                    <template x-for="photo in splitModal.photos" :key="photo.id">
                        <div @@click="toggleSplitPhoto(photo.id)"
                             class="relative w-18 h-18 rounded-xl overflow-hidden border-2 cursor-pointer transition-all"
                             :class="splitModal.selectedIds.includes(photo.id) ? 'border-indigo-500 ring-2 ring-indigo-500/30 scale-95' : 'border-slate-200 hover:border-indigo-300'">
                            <img :src="photo.url" class="w-full h-full object-cover" style="width:4.5rem;height:4.5rem">
                            <div x-show="splitModal.selectedIds.includes(photo.id)"
                                 class="absolute inset-0 bg-indigo-500/25 flex items-center justify-center">
                                <div class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center shadow">
                                    <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button @@click="splitModal.open = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                <button @@click="executeSplit()" :disabled="splitModal.selectedIds.length === 0"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:opacity-90 rounded-xl disabled:opacity-50 shadow-md shadow-indigo-300/30 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Split (<span x-text="splitModal.selectedIds.length"></span> photos)
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BOTTOM FIXED BAR                                           --}}

</div>

<style>
@keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
@endsection

@push('scripts')
<script>
function shipmentEditor() {
    return {
        config: {},
        shipment: {},
        regions: [],
        packages: [],
        destinationMode: 'single',
        form: { pickup: {}, delivery: {}, delivery_preference: 'deliver', fulfillment_type: null },
        pickupDistricts: [],
        deliveryDistricts: [],
        savingPickup: false,
        pickupSaved: false,
        savingDelivery: false,
        deliverySaved: false,
        duplicating: false,
        autoGrouping: false,
        lightboxUrl: null,
        splitModal: { open: false, packageId: null, photos: [], selectedIds: [] },
        currentAssignment: null,
        canEditShipmentFields: true,
        assignModal: { open: false, isEdit: false, driver_id: '', warehouse_id: '', notes: '', drivers: [], warehouses: [], loading: false, submitting: false },

        init() {
            this.config = JSON.parse(this.$root.dataset.editConfig);
            this.shipment = this.config.shipment;
            this.regions = this.config.regions;
            this.packages = (this.shipment.packages || []).map(pkg => ({
                ...pkg,
                delivery_region_id: String(pkg.delivery_region_id || ''),
                delivery_district_id: String(pkg.delivery_district_id || ''),
                _districts: [],
                _saving: false,
                _saved: false,
            }));
            // Load districts for packages that have a region set
            this.packages.forEach(pkg => {
                if (pkg.delivery_region_id) this.loadPackageDistricts(pkg);
            });
            this.destinationMode = this.shipment.destination_mode;
            const p = this.shipment.pickup || {};
            this.form.pickup = {
                contact_name: p.contact_name || '',
                contact_phone: p.contact_phone || '',
                region_id: p.region_id ? String(p.region_id) : '',
                district_id: p.district_id ? String(p.district_id) : '',
                town: p.town || '',
                landmark: p.landmark || '',
                instructions: p.instructions || '',
            };
            const d = this.shipment.delivery || {};
            this.form.delivery = {
                recipient_name: d.recipient_name || '',
                recipient_phone: d.recipient_phone || '',
                region_id: d.region_id ? String(d.region_id) : '',
                district_id: d.district_id ? String(d.district_id) : '',
                town: d.town || '',
                landmark: d.landmark || '',
                instructions: d.instructions || '',
            };
            this.form.delivery_preference = this.shipment.delivery_preference || 'deliver';
            this.form.fulfillment_type = this.shipment.fulfillment_type;

            this.currentAssignment = this.config.currentAssignment || null;
            this.canEditShipmentFields = this.config.canEditShipmentFields ?? true;

            if (this.form.pickup.region_id) this.loadDistricts('pickup');
            if (this.form.delivery.region_id) this.loadDistricts('delivery');
        },

        async loadDistricts(type) {
            const regionId = type === 'pickup' ? this.form.pickup.region_id : this.form.delivery.region_id;
            if (!regionId) { if (type === 'pickup') this.pickupDistricts = []; else this.deliveryDistricts = []; return; }
            // Save current district_id before loading (will re-apply after)
            const savedDistrictId = type === 'pickup' ? this.form.pickup.district_id : this.form.delivery.district_id;
            const url = this.config.districtsByRegionUrlTemplate.replace('__REGION__', regionId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (type === 'pickup') this.pickupDistricts = data.data?.districts || [];
            else this.deliveryDistricts = data.data?.districts || [];
            // Re-apply district_id after options are available
            await this.$nextTick();
            if (type === 'pickup') this.form.pickup.district_id = savedDistrictId;
            else this.form.delivery.district_id = savedDistrictId;
        },

        async loadPackageDistricts(pkg) {
            if (!pkg.delivery_region_id) { pkg._districts = []; return; }
            const savedDistrictId = pkg.delivery_district_id;
            const url = this.config.districtsByRegionUrlTemplate.replace('__REGION__', pkg.delivery_region_id);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            pkg._districts = data.data?.districts || [];
            await this.$nextTick();
            pkg.delivery_district_id = savedDistrictId;
        },

        async savePickup() {
            this.savingPickup = true;
            try {
                const payload = {
                    destination_mode: this.destinationMode,
                    pickup_contact_name: this.form.pickup.contact_name || null,
                    pickup_contact_phone: this.form.pickup.contact_phone || null,
                    pickup_region_id: this.form.pickup.region_id || null,
                    pickup_district_id: this.form.pickup.district_id || null,
                    pickup_town: this.form.pickup.town || null,
                    pickup_landmark: this.form.pickup.landmark || null,
                    pickup_instructions: this.form.pickup.instructions || null,
                };
                const res = await this._fetch(this.config.saveUrl, { method: 'PUT', body: JSON.stringify(payload) });
                if (res.success) {
                    this.pickupSaved = true;
                    setTimeout(() => this.pickupSaved = false, 2000);
                } else {
                    this._toast(res.message || 'Failed to save pickup details.', 'error');
                }
            } catch (e) { this._toast(e.message || 'Error saving pickup details.', 'error'); }
            finally { this.savingPickup = false; }
        },

        async saveDelivery() {
            this.savingDelivery = true;
            try {
                const payload = {
                    destination_mode: this.destinationMode,
                    delivery_preference: this.form.delivery_preference,
                    fulfillment_type: this.form.delivery_preference === 'deliver' ? (this.form.fulfillment_type || 'warehouse') : null,
                    delivery_recipient_name: this.form.delivery.recipient_name || null,
                    delivery_recipient_phone: this.form.delivery.recipient_phone || null,
                    delivery_region_id: this.form.delivery.region_id || null,
                    delivery_district_id: this.form.delivery.district_id || null,
                    delivery_town: this.form.delivery.town || null,
                    delivery_landmark: this.form.delivery.landmark || null,
                    delivery_instructions: this.form.delivery.instructions || null,
                };
                const res = await this._fetch(this.config.saveUrl, { method: 'PUT', body: JSON.stringify(payload) });
                if (res.success) {
                    this.deliverySaved = true;
                    setTimeout(() => this.deliverySaved = false, 2000);
                } else {
                    this._toast(res.message || 'Failed to save delivery details.', 'error');
                }
            } catch (e) { this._toast(e.message || 'Error saving delivery details.', 'error'); }
            finally { this.savingDelivery = false; }
        },

        async savePackage(pkg) {
            pkg._saving = true;
            const url = this.config.updatePackageUrlTemplate.replace('__PKG__', pkg.id);
            const payload = {
                description: pkg.description || null,
                quantity: pkg.quantity || 1,
                delivery_preference: pkg.delivery_preference || null,
                fulfillment_type: pkg.fulfillment_type || null,
                delivery_recipient_name: pkg.delivery_recipient_name || null,
                delivery_recipient_phone: pkg.delivery_recipient_phone || null,
                delivery_region_id: pkg.delivery_region_id || null,
                delivery_district_id: pkg.delivery_district_id || null,
                delivery_town: pkg.delivery_town || null,
                delivery_landmark: pkg.delivery_landmark || null,
                delivery_instructions: pkg.delivery_instructions || null,
            };
            try {
                const res = await this._fetch(url, { method: 'PUT', body: JSON.stringify(payload) });
                if (res.success) {
                    pkg._saved = true;
                    setTimeout(() => pkg._saved = false, 2000);
                } else {
                    this._toast(res.message || 'Failed to save package.', 'error');
                }
            } catch (e) { this._toast(e.message || 'Error saving package.', 'error'); }
            finally { pkg._saving = false; }
        },

        async addPackage() {
            const res = await this._fetch(this.config.addPackageUrl, { method: 'POST', body: JSON.stringify({}) });
            if (res.success && res.data?.package) {
                this.packages.push({ ...res.data.package, photos: [], delivery_preference: 'deliver', fulfillment_type: null, delivery_recipient_name: null, delivery_recipient_phone: null, delivery_town: null, delivery_landmark: null, _saved: false, _saving: false });
                this._toast('Package added.', 'success');
            }
        },

        async autoGroupByPhone() {
            if (this.autoGrouping) return;
            this.autoGrouping = true;
            try {
                const res = await this._fetch(this.config.autoGroupByPhoneUrl, { method: 'POST', body: JSON.stringify({}) });
                if (res.success) {
                    this._toast(res.message, 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this._toast(res.message || 'Auto-group failed.', 'error');
                    this.autoGrouping = false;
                }
            } catch (e) {
                this._toast(e.message || 'Error during auto-group.', 'error');
                this.autoGrouping = false;
            }
        },

        async deletePackage(pkg) {
            if (!confirm('Delete this package and all its photos?')) return;
            const url = this.config.deletePackageUrlTemplate.replace('__PKG__', pkg.id);
            const res = await this._fetch(url, { method: 'DELETE' });
            if (res.success) {
                this.packages = this.packages.filter(p => p.id !== pkg.id);
                this._toast('Package deleted.', 'success');
            }
        },

        async uploadPhotos(pkg, event) {
            const files = event.target.files;
            if (!files.length) return;
            const fd = new FormData();
            for (let f of files) fd.append('photos[]', f);
            const url = this.config.uploadPhotosUrlTemplate.replace('__PKG__', pkg.id);
            const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: fd });
            const data = await res.json();
            if (data.success && data.data?.photos) {
                pkg.photos.push(...data.data.photos);
                this._toast(data.message, 'success');
            }
            event.target.value = '';
        },

        async deletePhoto(pkg, photo) {
            const url = this.config.deletePhotoUrlTemplate.replace('__IMG__', photo.id);
            const res = await this._fetch(url, { method: 'DELETE' });
            if (res.success) {
                pkg.photos = pkg.photos.filter(p => p.id !== photo.id);
            }
        },

        async movePhoto(fromPkg, photo, toPkg) {
            const res = await this._fetch(this.config.movePhotoUrl, {
                method: 'POST',
                body: JSON.stringify({ photo_id: photo.id, target_package_id: toPkg.id })
            });
            if (res.success) {
                fromPkg.photos = fromPkg.photos.filter(p => p.id !== photo.id);
                toPkg.photos.push(photo);
                this._toast('Photo moved.', 'success');
            } else {
                this._toast(res.message || 'Failed to move.', 'error');
            }
        },

        openLightbox(url) { this.lightboxUrl = url; },

        splitPackageModal(pkg) {
            this.splitModal = { open: true, packageId: pkg.id, photos: [...pkg.photos], selectedIds: [] };
        },

        toggleSplitPhoto(id) {
            const idx = this.splitModal.selectedIds.indexOf(id);
            if (idx >= 0) this.splitModal.selectedIds.splice(idx, 1);
            else this.splitModal.selectedIds.push(id);
        },

        async executeSplit() {
            if (!this.splitModal.selectedIds.length) return;
            const url = this.config.splitPackageUrlTemplate.replace('__PKG__', this.splitModal.packageId);
            const res = await this._fetch(url, { method: 'POST', body: JSON.stringify({ photo_ids: this.splitModal.selectedIds }) });
            if (res.success && res.data?.package) {
                const srcPkg = this.packages.find(p => p.id === this.splitModal.packageId);
                if (srcPkg) {
                    srcPkg.photos = srcPkg.photos.filter(p => !this.splitModal.selectedIds.includes(p.id));
                }
                this.packages.push({ ...res.data.package, delivery_preference: 'deliver', fulfillment_type: null, delivery_recipient_name: null, delivery_recipient_phone: null, delivery_town: null, delivery_landmark: null, _saved: false, _saving: false });
                this.splitModal.open = false;
                this._toast(res.message, 'success');
            }
        },

        validateBeforeAssign() {
            const errors = [];

            // Always required
            if (!this.form.pickup.contact_name?.trim()) errors.push('Pickup contact name is required.');
            if (!this.form.pickup.contact_phone?.trim()) errors.push('Pickup contact phone is required.');
            if (!this.form.pickup.town?.trim() && !this.form.pickup.region_id) errors.push('Pickup location is required (at least a town or region).');
            if (this.packages.length === 0) errors.push('At least one package is required.');

            // Direct delivery — need delivery details
            const isDirect = this.form.fulfillment_type === 'direct';

            if (isDirect && this.destinationMode === 'single') {
                if (!this.form.delivery.recipient_name?.trim()) errors.push('Delivery recipient name is required for direct delivery.');
                if (!this.form.delivery.recipient_phone?.trim()) errors.push('Delivery recipient phone is required for direct delivery.');
                if (!this.form.delivery.town?.trim() && !this.form.delivery.region_id) errors.push('Delivery location is required for direct delivery (at least a town or region).');
            }

            if (isDirect && this.destinationMode === 'per_item') {
                this.packages.forEach((pkg, i) => {
                    const ft = pkg.fulfillment_type || 'warehouse';
                    if (ft === 'direct') {
                        if (!pkg.delivery_recipient_name?.trim()) errors.push(`Package ${i + 1}: recipient name required for direct delivery.`);
                        if (!pkg.delivery_recipient_phone?.trim()) errors.push(`Package ${i + 1}: recipient phone required for direct delivery.`);
                        if (!pkg.delivery_town?.trim() && !pkg.delivery_region_id) errors.push(`Package ${i + 1}: delivery location required for direct delivery.`);
                    }
                });
            }

            return errors;
        },

        async openAssignModal(isEdit = false) {
            // Validate before opening (skip validation for edit/change rider)
            if (!isEdit) {
                const errors = this.validateBeforeAssign();
                if (errors.length > 0) {
                    this._toast(errors[0], 'error');
                    return;
                }
            }
            this.assignModal.open = true;
            this.assignModal.isEdit = isEdit;
            this.assignModal.driver_id = isEdit && this.currentAssignment ? String(this.currentAssignment.driver_id) : '';
            this.assignModal.warehouse_id = isEdit && this.currentAssignment ? String(this.currentAssignment.target_warehouse_id) : '';
            this.assignModal.notes = '';
            this.assignModal.loading = true;
            try {
                const [driversRes, warehousesRes] = await Promise.all([
                    fetch(this.config.availableDriversEndpoint + '?assignment_type=pickup', { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
                    fetch(this.config.availableWarehousesEndpoint, { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
                ]);
                this.assignModal.drivers = driversRes.data || driversRes || [];
                this.assignModal.warehouses = warehousesRes.data || warehousesRes || [];
            } catch (e) { this._toast('Failed to load riders/warehouses.', 'error'); }
            finally { this.assignModal.loading = false; }
        },

        async submitAssignment() {
            this.assignModal.submitting = true;
            const isEdit = this.assignModal.isEdit;
            const url = isEdit ? this.config.updateAssignmentEndpointTemplate : this.config.assignDriverEndpoint;
            const method = isEdit ? 'PUT' : 'POST';
            try {
                const res = await this._fetch(url, {
                    method: method,
                    body: JSON.stringify({
                        driver_id: this.assignModal.driver_id,
                        target_warehouse_id: this.assignModal.warehouse_id,
                        notes: this.assignModal.notes || null,
                    })
                });
                if (res.success) {
                    this.assignModal.open = false;
                    this.shipment.status = 'pickup_assigned';
                    const driver = this.assignModal.drivers.find(d => d.id == this.assignModal.driver_id);
                    const warehouse = this.assignModal.warehouses.find(w => w.id == this.assignModal.warehouse_id);
                    this.currentAssignment = {
                        id: this.currentAssignment?.id || res.data?.assignment?.id,
                        status: 'assigned',
                        driver_id: this.assignModal.driver_id,
                        driver_name: driver?.name || 'Rider',
                        driver_phone: driver?.phone || '',
                        target_warehouse_id: this.assignModal.warehouse_id,
                        warehouse_name: warehouse?.name || 'Warehouse',
                        picked_up_at: null,
                    };
                    if (res.data?.assignment?.id && !this.config.updateAssignmentEndpointTemplate) {
                        this.config.updateAssignmentEndpointTemplate = this.config.assignDriverEndpoint.replace(/assign.*$/, 'assignments/' + res.data.assignment.id + '/update');
                    }
                    this._toast(isEdit ? 'Rider changed successfully!' : 'Rider assigned successfully!', 'success');
                } else {
                    this._toast(res.message || 'Failed to assign.', 'error');
                }
            } catch (e) { this._toast(e.message || 'Error.', 'error'); }
            finally { this.assignModal.submitting = false; }
        },

        async switchDestinationMode(newMode) {
            const oldMode = this.shipment.destination_mode;
            if (newMode === oldMode) return;

            const msg = newMode === 'per_item'
                ? 'Switching to per-package destinations will clear the shipment-level delivery address. Each package will need its own delivery details. Continue?'
                : 'Switching to single destination will clear individual delivery details from all packages. You\'ll set one address for everything. Continue?';

            if (!confirm(msg)) { this.destinationMode = oldMode; return; }

            const res = await this._fetch(this.config.saveUrl, {
                method: 'PUT',
                body: JSON.stringify({ destination_mode: newMode })
            });

            if (res.success) {
                this.shipment.destination_mode = newMode;
                if (newMode === 'per_item') {
                    this.form.delivery = {};
                    this.form.delivery_preference = null;
                    this.form.fulfillment_type = null;
                } else {
                    this.packages.forEach(pkg => {
                        pkg.delivery_recipient_name = null;
                        pkg.delivery_recipient_phone = null;
                        pkg.delivery_town = null;
                        pkg.delivery_landmark = null;
                        pkg.delivery_preference = null;
                        pkg.fulfillment_type = null;
                    });
                }
                this._toast('Destination mode updated.', 'success');
            } else {
                this.destinationMode = oldMode;
                this._toast(res.message || 'Failed.', 'error');
            }
        },

        async duplicateShipment() {
            if (!confirm('Create a duplicate of this shipment as a draft? All packages and photos will be copied.')) return;
            this.duplicating = true;
            try {
                const url = this.config.duplicateUrl;
                const res = await this._fetch(url, { method: 'POST', body: JSON.stringify({}) });
                if (res.success && res.data?.edit_url) {
                    this._toast('Shipment duplicated! Redirecting...', 'success');
                    setTimeout(() => window.location.href = res.data.edit_url, 1000);
                } else {
                    this._toast(res.message || 'Failed to duplicate.', 'error');
                }
            } catch (e) { this._toast(e.message || 'Error.', 'error'); }
            finally { this.duplicating = false; }
        },

        async _fetch(url, options = {}) {
            const res = await fetch(url, { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, ...options });
            return await res.json();
        },

        _toast(message, type = 'success') {
            const container = document.getElementById('admin-toast-container');
            if (!container) return;
            const el = document.createElement('div');
            el.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-xs font-medium transition-all ${type === 'success' ? 'bg-white border-emerald-100 text-emerald-800' : 'bg-white border-rose-100 text-rose-700'}`;
            el.innerHTML = `<span class="w-2 h-2 rounded-full flex-shrink-0 ${type === 'success' ? 'bg-emerald-400' : 'bg-rose-400'}"></span><span>${message}</span>`;
            container.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        },
    };
}
</script>
@endpush
