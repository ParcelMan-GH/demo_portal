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

        {{-- ========== HERO HEADER ========== --}}
        <div class="sh-hero" :class="[heroClass, progressSteps.isCancelled ? 'no-progress' : '']">
            <div class="sh-hero-inner">
                {{-- Top row: back + status badge --}}
                <div class="sh-hero-top">
                    <div class="sh-hero-left">
                        <a href="{{ route('web.vendor.shipments.index') }}" class="sh-back" title="Back to Shipments">
                            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </a>
                        <span class="sh-title-text">Shipment</span>
                    </div>
                    <span class="sh-status-badge">
                        <span x-text="statusLabel(shipment?.status)"></span>
                    </span>
                </div>

                {{-- Shipment number --}}
                <h1 class="sh-number" x-text="shipment?.shipment_number"></h1>

                {{-- Meta row --}}
                <div class="sh-meta">
                    <span class="sh-meta-item">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="formatDateTime(shipment?.created_at)"></span>
                    </span>
                    <span class="sh-meta-item">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span x-text="(shipment?.items?.length || 0) + ' item(s)'"></span>
                    </span>
                    <span class="sh-meta-item">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/></svg>
                        <span x-text="modeLabel(shipment?.destination_mode)"></span>
                    </span>
                    <span class="sh-meta-item" x-show="shipment?.submitted_at">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Submitted: <span x-text="formatDateTime(shipment?.submitted_at)"></span>
                    </span>
                </div>

                {{-- Status message + action buttons --}}
                <div class="sh-status-section">
                    <div class="sh-status-icon">
                        <template x-if="['draft'].includes(shipment?.status)">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </template>
                        <template x-if="['submitted'].includes(shipment?.status)">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <template x-if="['invoice_sent','invoice_accepted'].includes(shipment?.status)">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </template>
                        <template x-if="['pickup_assigned','picked_up'].includes(shipment?.status)">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </template>
                        <template x-if="['at_warehouse','sorted'].includes(shipment?.status)">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        </template>
                        <template x-if="['in_transit','at_destination','out_for_delivery'].includes(shipment?.status)">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </template>
                        <template x-if="shipment?.status === 'delivered'">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <template x-if="shipment?.status === 'cancelled'">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </template>
                    </div>
                    <div class="sh-status-content">
                        <h4 class="sh-status-title" x-text="heroMessage.title"></h4>
                        <p class="sh-status-text" x-text="heroMessage.text"></p>
                    </div>
                    <div class="sh-status-action">
                        <a x-show="canEditShipment" x-cloak :href="`/vendor/shipments/${shipmentId}/edit`" class="sh-action-btn">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </a>
                        <button x-show="canSubmitShipment" x-cloak type="button" @click="submitShipment()" class="sh-action-btn">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Submit
                        </button>
                        <button x-show="canDeleteShipment" x-cloak type="button" @click="deleteShipment()" class="sh-action-btn sh-action-btn-danger">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== PROGRESS STEPS (fused below hero) ========== --}}
        <div class="sh-progress" x-show="!progressSteps.isCancelled">
            <div class="sh-progress-header">
                <h3 class="sh-progress-title">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    Shipment Progress
                </h3>
                <span class="sh-progress-status" x-text="`Step ${progressSteps.currentIdx + 1} of ${progressSteps.total}`"></span>
            </div>

            {{-- Desktop: horizontal --}}
            <div class="sh-steps" :data-progress="progressSteps.currentIdx">
                <template x-for="(step, i) in progressSteps.steps" :key="step.key">
                    <div class="sh-step" :class="'sh-step-' + step.state">
                        <div class="sh-step-dot">
                            <template x-if="step.state === 'done'">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step.state === 'active'">
                                <div class="h-2 w-2 rounded-full bg-current"></div>
                            </template>
                            <template x-if="step.state === 'pending'">
                                <div class="h-1.5 w-1.5 rounded-full bg-current"></div>
                            </template>
                        </div>
                        <span class="sh-step-text" x-text="step.label"></span>
                    </div>
                </template>
            </div>

            {{-- Mobile: vertical --}}
            <div class="sh-steps-mobile" :data-progress="progressSteps.currentIdx">
                <template x-for="(step, i) in progressSteps.steps" :key="'m-' + step.key">
                    <div class="sh-step-m" :class="'sh-step-' + step.state">
                        <div class="sh-step-dot-m">
                            <template x-if="step.state === 'done'">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step.state === 'active'">
                                <div class="h-1.5 w-1.5 rounded-full bg-current"></div>
                            </template>
                            <template x-if="step.state === 'pending'">
                                <div class="h-1 w-1 rounded-full bg-current"></div>
                            </template>
                        </div>
                        <span class="sh-step-text-m" x-text="step.label"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- ========== MAIN GRID ========== --}}
        <div class="sh-grid">
                {{-- ===== ITEMS CARD ===== --}}
                <div class="sh-card sh-items-card">
                    <div class="sh-card-head">
                        <div class="sh-card-icon slate">
                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3>Items</h3>
                        </div>
                        <div class="sh-items-header-right">
                            <div class="sh-items-total-qty">
                                <span class="sh-items-total-qty-num" x-text="(shipment?.items || []).reduce((s, i) => s + (i.quantity || 0), 0)"></span>
                                <span class="sh-items-total-qty-label">Total Qty</span>
                            </div>
<button x-show="canEditShipment && !addingItem" x-cloak type="button" @click="openAddItem()"
                                    class="sh-card-action-btn">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Item
                            </button>
                        </div>
                    </div>

                    <div class="sh-card-body">
                        {{-- Item-level validation errors --}}
                        <div class="mx-4 mt-4 sm:mx-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-show="itemErrors.length" x-cloak>
                            <ul class="list-disc pl-5 space-y-0.5">
                                <template x-for="err in itemErrors" :key="err"><li x-text="err"></li></template>
                            </ul>
                        </div>

                        {{-- ===== ADD ITEM FORM ===== --}}
                        <div x-show="addingItem" x-cloak
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
                                                <select x-model="itemForm.delivery_location_method" class="vendor-input"
                                                        @change="if($el.value !== 'dropdown') clearItemLocation();">
                                                    <option value="dropdown">Location search</option>
                                                    <option value="coordinates">Coordinates</option>
                                                    <option value="gh_post">Ghana Post</option>
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2" x-show="itemForm.delivery_location_method === 'dropdown'" x-cloak>
                                                <label class="form-label text-emerald-700">Location <span class="text-red-400">*</span></label>
                                                <div class="loc-typeahead">
                                                    <div x-show="itemSelectedLocation" class="loc-selected">
                                                        <span x-text="itemSelectedLocation?.display"></span>
                                                        <button type="button" @click="clearItemLocation()" class="loc-clear-btn">×</button>
                                                    </div>
                                                    <div x-show="!itemSelectedLocation" class="relative">
                                                        <input x-model="itemLocationQuery"
                                                               @input.debounce.250ms="searchItemLocation()"
                                                               type="text" class="vendor-input pr-10"
                                                               placeholder="Type a town or city name...">
                                                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                                            <svg x-show="itemLocationSearching" class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                            <svg x-show="!itemLocationSearching" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                        </div>
                                                        <div x-show="itemLocationResults.length > 0" x-cloak class="loc-dropdown">
                                                            <template x-for="loc in itemLocationResults" :key="loc.id">
                                                                <button type="button" @click="selectItemLocation(loc)" class="loc-option">
                                                                    <div class="loc-option-name" x-text="loc.name"></div>
                                                                    <div class="loc-option-sub" x-text="`${loc.district.name}, ${loc.region.name}`"></div>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" x-model="itemForm.delivery_region_id">
                                                <input type="hidden" x-model="itemForm.delivery_district_id">
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
                                            <div x-show="itemForm.delivery_location_method !== 'dropdown'" x-cloak>
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
                        <div x-show="(shipment?.items || []).length > 0" x-cloak>
                            <template x-for="(item, idx) in (shipment?.items || [])" :key="item.id">
                                <div class="sh-item" :class="{ 'sh-item-editing': editingItemId === item.id }">
                                    {{-- Item content (hidden when editing) --}}
                                    <div class="sh-item-content" x-show="editingItemId !== item.id">
                                        <div class="sh-item-icon">
                                            <svg class="h-[22px] w-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                        <div class="sh-item-main">
                                            <div class="sh-item-header">
                                                <div class="sh-item-title-group">
                                                    <div class="sh-item-name" x-text="item.description"></div>
                                                    <div class="sh-item-tracking" x-show="item.tracking_code" x-cloak>
                                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                        <span x-text="item.tracking_code"></span>
                                                    </div>
                                                </div>
                                                <div class="sh-item-qty-group">
                                                    <span class="sh-item-qty">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                        Qty: <span x-text="item.quantity"></span>
                                                    </span>
                                                    <span class="sh-item-confirmed" x-show="item.pickup_confirmation" x-cloak>
                                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        <span x-text="item.pickup_confirmation?.confirmed_quantity ?? 0"></span> confirmed
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="sh-item-meta">
                                                <span x-show="item.status !== 'picked_up'" class="vendor-badge" :class="'vendor-badge-' + item.status" x-text="statusLabel(item.status)"></span>
                                            </div>

                                            {{-- Per-item delivery info --}}
                                            <div class="sh-item-delivery" x-show="item.delivery">
                                                <div class="sh-item-delivery-header">
                                                    <div class="sh-item-delivery-avatar">
                                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    </div>
                                                    <div class="sh-item-delivery-recipient">
                                                        <span class="sh-item-delivery-name" x-text="item.delivery?.recipient_name || '-'"></span>
                                                        <span class="sh-item-delivery-label">Recipient</span>
                                                    </div>
                                                    <div class="sh-item-delivery-phone-pill" x-show="item.delivery?.recipient_phone">
                                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                        <span x-text="item.delivery?.recipient_phone"></span>
                                                    </div>
                                                </div>
                                                <div class="sh-item-delivery-address" x-show="item.delivery?.location?.town || item.delivery?.location?.landmark">
                                                    <div class="sh-item-delivery-pin">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                                                    </div>
                                                    <div class="sh-item-delivery-address-text">
                                                        <span x-show="item.delivery?.location?.town" x-text="item.delivery?.location?.town"></span>
                                                        <span class="sh-item-delivery-dot" x-show="item.delivery?.location?.town && item.delivery?.location?.landmark">&middot;</span>
                                                        <span x-show="item.delivery?.location?.landmark" x-text="item.delivery?.location?.landmark"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Vendor Images --}}
                                            <div x-show="(item.images || []).length > 0" class="sh-item-photo-section">
                                                <div class="sh-item-photo-label">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    Vendor Photos
                                                    <span class="sh-item-photo-count" x-text="(item.images || []).length"></span>
                                                </div>
                                                <div class="sh-item-images">
                                                    <template x-for="image in (item.images || [])" :key="image.id">
                                                        <div class="sh-item-image group" x-data="{ broken: false, loaded: false }">
                                                            <img x-show="loaded && !broken" :src="image.url" :alt="image.original_name"
                                                                 x-on:load="loaded = true"
                                                                 x-on:error="broken = true"
                                                                 class="sh-item-image-img">
                                                            <div x-show="!loaded && !broken" class="sh-item-image-loader">
                                                                <svg class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                            </div>
                                                            <div x-show="broken" class="sh-item-image-fallback">
                                                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                <span class="sh-item-image-fallback-text">Failed</span>
                                                            </div>
                                                            <button x-show="canEditShipment" type="button" @click="deleteItemImage(item, image)"
                                                                    class="sh-item-image-delete">
                                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Driver Pickup Photos --}}
                                            <div x-show="(item.pickup_confirmation?.photos || []).length > 0" x-cloak class="sh-item-photo-section driver">
                                                <div class="sh-item-photo-label driver">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    Pickup Photos
                                                    <span class="sh-item-photo-count driver" x-text="(item.pickup_confirmation?.photos || []).length"></span>
                                                </div>
                                                <div class="sh-item-images">
                                                    <template x-for="photo in (item.pickup_confirmation?.photos || [])" :key="photo.id">
                                                        <div class="sh-item-image group" x-data="{ broken: false, loaded: false }">
                                                            <img x-show="loaded && !broken" :src="photo.url" :alt="photo.original_name"
                                                                 x-on:load="loaded = true"
                                                                 x-on:error="broken = true"
                                                                 class="sh-item-image-img">
                                                            <div x-show="!loaded && !broken" class="sh-item-image-loader">
                                                                <svg class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                            </div>
                                                            <div x-show="broken" class="sh-item-image-fallback">
                                                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                <span class="sh-item-image-fallback-text">Failed</span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Inline image upload --}}
                                            <div class="flex items-center gap-2 pt-3 border-t border-gray-100 mt-3" x-show="canEditShipment">
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

                                        {{-- Action buttons --}}
                                        <div class="sh-item-actions" x-show="canEditShipment">
                                            <button type="button" @click="openEditItem(item)" class="sh-item-action-btn" title="Edit item">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button type="button" @click="deleteItem(item)" class="sh-item-action-btn danger" title="Delete item">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- ===== EDIT ITEM FORM ===== --}}
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
                                                            <select x-model="editItemForm.delivery_location_method" class="vendor-input"
                                                                    @change="if($el.value !== 'dropdown') clearEditItemLocation();">
                                                                <option value="dropdown">Location search</option>
                                                                <option value="coordinates">Coordinates</option>
                                                                <option value="gh_post">Ghana Post</option>
                                                            </select>
                                                        </div>
                                                        <div class="sm:col-span-2" x-show="editItemForm.delivery_location_method === 'dropdown'" x-cloak>
                                                            <label class="form-label text-emerald-700">Location</label>
                                                            <div class="loc-typeahead">
                                                                <div x-show="editItemSelectedLocation" class="loc-selected">
                                                                    <span x-text="editItemSelectedLocation?.display"></span>
                                                                    <button type="button" @click="clearEditItemLocation()" class="loc-clear-btn">×</button>
                                                                </div>
                                                                <div x-show="!editItemSelectedLocation" class="relative">
                                                                    <input x-model="editItemLocationQuery"
                                                                           @input.debounce.250ms="searchEditItemLocation()"
                                                                           type="text" class="vendor-input pr-10"
                                                                           placeholder="Type a town or city name...">
                                                                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                                                        <svg x-show="editItemLocationSearching" class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                                        <svg x-show="!editItemLocationSearching" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                                    </div>
                                                                    <div x-show="editItemLocationResults.length > 0" x-cloak class="loc-dropdown">
                                                                        <template x-for="loc in editItemLocationResults" :key="loc.id">
                                                                            <button type="button" @click="selectEditItemLocation(loc)" class="loc-option">
                                                                                <div class="loc-option-name" x-text="loc.name"></div>
                                                                                <div class="loc-option-sub" x-text="`${loc.district.name}, ${loc.region.name}`"></div>
                                                                            </button>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" x-model="editItemForm.delivery_region_id">
                                                            <input type="hidden" x-model="editItemForm.delivery_district_id">
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
                                                        <div x-show="editItemForm.delivery_location_method !== 'dropdown'" x-cloak>
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
                        <div class="sh-empty" x-show="(shipment?.items || []).length === 0 && !addingItem" x-cloak>
                            <div class="sh-empty-icon">
                                <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <h4>No items yet</h4>
                            <p>Add items to this shipment to get started</p>
                            <button x-show="canEditShipment" type="button" @click="openAddItem()"
                                    class="vendor-btn-primary py-2 px-4 text-xs mt-4 gap-1.5 mx-auto">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add First Item
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ---- Invoice Card (priority) ---- --}}
                <div class="sh-sidebar-card sh-invoice-card" x-show="currentInvoice" x-cloak>
                    <div class="sh-sidebar-head">
                        <div class="head-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        <h4>Invoice</h4>
                        <button x-show="invoiceHistory.length > 1" x-cloak type="button"
                                @click="document.getElementById('invoice-history').scrollIntoView({ behavior: 'smooth' })"
                                class="ml-auto text-xs font-semibold text-slate-500 hover:text-slate-800 transition flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            View History
                        </button>
                    </div>
                    <div class="sh-sidebar-body">
                        <template x-if="currentInvoice">
                            <div>
                                <div class="sh-invoice-total">
                                    <div class="sh-invoice-label">Total Amount</div>
                                    <div class="sh-invoice-amount" x-text="formatMoney(currentInvoice?.total_amount, currentInvoice?.currency || 'GHS')"></div>
                                </div>
                                <div class="sh-invoice-meta">
                                    <div class="sh-invoice-row">
                                        <span>Invoice #</span>
                                        <span class="font-semibold" x-text="currentInvoice?.invoice_number || '-'"></span>
                                    </div>
                                    <div class="sh-invoice-row">
                                        <span>Created</span>
                                        <span class="font-semibold" x-text="formatDateTime(currentInvoice?.created_at)"></span>
                                    </div>
                                </div>

                                {{-- Accept / Reject --}}
                                <div class="grid grid-cols-2 gap-2 mt-3" x-show="canRespondToInvoice" x-cloak>
                                    <button type="button" @click="acceptCurrentInvoice()"
                                            class="sh-accept-btn">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Accept
                                    </button>
                                    <button type="button" @click="rejectCurrentInvoice()"
                                            class="sh-reject-btn">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </div>

                                {{-- View invoice link --}}
                                <a x-show="currentInvoice?.id" x-cloak :href="`/vendor/invoices/${currentInvoice?.id}`"
                                   class="sh-invoice-link">
                                    View Full Invoice
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </div>
                        </template>
                        <template x-if="!currentInvoice">
                            <div class="text-center py-6">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-50">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                </div>
                                <p class="text-xs font-medium text-slate-500">No invoice yet</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">Invoice will appear after submission</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ---- Pickup Location ---- --}}
                <div class="sh-sidebar-card sh-pickup-card">
                    <div class="sh-sidebar-head">
                        <div class="head-icon blue">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h4>Pickup Location</h4>
                    </div>
                    <div class="sh-sidebar-body">
                        <div class="sh-info-row">
                            <span class="sh-info-label">Contact</span>
                            <span class="sh-info-value" x-text="shipment?.pickup?.contact_name || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Phone</span>
                            <span class="sh-info-value" x-text="shipment?.pickup?.contact_phone || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Region</span>
                            <span class="sh-info-value" x-text="shipment?.pickup?.location?.region || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">District</span>
                            <span class="sh-info-value" x-text="shipment?.pickup?.location?.district || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Town</span>
                            <span class="sh-info-value" x-text="shipment?.pickup?.location?.town || '-'"></span>
                        </div>
                        <div class="sh-info-row" x-show="shipment?.pickup?.location?.landmark">
                            <span class="sh-info-label">Landmark</span>
                            <span class="sh-info-value" x-text="shipment?.pickup?.location?.landmark"></span>
                        </div>
                        <div x-show="shipment?.pickup?.instructions" class="mt-2 rounded-lg bg-blue-50 border border-blue-100 px-3.5 py-3">
                            <div class="flex items-start gap-2">
                                <svg class="h-3.5 w-3.5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-xs text-blue-700" x-text="shipment?.pickup?.instructions"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---- Delivery Destination ---- --}}
                <div class="sh-sidebar-card sh-delivery-card" x-show="shipment?.destination_mode !== 'per_item'" x-cloak>
                    <div class="sh-sidebar-head">
                        <div class="head-icon green">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <h4>Delivery Destination</h4>
                    </div>
                    <div class="sh-sidebar-body">
                        <template x-if="shipment?.delivery">
                            <div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Recipient</span>
                                    <span class="sh-info-value" x-text="shipment?.delivery?.recipient_name || '-'"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Phone</span>
                                    <span class="sh-info-value" x-text="shipment?.delivery?.recipient_phone || '-'"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Region</span>
                                    <span class="sh-info-value" x-text="shipment?.delivery?.location?.region || '-'"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">District</span>
                                    <span class="sh-info-value" x-text="shipment?.delivery?.location?.district || '-'"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Town</span>
                                    <span class="sh-info-value" x-text="shipment?.delivery?.location?.town || '-'"></span>
                                </div>
                                <div class="sh-info-row" x-show="shipment?.delivery?.location?.landmark">
                                    <span class="sh-info-label">Landmark</span>
                                    <span class="sh-info-value" x-text="shipment?.delivery?.location?.landmark"></span>
                                </div>
                                <div x-show="shipment?.delivery?.instructions" class="mt-2 rounded-lg bg-emerald-50 border border-emerald-100 px-3.5 py-3">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-3.5 w-3.5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs text-emerald-700" x-text="shipment?.delivery?.instructions"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!shipment?.delivery">
                            <div class="text-center py-6">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                                    <svg class="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="text-xs font-medium text-slate-500">Per-item delivery mode</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">Each item has its own delivery address</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ---- Pickup Assignment ---- --}}
                <div class="sh-sidebar-card sh-assignment-card">
                    <div class="sh-sidebar-head">
                        <div class="head-icon indigo">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0m-8 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <h4>Pickup Assignment</h4>
                    </div>
                    <div class="sh-sidebar-body">
                        <template x-if="pickupAssignment">
                            <div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Status</span>
                                    <span class="vendor-badge text-[10px]" :class="'vendor-badge-' + pickupAssignment?.status" x-text="statusLabel(pickupAssignment?.status)"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Driver</span>
                                    <span class="sh-info-value" x-text="pickupAssignment?.driver_name || pickupAssignment?.driver?.name || '-'"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Phone</span>
                                    <span class="sh-info-value" x-text="pickupAssignment?.driver_phone || pickupAssignment?.driver?.phone || '-'"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Warehouse</span>
                                    <span class="sh-info-value" x-text="pickupAssignment?.target_warehouse?.name || '-'"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!pickupAssignment">
                            <div class="text-center py-6">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50">
                                    <svg class="h-6 w-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 114 0m-8 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                </div>
                                <p class="text-xs font-medium text-slate-500">No pickup assigned yet</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">Driver will be assigned after processing</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ---- Invoice History ---- --}}
                <div class="sh-sidebar-card sh-history-card" id="invoice-history" x-show="invoiceHistory.length > 1" x-cloak>
                    <div class="sh-sidebar-head">
                        <div class="head-icon purple">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h4>Invoice History</h4>
                        <span class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-slate-100 px-1.5 text-[10px] font-bold text-slate-600" x-text="invoiceHistory.length"></span>
                    </div>
                    <div class="sh-sidebar-body">
                        <div class="space-y-2.5">
                            <template x-for="(inv, i) in invoiceHistory" :key="inv.id || i">
                                <a :href="`/vendor/invoices/${inv.id}`" class="block rounded-lg border border-gray-100 bg-gray-50/50 p-3 hover:border-slate-300 hover:bg-white hover:shadow-sm transition no-underline">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-2 rounded-full flex-shrink-0"
                                                 :class="{
                                                     'bg-green-400': inv.status === 'accepted',
                                                     'bg-red-400': inv.status === 'rejected',
                                                     'bg-blue-400': inv.status === 'sent',
                                                     'bg-amber-400': inv.status === 'pending',
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
                                        <span x-text="inv.rejection_reason"></span>
                                    </div>
                                    <div x-show="inv.vendor_notes" class="mt-2 ml-4 rounded-md bg-slate-50 border border-slate-100 px-2.5 py-2 text-[11px] text-slate-500">
                                        <span x-text="inv.vendor_notes"></span>
                                    </div>
                                </a>
                            </template>
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
