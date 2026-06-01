@extends('warehouse.layouts.app')

@section('title', 'Package ' . ($package['tracking_code'] ?? $package['shipment_number'] ?? $receiptItem->id))
@section('page-title', 'Package Detail')

@section('content')
<div class="space-y-5" x-data="warehousePackageShowPage" data-warehouse-package-show-config="{{ e(json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="relative p-5 sm:p-6">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_58%)]"></div>
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('warehouse.packages.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-slate-200 transition hover:bg-white/15">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Warehouse Packages
                    </a>
                    <div class="mt-5 flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-950/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Package Command Center</p>
                            <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl" x-text="pkg.item_description || 'Package'"></h1>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-bold text-slate-300">
                                <span x-text="pkg.tracking_code || pkg.barcode_value || 'No tracking code'"></span>
                                <span class="text-slate-600">/</span>
                                <span x-text="pkg.shipment_number || 'No shipment'"></span>
                                <span class="text-slate-600">/</span>
                                <span x-text="'Qty ' + (pkg.received_quantity || pkg.quantity || 0)"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <button type="button" x-show="permissions.can_edit_package" @@click="openEditModal()" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white px-4 py-3 text-sm font-black text-slate-900 shadow-lg transition hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        Edit
                    </button>
                    <button type="button" @@click="openPrintModal()" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-orange-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0v4h12v-4H6z"/></svg>
                        Print
                    </button>
                    <a x-show="pkg.shipment_url" :href="pkg.shipment_url" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/15">Shipment</a>
                </div>
            </div>
            <div class="relative mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Stage</p>
                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black" :class="badgeClass(pkg.current_stage?.tone)" x-text="pkg.current_stage?.label || '-'"></span>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Custody</p>
                    <p class="mt-2 text-sm font-black text-white" x-text="pkg.custody?.label || '-'"></p>
                    <p class="mt-1 text-xs text-slate-400" x-text="pkg.custody?.holder || pkg.custody?.detail || ''"></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Payment</p>
                    <p class="mt-2 text-xl font-black text-white" x-text="paymentAmount()"></p>
                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black" :class="paymentClass(pkg.payment?.status_label)" x-text="pkg.payment?.status_label || '-'"></span>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Received</p>
                    <p class="mt-2 text-sm font-black text-white" x-text="pkg.received_at || '-'"></p>
                    <p class="mt-1 text-xs text-slate-400" x-text="pkg.received_by || pkg.pickup_driver || ''"></p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
        <main class="space-y-5">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Package Details</h2>
                </div>
                <div class="grid gap-0 divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div class="p-5">
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">Description</dt><dd class="mt-1 text-sm font-black text-slate-900" x-text="pkg.item_description || '-'"></dd></div>
                            <div><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">Quantity</dt><dd class="mt-1 text-sm font-black text-slate-900" x-text="pkg.received_quantity || pkg.quantity || 0"></dd></div>
                            <div><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">Tracking</dt><dd class="mt-1 font-mono text-sm font-black text-orange-700" x-text="pkg.tracking_code || '-'"></dd></div>
                            <div><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">Labels</dt><dd class="mt-1 text-sm font-black text-slate-900" x-text="pkg.label_count || 0"></dd></div>
                            <div><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery method</dt><dd class="mt-1 text-sm font-black text-slate-900" x-text="pkg.delivery_method_label || '-'"></dd></div>
                            <div><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">Receipt source</dt><dd class="mt-1 text-sm font-black text-slate-900" x-text="pkg.receipt_source || '-'"></dd></div>
                        </dl>
                        <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-xs font-bold text-slate-500">
                            <p><span class="font-black uppercase tracking-wide text-slate-400">Submitted</span> <span x-text="pkg.shipment?.submitted_at || pkg.shipment?.created_at || '-'"></span></p>
                            <p class="mt-1" x-text="'By ' + (pkg.shipment?.submitted_by || pkg.vendor?.name || '-') + ' / ' + (pkg.shipment?.source || 'Unknown source')"></p>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Vendor</p><p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.vendor?.name || '-'"></p><p class="text-xs font-semibold text-slate-500" x-text="pkg.vendor?.phone || ''"></p></div>
                            <div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Shipment</p><p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.shipment?.number || '-'"></p><p class="text-xs font-semibold text-slate-500" x-text="pkg.shipment?.destination_mode || ''"></p></div>
                            <div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Recipient</p><p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.recipient_name || '-'"></p><p class="text-xs font-semibold text-slate-500" x-text="pkg.recipient_phone || ''"></p></div>
                            <div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Destination</p><p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.destination || '-'"></p></div>
                        </div>
                        <div class="mt-5 rounded-2xl bg-emerald-50 p-4 text-xs font-bold text-emerald-800">
                            <p><span class="font-black uppercase tracking-wide text-emerald-600">Received</span> <span x-text="pkg.warehouse_receipt?.received_at || '-'"></span></p>
                            <p class="mt-1" x-text="'By ' + (pkg.warehouse_receipt?.received_by || pkg.warehouse_receipt?.pickup_driver || '-')"></p>
                            <p class="mt-1 text-emerald-700" x-text="[pkg.warehouse_receipt?.source, pkg.warehouse_receipt?.pickup_driver_phone].filter(Boolean).join(' / ')"></p>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="pkg.collection" x-cloak class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-100 bg-emerald-50/60 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wide text-emerald-800">Collection Handover</h2>
                        <p class="mt-1 text-xs font-bold text-emerald-700">Self-pickup collection record for this package.</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black" :class="pkg.collection?.is_collected ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800'" x-text="pkg.collection?.status_label || '-'"></span>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Collected At</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.collection?.collected_at || '-'"></p>
                        <p class="mt-1 text-xs font-bold text-slate-500" x-show="pkg.collection?.ready_at" x-text="'Ready ' + pkg.collection.ready_at"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Collected By</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.collection?.collected_by_name || '-'"></p>
                        <p class="mt-1 text-xs font-bold text-slate-500" x-text="pkg.collection?.collected_by_phone || ''"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Handed Over By</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.collection?.handed_over_by || '-'"></p>
                        <p class="mt-1 text-xs font-bold text-slate-500" x-text="[pkg.collection?.warehouse, pkg.collection?.warehouse_code].filter(Boolean).join(' / ') || ''"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Collector ID</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="[pkg.collection?.collected_by_id_type, pkg.collection?.collected_by_id_number].filter(Boolean).join(' / ') || '-'"></p>
                        <p class="mt-1 text-xs font-bold text-slate-500" x-show="pkg.collection?.notes" x-text="pkg.collection.notes"></p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Warehouse Handling</h2>
                    <button type="button" x-show="permissions.can_edit_package && pkg.can_forward_to_warehouse" @@click="openEditModal()" class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-black text-orange-800">Change transfer warehouse</button>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Current stage</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.current_stage?.label || '-'"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Forwarding</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.forward_to_warehouse_id ? 'Forwarding selected' : 'No forwarding selected'"></p>
                        <p class="mt-1 text-xs font-bold text-slate-500" x-text="pkg.forward_lock_reason || (pkg.can_forward_to_warehouse ? 'Editable while still open at warehouse.' : '')"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Last warehouse touch</p>
                        <p class="mt-1 text-sm font-black text-slate-900" x-text="pkg.warehouse_receipt?.received_at || '-'"></p>
                        <p class="mt-1 text-xs font-bold text-slate-500" x-text="pkg.warehouse_receipt?.received_by || '-'"></p>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Sorting & Transfer</h2>
                        <button type="button" x-show="permissions.can_edit_package && pkg.can_forward_to_warehouse" @@click="openEditModal()" class="rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-black text-violet-800">Edit transfer</button>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="batch in pkg.histories.sort_batches" :key="batch.id">
                            <div class="p-5">
                                <a :href="batch.url" class="text-sm font-black text-violet-700 underline decoration-violet-200 underline-offset-4" x-text="batch.number || '-'"></a>
                                <p class="mt-1 text-xs font-bold text-slate-500" x-text="[batch.status, batch.dispatch_mode, batch.destination].filter(Boolean).join(' · ')"></p>
                                <p class="mt-2 text-xs text-slate-400" x-text="'Added by ' + (batch.added_by || '-') + ' · ' + (batch.added_at || '-')"></p>
                                <p class="mt-1 text-xs text-slate-400" x-show="batch.sealed_at" x-text="'Sealed by ' + (batch.sealed_by || '-') + ' · ' + batch.sealed_at"></p>
                            </div>
                        </template>
                        <div x-show="!pkg.histories.sort_batches.length" class="p-5">
                            <p class="text-sm font-bold text-slate-400">No sort batch yet.</p>
                            <p class="mt-2 text-xs font-bold text-slate-500" x-text="pkg.can_forward_to_warehouse ? 'You can choose a transfer warehouse from Edit Package.' : (pkg.forward_lock_reason || '')"></p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Manifest & Delivery</h2></div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="manifest in pkg.histories.manifests" :key="'m' + manifest.id">
                            <div class="p-5">
                                <a :href="manifest.url" class="text-sm font-black text-blue-700 underline decoration-blue-200 underline-offset-4" x-text="manifest.number || '-'"></a>
                                <p class="mt-1 text-xs font-bold text-slate-500" x-text="[manifest.status, manifest.driver, manifest.destination].filter(Boolean).join(' · ')"></p>
                                <p class="mt-2 text-xs text-slate-400" x-text="'Created by ' + (manifest.created_by || '-')"></p>
                                <p class="mt-1 text-xs text-slate-400" x-show="manifest.received_at" x-text="'Received by ' + (manifest.received_by || '-') + ' · ' + manifest.received_at"></p>
                            </div>
                        </template>
                        <template x-for="delivery in pkg.histories.deliveries" :key="'d' + delivery.id">
                            <div class="p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a :href="delivery.url" class="text-sm font-black text-emerald-700 underline decoration-emerald-200 underline-offset-4" x-text="delivery.number || '-'"></a>
                                        <p class="mt-1 text-xs font-bold text-slate-500" x-text="[delivery.status, delivery.stop_status, delivery.driver, delivery.delivery_method].filter(Boolean).join(' · ')"></p>
                                    </div>
                                    <button type="button" x-show="permissions.can_send_delay_notices && delivery.eta?.can_notify" @@click="openDelayModal(delivery)" class="inline-flex shrink-0 items-center rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-700 transition hover:bg-amber-100">Send Delay Notice</button>
                                </div>
                                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                                    <p><span class="font-black uppercase tracking-wide text-slate-400">Assigned</span><br><span class="font-bold text-slate-700" x-text="[delivery.driver, delivery.driver_phone].filter(Boolean).join(' / ') || '-'"></span></p>
                                    <p><span class="font-black uppercase tracking-wide text-slate-400">Dispatched</span><br><span class="font-bold text-slate-700" x-text="delivery.dispatched_at || '-'"></span></p>
                                    <p><span class="font-black uppercase tracking-wide text-slate-400">ETA / delay</span><br><span class="font-bold text-slate-700" x-text="[delivery.eta?.label, delivery.eta?.expected_delivery_at].filter(Boolean).join(' / ') || '-'"></span></p>
                                    <p><span class="font-black uppercase tracking-wide text-slate-400">Arrived</span><br><span class="font-bold text-slate-700" x-text="delivery.arrived_at || '-'"></span></p>
                                    <p><span class="font-black uppercase tracking-wide text-slate-400">Delivered / closed</span><br><span class="font-bold text-slate-700" x-text="delivery.delivered_at || delivery.completed_at || '-'"></span></p>
                                    <p class="sm:col-span-2"><span class="font-black uppercase tracking-wide text-slate-400">Stop location</span><br><span class="font-bold text-slate-700" x-text="[delivery.stop_location, delivery.stop_landmark, delivery.gh_post_address].filter(Boolean).join(' / ') || '-'"></span></p>
                                    <p x-show="delivery.confirmed_by || delivery.confirmed_at" class="sm:col-span-2"><span class="font-black uppercase tracking-wide text-slate-400">Confirmed</span><br><span class="font-bold text-slate-700" x-text="[delivery.confirmed_by, delivery.confirmed_at].filter(Boolean).join(' / ')"></span></p>
                                </div>
                                <div x-show="delivery.verification?.sent_at || delivery.verification?.attempts" class="mt-3 rounded-xl bg-indigo-50 p-3 text-xs font-bold text-indigo-800">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>OTP sent <span x-text="delivery.verification?.sent_at || '-'"></span></span>
                                        <span x-show="delivery.verification?.code" class="rounded-lg bg-white px-2 py-1 font-mono text-sm tracking-[0.25em] text-indigo-700 ring-1 ring-indigo-200" x-text="delivery.verification?.code"></span>
                                    </div>
                                    <p class="mt-1 text-indigo-700" x-text="'Attempts ' + (delivery.verification?.attempts || 0) + '/' + (delivery.verification?.max_attempts || 0)"></p>
                                </div>
                                <div x-show="delivery.failure_reason || delivery.failure_notes" class="mt-3 rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-800">
                                    <p x-text="delivery.failure_reason || 'Failed delivery'"></p>
                                    <p class="mt-1 text-rose-700" x-text="delivery.failure_notes || ''"></p>
                                </div>
                                <div x-show="delivery.bus_handoff" class="mt-3 rounded-xl bg-amber-50 p-3 text-xs font-bold text-amber-800">
                                    <p x-text="delivery.bus_handoff?.bus_station || 'Bus station / courier handoff'"></p>
                                    <p class="mt-1 text-amber-700" x-text="[delivery.bus_handoff?.courier_name, delivery.bus_handoff?.courier_phone, delivery.bus_handoff?.vehicle_number].filter(Boolean).join(' / ')"></p>
                                    <p class="mt-1 text-amber-700" x-show="delivery.bus_handoff?.handoff_at" x-text="'Handed off ' + delivery.bus_handoff.handoff_at"></p>
                                    <p class="mt-1 text-amber-700" x-show="delivery.bus_handoff?.handoff_owner" x-text="'Handoff recorded by ' + delivery.bus_handoff.handoff_owner"></p>
                                    <p class="mt-1 text-amber-700" x-show="delivery.bus_handoff?.confirmed_by || delivery.bus_handoff?.confirmation_source" x-text="'Delivery confirmation: ' + [delivery.bus_handoff?.confirmation_status, delivery.bus_handoff?.confirmation_source, delivery.bus_handoff?.confirmed_by, delivery.bus_handoff?.confirmed_at].filter(Boolean).join(' / ')"></p>
                                </div>
                                <div x-show="delivery.delay_history?.length" class="mt-3 rounded-xl bg-slate-50 p-3 text-xs font-bold text-slate-700">
                                    <p class="font-black uppercase tracking-wide text-slate-400">Delay history</p>
                                    <template x-for="event in delivery.delay_history" :key="event.id">
                                        <p class="mt-1" x-text="[event.source_label, event.reason, event.new_eta, event.actor, event.created_at].filter(Boolean).join(' / ')"></p>
                                    </template>
                                </div>
                                <a x-show="delivery.proof_photo_url" :href="delivery.proof_photo_url" target="_blank" class="mt-3 inline-flex rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">View delivery proof</a>
                            </div>
                        </template>
                        <div x-show="!pkg.histories.manifests.length && !pkg.histories.deliveries.length" class="p-5 text-sm font-bold text-slate-400">-</div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Photos</h2>
                    <button type="button" @@click="openPackagePhotos()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">View photos</button>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-3">
                    <template x-for="photo in rowPhotoList().slice(0, 6)" :key="photo.url">
                        <button type="button" @@click="openPackagePhotos()" class="aspect-[4/3] overflow-hidden rounded-2xl bg-slate-100">
                            <img :src="photo.url" :alt="photo.name || 'Package photo'" class="h-full w-full object-cover">
                        </button>
                    </template>
                    <div x-show="!rowPhotoList().length" class="rounded-2xl bg-slate-50 p-5 text-sm font-bold text-slate-400">No photos available.</div>
                </div>
            </section>
        </main>

        <aside class="space-y-5">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Current Custody</h2></div>
                <div class="p-5">
                    <p class="text-xl font-black text-slate-900" x-text="pkg.custody?.label || '-'"></p>
                    <p class="mt-1 text-sm font-bold text-slate-600" x-text="pkg.custody?.holder || ''"></p>
                    <p class="mt-1 text-sm text-slate-500" x-text="pkg.custody?.detail || ''"></p>
                    <p class="mt-3 text-xs font-bold text-slate-400" x-show="pkg.custody?.at" x-text="'Since ' + pkg.custody.at"></p>
                    <p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs font-bold text-slate-500" x-show="pkg.custody?.type === 'at_warehouse'" x-text="'Last warehouse touch: ' + (pkg.warehouse_receipt?.received_by || '-') + ' / ' + (pkg.warehouse_receipt?.received_at || '-')"></p>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Rider Location Changes</h2></div>
                <div class="divide-y divide-slate-100">
                    <template x-for="change in pkg.histories.location_changes" :key="'lc' + change.id">
                        <div class="p-5 text-sm">
                            <p class="font-black text-slate-900" x-text="change.new_location || '-'"></p>
                            <p class="mt-1 text-xs font-semibold text-slate-500" x-text="'From ' + (change.old_location || '-')"></p>
                            <p class="mt-2 text-xs text-slate-400" x-text="[change.driver, change.driver_phone, change.changed_at].filter(Boolean).join(' / ')"></p>
                            <a x-show="change.proof_photo_url" :href="change.proof_photo_url" target="_blank" class="mt-3 inline-flex rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-black text-orange-700">View proof photo</a>
                        </div>
                    </template>
                    <div x-show="!pkg.histories.location_changes?.length" class="p-5 text-sm font-bold text-slate-400">-</div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Rider Transfers</h2></div>
                <div class="divide-y divide-slate-100">
                    <template x-for="transfer in pkg.histories.rider_transfers" :key="'rt' + transfer.id">
                        <div class="p-5 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-slate-900" x-text="[transfer.from_driver, transfer.to_driver].filter(Boolean).join(' → ') || '-'"></p>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600" x-text="transfer.status || '-'"></span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500" x-text="'Requested ' + (transfer.requested_at || '-')"></p>
                            <p class="mt-1 text-xs text-slate-500" x-show="transfer.responded_at" x-text="'Resolved ' + transfer.responded_at"></p>
                        </div>
                    </template>
                    <div x-show="!pkg.histories.rider_transfers?.length" class="p-5 text-sm font-bold text-slate-400">-</div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Delivery Fee</h2>
                    <span class="rounded-full px-3 py-1 text-xs font-black" :class="paymentClass(pkg.payment?.status_label)" x-text="pkg.payment?.status_label || '-'"></span>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Amount</p>
                        <p class="mt-1 text-3xl font-black text-slate-900" x-text="paymentAmount()"></p>
                    </div>
                    <dl class="grid gap-3 text-sm">
                        <div><dt class="text-xs font-black uppercase tracking-wide text-slate-400">Processed by</dt><dd class="font-bold text-slate-700" x-text="pkg.payment?.paid_by || '-'"></dd></div>
                        <div><dt class="text-xs font-black uppercase tracking-wide text-slate-400">Paid at</dt><dd class="font-bold text-slate-700" x-text="pkg.payment?.paid_at || '-'"></dd></div>
                        <div><dt class="text-xs font-black uppercase tracking-wide text-slate-400">Wallet / Reference</dt><dd class="font-bold text-slate-700" x-text="[pkg.payment?.wallet, pkg.payment?.reference].filter(Boolean).join(' / ') || '-'"></dd></div>
                    </dl>
                    <div class="flex flex-wrap gap-2" x-show="permissions.can_process_payments">
                        <button type="button" @@click="openPaymentModal()" class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-black text-orange-800">Process payment</button>
                    </div>
                    <p x-show="!permissions.can_process_payments" class="rounded-xl bg-slate-50 p-3 text-xs font-bold text-slate-500">Payment actions require recipient payment permission.</p>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Quick Actions</h2></div>
                <div class="grid gap-2 p-5">
                    <button type="button" x-show="permissions.can_edit_package" @@click="openEditModal()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-black text-slate-700">Edit package / recipient / transfer</button>
                    <button type="button" @@click="openPrintModal()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-black text-slate-700">Print labels</button>
                    <button type="button" x-show="permissions.can_process_payments" @@click="openPaymentModal()" class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-left text-xs font-black text-orange-800">Record delivery fee / payment</button>
                </div>
            </section>
        </aside>
    </div>

    <div x-show="editModalOpen" x-cloak x-transition.opacity @@keydown.window.escape="closeEditModal()" class="fixed inset-0 z-50 flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" style="display:none">
        <div @@click.stop class="flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-start justify-between border-b border-slate-100 p-5">
                <div><h3 class="text-lg font-black text-slate-900">Edit Package</h3><p class="mt-1 text-sm text-slate-500" x-text="pkg.tracking_code || pkg.shipment_number || ''"></p></div>
                <button type="button" @@click="closeEditModal()" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div><p class="text-sm font-black text-slate-700" x-text="photoUploadFiles.length ? photoUploadFiles.length + ' new photo(s)' : (rowPhotoList().length ? pkg.photos.primary_label + ' available' : 'Upload package photos')"></p><p class="text-xs text-slate-400">PNG, JPG or WEBP up to 12MB each</p></div>
                        <div class="flex gap-2"><button type="button" x-show="rowPhotoList().length" @@click="openPackagePhotos()" class="rounded-lg bg-white px-3 py-2 text-xs font-black text-slate-700 ring-1 ring-slate-200">View</button><label class="cursor-pointer rounded-lg bg-white px-3 py-2 text-xs font-black text-orange-700 ring-1 ring-orange-100">Choose<input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" capture="environment" multiple class="hidden" @@change="handlePackagePhotos($event)"></label></div>
                    </div>
                    <p x-show="!hasRequiredPackagePhotos()" class="mt-2 text-xs font-bold text-rose-600">At least one package photo is required.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-9"><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Description</label><input x-model="editForm.description" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                    <div class="sm:col-span-3"><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Qty</label><input type="number" min="1" x-model.number="editForm.quantity" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-black outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient name</label><input x-model="editForm.delivery_recipient_name" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                    <div><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient phone</label><input x-model="editForm.delivery_recipient_phone" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                </div>
                <div class="relative"><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Location</label><input x-model="editForm.locationQuery" @@input.debounce.250ms="searchLocation(editForm)" @@blur="closeLocationDropdownSoon(editForm)" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><div x-show="editForm._showDropdown" class="absolute z-50 mt-2 max-h-56 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"><template x-for="location in editForm.locationResults" :key="location.id"><button type="button" @@mousedown.prevent="selectLocation(editForm, location)" class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50" x-text="location.display"></button></template></div></div>
                <div><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Landmark</label><input x-model="editForm.delivery_landmark" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                <div><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Instructions</label><textarea rows="3" x-model="editForm.delivery_instructions" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea></div>
                <label x-show="pkg.can_edit_bus_handoff" class="flex items-center justify-between rounded-2xl border-2 border-slate-200 p-4"><span><span class="block text-xs font-black uppercase tracking-wide text-slate-500">Bus station</span><span class="block text-sm font-black text-slate-900">Send to bus station</span></span><input type="checkbox" class="h-6 w-6 rounded border-slate-300 text-orange-600 focus:ring-orange-500" :checked="editForm.delivery_method === 'bus_handoff'" @@change="editForm.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'"></label>
                <div x-show="pkg.can_forward_to_warehouse"><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Forward to warehouse</label><select x-model="editForm.forward_to_warehouse_id" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><option value="">No forwarding</option><template x-for="warehouse in config.transfer_warehouses" :key="warehouse.id"><option :value="warehouse.id" x-text="warehouse.name"></option></template></select></div>
            </div>
            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="closeEditModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">Cancel</button>
                <button type="button" @@click="savePackage()" :disabled="!canSaveEditPackage()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white disabled:opacity-40" x-text="modalLoading ? 'Saving...' : 'Save Package'"></button>
            </div>
        </div>
    </div>

    <div x-show="printModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm" style="display:none">
        <div @@click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 p-5"><h3 class="text-lg font-black text-slate-900">Print Labels</h3><p class="mt-1 text-sm text-slate-500" x-text="pkg.tracking_code || pkg.shipment_number || ''"></p></div>
            <div class="p-5"><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Labels to print</label><div class="flex items-center justify-center gap-3"><button type="button" @@click="setPrintLabelCount(Number(printForm.label_count || 1) - 1)" class="h-11 w-11 rounded-xl border border-slate-200 text-xl font-black">-</button><input type="number" min="1" max="500" x-model.number="printForm.label_count" @@input="setPrintLabelCount(printForm.label_count)" class="h-11 w-24 rounded-xl border-2 border-slate-200 text-center font-black"><button type="button" @@click="setPrintLabelCount(Number(printForm.label_count || 1) + 1)" class="h-11 w-11 rounded-xl border border-slate-200 text-xl font-black">+</button></div></div>
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4"><button type="button" @@click="printModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">Cancel</button><button type="button" @@click="printLabel()" class="rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-sm font-black text-white" x-text="printLoading ? 'Printing...' : 'Print Labels'"></button></div>
        </div>
    </div>

    <div x-show="paymentModalOpen" x-cloak x-transition.opacity @@keydown.window.escape="paymentModalOpen = false" class="fixed inset-0 z-[9998] h-[100dvh] w-[100vw] overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm sm:p-6" style="display:none">
        <div class="flex min-h-full items-center justify-center">
        <div @@click.stop class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-slate-200/60 bg-white/95 shadow-2xl backdrop-blur-xl sm:max-h-[calc(100dvh-3rem)]">
            <div class="relative shrink-0 border-b border-slate-200/60 bg-gradient-to-r from-white to-slate-50 px-4 py-4 sm:px-6 sm:py-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-2.2 0-4 1.2-4 2.8s1.8 2.8 4 2.8 4 1.2 4 2.8S14.2 19.2 12 19.2m0-11.2V6m0 13.2V21M5 12H3m18 0h-2"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-black leading-tight text-slate-900">Process Payment</h3>
                            <p class="mt-1 text-sm font-medium leading-6 text-slate-500">Record the negotiated delivery fee and payment details.</p>
                        </div>
                    </div>
                    <button type="button" @@click="paymentModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 bg-white/90 p-2 text-slate-400 shadow-sm transition hover:border-slate-300 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto bg-white px-4 py-5 sm:px-6 sm:py-6">
                    <div class="space-y-6">
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label class="block text-xs font-black uppercase tracking-wide text-slate-500">Payment wallet <span class="text-rose-500">*</span></label>
                                <button type="button" x-show="activeWallets().length > 1 && selectedPaymentWallet() && !paymentWalletChanging" @@click="paymentWalletChanging = true" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-700 shadow-sm">Change</button>
                            </div>
                            <div x-show="!activeWallets().length" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800">
                                No active payment wallet is assigned to you. You can save the fee details, but payment cannot be recorded until a wallet is assigned.
                            </div>
                            <div x-show="selectedPaymentWallet() && !paymentWalletChanging" @@click="!selectedWalletHasOpenSession() && (window.location.href = config.payment_sessions_url)" class="rounded-2xl border-2 border-slate-200 bg-white p-4 transition" :class="selectedWalletHasOpenSession() ? '' : 'cursor-pointer hover:border-amber-300 hover:bg-amber-50/40'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-black text-slate-900" x-text="selectedWalletLabel()"></p>
                                        <p class="mt-1 text-sm font-semibold text-slate-500" x-text="selectedPaymentWallet()?.account_owner || 'Assigned wallet'"></p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black" :class="selectedWalletHasOpenSession() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'" x-text="selectedWalletHasOpenSession() ? 'Session open' : 'No session'"></span>
                                </div>
                                <p class="mt-3 text-xs font-bold" :class="selectedWalletHasOpenSession() ? 'text-emerald-700' : 'text-amber-700'">
                                    <span x-text="paymentSessionMessage()"></span>
                                    <span x-show="selectedPaymentWallet() && !selectedWalletHasOpenSession()" class="ml-1 font-black underline decoration-amber-300 underline-offset-4">Click here to start one.</span>
                                </p>
                            </div>
                            <select x-show="activeWallets().length && (paymentWalletChanging || !selectedPaymentWallet())" x-model="paymentForm.payment_wallet_id" @@change="paymentWalletChanging = false" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">Select wallet</option>
                                <template x-for="wallet in activeWallets()" :key="wallet.id">
                                    <option :value="wallet.id" x-text="selectedWalletLabel(wallet)"></option>
                                </template>
                            </select>
                            <div x-show="paymentTaskBlockedMessage()" class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800" x-text="paymentTaskBlockedMessage()"></div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Delivery fee <span class="text-rose-500">*</span></label>
                            <div class="flex items-center rounded-2xl border-2 border-slate-200 bg-white px-4 transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                                <span class="mr-3 shrink-0 text-base font-black text-slate-500">GHS</span>
                                <input type="number" min="0" step="0.01" x-model="paymentForm.amount" placeholder="0.00" class="min-w-0 w-full flex-1 border-0 bg-transparent py-4 text-base font-semibold text-slate-900 outline-none">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">MoMo reference <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                                <input x-model="paymentForm.payment_reference" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-4 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Payment reference">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Call result <span class="text-rose-500">*</span></label>
                                <select x-model="paymentForm.outcome" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="answered">Answered</option>
                                    <option value="no_answer">No answer</option>
                                    <option value="busy">Busy</option>
                                    <option value="callback">Call back later</option>
                                    <option value="wrong_number">Wrong number</option>
                                    <option value="payment_promised">Pay later</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Receipt screenshot <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                            <label class="flex min-w-0 cursor-pointer items-center justify-between gap-4 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-4 py-4 transition hover:border-orange-300 hover:bg-orange-50/40">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-base font-black text-slate-700" x-text="paymentForm.payment_receipt_name || 'Upload MoMo receipt screenshot'"></span>
                                    <span class="block text-sm font-medium text-slate-400">PNG, JPG or WEBP up to 5MB</span>
                                </span>
                                <span class="inline-flex w-fit shrink-0 rounded-xl bg-white px-4 py-3 text-sm font-black text-orange-700 shadow-sm ring-1 ring-orange-100">Choose</span>
                                <input x-ref="packagePaymentReceiptInput" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" @@change="handlePackagePaymentReceiptChange($event)">
                            </label>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Notes</label>
                            <textarea rows="3" x-model="paymentForm.notes" placeholder="Call notes" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-4 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea>
                        </div>
                    </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200/60 bg-gradient-to-r from-slate-50/80 to-slate-100/50 px-4 py-4 sm:px-6">
                <button type="button" @@click="paymentModalOpen = false" :disabled="paymentLoading" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 disabled:opacity-40">Cancel</button>
                <button type="button" @@click="saveDeliveryFee()" :disabled="!canSavePaymentDetails()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm disabled:opacity-40" x-text="paymentLoading ? 'Saving...' : 'Save Details'"></button>
                <button type="button" @@click="markPaid()" :disabled="!canSubmitPayment()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 disabled:opacity-40" x-text="paymentLoading ? 'Saving...' : 'Save Payment'"></button>
            </div>
        </div>
        </div>
    </div>

    <div x-show="delayModalOpen" x-cloak x-transition.opacity @@keydown.window.escape="closeDelayModal()" class="fixed inset-0 z-[9998] h-[100dvh] w-[100vw] overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm sm:p-6" style="display:none">
        <div class="flex min-h-full items-center justify-center">
            <div @@click.stop class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-2xl sm:max-h-[calc(100dvh-3rem)]">
                <div class="shrink-0 border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-xl font-black leading-tight text-slate-900">Send Delay Notice</h3>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-500" x-text="pkg.tracking_code || pkg.shipment_number || 'Package delay'"></p>
                            </div>
                        </div>
                        <button type="button" @@click="closeDelayModal()" :disabled="delayLoading" class="shrink-0 rounded-xl border border-slate-200 bg-white p-2 text-slate-400 shadow-sm transition hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-white px-4 py-5 sm:px-6">
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Delay reason</label>
                        <select x-model="delayForm.reason_id" @@change="updateDelayMessage()" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Select reason</option>
                            <template x-for="reason in delayReasons" :key="reason.id">
                                <option :value="reason.id" x-text="reason.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Revised ETA <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input type="text" x-ref="delayEtaInput" readonly placeholder="Select date and time" class="w-full cursor-pointer rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-bold text-slate-700"><input type="checkbox" x-model="delayForm.notify_recipient" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"> Recipient SMS</label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-bold text-slate-700"><input type="checkbox" x-model="delayForm.notify_vendor" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"> Vendor app</label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-bold text-slate-700"><input type="checkbox" x-model="delayForm.notify_vendor_sms" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"> Vendor SMS</label>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea x-model="delayForm.notes" rows="3" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Internal note"></textarea>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Message</label>
                        <textarea x-model="delayForm.message" @@input="delayForm.message_touched = true" rows="4" class="w-full rounded-2xl border-2 border-amber-200 bg-amber-50 px-4 py-4 text-base font-semibold leading-7 text-amber-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Message to send"></textarea>
                        <p class="mt-1 text-xs font-semibold text-slate-400">You can adjust this message before sending.</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200/60 bg-slate-50 px-4 py-4 sm:px-6">
                    <button type="button" @@click="closeDelayModal()" :disabled="delayLoading" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 disabled:opacity-40">Cancel</button>
                    <button type="button" @@click="sendDelayNotice()" :disabled="delayLoading || !delayForm.reason_id" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 disabled:opacity-40" x-text="delayLoading ? 'Sending...' : 'Send Notice'"></button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="photoPreviewOpen" x-cloak x-transition.opacity @@click="closePackagePhotos()" @@keydown.window.escape="closePackagePhotos()" @@keydown.window.arrow-left="previousPackagePhoto()" @@keydown.window.arrow-right="nextPackagePhoto()" class="fixed left-0 top-0 z-[9999] flex h-[100dvh] w-[100vw] items-center justify-center bg-black p-4" style="display:none">
        <button type="button" @@click.stop="closePackagePhotos()" class="absolute right-4 top-4 z-20 rounded-full bg-white/10 p-3 text-white/80 hover:bg-white/20"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        <button type="button" x-show="canRemoveActivePhoto()" @@click.stop="removeActivePhoto()" class="absolute left-4 top-4 z-20 rounded-full bg-rose-500/90 px-4 py-2 text-xs font-black text-white">Remove photo</button>
        <button type="button" x-show="photoPreviewUrls.length > 1" @@click.stop="previousPackagePhoto()" class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white/80"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
        <template x-if="activePackagePhoto()"><img @@click.stop :src="activePackagePhoto().url" :alt="activePackagePhoto().name || 'Package photo'" class="max-h-[92dvh] max-w-[94vw] rounded-2xl object-contain shadow-2xl ring-1 ring-white/10"></template>
        <button type="button" x-show="photoPreviewUrls.length > 1" @@click.stop="nextPackagePhoto()" class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white/80"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
    </div>
</div>
@endsection
