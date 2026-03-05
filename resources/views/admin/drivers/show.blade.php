@extends('admin.layouts.app')

@section('title', 'Driver - ' . $driver->name)
@section('breadcrumb-parent', 'Drivers')
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
    'canManage' => $canManage,
];
@endphp

@section('content')
<div x-data="driverShow()" data-driver-show-config="{{ json_encode($driverConfig) }}" class="space-y-6">

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <!-- Top Row: Back Button + Action Buttons -->
                <div class="flex items-center justify-between mb-6">
                    <a href="{{ route('admin.drivers.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Drivers</span>
                    </a>
                    @if($canManage)
                    <div class="flex items-center gap-2">
                        <button @@click="openEditModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm shadow-sm hover:shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit Profile
                        </button>
                        <button @@click="showToggleModal = true" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl border transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                            :class="driver.is_active ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30' : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/30'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            <span x-text="driver.is_active ? 'Deactivate' : 'Activate'"></span>
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Main Row: Profile LEFT, Summary RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-stretch gap-6">
                    <!-- LEFT: Driver Profile Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <!-- Avatar -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                                {{ strtoupper(substr($driver->name, 0, 1)) }}
                            </div>
                            <div class="absolute -bottom-1.5 -right-1.5 w-7 h-7 lg:w-8 lg:h-8 rounded-full
                                @if($driver->status === 'available') bg-gradient-to-br from-emerald-400 to-emerald-600
                                @elseif($driver->status === 'busy') bg-gradient-to-br from-amber-400 to-amber-600
                                @else bg-gradient-to-br from-slate-400 to-slate-500
                                @endif
                                border-4 border-slate-900 flex items-center justify-center shadow-lg">
                                @if($driver->status === 'available')
                                    <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @elseif($driver->status === 'busy')
                                    <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $driver->name }}</h1>
                                @if($driver->vehicle_type && $driver->vehicle_number)
                                    <p class="text-slate-400 text-sm mt-0.5 truncate">{{ $driver->vehicle_type }} &mdash; {{ $driver->vehicle_number }}</p>
                                @elseif($driver->vehicle_type)
                                    <p class="text-slate-400 text-sm mt-0.5 truncate">{{ $driver->vehicle_type }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="truncate">{{ $driver->email }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $driver->phone }}
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    @if($driver->status === 'available') bg-emerald-500/20 text-emerald-300
                                    @elseif($driver->status === 'busy') bg-amber-500/20 text-amber-300
                                    @else bg-slate-500/20 text-slate-300
                                    @endif">
                                    <span class="w-1.5 h-1.5 rounded-full
                                        @if($driver->status === 'available') bg-emerald-400
                                        @elseif($driver->status === 'busy') bg-amber-400
                                        @else bg-slate-400
                                        @endif"></span>
                                    {{ ucfirst($driver->status ?? 'offline') }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $driver->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $driver->is_active ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                    {{ $driver->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($driver->license_number)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/20 text-blue-300">
                                    {{ $driver->license_number }}
                                </span>
                                @endif
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $driver->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Stats -->
                    <div class="flex flex-col lg:ml-auto lg:justify-center">
                        <div class="flex items-stretch gap-2 flex-wrap lg:flex-nowrap h-full">
                            <!-- Pickups -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ number_format($pickupsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Pickups</p>
                                </div>
                            </div>
                            <!-- Transports -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500/30 to-indigo-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ number_format($transportManifestsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Transports</p>
                                </div>
                            </div>
                            <!-- Deliveries -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-emerald-400 leading-none">{{ number_format($deliveryRunsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Deliveries</p>
                                </div>
                            </div>
                            <!-- Last Login -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">
                                        @if($lastLogin) {{ $lastLogin->created_at->format('M d') }} @else &mdash; @endif
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Last Login</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        <!-- Sidebar Nav -->
        <aside class="w-60 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-5 px-3">
            <p class="px-2 mb-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Tasks</p>

            <!-- Pickups -->
            <button @@click="activeTab = 'pickups'; loadPickups()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'pickups' ? 'bg-blue-50 ring-1 ring-blue-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'pickups' ? 'bg-blue-500 shadow-md shadow-blue-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'pickups' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'pickups' ? 'font-bold text-blue-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Pickups</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'pickups' ? 'bg-blue-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $pickupsCount }}</span>
            </button>

            <!-- Transports -->
            <button @@click="activeTab = 'transports'; loadTransports()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'transports' ? 'bg-indigo-50 ring-1 ring-indigo-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'transports' ? 'bg-indigo-500 shadow-md shadow-indigo-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'transports' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'transports' ? 'font-bold text-indigo-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Transports</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'transports' ? 'bg-indigo-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $transportManifestsCount }}</span>
            </button>

            <!-- Deliveries -->
            <button @@click="activeTab = 'deliveries'; loadDeliveries()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'deliveries' ? 'bg-emerald-50 ring-1 ring-emerald-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'deliveries' ? 'bg-emerald-500 shadow-md shadow-emerald-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'deliveries' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'deliveries' ? 'font-bold text-emerald-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Deliveries</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'deliveries' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $deliveryRunsCount }}</span>
            </button>

            <!-- Divider: Activity -->
            <div class="flex items-center gap-2 mt-4 mb-2 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Activity</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Activity Logs -->
            <button @@click="activeTab = 'activity'; loadActivityLogs()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'activity' ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'activity' ? 'bg-violet-500 shadow-md shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'activity' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'activity' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Activity Logs</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'activity' ? 'bg-violet-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $activityLogsCount }}</span>
            </button>
        </aside>

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">
            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PICKUPS TAB                                            --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'pickups'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="pickups.search" @@input.debounce.500ms="pickups.page = 1; loadPickups()" placeholder="Search pickups..." class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select x-model="pickups.status" @@change="pickups.page = 1; loadPickups()" class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors cursor-pointer">
                            <option value="">All Statuses</option>
                            <option value="assigned">Assigned</option>
                            <option value="en_route">En Route</option>
                            <option value="arrived">Arrived</option>
                            <option value="picking_up">Picking Up</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in pickups.columns" :key="col.key">
                                    <button type="button" @@click="togglePickupsColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="pickups.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('pickups'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('pickups'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Table -->
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="pickups.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="pickups.visibleColumns.shipment_number" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">SHIPMENT #</th>
                                    <th x-show="pickups.visibleColumns.vendor_name" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">VENDOR</th>
                                    <th x-show="pickups.visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th x-show="pickups.visibleColumns.assigned_at" @@click="sortPickups('assigned_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center">ASSIGNED AT<svg class="w-2.5 h-2.5 ml-1" :class="pickups.sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="pickups.visibleColumns.completed_at" @@click="sortPickups('completed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center">COMPLETED AT<svg class="w-2.5 h-2.5 ml-1" :class="pickups.sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="pickups.data.length === 0 && !pickups.loading">
                                    <tr><td :colspan="visibleColumnCount('pickups') + 1" class="px-4 py-8 text-center text-gray-500 text-xs">No pickups found</td></tr>
                                </template>
                                <template x-for="pickup in pickups.data" :key="pickup.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="pickups.visibleColumns.shipment_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="pickup.shipment_number || '-'"></td>
                                        <td x-show="pickups.visibleColumns.vendor_name" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="pickup.vendor_name || '-'"></td>
                                        <td x-show="pickups.visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
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
                                        <td x-show="pickups.visibleColumns.assigned_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(pickup.assigned_at)"></td>
                                        <td x-show="pickups.visibleColumns.completed_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="pickup.completed_at ? formatDateTime(pickup.completed_at) : '-'"></td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <a x-show="pickup.view_url" :href="pickup.view_url" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="pickups.meta.from"></span> to <span x-text="pickups.meta.to"></span> of <span x-text="pickups.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="pickups.perPage"></span><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                            <button type="button" @@click="setPickupsPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="pickups.perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="setPickupsPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="pickups.perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="setPickupsPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="pickups.perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="pickups.meta.current_page"></span> of <span x-text="pickups.meta.last_page"></span></div>
                                <div class="flex space-x-1">
                                    <button @@click="pickupsFirstPage()" :disabled="pickups.page === 1" :class="pickups.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @@click="pickupsPrevPage()" :disabled="pickups.page === 1" :class="pickups.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @@click="pickupsNextPage()" :disabled="pickups.page === pickups.meta.last_page" :class="pickups.page === pickups.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @@click="pickupsLastPage()" :disabled="pickups.page === pickups.meta.last_page" :class="pickups.page === pickups.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- TRANSPORTS TAB                                         --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'transports'" x-cloak>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="transports.search" @@input.debounce.500ms="transports.page = 1; loadTransports()" placeholder="Search transports..." class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select x-model="transports.status" @@change="transports.page = 1; loadTransports()" class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors cursor-pointer">
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
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in transports.columns" :key="col.key"><button type="button" @@click="toggleTransportsColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><span x-text="col.label"></span><svg x-show="transports.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('transports'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('transports'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="transports.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="transports.visibleColumns.manifest_number" @@click="sortTransports('manifest_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">MANIFEST #<svg class="w-2.5 h-2.5 ml-1" :class="transports.sortBy === 'manifest_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="transports.visibleColumns.origin_warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ORIGIN</th>
                                    <th x-show="transports.visibleColumns.destination_warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESTINATION</th>
                                    <th x-show="transports.visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th x-show="transports.visibleColumns.assigned_at" @@click="sortTransports('assigned_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">ASSIGNED AT<svg class="w-2.5 h-2.5 ml-1" :class="transports.sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="transports.visibleColumns.received_at" @@click="sortTransports('received_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">RECEIVED AT<svg class="w-2.5 h-2.5 ml-1" :class="transports.sortBy === 'received_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="transports.data.length === 0 && !transports.loading"><tr><td :colspan="visibleColumnCount('transports') + 1" class="px-4 py-8 text-center text-gray-500 text-xs">No transport manifests found</td></tr></template>
                                <template x-for="transport in transports.data" :key="transport.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="transports.visibleColumns.manifest_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="transport.manifest_number"></td>
                                        <td x-show="transports.visibleColumns.origin_warehouse" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="transport.origin_warehouse || '-'"></td>
                                        <td x-show="transports.visibleColumns.destination_warehouse" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="transport.destination_warehouse || '-'"></td>
                                        <td x-show="transports.visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="{'bg-slate-100 text-slate-700': transport.status === 'draft','bg-blue-100 text-blue-700': transport.status === 'assigned','bg-indigo-100 text-indigo-700': transport.status === 'loading','bg-amber-100 text-amber-700': transport.status === 'in_transit','bg-violet-100 text-violet-700': transport.status === 'arrived','bg-emerald-100 text-emerald-700': transport.status === 'received','bg-rose-100 text-rose-700': transport.status === 'cancelled'}" x-text="transport.status ? transport.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="transports.visibleColumns.assigned_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(transport.assigned_at)"></td>
                                        <td x-show="transports.visibleColumns.received_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="transport.received_at ? formatDateTime(transport.received_at) : '-'"></td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <a :href="transport.view_url" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="transports.meta.from"></span> to <span x-text="transports.meta.to"></span> of <span x-text="transports.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"><span x-text="transports.perPage"></span><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                            <button type="button" @@click="setTransportsPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="transports.perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="setTransportsPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="transports.perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="setTransportsPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="transports.perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="transports.meta.current_page"></span> of <span x-text="transports.meta.last_page"></span></div>
                                <div class="flex space-x-1">
                                    <button @@click="transportsFirstPage()" :disabled="transports.page === 1" :class="transports.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @@click="transportsPrevPage()" :disabled="transports.page === 1" :class="transports.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @@click="transportsNextPage()" :disabled="transports.page === transports.meta.last_page" :class="transports.page === transports.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @@click="transportsLastPage()" :disabled="transports.page === transports.meta.last_page" :class="transports.page === transports.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- DELIVERIES TAB                                         --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'deliveries'" x-cloak>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="deliveries.search" @@input.debounce.500ms="deliveries.page = 1; loadDeliveries()" placeholder="Search deliveries..." class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select x-model="deliveries.status" @@change="deliveries.page = 1; loadDeliveries()" class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors cursor-pointer">
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="assigned">Assigned</option>
                            <option value="out_for_delivery">Out for Delivery</option>
                            <option value="partially_delivered">Partially Delivered</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in deliveries.columns" :key="col.key"><button type="button" @@click="toggleDeliveriesColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><span x-text="col.label"></span><svg x-show="deliveries.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('deliveries'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('deliveries'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="deliveries.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="deliveries.visibleColumns.run_number" @@click="sortDeliveries('run_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">RUN #<svg class="w-2.5 h-2.5 ml-1" :class="deliveries.sortBy === 'run_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="deliveries.visibleColumns.warehouse" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">WAREHOUSE</th>
                                    <th x-show="deliveries.visibleColumns.stops_count" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STOPS</th>
                                    <th x-show="deliveries.visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th x-show="deliveries.visibleColumns.assigned_at" @@click="sortDeliveries('assigned_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">ASSIGNED AT<svg class="w-2.5 h-2.5 ml-1" :class="deliveries.sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="deliveries.visibleColumns.completed_at" @@click="sortDeliveries('completed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">COMPLETED AT<svg class="w-2.5 h-2.5 ml-1" :class="deliveries.sortBy === 'completed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="deliveries.data.length === 0 && !deliveries.loading"><tr><td :colspan="visibleColumnCount('deliveries') + 1" class="px-4 py-8 text-center text-gray-500 text-xs">No delivery runs found</td></tr></template>
                                <template x-for="delivery in deliveries.data" :key="delivery.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="deliveries.visibleColumns.run_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="delivery.run_number"></td>
                                        <td x-show="deliveries.visibleColumns.warehouse" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="delivery.warehouse || '-'"></td>
                                        <td x-show="deliveries.visibleColumns.stops_count" class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="delivery.stops_count"></span>
                                        </td>
                                        <td x-show="deliveries.visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="{'bg-slate-100 text-slate-700': delivery.status === 'draft','bg-blue-100 text-blue-700': delivery.status === 'assigned','bg-amber-100 text-amber-700': delivery.status === 'out_for_delivery','bg-orange-100 text-orange-700': delivery.status === 'partially_delivered','bg-emerald-100 text-emerald-700': delivery.status === 'completed','bg-rose-100 text-rose-700': delivery.status === 'cancelled'}" x-text="delivery.status ? delivery.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="deliveries.visibleColumns.assigned_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(delivery.assigned_at)"></td>
                                        <td x-show="deliveries.visibleColumns.completed_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="delivery.completed_at ? formatDateTime(delivery.completed_at) : '-'"></td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <a :href="delivery.view_url" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="deliveries.meta.from"></span> to <span x-text="deliveries.meta.to"></span> of <span x-text="deliveries.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"><span x-text="deliveries.perPage"></span><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                            <button type="button" @@click="setDeliveriesPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="deliveries.perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="setDeliveriesPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="deliveries.perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="setDeliveriesPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="deliveries.perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="deliveries.meta.current_page"></span> of <span x-text="deliveries.meta.last_page"></span></div>
                                <div class="flex space-x-1">
                                    <button @@click="deliveriesFirstPage()" :disabled="deliveries.page === 1" :class="deliveries.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @@click="deliveriesPrevPage()" :disabled="deliveries.page === 1" :class="deliveries.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @@click="deliveriesNextPage()" :disabled="deliveries.page === deliveries.meta.last_page" :class="deliveries.page === deliveries.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @@click="deliveriesLastPage()" :disabled="deliveries.page === deliveries.meta.last_page" :class="deliveries.page === deliveries.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- ACTIVITY LOGS TAB                                      --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'activity'" x-cloak>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="activity.search" @@input.debounce.500ms="activity.page = 1; loadActivityLogs()" placeholder="Search activity..." class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select x-model="activity.action" @@change="activity.page = 1; loadActivityLogs()" class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors cursor-pointer">
                            <option value="">All Actions</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                            <option value="register">Register</option>
                            <option value="login_otp_requested">Login OTP Requested</option>
                            <option value="verify_phone">Verify Phone</option>
                            <option value="profile_updated">Profile Updated</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>View<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in activity.columns" :key="col.key"><button type="button" @@click="toggleActivityColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><span x-text="col.label"></span><svg x-show="activity.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="activity.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="activity.visibleColumns.action" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTION</th>
                                    <th x-show="activity.visibleColumns.description" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DESCRIPTION</th>
                                    <th x-show="activity.visibleColumns.device" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DEVICE</th>
                                    <th x-show="activity.visibleColumns.ip_address" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">IP ADDRESS</th>
                                    <th x-show="activity.visibleColumns.created_at" @@click="sortActivity('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center">DATE<svg class="w-2.5 h-2.5 ml-1" :class="activity.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="activity.data.length === 0 && !activity.loading"><tr><td :colspan="visibleColumnCount('activity')" class="px-4 py-8 text-center text-gray-500 text-xs">No activity logs found</td></tr></template>
                                <template x-for="log in activity.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="activity.visibleColumns.action" class="px-4 py-2.5 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="{'bg-emerald-100 text-emerald-700': ['login','register','verify_phone'].includes(log.action),'bg-blue-100 text-blue-700': ['profile_updated','login_otp_requested'].includes(log.action),'bg-slate-100 text-slate-700': log.action === 'logout'}" x-text="log.action ? log.action.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-'"></span>
                                        </td>
                                        <td x-show="activity.visibleColumns.description" class="px-4 py-2.5 text-xs text-slate-600 max-w-xs truncate" x-text="log.description || '-'"></td>
                                        <td x-show="activity.visibleColumns.device" class="px-4 py-2.5 whitespace-nowrap">
                                            <p class="text-xs font-medium text-slate-900" x-text="log.device_name || 'Unknown'"></p>
                                            <p class="text-xs text-slate-400" x-text="(log.device_type || '') + ' ' + (log.os_version || '')"></p>
                                        </td>
                                        <td x-show="activity.visibleColumns.ip_address" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600 font-mono" x-text="log.ip_address || '-'"></td>
                                        <td x-show="activity.visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="activity.meta.from"></span> to <span x-text="activity.meta.to"></span> of <span x-text="activity.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"><span x-text="activity.perPage"></span><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                            <button type="button" @@click="setActivityPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="activity.perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="setActivityPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="activity.perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="setActivityPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="activity.perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="activity.meta.current_page"></span> of <span x-text="activity.meta.last_page"></span></div>
                                <div class="flex space-x-1">
                                    <button @@click="activityFirstPage()" :disabled="activity.page === 1" :class="activity.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @@click="activityPrevPage()" :disabled="activity.page === 1" :class="activity.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @@click="activityNextPage()" :disabled="activity.page === activity.meta.last_page" :class="activity.page === activity.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @@click="activityLastPage()" :disabled="activity.page === activity.meta.last_page" :class="activity.page === activity.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                class="relative w-full max-w-lg bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50"
            >
                <!-- Header with Gradient -->
                <div class="relative bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-5 border-b border-slate-200/50 rounded-t-2xl">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center shadow-lg shadow-slate-900/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Edit Driver</h3>
                                <p class="text-sm text-slate-500 mt-1">Update driver information and settings</p>
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
                                Driver Name <span class="text-rose-500">*</span>
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
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
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
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
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
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
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
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
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
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
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
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="DL-123456"
                                >
                            </div>
                        </div>

                        <!-- Task Capabilities -->
                        <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Driver Capabilities</h4>
                                    <p class="text-xs text-slate-500">Select what this driver is allowed to handle.</p>
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
                                        <p class="text-xs text-slate-500" x-text="form.is_active ? 'Driver can accept assignments' : 'Driver access disabled'"></p>
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
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200/50 bg-gradient-to-r from-slate-50/50 to-slate-100/30 px-6 py-5 rounded-b-2xl">
                        <button
                            type="button"
                            @@click="showEditModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all shadow-sm"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-slate-950 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-slate-900/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
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
                class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50 overflow-hidden"
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

                    <h3 class="text-xl font-bold text-slate-900" x-text="driver.is_active ? 'Deactivate Driver?' : 'Activate Driver?'"></h3>
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
                <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                    <button
                        type="button"
                        @@click="showToggleModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all"
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



