<template x-teleport="body">
    <div
        x-show="createContainerModalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
        style="display: none;"
        @@keydown.escape.window="createContainerModalOpen = false"
    >
        <div
            @@click.outside="createContainerModalOpen = false"
            @@click="sortBatchDropdownOpen = false"
            x-transition
            class="flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-visible rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]"
        >
            <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900" x-text="attachContainerId ? 'Attach Batch' : 'Create Container'">Create Container</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            @if($canAttachSortBatch ?? false)
                                Select a sealed batch and preview its packages.
                            @else
                                Create a package grouping for this transfer.
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" @@click="createContainerModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-visible bg-slate-50/70 p-5">
                <div x-show="!attachContainerId">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Container Type</label>
                    <select
                        x-model="containerForm.container_type"
                        class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                    >
                        <option value="box">Box</option>
                        <option value="sack">Sack</option>
                        <option value="carton">Carton</option>
                        <option value="crate">Crate</option>
                        <option value="loose">Loose</option>
                    </select>
                </div>

                @if($canAttachSortBatch ?? false)
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Sort Batch <span class="text-rose-500">*</span></label>
                    <div class="relative" @@click.stop @@click.outside="sortBatchDropdownOpen = false">
                        <div class="relative">
                            <input
                                x-ref="sortBatchSearchInput"
                                type="search"
                                x-model="sortBatchSearch"
                                @@focus="sortBatchDropdownOpen = true"
                                @@input="sortBatchDropdownOpen = true; containerForm.sort_batch_id = ''"
                                placeholder="Search batch number or warehouse..."
                                class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-3 pr-10 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                :class="sortBatchDropdownOpen ? 'rounded-b-none border-orange-400 ring-4 ring-orange-100' : ''"
                            >
                            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-transform" :class="sortBatchDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>

                        <div
                            x-show="sortBatchDropdownOpen"
                            x-cloak
                            x-transition.opacity.duration.100ms
                            class="absolute left-0 right-0 z-40 -mt-0.5 overflow-hidden rounded-b-xl border-2 border-t-0 border-orange-400 bg-white shadow-xl shadow-orange-900/10"
                            style="display: none;"
                        >
                            <div class="max-h-72 overflow-y-auto border-t border-orange-100">
                                <template x-for="batch in filteredSortBatches()" :key="batch.id">
                                    <button
                                        type="button"
                                        @@click="selectSortBatch(batch)"
                                        class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-b-0 hover:bg-orange-50"
                                        :class="String(containerForm.sort_batch_id) === String(batch.id) ? 'bg-orange-50' : ''"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-black text-slate-900" x-text="batch.batch_number"></span>
                                            <span class="mt-0.5 block truncate text-xs font-semibold text-slate-500" x-text="`To ${batch.destination || 'destination warehouse'}${batch.destination_code ? ' / ' + batch.destination_code : ''}`"></span>
                                        </span>
                                        <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-orange-700 ring-1 ring-orange-100" x-text="`${batch.items_count || 0} packages`"></span>
                                    </button>
                                </template>
                                <div x-show="filteredSortBatches().length === 0" class="px-3 py-6 text-center text-sm font-semibold text-slate-400">
                                    No matching batches.
                                </div>
                            </div>
                        </div>
                    </div>
                    <p x-show="sortBatches.length === 0" class="mt-2 text-xs font-semibold text-amber-700">No sealed transfer batches are available.</p>
                </div>

                <div x-show="selectedSortBatch()" x-cloak class="overflow-hidden rounded-2xl border border-orange-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-orange-100 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-900" x-text="selectedSortBatch()?.batch_number"></p>
                            <p class="mt-0.5 text-xs font-semibold text-slate-500" x-text="`To ${selectedSortBatch()?.destination || 'destination warehouse'}`"></p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-orange-700 ring-1 ring-orange-100" x-text="`${selectedSortBatch()?.items_count || 0} packages`"></span>
                    </div>

                    <div class="max-h-56 overflow-y-auto divide-y divide-orange-100 bg-white/70">
                        <template x-for="item in (selectedSortBatch()?.items || [])" :key="item.id">
                            <div class="flex items-start justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-black text-slate-800" x-text="item.description || 'Package'"></p>
                                    <p class="mt-0.5 truncate font-mono text-[11px] font-semibold text-slate-500" x-text="item.tracking_code || item.shipment_number || 'No tracking'"></p>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-400" x-text="[item.recipient_name || item.vendor_name, item.recipient_phone].filter(Boolean).join(' / ')"></p>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-700" x-text="`Qty ${item.quantity || 0}`"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endif

                <div x-show="!attachContainerId">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea
                        x-model="containerForm.notes"
                        rows="3"
                        placeholder="Optional packing notes"
                        class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                    ></textarea>
                </div>
            </div>

            <div class="shrink-0 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="createContainerModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button
                    type="button"
                    @@click="submitCreateContainer()"
                    :disabled="actionLoading || ({{ ($canAttachSortBatch ?? false) ? 'true' : 'false' }} && !containerForm.sort_batch_id)"
                    class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm"
                >
                    <span x-show="!actionLoading" x-text="containerForm.sort_batch_id ? 'Load Batch' : 'Create Container'">Create Container</span>
                    <span x-show="actionLoading">Creating...</span>
                </button>
            </div>
        </div>
    </div>
</template>

<template x-teleport="body">
    <div
        x-show="editContainerNotesModalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
        style="display: none;"
        @@click.self="editContainerNotesModalOpen = false"
        @@keydown.escape.window="editContainerNotesModalOpen = false"
    >
        <div @@click.stop class="flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
            <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-extrabold text-slate-900">Edit Container Notes</h3>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-500" x-text="editContainerNotesForm.container_code || 'Container'"></p>
                    </div>
                </div>
                <button type="button" @@click="editContainerNotesModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50/70 p-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea
                        x-model="editContainerNotesForm.notes"
                        rows="5"
                        placeholder="Optional packing notes"
                        class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                    ></textarea>
                </div>
            </div>

            <div class="shrink-0 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="editContainerNotesModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button
                    type="button"
                    @@click="submitEditContainerNotes()"
                    :disabled="actionLoading"
                    class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm"
                >
                    Save Notes
                </button>
            </div>
        </div>
    </div>
</template>
