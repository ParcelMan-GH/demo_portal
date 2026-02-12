@extends('web.layouts.portal')

@section('title', 'View Pickup')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-7xl px-6 py-10" x-data="driverPickupShowPage()" data-pickup-id="{{ $pickupId }}">
    <div class="fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="rounded-xl border px-4 py-3 text-sm shadow-xl"
                 :class="{
                    'border-emerald-300/30 bg-emerald-500/90 text-white': toast.type === 'success',
                    'border-rose-300/30 bg-rose-500/90 text-white': toast.type === 'error',
                    'border-sky-300/30 bg-sky-500/90 text-white': toast.type === 'info'
                 }">
                <div class="flex items-start justify-between gap-3">
                    <span x-text="toast.message"></span>
                    <button type="button" @click="dismissToast(toast.id)" class="text-xs font-semibold text-white/90 hover:text-white">Close</button>
                </div>
            </div>
        </template>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-200">Driver Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Pickup Details</h1>
            <p class="mt-2 text-sm text-slate-300" x-show="pickup" x-cloak>
                <span x-text="pickup?.shipment_number"></span>
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('web.driver.pickups.index') }}" class="rounded-xl border border-slate-200/20 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
                Back to Pickups
            </a>
            <button x-show="canStartEnRoute" type="button" @click="startEnRoute()" :disabled="actionLoading" x-cloak
                    class="rounded-xl border border-sky-300/30 bg-sky-500/10 px-4 py-2 text-sm font-semibold text-sky-100 hover:bg-sky-500/20 disabled:opacity-50">
                Start En Route
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

        <div x-show="validationErrors.length" x-cloak class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
            <ul class="list-disc pl-5">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading && pickup" x-cloak>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div><div class="text-xs text-slate-400">Status</div><div class="text-sm font-semibold text-white" x-text="statusLabel(pickup?.status)"></div></div>
            <div><div class="text-xs text-slate-400">Shipment</div><div class="text-sm font-semibold text-white" x-text="pickup?.shipment_number || '-'"></div></div>
            <div><div class="text-xs text-slate-400">Vendor</div><div class="text-sm font-semibold text-white" x-text="pickup?.shipment?.vendor_name || '-'"></div></div>
            <div><div class="text-xs text-slate-400">Target Warehouse</div><div class="text-sm font-semibold text-white" x-text="pickup?.target_warehouse?.name || '-'"></div></div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200/10 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="font-semibold text-white">Pickup Contact</div>
                <div class="mt-1">Name: <span x-text="pickup?.shipment?.pickup?.contact_name || '-'"></span></div>
                <div>Phone: <span x-text="pickup?.shipment?.pickup?.contact_phone || '-'"></span></div>
                <div>Region: <span x-text="pickup?.shipment?.pickup?.location?.region || '-'"></span></div>
                <div>District: <span x-text="pickup?.shipment?.pickup?.location?.district || '-'"></span></div>
                <div>Town: <span x-text="pickup?.shipment?.pickup?.location?.town || '-'"></span></div>
                <div>Landmark: <span x-text="pickup?.shipment?.pickup?.location?.landmark || '-'"></span></div>
            </div>
            <div class="rounded-xl border border-slate-200/10 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="font-semibold text-white">Timeline</div>
                <div class="mt-1">Assigned: <span x-text="formatDateTime(pickup?.timeline?.assigned?.at)"></span></div>
                <div>En Route: <span x-text="formatDateTime(pickup?.timeline?.en_route?.at)"></span></div>
                <div>Arrived Pickup: <span x-text="formatDateTime(pickup?.timeline?.arrived_pickup?.at)"></span></div>
                <div>Picked Up: <span x-text="formatDateTime(pickup?.timeline?.picked_up?.at)"></span></div>
                <div>Completed: <span x-text="formatDateTime(pickup?.timeline?.completed?.at)"></span></div>
                <div x-show="pickup?.cancellation_reason">Cancel Reason: <span x-text="pickup?.cancellation_reason"></span></div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2" x-show="!loading && pickup" x-cloak>
        <div class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="canArrive">
            <h2 class="text-lg font-bold text-white">Arrive At Pickup Location</h2>
            <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="arriveAtPickup()">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-300">Latitude</label>
                    <input x-model="arriveForm.latitude" type="number" step="any"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-300">Longitude</label>
                    <input x-model="arriveForm.longitude" type="number" step="any"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" :disabled="actionLoading"
                            class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-400 disabled:opacity-50">
                        Mark Arrived
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="canFinalize">
            <h2 class="text-lg font-bold text-white">Finalize Pickup</h2>
            <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="finalizePickup()">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-300">Latitude (optional)</label>
                    <input x-model="finalizeForm.latitude" type="number" step="any"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-300">Longitude (optional)</label>
                    <input x-model="finalizeForm.longitude" type="number" step="any"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-300">Notes (optional)</label>
                    <textarea x-model="finalizeForm.notes" rows="2"
                              class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" :disabled="actionLoading"
                            class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-400 disabled:opacity-50">
                        Finalize Pickup
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading && pickup" x-cloak>
        <h2 class="text-lg font-bold text-white">Pickup Items</h2>
        <p class="mt-1 text-sm text-slate-300">Confirm quantity and upload item photos before finalizing pickup.</p>

        <div class="mt-5 space-y-4" x-show="(pickup?.shipment?.items || []).length > 0">
            <template x-for="item in (pickup?.shipment?.items || [])" :key="item.id">
                <div class="rounded-2xl border border-slate-200/10 bg-slate-950/35 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-white" x-text="item.description"></div>
                            <div class="text-sm text-slate-300">
                                Expected: <span x-text="item.quantity"></span> |
                                Confirmed: <span x-text="item.pickup_confirmation?.confirmed_quantity ?? '-'"></span>
                            </div>
                        </div>
                        <div class="text-xs text-slate-400">
                            Item Status: <span x-text="statusLabel(item.status)"></span>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" x-show="(item.images || []).length > 0">
                        <template x-for="image in (item.images || [])" :key="`vendor-${item.id}-${image.id}`">
                            <div class="rounded-xl border border-slate-200/10 bg-slate-900/60 p-2">
                                <img :src="image.url" :alt="image.original_name" class="h-24 w-full rounded object-cover">
                                <div class="mt-1 text-[11px] text-slate-300" x-text="image.original_name"></div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-3 rounded-xl border border-slate-200/10 bg-slate-900/60 p-3" x-show="item.pickup_confirmation">
                        <div class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-300">Pickup Confirmation</div>
                        <div class="mt-2 text-sm text-slate-300">
                            <div>Expected: <span x-text="item.pickup_confirmation?.expected_quantity"></span></div>
                            <div>Confirmed: <span x-text="item.pickup_confirmation?.confirmed_quantity"></span></div>
                            <div>Missing: <span x-text="item.pickup_confirmation?.missing_quantity"></span></div>
                            <div>Extra: <span x-text="item.pickup_confirmation?.extra_quantity"></span></div>
                            <div>Confirmed At: <span x-text="formatDateTime(item.pickup_confirmation?.confirmed_at)"></span></div>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" x-show="(item.pickup_confirmation?.photos || []).length > 0">
                            <template x-for="photo in (item.pickup_confirmation?.photos || [])" :key="`pickup-${item.id}-${photo.id}`">
                                <div class="rounded-xl border border-slate-200/10 bg-slate-950/50 p-2">
                                    <img :src="photo.url" :alt="photo.original_name" class="h-24 w-full rounded object-cover">
                                    <div class="mt-1 text-[11px] text-slate-300" x-text="photo.original_name"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="confirmItem(item)" x-show="canConfirmItems">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Confirmed Quantity</label>
                            <input :value="itemForms[item.id]?.confirmed_quantity ?? item.quantity"
                                   @input="itemForms[item.id].confirmed_quantity = $event.target.value"
                                   type="number" min="0"
                                   class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-300">Add Photos</label>
                            <input type="file" multiple accept="image/*" @change="onItemPhotosSelected(item.id, $event)"
                                   class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-slate-300">Notes</label>
                            <textarea :value="itemForms[item.id]?.notes || ''"
                                      @input="itemForms[item.id].notes = $event.target.value"
                                      rows="2"
                                      class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none"></textarea>
                        </div>
                        <div class="md:col-span-2" x-show="(item.pickup_confirmation?.photos || []).length > 0">
                            <label class="mb-1 block text-xs font-medium text-slate-300">Remove Existing Pickup Photos</label>
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                <template x-for="photo in (item.pickup_confirmation?.photos || [])" :key="`remove-${item.id}-${photo.id}`">
                                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200/10 bg-slate-900/70 px-2 py-1.5 text-xs text-slate-200">
                                        <input type="checkbox" :value="photo.id" x-model="itemForms[item.id].remove_photo_ids">
                                        <span x-text="photo.original_name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" :disabled="isItemActionLoading(item.id)"
                                    class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-400 disabled:opacity-50">
                                <span x-show="!isItemActionLoading(item.id)">Save Item Confirmation</span>
                                <span x-show="isItemActionLoading(item.id)" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200/10 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-300"
             x-show="(pickup?.shipment?.items || []).length === 0" x-cloak>
            No shipment items found for this pickup.
        </div>
    </section>
</main>
@endsection
