@extends('web.layouts.portal')

@section('title', 'Create Shipment')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-6xl px-6 py-10" x-data="vendorShipmentFormPage()" data-mode="create">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Create Shipment</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('web.vendor.invoices.index') }}" class="rounded-xl border border-sky-300/30 bg-sky-500/15 px-4 py-2 text-sm font-semibold text-sky-100 hover:bg-sky-500/25">
                Invoices
            </a>
            <a href="{{ route('web.vendor.shipments.index') }}" class="rounded-xl border border-slate-200/20 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
                Back to Shipments
            </a>
        </div>
    </div>

    <div class="mt-6" x-show="alert" x-cloak>
        <div class="rounded-xl border px-4 py-3 text-sm"
             :class="{
                'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': alert?.type === 'success',
                'border-rose-300/30 bg-rose-500/10 text-rose-100': alert?.type === 'error'
             }">
            <span x-text="alert?.message"></span>
        </div>
    </div>

    <div class="mt-4" x-show="validationErrors.length" x-cloak>
        <div class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
            <div class="font-semibold">Please fix the following:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading" x-cloak>
        <form class="space-y-6" @submit.prevent="saveShipment()">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-100">Destination Mode</label>
                <select x-model="form.destination_mode" @change="onDestinationModeChange()"
                        class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <template x-for="mode in destinationModeOptions" :key="mode.value">
                        <option :value="mode.value" :label="mode.label" class="text-slate-900"></option>
                    </template>
                </select>
            </div>

            <div class="rounded-2xl border border-slate-200/10 bg-slate-950/35 p-4">
                <h2 class="text-lg font-bold text-white">Pickup Details</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Contact Name</label>
                        <input x-model="form.pickup_contact_name" type="text"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Contact Phone</label>
                        <input x-model="form.pickup_contact_phone" @input="onPickupPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Confirm Contact Phone</label>
                        <input x-model="form.pickup_contact_phone_confirm" @input="onPickupPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Location Method</label>
                        <select x-model="form.pickup_location_method"
                                class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                            <template x-for="method in locationMethodOptions" :key="method.value">
                                <option :value="method.value" :label="method.label" class="text-slate-900"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="form.pickup_location_method === 'dropdown'" x-cloak>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup Region</label>
                        <select x-model="form.pickup_region_id" @change="onPickupRegionChange()"
                                class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                            <option value="" class="text-slate-900">Select region</option>
                            <template x-for="region in regions" :key="region.id">
                                <option :value="region.id" x-text="region.name" class="text-slate-900"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup District</label>
                        <select x-model="form.pickup_district_id"
                                class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                            <option value="" class="text-slate-900">Select district</option>
                            <template x-for="district in pickupDistricts" :key="district.id">
                                <option :value="district.id" x-text="district.name" class="text-slate-900"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="form.pickup_location_method === 'coordinates'" x-cloak>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup Latitude</label>
                        <input x-model="form.pickup_latitude" type="number" step="any"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup Longitude</label>
                        <input x-model="form.pickup_longitude" type="number" step="any"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                </div>

                <div class="mt-4" x-show="form.pickup_location_method === 'gh_post'" x-cloak>
                    <label class="mb-2 block text-sm font-medium text-slate-200">Ghana Post Address</label>
                    <input x-model="form.pickup_gh_post_address" type="text"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup Town</label>
                        <input x-model="form.pickup_town" type="text"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup Landmark</label>
                        <input x-model="form.pickup_landmark" type="text"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-200">Pickup Instructions</label>
                        <textarea x-model="form.pickup_instructions" rows="3"
                                  class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35"></textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/10 bg-slate-950/35 p-4" x-show="form.destination_mode === 'single'" x-cloak>
                <h2 class="text-lg font-bold text-white">Delivery Details (Single Destination)</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Recipient Name</label>
                        <input x-model="form.delivery_recipient_name" type="text"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Recipient Phone</label>
                        <input x-model="form.delivery_recipient_phone" @input="onDeliveryPhoneInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Confirm Recipient Phone</label>
                        <input x-model="form.delivery_recipient_phone_confirm" @input="onDeliveryPhoneConfirmInput($event)" type="tel" inputmode="tel" maxlength="13" spellcheck="false"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Location Method</label>
                        <select x-model="form.delivery_location_method"
                                class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                            <template x-for="method in locationMethodOptions" :key="method.value">
                                <option :value="method.value" :label="method.label" class="text-slate-900"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="form.delivery_location_method === 'dropdown'" x-cloak>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery Region</label>
                        <select x-model="form.delivery_region_id" @change="onDeliveryRegionChange()"
                                class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                            <option value="" class="text-slate-900">Select region</option>
                            <template x-for="region in regions" :key="region.id">
                                <option :value="region.id" x-text="region.name" class="text-slate-900"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery District</label>
                        <select x-model="form.delivery_district_id"
                                class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                            <option value="" class="text-slate-900">Select district</option>
                            <template x-for="district in deliveryDistricts" :key="district.id">
                                <option :value="district.id" x-text="district.name" class="text-slate-900"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="form.delivery_location_method === 'coordinates'" x-cloak>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery Latitude</label>
                        <input x-model="form.delivery_latitude" type="number" step="any"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery Longitude</label>
                        <input x-model="form.delivery_longitude" type="number" step="any"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                </div>

                <div class="mt-4" x-show="form.delivery_location_method === 'gh_post'" x-cloak>
                    <label class="mb-2 block text-sm font-medium text-slate-200">Ghana Post Address</label>
                    <input x-model="form.delivery_gh_post_address" type="text"
                           class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery Town</label>
                        <input x-model="form.delivery_town" type="text"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery Landmark</label>
                        <input x-model="form.delivery_landmark" type="text"
                               class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-200">Delivery Instructions</label>
                        <textarea x-model="form.delivery_instructions" rows="3"
                                  class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2.5 text-sm text-white outline-none focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" :disabled="saving"
                        class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!saving">Create Shipment</span>
                    <span x-show="saving" x-cloak>Saving...</span>
                </button>
                <a href="{{ route('web.vendor.shipments.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300/25 bg-slate-800/70 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                    Cancel
                </a>
            </div>
        </form>
    </section>
</main>
@endsection
