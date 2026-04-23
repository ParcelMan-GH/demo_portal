@extends('admin.layouts.app')

@section('title', 'Vendor - ' . $vendor->name)
@section('breadcrumb-parent', 'Vendors')
@section('breadcrumb-current', $vendor->name)

@php
$vendorConfig = [
    'vendor' => $vendor,
    'shipmentsEndpoint' => route('admin.vendors.shipments', $vendor),
    'activityLogsEndpoint' => route('admin.vendors.activity-logs', $vendor),
    'otpLogsEndpoint' => route('admin.vendors.otp-logs', $vendor),
    'updateEndpoint' => route('admin.vendors.update', $vendor),
    'toggleActiveEndpoint' => route('admin.vendors.toggle-active', $vendor),
    'restoreEndpoint' => route('admin.vendors.restore', $vendor),
    'deleteEndpoint' => route('admin.vendors.destroy', $vendor),
    'isDeleted' => $vendor->trashed(),
    'canManage' => $canManage,
    'statuses' => $statuses,
    'globalCommissionRate' => number_format($globalCommissionRate, 2),
];
@endphp

@section('content')
<div x-data="vendorShow()" data-vendor-show-config="{{ json_encode($vendorConfig) }}" class="space-y-6">

    @if($vendor->trashed())
    <!-- Deleted Vendor Banner -->
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-100">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-800">This vendor was deleted on {{ $vendor->deleted_at->format('M d, Y \a\t H:i') }}</p>
                    <p class="text-xs text-red-600">All API access has been revoked.</p>
                </div>
            </div>
            @if($canManage)
            <button
                @@click="showRestoreModal = true"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Restore Vendor
            </button>
            @endif
        </div>
    </div>
    @endif

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
                    <a href="{{ route('admin.vendors.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Vendors</span>
                    </a>
                    @if($canManage)
                    <div class="flex items-center gap-2">
                        <button
                            @@click="openEditModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Edit Profile
                        </button>
                        <button
                            @@click="showToggleModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl border transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                            :class="vendor.is_active
                                ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30'
                                : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/30'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <span x-text="vendor.is_active ? 'Deactivate' : 'Activate'"></span>
                        </button>
                        <button
                            @@click="showDeleteModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 text-xs font-semibold rounded-xl border border-rose-500/30 transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Main Row: Profile LEFT, Summary + Actions RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-stretch gap-6">
                    <!-- LEFT: Vendor Profile Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <!-- Avatar -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl lg:text-3xl font-bold shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                                {{ strtoupper(substr($vendor->name, 0, 1)) }}
                            </div>
                            <div class="absolute -bottom-1.5 -right-1.5 w-7 h-7 lg:w-8 lg:h-8 rounded-full {{ $vendor->is_active ? 'bg-gradient-to-br from-emerald-400 to-emerald-600' : 'bg-gradient-to-br from-slate-400 to-slate-500' }} border-4 border-slate-900 flex items-center justify-center shadow-lg">
                                @if($vendor->is_active)
                                    <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
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
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $vendor->name }}</h1>
                                @if($vendor->business_name)
                                    <p class="text-slate-400 text-sm mt-0.5 truncate">{{ $vendor->business_name }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="truncate">{{ $vendor->email }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $vendor->phone }}
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $vendor->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $vendor->is_active ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                    {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $vendor->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Stats -->
                    <div class="flex flex-col lg:ml-auto lg:justify-center">
                        <!-- Summary Stats - 4 compact cards in one row -->
                        <div class="flex items-stretch gap-2 flex-wrap lg:flex-nowrap h-full">
                            <!-- Total Shipments -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ number_format($shipmentStats['total']) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Shipments</p>
                                </div>
                            </div>

                            <!-- Delivered -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-emerald-400 leading-none">{{ number_format($shipmentStats['delivered']) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Delivered</p>
                                </div>
                            </div>

                            <!-- Activities -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ number_format($activityLogsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Activities</p>
                                </div>
                            </div>

                            <!-- Last Login -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2 flex items-center gap-2.5 transition-colors flex-1">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">
                                        @if($lastLogin)
                                            {{ $lastLogin->created_at->format('M d') }}
                                        @else
                                            —
                                        @endif
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

            <!-- Section: Shipments -->
            <p class="px-2 mb-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Shipments</p>

            <!-- Shipments -->
            <button @@click="activeTab = 'shipments'; loadShipments()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'shipments'
                    ? 'bg-blue-50 ring-1 ring-blue-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'shipments' ? 'bg-blue-500 shadow-md shadow-blue-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'shipments' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'shipments' ? 'font-bold text-blue-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Shipments</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'shipments' ? 'bg-blue-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $shipmentsCount }}</span>
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
                :class="activeTab === 'activity'
                    ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'activity' ? 'bg-violet-500 shadow-md shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'activity' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'activity' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Activity Logs</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'activity' ? 'bg-violet-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $activityLogsCount }}</span>
            </button>

            <!-- OTP Logs -->
            <button @@click="activeTab = 'otp'; loadOtpLogs()"
                class="group flex items-center gap-3 w-full px-2.5 py-2 rounded-xl mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'otp'
                    ? 'bg-amber-50 ring-1 ring-amber-100 shadow-sm'
                    : 'hover:bg-slate-50'">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'otp' ? 'bg-amber-500 shadow-md shadow-amber-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3.5 h-3.5 transition-colors" :class="activeTab === 'otp' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <span class="flex-1 text-sm transition-colors" :class="activeTab === 'otp' ? 'font-bold text-amber-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">OTP Logs</span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[10px] font-bold rounded-full transition-all"
                    :class="activeTab === 'otp' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">{{ $otpLogsCount }}</span>
            </button>

        </aside>

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">
            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- SHIPMENTS TAB                                          --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'shipments'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="shipments.search" @@input.debounce.500ms="shipments.page = 1; loadShipments()" placeholder="Search shipments..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select x-model="shipments.status" @@change="shipments.page = 1; loadShipments()" class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors cursor-pointer">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <!-- View Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in shipments.columns" :key="col.key">
                                    <button type="button" @@click="toggleShipmentColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="shipments.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('shipments'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('shipments'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="shipments.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="shipments.visibleColumns.shipment_number" @@click="sortShipments('shipment_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center">SHIPMENT #<svg class="w-2.5 h-2.5 ml-1" :class="shipments.sortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="shipments.visibleColumns.recipient" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">RECIPIENT</th>
                                    <th x-show="shipments.visibleColumns.location" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">LOCATION</th>
                                    <th x-show="shipments.visibleColumns.items" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ITEMS</th>
                                    <th x-show="shipments.visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th x-show="shipments.visibleColumns.created_at" @@click="sortShipments('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center">CREATED<svg class="w-2.5 h-2.5 ml-1" :class="shipments.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="shipments.data.length === 0 && !shipments.loading">
                                    <tr><td :colspan="visibleColumnCount('shipments')" class="px-4 py-8 text-center text-gray-500 text-xs">No shipments found</td></tr>
                                </template>
                                <template x-for="shipment in shipments.data" :key="shipment.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="shipments.visibleColumns.shipment_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="shipment.shipment_number"></td>
                                        <td x-show="shipments.visibleColumns.recipient" class="px-4 py-2.5 whitespace-nowrap">
                                            <p class="text-xs font-medium text-slate-900" x-text="shipment.recipient_name"></p>
                                            <p class="text-xs text-slate-500" x-text="shipment.recipient_phone"></p>
                                        </td>
                                        <td x-show="shipments.visibleColumns.location" class="px-4 py-2.5 whitespace-nowrap">
                                            <p class="text-xs text-slate-600" x-text="shipment.region || '-'"></p>
                                            <p class="text-xs text-slate-400" x-text="shipment.district || ''"></p>
                                        </td>
                                        <td x-show="shipments.visibleColumns.items" class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="shipment.items_count"></span>
                                        </td>
                                        <td x-show="shipments.visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-slate-100 text-slate-700': shipment.status === 'draft',
                                                    'bg-blue-100 text-blue-700': ['submitted', 'invoice_sent', 'invoice_accepted'].includes(shipment.status),
                                                    'bg-violet-100 text-violet-700': ['pickup_assigned', 'picked_up', 'at_warehouse', 'sorted'].includes(shipment.status),
                                                    'bg-amber-100 text-amber-700': ['in_transit', 'at_destination', 'out_for_delivery'].includes(shipment.status),
                                                    'bg-emerald-100 text-emerald-700': shipment.status === 'delivered',
                                                    'bg-rose-100 text-rose-700': shipment.status === 'cancelled'
                                                }" x-text="shipment.status_label"></span>
                                        </td>
                                        <td x-show="shipments.visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(shipment.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="shipments.meta.from"></span> to <span x-text="shipments.meta.to"></span> of <span x-text="shipments.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="shipments.perPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                            <button type="button" @@click="setShipmentsPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="shipments.perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="setShipmentsPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="shipments.perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="setShipmentsPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="shipments.perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="shipments.meta.current_page"></span> of <span x-text="shipments.meta.last_page"></span></div>
                                <div class="flex space-x-1">
                                    <button @@click="shipmentsFirstPage()" :disabled="shipments.page === 1" :class="shipments.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @@click="shipmentsPrevPage()" :disabled="shipments.page === 1" :class="shipments.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @@click="shipmentsNextPage()" :disabled="shipments.page === shipments.meta.last_page" :class="shipments.page === shipments.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @@click="shipmentsLastPage()" :disabled="shipments.page === shipments.meta.last_page" :class="shipments.page === shipments.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
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
                <!-- Controls -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="activity.search" @@input.debounce.500ms="activity.page = 1; loadActivityLogs()" placeholder="Search activity..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
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
                        <!-- View Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in activity.columns" :key="col.key">
                                    <button type="button" @@click="toggleActivityColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="activity.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('activity'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="activity.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] md:min-w-full divide-y divide-slate-200/50 text-xs">
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
                                <template x-if="activity.data.length === 0 && !activity.loading">
                                    <tr><td :colspan="visibleColumnCount('activity')" class="px-4 py-8 text-center text-gray-500 text-xs">No activity logs found</td></tr>
                                </template>
                                <template x-for="log in activity.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="activity.visibleColumns.action" class="px-4 py-2.5 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-700': ['login', 'register', 'verify_phone'].includes(log.action),
                                                    'bg-blue-100 text-blue-700': ['profile_updated', 'login_otp_requested'].includes(log.action),
                                                    'bg-slate-100 text-slate-700': log.action === 'logout'
                                                }" x-text="log.action.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
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
                    <!-- Pagination -->
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="activity.meta.from"></span> to <span x-text="activity.meta.to"></span> of <span x-text="activity.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="activity.perPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
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

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- OTP LOGS TAB                                           --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'otp'" x-cloak>
                <!-- Controls -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <select x-model="otp.purpose" @@change="otp.page = 1; loadOtpLogs()" class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors cursor-pointer">
                            <option value="">All Purposes</option>
                            <option value="registration">Registration</option>
                            <option value="login">Login</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <!-- View Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <template x-for="col in otp.columns" :key="col.key">
                                    <button type="button" @@click="toggleOtpColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="otp.visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                <button type="button" @@click="downloadCSV('otp'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printTable('otp'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="otp.loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display: none;"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="otp.visibleColumns.code" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">CODE</th>
                                    <th x-show="otp.visibleColumns.purpose" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">PURPOSE</th>
                                    <th x-show="otp.visibleColumns.status" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th x-show="otp.visibleColumns.expires_at" @@click="sortOtp('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center">EXPIRES AT<svg class="w-2.5 h-2.5 ml-1" :class="otp.sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="otp.visibleColumns.verified_at" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">VERIFIED AT</th>
                                    <th x-show="otp.visibleColumns.created_at" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">CREATED</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="otp.data.length === 0 && !otp.loading">
                                    <tr><td :colspan="visibleColumnCount('otp')" class="px-4 py-8 text-center text-gray-500 text-xs">No OTP logs found</td></tr>
                                </template>
                                <template x-for="log in otp.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="otp.visibleColumns.code" class="px-4 py-2.5 whitespace-nowrap">
                                            <span class="text-sm font-mono font-bold text-slate-900 tracking-wider" x-text="log.code"></span>
                                        </td>
                                        <td x-show="otp.visibleColumns.purpose" class="px-4 py-2.5 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                :class="{ 'bg-blue-100 text-blue-700': log.purpose === 'registration', 'bg-emerald-100 text-emerald-700': log.purpose === 'login' }"
                                                x-text="log.purpose.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></span>
                                        </td>
                                        <td x-show="otp.visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <template x-if="log.is_verified">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    Verified
                                                </span>
                                            </template>
                                            <template x-if="!log.is_verified && log.is_expired">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                    Expired
                                                </span>
                                            </template>
                                            <template x-if="!log.is_verified && !log.is_expired">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                                    Pending
                                                </span>
                                            </template>
                                        </td>
                                        <td x-show="otp.visibleColumns.expires_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.expires_at)"></td>
                                        <td x-show="otp.visibleColumns.verified_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="log.verified_at ? formatDateTime(log.verified_at) : '-'"></td>
                                        <td x-show="otp.visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">Showing <span x-text="otp.meta.from"></span> to <span x-text="otp.meta.to"></span> of <span x-text="otp.meta.total"></span> results</div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="otp.perPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                            <button type="button" @@click="setOtpPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="otp.perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                            <button type="button" @@click="setOtpPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="otp.perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @@click="setOtpPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="otp.perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="otp.meta.current_page"></span> of <span x-text="otp.meta.last_page"></span></div>
                                <div class="flex space-x-1">
                                    <button @@click="otpFirstPage()" :disabled="otp.page === 1" :class="otp.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @@click="otpPrevPage()" :disabled="otp.page === 1" :class="otp.page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @@click="otpNextPage()" :disabled="otp.page === otp.meta.last_page" :class="otp.page === otp.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @@click="otpLastPage()" :disabled="otp.page === otp.meta.last_page" :class="otp.page === otp.meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
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
                                <h3 class="text-xl font-bold text-slate-900">Edit Vendor</h3>
                                <p class="text-sm text-slate-500 mt-1">Update vendor information and settings</p>
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
                <form @@submit.prevent="saveVendor()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Vendor Name <span class="text-rose-500">*</span>
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

                        <!-- Business Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Business Name <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    x-model="form.business_name"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="Acme Corporation"
                                >
                            </div>
                        </div>

                        <!-- Email & Phone Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Email <span class="text-slate-400 text-xs font-normal">(Optional)</span>
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
                                        placeholder="vendor@example.com"
                                    >
                                </div>
                                <template x-if="errors.email">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.email[0]"></p>
                                </template>
                            </div>

                            <!-- Phone (read-only) -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Phone
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
                                        disabled
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-slate-50 text-sm text-slate-500 transition-all cursor-not-allowed"
                                    >
                                </div>
                                <p class="mt-1 text-xs text-slate-400">Phone number cannot be changed after creation</p>
                            </div>
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
                                        <p class="text-xs text-slate-500" x-text="form.is_active ? 'Vendor can access portal' : 'Vendor access disabled'"></p>
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

                        <!-- Payout Settings -->
                        <div class="bg-amber-50/40 rounded-2xl p-5 border border-amber-200/60">
                            <div class="flex items-start gap-3 mb-4">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-sm shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Commission Rate Override</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Overrides the global commission per delivered package for this vendor only.</p>
                                </div>
                            </div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Rate per package (GHS)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-slate-400 pointer-events-none">GHS</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    x-model="form.commission_rate_override"
                                    :placeholder="'Default: GHS ' + (config.globalCommissionRate ?? '0.00')"
                                    class="w-full pl-14 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                >
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                <strong>Leave blank</strong> to use the global default (GHS <span x-text="config.globalCommissionRate ?? '0.00'"></span>).
                                <br>
                                <strong>Enter 0</strong> to give this vendor no commission per package.
                            </p>
                            <template x-if="errors.commission_rate_override">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.commission_rate_override[0]"></p>
                            </template>
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
                         :class="vendor.is_active ? 'bg-amber-100' : 'bg-emerald-100'">
                        <svg x-show="vendor.is_active" class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <svg x-show="!vendor.is_active" x-cloak class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900" x-text="vendor.is_active ? 'Deactivate Vendor?' : 'Activate Vendor?'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        <span x-show="vendor.is_active">
                            Are you sure you want to deactivate <strong x-text="vendor.name"></strong>? They will no longer be able to access the vendor portal.
                        </span>
                        <span x-show="!vendor.is_active" x-cloak>
                            Are you sure you want to activate <strong x-text="vendor.name"></strong>? They will be able to access the vendor portal again.
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
                        :class="vendor.is_active
                            ? 'bg-amber-500 hover:bg-amber-600 text-white'
                            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
                    >
                        <svg x-show="toggling" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="toggling ? 'Processing...' : (vendor.is_active ? 'Yes, Deactivate' : 'Yes, Activate')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Vendor Modal -->
    <div x-show="showRestoreModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display:none">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="showRestoreModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @@click.stop>
            <div class="px-6 py-8 text-center">
                <div class="mx-auto mb-4 flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Restore Vendor?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to restore <strong x-text="vendor.name"></strong>? Their account will be recovered in an inactive state and will need to be manually activated.
                </p>
            </div>
            <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                <button type="button" @@click="showRestoreModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="button" @@click="restoreVendor()" :disabled="restoring"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-all disabled:opacity-50">
                    <svg x-show="restoring" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="restoring ? 'Restoring...' : 'Yes, Restore'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Vendor Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display:none">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="showDeleteModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @@click.stop>
            <div class="px-6 py-8 text-center">
                <div class="mx-auto mb-4 flex items-center justify-center w-16 h-16 rounded-full bg-rose-100">
                    <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Delete Vendor?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to delete <strong x-text="vendor.name"></strong>? Their API access will be revoked and phone number freed for re-registration. Shipment and invoice records will be preserved.
                </p>
            </div>
            <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                <button type="button" @@click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="button" @@click="deleteVendor()" :disabled="deleting"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl bg-rose-500 hover:bg-rose-600 text-white transition-all disabled:opacity-50">
                    <svg x-show="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="deleting ? 'Deleting...' : 'Yes, Delete'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection



