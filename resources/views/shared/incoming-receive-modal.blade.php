{{-- Shared Incoming Receive Modal --}}
<template x-teleport="body">
    <template x-if="receiveModal.open && receiveModal.itemIndex >= 0">
        <div
            class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center p-4"
            @@keydown.escape.window="closeReceiveModal()"
        >
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @@click="closeReceiveModal()"></div>

            <div
                @@click.stop
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                class="relative flex max-h-[90dvh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 4.5h2.5v15h-2.5zM9 4.5h1.5v15H9zM13.25 4.5h3v15h-3zM19 4.5h1.25v15H19z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-black text-slate-900">Receive Package</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Confirm the line, quantity, condition, and notes before saving.</p>
                        </div>
                    </div>
                    <button type="button" @@click="closeReceiveModal()" class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="grid gap-5">
                        <div class="space-y-4">
                            <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Package</p>
                                <h4 class="mt-2 text-xl font-black text-slate-950" x-text="items[receiveModal.itemIndex]?.description || 'Package'"></h4>
                                <p class="mt-1 font-mono text-sm font-black text-slate-500" x-text="items[receiveModal.itemIndex]?.tracking_code || 'No tracking code'"></p>
                                <p
                                    x-show="items[receiveModal.itemIndex]?.labels?.length"
                                    class="mt-1 truncate font-mono text-xs font-black text-slate-400"
                                    x-text="labelCodes(items[receiveModal.itemIndex])"
                                ></p>
                                <div class="mt-3 grid gap-2 text-sm font-bold text-slate-600">
                                    <p>
                                        <span class="text-slate-400">Recipient:</span>
                                        <span x-text="items[receiveModal.itemIndex]?.recipient_name || 'No recipient'"></span>
                                        <span x-show="items[receiveModal.itemIndex]?.recipient_phone"> / </span>
                                        <span x-show="items[receiveModal.itemIndex]?.recipient_phone" x-text="items[receiveModal.itemIndex]?.recipient_phone"></span>
                                    </p>
                                    <p>
                                        <span class="text-slate-400">Container:</span>
                                        <template x-if="items[receiveModal.itemIndex]?.container_url">
                                            <a :href="items[receiveModal.itemIndex]?.container_url" class="font-mono text-orange-700 underline decoration-orange-200 underline-offset-2 hover:text-orange-800" x-text="items[receiveModal.itemIndex]?.container_code || 'Unassigned'"></a>
                                        </template>
                                        <template x-if="!items[receiveModal.itemIndex]?.container_url">
                                            <span class="font-mono" x-text="items[receiveModal.itemIndex]?.container_code || 'Unassigned'"></span>
                                        </template>
                                        <span class="text-slate-400"> / </span>
                                        <span x-text="items[receiveModal.itemIndex]?.container_type || 'No container'"></span>
                                    </p>
                                    <p x-show="items[receiveModal.itemIndex]?.manifest_number">
                                        <span class="text-slate-400">Manifest:</span>
                                        <template x-if="items[receiveModal.itemIndex]?.manifest_url">
                                            <a :href="items[receiveModal.itemIndex]?.manifest_url" class="font-mono text-orange-700 underline decoration-orange-200 underline-offset-2 hover:text-orange-800" x-text="items[receiveModal.itemIndex]?.manifest_number"></a>
                                        </template>
                                        <template x-if="!items[receiveModal.itemIndex]?.manifest_url">
                                            <span class="font-mono" x-text="items[receiveModal.itemIndex]?.manifest_number"></span>
                                        </template>
                                    </p>
                                </div>
                                <div class="mt-4 grid grid-cols-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                    <div class="px-3 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Expected</p>
                                        <p class="mt-1 text-xl font-black text-slate-900" x-text="items[receiveModal.itemIndex]?.expected_quantity ?? 0"></p>
                                    </div>
                                    <div class="border-l border-slate-200 px-3 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Loaded</p>
                                        <p class="mt-1 text-xl font-black text-slate-900" x-text="items[receiveModal.itemIndex]?.loaded_quantity ?? 0"></p>
                                    </div>
                                    <div class="border-l border-slate-200 px-3 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Received</p>
                                        <p class="mt-1 text-xl font-black text-emerald-700" x-text="items[receiveModal.itemIndex]?.received_quantity || 0"></p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="space-y-5" x-show="!receiveComplete">
                            <div x-show="!canReceive()" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                                This manifest has not arrived yet, so this package cannot be received.
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-800">Received Quantity</label>
                                    <input
                                        type="number"
                                        min="0"
                                        x-model.number="receiveDraft.received_quantity"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-lg font-black text-slate-900 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                        placeholder="0"
                                    >
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-800">Receipt Status</label>
                                    <select
                                        x-model="receiveDraft.line_status"
                                        class="h-14 w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-900 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                    >
                                        <option value="received">Received</option>
                                        <option value="short">Short</option>
                                        <option value="excess">Excess</option>
                                        <option value="damaged">Damaged</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                            </div>

                            <div
                                x-show="hasDiscrepancy(receiveDraftRow())"
                                class="rounded-3xl border px-4 py-4"
                                :class="discrepancyTone(receiveDraftRow())"
                            >
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black" x-text="discrepancyCopy(receiveDraftRow())"></p>
                                        <p class="mt-1 text-xs font-bold opacity-80">Add a clear note before saving so the final receipt records the issue.</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-800">
                                    Notes
                                    <span x-show="hasDiscrepancy(receiveDraftRow())" class="text-rose-600">*</span>
                                </label>
                                <textarea
                                    rows="4"
                                    x-model="receiveDraft.notes"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100"
                                    :placeholder="hasDiscrepancy(receiveDraftRow()) ? 'Describe the shortage, excess, damage, or exception...' : 'Optional receiving notes...'"
                                ></textarea>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                    <template x-if="!receiveComplete">
                        <div class="grid w-full grid-cols-3 gap-2 sm:flex sm:justify-end">
                            <button type="button" @@click="closeReceiveModal()" class="min-w-0 whitespace-nowrap rounded-2xl border border-slate-200 bg-white px-2 py-3 text-[11px] font-bold text-slate-700 transition hover:bg-slate-100 sm:px-5 sm:text-sm">
                                Cancel
                            </button>
                            <button
                                type="button"
                                @@click="markExpected(receiveModal.itemId)"
                                :disabled="loading || !canReceive()"
                                class="min-w-0 whitespace-nowrap rounded-2xl border border-orange-200 bg-orange-50 px-2 py-3 text-[11px] font-black text-orange-700 transition hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-50 sm:px-5 sm:text-sm"
                            >
                                Receive As Expected
                            </button>
                            <button
                                type="button"
                                @@click="saveItem(receiveModal.itemId)"
                                :disabled="loading || !canReceive()"
                                class="inline-flex min-w-0 items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-slate-900 px-2 py-3 text-[11px] font-black text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 sm:px-6 sm:text-sm"
                            >
                                <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="loading ? 'Saving...' : 'Save Receipt'"></span>
                            </button>
                        </div>
                    </template>

                    <template x-if="receiveComplete">
                        <div class="flex w-full items-center justify-between gap-3">
                            <button type="button" @@click="closeReceiveModal()" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                                Close
                            </button>
                            <button type="button" x-show="showScanNextAction()" @@click="scanNext()" class="rounded-2xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                                Scan Next
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</template>
