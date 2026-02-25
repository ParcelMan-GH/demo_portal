@extends('web.layouts.driver')

@section('title', 'Delivery Run')

@section('content')
<div x-data="driverDeliveryShowPage()" data-run-id="{{ $runId }}">

    {{-- Toast stack --}}
    <div class="sh-toast-stack">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="sh-toast" :class="toast.type">
                <span x-text="toast.message"></span>
                <span class="sh-toast-close" @click="dismissToast(toast.id)">Close</span>
            </div>
        </template>
    </div>

    {{-- Confirm dialog --}}
    <div class="sh-confirm-overlay" x-show="confirmDialog.open" x-cloak>
        <div class="sh-confirm-box">
            <h3 x-text="confirmDialog.title || 'Confirm'"></h3>
            <p x-text="confirmDialog.message"></p>
            <div class="sh-confirm-btns">
                <button type="button" @click="confirmNo()" class="sh-confirm-cancel">Cancel</button>
                <button type="button" @click="confirmYes()" class="sh-confirm-ok">Confirm</button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="inv-loading" style="padding:4rem 1rem;">
        <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Loading delivery run...
    </div>

    <div x-show="!loading && run" x-cloak>

        {{-- Hero --}}
        <div class="sh-hero" :class="heroClass">
            <div class="sh-hero-inner">
                <div class="sh-hero-top">
                    <div class="sh-hero-left">
                        <a href="{{ route('web.driver.deliveries.index') }}" class="sh-back">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            My Deliveries
                        </a>
                        <div class="sh-title-text">Delivery Run</div>
                        <div class="sh-status-badge" x-text="statusLabel(run?.status)"></div>
                        <div class="sh-number" x-text="run?.run_number || `Delivery Run #${run?.id}`"></div>
                        <div class="sh-meta">
                            <div class="sh-meta-item" x-show="run?.warehouse?.name">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                <span x-text="run?.warehouse?.name"></span>
                            </div>
                            <div class="sh-meta-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <span x-text="`${(run?.stops || []).length} stop${(run?.stops || []).length !== 1 ? 's' : ''}`"></span>
                            </div>
                            <div class="sh-meta-item" x-show="run?.assigned_at">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="formatDateTime(run?.assigned_at)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status section --}}
                <div class="sh-status-section">
                    <div class="sh-status-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="sh-status-content">
                        <div class="sh-status-title" x-text="heroMessage.title"></div>
                        <div class="sh-status-text" x-text="heroMessage.text"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress steps --}}
        <div class="sh-progress" x-show="run?.status !== 'cancelled'">
            <div class="sh-progress-header">
                <span class="sh-progress-title">Delivery Progress</span>
                <span class="sh-progress-status" x-text="statusLabel(run?.status)"></span>
            </div>
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

        {{-- Stops card --}}
        <div class="sh-grid sh-grid-full mt-4">
            <div class="sh-card sh-items-card">
                <div class="sh-card-head">
                    <div class="sh-card-icon amber">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3>Delivery Stops</h3>
                    <span class="sh-card-count">
                        <span x-text="(run?.stops || []).filter(s => s.status === 'delivered').length"></span>
                        / <span x-text="(run?.stops || []).length"></span> delivered
                    </span>
                </div>

                <div x-show="(run?.stops || []).length === 0" style="padding:2rem;text-align:center;color:#94a3b8;">
                    No stops in this delivery run.
                </div>

                <template x-for="stop in (run?.stops || [])" :key="stop.id">
                    <div class="sh-item" style="flex-direction:column;align-items:stretch;">
                        {{-- Stop header --}}
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.5rem;">
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.2rem;">
                                    <div class="sh-item-status-dot" :class="stopStatusColor(stop)"></div>
                                    <div class="sh-item-name">
                                        Stop <span x-text="stop.stop_number || stop.sequence || stop.id"></span>
                                        — <span x-text="stop.recipient_name || '-'"></span>
                                    </div>
                                </div>
                                <div class="sh-item-tracking" x-show="stop.recipient_phone" x-text="stop.recipient_phone"></div>
                                <div class="inv-meta-item" style="margin-top:0.25rem;" x-show="stop.location?.region || stop.location?.town">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span x-text="[stop.location?.town, stop.location?.district, stop.location?.region].filter(Boolean).join(', ')"></span>
                                </div>
                            </div>
                            <span class="inv-status-badge" :class="stopStatusColor(stop)" x-text="stopStatusLabel(stop)"></span>
                        </div>

                        {{-- Items at this stop --}}
                        <div x-show="(stop.items || []).length > 0"
                             style="background:#f8fafc;border-radius:10px;padding:0.65rem 0.875rem;margin-bottom:0.65rem;border:1px solid #f1f5f9;">
                            <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:0.4rem;">Items</div>
                            <template x-for="sItem in (stop.items || [])" :key="sItem.id">
                                <div style="display:flex;justify-content:space-between;font-size:0.82rem;color:#475569;padding:0.15rem 0;">
                                    <span x-text="sItem.description || sItem.tracking_code"></span>
                                    <span style="font-weight:600;color:#1e293b;" x-text="`× ${sItem.quantity}`"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Proof photo (delivered) --}}
                        <div x-show="stop.status === 'delivered' && stop.proof_photo_url" style="margin-bottom:0.65rem;">
                            <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#059669;margin-bottom:0.35rem;">Proof of Delivery</div>
                            <img :src="stop.proof_photo_url" alt="Proof of delivery" style="max-width:200px;border-radius:10px;border:1px solid #d1fae5;">
                        </div>

                        {{-- Failure info --}}
                        <div x-show="stop.status === 'failed'" style="background:#fef2f2;border-radius:10px;padding:0.65rem 0.875rem;margin-bottom:0.65rem;border:1px solid #fecaca;">
                            <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#dc2626;margin-bottom:0.35rem;">Delivery Failed</div>
                            <div style="font-size:0.82rem;color:#7f1d1d;" x-show="stop.failure_reason" x-text="stop.failure_reason"></div>
                            <div style="font-size:0.82rem;color:#991b1b;margin-top:0.25rem;" x-show="stop.failure_notes" x-text="stop.failure_notes"></div>
                        </div>

                        {{-- Stop actions --}}
                        <div class="sh-stop-actions">
                            {{-- Arrive at stop --}}
                            <div x-show="stop.status === 'pending'">
                                <button type="button" @click="arriveAtStop(stop)" :disabled="stopActionLoading[stop.id]"
                                        class="sh-stop-btn-arrive">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span x-show="!stopActionLoading[stop.id]">Arrived at Stop</span>
                                    <span x-show="stopActionLoading[stop.id]" x-cloak>Updating...</span>
                                </button>
                            </div>

                            {{-- Confirm delivery form --}}
                            <div class="sh-stop-form" x-show="stop.status === 'arrived'">
                                <div class="sh-stop-form-title">Confirm Delivery</div>
                                <div>
                                    <label>Verification Code <span style="color:#dc2626;">*</span></label>
                                    <input x-model="getStopForm(stop.id).verification_code" type="text"
                                           placeholder="Enter code from recipient">
                                </div>
                                <div>
                                    <label>Proof Photo (optional)</label>
                                    <input type="file" accept="image/*" @change="onProofPhotoSelected(stop.id, $event)">
                                </div>
                                <div>
                                    <button type="button" @click="confirmDelivery(stop)" :disabled="stopActionLoading[stop.id]"
                                            class="sh-stop-btn-confirm">
                                        <span x-show="!stopActionLoading[stop.id]">Confirm Delivery</span>
                                        <span x-show="stopActionLoading[stop.id]" x-cloak>Confirming...</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Fail delivery form --}}
                            <div class="sh-stop-form" x-show="stop.status === 'arrived'" style="background:#fef9f9;border-color:#fecaca;">
                                <div class="sh-stop-form-title" style="color:#dc2626;">Mark as Failed</div>
                                <div>
                                    <label>Failure Reason <span style="color:#dc2626;">*</span></label>
                                    <select x-model="getStopForm(stop.id).failure_reason">
                                        <option value="">Select reason...</option>
                                        <option value="could_not_locate">Could not locate address</option>
                                        <option value="recipient_unavailable">Recipient unavailable</option>
                                        <option value="wrong_address">Wrong address</option>
                                        <option value="refused_delivery">Recipient refused delivery</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Notes (optional)</label>
                                    <textarea x-model="getStopForm(stop.id).failure_notes" rows="2" placeholder="Additional details..."></textarea>
                                </div>
                                <div>
                                    <button type="button" @click="failDelivery(stop)" :disabled="stopActionLoading[stop.id]"
                                            class="sh-stop-btn-fail">
                                        <span x-show="!stopActionLoading[stop.id]">Mark as Failed</span>
                                        <span x-show="stopActionLoading[stop.id]" x-cloak>Updating...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

</div>
@endsection
