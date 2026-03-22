@extends('admin.layouts.app')

@section('title', 'Create Walk-in Shipment')
@section('breadcrumb-parent', 'Shipments')
@section('breadcrumb-current', 'Walk-in Shipment')

@section('content')

@php
$walkinConfig = [
    'vendorLookupUrl' => route('admin.shipments.vendor-lookup'),
    'vendorCreateUrl' => route('admin.shipments.vendor-create'),
    'storeUrl' => route('admin.shipments.store'),
    'locationSearchUrl' => route('admin.locations.search'),
    'warehouses' => $warehouses,
];
@endphp

<div x-data="walkinShipment()" x-init="init()" data-walkin-config='@json($walkinConfig)'>

    <div class="max-w-3xl mx-auto">

    <!-- Hero -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl shadow-2xl shadow-slate-900/30 mb-6">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs><pattern id="agrid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/></pattern></defs>
                    <rect width="100" height="100" fill="url(#agrid)"/>
                </svg>
            </div>
            <div class="relative px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.shipments.index') }}" class="group w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white flex items-center justify-center transition-all">
                            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <div>
                            <h1 class="text-lg font-bold text-white tracking-tight">Walk-in Shipment</h1>
                            <p class="text-xs text-slate-400 mt-0.5">Create a shipment for a walk-in vendor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stepper -->
    <div class="mb-6">
        <div class="flex items-center">
            <template x-for="(s, idx) in steps" :key="idx">
                <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                    <button @@click="idx + 1 < step && (step = idx + 1)" class="flex items-center gap-2 group" :class="idx + 1 < step ? 'cursor-pointer' : 'cursor-default'">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition-all duration-300 border-2"
                             :class="step > idx + 1 ? 'bg-emerald-500 border-emerald-500 text-white shadow-md shadow-emerald-500/30' : (step === idx + 1 ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-white border-slate-200 text-slate-400')">
                            <template x-if="step > idx + 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step <= idx + 1">
                                <span x-text="idx + 1"></span>
                            </template>
                        </div>
                        <span class="text-xs font-semibold hidden sm:inline transition-colors"
                              :class="step === idx + 1 ? 'text-slate-900' : (step > idx + 1 ? 'text-emerald-600' : 'text-slate-400')"
                              x-text="s"></span>
                    </button>
                    <template x-if="idx < steps.length - 1">
                        <div class="flex-1 h-[2px] mx-4 rounded-full transition-all duration-500" :class="step > idx + 1 ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- ═══════════ STEP 1: VENDOR ═══════════ -->
    <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                        Vendor Identification
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 ml-[42px]">Enter the vendor's phone number to look up or create their account</p>
                </div>
                <div class="p-6 space-y-5">
                    <!-- Phone Lookup -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Phone Number</label>
                        <div class="flex gap-2.5">
                            <div class="relative flex-1">
                                <div class="absolute left-0 top-0 bottom-0 w-11 flex items-center justify-center bg-slate-100 border-r border-slate-200 rounded-l-xl text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <input type="text" x-model="vendorPhone" placeholder="e.g. 0241234567"
                                       @@keydown.enter.prevent="lookupVendor()"
                                       class="w-full pl-14 pr-4 py-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none placeholder:text-slate-400 font-mono">
                            </div>
                            <button @@click="lookupVendor()" :disabled="vendorLoading || vendorPhone.length < 9"
                                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-lg hover:shadow-blue-600/25 active:scale-[0.98]">
                                <span x-show="!vendorLoading">Search</span>
                                <span x-show="vendorLoading" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Searching
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Vendor Found -->
                    <div x-show="vendorFound === true && vendorData" x-transition.scale.origin.top class="rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-center gap-1.5 mb-3">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Vendor Found</span>
                        </div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-emerald-200 flex items-center justify-center text-emerald-700 text-lg font-bold" x-text="vendorData?.name?.charAt(0)?.toUpperCase()"></div>
                            <div>
                                <p class="text-sm font-bold text-slate-900" x-text="vendorData?.name"></p>
                                <p class="text-xs text-slate-500" x-text="vendorData?.business_name || 'No business name'"></p>
                                <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="vendorData?.phone"></p>
                            </div>
                        </div>
                        <div class="flex gap-2.5">
                            <button @@click="selectVendor(vendorData)" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm active:scale-[0.98]">Use This Vendor</button>
                            <button @@click="resetVendor()" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Clear</button>
                        </div>
                    </div>

                    <!-- Vendor Not Found -->
                    <div x-show="vendorFound === false" x-transition.scale.origin.top class="rounded-2xl border-2 border-amber-200 bg-amber-50 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-amber-200 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-amber-900">No vendor found</p>
                                <p class="text-[11px] text-amber-700">Fill in the details below to create a new vendor account</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Full Name <span class="text-red-400">*</span></label>
                                    <input type="text" x-model="newVendor.name" placeholder="John Doe" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Business Name</label>
                                    <input type="text" x-model="newVendor.business_name" placeholder="Optional" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Phone <span class="text-red-400">*</span></label>
                                    <input type="text" x-model="newVendor.phone" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-100 text-slate-500 outline-none font-mono" readonly>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Email</label>
                                    <input type="email" x-model="newVendor.email" placeholder="Optional" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2.5">
                            <button @@click="createVendor()" :disabled="creatingVendor || !newVendor.name"
                                    class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm active:scale-[0.98]">
                                <span x-show="!creatingVendor">Create Vendor & Continue</span>
                                <span x-show="creatingVendor" class="flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Creating...
                                </span>
                            </button>
                            <button @@click="resetVendor()" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Cancel</button>
                        </div>
                        <div x-show="vendorError" x-transition class="mt-3 px-3.5 py-2.5 bg-red-100 border border-red-200 rounded-xl">
                            <p class="text-xs text-red-700 font-medium" x-text="vendorError"></p>
                        </div>
                    </div>

                    <!-- Vendor Selected -->
                    <div x-show="vendorId && step === 1" x-transition class="rounded-2xl border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3.5">
                                <div class="w-11 h-11 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 text-lg font-bold" x-text="vendorData?.name?.charAt(0)?.toUpperCase()"></div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900" x-text="vendorData?.name"></p>
                                    <p class="text-xs text-slate-500 font-mono" x-text="vendorData?.phone"></p>
                                </div>
                            </div>
                            <button @@click="step = 2" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-blue-600/25 hover:shadow-lg hover:shadow-blue-600/30 active:scale-[0.98] flex items-center gap-2">
                                Continue
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ STEP 2: ITEMS & DELIVERY ═══════════ -->
    <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="space-y-6">

            <!-- Fulfillment Type -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-6">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </span>
                    Fulfillment Method
                </h2>
                <div class="grid grid-cols-3 gap-3">
                    <template x-for="ft in [{v:'warehouse',label:'Warehouse Delivery',desc:'Normal: warehouse → delivery run'},{v:'self_pickup',label:'Self Pickup',desc:'Recipient collects from warehouse'},{v:'direct',label:'Direct Delivery',desc:'Driver delivers directly after pickup'}]" :key="ft.v">
                        <label class="cursor-pointer">
                            <input type="radio" x-model="fulfillmentType" :value="ft.v" class="sr-only peer">
                            <div class="relative p-3.5 rounded-xl border-2 transition-all duration-200 border-slate-200 hover:border-slate-300 hover:bg-slate-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-sm">
                                <p class="text-xs font-bold text-slate-900" x-text="ft.label"></p>
                                <p class="text-[10px] text-slate-500 mt-1" x-text="ft.desc"></p>
                                <div class="absolute top-3 right-3 w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all" :class="fulfillmentType === ft.v ? 'border-blue-500 bg-blue-500' : 'border-slate-300'">
                                    <svg x-show="fulfillmentType === ft.v" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Destination Mode -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-6">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-violet-600 text-white flex items-center justify-center text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    </span>
                    Delivery Mode
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" x-model="destinationMode" value="single" class="sr-only peer">
                        <div class="relative p-4 rounded-2xl border-2 transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-md peer-checked:shadow-blue-500/15 border-slate-200 hover:border-slate-300 hover:bg-slate-50">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <p class="text-sm font-bold text-slate-900">Single Destination</p>
                            <p class="text-[11px] text-slate-500 mt-1">All items delivered to one address</p>
                            <div class="absolute top-3.5 right-3.5 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all" :class="destinationMode === 'single' ? 'border-blue-500 bg-blue-500' : 'border-slate-300'">
                                <svg x-show="destinationMode === 'single'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" x-model="destinationMode" value="per_item" class="sr-only peer">
                        <div class="relative p-4 rounded-2xl border-2 transition-all duration-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:shadow-md peer-checked:shadow-amber-500/15 border-slate-200 hover:border-slate-300 hover:bg-slate-50">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <p class="text-sm font-bold text-slate-900">Per-Item Destination</p>
                            <p class="text-[11px] text-slate-500 mt-1">Each item has its own delivery address</p>
                            <div class="absolute top-3.5 right-3.5 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all" :class="destinationMode === 'per_item' ? 'border-amber-500 bg-amber-500' : 'border-slate-300'">
                                <svg x-show="destinationMode === 'per_item'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-cyan-600 text-white flex items-center justify-center text-xs font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </span>
                        Items
                        <span class="ml-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px] font-bold" x-text="items.length"></span>
                    </h2>
                    <button @@click="addItem()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all active:scale-[0.97] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>
                <div class="divide-y divide-slate-100">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="p-5">
                            <div class="flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-800 to-slate-700 flex items-center justify-center text-white text-[11px] font-bold shrink-0 mt-0.5 shadow-sm" x-text="idx + 1"></div>
                                <div class="flex-1 space-y-3.5">
                                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                        <div class="sm:col-span-4">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Item Description <span class="text-red-400">*</span></label>
                                            <input type="text" x-model="item.description" placeholder="What is being shipped?" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Qty <span class="text-red-400">*</span></label>
                                            <input type="number" x-model.number="item.quantity" min="1" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 text-center font-mono font-bold">
                                        </div>
                                    </div>
                                    <template x-if="destinationMode === 'per_item'">
                                        <div class="bg-amber-50/60 rounded-xl p-4 space-y-3 border border-amber-200/50">
                                            <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider flex items-center gap-1.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                Delivery for this item
                                            </p>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient <span class="text-red-400">*</span></label>
                                                    <input type="text" x-model="item.delivery.recipient_name" placeholder="Name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Phone <span class="text-red-400">*</span></label>
                                                    <input type="text" x-model="item.delivery.recipient_phone" placeholder="Phone" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                            </div>
                                            <div class="relative">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Location <span class="text-red-400">*</span></label>
                                                <div class="relative">
                                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <input type="text" x-model="item.delivery.locationQuery" @@input="searchLocation(item.delivery)" @@focus="item.delivery.locationResults.length && (item.delivery._showDropdown = true)" @@click.outside="item.delivery._showDropdown = false" placeholder="Search location..." class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                                                </div>
                                                <div x-show="item.delivery._showDropdown && item.delivery.locationResults.length" class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-2xl max-h-52 overflow-y-auto">
                                                    <template x-for="loc in item.delivery.locationResults" :key="loc.id">
                                                        <button @@click="selectLocation(item.delivery, loc)" class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-xs text-slate-700 border-b border-slate-50 last:border-0 flex items-center gap-2 transition-colors">
                                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                            <span x-text="loc.display"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                                <p x-show="item.delivery.selectedLocation" class="mt-1 text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    <span x-text="item.delivery.selectedLocation?.display"></span>
                                                </p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Landmark</label>
                                                    <input type="text" x-model="item.delivery.landmark" placeholder="Near..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Instructions</label>
                                                    <input type="text" x-model="item.delivery.instructions" placeholder="Notes..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <button x-show="items.length > 1" @@click="items.splice(idx, 1)" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50 text-slate-300 hover:text-red-500 transition-colors shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Single Mode Delivery -->
            <div x-show="destinationMode === 'single'" class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-6">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    </span>
                    Delivery Address
                </h2>
                <div class="space-y-3.5">
                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Recipient Name <span class="text-red-400">*</span></label>
                            <input type="text" x-model="delivery.recipient_name" placeholder="Who receives the items?" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Phone <span class="text-red-400">*</span></label>
                            <input type="text" x-model="delivery.recipient_phone" placeholder="0241234567" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400 font-mono">
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Location <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="delivery.locationQuery" @@input="searchLocation(delivery)" @@focus="delivery.locationResults.length && (delivery._showDropdown = true)" @@click.outside="delivery._showDropdown = false" placeholder="Search for a town or area..." class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                        </div>
                        <div x-show="delivery._showDropdown && delivery.locationResults.length" class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-2xl max-h-52 overflow-y-auto">
                            <template x-for="loc in delivery.locationResults" :key="loc.id">
                                <button @@click="selectLocation(delivery, loc)" class="w-full text-left px-4 py-3 hover:bg-blue-50 text-sm text-slate-700 border-b border-slate-100 last:border-0 flex items-center gap-2.5 transition-colors">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span x-text="loc.display"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="delivery.selectedLocation" class="mt-1.5 text-xs text-emerald-600 font-semibold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span x-text="delivery.selectedLocation?.display"></span>
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Landmark</label>
                            <input type="text" x-model="delivery.landmark" placeholder="Near a known place" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Delivery Instructions</label>
                            <input type="text" x-model="delivery.instructions" placeholder="Special notes" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receiving Warehouse (admin only) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-6">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-orange-600 text-white flex items-center justify-center text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                    Receiving Warehouse
                </h2>
                <select x-model="warehouseId" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50">
                    <option value="">Select a warehouse...</option>
                    <template x-for="wh in config.warehouses" :key="wh.id">
                        <option :value="wh.id" x-text="wh.name + ' (' + wh.code + ')'"></option>
                    </template>
                </select>
            </div>

            <!-- Nav -->
            <div class="flex items-center justify-between pt-2">
                <button @@click="step = 1" class="px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                    Back
                </button>
                <button @@click="goToReview()" :disabled="!canProceedToReview()"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-md shadow-blue-600/25 hover:shadow-lg active:scale-[0.98] flex items-center gap-2">
                    Review
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════ STEP 3: REVIEW ═══════════ -->
    <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-5">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Vendor</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-sm font-bold" x-text="vendorData?.name?.charAt(0)?.toUpperCase()"></div>
                        <div>
                            <p class="text-sm font-bold text-slate-900" x-text="vendorData?.name"></p>
                            <p class="text-xs text-slate-500 font-mono" x-text="vendorData?.phone"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-5">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Warehouse</p>
                    <p class="text-sm font-bold text-slate-900" x-text="config.warehouses.find(w => w.id == warehouseId)?.name || '—'"></p>
                    <p class="text-xs text-slate-500 font-mono" x-text="config.warehouses.find(w => w.id == warehouseId)?.code || ''"></p>
                </div>
            </div>

            <div x-show="destinationMode === 'single'" class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50 p-5">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Deliver To</p>
                <p class="text-sm font-bold text-slate-900" x-text="delivery.recipient_name"></p>
                <p class="text-xs text-slate-500 font-mono" x-text="delivery.recipient_phone"></p>
                <p class="text-xs text-slate-500 mt-1" x-text="delivery.selectedLocation?.display"></p>
                <p x-show="delivery.landmark" class="text-[10px] text-slate-400 mt-1">Landmark: <span x-text="delivery.landmark"></span></p>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50">
                <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white rounded-t-2xl">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Shipment Items</p>
                    <p class="text-sm font-bold text-slate-900 mt-0.5"><span x-text="items.length"></span> item(s)</p>
                </div>
                <template x-for="(item, idx) in items" :key="idx">
                    <div class="px-5 py-3.5 border-b border-slate-50 last:border-0 flex items-center gap-3.5">
                        <div class="w-7 h-7 rounded-lg bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0" x-text="idx + 1"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate" x-text="item.description"></p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Qty: <span class="font-mono font-bold" x-text="item.quantity"></span>
                                <template x-if="destinationMode === 'per_item' && item.delivery.recipient_name">
                                    <span> &middot; To: <span x-text="item.delivery.recipient_name"></span></span>
                                </template>
                            </p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-3">
                <button @@click="step = 2" class="px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                    Edit
                </button>
                <button @@click="submitShipment()" :disabled="submitting"
                        class="px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white text-base font-bold rounded-2xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-xl shadow-emerald-600/30 hover:shadow-2xl hover:shadow-emerald-600/40 active:scale-[0.98] flex items-center gap-2.5">
                    <span x-show="!submitting" class="flex items-center gap-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Submit Shipment
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Creating...
                    </span>
                </button>
            </div>
            <div x-show="submitError" x-transition class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-xs text-red-700 font-medium" x-text="submitError"></p>
            </div>
        </div>
    </div>

    </div><!-- /max-w-3xl -->
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
        fulfillmentType: 'warehouse',
        destinationMode: 'single',
        items: [],
        delivery: null,
        warehouseId: '',
        submitting: false, submitError: '',

        init() {
            const el = this.$el.closest('[data-walkin-config]');
            this.config = JSON.parse(el.dataset.walkinConfig);
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
            if (!this.warehouseId) return false;
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
                vendor_id: this.vendorId, warehouse_id: this.warehouseId, fulfillment_type: this.fulfillmentType, destination_mode: this.destinationMode,
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
