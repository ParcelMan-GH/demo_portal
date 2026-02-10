@extends('admin.layouts.app')

@section('title', 'Driver - ' . $driver->name)
@section('breadcrumb-parent', 'Drivers')
@section('breadcrumb-current', $driver->name)

@php
$driverConfig = [
    'driver' => $driver,
    'assignmentsEndpoint' => route('admin.drivers.assignments', $driver),
    'activityLogsEndpoint' => route('admin.drivers.activity-logs', $driver),
    'updateEndpoint' => route('admin.drivers.update', $driver),
    'toggleActiveEndpoint' => route('admin.drivers.toggle-active', $driver),
    'canManage' => $canManage,
];
@endphp

@section('content')
<script>
    window.driverShowConfig = {!! json_encode($driverConfig) !!};
</script>
<div x-data="driverShow()" class="space-y-6">

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
                <!-- Top Row: Back Button -->
                <div class="mb-6">
                    <a href="{{ route('admin.drivers.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Drivers</span>
                    </a>
                </div>

                <!-- Main Row: Profile LEFT, Summary + Actions RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
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

                    <!-- RIGHT: Action Buttons (row 1) + Summary Stats (row 2) -->
                    <div class="flex flex-col gap-3 lg:ml-auto lg:items-end">
                        <!-- Row 1: Action Buttons -->
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
                                :class="driver.is_active
                                    ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30'
                                    : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/30'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <span x-text="driver.is_active ? 'Deactivate' : 'Activate'"></span>
                            </button>
                        </div>
                        @endif

                        <!-- Row 2: Summary Stats - 4 compact cards in one row -->
                        <div class="flex items-center gap-2 flex-wrap lg:flex-nowrap">
                            <!-- Total Assignments -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ number_format($assignmentsCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Assignments</p>
                                </div>
                            </div>

                            <!-- Completed Pickups -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-emerald-400 leading-none">{{ number_format($completedCount) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Completed</p>
                                </div>
                            </div>

                            <!-- Active Assignment -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $activeAssignment ? 'Yes' : 'No' }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Active Job</p>
                                </div>
                            </div>

                            <!-- Last Login -->
                            <div class="bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
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
                                            &mdash;
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
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Tab Headers -->
        <div class="bg-gradient-to-r from-slate-50 to-slate-100/50 border-b border-slate-200 px-4">
            <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
                <button
                    @@click="activeTab = 'assignments'; loadAssignments()"
                    :class="activeTab === 'assignments'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'assignments' ? 'text-blue-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Assignments</span>
                    <span :class="activeTab === 'assignments' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ $assignmentsCount }}</span>
                </button>
                <button
                    @@click="activeTab = 'activity'; loadActivityLogs()"
                    :class="activeTab === 'activity'
                        ? 'bg-white text-slate-900 border-slate-200 border-b-white shadow-[0_-2px_6px_-1px_rgba(0,0,0,0.05)]'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent'"
                    class="relative flex-shrink-0 py-3 px-5 text-sm font-medium border border-b-0 rounded-t-lg transition-all flex items-center gap-2.5"
                >
                    <svg class="w-4 h-4" :class="activeTab === 'activity' ? 'text-violet-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Activity Logs</span>
                    <span :class="activeTab === 'activity' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 text-[10px] font-bold rounded-full">{{ $activityLogsCount }}</span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Assignments Tab -->
            <div x-show="activeTab === 'assignments'" x-cloak>
                <!-- Filters -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">
                    <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input
                                type="text"
                                x-model="assignments.search"
                                @@input.debounce.500ms="loadAssignments()"
                                placeholder="Search assignments..."
                                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <select
                            x-model="assignments.status"
                            @@change="loadAssignments()"
                            class="px-3 py-2 border border-slate-200 rounded-lg bg-white text-sm text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all cursor-pointer"
                        >
                            <option value="">All Statuses</option>
                            <option value="assigned">Assigned</option>
                            <option value="picked_up">Picked Up</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Assignments Table -->
                <div class="rounded-xl border border-slate-200 bg-white relative overflow-hidden">
                    <div x-show="assignments.loading" class="absolute inset-0 bg-white/80 z-10 flex items-center justify-center">
                        <svg class="animate-spin w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment #</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Vendor</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Assigned At</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Completed At</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="assignments.data.length === 0 && !assignments.loading">
                                    <tr>
                                        <td colspan="5" class="px-4 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                </svg>
                                                <p class="text-slate-500 text-sm font-medium">No assignments found</p>
                                                <p class="text-slate-400 text-xs mt-1">This driver hasn't been assigned any shipments yet</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="assignment in assignments.data" :key="assignment.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-xs font-semibold text-slate-900" x-text="assignment.shipment_number"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-xs text-slate-600" x-text="assignment.vendor_name || '-'"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': assignment.status === 'assigned',
                                                    'bg-violet-100 text-violet-700': assignment.status === 'picked_up',
                                                    'bg-amber-100 text-amber-700': assignment.status === 'in_transit',
                                                    'bg-emerald-100 text-emerald-700': assignment.status === 'delivered',
                                                    'bg-rose-100 text-rose-700': assignment.status === 'cancelled',
                                                    'bg-slate-100 text-slate-700': !['assigned','picked_up','in_transit','delivered','cancelled'].includes(assignment.status)
                                                }"
                                                x-text="assignment.status_label || (assignment.status ? assignment.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-')"
                                            ></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(assignment.assigned_at)"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="assignment.completed_at ? formatDateTime(assignment.completed_at) : '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <template x-if="assignments.meta.total > 0">
                        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="text-xs text-slate-600">
                                    Showing <span x-text="assignments.meta.from"></span> to <span x-text="assignments.meta.to"></span> of <span x-text="assignments.meta.total"></span> results
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @@click="assignments.page = 1; loadAssignments()"
                                        :disabled="assignments.page === 1"
                                        :class="assignments.page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'"
                                        class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button
                                        @@click="assignments.page--; loadAssignments()"
                                        :disabled="assignments.page === 1"
                                        :class="assignments.page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'"
                                        class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <span class="px-3 text-xs font-medium text-slate-600">Page <span class="text-slate-900" x-text="assignments.page"></span> of <span x-text="assignments.meta.last_page"></span></span>
                                    <button
                                        @@click="assignments.page++; loadAssignments()"
                                        :disabled="assignments.page === assignments.meta.last_page"
                                        :class="assignments.page === assignments.meta.last_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'"
                                        class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <button
                                        @@click="assignments.page = assignments.meta.last_page; loadAssignments()"
                                        :disabled="assignments.page === assignments.meta.last_page"
                                        :class="assignments.page === assignments.meta.last_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'"
                                        class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Activity Logs Tab -->
            <div x-show="activeTab === 'activity'" x-cloak>
                <!-- Filters -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">
                    <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input
                                type="text"
                                x-model="activity.search"
                                @@input.debounce.500ms="loadActivityLogs()"
                                placeholder="Search activity..."
                                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 text-sm text-slate-900 placeholder-slate-400 transition-all"
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <select
                            x-model="activity.action"
                            @@change="loadActivityLogs()"
                            class="px-3 py-2 border border-slate-200 rounded-lg bg-white text-sm text-slate-700 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 transition-all cursor-pointer"
                        >
                            <option value="">All Actions</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                            <option value="register">Register</option>
                            <option value="login_otp_requested">Login OTP Requested</option>
                            <option value="verify_phone">Verify Phone</option>
                            <option value="profile_updated">Profile Updated</option>
                        </select>
                    </div>
                </div>

                <!-- Activity Table -->
                <div class="rounded-xl border border-slate-200 bg-white relative overflow-hidden">
                    <div x-show="activity.loading" class="absolute inset-0 bg-white/80 z-10 flex items-center justify-center">
                        <svg class="animate-spin w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Device</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">IP Address</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="activity.data.length === 0 && !activity.loading">
                                    <tr>
                                        <td colspan="5" class="px-4 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="text-slate-500 text-sm font-medium">No activity logs found</p>
                                                <p class="text-slate-400 text-xs mt-1">Activity will appear here once the driver uses the app</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="log in activity.data" :key="log.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-700': ['login', 'register', 'verify_phone'].includes(log.action),
                                                    'bg-blue-100 text-blue-700': ['profile_updated', 'login_otp_requested'].includes(log.action),
                                                    'bg-slate-100 text-slate-700': log.action === 'logout'
                                                }"
                                                x-text="log.action.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"
                                            ></span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-600 max-w-xs truncate" x-text="log.description || '-'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div>
                                                <p class="text-xs font-medium text-slate-900" x-text="log.device_name || 'Unknown'"></p>
                                                <p class="text-xs text-slate-400" x-text="(log.device_type || '') + ' ' + (log.os_version || '')"></p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600 font-mono" x-text="log.ip_address || '-'"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="formatDateTime(log.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <template x-if="activity.meta.total > 0">
                        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="text-xs text-slate-600">
                                    Showing <span x-text="activity.meta.from"></span> to <span x-text="activity.meta.to"></span> of <span x-text="activity.meta.total"></span> results
                                </div>
                                <div class="flex items-center gap-1">
                                    <button @@click="activity.page = 1; loadActivityLogs()" :disabled="activity.page === 1" :class="activity.page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'" class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button @@click="activity.page--; loadActivityLogs()" :disabled="activity.page === 1" :class="activity.page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'" class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <span class="px-3 text-xs font-medium text-slate-600">Page <span class="text-slate-900" x-text="activity.page"></span> of <span x-text="activity.meta.last_page"></span></span>
                                    <button @@click="activity.page++; loadActivityLogs()" :disabled="activity.page === activity.meta.last_page" :class="activity.page === activity.meta.last_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'" class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <button @@click="activity.page = activity.meta.last_page; loadActivityLogs()" :disabled="activity.page === activity.meta.last_page" :class="activity.page === activity.meta.last_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:border-slate-300'" class="w-8 h-8 border border-slate-200 rounded-md bg-white text-slate-500 flex items-center justify-center transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
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

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Password <span class="text-slate-400 text-xs font-normal">(Optional)</span>
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

@push('scripts')
<script>
function driverShow() {
    return {
        config: {},
        driver: {},
        canManage: false,

        activeTab: 'assignments',
        showToggleModal: false,

        // Assignments state
        assignments: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            search: '',
            status: '',
            page: 1
        },

        // Activity logs state
        activity: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            search: '',
            action: '',
            page: 1
        },

        // Edit modal state
        showEditModal: false,
        saving: false,
        toggling: false,
        errors: {},
        form: {
            name: '',
            email: '',
            phone: '',
            password: '',
            vehicle_type: '',
            vehicle_number: '',
            license_number: '',
            is_active: true
        },

        init() {
            this.config = window.driverShowConfig;
            this.driver = this.config.driver;
            this.canManage = this.config.canManage;
            this.loadAssignments();
        },

        async loadAssignments() {
            this.assignments.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.assignments.page,
                    per_page: 10,
                    search: this.assignments.search,
                    status: this.assignments.status
                });

                const response = await fetch(`${this.config.assignmentsEndpoint}?${params}`);
                const data = await response.json();

                this.assignments.data = data.data;
                this.assignments.meta = data.meta;
            } catch (error) {
                console.error('Failed to load assignments:', error);
            } finally {
                this.assignments.loading = false;
            }
        },

        async loadActivityLogs() {
            this.activity.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.activity.page,
                    per_page: 10,
                    search: this.activity.search,
                    action: this.activity.action
                });

                const response = await fetch(`${this.config.activityLogsEndpoint}?${params}`);
                const data = await response.json();

                this.activity.data = data.data;
                this.activity.meta = data.meta;
            } catch (error) {
                console.error('Failed to load activity logs:', error);
            } finally {
                this.activity.loading = false;
            }
        },

        openEditModal() {
            this.form = {
                name: this.driver.name,
                email: this.driver.email,
                phone: this.driver.phone,
                password: '',
                vehicle_type: this.driver.vehicle_type || '',
                vehicle_number: this.driver.vehicle_number || '',
                license_number: this.driver.license_number || '',
                is_active: this.driver.is_active
            };
            this.errors = {};
            this.showEditModal = true;
        },

        async saveDriver() {
            this.saving = true;
            this.errors = {};

            try {
                const payload = { ...this.form };
                if (!payload.password) {
                    delete payload.password;
                }

                const response = await fetch(this.config.updateEndpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                    } else {
                        throw new Error(data.message || 'Failed to update driver');
                    }
                    return;
                }

                // Update local driver data
                this.driver.name = this.form.name;
                this.driver.email = this.form.email;
                this.driver.vehicle_type = this.form.vehicle_type;
                this.driver.vehicle_number = this.form.vehicle_number;
                this.driver.license_number = this.form.license_number;
                this.driver.is_active = this.form.is_active;

                this.showEditModal = false;

                // Show success notification
                if (window.showToast) {
                    window.showToast('Driver updated successfully', 'success');
                }
            } catch (error) {
                console.error('Save error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to update driver', 'error');
                }
            } finally {
                this.saving = false;
            }
        },

        async toggleActive() {
            this.toggling = true;

            try {
                const response = await fetch(this.config.toggleActiveEndpoint, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to toggle status');
                }

                this.driver.is_active = data.is_active;
                this.showToggleModal = false;

                if (window.showToast) {
                    window.showToast(data.message, 'success');
                }
            } catch (error) {
                console.error('Toggle error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to toggle status', 'error');
                }
            } finally {
                this.toggling = false;
            }
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endpush
