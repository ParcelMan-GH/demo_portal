@extends('web.layouts.vendor')

@section('title', 'Create Shipment')
@section('page-title', 'New Shipment')

@section('content')
<div x-data="vendorShipmentFormPage()" data-mode="create">

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-24">
        <div class="text-center">
            <div class="relative mx-auto h-12 w-12">
                <div class="absolute inset-0 rounded-full border-2 border-slate-200"></div>
                <div class="absolute inset-0 animate-spin rounded-full border-2 border-transparent border-t-slate-800"></div>
            </div>
            <p class="mt-4 text-sm font-medium text-slate-400">Preparing your shipment form...</p>
        </div>
    </div>

    <div x-show="!loading" x-cloak>

        {{-- Mobile step indicator --}}
        <div class="mb-5 lg:hidden">
            <div class="vendor-card overflow-hidden">
                {{-- Progress bar across top --}}
                <div class="h-1 bg-slate-100">
                    <div class="h-full bg-gradient-to-r from-slate-700 to-slate-900 transition-all duration-500 ease-out"
                         :style="'width: ' + ((step / totalSteps) * 100) + '%'"></div>
                </div>
                <div class="flex items-center gap-3.5 px-4 py-3">
                    <div class="relative flex h-10 w-10 flex-shrink-0 items-center justify-center">
                        {{-- Ring --}}
                        <svg class="absolute inset-0 h-10 w-10 -rotate-90" viewBox="0 0 40 40">
                            <circle cx="20" cy="20" r="17" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                            <circle cx="20" cy="20" r="17" fill="none" stroke="#1e293b" stroke-width="3"
                                    stroke-dasharray="106.8" :stroke-dashoffset="106.8 - (106.8 * step / totalSteps)"
                                    stroke-linecap="round" class="transition-all duration-500"/>
                        </svg>
                        <span class="text-xs font-bold text-slate-800" x-text="step"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-slate-900" x-text="stepLabel(step)"></div>
                        <div class="text-[11px] text-slate-400">Step <span x-text="step"></span> of <span x-text="totalSteps"></span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main layout: side stepper + content --}}
        <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-8">

            {{-- Left: Vertical stepper (desktop only) --}}
            <div class="hidden lg:block">
                <div class="sticky top-20 space-y-4">
                    <div class="vendor-card overflow-hidden">
                        {{-- Progress header with gradient --}}
                        <div class="bg-gradient-to-br from-slate-800 to-slate-900 px-5 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Progress</div>
                                    <div class="mt-0.5 text-lg font-bold text-white" x-text="Math.round((step / totalSteps) * 100) + '%'"></div>
                                </div>
                                <div class="relative h-11 w-11">
                                    <svg class="h-11 w-11 -rotate-90" viewBox="0 0 44 44">
                                        <circle cx="22" cy="22" r="18" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="3"/>
                                        <circle cx="22" cy="22" r="18" fill="none" stroke="#fff" stroke-width="3"
                                                stroke-dasharray="113.1" :stroke-dashoffset="113.1 - (113.1 * step / totalSteps)"
                                                stroke-linecap="round" class="transition-all duration-700 ease-out"/>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-[10px] font-bold text-white" x-text="step + '/' + totalSteps"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Steps --}}
                        <nav class="p-4 space-y-0.5">
                            {{-- Step 1 --}}
                            <button type="button" @click="step > 1 ? step = 1 : null"
                                    class="stepper-step group" :class="step === 1 ? 'stepper-active' : (step > 1 ? 'stepper-done' : 'stepper-pending')">
                                <div class="stepper-icon">
                                    <template x-if="step <= 1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </template>
                                    <template x-if="step > 1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </div>
                                <span class="stepper-label">Shipment Type</span>
                            </button>
                            <div class="stepper-line" :class="step > 1 ? 'stepper-line-done' : ''"></div>

                            {{-- Step 2 --}}
                            <button type="button" @click="step > 2 ? step = 2 : (step === 1 ? goToStep(2) : null)"
                                    class="stepper-step group" :class="step === 2 ? 'stepper-active' : (step > 2 ? 'stepper-done' : 'stepper-pending')">
                                <div class="stepper-icon">
                                    <template x-if="step <= 2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </template>
                                    <template x-if="step > 2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </div>
                                <span class="stepper-label">Pickup Info</span>
                            </button>
                            <div class="stepper-line" :class="step > 2 ? 'stepper-line-done' : ''"></div>

                            {{-- Step 3 --}}
                            <button type="button" @click="step > 3 ? step = 3 : (step === 2 ? goToStep(3) : null)"
                                    class="stepper-step group" :class="step === 3 ? 'stepper-active' : (step > 3 ? 'stepper-done' : 'stepper-pending')">
                                <div class="stepper-icon">
                                    <template x-if="step <= 3 && form.destination_mode === 'single'">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    </template>
                                    <template x-if="step <= 3 && form.destination_mode !== 'single'">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </template>
                                    <template x-if="step > 3">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </div>
                                <span class="stepper-label" x-text="form.destination_mode === 'single' ? 'Delivery Info' : 'Review'"></span>
                            </button>

                            {{-- Step 4 (single mode only) --}}
                            <template x-if="form.destination_mode === 'single'">
                                <div>
                                    <div class="stepper-line" :class="step > 3 ? 'stepper-line-done' : ''"></div>
                                    <button type="button" @click="step === 3 ? goToStep(4) : null"
                                            class="stepper-step group" :class="step === 4 ? 'stepper-active' : 'stepper-pending'">
                                        <div class="stepper-icon">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <span class="stepper-label">Review</span>
                                    </button>
                                </div>
                            </template>
                        </nav>
                    </div>

                    {{-- Help box --}}
                    <div class="overflow-hidden rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50/50 via-white to-blue-50/30">
                        <div class="border-b border-blue-100 bg-blue-500/5 px-3.5 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/10">
                                    <svg class="h-3.5 w-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="text-xs font-bold text-blue-700">Quick Tip</div>
                            </div>
                        </div>
                        <p class="px-3.5 py-3 text-[11px] leading-relaxed text-slate-600">Items are added <strong class="font-semibold text-slate-700">after</strong> the shipment is created. You'll be redirected to add items once done.</p>
                    </div>
                </div>
            </div>

            {{-- Right: Form content --}}
            <div class="min-w-0">

                {{-- Step transition wrapper --}}
                <div class="step-content-area">

                {{-- ============================================ --}}
                {{-- STEP 1: Shipment Type --}}
                {{-- ============================================ --}}
                <div x-show="step === 1"
                     x-transition:enter="step-enter"
                     x-transition:enter-start="step-enter-from"
                     x-transition:enter-end="step-enter-to">
                    <div class="vendor-card overflow-hidden">
                        {{-- Step header --}}
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-800 text-white">
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900">How should items be delivered?</h2>
                                    <p class="mt-0.5 text-xs text-slate-400">Choose whether all items go to one address or each has its own destination</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                {{-- Single destination card --}}
                                <label class="dest-option-card group cursor-pointer"
                                       :class="form.destination_mode === 'single' ? 'dest-option-selected' : ''">
                                    <input type="radio" x-model="form.destination_mode" value="single" @change="onDestinationModeChange()" class="sr-only">
                                    <div class="p-5">
                                        <div class="flex items-start justify-between">
                                            <div class="dest-option-icon"
                                                 :class="form.destination_mode === 'single' ? 'dest-option-icon-active' : ''">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </div>
                                            <div class="dest-option-check" x-show="form.destination_mode === 'single'">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="text-sm font-bold text-slate-900">Single Destination</div>
                                            <p class="mt-1.5 text-xs leading-relaxed text-slate-500">All items ship to <strong class="text-slate-600">one address</strong>. Best for bulk deliveries to the same recipient.</p>
                                        </div>
                                    </div>
                                </label>

                                {{-- Per-item destination card --}}
                                <label class="dest-option-card group cursor-pointer"
                                       :class="form.destination_mode === 'per_item' ? 'dest-option-selected' : ''">
                                    <input type="radio" x-model="form.destination_mode" value="per_item" @change="onDestinationModeChange()" class="sr-only">
                                    <div class="p-5">
                                        <div class="flex items-start justify-between">
                                            <div class="dest-option-icon"
                                                 :class="form.destination_mode === 'per_item' ? 'dest-option-icon-active' : ''">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                            </div>
                                            <div class="dest-option-check" x-show="form.destination_mode === 'per_item'">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="text-sm font-bold text-slate-900">Per-Item Destination</div>
                                            <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Each item gets its <strong class="text-slate-600">own address</strong>. Delivery details are set when adding items.</p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="button" @click="goToStep(2)" class="vendor-btn-primary group">
                                    Continue
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================ --}}
                {{-- STEP 2: Pickup Details --}}
                {{-- ============================================ --}}
                <div x-show="step === 2" x-cloak
                     x-transition:enter="step-enter"
                     x-transition:enter-start="step-enter-from"
                     x-transition:enter-end="step-enter-to">
                    <div class="vendor-card overflow-hidden">
                        {{-- Step header --}}
                        <div class="border-b border-slate-100 bg-gradient-to-r from-blue-50/50 to-white px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white">
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900">Pickup Details</h2>
                                    <p class="mt-0.5 text-xs text-slate-400">Where should we pick up your items?</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            {{-- Contact section --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Contact Information</span>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Contact Name <span class="text-red-400">*</span></label>
                                        <input x-model="form.pickup_contact_name" type="text" class="vendor-input" placeholder="Full name of person at pickup" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Contact Phone <span class="text-red-400">*</span></label>
                                        <input x-model="form.pickup_contact_phone" @input="onPickupPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input" placeholder="e.g. 0241234567" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Confirm Phone <span class="text-red-400">*</span></label>
                                        <input x-model="form.pickup_contact_phone_confirm" @input="onPickupPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input" placeholder="Re-enter phone to confirm" required>
                                    </div>
                                    <div class="sm:col-span-2" x-show="form.pickup_contact_phone && form.pickup_contact_phone_confirm">
                                        <template x-if="form.pickup_contact_phone === form.pickup_contact_phone_confirm">
                                            <div class="flex items-center gap-1.5 text-green-600">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span class="text-[11px] font-medium">Phone numbers match</span>
                                            </div>
                                        </template>
                                        <template x-if="form.pickup_contact_phone !== form.pickup_contact_phone_confirm">
                                            <div class="flex items-center gap-1.5 text-red-500">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span class="text-[11px] font-medium">Phone numbers don't match</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-slate-100"></div>

                            {{-- Location section --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Location</span>
                                </div>

                                <div class="mb-4 flex flex-wrap gap-x-5 gap-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" x-model="form.pickup_location_method" value="dropdown"
                                               @change="form.pickup_latitude = ''; form.pickup_longitude = '';"
                                               class="h-4 w-4 accent-slate-700">
                                        <span class="text-xs font-medium text-slate-700">Region + District</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" x-model="form.pickup_location_method" value="coordinates"
                                               @change="form.pickup_town = ''; form.pickup_region_id = ''; form.pickup_district_id = '';"
                                               class="h-4 w-4 accent-slate-700">
                                        <span class="text-xs font-medium text-slate-700">Coordinates</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" x-model="form.pickup_location_method" value="gh_post"
                                               @change="form.pickup_latitude = ''; form.pickup_longitude = ''; form.pickup_town = '';"
                                               class="h-4 w-4 accent-slate-700">
                                        <span class="text-xs font-medium text-slate-700">Ghana Post Address</span>
                                    </label>
                                </div>

                                {{-- Region/District --}}
                                <div class="grid gap-4 sm:grid-cols-2" x-show="form.pickup_location_method === 'dropdown'" x-cloak>
                                    <div>
                                        <label class="form-label">Region <span class="text-red-400">*</span></label>
                                        <select x-model="form.pickup_region_id" @change="onPickupRegionChange()" class="vendor-input" required>
                                            <option value="">Select region</option>
                                            <template x-for="region in regions" :key="region.id"><option :value="String(region.id)" x-text="region.name"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">District <span class="text-red-400">*</span></label>
                                        <select x-model="form.pickup_district_id" class="vendor-input" required>
                                            <option value="">Select district</option>
                                            <template x-for="district in pickupDistricts" :key="district.id"><option :value="String(district.id)" x-text="district.name"></option></template>
                                        </select>
                                    </div>
                                </div>

                                {{-- Coordinates with Map Search --}}
                                <div x-show="form.pickup_location_method === 'coordinates'" x-cloak>
                                    <div>
                                        <label class="form-label">Search Location <span class="text-red-400">*</span></label>
                                        <div class="relative">
                                            <input
                                                x-model="pickupSearchQuery"
                                                @input.debounce.300ms="searchPickupLocation()"
                                                type="text"
                                                class="vendor-input pr-10"
                                                placeholder="Type to search for a place...">
                                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                                <svg x-show="pickupSearching" class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <svg x-show="!pickupSearching" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            </div>
                                            <div x-show="pickupSearchResults.length > 0" x-cloak class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg max-h-60 overflow-y-auto">
                                                <template x-for="result in pickupSearchResults" :key="result.place_id">
                                                    <button type="button" @click="selectPickupResult(result)"
                                                            class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50 border-b border-gray-100 last:border-0">
                                                        <div class="font-medium text-slate-700" x-text="result.display_name.split(',')[0]"></div>
                                                        <div class="text-xs text-slate-400" x-text="result.display_name"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" x-model="form.pickup_latitude" required>
                                    <input type="hidden" x-model="form.pickup_longitude" required>
                                </div>

                                {{-- Town + Landmark (dropdown only) --}}
                                <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="form.pickup_location_method === 'dropdown'" x-cloak>
                                    <div>
                                        <label class="form-label">Town <span class="text-red-400">*</span></label>
                                        <input x-model="form.pickup_town" type="text" class="vendor-input" placeholder="Pickup town or city">
                                    </div>
                                    <div>
                                        <label class="form-label">Landmark</label>
                                        <input x-model="form.pickup_landmark" type="text" class="vendor-input" placeholder="Nearby landmark">
                                    </div>
                                </div>

                                {{-- Ghana Post Address + Landmark --}}
                                <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="form.pickup_location_method === 'gh_post'" x-cloak>
                                    <div>
                                        <label class="form-label">Ghana Post Address <span class="text-red-400">*</span></label>
                                        <input x-model="form.pickup_gh_post_address" type="text" class="vendor-input" placeholder="e.g. GA-123-4567" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Landmark</label>
                                        <input x-model="form.pickup_landmark" type="text" class="vendor-input" placeholder="Nearby landmark">
                                    </div>
                                </div>

                                {{-- Landmark only (coordinates) --}}
                                <div class="mt-4" x-show="form.pickup_location_method === 'coordinates'" x-cloak>
                                    <label class="form-label">Landmark</label>
                                    <input x-model="form.pickup_landmark" type="text" class="vendor-input" placeholder="Nearby landmark">
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-slate-100"></div>

                            {{-- Instructions section --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Special Instructions</span>
                                </div>
                                <textarea x-model="form.pickup_instructions" rows="2" class="vendor-input" placeholder="Any special instructions for the driver at pickup (optional)"></textarea>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex justify-between border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                            <button type="button" @click="step = 1" class="vendor-btn-secondary group">
                                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back
                            </button>
                            <button type="button" @click="goToStep(3)" class="vendor-btn-primary group">
                                <span x-text="form.destination_mode === 'single' ? 'Continue to Delivery' : 'Review Shipment'"></span>
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ============================================ --}}
                {{-- STEP 3: Delivery Details (single mode only) --}}
                {{-- ============================================ --}}
                <div x-show="step === 3 && form.destination_mode === 'single'" x-cloak
                     x-transition:enter="step-enter"
                     x-transition:enter-start="step-enter-from"
                     x-transition:enter-end="step-enter-to">
                    <div class="vendor-card overflow-hidden">
                        {{-- Step header --}}
                        <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50/50 to-white px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900">Delivery Details</h2>
                                    <p class="mt-0.5 text-xs text-slate-400">Where should items be delivered?</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            {{-- Recipient section --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Recipient Information</span>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Recipient Name</label>
                                        <input x-model="form.delivery_recipient_name" type="text" class="vendor-input" placeholder="Full name of the recipient">
                                    </div>
                                    <div>
                                        <label class="form-label">Recipient Phone</label>
                                        <input x-model="form.delivery_recipient_phone" @input="onDeliveryPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input" placeholder="e.g. 0241234567">
                                    </div>
                                    <div>
                                        <label class="form-label">Confirm Phone</label>
                                        <input x-model="form.delivery_recipient_phone_confirm" @input="onDeliveryPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input" placeholder="Re-enter phone to confirm">
                                    </div>
                                    <div class="sm:col-span-2" x-show="form.delivery_recipient_phone && form.delivery_recipient_phone_confirm">
                                        <template x-if="form.delivery_recipient_phone === form.delivery_recipient_phone_confirm">
                                            <div class="flex items-center gap-1.5 text-green-600">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span class="text-[11px] font-medium">Phone numbers match</span>
                                            </div>
                                        </template>
                                        <template x-if="form.delivery_recipient_phone !== form.delivery_recipient_phone_confirm">
                                            <div class="flex items-center gap-1.5 text-red-500">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span class="text-[11px] font-medium">Phone numbers don't match</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-slate-100"></div>

                            {{-- Location section --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Delivery Location</span>
                                </div>

                                <div class="mb-4 flex flex-wrap gap-x-5 gap-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" x-model="form.delivery_location_method" value="dropdown"
                                               @change="form.delivery_latitude = ''; form.delivery_longitude = '';"
                                               class="h-4 w-4 accent-slate-700">
                                        <span class="text-xs font-medium text-slate-700">Region + District</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" x-model="form.delivery_location_method" value="coordinates"
                                               @change="form.delivery_town = ''; form.delivery_region_id = ''; form.delivery_district_id = '';"
                                               class="h-4 w-4 accent-slate-700">
                                        <span class="text-xs font-medium text-slate-700">Coordinates</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" x-model="form.delivery_location_method" value="gh_post"
                                               @change="form.delivery_latitude = ''; form.delivery_longitude = ''; form.delivery_town = '';"
                                               class="h-4 w-4 accent-slate-700">
                                        <span class="text-xs font-medium text-slate-700">Ghana Post Address</span>
                                    </label>
                                </div>

                                {{-- Region/District --}}
                                <div class="grid gap-4 sm:grid-cols-2" x-show="form.delivery_location_method === 'dropdown'" x-cloak>
                                    <div>
                                        <label class="form-label">Region</label>
                                        <select x-model="form.delivery_region_id" @change="onDeliveryRegionChange()" class="vendor-input">
                                            <option value="">Select region</option>
                                            <template x-for="region in regions" :key="region.id"><option :value="String(region.id)" x-text="region.name"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">District</label>
                                        <select x-model="form.delivery_district_id" class="vendor-input">
                                            <option value="">Select district</option>
                                            <template x-for="district in deliveryDistricts" :key="district.id"><option :value="String(district.id)" x-text="district.name"></option></template>
                                        </select>
                                    </div>
                                </div>

                                {{-- Coordinates with Map Search --}}
                                <div x-show="form.delivery_location_method === 'coordinates'" x-cloak>
                                    <div>
                                        <label class="form-label">Search Location</label>
                                        <div class="relative">
                                            <input
                                                x-model="deliverySearchQuery"
                                                @input.debounce.300ms="searchDeliveryLocation()"
                                                type="text"
                                                class="vendor-input pr-10"
                                                placeholder="Type to search for a place...">
                                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                                <svg x-show="deliverySearching" class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <svg x-show="!deliverySearching" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            </div>
                                            <div x-show="deliverySearchResults.length > 0" x-cloak class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg max-h-60 overflow-y-auto">
                                                <template x-for="result in deliverySearchResults" :key="result.place_id">
                                                    <button type="button" @click="selectDeliveryResult(result)"
                                                            class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50 border-b border-gray-100 last:border-0">
                                                        <div class="font-medium text-slate-700" x-text="result.display_name.split(',')[0]"></div>
                                                        <div class="text-xs text-slate-400" x-text="result.display_name"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" x-model="form.delivery_latitude">
                                    <input type="hidden" x-model="form.delivery_longitude">
                                </div>

                                {{-- Town + Landmark (dropdown only) --}}
                                <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="form.delivery_location_method === 'dropdown'" x-cloak>
                                    <div>
                                        <label class="form-label">Town</label>
                                        <input x-model="form.delivery_town" type="text" class="vendor-input" placeholder="Delivery town or city">
                                    </div>
                                    <div>
                                        <label class="form-label">Landmark</label>
                                        <input x-model="form.delivery_landmark" type="text" class="vendor-input" placeholder="Nearby landmark">
                                    </div>
                                </div>

                                {{-- Ghana Post Address + Landmark --}}
                                <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="form.delivery_location_method === 'gh_post'" x-cloak>
                                    <div>
                                        <label class="form-label">Ghana Post Address</label>
                                        <input x-model="form.delivery_gh_post_address" type="text" class="vendor-input" placeholder="e.g. GA-123-4567">
                                    </div>
                                    <div>
                                        <label class="form-label">Landmark</label>
                                        <input x-model="form.delivery_landmark" type="text" class="vendor-input" placeholder="Nearby landmark">
                                    </div>
                                </div>

                                {{-- Landmark only (coordinates) --}}
                                <div class="mt-4" x-show="form.delivery_location_method === 'coordinates'" x-cloak>
                                    <label class="form-label">Landmark</label>
                                    <input x-model="form.delivery_landmark" type="text" class="vendor-input" placeholder="Nearby landmark">
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-slate-100"></div>

                            {{-- Instructions section --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Delivery Instructions</span>
                                </div>
                                <textarea x-model="form.delivery_instructions" rows="2" class="vendor-input" placeholder="Delivery instructions for the driver (optional)"></textarea>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex justify-between border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                            <button type="button" @click="step = 2" class="vendor-btn-secondary group">
                                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back
                            </button>
                            <button type="button" @click="goToStep(4)" class="vendor-btn-primary group">
                                Review Shipment
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ============================================ --}}
                {{-- REVIEW STEP --}}
                {{-- ============================================ --}}
                <div x-show="(form.destination_mode === 'per_item' && step === 3) || (form.destination_mode === 'single' && step === 4)" x-cloak
                     x-transition:enter="step-enter"
                     x-transition:enter-start="step-enter-from"
                     x-transition:enter-end="step-enter-to">
                    <div class="space-y-5">
                        {{-- Review header --}}
                        <div class="vendor-card overflow-hidden">
                            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-white">Review Your Shipment</h2>
                                        <p class="mt-0.5 text-xs text-slate-300">Please verify all details before creating</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipment Type --}}
                        <div class="vendor-card overflow-hidden">
                            <div class="review-section-header">
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>Shipment Type</span>
                            </div>
                            <div class="px-5 pb-4">
                                <div class="review-row">
                                    <span class="review-label">Destination Mode</span>
                                    <span class="review-value">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                                              x-text="form.destination_mode === 'single' ? 'Single Destination' : 'Per-Item Destination'"></span>
                                    </span>
                                </div>
                                <div x-show="form.destination_mode === 'per_item'" class="mt-3 flex items-start gap-2.5 rounded-lg bg-blue-50 px-4 py-3">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-xs text-blue-700">You will set delivery addresses for each item individually after the shipment is created.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Pickup Summary --}}
                        <div class="vendor-card overflow-hidden">
                            <div class="review-section-header review-section-blue">
                                <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <span>Pickup Details</span>
                            </div>
                            <div class="px-5 pb-4">
                                <div class="review-row">
                                    <span class="review-label">Contact</span>
                                    <span class="review-value" x-text="form.pickup_contact_name || '-'"></span>
                                </div>
                                <div class="review-row">
                                    <span class="review-label">Phone</span>
                                    <span class="review-value font-mono text-xs" x-text="form.pickup_contact_phone || '-'"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_location_method === 'dropdown'">
                                    <span class="review-label">Region</span>
                                    <span class="review-value" x-text="getRegionName(form.pickup_region_id)"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_location_method === 'dropdown'">
                                    <span class="review-label">District</span>
                                    <span class="review-value" x-text="getDistrictName(form.pickup_region_id, form.pickup_district_id)"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_location_method === 'coordinates'">
                                    <span class="review-label">Coordinates</span>
                                    <span class="review-value font-mono text-xs" x-text="(form.pickup_latitude && form.pickup_longitude) ? form.pickup_latitude + ', ' + form.pickup_longitude : '-'"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_location_method === 'gh_post'">
                                    <span class="review-label">Ghana Post</span>
                                    <span class="review-value" x-text="form.pickup_gh_post_address || '-'"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_town">
                                    <span class="review-label">Town</span>
                                    <span class="review-value" x-text="form.pickup_town"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_landmark">
                                    <span class="review-label">Landmark</span>
                                    <span class="review-value" x-text="form.pickup_landmark"></span>
                                </div>
                                <div class="review-row" x-show="form.pickup_instructions">
                                    <span class="review-label">Instructions</span>
                                    <span class="review-value" x-text="form.pickup_instructions"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Delivery Summary (single only) --}}
                        <div class="vendor-card overflow-hidden" x-show="form.destination_mode === 'single'">
                            <div class="review-section-header review-section-green">
                                <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span>Delivery Details</span>
                            </div>
                            <div class="px-5 pb-4">
                                <div class="review-row">
                                    <span class="review-label">Recipient</span>
                                    <span class="review-value" x-text="form.delivery_recipient_name || '-'"></span>
                                </div>
                                <div class="review-row">
                                    <span class="review-label">Phone</span>
                                    <span class="review-value font-mono text-xs" x-text="form.delivery_recipient_phone || '-'"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_location_method === 'dropdown'">
                                    <span class="review-label">Region</span>
                                    <span class="review-value" x-text="getRegionName(form.delivery_region_id)"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_location_method === 'dropdown'">
                                    <span class="review-label">District</span>
                                    <span class="review-value" x-text="getDistrictName(form.delivery_region_id, form.delivery_district_id)"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_location_method === 'coordinates'">
                                    <span class="review-label">Coordinates</span>
                                    <span class="review-value font-mono text-xs" x-text="(form.delivery_latitude && form.delivery_longitude) ? form.delivery_latitude + ', ' + form.delivery_longitude : '-'"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_location_method === 'gh_post'">
                                    <span class="review-label">Ghana Post</span>
                                    <span class="review-value" x-text="form.delivery_gh_post_address || '-'"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_town">
                                    <span class="review-label">Town</span>
                                    <span class="review-value" x-text="form.delivery_town"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_landmark">
                                    <span class="review-label">Landmark</span>
                                    <span class="review-value" x-text="form.delivery_landmark"></span>
                                </div>
                                <div class="review-row" x-show="form.delivery_instructions">
                                    <span class="review-label">Instructions</span>
                                    <span class="review-value" x-text="form.delivery_instructions"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between">
                            <button type="button" @click="step = form.destination_mode === 'single' ? 3 : 2" class="vendor-btn-secondary group">
                                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back to Edit
                            </button>
                            <button type="button" @click="saveShipment()" :disabled="saving"
                                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition-all hover:shadow-xl hover:shadow-slate-900/25 disabled:opacity-55 disabled:cursor-not-allowed">
                                <svg class="h-4 w-4" x-show="!saving" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <svg class="h-4 w-4 animate-spin" x-show="saving" x-cloak fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="saving ? 'Creating...' : 'Create Shipment'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
