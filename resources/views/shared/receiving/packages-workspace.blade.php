@php
    $packagesExpr = $packagesExpr ?? 'receiving.packages';
    $title = $title ?? 'Packages';
    $subtitle = $subtitle ?? 'Open a package to receive it or save delivery changes.';
    $emptyTitle = $emptyTitle ?? 'No packages found for receiving.';
    $emptySubtitle = $emptySubtitle ?? 'Add a package here if more packages arrived during receiving.';
    $showToolbar = $showToolbar ?? false;
    $showAdminPackageControls = $showAdminPackageControls ?? false;
    $showSplitControls = $showSplitControls ?? $showAdminPackageControls;
    $showRemoveControls = $showRemoveControls ?? $showAdminPackageControls;
    $splitVisibleExpr = 'pkg.can_split && (pkg.vendor_photos || []).length > 1';
    $controlsVisibleExpr = $showSplitControls && $showRemoveControls
        ? '(pkg.can_delete || ('.$splitVisibleExpr.'))'
        : ($showSplitControls
            ? $splitVisibleExpr
            : 'pkg.can_delete');
    $showFinalize = $showFinalize ?? true;
    $detailsClick = $detailsClick ?? 'openPackageDetailsModal(pkg)';
    $receiveClick = $receiveClick ?? 'openReceivingPackageModal(pkg, 1)';
    $photosClick = $photosClick ?? 'openReceivingPhotosModal(pkg)';
    $printClick = $printClick ?? 'openReceivingLabelPrintModal(pkg)';
    $finalizeClick = $finalizeClick ?? 'openFinalizeConfirm()';
    $finalizeDisabled = $finalizeDisabled ?? '!canFinalizeReceiving()';
    $finalizeLabelExpr = $finalizeLabelExpr ?? 'finalizeReceivingButtonLabel()';
    $finalizeTitle = $finalizeTitle ?? 'Finalize Receiving';
    $finalizeSubtitle = $finalizeSubtitle ?? 'Mark all packages as received and move shipment to warehouse status.';
@endphp

<section class="space-y-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 shadow-sm ring-1 ring-orange-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
            <div>
                <h3 class="text-lg font-black text-slate-950">{{ $title }}</h3>
                <p class="mt-0.5 max-w-2xl text-sm font-medium text-slate-500">{{ $subtitle }}</p>
                @if($showToolbar)
                    <p x-show="receiving.autoGroupLockReason" class="mt-1 text-[10px] text-amber-600" x-text="receiving.autoGroupLockReason" style="display:none"></p>
                @endif
            </div>
        </div>
        @if($showToolbar)
            <div class="flex flex-wrap items-center justify-start gap-2 sm:justify-end">
                <button type="button"
                        @@click="openReceivingAddPackageModal()"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-orange-600 px-4 text-sm font-black text-white shadow-sm shadow-orange-500/20 transition hover:bg-orange-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Package
                </button>
                <button type="button"
                        x-show="receiving.canAutoGroup || receiving.autoGrouping"
                        @@click="autoGroupReceivingPackagesByPhone()"
                        :disabled="receiving.autoGrouping"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-orange-100 bg-orange-50 px-4 text-sm font-black text-orange-700 shadow-sm transition hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="!receiving.autoGrouping" class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <svg x-show="receiving.autoGrouping" class="h-4 w-4 animate-spin text-orange-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="receiving.autoGrouping ? 'Grouping...' : 'Auto-group by Phone'"></span>
                </button>
                @if($showFinalize)
                    <button @@click="{{ $finalizeClick }}" :disabled="{{ $finalizeDisabled }}"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 text-sm font-black text-white shadow-sm shadow-emerald-500/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="{{ $finalizeLabelExpr }}"></span>
                    </button>
                @endif
                <button type="button"
                        @@click="loadReceiving()"
                        title="Refresh"
                        aria-label="Refresh packages"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        @endif
    </div>

    <div class="grid gap-3 lg:hidden">
        <template x-if="{{ $packagesExpr }}.length === 0">
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center">
                <p class="text-sm font-semibold text-slate-600">{{ $emptyTitle }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $emptySubtitle }}</p>
            </div>
        </template>

        <template x-for="pkg in {{ $packagesExpr }}" :key="'mobile-' + pkg.shipment_item_id">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/25">
                <div class="border-b border-orange-100 bg-orange-50 px-4 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <button type="button"
                                @@click="{{ $detailsClick }}"
                                class="block text-left text-sm font-black leading-snug text-slate-950 underline decoration-slate-300 underline-offset-4"
                                x-text="pkg.description || 'No description'"></button>
                        <p x-show="pkg.tracking_code" class="mt-1 break-all font-mono text-[10px] font-semibold text-slate-400" x-text="pkg.tracking_code"></p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wide"
                          :class="receivingPackageIsReceived(pkg)
                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                            : (pkg.discrepancy_type && pkg.discrepancy_type !== 'none'
                                ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                : 'bg-orange-50 text-orange-700 ring-1 ring-orange-200')"
                          x-text="receivingPackageStatusLabel(pkg)"></span>
                </div>
                </div>

                <div class="m-4 grid grid-cols-3 rounded-2xl border border-slate-200 bg-slate-50/80 text-center">
                    <div class="px-2 py-3">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Expected Qty</p>
                        <p class="mt-1 text-base font-black text-slate-900" x-text="receivingExpectedQuantity(pkg)"></p>
                    </div>
                    <div class="border-x border-slate-100 px-2 py-3">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Received Qty</p>
                        <p class="mt-1 text-base font-black text-emerald-700" x-text="pkg.received_quantity || 0"></p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Remaining Qty</p>
                        <p class="mt-1 text-base font-black text-amber-700" x-text="receivingPendingQuantity(pkg)"></p>
                    </div>
                </div>

                <div class="mx-4 mb-4 space-y-2 rounded-2xl border border-slate-200 bg-white p-3 text-[12px]">
                    <div class="flex items-start gap-2">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
                        <div class="min-w-0">
                            <p class="font-bold text-slate-800" x-text="pkg.delivery_recipient_name || 'No recipient set'"></p>
                            <p x-show="pkg.delivery_recipient_phone" class="break-all text-slate-500" x-text="pkg.delivery_recipient_phone"></p>
                        </div>
                    </div>
                    <div x-show="pkg.delivery_town" class="flex items-start gap-2">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <p class="font-semibold text-slate-600" x-text="pkg.delivery_town"></p>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        <span class="font-bold" :class="pkg.delivery_method === 'bus_handoff' ? 'text-violet-700' : 'text-orange-700'" x-text="pkg.delivery_method === 'bus_handoff' ? 'Bus courier' : 'Direct delivery'"></span>
                        <span :class="receivingPackageIsReceived(pkg) ? receivingConditionTextClass(pkg.condition_status) : 'text-slate-400'" class="font-bold" x-text="receivingPackageIsReceived(pkg) ? receivingConditionLabel(pkg.condition_status) : 'Not checked'"></span>
                        <span class="font-bold" :class="packageCustodyClass(pkg)" x-text="packageCustodySummary(pkg)"></span>
                    </div>
                </div>

                <div class="mx-4 mb-4 grid grid-cols-3 gap-2">
                    <button type="button"
                            @@click="{{ $photosClick }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                        Photos
                    </button>
                    <button type="button"
                            x-show="receivingPackageIsReceived(pkg)"
                            @@click="{{ $printClick }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-50"
                            style="display:none">
                        Print
                    </button>
                    <button type="button"
                            x-show="!receivingPackageIsReceived(pkg)"
                            disabled
                            class="inline-flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5 text-xs font-black text-slate-300"
                            style="display:none">
                        Print
                    </button>
                    <button type="button"
                            @@click="{{ $receiveClick }}"
                            class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-3 py-2.5 text-xs font-black text-white shadow-sm shadow-orange-500/20 transition hover:bg-orange-700">
                        <span x-text="receivingPackageActionLabel(pkg)"></span>
                    </button>
                </div>

                @if($showSplitControls || $showRemoveControls)
                    <div x-show="{{ $controlsVisibleExpr }}" class="mt-3 flex justify-end gap-3 border-t border-slate-100 pt-3" style="display:none">
                        @if($showSplitControls)
	                            <button type="button"
	                                    x-show="{{ $splitVisibleExpr }}"
	                                    @@click="openReceivingSplitModal(pkg)"
	                                    :disabled="!pkg.can_split || (pkg.vendor_photos || []).length < 2"
	                                    class="text-[11px] font-black underline underline-offset-4 disabled:cursor-not-allowed disabled:opacity-40"
	                                    :class="{{ $splitVisibleExpr }} ? 'text-slate-700 decoration-slate-300' : 'text-slate-400 decoration-slate-200'"
	                                    style="display:none">
	                                Split Photos
	                            </button>
                        @endif
                        @if($showRemoveControls)
                            <button type="button"
                                    @@click="removeReceivingPackage(pkg)"
                                    :disabled="!pkg.can_delete"
                                    class="text-[11px] font-black underline underline-offset-4 disabled:cursor-not-allowed disabled:opacity-40"
                                    :class="pkg.can_delete ? 'text-rose-700 decoration-rose-300' : 'text-slate-400 decoration-slate-200'">
                                Remove
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </template>
    </div>

    <div class="hidden lg:block">
        <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/25">
        <div class="overflow-x-auto">
        <table class="min-w-[1220px] w-full divide-y divide-slate-200/60 text-left text-xs">
            <thead class="bg-orange-50">
                <tr>
                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-500">Package</th>
                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wide text-slate-500">Expected Qty</th>
                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wide text-slate-500">Received Qty</th>
                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wide text-slate-500">Remaining Qty</th>
                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wide text-slate-500">Condition</th>
                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-500">Recipient</th>
                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-500">Delivery Method</th>
                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-wide text-slate-500">Custody</th>
                    <th class="w-[360px] px-4 py-3 text-right text-[10px] font-black uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <template x-if="{{ $packagesExpr }}.length === 0">
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <p class="text-sm font-semibold text-slate-600">{{ $emptyTitle }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $emptySubtitle }}</p>
                        </td>
                    </tr>
                </template>
                <template x-for="pkg in {{ $packagesExpr }}" :key="pkg.shipment_item_id">
                    <tr class="align-top transition hover:bg-orange-50/30">
                        <td class="px-4 py-4">
                            <div class="min-w-0 space-y-1">
                                <button type="button"
                                        @@click="{{ $detailsClick }}"
                                        class="block max-w-xs truncate text-left text-xs font-black text-slate-950 underline decoration-slate-300 underline-offset-4 transition-colors hover:text-orange-700 hover:decoration-orange-300"
                                        x-text="pkg.description || 'No description'"></button>
                                <p x-show="pkg.tracking_code" class="mt-0.5 text-[10px] font-mono text-slate-400" x-text="pkg.tracking_code"></p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-black text-slate-700" x-text="receivingExpectedQuantity(pkg)"></span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-black text-emerald-700" x-text="pkg.received_quantity"></span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-black text-amber-700" x-text="receivingPendingQuantity(pkg)"></span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1"
                                  :class="receivingPackageIsReceived(pkg)
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : (pkg.discrepancy_type && pkg.discrepancy_type !== 'none'
                                        ? 'bg-amber-50 text-amber-700 ring-amber-200'
                                        : 'bg-orange-50 text-orange-700 ring-orange-200')"
                                  x-text="receivingPackageStatusLabel(pkg)"></span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span x-show="receivingPackageIsReceived(pkg)"
                                  class="inline-flex items-center justify-center whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1"
                                  :class="pkg.condition_status === 'damaged'
                                    ? 'bg-rose-50 text-rose-700 ring-rose-200'
                                    : (pkg.condition_status === 'partial_damage'
                                        ? 'bg-amber-50 text-amber-700 ring-amber-200'
                                        : 'bg-emerald-50 text-emerald-700 ring-emerald-200')"
                                  x-text="receivingConditionLabel(pkg.condition_status)" style="display:none"></span>
                            <span x-show="!receivingPackageIsReceived(pkg)"
                                  class="text-[10px] font-bold uppercase tracking-wide text-slate-400" style="display:none">-</span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="space-y-1 text-xs">
                                <p class="flex max-w-xs items-center gap-1.5 truncate font-semibold text-slate-800">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
                                    <span class="truncate" x-text="pkg.delivery_recipient_name || 'No recipient set'"></span>
                                </p>
                                <p x-show="pkg.delivery_recipient_phone" class="flex items-center gap-1.5 text-slate-500">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372a1.125 1.125 0 00-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293a1.125 1.125 0 01-1.21.38 12.035 12.035 0 01-7.143-7.143 1.125 1.125 0 01.38-1.21l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 3.102A1.125 1.125 0 005.872 2.25H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                    <span x-text="pkg.delivery_recipient_phone"></span>
                                </p>
                                <p x-show="pkg.delivery_town" class="flex max-w-xs items-center gap-1.5 truncate text-slate-500">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    <span class="truncate" x-text="pkg.delivery_town"></span>
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="space-y-1 text-xs">
                                <p class="flex items-center gap-1.5 font-bold"
                                   :class="pkg.delivery_method === 'bus_handoff' ? 'text-violet-700' : 'text-orange-700'">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h7.5m-7.5 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m13.5 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.875c.621 0 1.125-.504 1.125-1.125V14.25m-17.25 0h17.25m-17.25 0V6.375c0-.621.504-1.125 1.125-1.125h10.5c.621 0 1.125.504 1.125 1.125v7.875"/></svg>
                                    <span x-text="pkg.delivery_method === 'bus_handoff' ? 'Bus courier' : 'Direct delivery'"></span>
                                </p>
                                <p class="flex items-center gap-1.5 font-semibold"
                                   :class="receivingDeliveryFeeClass(pkg)">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m-3-2.818c.696.424 1.626.682 3 .682 2.25 0 3-1.007 3-2.25 0-1.244-.75-2.25-3-2.25s-3-1.006-3-2.25S9.75 6.864 12 6.864c1.374 0 2.304.258 3 .682"/></svg>
                                    <span x-text="receivingDeliveryFeeLabel(pkg)"></span>
                                </p>
                                <p x-show="packageDeliveryStatusLabel(pkg)"
                                   class="flex items-center gap-1.5 font-semibold"
                                   :class="packageDeliveryStatusClass(pkg)">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span x-text="'Status: ' + packageDeliveryStatusLabel(pkg)"></span>
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <button type="button"
                                    @@click="openPackageCustodyModal(pkg)"
                                    :disabled="!packageCustodyCanOpen(pkg)"
                                    class="block text-left disabled:cursor-default">
                                <span class="block truncate text-[11px] font-bold"
                                      :class="packageCustodyClass(pkg)"
                                      x-text="packageCustodySummary(pkg)"></span>
                                <span class="mt-1 block space-y-0.5 text-[10px] text-slate-400">
                                    <template x-for="line in packageCustodyDetailLines(pkg)" :key="line">
                                        <span class="block whitespace-nowrap" x-text="line"></span>
                                    </template>
                                </span>
                            </button>
                        </td>
                        <td class="w-[360px] px-4 py-4 text-right">
                            <div class="flex flex-nowrap items-center justify-end gap-2">
                                <button type="button"
                                        @@click="{{ $receiveClick }}"
                                        class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-xl bg-orange-600 px-3 text-[11px] font-black text-white shadow-sm shadow-orange-500/20 transition hover:bg-orange-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5"/></svg>
                                    <span x-text="receivingPackageActionLabel(pkg)"></span>
                                </button>
                                <button type="button"
                                        @@click="{{ $photosClick }}"
                                        class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 text-[11px] font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.827 6.175A2.31 2.31 0 019.186 4.5h5.628a2.31 2.31 0 012.36 1.675l.365 1.286A1.875 1.875 0 0019.342 8.8H20.25A2.25 2.25 0 0122.5 11.05v6.2a2.25 2.25 0 01-2.25 2.25H3.75a2.25 2.25 0 01-2.25-2.25v-6.2A2.25 2.25 0 013.75 8.8h.908a1.875 1.875 0 001.803-1.339l.366-1.286z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 13.5a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Photos
                                </button>
                                    @if($showSplitControls)
	                                        <button type="button"
	                                                x-show="{{ $splitVisibleExpr }}"
	                                                @@click="openReceivingSplitModal(pkg)"
	                                                :disabled="!pkg.can_split || (pkg.vendor_photos || []).length < 2"
	                                            class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-xl border px-3 text-[11px] font-black transition-colors disabled:cursor-not-allowed disabled:opacity-40"
	                                            :class="{{ $splitVisibleExpr }} ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-950' : 'border-slate-100 bg-slate-50 text-slate-400'"
	                                            style="display:none">
	                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.047 1.124-.047s.751.016 1.124.047c1.131.094 1.976 1.057 1.976 2.192V7.5M8.25 7.5h7.5M8.25 7.5l-.621 8.696A2.25 2.25 0 009.873 18.6h4.254a2.25 2.25 0 002.244-2.404L15.75 7.5"/></svg>
	                                            Split
	                                        </button>
                                    @endif
                                    @if($showRemoveControls)
                                        <button type="button"
                                                @@click="removeReceivingPackage(pkg)"
                                                :disabled="!pkg.can_delete"
                                            class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-xl border px-3 text-[11px] font-black transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                            :class="pkg.can_delete ? 'border-rose-100 bg-white text-rose-700 hover:bg-rose-50 hover:text-rose-900' : 'border-slate-100 bg-slate-50 text-slate-400'">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Remove
                                    </button>
                                @endif
                                <div x-show="receivingPackageIsReceived(pkg)" class="inline-flex" style="display:none">
                                    <button type="button"
                                            @@click="{{ $printClick }}"
                                            class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 text-[11px] font-black text-slate-700 shadow-sm transition-colors hover:bg-slate-50 hover:text-slate-950">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5V6A2.25 2.25 0 019 3.75h6A2.25 2.25 0 0117.25 6v1.5m-10.5 0h10.5m-10.5 0A2.25 2.25 0 004.5 9.75v3A2.25 2.25 0 006.75 15h.75m9.75-7.5A2.25 2.25 0 0119.5 9.75v3A2.25 2.25 0 0117.25 15h-.75m-9 0v5.25h9V15m-9 0h9"/></svg>
                                        Print
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>
        </div>
    </div>

</section>
