@extends('admin.layouts.app')

@section('title', 'Edit Shipment — ' . $shipment->shipment_number)
@section('breadcrumb-parent', 'Shipments')
@section('breadcrumb-current', 'Edit ' . $shipment->shipment_number)

@section('content')
<div x-data="shipmentEditor()" x-init="init()" data-edit-config='@json($editConfig)'>

    <div class="max-w-5xl mx-auto pb-24">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 flex items-center justify-center transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-900">Edit Shipment <span class="text-slate-400 font-mono" x-text="shipment.shipment_number"></span></h1>
                <p class="text-xs text-slate-500 mt-0.5">Fill in missing details, manage packages, assign destinations</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600" x-text="shipment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold" :class="destinationMode === 'per_item' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'" x-text="destinationMode === 'per_item' ? 'Multiple Drop-offs' : 'One Drop-off'"></span>
            <button @@click="duplicateShipment()" :disabled="duplicating" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span x-text="duplicating ? 'Duplicating...' : 'Duplicate'"></span>
            </button>
        </div>
    </div>

    {{-- Sender Notes Banner --}}
    <template x-if="shipment.sender_notes">
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">Sender's Notes</h3>
                    <p class="text-sm text-amber-800 mt-1 leading-relaxed" x-text="shipment.sender_notes"></p>
                </div>
            </div>
        </div>
    </template>

    {{-- Step 2: Drop-off Type --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wide">Step 2</span>
                    <h2 class="text-sm font-bold text-slate-900">Drop-off Type</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Choose whether all packages share one address or each has its own.</p>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-3">
                <div @@click="if (destinationMode !== 'single') switchDestinationMode('single')" class="cursor-pointer p-4 rounded-2xl border-2 transition-all"
                     :class="destinationMode === 'single' ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-slate-300'">
                    <p class="text-sm font-bold text-slate-900">One Drop-off</p>
                    <p class="text-xs text-slate-500 mt-1">All packages go to one address</p>
                </div>
                <div @@click="if (destinationMode !== 'per_item') switchDestinationMode('per_item')" class="cursor-pointer p-4 rounded-2xl border-2 transition-all"
                     :class="destinationMode === 'per_item' ? 'border-violet-500 bg-violet-50' : 'border-slate-200 hover:border-slate-300'">
                    <p class="text-sm font-bold text-slate-900">Multiple Drop-offs</p>
                    <p class="text-xs text-slate-500 mt-1">Each package has its own delivery address</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 1: Pickup Details --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-violet-600 uppercase tracking-wide">Step 1</span>
                    <h2 class="text-sm font-bold text-slate-900">Pickup Details</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Fill in the pickup location details. The driver will use this to find the sender.</p>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Contact Name</label>
                    <input type="text" x-model="form.pickup.contact_name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Contact Phone</label>
                    <input type="text" x-model="form.pickup.contact_phone" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Region</label>
                    <select x-model="form.pickup.region_id" @@change="loadDistricts('pickup')" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        <option value="">Select Region</option>
                        <template x-for="r in regions" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">District</label>
                    <select x-model="form.pickup.district_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        <option value="">Select District</option>
                        <template x-for="d in pickupDistricts" :key="d.id"><option :value="String(d.id)" x-text="d.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Town / Area</label>
                    <input type="text" x-model="form.pickup.town" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Landmark</label>
                    <input type="text" x-model="form.pickup.landmark" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Instructions</label>
                    <textarea x-model="form.pickup.instructions" rows="2" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none resize-none"></textarea>
                </div>
            </div>
            {{-- Save button --}}
            <div class="mt-5 flex items-center justify-end gap-3">
                <span x-show="pickupSaved" x-transition.opacity class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Saved ✓
                </span>
                <button @@click="savePickup()" :disabled="savingPickup" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <svg x-show="savingPickup" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="savingPickup ? 'Saving...' : 'Save Pickup Details'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Step 3: Delivery Details (single mode only) --}}
    <template x-if="shipment.destination_mode === 'single'">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">Step 3</span>
                        <h2 class="text-sm font-bold text-slate-900">Delivery Details</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Where should the driver deliver? Set the recipient and address for all packages.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Delivery Preference</label>
                        <select x-model="form.delivery_preference" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                            <option value="deliver">Deliver to Recipient</option>
                            <option value="self_pickup">Recipient Collects</option>
                        </select>
                    </div>
                    <div x-show="form.delivery_preference === 'deliver'">
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Routing (Admin)</label>
                        <select x-model="form.fulfillment_type" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                            <option value="warehouse">Warehouse</option>
                            <option value="direct">Direct Delivery</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Name</label>
                        <input type="text" x-model="form.delivery.recipient_name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Phone</label>
                        <input type="text" x-model="form.delivery.recipient_phone" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Region</label>
                        <select x-model="form.delivery.region_id" @@change="loadDistricts('delivery')" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                            <option value="">Select Region</option>
                            <template x-for="r in regions" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">District</label>
                        <select x-model="form.delivery.district_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                            <option value="">Select District</option>
                            <template x-for="d in deliveryDistricts" :key="d.id"><option :value="String(d.id)" x-text="d.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Town / Area</label>
                        <input type="text" x-model="form.delivery.town" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Landmark</label>
                        <input type="text" x-model="form.delivery.landmark" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Delivery Instructions</label>
                        <textarea x-model="form.delivery.instructions" rows="2" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none resize-none"></textarea>
                    </div>
                </div>
                {{-- Save button --}}
                <div class="mt-5 flex items-center justify-end gap-3">
                    <span x-show="deliverySaved" x-transition.opacity class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Saved ✓
                    </span>
                    <button @@click="saveDelivery()" :disabled="savingDelivery" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-60">
                        <svg x-show="savingDelivery" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="savingDelivery ? 'Saving...' : 'Save Delivery Details'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Step 3 (per_item): Packages --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-orange-600 uppercase tracking-wide">Step 3</span>
                        <h2 class="text-sm font-bold text-slate-900">Packages</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600" x-text="packages.length + ' package' + (packages.length !== 1 ? 's' : '')"></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Describe each package. Upload photos for reference. Save each card individually.</p>
                </div>
            </div>
            <button @@click="addPackage()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Package
            </button>
        </div>
        <div class="p-6 space-y-6">
            <template x-if="packages.length === 0">
                <div class="text-center py-12 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-sm font-medium">No packages yet</p>
                    <p class="text-xs mt-1">Add a package to get started</p>
                </div>
            </template>
            <template x-for="(pkg, pkgIndex) in packages" :key="pkg.id">
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    {{-- Package Header --}}
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center" x-text="pkgIndex + 1"></span>
                            <span class="text-xs font-bold text-slate-700">Package <span x-text="pkgIndex + 1"></span></span>
                            <span x-show="pkg.tracking_code" class="text-[10px] font-mono text-slate-400" x-text="pkg.tracking_code"></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @@click="splitPackageModal(pkg)" x-show="pkg.photos.length > 1" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" title="Split Package">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </button>
                            <button @@click="deletePackage(pkg)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete Package">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Photos --}}
                    <div class="px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase">Photos (<span x-text="pkg.photos.length"></span>)</span>
                            <label class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-semibold text-blue-600 hover:bg-blue-50 rounded-lg cursor-pointer transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Upload
                                <input type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @@change="uploadPhotos(pkg, $event)">
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2" x-show="pkg.photos.length > 0">
                            <template x-for="photo in pkg.photos" :key="photo.id">
                                <div class="relative group w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100" x-data="{ moveOpen: false }">
                                    <img :src="photo.url" class="w-full h-full object-cover cursor-pointer" @@click="openLightbox(photo.url)">
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100">
                                        <button x-show="packages.length > 1" @@click.stop="moveOpen = !moveOpen" class="w-6 h-6 rounded-full bg-white/90 text-blue-600 flex items-center justify-center" title="Move to another package">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        </button>
                                        <button @@click="deletePhoto(pkg, photo)" class="w-6 h-6 rounded-full bg-white/90 text-rose-600 flex items-center justify-center" title="Delete">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                            </template>
                        </div>
                        <p x-show="pkg.photos.length === 0" class="text-xs text-slate-400 italic">No photos uploaded</p>
                    </div>

                    {{-- Package Fields --}}
                    <div class="px-5 py-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Description (what's inside)</label>
                                <textarea x-model="pkg.description" rows="2" placeholder="e.g. 2x Nike shoes, 1x phone case" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none resize-none"></textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">Qty <span class="text-slate-300 font-normal">(skip if sealed)</span></label>
                                <input type="number" x-model.number="pkg.quantity" min="1" placeholder="—" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                            </div>
                        </div>

                        {{-- Per-item delivery fields --}}
                        <template x-if="shipment.destination_mode === 'per_item'">
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <h4 class="text-[10px] font-semibold text-slate-400 uppercase mb-3">Delivery Details for this Package</h4>
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Delivery Preference</label>
                                        <select x-model="pkg.delivery_preference" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                            <option value="deliver">Deliver to Recipient</option>
                                            <option value="self_pickup">Recipient Collects</option>
                                        </select>
                                    </div>
                                    <div x-show="pkg.delivery_preference === 'deliver'">
                                        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Routing</label>
                                        <select x-model="pkg.fulfillment_type" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                            <option value="warehouse">Warehouse</option>
                                            <option value="direct">Direct</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Name</label>
                                        <input type="text" x-model="pkg.delivery_recipient_name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Phone</label>
                                        <input type="text" x-model="pkg.delivery_recipient_phone" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Town / Area</label>
                                        <input type="text" x-model="pkg.delivery_town" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Landmark</label>
                                        <input type="text" x-model="pkg.delivery_landmark" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Package Save button --}}
                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <span x-show="pkg._saved" x-transition.opacity class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Saved ✓
                            </span>
                            <button @@click="savePackage(pkg)" :disabled="pkg._saving" class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-60">
                                <svg x-show="pkg._saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="pkg._saving ? 'Saving...' : 'Save Package'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Assign Driver --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6" x-show="['submitted', 'invoice_accepted', 'pickup_assigned'].includes(shipment.status)">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Pickup Assignment</h2>
                    <p class="text-xs text-slate-500" x-text="currentAssignment ? 'Driver assigned — change before pickup starts' : 'Assign a driver to pick up this shipment'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <template x-if="!currentAssignment">
                    <button @@click="openAssignModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:opacity-90 text-white text-xs font-bold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Assign Driver
                    </button>
                </template>
                <template x-if="currentAssignment && currentAssignment.status === 'assigned' && !currentAssignment.picked_up_at">
                    <button @@click="openAssignModal(true)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-violet-200 text-violet-700 text-xs font-semibold rounded-xl hover:bg-violet-50 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Change Driver
                    </button>
                </template>
            </div>
        </div>
        {{-- Current assignment info --}}
        <template x-if="currentAssignment">
            <div class="px-6 py-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-sm" x-text="(currentAssignment.driver_name || '?').charAt(0).toUpperCase()"></div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-slate-900" x-text="currentAssignment.driver_name"></p>
                    <p class="text-xs text-slate-500" x-text="currentAssignment.driver_phone"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500" x-text="currentAssignment.warehouse_name"></p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                          :class="currentAssignment.status === 'assigned' ? 'bg-blue-100 text-blue-700' : currentAssignment.status === 'en_route' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'"
                          x-text="currentAssignment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Assign Driver Modal --}}
    <div x-show="assignModal.open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
        <div @@click.stop class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" x-text="assignModal.isEdit ? 'Change Driver' : 'Assign Driver'"></h3>
                    <p class="text-xs text-slate-500" x-text="assignModal.isEdit ? 'Select a new driver or warehouse' : 'Select driver and target warehouse'"></p>
                </div>
                <button @@click="assignModal.open = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Driver <span class="text-rose-500">*</span></label>
                    <select x-model="assignModal.driver_id" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-white text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 outline-none">
                        <option value="">Choose a driver...</option>
                        <template x-for="d in assignModal.drivers" :key="d.id">
                            <option :value="d.id" x-text="d.name + ' (' + d.phone + ')'"></option>
                        </template>
                    </select>
                    <p x-show="assignModal.drivers.length === 0 && !assignModal.loading" class="mt-1 text-xs text-amber-600">No available drivers</p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Target Warehouse <span class="text-rose-500">*</span></label>
                    <select x-model="assignModal.warehouse_id" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-white text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 outline-none">
                        <option value="">Choose warehouse...</option>
                        <template x-for="w in assignModal.warehouses" :key="w.id">
                            <option :value="w.id" x-text="w.name + (w.code ? ' (' + w.code + ')' : '')"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea x-model="assignModal.notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-white text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 outline-none resize-none" placeholder="Pickup notes for the driver..."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button @@click="assignModal.open = false" class="px-4 py-2 text-xs font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50">Cancel</button>
                    <button @@click="submitAssignment()" :disabled="assignModal.submitting || !assignModal.driver_id || !assignModal.warehouse_id" class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-violet-500 to-purple-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 transition-all">
                        <svg x-show="assignModal.submitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="assignModal.submitting ? (assignModal.isEdit ? 'Updating...' : 'Assigning...') : (assignModal.isEdit ? 'Update Assignment' : 'Assign Driver')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Bar --}}
    <div class="fixed bottom-0 left-0 right-0 z-20 bg-white/90 backdrop-blur-xl border-t border-slate-200 shadow-lg px-6 py-3 flex items-center justify-between">
        <a :href="config.showUrl" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Shipment
        </a>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600" x-text="shipment.status ? shipment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : ''"></span>
    </div>

    </div>

    {{-- Lightbox --}}
    <div x-show="lightboxUrl" @@click="lightboxUrl = null" x-transition.opacity class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-8 cursor-pointer" style="display:none">
        <img :src="lightboxUrl" class="max-w-full max-h-full rounded-xl shadow-2xl">
    </div>

    {{-- Split Package Modal --}}
    <div x-show="splitModal.open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
        <div @@click.stop class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Split Package</h3>
                <p class="text-xs text-slate-500 mt-1">Select photos to move to a new package</p>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-2">
                    <template x-for="photo in splitModal.photos" :key="photo.id">
                        <div @@click="toggleSplitPhoto(photo.id)" class="relative w-16 h-16 rounded-xl overflow-hidden border-2 cursor-pointer transition-all"
                             :class="splitModal.selectedIds.includes(photo.id) ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-slate-200'">
                            <img :src="photo.url" class="w-full h-full object-cover">
                            <div x-show="splitModal.selectedIds.includes(photo.id)" class="absolute inset-0 bg-blue-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button @@click="splitModal.open = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-800 border border-slate-200 rounded-xl">Cancel</button>
                <button @@click="executeSplit()" :disabled="splitModal.selectedIds.length === 0" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl disabled:opacity-50">
                    Split (<span x-text="splitModal.selectedIds.length"></span> photos)
                </button>
            </div>
        </div>
    </div>

</div>
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
        lightboxUrl: null,
        splitModal: { open: false, packageId: null, photos: [], selectedIds: [] },
        currentAssignment: null,
        assignModal: { open: false, isEdit: false, driver_id: '', warehouse_id: '', notes: '', drivers: [], warehouses: [], loading: false, submitting: false },

        init() {
            this.config = JSON.parse(this.$root.dataset.editConfig);
            this.shipment = this.config.shipment;
            this.regions = this.config.regions;
            this.packages = this.shipment.packages || [];
            this.destinationMode = this.shipment.destination_mode;
            this.form.pickup = { ...this.shipment.pickup };
            this.form.delivery = this.shipment.delivery ? { ...this.shipment.delivery } : {};
            this.form.delivery_preference = this.shipment.delivery_preference || 'deliver';
            this.form.fulfillment_type = this.shipment.fulfillment_type;

            // Fix region/district binding: coerce to string so <select> x-model matches
            this.form.pickup.region_id = String(this.shipment.pickup?.region_id || '');
            this.form.pickup.district_id = String(this.shipment.pickup?.district_id || '');
            this.form.delivery.region_id = String(this.shipment.delivery?.region_id || '');
            this.form.delivery.district_id = String(this.shipment.delivery?.district_id || '');

            this.currentAssignment = this.config.currentAssignment || null;

            if (this.form.pickup.region_id) this.loadDistricts('pickup');
            if (this.form.delivery.region_id) this.loadDistricts('delivery');
        },

        async loadDistricts(type) {
            const regionId = type === 'pickup' ? this.form.pickup.region_id : this.form.delivery.region_id;
            if (!regionId) { if (type === 'pickup') this.pickupDistricts = []; else this.deliveryDistricts = []; return; }
            const url = this.config.districtsByRegionUrlTemplate.replace('__REGION__', regionId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (type === 'pickup') this.pickupDistricts = data.data?.districts || [];
            else this.deliveryDistricts = data.data?.districts || [];
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
                delivery_town: pkg.delivery_town || null,
                delivery_landmark: pkg.delivery_landmark || null,
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

        async openAssignModal(isEdit = false) {
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
            } catch (e) { this._toast('Failed to load drivers/warehouses.', 'error'); }
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
                        driver_name: driver?.name || 'Driver',
                        driver_phone: driver?.phone || '',
                        target_warehouse_id: this.assignModal.warehouse_id,
                        warehouse_name: warehouse?.name || 'Warehouse',
                        picked_up_at: null,
                    };
                    if (res.data?.assignment?.id && !this.config.updateAssignmentEndpointTemplate) {
                        this.config.updateAssignmentEndpointTemplate = this.config.assignDriverEndpoint.replace(/assign.*$/, 'assignments/' + res.data.assignment.id + '/update');
                    }
                    this._toast(isEdit ? 'Driver changed successfully!' : 'Driver assigned successfully!', 'success');
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
