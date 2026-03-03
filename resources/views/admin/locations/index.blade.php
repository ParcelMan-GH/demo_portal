@extends('admin.layouts.app')

@section('title', 'Location Management')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Location Management')

@section('content')
<div x-data="locationManager()" x-init="init()">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Location Management</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage Ghana regions, districts, and towns used for shipment addresses.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openAddModal(activeTab)"
                    class="flex items-center gap-2 h-9 px-4 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl transition-all shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="addBtnLabel"></span>
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-5 bg-slate-100/80 p-1 rounded-xl w-fit">
        <button @click="switchTab('regions')"
                :class="activeTab === 'regions' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all">
            Regions
            <span x-text="'(' + regionStats.total + ')'" class="ml-1 text-xs opacity-60"></span>
        </button>
        <button @click="switchTab('districts')"
                :class="activeTab === 'districts' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all">
            Districts
            <span x-text="'(' + districtStats.total + ')'" class="ml-1 text-xs opacity-60"></span>
        </button>
        <button @click="switchTab('towns')"
                :class="activeTab === 'towns' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all">
            Towns / Cities
            <span x-text="'(' + townStats.total + ')'" class="ml-1 text-xs opacity-60"></span>
        </button>
    </div>

    {{-- ─── REGIONS TAB ──────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'regions'" x-cloak>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            {{-- Toolbar --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                <div class="relative flex-1 max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input x-model="regionSearch" @input.debounce.300ms="loadRegions()" type="text"
                           placeholder="Search regions..." class="w-full pl-8 pr-3 h-8 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300">
                </div>
                <button @click="loadRegions()" class="h-8 px-3 text-xs text-slate-500 hover:text-slate-800 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Region</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Code</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Districts</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Towns</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Status</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loadingRegions">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Loading...</td></tr>
                        </template>
                        <template x-if="!loadingRegions && regions.length === 0">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No regions found.</td></tr>
                        </template>
                        <template x-for="r in regions" :key="r.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-800" x-text="r.name"></td>
                                <td class="px-4 py-3 font-mono text-slate-500" x-text="r.code"></td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="filterDistricts(r)" class="text-primary-600 hover:underline font-medium" x-text="r.districts_count"></button>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-500" x-text="r.locations_count"></td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="r.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                          class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                          x-text="r.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditModal('regions', r)"
                                                class="h-7 px-2.5 text-[11px] font-medium text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">Edit</button>
                                        <button @click="toggleItem('regions', r)"
                                                :class="r.is_active ? 'text-amber-600 hover:bg-amber-50 border-amber-200' : 'text-emerald-600 hover:bg-emerald-50 border-emerald-200'"
                                                class="h-7 px-2.5 text-[11px] font-medium border rounded-lg transition-all"
                                                x-text="r.is_active ? 'Deactivate' : 'Activate'"></button>
                                        <button @click="deleteItem('regions', r)"
                                                class="h-7 px-2.5 text-[11px] font-medium text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg transition-all">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ─── DISTRICTS TAB ────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'districts'" x-cloak>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 flex-wrap">
                <div class="relative flex-1 min-w-[160px] max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input x-model="districtSearch" @input.debounce.300ms="loadDistricts()" type="text"
                           placeholder="Search districts..." class="w-full pl-8 pr-3 h-8 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300">
                </div>
                <select x-model="districtRegionFilter" @change="loadDistricts()"
                        class="h-8 px-2 pr-7 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">All Regions</option>
                    <template x-for="r in regions" :key="r.id">
                        <option :value="r.id" x-text="r.name"></option>
                    </template>
                </select>
                <button @click="loadDistricts()" class="h-8 px-3 text-xs text-slate-500 hover:text-slate-800 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">District</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Code</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Region</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Towns</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Status</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loadingDistricts">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Loading...</td></tr>
                        </template>
                        <template x-if="!loadingDistricts && districts.length === 0">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No districts found.</td></tr>
                        </template>
                        <template x-for="d in districts" :key="d.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-800" x-text="d.name"></td>
                                <td class="px-4 py-3 font-mono text-slate-500" x-text="d.code"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="d.region_name"></td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="filterTowns(d)" class="text-primary-600 hover:underline font-medium" x-text="d.locations_count"></button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="d.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                          class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                          x-text="d.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditModal('districts', d)"
                                                class="h-7 px-2.5 text-[11px] font-medium text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">Edit</button>
                                        <button @click="toggleItem('districts', d)"
                                                :class="d.is_active ? 'text-amber-600 hover:bg-amber-50 border-amber-200' : 'text-emerald-600 hover:bg-emerald-50 border-emerald-200'"
                                                class="h-7 px-2.5 text-[11px] font-medium border rounded-lg transition-all"
                                                x-text="d.is_active ? 'Deactivate' : 'Activate'"></button>
                                        <button @click="deleteItem('districts', d)"
                                                class="h-7 px-2.5 text-[11px] font-medium text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg transition-all">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ─── TOWNS TAB ────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'towns'" x-cloak>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 flex-wrap">
                <div class="relative flex-1 min-w-[160px] max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input x-model="townSearch" @input.debounce.300ms="loadTowns()" type="text"
                           placeholder="Search towns..." class="w-full pl-8 pr-3 h-8 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300">
                </div>
                <select x-model="townRegionFilter" @change="townDistrictFilter = ''; loadTownDistricts(); loadTowns()"
                        class="h-8 px-2 pr-7 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">All Regions</option>
                    <template x-for="r in regions" :key="r.id">
                        <option :value="r.id" x-text="r.name"></option>
                    </template>
                </select>
                <select x-model="townDistrictFilter" @change="loadTowns()"
                        class="h-8 px-2 pr-7 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">All Districts</option>
                    <template x-for="d in townFilterDistricts" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
                <button @click="loadTowns()" class="h-8 px-3 text-xs text-slate-500 hover:text-slate-800 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                <span class="ml-auto text-xs text-slate-400" x-text="townStats.total + ' total'"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Town / City</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Type</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">District</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Region</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Status</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loadingTowns">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Loading...</td></tr>
                        </template>
                        <template x-if="!loadingTowns && towns.length === 0">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No towns found. Add one or run the Ghana Towns seeder.</td></tr>
                        </template>
                        <template x-for="t in towns" :key="t.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-800" x-text="t.name"></td>
                                <td class="px-4 py-3">
                                    <span :class="{
                                        'bg-purple-50 text-purple-700 border-purple-100': t.type === 'city',
                                        'bg-blue-50 text-blue-700 border-blue-100': t.type === 'town',
                                        'bg-slate-50 text-slate-600 border-slate-200': t.type === 'suburb' || t.type === 'village'
                                    }" class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize" x-text="t.type"></span>
                                </td>
                                <td class="px-4 py-3 text-slate-600" x-text="t.district_name"></td>
                                <td class="px-4 py-3 text-slate-500" x-text="t.region_name"></td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="t.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                          class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                          x-text="t.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditModal('towns', t)"
                                                class="h-7 px-2.5 text-[11px] font-medium text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">Edit</button>
                                        <button @click="toggleItem('towns', t)"
                                                :class="t.is_active ? 'text-amber-600 hover:bg-amber-50 border-amber-200' : 'text-emerald-600 hover:bg-emerald-50 border-emerald-200'"
                                                class="h-7 px-2.5 text-[11px] font-medium border rounded-lg transition-all"
                                                x-text="t.is_active ? 'Deactivate' : 'Activate'"></button>
                                        <button @click="deleteItem('towns', t)"
                                                class="h-7 px-2.5 text-[11px] font-medium text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg transition-all">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <div x-show="townStats.total > townStats.limit" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                <span class="text-xs text-slate-500"
                      x-text="'Showing ' + (townStats.offset + 1) + '–' + Math.min(townStats.offset + townStats.limit, townStats.total) + ' of ' + townStats.total"></span>
                <div class="flex gap-2">
                    <button @click="townStats.offset = Math.max(0, townStats.offset - townStats.limit); loadTowns()"
                            :disabled="townStats.offset === 0"
                            class="h-7 px-3 text-xs border border-slate-200 rounded-lg disabled:opacity-40 hover:bg-slate-50 transition-all">Prev</button>
                    <button @click="townStats.offset += townStats.limit; loadTowns()"
                            :disabled="townStats.offset + townStats.limit >= townStats.total"
                            class="h-7 px-3 text-xs border border-slate-200 rounded-lg disabled:opacity-40 hover:bg-slate-50 transition-all">Next</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── ADD / EDIT MODAL ─────────────────────────────────────────────────── --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="closeModal()">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-100"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800" x-text="modalTitle"></h3>
                <button @click="closeModal()" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-5 py-4 space-y-4">
                {{-- Error --}}
                <div x-show="modalError" class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700" x-text="modalError"></div>

                {{-- Region form --}}
                <template x-if="modalType === 'regions'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Region Name <span class="text-rose-500">*</span></label>
                            <input x-model="form.name" type="text" placeholder="e.g. Greater Accra"
                                   class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Code <span class="text-rose-500">*</span></label>
                            <input x-model="form.code" type="text" placeholder="e.g. GAR" maxlength="20"
                                   class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300 uppercase">
                            <p class="mt-1 text-[10px] text-slate-400">Short unique code (auto-uppercased)</p>
                        </div>
                    </div>
                </template>

                {{-- District form --}}
                <template x-if="modalType === 'districts'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Region <span class="text-rose-500">*</span></label>
                            <select x-model="form.region_id" @change="loadModalDistricts()"
                                    class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="">Select region...</option>
                                <template x-for="r in regions" :key="r.id">
                                    <option :value="r.id" x-text="r.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">District Name <span class="text-rose-500">*</span></label>
                            <input x-model="form.name" type="text" placeholder="e.g. Accra Metropolitan"
                                   class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Code <span class="text-rose-500">*</span></label>
                            <input x-model="form.code" type="text" placeholder="e.g. GAR-AMA" maxlength="30"
                                   class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300 uppercase">
                        </div>
                    </div>
                </template>

                {{-- Town form --}}
                <template x-if="modalType === 'towns'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Region <span class="text-rose-500">*</span></label>
                            <select x-model="form.region_id" @change="loadModalDistricts()"
                                    class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="">Select region...</option>
                                <template x-for="r in regions" :key="r.id">
                                    <option :value="r.id" x-text="r.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">District <span class="text-rose-500">*</span></label>
                            <select x-model="form.district_id"
                                    class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="">Select district...</option>
                                <template x-for="d in modalDistricts" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Town / City Name <span class="text-rose-500">*</span></label>
                            <input x-model="form.name" type="text" placeholder="e.g. Kasoa"
                                   class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Type <span class="text-rose-500">*</span></label>
                            <select x-model="form.type"
                                    class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="town">Town</option>
                                <option value="city">City</option>
                                <option value="suburb">Suburb</option>
                                <option value="village">Village</option>
                            </select>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100">
                <button @click="closeModal()" class="h-9 px-4 text-xs font-medium text-slate-600 hover:text-slate-900 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                <button @click="saveModal()" :disabled="saving"
                        class="h-9 px-5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="saving ? 'Saving...' : (editingId ? 'Update' : 'Create')"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function locationManager() {
    return {
        activeTab: 'regions',
        addBtnLabel: 'Add Region',

        // Regions
        regions: [], loadingRegions: false, regionSearch: '',
        regionStats: { total: 0 },

        // Districts
        districts: [], loadingDistricts: false, districtSearch: '', districtRegionFilter: '',
        districtStats: { total: 0 },

        // Towns
        towns: [], loadingTowns: false, townSearch: '', townRegionFilter: '', townDistrictFilter: '',
        townFilterDistricts: [],
        townStats: { total: 0, limit: 100, offset: 0 },

        // Modal
        modalOpen: false, modalType: '', modalTitle: '', editingId: null,
        form: { name: '', code: '', region_id: '', district_id: '', type: 'town' },
        modalDistricts: [],
        modalError: '', saving: false,

        async init() {
            await this.loadRegions();
            await this.loadDistricts();
            await this.loadTowns();
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.addBtnLabel = tab === 'regions' ? 'Add Region' : tab === 'districts' ? 'Add District' : 'Add Town';
        },

        // ─── Regions ───────────────────────────────────────────────────────────
        async loadRegions() {
            this.loadingRegions = true;
            try {
                const params = new URLSearchParams();
                if (this.regionSearch) params.set('search', this.regionSearch);
                const res = await this._fetch(`/admin/locations-data/regions?${params}`);
                this.regions = res.data.regions;
                this.regionStats.total = res.data.total;
            } finally { this.loadingRegions = false; }
        },

        filterDistricts(region) {
            this.districtRegionFilter = String(region.id);
            this.switchTab('districts');
            this.loadDistricts();
        },

        // ─── Districts ─────────────────────────────────────────────────────────
        async loadDistricts() {
            this.loadingDistricts = true;
            try {
                const params = new URLSearchParams();
                if (this.districtSearch) params.set('search', this.districtSearch);
                if (this.districtRegionFilter) params.set('region_id', this.districtRegionFilter);
                const res = await this._fetch(`/admin/locations-data/districts?${params}`);
                this.districts = res.data.districts;
                this.districtStats.total = res.data.total;
            } finally { this.loadingDistricts = false; }
        },

        filterTowns(district) {
            this.townRegionFilter = String(district.region_id);
            this.townDistrictFilter = String(district.id);
            this.switchTab('towns');
            this.loadTownDistricts();
            this.loadTowns();
        },

        // ─── Towns ─────────────────────────────────────────────────────────────
        async loadTowns() {
            this.loadingTowns = true;
            try {
                const params = new URLSearchParams();
                if (this.townSearch) params.set('search', this.townSearch);
                if (this.townRegionFilter) params.set('region_id', this.townRegionFilter);
                if (this.townDistrictFilter) params.set('district_id', this.townDistrictFilter);
                params.set('limit', this.townStats.limit);
                params.set('offset', this.townStats.offset);
                const res = await this._fetch(`/admin/locations-data/towns?${params}`);
                this.towns = res.data.towns;
                this.townStats.total  = res.data.total;
                this.townStats.limit  = res.data.limit;
            } finally { this.loadingTowns = false; }
        },

        async loadTownDistricts() {
            if (!this.townRegionFilter) { this.townFilterDistricts = []; return; }
            const params = new URLSearchParams({ region_id: this.townRegionFilter });
            const res = await this._fetch(`/admin/locations-data/districts?${params}`);
            this.townFilterDistricts = res.data.districts;
        },

        // ─── Modal ─────────────────────────────────────────────────────────────
        openAddModal(type) {
            this.modalType  = type;
            this.editingId  = null;
            this.modalError = '';
            this.form = { name: '', code: '', region_id: '', district_id: '', type: 'town' };
            this.modalDistricts = [];
            this.modalTitle = type === 'regions' ? 'Add New Region' : type === 'districts' ? 'Add New District' : 'Add New Town';
            this.modalOpen  = true;
        },

        openEditModal(type, item) {
            this.modalType  = type;
            this.editingId  = item.id;
            this.modalError = '';
            this.modalDistricts = [];
            this.modalTitle = type === 'regions' ? 'Edit Region' : type === 'districts' ? 'Edit District' : 'Edit Town';
            if (type === 'regions') {
                this.form = { name: item.name, code: item.code, region_id: '', district_id: '', type: 'town' };
            } else if (type === 'districts') {
                this.form = { name: item.name, code: item.code, region_id: String(item.region_id), district_id: '', type: 'town' };
                this.loadModalDistricts();
            } else {
                this.form = { name: item.name, code: '', region_id: String(item.region_id), district_id: String(item.district_id), type: item.type };
                this.loadModalDistricts();
            }
            this.modalOpen = true;
        },

        closeModal() { this.modalOpen = false; this.modalError = ''; },

        async loadModalDistricts() {
            if (!this.form.region_id) { this.modalDistricts = []; return; }
            const res = await this._fetch(`/admin/locations-data/districts?region_id=${this.form.region_id}`);
            this.modalDistricts = res.data.districts;
        },

        async saveModal() {
            this.saving = true; this.modalError = '';
            try {
                let url, method;
                if (this.editingId) {
                    url = `/admin/locations/${this.modalType === 'regions' ? 'regions' : this.modalType === 'districts' ? 'districts' : 'towns'}/${this.editingId}`;
                    method = 'PUT';
                } else {
                    url = `/admin/locations/${this.modalType === 'regions' ? 'regions' : this.modalType === 'districts' ? 'districts' : 'towns'}`;
                    method = 'POST';
                }

                const payload = {};
                if (this.modalType === 'regions') {
                    payload.name = this.form.name;
                    payload.code = this.form.code;
                } else if (this.modalType === 'districts') {
                    payload.name = this.form.name;
                    payload.code = this.form.code;
                    payload.region_id = this.form.region_id;
                } else {
                    payload.name = this.form.name;
                    payload.type = this.form.type;
                    payload.district_id = this.form.district_id;
                }

                const res = await this._fetch(url, { method, body: JSON.stringify(payload) });
                if (!res.success) { this.modalError = res.message || 'Failed to save.'; return; }

                this.closeModal();
                this._toast(res.message, 'success');
                if (this.modalType === 'regions') this.loadRegions();
                else if (this.modalType === 'districts') { this.loadDistricts(); this.loadRegions(); }
                else { this.loadTowns(); this.loadDistricts(); }
            } catch (e) {
                this.modalError = e.message || 'An error occurred.';
            } finally { this.saving = false; }
        },

        // ─── Toggle / Delete ───────────────────────────────────────────────────
        async toggleItem(type, item) {
            const url = `/admin/locations/${type === 'regions' ? 'regions' : type === 'districts' ? 'districts' : 'towns'}/${item.id}/toggle`;
            const res = await this._fetch(url, { method: 'PATCH' });
            if (res.success) {
                item.is_active = res.data.is_active;
                this._toast(res.message, 'success');
            } else {
                this._toast(res.message || 'Failed.', 'error');
            }
        },

        async deleteItem(type, item) {
            const label = type === 'regions' ? item.name + ' (Region)' : type === 'districts' ? item.name + ' (District)' : item.name;
            if (!confirm(`Delete "${label}"? This cannot be undone.`)) return;
            const url = `/admin/locations/${type === 'regions' ? 'regions' : type === 'districts' ? 'districts' : 'towns'}/${item.id}`;
            const res = await this._fetch(url, { method: 'DELETE' });
            if (res.success) {
                this._toast(res.message, 'success');
                if (type === 'regions') this.loadRegions();
                else if (type === 'districts') { this.loadDistricts(); this.loadRegions(); }
                else { this.loadTowns(); this.loadDistricts(); }
            } else {
                this._toast(res.message || 'Cannot delete.', 'error');
            }
        },

        // ─── Helpers ───────────────────────────────────────────────────────────
        async _fetch(url, options = {}) {
            const res = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                ...options,
            });
            const data = await res.json();
            if (!res.ok && res.status === 422 && data.errors) {
                const first = Object.values(data.errors)[0];
                throw new Error(Array.isArray(first) ? first[0] : first);
            }
            if (!res.ok) throw new Error(data.message || 'Request failed');
            return data;
        },

        _toast(message, type = 'success') {
            const container = document.getElementById('admin-toast-container');
            if (!container) return;
            const el = document.createElement('div');
            el.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-xs font-medium transition-all ${type === 'success' ? 'bg-white border-emerald-100 text-emerald-800' : 'bg-white border-rose-100 text-rose-700'}`;
            el.innerHTML = `<span class="w-2 h-2 rounded-full flex-shrink-0 ${type === 'success' ? 'bg-emerald-400' : 'bg-rose-400'}"></span><span>${message}</span>`;
            container.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        },
    };
}
</script>
@endpush
