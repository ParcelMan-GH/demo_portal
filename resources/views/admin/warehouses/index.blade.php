@extends('admin.layouts.app')

@section('title', 'Warehouse Management')
@section('breadcrumb-parent', 'Management')
@section('breadcrumb-current', 'Warehouses')

@section('content')

@php
    $warehousesConfig = [
        'endpoint' => route('admin.warehouses.data'),
        'exportEndpoint' => route('admin.warehouses.export'),
        'storeEndpoint' => route('admin.warehouses.store'),
        'regionsEndpoint' => route('admin.warehouses.regions'),
        'districtsEndpointTemplate' => route('admin.warehouses.districts', ['region' => '__REGION__']),
        'showEndpointTemplate' => route('admin.warehouses.show.json', ['warehouse' => '__ID__']),
    ];
@endphp

<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" 
     x-data="warehousesTable()" 
     x-init="init()"
     data-warehouses-config="{{ json_encode($warehousesConfig) }}">

    {{-- Alert Toast --}}
    <div x-show="notice.message" x-cloak x-transition class="fixed right-6 bottom-6 z-[200] flex items-center gap-3 rounded-2xl border px-5 py-3 text-sm font-semibold shadow-2xl"
         :class="notice.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path x-show="!notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="notice.message"></span>
        <button type="button" class="ml-4 opacity-60 hover:opacity-100" @click="notice.message = ''">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- ═══════════ HEADER & ACTIONS ═══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Warehouse Management</h1>
            <p class="text-slate-500 text-sm mt-1">Manage HQ warehouse locations, capacity, contacts, and staff access.</p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('admin.roles.index') }}" class="px-5 py-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2Zm10-10V7a4 4 0 00-8 0v4"/></svg>
                Roles
            </a>
            @if(Auth::guard('admin')->user()->hasPermission('warehouses.create'))
            <button type="button" @click="openAddModal()" class="px-5 py-3 bg-[#ea580c] hover:bg-[#c2410c] text-white text-sm font-semibold rounded-xl shadow-md shadow-orange-500/20 transition-all active:scale-95 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Warehouse
            </button>
            @endif
        </div>
    </div>

    {{-- ═══════════ STATS CARDS ═══════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div @click="clearStatusFilter()" class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32 cursor-pointer hover:shadow-md transition-shadow">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Warehouses</span>
            <span class="text-4xl font-normal text-slate-900" x-text="meta.total || 0">0</span>
        </div>
        <div @click="setStatusFilter('active')" class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32 cursor-pointer hover:shadow-md transition-shadow">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active</span>
            <span class="text-4xl font-normal text-emerald-600" x-text="stats.active || 0">0</span>
        </div>
        <div @click="setStatusFilter('inactive')" class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32 cursor-pointer hover:shadow-md transition-shadow">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Inactive</span>
            <span class="text-4xl font-normal text-rose-600" x-text="stats.inactive || 0">0</span>
        </div>
        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Staff</span>
            <span class="text-4xl font-normal text-slate-900" x-text="stats.staff || 0">0</span>
        </div>
    </div>

    {{-- ═══════════ MAIN TABLE CONTAINER ═══════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        
        {{-- Search & Action Bar --}}
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @input="onSearch()" placeholder="Search name, code, phone..."
                       class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-all">
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <button type="button" @click="showFilters = !showFilters"
                        class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5"
                        :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    Filters
                </button>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                        View
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-48 bg-white border border-slate-200 rounded-xl shadow-lg p-2" style="display:none">
                        <template x-for="col in columns" :key="col.key">
                            <button type="button" @click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <span x-text="col.label"></span>
                                <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 002 3h12a3 3 0 002-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-lg p-1" style="display:none">
                        <button type="button" @click="exportData('csv'); open = false" class="w-full text-left px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 rounded-lg">CSV</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expanded Filter Panel --}}
        <div x-show="showFilters" x-transition class="p-4 bg-slate-50/80 border-b border-slate-100" style="display:none">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select x-model="filters.status" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Region</label>
                    <select x-model="filters.region_id" @change="onFilterRegionChange()" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All regions</option>
                        <template x-for="region in regions" :key="region.id">
                            <option :value="region.id" x-text="region.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">District</label>
                    <select x-model="filters.district_id" :disabled="!filters.region_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500 disabled:opacity-50">
                        <option value="">All districts</option>
                        <template x-for="district in filterDistricts" :key="district.id">
                            <option :value="district.id" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Capacity (m³)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" x-model="filters.capacity_min" placeholder="Min" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <span class="text-slate-400">-</span>
                        <input type="number" min="0" x-model="filters.capacity_max" placeholder="Max" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" @click="clearFilters()" class="px-4 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold border border-slate-200 rounded-xl bg-white shadow-sm transition-colors">Clear All</button>
                <button type="button" @click="loadData(1)" class="px-5 py-2 bg-orange-600 text-white rounded-xl text-xs font-semibold shadow-sm hover:bg-orange-700 transition-colors">Apply Filters</button>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto relative">
            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>

            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead class="bg-[#FFF8F3] border-y border-orange-100/60">
                    <tr>
                        <th x-show="visibleColumns.name" @click="sort('name')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Warehouse <span x-show="sortBy==='name'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.code" @click="sort('code')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700">Code <span x-show="sortBy==='code'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.region" class="px-6 py-3.5 text-xs font-semibold text-slate-700">Region</th>
                        <th x-show="visibleColumns.district" class="px-6 py-3.5 text-xs font-semibold text-slate-700">District</th>
                        <th x-show="visibleColumns.contact_phone" class="px-6 py-3.5 text-xs font-semibold text-slate-700">Contact</th>
                        <th x-show="visibleColumns.capacity" class="px-6 py-3.5 text-xs font-semibold text-slate-700">Capacity</th>
                        <th x-show="visibleColumns.users_count" @click="sort('users_count')" class="cursor-pointer px-6 py-3.5 text-xs font-semibold text-slate-700 text-center">Users <span x-show="sortBy==='users_count'" x-text="sortDirection==='asc'?'↑':'↓'"></span></th>
                        <th x-show="visibleColumns.is_active" class="px-6 py-3.5 text-xs font-semibold text-slate-700">Status</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-800 bg-white">
                    <template x-if="!loading && warehouses.length === 0">
                        <tr><td colspan="9" class="px-6 py-12 text-center text-slate-400">No warehouses found.</td></tr>
                    </template>

                    <template x-for="warehouse in warehouses" :key="warehouse.id">
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <td x-show="visibleColumns.name" class="px-6 py-4">
                                <a :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id" class="font-extrabold text-slate-900 hover:text-orange-700" x-text="warehouse.name"></a>
                                <p class="mt-1 line-clamp-1 text-[11px] font-medium text-slate-500" x-text="warehouse.address || 'No address set'"></p>
                            </td>
                            <td x-show="visibleColumns.code" class="px-6 py-4 font-mono font-medium text-slate-600" x-text="warehouse.code || '-'"></td>
                            <td x-show="visibleColumns.region" class="px-6 py-4 text-slate-700" x-text="warehouse.region || '-'"></td>
                            <td x-show="visibleColumns.district" class="px-6 py-4 text-slate-700" x-text="warehouse.district || '-'"></td>
                            <td x-show="visibleColumns.contact_phone" class="px-6 py-4">
                                <p class="text-slate-800" x-text="warehouse.contact_phone || '-'"></p>
                                <p class="text-slate-400 text-[10px] mt-0.5" x-text="warehouse.contact_email || ''"></p>
                            </td>
                            <td x-show="visibleColumns.capacity" class="px-6 py-4 text-slate-700" x-text="warehouse.capacity ? warehouse.capacity + ' m³' : '-'"></td>
                            <td x-show="visibleColumns.users_count" class="px-6 py-4 text-center font-bold text-slate-900" x-text="warehouse.users_count ?? 0"></td>
                            <td x-show="visibleColumns.is_active" class="px-6 py-4">
                                <span class="px-3 py-1 text-[11px] font-semibold rounded-full inline-block" 
                                      :class="warehouse.is_active ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-slate-100 border border-slate-200 text-slate-600'" 
                                      x-text="warehouse.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a :href="'{{ route('admin.warehouses.index') }}/' + warehouse.id" class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-800 transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <template x-if="warehouse.can_manage">
                                        <button type="button" @click="openEditModal(warehouse)" class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-orange-600 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    </template>
                                    <template x-if="warehouse.can_manage">
                                        <button type="button" @click="toggleWarehouseStatus(warehouse)" class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 transition-colors" :class="warehouse.is_active ? 'hover:text-amber-600' : 'hover:text-emerald-600'" :title="warehouse.is_active ? 'Deactivate' : 'Activate'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        </button>
                                    </template>
                                    <template x-if="warehouse.can_manage">
                                        <button type="button" @click="openDeleteModal(warehouse)" class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div class="px-6 py-4 bg-white border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
            <div>
                Showing <span x-text="meta.from || 1"></span> to <span x-text="meta.to || warehouses.length"></span> of <span x-text="meta.total || warehouses.length"></span> warehouses
            </div>
            <div class="flex items-center gap-1.5">
                <select x-model="perPage" @change="meta.current_page = 1; loadData()" class="mr-4 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-700 outline-none">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
                <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODALS ═══════════ --}}
    
    {{-- Add/Edit Modal --}}
    <template x-teleport="body">
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity @click.self="closeModal()" @keydown.escape.window="closeModal()">
            <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl relative overflow-hidden" @click.stop>
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900" x-text="modalMode === 'add' ? 'Add New Warehouse' : (modalMode === 'edit' ? 'Edit Warehouse' : 'View Warehouse')"></h3>
                            <p class="text-sm text-slate-500 mt-1" x-text="modalMode === 'add' ? 'Create a new warehouse location' : 'Update warehouse information and settings'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()" class="p-2 text-slate-400 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warehouse Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.name" :disabled="modalMode === 'view'" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500" placeholder="e.g., Main Hub">
                        <template x-if="errors.name"><p class="mt-1 text-xs text-rose-600" x-text="errors.name[0]"></p></template>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Code <span class="text-slate-400 font-normal">(Auto-generated if empty)</span></label>
                            <input type="text" x-model="form.code" :disabled="modalMode === 'view'" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500" placeholder="WH-001">
                            <template x-if="errors.code"><p class="mt-1 text-xs text-rose-600" x-text="errors.code[0]"></p></template>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Capacity (m³) <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="number" x-model="form.capacity" :disabled="modalMode === 'view'" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500" placeholder="500">
                            <template x-if="errors.capacity"><p class="mt-1 text-xs text-rose-600" x-text="errors.capacity[0]"></p></template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Address <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <textarea x-model="form.address" :disabled="modalMode === 'view'" rows="2" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500 resize-none" placeholder="Full address"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Region <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <select x-model="form.region_id" :disabled="modalMode === 'view'" @change="onRegionChange()" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500">
                                <option value="">Select region</option>
                                <template x-for="region in regions" :key="region.id">
                                    <option :value="region.id" x-text="region.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">District <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <select x-model="form.district_id" :disabled="modalMode === 'view' || !form.region_id || loadingDistricts" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:opacity-50">
                                <option value="">Select district</option>
                                <template x-for="district in formDistricts" :key="district.id">
                                    <option :value="district.id" x-text="district.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="text" x-model="form.contact_phone" :disabled="modalMode === 'view'" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500" placeholder="+233...">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Email <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="email" x-model="form.contact_email" :disabled="modalMode === 'view'" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-500" placeholder="warehouse@example.com">
                        </div>
                    </div>

                    <div x-show="modalMode !== 'view'" class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Warehouse Status</h4>
                            <p class="text-xs text-slate-500" x-text="form.is_active ? 'Warehouse is active and operational' : 'Warehouse is currently disabled'"></p>
                        </div>
                        <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-300'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                            <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 rounded-b-3xl">
                    <button type="button" @click="closeModal()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">
                        <span x-text="modalMode === 'view' ? 'Close' : 'Cancel'"></span>
                    </button>
                    <button x-show="modalMode !== 'view'" type="button" @click="saveWarehouse()" :disabled="saving" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="saving ? 'Saving...' : (modalMode === 'add' ? 'Create Warehouse' : 'Save Changes')"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Delete Modal --}}
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity @click.self="deleteModalOpen = false" @keydown.escape.window="deleteModalOpen = false">
            <div class="bg-white rounded-3xl max-w-sm w-full shadow-2xl overflow-hidden" @click.stop>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Delete Warehouse</h3>
                    <p class="text-sm text-slate-500">Are you sure you want to delete <span class="font-bold text-slate-800" x-text="warehouseToDelete?.name"></span>? This action cannot be undone.</p>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Cancel</button>
                    <button type="button" @click="confirmDelete()" :disabled="deleting" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                        <span x-text="deleting ? 'Deleting...' : 'Delete'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function warehousesTable() {
    return {
        config: {},
        warehouses: [],
        regions: [],
        filterDistricts: [],
        formDistricts: [],
        meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
        stats: { active: 0, inactive: 0, staff: 0 },
        loading: false,
        saving: false,
        deleting: false,
        loadingDistricts: false,
        
        search: '',
        perPage: 20,
        sortBy: 'name',
        sortDirection: 'asc',
        
        filters: {
            status: '',
            region_id: '',
            district_id: '',
            capacity_min: '',
            capacity_max: '',
            users_min: '',
            users_max: ''
        },
        
        showFilters: false,
        notice: { success: true, message: '' },
        _searchTimeout: null,
        
        columns: [
            { key: 'name', label: 'Warehouse' },
            { key: 'code', label: 'Code' },
            { key: 'region', label: 'Region' },
            { key: 'district', label: 'District' },
            { key: 'contact_phone', label: 'Contact' },
            { key: 'capacity', label: 'Capacity' },
            { key: 'users_count', label: 'Users' },
            { key: 'is_active', label: 'Status' },
            { key: 'actions', label: 'Actions' }
        ],
        visibleColumns: {
            name: true, code: true, region: true, district: true, contact_phone: true, 
            capacity: true, users_count: true, is_active: true, actions: true
        },

        showModal: false,
        modalMode: 'add', // 'add', 'edit', 'view'
        form: { id: null, name: '', code: '', address: '', region_id: '', district_id: '', contact_phone: '', contact_email: '', capacity: '', is_active: true },
        errors: {},

        deleteModalOpen: false,
        warehouseToDelete: null,

        init() {
            const el = this.$root;
            this.config = JSON.parse(el.dataset.warehousesConfig || '{}');
            this.loadRegions();
            this.loadData();
        },

        csrfToken() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; },

        async loadRegions() {
            try {
                const res = await fetch(this.config.regionsEndpoint, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.regions = json.data || [];
            } catch (e) { console.error('Failed to load regions'); }
        },

        async loadDistricts(regionId, targetList = 'filter') {
            if (!regionId) {
                if (targetList === 'filter') this.filterDistricts = [];
                if (targetList === 'form') this.formDistricts = [];
                return;
            }
            if (targetList === 'form') this.loadingDistricts = true;
            try {
                const url = this.config.districtsEndpointTemplate.replace('__REGION__', regionId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (targetList === 'filter') this.filterDistricts = json.data || [];
                if (targetList === 'form') this.formDistricts = json.data || [];
            } catch (e) {
                console.error('Failed to load districts');
            } finally {
                if (targetList === 'form') this.loadingDistricts = false;
            }
        },

        onFilterRegionChange() {
            this.filters.district_id = '';
            this.loadDistricts(this.filters.region_id, 'filter');
        },

        onRegionChange() {
            this.form.district_id = '';
            this.loadDistricts(this.form.region_id, 'form');
        },

        async loadData(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({
                page, per_page: this.perPage, sort: this.sortBy, direction: this.sortDirection
            });
            if (this.search) params.set('search', this.search);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.region_id) params.set('region_id', this.filters.region_id);
            if (this.filters.district_id) params.set('district_id', this.filters.district_id);
            if (this.filters.capacity_min) params.set('capacity_min', this.filters.capacity_min);
            if (this.filters.capacity_max) params.set('capacity_max', this.filters.capacity_max);
            if (this.filters.users_min) params.set('users_min', this.filters.users_min);
            if (this.filters.users_max) params.set('users_max', this.filters.users_max);

            try {
                const res = await fetch(`${this.config.endpoint}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.warehouses = json.data || [];
                this.meta = json.meta || this.meta;
                this.stats = json.stats || this.stats;
            } catch (e) {
                this.toast(false, 'Failed to load warehouses.');
            } finally {
                this.loading = false;
            }
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.loadData(1), 300);
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.loadData(1);
        },

        previousPage() { if(this.meta.current_page > 1) this.loadData(this.meta.current_page - 1); },
        nextPage() { if(this.meta.current_page < this.meta.last_page) this.loadData(this.meta.current_page + 1); },

        toggleColumn(key) { this.visibleColumns[key] = !this.visibleColumns[key]; },

        setStatusFilter(status) {
            this.filters.status = status;
            this.loadData(1);
        },
        clearStatusFilter() {
            this.filters.status = '';
            this.loadData(1);
        },

        applyFilters() { this.loadData(1); this.showFilters = false; },
        
        clearFilters() {
            this.filters = { status: '', region_id: '', district_id: '', capacity_min: '', capacity_max: '', users_min: '', users_max: '' };
            this.filterDistricts = [];
            this.loadData(1);
        },

        clearFilter(key) {
            this.filters[key] = '';
            if (key === 'region_id') this.filterDistricts = [];
            this.loadData(1);
        },

        activeFilterChips() {
            const chips = [];
            if (this.filters.status) chips.push({ key: 'status', label: `Status: ${this.filters.status}` });
            if (this.filters.region_id) chips.push({ key: 'region_id', label: `Region Filtered` });
            if (this.filters.capacity_min) chips.push({ key: 'capacity_min', label: `Min Capacity: ${this.filters.capacity_min}` });
            return chips;
        },

        toast(success, message) {
            this.notice = { success, message };
            if (success) setTimeout(() => { this.notice.message = ''; }, 4000);
        },

        // Modals
        openAddModal() {
            this.modalMode = 'add';
            this.form = { id: null, name: '', code: '', address: '', region_id: '', district_id: '', contact_phone: '', contact_email: '', capacity: '', is_active: true };
            this.errors = {};
            this.formDistricts = [];
            this.showModal = true;
        },

        openEditModal(warehouse) {
            this.modalMode = 'edit';
            this.form = { ...warehouse };
            this.errors = {};
            if (this.form.region_id) this.loadDistricts(this.form.region_id, 'form');
            else this.formDistricts = [];
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.errors = {};
        },

        async saveWarehouse() {
            if (this.modalMode === 'view') return;
            this.saving = true;
            this.errors = {};
            const isEdit = this.modalMode === 'edit';
            const url = isEdit ? this.config.showEndpointTemplate.replace('__ID__', this.form.id) : this.config.storeEndpoint;
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                
                if (!res.ok) {
                    if (res.status === 422) this.errors = json.errors || {};
                    else this.toast(false, json.message || 'Error saving warehouse.');
                } else {
                    this.toast(true, json.message || 'Warehouse saved successfully.');
                    this.closeModal();
                    this.loadData(this.meta.current_page);
                }
            } catch (e) {
                this.toast(false, 'Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async toggleWarehouseStatus(warehouse) {
            const url = this.config.showEndpointTemplate.replace('__ID__', warehouse.id) + '/status';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: JSON.stringify({ is_active: !warehouse.is_active })
                });
                const json = await res.json();
                if (res.ok) {
                    this.toast(true, json.message || 'Status updated.');
                    this.loadData(this.meta.current_page);
                } else {
                    this.toast(false, json.message || 'Failed to update status.');
                }
            } catch (e) {
                this.toast(false, 'Network error. Please try again.');
            }
        },

        openDeleteModal(warehouse) {
            this.warehouseToDelete = warehouse;
            this.deleteModalOpen = true;
        },

        async confirmDelete() {
            if (!this.warehouseToDelete) return;
            this.deleting = true;
            const url = this.config.showEndpointTemplate.replace('__ID__', this.warehouseToDelete.id);
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() }
                });
                const json = await res.json();
                if (res.ok) {
                    this.toast(true, json.message || 'Warehouse deleted.');
                    this.deleteModalOpen = false;
                    this.loadData(this.meta.current_page);
                } else {
                    this.toast(false, json.message || 'Failed to delete warehouse.');
                }
            } catch (e) {
                this.toast(false, 'Network error. Please try again.');
            } finally {
                this.deleting = false;
            }
        },

        exportData(format) {
            const params = new URLSearchParams({ format, sort: this.sortBy, direction: this.sortDirection });
            if (this.search) params.set('search', this.search);
            if (this.filters.status) params.set('status', this.filters.status);
            window.location.href = `${this.config.exportEndpoint}?${params.toString()}`;
        }
    };
}
</script>
@endpush