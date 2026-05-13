@php
    $loadingExpr = $loadingExpr ?? 'receiving.loading';
    $packagesExpr = $packagesExpr ?? 'receiving.packages';
    $showPickupActions = $showPickupActions ?? true;
    $pickupBadgeLabel = $pickupBadgeLabel ?? null;
    $pickupBadgeClasses = $pickupBadgeClasses ?? 'border border-slate-200 bg-slate-50 text-slate-700';
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
            <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:0ms"></div>
            <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:150ms"></div>
            <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:300ms"></div>
        </div>
    </div>

    <template x-if="!{{ $loadingExpr }}">
        <div class="space-y-5">
            <div class="grid gap-5 lg:grid-cols-2">
                <div class="h-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6.75V15m0 0l-2.25-2.25M9 15l2.25-2.25M15 6.75h.01M18.25 17.25V6.75A2.25 2.25 0 0016 4.5H8a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 008 19.5h8a2.25 2.25 0 002.25-2.25z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-900">Pickup Details</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Pickup contact, driver, and warehouse handoff summary.</p>
                            </div>
                        </div>
                        @if($showPickupActions)
                            <div class="flex shrink-0 flex-wrap justify-end gap-2">
                                <button type="button"
                                        x-show="assignmentHistory.length > 1"
                                        @@click="openAssignmentHistoryModal()"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
                                    Driver History
                                </button>
                                <button type="button"
                                        x-show="canManage"
                                        @@click="openPickupEditModal()"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50">
                                    Edit Pickup
                                </button>
                            </div>
                        @elseif($pickupBadgeLabel)
                            <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-xl px-3 py-2 text-xs font-black uppercase tracking-wide {{ $pickupBadgeClasses }}">
                                {{ $pickupBadgeLabel }}
                            </span>
                        @endif
                    </div>
                    <div class="space-y-2.5 px-5 py-4 text-[12px]">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Location:</p>
                            <p class="min-w-0 font-semibold text-slate-900">
                                <span x-text="pickupLocationSummary()"></span>
                                <span x-show="shipment.pickup_landmark" class="text-slate-500" x-text="' - ' + shipment.pickup_landmark"></span>
                            </p>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Instructions:</p>
                            <p class="min-w-0 font-medium text-slate-700" x-text="shipment.pickup_instructions || '-'"></p>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                            <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Pickup Driver:</p>
                            <p class="min-w-0 font-semibold text-slate-900">
                                <span x-text="assignment ? (assignmentDriverName() + ', ' + assignmentDriverPhone()) : 'Unassigned'"></span>
                                @if($showPickupActions)
                                    <button type="button"
                                            x-show="canCreatePickupAssignment()"
                                            @@click="openAssignPickupDriver()"
                                            class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800">Assign</button>
                                    <button type="button"
                                            x-show="canManage && canEditCurrentAssignment()"
                                            @@click="openEditAssignment()"
                                            class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800">Change</button>
                                @endif
                            </p>
                        </div>
                        @if($showPickupFee)
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Pickup Fee:</p>
                                <p class="min-w-0 font-semibold text-slate-900">
                                    <span x-text="pickupFeeValueLabel()"></span>
                                    <span x-show="pickupFeeStatusLabel()"
                                          class="ml-2 inline-flex rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wide"
                                          :class="pickupFeeStatusClass()"
                                          x-text="pickupFeeStatusLabel()"></span>
                                    <button type="button"
                                            x-show="canManageCharges"
                                            @@click="openPickupFeeModal()"
                                            class="ml-2 text-[10px] font-black text-indigo-600 hover:text-indigo-800"
                                            x-text="pickupFeeActionLabel()"></button>
                                </p>
                            </div>
                        @endif
                        @if($showTargetWarehouse)
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                <p class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-32">Target Warehouse:</p>
                                <p class="min-w-0 font-semibold text-slate-900">
                                    <span x-text="assignmentWarehouseName()"></span>
                                    <span x-show="assignmentWarehouseCode()" class="text-slate-500" x-text="' (' + assignmentWarehouseCode() + ')'"></span>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">Drop-off Type</p>
                                <p class="text-xs text-slate-500">Controls whether delivery destination is shared or set package by package.</p>
                            </div>
                        </div>
                        @if($showDropOffSelect)
                            <div class="flex items-center justify-end">
                                <select :value="{{ $destinationModeExpr }}"
                                        @@change="{{ $destinationModeChange }}"
                                        :disabled="{{ $dropOffSavingExpr }}"
                                        class="min-w-[190px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
                                    <option value="single">One Drop-off</option>
                                    <option value="per_item">Multiple Drop-offs</option>
                                </select>
                            </div>
                        @else
                            <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-slate-700" x-text="isPerItemMode() ? 'Multiple Drop-offs' : 'One Drop-off'"></span>
                        @endif
                    </div>
                    <div class="px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400" x-text="isPerItemMode() ? 'Package destinations' : 'Shared destination'"></p>
                            <p class="mt-1 text-sm font-bold text-slate-900" x-text="isPerItemMode() ? 'Set recipient and location inside each package.' : receivingSharedDestinationSummary()"></p>
                            <p class="mt-0.5 text-xs text-slate-500" x-text="isPerItemMode() ? 'Use this when packages are going to different phone numbers or places.' : 'Use this when the packages go to one recipient or location.'"></p>
                        </div>
                        <button type="button"
                                x-show="{{ $showSharedDestinationEditExpr }}"
                                @@click="{{ $sharedDestinationClick }}"
                                class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-slate-700 underline decoration-slate-300 underline-offset-4 transition-colors hover:text-slate-950 hover:decoration-slate-700">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 7.125L16.875 4.5"/></svg>
                            Edit Shared Destination
                        </button>
                        <span x-show="isPerItemMode()" class="inline-flex shrink-0 text-xs font-bold text-slate-500">Per-package delivery is active</span>
                    </div>
                </div>
            </div>

            @include('shared.receiving.packages-workspace', [
                'packagesExpr' => $packagesExpr,
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
