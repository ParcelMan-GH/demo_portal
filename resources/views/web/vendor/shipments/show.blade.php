@extends('web.layouts.portal')

@section('title', 'View Shipment')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-7xl px-6 py-10" x-data="vendorShipmentShowPage()" data-shipment-id="{{ $shipmentId }}">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Shipment Details</h1>
            <p class="mt-2 text-sm text-slate-300" x-show="shipment" x-cloak>
                <span x-text="shipment?.shipment_number"></span>
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('web.vendor.shipments.index') }}" class="rounded-xl border border-slate-200/20 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
                Back to List
            </a>
            <a href="{{ route('web.vendor.invoices.index') }}" class="rounded-xl border border-sky-300/30 bg-sky-500/15 px-4 py-2 text-sm font-semibold text-sky-100 hover:bg-sky-500/25">
                Invoices
            </a>
            <a x-show="canEditShipment" :href="`/vendor/shipments/${shipmentId}/edit`" x-cloak
               class="rounded-xl border border-orange-300/30 bg-orange-500/10 px-4 py-2 text-sm font-semibold text-orange-100 hover:bg-orange-500/20">
                Edit Shipment
            </a>
            <button x-show="canSubmitShipment" type="button" @click="submitShipment()" x-cloak
                    class="rounded-xl border border-emerald-300/30 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/20">
                Submit
            </button>
            <button x-show="canDeleteShipment" type="button" @click="deleteShipment()" x-cloak
                    class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-2 text-sm font-semibold text-rose-100 hover:bg-rose-500/20">
                Delete
            </button>
        </div>
    </div>

    <div class="mt-6 space-y-3">
        <div x-show="alert" x-cloak class="rounded-xl border px-4 py-3 text-sm"
             :class="{
                'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': alert?.type === 'success',
                'border-rose-300/30 bg-rose-500/10 text-rose-100': alert?.type === 'error'
             }">
            <span x-text="alert?.message"></span>
        </div>
        <div x-show="itemAlert" x-cloak class="rounded-xl border px-4 py-3 text-sm"
             :class="{
                'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': itemAlert?.type === 'success',
                'border-rose-300/30 bg-rose-500/10 text-rose-100': itemAlert?.type === 'error'
             }">
            <span x-text="itemAlert?.message"></span>
        </div>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading && shipment" x-cloak>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div><div class="text-xs text-slate-400">Status</div><div class="text-sm font-semibold text-white" x-text="statusLabel(shipment.status)"></div></div>
            <div><div class="text-xs text-slate-400">Mode</div><div class="text-sm font-semibold text-white" x-text="modeLabel(shipment.destination_mode)"></div></div>
            <div><div class="text-xs text-slate-400">Created</div><div class="text-sm font-semibold text-white" x-text="formatDateTime(shipment.created_at)"></div></div>
            <div><div class="text-xs text-slate-400">Submitted</div><div class="text-sm font-semibold text-white" x-text="formatDateTime(shipment.submitted_at)"></div></div>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200/10 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="font-semibold text-white">Pickup</div>
                <div class="mt-1">Contact: <span x-text="shipment.pickup?.contact_name || '-'"></span></div>
                <div>Phone: <span x-text="shipment.pickup?.contact_phone || '-'"></span></div>
                <div>Region: <span x-text="shipment.pickup?.location?.region || '-'"></span></div>
                <div>District: <span x-text="shipment.pickup?.location?.district || '-'"></span></div>
                <div>Town: <span x-text="shipment.pickup?.location?.town || '-'"></span></div>
                <div>Landmark: <span x-text="shipment.pickup?.location?.landmark || '-'"></span></div>
            </div>
            <div class="rounded-xl border border-slate-200/10 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="font-semibold text-white">Delivery</div>
                <template x-if="shipment.delivery">
                    <div class="mt-1">
                        <div>Recipient: <span x-text="shipment.delivery?.recipient_name || '-'"></span></div>
                        <div>Phone: <span x-text="shipment.delivery?.recipient_phone || '-'"></span></div>
                        <div>Region: <span x-text="shipment.delivery?.location?.region || '-'"></span></div>
                        <div>District: <span x-text="shipment.delivery?.location?.district || '-'"></span></div>
                        <div>Town: <span x-text="shipment.delivery?.location?.town || '-'"></span></div>
                    </div>
                </template>
                <template x-if="!shipment.delivery">
                    <div class="mt-1">Per-item destination mode.</div>
                </template>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2" x-show="!loading && shipment" x-cloak>
        <div class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-white">Current Invoice</h2>
                <a x-show="currentInvoice?.id" :href="`/vendor/invoices/${currentInvoice?.id}`"
                   class="rounded-lg border border-slate-300/30 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800">
                    View Invoice
                </a>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200/10 bg-slate-950/45 p-4 text-sm text-slate-300" x-show="currentInvoice">
                <div>Invoice #: <span class="font-semibold text-white" x-text="currentInvoice?.invoice_number || '-'"></span></div>
                <div>Status: <span class="font-semibold text-white" x-text="statusLabel(currentInvoice?.status)"></span></div>
                <div>Total: <span class="font-semibold text-white" x-text="formatMoney(currentInvoice?.total_amount, currentInvoice?.currency || 'GHS')"></span></div>
                <div>Created: <span x-text="formatDateTime(currentInvoice?.created_at)"></span></div>
                <div>Sent: <span x-text="formatDateTime(currentInvoice?.sent_at)"></span></div>
                <div>Accepted: <span x-text="formatDateTime(currentInvoice?.accepted_at)"></span></div>
                <div>Rejected: <span x-text="formatDateTime(currentInvoice?.rejected_at)"></span></div>
                <div>Rejected Reason: <span x-text="currentInvoice?.rejection_reason || '-'"></span></div>
                <div>Cancel Reason: <span x-text="currentInvoice?.cancel_reason || '-'"></span></div>
            </div>

            <div class="mt-4 text-sm text-slate-300" x-show="!currentInvoice">
                No invoice attached to this shipment yet.
            </div>

            <div class="mt-4 flex flex-wrap gap-2" x-show="canRespondToInvoice" x-cloak>
                <button type="button" @click="acceptCurrentInvoice()"
                        class="rounded-xl border border-emerald-300/30 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/20">
                    Accept Invoice
                </button>
                <button type="button" @click="rejectCurrentInvoice()"
                        class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-2 text-sm font-semibold text-rose-100 hover:bg-rose-500/20">
                    Reject Invoice
                </button>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
            <h2 class="text-lg font-bold text-white">Pickup Assignment</h2>
            <div class="mt-4 rounded-xl border border-slate-200/10 bg-slate-950/45 p-4 text-sm text-slate-300" x-show="pickupAssignment">
                <div>Status: <span class="font-semibold text-white" x-text="statusLabel(pickupAssignment?.status)"></span></div>
                <div>Driver: <span class="font-semibold text-white" x-text="pickupAssignment?.driver_name || pickupAssignment?.driver?.name || '-'"></span></div>
                <div>Driver Phone: <span x-text="pickupAssignment?.driver_phone || pickupAssignment?.driver?.phone || '-'"></span></div>
                <div>Warehouse: <span x-text="pickupAssignment?.target_warehouse?.name || '-'"></span></div>
                <div>Assignment Notes: <span x-text="pickupAssignment?.notes || '-'"></span></div>
                <div class="mt-3 grid gap-2 text-xs text-slate-400">
                    <div>Assigned: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.assigned?.at)"></span></div>
                    <div>En Route: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.en_route?.at)"></span></div>
                    <div>Arrived Pickup: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.arrived_pickup?.at)"></span></div>
                    <div>Picked Up: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.picked_up?.at)"></span></div>
                    <div>Arrived Warehouse: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.arrived_warehouse?.at)"></span></div>
                    <div>Received: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.received?.at)"></span></div>
                    <div>Completed: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.completed?.at)"></span></div>
                    <div>Cancelled: <span class="text-slate-200" x-text="formatDateTime(pickupAssignment?.timeline?.cancelled?.at)"></span></div>
                    <div>Cancellation Reason: <span class="text-slate-200" x-text="pickupAssignment?.timeline?.cancelled?.reason || '-'"></span></div>
                </div>
            </div>
            <div class="mt-4 text-sm text-slate-300" x-show="!pickupAssignment">
                No pickup assignment available yet.
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading && shipment" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-white">Invoice History</h2>
            <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200">
                <span x-text="invoiceHistory.length"></span> invoice(s)
            </span>
        </div>

        <div class="mt-4 overflow-auto rounded-2xl border border-slate-200/10" x-show="invoiceHistory.length > 0">
            <table class="min-w-full divide-y divide-slate-200/10 text-sm">
                <thead class="bg-slate-900/90">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Invoice #</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Total</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Created</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/10">
                    <template x-for="invoice in invoiceHistory" :key="invoice.id">
                        <tr class="bg-slate-950/40">
                            <td class="px-4 py-3 font-semibold text-white" x-text="invoice.invoice_number"></td>
                            <td class="px-4 py-3 text-slate-200" x-text="statusLabel(invoice.status)"></td>
                            <td class="px-4 py-3 text-slate-200" x-text="formatMoney(invoice.total_amount, invoice.currency || 'GHS')"></td>
                            <td class="px-4 py-3 text-slate-200" x-text="formatDateTime(invoice.created_at)"></td>
                            <td class="px-4 py-3 text-slate-200" x-text="invoice.rejection_reason || invoice.cancel_reason || '-'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200/10 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-300"
             x-show="invoiceHistory.length === 0" x-cloak>
            No invoice history yet.
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading && shipment" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-white">Items</h2>
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200">
                    <span x-text="shipment?.items?.length || 0"></span> item(s)
                </span>
                <button x-show="canEditShipment && !addingItem" type="button" @click="openAddItem()"
                        class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-400">
                    Add Item
                </button>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100" x-show="itemErrors.length" x-cloak>
            <ul class="list-disc pl-5">
                <template x-for="err in itemErrors" :key="err"><li x-text="err"></li></template>
            </ul>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200/10 bg-slate-950/35 p-4" x-show="addingItem" x-cloak>
            <form class="grid gap-3 md:grid-cols-2" @submit.prevent="addItem()">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-300">Description</label>
                    <textarea x-model="itemForm.description" rows="2" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-300">Quantity</label>
                    <input x-model.number="itemForm.quantity" type="number" min="1" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-300">Images (optional)</label>
                    <input type="file" multiple accept="image/*" @change="onItemImagesSelected($event, 'create')" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 outline-none">
                </div>

                <template x-if="isPerItemDestination">
                    <div class="md:col-span-2 grid gap-3 rounded-xl border border-slate-200/10 bg-slate-900/60 p-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Recipient Name</label>
                            <input x-model="itemForm.delivery_recipient_name" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Recipient Phone</label>
                            <input x-model="itemForm.delivery_recipient_phone" @input="onItemPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Confirm Phone</label>
                            <input x-model="itemForm.delivery_recipient_phone_confirm" @input="onItemPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Location Method</label>
                            <select x-model="itemForm.delivery_location_method" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                <option value="dropdown" class="text-slate-900">Region + district</option>
                                <option value="coordinates" class="text-slate-900">Coordinates</option>
                                <option value="gh_post" class="text-slate-900">Ghana Post</option>
                            </select>
                        </div>
                        <div x-show="itemForm.delivery_location_method === 'dropdown'" x-cloak>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Region</label>
                            <select x-model="itemForm.delivery_region_id" @change="onItemRegionChange()" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                <option value="" class="text-slate-900">Select</option>
                                <template x-for="region in regions" :key="region.id"><option :value="region.id" x-text="region.name" class="text-slate-900"></option></template>
                            </select>
                        </div>
                        <div x-show="itemForm.delivery_location_method === 'dropdown'" x-cloak>
                            <label class="mb-1 block text-xs font-medium text-slate-300">District</label>
                            <select x-model="itemForm.delivery_district_id" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                <option value="" class="text-slate-900">Select</option>
                                <template x-for="district in itemDistricts" :key="district.id"><option :value="district.id" x-text="district.name" class="text-slate-900"></option></template>
                            </select>
                        </div>
                        <div x-show="itemForm.delivery_location_method === 'coordinates'" x-cloak>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Latitude</label>
                            <input x-model="itemForm.delivery_latitude" type="number" step="any" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div x-show="itemForm.delivery_location_method === 'coordinates'" x-cloak>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Longitude</label>
                            <input x-model="itemForm.delivery_longitude" type="number" step="any" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div class="md:col-span-2" x-show="itemForm.delivery_location_method === 'gh_post'" x-cloak>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Ghana Post Address</label>
                            <input x-model="itemForm.delivery_gh_post_address" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Town</label>
                            <input x-model="itemForm.delivery_town" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Landmark</label>
                            <input x-model="itemForm.delivery_landmark" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-slate-300">Instructions</label>
                            <textarea x-model="itemForm.delivery_instructions" rows="2" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none"></textarea>
                        </div>
                    </div>
                </template>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-400">Save Item</button>
                    <button type="button" @click="cancelAddItem()" class="rounded-xl border border-slate-300/25 bg-slate-800/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-700">Cancel</button>
                </div>
            </form>
        </div>

        <div class="mt-5 space-y-4" x-show="(shipment?.items || []).length > 0" x-cloak>
            <template x-for="item in (shipment?.items || [])" :key="item.id">
                <div class="rounded-2xl border border-slate-200/10 bg-slate-950/35 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-white" x-text="item.description"></div>
                            <div class="text-sm text-slate-300">Qty <span x-text="item.quantity"></span> - <span x-text="statusLabel(item.status)"></span></div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button x-show="canEditShipment" type="button" @click="openEditItem(item)" class="rounded-lg border border-orange-300/30 px-2.5 py-1 text-xs font-semibold text-orange-100 hover:bg-orange-500/15">Edit</button>
                            <button x-show="canEditShipment" type="button" @click="deleteItem(item)" class="rounded-lg border border-rose-300/30 px-2.5 py-1 text-xs font-semibold text-rose-100 hover:bg-rose-500/15">Delete</button>
                        </div>
                    </div>

                    <div class="mt-2 rounded-xl border border-slate-200/10 bg-slate-900/60 p-3 text-xs text-slate-300" x-show="item.delivery">
                        <div>Recipient: <span class="text-slate-100" x-text="item.delivery?.recipient_name || '-'"></span></div>
                        <div>Phone: <span class="text-slate-100" x-text="item.delivery?.recipient_phone || '-'"></span></div>
                        <div>Region: <span class="text-slate-100" x-text="item.delivery?.location?.region || '-'"></span></div>
                        <div>District: <span class="text-slate-100" x-text="item.delivery?.location?.district || '-'"></span></div>
                        <div>Town: <span class="text-slate-100" x-text="item.delivery?.location?.town || '-'"></span></div>
                        <div>Landmark: <span class="text-slate-100" x-text="item.delivery?.location?.landmark || '-'"></span></div>
                    </div>

                    <div class="mt-2 rounded-xl border border-emerald-300/20 bg-emerald-500/10 p-3 text-xs text-emerald-100" x-show="item.pickup_confirmation">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.1em] text-emerald-200">Pickup Confirmation</div>
                        <div class="mt-2 grid gap-1 sm:grid-cols-2">
                            <div>Expected Qty: <span class="font-semibold" x-text="item.pickup_confirmation?.expected_quantity ?? '-'"></span></div>
                            <div>Confirmed Qty: <span class="font-semibold" x-text="item.pickup_confirmation?.confirmed_quantity ?? '-'"></span></div>
                            <div>Missing Qty: <span class="font-semibold" x-text="item.pickup_confirmation?.missing_quantity ?? '-'"></span></div>
                            <div>Extra Qty: <span class="font-semibold" x-text="item.pickup_confirmation?.extra_quantity ?? '-'"></span></div>
                            <div class="sm:col-span-2">Confirmed At: <span class="font-semibold" x-text="formatDateTime(item.pickup_confirmation?.confirmed_at)"></span></div>
                            <div class="sm:col-span-2">Driver Notes: <span class="font-semibold" x-text="item.pickup_confirmation?.notes || '-'"></span></div>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" x-show="(item.pickup_confirmation?.photos || []).length > 0">
                        <template x-for="photo in (item.pickup_confirmation?.photos || [])" :key="`pickup-${item.id}-${photo.id}`">
                            <div class="rounded-xl border border-emerald-300/20 bg-emerald-500/10 p-2">
                                <img :src="photo.url" :alt="photo.original_name || 'pickup photo'" class="h-24 w-full rounded object-cover">
                                <div class="mt-1 text-[11px] text-emerald-100" x-text="photo.original_name || 'Pickup photo'"></div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" x-show="(item.images || []).length > 0">
                        <template x-for="image in (item.images || [])" :key="image.id">
                            <div class="rounded-xl border border-slate-200/10 bg-slate-900/60 p-2">
                                <img :src="image.url" :alt="image.original_name" class="h-24 w-full rounded object-cover">
                                <div class="mt-1 text-[11px] text-slate-300" x-text="image.original_name"></div>
                                <button x-show="canEditShipment" type="button" @click="deleteItemImage(item, image)" class="mt-1 rounded-lg border border-rose-300/30 px-2 py-1 text-[11px] font-semibold text-rose-100 hover:bg-rose-500/15">Delete</button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2" x-show="canEditShipment">
                        <input type="file" multiple accept="image/*" @change="setInlineUploadFiles(item.id, $event)" class="rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 outline-none">
                        <button type="button" @click="uploadItemImages(item)" :disabled="imageUploadState[item.id]" class="rounded-lg border border-orange-300/30 px-3 py-1.5 text-xs font-semibold text-orange-100 hover:bg-orange-500/15 disabled:opacity-50">
                            <span x-show="!imageUploadState[item.id]">Upload</span>
                            <span x-show="imageUploadState[item.id]" x-cloak>Uploading...</span>
                        </button>
                    </div>

                    <div class="mt-3 rounded-xl border border-slate-200/10 bg-slate-900/60 p-3" x-show="editingItemId === item.id" x-cloak>
                        <div class="text-sm font-semibold text-white">Edit Item</div>
                        <div class="mt-2 rounded-lg border border-rose-300/30 bg-rose-500/10 px-3 py-2 text-xs text-rose-100" x-show="editItemErrors.length">
                            <ul class="list-disc pl-4"><template x-for="err in editItemErrors" :key="err"><li x-text="err"></li></template></ul>
                        </div>
                        <form class="mt-2 grid gap-3 md:grid-cols-2" @submit.prevent="updateItem(item)">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-slate-300">Description</label>
                                <textarea x-model="editItemForm.description" rows="2" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none"></textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-slate-300">Quantity</label>
                                <input x-model.number="editItemForm.quantity" type="number" min="1" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-slate-300">Add Images</label>
                                <input type="file" multiple accept="image/*" @change="onItemImagesSelected($event, 'edit')" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-slate-300">Remove Existing Images</label>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                    <template x-for="image in (item.images || [])" :key="`rm-${item.id}-${image.id}`">
                                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200/10 bg-slate-900/70 px-2 py-1.5 text-xs text-slate-200">
                                            <input type="checkbox" :value="image.id" x-model="editItemForm.remove_image_ids">
                                            <span x-text="image.original_name"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            <template x-if="isPerItemDestination">
                                <div class="md:col-span-2 grid gap-3 rounded-xl border border-slate-200/10 bg-slate-800/45 p-3 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">Recipient Name</label>
                                        <input x-model="editItemForm.delivery_recipient_name" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">Recipient Phone</label>
                                        <input x-model="editItemForm.delivery_recipient_phone" @input="onEditItemPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">Confirm Phone</label>
                                        <input x-model="editItemForm.delivery_recipient_phone_confirm" @input="onEditItemPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">Location Method</label>
                                        <select x-model="editItemForm.delivery_location_method" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                            <option value="dropdown" class="text-slate-900">Region + district</option>
                                            <option value="coordinates" class="text-slate-900">Coordinates</option>
                                            <option value="gh_post" class="text-slate-900">Ghana Post</option>
                                        </select>
                                    </div>
                                    <div x-show="editItemForm.delivery_location_method === 'dropdown'" x-cloak>
                                        <label class="mb-1 block text-xs text-slate-300">Region</label>
                                        <select x-model="editItemForm.delivery_region_id" @change="onEditItemRegionChange()" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                            <option value="" class="text-slate-900">Select</option>
                                            <template x-for="region in regions" :key="region.id"><option :value="region.id" x-text="region.name" class="text-slate-900"></option></template>
                                        </select>
                                    </div>
                                    <div x-show="editItemForm.delivery_location_method === 'dropdown'" x-cloak>
                                        <label class="mb-1 block text-xs text-slate-300">District</label>
                                        <select x-model="editItemForm.delivery_district_id" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                            <option value="" class="text-slate-900">Select</option>
                                            <template x-for="district in editItemDistricts" :key="district.id"><option :value="district.id" x-text="district.name" class="text-slate-900"></option></template>
                                        </select>
                                    </div>
                                    <div x-show="editItemForm.delivery_location_method === 'coordinates'" x-cloak>
                                        <label class="mb-1 block text-xs text-slate-300">Latitude</label>
                                        <input x-model="editItemForm.delivery_latitude" type="number" step="any" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div x-show="editItemForm.delivery_location_method === 'coordinates'" x-cloak>
                                        <label class="mb-1 block text-xs text-slate-300">Longitude</label>
                                        <input x-model="editItemForm.delivery_longitude" type="number" step="any" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div class="md:col-span-2" x-show="editItemForm.delivery_location_method === 'gh_post'" x-cloak>
                                        <label class="mb-1 block text-xs text-slate-300">Ghana Post Address</label>
                                        <input x-model="editItemForm.delivery_gh_post_address" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">Town</label>
                                        <input x-model="editItemForm.delivery_town" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">Landmark</label>
                                        <input x-model="editItemForm.delivery_landmark" type="text" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-xs text-slate-300">Instructions</label>
                                        <textarea x-model="editItemForm.delivery_instructions" rows="2" class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none"></textarea>
                                    </div>
                                </div>
                            </template>

                            <div class="md:col-span-2 flex gap-2">
                                <button type="submit" class="rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-400">Save</button>
                                <button type="button" @click="cancelEditItem()" class="rounded-lg border border-slate-300/30 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5 rounded-xl border border-slate-200/10 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-300"
             x-show="(shipment?.items || []).length === 0" x-cloak>
            No items added yet.
        </div>
    </section>
</main>
@endsection
