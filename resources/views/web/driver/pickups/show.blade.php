@extends('web.layouts.driver')

@section('title', 'Pickup Details')

@section('content')
<div x-data="driverPickupShowPage()" data-pickup-id="{{ $pickupId }}">


    {{-- Confirm Item Modal --}}
    <div x-show="confirmItemModalOpen" x-cloak style="position:fixed;inset:0;z-index:9998;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1rem;"
             @@click.self="closeConfirmItemModal()">
            <div style="background:#fff;border-radius:24px;width:100%;max-width:480px;max-height:92vh;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.25);display:flex;flex-direction:column;">

                {{-- Header --}}
                <div style="display:flex;align-items:center;gap:0.875rem;padding:1.5rem 1.5rem 1.25rem;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(16,185,129,0.35);">
                        <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:1.05rem;font-weight:800;color:#0f172a;margin:0 0 0.1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            x-text="confirmItemModalItem?.description || 'Item'"></h3>
                        <p style="font-size:0.8rem;color:#64748b;margin:0;font-weight:500;">Confirm Item</p>
                    </div>
                    <button type="button" @@click="closeConfirmItemModal()" style="width:34px;height:34px;border-radius:10px;background:#f1f5f9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="height:1px;background:#f1f5f9;flex-shrink:0;"></div>

                {{-- Body --}}
                <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1.25rem;">

                    {{-- Confirmed Qty --}}
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#374151;margin-bottom:0.4rem;letter-spacing:0.02em;text-transform:uppercase;">Confirmed Quantity</label>
                        <input type="number" min="0"
                               :value="confirmItemModalItem ? (itemForms[confirmItemModalItem.id]?.confirmed_quantity ?? confirmItemModalItem.quantity) : ''"
                               @input="if(confirmItemModalItem) itemForms[confirmItemModalItem.id].confirmed_quantity = $event.target.value"
                               style="width:100%;border:2px solid #e2e8f0;border-radius:12px;padding:0.75rem 1rem;font-size:1.15rem;font-weight:700;color:#0f172a;box-sizing:border-box;outline:none;transition:border-color 0.2s;"
                               onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>

                    {{-- Existing Photos --}}
                    <div x-show="(confirmItemModalItem?.pickup_confirmation?.photos || []).length > 0">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                            <label style="font-size:0.75rem;font-weight:700;color:#374151;letter-spacing:0.02em;text-transform:uppercase;">Existing Photos</label>
                            <span style="font-size:0.68rem;color:#64748b;font-weight:600;">Tap photo to preview. Use button below each photo to remove.</span>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;">
                            <template x-for="(photo, photoIdx) in (confirmItemModalItem?.pickup_confirmation?.photos || [])" :key="photo.id">
                                <div style="display:flex;flex-direction:column;gap:0.35rem;">
                                    <div style="position:relative;border-radius:10px;overflow:hidden;aspect-ratio:1;background:#f1f5f9;box-shadow:0 1px 3px rgba(15,23,42,0.08);">
                                        <img :src="photo.url" :alt="photo.original_name"
                                             style="width:100%;height:100%;object-fit:cover;display:block;cursor:pointer;transition:opacity 0.2s;"
                                             :style="(itemForms[confirmItemModalItem?.id]?.remove_photo_ids || []).includes(photo.id) ? 'opacity:0.35;' : 'opacity:1;'"
                                             @@click="openLightboxAt(confirmItemModalItem.pickup_confirmation.photos, photoIdx)">
                                    </div>
                                    <template x-if="!(itemForms[confirmItemModalItem?.id]?.remove_photo_ids || []).includes(photo.id)">
                                        <button type="button"
                                                @@click.stop="itemForms[confirmItemModalItem.id].remove_photo_ids.push(photo.id)"
                                                style="align-self:flex-end;padding:0.25rem 0.55rem;border-radius:999px;border:1px solid #fecaca;background:#fff1f2;color:#b91c1c;font-size:0.64rem;font-weight:700;line-height:1;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 1px 2px rgba(15,23,42,0.08);">
                                            Remove
                                        </button>
                                    </template>
                                    <template x-if="(itemForms[confirmItemModalItem?.id]?.remove_photo_ids || []).includes(photo.id)">
                                        <button type="button"
                                                @@click.stop="itemForms[confirmItemModalItem.id].remove_photo_ids.splice(itemForms[confirmItemModalItem.id].remove_photo_ids.indexOf(photo.id), 1)"
                                                style="align-self:flex-end;padding:0.25rem 0.55rem;border-radius:999px;border:1px solid #6ee7b7;background:#ecfdf5;color:#047857;font-size:0.64rem;font-weight:700;line-height:1;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 1px 2px rgba(15,23,42,0.08);">
                                            Undo
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    {{-- Add New Photos --}}
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#374151;margin-bottom:0.4rem;letter-spacing:0.02em;text-transform:uppercase;">Add Photos</label>
                        <label style="display:flex;align-items:center;gap:0.75rem;border:2px dashed #e2e8f0;border-radius:12px;padding:0.8rem 1rem;cursor:pointer;transition:border-color 0.2s,background 0.2s;background:#fafafa;"
                               onfocuswithin="this.style.borderColor='#10b981';this.style.background='#f0fdf4'">
                            <div style="width:36px;height:36px;border-radius:9px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" fill="none" stroke="#10b981" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.85rem;font-weight:600;color:#374151;"
                                     x-text="(itemForms[confirmItemModalItem?.id]?.photos || []).length ? `${(itemForms[confirmItemModalItem?.id]?.photos || []).length} photo(s) selected` : 'Tap to add photos'"></div>
                                <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">JPG, PNG, WEBP</div>
                            </div>
                            <input type="file" multiple accept="image/*"
                                   @change="if(confirmItemModalItem) onItemPhotosSelected(confirmItemModalItem.id, $event)"
                                   style="display:none;">
                        </label>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#374151;margin-bottom:0.4rem;letter-spacing:0.02em;text-transform:uppercase;">Notes <span style="color:#94a3b8;font-weight:400;text-transform:none;">(optional)</span></label>
                        <textarea rows="2"
                                  :value="confirmItemModalItem ? (itemForms[confirmItemModalItem.id]?.notes || '') : ''"
                                  @input="if(confirmItemModalItem) itemForms[confirmItemModalItem.id].notes = $event.target.value"
                                  placeholder="Any notes about this item..."
                                  style="width:100%;border:2px solid #e2e8f0;border-radius:12px;padding:0.7rem 1rem;font-size:0.875rem;color:#0f172a;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color 0.2s;"
                                  onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                    </div>

                </div>

                {{-- Footer --}}
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.55rem;padding:0.9rem 1.5rem 1.25rem;border-top:1px solid #f1f5f9;flex-shrink:0;">
                    <button type="button" @@click="closeConfirmItemModal()"
                            style="padding:0.62rem 1.15rem;border-radius:12px;background:#f8fafc;border:1.5px solid #e2e8f0;font-size:0.86rem;font-weight:700;color:#64748b;cursor:pointer;white-space:nowrap;user-select:none;">
                        Cancel
                    </button>
                    <button type="button"
                            @@click="confirmItemModalItem && !isItemActionLoading(confirmItemModalItem?.id) && confirmItem(confirmItemModalItem)"
                            :disabled="!confirmItemModalItem || isItemActionLoading(confirmItemModalItem?.id)"
                            style="padding:0.62rem 1.3rem;border-radius:12px;border:none;font-size:0.86rem;font-weight:700;color:#fff;white-space:nowrap;user-select:none;display:flex;align-items:center;gap:0.42rem;background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 3px 12px rgba(16,185,129,0.3);"
                            :class="(!confirmItemModalItem || isItemActionLoading(confirmItemModalItem?.id)) ? 'opacity-60 cursor-not-allowed' : ''">
                        <svg x-show="confirmItemModalItem && isItemActionLoading(confirmItemModalItem?.id)" width="13" height="13" class="animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <svg x-show="!confirmItemModalItem || !isItemActionLoading(confirmItemModalItem?.id)" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="(confirmItemModalItem && isItemActionLoading(confirmItemModalItem?.id)) ? 'Saving...' : 'Save Confirmation'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Finalize Pickup Modal --}}
    <div x-show="showFinalizeModal" x-cloak
         style="position:fixed;inset:0;z-index:9999;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1rem;"
             @@click.self="showFinalizeModal = false">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:480px;box-shadow:0 24px 64px rgba(0,0,0,0.25);display:flex;flex-direction:column;">

            {{-- Header --}}
            <div style="display:flex;align-items:center;gap:0.875rem;padding:1.5rem 1.5rem 1.25rem;">
                <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(16,185,129,0.35);">
                    <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <h3 style="font-size:1.05rem;font-weight:800;color:#0f172a;margin:0 0 0.1rem;">Finalize Pickup</h3>
                    <p style="font-size:0.8rem;color:#64748b;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">Confirm to complete this pickup.</p>
                </div>
                <button type="button" @@click="showFinalizeModal = false" style="width:34px;height:34px;border-radius:10px;background:#f1f5f9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="height:1px;background:#f1f5f9;flex-shrink:0;"></div>

            {{-- Notes --}}
            <div style="padding:1.25rem 1.5rem 1rem;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#374151;margin-bottom:0.4rem;letter-spacing:0.02em;text-transform:uppercase;">Notes <span style="color:#94a3b8;font-weight:400;text-transform:none;">(optional)</span></label>
                <textarea x-model="finalizeForm.notes" rows="3"
                          placeholder="Any notes about this pickup..."
                          style="width:100%;border:2px solid #e2e8f0;border-radius:12px;padding:0.7rem 1rem;font-size:0.875rem;color:#0f172a;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color 0.2s;"
                          onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
            </div>

            {{-- Actions --}}
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.55rem;padding:0.9rem 1.5rem 1.25rem;border-top:1px solid #f1f5f9;flex-shrink:0;">
                <button type="button" @@click="showFinalizeModal = false"
                        style="padding:0.62rem 1.15rem;border-radius:12px;background:#f8fafc;border:1.5px solid #e2e8f0;font-size:0.86rem;font-weight:700;color:#64748b;cursor:pointer;white-space:nowrap;user-select:none;">
                    Cancel
                </button>
                <button type="button" @@click="finalizePickup()" :disabled="actionLoading"
                        style="padding:0.62rem 1.3rem;border-radius:12px;border:none;font-size:0.86rem;font-weight:700;color:#fff;white-space:nowrap;user-select:none;display:flex;align-items:center;gap:0.42rem;background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 3px 12px rgba(16,185,129,0.3);"
                        :class="actionLoading ? 'opacity-60 cursor-not-allowed' : ''">
                    <svg x-show="actionLoading" width="13" height="13" class="animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <svg x-show="!actionLoading" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Confirm &amp; Finalize</span>
                </button>
            </div>
        </div>
        </div>
    </div>

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
        <div class="sh-hero" :class="progressSteps.isCancelled ? 'no-progress' : ''">
            <div class="sh-hero-inner">

                {{-- Top row: back + status badge --}}
                <div class="sh-hero-top">
                    <div class="sh-hero-left">
                        <a href="{{ route('web.driver.pickups.index') }}" class="sh-back" title="Back to My Pickups">
                            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </a>
                        <span class="sh-title-text">Pickup</span>
                    </div>
                    <span class="sh-status-badge" x-text="statusLabel(pickup?.status)"></span>
                </div>

                {{-- Pickup number --}}
                <h1 class="sh-number" x-text="pickup?.shipment_number || `Pickup #${pickup?.id}`"></h1>

                {{-- Meta row --}}
                <div class="sh-meta">
                    <span class="sh-meta-item" x-show="pickup?.shipment?.vendor_name">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Vendor: <strong x-text="pickup?.shipment?.vendor_name"></strong>
                    </span>
                    <span class="sh-meta-item" x-show="pickup?.shipment?.pickup?.contact_name">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Contact: <span x-text="pickup?.shipment?.pickup?.contact_name"></span>
                    </span>
                    <span class="sh-meta-item" x-show="pickup?.target_warehouse?.name">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        Warehouse: <span x-text="pickup?.target_warehouse?.name"></span>
                    </span>
                    <span class="sh-meta-item" x-show="pickup?.timeline?.assigned?.at">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Assigned: <span x-text="formatDateTime(pickup?.timeline?.assigned?.at)"></span>
                    </span>
                </div>

                {{-- Status action section --}}
                <div class="sh-status-section">
                    <div class="sh-status-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="sh-status-content">
                        <h4 class="sh-status-title" x-text="heroMessage.title"></h4>
                        <p class="sh-status-text" x-text="heroMessage.text"></p>
                    </div>
                    <div class="sh-status-action" x-show="canStartEnRoute">
                        <button type="button" @click="startEnRoute()" :disabled="actionLoading" class="sh-action-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            Start En Route
                        </button>
                    </div>
                    <div class="sh-status-action" x-show="canArrive">
                        <button type="button" @click="arriveAtPickup()" :disabled="actionLoading" class="sh-action-btn sh-action-btn-amber">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span x-show="!actionLoading">Mark Arrived</span>
                            <span x-show="actionLoading" x-cloak>Getting location...</span>
                        </button>
                    </div>
                    <div class="sh-status-action" x-show="canFinalize">
                        <button type="button" @click="showFinalizeModal = true" :disabled="actionLoading" class="sh-action-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Finalize Pickup
                        </button>
                    </div>
                </div>
                {{-- Hint: items still pending confirmation --}}
                <div x-show="!canFinalize && ['arrived','picking_up'].includes(pickup?.status || '')" x-cloak style="margin-top:0.75rem;">
                    <div style="display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;gap:0.5rem;padding:0.5rem 0.875rem;border-radius:10px;background:rgba(251,191,36,0.18);border:1.5px solid rgba(251,191,36,0.55);">
                        <svg width="16" height="16" style="flex-shrink:0;" fill="none" stroke="#fbbf24" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span style="font-size:0.8rem;font-weight:700;color:#fbbf24;line-height:1.2;" x-text="`${pendingItemsCount} item(s) still need confirmation before you can finalize`"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress steps (fused below hero) --}}
        <div class="sh-progress" x-show="!progressSteps.isCancelled">
            <div class="sh-progress-header">
                <h3 class="sh-progress-title">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    Pickup Progress
                </h3>
                <span class="sh-progress-status" x-text="`Step ${progressSteps.currentIdx + 1} of ${progressSteps.total}`"></span>
            </div>

            {{-- Desktop: horizontal --}}
            <div class="sh-steps" :data-progress="progressSteps.currentIdx">
                <template x-for="(step, i) in progressSteps.steps" :key="step.label">
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
                        <div class="sh-step-label-group">
                            <span class="sh-step-text" x-text="step.label"></span>
                            <span class="sh-step-time" x-show="step.at" x-text="formatDateTime(step.at)"></span>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Mobile: vertical --}}
            <div class="sh-steps-mobile" :data-progress="progressSteps.currentIdx">
                <template x-for="(step, i) in progressSteps.steps" :key="'m-' + step.label">
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
                        <div class="sh-step-label-group-m">
                            <span class="sh-step-text-m" x-text="step.label"></span>
                            <span class="sh-step-time-m" x-show="step.at" x-text="formatDateTime(step.at)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Main grid --}}
        <div class="sh-grid">
            {{-- Left: Items --}}
            <div class="sh-items-card">
                {{-- Section header --}}
                <div style="display:flex;align-items:center;gap:0.625rem;margin-bottom:0.75rem;">
                    <div class="sh-card-icon green" style="width:36px;height:36px;border-radius:10px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h3 style="font-size:0.95rem;font-weight:700;color:#1f2937;margin:0;flex:1;">Pickup Items</h3>
                    <span style="font-size:0.72rem;font-weight:600;color:#64748b;" x-text="`${(pickup?.shipment?.items || []).length} items`"></span>
                </div>

                <div x-show="(pickup?.shipment?.items || []).length === 0" style="text-align:center;color:#94a3b8;padding:2rem;background:#fff;border-radius:16px;border:1px solid #f3f4f6;">
                    No shipment items found.
                </div>

                <template x-for="item in (pickup?.shipment?.items || [])" :key="item.id">
                    <div class="inv-card" :class="item.pickup_confirmation ? 'inv-card-green' : 'inv-card-amber'" style="margin-bottom:0.625rem;">
                        <div class="inv-card-inner">
                            {{-- Icon --}}
                            <div class="inv-icon-wrap" :class="item.pickup_confirmation ? 'green' : 'amber'">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>

                            {{-- Main info --}}
                            <div class="inv-main">
                                <div style="display:flex;flex-direction:column;gap:0.2rem;width:100%;">

                                    {{-- Row 1: Name --}}
                                    <div style="font-weight:800;color:#0f172a;font-size:1.05rem;line-height:1.25;" x-text="item.description"></div>

                                    {{-- Row 2: Image stack --}}
                                    <template x-if="(item.images || []).length > 0">
                                        <div style="display:flex;flex-direction:row;align-items:center;cursor:pointer;margin-top:0.1rem;"
                                             @@click="openLightboxAt(item.images || [], 0)">
                                            <template x-for="(img, imgIdx) in (item.images || []).slice(0, 3)" :key="img.id">
                                                <div :style="'width:36px;height:36px;border-radius:7px;overflow:hidden;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.18);flex-shrink:0;' + (imgIdx > 0 ? 'margin-left:-8px;' : '')">
                                                    <img :src="img.url" :alt="img.original_name" style="width:100%;height:100%;object-fit:cover;display:block;">
                                                </div>
                                            </template>
                                            <template x-if="(item.images || []).length > 3">
                                                <div style="width:26px;height:36px;margin-left:-8px;border-radius:7px;background:rgba(15,23,42,0.75);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.18);">
                                                    <span style="font-size:0.6rem;font-weight:700;color:#fff;" x-text="'+' + ((item.images || []).length - 3)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Row 2: Item metadata --}}
                                    <div style="display:flex;align-items:center;gap:0.35rem;flex-wrap:wrap;">
                                        {{-- Tracking code --}}
                                        <span x-show="item.tracking_code"
                                              style="display:inline-flex;align-items:center;font-size:0.67rem;font-weight:600;color:#6366f1;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);padding:0.2rem 0.45rem;border-radius:6px;font-family:ui-monospace,monospace;letter-spacing:0.03em;"
                                              x-text="item.tracking_code"></span>
                                        {{-- Missing badge --}}
                                        <div x-show="item.pickup_confirmation?.missing_quantity > 0">
                                            <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.72rem;font-weight:700;color:#b91c1c;background:#fee2e2;border:1px solid #fca5a5;padding:0.22rem 0.5rem;border-radius:6px;">
                                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                                <span x-text="item.pickup_confirmation?.missing_quantity"></span> missing
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="inv-actions" style="align-items:center;gap:0.4rem;flex-shrink:0;">
                                {{-- Expected box (always shown) --}}
                                <div class="pickup-qty-tile pickup-qty-tile--expected">
                                    <div class="pickup-qty-tile__value" x-text="item.quantity"></div>
                                    <div class="pickup-qty-tile__label">Expected</div>
                                </div>
                                {{-- Confirmed box (only when confirmed) --}}
                                <template x-if="item.pickup_confirmation">
                                    <div class="pickup-qty-tile pickup-qty-tile--confirmed">
                                        <div class="pickup-qty-tile__value" x-text="item.pickup_confirmation.confirmed_quantity"></div>
                                        <div class="pickup-qty-tile__label">Confirmed</div>
                                    </div>
                                </template>
                                {{-- Separator --}}
                                <div x-show="canConfirmItems" style="width:1px;height:42px;background:rgba(0,0,0,0.1);flex-shrink:0;border-radius:99px;margin:0 0.05rem;"></div>
                                {{-- Confirm/Edit box â€” outer div for x-show toggle, inner div for display:flex styling --}}
                                <div x-show="canConfirmItems" style="flex-shrink:0;">
                                    <div @@click="openConfirmItemModal(item)"
                                         class="pickup-qty-tile pickup-action-tile"
                                         :class="item.pickup_confirmation ? 'pickup-qty-tile--edit' : 'pickup-qty-tile--confirm'">
                                        <template x-if="item.pickup_confirmation">
                                            <svg class="pickup-qty-tile__icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </template>
                                        <template x-if="!item.pickup_confirmation">
                                            <svg class="pickup-qty-tile__icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                        <div class="pickup-qty-tile__label" x-text="item.pickup_confirmation ? 'Edit' : 'Confirm'"></div>
                                    </div>
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
                        <div class="sh-info-row" x-show="pickup?.shipment?.pickup?.contact_phone">
                            <span class="sh-info-label">Phone</span>
                            <a class="sh-call-btn" :href="'tel:' + pickup?.shipment?.pickup?.contact_phone">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span x-text="pickup?.shipment?.pickup?.contact_phone"></span>
                            </a>
                        </div>
                        <div class="sh-info-row" x-show="pickup?.shipment?.pickup?.location?.region || pickup?.shipment?.pickup?.location?.district || pickup?.shipment?.pickup?.location?.town">
                            <span class="sh-info-label">Location</span>
                            <span class="sh-info-value" x-text="[pickup?.shipment?.pickup?.location?.region, pickup?.shipment?.pickup?.location?.district, pickup?.shipment?.pickup?.location?.town].filter(Boolean).join(', ')"></span>
                        </div>
                        <div class="sh-info-row" x-show="pickup?.shipment?.pickup?.location?.landmark">
                            <span class="sh-info-label">Landmark</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.location?.landmark"></span>
                        </div>
                        <div class="sh-info-row" x-show="pickup?.shipment?.pickup?.instructions">
                            <span class="sh-info-label">Instructions</span>
                            <span class="sh-info-value" x-text="pickup?.shipment?.pickup?.instructions"></span>
                        </div>
                        <div x-show="pickup?.shipment?.pickup?.location?.latitude && pickup?.shipment?.pickup?.location?.longitude" class="sh-map-btn-row">
                            <a class="sh-map-btn"
                               :href="'https://www.google.com/maps?q=' + pickup?.shipment?.pickup?.location?.latitude + ',' + pickup?.shipment?.pickup?.location?.longitude"
                               target="_blank" rel="noopener noreferrer">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Target Warehouse --}}
                <div class="sh-sidebar-card" x-show="pickup?.target_warehouse">
                    <div class="sh-card-head">
                        <div class="sh-card-icon amber">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        </div>
                        <h3>Deliver To</h3>
                    </div>
                    <div class="sh-card-body">
                        <div class="sh-warehouse-banner">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Drop off all items at this warehouse after pickup
                        </div>
                        <div class="sh-info-row">
                            <span class="sh-info-label">Warehouse</span>
                            <span class="sh-info-value" x-text="pickup?.target_warehouse?.name"></span>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection

