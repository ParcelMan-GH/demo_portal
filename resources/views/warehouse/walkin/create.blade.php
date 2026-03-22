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

<div class="max-w-4xl mx-auto pb-10"
     x-data="walkinShipment()"
     x-init="init()"
     data-walkin-config='@json($walkinConfig)'>

    <!-- Hero Header -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl shadow-2xl shadow-slate-900/30 mb-6">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="wgrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#wgrid)"/>
                </svg>
            </div>
            <div class="relative px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between mb-5">
                    <a href="{{ route('warehouse.dashboard') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-medium transition-all backdrop-blur-sm">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Dashboard
                    </a>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 border border-white/20 backdrop-blur-sm">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                        <span class="text-[11px] font-medium text-white/80">{{ $warehouse->name }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white tracking-tight">Walk-in Receiving</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Receive items from a vendor who walked in</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step Indicator -->
    <div class="mb-6">
        <div class="flex items-center bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm px-6 py-4">
            <template x-for="(s, idx) in steps" :key="idx">
                <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition-all duration-300"
                             :class="step > idx + 1 ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30' : (step === idx + 1 ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-100 text-slate-400')">
                            <template x-if="step > idx + 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step <= idx + 1">
                                <span x-text="idx + 1"></span>
                            </template>
                        </div>
                        <span class="text-xs font-semibold hidden sm:inline transition-colors" :class="step === idx + 1 ? 'text-slate-900' : (step > idx + 1 ? 'text-emerald-600' : 'text-slate-400')" x-text="s"></span>
                    </div>
                    <template x-if="idx < steps.length - 1">
                        <div class="flex-1 h-[2px] mx-4 rounded-full transition-colors duration-300" :class="step > idx + 1 ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 1: VENDOR IDENTIFICATION           -->
    <!-- ═══════════════════════════════════════ -->
    <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
        <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm">
            <div class="px-6 py-5 border-b border-slate-100/80">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Vendor Identification</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Search by phone number or create a new vendor account</p>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <!-- Phone Lookup -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Vendor Phone Number</label>
                    <div class="flex gap-2.5">
                        <div class="relative flex-1">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <input type="text" x-model="vendorPhone" placeholder="e.g. 0241234567"
                                   @@keydown.enter.prevent="lookupVendor()"
                                   class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                        </div>
                        <button @@click="lookupVendor()" :disabled="vendorLoading || vendorPhone.length < 9"
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md hover:shadow-blue-600/20">
                            <span x-show="!vendorLoading" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Search
                            </span>
                            <span x-show="vendorLoading" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Searching
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Vendor Found -->
                <div x-show="vendorFound === true && vendorData" x-transition class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-2xl border border-emerald-200/60 p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5.5 h-5.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900" x-text="vendorData?.name"></p>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-text="vendorData?.business_name || 'No business name'"></p>
                                <p class="text-[11px] text-slate-500 font-mono" x-text="vendorData?.phone"></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold"
                              :class="vendorData?.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                              x-text="vendorData?.is_active ? 'Active' : 'Inactive'"></span>
                    </div>
                    <div class="mt-4 flex gap-2.5">
                        <button @@click="selectVendor(vendorData)" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-md hover:shadow-emerald-600/20 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Use This Vendor
                        </button>
                        <button @@click="resetVendor()" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-xl border border-slate-200 transition-colors">Search Again</button>
                    </div>
                </div>

                <!-- Vendor Not Found -->
                <div x-show="vendorFound === false" x-transition class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl border border-amber-200/60 p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-amber-900">Vendor not found</p>
                            <p class="text-[10px] text-amber-700">Create a new account to continue</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Full Name *</label>
                            <input type="text" x-model="newVendor.name" placeholder="Enter vendor name" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Business Name</label>
                            <input type="text" x-model="newVendor.business_name" placeholder="Optional" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Phone *</label>
                            <input type="text" x-model="newVendor.phone" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl bg-slate-100 text-slate-500 outline-none font-mono" readonly>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Email</label>
                            <input type="email" x-model="newVendor.email" placeholder="Optional" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2.5">
                        <button @@click="createVendor()" :disabled="creatingVendor || !newVendor.name"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm flex items-center gap-1.5">
                            <span x-show="!creatingVendor">Create & Continue</span>
                            <span x-show="creatingVendor" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Creating...
                            </span>
                        </button>
                        <button @@click="resetVendor()" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-xl border border-slate-200 transition-colors">Cancel</button>
                    </div>
                    <div x-show="vendorError" x-transition class="mt-3 flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                        <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[11px] text-red-700 font-medium" x-text="vendorError"></p>
                    </div>
                </div>

                <!-- Selected Vendor — Next Button -->
                <div x-show="vendorId && step === 1" x-transition class="bg-gradient-to-r from-blue-50 via-indigo-50 to-blue-50 rounded-2xl border border-blue-200/60 p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">Vendor Selected</p>
                                <p class="text-[11px] text-slate-500"><span x-text="vendorData?.name"></span> &middot; <span x-text="vendorData?.phone"></span></p>
                            </div>
                        </div>
                        <button @@click="step = 2" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-md hover:shadow-blue-600/20 flex items-center gap-1.5">
                            Next
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 2: ITEMS & DELIVERY                -->
    <!-- ═══════════════════════════════════════ -->
    <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
        <div class="space-y-5">
            <!-- Destination Mode -->
            <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-50 to-purple-100 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Destination Mode</h2>
                        <p class="text-[11px] text-slate-500">How should items be delivered?</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" x-model="destinationMode" value="single" class="sr-only peer">
                        <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50/50 peer-checked:shadow-sm peer-checked:shadow-blue-500/10 border-slate-200 hover:border-slate-300">
                            <div class="flex items-center gap-2.5 mb-1.5">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-900">Single Destination</p>
                            </div>
                            <p class="text-[10px] text-slate-500 ml-[34px]">All items to one address</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" x-model="destinationMode" value="per_item" class="sr-only peer">
                        <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50/50 peer-checked:shadow-sm peer-checked:shadow-blue-500/10 border-slate-200 hover:border-slate-300">
                            <div class="flex items-center gap-2.5 mb-1.5">
                                <div class="w-6 h-6 rounded-lg bg-amber-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-900">Per-Item</p>
                            </div>
                            <p class="text-[10px] text-slate-500 ml-[34px]">Each item has its own address</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-50 to-cyan-100 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Shipment Items</h2>
                            <p class="text-[11px] text-slate-500 mt-0.5"><span x-text="items.length"></span> item(s) added</p>
                        </div>
                    </div>
                    <button @@click="addItem()" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-xl transition-all shadow-sm hover:shadow-md hover:shadow-blue-600/20 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>
                <div class="divide-y divide-slate-100/80">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="p-5">
                            <div class="flex items-start gap-3">
                                <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-slate-100 to-slate-50 border border-slate-200/60 flex items-center justify-center text-[10px] font-bold text-slate-500 mt-0.5 shrink-0" x-text="idx + 1"></span>
                                <div class="flex-1 space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <div class="sm:col-span-3">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Description *</label>
                                            <input type="text" x-model="item.description" placeholder="e.g. Box of electronics"
                                                   class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Qty *</label>
                                            <input type="number" x-model.number="item.quantity" min="1"
                                                   class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50">
                                        </div>
                                    </div>
                                    <template x-if="destinationMode === 'per_item'">
                                        <div class="bg-gradient-to-br from-slate-50 to-slate-50/50 rounded-xl p-4 space-y-3 border border-slate-200/60">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                Delivery Details
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Name *</label>
                                                    <input type="text" x-model="item.delivery.recipient_name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Recipient Phone *</label>
                                                    <input type="text" x-model="item.delivery.recipient_phone" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                                                </div>
                                            </div>
                                            <div class="relative">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Delivery Location *</label>
                                                <div class="relative">
                                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <input type="text" x-model="item.delivery.locationQuery"
                                                           @@input="searchLocation(item.delivery)"
                                                           @@focus="item.delivery.locationResults.length && (item.delivery._showDropdown = true)"
                                                           @@click.outside="item.delivery._showDropdown = false"
                                                           placeholder="Search town or area..."
                                                           class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                                                </div>
                                                <div x-show="item.delivery._showDropdown && item.delivery.locationResults.length"
                                                     class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/50 max-h-52 overflow-y-auto">
                                                    <template x-for="loc in item.delivery.locationResults" :key="loc.id">
                                                        <button @@click="selectLocation(item.delivery, loc)" class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-xs text-slate-700 border-b border-slate-100/60 last:border-0 flex items-center gap-2.5 transition-colors">
                                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                            <span x-text="loc.display"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                                <p x-show="item.delivery.selectedLocation" class="mt-1.5 text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span x-text="item.delivery.selectedLocation?.display"></span>
                                                </p>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Landmark</label>
                                                    <input type="text" x-model="item.delivery.landmark" placeholder="Near landmark..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Instructions</label>
                                                    <input type="text" x-model="item.delivery.instructions" placeholder="Delivery notes..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <button x-show="items.length > 1" @@click="items.splice(idx, 1)" class="mt-0.5 w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Single Delivery Details -->
            <div x-show="destinationMode === 'single'" class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm p-6 space-y-4">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-50 to-green-100 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Delivery Details</h2>
                        <p class="text-[11px] text-slate-500">Where should these items be delivered?</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Recipient Name *</label>
                        <input type="text" x-model="delivery.recipient_name" placeholder="Who receives the items?" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Recipient Phone *</label>
                        <input type="text" x-model="delivery.recipient_phone" placeholder="Phone number" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Delivery Location *</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="delivery.locationQuery"
                               @@input="searchLocation(delivery)"
                               @@focus="delivery.locationResults.length && (delivery._showDropdown = true)"
                               @@click.outside="delivery._showDropdown = false"
                               placeholder="Search town or area..."
                               class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                    </div>
                    <div x-show="delivery._showDropdown && delivery.locationResults.length"
                         class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/50 max-h-52 overflow-y-auto">
                        <template x-for="loc in delivery.locationResults" :key="loc.id">
                            <button @@click="selectLocation(delivery, loc)" class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-xs text-slate-700 border-b border-slate-100/60 last:border-0 flex items-center gap-2.5 transition-colors">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <span x-text="loc.display"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="delivery.selectedLocation" class="mt-1.5 text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="delivery.selectedLocation?.display"></span>
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Landmark</label>
                        <input type="text" x-model="delivery.landmark" placeholder="Near a known landmark" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Instructions</label>
                        <input type="text" x-model="delivery.instructions" placeholder="Special delivery notes" class="w-full px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-slate-50/50 placeholder:text-slate-400">
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between pt-1">
                <button @@click="step = 1" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-xl border border-slate-200 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <button @@click="goToReview()" :disabled="!canProceedToReview()"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md hover:shadow-blue-600/20 flex items-center gap-1.5">
                    Review & Submit
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 3: REVIEW & SUBMIT                 -->
    <!-- ═══════════════════════════════════════ -->
    <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
        <div class="space-y-5">
            <!-- Vendor Card -->
            <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm p-5">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Vendor</p>
                        <p class="text-sm font-bold text-slate-900 mt-0.5" x-text="vendorData?.name"></p>
                        <p class="text-xs text-slate-500 font-mono" x-text="vendorData?.phone"></p>
                    </div>
                </div>
            </div>

            <!-- Items Card -->
            <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100/80 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-50 to-cyan-100 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900"><span x-text="items.length"></span> Item(s)</h3>
                        <p class="text-[10px] text-slate-500" x-text="destinationMode === 'single' ? 'Single destination' : 'Per-item destinations'"></p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100/60">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="px-5 py-3.5 flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 shrink-0" x-text="idx + 1"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-900 truncate" x-text="item.description"></p>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        Qty: <span x-text="item.quantity"></span>
                                    </span>
                                    <template x-if="destinationMode === 'per_item' && item.delivery.recipient_name">
                                        <span class="text-[10px] text-slate-400 truncate flex items-center gap-1">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            <span x-text="item.delivery.recipient_name"></span> &middot; <span x-text="item.delivery.selectedLocation?.display || ''"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Delivery Card (single mode) -->
            <div x-show="destinationMode === 'single'" class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/60 shadow-sm p-5">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-50 to-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Deliver To</p>
                        <p class="text-sm font-bold text-slate-900 mt-0.5" x-text="delivery.recipient_name"></p>
                        <p class="text-xs text-slate-500 font-mono" x-text="delivery.recipient_phone"></p>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="delivery.selectedLocation?.display"></p>
                        <p x-show="delivery.landmark" class="text-[10px] text-slate-400 mt-0.5">Landmark: <span x-text="delivery.landmark"></span></p>
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-between pt-2">
                <button @@click="step = 2" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-xl border border-slate-200 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <button @@click="submitShipment()" :disabled="submitting"
                        class="px-7 py-3 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-emerald-600/30 hover:shadow-xl hover:shadow-emerald-600/40 flex items-center gap-2">
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Submit Walk-in Shipment
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="w-4.5 h-4.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Creating Shipment...
                    </span>
                </button>
            </div>

            <!-- Error -->
            <div x-show="submitError" x-transition class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
