@extends('warehouse.layouts.app')

@section('title', 'Walk-in Shipment')

@section('content')
@php
    $walkinConfig = [
        'vendorLookupUrl' => route('warehouse.walkin.vendor-lookup'),
        'vendorCreateUrl' => route('warehouse.walkin.vendor-create'),
        'storeUrl' => route('warehouse.walkin.store'),
        'printLabelsUrl' => route('warehouse.walkin.print-labels'),
        'locationSearchUrl' => route('warehouse.locations.search'),
        'transferWarehouses' => $transferWarehouses,
        'debug' => (bool) config('app.debug'),
    ];
@endphp

<div x-data="walkinShipment()" x-init="init()" data-walkin-config='@json($walkinConfig)' class="mx-auto max-w-5xl space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex items-center gap-3">
                <a href="{{ route('warehouse.dashboard') }}" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-lg font-extrabold tracking-tight text-slate-950">Walk-in Shipment</h1>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Counter entry for packages received at {{ $warehouse->name }}.</p>
                </div>
            </div>
            <div class="flex items-center gap-1 overflow-x-auto rounded-xl bg-slate-50 p-1 text-xs font-black text-slate-500 sm:gap-2">
                <template x-for="item in stepItems" :key="item.id">
                    <button type="button" @click="item.id < step && (step = item.id)"
                            class="flex shrink-0 items-center gap-2 rounded-lg px-2 py-2 transition sm:px-3"
                            :class="step === item.id ? 'bg-orange-600 text-white shadow-sm shadow-orange-600/20' : (item.id < step ? 'text-orange-700 hover:bg-white' : 'cursor-default text-slate-400')">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-black"
                              :class="step === item.id ? 'bg-white text-orange-700' : (item.id < step ? 'bg-orange-100 text-orange-700' : 'bg-white text-slate-500 ring-1 ring-slate-200')"
                              x-text="item.id"></span>
                        <span class="whitespace-nowrap" x-text="item.label"></span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    <!-- STEP 1: VENDOR -->
    <section x-show="step === 1" x-transition.opacity class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <h2 class="text-sm font-extrabold text-slate-950">Vendor</h2>
            <p class="mt-1 text-xs text-slate-500">Search by phone. Existing vendors continue in one click.</p>
        </div>
        <div class="space-y-4 p-4 sm:p-5">
            <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Vendor phone</label>
                    <input type="tel" x-model="vendorPhone" @input="normalizeVendorPhoneInput()" @blur="validateVendorPhone(true)" @keydown.enter.prevent="lookupVendor()" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="0241234567"
                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                           :class="vendorPhoneError ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''">
                    <p x-show="vendorPhoneError" x-text="vendorPhoneError" class="mt-1 text-xs font-semibold text-rose-600"></p>
                </div>
                <button type="button" @click="lookupVendor()" :disabled="vendorLoading || !isValidPhone(vendorPhone)"
                        class="mt-auto rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white transition hover:border-slate-800 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm">
                    <span x-show="!vendorLoading">Search</span>
                    <span x-show="vendorLoading">Searching...</span>
                </button>
            </div>

            <div x-show="vendorFound === true && vendorData" x-transition class="rounded-2xl border border-orange-100 bg-orange-50/60 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-600 text-sm font-black text-white shadow-lg shadow-orange-600/15" x-text="vendorData?.name?.charAt(0)?.toUpperCase()"></div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-slate-950" x-text="vendorData?.name"></p>
                            <p class="truncate text-xs text-slate-600"><span x-text="vendorData?.business_name || 'No business name'"></span> · <span class="font-mono" x-text="vendorData?.phone"></span></p>
                        </div>
                    </div>
                    <button type="button" @click="selectVendor(vendorData)" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700">Continue</button>
                </div>
            </div>

            <div x-show="vendorFound === false" x-transition class="rounded-2xl border border-orange-100 bg-orange-50/60 p-4">
                <div class="mb-4">
                    <p class="text-sm font-extrabold text-slate-950">Vendor not found</p>
                    <p class="mt-1 text-xs text-slate-600">Enter the sender name to continue.</p>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="newVendor.name" placeholder="Sender name" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                </div>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="resetVendor()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                    <button type="button" @click="createVendor()" :disabled="creatingVendor || !newVendor.name"
                            class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm">
                        <span x-show="!creatingVendor">Create & continue</span>
                        <span x-show="creatingVendor">Creating...</span>
                    </button>
                </div>
            </div>

            <p x-show="vendorError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700" x-text="vendorError"></p>
        </div>
    </section>

    <!-- STEP 2: PACKAGES TABLE -->
    <section x-show="step === 2" x-transition.opacity class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-row items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:items-center sm:px-5">
                <div class="min-w-0">
                    <h2 class="text-sm font-extrabold text-slate-950">Packages</h2>
                    <p class="mt-1 text-xs text-slate-500">Each package carries its recipient details. Same recipient phone stays as one drop-off.</p>
                </div>
                <button type="button" @click="openPackageModal()" class="shrink-0 rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700">Add Package</button>
            </div>
            <div>
                <div x-show="items.length === 0" class="px-5 py-12 text-center">
                    <p class="text-sm font-bold text-slate-500">No packages added yet.</p>
                </div>

                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-[820px] w-full text-left">
                        <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Package</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3 text-right">Price (GHS)</th>
                                <th class="px-4 py-3">Recipient</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Location</th>
                                <th class="whitespace-nowrap px-4 py-3">Forward To</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, idx) in items" :key="item.key">
                                <tr class="align-top transition hover:bg-orange-50/20">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-slate-100 px-2 text-xs font-black text-slate-600" x-text="idx + 1"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="max-w-[200px] truncate text-sm font-black text-slate-950" x-text="item.description"></p>
                                        <span x-show="item.delivery_method === 'bus_handoff'" class="mt-1 inline-flex whitespace-nowrap rounded-lg bg-orange-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-orange-700 ring-1 ring-orange-100">Bus handoff</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-black text-slate-800" x-text="item.quantity"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono text-sm font-black text-emerald-600" x-text="'GH₵ ' + (parseFloat(item.delivery_fee || 0)).toFixed(2)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-700" x-text="item.delivery.recipient_name || 'Recipient'"></td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-slate-600" x-text="item.delivery.recipient_phone || '-'"></td>
                                    <td class="px-4 py-3 text-sm font-semibold leading-5 text-slate-700" x-text="item.delivery.locationQuery || '-'"></td>
                                    <td class="min-w-28 px-4 py-3 text-sm font-semibold leading-5 text-slate-700" x-text="warehouseLabel(item.forward_to_warehouse_id)"></td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openPackagePhotos(item)" class="rounded-xl border border-orange-100 bg-orange-50 px-3 py-2 text-xs font-black text-orange-700 transition hover:bg-orange-100">Photos</button>
                                            <button type="button" @click="openPackageModal(idx)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50">Edit</button>
                                            <button type="button" @click="removeItem(idx)" class="rounded-xl border border-rose-100 bg-white px-3 py-2 text-xs font-black text-rose-600 transition hover:bg-rose-50">Remove</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <button type="button" @click="step = 1" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:border-slate-300 hover:bg-slate-50">Back</button>
            <button type="button" @click="goToReview()" :disabled="!canProceedToReview()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">Review shipment</button>
        </div>
    </section>

    <!-- STEP 3: REVIEW -->
    <section x-show="step === 3" x-transition.opacity class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                <h2 class="text-sm font-extrabold text-slate-950">Review</h2>
                <p class="mt-1 text-xs text-slate-500">Confirm the package setup before creating labels.</p>
            </div>
            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Vendor</p>
                    <p class="mt-2 text-sm font-extrabold text-slate-950" x-text="vendorData?.name"></p>
                    <p class="mt-1 text-xs font-mono text-slate-500" x-text="vendorData?.phone"></p>
                </div>
                <div class="rounded-2xl border border-orange-100 bg-orange-50/60 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-orange-500">Detected Drop-Off</p>
                    <p class="mt-2 text-sm font-extrabold text-slate-950" x-text="detectedDropoffMode()"></p>
                    <p class="mt-1 text-xs text-slate-500">Based on recipient phone numbers</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Packages</p>
                    <p class="mt-2 text-sm font-extrabold text-slate-950"><span x-text="items.length"></span> package(s)</p>
                    <p class="mt-1 text-xs text-slate-500"><span x-text="items.filter(i => i.delivery_method === 'bus_handoff').length"></span> bus-station handoff</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Item Fees</p>
                    <p class="mt-2 text-base font-black text-emerald-600 font-mono" x-text="'GH₵ ' + totalItemFees().toFixed(2)"></p>
                    <p class="mt-1 text-xs text-slate-500">Sum of package fees</p>
                </div>
            </div>
            <div class="border-t border-slate-100">
                <div class="overflow-x-auto">
                    <table class="min-w-[820px] w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Package</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3 text-right">Price</th>
                                <th class="px-4 py-3">Recipient</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Location</th>
                                <th class="px-4 py-3">Handoff</th>
                                <th class="whitespace-nowrap px-4 py-3">Forward To</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, idx) in items" :key="'review-' + item.key">
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-xs font-black text-slate-500" x-text="idx + 1"></td>
                                    <td class="px-4 py-3 text-sm font-black text-slate-950" x-text="item.description"></td>
                                    <td class="px-4 py-3 text-center text-sm font-black text-slate-800" x-text="item.quantity"></td>
                                    <td class="px-4 py-3 text-right text-sm font-black text-emerald-600 font-mono" x-text="'GH₵ ' + (parseFloat(item.delivery_fee || 0)).toFixed(2)"></td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-700" x-text="item.delivery.recipient_name || 'Recipient'"></td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-slate-600" x-text="item.delivery.recipient_phone || '-'"></td>
                                    <td class="px-4 py-3 text-sm font-semibold leading-5 text-slate-700" x-text="item.delivery.locationQuery || '-'"></td>
                                    <td class="px-4 py-3">
                                        <span x-show="item.delivery_method === 'bus_handoff'" class="inline-flex whitespace-nowrap rounded-lg bg-orange-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-orange-700 ring-1 ring-orange-100">Bus handoff</span>
                                        <span x-show="item.delivery_method !== 'bus_handoff'" class="text-xs font-bold text-slate-400">Direct</span>
                                    </td>
                                    <td class="min-w-28 px-4 py-3 text-sm font-semibold leading-5 text-slate-700" x-text="warehouseLabel(item.forward_to_warehouse_id)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <button type="button" @click="step = 2" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:border-slate-300 hover:bg-slate-50">Edit details</button>
            <button type="button" @click="submitShipment()" :disabled="submitting" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                <span x-show="!submitting">Submit</span>
                <span x-show="submitting">Creating...</span>
            </button>
        </div>
        <p x-show="submitError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700" x-text="submitError"></p>
    </section>

    <!-- STEP 4: LABELS -->
    <section x-show="step === 4" x-transition.opacity class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-row items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:items-center sm:px-5">
            <div class="min-w-0">
                <h2 class="text-sm font-extrabold text-slate-950">Print Labels</h2>
                <p class="mt-1 text-xs text-slate-500">Print and attach package labels.</p>
            </div>
            <button type="button" @click="printAllLabels()" :disabled="printingLabels || !canPrintAnyLabels()"
                    class="shrink-0 rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:border-slate-800 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                <span x-show="!printingLabels">Print All</span>
                <span x-show="printingLabels">Printing...</span>
            </button>
        </div>
        <div class="border-b border-slate-100 bg-orange-50/40 px-4 py-3 text-xs font-bold text-slate-600 sm:px-5">
            <span x-text="totalLabelCount()"></span> label(s) selected across <span x-text="createdPackages.length"></span> package(s).
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-left">
                <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Package</th>
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3">Tracking</th>
                        <th class="px-4 py-3 text-center">Labels to print</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <template x-for="pkg in createdPackages" :key="pkg.id">
                        <tr class="align-middle">
                            <td class="px-4 py-4">
                                <p class="text-sm font-black text-slate-950" x-text="pkg.description"></p>
                                <p class="mt-1 text-xs text-slate-500"><span x-text="pkg.photo_count"></span> receipt photo(s)</p>
                            </td>
                            <td class="px-4 py-4 text-center text-sm font-black text-slate-800" x-text="pkg.quantity"></td>
                            <td class="px-4 py-4 font-mono text-xs font-bold text-orange-600" x-text="pkg.tracking_code || 'Will generate on print'"></td>
                            <td class="px-4 py-4">
                                <div class="mx-auto flex w-36 items-center justify-center gap-2">
                                    <button type="button" @click="setPackageLabelCount(pkg, Number(pkg.label_count || 1) - 1)" :disabled="printingLabels || Number(pkg.label_count || 1) <= 1"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">-</button>
                                    <input type="number" min="1" max="500" x-model.number="pkg.label_count" @input="setPackageLabelCount(pkg, pkg.label_count)"
                                           :disabled="printingLabels"
                                           class="h-9 w-16 rounded-xl border-2 border-slate-200 bg-white text-center text-sm font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:cursor-not-allowed disabled:bg-slate-50">
                                    <button type="button" @click="setPackageLabelCount(pkg, Number(pkg.label_count || 1) + 1)" :disabled="printingLabels || Number(pkg.label_count || 1) >= 500"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button type="button" @click="printLabel(pkg)" :disabled="printingLabels || !Number(pkg.label_count || 0)"
                                        class="rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                                    <span x-show="printingPackageId !== pkg.id" x-text="pkg.barcode_print_count ? 'Reprint' : 'Print'"></span>
                                    <span x-show="printingPackageId === pkg.id">Printing...</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ADD / EDIT PACKAGE MODAL WITH PRICE FIELD -->
    <template x-teleport="body">
        <div x-show="packageModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" style="display:none">
            <div @click.stop class="flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
                <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8.5l5-3 5 3-5 3-5-3zM4 13l5 3 5-3M10 16l5 3 5-3M4 13l5-3 5 3-5 3-5-3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900" x-text="packageModalIndex === null ? 'Add Package' : 'Edit Package'"></h3>
                            <p class="mt-1 text-sm text-slate-500">Record package details, recipient info, and receipt photos.</p>
                        </div>
                    </div>
                    <button type="button" @click="closePackageModal()" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Receipt photos <span class="text-rose-500">*</span></label>
                        <label class="flex min-w-0 cursor-pointer flex-col gap-3 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-3 py-3 transition hover:border-orange-300 hover:bg-orange-50/40 sm:flex-row sm:items-center sm:justify-between">
                            <span class="min-w-0 max-w-full">
                                <span class="block truncate text-sm font-bold text-slate-700" x-text="packageForm.photos.length ? packageForm.photos.length + ' photo(s) selected' : 'Upload or take package photos'"></span>
                                <span class="block text-xs font-medium text-slate-400">PNG, JPG or WEBP up to 12MB each</span>
                            </span>
                            <span class="inline-flex w-fit shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-black text-orange-700 shadow-sm ring-1 ring-orange-100">Take Photo</span>
                            <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" capture="environment" multiple class="hidden" @change="handlePackagePhotos($event)">
                        </label>
                    </div>

                    {{-- Description, Quantity, and Price Field Row --}}
                    <div class="grid gap-3 sm:grid-cols-12">
                        <div class="sm:col-span-5">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Description <span class="text-rose-500">*</span></label>
                            <input x-model="packageForm.description" placeholder="e.g. Package 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Quantity <span class="text-rose-500">*</span></label>
                            <input type="number" min="1" x-model.number="packageForm.quantity" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-center text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Price / Fee <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-xs font-bold text-slate-400">GH₵</span>
                                <input type="number" step="0.01" min="0" x-model.number="packageForm.delivery_fee" placeholder="0.00" class="w-full rounded-xl border-2 border-slate-200 bg-white pl-9 pr-3 py-3 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient name</label>
                                <input x-model="packageForm.delivery.recipient_name" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient phone <span class="text-rose-500">*</span></label>
                                <input type="tel" x-model="packageForm.delivery.recipient_phone" @input="normalizeDeliveryPhoneInput(packageForm.delivery)" @blur="validateDeliveryPhone(packageForm.delivery, true)" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="0241234567"
                                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                       :class="packageForm.delivery.phoneError ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''">
                                <p x-show="packageForm.delivery.phoneError" x-text="packageForm.delivery.phoneError" class="mt-1 text-xs font-semibold text-rose-600"></p>
                            </div>
                            <div class="relative sm:col-span-2" @click.outside="packageForm.delivery._showDropdown = false" @focusout="closeLocationDropdownSoon(packageForm.delivery)">
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Location <span class="text-rose-500">*</span></label>
                                <input x-model="packageForm.delivery.locationQuery" @input="searchLocation(packageForm.delivery)" @focus="packageForm.delivery.locationResults.length && (packageForm.delivery._showDropdown = true)"
                                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                                <div x-show="packageForm.delivery._showDropdown && packageForm.delivery.locationResults.length" class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                    <template x-for="loc in packageForm.delivery.locationResults" :key="loc.id">
                                        <button type="button" @click="selectLocation(packageForm.delivery, loc)" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-orange-50">
                                            <span x-text="loc.display"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="packageForm.delivery.locationError" x-text="packageForm.delivery.locationError" class="mt-1 text-xs font-semibold text-rose-600"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Instructions</label>
                                <textarea x-model="packageForm.delivery.instructions" rows="2" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border-2 border-slate-200 bg-white px-3 py-3">
                            <span>
                                <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Bus station</span>
                                <span class="block text-sm font-black text-slate-900">Send to bus station</span>
                            </span>
                            <input type="checkbox" x-model="packageForm.send_to_bus_station" @change="if (packageForm.send_to_bus_station) packageForm.forward_to_warehouse_id = ''" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        </label>

                        <div x-show="!packageForm.send_to_bus_station" class="rounded-xl border-2 border-orange-100 bg-orange-50/40 px-3 py-3" style="display:none">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-orange-600">Forward to warehouse</label>
                            <select x-model="packageForm.forward_to_warehouse_id"
                                    class="w-full rounded-xl border-2 border-orange-100 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                                <option value="">Keep at this warehouse</option>
                                <template x-for="warehouse in transferWarehouses" :key="warehouse.id">
                                    <option :value="warehouse.id" x-text="warehouse.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button" @click="closePackageModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                    <button type="button" @click="savePackageFromModal()" :disabled="!canSavePackageForm()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm" x-text="packageModalIndex === null ? 'Add Package' : 'Save Package'"></button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="photoPreviewOpen" x-cloak x-transition.opacity @click="closePackagePhotos()" @keydown.window.escape="closePackagePhotos()" @keydown.window.arrow-left="previousPackagePhoto()" @keydown.window.arrow-right="nextPackagePhoto()"
             class="fixed inset-0 z-[130] flex cursor-zoom-out items-center justify-center bg-black/85 p-8 backdrop-blur-sm" style="display:none">
            <button type="button" @click.stop="closePackagePhotos()" class="absolute right-4 top-4 z-20 rounded-full bg-black/45 p-3 text-white/80 transition hover:bg-black/70 hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <button type="button" x-show="photoPreviewUrls.length > 1" @click.stop="previousPackagePhoto()" class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/45 p-3 text-white/80 transition hover:bg-black/70 hover:text-white">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <img @click.stop :src="activePackagePhoto()?.url" :alt="activePackagePhoto()?.name || 'Package photo'" class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl ring-1 ring-white/10">
            <button type="button" x-show="photoPreviewUrls.length > 1" @click.stop="nextPackagePhoto()" class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/45 p-3 text-white/80 transition hover:bg-black/70 hover:text-white">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <div class="absolute bottom-4 left-1/2 z-20 -translate-x-1/2 rounded-full bg-black/45 px-3 py-1.5 text-xs font-bold text-white/90">
                <span x-text="photoPreviewPackage?.description || 'Package'"></span>
                <span x-show="photoPreviewUrls.length > 1"> · <span x-text="`${activePhotoIndex + 1} / ${photoPreviewUrls.length}`"></span></span>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
@include('shared.walkin-response-helpers')

function walkinShipment() {
    return {
        config: {},
        transferWarehouses: [],
        stepItems: [{ id: 1, label: 'Vendor' }, { id: 2, label: 'Packages' }, { id: 3, label: 'Review' }, { id: 4, label: 'Labels' }],
        step: 1,
        vendorPhone: '',
        vendorLoading: false,
        vendorFound: null,
        vendorData: null,
        vendorId: null,
        vendorError: '',
        vendorPhoneError: '',
        newVendor: { name: '', business_name: '', phone: '', email: '' },
        creatingVendor: false,
        delivery: null,
        pickupFeeAmount: '',
        items: [],
        itemSeed: 0,
        packageModalOpen: false,
        packageModalIndex: null,
        packageForm: null,
        submitting: false,
        submitError: '',
        createdPackages: [],
        printingLabels: false,
        printingPackageId: null,
        photoPreviewOpen: false,
        photoPreviewPackage: null,
        photoPreviewUrls: [],
        activePhotoIndex: 0,

        init() {
            this.config = JSON.parse(this.$el.dataset.walkinConfig);
            this.transferWarehouses = Array.isArray(this.config.transferWarehouses) ? this.config.transferWarehouses : [];
            this.delivery = this.makeDelivery();
            this.packageForm = this.emptyPackageForm();
        },

        makeDelivery() {
            return {
                recipient_name: '',
                recipient_phone: '',
                phoneError: '',
                locationQuery: '',
                locationResults: [],
                locationError: '',
                selectedLocation: null,
                _showDropdown: false,
                region_id: '',
                district_id: '',
                town: '',
                instructions: '',
            };
        },

        makeItem() {
            this.itemSeed += 1;
            return {
                key: `package-${Date.now()}-${this.itemSeed}`,
                description: `Package ${this.itemSeed}`,
                quantity: 1,
                delivery_fee: '',
                delivery_method: 'direct',
                forward_to_warehouse_id: '',
                delivery: this.makeDelivery(),
                photos: [],
            };
        },

        emptyPackageForm() {
            return {
                key: 'package-form-empty',
                description: '',
                quantity: 1,
                delivery_fee: '',
                delivery_method: 'direct',
                forward_to_warehouse_id: '',
                delivery: this.makeDelivery(),
                photos: [],
                send_to_bus_station: false,
            };
        },

        cloneItem(item) {
            return {
                ...item,
                delivery: { ...item.delivery, locationResults: [], _showDropdown: false },
                photos: [...(item.photos || [])],
            };
        },

        openPackageModal(index = null) {
            this.packageModalIndex = index;
            this.packageForm = index === null ? this.makeItem() : this.cloneItem(this.items[index]);
            this.packageForm.send_to_bus_station = this.packageForm.delivery_method === 'bus_handoff';
            this.validateDeliveryPhone(this.packageForm.delivery, false);
            this.packageModalOpen = true;
        },

        closePackageModal() {
            this.packageModalOpen = false;
            this.packageModalIndex = null;
            this.packageForm = this.emptyPackageForm();
        },

        savePackageFromModal() {
            if (!this.canSavePackageForm()) {
                this.validateDeliveryPhone(this.packageForm?.delivery, true);
                return;
            }
            const item = this.cloneItem(this.packageForm);
            item.delivery.recipient_name = item.delivery.recipient_name?.trim() || 'Recipient';
            item.delivery_method = item.send_to_bus_station ? 'bus_handoff' : 'direct';
            item.forward_to_warehouse_id = item.send_to_bus_station
                ? null
                : (item.forward_to_warehouse_id ? Number(item.forward_to_warehouse_id) : null);
            delete item.send_to_bus_station;
            if (this.packageModalIndex === null) {
                this.items.push(item);
            } else {
                this.items.splice(this.packageModalIndex, 1, item);
            }
            this.closePackageModal();
        },

        canSavePackageForm() {
            if (!this.packageForm) return false;
            const delivery = this.packageForm.delivery || {};
            return Boolean(
                this.packageForm.description?.trim()
                && Number(this.packageForm.quantity || 0) > 0
                && this.isValidPhone(delivery.recipient_phone)
                && delivery.locationQuery?.trim()
                && (this.packageForm.photos || []).length > 0
            );
        },

        totalItemFees() {
            return this.items.reduce((sum, i) => sum + (parseFloat(i.delivery_fee) || 0), 0);
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        warehouseLabel(id) {
            if (!id) return '-';
            const warehouse = this.transferWarehouses.find(row => Number(row.id) === Number(id));
            return warehouse ? warehouse.name : 'Selected warehouse';
        },

        handlePackagePhotos(event) {
            this.packageForm.photos = Array.from(event.target.files || []);
        },

        openPackagePhotos(item) {
            this.closePackagePhotos();
            this.photoPreviewPackage = item;
            this.photoPreviewUrls = (item.photos || []).map(file => ({
                name: file.name || 'Package photo',
                url: URL.createObjectURL(file),
            }));
            this.activePhotoIndex = 0;
            this.photoPreviewOpen = true;
        },

        closePackagePhotos() {
            this.photoPreviewUrls.forEach(photo => URL.revokeObjectURL(photo.url));
            this.photoPreviewUrls = [];
            this.photoPreviewPackage = null;
            this.activePhotoIndex = 0;
            this.photoPreviewOpen = false;
        },

        activePackagePhoto() {
            return this.photoPreviewUrls[this.activePhotoIndex] || this.photoPreviewUrls[0] || null;
        },

        nextPackagePhoto() {
            if (this.photoPreviewUrls.length <= 1) return;
            this.activePhotoIndex = (this.activePhotoIndex + 1) % this.photoPreviewUrls.length;
        },

        previousPackagePhoto() {
            if (this.photoPreviewUrls.length <= 1) return;
            this.activePhotoIndex = (this.activePhotoIndex - 1 + this.photoPreviewUrls.length) % this.photoPreviewUrls.length;
        },

        phoneValidationMessage(phone, showIncomplete = true) {
            const value = String(phone || '');
            const validPrefixes = ['020', '024', '025', '026', '027', '050', '054', '055', '056', '057', '059'];

            if (!value) return '';

            if (value.length !== 10) {
                return showIncomplete ? 'Phone number must be exactly 10 digits.' : '';
            }

            if (!value.startsWith('0') || !validPrefixes.includes(value.slice(0, 3))) {
                return 'Please enter a valid Ghana phone number.';
            }

            return '';
        },

        normalizePhoneValue(value) {
            return String(value || '').replace(/\D/g, '').slice(0, 10);
        },

        isValidPhone(phone) {
            return String(phone || '').length === 10 && !this.phoneValidationMessage(phone, true);
        },

        normalizeVendorPhoneInput() {
            this.vendorPhone = this.normalizePhoneValue(this.vendorPhone);
            this.validateVendorPhone(this.vendorPhone.length === 10);
            this.vendorFound = null;
            this.vendorData = null;
            this.vendorError = '';
        },

        validateVendorPhone(showIncomplete = true) {
            this.vendorPhoneError = this.phoneValidationMessage(this.vendorPhone, showIncomplete);
            return !this.vendorPhoneError;
        },

        normalizeDeliveryPhoneInput(delivery) {
            if (!delivery) return;
            delivery.recipient_phone = this.normalizePhoneValue(delivery.recipient_phone);
            this.validateDeliveryPhone(delivery, delivery.recipient_phone.length === 10);
        },

        validateDeliveryPhone(delivery, showIncomplete = true) {
            if (!delivery) return false;
            delivery.phoneError = this.phoneValidationMessage(delivery.recipient_phone, showIncomplete);
            return !delivery.phoneError;
        },

        async lookupVendor() {
            if (!this.validateVendorPhone(true)) return;
            this.vendorLoading = true;
            this.vendorFound = null;
            this.vendorData = null;
            this.vendorError = '';
            try {
                const res = await fetch(this.config.vendorLookupUrl + '?phone=' + encodeURIComponent(this.vendorPhone), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const response = await window.ParcelmanWalkinResponse.parse(res, {
                    debug: this.config.debug,
                    context: 'Unexpected vendor lookup response.',
                    messages: {
                        forbidden: 'You do not have permission to look up vendors.',
                        server: 'Server error while looking up vendor. Please try again.',
                        fallback: 'Vendor lookup failed. Please try again.',
                    },
                });
                if (!res.ok || !response.json) {
                    this.vendorError = response.message;
                    return;
                }
                const json = response.json;
                if (json.found) {
                    this.vendorFound = true;
                    this.vendorData = json.vendor;
                } else {
                    this.vendorFound = false;
                    this.newVendor.phone = this.vendorPhone;
                }
            } catch (error) {
                if (this.config.debug) {
                    console.error('Vendor lookup request failed before receiving a response.', error);
                }
                this.vendorError = 'Network error while looking up vendor. Please try again.';
            } finally {
                this.vendorLoading = false;
            }
        },

        selectVendor(vendor) {
            this.vendorId = vendor.id;
            this.vendorData = vendor;
            this.step = 2;
        },

        resetVendor() {
            this.vendorFound = null;
            this.vendorData = null;
            this.vendorId = null;
            this.vendorError = '';
            this.newVendor = { name: '', business_name: '', phone: '', email: '' };
        },

        async createVendor() {
            if (!this.newVendor.name || !this.validateVendorPhone(true)) return;
            this.creatingVendor = true;
            this.vendorError = '';
            try {
                this.newVendor.phone = this.vendorPhone;
                const res = await fetch(this.config.vendorCreateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newVendor),
                });
                const response = await window.ParcelmanWalkinResponse.parse(res, {
                    debug: this.config.debug,
                    context: 'Unexpected vendor create response.',
                    messages: {
                        forbidden: 'You do not have permission to create vendors.',
                        validation: 'Please check the vendor details and try again.',
                        server: 'Server error while creating vendor. Please try again.',
                        fallback: 'Vendor creation failed. Please try again.',
                    },
                });
                if (!res.ok || !response.json) {
                    this.vendorError = response.message;
                    return;
                }
                const json = response.json;
                this.vendorFound = true;
                this.vendorData = json.vendor;
                this.selectVendor(json.vendor);
            } catch (error) {
                if (this.config.debug) {
                    console.error('Vendor create request failed before receiving a response.', error);
                }
                this.vendorError = 'Network error while creating vendor. Please try again.';
            } finally {
                this.creatingVendor = false;
            }
        },

        searchLocation(target) {
            const query = target.locationQuery.trim();
            target.selectedLocation = null;
            target.region_id = '';
            target.district_id = '';
            target.town = query;
            target.locationError = '';
            if (query.length < 2) {
                target.locationResults = [];
                target._showDropdown = false;
                return;
            }
            clearTimeout(target._timeout);
            target._searchSeq = (target._searchSeq || 0) + 1;
            const searchSeq = target._searchSeq;
            target._timeout = setTimeout(async () => {
                try {
                    const res = await fetch(this.config.locationSearchUrl + '?q=' + encodeURIComponent(query), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const response = await window.ParcelmanWalkinResponse.parse(res, {
                        debug: this.config.debug,
                        context: 'Unexpected location search response.',
                        messages: {
                            forbidden: 'You do not have permission to search locations.',
                            server: 'Server error while searching locations. Please try again.',
                            fallback: 'Location search failed. Please try again.',
                        },
                    });
                    if (searchSeq !== target._searchSeq) return;
                    if (!res.ok || !response.json) {
                        target.locationError = response.message;
                        target.locationResults = [];
                        target._showDropdown = false;
                        return;
                    }
                    const json = response.json;
                    target.locationResults = json.locations || [];
                    target._showDropdown = target.locationResults.length > 0;
                } catch (error) {
                    if (this.config.debug) {
                        console.error('Location search request failed before receiving a response.', error);
                    }
                    target.locationError = 'Network error while searching locations. Please try again.';
                    target.locationResults = [];
                    target._showDropdown = false;
                }
            }, 250);
        },

        selectLocation(target, location) {
            clearTimeout(target._timeout);
            target._searchSeq = (target._searchSeq || 0) + 1;
            target.selectedLocation = location;
            target.locationQuery = location.display;
            target.locationResults = [];
            target.locationError = '';
            target._showDropdown = false;
            target.region_id = location.region?.id || '';
            target.district_id = location.district?.id || '';
            target.town = location.name || location.display;
        },

        closeLocationDropdownSoon(target) {
            setTimeout(() => {
                target._showDropdown = false;
            }, 120);
        },

        canProceedToReview() {
            if (!this.vendorId || this.items.length === 0) return false;
            return this.items.every(item => item.description && item.quantity > 0 && this.isValidPhone(item.delivery.recipient_phone) && item.delivery.locationQuery.trim());
        },

        detectedDropoffMode() {
            if (this.items.length <= 1) return 'Single drop-off';
            const signatures = new Set(this.items.map(item => this.recipientSignature(item.delivery)));
            return signatures.size === 1 ? 'Single drop-off' : 'Multiple drop-offs';
        },

        recipientSignature(delivery) {
            const phone = (delivery.recipient_phone || '').replace(/\D/g, '');
            return phone;
        },

        goToReview() {
            if (this.canProceedToReview()) this.step = 3;
        },

        deliveryPayload(delivery) {
            return {
                recipient_name: delivery.recipient_name,
                recipient_phone: delivery.recipient_phone,
                region_id: delivery.region_id || null,
                district_id: delivery.district_id || null,
                town: delivery.town || delivery.locationQuery,
                instructions: delivery.instructions,
            };
        },

        async submitShipment() {
            this.submitting = true;
            this.submitError = '';
            const items = this.items.map(item => ({
                description: item.description,
                quantity: item.quantity,
                delivery_fee: item.delivery_fee,
                delivery_method: item.delivery_method,
                ...(item.delivery_method !== 'bus_handoff' && item.forward_to_warehouse_id
                    ? { forward_to_warehouse_id: item.forward_to_warehouse_id }
                    : {}),
                delivery: this.deliveryPayload(item.delivery),
            }));

            const formData = new FormData();
            formData.append('vendor_id', this.vendorId);
            formData.append('fulfillment_type', 'warehouse');
            formData.append('delivery_preference', 'deliver');
            formData.append('destination_mode', 'per_item');
            formData.append('items_json', JSON.stringify(items));
            if (String(this.pickupFeeAmount ?? '').trim() !== '') {
                formData.append('pickup_fee_amount', this.pickupFeeAmount);
            }
            this.items.forEach((item, index) => {
                (item.photos || []).forEach(file => formData.append(`item_photos[${index}][]`, file));
            });

            try {
                const res = await fetch(this.config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const response = await window.ParcelmanWalkinResponse.parse(res, {
                    debug: this.config.debug,
                    context: 'Unexpected walk-in shipment response.',
                    unreadableMessage: 'Server returned an unreadable response while creating shipment.',
                    messages: {
                        forbidden: 'You do not have permission to create walk-in shipments.',
                        server: 'Server error while creating shipment. Please try again.',
                        fallback: `Shipment request failed with status ${res.status}. Please try again.`,
                    },
                });
                if (!res.ok || !response.json) {
                    this.submitError = response.message;
                    return;
                }
                const json = response.json;
                this.createdPackages = (json.packages || []).map(pkg => this.prepareCreatedPackage(pkg));
                this.step = 4;
            } catch (error) {
                if (this.config.debug) {
                    console.error('Walk-in shipment submit failed before receiving a response.', error);
                }
                this.submitError = 'Network error while creating shipment. Please try again.';
            } finally {
                this.submitting = false;
            }
        },

        prepareCreatedPackage(pkg) {
            const quantity = Math.max(1, Number(pkg.quantity || 1));
            return {
                ...pkg,
                label_count: Math.max(1, Number(pkg.label_count || quantity)),
            };
        },

        setPackageLabelCount(pkg, value) {
            const count = Math.floor(Number(value || 1));
            pkg.label_count = Number.isFinite(count) ? Math.max(1, Math.min(500, count)) : 1;
        },

        totalLabelCount() {
            return this.createdPackages.reduce((total, pkg) => total + Math.max(1, Number(pkg.label_count || 1)), 0);
        },

        canPrintAnyLabels() {
            return this.createdPackages.length > 0 && this.totalLabelCount() > 0 && this.totalLabelCount() <= 500;
        },

        printAllLabels() {
            const packages = this.createdPackages
                .map(pkg => ({ pkg, label_count: Math.max(1, Number(pkg.label_count || 1)) }))
                .filter(row => row.label_count > 0);

            return this.printRequestedLabels(packages);
        },

        async printLabel(pkg) {
            return this.printRequestedLabels([{ pkg, label_count: Math.max(1, Number(pkg.label_count || 1)) }], pkg.id);
        },

        async printRequestedLabels(packages, packageId = null) {
            if (!packages.length || this.printingLabels) return false;

            this.printingLabels = true;
            this.printingPackageId = packageId;
            this.submitError = '';

            try {
                const singlePackage = packages.length === 1 ? packages[0] : null;
                const res = await fetch(singlePackage ? singlePackage.pkg.print_url : this.config.printLabelsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: singlePackage
                        ? JSON.stringify({ label_count: singlePackage.label_count })
                        : JSON.stringify({
                            packages: packages.map(row => ({
                                shipment_item_id: row.pkg.id,
                                label_count: row.label_count,
                            })),
                        }),
                });
                const response = await window.ParcelmanWalkinResponse.parse(res, {
                    debug: this.config.debug,
                    context: 'Unexpected walk-in label response.',
                    messages: {
                        forbidden: 'You do not have permission to print labels.',
                        validation: 'Please check the label request and try again.',
                        server: 'Server error while generating labels. Please try again.',
                        fallback: 'Label generation failed. Please try again.',
                    },
                });
                if (!res.ok || !response.json) throw new Error(response.message);
                const json = response.json;
                if (!json.success) throw new Error(json.message || 'Failed to generate label.');
                const popup = window.open('', '_blank', 'width=900,height=650');
                if (!popup) throw new Error('Pop-up blocked. Please allow pop-ups to print labels.');
                popup.document.open();
                popup.document.write(json.data?.label_html || '');
                popup.document.close();
                this.applyPrintedPackages(json.data?.packages || []);
                return true;
            } catch (error) {
                this.submitError = error.message || 'Unable to print label.';
                return false;
            } finally {
                this.printingLabels = false;
                this.printingPackageId = null;
            }
        },

        applyPrintedPackages(packages) {
            packages.forEach(row => {
                const pkg = this.createdPackages.find(candidate => Number(candidate.id) === Number(row.shipment_item_id));
                if (!pkg) return;

                pkg.tracking_code = row.barcode_value || pkg.tracking_code;
                pkg.barcode_print_count = Number(row.print_count || pkg.barcode_print_count || 0);
                pkg.label_count = Math.max(1, Number(row.label_count || pkg.label_count || 1));
            });
        },
    };
}
</script>
@endpush