@extends($layoutName ?? 'admin.layouts.app')

@section('title', 'Sort Batches')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Sort Batches')

@section('content')
@php
    $sortBatchConfig = $sortBatchConfig ?? [
        'dataUrl' => route('admin.sort-batches.data'),
        'exportUrl' => route('admin.sort-batches.export'),
        'storeUrl' => route('admin.sort-batches.store'),
        'showUrlTemplate' => route('admin.sort-batches.show', ['batch' => '__ID__']),
        'deleteUrlTemplate' => route('admin.sort-batches.destroy', ['batch' => '__ID__']),
        'manifestShowUrlTemplate' => route('admin.transport-manifests.show', ['manifest' => '__ID__']),
        'deliveryRunShowUrlTemplate' => route('admin.delivery-runs.show', ['run' => '__ID__']),
        'originWarehouseId' => null,
        'warehouseColumnLabel' => 'Warehouse',
    ];
@endphp

<div class="space-y-6" x-data="sortBatchesTable">
    <!-- Sort Batches Datatable -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <!-- Card Header -->
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Sort Batches</h2>
                        <p class="mt-0.5 text-sm font-medium text-slate-500">View and manage warehouse sort batches</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-black text-orange-700" x-text="meta.total + ' total'"></span>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="space-y-4 border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                <!-- Search + Filters -->
                <div class="grid gap-3 sm:grid-cols-4">

                    <!-- Search -->
                    <div class="relative sm:col-span-1">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="loadData()"
                            placeholder="Search batch number..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 pr-10 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                        >
                        <svg class="absolute right-3 top-3.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="inline-flex w-full items-center justify-between rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                        >
                            <span x-text="statusFilterName || 'All statuses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 z-50 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="statusFilter = ''; statusFilterName = ''; loadData(); open = false"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50"
                                :class="statusFilter === '' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="statusFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All statuses</span>
                            </button>
                            @foreach($statuses as $status)
                            <button
                                type="button"
                                @@click="statusFilter = '{{ $status['value'] }}'; statusFilterName = '{{ $status['label'] }}'; loadData(); open = false"
                                class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50"
                                :class="statusFilter === '{{ $status['value'] }}' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="statusFilter === '{{ $status['value'] }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $status['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dispatch Mode Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="inline-flex w-full items-center justify-between rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                        >
                            <span x-text="dispatchModeFilterName || 'All modes'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 z-50 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="dispatchModeFilter = ''; dispatchModeFilterName = ''; loadData(); open = false"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50"
                                :class="dispatchModeFilter === '' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="dispatchModeFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All modes</span>
                            </button>
                            @foreach($dispatchModes as $mode)
                            <button
                                type="button"
                                @@click="dispatchModeFilter = '{{ $mode['value'] }}'; dispatchModeFilterName = '{{ $mode['label'] }}'; loadData(); open = false"
                                class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50"
                                :class="dispatchModeFilter === '{{ $mode['value'] }}' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="dispatchModeFilter === '{{ $mode['value'] }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $mode['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Warehouse Filter -->
                    <div x-show="!config.originWarehouseId" x-data="{ open: false }" class="relative w-full sm:w-52">
                        <button
                            type="button"
                            @@click="open = !open"
                            class="inline-flex w-full items-center justify-between rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                        >
                            <span x-text="warehouseFilterName || 'All warehouses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            @@click.away="open = false"
                            x-transition
                            class="absolute left-0 z-50 mt-2 w-56 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                            style="display: none;"
                        >
                            <button
                                type="button"
                                @@click="warehouseFilter = ''; warehouseFilterName = ''; loadData(); open = false"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50"
                                :class="warehouseFilter === '' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="warehouseFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All warehouses</span>
                            </button>
                            @foreach(($originWarehouses ?? $warehouses) as $warehouse)
                            <button
                                type="button"
                                @@click="warehouseFilter = '{{ $warehouse->id }}'; warehouseFilterName = '{{ $warehouse->name }}'; loadData(); open = false"
                                class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-slate-700 hover:bg-orange-50"
                                :class="warehouseFilter === '{{ $warehouse->id }}' ? 'bg-orange-50 text-orange-700' : ''"
                            >
                                <svg x-show="warehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $warehouse->name }}</span>
                                <span class="ml-auto text-[10px] text-slate-400">{{ $warehouse->code }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Right Controls: Create + Export + View -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <!-- Create Sort Batch -->
                    <button
                        type="button"
                        @@click="openCreateBatchModal()"
                        class="inline-flex items-center gap-2 rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/15 transition hover:border-orange-700 hover:bg-orange-700"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Batch
                    </button>
                    <!-- Export -->
                    <div x-data="{ open: false }" class="relative">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" style="display: none;">
                            <button type="button" @@click="exportData('excel'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Excel
                            </button>
                            <button type="button" @@click="exportData('pdf'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                PDF
                            </button>
                            <button type="button" @@click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                CSV
                            </button>
                            <div class="border-t border-slate-200/50 my-1"></div>
                            <button type="button" @@click="printData(); open = false" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                        </div>
                    </div>

                    <!-- View (column toggle) -->
                    <div x-data="{ open: false }" class="relative">
                        <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                             style="display: none;">
                            <template x-for="col in columns" :key="col.key">
                                <button type="button" @@click="toggleColumn(col.key)"
                                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                    <span x-text="col.label"></span>
                                    <svg x-show="visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px]"></div>

            <div class="block divide-y divide-slate-100 md:hidden">
                <div x-show="!loading && batches.length === 0" x-cloak class="px-4 py-10 text-center">
                    <p class="text-sm font-bold text-slate-500">No sort batches found.</p>
                </div>
                <template x-for="batch in batches" :key="batch.id">
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Batch</p>
                                <a :href="config.showUrlTemplate.replace('__ID__', batch.id)" class="mt-1 block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="batch.batch_number"></a>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-black"
                                  :class="batch.status === 'open' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-700'"
                                  x-text="batch.status === 'open' ? 'Open' : 'Sealed'"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Mode</p>
                                <p class="mt-1 whitespace-nowrap text-sm font-bold text-slate-800" x-text="batch.dispatch_mode_label"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Items</p>
                                <p class="mt-1 text-sm font-bold text-slate-800" x-text="batch.items_count"></p>
                            </div>
                            <div class="col-span-2 rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400" x-text="config.warehouseColumnLabel || 'Warehouse'"></p>
                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    <template x-if="!config.originWarehouseId">
                                        <span x-text="batch.origin_warehouse ? batch.origin_warehouse.name : '—'"></span>
                                    </template>
                                    <template x-if="!config.originWarehouseId && batch.destination_warehouse">
                                        <span x-text="' → ' + batch.destination_warehouse.name"></span>
                                    </template>
                                    <template x-if="config.originWarehouseId">
                                        <span x-text="batch.destination_warehouse ? batch.destination_warehouse.name : 'Local delivery'"></span>
                                    </template>
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Created</p>
                                <p class="mt-1 text-sm font-bold text-slate-800" x-text="formatDateTime(batch.created_at)"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Manifest / Run</p>
                                <template x-if="batch.has_manifest">
                                    <a :href="config.manifestShowUrlTemplate.replace('__ID__', batch.manifest_id)" class="mt-1 block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="batch.manifest_number"></a>
                                </template>
                                <template x-if="!batch.has_manifest && batch.has_delivery_run">
                                    <a :href="config.deliveryRunShowUrlTemplate.replace('__ID__', batch.run_id)" class="mt-1 block truncate font-mono text-sm font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="batch.run_number"></a>
                                </template>
                                <template x-if="!batch.has_manifest && !batch.has_delivery_run">
                                    <span class="mt-1 block truncate text-sm font-bold text-slate-400">—</span>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a :href="config.showUrlTemplate.replace('__ID__', batch.id)" class="inline-flex flex-1 items-center justify-center rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/15 transition hover:border-orange-700 hover:bg-orange-700">
                                View
                            </a>
                            <button x-show="batch.can_delete" type="button" @@click="openDeleteBatchModal(batch)" class="inline-flex items-center justify-center rounded-xl border-2 border-rose-100 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 transition hover:border-rose-200">
                                Delete
                            </button>
                        </div>
                    </article>
                </template>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[980px] w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-400">
                        <tr>
                            <th x-show="visibleColumns.batch_number" @@click="sort('batch_number')" class="cursor-pointer px-4 py-3">Batch #</th>
                            <th x-show="visibleColumns.warehouse" class="px-4 py-3" x-text="config.warehouseColumnLabel || 'Warehouse'"></th>
                            <th x-show="visibleColumns.mode" class="px-4 py-3">Mode</th>
                            <th x-show="visibleColumns.items" class="px-4 py-3 text-center">Items</th>
                            <th x-show="visibleColumns.status" @@click="sort('status')" class="cursor-pointer px-4 py-3 text-center">Status</th>
                            <th x-show="visibleColumns.sealed_at" @@click="sort('sealed_at')" class="cursor-pointer px-4 py-3">Sealed At</th>
                            <th x-show="visibleColumns.linked" class="px-4 py-3">Manifest / Run</th>
                            <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="cursor-pointer px-4 py-3">Created At</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr x-show="!loading && batches.length === 0" x-cloak>
                            <td colspan="9" class="px-4 py-10 text-center text-sm font-bold text-slate-500">No sort batches found.</td>
                        </tr>
                        <template x-for="batch in batches" :key="batch.id">
                            <tr class="transition hover:bg-orange-50/20">
                                <td x-show="visibleColumns.batch_number" class="px-4 py-3">
                                    <a :href="config.showUrlTemplate.replace('__ID__', batch.id)" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="batch.batch_number"></a>
                                </td>
                                <td x-show="visibleColumns.warehouse" class="px-4 py-3">
                                    <div class="text-sm font-bold text-slate-800">
                                        <template x-if="!config.originWarehouseId">
                                            <span x-text="batch.origin_warehouse ? batch.origin_warehouse.name : '—'"></span>
                                        </template>
                                        <template x-if="!config.originWarehouseId && batch.destination_warehouse">
                                            <span class="text-slate-400"> → </span>
                                        </template>
                                        <template x-if="!config.originWarehouseId && batch.destination_warehouse">
                                            <span x-text="batch.destination_warehouse.name"></span>
                                        </template>
                                        <template x-if="config.originWarehouseId">
                                            <span x-text="batch.destination_warehouse ? batch.destination_warehouse.name : 'Local delivery'"></span>
                                        </template>
                                    </div>
                                    <div class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                        <template x-if="!config.originWarehouseId">
                                            <span x-text="batch.origin_warehouse ? batch.origin_warehouse.code : ''"></span>
                                        </template>
                                        <template x-if="!config.originWarehouseId && batch.destination_warehouse">
                                            <span x-text="' → ' + batch.destination_warehouse.code"></span>
                                        </template>
                                        <template x-if="config.originWarehouseId">
                                            <span x-text="batch.destination_warehouse ? batch.destination_warehouse.code : 'This warehouse'"></span>
                                        </template>
                                    </div>
                                </td>
                                <td x-show="visibleColumns.mode" class="px-4 py-3">
                                    <span class="inline-flex min-w-max items-center whitespace-nowrap rounded-full border px-2.5 py-1 text-[10px] font-black"
                                          :class="batch.dispatch_mode === 'transfer' ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                                          x-text="batch.dispatch_mode_label"></span>
                                </td>
                                <td x-show="visibleColumns.items" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-700" x-text="batch.items_count"></span>
                                </td>
                                <td x-show="visibleColumns.status" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black"
                                          :class="batch.status === 'open' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-700'"
                                          x-text="batch.status === 'open' ? 'Open' : 'Sealed'"></span>
                                </td>
                                <td x-show="visibleColumns.sealed_at" class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="batch.sealed_at ? formatDateTime(batch.sealed_at) : '—'"></td>
                                <td x-show="visibleColumns.linked" class="px-4 py-3">
                                    <template x-if="batch.has_manifest">
                                        <a :href="config.manifestShowUrlTemplate.replace('__ID__', batch.manifest_id)" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="batch.manifest_number"></a>
                                    </template>
                                    <template x-if="!batch.has_manifest && batch.has_delivery_run">
                                        <a :href="config.deliveryRunShowUrlTemplate.replace('__ID__', batch.run_id)" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="batch.run_number"></a>
                                    </template>
                                    <template x-if="!batch.has_manifest && !batch.has_delivery_run">
                                        <span class="text-xs font-semibold text-slate-400">—</span>
                                    </template>
                                </td>
                                <td x-show="visibleColumns.created_at" class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="formatDateTime(batch.created_at)"></td>
                                <td x-show="visibleColumns.actions" class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a :href="config.showUrlTemplate.replace('__ID__', batch.id)" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-700">
                                            View
                                        </a>
                                        <button x-show="batch.can_delete" type="button" @@click="openDeleteBatchModal(batch)" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 transition hover:border-rose-200 hover:bg-rose-100" title="Delete batch">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z"/>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-600">Rows</span>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700">
                                    <span x-text="perPage"></span>
                                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display: none;">
                                    <button type="button" @@click="perPage = 10; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 10 ? 'bg-slate-100' : ''">10</button>
                                    <button type="button" @@click="perPage = 25; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 25 ? 'bg-slate-100' : ''">25</button>
                                    <button type="button" @@click="perPage = 50; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 50 ? 'bg-slate-100' : ''">50</button>
                                    <button type="button" @@click="perPage = 100; loadData(); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="perPage == 100 ? 'bg-slate-100' : ''">100</button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <button @@click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                            <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Batch Modal --}}
    <template x-teleport="body">
    <div
        x-show="createBatchModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-end justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:items-center sm:p-4"
        style="display: none;"
        @@keydown.escape.window="closeCreateBatchModal()"
        @@click.self="closeCreateBatchModal()"
    >
        <div
            x-show="createBatchModalOpen"
            x-transition.scale.origin.center.duration.200ms
            class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]"
            @@click.stop
        >
            <div class="shrink-0 border-b border-slate-100 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">New Sort Batch</h3>
                            <p class="mt-1 text-sm text-slate-500">Create a batch, then add packages on the details page.</p>
                        </div>
                    </div>
                    <button type="button" @@click="closeCreateBatchModal()" :disabled="createBatchLoading" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form @@submit.prevent="submitCreateBatch()" class="min-h-0 flex flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <div x-show="createBatchError" x-cloak class="mb-5 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-semibold text-rose-700" x-text="createBatchError"></p>
                    </div>

                <div class="grid grid-cols-1 gap-5">
                    <div x-show="!config.originWarehouseId" x-cloak>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Origin Warehouse <span class="text-rose-500">*</span></label>
                        <select x-model="newBatch.origin_warehouse_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <option value="">Select origin warehouse...</option>
                            @foreach(($originWarehouses ?? $warehouses) as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}{{ $warehouse->code ? ' (' . $warehouse->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Dispatch Mode <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition-all"
                                   :class="newBatch.dispatch_mode === 'transfer' ? 'border-orange-300 bg-orange-50/50 ring-4 ring-orange-100' : 'border-slate-200 bg-white hover:border-orange-200 hover:bg-orange-50/20'">
                                <input type="radio" value="transfer" x-model="newBatch.dispatch_mode" class="sr-only">
                                <div class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all"
                                     :class="newBatch.dispatch_mode === 'transfer' ? 'border-orange-600' : 'border-slate-300'">
                                    <div class="h-2 w-2 rounded-full bg-orange-600 transition-all" :class="newBatch.dispatch_mode === 'transfer' ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <div>
                                    <span class="block text-sm font-black" :class="newBatch.dispatch_mode === 'transfer' ? 'text-slate-900' : 'text-slate-600'">Transfer</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">Move packages to another warehouse.</span>
                                </div>
                            </label>
                            <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition-all"
                                   :class="newBatch.dispatch_mode === 'local_delivery' ? 'border-orange-300 bg-orange-50/50 ring-4 ring-orange-100' : 'border-slate-200 bg-white hover:border-orange-200 hover:bg-orange-50/20'">
                                <input type="radio" value="local_delivery" x-model="newBatch.dispatch_mode" class="sr-only">
                                <div class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all"
                                     :class="newBatch.dispatch_mode === 'local_delivery' ? 'border-orange-600' : 'border-slate-300'">
                                    <div class="h-2 w-2 rounded-full bg-orange-600 transition-all" :class="newBatch.dispatch_mode === 'local_delivery' ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <div>
                                    <span class="block text-sm font-black" :class="newBatch.dispatch_mode === 'local_delivery' ? 'text-slate-900' : 'text-slate-600'">Local Delivery</span>
                                    <span class="mt-0.5 block text-xs text-slate-500" x-text="config.originWarehouseId ? 'Deliver from this warehouse.' : 'Deliver from the selected warehouse.'"></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div x-show="newBatch.dispatch_mode === 'transfer'" x-transition>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Destination Warehouse <span class="text-rose-500">*</span></label>
                        <select x-model="newBatch.destination_warehouse_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <option value="">Select destination warehouse...</option>
                            @foreach(($destinationWarehouses ?? $warehouses) as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}{{ $warehouse->code ? ' (' . $warehouse->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes <span class="font-normal normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <textarea x-model="newBatch.notes" rows="3" placeholder="Optional notes about this batch..."
                                  class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                    </div>
                </div>

                </div>

                <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button" @@click="closeCreateBatchModal()" :disabled="createBatchLoading" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm">
                        Cancel
                    </button>
                    <button type="submit" :disabled="createBatchLoading"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:border-orange-300 disabled:bg-orange-300 disabled:shadow-none sm:text-sm">
                        <svg x-show="!createBatchLoading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <svg x-show="createBatchLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Create Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    {{-- Delete Batch Modal --}}
    <template x-teleport="body">
    <div
        x-show="deleteBatchModalOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center p-4"
        style="display: none;"
        @@keydown.escape.window="closeDeleteBatchModal()"
    >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="closeDeleteBatchModal()"></div>
        <div
            x-show="deleteBatchModalOpen"
            x-transition.scale.origin.center.duration.200ms
            class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200"
            @@click.stop
        >
            <div class="px-6 py-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900">Delete Batch</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            Delete <span class="font-bold text-slate-800" x-text="deleteBatchTarget?.batch_number || 'this batch'"></span>? Packages in this batch will be returned to warehouse inventory.
                        </p>
                    </div>
                </div>
                <div x-show="deleteBatchError" x-cloak class="mt-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-rose-700" x-text="deleteBatchError"></p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <button type="button" @@click="closeDeleteBatchModal()" :disabled="deleteBatchLoading"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50">
                    Cancel
                </button>
                <button type="button" @@click="deleteBatch()" :disabled="deleteBatchLoading"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="deleteBatchLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span x-text="deleteBatchLoading ? 'Deleting...' : 'Delete Batch'"></span>
                </button>
            </div>
        </div>
    </div>
    </template>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('sortBatchesTable', () => ({
        config: @json($sortBatchConfig),
        batches: [],
        loading: false,
        search: '',
        statusFilter: '',
        statusFilterName: '',
        dispatchModeFilter: '',
        dispatchModeFilterName: '',
        warehouseFilter: '',
        warehouseFilterName: '',
        sortBy: 'created_at',
        sortDirection: 'desc',
        perPage: 50,
        createBatchModalOpen: false,
        createBatchLoading: false,
        createBatchError: '',
        deleteBatchModalOpen: false,
        deleteBatchLoading: false,
        deleteBatchError: '',
        deleteBatchTarget: null,
        newBatch: {
            origin_warehouse_id: '',
            dispatch_mode: 'local_delivery',
            destination_warehouse_id: '',
            notes: '',
        },

        columns: [
            { key: 'batch_number', label: 'Batch #' },
            { key: 'warehouse',    label: 'Warehouse' },
            { key: 'mode',         label: 'Mode' },
            { key: 'items',        label: 'Items' },
            { key: 'status',       label: 'Status' },
            { key: 'sealed_at',    label: 'Sealed At' },
            { key: 'linked',       label: 'Manifest / Run' },
            { key: 'created_at',   label: 'Created At' },
            { key: 'actions',      label: 'Actions' },
        ],
        visibleColumns: {
            batch_number: true,
            warehouse:    true,
            mode:         true,
            items:        true,
            status:       true,
            sealed_at:    true,
            linked:       true,
            created_at:   true,
            actions:      true,
        },
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },

        init() {
            if (this.config.originWarehouseId) {
                this.warehouseFilter = String(this.config.originWarehouseId);
                this.newBatch.origin_warehouse_id = String(this.config.originWarehouseId);
            }
            this.loadData();
        },

        loadData(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({
                page,
                per_page: this.perPage,
                sort: this.sortBy,
                direction: this.sortDirection,
            });
            if (this.search)              params.set('search', this.search);
            if (this.statusFilter)        params.set('status', this.statusFilter);
            if (this.dispatchModeFilter)  params.set('dispatch_mode', this.dispatchModeFilter);
            if (this.warehouseFilter)     params.set('origin_warehouse_id', this.warehouseFilter);

            fetch(`${this.config.dataUrl}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(r => r.json())
                .then(json => {
                    this.batches = json.data;
                    this.meta    = json.meta;
                })
                .finally(() => { this.loading = false; });
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy        = column;
                this.sortDirection = 'asc';
            }
            this.loadData();
        },

        firstPage()    { if (this.meta.current_page > 1) this.loadData(1); },
        previousPage() { if (this.meta.current_page > 1) this.loadData(this.meta.current_page - 1); },
        nextPage()     { if (this.meta.current_page < this.meta.last_page) this.loadData(this.meta.current_page + 1); },
        lastPage()     { if (this.meta.current_page < this.meta.last_page) this.loadData(this.meta.last_page); },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        resetCreateBatchForm() {
            this.createBatchError = '';
            this.newBatch = {
                origin_warehouse_id: this.config.originWarehouseId ? String(this.config.originWarehouseId) : '',
                dispatch_mode: 'local_delivery',
                destination_warehouse_id: '',
                notes: '',
            };
        },

        openCreateBatchModal() {
            this.resetCreateBatchForm();
            this.createBatchModalOpen = true;
        },

        closeCreateBatchModal() {
            if (this.createBatchLoading) return;
            this.createBatchModalOpen = false;
        },

        openDeleteBatchModal(batch) {
            if (!batch?.can_delete) return;
            this.deleteBatchTarget = batch;
            this.deleteBatchError = '';
            this.deleteBatchModalOpen = true;
        },

        closeDeleteBatchModal() {
            if (this.deleteBatchLoading) return;
            this.deleteBatchModalOpen = false;
            this.deleteBatchTarget = null;
            this.deleteBatchError = '';
        },

        async deleteBatch() {
            if (!this.deleteBatchTarget?.id) return;

            this.deleteBatchLoading = true;
            this.deleteBatchError = '';

            try {
                const url = this.config.deleteUrlTemplate.replace('__ID__', this.deleteBatchTarget.id);
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    this.closeDeleteBatchModal();
                    this.loadData(this.meta.current_page || 1);
                    return;
                }

                this.deleteBatchError = result.message || 'Failed to delete sort batch.';
            } catch (err) {
                console.error('Delete batch failed:', err);
                this.deleteBatchError = 'An unexpected error occurred.';
            } finally {
                this.deleteBatchLoading = false;
            }
        },

        async submitCreateBatch() {
            this.createBatchError = '';

            if (!this.newBatch.origin_warehouse_id) {
                this.createBatchError = 'Please select an origin warehouse.';
                return;
            }

            if (this.newBatch.dispatch_mode === 'transfer' && !this.newBatch.destination_warehouse_id) {
                this.createBatchError = 'Please select a destination warehouse for transfer mode.';
                return;
            }

            this.createBatchLoading = true;
            try {
                const body = {
                    origin_warehouse_id: this.newBatch.origin_warehouse_id,
                    dispatch_mode: this.newBatch.dispatch_mode,
                    notes: this.newBatch.notes || null,
                };

                if (this.newBatch.dispatch_mode === 'transfer') {
                    body.destination_warehouse_id = this.newBatch.destination_warehouse_id;
                }

                const response = await fetch(this.config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(body),
                });
                const result = await response.json();

                if (response.ok && result.success && result.data?.batch?.id) {
                    window.location.href = this.config.showUrlTemplate.replace('__ID__', result.data.batch.id);
                    return;
                }

                this.createBatchError = result.message || 'Failed to create sort batch.';
            } catch (err) {
                console.error('Create batch failed:', err);
                this.createBatchError = 'An unexpected error occurred.';
            } finally {
                this.createBatchLoading = false;
            }
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search)             params.append('search', this.search);
                if (this.statusFilter)       params.append('status', this.statusFilter);
                if (this.dispatchModeFilter) params.append('dispatch_mode', this.dispatchModeFilter);
                if (this.warehouseFilter)    params.append('origin_warehouse_id', this.warehouseFilter);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `${this.config.exportUrl}?${params}`;
                    return;
                }

                const response = await fetch(`${this.config.exportUrl}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Export failed');
                const result = await response.json();
                if (format === 'csv') this.downloadCSV(result.data);
            } catch (err) {
                console.error('Export failed:', err);
                alert('Export failed. Please try again.');
            }
        },

        async printData() {
            try {
                const params = new URLSearchParams();
                if (this.search)             params.append('search', this.search);
                if (this.statusFilter)       params.append('status', this.statusFilter);
                if (this.dispatchModeFilter) params.append('dispatch_mode', this.dispatchModeFilter);
                if (this.warehouseFilter)    params.append('origin_warehouse_id', this.warehouseFilter);

                const response = await fetch(`${this.config.exportUrl}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Failed to fetch data');
                const result = await response.json();
                this.openPrintWindow(result.data);
            } catch (err) {
                console.error('Print failed:', err);
                alert('Print failed. Please try again.');
            }
        },

        openPrintWindow(data) {
            if (!data.length) { alert('No data to print'); return; }
            const printWindow = window.open('', '_blank');
            if (!printWindow) { alert('Pop-up blocked. Please allow pop-ups to print.'); return; }
            const doc = printWindow.document;
            const headers = Object.keys(data[0]);
            doc.title = 'Sort Batches Export';
            doc.body.innerHTML = '';
            const style = doc.createElement('style');
            style.textContent = 'body{font-family:sans-serif;padding:20px}h1{font-size:22px;margin-bottom:16px;color:#1e293b}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border:1px solid #e2e8f0;padding:7px 10px;text-align:left;font-size:11px}th{background:#f1f5f9;font-weight:600;color:#475569}tr:nth-child(even){background:#f8fafc}';
            doc.head.appendChild(style);
            const title = doc.createElement('h1');
            title.textContent = 'Sort Batches';
            doc.body.appendChild(title);
            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow);
            table.appendChild(thead);
            const tbody = doc.createElement('tbody');
            data.forEach(row => {
                const tr = doc.createElement('tr');
                headers.forEach(h => { const td = doc.createElement('td'); td.textContent = row[h] ?? '-'; tr.appendChild(td); });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);
            setTimeout(() => printWindow.print(), 250);
        },

        downloadCSV(data) {
            if (!data.length) return;
            const headers = Object.keys(data[0]);
            const csv = [headers.join(','), ...data.map(row => headers.map(h => `"${String(row[h] ?? '').replace(/"/g, '""')}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'sort_batches.csv';
            document.body.appendChild(a); a.click();
            document.body.removeChild(a); URL.revokeObjectURL(url);
        },

        formatDateTime(value) {
            if (!value) return '—';
            const d = new Date(value);
            if (isNaN(d)) return value;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        },

    }));
});
</script>
@endpush
