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
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-visible">
        <div class="border-b border-slate-200 px-4 sm:px-6 py-4 bg-slate-50/60">
            <div class="flex flex-wrap gap-2">
                <button type="button" @@click="activeTab = 'details'" :class="tabClass('details')" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all">
                    Warehouse Details
                </button>
                <button type="button" @@click="activeTab = 'users'" :class="tabClass('users')" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all">
                    Warehouse Users
                    <span class="rounded-full px-2 py-0.5 text-[10px] bg-slate-100 text-slate-600">{{ $warehouseUsers->count() }}</span>
                </button>
                <button type="button" @@click="activeTab = 'received_items'" :class="tabClass('received_items')" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all">
                    Received Items
                    <span class="rounded-full px-2 py-0.5 text-[10px] bg-slate-100 text-slate-600">{{ $receivedItems->count() }}</span>
                </button>
                <button type="button" @@click="activeTab = 'received_pickups'" :class="tabClass('received_pickups')" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all">
                    Received Pickups
                    <span class="rounded-full px-2 py-0.5 text-[10px] bg-slate-100 text-slate-600">{{ $receivedPickups->count() }}</span>
                </button>
                <button type="button" @@click="activeTab = 'pending_receipts'" :class="tabClass('pending_receipts')" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all">
                    Pending Receipts
                    <span class="rounded-full px-2 py-0.5 text-[10px] bg-slate-100 text-slate-600">{{ $pendingReceipts->count() }}</span>
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div x-show="activeTab === 'details'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Name</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->name }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Code</p>
                        <p class="text-sm font-medium text-slate-900 font-mono">{{ $warehouse->code ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Status</p>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $warehouse->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Capacity (m&sup3;)</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->capacity ? number_format($warehouse->capacity) . " m\u{00B3}" : '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Region</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->region->name ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">District</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->district->name ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Contact Phone</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->contact_phone ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Contact Email</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->contact_email ?? '-' }}</p>
                    </div>

                    <div class="space-y-1 md:col-span-2 lg:col-span-3">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Address</p>
                        <p class="text-sm font-medium text-slate-900 break-words">{{ $warehouse->address ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Created At</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->created_at->format('M d, Y \a\t h:i A') }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Last Updated</p>
                        <p class="text-sm font-medium text-slate-900">{{ $warehouse->updated_at->format('M d, Y \a\t h:i A') }}</p>
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
                                placeholder="Search users..."
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

                        <div x-data="{ open: false }" class="relative w-full sm:w-56">
                            <button
                                type="button"
                                @@click="open = !open"
                                class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors"
                            >
                                <span x-text="statusFilterName"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                @@click.away="open = false"
                                x-transition
                                class="absolute right-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl"
                                style="display: none;"
                            >
                                <button type="button"
                                        @@click="setStatusFilter('', 'All statuses'); open = false"
                                        class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                        :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">
                                    <svg x-show="statusFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>All statuses</span>
                                </button>
                                <button type="button"
                                        @@click="setStatusFilter('1', 'Active'); open = false"
                                        class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                        :class="statusFilter === '1' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                    <svg x-show="statusFilter === '1'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Active</span>
                                </button>
                                <button type="button"
                                        @@click="setStatusFilter('0', 'Inactive'); open = false"
                                        class="mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                        :class="statusFilter === '0' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                    <svg x-show="statusFilter === '0'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Inactive</span>
                                </button>
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

                <div class="overflow-hidden rounded-xl border border-slate-200/50 relative z-10">
                    <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10" style="display:none;"></div>
                    <table class="min-w-full divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
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
                        <tbody class="bg-transparent divide-y divide-slate-100/50">
                            <template x-if="users.length === 0 && !loading">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-gray-500 text-xs">No warehouse users found</td>
                                </tr>
                            </template>
                            <template x-for="user in users" :key="user.id">
                                <tr class="hover:bg-slate-50/70">
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
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Assign Role</label>
                                    <div class="space-y-2 max-h-40 overflow-y-auto border border-slate-200/70 rounded-xl p-3 bg-white/50">
                                        <template x-for="role in roles" :key="role.id">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio"
                                                       name="warehouse_user_role_id"
                                                       :value="Number(role.id)"
                                                       x-model.number="userForm.role_id"
                                                       class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300">
                                                <span class="ml-2 text-sm text-slate-700" x-text="role.name"></span>
                                            </label>
                                        </template>
                                    </div>
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

            <div x-show="activeTab === 'received_items'" x-cloak>
                @if($receivedItems->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No received item confirmations yet.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Confirmed At</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Shipment</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Item</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Qty</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Driver</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($receivedItems as $confirmation)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-700">{{ $confirmation->confirmed_at?->format('M d, Y h:i A') ?? '-' }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $confirmation->shipmentItem?->shipment?->shipment_number ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $confirmation->shipmentItem?->description ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $confirmation->confirmed_quantity }} / {{ $confirmation->expected_quantity }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $confirmation->pickupAssignment?->driver?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700 max-w-xs break-words">{{ $confirmation->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div x-show="activeTab === 'received_pickups'" x-cloak>
                @if($receivedPickups->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No pickups have been received at this warehouse yet.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Shipment</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Driver</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Arrived Warehouse</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Received At</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($receivedPickups as $pickup)
                                    @php
                                        $statusValue = is_object($pickup->status) ? $pickup->status->value : (string) $pickup->status;
                                        $statusLabel = is_object($pickup->status) && method_exists($pickup->status, 'label')
                                            ? $pickup->status->label()
                                            : ucwords(str_replace('_', ' ', (string) $statusValue));
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $pickup->shipment?->shipment_number ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $pickup->driver?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $pickup->arrived_warehouse_at?->format('M d, Y h:i A') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $pickup->received_at?->format('M d, Y h:i A') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700 max-w-xs break-words">{{ $pickup->receive_notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div x-show="activeTab === 'pending_receipts'" x-cloak>
                @if($pendingReceipts->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No pending receipts for this warehouse.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Shipment</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Driver</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Assigned At</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Arrived Warehouse</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($pendingReceipts as $pickup)
                                    @php
                                        $statusValue = is_object($pickup->status) ? $pickup->status->value : (string) $pickup->status;
                                        $statusLabel = is_object($pickup->status) && method_exists($pickup->status, 'label')
                                            ? $pickup->status->label()
                                            : ucwords(str_replace('_', ' ', (string) $statusValue));
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $pickup->shipment?->shipment_number ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $pickup->driver?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $pickup->assigned_at?->format('M d, Y h:i A') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $pickup->arrived_warehouse_at?->format('M d, Y h:i A') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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



