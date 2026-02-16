@extends('web.layouts.vendor')

@section('title', 'Shipment Details')

@section('content')
<div x-data="vendorShipmentShowPage()" data-shipment-id="{{ $shipmentId }}">

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-24">
        <svg class="h-7 w-7 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Main content --}}
    <div x-show="!loading && shipment" x-cloak>

        {{-- Toast notification --}}
        <div class="fixed top-4 right-4 z-50" x-show="toast" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-4">
            <div class="flex items-center gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg min-w-[280px] max-w-[400px]"
                 :class="{
                    'border-green-200 bg-green-50 text-green-700': toast?.type === 'success',
                    'border-red-200 bg-red-50 text-red-700': toast?.type === 'error'
                 }">
                <template x-if="toast?.type === 'success'">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast?.type === 'error'">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <span class="flex-1" x-text="toast?.message"></span>
                <button type="button" @click="toast = null" class="flex-shrink-0 opacity-60 hover:opacity-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Custom confirm dialog --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-show="confirmDialog" x-cloak>
            <div class="fixed inset-0 bg-black/40" @click="confirmNo()"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div class="relative rounded-xl bg-white p-6 shadow-xl max-w-sm w-full mx-4"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-start gap-3 mb-5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900">Are you sure?</h3>
                        <p class="mt-1 text-sm text-slate-600 break-all" x-text="confirmDialog?.message"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmNo()" class="vendor-btn-secondary py-2 px-4 text-xs">Cancel</button>
                    <button type="button" @click="confirmYes()" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">Confirm</button>
                </div>
            </div>
        </div>

        {{-- ========== HEADER ========== --}}
        <div class="mb-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('web.vendor.shipments.index') }}"
                       class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-slate-400 hover:bg-gray-50 hover:text-slate-600 transition flex-shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </a>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-slate-900 truncate" x-text="shipment?.shipment_number"></h1>
                        <div class="flex flex-wrap items-center gap-2 mt-0.5">
                            <span class="vendor-badge" :class="'vendor-badge-' + shipment?.status" x-text="statusLabel(shipment?.status)"></span>
                            <span class="text-xs text-slate-400" x-text="modeLabel(shipment?.destination_mode)"></span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a x-show="canEditShipment" x-cloak :href="`/vendor/shipments/${shipmentId}/edit`"
                       class="vendor-btn-secondary gap-1.5 py-2 px-3 text-xs">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Shipment
                    </a>
                    <button x-show="canSubmitShipment" x-cloak type="button" @click="submitShipment()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Submit
                    </button>
                    <button x-show="canDeleteShipment" x-cloak type="button" @click="deleteShipment()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete Shipment
                    </button>
                </div>
            </div>
        </div>

        {{-- ========== GRID LAYOUT ========== --}}
        {{-- Mobile: items first (order-1), sidebar second (order-2) --}}
        {{-- Desktop: sidebar left (order-1), items right (order-2) --}}
        <div class="grid gap-5 lg:grid-cols-[360px_1fr]">

            {{-- ===== ITEMS COLUMN ===== --}}
            <div class="order-1 lg:order-2 space-y-4">

                {{-- Items header --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Items</h3>
                            <p class="text-xs text-slate-400"><span x-text="shipment?.items?.length || 0"></span> item(s)</p>
                        </div>
                    </div>
                    <button x-show="canEditShipment && !addingItem" x-cloak type="button" @click="openAddItem()"
                            class="vendor-btn-primary py-2 px-3.5 text-xs gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>

                {{-- Item-level validation errors --}}
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-show="itemErrors.length" x-cloak>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <template x-for="err in itemErrors" :key="err"><li x-text="err"></li></template>
                    </ul>
                </div>

                {{-- ===== ADD ITEM FORM ===== --}}
                <div class="vendor-card overflow-hidden" x-show="addingItem" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="border-b border-gray-100 bg-slate-50 px-4 py-3 sm:px-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span class="text-sm font-bold text-slate-700">New Item</span>
                            </div>
                            <button type="button" @click="cancelAddItem()" class="text-slate-400 hover:text-slate-600 transition p-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <form class="p-4 sm:p-5 space-y-4" @submit.prevent="addItem()">
                        <div>
                            <label class="form-label">Description <span class="text-red-400">*</span></label>
                            <textarea x-model="itemForm.description" rows="2" class="vendor-input" placeholder="What are you shipping?"></textarea>
                        </div>
                        <div class="grid gap-4 grid-cols-2">
                            <div>
                                <label class="form-label">Quantity</label>
                                <input x-model.number="itemForm.quantity" type="number" min="1" class="vendor-input">
                            </div>
                            <div>
                                <label class="form-label">Images</label>
                                <input type="file" multiple accept="image/*" @change="onItemImagesSelected($event, 'create')"
                                       class="vendor-input text-xs file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-slate-600">
                            </div>
                        </div>

                        {{-- Per-item delivery fields --}}
                        <template x-if="isPerItemDestination">
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/30 p-4 space-y-3">
                                <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Delivery Details
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="form-label text-emerald-700">Recipient Name</label>
                                        <input x-model="itemForm.delivery_recipient_name" type="text" class="vendor-input">
                                    </div>
                                    <div>
                                        <label class="form-label text-emerald-700">Recipient Phone</label>
                                        <input x-model="itemForm.delivery_recipient_phone" @input="onItemPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input">
                                    </div>
                                    <div>
                                        <label class="form-label text-emerald-700">Confirm Phone</label>
                                        <input x-model="itemForm.delivery_recipient_phone_confirm" @input="onItemPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input">
                                    </div>
                                    <div>
                                        <label class="form-label text-emerald-700">Location Method</label>
                                        <select x-model="itemForm.delivery_location_method" class="vendor-input">
                                            <option value="dropdown">Region + district</option>
                                            <option value="coordinates">Coordinates</option>
                                            <option value="gh_post">Ghana Post</option>
                                        </select>
                                    </div>
                                    <div x-show="itemForm.delivery_location_method === 'dropdown'" x-cloak>
                                        <label class="form-label text-emerald-700">Region</label>
                                        <select x-model="itemForm.delivery_region_id" @change="onItemRegionChange()" class="vendor-input">
                                            <option value="">Select</option>
                                            <template x-for="region in regions" :key="region.id"><option :value="region.id" x-text="region.name"></option></template>
                                        </select>
                                    </div>
                                    <div x-show="itemForm.delivery_location_method === 'dropdown'" x-cloak>
                                        <label class="form-label text-emerald-700">District</label>
                                        <select x-model="itemForm.delivery_district_id" class="vendor-input">
                                            <option value="">Select</option>
                                            <template x-for="district in itemDistricts" :key="district.id"><option :value="district.id" x-text="district.name"></option></template>
                                        </select>
                                    </div>
                                    <div x-show="itemForm.delivery_location_method === 'coordinates'" x-cloak>
                                        <label class="form-label text-emerald-700">Latitude</label>
                                        <input x-model="itemForm.delivery_latitude" type="number" step="any" class="vendor-input">
                                    </div>
                                    <div x-show="itemForm.delivery_location_method === 'coordinates'" x-cloak>
                                        <label class="form-label text-emerald-700">Longitude</label>
                                        <input x-model="itemForm.delivery_longitude" type="number" step="any" class="vendor-input">
                                    </div>
                                    <div class="sm:col-span-2" x-show="itemForm.delivery_location_method === 'gh_post'" x-cloak>
                                        <label class="form-label text-emerald-700">Ghana Post Address</label>
                                        <input x-model="itemForm.delivery_gh_post_address" type="text" class="vendor-input">
                                    </div>
                                    <div>
                                        <label class="form-label text-emerald-700">Town</label>
                                        <input x-model="itemForm.delivery_town" type="text" class="vendor-input">
                                    </div>
                                    <div>
                                        <label class="form-label text-emerald-700">Landmark</label>
                                        <input x-model="itemForm.delivery_landmark" type="text" class="vendor-input">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label text-emerald-700">Instructions</label>
                                        <textarea x-model="itemForm.delivery_instructions" rows="2" class="vendor-input"></textarea>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" @click="cancelAddItem()" class="vendor-btn-secondary py-2.5 px-5 text-xs">Cancel</button>
                            <button type="submit" :disabled="savingItem" class="vendor-btn-primary py-2.5 px-5 text-xs">
                                <span x-show="!savingItem">Save Item</span>
                                <span x-show="savingItem" x-cloak class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ===== ITEM LIST ===== --}}
                <div class="space-y-3" x-show="(shipment?.items || []).length > 0" x-cloak>
                    <template x-for="(item, idx) in (shipment?.items || [])" :key="item.id">
                        <div class="vendor-card overflow-hidden">
                            {{-- Item header --}}
                            <div class="flex items-start gap-3 px-4 py-3.5 sm:px-5 border-b border-gray-100">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500 flex-shrink-0 mt-0.5" x-text="idx + 1"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-slate-900 text-sm" x-text="item.description"></div>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            Qty: <span class="font-semibold text-slate-700" x-text="item.quantity"></span>
                                        </span>
                                        <span class="vendor-badge" :class="'vendor-badge-' + item.status" x-text="statusLabel(item.status)"></span>
                                    </div>
                                </div>
                                <div class="flex gap-1.5 flex-shrink-0" x-show="canEditShipment">
                                    <button type="button" @click="openEditItem(item)"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-slate-400 hover:text-slate-600 hover:bg-gray-50 transition"
                                            title="Edit item">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" @click="deleteItem(item)"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                                            title="Delete item">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Item body (hidden when editing) --}}
                            <div class="px-4 py-3.5 sm:px-5 space-y-3" x-show="editingItemId !== item.id">
                                {{-- Per-item delivery info --}}
                                <div class="rounded-lg border border-emerald-100 bg-emerald-50/40 px-3.5 py-3 text-xs" x-show="item.delivery">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="font-bold uppercase tracking-wider text-emerald-700">Delivery</span>
                                    </div>
                                    <div class="grid gap-1.5 sm:grid-cols-2 text-emerald-900">
                                        <div><span class="text-emerald-600">To:</span> <span class="font-semibold" x-text="item.delivery?.recipient_name || '-'"></span></div>
                                        <div><span class="text-emerald-600">Phone:</span> <span class="font-semibold" x-text="item.delivery?.recipient_phone || '-'"></span></div>
                                        <div><span class="text-emerald-600">Town:</span> <span class="font-semibold" x-text="item.delivery?.location?.town || '-'"></span></div>
                                        <div><span class="text-emerald-600">Landmark:</span> <span class="font-semibold" x-text="item.delivery?.location?.landmark || '-'"></span></div>
                                    </div>
                                </div>

                                {{-- Pickup confirmation --}}
                                <div class="rounded-lg border border-blue-100 bg-blue-50/40 px-3.5 py-3 text-xs" x-show="item.pickup_confirmation">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <svg class="h-3.5 w-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="font-bold uppercase tracking-wider text-blue-700">Pickup Confirmation</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4 text-blue-900">
                                        <div><span class="text-blue-500">Expected:</span> <span class="font-bold" x-text="item.pickup_confirmation?.expected_quantity ?? '-'"></span></div>
                                        <div><span class="text-blue-500">Confirmed:</span> <span class="font-bold" x-text="item.pickup_confirmation?.confirmed_quantity ?? '-'"></span></div>
                                        <div><span class="text-blue-500">Missing:</span> <span class="font-bold" x-text="item.pickup_confirmation?.missing_quantity ?? '-'"></span></div>
                                        <div><span class="text-blue-500">Extra:</span> <span class="font-bold" x-text="item.pickup_confirmation?.extra_quantity ?? '-'"></span></div>
                                    </div>
                                </div>

                                {{-- Images --}}
                                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4" x-show="(item.images || []).length > 0">
                                    <template x-for="image in (item.images || [])" :key="image.id">
                                        <div class="group relative rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                                            <img :src="image.url" :alt="image.original_name" class="h-20 w-full object-cover sm:h-24">
                                            <button x-show="canEditShipment" type="button" @click="deleteItemImage(item, image)"
                                                    class="absolute inset-0 flex items-center justify-center bg-black/50 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                {{-- Inline image upload --}}
                                <div class="flex items-center gap-2 pt-2 border-t border-gray-100" x-show="canEditShipment">
                                    <input type="file" multiple accept="image/*" @change="setInlineUploadFiles(item.id, $event)"
                                           class="vendor-input flex-1 text-xs file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-slate-600">
                                    <button type="button" @click="uploadItemImages(item)"
                                            :disabled="imageUploadState[item.id] || !hasInlineUploadFiles(item.id)"
                                            class="vendor-btn-primary py-2 px-3 text-xs whitespace-nowrap disabled:opacity-50">
                                        <span x-show="!imageUploadState[item.id]">Upload</span>
                                        <span x-show="imageUploadState[item.id]" x-cloak class="flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Uploading...
                                        </span>
                                    </button>
                                </div>
                            </div>

                            {{-- ===== EDIT ITEM FORM (card-style matching Add Item) ===== --}}
                            <div x-show="editingItemId === item.id" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100">
                                <div class="border-b border-gray-100 bg-slate-50 px-4 py-3 sm:px-5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span class="text-sm font-bold text-slate-700">Edit Item</span>
                                        </div>
                                        <button type="button" @click="cancelEditItem()" class="text-slate-400 hover:text-slate-600 transition p-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <form class="p-4 sm:p-5 space-y-4" @submit.prevent="updateItem(item)">
                                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" x-show="editItemErrors.length" x-cloak>
                                        <ul class="list-disc pl-4"><template x-for="err in editItemErrors" :key="err"><li x-text="err"></li></template></ul>
                                    </div>

                                    <div>
                                        <label class="form-label">Description <span class="text-red-400">*</span></label>
                                        <textarea x-model="editItemForm.description" rows="2" class="vendor-input"></textarea>
                                    </div>
                                    <div class="grid gap-4 grid-cols-2">
                                        <div>
                                            <label class="form-label">Quantity</label>
                                            <input x-model.number="editItemForm.quantity" type="number" min="1" class="vendor-input">
                                        </div>
                                        <div>
                                            <label class="form-label">Add Images</label>
                                            <input type="file" multiple accept="image/*" @change="onItemImagesSelected($event, 'edit')"
                                                   class="vendor-input text-xs file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-slate-600">
                                        </div>
                                    </div>

                                    {{-- Existing images as thumbnails --}}
                                    <div x-show="(item.images || []).length > 0">
                                        <label class="form-label mb-2">Current Images</label>
                                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                            <template x-for="image in (item.images || [])" :key="`edit-img-${item.id}-${image.id}`">
                                                <label class="relative rounded-lg border overflow-hidden bg-gray-50 cursor-pointer group"
                                                       :class="editItemForm.remove_image_ids.includes(String(image.id)) ? 'border-red-300 ring-2 ring-red-200' : 'border-gray-200'">
                                                    <img :src="image.url" :alt="image.original_name" class="h-20 w-full object-cover sm:h-24"
                                                         :class="editItemForm.remove_image_ids.includes(String(image.id)) ? 'opacity-40' : ''">
                                                    <div class="absolute inset-0 flex items-center justify-center"
                                                         :class="editItemForm.remove_image_ids.includes(String(image.id)) ? 'bg-red-500/20' : 'bg-black/0 group-hover:bg-black/30'"
                                                         style="transition: background .15s">
                                                        <div class="flex h-7 w-7 items-center justify-center rounded-full transition"
                                                             :class="editItemForm.remove_image_ids.includes(String(image.id)) ? 'bg-red-500 text-white' : 'bg-white/80 text-slate-500 opacity-0 group-hover:opacity-100'">
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </div>
                                                    </div>
                                                    <input type="checkbox" :value="String(image.id)" x-model="editItemForm.remove_image_ids" class="sr-only">
                                                </label>
                                            </template>
                                        </div>
                                        <p class="text-[10.5px] text-slate-400 mt-1.5" x-show="editItemForm.remove_image_ids.length > 0">
                                            <span class="text-red-500 font-semibold" x-text="editItemForm.remove_image_ids.length"></span> image(s) marked for removal
                                        </p>
                                    </div>

                                    {{-- Per-item delivery fields for edit --}}
                                    <template x-if="isPerItemDestination">
                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/30 p-4 space-y-3">
                                            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-700">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Delivery Details
                                            </div>
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="form-label text-emerald-700">Recipient Name</label>
                                                    <input x-model="editItemForm.delivery_recipient_name" type="text" class="vendor-input">
                                                </div>
                                                <div>
                                                    <label class="form-label text-emerald-700">Recipient Phone</label>
                                                    <input x-model="editItemForm.delivery_recipient_phone" @input="onEditItemPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input">
                                                </div>
                                                <div>
                                                    <label class="form-label text-emerald-700">Confirm Phone</label>
                                                    <input x-model="editItemForm.delivery_recipient_phone_confirm" @input="onEditItemPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" class="vendor-input">
                                                </div>
                                                <div>
                                                    <label class="form-label text-emerald-700">Location Method</label>
                                                    <select x-model="editItemForm.delivery_location_method" class="vendor-input">
                                                        <option value="dropdown">Region + district</option>
                                                        <option value="coordinates">Coordinates</option>
                                                        <option value="gh_post">Ghana Post</option>
                                                    </select>
                                                </div>
                                                <div x-show="editItemForm.delivery_location_method === 'dropdown'" x-cloak>
                                                    <label class="form-label text-emerald-700">Region</label>
                                                    <select x-model="editItemForm.delivery_region_id" @change="onEditItemRegionChange()" class="vendor-input">
                                                        <option value="">Select</option>
                                                        <template x-for="region in regions" :key="region.id"><option :value="region.id" x-text="region.name"></option></template>
                                                    </select>
                                                </div>
                                                <div x-show="editItemForm.delivery_location_method === 'dropdown'" x-cloak>
                                                    <label class="form-label text-emerald-700">District</label>
                                                    <select x-model="editItemForm.delivery_district_id" class="vendor-input">
                                                        <option value="">Select</option>
                                                        <template x-for="district in editItemDistricts" :key="district.id"><option :value="district.id" x-text="district.name"></option></template>
                                                    </select>
                                                </div>
                                                <div x-show="editItemForm.delivery_location_method === 'coordinates'" x-cloak>
                                                    <label class="form-label text-emerald-700">Latitude</label>
                                                    <input x-model="editItemForm.delivery_latitude" type="number" step="any" class="vendor-input">
                                                </div>
                                                <div x-show="editItemForm.delivery_location_method === 'coordinates'" x-cloak>
                                                    <label class="form-label text-emerald-700">Longitude</label>
                                                    <input x-model="editItemForm.delivery_longitude" type="number" step="any" class="vendor-input">
                                                </div>
                                                <div class="sm:col-span-2" x-show="editItemForm.delivery_location_method === 'gh_post'" x-cloak>
                                                    <label class="form-label text-emerald-700">Ghana Post Address</label>
                                                    <input x-model="editItemForm.delivery_gh_post_address" type="text" class="vendor-input">
                                                </div>
                                                <div>
                                                    <label class="form-label text-emerald-700">Town</label>
                                                    <input x-model="editItemForm.delivery_town" type="text" class="vendor-input">
                                                </div>
                                                <div>
                                                    <label class="form-label text-emerald-700">Landmark</label>
                                                    <input x-model="editItemForm.delivery_landmark" type="text" class="vendor-input">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="form-label text-emerald-700">Instructions</label>
                                                    <textarea x-model="editItemForm.delivery_instructions" rows="2" class="vendor-input"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" @click="cancelEditItem()" class="vendor-btn-secondary py-2.5 px-5 text-xs">Cancel</button>
                                        <button type="submit" :disabled="updatingItem" class="vendor-btn-primary py-2.5 px-5 text-xs">
                                            <span x-show="!updatingItem">Save Changes</span>
                                            <span x-show="updatingItem" x-cloak class="flex items-center gap-1.5">
                                                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                Saving...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Empty items state --}}
                <div class="vendor-card px-4 py-12 text-center" x-show="(shipment?.items || []).length === 0 && !addingItem" x-cloak>
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <p class="text-sm font-medium text-slate-500">No items yet</p>
                    <p class="mt-1 text-xs text-slate-400">Add items to this shipment to get started</p>
                    <button x-show="canEditShipment" type="button" @click="openAddItem()"
                            class="vendor-btn-primary py-2 px-4 text-xs mt-4 gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add First Item
                    </button>
                </div>
            </div>

            {{-- ===== SIDEBAR COLUMN ===== --}}
            <div class="order-2 lg:order-1 space-y-3" x-data="{ panels: { status: true, pickup: true, delivery: true, invoice: true, history: false, assignment: true } }">

                {{-- ---- Status & Info ---- --}}
                <div class="rounded-xl bg-white border border-gray-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-slate-700 to-slate-500"></div>
                    <button type="button" @click="panels.status = !panels.status"
                            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 shadow-sm ring-1 ring-slate-200/50 flex-shrink-0">
                            <svg class="h-[18px] w-[18px] text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <h3 class="text-[13px] font-bold text-slate-800 leading-tight">Shipment Overview</h3>
                            <p class="text-[10.5px] text-slate-400 mt-0.5">Status & timeline</p>
                        </div>
                        <span class="vendor-badge text-[10px] px-2.5 py-1 flex-shrink-0" :class="'vendor-badge-' + shipment?.status" x-text="statusLabel(shipment?.status)"></span>
                        <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 ease-out flex-shrink-0" :class="{ '-rotate-180': panels.status }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="panels.status" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
                        <div class="border-t border-gray-100 px-4 py-3">
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                    <span class="text-xs font-medium">Mode</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="modeLabel(shipment?.destination_mode)"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs font-medium">Created</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="formatDateTime(shipment?.created_at)"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5" x-show="shipment?.submitted_at">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    <span class="text-xs font-medium">Submitted</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="formatDateTime(shipment?.submitted_at)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---- Pickup Details ---- --}}
                <div class="rounded-xl bg-white border border-gray-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-blue-500 to-blue-400"></div>
                    <button type="button" @click="panels.pickup = !panels.pickup"
                            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-sm ring-1 ring-blue-200/50 flex-shrink-0">
                            <svg class="h-[18px] w-[18px] text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <h3 class="text-[13px] font-bold text-slate-800 leading-tight">Pickup Location</h3>
                            <p class="text-[10.5px] text-slate-400 mt-0.5">Collection point</p>
                        </div>
                        <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 ease-out flex-shrink-0" :class="{ '-rotate-180': panels.pickup }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="panels.pickup" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
                        <div class="border-t border-gray-100 px-4 py-3">
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-xs font-medium">Contact</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.pickup?.contact_name || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span class="text-xs font-medium">Phone</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.pickup?.contact_phone || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-xs font-medium">Region</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.pickup?.location?.region || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    <span class="text-xs font-medium">District</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.pickup?.location?.district || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span class="text-xs font-medium">Town</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.pickup?.location?.town || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5" x-show="shipment?.pickup?.location?.landmark">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                    <span class="text-xs font-medium">Landmark</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.pickup?.location?.landmark"></span>
                            </div>
                        </div>
                        <div x-show="shipment?.pickup?.instructions" class="border-t border-gray-100 px-4 py-3">
                            <div class="flex items-start gap-2.5 rounded-lg bg-blue-50 px-3.5 py-3">
                                <svg class="h-4 w-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div class="text-xs text-blue-700">
                                    <span class="font-bold">Instructions:</span>
                                    <span x-text="shipment?.pickup?.instructions"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---- Delivery Details ---- --}}
                <div class="rounded-xl bg-white border border-gray-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-emerald-500 to-emerald-400"></div>
                    <button type="button" @click="panels.delivery = !panels.delivery"
                            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 shadow-sm ring-1 ring-emerald-200/50 flex-shrink-0">
                            <svg class="h-[18px] w-[18px] text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <h3 class="text-[13px] font-bold text-slate-800 leading-tight">Delivery Destination</h3>
                            <p class="text-[10.5px] text-slate-400 mt-0.5">Drop-off details</p>
                        </div>
                        <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 ease-out flex-shrink-0" :class="{ '-rotate-180': panels.delivery }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="panels.delivery" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
                        <template x-if="shipment?.delivery">
                            <div class="border-t border-gray-100 px-4 py-3">
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span class="text-xs font-medium">Recipient</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.delivery?.recipient_name || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span class="text-xs font-medium">Phone</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.delivery?.recipient_phone || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-medium">Region</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.delivery?.location?.region || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                        <span class="text-xs font-medium">District</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.delivery?.location?.district || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span class="text-xs font-medium">Town</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.delivery?.location?.town || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5" x-show="shipment?.delivery?.location?.landmark">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                        <span class="text-xs font-medium">Landmark</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="shipment?.delivery?.location?.landmark"></span>
                                </div>
                                <div x-show="shipment?.delivery?.instructions" class="mt-2">
                                    <div class="flex items-start gap-2.5 rounded-lg bg-emerald-50 px-3.5 py-3">
                                        <svg class="h-4 w-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <div class="text-xs text-emerald-700">
                                            <span class="font-bold">Instructions:</span>
                                            <span x-text="shipment?.delivery?.instructions"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!shipment?.delivery">
                            <div class="border-t border-gray-100 px-4 py-8 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                                    <svg class="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="text-xs font-medium text-slate-500">Per-item delivery mode</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">Each item has its own delivery address</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ---- Current Invoice ---- --}}
                <div class="rounded-xl bg-white border border-gray-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-amber-500 to-orange-400"></div>
                    <button type="button" @click="panels.invoice = !panels.invoice"
                            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 shadow-sm ring-1 ring-amber-200/50 flex-shrink-0">
                            <svg class="h-[18px] w-[18px] text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <h3 class="text-[13px] font-bold text-slate-800 leading-tight">Invoice</h3>
                            <p class="text-[10.5px] text-slate-400 mt-0.5">Pricing & billing</p>
                        </div>
                        <a x-show="currentInvoice?.id" x-cloak :href="`/vendor/invoices/${currentInvoice?.id}`"
                           @click.stop
                           class="rounded-full bg-amber-50 px-2.5 py-1 text-[10.5px] font-semibold text-amber-700 hover:bg-amber-100 transition ring-1 ring-amber-200/50 flex-shrink-0">View &rarr;</a>
                        <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 ease-out flex-shrink-0" :class="{ '-rotate-180': panels.invoice }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="panels.invoice" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
                        <div x-show="currentInvoice" x-cloak>
                            <div class="border-t border-gray-100 px-4 py-3">
                                {{-- Total amount highlight --}}
                                <div class="rounded-lg bg-amber-50/80 ring-1 ring-amber-100 px-4 py-3 text-center mb-3">
                                    <span class="block text-[10px] font-medium text-amber-600 uppercase tracking-wider mb-0.5">Total Amount</span>
                                    <span class="text-xl font-extrabold text-slate-900" x-text="formatMoney(currentInvoice?.total_amount, currentInvoice?.currency || 'GHS')"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                        <span class="text-xs font-medium">Invoice #</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="currentInvoice?.invoice_number || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-medium">Status</span>
                                    </div>
                                    <span class="vendor-badge text-[10px]" :class="'vendor-badge-' + currentInvoice?.status" x-text="statusLabel(currentInvoice?.status)"></span>
                                </div>
                                <div class="flex items-center justify-between py-2.5">
                                    <div class="flex items-center gap-2.5 text-slate-500">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-xs font-medium">Created</span>
                                    </div>
                                    <span class="text-[13px] font-semibold text-slate-800" x-text="formatDateTime(currentInvoice?.created_at)"></span>
                                </div>
                            </div>
                            {{-- Accept / Reject --}}
                            <div class="px-4 pb-4" x-show="canRespondToInvoice" x-cloak>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="acceptCurrentInvoice()"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-3 py-2.5 text-xs font-bold text-white hover:bg-green-700 transition shadow-sm">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Accept
                                    </button>
                                    <button type="button" @click="rejectCurrentInvoice()"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border-2 border-red-200 bg-white px-3 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div x-show="!currentInvoice" class="border-t border-gray-100 px-4 py-8 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
                                <svg class="h-6 w-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </div>
                            <p class="text-xs font-medium text-slate-500">No invoice yet</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">Invoice will appear after submission</p>
                        </div>
                    </div>
                </div>

                {{-- ---- Invoice History ---- --}}
                <div class="rounded-xl bg-white border border-gray-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden" x-show="invoiceHistory.length > 0" x-cloak>
                    <div class="h-[3px] bg-gradient-to-r from-purple-500 to-violet-400"></div>
                    <button type="button" @click="panels.history = !panels.history"
                            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-sm ring-1 ring-purple-200/50 flex-shrink-0">
                            <svg class="h-[18px] w-[18px] text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <h3 class="text-[13px] font-bold text-slate-800 leading-tight">Invoice History</h3>
                            <p class="text-[10.5px] text-slate-400 mt-0.5">Past invoices</p>
                        </div>
                        <span class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-purple-100 px-1.5 text-[10px] font-bold text-purple-700 ring-1 ring-purple-200/50 flex-shrink-0" x-text="invoiceHistory.length"></span>
                        <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 ease-out flex-shrink-0" :class="{ '-rotate-180': panels.history }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="panels.history" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
                        <div class="border-t border-gray-100 px-4 py-3">
                            <div class="space-y-2.5">
                                <template x-for="(inv, i) in invoiceHistory" :key="inv.id || i">
                                    <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-3 hover:border-purple-200 hover:bg-purple-50/30 transition">
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-2 rounded-full flex-shrink-0"
                                                     :class="{
                                                         'bg-green-400 shadow-sm shadow-green-200': inv.status === 'accepted',
                                                         'bg-red-400 shadow-sm shadow-red-200': inv.status === 'rejected',
                                                         'bg-blue-400 shadow-sm shadow-blue-200': inv.status === 'sent',
                                                         'bg-amber-400 shadow-sm shadow-amber-200': inv.status === 'pending',
                                                         'bg-slate-300': !['accepted','rejected','sent','pending'].includes(inv.status)
                                                     }"></div>
                                                <span class="text-xs font-bold text-slate-800" x-text="inv.invoice_number || `Invoice #${i + 1}`"></span>
                                            </div>
                                            <span class="vendor-badge text-[10px]" :class="'vendor-badge-' + inv.status" x-text="statusLabel(inv.status)"></span>
                                        </div>
                                        <div class="flex items-center justify-between pl-4">
                                            <span class="text-sm font-bold text-slate-700" x-text="formatMoney(inv.total_amount, inv.currency || 'GHS')"></span>
                                            <span class="text-[10px] text-slate-400" x-text="formatDateTime(inv.created_at)"></span>
                                        </div>
                                        <div x-show="inv.rejection_reason" class="mt-2 ml-4 rounded-md bg-red-50 border border-red-100 px-2.5 py-2 text-[11px] text-red-600">
                                            <svg class="h-3 w-3 text-red-400 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="inv.rejection_reason"></span>
                                        </div>
                                        <div x-show="inv.vendor_notes" class="mt-2 ml-4 rounded-md bg-slate-50 border border-slate-100 px-2.5 py-2 text-[11px] text-slate-500">
                                            <svg class="h-3 w-3 text-slate-400 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                            <span x-text="inv.vendor_notes"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---- Pickup Assignment ---- --}}
                <div class="rounded-xl bg-white border border-gray-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-indigo-500 to-indigo-400"></div>
                    <button type="button" @click="panels.assignment = !panels.assignment"
                            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 shadow-sm ring-1 ring-indigo-200/50 flex-shrink-0">
                            <svg class="h-[18px] w-[18px] text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0m-8 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <h3 class="text-[13px] font-bold text-slate-800 leading-tight">Pickup Assignment</h3>
                            <p class="text-[10.5px] text-slate-400 mt-0.5">Driver & logistics</p>
                        </div>
                        <svg class="h-4 w-4 text-slate-300 transition-transform duration-300 ease-out flex-shrink-0" :class="{ '-rotate-180': panels.assignment }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="panels.assignment" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
                        <div x-show="pickupAssignment" x-cloak class="border-t border-gray-100 px-4 py-3">
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-xs font-medium">Status</span>
                                </div>
                                <span class="vendor-badge text-[10px]" :class="'vendor-badge-' + pickupAssignment?.status" x-text="statusLabel(pickupAssignment?.status)"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-xs font-medium">Driver</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="pickupAssignment?.driver_name || pickupAssignment?.driver?.name || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span class="text-xs font-medium">Phone</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="pickupAssignment?.driver_phone || pickupAssignment?.driver?.phone || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <div class="flex items-center gap-2.5 text-slate-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                    <span class="text-xs font-medium">Warehouse</span>
                                </div>
                                <span class="text-[13px] font-semibold text-slate-800" x-text="pickupAssignment?.target_warehouse?.name || '-'"></span>
                            </div>
                        </div>
                        <div x-show="!pickupAssignment" class="border-t border-gray-100 px-4 py-8 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50">
                                <svg class="h-6 w-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0m-8 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                            </div>
                            <p class="text-xs font-medium text-slate-500">No pickup assigned yet</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">Driver will be assigned after processing</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- Mobile floating add-item button --}}
    <button x-show="!loading && shipment && canEditShipment && !addingItem && (shipment?.items?.length || 0) > 0"
            x-cloak type="button" @click="openAddItem(); window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-5 right-5 z-30 flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 text-white shadow-lg hover:bg-slate-700 transition lg:hidden">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    </button>
</div>
@endsection
