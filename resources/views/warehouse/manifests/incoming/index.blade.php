@extends($layoutName ?? 'warehouse.layouts.app')

@section('title', $pageTitle ?? 'Incoming Batches')
@section('page-title', $pageTitle ?? 'Incoming Batches')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', $pageTitle ?? 'Incoming Batches')

@php
    $config = [
        'data_endpoint' => $dataEndpoint ?? route('warehouse.manifests.incoming.data'),
        'receive_endpoint' => $receiveEndpoint ?? null,
        'scan_endpoint' => route('warehouse.manifests.incoming.scan'),
        'index_url' => route('warehouse.manifests.incoming.index'),
        'origin_warehouses' => ($originWarehouses ?? collect())->values(),
        'transport_drivers' => ($transportDrivers ?? collect())->map(fn ($driver) => [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ])->values(),
        'available_regions' => [
            ['id' => 1, 'name' => 'Kumasi'],
            ['id' => 2, 'name' => 'Koforidua'],
            ['id' => 3, 'name' => 'Takoradi'],
            ['id' => 4, 'name' => 'Tamale'],
            ['id' => 5, 'name' => 'Accra'],
            ['id' => 6, 'name' => 'Sunyani'],
            ['id' => 7, 'name' => 'Cape Coast'],
            ['id' => 8, 'name' => 'Ho'],
        ]
    ];
@endphp

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="warehouseIncomingManifestsPage" data-warehouse-incoming-manifests-config="{{ json_encode($config, JSON_INVALID_UTF8_SUBSTITUTE) }}">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Incoming Batches</h1>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-1 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                        <span x-text="selectedDateLabel">Today</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute left-0 mt-2 w-32 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-1 text-xs font-bold text-slate-700" style="display:none">
                        <button type="button" @click="setDateFilter('today', 'Today'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 rounded-lg">Today</button>
                        <button type="button" @click="setDateFilter('this_week', 'This Week'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 rounded-lg">This Week</button>
                        <button type="button" @click="setDateFilter('all', 'All Time'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 rounded-lg">All Time</button>
                    </div>
                </div>
            </div>
            <p class="text-slate-400 text-sm font-semibold mt-1">Receive manifests and unseal batches arriving at {{ $warehouse->name ?? 'this warehouse' }}.</p>
        </div>

        <div>
            <button type="button" @click="openScanModal()" class="bg-[#E2762B] hover:bg-[#d1651d] text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-md transition-colors flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                </svg>
                Scan Incoming Package
            </button>
        </div>
    </div>

    {{-- Customizable Stat Cards Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <template x-for="(card, index) in activeCards" :key="index">
            <div class="bg-amber-50/20 border border-amber-100/80 rounded-2xl p-4 shadow-sm relative group">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 truncate" x-text="`Incoming from ${card.region_name}`"></p>
                    
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="text-slate-300 hover:text-slate-600 transition-colors p-1 rounded-lg hover:bg-white/80">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-1 text-xs font-bold text-slate-700 max-h-48 overflow-y-auto" style="display:none">
                            <div class="px-2 py-1 text-[9px] uppercase tracking-wider text-slate-400 font-extrabold border-b border-slate-100">Select Location</div>
                            <template x-for="region in availableRegions" :key="region.id">
                                <button type="button" @click="updateCardRegion(index, region.id, region.name); open = false" class="w-full text-left px-2.5 py-1.5 hover:bg-slate-50 rounded-lg flex items-center justify-between">
                                    <span x-text="region.name"></span>
                                    <svg x-show="card.region_id === region.id" class="w-3 h-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <p class="text-3xl font-black text-slate-900 mt-2" x-text="card.count"></p>
            </div>
        </template>
    </div>

    {{-- Main Datatable Container --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search manifest, batch, origin, driver..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="filters.status" @change="loadData()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="dispatched">Dispatched / In Transit</option>
                            <option value="received">Received</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-extrabold uppercase tracking-wider text-slate-500">Batch Number</th>
                            <th class="px-4 py-3 text-left font-extrabold uppercase tracking-wider text-slate-500">Origin Context</th>
                            <th class="px-4 py-3 text-center font-extrabold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-4 py-3 text-center font-extrabold uppercase tracking-wider text-slate-500">Parcels Count</th>
                            <th class="px-4 py-3 text-left font-extrabold uppercase tracking-wider text-slate-500">Dispatched At</th>
                            <th class="px-4 py-3 text-right font-extrabold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50">
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-400 font-bold">
                                    No incoming batches match the current query
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in rows" :key="row.id">
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 font-extrabold text-slate-900" x-text="row.batch_number"></td>
                                <td class="px-4 py-3 font-bold text-slate-700" x-text="row.origin_context"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                          :class="row.can_receive ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                          x-text="row.status_label"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-black text-slate-700" x-text="row.items_count"></span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-600" x-text="row.dispatched_at"></td>
                                <td class="px-4 py-3 text-right">
                                    <template x-if="row.can_receive">
                                        <button type="button" @click="receiveBatch(row.id)" class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Receive & Unseal
                                        </button>
                                    </template>
                                    <template x-if="!row.can_receive">
                                        <span class="text-xs font-extrabold text-slate-400 bg-slate-100 px-3 py-1.5 rounded-xl inline-block">Received</span>
                                    </template>
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
                    <div class="flex items-center gap-2">
                        <button @click="meta.current_page--; loadData()" :disabled="meta.current_page <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40">Prev</button>
                        <span class="text-xs font-black text-slate-700">Page <span x-text="meta.current_page"></span> / <span x-text="meta.last_page"></span></span>
                        <button @click="meta.current_page++; loadData()" :disabled="meta.current_page >= meta.last_page" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scanner Teleport Modal --}}
    <template x-teleport="body">
        <div x-show="scanModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[210] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
            <div @click.stop class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Scan Package</h3>
                            <p class="mt-1 text-sm text-slate-500">Scan the printed label to receive an incoming package.</p>
                        </div>
                    </div>
                    <button type="button" @click="closeScanModal()" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <video x-ref="scanVideo" class="hidden aspect-video w-full object-contain" playsinline muted></video>
                        <canvas x-ref="scanCanvas" class="hidden"></canvas>
                        <div x-show="scannerActive" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-between p-4" style="display:none">
                            <div class="rounded-full bg-black/55 px-3 py-1.5 text-xs font-bold text-white shadow-lg" x-text="scannerStatus || 'Scanning barcode...'"></div>
                            <div></div>
                            <p class="rounded-full bg-black/55 px-3 py-1.5 text-[11px] font-semibold text-white">Point camera at package label</p>
                        </div>
                        <div x-show="!scannerActive" class="flex aspect-video flex-col items-center justify-center gap-3 p-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-white">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-white" x-text="scannerStatus || 'Camera scanner is ready.'"></p>
                            <button type="button" @click="startScanner()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg transition hover:bg-orange-700">Start Camera Scan</button>
                        </div>
                    </div>

                    <div x-show="scanModalMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700" x-text="scanModalMessage"></div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Manual entry</label>
                        <form class="flex flex-col gap-3" @submit.prevent="scanIncomingPackage()">
                            <input type="text" x-model="scannerCode" @input="scanModalMessage = ''" x-ref="scannerInput" placeholder="Enter or paste label code"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <button type="submit" x-show="scannerCode.trim()" :disabled="scannerLoading" class="w-full rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white shadow-lg transition hover:bg-slate-800 disabled:opacity-50 sm:text-sm">
                                <span x-text="scannerLoading ? 'Checking...' : 'Load Package'"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @include('shared.incoming-receive-modal')
</div>
@endsection