@php
$manifestResourceLabel = $manifestResourceLabel ?? 'Transport Manifest';
$manifestCompletedLabel = $manifestCompletedLabel ?? 'Received';
$hideManifestOrigin = $hideManifestOrigin ?? false;

$statusColors = match($manifest->status) {
    'draft'      => ['badge' => 'bg-slate-500/20 text-slate-300',   'dot' => 'bg-slate-400'],
    'assigned'   => ['badge' => 'bg-blue-500/20 text-blue-300',     'dot' => 'bg-blue-400'],
    'loading'    => ['badge' => 'bg-purple-500/20 text-purple-300', 'dot' => 'bg-purple-400'],
    'in_transit' => ['badge' => 'bg-amber-500/20 text-amber-300',   'dot' => 'bg-amber-400'],
    'arrived'    => ['badge' => 'bg-cyan-500/20 text-cyan-300',     'dot' => 'bg-cyan-400'],
    'received'   => ['badge' => 'bg-emerald-500/20 text-emerald-300', 'dot' => 'bg-emerald-400'],
    'cancelled'  => ['badge' => 'bg-rose-500/20 text-rose-300',     'dot' => 'bg-rose-400'],
    default      => ['badge' => 'bg-slate-500/20 text-slate-300',   'dot' => 'bg-slate-400'],
};

$lineStatusColors = [
    'pending'  => 'bg-slate-100 text-slate-700',
    'loaded'   => 'bg-blue-100 text-blue-700',
    'received' => 'bg-emerald-100 text-emerald-700',
    'short'    => 'bg-amber-100 text-amber-700',
    'excess'   => 'bg-purple-100 text-purple-700',
    'damaged'  => 'bg-rose-100 text-rose-700',
];

$hasManifestItems = $manifest->items->isNotEmpty();
$allManifestItemsLoaded = $hasManifestItems && $manifest->items->every(
    fn ($line) => (int) $line->loaded_quantity >= (int) $line->expected_quantity
        && $line->line_status === \App\Models\TransportManifestItem::LINE_LOADED
);
$anyManifestItemsLoaded = $manifest->items->contains(
    fn ($line) => (int) $line->loaded_quantity > 0 || $line->loaded_at || $line->line_status === \App\Models\TransportManifestItem::LINE_LOADED
);
$canDispatchManifest = $manifest->assigned_driver_id && (
    in_array($manifest->status, ['assigned', 'loading'], true)
    && $allManifestItemsLoaded
    && !$manifest->dispatched_at
);
$canMarkArrived = in_array($manifest->status, ['loading', 'in_transit'], true)
    && $allManifestItemsLoaded
    && (bool) $manifest->dispatched_at;
$canUndoDispatch = $manifest->status === 'in_transit'
    && (bool) $manifest->dispatched_at
    && !$manifest->arrived_at
    && !$manifest->received_at;
$canUndoArrival = $manifest->status === 'arrived'
    && (bool) $manifest->arrived_at
    && !$manifest->received_at;
$canPrintWaybill = $hasManifestItems;
@endphp

<div class="space-y-6"
     x-data="adminTransportManifestShow"
     data-manifest-config="{{ json_encode($manifestConfig, JSON_INVALID_UTF8_SUBSTITUTE) }}"
     data-manifest-status="{{ $manifest->status }}"
     data-manifest-driver="{{ $manifest->assigned_driver_id }}">

    <!-- Hero Section -->
    <section class="relative overflow-visible rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_58%)]"></div>
        </div>
        <div class="relative p-5 sm:p-6">

            <div class="relative flex items-center gap-2 overflow-visible">
                <a href="{{ $manifestIndexUrl }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-xs font-black text-slate-200 transition hover:bg-white/15 sm:gap-2 sm:px-3">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    <span>Back</span>
                </a>

                @if(in_array($manifest->status, ['draft', 'assigned', 'loading', 'in_transit', 'arrived', 'received']) || $canPrintWaybill)
                <div class="ml-auto shrink-0">
                    <div class="relative sm:hidden" @@click.outside="heroActionsOpen = false">
                        <button
                            type="button"
                            @@click="heroActionsOpen = !heroActionsOpen"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-orange-600 px-3 py-2 text-xs font-black text-white shadow-sm shadow-orange-600/15 transition hover:bg-orange-700"
                        >
                            Actions
                            <svg class="h-3.5 w-3.5 transition-transform" :class="heroActionsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div
                            x-show="heroActionsOpen"
                            x-cloak
                            x-transition.opacity.duration.100ms
                            class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-white/10 bg-slate-900 py-1 shadow-2xl shadow-slate-950/40 ring-1 ring-white/10"
                            style="display: none;"
                        >
                            @if(in_array($manifest->status, ['draft', 'assigned']))
                            <button type="button" @@click="heroActionsOpen = false; openAssignDriverModal()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-slate-100 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $manifest->assigned_driver_id ? 'Reassign Rider' : 'Assign Rider' }}
                            </button>
                            @endif

                            @if($manifest->status === 'assigned' && $manifest->assigned_driver_id)
                            <button type="button" @@click="heroActionsOpen = false; openUnassignDriverModal()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-slate-100 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6m9-6-6 6 6 6"/>
                                </svg>
                                Unassign Rider
                            </button>
                            @endif
                            @if($canDispatchManifest)
                            <button type="button" @@click="heroActionsOpen = false; openDispatchModal()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-amber-200 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                                Dispatch
                            </button>
                            @endif

                            @if(in_array($manifest->status, ['assigned', 'loading']) && $manifest->assigned_driver_id && $hasManifestItems)
                                @if($anyManifestItemsLoaded)
                                <button type="button" @@click="heroActionsOpen = false; markAllNotLoaded()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-slate-100 transition hover:bg-white/10">
                                    <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m4-4-4 4 4 4"/>
                                    </svg>
                                    Mark All Unloaded
                                </button>
                                @endif
                                @if(!$allManifestItemsLoaded)
                                <button type="button" @@click="heroActionsOpen = false; markAllLoaded()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-emerald-200 transition hover:bg-white/10">
                                    <svg class="h-4 w-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                    </svg>
                                    Mark All Loaded
                                </button>
                                @endif
                            @endif

                            @if($canMarkArrived)
                            <button type="button" @@click="heroActionsOpen = false; markArrived()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-cyan-200 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 10 2 2 4-4"/>
                                </svg>
                                Mark Arrived
                            </button>
                            @endif

                            @if($canPrintWaybill)
                            <button type="button" @@click="heroActionsOpen = false; printWaybill()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-slate-100 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7v-6z"/>
                                </svg>
                                Print Waybill
                            </button>
                            @endif

                            @if($canUndoDispatch)
                            <button type="button" @@click="heroActionsOpen = false; undoDispatch()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-amber-200 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14 4 9m0 0 5-5M4 9h11a5 5 0 0 1 0 10h-1"/>
                                </svg>
                                Undo Dispatch
                            </button>
                            @endif

                            @if($canUndoArrival)
                            <button type="button" @@click="heroActionsOpen = false; undoArrival()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-cyan-200 transition hover:bg-white/10">
                                <svg class="h-4 w-4 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14 4 9m0 0 5-5M4 9h11a5 5 0 0 1 0 10h-1"/>
                                </svg>
                                Undo Arrival
                            </button>
                            @endif

                            @if($deleteState['deletable'] ?? false)
                            <button type="button" @@click="heroActionsOpen = false; deleteManifest()" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs font-black text-rose-200 transition hover:bg-white/10">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6"/>
                                </svg>
                                Delete
                            </button>
                            @endif

                            <div x-show="actionLoading" x-transition.opacity.duration.150ms class="flex items-center gap-2 px-3 py-2.5 text-xs font-bold text-slate-300" style="display:none;">
                                <svg class="animate-spin h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Processing...
                            </div>
                        </div>
                    </div>

                <div class="hidden shrink-0 items-center justify-end gap-2 sm:flex">
                    @if(in_array($manifest->status, ['draft', 'assigned']))
                    <button
                        type="button"
                        @@click="openAssignDriverModal()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-orange-600 px-2.5 py-2 text-xs font-black text-white shadow-sm shadow-orange-600/15 transition hover:bg-orange-700 sm:px-3"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>{{ $manifest->assigned_driver_id ? 'Reassign' : 'Assign' }}</span>
                        <span class="hidden sm:inline">{{ $manifest->assigned_driver_id ? 'Driver' : 'Driver' }}</span>
                    </button>
                    @endif

                    @if($manifest->status === 'assigned' && $manifest->assigned_driver_id)
                    <button type="button" @@click="openUnassignDriverModal()" class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-xs font-black text-slate-100 transition hover:bg-white/15 sm:px-3">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6m9-6-6 6 6 6"/>
                        </svg>
                        Unassign
                    </button>
                    @endif
                    @if($canDispatchManifest)
                    <button type="button" @@click="openDispatchModal()" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-2.5 py-2 text-xs font-black text-white shadow-sm transition hover:bg-amber-600 sm:px-3">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                        Dispatch
                    </button>
                    @endif

                    @if(in_array($manifest->status, ['assigned', 'loading']) && $manifest->assigned_driver_id && $hasManifestItems)
                        @if($anyManifestItemsLoaded)
                        <button type="button" @@click="markAllNotLoaded()" class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-xs font-black text-slate-100 transition hover:bg-white/15 sm:px-3">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m4-4-4 4 4 4"/>
                            </svg>
                            Mark All Unloaded
                        </button>
                        @endif
                        @if(!$allManifestItemsLoaded)
                        <button type="button" @@click="markAllLoaded()" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-2.5 py-2 text-xs font-black text-white shadow-sm transition hover:bg-emerald-700 sm:px-3">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                            </svg>
                            Mark All Loaded
                        </button>
                        @endif
                    @endif

                    @if($canMarkArrived)
                    <button type="button" @@click="markArrived()" class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 px-2.5 py-2 text-xs font-black text-white shadow-sm transition hover:bg-cyan-700 sm:px-3">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 10 2 2 4-4"/>
                        </svg>
                        Mark Arrived
                    </button>
                    @endif

                    @if($canPrintWaybill)
                    <button type="button" @@click="printWaybill()" class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-xs font-black text-slate-100 transition hover:bg-white/15 sm:px-3">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7v-6z"/>
                        </svg>
                        Waybill
                    </button>
                    @endif

                    @if($canUndoDispatch)
                    <button type="button" @@click="undoDispatch()" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300/20 bg-amber-500/15 px-2.5 py-2 text-xs font-black text-amber-100 transition hover:bg-amber-500/25 sm:px-3">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14 4 9m0 0 5-5M4 9h11a5 5 0 0 1 0 10h-1"/>
                        </svg>
                        Undo Dispatch
                    </button>
                    @endif

                    @if($canUndoArrival)
                    <button type="button" @@click="undoArrival()" class="inline-flex items-center gap-1.5 rounded-xl border border-cyan-300/20 bg-cyan-500/15 px-2.5 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-500/25 sm:px-3">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14 4 9m0 0 5-5M4 9h11a5 5 0 0 1 0 10h-1"/>
                        </svg>
                        Undo Arrival
                    </button>
                    @endif

                    @if($deleteState['deletable'] ?? false)
                    <button
                        type="button"
                        @@click="deleteManifest()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-2.5 py-2 text-xs font-black text-white shadow-sm shadow-rose-600/15 transition hover:bg-rose-700 sm:px-3"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6"/>
                        </svg>
                        <span>Delete</span>
                    </button>
                    @endif

                    <div x-show="actionLoading" x-transition.opacity.duration.150ms class="flex items-center gap-2 text-xs text-slate-300" style="display:none;">
                        <svg class="animate-spin h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Processing...
                    </div>
                </div>
                </div>
                @endif
            </div>

            <div class="relative mt-5 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[760px] lg:shrink">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-950/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">
                                {{ $manifest->destinationWarehouse ? 'To ' . $manifest->destinationWarehouse->name : $manifestResourceLabel . ' Workspace' }}
                            </p>
                            <h1 class="mt-1 break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl xl:text-2xl 2xl:text-3xl">{{ $manifest->manifest_number }}</h1>
                            @php
                                $heroMeta = [];

                                if (! $hideManifestOrigin && $manifest->originWarehouse) {
                                    $heroMeta[] = 'From ' . $manifest->originWarehouse->name;
                                } elseif (! $hideManifestOrigin) {
                                    $heroMeta[] = 'No destination selected';
                                }

                                $heroMeta[] = 'Created by ' . ($manifest->createdBy?->name ?? '—');
                                $heroMeta[] = $manifest->created_at->format('d M Y, h:i A');
                            @endphp
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                @foreach($heroMeta as $metaIndex => $metaText)
                                    @if($metaIndex > 0)
                                        <span class="text-slate-600">/</span>
                                    @endif
                                    <span>{{ $metaText }}</span>
                                @endforeach
                            </div>
                            @if($manifest->assignedDriver || $manifest->sortBatch)
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                                @if($manifest->assignedDriver)
                                    <span>{{ $manifest->assignedDriver->name }}{{ $manifest->assignedDriver->phone ? ' / ' . $manifest->assignedDriver->phone : '' }}</span>
                                @endif
                                @if($manifest->assignedDriver && $manifest->sortBatch)
                                    <span class="text-slate-600">/</span>
                                @endif
                                @if($manifest->sortBatch)
                                    @if($sortBatchUrl ?? null)
                                        <a href="{{ $sortBatchUrl }}" class="text-orange-200 underline decoration-orange-300/40 underline-offset-4 transition hover:text-orange-100">{{ $manifest->sortBatch->batch_number ?? '#' . $manifest->sortBatch->id }}</a>
                                    @else
                                        <span>{{ $manifest->sortBatch->batch_number ?? '#' . $manifest->sortBatch->id }}</span>
                                    @endif
                                @endif
                            </div>
                            @endif

                    </div>
                </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:ml-auto lg:w-[430px] lg:shrink-0 2xl:w-[480px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ $manifest->items->count() }} items</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">{{ (int) $manifest->items->sum('loaded_quantity') }} loaded</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-7 4h8m4-14v16l-2-1-2 1-2-1-2 1-2-1-2 1V4l2 1 2-1 2 1 2-1 2 1 2-1z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ $manifest->containers->count() }} containers</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">{{ $statusLabel }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $timelineEvents = collect($transferTimeline ?? []);
        $latestTimelineEvent = $timelineEvents->last();
    @endphp
    @if($latestTimelineEvent)
    <section x-data="{ timelineOpen: false }" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/30">
        <button
            type="button"
            @@click="timelineOpen = !timelineOpen"
            class="flex w-full items-center justify-between gap-3 px-4 py-4 text-left transition hover:bg-slate-50 sm:px-5"
        >
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Transfer Timeline</p>
                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                    <span class="text-sm font-black text-slate-950">{{ $latestTimelineEvent['label'] }}</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-xs font-bold text-slate-500">{{ $latestTimelineEvent['at_label'] }}</span>
                    @if($latestTimelineEvent['actor'] ?? null)
                        <span class="text-slate-300">/</span>
                        <span class="truncate text-xs font-bold text-slate-500">by {{ $latestTimelineEvent['actor'] }}</span>
                    @endif
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600 sm:inline">{{ $timelineEvents->count() }} events</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500">
                    <svg class="h-4 w-4 transition-transform" :class="timelineOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </span>
            </div>
        </button>

        <div x-show="timelineOpen" x-cloak x-transition.opacity.duration.150ms class="border-t border-slate-100 px-4 py-2 sm:px-5" style="display: none;">
            <div class="divide-y divide-slate-100">
                @foreach($timelineEvents as $event)
                    @php
                        $timelineToneClasses = match($event['tone'] ?? 'slate') {
                            'blue' => 'bg-blue-500',
                            'purple' => 'bg-purple-500',
                            'emerald' => 'bg-emerald-500',
                            'amber' => 'bg-amber-500',
                            'cyan' => 'bg-cyan-500',
                            default => 'bg-slate-400',
                        };
                    @endphp
                    <div class="flex gap-3 py-3">
                        <div class="flex w-3 shrink-0 justify-center pt-1.5">
                            <span class="h-2 w-2 rounded-full {{ $timelineToneClasses }}"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm font-black text-slate-900">{{ $event['label'] }}</p>
                                <p class="text-xs font-bold text-slate-500">{{ $event['at_label'] }}</p>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                                @if($event['actor'] ?? null)
                                    <span>by {{ $event['actor'] }}</span>
                                @endif
                                @if(($event['actor'] ?? null) && ($event['detail'] ?? null))
                                    <span class="text-slate-300">/</span>
                                @endif
                                @if($event['detail'] ?? null)
                                    <span>{{ $event['detail'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @php
        $canManageManifestContainers = in_array($manifest->status, ['draft', 'assigned', 'loading'], true);
        $transferHasPackages = $manifest->items->isNotEmpty() || $manifest->containers->contains(fn ($container) => (int) ($container->items_count ?? 0) > 0);
        $canAttachSortBatch = ($allowSortBatchAttachment ?? false) && $canManageManifestContainers && !$transferHasPackages;
    @endphp

    @include('shared.transport-loading-exceptions-section', ['manifest' => $manifest])

    <!-- Container Board -->
    <section class="space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-black text-slate-950">Containers</h3>
                    <p class="mt-0.5 text-sm font-medium text-slate-500">
                        @if($canAttachSortBatch)
                            Add containers and load batches.
                        @else
                            Arrange packages before dispatch.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end">
                @if($canManageManifestContainers)
                <button type="button" @@click="openCreateContainerModal()" class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-xl bg-orange-600 px-3 text-xs font-black text-white shadow-sm shadow-orange-600/15 transition hover:bg-orange-700 sm:px-4">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Container
                </button>
                @endif
            </div>
        </div>

        <div class="pb-2">
            <div class="grid min-w-full gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($manifest->containers->sortBy('sequence_number') as $container)
                    @php
                        $containerTypeLabel = str($container->container_type)->replace('_', ' ')->title();
                        $containerLinesCount = (int) ($container->items_count ?? 0);
                        $containerQty = (int) ($container->items_sum_expected_quantity ?? 0);
                        $isLooseContainer = strtolower((string) $container->container_type) === 'loose';
                    @endphp
                    <div
                        class="flex min-w-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/30"
                        x-init="initContainerTable({{ $container->id }}, {{ $containerLinesCount }})"
                        @if($canManageManifestContainers)
                        @@dragover.prevent
                        @@drop.prevent="dropItemIntoContainer({{ $container->id }})"
                        @endif
                    >
                        <div class="border-b border-orange-100 bg-orange-50 px-4 py-4 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-base font-black leading-tight text-slate-900">{{ $isLooseContainer ? 'Loose Items' : $containerTypeLabel }}</p>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-orange-700 ring-1 ring-orange-200">{{ $containerLinesCount }} lines - {{ $containerQty }} qty</span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                        <p class="truncate font-mono text-[11px] font-bold text-slate-500">{{ $container->container_code }}</p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                @if($containerLinesCount > 0)
                                <button
                                    type="button"
                                    @@click="toggleContainerSearch({{ $container->id }}); $nextTick(() => containerTable({{ $container->id }}).searchOpen && $refs.containerSearch{{ $container->id }}?.focus())"
                                    class="group relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    :class="containerTable({{ $container->id }}).searchOpen || containerTable({{ $container->id }}).search ? 'border-orange-200 text-orange-700 ring-2 ring-orange-100' : ''"
                                    aria-label="Search packages"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/>
                                    </svg>
                                    <span class="pointer-events-none absolute right-0 top-full z-40 mt-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-visible:opacity-100">Search</span>
                                </button>
                                @endif
                                @if($canManageManifestContainers)
                                <button
                                    type="button"
                                    @@click="openEditContainerNotesModal({{ $container->id }}, @js($container->container_code), @js($container->notes))"
                                    :disabled="actionLoading"
                                    class="group relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                                    aria-label="View and edit container notes"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 11h8M8 15h5M6 3h9l3 3v15H6V3z"/>
                                    </svg>
                                    <span class="pointer-events-none absolute right-0 top-full z-40 mt-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-visible:opacity-100">Notes</span>
                                </button>
                                @endif
                                @if($containerLinesCount > 0)
                                    <button
                                        type="button"
                                        @@click="printContainerLabel({{ $container->id }})"
                                        :disabled="actionLoading"
                                        class="group relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                                        aria-label="Print container label"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0h12v4H8v-4z"/>
                                        </svg>
                                        <span class="pointer-events-none absolute right-0 top-full z-40 mt-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-visible:opacity-100">Print label</span>
                                    </button>
                                @endif

                                @if($canManageManifestContainers && $containerLinesCount === 0 && $manifest->containers->count() > 1)
                                <button
                                    type="button"
                                    @@click="deleteContainer({{ $container->id }}, 0, {{ $manifest->containers->count() }})"
                                    :disabled="actionLoading"
                                    class="group relative inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-100 transition hover:bg-rose-100 disabled:opacity-50"
                                    aria-label="Delete container"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6"/>
                                    </svg>
                                    <span class="pointer-events-none absolute right-0 top-full z-40 mt-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-visible:opacity-100">Delete container</span>
                                </button>
                                @endif

                                </div>
                            </div>
                        </div>

                        <div class="min-h-80 flex-1 bg-white">
                            @if($containerLinesCount > 0)
                                <div>
                                    <div
                                        x-show="containerTable({{ $container->id }}).searchOpen"
                                        x-cloak
                                        x-transition.opacity.duration.150ms
                                        class="relative border-b border-slate-100 bg-white px-4 py-3 sm:px-5"
                                        style="display: none;"
                                    >
                                        <svg class="pointer-events-none absolute left-7 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 sm:left-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/>
                                        </svg>
                                        <input
                                            type="search"
                                            x-ref="containerSearch{{ $container->id }}"
                                            x-model="containerTable({{ $container->id }}).search"
                                            @@input="onContainerSearch({{ $container->id }})"
                                            placeholder="Search package, recipient, phone, label..."
                                            class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-xs font-bold text-slate-700 outline-none transition focus:border-orange-300 focus:ring-4 focus:ring-orange-100"
                                        >
                                    </div>

                                    <div>
                                        <div class="max-h-[34rem] overflow-auto bg-white">
                                            <div class="divide-y divide-slate-100">
                                                <template x-if="containerTable({{ $container->id }}).loading && !containerTable({{ $container->id }}).loaded">
                                                    <div class="px-3 py-8 text-center text-xs font-bold text-slate-400">Loading packages...</div>
                                                </template>
                                                <template x-for="row in containerTable({{ $container->id }}).items" :key="row.id">
                                                    <div
                                                        class="flex items-start justify-between gap-3 px-4 py-3 transition hover:bg-orange-50/50"
                                                        @if($canManageManifestContainers)
                                                        draggable="true"
                                                        @@dragstart="draggedItemId = row.manifest_item_id"
                                                        @@dragend="draggedItemId = null"
                                                        @endif
                                                    >
                                                        <div class="min-w-0 flex-1">
                                                            <p class="text-sm font-black leading-tight text-slate-900">
                                                                <span class="font-mono" x-text="row.tracking_code || 'No tracking'"></span>
                                                                <span class="font-black text-slate-300"> - </span>
                                                                <span x-text="row.description || 'Package'"></span>
                                                            </p>
                                                            <p class="mt-1 text-xs font-semibold text-slate-500">
                                                                <span x-text="row.recipient_name || '-'"></span>
                                                                <span class="text-slate-300"> / </span>
                                                                <span class="font-mono" x-text="row.recipient_phone || 'No phone'"></span>
                                                            </p>
                                                        </div>

                                                        <div class="flex shrink-0 items-center gap-3 pt-0.5 text-right">
                                                            <span class="whitespace-nowrap rounded-full bg-orange-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-orange-700 ring-1 ring-orange-100" x-text="packageCountLabel(row)"></span>
                                                            @if($canManageManifestContainers)
                                                            <div class="relative" x-data="{ open: false }">
                                                                <button
                                                                    type="button"
                                                                    @@click="open = !open"
                                                                    @@keydown.escape.window="open = false"
                                                                    :disabled="actionLoading || containerMoveTargetsFor({{ $container->id }}).length === 0"
                                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40"
                                                                    aria-label="Package actions"
                                                                >
                                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM10 11.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM10 17a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
                                                                    </svg>
                                                                </button>

                                                                <div
                                                                    x-show="open"
                                                                    x-cloak
                                                                    x-transition.opacity.duration.100ms
                                                                    @@click.outside="open = false"
                                                                    class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl shadow-slate-900/10"
                                                                    style="display: none;"
                                                                >
                                                                    <p class="px-3 py-2 text-[10px] font-black uppercase tracking-wide text-slate-400">Move to</p>
                                                                    <template x-for="target in containerMoveTargetsFor({{ $container->id }})" :key="target.id">
                                                                        <button
                                                                            type="button"
                                                                            @@click="open = false; moveRowToContainer(row, target.id)"
                                                                            :disabled="actionLoading"
                                                                            class="flex w-full px-3 py-2 text-left text-xs font-bold text-slate-700 transition hover:bg-orange-50 hover:text-orange-700 disabled:opacity-50"
                                                                            x-text="target.label"
                                                                        ></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="containerTable({{ $container->id }}).loaded && !containerTable({{ $container->id }}).loading && containerTable({{ $container->id }}).items.length === 0">
                                                    <div class="px-3 py-8 text-center text-xs font-bold text-slate-400">No packages match this search.</div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50 px-3 py-2.5 text-[11px] font-bold text-slate-500 sm:px-5">
                                            <span class="min-w-0 truncate" x-text="containerTableSummary({{ $container->id }})"></span>
                                            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                                                <button type="button" @@click="loadContainerItems({{ $container->id }}, containerTable({{ $container->id }}).meta.current_page - 1)" :disabled="containerTable({{ $container->id }}).loading || containerTable({{ $container->id }}).meta.current_page <= 1" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-black text-slate-700 disabled:opacity-40 sm:px-3">Prev</button>
                                                <span class="whitespace-nowrap" x-text="`Page ${containerTable({{ $container->id }}).meta.current_page || 1} of ${containerTable({{ $container->id }}).meta.last_page || 1}`"></span>
                                                <button type="button" @@click="loadContainerItems({{ $container->id }}, containerTable({{ $container->id }}).meta.current_page + 1)" :disabled="containerTable({{ $container->id }}).loading || containerTable({{ $container->id }}).meta.current_page >= containerTable({{ $container->id }}).meta.last_page" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-black text-slate-700 disabled:opacity-40 sm:px-3">Next</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex min-h-80 items-center justify-center px-4 py-8 text-center">
                                    <div class="mx-auto max-w-xs">
                                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-xs font-black text-slate-700">Drop packages here</p>
                                        <p class="mt-1 text-[11px] font-medium text-slate-400">Drag loaded items into this container.</p>
                                        @if($canAttachSortBatch)
                                        <button
                                            type="button"
                                            @@click="openAttachSortBatchModal({{ $container->id }})"
                                            :disabled="actionLoading"
                                            class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-orange-600 px-4 text-xs font-black text-white shadow-lg shadow-orange-600/15 transition hover:bg-orange-700 disabled:opacity-50"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Attach Batch
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex min-h-72 min-w-full items-center justify-center rounded-3xl border border-dashed border-orange-200 bg-white px-6 py-12 text-center shadow-lg shadow-slate-300/30">
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="mt-3 text-sm font-bold text-slate-800">No containers yet</p>
                            <p class="mt-1 text-xs font-medium text-slate-500">Create the first container and select the sort batch to load.</p>
                            @if($canManageManifestContainers)
                            <button type="button" @@click="openCreateContainerModal()" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-orange-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-orange-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create Container
                            </button>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Warehouse Receipt Section -->
    @if($manifest->warehouseReceipt)
    @php $receipt = $manifest->warehouseReceipt; @endphp
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Warehouse Receipt</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Receipt created upon arrival at destination warehouse</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Receipt Status -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Receipt Status</p>
                    @php
                    $receiptBadgeClass = match($receipt->status) {
                        'draft'              => 'bg-slate-100 text-slate-700',
                        'discrepancy_open'   => 'bg-amber-100 text-amber-700',
                        'finalized'          => 'bg-emerald-100 text-emerald-700',
                        default              => 'bg-slate-100 text-slate-700',
                    };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $receiptBadgeClass }}">
                        {{ ucwords(str_replace('_', ' ', $receipt->status)) }}
                    </span>
                </div>

                <!-- Started At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Started At</p>
                    <p class="text-sm text-slate-700">{{ $receipt->started_at ? $receipt->started_at->format('M d, Y H:i') : '—' }}</p>
                    @if($receipt->startedBy)
                        <p class="text-[10px] text-slate-400 mt-0.5">by {{ $receipt->startedBy->name }}</p>
                    @endif
                </div>

                <!-- Finalized At -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Finalized At</p>
                    <p class="text-sm text-slate-700">{{ $receipt->finalized_at ? $receipt->finalized_at->format('M d, Y H:i') : '—' }}</p>
                    @if($receipt->finalizedBy)
                        <p class="text-[10px] text-slate-400 mt-0.5">by {{ $receipt->finalizedBy->name }}</p>
                    @endif
                </div>

                <!-- Discrepancies -->
                <div>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Discrepancies</p>
                    @if($receipt->status === 'discrepancy_open')
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Open discrepancies
                        </span>
                    @else
                        <span class="text-xs text-slate-500">None</span>
                    @endif
                </div>
            </div>

            @if($receipt->notes)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Receipt Notes</p>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $receipt->notes }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    @include('shared.transport-container-modals')

    {{-- ── Assign Rider Modal ──────────────────────────────────────── --}}
    <template x-teleport="body">
        <div
            x-show="assignDriverModalOpen"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
            style="display: none;"
            @@click.self="assignDriverModalOpen = false"
            @@keydown.escape.window="assignDriverModalOpen = false"
        >
            <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-visible rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]" @@click.stop="driverDropdownOpen = false">
                <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">{{ $manifest->assigned_driver_id ? 'Reassign Rider' : 'Assign Rider' }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Select a transport rider for this transfer.</p>
                        </div>
                    </div>
                    <button type="button" @@click="assignDriverModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-4 overflow-visible bg-slate-50/70 p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Rider <span class="text-rose-500">*</span></label>
                        <div class="relative" @@click.stop @@click.outside="driverDropdownOpen = false">
                            <div class="relative">
                                <input
                                    x-ref="driverSearchInput"
                                    type="search"
                                    x-model="driverSearch"
                                    @@focus="driverDropdownOpen = true"
                                    @@input="driverDropdownOpen = true; driverActiveIndex = -1; selectedDriverId = ''; selectedDriverLabel = ''"
                                    @@keydown.arrow-down.prevent="moveDriverFocus(1)"
                                    @@keydown.arrow-up.prevent="moveDriverFocus(-1)"
                                    @@keydown.enter.prevent="selectActiveDriver()"
                                    @@keydown.escape.stop.prevent="driverDropdownOpen = false; driverActiveIndex = -1"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-controls="transport-rider-listbox"
                                    :aria-expanded="driverDropdownOpen"
                                    :aria-activedescendant="driverActiveIndex >= 0 ? `transport-rider-option-${filteredDrivers()[driverActiveIndex]?.id}` : null"
                                    placeholder="Search rider name, phone, vehicle..."
                                    class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-3 pr-10 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                    :class="driverDropdownOpen ? 'rounded-b-none border-orange-400 ring-4 ring-orange-100' : ''"
                                >
                                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-transform" :class="driverDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                                </svg>
                            </div>

                            <div
                                x-show="driverDropdownOpen"
                                x-cloak
                                x-transition.opacity.duration.100ms
                                class="absolute left-0 right-0 z-40 -mt-0.5 overflow-hidden rounded-b-xl border-2 border-t-0 border-orange-400 bg-white shadow-xl shadow-orange-900/10"
                                style="display: none;"
                            >
                                <div id="transport-rider-listbox" role="listbox" aria-label="Transport riders" class="max-h-72 overflow-y-auto border-t border-orange-100">
                                    <template x-for="(driver, index) in filteredDrivers()" :key="driver.id">
                                        <button
                                        type="button"
                                        :id="`transport-rider-option-${driver.id}`"
                                        @@click="selectDriver(driver)"
                                        @@mouseenter="driverActiveIndex = index"
                                        role="option"
                                        :aria-selected="Number(selectedDriverId) === Number(driver.id)"
                                        class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-b-0 hover:bg-orange-50"
                                        :class="Number(currentDriverId) === Number(driver.id) ? 'bg-orange-50 ring-1 ring-inset ring-orange-200' : ((Number(selectedDriverId) === Number(driver.id) || driverActiveIndex === index) ? 'bg-orange-50' : '')"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900" x-text="driver.name"></p>
                                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-500" x-text="driver.meta"></p>
                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                <span x-show="Number(currentDriverId) === Number(driver.id)" class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-black text-orange-700">Assigned here</span>
                                                <span x-show="driver.is_busy && Number(currentDriverId) !== Number(driver.id)" class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-700" x-text="`Busy · ${driver.active_work_count} active ${Number(driver.active_work_count) === 1 ? 'job' : 'jobs'}`"></span>
                                                <span x-show="!driver.is_busy" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-700">Available</span>
                                            </div>
                                            <p x-show="driver.is_busy && Number(currentDriverId) !== Number(driver.id)" class="mt-1 text-[10px] font-semibold text-amber-700" x-text="`${driver.active_work?.pickups || 0} pickups · ${driver.active_work?.transports || 0} transports · ${driver.active_work?.deliveries || 0} deliveries`"></p>
                                        </div>
                                            <svg x-show="Number(selectedDriverId) === Number(driver.id)" class="h-4 w-4 shrink-0 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <div x-show="filteredDrivers().length === 0" class="px-3 py-6 text-center text-sm text-slate-400">
                                        No matching riders.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Reassignment reason <span class="font-semibold normal-case text-slate-400">(optional)</span></label>
                        <textarea x-model="reassignmentReason" maxlength="500" rows="2" placeholder="Add context for the old and new rider..." class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea>
                    </div>
                </div>
                <div class="shrink-0 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button" @@click="assignDriverModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                    <button
                        type="button"
                        @@click="submitAssignDriver()"
                        :disabled="!selectedDriverId || actionLoading"
                        class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm"
                    >
                        {{ $manifest->assigned_driver_id ? 'Reassign Rider' : 'Assign Rider' }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── Unassign Rider Modal ─────────────────────────────────────── --}}
    <div
        x-show="unassignDriverModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
        style="display: none;"
        @@click.self="unassignDriverModalOpen = false"
        @@keydown.escape.window="unassignDriverModalOpen = false"
    >
        <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]" @@click.stop>
            <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-lg shadow-slate-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Unassign Rider</h3>
                        <p class="mt-1 text-sm text-slate-500">Remove the assigned rider and return this transfer to Ready.</p>
                    </div>
                </div>
                <button type="button" @@click="unassignDriverModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50/70 p-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Reason <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                    <textarea
                        x-model="unassignReason"
                        rows="3"
                        placeholder="Enter reason for unassigning..."
                        class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                    ></textarea>
                </div>
            </div>
            <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="unassignDriverModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button
                    type="button"
                    @@click="submitUnassignDriver()"
                    :disabled="actionLoading"
                    class="rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white shadow-lg shadow-slate-900/15 transition hover:border-slate-800 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                >
                    Unassign Rider
                </button>
            </div>
        </div>
    </div>

    {{-- ── Dispatch Modal ────────────────────────────────────────────── --}}
    <div
        x-show="dispatchModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
        style="display: none;"
        @@click.self="dispatchModalOpen = false"
        @@keydown.escape.window="dispatchModalOpen = false"
    >
        <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]" @@click.stop>
            <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-lg shadow-orange-500/25">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Dispatch {{ $manifestResourceLabel }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Confirm packages are ready to leave this warehouse.</p>
                    </div>
                </div>
                <button type="button" @@click="dispatchModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50/70 p-5">
                <div class="flex items-start gap-3 rounded-2xl border border-amber-100 bg-amber-50 p-4">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm font-semibold leading-6 text-amber-800">Dispatching sends this loaded transfer out of the warehouse and records the departure time.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Outgoing Transfer</p>
                    <p class="mt-1 break-words font-mono text-sm font-black text-slate-900">{{ $manifest->manifest_number }}</p>
                </div>
            </div>
            <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="dispatchModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button
                    type="button"
                    @@click="submitDispatch()"
                    :disabled="actionLoading"
                    class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm"
                >
                    Dispatch
                </button>
            </div>
        </div>
    </div>

    {{-- ── Confirm Action Modal ─────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div
            x-show="confirmModalOpen"
            x-cloak
            x-transition.opacity.duration.150ms
            class="fixed inset-0 z-[120] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
            style="display: none;"
            @@click.self="confirmModalOpen = false"
            @@keydown.escape.window="confirmModalOpen = false"
        >
            <div class="relative w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl" @@click.stop>
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                            :class="{
                                'bg-orange-100 text-orange-600': confirmConfig.tone === 'orange',
                                'bg-emerald-100 text-emerald-600': confirmConfig.tone === 'emerald',
                                'bg-amber-100 text-amber-600': confirmConfig.tone === 'amber',
                                'bg-rose-100 text-rose-600': confirmConfig.tone === 'rose'
                            }"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-extrabold text-slate-900" x-text="confirmConfig.title"></h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600" x-text="confirmConfig.message"></p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button" @@click="confirmModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                    <button
                        type="button"
                        @@click="submitConfirm()"
                        class="rounded-xl border-2 px-5 py-3 text-base font-black text-white shadow-lg transition sm:text-sm"
                        :class="{
                            'border-orange-600 bg-orange-600 shadow-orange-600/20 hover:border-orange-700 hover:bg-orange-700': confirmConfig.tone === 'orange',
                            'border-emerald-600 bg-emerald-600 shadow-emerald-600/20 hover:border-emerald-700 hover:bg-emerald-700': confirmConfig.tone === 'emerald',
                            'border-amber-500 bg-amber-500 shadow-amber-500/20 hover:border-amber-600 hover:bg-amber-600': confirmConfig.tone === 'amber',
                            'border-rose-600 bg-rose-600 shadow-rose-600/20 hover:border-rose-700 hover:bg-rose-700': confirmConfig.tone === 'rose'
                        }"
                        x-text="confirmConfig.confirmText"
                    ></button>
                </div>
            </div>
        </div>
    </template>

</div>

@push('scripts')
@php
    $transportDriverOptions = collect($transportDrivers)->map(fn ($driver) => [
        'id' => data_get($driver, 'id'),
        'name' => data_get($driver, 'name'),
        'phone' => data_get($driver, 'phone'),
        'vehicle_type' => data_get($driver, 'vehicle_type'),
        'vehicle_number' => data_get($driver, 'vehicle_number'),
        'status' => data_get($driver, 'status'),
        'is_busy' => (bool) data_get($driver, 'is_busy', false),
        'active_work_count' => (int) data_get($driver, 'active_work_count', 0),
        'active_work' => data_get($driver, 'active_work', ['pickups' => 0, 'transports' => 0, 'deliveries' => 0]),
        'meta' => collect([data_get($driver, 'phone'), data_get($driver, 'vehicle_type'), data_get($driver, 'vehicle_number')])->filter()->join(' · '),
    ])->values();
    $sortBatchOptions = collect($availableSortBatches ?? [])->values();
    $containerMoveTargets = $manifest->containers->sortBy('sequence_number')->map(function ($container) {
        $label = strtolower((string) $container->container_type) === 'loose'
            ? 'Loose Items'
            : str($container->container_type)->replace('_', ' ')->title()->toString();

        return [
            'id' => $container->id,
            'label' => $label . ' · ' . $container->container_code,
        ];
    })->values();
@endphp
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminTransportManifestShow', () => {
        const el = document.querySelector('[data-manifest-config]');
        const config = (() => {
            try { return JSON.parse(el?.getAttribute('data-manifest-config') || '{}'); }
            catch { return {}; }
        })();

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        return {
            // Modal state
            assignDriverModalOpen: false,
            unassignDriverModalOpen: false,
            dispatchModalOpen: false,
            createContainerModalOpen: false,
            editContainerNotesModalOpen: false,
            heroActionsOpen: false,
            actionLoading: false,
            draggedItemId: null,
            confirmModalOpen: false,
            confirmConfig: {
                title: '',
                message: '',
                confirmText: 'Confirm',
                tone: 'orange',
                onConfirm: null,
            },

            // Form values
            selectedDriverId: '',
            selectedDriverLabel: '',
            currentDriverId: Number(el?.dataset.manifestDriver || 0),
            driverSearch: '',
            driverDropdownOpen: false,
            driverActiveIndex: -1,
            reassignmentReason: '',
            drivers: @json($transportDriverOptions),
            sortBatches: @json($sortBatchOptions),
            containerMoveTargets: @json($containerMoveTargets),
            containerTables: {},
            sortBatchSearch: '',
            sortBatchDropdownOpen: false,
            attachContainerId: null,
            unassignReason: '',
            containerForm: {
                container_type: 'box',
                sort_batch_id: '',
                notes: '',
            },
            editContainerNotesForm: {
                container_id: null,
                container_code: '',
                notes: '',
            },

            containerTable(containerId) {
                const key = String(containerId);
                if (!this.containerTables[key]) {
                    this.containerTables[key] = {
                        items: [],
                        search: '',
                        searchOpen: false,
                        loading: false,
                        loaded: false,
                        searchTimer: null,
                        meta: {
                            total: 0,
                            per_page: 50,
                            current_page: 1,
                            last_page: 1,
                            from: 0,
                            to: 0,
                        },
                    };
                }

                return this.containerTables[key];
            },

            initContainerTable(containerId, total = 0) {
                const table = this.containerTable(containerId);
                table.meta.total = Number(total || table.meta.total || 0);
                if (Number(total) > 0 && !table.loaded && !table.loading) {
                    this.loadContainerItems(containerId, 1);
                }
            },

            containerMoveTargetsFor(containerId) {
                return this.containerMoveTargets.filter((target) => Number(target.id) !== Number(containerId));
            },

            containerTableSummary(containerId) {
                const meta = this.containerTable(containerId).meta || {};
                const total = Number(meta.total || 0);
                if (!total) return 'Showing 0 packages';
                return `Showing ${meta.from || 1} to ${meta.to || total} of ${total}`;
            },

            packageCountLabel(row) {
                const labels = Number(row?.labels_count || 0);
                const qty = Number(row?.quantity || 0);
                return `${labels} ${labels === 1 ? 'label' : 'labels'} - ${qty} qty`;
            },

            toggleContainerSearch(containerId) {
                const table = this.containerTable(containerId);
                table.searchOpen = !table.searchOpen;

                if (!table.searchOpen && String(table.search || '').trim()) {
                    table.search = '';
                    this.loadContainerItems(containerId, 1);
                }
            },

            openAssignDriverModal() {
                const current = this.drivers.find((driver) => Number(driver.id) === Number(this.currentDriverId));
                this.selectedDriverId = current?.id || '';
                this.selectedDriverLabel = current?.name || '';
                this.driverSearch = current ? (current.meta ? `${current.name} / ${current.meta}` : current.name) : '';
                this.driverDropdownOpen = false;
                this.reassignmentReason = '';
                this.assignDriverModalOpen = true;
            },

            openUnassignDriverModal() {
                this.unassignReason = '';
                this.unassignDriverModalOpen = true;
            },

            openDispatchModal() {
                this.dispatchModalOpen = true;
            },

            openCreateContainerModal() {
                this.containerForm = { container_type: 'box', sort_batch_id: '', notes: '' };
                this.sortBatchSearch = '';
                this.sortBatchDropdownOpen = false;
                this.attachContainerId = null;
                this.createContainerModalOpen = true;
            },

            openAttachSortBatchModal(containerId) {
                this.containerForm = { container_type: 'box', sort_batch_id: '', notes: '' };
                this.sortBatchSearch = '';
                this.sortBatchDropdownOpen = false;
                this.attachContainerId = Number(containerId);
                this.createContainerModalOpen = true;
            },

            selectedSortBatch() {
                return this.sortBatches.find((batch) => String(batch.id) === String(this.containerForm.sort_batch_id)) || null;
            },

            openEditContainerNotesModal(containerId, containerCode, notes) {
                this.editContainerNotesForm = {
                    container_id: Number(containerId),
                    container_code: containerCode || '',
                    notes: notes || '',
                };
                this.editContainerNotesModalOpen = true;
            },

            filteredSortBatches() {
                const query = String(this.sortBatchSearch || '').trim().toLowerCase();

                if (!query) {
                    return this.sortBatches;
                }

                return this.sortBatches.filter((batch) => {
                    return [
                        batch.batch_number,
                        batch.destination,
                        batch.destination_code,
                        `${batch.items_count || 0} packages`,
                    ].filter(Boolean).some((value) => String(value).toLowerCase().includes(query));
                });
            },

            filteredDrivers() {
                const query = String(this.driverSearch || '').trim().toLowerCase();

                if (!query) {
                    return this.drivers;
                }

                return this.drivers.filter((driver) => {
                    return [driver.name, driver.phone, driver.vehicle_type, driver.vehicle_number]
                        .filter(Boolean)
                        .some((value) => String(value).toLowerCase().includes(query));
                });
            },

            selectDriver(driver) {
                this.selectedDriverId = driver.id;
                this.selectedDriverLabel = driver.name;
                this.driverSearch = driver.meta ? `${driver.name} / ${driver.meta}` : driver.name;
                this.driverDropdownOpen = false;
                this.driverActiveIndex = -1;
            },

            moveDriverFocus(direction) {
                const drivers = this.filteredDrivers();
                if (!drivers.length) return;
                this.driverDropdownOpen = true;
                this.driverActiveIndex = this.driverActiveIndex < 0
                    ? (direction > 0 ? 0 : drivers.length - 1)
                    : (this.driverActiveIndex + direction + drivers.length) % drivers.length;
            },

            selectActiveDriver() {
                const driver = this.filteredDrivers()[this.driverActiveIndex];
                if (driver) this.selectDriver(driver);
            },

            selectSortBatch(batch) {
                this.containerForm.sort_batch_id = batch.id;
                this.sortBatchSearch = `${batch.batch_number} to ${batch.destination || 'destination warehouse'}`;
                this.sortBatchDropdownOpen = false;
            },

            async loadContainerItems(containerId, page = 1) {
                const table = this.containerTable(containerId);
                const endpoint = (config.container_items_endpoint_template || '').replace('__CONTAINER__', containerId);
                if (!endpoint) {
                    window.showToast?.('Missing container package endpoint.', 'error');
                    return;
                }

                const nextPage = Math.max(Number(page || 1), 1);
                table.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: String(nextPage),
                        per_page: String(table.meta.per_page || 50),
                    });
                    const search = String(table.search || '').trim();
                    if (search) {
                        params.set('search', search);
                    }

                    const response = await fetch(`${endpoint}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message || 'Unable to load container packages.');
                    }

                    table.items = result.data || [];
                    table.meta = {
                        total: Number(result.meta?.total || 0),
                        per_page: Number(result.meta?.per_page || table.meta.per_page || 50),
                        current_page: Number(result.meta?.current_page || nextPage),
                        last_page: Number(result.meta?.last_page || 1),
                        from: Number(result.meta?.from || 0),
                        to: Number(result.meta?.to || 0),
                    };
                    table.loaded = true;
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'Unable to load container packages.', 'error');
                } finally {
                    table.loading = false;
                }
            },

            onContainerSearch(containerId) {
                const table = this.containerTable(containerId);
                window.clearTimeout(table.searchTimer);
                table.searchTimer = window.setTimeout(() => this.loadContainerItems(containerId, 1), 250);
            },

            async moveRowToContainer(row, targetContainerId) {
                if (!targetContainerId || !row?.manifest_item_id) {
                    return;
                }
                await this.moveItemToContainer(row.manifest_item_id, targetContainerId);
            },

            async submitAssignDriver() {
                if (!this.selectedDriverId) {
                    window.showToast?.('Please select a rider.', 'warning');
                    return;
                }
                await this._postAction(config.assign_driver_endpoint, {
                    driver_id: Number(this.selectedDriverId),
                    reassignment_reason: this.reassignmentReason.trim() || null,
                }, () => {
                    this.assignDriverModalOpen = false;
                });
            },

            async submitUnassignDriver() {
                await this._postAction(config.unassign_driver_endpoint, { reason: this.unassignReason || null }, () => {
                    this.unassignDriverModalOpen = false;
                });
            },

            async submitDispatch() {
                await this._postAction(config.dispatch_endpoint, {}, () => {
                    this.dispatchModalOpen = false;
                });
            },

            async markItemLoaded(itemId) {
                const endpoint = (config.mark_item_loaded_endpoint_template || '').replace('__ITEM__', itemId);
                if (!endpoint) {
                    window.showToast?.('Missing item load endpoint.', 'error');
                    return;
                }
                await this._postAction(endpoint, {}, null);
            },

            async markItemNotLoaded(itemId) {
                const endpoint = (config.mark_item_not_loaded_endpoint_template || '').replace('__ITEM__', itemId);
                if (!endpoint) {
                    window.showToast?.('Missing item unload endpoint.', 'error');
                    return;
                }
                this.openConfirm({
                    title: 'Mark Item Not Loaded',
                    message: 'This will reverse the loaded state for this manifest item.',
                    confirmText: 'Mark Not Loaded',
                    tone: 'amber',
                    onConfirm: () => this._postAction(endpoint, {}, null),
                });
            },

            async markAllLoaded() {
                this.openConfirm({
                    title: 'Mark All Loaded',
                    message: 'This will mark every item on this manifest as loaded.',
                    confirmText: 'Mark All Loaded',
                    tone: 'emerald',
                    onConfirm: () => this._postAction(config.mark_all_loaded_endpoint, {}, null),
                });
            },

            async markAllNotLoaded() {
                this.openConfirm({
                    title: 'Mark All Unloaded',
                    message: 'This will reverse loading for every item that has not been received yet.',
                    confirmText: 'Mark All Unloaded',
                    tone: 'amber',
                    onConfirm: () => this._postAction(config.mark_all_not_loaded_endpoint, {}, null),
                });
            },

            async markArrived() {
                this.openConfirm({
                    title: 'Mark {{ $manifestResourceLabel }} Arrived',
                    message: 'This records the manifest as arrived at the destination warehouse and opens incoming receiving.',
                    confirmText: 'Mark Arrived',
                    tone: 'emerald',
                    onConfirm: () => this._postAction(config.mark_arrived_endpoint, {}, null),
                });
            },

            async undoDispatch() {
                this.openConfirm({
                    title: 'Undo Dispatch',
                    message: 'This brings the loaded transfer back to this warehouse so you can reassign the rider, unload it, or dispatch later. It is blocked once arrival or receiving starts.',
                    confirmText: 'Undo Dispatch',
                    tone: 'amber',
                    onConfirm: () => this._postAction(config.undo_dispatch_endpoint, {}, null),
                });
            },

            async undoArrival() {
                this.openConfirm({
                    title: 'Undo Arrival',
                    message: 'This removes the arrival time and puts the transfer back in transit. It is blocked once receiving starts.',
                    confirmText: 'Undo Arrival',
                    tone: 'amber',
                    onConfirm: () => this._postAction(config.undo_arrival_endpoint, {}, null),
                });
            },

            async submitCreateContainer() {
                const payload = { ...this.containerForm };
                if (payload.sort_batch_id) {
                    payload.sort_batch_id = Number(payload.sort_batch_id);
                }

                let endpoint = config.create_container_endpoint;
                if (this.attachContainerId) {
                    endpoint = (config.attach_sort_batch_container_endpoint_template || '').replace('__CONTAINER__', this.attachContainerId);
                    delete payload.container_type;
                    delete payload.notes;
                }

                await this._postAction(endpoint, payload, () => {
                    this.createContainerModalOpen = false;
                    this.attachContainerId = null;
                });
            },

            async submitEditContainerNotes() {
                const containerId = this.editContainerNotesForm.container_id;
                if (!containerId) {
                    window.showToast?.('Missing container.', 'error');
                    return;
                }

                const endpoint = (config.update_container_notes_endpoint_template || '').replace('__CONTAINER__', containerId);
                await this._postAction(endpoint, { notes: this.editContainerNotesForm.notes || null }, () => {
                    this.editContainerNotesModalOpen = false;
                });
            },

            async markContainerLoaded(containerId) {
                const endpoint = (config.mark_container_loaded_endpoint_template || '').replace('__CONTAINER__', containerId);
                this.openConfirm({
                    title: 'Mark Container Loaded',
                    message: 'This will mark every item in this container as loaded.',
                    confirmText: 'Mark Loaded',
                    tone: 'emerald',
                    onConfirm: () => this._postAction(endpoint, {}, null),
                });
            },

            async markContainerNotLoaded(containerId) {
                const endpoint = (config.mark_container_not_loaded_endpoint_template || '').replace('__CONTAINER__', containerId);
                this.openConfirm({
                    title: 'Mark Container Not Loaded',
                    message: 'This will reverse the loaded state for this container.',
                    confirmText: 'Mark Not Loaded',
                    tone: 'amber',
                    onConfirm: () => this._postAction(endpoint, {}, null),
                });
            },

            async moveItemToContainer(itemId, containerId) {
                const endpoint = (config.move_item_container_endpoint_template || '').replace('__ITEM__', itemId);
                await this._postAction(endpoint, { container_id: Number(containerId) }, null);
            },

            async dropItemIntoContainer(containerId) {
                if (!this.draggedItemId) return;
                await this._moveItemsToContainer([Number(this.draggedItemId)], Number(containerId), 'Item moved.');
            },

            async _moveItemsToContainer(itemIds, containerId, successMessage) {
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    for (const itemId of itemIds) {
                        const endpoint = (config.move_item_container_endpoint_template || '').replace('__ITEM__', itemId);
                        await this._postJson(endpoint, { container_id: containerId });
                    }
                    window.showToast?.(successMessage || 'Items moved.', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'Unable to move items.', 'error');
                } finally {
                    this.actionLoading = false;
                    this.draggedItemId = null;
                }
            },

            async deleteContainer(containerId, itemCount, containerCount) {
                if (Number(containerCount) <= 1) {
                    window.showToast?.('At least one container must remain on the manifest.', 'warning');
                    return;
                }
                if (Number(itemCount) > 0) {
                    window.showToast?.('Move all items to another container before deleting this one.', 'warning');
                    return;
                }
                const endpoint = (config.delete_container_endpoint_template || '').replace('__CONTAINER__', containerId);
                this.openConfirm({
                    title: 'Delete Container',
                    message: 'This empty container will be removed from the manifest.',
                    confirmText: 'Delete Container',
                    tone: 'rose',
                    onConfirm: () => this._deleteAction(endpoint),
                });
            },

            async deleteManifest() {
                this.openConfirm({
                    title: 'Delete {{ $manifestResourceLabel }}',
                    message: 'This removes the draft manifest and returns its sort batch to a state where a new manifest can be created. Only manifests with no loading or receiving activity can be deleted.',
                    confirmText: 'Delete {{ $manifestResourceLabel }}',
                    tone: 'rose',
                    onConfirm: () => this._deleteAction(config.delete_endpoint, config.index_url),
                });
            },

            async printContainerLabel(containerId) {
                const endpoint = (config.print_container_label_endpoint_template || '').replace('__CONTAINER__', containerId);
                if (!endpoint) {
                    window.showToast?.('Missing print endpoint.', 'error');
                    return;
                }
                await this._printLabel(endpoint);
            },

            async printWaybill() {
                if (!config.print_waybill_endpoint) {
                    window.showToast?.('Missing waybill endpoint.', 'error');
                    return;
                }
                await this._printLabel(config.print_waybill_endpoint);
            },

            async reviewScanIssue(issueId, accept) {
                const template = accept
                    ? config.approve_scan_issue_endpoint_template
                    : config.reject_scan_issue_endpoint_template;
                const endpoint = (template || '').replace('__ISSUE__', issueId);
                const action = accept ? 'accept' : 'reject';
                this.openConfirm({
                    title: accept ? 'Accept Scan Issue' : 'Reject Scan Issue',
                    message: `This will ${action} the scan issue.`,
                    confirmText: accept ? 'Accept Issue' : 'Reject Issue',
                    tone: accept ? 'emerald' : 'rose',
                    onConfirm: () => this._postAction(endpoint, {}, null),
                });
            },

            openConfirm({ title, message, confirmText = 'Confirm', tone = 'orange', onConfirm }) {
                this.confirmConfig = { title, message, confirmText, tone, onConfirm };
                this.confirmModalOpen = true;
            },

            async submitConfirm() {
                const callback = this.confirmConfig.onConfirm;
                this.confirmModalOpen = false;
                this.confirmConfig.onConfirm = null;
                if (typeof callback === 'function') {
                    await callback();
                }
            },

            async _printLabel(endpoint) {
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to generate label.');
                    }

                    const printWindow = window.open('', '_blank');
                    if (!printWindow) {
                        throw new Error('Pop-up blocked. Allow pop-ups to print this document.');
                    }
                    printWindow.document.open();
                    printWindow.document.write(result.data?.label_html || '');
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(() => printWindow.print(), 300);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'Unable to print label.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async _deleteAction(endpoint, redirectUrl = null) {
                if (!endpoint) {
                    window.showToast?.('Missing action endpoint.', 'error');
                    return;
                }
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Action failed.');
                    }
                    window.showToast?.(result.message || 'Done.', 'success');
                    setTimeout(() => {
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                            return;
                        }
                        window.location.reload();
                    }, 800);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'An error occurred.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },

            async _postJson(endpoint, body) {
                if (!endpoint) {
                    throw new Error('Missing action endpoint.');
                }
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify(body),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Action failed.');
                }
                return result;
            },

            async _postAction(endpoint, body, onSuccess) {
                if (!endpoint) {
                    window.showToast?.('Missing action endpoint.', 'error');
                    return;
                }
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify(body),
                    });
                    const result = await response.json();
                    if (response.status === 409 && result.code === 'rider_busy' && !body.confirm_busy_assignment) {
                        const counts = result.data?.active_work || {};
                        const detail = `${counts.pickups || 0} pickup, ${counts.transports || 0} transport, ${counts.deliveries || 0} delivery`;
                        if (window.confirm(`${result.message}\n\nCurrent workload: ${detail}.\n\nAssign this rider anyway?`)) {
                            this.actionLoading = false;
                            return this._postAction(endpoint, { ...body, confirm_busy_assignment: true }, onSuccess);
                        }
                    }
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Action failed.');
                    }
                    window.showToast?.(result.message || 'Done.', 'success');
                    onSuccess?.();
                    // Reload the page so updated status/driver is reflected
                    setTimeout(() => window.location.reload(), 800);
                } catch (err) {
                    console.error(err);
                    window.showToast?.(err.message || 'An error occurred.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
        };
    });
});
</script>
@endpush
