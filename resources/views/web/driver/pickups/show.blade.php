@extends('web.layouts.driver')

@section('title', 'Pickup Details')

@section('content')
<div x-data="driverPickupShowPage()" data-pickup-id="{{ $pickupId }}">

    {{-- Toast stack --}}
    <div class="sh-toast-stack">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="sh-toast" :class="toast.type">
                <span x-text="toast.message"></span>
                <span class="sh-toast-close" @click="dismissToast(toast.id)">Close</span>
            </div>
        </template>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="inv-loading" style="padding:4rem 1rem;">
        <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Loading pickup...
    </div>

    <div x-show="!loading && pickup" x-cloak>

        {{-- Hero --}}
        <div class="sh-hero" :class="heroClass">
            <div class="sh-hero-inner">
                <div class="sh-hero-top">
                    <div class="sh-hero-left">
                        <a href="{{ route('web.driver.pickups.index') }}" class="sh-back">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            My Pickups
                        </a>
                        <div class="sh-title-text">Pickup</div>
                        <div class="sh-status-badge" x-text="statusLabel(pickup?.status)"></div>
                        <div class="sh-number" x-text="pickup?.shipment_number || `Pickup #${pickup?.id}`"></div>
                        <div class="sh-meta">
                            <div class="sh-meta-item" x-show="pickup?.shipment?.vendor_name">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <strong x-text="pickup?.shipment?.vendor_name"></strong>
                            </div>
                            <div class="sh-meta-item" x-show="pickup?.shipment?.pickup?.contact_name">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span x-text="pickup?.shipment?.pickup?.contact_name"></span>
                            </div>
                            <div class="sh-meta-item" x-show="pickup?.target_warehouse?.name">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                <span x-text="pickup?.target_warehouse?.name"></span>
                            </div>
                            <div class="sh-meta-item" x-show="pickup?.timeline?.assigned?.at">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="formatDateTime(pickup?.timeline?.assigned?.at)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status action section --}}
                <div class="sh-status-section">
                    <div class="sh-status-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="sh-status-content">
                        <div class="sh-status-title" x-text="heroMessage.title"></div>
                        <div class="sh-status-text" x-text="heroMessage.text"></div>
                    </div>
                    <div class="sh-status-action" x-show="canStartEnRoute">
                        <button type="button" @click="startEnRoute()" :disabled="actionLoading" class="sh-action-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            Start En Route
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress steps --}}
        <div class="sh-progress" x-show="pickup?.status !== 'cancelled'">
            <div class="sh-progress-header">
                <span class="sh-progress-title">Pickup Progress</span>
                <span class="sh-progress-status" x-text="statusLabel(pickup?.status)"></span>
            </div>
            {{-- Desktop --}}
            <div class="sh-steps">
                <template x-for="step in progressSteps" :key="step.key">
                    <div class="sh-step" :class="step.state">
                        <div class="sh-step-dot">
                            <template x-if="step.state === 'sh-step-done'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step.state !== 'sh-step-done'">
                                <span x-text="step.num"></span>
                            </template>
                        </div>
                        <div class="sh-step-text" x-text="step.label"></div>
                    </div>
                </template>
            </div>
            {{-- Mobile --}}
            <div class="sh-steps-mobile">
                <template x-for="step in progressSteps" :key="step.key">
                    <div class="sh-step" :class="step.state">
                        <div class="sh-step-dot">
                            <template x-if="step.state === 'sh-step-done'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step.state !== 'sh-step-done'">
                                <span x-text="step.num"></span>
                            </template>
                        </div>
                        <div class="sh-step-text" x-text="step.label"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Alert --}}
        <div x-show="alert" x-cloak class="my-4 rounded-xl border px-4 py-3 text-sm"
             :class="{
                'border-emerald-300/30 bg-emerald-50 text-emerald-800': alert?.type === 'success',
                'border-rose-300/30 bg-rose-50 text-rose-800': alert?.type === 'error'
             }">
            <span x-text="alert?.message"></span>
        </div>

        {{-- Validation errors --}}
        <div x-show="validationErrors.length" x-cloak class="my-4 rounded-xl border border-rose-300/30 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc pl-5">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>

        {{-- Main grid --}}
        <div class="sh-grid mt-4">
            {{-- Left: Items card --}}
            <div class="sh-card sh-items-card">
                <div class="sh-card-head">
                    <div class="sh-card-icon green">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h3>Pickup Items</h3>
                    <span class="sh-card-count" x-text="`${(pickup?.shipment?.items || []).length} items`"></span>
                </div>

                <div x-show="(pickup?.shipment?.items || []).length === 0" class="sh-card-body" style="text-align:center;color:#94a3b8;padding:2rem;">
                    No shipment items found.
                </div>

                <template x-for="item in (pickup?.shipment?.items || [])" :key="item.id">
                    <div class="sh-item">
                        <div class="sh-item-status-dot" :class="statusColor(item.status)" style="margin-top:0.4rem;flex-shrink:0;"></div>
                        <div class="sh-item-main">
                            <div class="sh-item-header">
                                <div class="sh-item-name" x-text="item.description"></div>
                                <div style="display:flex;gap:0.35rem;flex-wrap:wrap;">
                                    <span class="sh-item-qty expected">Exp: <span x-text="item.quantity"></span></span>
                                    <span class="sh-item-qty confirmed" x-show="item.pickup_confirmation">
                                        Conf: <span x-text="item.pickup_confirmation?.confirmed_quantity ?? '-'"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="sh-item-tracking" x-show="item.tracking_code" x-text="item.tracking_code"></div>

                            {{-- Vendor photos --}}
                            <div class="sh-item-images" x-show="(item.images || []).length > 0">
                                <template x-for="img in (item.images || [])" :key="img.id">
                                    <div class="sh-item-image">
                                        <img :src="img.url" :alt="img.original_name">
                                        <div class="sh-item-image-label" x-text="img.original_name"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Pickup confirmation block --}}
                            <div class="sh-confirmation-block" x-show="item.pickup_confirmation">
                                <div class="sh-confirmation-block-title">Pickup Confirmation</div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Expected</span>
                                    <span class="sh-info-value" x-text="item.pickup_confirmation?.expected_quantity"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Confirmed</span>
                                    <span class="sh-info-value" x-text="item.pickup_confirmation?.confirmed_quantity"></span>
                                </div>
                                <div class="sh-info-row" x-show="item.pickup_confirmation?.missing_quantity > 0">
                                    <span class="sh-info-label">Missing</span>
                                    <span class="sh-info-value" style="color:#dc2626;" x-text="item.pickup_confirmation?.missing_quantity"></span>
                                </div>
                                <div class="sh-info-row" x-show="item.pickup_confirmation?.extra_quantity > 0">
                                    <span class="sh-info-label">Extra</span>
                                    <span class="sh-info-value" x-text="item.pickup_confirmation?.extra_quantity"></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">Confirmed At</span>
                                    <span class="sh-info-value" x-text="formatDateTime(item.pickup_confirmation?.confirmed_at)"></span>
                                </div>
                                {{-- Confirmation photos --}}
                                <div class="sh-item-images" x-show="(item.pickup_confirmation?.photos || []).length > 0" style="margin-top:0.5rem;">
                                    <template x-for="photo in (item.pickup_confirmation?.photos || [])" :key="photo.id">
                                        <div class="sh-item-image">
                                            <img :src="photo.url" :alt="photo.original_name">
                                            <div class="sh-item-image-label" x-text="photo.original_name"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Confirm item form --}}
                            <div class="sh-item-confirm-form" x-show="canConfirmItems">
                                <div>
                                    <label>Confirmed Qty</label>
                                    <input :value="itemForms[item.id]?.confirmed_quantity ?? item.quantity"
                                           @input="itemForms[item.id].confirmed_quantity = $event.target.value"
                                           type="number" min="0">
                                </div>
                                <div>
                                    <label>Add Photos</label>
                                    <input type="file" multiple accept="image/*" @change="onItemPhotosSelected(item.id, $event)">
                                </div>
                                <div class="full">
                                    <label>Notes</label>
                                    <textarea :value="itemForms[item.id]?.notes || ''"
                                              @input="itemForms[item.id].notes = $event.target.value"
                                              rows="2"></textarea>
                                </div>
                                {{-- Remove existing photos --}}
                                <div class="full" x-show="(item.pickup_confirmation?.photos || []).length > 0">
                                    <label>Remove Existing Photos</label>
                                    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;margin-top:0.25rem;">
                                        <template x-for="photo in (item.pickup_confirmation?.photos || [])" :key="photo.id">
                                            <label style="display:inline-flex;align-items:center;gap:0.4rem;font-size:0.75rem;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.3rem 0.6rem;cursor:pointer;">
                                                <input type="checkbox" :value="photo.id" x-model="itemForms[item.id].remove_photo_ids">
                                                <span x-text="photo.original_name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                                <div class="full">
                                    <button type="button" @click="confirmItem(item)" :disabled="isItemActionLoading(item.id)" class="sh-item-confirm-btn">
                                        <span x-show="!isItemActionLoading(item.id)">Save Confirmation</span>
                                        <span x-show="isItemActionLoading(item.id)" x-cloak>Saving...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Right sidebar --}}
            <div class="sh-sidebar-stack">

                {{-- Pickup location --}}
                <div class="sh-sidebar-card">
                    <div class="sh-card-head">
                        <div class="sh-card-icon blue">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3>Pickup Location</h3>
                    </div>
                    <div class="sh-card-body">
                        <div class="sh-info-row">
                            <span class="sh-info-label">Contact</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.contact_name || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Phone</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.contact_phone || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Region</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.location?.region || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">District</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.location?.district || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Town</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.location?.town || '-'"></span>
                        </div>
                        <div class="sh-info-row" x-show="pickup?.shipment?.pickup?.location?.landmark">
                            <span class="sh-info-label">Landmark</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.location?.landmark"></span>
                        </div>
                        <div class="sh-info-row" x-show="pickup?.shipment?.pickup?.instructions">
                            <span class="sh-info-label">Instructions</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.instructions"></span>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="sh-sidebar-card">
                    <div class="sh-card-head">
                        <div class="sh-card-icon slate">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3>Timeline</h3>
                    </div>
                    <div class="sh-card-body">
                        <div class="sh-info-row">
                            <span class="sh-info-label">Assigned</span>
                            <span class="sh-info-value" x-text="formatDateTime(pickup?.timeline?.assigned?.at) || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">En Route</span>
                            <span class="sh-info-value" x-text="formatDateTime(pickup?.timeline?.en_route?.at) || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Arrived</span>
                            <span class="sh-info-value" x-text="formatDateTime(pickup?.timeline?.arrived_pickup?.at) || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Picked Up</span>
                            <span class="sh-info-value" x-text="formatDateTime(pickup?.timeline?.picked_up?.at) || '-'"></span>
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Completed</span>
                            <span class="sh-info-value" x-text="formatDateTime(pickup?.timeline?.completed?.at) || '-'"></span>
                        </div>
                        <div class="sh-info-row" x-show="pickup?.cancellation_reason">
                            <span class="sh-info-label">Cancel Reason</span>
                            <span class="sh-info-value" style="color:#dc2626;" x-text="pickup?.cancellation_reason"></span>
                        </div>
                    </div>
                </div>

                {{-- Arrive at pickup location --}}
                <div class="sh-sidebar-card" x-show="canArrive">
                    <div class="sh-card-head">
                        <div class="sh-card-icon amber">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3>Arrive at Location</h3>
                    </div>
                    <div class="sh-card-body">
                        <form @submit.prevent="arriveAtPickup()" style="display:grid;gap:0.65rem;">
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:0.25rem;">Latitude</label>
                                <input x-model="arriveForm.latitude" type="number" step="any"
                                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:0.45rem 0.75rem;font-size:0.82rem;color:#1e293b;outline:none;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:0.25rem;">Longitude</label>
                                <input x-model="arriveForm.longitude" type="number" step="any"
                                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:0.45rem 0.75rem;font-size:0.82rem;color:#1e293b;outline:none;box-sizing:border-box;">
                            </div>
                            <button type="submit" :disabled="actionLoading" class="sh-card-action-btn" style="background:#fef3c7;color:#92400e;border-color:#fde68a;">
                                Mark Arrived
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Finalize pickup --}}
                <div class="sh-sidebar-card" x-show="canFinalize">
                    <div class="sh-card-head">
                        <div class="sh-card-icon green">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3>Finalize Pickup</h3>
                    </div>
                    <div class="sh-card-body">
                        <form @submit.prevent="finalizePickup()" style="display:grid;gap:0.65rem;">
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:0.25rem;">Latitude (optional)</label>
                                <input x-model="finalizeForm.latitude" type="number" step="any"
                                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:0.45rem 0.75rem;font-size:0.82rem;color:#1e293b;outline:none;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:0.25rem;">Longitude (optional)</label>
                                <input x-model="finalizeForm.longitude" type="number" step="any"
                                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:0.45rem 0.75rem;font-size:0.82rem;color:#1e293b;outline:none;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:0.25rem;">Notes (optional)</label>
                                <textarea x-model="finalizeForm.notes" rows="2"
                                          style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:0.45rem 0.75rem;font-size:0.82rem;color:#1e293b;outline:none;box-sizing:border-box;resize:vertical;"></textarea>
                            </div>
                            <button type="submit" :disabled="actionLoading" class="sh-card-action-btn">
                                Finalize Pickup
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
