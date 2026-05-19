@extends('admin.layouts.app')

@section('title', 'Rider/Driver - ' . $driver->name)
@section('breadcrumb-parent', 'Riders & Drivers')
@section('breadcrumb-current', $driver->name)

@php
$driverConfig = [
    'driver' => $driver,
    'pickupsEndpoint' => route('admin.drivers.assignments', $driver),
    'transportManifestsEndpoint' => route('admin.drivers.transport-manifests', $driver),
    'deliveryRunsEndpoint' => route('admin.drivers.delivery-runs', $driver),
    'activityLogsEndpoint' => route('admin.drivers.activity-logs', $driver),
    'updateEndpoint' => route('admin.drivers.update', $driver),
    'toggleActiveEndpoint' => route('admin.drivers.toggle-active', $driver),
    'packagesDataUrl' => route('admin.drivers.packages-data', $driver),
    'canManage' => $canManage,
];
@endphp

@section('content')
<div x-data="driverShow()" data-driver-show-config="{{ json_encode($driverConfig) }}" class="space-y-6">

    <!-- Hero Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ route('admin.drivers.index') }}" class="inline-flex h-11 w-auto shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Back</span>
                    </a>

                <div class="ml-auto flex w-auto max-w-[calc(100%-5.75rem)] flex-wrap items-center justify-end gap-2 sm:max-w-none">
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black
                        @if($driver->status === 'available') bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/30
                        @elseif($driver->status === 'busy') bg-amber-500/15 text-amber-100 ring-1 ring-amber-400/30
                        @else bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30
                        @endif">
                        <span class="mr-2 h-2 w-2 rounded-full
                            @if($driver->status === 'available') bg-emerald-300
                            @elseif($driver->status === 'busy') bg-amber-300
                            @else bg-slate-300
                            @endif"></span>
                        {{ ucfirst($driver->status ?? 'offline') }}
                    </span>
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black {{ $driver->is_active ? 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/30' : 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30' }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $driver->is_active ? 'bg-emerald-300' : 'bg-slate-300' }}"></span>
                        {{ $driver->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($canManage)
                        <button @@click="openEditModal()" class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit
                        </button>
                        <button @@click="showToggleModal = true" class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-3 text-xs font-black transition"
                            :class="driver.is_active ? 'border-amber-400/45 bg-amber-500/15 text-amber-100 hover:bg-amber-500/25' : 'border-emerald-400/45 bg-emerald-500/15 text-emerald-100 hover:bg-emerald-500/25'">
                            <span x-text="driver.is_active ? 'Deactivate' : 'Activate'"></span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="relative mt-5 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[640px] lg:shrink">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-2xl font-black text-white shadow-lg shadow-orange-950/25">
                                {{ strtoupper(substr($driver->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Driver Workspace</p>
                            <h1 class="mt-1 max-w-4xl break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl xl:text-2xl 2xl:text-3xl">{{ $driver->name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                <span>{{ $driver->phone }}</span>
                                @if($driver->email)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ $driver->email }}</span>
                                @endif
                                @if($driver->vehicle_type || $driver->vehicle_number)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ collect([ucfirst((string) $driver->vehicle_type), $driver->vehicle_number])->filter()->join(' ') }}</span>
                                @endif
                                @if($driver->license_number)
                                    <span class="text-slate-600">/</span>
                                    <span>{{ $driver->license_number }}</span>
                                @endif
                                <span class="text-slate-600">/</span>
                                <span>Created {{ $driver->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-3 lg:ml-auto lg:w-[620px] lg:shrink-0 2xl:w-[680px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2m-6 9 2 2 4-4"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ number_format($pickupsCount) }} Pickups</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">{{ number_format($completedCount) }} Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ number_format($transportManifestsCount) }} Transports</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">{{ number_format($currentPackagesCount) }} Current packages</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 sm:p-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 sm:h-9 sm:w-9">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="whitespace-nowrap text-[15px] font-black leading-tight text-white sm:text-lg">{{ number_format($deliveryRunsCount) }} Deliveries</p>
                                <p class="mt-1 text-[11px] font-bold leading-snug text-slate-400 sm:text-xs">@if($lastLogin) Last login {{ $lastLogin->created_at->format('d M, h:i A') }} @else No recent login @endif</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabs Section -->
    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
            <p class="sr-only">Rider/driver activity tabs</p>
            <div class="flex flex-wrap items-center gap-2">

            <!-- Pickups -->
            <button @@click="setActiveTab('pickups')"
                class="group inline-flex w-auto shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'pickups' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Pickups</span>
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black" :class="activeTab === 'pickups' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">{{ $pickupsCount }}</span>
            </button>

            <!-- Transports -->
            <button @@click="setActiveTab('transports')"
                class="group inline-flex w-auto shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'transports' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Transports</span>
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black" :class="activeTab === 'transports' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">{{ $transportManifestsCount }}</span>
            </button>

            <!-- Deliveries -->
            <button @@click="setActiveTab('deliveries')"
                class="group inline-flex w-auto shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'deliveries' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Deliveries</span>
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black" :class="activeTab === 'deliveries' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">{{ $deliveryRunsCount }}</span>
            </button>

            <!-- Divider: Activity -->
            <div class="hidden"></div>

            <!-- Activity Logs -->
            <button @@click="setActiveTab('activity')"
                class="group inline-flex w-auto shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'activity' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Activity Logs</span>
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black" :class="activeTab === 'activity' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">{{ $activityLogsCount }}</span>
            </button>

            <!-- Packages -->
            <button @@click="setActiveTab('packages')"
                class="group inline-flex w-auto shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="activeTab === 'packages' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Packages</span>
            </button>
            </div>
    </section>

    <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <!-- Tab Content Area -->
        <div class="min-w-0 bg-white">
            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PICKUPS TAB                                            --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'pickups'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="text" x-model="pickups.search" @@input.debounce.500ms="pickups.page = 1; loadPickups()" placeholder="Search pickups..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="pickups.showFilters = !pickups.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="pickups.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="pickups.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <template x-for="col in pickups.columns" :key="col.key">
                                    <button type="button" @@click="togglePickupsColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="pickups.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <button type="button" @@click="downloadCSV('pickups'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('pickups'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="pickups.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assigned Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="pickupsAssignedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                                <select x-model="pickups.status" @@change="pickups.statusName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All Statuses</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="en_route">En Route</option>
                                    <option value="arrived">Arrived</option>
                                    <option value="picking_up">Picking Up</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Vendor</label>
                                <input type="text" x-model="pickups.vendor" placeholder="Vendor name or phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Recipient Phone</label>
                                <input type="text" x-model="pickups.recipientPhone" placeholder="Phone number" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="pickups.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('pickups')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('pickups')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('pickups').length">
                        <template x-for="chip in activeFilterChips('pickups')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('pickups', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>
                <!-- Table -->
                <div class="relative overflow-hidden bg-white">
                    <div x-show="pickups.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="pickups.visibleColumns.shipment_number" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">SHIPMENT #</th>
                                    <th x-show="pickups.visibleColumns.vendor_name" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">VENDOR</th>
                                    <th x-show="pickups.visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">STATUS</th>
                                    <th x-show="pickups.visibleColumns.assigned_at" @@click="sortPickups('assigned_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">
                                        <div class="flex items-center">ASSIGNED AT<svg class="w-2.5 h-2.5 ml-1" :class="pickups.sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="pickups.visibleColumns.completed_at" @@click="sortPickups('completed_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">
                                        <div class="flex items-center">COMPLETED AT<svg class="w-2.5 h-2.5 ml-1" :class="pickups.sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="pickups.data.length === 0 && !pickups.loading">
                                    <tr><td :colspan="visibleColumnCount('pickups') + 1" class="px-4 py-8 text-center text-gray-500 text-xs">No pickups found</td></tr>
                                </template>
                                <template x-for="pickup in pickups.data" :key="pickup.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="pickups.visibleColumns.shipment_number" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="pickup.shipment_number || '-'"></td>
                                        <td x-show="pickups.visibleColumns.vendor_name" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="pickup.vendor_name || '-'"></td>
                                        <td x-show="pickups.visibleColumns.status" class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': pickup.status === 'assigned',
                                                    'bg-cyan-100 text-cyan-700': pickup.status === 'en_route',
                                                    'bg-violet-100 text-violet-700': pickup.status === 'arrived',
                                                    'bg-amber-100 text-amber-700': pickup.status === 'picking_up',
                                                    'bg-emerald-100 text-emerald-700': pickup.status === 'completed',
                                                    'bg-rose-100 text-rose-700': pickup.status === 'cancelled',
                                                    'bg-slate-100 text-slate-700': !['assigned','en_route','arrived','picking_up','completed','cancelled'].includes(pickup.status)
                                                }" x-text="pickup.status ? pickup.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="pickups.visibleColumns.assigned_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(pickup.assigned_at)"></td>
                                        <td x-show="pickups.visibleColumns.completed_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="pickup.completed_at ? formatDateTime(pickup.completed_at) : '-'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <a x-show="pickup.view_url" :href="pickup.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="pickups.meta.from || 0"></span> to <span x-text="pickups.meta.to || 0"></span> of <span x-text="pickups.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="pickupsPrevPage()" :disabled="pickups.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="pickups.meta.current_page || 1"></span> / <span x-text="pickups.meta.last_page || 1"></span></div>
                                <button @@click="pickupsNextPage()" :disabled="pickups.page >= pickups.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- TRANSPORTS TAB                                         --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'transports'" x-cloak>
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="text" x-model="transports.search" @@input.debounce.500ms="transports.page = 1; loadTransports()" placeholder="Search transports..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="transports.showFilters = !transports.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="transports.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="transports.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <template x-for="col in transports.columns" :key="col.key"><button type="button" @@click="toggleTransportsColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><span x-text="col.label"></span><svg x-show="transports.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <button type="button" @@click="downloadCSV('transports'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('transports'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="transports.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assigned Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="transportsAssignedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Received Date</label>
                                <div class="relative">
                                    <input type="text" x-ref="transportsReceivedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                                <select x-model="transports.status" @@change="transports.statusName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="loading">Loading</option>
                                    <option value="in_transit">In Transit</option>
                                    <option value="arrived">Arrived</option>
                                    <option value="received">Received</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Origin</label><input type="text" x-model="transports.origin" placeholder="Origin warehouse" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Destination</label><input type="text" x-model="transports.destination" placeholder="Destination warehouse" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="transports.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('transports')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('transports')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('transports').length">
                        <template x-for="chip in activeFilterChips('transports')" :key="chip.key">
                            <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                                <span x-text="chip.label"></span>
                                <button type="button" @@click="clearFilter('transports', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>
                <div class="relative overflow-hidden bg-white">
                    <div x-show="transports.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="transports.visibleColumns.manifest_number" @@click="sortTransports('manifest_number')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><div class="flex items-center">MANIFEST #<svg class="w-2.5 h-2.5 ml-1" :class="transports.sortBy === 'manifest_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="transports.visibleColumns.origin_warehouse" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ORIGIN</th>
                                    <th x-show="transports.visibleColumns.destination_warehouse" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">DESTINATION</th>
                                    <th x-show="transports.visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">STATUS</th>
                                    <th x-show="transports.visibleColumns.assigned_at" @@click="sortTransports('assigned_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><div class="flex items-center">ASSIGNED AT<svg class="w-2.5 h-2.5 ml-1" :class="transports.sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="transports.visibleColumns.received_at" @@click="sortTransports('received_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><div class="flex items-center">RECEIVED AT<svg class="w-2.5 h-2.5 ml-1" :class="transports.sortBy === 'received_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="transports.data.length === 0 && !transports.loading"><tr><td :colspan="visibleColumnCount('transports') + 1" class="px-4 py-8 text-center text-gray-500 text-xs">No transport manifests found</td></tr></template>
                                <template x-for="transport in transports.data" :key="transport.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="transports.visibleColumns.manifest_number" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="transport.manifest_number"></td>
                                        <td x-show="transports.visibleColumns.origin_warehouse" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="transport.origin_warehouse || '-'"></td>
                                        <td x-show="transports.visibleColumns.destination_warehouse" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="transport.destination_warehouse || '-'"></td>
                                        <td x-show="transports.visibleColumns.status" class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="{'bg-slate-100 text-slate-700': transport.status === 'draft','bg-blue-100 text-blue-700': transport.status === 'assigned','bg-indigo-100 text-indigo-700': transport.status === 'loading','bg-amber-100 text-amber-700': transport.status === 'in_transit','bg-violet-100 text-violet-700': transport.status === 'arrived','bg-emerald-100 text-emerald-700': transport.status === 'received','bg-rose-100 text-rose-700': transport.status === 'cancelled'}" x-text="transport.status ? transport.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="transports.visibleColumns.assigned_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(transport.assigned_at)"></td>
                                        <td x-show="transports.visibleColumns.received_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="transport.received_at ? formatDateTime(transport.received_at) : '-'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <a :href="transport.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="transports.meta.from || 0"></span> to <span x-text="transports.meta.to || 0"></span> of <span x-text="transports.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="transportsPrevPage()" :disabled="transports.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="transports.meta.current_page || 1"></span> / <span x-text="transports.meta.last_page || 1"></span></div>
                                <button @@click="transportsNextPage()" :disabled="transports.page >= transports.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- DELIVERIES TAB                                         --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'deliveries'" x-cloak>
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="text" x-model="deliveries.search" @@input.debounce.500ms="deliveries.page = 1; loadDeliveries()" placeholder="Search deliveries..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="deliveries.showFilters = !deliveries.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="deliveries.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="deliveries.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <template x-for="col in deliveries.columns" :key="col.key"><button type="button" @@click="toggleDeliveriesColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><span x-text="col.label"></span><svg x-show="deliveries.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <button type="button" @@click="downloadCSV('deliveries'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('deliveries'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="deliveries.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Assigned Date</label><div class="relative"><input type="text" x-ref="deliveriesAssignedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg></div></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Completed Date</label><div class="relative"><input type="text" x-ref="deliveriesCompletedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg></div></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label><select x-model="deliveries.status" @@change="deliveries.statusName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><option value="">All Statuses</option><option value="draft">Draft</option><option value="assigned">Assigned</option><option value="out_for_delivery">Out for Delivery</option><option value="partially_delivered">Partially Delivered</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Warehouse</label><input type="text" x-model="deliveries.warehouse" placeholder="Warehouse name" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Stops Range</label><div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100"><input type="number" min="0" x-model="deliveries.stopsMin" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none"><div class="w-px bg-slate-200"></div><input type="number" min="0" x-model="deliveries.stopsMax" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none"></div></div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="deliveries.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('deliveries')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('deliveries')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('deliveries').length"><template x-for="chip in activeFilterChips('deliveries')" :key="chip.key"><span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200"><span x-text="chip.label"></span><button type="button" @@click="clearFilter('deliveries', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button></span></template></div>
                </div>
                <div class="relative overflow-hidden bg-white">
                    <div x-show="deliveries.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="deliveries.visibleColumns.run_number" @@click="sortDeliveries('run_number')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><div class="flex items-center">RUN #<svg class="w-2.5 h-2.5 ml-1" :class="deliveries.sortBy === 'run_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="deliveries.visibleColumns.warehouse" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">WAREHOUSE</th>
                                    <th x-show="deliveries.visibleColumns.stops_count" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">STOPS</th>
                                    <th x-show="deliveries.visibleColumns.status" class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">STATUS</th>
                                    <th x-show="deliveries.visibleColumns.assigned_at" @@click="sortDeliveries('assigned_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><div class="flex items-center">ASSIGNED AT<svg class="w-2.5 h-2.5 ml-1" :class="deliveries.sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="deliveries.visibleColumns.completed_at" @@click="sortDeliveries('completed_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><div class="flex items-center">COMPLETED AT<svg class="w-2.5 h-2.5 ml-1" :class="deliveries.sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="deliveries.data.length === 0 && !deliveries.loading"><tr><td :colspan="visibleColumnCount('deliveries') + 1" class="px-4 py-8 text-center text-gray-500 text-xs">No delivery runs found</td></tr></template>
                                <template x-for="delivery in deliveries.data" :key="delivery.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="deliveries.visibleColumns.run_number" class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="delivery.run_number"></td>
                                        <td x-show="deliveries.visibleColumns.warehouse" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="delivery.warehouse || '-'"></td>
                                        <td x-show="deliveries.visibleColumns.stops_count" class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="delivery.stops_count"></span>
                                        </td>
                                        <td x-show="deliveries.visibleColumns.status" class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="{'bg-slate-100 text-slate-700': delivery.status === 'draft','bg-blue-100 text-blue-700': delivery.status === 'assigned','bg-amber-100 text-amber-700': delivery.status === 'out_for_delivery','bg-orange-100 text-orange-700': delivery.status === 'partially_delivered','bg-emerald-100 text-emerald-700': delivery.status === 'completed','bg-rose-100 text-rose-700': delivery.status === 'cancelled'}" x-text="delivery.status ? delivery.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="deliveries.visibleColumns.assigned_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(delivery.assigned_at)"></td>
                                        <td x-show="deliveries.visibleColumns.completed_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="delivery.completed_at ? formatDateTime(delivery.completed_at) : '-'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <a :href="delivery.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="deliveries.meta.from || 0"></span> to <span x-text="deliveries.meta.to || 0"></span> of <span x-text="deliveries.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="deliveriesPrevPage()" :disabled="deliveries.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="deliveries.meta.current_page || 1"></span> / <span x-text="deliveries.meta.last_page || 1"></span></div>
                                <button @@click="deliveriesNextPage()" :disabled="deliveries.page >= deliveries.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- ACTIVITY LOGS TAB                                      --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'activity'" x-cloak>
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="relative w-full xl:max-w-md">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <input type="text" x-model="activity.search" @@input.debounce.500ms="activity.page = 1; loadActivityLogs()" placeholder="Search activity..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="activity.showFilters = !activity.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="activity.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="activity.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <template x-for="col in activity.columns" :key="col.key"><button type="button" @@click="toggleActivityColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><span x-text="col.label"></span><svg x-show="activity.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <button type="button" @@click="downloadCSV('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="activity.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Activity Date</label><div class="relative"><input type="text" x-ref="activityCreatedRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg></div></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Action</label><select x-model="activity.action" @@change="activity.actionName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><option value="">All Actions</option><option value="login">Login</option><option value="logout">Logout</option><option value="register">Register</option><option value="login_otp_requested">Login OTP Requested</option><option value="verify_phone">Verify Phone</option><option value="profile_updated">Profile Updated</option></select></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Device Type</label><select x-model="activity.deviceType" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><option value="">All devices</option><option value="web">Web</option><option value="mobile">Mobile</option><option value="ios">iOS</option><option value="android">Android</option></select></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">IP Address</label><input type="text" x-model="activity.ipAddress" placeholder="IP address" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="activity.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('activity')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('activity')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('activity').length"><template x-for="chip in activeFilterChips('activity')" :key="chip.key"><span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200"><span x-text="chip.label"></span><button type="button" @@click="clearFilter('activity', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button></span></template></div>
                </div>
                <div class="relative overflow-hidden bg-white">
                    <div x-show="activity.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th x-show="activity.visibleColumns.action" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">ACTION</th>
                                    <th x-show="activity.visibleColumns.description" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">DESCRIPTION</th>
                                    <th x-show="activity.visibleColumns.device" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">DEVICE</th>
                                    <th x-show="activity.visibleColumns.ip_address" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">IP ADDRESS</th>
                                    <th x-show="activity.visibleColumns.created_at" @@click="sortActivity('created_at')" class="cursor-pointer px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">
                                        <div class="flex items-center">DATE<svg class="w-2.5 h-2.5 ml-1" :class="activity.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="activity.data.length === 0 && !activity.loading"><tr><td :colspan="visibleColumnCount('activity')" class="px-4 py-8 text-center text-gray-500 text-xs">No activity logs found</td></tr></template>
                                <template x-for="log in activity.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="activity.visibleColumns.action" class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="{'bg-emerald-100 text-emerald-700': ['login','register','verify_phone'].includes(log.action),'bg-blue-100 text-blue-700': ['profile_updated','login_otp_requested'].includes(log.action),'bg-slate-100 text-slate-700': log.action === 'logout'}" x-text="log.action ? log.action.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="activity.visibleColumns.description" class="px-4 py-3 text-xs text-slate-600 max-w-xs truncate" x-text="log.description || '-'"></td>
                                        <td x-show="activity.visibleColumns.device" class="px-4 py-3 whitespace-nowrap">
                                            <p class="text-xs font-medium text-slate-900" x-text="log.device_name || 'Unknown'"></p>
                                            <p class="text-xs text-slate-400" x-text="(log.device_type || '') + ' ' + (log.os_version || '')"></p>
                                        </td>
                                        <td x-show="activity.visibleColumns.ip_address" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600 font-mono" x-text="log.ip_address || '-'"></td>
                                        <td x-show="activity.visibleColumns.created_at" class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="activity.meta.from || 0"></span> to <span x-text="activity.meta.to || 0"></span> of <span x-text="activity.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="activityPrevPage()" :disabled="activity.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="activity.meta.current_page || 1"></span> / <span x-text="activity.meta.last_page || 1"></span></div>
                                <button @@click="activityNextPage()" :disabled="activity.page >= activity.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════ PACKAGES TAB ═══════════ --}}
            <div x-show="activeTab === 'packages'" x-cloak>
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="text" x-model="packages.search" @@input.debounce.500ms="packages.page = 1; loadPackages()" placeholder="Search packages..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" @@click="packages.showFilters = !packages.showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="packages.showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="packages.showFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="packages.showFilters" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Package Date</label><div class="relative"><input type="text" x-ref="packagesEventRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><svg class="absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg></div></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Scope</label><select x-model="packages.filter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><option value="current">Current packages</option><option value="all">All history</option></select></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">State</label><select x-model="packages.eventType" @@change="packages.eventTypeName = $event.target.selectedOptions[0].text" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"><option value="">All States</option><option value="claimed">Claimed</option><option value="released">Released</option><option value="delivered">Delivered</option></select></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Recipient</label><input type="text" x-model="packages.recipient" placeholder="Recipient name" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                            <div><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Location</label><input type="text" x-model="packages.location" placeholder="Town or area" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="packages.showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearTabFilters('packages')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="applyTabFilters('packages')" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips('packages').length"><template x-for="chip in activeFilterChips('packages')" :key="chip.key"><span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200"><span x-text="chip.label"></span><button type="button" @@click="clearFilter('packages', chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button></span></template></div>
                </div>

                <div class="relative overflow-hidden bg-white">
                    <div x-show="packages.loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] table-auto divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Package</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Shipment</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Recipient</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Location</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">State</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Date</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="packages.rows.length === 0 && !packages.loading">
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-sm font-semibold text-slate-400" x-text="packages.filter === 'current' ? 'No packages currently assigned' : 'No package history found'"></td>
                                    </tr>
                                </template>
                                <template x-for="pkg in packages.rows" :key="pkg.barcode + pkg.created_at">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3">
                                            <p class="text-sm font-black text-slate-900" x-text="pkg.description || 'Package'"></p>
                                            <p class="mt-1 font-mono text-[11px] font-semibold text-slate-500" x-text="pkg.barcode || '-'"></p>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-black text-orange-700" x-text="pkg.shipment_number || '-'"></td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-800" x-text="pkg.recipient_name || '-'"></p>
                                        </td>
                                        <td class="max-w-[320px] px-4 py-3 text-xs font-semibold leading-5 text-slate-600" x-text="pkg.delivery_town || '-'"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="pkg.event_type === 'claimed' ? 'bg-emerald-100 text-emerald-700' : pkg.event_type === 'delivered' ? 'bg-blue-100 text-blue-700' : pkg.event_type === 'released' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'"
                                                x-text="pkg.event_type ? pkg.event_type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-semibold text-slate-600" x-text="pkg.created_at || '-'"></td>
                                        <td class="px-4 py-3 text-right">
                                            <a x-show="pkg.shipment_number" :href="'/admin/shipments?search=' + encodeURIComponent(pkg.shipment_number)" class="inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Open</a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs font-semibold text-slate-600">Showing <span x-text="packages.meta.from || 0"></span> to <span x-text="packages.meta.to || 0"></span> of <span x-text="packages.meta.total || 0"></span></div>
                            <div class="flex items-center gap-1">
                                <button @@click="packagesPrevPage()" :disabled="packages.page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="packages.meta.current_page || 1"></span> / <span x-text="packages.meta.last_page || 1"></span></div>
                                <button @@click="packagesNextPage()" :disabled="packages.page >= packages.meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Edit Modal -->
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @@keydown.escape.window="showEditModal = false"
    >
        <!-- Backdrop -->
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showEditModal = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showEditModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @@click.stop
                class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20"
            >
                <!-- Header with Gradient -->
                <div class="border-b border-slate-200 bg-white px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Edit Rider/Driver</h3>
                                <p class="text-sm text-slate-500 mt-1">Update rider/driver information and settings</p>
                            </div>
                        </div>
                        <button @@click="showEditModal = false" class="flex-shrink-0 rounded-xl p-2 text-slate-400 hover:bg-white hover:text-slate-700 transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <form @@submit.prevent="saveDriver()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    x-model="form.name"
                                    class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                    placeholder="John Doe"
                                    required
                                >
                            </div>
                            <template x-if="errors.name">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        <!-- Email & Phone Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="email"
                                        x-model="form.email"
                                        class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                        placeholder="driver@example.com"
                                        required
                                    >
                                </div>
                                <template x-if="errors.email">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.email[0]"></p>
                                </template>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Phone <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.phone"
                                        class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                        placeholder="0241234567"
                                        required
                                    >
                                </div>
                                <template x-if="errors.phone">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.phone[0]"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    x-model="form.password"
                                    class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                    placeholder="Leave blank to keep current"
                                >
                            </div>
                            <template x-if="errors.password">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.password[0]"></p>
                            </template>
                        </div>

                        <!-- Vehicle Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <!-- Vehicle Type -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Vehicle Type
                                </label>
                                <input
                                    type="text"
                                    x-model="form.vehicle_type"
                                    class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                    placeholder="Motorcycle"
                                >
                            </div>

                            <!-- Vehicle Number -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Vehicle Number
                                </label>
                                <input
                                    type="text"
                                    x-model="form.vehicle_number"
                                    class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                    placeholder="GR-1234-21"
                                >
                            </div>

                            <!-- License Number -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    License Number
                                </label>
                                <input
                                    type="text"
                                    x-model="form.license_number"
                                    class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                    placeholder="DL-123456"
                                >
                            </div>
                        </div>

                        <!-- Task Capabilities -->
                        <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Assignment Capabilities</h4>
                                    <p class="text-xs text-slate-500">Select what this person is allowed to handle.</p>
                                </div>
                                <span class="text-xs font-semibold text-slate-600" x-text="(form.task_capabilities || []).length + ' selected'"></span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                    <input type="checkbox"
                                           value="pickup"
                                           x-model="form.task_capabilities"
                                           class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                                    <span class="text-sm font-medium text-slate-700">Pickup</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                    <input type="checkbox"
                                           value="transport"
                                           x-model="form.task_capabilities"
                                           class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                                    <span class="text-sm font-medium text-slate-700">Transport</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                    <input type="checkbox"
                                           value="delivery"
                                           x-model="form.task_capabilities"
                                           class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                                    <span class="text-sm font-medium text-slate-700">Delivery</span>
                                </label>
                            </div>

                            <template x-if="errors.task_capabilities">
                                <p class="mt-2 text-xs text-rose-600 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span x-text="errors.task_capabilities[0]"></span>
                                </p>
                            </template>
                        </div>

                        <!-- Status Toggle -->
                        <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800">Account Status</h4>
                                        <p class="text-xs text-slate-500" x-text="form.is_active ? 'Can accept assignments' : 'Account access disabled'"></p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @@click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-slate-300'"
                                    class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-sm"
                                >
                                    <span
                                        :class="form.is_active ? 'translate-x-7' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                                    ></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-5">
                        <button
                            type="button"
                            @@click="showEditModal = false"
                            class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                        >
                            <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div
        x-show="showToggleModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @@keydown.escape.window="showToggleModal = false"
    >
        <!-- Backdrop -->
        <div x-show="showToggleModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showToggleModal = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showToggleModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @@click.stop
                class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20"
            >
                <!-- Header -->
                <div class="p-6 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4"
                         :class="driver.is_active ? 'bg-amber-100' : 'bg-emerald-100'">
                        <svg x-show="driver.is_active" class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <svg x-show="!driver.is_active" x-cloak class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900" x-text="driver.is_active ? 'Deactivate Rider/Driver?' : 'Activate Rider/Driver?'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        <span x-show="driver.is_active">
                            Are you sure you want to deactivate <strong x-text="driver.name"></strong>? They will no longer be able to accept assignments.
                        </span>
                        <span x-show="!driver.is_active" x-cloak>
                            Are you sure you want to activate <strong x-text="driver.name"></strong>? They will be able to accept assignments again.
                        </span>
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex items-center gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-5">
                    <button
                        type="button"
                        @@click="showToggleModal = false"
                        class="flex-1 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @@click="toggleActive()"
                        :disabled="toggling"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl transition-all disabled:opacity-50"
                        :class="driver.is_active
                            ? 'bg-amber-500 hover:bg-amber-600 text-white'
                            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
                    >
                        <svg x-show="toggling" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="toggling ? 'Processing...' : (driver.is_active ? 'Yes, Deactivate' : 'Yes, Activate')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
