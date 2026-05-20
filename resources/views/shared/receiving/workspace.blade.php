@php
    $loadingExpr = $loadingExpr ?? 'receiving.loading';
    $packagesExpr = $packagesExpr ?? 'receiving.packages';
    $title = $title ?? 'Packages';
    $subtitle = $subtitle ?? 'Open a package to receive it or save delivery changes.';
    $emptyTitle = $emptyTitle ?? 'No packages found for receiving.';
    $emptySubtitle = $emptySubtitle ?? 'Add a package here if more packages arrived during receiving.';
    $showPickupActions = $showPickupActions ?? true;
    $pickupBadgeLabel = $pickupBadgeLabel ?? null;
    $pickupBadgeClasses = $pickupBadgeClasses ?? 'border-orange-200 bg-white text-orange-700';
    $showPickupFee = $showPickupFee ?? true;
    $showTargetWarehouse = $showTargetWarehouse ?? true;
    $showDropOffSelect = $showDropOffSelect ?? true;
    $sharedDestinationClick = $sharedDestinationClick ?? 'openReceivingSharedDestinationModal()';
    $showSharedDestinationEditExpr = $showSharedDestinationEditExpr ?? '!isPerItemMode()';
    $dropOffSavingExpr = $dropOffSavingExpr ?? 'receiving.dropOffSaving';
    $destinationModeExpr = $destinationModeExpr ?? 'shipmentDestinationMode()';
    $destinationModeChange = $destinationModeChange ?? 'handleReceivingDestinationModeChange($event)';
    $showPackageToolbar = $showPackageToolbar ?? true;
    $showAdminPackageControls = $showAdminPackageControls ?? false;
    $showSplitControls = $showSplitControls ?? $showAdminPackageControls;
    $showRemoveControls = $showRemoveControls ?? $showAdminPackageControls;
    $detailsClick = $detailsClick ?? 'openPackageDetailsModal(pkg)';
    $receiveClick = $receiveClick ?? 'openReceivingPackageModal(pkg, 1)';
    $photosClick = $photosClick ?? 'openReceivingPhotosModal(pkg)';
    $printClick = $printClick ?? 'openReceivingLabelPrintModal(pkg)';
    $finalizeClick = $finalizeClick ?? 'openFinalizeConfirm()';
    $finalizeDisabled = $finalizeDisabled ?? '!canFinalizeReceiving()';
    $finalizeLabelExpr = $finalizeLabelExpr ?? 'finalizeReceivingButtonLabel()';
    $finalizeSubtitle = $finalizeSubtitle ?? 'Mark all packages as received and move shipment to warehouse status.';
@endphp

<div>
    <div x-show="{{ $loadingExpr }}" class="flex items-center justify-center py-20">
        <div class="flex gap-1.5">
            <div class="h-2 w-2 animate-bounce rounded-full bg-orange-500" style="animation-delay:0ms"></div>
            <div class="h-2 w-2 animate-bounce rounded-full bg-orange-500" style="animation-delay:150ms"></div>
            <div class="h-2 w-2 animate-bounce rounded-full bg-orange-500" style="animation-delay:300ms"></div>
        </div>
    </div>

    <template x-if="!{{ $loadingExpr }}">
        <div class="space-y-6">
            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-300/20 sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6.75V15m0 0l-2.25-2.25M9 15l2.25-2.25M15 6.75h.01M18.25 17.25V6.75A2.25 2.25 0 0 0 16 4.5H8a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 8 19.5h8a2.25 2.25 0 0 0 2.25-2.25Z"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-500">Pickup</h3>
                                        @if(!$showPickupActions && $pickupBadgeLabel)
                                            <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $pickupBadgeClasses }}">
                                                {{ $pickupBadgeLabel }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm font-black text-slate-950">
                                        <span x-text="pickupLocationSummary()"></span>
                                        <span x-show="shipment.pickup_landmark" class="text-slate-500" x-text="' - ' + shipment.pickup_landmark"></span>
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                                        <span>Rider: <strong class="font-black text-slate-800" x-text="assignment ? assignmentDriverName() : 'Unassigned'"></strong></span>
                                        <span x-show="assignmentDriverPhone()" class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-flex"></span>
                                        <span x-show="assignmentDriverPhone()" x-text="assignmentDriverPhone()"></span>
                                        <span x-show="shipment.pickup_instructions" class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-flex"></span>
                                        <span x-show="shipment.pickup_instructions" class="min-w-0 truncate" x-text="shipment.pickup_instructions"></span>
                                    </div>
                                </div>
                            </div>
                            @if($showPickupActions)
                                <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                                    <button type="button" x-show="assignmentHistory.length > 1" @@click="openAssignmentHistoryModal()" class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        Rider History
                                    </button>
                                    <button type="button" x-show="canManage" @@click="openPickupEditModal()" class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        Edit Pickup
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($showPickupFee || $showTargetWarehouse)
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                @if($showPickupFee)
                                    <button type="button" x-show="canManageCharges" @@click="openPickupFeeModal()" class="inline-flex h-8 items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 font-black text-slate-700">
                                        <span x-text="pickupFeeValueLabel()"></span>
                                        <span x-show="pickupFeeStatusLabel()" class="rounded-full border px-1.5 py-0.5 text-[9px] uppercase tracking-wide" :class="pickupFeeStatusClass()" x-text="pickupFeeStatusLabel()"></span>
                                    </button>
                                    <span x-show="!canManageCharges" class="inline-flex h-8 items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 font-black text-slate-700">
                                        <span x-text="pickupFeeValueLabel()"></span>
                                        <span x-show="pickupFeeStatusLabel()" class="rounded-full border px-1.5 py-0.5 text-[9px] uppercase tracking-wide" :class="pickupFeeStatusClass()" x-text="pickupFeeStatusLabel()"></span>
                                    </span>
                                @endif
                                @if($showTargetWarehouse)
                                    <span class="inline-flex h-8 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 font-black text-slate-700">
                                        <span x-text="assignmentWarehouseName()"></span>
                                        <span x-show="assignmentWarehouseCode()" class="text-slate-500" x-text="' (' + assignmentWarehouseCode() + ')'"></span>
                                    </span>
                                @endif
                            </div>
                        @endif
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-300/20 sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20 3.553 17.276A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13 6-3m-6 3V7m6 10 4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-500">Drop-off</h3>
                                        @if($showDropOffSelect)
                                            <select :value="{{ $destinationModeExpr }}" @@change="{{ $destinationModeChange }}" :disabled="{{ $dropOffSavingExpr }}" class="h-8 rounded-xl border border-orange-200 bg-white px-2.5 text-[11px] font-black text-slate-800 shadow-sm outline-none transition focus:border-orange-300 focus:ring-4 focus:ring-orange-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
                                                <option value="single">One Drop-off</option>
                                                <option value="per_item">Multiple Drop-offs</option>
                                            </select>
                                        @else
                                            <span class="inline-flex h-7 shrink-0 items-center whitespace-nowrap rounded-full border border-orange-200 bg-orange-50 px-2.5 text-[10px] font-black uppercase tracking-wide text-orange-700" x-text="isPerItemMode() ? 'Multiple Drop-offs' : 'One Drop-off'"></span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm font-black text-slate-950" x-text="isPerItemMode() ? 'Package destinations' : receivingSharedDestinationSummary()"></p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500" x-text="isPerItemMode() ? 'Each package carries its own recipient details.' : 'Shared recipient details apply to all packages.'"></p>
                                </div>
                            </div>
                            <button type="button" x-show="{{ $showSharedDestinationEditExpr }}" @@click="{{ $sharedDestinationClick }}" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-950">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487 18.55 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125 16.875 4.5"/></svg>
                                Edit
                            </button>
                        </div>
                </div>
            </section>

            @include('shared.receiving.packages-workspace', [
                'packagesExpr' => $packagesExpr,
                'title' => $title,
                'subtitle' => $subtitle,
                'emptyTitle' => $emptyTitle,
                'emptySubtitle' => $emptySubtitle,
                'showToolbar' => $showPackageToolbar,
                'showAdminPackageControls' => $showAdminPackageControls,
                'showSplitControls' => $showSplitControls,
                'showRemoveControls' => $showRemoveControls,
                'detailsClick' => $detailsClick,
                'receiveClick' => $receiveClick,
                'photosClick' => $photosClick,
                'printClick' => $printClick,
                'finalizeClick' => $finalizeClick,
                'finalizeDisabled' => $finalizeDisabled,
                'finalizeLabelExpr' => $finalizeLabelExpr,
                'finalizeSubtitle' => $finalizeSubtitle,
            ])
        </div>
    </template>
</div>
