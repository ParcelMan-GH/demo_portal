@extends('admin.layouts.app')

@section('title', 'Warehouse - ' . $warehouse->name)
@section('breadcrumb-parent', 'Warehouses')
@section('breadcrumb-current', $warehouse->name)

@php
$warehouseConfig = [
    'warehouse' => $warehouse,
    'updateEndpoint' => route('admin.warehouses.update', $warehouse),
    'toggleActiveEndpoint' => route('admin.warehouses.toggle-active', $warehouse),
    'canManage' => $canManage,
];

$warehouseUsersTableConfig = [
    'endpoint' => route('admin.warehouses.users.data', $warehouse),
    'exportEndpoint' => route('admin.warehouses.users.export', $warehouse),
    'createEndpoint' => route('admin.admins.store'),
    'warehouseId' => $warehouse->id,
    'csrfToken' => csrf_token(),
    'canCreateUsers' => $canCreateUsers,
    'roles' => $userRoles->map(fn($role) => ['id' => $role->id, 'name' => $role->name])->values(),
];
@endphp

@section('content')
<div
    x-data="warehouseShow()"
    data-warehouse-show-config="{{ json_encode($warehouseConfig) }}"
    data-warehouse-users-config="{{ json_encode($warehouseUsersTableConfig) }}"
    class="space-y-6"
>

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
                <!-- Top Row: Back (left) + Actions (right) -->
                <div class="mb-6 flex items-center justify-between gap-3">
                    <a href="{{ route('admin.warehouses.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Warehouses</span>
                    </a>

                    @if($canManage)
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button
                            @@click="openEditModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Edit Warehouse
                        </button>
                        <button
                            @@click="showToggleModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl border transition-all backdrop-blur-sm shadow-sm hover:shadow-md"
                            :class="warehouse.is_active
                                ? 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30'
                                : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/30'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <span x-text="warehouse.is_active ? 'Deactivate' : 'Activate'"></span>
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Main Row: Details (left) + Stats (right) -->
                <div class="flex flex-col xl:flex-row xl:items-start gap-4">
                    <!-- Left: Warehouse profile details -->
                    <div class="flex items-start gap-5 min-w-0 xl:flex-1">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-teal-700 flex items-center justify-center shadow-xl shadow-teal-500/30 ring-4 ring-white/10">
                                <svg class="w-10 h-10 lg:w-12 lg:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                </svg>
                            </div>
                            <div class="absolute -bottom-1.5 -right-1.5 w-7 h-7 lg:w-8 lg:h-8 rounded-full {{ $warehouse->is_active ? 'bg-gradient-to-br from-emerald-400 to-emerald-600' : 'bg-gradient-to-br from-slate-400 to-slate-500' }} border-4 border-slate-900 flex items-center justify-center shadow-lg">
                                @if($warehouse->is_active)
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

                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white leading-tight">{{ $warehouse->name }}</h1>
                                @if($warehouse->code)
                                    <p class="text-slate-400 text-sm mt-0.5 font-mono">{{ $warehouse->code }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-start gap-x-3 gap-y-2 text-xs">
                                @if($warehouse->address)
                                <div class="flex items-start gap-1.5 text-slate-300 basis-full min-w-0">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="whitespace-normal break-words leading-relaxed">{{ $warehouse->address }}</span>
                                </div>
                                @endif
                                @if($warehouse->contact_phone)
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $warehouse->contact_phone }}
                                </div>
                                @endif
                                @if($warehouse->contact_email)
                                <div class="flex items-center gap-1.5 text-slate-300">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="break-all">{{ $warehouse->contact_email }}</span>
                                </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $warehouse->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $warehouse->is_active ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                    {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300">
                                    {{ $warehouse->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Operational summary cards -->
                    <div class="w-full xl:w-[420px] xl:shrink-0 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div class="min-w-0 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500/30 to-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0l-8 5-8-5"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-white leading-none">{{ number_format($warehouseStats['total_received_items']) }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Total Items Received</p>
                            </div>
                        </div>

                        <div class="min-w-0 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/30 to-emerald-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-white leading-none">{{ number_format($warehouseStats['received_pickups']) }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Received Pickups</p>
                            </div>
                        </div>

                        <div class="min-w-0 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/30 to-amber-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M12 3a9 9 0 100 18 9 9 0 000-18z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-white leading-none">{{ number_format($warehouseStats['pending_receipts']) }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Pending Receipts</p>
                            </div>
                        </div>

                        <div class="min-w-0 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 px-3.5 py-2.5 flex items-center gap-2.5 transition-colors">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/30 to-violet-600/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V9H2v11h5m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0H7"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-white leading-none">{{ number_format($warehouseStats['users_count']) }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Warehouse Users</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Workbench Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        <!-- Sidebar Nav -->
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Warehouse</p>

            {{-- Details --}}
            <button @@click="activeTab = 'details'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'details' ? 'bg-sky-50 ring-1 ring-sky-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'details' ? 'bg-sky-500 shadow-sm shadow-sky-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'details' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'details' ? 'font-bold text-sky-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Details</span>
            </button>

            {{-- Users --}}
            <button @@click="activeTab = 'users'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'users' ? 'bg-violet-50 ring-1 ring-violet-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'users' ? 'bg-violet-500 shadow-sm shadow-violet-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'users' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'users' ? 'font-bold text-violet-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Users</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'users' ? 'bg-violet-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $warehouseUsers->count() }}</span>
            </button>

            {{-- Divider --}}
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Inventory</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            {{-- Received Items --}}
            <button @@click="activeTab = 'received_items'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'received_items' ? 'bg-emerald-50 ring-1 ring-emerald-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'received_items' ? 'bg-emerald-500 shadow-sm shadow-emerald-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'received_items' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'received_items' ? 'font-bold text-emerald-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Received Items</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'received_items' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $receivedItems->count() }}</span>
            </button>

            {{-- Received Pickups --}}
            <button @@click="activeTab = 'received_pickups'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'received_pickups' ? 'bg-amber-50 ring-1 ring-amber-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'received_pickups' ? 'bg-amber-500 shadow-sm shadow-amber-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'received_pickups' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'received_pickups' ? 'font-bold text-amber-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Received Pickups</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'received_pickups' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $receivedPickups->count() }}</span>
            </button>

            {{-- Pending Receipts --}}
            <button @@click="activeTab = 'pending_receipts'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'pending_receipts' ? 'bg-rose-50 ring-1 ring-rose-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'pending_receipts' ? 'bg-rose-500 shadow-sm shadow-rose-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'pending_receipts' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'pending_receipts' ? 'font-bold text-rose-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Pending Receipts</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'pending_receipts' ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $pendingReceipts->count() }}</span>
            </button>

            {{-- Divider: Logistics --}}
            <div class="px-2 pt-3 pb-1">
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Logistics</p>
            </div>

            {{-- Sort Batches --}}
            <button @@click="activeTab = 'sort_batches'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'sort_batches' ? 'bg-orange-50 ring-1 ring-orange-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'sort_batches' ? 'bg-orange-500 shadow-sm shadow-orange-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'sort_batches' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'sort_batches' ? 'font-bold text-orange-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Sort Batches</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'sort_batches' ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $sortBatchesOrigin->count() + $sortBatchesDest->count() }}</span>
            </button>

            {{-- Manifests --}}
            <button @@click="activeTab = 'manifests'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'manifests' ? 'bg-cyan-50 ring-1 ring-cyan-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'manifests' ? 'bg-cyan-500 shadow-sm shadow-cyan-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'manifests' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'manifests' ? 'font-bold text-cyan-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Manifests</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'manifests' ? 'bg-cyan-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $manifestsOutgoing->count() + $manifestsIncoming->count() }}</span>
            </button>

            {{-- Delivery Runs --}}
            <button @@click="activeTab = 'delivery_runs'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'delivery_runs' ? 'bg-indigo-50 ring-1 ring-indigo-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'delivery_runs' ? 'bg-indigo-500 shadow-sm shadow-indigo-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'delivery_runs' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors flex-1" :class="activeTab === 'delivery_runs' ? 'font-bold text-indigo-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Delivery Runs</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'delivery_runs' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $deliveryRuns->count() }}</span>
            </button>

        </aside>

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">
            <div x-show="activeTab === 'details'" x-cloak class="space-y-5">

                {{-- Identity Card --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-sky-50 to-slate-50 border-b border-slate-100 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-sky-500 shadow-sm shadow-sky-200 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold text-sky-500 uppercase tracking-wider">Warehouse Identity</p>
                            <p class="text-base font-bold text-slate-900 leading-tight">{{ $warehouse->name }}</p>
                        </div>
                        <div class="ml-auto flex items-center gap-2 flex-shrink-0">
                            @if($warehouse->code)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-100 text-sky-700 text-xs font-bold font-mono border border-sky-200">
                                <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                {{ $warehouse->code }}
                            </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $warehouse->is_active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $warehouse->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    {{-- Stats row --}}
                    <div class="grid grid-cols-3 divide-x divide-slate-100">
                        <div class="px-5 py-4 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Capacity</p>
                                <p class="text-sm font-bold text-slate-900">
                                    {{ $warehouse->capacity ? number_format($warehouse->capacity) . ' m³' : '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="px-5 py-4 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Region</p>
                                <p class="text-sm font-bold text-slate-900">{{ $warehouse->region->name ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="px-5 py-4 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">District</p>
                                <p class="text-sm font-bold text-slate-900">{{ $warehouse->district->name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact & Location row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Contact Card --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-emerald-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-slate-700">Contact Information</p>
                        </div>
                        <div class="px-4 py-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Phone</p>
                                    @if($warehouse->contact_phone)
                                        <a href="tel:{{ $warehouse->contact_phone }}" class="text-sm font-semibold text-sky-600 hover:text-sky-700 hover:underline">{{ $warehouse->contact_phone }}</a>
                                    @else
                                        <p class="text-sm text-slate-400 italic">Not provided</p>
                                    @endif
                                </div>
                            </div>
                            <div class="h-px bg-slate-50"></div>
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Email</p>
                                    @if($warehouse->contact_email)
                                        <a href="mailto:{{ $warehouse->contact_email }}" class="text-sm font-semibold text-sky-600 hover:text-sky-700 hover:underline truncate block">{{ $warehouse->contact_email }}</a>
                                    @else
                                        <p class="text-sm text-slate-400 italic">Not provided</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Location Card --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-violet-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-slate-700">Location</p>
                        </div>
                        <div class="px-4 py-4">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Full Address</p>
                                    @if($warehouse->address)
                                        <p class="text-sm font-semibold text-slate-800 leading-relaxed mt-0.5">{{ $warehouse->address }}</p>
                                    @else
                                        <p class="text-sm text-slate-400 italic mt-0.5">No address provided</p>
                                    @endif
                                    @if($warehouse->region || $warehouse->district)
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @if($warehouse->district)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-xs font-medium border border-rose-100">{{ $warehouse->district->name }}</span>
                                        @endif
                                        @if($warehouse->region)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 text-xs font-medium border border-violet-100">{{ $warehouse->region->name }}</span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Metadata footer --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="px-5 py-3.5 flex flex-wrap items-center gap-x-8 gap-y-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Created</span>
                            <span class="text-xs font-semibold text-slate-700">{{ $warehouse->created_at->format('M d, Y') }}</span>
                            <span class="text-xs text-slate-400">at {{ $warehouse->created_at->format('h:i A') }}</span>
                        </div>
                        <div class="w-px h-4 bg-slate-100 hidden sm:block"></div>
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Updated</span>
                            <span class="text-xs font-semibold text-slate-700">{{ $warehouse->updated_at->format('M d, Y') }}</span>
                            <span class="text-xs text-slate-400">at {{ $warehouse->updated_at->format('h:i A') }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <div x-show="activeTab === 'users'" x-cloak x-data="warehouseUsersTable()" x-init="init()" class="space-y-4 relative">
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input
                                type="text"
                                x-model="search"
                                @@input.debounce.500ms="meta.current_page = 1; loadData()"
                                placeholder="Search users, email, phone..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400"
                            >
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div x-data="{ open: false }" class="relative w-full sm:w-56">
                            <button type="button" @@click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 text-sm font-medium text-slate-700 hover:bg-white/90">
                                <span x-text="selectedRoleName()"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display:none;">
                                <button type="button" @@click="roleFilter = ''; open = false; meta.current_page = 1; loadData()" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="roleFilter === '' ? 'bg-white/70 shadow-sm' : ''">
                                    <svg x-show="roleFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>All roles</span>
                                </button>
                                <template x-for="role in roles" :key="role.id">
                                    <button type="button" @@click="roleFilter = String(role.id); open = false; meta.current_page = 1; loadData()" class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="roleFilter === String(role.id) ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                        <svg x-show="roleFilter === String(role.id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span x-text="role.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 text-sm font-semibold text-slate-700 hover:bg-white/90">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none;">
                                <template x-for="col in columns" :key="col.key">
                                    <button type="button" @@click="toggleColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="col.label"></span>
                                        <svg x-show="visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 text-sm font-semibold text-slate-700 hover:bg-white/90">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none;">
                                <button type="button" @@click="exportData('excel'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Excel
                                </button>
                                <button type="button" @@click="exportData('pdf'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    PDF
                                </button>
                                <button type="button" @@click="exportData('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="printData(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print
                                </button>
                            </div>
                        </div>

                        <button x-show="canCreateUsers" type="button" @@click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add User
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display:none;"></div>
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.name" @@click="sort('name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        NAME
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'name' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.role" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ROLE</th>
                                <th x-show="visibleColumns.email" @@click="sort('email')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        EMAIL
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'email' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.phone" @@click="sort('phone')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        PHONE
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'phone' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.status" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        CREATED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.last_login_at" @@click="sort('last_login_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">
                                        LAST LOGIN
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'last_login_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="users.length === 0 && !loading">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No warehouse users found</td>
                                </tr>
                            </template>
                            <template x-for="user in users" :key="user.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.name" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="user.name"></td>
                                    <td x-show="visibleColumns.role" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600">
                                        <template x-if="user.roles && user.roles.length">
                                            <span class="inline-flex items-center rounded-full border border-slate-200/50 px-2 py-0.5 text-[10px] font-semibold text-slate-700 bg-white/60" x-text="user.roles[0].name"></span>
                                        </template>
                                        <template x-if="!user.roles || !user.roles.length">
                                            <span class="text-[10px] text-slate-400">No role</span>
                                        </template>
                                    </td>
                                    <td x-show="visibleColumns.email" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="user.email"></td>
                                    <td x-show="visibleColumns.phone" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="user.phone || '-'"></td>
                                    <td x-show="visibleColumns.status" class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                    </td>
                                    <td x-show="visibleColumns.created_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="user.created_at"></td>
                                    <td x-show="visibleColumns.last_login_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="user.last_login_at"></td>
                                    <td x-show="visibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap text-center">
                                        <div class="inline-flex items-center gap-2">
                                            <a :href="user.view_url" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                            <button x-show="user.can_manage" type="button" @@click="openEditModal(user)" class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700 hover:bg-blue-100">Edit</button>
                                            <button x-show="user.can_manage && !user.is_self" type="button" @@click="toggleUserStatus(user)" class="inline-flex items-center rounded-lg border px-2 py-1 text-[10px] font-semibold" :class="user.is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'" x-text="user.is_active ? 'Deactivate' : 'Activate'"></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>

                <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-xs text-slate-600">
                            Showing
                            <span x-text="meta.from"></span>
                            to
                            <span x-text="meta.to"></span>
                            of
                            <span x-text="meta.total"></span>
                            users
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-600">Rows</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                        <span x-text="perPage"></span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                        <button type="button" @@click="setPerPage(10); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                        <button type="button" @@click="setPerPage(25); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                        <button type="button" @@click="setPerPage(50); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                        <button type="button" @@click="setPerPage(100); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs font-medium text-slate-600">
                                Page
                                <span x-text="meta.current_page"></span>
                                of
                                <span x-text="meta.last_page"></span>
                            </div>

                            <div class="flex space-x-1">
                                <button @@click="firstPage()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <button @@click="previousPage()" :disabled="meta.current_page === 1" :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <button @@click="nextPage()" :disabled="meta.current_page === meta.last_page" :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>

                                <button @@click="lastPage()" :disabled="meta.current_page === meta.last_page" :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
                <div
                    x-show="showUserModal"
                    x-cloak
                    class="fixed inset-0 z-[120] overflow-y-auto"
                    @@keydown.escape.window="closeUserModal()"
                >
                    <div
                        x-show="showUserModal"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                        @@click="closeUserModal()"
                    ></div>

                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            x-show="showUserModal"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            @@click.stop
                            class="relative w-full max-w-lg bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50"
                        >
                            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200/50">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900" x-text="userModalMode === 'create' ? 'Add New User' : 'Edit User'"></h3>
                                    <p class="text-sm text-slate-500" x-text="userModalMode === 'create' ? 'Create a new warehouse user' : 'Update warehouse user information'"></p>
                                </div>
                                <button @@click="closeUserModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <form @@submit.prevent="submitUserForm()" class="p-6 space-y-4">
                                <div x-show="userFormErrors.general" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200">
                                    <p class="text-sm text-red-600" x-text="userFormErrors.general"></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           x-model="userForm.name"
                                           class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                           :class="userFormErrors.name ? 'border-red-300 focus:ring-red-400/50' : ''"
                                           placeholder="John Doe">
                                    <p x-show="userFormErrors.name" x-text="userFormErrors.name" class="mt-1 text-xs text-red-500"></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email"
                                           x-model="userForm.email"
                                           class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                           :class="userFormErrors.email ? 'border-red-300 focus:ring-red-400/50' : ''"
                                           placeholder="warehouse.user@example.com">
                                    <p x-show="userFormErrors.email" x-text="userFormErrors.email" class="mt-1 text-xs text-red-500"></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="tel"
                                           x-model="userForm.phone"
                                           class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                           :class="userFormErrors.phone ? 'border-red-300 focus:ring-red-400/50' : ''"
                                           placeholder="0241234567">
                                    <p x-show="userFormErrors.phone" x-text="userFormErrors.phone" class="mt-1 text-xs text-red-500"></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Assign Role <span class="text-red-500">*</span></label>
                                    <select x-model.number="userForm.role_id"
                                            class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                            :class="(userFormErrors.role_id || userFormErrors.roles) ? 'border-red-300 focus:ring-red-400/50' : ''">
                                        <option value="">Select a role…</option>
                                        <template x-for="role in roles" :key="role.id">
                                            <option :value="Number(role.id)" x-text="role.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="userFormErrors.role_id || userFormErrors.roles"
                                       x-text="userFormErrors.role_id || userFormErrors.roles"
                                       class="mt-1 text-xs text-red-500"></p>
                                </div>

                                <div x-show="userModalMode === 'edit' && selectedUser && !selectedUser.is_self" x-cloak>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" x-model="userForm.is_active" value="1" class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300">
                                            <span class="ml-2 text-sm text-slate-700">Active</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" x-model="userForm.is_active" value="0" class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300">
                                            <span class="ml-2 text-sm text-slate-700">Inactive</span>
                                        </label>
                                    </div>
                                </div>

                                <div x-data="{ showPassword: false }">
                                    <div x-show="userModalMode === 'edit'" x-cloak class="mb-3">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="changePassword" class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300 rounded">
                                            <span class="ml-2 text-sm text-slate-700">Change Password</span>
                                        </label>
                                    </div>

                                    <div x-show="userModalMode === 'create' || changePassword" x-cloak class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                                <span x-text="userModalMode === 'create' ? 'Password' : 'New Password'"></span>
                                                <span x-show="userModalMode === 'create'" class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input :type="showPassword ? 'text' : 'password'"
                                                       x-model="userForm.password"
                                                       :required="userModalMode === 'create'"
                                                       class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                                       :class="userFormErrors.password ? 'border-red-300 focus:ring-red-400/50' : ''"
                                                       placeholder="Minimum 8 characters">
                                                <button type="button" @@click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600">
                                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <p x-show="userFormErrors.password" x-text="userFormErrors.password" class="mt-1 text-xs text-red-500"></p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                                Confirm Password <span x-show="userModalMode === 'create'" class="text-red-500">*</span>
                                            </label>
                                            <input :type="showPassword ? 'text' : 'password'"
                                                   x-model="userForm.password_confirmation"
                                                   :required="userModalMode === 'create'"
                                                   class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                                   placeholder="Re-enter password">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/50">
                                    <button type="button" @@click="closeUserModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            :disabled="submittingUser"
                                            class="px-4 py-2 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!submittingUser" x-text="userModalMode === 'create' ? 'Create User' : 'Update User'"></span>
                                        <span x-show="submittingUser" class="flex items-center gap-2">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Saving...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @php
                // Flat data for client-side inventory tables
                $receivedItemsFlat = $receivedItems->map(fn($c) => [
                    'id'               => $c->id,
                    'confirmed_at'     => $c->confirmed_at?->format('M d, Y h:i A') ?? '-',
                    'shipment_number'  => $c->shipmentItem?->shipment?->shipment_number ?? '-',
                    'item_description' => $c->shipmentItem?->description ?? '-',
                    'qty'              => ($c->confirmed_quantity ?? 0) . ' / ' . ($c->expected_quantity ?? 0),
                    'driver'           => $c->pickupAssignment?->driver?->name ?? '-',
                    'notes'            => $c->notes ?: '-',
                    'view_url'         => $c->shipmentItem?->shipment?->id ? route('admin.shipments.show', $c->shipmentItem->shipment->id) : '#',
                ])->values()->all();

                $receivedPickupsFlat = $receivedPickups->map(function ($p) {
                    $sv  = is_object($p->status) ? $p->status->value : (string) $p->status;
                    $lbl = is_object($p->status) && method_exists($p->status, 'label') ? $p->status->label() : ucwords(str_replace('_', ' ', $sv));
                    $cls = match ($sv) {
                        'assigned'                          => 'bg-amber-100 text-amber-700',
                        'en_route', 'en_route_to_warehouse' => 'bg-blue-100 text-blue-700',
                        'arrived_warehouse'                 => 'bg-violet-100 text-violet-700',
                        'received', 'completed'             => 'bg-emerald-100 text-emerald-700',
                        'cancelled'                         => 'bg-red-100 text-red-700',
                        default                             => 'bg-slate-100 text-slate-700',
                    };
                    return [
                        'id'                   => $p->id,
                        'shipment_number'      => $p->shipment?->shipment_number ?? '-',
                        'driver'               => $p->driver?->name ?? '-',
                        'status_label'         => $lbl,
                        'status_badge_class'   => $cls,
                        'arrived_warehouse_at' => $p->arrived_warehouse_at?->format('M d, Y h:i A') ?? '-',
                        'received_at'          => $p->received_at?->format('M d, Y h:i A') ?? '-',
                        'notes'                => $p->receive_notes ?: '-',
                        'view_url'             => $p->shipment?->id ? route('admin.shipments.show', $p->shipment->id) : '#',
                    ];
                })->values()->all();

                $pendingReceiptsFlat = $pendingReceipts->map(function ($p) {
                    $sv  = is_object($p->status) ? $p->status->value : (string) $p->status;
                    $lbl = is_object($p->status) && method_exists($p->status, 'label') ? $p->status->label() : ucwords(str_replace('_', ' ', $sv));
                    $cls = match ($sv) {
                        'assigned'                          => 'bg-amber-100 text-amber-700',
                        'en_route', 'en_route_to_warehouse' => 'bg-blue-100 text-blue-700',
                        'arrived_warehouse'                 => 'bg-violet-100 text-violet-700',
                        'received', 'completed'             => 'bg-emerald-100 text-emerald-700',
                        'cancelled'                         => 'bg-red-100 text-red-700',
                        default                             => 'bg-slate-100 text-slate-700',
                    };
                    return [
                        'id'                   => $p->id,
                        'shipment_number'      => $p->shipment?->shipment_number ?? '-',
                        'driver'               => $p->driver?->name ?? '-',
                        'status_label'         => $lbl,
                        'status_badge_class'   => $cls,
                        'assigned_at'          => $p->assigned_at?->format('M d, Y h:i A') ?? '-',
                        'arrived_warehouse_at' => $p->arrived_warehouse_at?->format('M d, Y h:i A') ?? '-',
                        'view_url'             => $p->shipment?->id ? route('admin.shipments.show', $p->shipment->id) : '#',
                    ];
                })->values()->all();
            @endphp

            {{-- ── Received Items Tab ─────────────────────────────────────────── --}}
            <div x-show="activeTab === 'received_items'" x-cloak
                 x-data="warehouseReceivedItemsTable({{ Js::from($receivedItemsFlat) }})"
                 x-init="init()"
                 class="space-y-4 relative">

                {{-- Toolbar --}}
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="search" placeholder="Search items..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @include('admin.warehouses._inventory_view_btn')
                        @include('admin.warehouses._inventory_export_btn')
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.confirmed_at" @@click="sort('confirmed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">CONFIRMED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'confirmed_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">SHIPMENT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.item_description" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ITEM</th>
                                <th x-show="visibleColumns.qty" @@click="sort('qty')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">QTY
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'qty' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.driver" @@click="sort('driver')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">DRIVER
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'driver' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.notes"    class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                                <th x-show="visibleColumns.actions"  class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                                        <p class="text-sm text-slate-500" x-text="search ? 'No items match your search' : 'No received item confirmations yet'"></p>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.confirmed_at"     class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.confirmed_at"></td>
                                    <td x-show="visibleColumns.shipment_number"  class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="row.shipment_number"></td>
                                    <td x-show="visibleColumns.item_description" class="px-4 py-2.5 text-xs text-slate-700 max-w-xs truncate"              x-text="row.item_description"></td>
                                    <td x-show="visibleColumns.qty"              class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.qty"></td>
                                    <td x-show="visibleColumns.driver"           class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.driver"></td>
                                    <td x-show="visibleColumns.notes"            class="px-4 py-2.5 text-xs text-slate-700 max-w-xs truncate"              x-text="row.notes"></td>
                                    <td x-show="visibleColumns.actions"          class="px-4 py-2.5 whitespace-nowrap text-center"><a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View Shipment<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    @include('admin.warehouses._inventory_pagination', ['noun' => 'items'])
                </div>
            </div>

            {{-- ── Received Pickups Tab ────────────────────────────────────────── --}}
            <div x-show="activeTab === 'received_pickups'" x-cloak
                 x-data="warehouseReceivedPickupsTable({{ Js::from($receivedPickupsFlat) }})"
                 x-init="init()"
                 class="space-y-4 relative">

                {{-- Toolbar --}}
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="search" placeholder="Search pickups..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @include('admin.warehouses._inventory_view_btn')
                        @include('admin.warehouses._inventory_export_btn')
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">SHIPMENT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.driver"              class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DRIVER</th>
                                <th x-show="visibleColumns.status_label"        class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                <th x-show="visibleColumns.arrived_warehouse_at" @@click="sort('arrived_warehouse_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">ARRIVED WAREHOUSE
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'arrived_warehouse_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.received_at" @@click="sort('received_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">RECEIVED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'received_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.notes"    class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                                <th x-show="visibleColumns.actions"  class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/></svg>
                                        <p class="text-sm text-slate-500" x-text="search ? 'No pickups match your search' : 'No pickups have been received at this warehouse yet'"></p>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.shipment_number"      class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="row.shipment_number"></td>
                                    <td x-show="visibleColumns.driver"               class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.driver"></td>
                                    <td x-show="visibleColumns.status_label"         class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.status_badge_class" x-text="row.status_label"></span></td>
                                    <td x-show="visibleColumns.arrived_warehouse_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.arrived_warehouse_at"></td>
                                    <td x-show="visibleColumns.received_at"          class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.received_at"></td>
                                    <td x-show="visibleColumns.notes"                class="px-4 py-2.5 text-xs text-slate-700 max-w-xs truncate"              x-text="row.notes"></td>
                                    <td x-show="visibleColumns.actions"              class="px-4 py-2.5 whitespace-nowrap text-center"><a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View Shipment<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    @include('admin.warehouses._inventory_pagination', ['noun' => 'pickups'])
                </div>
            </div>

            {{-- ── Pending Receipts Tab ────────────────────────────────────────── --}}
            <div x-show="activeTab === 'pending_receipts'" x-cloak
                 x-data="warehousePendingReceiptsTable({{ Js::from($pendingReceiptsFlat) }})"
                 x-init="init()"
                 class="space-y-4 relative">

                {{-- Toolbar --}}
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" x-model="search" placeholder="Search pending receipts..."
                                class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @include('admin.warehouses._inventory_view_btn')
                        @include('admin.warehouses._inventory_export_btn')
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.shipment_number" @@click="sort('shipment_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">SHIPMENT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'shipment_number' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.driver"              class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DRIVER</th>
                                <th x-show="visibleColumns.status_label"        class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                <th x-show="visibleColumns.assigned_at" @@click="sort('assigned_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">ASSIGNED AT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'assigned_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.arrived_warehouse_at" @@click="sort('arrived_warehouse_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                    <div class="flex items-center">ARRIVED WAREHOUSE
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'arrived_warehouse_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <p class="text-sm text-slate-500" x-text="search ? 'No receipts match your search' : 'No pending receipts for this warehouse'"></p>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.shipment_number"      class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="row.shipment_number"></td>
                                    <td x-show="visibleColumns.driver"               class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.driver"></td>
                                    <td x-show="visibleColumns.status_label"         class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.status_badge_class" x-text="row.status_label"></span></td>
                                    <td x-show="visibleColumns.assigned_at"          class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.assigned_at"></td>
                                    <td x-show="visibleColumns.arrived_warehouse_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700"               x-text="row.arrived_warehouse_at"></td>
                                    <td x-show="visibleColumns.actions"              class="px-4 py-2.5 whitespace-nowrap text-center"><a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View Shipment<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    @include('admin.warehouses._inventory_pagination', ['noun' => 'entries'])
                </div>
            </div>

            {{-- ── Sort Batches Tab ────────────────────────────────────────────── --}}
            @php
                $sortBatchesFlat = $sortBatchesOrigin->map(fn($b) => [
                    'id'               => $b->id,
                    'batch_number'     => $b->batch_number,
                    'direction'        => 'Outgoing',
                    'direction_class'  => 'bg-sky-100 text-sky-700',
                    'other_warehouse'  => $b->destinationWarehouse?->name ?? '—',
                    'dispatch_mode'    => $b->dispatch_mode === 'transfer' ? 'Transfer' : 'Local Delivery',
                    'status'           => ucfirst($b->status),
                    'status_badge_class' => $b->status === 'sealed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                    'items'            => $b->active_items_count ?? 0,
                    'sealed_at'        => $b->sealed_at?->format('M d, Y h:i A') ?? '—',
                    'created_at'       => $b->created_at->format('M d, Y h:i A'),
                    'view_url'         => route('admin.sort-batches.show', $b->id),
                ])->merge($sortBatchesDest->map(fn($b) => [
                    'id'               => $b->id,
                    'batch_number'     => $b->batch_number,
                    'direction'        => 'Incoming',
                    'direction_class'  => 'bg-violet-100 text-violet-700',
                    'other_warehouse'  => $b->originWarehouse?->name ?? '—',
                    'dispatch_mode'    => $b->dispatch_mode === 'transfer' ? 'Transfer' : 'Local Delivery',
                    'status'           => ucfirst($b->status),
                    'status_badge_class' => $b->status === 'sealed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                    'items'            => $b->active_items_count ?? 0,
                    'sealed_at'        => $b->sealed_at?->format('M d, Y h:i A') ?? '—',
                    'created_at'       => $b->created_at->format('M d, Y h:i A'),
                    'view_url'         => route('admin.sort-batches.show', $b->id),
                ]))->sortByDesc('created_at')->values()->all();

                $manifestsFlat = $manifestsOutgoing->map(fn($m) => [
                    'id'               => $m->id,
                    'manifest_number'  => $m->manifest_number,
                    'direction'        => 'Outgoing',
                    'direction_class'  => 'bg-sky-100 text-sky-700',
                    'other_warehouse'  => $m->destinationWarehouse?->name ?? '—',
                    'driver'           => $m->assignedDriver?->name ?? '—',
                    'status'           => ucwords(str_replace('_', ' ', $m->status)),
                    'status_badge_class' => match($m->status) {
                        'draft'       => 'bg-slate-100 text-slate-600',
                        'assigned'    => 'bg-amber-100 text-amber-700',
                        'loading'     => 'bg-blue-100 text-blue-700',
                        'in_transit'  => 'bg-violet-100 text-violet-700',
                        'arrived'     => 'bg-cyan-100 text-cyan-700',
                        'received'    => 'bg-emerald-100 text-emerald-700',
                        'cancelled'   => 'bg-red-100 text-red-700',
                        default       => 'bg-slate-100 text-slate-600',
                    },
                    'items'            => $m->items_count ?? 0,
                    'dispatched_at'    => $m->dispatched_at?->format('M d, Y h:i A') ?? '—',
                    'arrived_at'       => $m->arrived_at?->format('M d, Y h:i A') ?? '—',
                    'created_at'       => $m->created_at->format('M d, Y h:i A'),
                    'view_url'         => route('admin.transport-manifests.show', $m->id),
                ])->merge($manifestsIncoming->map(fn($m) => [
                    'id'               => $m->id,
                    'manifest_number'  => $m->manifest_number,
                    'direction'        => 'Incoming',
                    'direction_class'  => 'bg-violet-100 text-violet-700',
                    'other_warehouse'  => $m->originWarehouse?->name ?? '—',
                    'driver'           => $m->assignedDriver?->name ?? '—',
                    'status'           => ucwords(str_replace('_', ' ', $m->status)),
                    'status_badge_class' => match($m->status) {
                        'draft'       => 'bg-slate-100 text-slate-600',
                        'assigned'    => 'bg-amber-100 text-amber-700',
                        'loading'     => 'bg-blue-100 text-blue-700',
                        'in_transit'  => 'bg-violet-100 text-violet-700',
                        'arrived'     => 'bg-cyan-100 text-cyan-700',
                        'received'    => 'bg-emerald-100 text-emerald-700',
                        'cancelled'   => 'bg-red-100 text-red-700',
                        default       => 'bg-slate-100 text-slate-600',
                    },
                    'items'            => $m->items_count ?? 0,
                    'dispatched_at'    => $m->dispatched_at?->format('M d, Y h:i A') ?? '—',
                    'arrived_at'       => $m->arrived_at?->format('M d, Y h:i A') ?? '—',
                    'created_at'       => $m->created_at->format('M d, Y h:i A'),
                    'view_url'         => route('admin.transport-manifests.show', $m->id),
                ]))->sortByDesc('created_at')->values()->all();

                $deliveryRunsFlat = $deliveryRuns->map(fn($r) => [
                    'id'           => $r->id,
                    'run_number'   => $r->run_number,
                    'driver'       => $r->assignedDriver?->name ?? '—',
                    'status'       => ucwords(str_replace('_', ' ', $r->status)),
                    'status_badge_class' => match($r->status) {
                        'draft'                => 'bg-slate-100 text-slate-600',
                        'assigned'             => 'bg-amber-100 text-amber-700',
                        'out_for_delivery'     => 'bg-blue-100 text-blue-700',
                        'partially_delivered'  => 'bg-violet-100 text-violet-700',
                        'completed'            => 'bg-emerald-100 text-emerald-700',
                        'cancelled'            => 'bg-red-100 text-red-700',
                        default                => 'bg-slate-100 text-slate-600',
                    },
                    'stops'        => $r->stops_count ?? 0,
                    'dispatched_at'=> $r->dispatched_at?->format('M d, Y h:i A') ?? '—',
                    'completed_at' => $r->completed_at?->format('M d, Y h:i A') ?? '—',
                    'created_at'   => $r->created_at->format('M d, Y h:i A'),
                    'view_url'     => route('admin.delivery-runs.show', $r->id),
                ])->values()->all();
            @endphp

            <div x-show="activeTab === 'sort_batches'" x-cloak
                 x-data="warehouseSortBatchesTable({{ Js::from($sortBatchesFlat) }})"
                 x-init="init()"
                 class="space-y-4 relative">
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" x-model="search" placeholder="Search sort batches..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @include('admin.warehouses._inventory_view_btn')
                        @include('admin.warehouses._inventory_export_btn')
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.batch_number" @@click="sort('batch_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">BATCH #<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='batch_number'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.direction"     class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DIRECTION</th>
                                <th x-show="visibleColumns.other_warehouse" @@click="sort('other_warehouse')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">OTHER WAREHOUSE<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='other_warehouse'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.dispatch_mode" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">MODE</th>
                                <th x-show="visibleColumns.status"        class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                <th x-show="visibleColumns.items"         @@click="sort('items')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center justify-center">ITEMS<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='items'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.sealed_at"     @@click="sort('sealed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">SEALED AT<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='sealed_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.created_at"    @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">CREATED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='created_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.actions"       class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr><td :colspan="visibleColumnCount()" class="px-4 py-10 text-center"><svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p class="text-sm text-slate-500" x-text="search ? 'No batches match your search' : 'No sort batches for this warehouse'"></p></td></tr>
                            </template>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.batch_number"   class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="row.batch_number"></td>
                                    <td x-show="visibleColumns.direction"      class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.direction_class" x-text="row.direction"></span></td>
                                    <td x-show="visibleColumns.other_warehouse" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.other_warehouse"></td>
                                    <td x-show="visibleColumns.dispatch_mode"  class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.dispatch_mode"></td>
                                    <td x-show="visibleColumns.status"         class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.status_badge_class" x-text="row.status"></span></td>
                                    <td x-show="visibleColumns.items"          class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700 text-center" x-text="row.items"></td>
                                    <td x-show="visibleColumns.sealed_at"      class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.sealed_at"></td>
                                    <td x-show="visibleColumns.created_at"     class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.created_at"></td>
                                    <td x-show="visibleColumns.actions"        class="px-4 py-2.5 whitespace-nowrap text-center"><a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View Details<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    @include('admin.warehouses._inventory_pagination', ['noun' => 'batches'])
                </div>
            </div>

            {{-- ── Manifests Tab ───────────────────────────────────────────────── --}}
            <div x-show="activeTab === 'manifests'" x-cloak
                 x-data="warehouseManifestsTable({{ Js::from($manifestsFlat) }})"
                 x-init="init()"
                 class="space-y-4 relative">
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" x-model="search" placeholder="Search manifests..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @include('admin.warehouses._inventory_view_btn')
                        @include('admin.warehouses._inventory_export_btn')
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.manifest_number" @@click="sort('manifest_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">MANIFEST #<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='manifest_number'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.direction"       class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">DIRECTION</th>
                                <th x-show="visibleColumns.other_warehouse" @@click="sort('other_warehouse')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">OTHER WAREHOUSE<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='other_warehouse'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.driver"          @@click="sort('driver')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">DRIVER<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='driver'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.status"          class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                <th x-show="visibleColumns.items"           @@click="sort('items')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center justify-center">ITEMS<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='items'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.dispatched_at"   @@click="sort('dispatched_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">DISPATCHED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='dispatched_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.arrived_at"      @@click="sort('arrived_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">ARRIVED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='arrived_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.created_at"      @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">CREATED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='created_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.actions"         class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr><td :colspan="visibleColumnCount()" class="px-4 py-10 text-center"><svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="text-sm text-slate-500" x-text="search ? 'No manifests match your search' : 'No transport manifests for this warehouse'"></p></td></tr>
                            </template>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.manifest_number" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="row.manifest_number"></td>
                                    <td x-show="visibleColumns.direction"       class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.direction_class" x-text="row.direction"></span></td>
                                    <td x-show="visibleColumns.other_warehouse" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.other_warehouse"></td>
                                    <td x-show="visibleColumns.driver"          class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.driver"></td>
                                    <td x-show="visibleColumns.status"          class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.status_badge_class" x-text="row.status"></span></td>
                                    <td x-show="visibleColumns.items"           class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700 text-center" x-text="row.items"></td>
                                    <td x-show="visibleColumns.dispatched_at"   class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.dispatched_at"></td>
                                    <td x-show="visibleColumns.arrived_at"      class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.arrived_at"></td>
                                    <td x-show="visibleColumns.created_at"      class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.created_at"></td>
                                    <td x-show="visibleColumns.actions"         class="px-4 py-2.5 whitespace-nowrap text-center"><a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View Details<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    @include('admin.warehouses._inventory_pagination', ['noun' => 'manifests'])
                </div>
            </div>

            {{-- ── Delivery Runs Tab ───────────────────────────────────────────── --}}
            <div x-show="activeTab === 'delivery_runs'" x-cloak
                 x-data="warehouseDeliveryRunsTable({{ Js::from($deliveryRunsFlat) }})"
                 x-init="init()"
                 class="space-y-4 relative">
                <div class="relative z-30 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" x-model="search" placeholder="Search delivery runs..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 focus:ring-2 focus:ring-slate-400/40 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400">
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @include('admin.warehouses._inventory_view_btn')
                        @include('admin.warehouses._inventory_export_btn')
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm relative z-10">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.run_number"   @@click="sort('run_number')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">RUN #<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='run_number'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.driver"       @@click="sort('driver')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">DRIVER<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='driver'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.status"       class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                <th x-show="visibleColumns.stops"        @@click="sort('stops')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">STOPS<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='stops'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.dispatched_at" @@click="sort('dispatched_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">DISPATCHED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='dispatched_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.completed_at"  @@click="sort('completed_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">COMPLETED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='completed_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.created_at"    @@click="sort('created_at')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center">CREATED<svg class="w-2.5 h-2.5 ml-1" :class="sortBy==='created_at'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                <th x-show="visibleColumns.actions"       class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr><td :colspan="visibleColumnCount()" class="px-4 py-10 text-center"><svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg><p class="text-sm text-slate-500" x-text="search ? 'No runs match your search' : 'No delivery runs for this warehouse'"></p></td></tr>
                            </template>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td x-show="visibleColumns.run_number"    class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="row.run_number"></td>
                                    <td x-show="visibleColumns.driver"        class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.driver"></td>
                                    <td x-show="visibleColumns.status"        class="px-4 py-2.5 whitespace-nowrap"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="row.status_badge_class" x-text="row.status"></span></td>
                                    <td x-show="visibleColumns.stops"         class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.stops"></td>
                                    <td x-show="visibleColumns.dispatched_at" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.dispatched_at"></td>
                                    <td x-show="visibleColumns.completed_at"  class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.completed_at"></td>
                                    <td x-show="visibleColumns.created_at"    class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-700" x-text="row.created_at"></td>
                                    <td x-show="visibleColumns.actions"       class="px-4 py-2.5 whitespace-nowrap text-center"><a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">View Details<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    @include('admin.warehouses._inventory_pagination', ['noun' => 'runs'])
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
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center shadow-lg shadow-teal-900/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Edit Warehouse</h3>
                                <p class="text-sm text-slate-500 mt-1">Update warehouse information and settings</p>
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
                <form @@submit.prevent="saveWarehouse()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Warehouse Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    x-model="form.name"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="Main Warehouse"
                                    required
                                >
                            </div>
                            <template x-if="errors.name">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        <!-- Code -->
                        <div class="grid grid-cols-1 gap-5">
                            <!-- Code -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Code <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.code"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="WH-001"
                                    >
                                </div>
                                <template x-if="errors.code">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.code[0]"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Address <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute top-2.5 left-0 pl-3 flex items-start pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <textarea
                                    x-model="form.address"
                                    rows="2"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none"
                                    placeholder="Full address of warehouse location"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Contact Phone & Email Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Contact Phone -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Contact Phone <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.contact_phone"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="+233 24 123 4567"
                                    >
                                </div>
                                <template x-if="errors.contact_phone">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.contact_phone[0]"></p>
                                </template>
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Contact Email <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="email"
                                        x-model="form.contact_email"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="warehouse@example.com"
                                    >
                                </div>
                                <template x-if="errors.contact_email">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.contact_email[0]"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Capacity -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Capacity (m&sup3;) <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <input
                                    type="number"
                                    x-model="form.capacity"
                                    min="0"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="500"
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
                                        <h4 class="text-sm font-bold text-slate-800">Warehouse Status</h4>
                                        <p class="text-xs text-slate-500" x-text="form.is_active ? 'Warehouse is operational' : 'Warehouse is inactive'"></p>
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
                         :class="warehouse.is_active ? 'bg-amber-100' : 'bg-emerald-100'">
                        <svg x-show="warehouse.is_active" class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <svg x-show="!warehouse.is_active" x-cloak class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900" x-text="warehouse.is_active ? 'Deactivate Warehouse?' : 'Activate Warehouse?'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        <span x-show="warehouse.is_active">
                            Are you sure you want to deactivate <strong x-text="warehouse.name"></strong>? The warehouse will be marked as non-operational.
                        </span>
                        <span x-show="!warehouse.is_active" x-cloak>
                            Are you sure you want to activate <strong x-text="warehouse.name"></strong>? The warehouse will be marked as operational again.
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
                        :class="warehouse.is_active
                            ? 'bg-amber-500 hover:bg-amber-600 text-white'
                            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
                    >
                        <svg x-show="toggling" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="toggling ? 'Processing...' : (warehouse.is_active ? 'Yes, Deactivate' : 'Yes, Activate')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection


