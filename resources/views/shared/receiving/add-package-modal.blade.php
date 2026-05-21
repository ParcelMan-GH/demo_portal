@php
    $modal = $modal ?? 'receivingAddPackageModal';
    $closeAction = $closeAction ?? 'closeReceivingAddPackageModal()';
    $saveAction = $saveAction ?? 'addReceivingPackage()';
@endphp

<div x-show="{{ $modal }}.open" x-cloak x-transition.opacity class="fixed inset-0 z-[188] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" style="display:none">
    <div @@click.stop class="flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
        <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8.5l5-3 5 3-5 3-5-3zM4 13l5 3 5-3M10 16l5 3 5-3M4 13l5-3 5 3-5 3-5-3z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Add Package</h3>
                    <p class="mt-1 text-sm text-slate-500">Record package details, recipient info, and receipt photos.</p>
                </div>
            </div>
            <button type="button" @@click="{{ $closeAction }}" :disabled="{{ $modal }}.saving" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Receipt photos <span class="text-rose-500">*</span></label>
                <label class="flex min-w-0 cursor-pointer flex-col gap-3 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-3 py-3 transition hover:border-orange-300 hover:bg-orange-50/40 sm:flex-row sm:items-center sm:justify-between">
                    <span class="min-w-0 max-w-full">
                        <span class="block truncate text-sm font-bold text-slate-700" x-text="receivingReceiptPhotoNames({{ $modal }}) || 'Upload or take package photos'"></span>
                        <span class="block text-xs font-medium text-slate-400">PNG, JPG or WEBP up to 12MB each</span>
                    </span>
                    <span class="inline-flex w-fit shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-black text-orange-700 shadow-sm ring-1 ring-orange-100">Choose</span>
                    <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" capture="environment" multiple class="hidden" :disabled="{{ $modal }}.saving" @@change="setReceivingReceiptPhotos({{ $modal }}, $event.target.files)">
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-12">
                <div class="sm:col-span-9">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Description <span class="text-rose-500">*</span></label>
                    <input x-model="{{ $modal }}.description" :disabled="{{ $modal }}.saving" placeholder="e.g. Extra carton" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Quantity <span class="text-rose-500">*</span></label>
                    <input type="number" min="1" x-model.number="{{ $modal }}.quantity" :disabled="{{ $modal }}.saving" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient name</label>
                        <input x-model="{{ $modal }}.delivery_recipient_name" :disabled="{{ $modal }}.saving" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient phone <span class="text-rose-500">*</span></label>
                        <input x-model="{{ $modal }}.delivery_recipient_phone" :disabled="{{ $modal }}.saving" placeholder="0241234567" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                    <div class="relative sm:col-span-2" @@click.outside="closeReceivingTownSearch({{ $modal }})">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Location <span class="text-rose-500">*</span></label>
                        <input :value="{{ $modal }}._town_query" @@input="updateReceivingTownQuery({{ $modal }}, $event.target.value)" @@focus="{{ $modal }}._town_results.length && ({{ $modal }}._town_open = true)" :disabled="{{ $modal }}.saving" placeholder="Search town or area" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 pr-16 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <div x-show="{{ $modal }}._town_loading" class="absolute inset-y-0 right-10 flex items-center pt-7 text-slate-400" style="display:none">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                        <div x-show="{{ $modal }}._town_open && {{ $modal }}._town_results.length" class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl" style="display:none">
                            <template x-for="town in {{ $modal }}._town_results" :key="`${town.id}-${town.region_id}`">
                                <button type="button" @@click="selectReceivingTownOption({{ $modal }}, town)" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-orange-50">
                                    <span x-text="town.display || town.name"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="{{ $modal }}._town_linked && {{ $modal }}._town_context" class="mt-1 text-[10px] font-medium text-emerald-600" x-text="'Linked to ' + {{ $modal }}._town_context" style="display:none"></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Instructions</label>
                        <textarea x-model="{{ $modal }}.delivery_instructions" :disabled="{{ $modal }}.saving" rows="2" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border-2 border-slate-200 bg-white px-3 py-3">
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Bus station</span>
                        <span class="block text-sm font-black text-slate-900">Send to bus station</span>
                    </span>
                    <input type="checkbox" :checked="{{ $modal }}.delivery_method === 'bus_handoff'" @@change="{{ $modal }}.delivery_method = $event.target.checked ? 'bus_handoff' : 'direct'; if ($event.target.checked) {{ $modal }}.forward_to_warehouse_id = ''" :disabled="{{ $modal }}.saving" class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                </label>

                <div x-show="{{ $modal }}.delivery_method !== 'bus_handoff'" class="rounded-xl border-2 border-orange-100 bg-orange-50/40 px-3 py-3" style="display:none">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-orange-600">Forward to warehouse</label>
                    <select x-model="{{ $modal }}.forward_to_warehouse_id" :disabled="{{ $modal }}.saving" class="w-full rounded-xl border-2 border-orange-100 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">Keep at this warehouse</option>
                        <template x-for="warehouse in addPackageTransferWarehouses()" :key="warehouse.id">
                            <option :value="warehouse.id" x-text="warehouse.code ? `${warehouse.name} / ${warehouse.code}` : warehouse.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
            <button type="button" @@click="{{ $closeAction }}" :disabled="{{ $modal }}.saving" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm">Cancel</button>
            <button type="button" @@click="{{ $saveAction }}" :disabled="{{ $modal }}.saving || !addPackageCanSave({{ $modal }})" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm">
                <svg x-show="{{ $modal }}.saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span x-text="{{ $modal }}.saving ? 'Adding...' : 'Add Package'"></span>
            </button>
        </div>
    </div>
</div>
