@extends('warehouse.layouts.app')

@section('title', 'Walk-in Receiving')

@section('content')

@php
$walkinConfig = [
    'vendorLookupUrl' => route('warehouse.walkin.vendor-lookup'),
    'vendorCreateUrl' => route('warehouse.walkin.vendor-create'),
    'storeUrl' => route('warehouse.walkin.store'),
    'locationSearchUrl' => route('warehouse.locations.search'),
    'warehouse' => ['id' => $warehouse->id, 'name' => $warehouse->name, 'code' => $warehouse->code],
];
@endphp

<div class="max-w-4xl mx-auto"
     x-data="walkinShipment()"
     x-init="init()"
     data-walkin-config='@json($walkinConfig)'>

    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Walk-in Vendor Receiving</h1>
                <p class="text-xs text-slate-500 mt-0.5">Receive items from a vendor at <span class="font-semibold">{{ $warehouse->name }}</span></p>
            </div>
            <a href="{{ route('warehouse.dashboard') }}" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-lg border border-slate-200 transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Step Indicator -->
    <div class="mb-6">
        <div class="flex items-center justify-between bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-4">
            <template x-for="(s, idx) in steps" :key="idx">
                <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                             :class="step > idx + 1 ? 'bg-emerald-500 text-white' : (step === idx + 1 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400')">
                            <template x-if="step > idx + 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step <= idx + 1">
                                <span x-text="idx + 1"></span>
                            </template>
                        </div>
                        <span class="text-xs font-semibold hidden sm:inline" :class="step === idx + 1 ? 'text-slate-900' : 'text-slate-400'" x-text="s"></span>
                    </div>
                    <template x-if="idx < steps.length - 1">
                        <div class="flex-1 h-px mx-4" :class="step > idx + 1 ? 'bg-emerald-300' : 'bg-slate-200'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Step 1: Vendor Identification -->
    <div x-show="step === 1" x-cloak>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Vendor Identification</h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Search for an existing vendor or create a new account</p>
            </div>
            <div class="p-6 space-y-5">
                <!-- Phone Lookup -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Vendor Phone Number</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="vendorPhone" placeholder="e.g. 0241234567"
                               @@keydown.enter.prevent="lookupVendor()"
                               class="flex-1 px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        <button @@click="lookupVendor()" :disabled="vendorLoading || vendorPhone.length < 9"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!vendorLoading">Search</span>
                            <span x-show="vendorLoading">Searching...</span>
                        </button>
                    </div>
                </div>

                <!-- Vendor Found -->
                <div x-show="vendorFound === true && vendorData" class="bg-emerald-50 rounded-xl border border-emerald-200 p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900" x-text="vendorData?.name"></p>
                            <p class="text-[11px] text-slate-500" x-text="vendorData?.business_name || 'No business name'"></p>
                            <p class="text-[11px] text-slate-500" x-text="vendorData?.phone"></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @@click="selectVendor(vendorData)" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors">Use This Vendor</button>
                        <button @@click="resetVendor()" class="px-4 py-1.5 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-lg border border-slate-200 transition-colors">Search Again</button>
                    </div>
                </div>

                <!-- Vendor Not Found -->
                <div x-show="vendorFound === false" class="bg-amber-50 rounded-xl border border-amber-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <p class="text-xs font-semibold text-amber-800">Vendor not found. Create a new account below.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Full Name *</label>
                            <input type="text" x-model="newVendor.name" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Business Name</label>
                            <input type="text" x-model="newVendor.business_name" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Phone *</label>
                            <input type="text" x-model="newVendor.phone" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg bg-slate-50 outline-none" readonly>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                            <input type="email" x-model="newVendor.email" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button @@click="createVendor()" :disabled="creatingVendor || !newVendor.name"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!creatingVendor">Create & Continue</span>
                            <span x-show="creatingVendor">Creating...</span>
                        </button>
                        <button @@click="resetVendor()" class="px-4 py-1.5 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-lg border border-slate-200 transition-colors">Cancel</button>
                    </div>
                    <p x-show="vendorError" class="mt-2 text-[11px] text-red-600 font-medium" x-text="vendorError"></p>
                </div>

                <!-- Selected Vendor -->
                <div x-show="vendorId && step === 1" class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Vendor: <span x-text="vendorData?.name"></span></p>
                                <p class="text-[11px] text-slate-500" x-text="vendorData?.phone"></p>
                            </div>
                        </div>
                        <button @@click="step = 2" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors">
                            Next: Items & Delivery
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Items & Delivery (same as admin but no warehouse selector) -->
    <div x-show="step === 2" x-cloak>
        <div class="space-y-5">
            <!-- Destination Mode -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-sm font-bold text-slate-900 mb-3">Destination Mode</h2>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" x-model="destinationMode" value="single" class="sr-only peer">
                        <div class="p-3 rounded-xl border-2 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 border-slate-200 hover:border-slate-300">
                            <p class="text-xs font-bold text-slate-900">Single Destination</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">All items go to one delivery address</p>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" x-model="destinationMode" value="per_item" class="sr-only peer">
                        <div class="p-3 rounded-xl border-2 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 border-slate-200 hover:border-slate-300">
                            <p class="text-xs font-bold text-slate-900">Per-Item Destination</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Each item has its own delivery address</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Shipment Items</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5"><span x-text="items.length"></span> item(s)</p>
                    </div>
                    <button @@click="addItem()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold rounded-lg transition-colors">+ Add Item</button>
                </div>
                <div class="divide-y divide-slate-100">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="p-5">
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 mt-1" x-text="idx + 1"></span>
                                <div class="flex-1 space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <div class="sm:col-span-3">
                                            <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Description *</label>
                                            <input type="text" x-model="item.description" placeholder="e.g. Box of electronics"
                                                   class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Qty *</label>
                                            <input type="number" x-model.number="item.quantity" min="1"
                                                   class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                                        </div>
                                    </div>
                                    <template x-if="destinationMode === 'per_item'">
                                        <div class="bg-slate-50 rounded-xl p-3 space-y-3 border border-slate-100">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Delivery Details</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Name *</label>
                                                    <input type="text" x-model="item.delivery.recipient_name" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Phone *</label>
                                                    <input type="text" x-model="item.delivery.recipient_phone" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                            </div>
                                            <div class="relative">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Delivery Location *</label>
                                                <input type="text" x-model="item.delivery.locationQuery"
                                                       @@input="searchLocation(item.delivery)"
                                                       @@focus="item.delivery.locationResults.length && (item.delivery._showDropdown = true)"
                                                       @@click.outside="item.delivery._showDropdown = false"
                                                       placeholder="Search town or area..."
                                                       class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                <div x-show="item.delivery._showDropdown && item.delivery.locationResults.length"
                                                     class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                                    <template x-for="loc in item.delivery.locationResults" :key="loc.id">
                                                        <button @@click="selectLocation(item.delivery, loc)" class="w-full text-left px-3 py-2 hover:bg-blue-50 text-xs text-slate-700 border-b border-slate-50 last:border-0" x-text="loc.display"></button>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Landmark</label>
                                                    <input type="text" x-model="item.delivery.landmark" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Instructions</label>
                                                    <input type="text" x-model="item.delivery.instructions" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <button x-show="items.length > 1" @@click="items.splice(idx, 1)" class="mt-1 p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Single Delivery Details -->
            <div x-show="destinationMode === 'single'" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-slate-900">Delivery Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Recipient Name *</label>
                        <input type="text" x-model="delivery.recipient_name" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Recipient Phone *</label>
                        <input type="text" x-model="delivery.recipient_phone" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Delivery Location *</label>
                    <input type="text" x-model="delivery.locationQuery"
                           @@input="searchLocation(delivery)"
                           @@focus="delivery.locationResults.length && (delivery._showDropdown = true)"
                           @@click.outside="delivery._showDropdown = false"
                           placeholder="Search town or area..."
                           class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    <div x-show="delivery._showDropdown && delivery.locationResults.length"
                         class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="loc in delivery.locationResults" :key="loc.id">
                            <button @@click="selectLocation(delivery, loc)" class="w-full text-left px-3 py-2 hover:bg-blue-50 text-xs text-slate-700 border-b border-slate-50 last:border-0" x-text="loc.display"></button>
                        </template>
                    </div>
                    <p x-show="delivery.selectedLocation" class="mt-1 text-[11px] text-emerald-600 font-medium" x-text="delivery.selectedLocation?.display"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Landmark</label>
                        <input type="text" x-model="delivery.landmark" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Instructions</label>
                        <input type="text" x-model="delivery.instructions" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between">
                <button @@click="step = 1" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-xl border border-slate-200 transition-colors">Back</button>
                <button @@click="goToReview()" :disabled="!canProceedToReview()"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    Review & Submit
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Review & Submit -->
    <div x-show="step === 3" x-cloak>
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-1">Vendor</h3>
                <p class="text-xs text-slate-600"><span x-text="vendorData?.name"></span> &mdash; <span x-text="vendorData?.phone"></span></p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900"><span x-text="items.length"></span> Item(s)</h3>
                </div>
                <div class="divide-y divide-slate-50">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="px-5 py-3">
                            <p class="text-xs font-semibold text-slate-900" x-text="item.description"></p>
                            <p class="text-[10px] text-slate-500">Qty: <span x-text="item.quantity"></span></p>
                            <template x-if="destinationMode === 'per_item' && item.delivery.recipient_name">
                                <p class="text-[10px] text-slate-400 mt-0.5">To: <span x-text="item.delivery.recipient_name"></span> &mdash; <span x-text="item.delivery.selectedLocation?.display || ''"></span></p>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="destinationMode === 'single'" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Delivery To</h3>
                <p class="text-sm font-semibold text-slate-900" x-text="delivery.recipient_name"></p>
                <p class="text-xs text-slate-500" x-text="delivery.recipient_phone"></p>
                <p class="text-xs text-slate-500 mt-1" x-text="delivery.selectedLocation?.display"></p>
            </div>

            <div class="flex items-center justify-between">
                <button @@click="step = 2" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-xl border border-slate-200 transition-colors">Back</button>
                <button @@click="submitShipment()" :disabled="submitting"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-sm">
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Submit Walk-in Shipment
                    </span>
                    <span x-show="submitting">Creating Shipment...</span>
                </button>
            </div>
            <div x-show="submitError" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="text-xs text-red-700 font-medium" x-text="submitError"></p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function walkinShipment() {
    return {
        config: {},
        steps: ['Vendor', 'Items & Delivery', 'Review'],
        step: 1,
        vendorPhone: '', vendorLoading: false, vendorFound: null, vendorData: null, vendorId: null, vendorError: '',
        newVendor: { name: '', business_name: '', phone: '', email: '' },
        creatingVendor: false,
        destinationMode: 'single',
        items: [],
        delivery: null,
        warehouseId: '',
        submitting: false, submitError: '',

        init() {
            const el = this.$el.closest('[data-walkin-config]');
            this.config = JSON.parse(el.dataset.walkinConfig);
            this.warehouseId = this.config.warehouse.id;
            this.items = [this.makeItem()];
            this.delivery = this.makeDelivery();
        },

        makeDelivery() {
            return {
                recipient_name: '', recipient_phone: '',
                locationQuery: '', locationResults: [], locationSearching: false,
                selectedLocation: null, _showDropdown: false,
                region_id: '', district_id: '', town: '',
                landmark: '', instructions: ''
            };
        },

        makeItem() {
            return {
                description: '', quantity: 1,
                delivery: this.makeDelivery ? this.makeDelivery() : {
                    recipient_name: '', recipient_phone: '',
                    locationQuery: '', locationResults: [], locationSearching: false,
                    selectedLocation: null, _showDropdown: false,
                    region_id: '', district_id: '', town: '',
                    landmark: '', instructions: ''
                }
            };
        },

        addItem() { this.items.push(this.makeItem()); },

        async lookupVendor() {
            if (this.vendorPhone.length < 9) return;
            this.vendorLoading = true; this.vendorFound = null; this.vendorData = null; this.vendorError = '';
            try {
                const res = await fetch(this.config.vendorLookupUrl + '?phone=' + encodeURIComponent(this.vendorPhone), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.found) { this.vendorFound = true; this.vendorData = json.vendor; }
                else { this.vendorFound = false; this.newVendor.phone = this.vendorPhone; }
            } catch { this.vendorError = 'Failed to lookup vendor.'; }
            this.vendorLoading = false;
        },

        selectVendor(v) { this.vendorId = v.id; this.vendorData = v; },
        resetVendor() { this.vendorFound = null; this.vendorData = null; this.vendorId = null; this.vendorError = ''; this.newVendor = { name: '', business_name: '', phone: '', email: '' }; },

        async createVendor() {
            if (!this.newVendor.name) return;
            this.creatingVendor = true; this.vendorError = '';
            try {
                const res = await fetch(this.config.vendorCreateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify(this.newVendor),
                });
                const json = await res.json();
                if (json.success) { this.selectVendor(json.vendor); this.vendorFound = true; this.vendorData = json.vendor; }
                else if (json.errors) { this.vendorError = Object.values(json.errors).flat().join(', '); }
                else { this.vendorError = json.message || 'Failed to create vendor.'; }
            } catch { this.vendorError = 'Failed to create vendor.'; }
            this.creatingVendor = false;
        },

        searchLocation(d) {
            const q = d.locationQuery;
            if (q.length < 2) { d.locationResults = []; d._showDropdown = false; return; }
            d.selectedLocation = null; d.region_id = ''; d.district_id = ''; d.town = '';
            clearTimeout(d._timeout);
            d._timeout = setTimeout(async () => {
                try {
                    const res = await fetch(this.config.locationSearchUrl + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                    const json = await res.json();
                    d.locationResults = json.locations || [];
                    d._showDropdown = d.locationResults.length > 0;
                } catch { d.locationResults = []; }
            }, 300);
        },

        selectLocation(d, loc) {
            d.selectedLocation = loc; d.locationQuery = loc.display; d.locationResults = []; d._showDropdown = false;
            d.region_id = loc.region.id; d.district_id = loc.district.id; d.town = loc.name;
        },

        canProceedToReview() {
            if (!this.items.length) return false;
            for (const item of this.items) {
                if (!item.description || !item.quantity) return false;
                if (this.destinationMode === 'per_item' && (!item.delivery.recipient_name || !item.delivery.recipient_phone || !item.delivery.region_id)) return false;
            }
            if (this.destinationMode === 'single' && (!this.delivery.recipient_name || !this.delivery.recipient_phone || !this.delivery.region_id)) return false;
            return true;
        },

        goToReview() { if (this.canProceedToReview()) this.step = 3; },

        async submitShipment() {
            this.submitting = true; this.submitError = '';
            const payload = {
                vendor_id: this.vendorId, destination_mode: this.destinationMode,
                items: this.items.map(i => ({
                    description: i.description, quantity: i.quantity,
                    delivery: this.destinationMode === 'per_item' ? {
                        recipient_name: i.delivery.recipient_name, recipient_phone: i.delivery.recipient_phone,
                        region_id: i.delivery.region_id, district_id: i.delivery.district_id, town: i.delivery.town,
                        landmark: i.delivery.landmark, instructions: i.delivery.instructions,
                    } : undefined,
                })),
            };
            if (this.destinationMode === 'single') {
                payload.delivery = {
                    recipient_name: this.delivery.recipient_name, recipient_phone: this.delivery.recipient_phone,
                    region_id: this.delivery.region_id, district_id: this.delivery.district_id, town: this.delivery.town,
                    landmark: this.delivery.landmark, instructions: this.delivery.instructions,
                };
            }
            try {
                const res = await fetch(this.config.storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (json.success) { window.location.href = json.redirect; }
                else if (json.errors) { this.submitError = Object.values(json.errors).flat().join(', '); }
                else { this.submitError = json.message || 'Failed to create shipment.'; }
            } catch { this.submitError = 'An unexpected error occurred.'; }
            this.submitting = false;
        },
    };
}
</script>
@endpush
